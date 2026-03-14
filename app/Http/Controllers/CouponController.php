<?php

namespace App\Http\Controllers;

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

        return view('admin.coupons.index', compact('coupons', 'stats'));
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

        return view('admin.coupons.create', compact(
            'batches',
            'customerGroups',
            'locations',
            'services',
            'servicePackages',
            'categories'
        ));
    }

    /**
     * Store a newly created coupon in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create coupons');

        $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,bogo,free_shipping,tiered',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'minimum_purchase_amount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'timezone' => 'required|string',
            'total_usage_limit' => 'nullable|integer|min:1',
            'per_customer_limit' => 'nullable|integer|min:1',
            'stackable' => 'boolean',
            'active' => 'boolean',
            'location_restriction_type' => 'required|in:all,specific',
            'customer_eligibility_type' => 'required|in:all,new,existing,groups',
            'product_restriction_type' => 'required|in:all,specific,categories',
            'metadata' => 'nullable|json',
            'batch_id' => 'nullable|exists:coupon_batches,id',
            'customer_groups' => 'nullable|array',
            'customer_groups.*' => 'exists:customer_groups,id',
            'locations' => 'nullable|array',
            'locations.*' => 'exists:locations,id',
            'products' => 'nullable|array',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:service_package_categories,id',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->only([
                'code', 'name', 'description', 'type', 'max_discount_amount',
                'minimum_purchase_amount', 'start_date', 'end_date', 'timezone',
                'total_usage_limit', 'per_customer_limit', 'stackable', 'active',
                'location_restriction_type', 'customer_eligibility_type', 'product_restriction_type',
                'metadata', 'batch_id'
            ]);

            // Handle discount mapping
            if ($request->type === 'percentage') {
                $data['discount_value'] = $request->discount_percentage;
            } else {
                $data['discount_value'] = $request->discount_value;
            }

            // Ensure non-nullable fields have defaults if null
            $data['minimum_purchase_amount'] = $data['minimum_purchase_amount'] ?? 0;
            $data['per_customer_limit'] = $data['per_customer_limit'] ?? 1;

            $coupon = Coupon::create($data);

            // Attach customer groups
            if ($request->has('customer_groups')) {
                $coupon->customerGroups()->sync($request->customer_groups);
            }

            // Attach locations
            if ($request->has('locations')) {
                $coupon->locations()->sync($request->locations);
            }

            // Attach products (handle prefixed IDs from form)
            if ($request->has('products')) {
                foreach ($request->products as $prefixedId) {
                    if (str_starts_with($prefixedId, 'service_')) {
                        $id = str_replace('service_', '', $prefixedId);
                        $productType = Service::class;
                    } elseif (str_starts_with($prefixedId, 'package_')) {
                        $id = str_replace('package_', '', $prefixedId);
                        $productType = ServicePackage::class;
                    } else {
                        continue;
                    }

                    $coupon->products()->attach($id, [
                        'product_type' => $productType,
                        'restriction_type' => 'included',
                    ]);
                }
            }

            // Attach categories
            if ($request->has('categories')) {
                $coupon->categories()->sync($request->categories);
            }

            DB::commit();
            return redirect()->route('coupons.index')
                ->with('success', 'Coupon created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()
                ->with('error', 'Failed to create coupon: ' . $e->getMessage());
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

        return view('admin.coupons.show', compact('coupon', 'stats'));
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

        return view('admin.coupons.edit', compact(
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
            'selectedCategories'
        ));
    }

    /**
     * Update the specified coupon in storage.
     */
    public function update(Request $request, Coupon $coupon)
    {
        $this->authorize('edit coupons');

        $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,bogo,free_shipping,tiered',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'minimum_purchase_amount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'timezone' => 'required|string',
            'total_usage_limit' => 'nullable|integer|min:1',
            'per_customer_limit' => 'nullable|integer|min:1',
            'stackable' => 'boolean',
            'active' => 'boolean',
            'location_restriction_type' => 'required|in:all,specific',
            'customer_eligibility_type' => 'required|in:all,new,existing,groups',
            'product_restriction_type' => 'required|in:all,specific,categories',
            'metadata' => 'nullable|json',
            'batch_id' => 'nullable|exists:coupon_batches,id',
            'customer_groups' => 'nullable|array',
            'customer_groups.*' => 'exists:customer_groups,id',
            'locations' => 'nullable|array',
            'locations.*' => 'exists:locations,id',
            'products' => 'nullable|array',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:service_package_categories,id',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->only([
                'code', 'name', 'description', 'type', 'max_discount_amount',
                'minimum_purchase_amount', 'start_date', 'end_date', 'timezone',
                'total_usage_limit', 'per_customer_limit', 'stackable', 'active',
                'location_restriction_type', 'customer_eligibility_type', 'product_restriction_type',
                'metadata', 'batch_id'
            ]);

            // Handle discount mapping
            if ($request->type === 'percentage') {
                $data['discount_value'] = $request->discount_percentage ?? $request->discount_value;
            } else {
                $data['discount_value'] = $request->discount_value;
            }

            // Ensure non-nullable fields have defaults if null
            $data['minimum_purchase_amount'] = $data['minimum_purchase_amount'] ?? 0;
            $data['per_customer_limit'] = $data['per_customer_limit'] ?? 1;

            $coupon->update($data);

            // Sync relationships
            $coupon->customerGroups()->sync($request->customer_groups ?? []);
            $coupon->locations()->sync($request->locations ?? []);

            // Sync products
            $coupon->products()->detach();
            $coupon->servicePackages()->detach();
            if ($request->has('products')) {
                foreach ($request->products as $prefixedId) {
                    if (str_starts_with($prefixedId, 'service_')) {
                        $id = str_replace('service_', '', $prefixedId);
                        $coupon->products()->attach($id, [
                            'restriction_type' => 'included',
                        ]);
                    } elseif (str_starts_with($prefixedId, 'package_')) {
                        $id = str_replace('package_', '', $prefixedId);
                        $coupon->servicePackages()->attach($id, [
                            'restriction_type' => 'included',
                        ]);
                    }
                }
            }

            // Sync categories
            $coupon->categories()->sync($request->categories ?? []);

            DB::commit();
            return redirect()->route('coupons.index')
                ->with('success', 'Coupon updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()
                ->with('error', 'Failed to update coupon: ' . $e->getMessage());
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
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete coupon: ' . $e->getMessage());
        }
    }

    /**
     * Display a listing of customer groups.
     */
    public function customerGroups()
    {
        $this->authorize('manage system');

        $groups = CustomerGroup::withCount('customers')->get();
        return view('admin.customer-groups.index', compact('groups'));
    }

    /**
     * Show the form for creating a new customer group.
     */
    public function createCustomerGroup()
    {
        $this->authorize('manage system');

        return view('admin.customer-groups.create');
    }

    /**
     * Store a newly created customer group in storage.
     */
    public function storeCustomerGroup(Request $request)
    {
        $this->authorize('manage system');

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
        $this->authorize('manage system');

        return view('admin.customer-groups.edit', compact('group'));
    }

    /**
     * Update the specified customer group in storage.
     */
    public function updateCustomerGroup(Request $request, CustomerGroup $group)
    {
        $this->authorize('manage system');

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
        $this->authorize('manage system');

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
        return view('admin.coupons.bulk', compact('batches'));
    }

    /**
     * Generate bulk coupons.
     */
    public function bulkStore(Request $request)
    {
        $this->authorize('manage coupon batches');

        $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'pattern'           => 'required|string|max:100',
            'count'             => 'required|integer|min:1|max:500',
            'coupon_type'       => 'required|in:fixed,percentage',
            'discount_value'    => 'required|numeric|min:0',
            'valid_days'        => 'required|integer|min:1|max:3650',
            'usage_limit'       => 'nullable|integer|min:1',
            'per_customer'      => 'required|integer|min:1',
            'min_purchase'      => 'nullable|numeric|min:0',
        ]);

        // Build settings from clean form fields
        $settings = [
            'type'                     => $request->coupon_type,
            'discount_value'           => (float) $request->discount_value,
            'start_date'               => now()->toDateTimeString(),
            'end_date'                 => now()->addDays((int) $request->valid_days)->toDateTimeString(),
            'total_usage_limit'        => $request->usage_limit ? (int) $request->usage_limit : null,
            'per_customer_limit'       => (int) $request->per_customer,
            'minimum_purchase_amount'  => $request->min_purchase ? (float) $request->min_purchase : 0,
            'active'                   => true,
        ];

        $batch = CouponBatch::create([
            'name'        => $request->name,
            'description' => $request->description,
            'pattern'     => $request->pattern,
            'count'       => $request->count,
            'settings'    => $settings,
            'status'      => 'pending',
        ]);

        $this->couponService->generateBulkCoupons($batch);

        $generated = $batch->fresh()->generated_count;

        return redirect()->route('coupons.index')
            ->with('success', "Batch '{$batch->name}' created — {$generated} coupons generated successfully.");
    }

    /**
     * List coupon batches.
     */
    public function batches()
    {
        $this->authorize('manage coupon batches');

        $batches = CouponBatch::withCount('coupons')->paginate(20);
        return view('admin.coupons.batches', compact('batches'));
    }

    /**
     * Show batch details.
     */
    public function showBatch(CouponBatch $batch)
    {
        $this->authorize('manage coupon batches');

        $batch->load('coupons');
        return view('admin.coupons.batch_show', compact('batch'));
    }

    /**
     * Export batch coupons as CSV.
     */
    public function exportBatch(CouponBatch $batch)
    {
        $this->authorize('manage coupon batches');

        $batch->load('coupons');
        $coupons = $batch->coupons;

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
            // Add UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");
            // Header row
            fputcsv($handle, ['Code', 'Name', 'Description', 'Type', 'Discount Value', 'Start Date', 'End Date', 'Status', 'Created At']);
            foreach ($coupons as $coupon) {
                fputcsv($handle, [
                    $coupon->code,
                    $coupon->name,
                    $coupon->description,
                    $coupon->type,
                    $coupon->discount_value ?? $coupon->discount_percentage,
                    $coupon->start_date,
                    $coupon->end_date,
                    $coupon->status,
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
            'items' => 'nullable|array',
            'items.*.type' => 'in:service,package',
            'items.*.id' => 'required',
            'items.*.price' => 'required|numeric',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $coupon = Coupon::where('code', $request->code)->first();
        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'errors' => ['Coupon code not found.'],
            ], 404);
        }

        // Create a temporary sale object for validation
        $sale = new Sale();
        $sale->subtotal = collect($request->items)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
        $sale->location_id = $request->location_id;
        // We'll need to set other sale attributes if necessary

        $customer = $request->customer_id ? \App\Models\Customer::find($request->customer_id) : null;

        $validationResult = $this->couponService->validate($coupon, $sale, $customer);

        if (!$validationResult['valid']) {
            return response()->json($validationResult, 422);
        }

        // Calculate discount amount
        $discount = $this->couponService->calculateDiscount($coupon, $sale->subtotal, $request->items);

        return response()->json([
            'valid' => true,
            'coupon' => $coupon->only(['id', 'code', 'name', 'type', 'discount_value', 'max_discount_amount', 'stackable']),
            'discount_amount' => $discount,
            'message' => 'Coupon applied successfully.',
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
}