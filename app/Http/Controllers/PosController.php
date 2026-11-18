<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\Setting;
use App\Models\User;
use App\Mail\SaleReceipt;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PosController extends Controller
{
    /**
     * Display the POS interface
     */
    public function index()
    {
        $this->authorize('view pos');

        $customers = Customer::orderBy('first_name')->get();
        $services = Service::where('is_active', true)->get();
        $servicePackages = ServicePackage::where('is_active', true)
            ->get();
        $staff = User::withStaffRole()->get();

        // Get business hours from settings (the Weekly Schedule configured in
        // Settings > Business — the single source of truth for booking hours)
        $businessHours = app(\App\Services\BusinessHoursService::class)->getWeeklyHoursForBooking();

        // Get POS-specific settings
        $currencySymbol = Setting::get('payment.currency_symbol', '$');
        $paymentMethods = Setting::get('payment.methods', ['cash', 'card']);
        $taxRate = Setting::get('payment.tax_rate', 0);

        // POS Settings for JavaScript
        $posSettings = [
            'currencySymbol' => Setting::get('payment.currency_symbol', '$'),
            'currencyCode' => Setting::get('payment.currency_code', 'USD'),
            'taxRate' => Setting::get('payment.tax_rate', 0),
            'businessName' => Setting::get('business.name', 'Business'),
            'businessTagline' => Setting::get('business.tagline', ''),
            'businessLogo' => Setting::get('business.logo') ? Storage::url(Setting::get('business.logo')) : null,
            'businessAddress' => Setting::get('business.address', ''),
            'quickAmountsMode' => Setting::get('payment.pos.quick_amounts_mode', 'smart'),
            'quickAmountsFixed' => Setting::get('payment.pos.quick_amounts_fixed', [20, 50, 100]),
            'quickAmountsPercentages' => Setting::get('payment.pos.quick_amounts_percentages', [105, 110, 120]),
            'soundEnabled' => Setting::get('payment.pos.enable_sound_effects', true),
            'maxPaymentAmount' => Setting::get('payment.pos.max_payment_amount', 100000),
            'requireReference' => [
                'card' => Setting::get('payment.pos.require_reference_card', true),
                'check' => Setting::get('payment.pos.require_reference_check', true),
                'bank_transfer' => Setting::get('payment.pos.require_reference_bank_transfer', true),
                'mobile' => Setting::get('payment.pos.require_reference_mobile', true),
            ]
        ];

        return view('pos.index', compact(
            'customers',
            'services',
            'servicePackages',
            'staff',
            'businessHours',
            'currencySymbol',
            'paymentMethods',
            'taxRate',
            'posSettings'
        ));
    }

    /**
     * Create a new sale
     */
    public function store(Request $request)
    {
        if (!Gate::any(['create pos transactions', 'process transactions'])) {
            abort(403, 'Unauthorized action.');
        }

        // Get available payment methods from settings
        $availableMethods = Setting::get('payment.methods', ['cash', 'card']) ?? ['cash', 'card'];
        if (!is_array($availableMethods)) $availableMethods = ['cash', 'card'];

        // Support both legacy single-payment and new split-payment format
        $hasSplitPayments = $request->has('payments') && is_array($request->payments) && count($request->payments) > 0;

        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'staff_id' => 'nullable|exists:users,id',
            'items' => 'required|array|min:1|max:100',
            'items.*.type' => 'required|in:service,package',
            'items.*.id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.price' => 'nullable|numeric|min:0|max:999999',
            // Split payments validation
            'payments' => 'required_if:payment_method,null|array|min:1|max:10',
            'payments.*.method' => 'required|in:' . implode(',', $availableMethods),
            // A coupon can cover the whole sale, in which case the till legitimately takes
            // nothing. The sum-vs-total check below still refuses a short payment.
            'payments.*.amount' => 'required|numeric|min:0|max:' . Setting::get('payment.pos.max_payment_amount', 100000),
            'payments.*.reference' => 'nullable|string|max:100|regex:/^[A-Za-z0-9\-_#]+$/',
            'payments.*.notes' => 'nullable|string|max:500',
            // Legacy single payment (backward compat)
            'payment_method' => 'required_without:payments|nullable|in:' . implode(',', $availableMethods),
            'amount_received' => 'required_without:payments|nullable|numeric|min:0',
            'payment_reference' => 'nullable|string|max:100|regex:/^[A-Za-z0-9\-_#]+$/',
            'payment_notes' => 'nullable|string|max:500',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percent',
            'coupon_discount_amount' => 'nullable|numeric|min:0',
            'applied_coupons' => 'nullable|array',
            'applied_coupons.*.id' => 'required|integer|exists:coupons,id',
            'applied_coupons.*.code' => 'required|string|max:50',
            'applied_coupons.*.discount_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            // Additional validation for customer existence if provided
            if ($request->customer_id) {
                $customer = Customer::find($request->customer_id);
                if (!$customer) {
                    throw new \Exception('Selected customer does not exist');
                }
            }

            // Get tax rate from settings
            $taxRate = Setting::get('payment.tax_rate', 0);
            if ($taxRate < 0 || $taxRate > 100) {
                throw new \Exception('Invalid tax rate configuration');
            }
            $taxRateDecimal = $taxRate / 100;

            // Only a user who manages the POS may override a catalogue price.
            $mayOverridePrice = auth()->user()->can('manage pos');
            $priceOverrides = [];

            // Calculate totals and validate items exist and are active
            $subtotal = 0;

            foreach ($request->items as $item) {
                if ($item['type'] === 'service') {
                    $service = Service::where('id', $item['id'])
                        ->where('is_active', true)
                        ->first();

                    if (!$service) {
                        throw new \Exception('Service with ID ' . $item['id'] . ' does not exist or is not active');
                    }

                    // The catalogue price is authoritative; an override needs POS
                    // management rights and is recorded for the audit trail.
                    $price = (float) $service->price;
                    if ($mayOverridePrice && isset($item['price']) && (float) $item['price'] !== $price) {
                        $price = (float) $item['price'];
                        $priceOverrides[] = ['type' => 'service', 'id' => $service->id, 'catalogue' => (float) $service->price, 'charged' => $price];
                    }

                    // Validate that price is not negative
                    if ($price < 0) {
                        throw new \Exception('Item price cannot be negative');
                    }

                    $subtotal += ($price * $item['quantity']);
                } elseif ($item['type'] === 'package') {
                    $package = ServicePackage::where('id', $item['id'])
                        ->where('is_active', true)
                        ->first();

                    if (!$package) {
                        throw new \Exception('Service package with ID ' . $item['id'] . ' does not exist, is not active, or is not available for sale');
                    }

                    // Catalogue price is authoritative here too (see services above).
                    $price = (float) $package->price;
                    if ($mayOverridePrice && isset($item['price']) && (float) $item['price'] !== $price) {
                        $price = (float) $item['price'];
                        $priceOverrides[] = ['type' => 'package', 'id' => $package->id, 'catalogue' => (float) $package->price, 'charged' => $price];
                    }

                    // Validate that price is not negative
                    if ($price < 0) {
                        throw new \Exception('Item price cannot be negative');
                    }

                    $subtotal += ($price * $item['quantity']);
                } else {
                    throw new \Exception('Invalid item type: ' . $item['type']);
                }
            }

            // Calculate discount
            $discountAmount = 0;
            if ($request->has('discount_amount') && $request->discount_amount > 0) {
                if ($request->discount_type === 'percent') {
                     $discountAmount = $subtotal * ($request->discount_amount / 100);
                } else {
                     $discountAmount = $request->discount_amount;
                }
            }

            // Calculate coupon discount
            // Coupon discounts are recomputed from the coupons themselves. The amount the
            // browser sends is never trusted, and a coupon that is no longer valid stops
            // the sale instead of silently discounting it.
            $couponDiscountAmount = 0.0;
            $computedCouponDiscounts = [];

            foreach ($request->input('applied_coupons', []) as $appliedCoupon) {
                $coupon = \App\Models\Coupon::find($appliedCoupon['id'] ?? null);

                if (!$coupon) {
                    throw new \Exception('One of the applied coupons could not be found. Please remove it and try again.');
                }

                if (!$coupon->isActive()) {
                    throw new \Exception("Coupon {$coupon->code} is no longer active.");
                }

                if (!$coupon->hasRemainingUses()) {
                    throw new \Exception("Coupon {$coupon->code} has reached its usage limit.");
                }

                if ($subtotal < (float) $coupon->minimum_purchase_amount) {
                    throw new \Exception("Coupon {$coupon->code} requires a minimum purchase of " . number_format((float) $coupon->minimum_purchase_amount, 2) . '.');
                }

                $discountForCoupon = app(\App\Services\CouponService::class)
                    ->calculateDiscount($coupon, max(0, $subtotal - $couponDiscountAmount));

                $computedCouponDiscounts[$coupon->id] = $discountForCoupon;
                $couponDiscountAmount += $discountForCoupon;
            }

            // Calculate taxable amount (subtotal - discount - coupon discount)
            // Ensure taxable amount doesn't go below zero
            if (($discountAmount + $couponDiscountAmount) > $subtotal) {
                throw new \Exception('The discount cannot be more than the sale subtotal.');
            }

            $taxableAmount = max(0, $subtotal - $discountAmount - $couponDiscountAmount);

            $taxAmount = $taxableAmount * $taxRateDecimal;
            $totalAmount = $taxableAmount + $taxAmount;

            // Calculate total amount received (sum of split payments or single amount)
            $totalReceived = $hasSplitPayments
                ? array_sum(array_column($request->payments, 'amount'))
                : $request->amount_received;

            // Validate that total received is sufficient
            if ($totalReceived < $totalAmount) {
                throw new \Exception('Total payment amount is less than total amount. Required: $' . number_format($totalAmount, 2) . ', Received: $' . number_format($totalReceived, 2));
            }

            $changeAmount = max(0, $totalReceived - $totalAmount);

            // Determine which user ID to associate with the sale
            $userId = $request->staff_id ?? auth()->id();

            // Verify that the selected staff user is valid
            if ($request->staff_id) {
                $staffUser = User::find($request->staff_id);
                if (!$staffUser) {
                    throw new \Exception('Selected staff member does not exist');
                }

                // Check if the selected user has staff permissions
                if (!$staffUser->hasAnyRole(['administrator', 'staff'])) {
                    throw new \Exception('Selected user is not authorized as staff member');
                }
            }

            // Create sale record. The number is derived, not reserved, so two tills completing in
            // the same instant can land on the same one; MySQL does not abort the transaction on a
            // duplicate key, so recompute and retry rather than losing the sale.
            $saleAttributes = [
                'customer_id' => $request->customer_id,
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'coupon_discount_amount' => $couponDiscountAmount,
                'applied_coupon_ids' => array_column($request->input('applied_coupons', []), 'id'),
                'total_amount' => $totalAmount,
                'amount_paid' => $totalReceived,
                'change_amount' => $changeAmount,
                'status' => 'completed',
                'sale_type' => 'walk_in',
                'notes' => $request->notes,
                'sale_date' => now(),
            ];

            $sale = null;

            for ($attempt = 1; ; $attempt++) {
                try {
                    $sale = Sale::create($saleAttributes);
                    break;
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($attempt >= 5 || ! str_contains($e->getMessage(), 'sale_number')) {
                        throw $e;
                    }

                    usleep(25000 * $attempt);
                }
            }

            if (!empty($priceOverrides)) {
                \Log::warning('POS price override on sale', [
                    'sale_id' => $sale->id,
                    'user_id' => auth()->id(),
                    'overrides' => $priceOverrides,
                ]);
            }

            // Create coupon redemptions and link coupons to sale
            if ($request->has('applied_coupons') && !empty($request->applied_coupons)) {
                foreach ($request->applied_coupons as $couponData) {
                    $coupon = \App\Models\Coupon::find($couponData['id']);
                    if (!$coupon) {
                        throw new \Exception('Coupon with ID ' . $couponData['id'] . ' not found');
                    }

                    // Create redemption record
                    $redemption = \App\Models\CouponRedemption::create([
                        'coupon_id' => $coupon->id,
                        'sale_id' => $sale->id,
                        'customer_id' => $request->customer_id,
                        'redeemed_by_user_id' => auth()->id(),
                        'discount_amount' => $computedCouponDiscounts[$coupon->id] ?? 0,
                        'redeemed_at' => now(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);

                    // Link coupon to sale via sale_coupons
                    \App\Models\SaleCoupon::create([
                        'sale_id' => $sale->id,
                        'coupon_id' => $coupon->id,
                        'coupon_redemption_id' => $redemption->id,
                        'discount_amount' => $computedCouponDiscounts[$coupon->id] ?? 0,
                    ]);

                    // A value-based coupon keeps its balance for the next visit; spend the
                    // part this sale used, guarded against a concurrent spend of the same code.
                    if ($coupon->isValueBased()) {
                        app(\App\Services\CouponService::class)
                            ->consumeValue($coupon, (float) ($computedCouponDiscounts[$coupon->id] ?? 0));
                    }
                }
            }

            // Create sale items
            foreach ($request->items as $item) {
                if ($item['type'] === 'service') {
                    $service = Service::where('id', $item['id'])
                        ->where('is_active', true)
                        ->first();

                    if (!$service) {
                        throw new \Exception('Service with ID ' . $item['id'] . ' does not exist or is not active (during creation phase)');
                    }

                    // Use custom price if provided, otherwise use service price
                    $unitPrice = isset($item['price']) && $mayOverridePrice ? (float) $item['price'] : (float) $service->price;

                    // Validate that price is not negative
                    if ($unitPrice < 0) {
                        throw new \Exception('Item price cannot be negative');
                    }

                    $itemName = $service->name;
                } elseif ($item['type'] === 'package') {
                    $package = ServicePackage::where('id', $item['id'])
                        ->where('is_active', true)
                        ->first();

                    if (!$package) {
                        throw new \Exception('Service package with ID ' . $item['id'] . ' does not exist, is not active, or is not available for sale (during creation phase)');
                    }

                    // Use custom price if provided, otherwise use package price
                    $unitPrice = isset($item['price']) && $mayOverridePrice ? (float) $item['price'] : (float) $package->price;

                    // Validate that price is not negative
                    if ($unitPrice < 0) {
                        throw new \Exception('Item price cannot be negative');
                    }

                    $itemName = $package->name;
                } else {
                    throw new \Exception('Invalid item type: ' . $item['type'] . ' (during creation phase)');
                }

                $lineTotal = $unitPrice * $item['quantity'];

                $saleItem = $sale->items()->create([
                    'sellable_type' => $item['type'] === 'service' ? Service::class : ServicePackage::class,
                    'sellable_id' => $item['id'],
                    'item_name' => $itemName,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0, // Allow for future discount functionality
                    'tax_amount' => ($unitPrice * $item['quantity']) * $taxRateDecimal,
                    'line_total' => $lineTotal,
                ]);

                // If the item is a service package, create a service package sale record
                if ($item['type'] === 'package') {
                    $package = ServicePackage::where('id', $item['id'])
                        ->where('is_active', true)
                        ->first();

                    if (!$package) {
                        throw new \Exception('Service package with ID ' . $item['id'] . ' does not exist, is not active, or is not available for sale (for service package sale record)');
                    }

                    $saleItem->servicePackageSales()->create([
                        'service_package_id' => $item['id'],
                        'sessions_used' => 0,
                        'sessions_remaining' => $package->session_count,
                        'expires_at' => now()->addDays($package->validity_days),
                        'status' => 'active',
                    ]);
                }
            }

            // Record payment(s)
            if ($hasSplitPayments) {
                foreach ($request->payments as $pmt) {
                    $sale->payments()->create([
                        'payment_method' => $pmt['method'],
                        'amount' => $pmt['amount'],
                        'reference_number' => $pmt['reference'] ?? null,
                        'notes' => $pmt['notes'] ?? null,
                        'payment_date' => now(),
                    ]);
                }
            } else {
                // Legacy single payment
                $sale->payments()->create([
                    'payment_method' => $request->payment_method,
                    'amount' => $request->amount_received,
                    'reference_number' => $request->payment_reference ?? null,
                    'notes' => $request->payment_notes ?? null,
                    'payment_date' => now(),
                ]);
            }

            DB::commit();

            // Try to send receipt email
            if ($request->customer_id && isset($sale->customer->email) && $sale->customer->email) {
                try {
                    $provider = $this->getActiveEmailProvider();
                    if ($provider) {
                        $this->configureMailerForProvider($provider);
                        Mail::mailer('dynamic_smtp')
                            ->to($sale->customer->email)
                            ->send(new SaleReceipt($sale));
                    }
                } catch (\Exception $e) {
                    // Log error but don't fail the sale
                    \Log::error('Failed to send POS receipt email: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully.',
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'total_received' => $totalReceived,
                'change_amount' => $changeAmount,
                'payment_count' => $hasSplitPayments ? count($request->payments) : 1,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('POS Sale Error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            // A database failure is ours, not the cashier's. It used to print SQLSTATE and the
            // whole INSERT (with every bound value) into the till's error toast, which told the
            // cashier nothing and showed them the schema. The detail is in the log line above.
            $message = $e instanceof \Illuminate\Database\QueryException
                ? 'The sale could not be saved, so nothing was charged. Please try again — if it happens twice, tell a manager the time it failed.'
                : $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 500);
        }
    }

    /**
     * Search for services/packages
     */
    public function searchItems(Request $request)
    {
        $this->authorize('view pos');

        $query = $request->input('q');
        
        $services = Service::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->get();

        $packages = ServicePackage::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->get();

        return response()->json([
            'services' => $services,
            'packages' => $packages,
        ]);
    }
    
    /**
     * Get customer details via AJAX
     */
    public function getCustomerDetails($id)
    {
        $this->authorize('view customers');

        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.'
            ]);
        }

        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);
    }

    /**
     * Quick create customer via AJAX from POS
     */
    public function storeCustomer(Request $request)
    {
        // Authorization: needs the 'create customers' permission
        $this->authorize('create customers');

        // Normalise the mobile BEFORE validating: the table stores 94714335456, staff type
        // 0714335456. Unique checks must compare the value we are about to write.
        if ($request->filled('phone')) {
            $request->merge(['phone' => $this->formatPhoneNumberForStorage((string) $request->input('phone'))]);
        }

        // Validate request — messages are written for counter staff, not developers
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                function (string $attribute, $value, \Closure $fail) {
                    // withTrashed(): the phone unique index still holds soft-deleted customers,
                    // so a removed record is the usual reason a number is "already taken".
                    $existing = Customer::withTrashed()->where('phone', $value)->first();
                    if (!$existing) {
                        return;
                    }

                    $display = $this->localPhone($value);

                    if ($existing->trashed()) {
                        $fail(sprintf(
                            'Mobile %s was used by a customer record that has been removed (%s). Ask the manager to restore that customer, or enter a different number.',
                            $display,
                            $existing->full_name
                        ));

                        return;
                    }

                    $fail(sprintf(
                        'Mobile %s is already saved for %s. Choose %s from the Customer list instead of adding a new customer.',
                        $display,
                        $existing->full_name,
                        $existing->first_name
                    ));
                },
            ],
            'email' => 'nullable|email|unique:customers',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'joined_date' => 'nullable|date|before_or_equal:today',
        ], [
            'first_name.required' => "Please enter the customer's first name.",
            'last_name.required' => "Please enter the customer's last name.",
            'phone.required' => "Please enter the customer's mobile number.",
            'email.email' => "That email address does not look right — check it and try again.",
            'email.unique' => 'That email is already saved for another customer. Leave it blank or use a different address.',
            'joined_date.before_or_equal' => 'The joined date cannot be in the future.',
        ]);

        try {
            // Format phone number
            $validated['phone'] = $this->formatPhoneNumberForStorage($validated['phone']);

            // Default joined_date to today if not provided
            if (empty($validated['joined_date'])) {
                $validated['joined_date'] = now()->toDateString();
            }

            // Generate slug
            $validated['slug'] = \Illuminate\Support\Str::slug(
                $validated['first_name'] . '-' . $validated['last_name']
            );

            // Check for duplicate slug and make unique
            $originalSlug = $validated['slug'];
            $count = 1;
            while (Customer::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $count;
                $count++;
            }

            // Create customer
            $customer = Customer::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully.',
                'customer' => [
                    'id' => $customer->id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'full_name' => $customer->full_name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                ]
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Quick add customer DB error: ' . $e->getMessage());

            // Someone saved the same mobile between validation and insert
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'customers_phone_unique')) {
                return response()->json([
                    'success' => false,
                    'message' => 'That mobile number is already on another customer record (possibly one that was removed). Search the Customer list for it, or ask the manager to restore the removed customer.',
                    'errors' => [
                        'phone' => ['That mobile number is already on another customer record (possibly one that was removed). Search the Customer list for it, or ask the manager to restore the removed customer.'],
                    ],
                ], 422);
            }

            // Never surface SQL to the counter — say what happened in plain words
            return response()->json([
                'success' => false,
                'message' => 'We could not save this customer right now. The details were not lost — try again, and tell the manager if it keeps happening.',
                'errors' => [
                    'error' => ['We could not save this customer right now. Try again, and tell the manager if it keeps happening.'],
                ],
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Quick add customer error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'We could not save this customer right now. Try again, and tell the manager if it keeps happening.',
                'errors' => [
                    'error' => ['We could not save this customer right now. Try again, and tell the manager if it keeps happening.'],
                ],
            ], 500);
        }
    }

    /**
     * Display form of a stored number: 94714335456 -> 0714335456
     */
    private function localPhone($phone)
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone);

        if ($digits === '') {
            return '';
        }

        $countryCode = Setting::get('business.country_code', '94');

        if (strlen($digits) > 9 && str_starts_with($digits, $countryCode)) {
            return '0' . substr($digits, strlen($countryCode));
        }

        return $digits;
    }

    /**
     * Format phone number for storage
     */
    private function formatPhoneNumberForStorage($phone)
    {
        if (!$phone) {
            return $phone;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If phone starts with 0, assume it's local (replace with country code)
        if (strlen($phone) > 0 && $phone[0] === '0') {
            $countryCode = Setting::get('business.country_code', '94');
            $phone = $countryCode . substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Live search for services and packages
     */
    public function liveSearch(Request $request)
    {
        $this->authorize('view pos');  // Using specific POS permission

        $query = $request->input('q');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'services' => [],
                'packages' => []
            ]);
        }

        try {
            // Search services by name, description, price
            $services = Service::where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%")
                      ->orWhere('price', 'LIKE', "%{$query}%")
                      ->orWhere('duration', 'LIKE', "%{$query}%");
                })
                ->get();

            // Search service packages by name, description, price
            $packages = ServicePackage::where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%")
                      ->orWhere('price', 'LIKE', "%{$query}%")
                      ->orWhereRaw('CAST(price AS CHAR) LIKE ?', ["%{$query}%"]);
                })
                ->get();

            return response()->json([
                'success' => true,
                'services' => $services,
                'packages' => $packages
            ]);
        } catch (\Exception $e) {
            \Log::error('Live search error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during search',
                'services' => [],
                'packages' => []
            ], 500);
        }
    }

    /**
     * Show receipt for a sale
     */
    public function showReceipt(Sale $sale)
    {
        $this->authorize('view pos');

        // Eager load relationships
        $sale->load(['customer', 'user', 'items.sellable', 'payments']);

        // Get business info from settings
        $businessName = Setting::get('business.name', config('app.name'));
        $businessAddress = Setting::get('business.address', '');
        $businessPhone = Setting::get('business.phone', '');
        $businessEmail = Setting::get('business.email', '');
        $businessTagline = Setting::get('business.tagline', '');
        $businessLogo = Setting::get('business.logo');
        $businessWebsite = Setting::get('business.website', '');
        $businessFacebook = Setting::get('business.social.facebook', '');
        $businessInstagram = Setting::get('business.social.instagram', '');
        $businessTwitter = Setting::get('business.social.twitter', '');
        $businessLinkedin = Setting::get('business.social.linkedin', '');
        $businessCity = Setting::get('business.city', '');
        $businessState = Setting::get('business.state', '');
        $businessZip = Setting::get('business.zip', '');
        $refundPolicy = Setting::get('payment.refund_policy', '');
        $currencySymbol = Setting::get('payment.currency_symbol', '$');

        // Format phone for WhatsApp
        $whatsappPhone = null;
        if ($sale->customer) {
            $whatsappPhone = $this->formatPhoneForWhatsApp($sale->customer->phone);
        }

        return view('pos.receipt', compact(
            'sale',
            'businessName',
            'businessAddress',
            'businessPhone',
            'businessEmail',
            'businessTagline',
            'businessLogo',
            'businessWebsite',
            'businessFacebook',
            'businessInstagram',
            'businessTwitter',
            'businessLinkedin',
            'businessCity',
            'businessState',
            'businessZip',
            'refundPolicy',
            'currencySymbol',
            'whatsappPhone'
        ));
    }

    /**
     * Download receipt as PDF (generated on-the-fly, not saved)
     */
    public function downloadReceipt(Sale $sale)
    {
        $this->authorize('view pos');

        $sale->load(['customer', 'user', 'items.sellable', 'payments']);

        $businessName = Setting::get('business.name', config('app.name'));
        $businessAddress = Setting::get('business.address', '');
        $businessPhone = Setting::get('business.phone', '');
        $businessEmail = Setting::get('business.email', '');
        $businessTagline = Setting::get('business.tagline', '');
        $businessLogo = Setting::get('business.logo');
        $businessWebsite = Setting::get('business.website', '');
        $businessFacebook = Setting::get('business.social.facebook', '');
        $businessInstagram = Setting::get('business.social.instagram', '');
        $businessTwitter = Setting::get('business.social.twitter', '');
        $businessLinkedin = Setting::get('business.social.linkedin', '');
        $businessCity = Setting::get('business.city', '');
        $businessState = Setting::get('business.state', '');
        $businessZip = Setting::get('business.zip', '');
        $refundPolicy = Setting::get('payment.refund_policy', '');
        $currencySymbol = Setting::get('payment.currency_symbol', '$');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pos.receipt-pdf', compact(
            'sale',
            'businessName',
            'businessAddress',
            'businessPhone',
            'businessEmail',
            'businessTagline',
            'businessLogo',
            'businessWebsite',
            'businessFacebook',
            'businessInstagram',
            'businessTwitter',
            'businessLinkedin',
            'businessCity',
            'businessState',
            'businessZip',
            'refundPolicy',
            'currencySymbol'
        ))->setPaper([0, 0, 226.77, 841.89], 'portrait'); // 80mm width, flexible height

        $filename = "receipt-{$sale->sale_number}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Format phone number for WhatsApp (international format without +)
     *
     * @param string|null $phone
     * @return string|null
     */
    private function formatPhoneForWhatsApp($phone)
    {
        if (!$phone) {
            return null;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If phone is empty after cleaning, return null
        if (empty($phone)) {
            return null;
        }

        // Get country code from settings
        $countryCode = Setting::get('business.country_code', '94'); // Default: Sri Lanka
        $countryCode = ltrim($countryCode, '+'); // Remove + if present in setting

        // Handle different cases:
        // 1. If phone starts with country code, leave as is
        if (substr($phone, 0, strlen($countryCode)) === $countryCode) {
            return $phone;
        }

        // 2. If phone starts with 0, replace with country code
        if ($phone[0] === '0') {
            return $countryCode . substr($phone, 1);
        }

        // 3. If phone has 10 digits (common for local numbers), prepend country code
        if (strlen($phone) === 10 && $countryCode === '94') { // Sri Lankan format
            // Assume it's a mobile number like 771234567, prepend country code
            return $countryCode . $phone;
        }

        // 4. For numbers that appear to already be international format (>10 digits)
        //    assuming if it's more than 10 digits it might already be international
        if (strlen($phone) > 10) {
            // Check if it already has the country code
            $phoneWithoutCountryCode = substr($phone, 1); // Remove first digit to check

            if (substr($phone, 0, strlen($countryCode)) === $countryCode) {
                return $phone; // Already has country code
            }
        }

        // Default: assume it's a local number without country code prefix
        return $countryCode . $phone;
    }

    /**
     * Display POS transactions history
     */
    public function transactions()
    {
        if (!Gate::any(['view pos', 'view transactions'])) {
            abort(403, 'Unauthorized action.');
        }

        // Return all sales - let custom-table.js handle filtering/pagination
        $sales = Sale::with(['customer', 'user', 'items'])
            ->orderBy('sale_date', 'desc')
            ->get();

        $currencySymbol = \App\Models\Setting::get('payment.currency_symbol', '$');

        return view('pos.transactions', compact('sales', 'currencySymbol'));
    }

    /**
     * Show POS terminal with existing sale data loaded for editing.
     */
    public function edit(Sale $sale)
    {
        if (!Gate::any(['edit pos transactions', 'manage pos'])) {
            abort(403, 'Unauthorized action.');
        }

        $sale->load(['customer', 'user', 'items.sellable', 'payments', 'saleCoupons']);

        // Same data as index()
        $customers = Customer::orderBy('first_name')->get();
        $services = Service::where('is_active', true)->get();
        $servicePackages = ServicePackage::where('is_active', true)->get();
        $staff = User::withStaffRole()->get();
        $businessHours = app(\App\Services\BusinessHoursService::class)->getWeeklyHoursForBooking();
        $currencySymbol = Setting::get('payment.currency_symbol', '$');
        $paymentMethods = Setting::get('payment.methods', ['cash', 'card']);
        $taxRate = Setting::get('payment.tax_rate', 0);
        $posSettings = [
            'currencySymbol' => Setting::get('payment.currency_symbol', '$'),
            'currencyCode' => Setting::get('payment.currency_code', 'USD'),
            'taxRate' => Setting::get('payment.tax_rate', 0),
            'businessName' => Setting::get('business.name', 'Business'),
            'businessTagline' => Setting::get('business.tagline', ''),
            'businessLogo' => Setting::get('business.logo') ? Storage::url(Setting::get('business.logo')) : null,
            'businessAddress' => Setting::get('business.address', ''),
            'quickAmountsMode' => Setting::get('payment.pos.quick_amounts_mode', 'smart'),
            'quickAmountsFixed' => Setting::get('payment.pos.quick_amounts_fixed', [20, 50, 100]),
            'quickAmountsPercentages' => Setting::get('payment.pos.quick_amounts_percentages', [105, 110, 120]),
            'soundEnabled' => Setting::get('payment.pos.enable_sound_effects', true),
            'maxPaymentAmount' => Setting::get('payment.pos.max_payment_amount', 100000),
            'requireReference' => [
                'card' => Setting::get('payment.pos.require_reference_card', true),
                'check' => Setting::get('payment.pos.require_reference_check', true),
                'bank_transfer' => Setting::get('payment.pos.require_reference_bank_transfer', true),
                'mobile' => Setting::get('payment.pos.require_reference_mobile', true),
            ],
        ];

        // Build recyclable cart items from the sale's items
        $editCart = $sale->items->map(function ($item) {
            return [
                'id' => (string) $item->sellable_id,
                'type' => $item->sellable_type === \App\Models\Service::class ? 'service' : 'package',
                'name' => $item->item_name,
                'price' => (float) $item->unit_price,
                'quantity' => $item->quantity,
            ];
        })->values();

        // Detect discount type from stored values
        $editDiscountType = 'fixed';
        $editDiscountAmount = (float) $sale->discount_amount;
        if ($editDiscountAmount > 0 && $sale->subtotal > 0) {
            $pct = ($editDiscountAmount / $sale->subtotal) * 100;
            if (abs($pct - round($pct)) < 0.01) {
                $editDiscountType = 'percent';
                $editDiscountAmount = round($pct);
            }
        }

        // Coupon data
        $editCoupons = $sale->saleCoupons->map(function ($sc) {
            return [
                'id' => $sc->coupon_id,
                'code' => $sc->coupon?->code ?? '',
                'discount_amount' => (float) $sc->discount_amount,
            ];
        })->values();

        $editSale = [
            'id' => $sale->id,
            'sale_number' => $sale->sale_number,
            'customer_id' => $sale->customer_id,
            'staff_id' => $sale->user_id,
            'cart' => $editCart,
            'discount_type' => $editDiscountType,
            'discount_amount' => $editDiscountAmount,
            'applied_coupons' => $editCoupons,
            'notes' => $sale->notes,
            'subtotal' => (float) $sale->subtotal,
            'tax_amount' => (float) $sale->tax_amount,
            'total_amount' => (float) $sale->total_amount,
        ];

        return view('pos.index', compact(
            'customers', 'services', 'servicePackages', 'staff',
            'businessHours', 'currencySymbol', 'paymentMethods',
            'taxRate', 'posSettings', 'editSale'
        ));
    }

    /**
     * Update an existing sale — replace items, recalculate totals, adjust payments.
     */
    public function update(Request $request, Sale $sale)
    {
        // Catalogue prices stay authoritative on edit too; only a POS manager
        // may change what was charged.
        $mayOverridePrice = auth()->user()->can('manage pos');
        $priceOverrides = [];

        if (!Gate::any(['edit pos transactions', 'manage pos'])) {
            abort(403, 'Unauthorized action.');
        }

        // Same validation as store()
        $availableMethods = Setting::get('payment.methods', ['cash', 'card']) ?? ['cash', 'card'];
        if (!is_array($availableMethods)) $availableMethods = ['cash', 'card'];
        $hasSplitPayments = $request->has('payments') && is_array($request->payments) && count($request->payments) > 0;

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'staff_id' => 'nullable|exists:users,id',
            'items' => 'required|array|min:1|max:100',
            'items.*.type' => 'required|in:service,package',
            'items.*.id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.price' => 'nullable|numeric|min:0|max:999999',
            'payments' => 'required_if:payment_method,null|array|min:1|max:10',
            'payments.*.method' => 'required|in:' . implode(',', $availableMethods),
            'payments.*.amount' => 'required|numeric|min:0.01|max:' . Setting::get('payment.pos.max_payment_amount', 100000),
            'payments.*.reference' => 'nullable|string|max:100|regex:/^[A-Za-z0-9\-_#]+$/',
            'payments.*.notes' => 'nullable|string|max:500',
            'payment_method' => 'required_without:payments|nullable|in:' . implode(',', $availableMethods),
            'amount_received' => 'required_without:payments|nullable|numeric|min:0',
            'payment_reference' => 'nullable|string|max:100|regex:/^[A-Za-z0-9\-_#]+$/',
            'payment_notes' => 'nullable|string|max:500',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percent',
            'coupon_discount_amount' => 'nullable|numeric|min:0',
            'applied_coupons' => 'nullable|array',
            'applied_coupons.*.id' => 'required|integer|exists:coupons,id',
            'applied_coupons.*.code' => 'required|string|max:50',
            'applied_coupons.*.discount_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $taxRate = Setting::get('payment.tax_rate', 0);
            if ($taxRate < 0 || $taxRate > 100) {
                throw new \Exception('Invalid tax rate configuration');
            }
            $taxRateDecimal = $taxRate / 100;

            // Recalculate totals
            $subtotal = 0;
            foreach ($request->items as $item) {
                if ($item['type'] === 'service') {
                    $service = Service::where('id', $item['id'])->where('is_active', true)->first();
                    if (!$service) {
                        throw new \Exception('Service with ID ' . $item['id'] . ' does not exist or is not active');
                    }
                    $price = (float) $service->price;
                    if ($mayOverridePrice && isset($item['price']) && (float) $item['price'] !== $price) {
                        $price = (float) $item['price'];
                        $priceOverrides[] = ['type' => 'service', 'id' => $service->id, 'catalogue' => (float) $service->price, 'charged' => $price];
                    }
                } else {
                    $package = ServicePackage::where('id', $item['id'])->where('is_active', true)->first();
                    if (!$package) {
                        throw new \Exception('Package with ID ' . $item['id'] . ' does not exist or is not active');
                    }
                    $price = (float) $package->price;
                    if ($mayOverridePrice && isset($item['price']) && (float) $item['price'] !== $price) {
                        $price = (float) $item['price'];
                        $priceOverrides[] = ['type' => 'package', 'id' => $package->id, 'catalogue' => (float) $package->price, 'charged' => $price];
                    }
                }
                if ($price < 0) throw new \Exception('Item price cannot be negative');
                $subtotal += ($price * $item['quantity']);
            }

            // Discount
            $discountAmount = 0;
            if ($request->has('discount_amount') && $request->discount_amount > 0) {
                if ($request->discount_type === 'percent') {
                    $discountAmount = $subtotal * ($request->discount_amount / 100);
                } else {
                    $discountAmount = $request->discount_amount;
                }
            }

            // Recomputed from the coupons themselves — never taken from the request.
            $couponDiscountAmount = 0.0;
            $computedCouponDiscounts = [];

            foreach ($request->input('applied_coupons', []) as $appliedCoupon) {
                $coupon = \App\Models\Coupon::find($appliedCoupon['id'] ?? null);

                if (!$coupon) {
                    throw new \Exception('One of the applied coupons could not be found. Please remove it and try again.');
                }

                if (!$coupon->isActive()) {
                    throw new \Exception("Coupon {$coupon->code} is no longer active.");
                }

                if (!$coupon->hasRemainingUses()) {
                    throw new \Exception("Coupon {$coupon->code} has reached its usage limit.");
                }

                if ($subtotal < (float) $coupon->minimum_purchase_amount) {
                    throw new \Exception("Coupon {$coupon->code} requires a minimum purchase of " . number_format((float) $coupon->minimum_purchase_amount, 2) . '.');
                }

                $discountForCoupon = app(\App\Services\CouponService::class)
                    ->calculateDiscount($coupon, max(0, $subtotal - $couponDiscountAmount));

                $computedCouponDiscounts[$coupon->id] = $discountForCoupon;
                $couponDiscountAmount += $discountForCoupon;
            }

            if (($discountAmount + $couponDiscountAmount) > $subtotal) {
                throw new \Exception('The discount cannot be more than the sale subtotal.');
            }

            $taxableAmount = max(0, $subtotal - $discountAmount - $couponDiscountAmount);
            $taxAmount = $taxableAmount * $taxRateDecimal;
            $totalAmount = $taxableAmount + $taxAmount;

            $totalReceived = $hasSplitPayments
                ? array_sum(array_column($request->payments, 'amount'))
                : $request->amount_received;

            if ($totalReceived < $totalAmount) {
                throw new \Exception('Total payment amount is less than total amount. Required: ' . number_format($totalAmount, 2) . ', Received: ' . number_format($totalReceived, 2));
            }

            $changeAmount = max(0, $totalReceived - $totalAmount);
            $userId = $request->staff_id ?? auth()->id();

            if ($request->staff_id) {
                $staffUser = User::find($request->staff_id);
                if (!$staffUser) throw new \Exception('Selected staff member does not exist');
                if (!$staffUser->hasAnyRole(['administrator', 'staff'])) {
                    throw new \Exception('Selected user is not authorized as staff member');
                }
            }

            // --- Clear old data ---
            // Remove old items (service package sales cascade)
            $sale->items()->delete();
            // Remove old payments
            $sale->payments()->delete();
            // Remove old coupon redemptions and sale_coupon links
            $previousCouponIds = $sale->couponRedemptions()->pluck('coupon_id')->all();

            $sale->couponRedemptions()->delete();
            $sale->saleCoupons()->delete();

            // --- Update sale record ---
            $sale->update([
                'customer_id' => $request->customer_id,
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'coupon_discount_amount' => $couponDiscountAmount,
                'applied_coupon_ids' => array_column($request->input('applied_coupons', []), 'id'),
                'total_amount' => $totalAmount,
                'amount_paid' => $totalReceived,
                'change_amount' => $changeAmount,
                'status' => 'completed',
                'notes' => $request->notes,
            ]);

            if (!empty($priceOverrides)) {
                \Log::warning('POS price override on sale edit', [
                    'sale_id' => $sale->id,
                    'user_id' => auth()->id(),
                    'overrides' => $priceOverrides,
                ]);
            }

            // --- Recreate coupon redemptions ---
            if ($request->has('applied_coupons') && !empty($request->applied_coupons)) {
                foreach ($request->applied_coupons as $couponData) {
                    $coupon = \App\Models\Coupon::find($couponData['id']);
                    if (!$coupon) throw new \Exception('Coupon with ID ' . $couponData['id'] . ' not found');

                    $redemption = \App\Models\CouponRedemption::create([
                        'coupon_id' => $coupon->id,
                        'sale_id' => $sale->id,
                        'customer_id' => $request->customer_id,
                        'redeemed_by_user_id' => auth()->id(),
                        'discount_amount' => $computedCouponDiscounts[$coupon->id] ?? 0,
                        'redeemed_at' => now(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);

                    \App\Models\SaleCoupon::create([
                        'sale_id' => $sale->id,
                        'coupon_id' => $coupon->id,
                        'coupon_redemption_id' => $redemption->id,
                        'discount_amount' => $computedCouponDiscounts[$coupon->id] ?? 0,
                    ]);
                }
            }

            // A value coupon's balance follows its redemption rows, so rebuild it for every
            // coupon this edit touched — including one that was removed from the sale.
            $touchedCouponIds = array_unique(array_merge(
                $previousCouponIds ?? [],
                array_column($request->input('applied_coupons', []), 'id')
            ));

            foreach (\App\Models\Coupon::whereIn('id', $touchedCouponIds)->get() as $touchedCoupon) {
                if ($touchedCoupon->isValueBased()) {
                    $touchedCoupon->syncConsumedValue();
                }
            }

            // --- Recreate sale items ---
            foreach ($request->items as $item) {
                if ($item['type'] === 'service') {
                    $service = Service::where('id', $item['id'])->where('is_active', true)->first();
                    $unitPrice = isset($item['price']) && $mayOverridePrice ? (float) $item['price'] : (float) $service->price;
                    $itemName = $service->name;
                } else {
                    $package = ServicePackage::where('id', $item['id'])->where('is_active', true)->first();
                    $unitPrice = isset($item['price']) && $mayOverridePrice ? (float) $item['price'] : (float) $package->price;
                    $itemName = $package->name;
                }

                $lineTotal = $unitPrice * $item['quantity'];

                $saleItem = $sale->items()->create([
                    'sellable_type' => $item['type'] === 'service' ? Service::class : ServicePackage::class,
                    'sellable_id' => $item['id'],
                    'item_name' => $itemName,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0,
                    'tax_amount' => ($unitPrice * $item['quantity']) * $taxRateDecimal,
                    'line_total' => $lineTotal,
                ]);

                if ($item['type'] === 'package') {
                    $package = ServicePackage::where('id', $item['id'])->where('is_active', true)->first();
                    $saleItem->servicePackageSales()->create([
                        'service_package_id' => $item['id'],
                        'sessions_used' => 0,
                        'sessions_remaining' => $package->session_count,
                        'expires_at' => now()->addDays($package->validity_days),
                        'status' => 'active',
                    ]);
                }
            }

            // --- Recreate payments ---
            if ($hasSplitPayments) {
                foreach ($request->payments as $pmt) {
                    $sale->payments()->create([
                        'payment_method' => $pmt['method'],
                        'amount' => $pmt['amount'],
                        'reference_number' => $pmt['reference'] ?? null,
                        'notes' => $pmt['notes'] ?? null,
                        'payment_date' => now(),
                    ]);
                }
            } else {
                $sale->payments()->create([
                    'payment_method' => $request->payment_method,
                    'amount' => $request->amount_received,
                    'reference_number' => $request->payment_reference ?? null,
                    'notes' => $request->payment_notes ?? null,
                    'payment_date' => now(),
                ]);
            }

            // Refresh the loaded relationships
            $sale->load(['items', 'payments']);

            DB::commit();

            // Try sending receipt email
            if ($request->customer_id && isset($sale->customer->email) && $sale->customer->email) {
                try {
                    $provider = $this->getActiveEmailProvider();
                    if ($provider) {
                        $this->configureMailerForProvider($provider);
                        Mail::mailer('dynamic_smtp')
                            ->to($sale->customer->email)
                            ->send(new SaleReceipt($sale));
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to send POS receipt email: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Sale updated successfully.',
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'total_received' => $totalReceived,
                'change_amount' => $changeAmount,
                'payment_count' => $hasSplitPayments ? count($request->payments) : 1,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('POS Update Error: ' . $e->getMessage(), [
                'sale_id' => $sale->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating sale: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft-delete a sale.
     */
    public function destroySale(Sale $sale)
    {
        if (!Gate::any(['delete pos transactions', 'delete transactions', 'manage pos'])) {
            abort(403, 'Unauthorized action.');
        }

        // A value-based coupon's balance follows its redemption rows: deleting the sale
        // removes those rows, so put the unspent value back on the code.
        $touchedCouponIds = $sale->couponRedemptions()->pluck('coupon_id')->unique()->all();

        $sale->delete();

        foreach (\App\Models\Coupon::whereIn('id', $touchedCouponIds)->get() as $touchedCoupon) {
            if (! $touchedCoupon->isValueBased()) {
                continue;
            }

            // Sale::delete() is a soft delete, so the redemption rows survive it and the
            // balance would stay spent. Voiding the sale refunds the value it used: drop
            // those redemption rows and rebuild the balance from what is left.
            $touchedCoupon->redemptions()->where('sale_id', $sale->id)->delete();
            $touchedCoupon->syncConsumedValue();
        }

        return redirect()->route('pos.transactions')
            ->with('success', 'Sale deleted successfully.');
    }

}