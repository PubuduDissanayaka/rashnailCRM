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
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $staff = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['administrator', 'staff']);
        })->get();

        // Get business hours from settings
        $businessHours = Setting::get('business.hours', [
            'monday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'tuesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'wednesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'thursday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'friday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
            'saturday' => ['open' => '10:00', 'close' => '16:00', 'closed' => false],
            'sunday' => ['open' => null, 'close' => null, 'closed' => true],
        ]);

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
        $this->authorize('create pos transactions');

        // Get available payment methods from settings
        $availableMethods = Setting::get('payment.methods', ['cash', 'card']) ?? ['cash', 'card'];

        // Ensure it's an array (in case JSON decode fails)
        if (!is_array($availableMethods)) {
            $availableMethods = ['cash', 'card'];
        }

        // Additional validation: Check if payment method exists in settings
        if (!in_array($request->payment_method, $availableMethods)) {
            throw new \InvalidArgumentException('Invalid payment method selected');
        }

        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'staff_id' => 'nullable|exists:users,id',
            'items' => 'required|array|min:1|max:100', // Limit items in a single transaction
            'items.*.type' => 'required|in:service,package',
            'items.*.id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.price' => 'nullable|numeric|min:0|max:999999',
            'payment_method' => 'required|in:' . implode(',', $availableMethods),
            'amount_received' => [
                'required',
                'numeric',
                'min:0',
                'max:' . Setting::get('payment.pos.max_payment_amount', 100000)
            ],
            'payment_reference' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9\-_#]+$/',
                function ($attribute, $value, $fail) use ($request) {
                    $method = $request->payment_method;
                    $requireRef = false;

                    switch ($method) {
                        case 'card':
                            $requireRef = Setting::get('payment.pos.require_reference_card', true);
                            break;
                        case 'check':
                            $requireRef = Setting::get('payment.pos.require_reference_check', true);
                            break;
                        case 'bank_transfer':
                            $requireRef = Setting::get('payment.pos.require_reference_bank_transfer', true);
                            break;
                        case 'mobile':
                            $requireRef = Setting::get('payment.pos.require_reference_mobile', true);
                            break;
                    }

                    if ($requireRef && empty($value)) {
                        $fail("Reference number is required for {$method} payments.");
                    }
                },
            ],
            'payment_notes' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000|regex:/^[A-Za-z0-9\s\-_,.!?@#$%^&*()]+$/',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percent',
            'coupon_discount_amount' => 'nullable|numeric|min:0',
            'applied_coupons' => 'nullable|array',
            'applied_coupons.*.id' => 'required|integer|exists:coupons,id',
            'applied_coupons.*.code' => 'required|string|max:50',
            'applied_coupons.*.discount_amount' => 'required|numeric|min:0',
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

                    // Use custom price if provided, otherwise use service price
                    $price = isset($item['price']) ? $item['price'] : $service->price;

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

                    // Use custom price if provided, otherwise use package price
                    $price = isset($item['price']) ? $item['price'] : $package->price;

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
            $couponDiscountAmount = $request->input('coupon_discount_amount', 0);
            if ($couponDiscountAmount < 0) {
                throw new \Exception('Coupon discount amount cannot be negative');
            }

            // Calculate taxable amount (subtotal - discount - coupon discount)
            // Ensure taxable amount doesn't go below zero
            $taxableAmount = max(0, $subtotal - $discountAmount - $couponDiscountAmount);

            $taxAmount = $taxableAmount * $taxRateDecimal;
            $totalAmount = $taxableAmount + $taxAmount;

            // Validate that amount received is sufficient
            if ($request->amount_received < $totalAmount) {
                throw new \Exception('Amount received is less than total amount. Required: $' . number_format($totalAmount, 2) . ', Received: $' . number_format($request->amount_received, 2));
            }

            $changeAmount = max(0, $request->amount_received - $totalAmount);

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

            // Create sale record
            $sale = Sale::create([
                'customer_id' => $request->customer_id,
                'user_id' => $userId, // Associate with selected staff member or current user
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'coupon_discount_amount' => $couponDiscountAmount,
                'applied_coupon_ids' => array_column($request->input('applied_coupons', []), 'id'),
                'total_amount' => $totalAmount,
                'amount_paid' => $request->amount_received,
                'change_amount' => $changeAmount,
                'status' => 'completed',
                'sale_type' => 'walk_in',
                'notes' => $request->notes,
                'sale_date' => now(),
            ]);

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
                        'discount_amount' => $couponData['discount_amount'],
                        'redeemed_at' => now(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);

                    // Link coupon to sale via sale_coupons
                    \App\Models\SaleCoupon::create([
                        'sale_id' => $sale->id,
                        'coupon_id' => $coupon->id,
                        'coupon_redemption_id' => $redemption->id,
                        'discount_amount' => $couponData['discount_amount'],
                    ]);
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
                    $unitPrice = isset($item['price']) ? $item['price'] : $service->price;

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
                    $unitPrice = isset($item['price']) ? $item['price'] : $package->price;

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

            // Record the payment
            $sale->payments()->create([
                'payment_method' => $request->payment_method,
                'amount' => $request->amount_received,
                'reference_number' => $request->payment_reference ?? null,
                'notes' => $request->payment_notes ?? null,
                'payment_date' => now(),
            ]);

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
                'change_amount' => $changeAmount,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('POS Sale Error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing sale: ' . $e->getMessage(),
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
        // Authorization: Same as regular customer creation
        $this->authorize('create customers');

        // Validate request
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:customers',
            'email' => 'nullable|email|unique:customers',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'joined_date' => 'nullable|date|before_or_equal:today',
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
        } catch (\Exception $e) {
            \Log::error('Quick add customer error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer: ' . $e->getMessage()
            ], 500);
        }
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
        $currencySymbol = Setting::get('payment.currency_symbol', '$');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pos.receipt-pdf', compact(
            'sale',
            'businessName',
            'businessAddress',
            'businessPhone',
            'businessEmail',
            'businessTagline',
            'businessLogo',
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
        $this->authorize('view pos');

        // Return all sales - let custom-table.js handle filtering/pagination
        $sales = Sale::with(['customer', 'user', 'items'])
            ->orderBy('sale_date', 'desc')
            ->get();

        return view('pos.transactions', compact('sales'));
    }

}