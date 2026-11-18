<?php

namespace App\Http\Controllers;

use App\Http\Requests\CouponRequest;
use App\Models\Coupon;
use App\Models\CouponBatch;
use App\Models\CustomerGroup;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\ServicePackageCategory;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\CouponService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CouponController extends Controller
{
    protected $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
    }

    /**
     * Display a listing of the coupons.
     */
    public function index()
    {
        $this->authorize('view coupons');

        $coupons = Coupon::with(['batch', 'redemptions'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => Coupon::count(),
            'active' => Coupon::active()->count(),
            'expired' => Coupon::expired()->count(),
            'total_redemptions' => \App\Models\CouponRedemption::count(),
        ];

        $currencySymbol = \App\Models\Setting::get('payment.currency_symbol', 'Rs');

        return view('coupons.index', compact('coupons', 'stats', 'currencySymbol'));
    }

    /**
     * Show the form for creating a new coupon.
     */
    public function create()
    {
        $this->authorize('create coupons');

        $batches = CouponBatch::all();
        $customerGroups = CustomerGroup::active()->get();
        $locations = Location::all();
        $services = Service::active()->get();
        $servicePackages = ServicePackage::active()->get();
        $categories = ServicePackageCategory::all();

        $currencySymbol = \App\Models\Setting::get('payment.currency_symbol', 'Rs');

        return view('coupons.create', compact(
            'batches',
            'customerGroups',
            'locations',
            'services',
            'servicePackages',
            'categories',
            'currencySymbol'
        ));
    }

    /**
     * Store a newly created coupon in storage.
     */
    public function store(CouponRequest $request)
    {
        $this->authorize('create coupons');

        $data = $this->couponPayload($request);

        DB::beginTransaction();
        try {
            $coupon = Coupon::create($data);
            $this->syncCouponRelations($coupon, $request);

            DB::commit();

            return redirect()->route('coupons.index')
                ->with('success', 'Coupon ' . $coupon->code . ' created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Coupon create failed', [
                'code' => $request->input('code'),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()
                ->with('error', 'We could not save this coupon. Please check the details and try again.');
        }
    }

    /**
     * Columns that come straight from the validated coupon form.
     * `discount_value` is already mapped from the percentage/fixed input by CouponRequest.
     */
    private function couponPayload(CouponRequest $request): array
    {
        $data = $request->only([
            'code', 'name', 'description', 'type', 'discount_value', 'max_discount_amount',
            'minimum_purchase_amount', 'start_date', 'end_date', 'timezone',
            'total_usage_limit', 'per_customer_limit', 'stackable', 'active',
            'location_restriction_type', 'customer_eligibility_type', 'product_restriction_type',
            'metadata', 'batch_id',
        ]);

        // Columns that are NOT NULL with defaults in the database
        $data['minimum_purchase_amount'] = $data['minimum_purchase_amount'] ?? 0;
        $data['per_customer_limit'] = $data['per_customer_limit'] ?? 1;

        return $data;
    }

    /**
     * Rebuild the coupon's restriction links (customer groups, locations,
     * products/packages and categories). Used by both create and update so the
     * two paths can never store different shapes of data.
     */
    private function syncCouponRelations(Coupon $coupon, CouponRequest $request): void
    {
        $coupon->customerGroups()->sync($request->input('customer_groups') ?? []);
        $coupon->locations()->sync($request->input('locations') ?? []);
        $coupon->categories()->sync($request->input('categories') ?? []);

        // Morph relations share the coupon_products table; the morph type is set
        // automatically by attach(), so only the pivot attribute is passed.
        $coupon->products()->detach();
        $coupon->servicePackages()->detach();

        foreach ($request->input('products') ?? [] as $prefixedId) {
            if (! is_string($prefixedId)) {
                continue;
            }

            if (str_starts_with($prefixedId, 'service_')) {
                $coupon->products()->attach((int) str_replace('service_', '', $prefixedId), [
                    'restriction_type' => 'included',
                ]);
            } elseif (str_starts_with($prefixedId, 'package_')) {
                $coupon->servicePackages()->attach((int) str_replace('package_', '', $prefixedId), [
                    'restriction_type' => 'included',
                ]);
            }
        }
    }

    /**
     * Display the specified coupon.
     */
    public function show(Coupon $coupon)
    {
        $this->authorize('view coupons');

        $coupon->load([
            'batch',
            'customerGroups',
            'locations',
            'products',
            'categories',
            'redemptions.sale',
            'redemptions.customer',
        ]);

        $stats = $this->couponService->getRedemptionStats($coupon);
        $currencySymbol = \App\Models\Setting::get('payment.currency_symbol', '$');

        return view('coupons.show', compact('coupon', 'stats', 'currencySymbol'));
    }

    /**
     * Show the form for editing the specified coupon.
     */
    public function edit(Coupon $coupon)
    {
        $this->authorize('edit coupons');

        $batches = CouponBatch::all();
        $customerGroups = CustomerGroup::active()->get();
        $locations = Location::all();
        $services = Service::active()->get();
        $servicePackages = ServicePackage::active()->get();
        $categories = ServicePackageCategory::all();

        $selectedCustomerGroups = $coupon->customerGroups->pluck('id')->toArray();
        $selectedLocations = $coupon->locations->pluck('id')->toArray();
        $selectedCategories = $coupon->categories->pluck('id')->toArray();

        // Convert selected products to prefixed IDs for the view
        $selectedProducts = [];
        foreach ($coupon->products as $product) {
            $selectedProducts[] = 'service_' . $product->id;
        }
        foreach ($coupon->servicePackages as $package) {
            $selectedProducts[] = 'package_' . $package->id;
        }

        $currencySymbol = \App\Models\Setting::get('payment.currency_symbol', '$');

        return view('coupons.edit', compact(
            'coupon',
            'batches',
            'customerGroups',
            'locations',
            'services',
            'servicePackages',
            'categories',
            'selectedCustomerGroups',
            'selectedLocations',
            'selectedProducts',
            'selectedCategories',
            'currencySymbol'
        ));
    }

    /**
     * Update the specified coupon in storage.
     */
    public function update(CouponRequest $request, Coupon $coupon)
    {
        $this->authorize('edit coupons');

        $data = $this->couponPayload($request);

        DB::beginTransaction();
        try {
            $coupon->update($data);
            $this->syncCouponRelations($coupon, $request);

            DB::commit();

            return redirect()->route('coupons.index')
                ->with('success', 'Coupon ' . $coupon->code . ' updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Coupon update failed', [
                'coupon' => $coupon->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()
                ->with('error', 'We could not update this coupon. Please check the details and try again.');
        }
    }

    /**
     * Remove the specified coupon from storage.
     */
    public function destroy(Coupon $coupon)
    {
        $this->authorize('delete coupons');

        DB::beginTransaction();
        try {
            $coupon->delete();
            DB::commit();
            return redirect()->route('coupons.index')
                ->with('success', 'Coupon deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Coupon delete failed', ['coupon' => $coupon->id, 'error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'We could not delete this coupon. Please try again.');
        }
    }

    /**
     * Display a listing of customer groups.
     */
    public function customerGroups()
    {
        $this->authorize('manage coupon batches');

        $groups = CustomerGroup::withCount('customers')->get();
        return view('customer-groups.index', compact('groups'));
    }

    /**
     * Show the form for creating a new customer group.
     */
    public function createCustomerGroup()
    {
        $this->authorize('manage coupon batches');

        return view('customer-groups.create');
    }

    /**
     * Store a newly created customer group in storage.
     */
    public function storeCustomerGroup(Request $request)
    {
        $this->authorize('manage coupon batches');

        $request->validate([
            'name' => 'required|string|max:255|unique:customer_groups,name',
            'description' => 'nullable|string',
            'criteria' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        CustomerGroup::create($request->only(['name', 'description', 'criteria', 'is_active']));

        return redirect()->route('customer-groups.index')
            ->with('success', 'Customer group created successfully.');
    }

    /**
     * Show the form for editing the specified customer group.
     */
    public function editCustomerGroup(CustomerGroup $group)
    {
        $this->authorize('manage coupon batches');

        return view('customer-groups.edit', compact('group'));
    }

    /**
     * Update the specified customer group in storage.
     */
    public function updateCustomerGroup(Request $request, CustomerGroup $group)
    {
        $this->authorize('manage coupon batches');

        $request->validate([
            'name' => 'required|string|max:255|unique:customer_groups,name,' . $group->id,
            'description' => 'nullable|string',
            'criteria' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        $group->update($request->only(['name', 'description', 'criteria', 'is_active']));

        return redirect()->route('customer-groups.index')
            ->with('success', 'Customer group updated successfully.');
    }

    /**
     * Remove the specified customer group from storage.
     */
    public function destroyCustomerGroup(CustomerGroup $group)
    {
        $this->authorize('manage coupon batches');

        // Check if group is used in any coupons
        if ($group->coupons()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete customer group because it is used in one or more coupons.');
        }

        $group->delete();

        return redirect()->route('customer-groups.index')
            ->with('success', 'Customer group deleted successfully.');
    }

    /**
     * Bulk coupon generation form.
     */
    public function bulkCreate()
    {
        $this->authorize('manage coupon batches');

        $batches = CouponBatch::all();
        return view('coupons.bulk', compact('batches'));
    }

    /**
     * Generate bulk coupons.
     */
    public function bulkStore(Request $request)
    {
        $this->authorize('manage coupon batches');

        $isPercentage = $request->input('coupon_type') === 'percentage';

        $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string|max:2000',
            'pattern'           => 'required|string|max:100',
            'count'             => 'required|integer|min:1|max:500',
            'seq_start'         => 'nullable|integer|min:0|max:999999999',
            'coupon_type'       => 'required|in:fixed,percentage',
            'discount_value'    => $isPercentage
                ? 'required|numeric|min:0.01|max:100'
                : 'required|numeric|min:0.01',
            'valid_days'        => 'required|integer|min:1|max:3650',
            'usage_limit'       => 'nullable|integer|min:1',
            'per_customer'      => 'required|integer|min:1',
            'min_purchase'      => 'nullable|numeric|min:0',
        ], [
            'name.required' => 'Give this batch a name.',
            'pattern.required' => 'Enter a code pattern, for example SUMMER-{RANDOM:6}.',
            'discount_value.required' => 'Enter the discount value for these coupons.',
            'discount_value.max' => 'A percentage discount cannot be more than 100.',
            'count.max' => 'You can generate at most 500 coupons in one batch.',
            'seq_start.integer' => 'The start number must be a whole number.',
            'seq_start.min' => 'The start number cannot be negative.',
        ]);

        // Without a placeholder every coupon in the batch would receive the same
        // code, so only the first one could ever be saved.
        if ((int) $request->input('count') > 1
            && ! $this->couponService->patternHasPlaceholder((string) $request->input('pattern'))) {
            throw ValidationException::withMessages([
                'pattern' => 'Add a placeholder to the pattern (for example {RANDOM:6} or {SEQ:4}) so each coupon gets its own code.',
            ]);
        }

        $settings = [
            'type'                    => $request->coupon_type,
            'discount_value'          => (float) $request->discount_value,
            'start_date'              => now()->toDateTimeString(),
            'end_date'                => now()->addDays((int) $request->valid_days)->toDateTimeString(),
            'total_usage_limit'       => $request->usage_limit ? (int) $request->usage_limit : null,
            'per_customer_limit'      => (int) $request->per_customer,
            'minimum_purchase_amount' => $request->min_purchase ? (float) $request->min_purchase : 0,
            'active'                  => true,
            'timezone'                => config('app.timezone', 'Asia/Colombo'),
            // Starting number for any {SEQ:n} placeholder in the pattern
            'seq_start'               => $request->filled('seq_start') ? (int) $request->seq_start : 1,
        ];

        $batch = CouponBatch::create([
            'name'        => $request->name,
            'description' => $request->description,
            'pattern'     => $request->pattern,
            'count'       => (int) $request->count,
            'settings'    => $settings,
            'status'      => CouponBatch::STATUS_PENDING,
        ]);

        try {
            $result = $this->couponService->generateBulkCoupons($batch);
        } catch (\Throwable $e) {
            \Log::error('Bulk coupon generation failed', ['batch' => $batch->id, 'error' => $e->getMessage()]);

            return redirect()->route('coupons.batches.show', $batch)
                ->with('error', 'The batch was created but the codes could not be generated. Nothing was saved — check the pattern and try again.');
        }

        $message = "Batch '{$batch->name}' created — {$result['generated']} of {$batch->count} coupons generated.";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} code(s) were skipped because they already exist; use a longer {RANDOM:N} or a {SEQ:N} pattern.";
        }

        return redirect()->route('coupons.batches.show', $batch)->with('success', $message);
    }

    /**
     * List coupon batches.
     */
    public function batches()
    {
        $this->authorize('manage coupon batches');

        $batches = CouponBatch::withCount('coupons')
            ->orderByDesc('id')
            ->paginate(20);

        return view('coupons.batches', compact('batches'));
    }

    /**
     * Show batch details.
     */
    public function showBatch(CouponBatch $batch)
    {
        $this->authorize('manage coupon batches');

        $batch->load(['coupons' => fn ($q) => $q->orderBy('id')]);

        return view('coupons.batch-show', compact('batch'));
    }

    /**
     * Export batch coupons as CSV.
     */
    public function exportBatch(CouponBatch $batch)
    {
        $this->authorize('manage coupon batches');

        $coupons = $batch->coupons()->orderBy('id')->get();

        $filename = 'batch_' . $batch->id . '_coupons.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($coupons) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM so Excel opens the file with the right encoding
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Code', 'Name', 'Type', 'Discount', 'Min Purchase', 'Start Date', 'End Date', 'Usage Limit', 'Per Customer', 'Status', 'Created At']);

            foreach ($coupons as $coupon) {
                $status = $coupon->isExpired() ? 'Expired' : ($coupon->isActive() ? 'Active' : 'Inactive');

                fputcsv($handle, [
                    $coupon->code,
                    $coupon->name,
                    $coupon->type,
                    $coupon->type === 'percentage' ? $coupon->discount_value . '%' : $coupon->discount_value,
                    $coupon->minimum_purchase_amount,
                    $coupon->start_date,
                    $coupon->end_date,
                    $coupon->total_usage_limit,
                    $coupon->per_customer_limit,
                    $status,
                    $coupon->created_at,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Validate a coupon for POS.
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'customer_id' => 'nullable|exists:customers,id',
            'location_id' => 'nullable|exists:locations,id',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:service,package',
            'items.*.id' => 'required',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'code.required' => 'Enter a coupon code.',
            'items.required' => 'Add at least one service or package to the cart before applying a coupon.',
        ]);

        $code = strtoupper(trim($request->input('code')));
        $coupon = Coupon::whereRaw('UPPER(code) = ?', [$code])->first();

        if (! $coupon) {
            return response()->json([
                'valid' => false,
                'message' => 'Coupon code ' . $code . ' was not found.',
                'errors' => ['Coupon code ' . $code . ' was not found.'],
            ], 404);
        }

        $subtotal = collect($request->input('items'))
            ->sum(fn ($item) => (float) $item['price'] * (int) $item['quantity']);

        // A transient sale is only used to carry the subtotal/location into the
        // coupon service — nothing is persisted here.
        $sale = new Sale();
        $sale->subtotal = $subtotal;
        $sale->location_id = $request->input('location_id');

        $customer = $request->input('customer_id') ? Customer::find($request->input('customer_id')) : null;

        $result = $this->couponService->validate($coupon, $sale, $customer);

        if (! $result['valid']) {
            return response()->json([
                'valid' => false,
                'message' => $result['errors'][0] ?? 'This coupon cannot be used for this sale.',
                'errors' => $result['errors'],
            ], 422);
        }

        $discount = $this->couponService->calculateDiscount($coupon, $subtotal, $request->input('items'));
        $discount = round(min($discount, $subtotal), 2);

        return response()->json([
            'valid' => true,
            'message' => 'Coupon applied successfully.',
            'coupon' => $coupon->only(['id', 'code', 'name', 'type', 'discount_value', 'max_discount_amount', 'stackable']),
            'discount_amount' => $discount,
            'subtotal' => round($subtotal, 2),
        ]);
    }

    /**
     * Bulk coupon generation form (alias for bulkCreate).
     */
    public function createBulk()
    {
        return $this->bulkCreate();
    }

    /**
     * Generate bulk coupons (alias for bulkStore).
     */
    public function generateBulk(Request $request)
    {
        return $this->bulkStore($request);
    }

    /**
     * Coupon report/stats for POS — returns JSON.
     * GET /api/coupons/report
     */
    public function report()
    {
        $this->authorize('view pos');

        $today = now()->startOfDay();
        $currencySymbol = \App\Models\Setting::get('payment.currency_symbol', '$');

        $stats = [
            'active_coupons' => Coupon::active()->count(),
            'expired_coupons' => Coupon::expired()->count(),
            'total_redemptions_all' => \App\Models\CouponRedemption::count(),
            'redemptions_today' => \App\Models\CouponRedemption::where('redeemed_at', '>=', $today)->count(),
            'discount_given_today' => (float) \App\Models\CouponRedemption::where('redeemed_at', '>=', $today)->sum('discount_amount'),
            'currency_symbol' => $currencySymbol,
            'top_coupons' => \App\Models\Coupon::withCount('redemptions')
                ->orderBy('redemptions_count', 'desc')
                ->limit(5)
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'code' => $c->code,
                    'name' => $c->name,
                    'redemptions_count' => $c->redemptions_count,
                ]),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}