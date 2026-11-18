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
    /**
     * Delete a single coupon permanently.
     *
     * Coupons that were actually redeemed keep their row: `coupon_redemptions`
     * cascades on delete, so a hard delete would erase the discount history of
     * past sales. Those must be deactivated instead.
     */
    public function destroy(Coupon $coupon)
    {
        $this->authorize('delete coupons');

        if ($coupon->redemptions()->exists()) {
            return redirect()->back()->with('error', sprintf(
                'Coupon %s has already been used, so it cannot be deleted without erasing sale history. Deactivate it instead.',
                $coupon->code
            ));
        }

        try {
            $code = $coupon->code;
            $coupon->forceDelete();

            return redirect()->route('coupons.index')
                ->with('success', "Coupon {$code} was permanently deleted. The code can be used again.");
        } catch (\Throwable $e) {
            \Log::error('Coupon delete failed', ['coupon' => $coupon->id, 'error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'We could not delete this coupon. Please try again.');
        }
    }

    /**
     * Bulk activate / deactivate / permanently delete from the coupons list.
     */
    public function bulkAction(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer',
        ], [
            'ids.required' => 'Select at least one coupon first.',
            'action.in' => 'That bulk action is not supported.',
        ]);

        $this->authorize($data['action'] === 'delete' ? 'delete coupons' : 'edit coupons');

        $coupons = Coupon::whereIn('id', $data['ids'])->get();

        if ($coupons->isEmpty()) {
            return redirect()->back()->with('error', 'Those coupons no longer exist — nothing was changed.');
        }

        if ($data['action'] === 'delete') {
            // Never cascade away redemption history: only coupons that were
            // never redeemed can be removed for good.
            $deletableIds = [];
            $kept = collect();

            foreach ($coupons as $coupon) {
                if ($coupon->redemptions()->exists()) {
                    $kept->push($coupon);
                } else {
                    $deletableIds[] = $coupon->id;
                }
            }

            $deleted = $deletableIds === [] ? 0 : Coupon::whereIn('id', $deletableIds)->forceDelete();

            if ($kept->isNotEmpty()) {
                $codes = $kept->pluck('code')->take(5)->implode(', ');
                $more = $kept->count() > 5 ? ' and ' . ($kept->count() - 5) . ' more' : '';

                \Log::info('Coupon bulk delete skipped redeemed coupons', ['ids' => $kept->pluck('id')->all()]);

                return redirect()->back()->with(
                    $deleted > 0 ? 'success' : 'error',
                    ($deleted > 0 ? "{$deleted} coupon(s) permanently deleted. " : '')
                    . "{$kept->count()} coupon(s) were kept because they have redemptions ({$codes}{$more}) — deactivate them instead."
                );
            }

            return redirect()->back()->with('success', "{$deleted} coupon(s) permanently deleted. Those codes can be used again.");
        }

        $active = $data['action'] === 'activate';
        Coupon::whereIn('id', $coupons->pluck('id'))->update(['active' => $active]);

        return redirect()->back()->with(
            'success',
            $coupons->count() . ' coupon(s) ' . ($active ? 'activated.' : 'deactivated.')
        );
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

        $batches = CouponBatch::orderByDesc('id')->get(['id', 'name', 'pattern', 'count', 'generated_count', 'settings']);
        // ?batch=ID opens the form ready to top that batch up (used by the "Add codes" button)
        $preselectedBatchId = (int) request('batch') ?: null;

        return view('coupons.bulk', compact('batches', 'preselectedBatchId'));
    }

    /**
     * Generate bulk coupons.
     */
    public function bulkStore(Request $request)
    {
        $this->authorize('manage coupon batches');

        $isPercentage = $request->input('coupon_type') === 'percentage';

        // ---- append to an existing batch ----
        // Choosing a batch on the bulk form tops it up instead of creating a new
        // one: the batch keeps its pattern and settings, and the new codes
        // continue the numbering where the old ones stopped.
        if ($request->filled('batch_id') && (int) $request->batch_id > 0) {
            $target = CouponBatch::find((int) $request->batch_id);

            if (! $target) {
                return redirect()->route('coupons.bulk.create')
                    ->with('error', 'That coupon batch no longer exists. Pick another batch or create a new one.');
            }

            $add = (int) $request->count;

            if ($add < 1 || $add > 500) {
                throw ValidationException::withMessages([
                    'count' => 'Enter how many coupons to add (1 to 500).',
                ]);
            }

            $target->forceFill([
                'count' => (int) $target->generated_count + $add,
                'status' => CouponBatch::STATUS_PENDING,
            ])->save();

            try {
                $result = $this->couponService->generateBulkCoupons($target);
            } catch (\Throwable $e) {
                \Log::error('Bulk coupon append failed', ['batch' => $target->id, 'error' => $e->getMessage()]);

                return redirect()->route('coupons.batches.show', $target)
                    ->with('error', 'The coupons could not be generated. Nothing was added — try again.');
            }

            $message = "{$result['generated']} coupon(s) added to batch '{$target->name}' — it now holds {$target->fresh()->generated_count} code(s).";
            if ($result['skipped'] > 0) {
                $message .= " {$result['skipped']} code(s) were skipped because they already exist; use {SEQ:N} or a longer {RANDOM:N} in the batch pattern.";
            }

            return redirect()->route('coupons.batches.show', $target)->with('success', $message);
        }

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
     * Edit a batch: its name, description, pattern and the settings its coupons
     * were built from.
     */
    public function editBatch(CouponBatch $batch)
    {
        $this->authorize('manage coupon batches');

        $batch->loadCount('coupons');

        return view('coupons.batch-edit', compact('batch'));
    }

    /**
     * Save changes to a batch. Coupon settings (discount, validity, limits) are
     * stored on the batch and can optionally be pushed to the coupons that were
     * already generated from it.
     */
    public function updateBatch(Request $request, CouponBatch $batch)
    {
        $this->authorize('manage coupon batches');

        $isPercentage = $request->input('coupon_type') === 'percentage';

        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:2000',
            'pattern'        => 'required|string|max:100',
            'seq_start'      => 'nullable|integer|min:0|max:999999999',
            'coupon_type'    => 'required|in:fixed,percentage',
            'discount_value' => $isPercentage
                ? 'required|numeric|min:0.01|max:100'
                : 'required|numeric|min:0.01',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
            'usage_limit'    => 'nullable|integer|min:1',
            'per_customer'   => 'required|integer|min:1',
            'min_purchase'   => 'nullable|numeric|min:0',
            'apply_to_coupons' => 'nullable|boolean',
        ], [
            'name.required' => 'Give this batch a name.',
            'pattern.required' => 'Enter a code pattern, for example SUMMER-{RANDOM:6}.',
            'discount_value.max' => 'A percentage discount cannot be more than 100.',
            'end_date.after_or_equal' => 'The end date cannot be before the start date.',
        ]);

        $settings = array_merge($batch->settings ?? [], [
            'type'                    => $data['coupon_type'],
            'discount_value'          => (float) $data['discount_value'],
            'start_date'              => $data['start_date'] ?? $batch->settings['start_date'] ?? now()->toDateTimeString(),
            'end_date'                => $data['end_date'] ?? null,
            'total_usage_limit'       => $data['usage_limit'] ? (int) $data['usage_limit'] : null,
            'per_customer_limit'      => (int) $data['per_customer'],
            'minimum_purchase_amount' => $data['min_purchase'] ? (float) $data['min_purchase'] : 0,
            'active'                  => $request->boolean('active'),
            'timezone'                => config('app.timezone', 'Asia/Colombo'),
            'seq_start'               => isset($data['seq_start']) ? (int) $data['seq_start'] : (int) ($batch->settings['seq_start'] ?? 1),
        ]);

        $batch->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'pattern'     => $data['pattern'],
            'settings'    => $settings,
        ]);

        $applied = 0;
        if ($request->boolean('apply_to_coupons')) {
            $applied = $batch->coupons()->update([
                'type'                    => $settings['type'],
                'discount_value'          => $settings['discount_value'],
                'start_date'              => $settings['start_date'],
                'end_date'                => $settings['end_date'],
                'total_usage_limit'       => $settings['total_usage_limit'],
                'per_customer_limit'      => $settings['per_customer_limit'],
                'minimum_purchase_amount' => $settings['minimum_purchase_amount'],
                'active'                  => $settings['active'],
                'timezone'                => $settings['timezone'],
            ]);
        }

        return redirect()->route('coupons.batches.show', $batch)->with(
            'success',
            "Batch '{$batch->name}' updated." . ($applied > 0 ? " The new settings were applied to {$applied} coupon(s)." : '')
        );
    }

    /**
     * Delete a batch. Its unused codes go with it (they can be issued again);
     * codes that were already redeemed are kept and detached from the batch so
     * sale history stays intact.
     */
    public function destroyBatch(CouponBatch $batch)
    {
        $this->authorize('manage coupon batches');

        try {
            [$deletableIds, $keptIds] = [[], []];

            foreach ($batch->coupons()->get() as $coupon) {
                if ($coupon->redemptions()->exists()) {
                    $keptIds[] = $coupon->id;
                } else {
                    $deletableIds[] = $coupon->id;
                }
            }

            $deleted = $deletableIds === [] ? 0 : Coupon::whereIn('id', $deletableIds)->forceDelete();
            $kept = count($keptIds);
            if ($kept > 0) {
                Coupon::whereIn('id', $keptIds)->update(['batch_id' => null]);
            }

            $name = $batch->name;
            $batch->delete();

            $message = "Batch '{$name}' deleted. {$deleted} unused code(s) removed.";
            if ($kept > 0) {
                $message .= " {$kept} redeemed coupon(s) were kept and are now listed without a batch.";
            }

            return redirect()->route('coupons.batches')->with('success', $message);
        } catch (\Throwable $e) {
            \Log::error('Coupon batch delete failed', ['batch' => $batch->id, 'error' => $e->getMessage()]);

            return redirect()->route('coupons.batches')
                ->with('error', 'We could not delete this batch. Please try again.');
        }
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