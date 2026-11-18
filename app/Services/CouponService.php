<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\CouponRedemption;
use App\Models\SaleCoupon;
use App\Models\Location;
use App\Models\CouponBatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CouponService
{
    /**
     * Validate a coupon for a given sale and customer.
     *
     * @param Coupon $coupon
     * @param Sale $sale
     * @param Customer|null $customer
     * @return array Validation result with success flag and messages.
     */
    public function validate(Coupon $coupon, Sale $sale, ?Customer $customer = null): array
    {
        $errors = [];

        // 1. Active and date validity
        if (!$coupon->isActive()) {
            $errors[] = 'Coupon is not active or has expired.';
        }

        // 2. Total usage limit
        if (!$coupon->hasRemainingUses()) {
            $errors[] = 'Coupon usage limit reached.';
        }

        // 3. Per customer limit
        if ($customer && !$coupon->canBeUsedByCustomer($customer)) {
            $errors[] = 'You have already used this coupon the maximum number of times.';
        }

        // 4. Minimum purchase amount
        if ($sale->subtotal < $coupon->minimum_purchase_amount) {
            $errors[] = 'Minimum purchase amount not met.';
        }

        // 5. Location restrictions
        if ($coupon->location_restriction_type === 'specific') {
            $saleLocation = $sale->location_id; // Assuming sale has location_id
            if (!$coupon->locations()->where('location_id', $saleLocation)->exists()) {
                $errors[] = 'Coupon is not valid for this location.';
            }
        }

        // 6. Customer eligibility
        if ($customer) {
            $eligibilityErrors = $this->validateCustomerEligibility($coupon, $customer);
            $errors = array_merge($errors, $eligibilityErrors);
        }

        // 7. Product restrictions
        $productErrors = $this->validateProductRestrictions($coupon, $sale);
        $errors = array_merge($errors, $productErrors);

        // 8. Stackability (if sale already has coupons and coupon is not stackable)
        if (!$coupon->stackable && $sale->coupons()->exists()) {
            $errors[] = 'Coupon cannot be combined with other coupons.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'coupon' => $coupon,
        ];
    }

    /**
     * Validate customer eligibility based on coupon settings.
     */
    private function validateCustomerEligibility(Coupon $coupon, Customer $customer): array
    {
        $errors = [];

        switch ($coupon->customer_eligibility_type) {
            case 'new':
                if ($customer->created_at->diffInDays(now()) > 30 || $customer->sales()->count() > 0) {
                    $errors[] = 'Coupon is for new customers only.';
                }
                break;
            case 'existing':
                if ($customer->sales()->count() === 0) {
                    $errors[] = 'Coupon is for existing customers only.';
                }
                break;
            case 'groups':
                if (!$coupon->customerGroups()->where('customer_group_id', $customer->group_id)->exists()) {
                    $errors[] = 'You are not eligible for this coupon.';
                }
                break;
        }

        return $errors;
    }

    /**
     * Validate product restrictions.
     */
    private function validateProductRestrictions(Coupon $coupon, Sale $sale): array
    {
        $errors = [];

        if ($coupon->product_restriction_type === 'all') {
            return [];
        }

        $saleItems = $sale->items()->with('sellable')->get();
        $applicableItems = [];

        foreach ($saleItems as $item) {
            $sellable = $item->sellable;
            if (!$sellable) {
                continue;
            }

            $productType = get_class($sellable);
            $productId = $sellable->id;

            // Check if this product is included/excluded
            $restriction = $coupon->products()
                ->where('product_id', $productId)
                ->where('product_type', $productType)
                ->first();

            if ($coupon->product_restriction_type === 'specific') {
                // Must be in the included list
                if (!$restriction || $restriction->pivot->restriction_type === 'excluded') {
                    $applicableItems[] = false;
                } else {
                    $applicableItems[] = true;
                }
            } elseif ($coupon->product_restriction_type === 'categories') {
                // Check category membership
                $categories = $coupon->categories()->pluck('category_id')->toArray();
                // Assuming sellable has category relationship; adjust as needed
                if (method_exists($sellable, 'categories')) {
                    $itemCategories = $sellable->categories()->pluck('id')->toArray();
                    if (empty(array_intersect($categories, $itemCategories))) {
                        $applicableItems[] = false;
                    } else {
                        $applicableItems[] = true;
                    }
                } else {
                    // No categories defined, treat as not restricted
                    $applicableItems[] = true;
                }
            }
        }

        // If no items are applicable, coupon cannot be applied
        if (!empty($applicableItems) && !in_array(true, $applicableItems, true)) {
            $errors[] = 'Coupon does not apply to any items in the sale.';
        }

        return $errors;
    }

    /**
     * Calculate discount amount for a coupon given subtotal and items.
     *
     * @param Coupon $coupon
     * @param float $subtotal
     * @param array $items Array of sale items with price, quantity, product info
     * @return float Discount amount
     */
    public function calculateDiscount(Coupon $coupon, float $subtotal, array $items = []): float
    {
        switch ($coupon->type) {
            case Coupon::TYPE_PERCENTAGE:
                $discount = $subtotal * ($coupon->discount_value / 100);
                if ($coupon->max_discount_amount && $discount > $coupon->max_discount_amount) {
                    $discount = $coupon->max_discount_amount;
                }
                return round($discount, 2);

            case Coupon::TYPE_FIXED:
                return min($coupon->discount_value, $subtotal);

            case Coupon::TYPE_BOGO:
                // Buy X Get Y free logic (simplified)
                // This is a placeholder; implement based on actual BOGO rules
                return 0;

            case Coupon::TYPE_FREE_SHIPPING:
                // Free shipping discount could be a fixed shipping cost
                // For now, return 0 as shipping is not part of subtotal
                return 0;

            case Coupon::TYPE_TIERED:
                // Tiered discount based on thresholds in metadata
                $metadata = $coupon->metadata ?? [];
                $tiers = $metadata['tiers'] ?? [];
                usort($tiers, function ($a, $b) {
                    return $a['min_amount'] <=> $b['min_amount'];
                });

                $applicableTier = null;
                foreach ($tiers as $tier) {
                    if ($subtotal >= $tier['min_amount']) {
                        $applicableTier = $tier;
                    }
                }

                if ($applicableTier) {
                    if ($applicableTier['type'] === 'percentage') {
                        return $subtotal * ($applicableTier['value'] / 100);
                    } else {
                        return $applicableTier['value'];
                    }
                }
                return 0;

            default:
                return 0;
        }
    }

    /**
     * Apply a coupon to a sale.
     *
     * @param Sale $sale
     * @param string $code
     * @param Customer|null $customer
     * @return CouponRedemption
     * @throws \Exception If validation fails
     */
    public function applyCoupon(Sale $sale, string $code, ?Customer $customer = null): CouponRedemption
    {
        $coupon = Coupon::where('code', $code)->first();
        if (!$coupon) {
            throw new \Exception('Coupon not found.');
        }

        $validation = $this->validate($coupon, $sale, $customer);
        if (!$validation['valid']) {
            throw new \Exception(implode(' ', $validation['errors']));
        }

        $discountAmount = $this->calculateDiscount($coupon, $sale->subtotal, $sale->items->toArray());

        DB::beginTransaction();
        try {
            // Create redemption record
            $redemption = CouponRedemption::create([
                'coupon_id' => $coupon->id,
                'sale_id' => $sale->id,
                'customer_id' => $customer?->id,
                'redeemed_by_user_id' => auth()->id(),
                'discount_amount' => $discountAmount,
                'redeemed_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Link coupon to sale via sale_coupons
            SaleCoupon::create([
                'sale_id' => $sale->id,
                'coupon_id' => $coupon->id,
                'coupon_redemption_id' => $redemption->id,
                'discount_amount' => $discountAmount,
            ]);

            // Update sale's coupon discount total
            $sale->coupon_discount_amount = $sale->saleCoupons()->sum('discount_amount');
            $sale->applied_coupon_ids = $sale->coupons()->pluck('coupons.id')->toArray();
            $sale->calculateTotals(); // recalculates total_amount with coupon discount
            $sale->save();

            DB::commit();
            return $redemption;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove a coupon from a sale.
     */
    public function removeCoupon(Sale $sale, Coupon $coupon): void
    {
        DB::beginTransaction();
        try {
            $sale->saleCoupons()->where('coupon_id', $coupon->id)->delete();
            $sale->couponRedemptions()->where('coupon_id', $coupon->id)->delete();

            $sale->coupon_discount_amount = $sale->saleCoupons()->sum('discount_amount');
            $sale->applied_coupon_ids = $sale->coupons()->pluck('coupons.id')->toArray();
            $sale->calculateTotals();
            $sale->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate bulk coupons from a batch.
     */
    /**
     * Generate the coupons belonging to a batch.
     *
     * Runs in a single transaction: either the whole batch is written or none of
     * it is, so a failure can never leave a "generating" batch with half its
     * codes. Codes are checked against soft-deleted coupons too, because
     * `coupons.code` is unique across live and trashed rows.
     *
     * @return array{generated: int, skipped: int}
     */
    public function generateBulkCoupons(CouponBatch $batch): array
    {
        if ($batch->status === CouponBatch::STATUS_COMPLETED) {
            return ['generated' => 0, 'skipped' => 0];
        }

        $batch->update(['status' => CouponBatch::STATUS_GENERATING]);

        try {
            $result = DB::transaction(function () use ($batch) {
                $settings = array_merge([
                    'type' => Coupon::TYPE_FIXED,
                    'discount_value' => 0,
                    'start_date' => now(),
                    'end_date' => null,
                    'active' => true,
                    'timezone' => config('app.timezone', 'Asia/Colombo'),
                ], $batch->settings ?? []);

                $count = $batch->remainingToGenerate();
                $startIndex = (int) $batch->generated_count;
                // {SEQ:n} starts here instead of 1, so a batch can continue an
                // existing numbering (e.g. 501, 502, ...).
                $seqStart = (int) ($batch->settings['seq_start'] ?? 1);
                $generated = 0;
                $skipped = 0;

                for ($i = 0; $i < $count; $i++) {
                    $code = null;

                    // Retry with fresh randomness; a duplicate is a skip, never an
                    // SQL error.
                    for ($attempt = 0; $attempt < 10; $attempt++) {
                        $candidate = $this->generateCodeFromPattern($batch->pattern, $startIndex + $i + $attempt, $seqStart);

                        if ($candidate !== '' && ! Coupon::withTrashed()->where('code', $candidate)->exists()) {
                            $code = $candidate;
                            break;
                        }
                    }

                    if ($code === null) {
                        $skipped++;
                        continue;
                    }

                    Coupon::create(array_merge($settings, [
                        'name' => $batch->name . ' #' . ($startIndex + $i + 1),
                        'code' => $code,
                        'batch_id' => $batch->id,
                    ]));

                    $generated++;
                }

                $batch->forceFill([
                    'generated_count' => $startIndex + $generated,
                    'status' => CouponBatch::STATUS_COMPLETED,
                ])->save();

                return ['generated' => $generated, 'skipped' => $skipped];
            });
        } catch (\Throwable $e) {
            $batch->forceFill(['status' => CouponBatch::STATUS_FAILED])->save();

            throw $e;
        }

        return $result;
    }

    /**
     * Does this pattern contain at least one placeholder that keeps codes unique?
     */
    public function patternHasPlaceholder(string $pattern): bool
    {
        return (bool) preg_match('/\{(RANDOM|NUMBER|NUM|DIGITS|SEQUENTIAL|SEQ|DATE|TIME)/i', $pattern);
    }

    /**
     * Expand a code pattern into one concrete coupon code.
     *
     * Supported placeholders (case-insensitive, colon optional):
     *   {RANDOM:N}   N mixed letters + digits (always contains at least one digit)
     *   {NUMBER:N}   N digits only          (aliases: {NUM:N}, {DIGITS:N})
     *   {SEQ:N}      zero-padded running number, starting at the batch's start number
     *                (alias {SEQUENTIAL:N}; {SEQ:4:501} sets length and start inline)
     *   {DATE}       yyyymmdd today         {DATE-YMD} same        {TIME} unix timestamp
     *
     * @param  int       $index    Position inside the batch (0 based).
     * @param  int|null  $seqStart First number used by {SEQ:...} (defaults to 1).
     */
    private function generateCodeFromPattern(string $pattern, int $index = 0, ?int $seqStart = null): string
    {
        $code = $pattern;
        $seqBase = $seqStart ?? 1;

        // {NUMBER:N} / {NUM:N} / {DIGITS:N} — digits only. Handled before
        // {RANDOM} so the words cannot shadow each other.
        $code = preg_replace_callback('/\{(?:NUMBER|NUM|DIGITS):?(\d*)\}/i', function ($m) {
            $length = $m[1] !== '' ? (int) $m[1] : 6;

            return $this->numericCodeSegment(max(1, min($length, 32)));
        }, $code);

        // {RANDOM:N} / {RANDOMN} / {RANDOM} — mixed letters and digits.
        $code = preg_replace_callback('/\{RANDOM:?(\d*)\}/i', function ($m) {
            $length = $m[1] !== '' ? (int) $m[1] : 6;

            return $this->randomCodeSegment(max(1, min($length, 32)));
        }, $code);

        // {SEQ:4}, {SEQUENTIAL:4}, {SEQ4}, and {SEQ:4:501} (length:start)
        $code = preg_replace_callback('/\{(?:SEQUENTIAL|SEQ):?(\d*)(?::(\d+))?\}/i', function ($m) use ($index, $seqBase) {
            $length = $m[1] !== '' ? (int) $m[1] : 4;
            $start = ($m[2] ?? '') !== '' ? (int) $m[2] : $seqBase;

            return str_pad((string) ($start + $index), max(1, min($length, 12)), '0', STR_PAD_LEFT);
        }, $code);

        // Date / time placeholders
        $code = str_replace(['{DATE-YMD}', '{DATE}'], date('Ymd'), $code);
        $code = str_replace('{TIME}', (string) time(), $code);

        return strtoupper(trim($code));
    }

    /**
     * Mixed random segment: uppercase letters (no vowels, so codes cannot spell
     * words) plus digits, always containing at least one of each when the
     * segment is long enough.
     */
    private function randomCodeSegment(int $length): string
    {
        $digits = '0123456789';
        $letters = 'BCDFGHJKLMNPQRSTVWXYZ';

        if ($length === 1) {
            $pool = $digits.$letters;

            return $pool[random_int(0, strlen($pool) - 1)];
        }

        $chars = [
            $digits[random_int(0, strlen($digits) - 1)],
            $letters[random_int(0, strlen($letters) - 1)],
        ];

        $pool = $digits.$letters;
        for ($i = 2; $i < $length; $i++) {
            $chars[] = $pool[random_int(0, strlen($pool) - 1)];
        }

        shuffle($chars);

        return implode('', $chars);
    }

    /**
     * Digits-only random segment.
     */
    private function numericCodeSegment(int $length): string
    {
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= (string) random_int(0, 9);
        }

        return $out;
    }

    /**
     * Get redemption statistics for a coupon.
     */
    public function getRedemptionStats(Coupon $coupon): array
    {
        $redemptions = $coupon->redemptions();

        return [
            'total_redemptions' => $redemptions->count(),
            'total_discount_amount' => $redemptions->sum('discount_amount'),
            'unique_customers' => $redemptions->distinct('customer_id')->count('customer_id'),
            'redemptions_today' => $redemptions->today()->count(),
            'redemptions_this_week' => $redemptions->thisWeek()->count(),
            'redemptions_this_month' => $redemptions->thisMonth()->count(),
        ];
    }

    /**
     * Get available coupons for a customer.
     */
    public function getAvailableCouponsForCustomer(Customer $customer, ?Location $location = null): array
    {
        $query = Coupon::active()
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->whereHasRemainingUses();

        // Location filter
        if ($location) {
            $query->where(function ($q) use ($location) {
                $q->where('location_restriction_type', 'all')
                    ->orWhereHas('locations', function ($q2) use ($location) {
                        $q2->where('location_id', $location->id);
                    });
            });
        }

        // Customer eligibility filter
        $query->where(function ($q) use ($customer) {
            $q->where('customer_eligibility_type', 'all')
                ->orWhere(function ($q2) use ($customer) {
                    $q2->where('customer_eligibility_type', 'new')
                        ->whereDoesntHave('redemptions', function ($q3) use ($customer) {
                            $q3->where('customer_id', $customer->id);
                        });
                })
                ->orWhere(function ($q2) use ($customer) {
                    $q2->where('customer_eligibility_type', 'existing')
                        ->whereHas('redemptions', function ($q3) use ($customer) {
                            $q3->where('customer_id', $customer->id);
                        });
                })
                ->orWhereHas('customerGroups', function ($q2) use ($customer) {
                    $q2->where('customer_group_id', $customer->group_id);
                });
        });

        return $query->get()->filter(function ($coupon) use ($customer) {
            return $coupon->canBeUsedByCustomer($customer);
        })->values()->toArray();
    }
}