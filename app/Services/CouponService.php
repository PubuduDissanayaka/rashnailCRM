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
    public function generateBulkCoupons(CouponBatch $batch): void
    {
        if ($batch->status === CouponBatch::STATUS_COMPLETED) {
            return;
        }

        $batch->update(['status' => CouponBatch::STATUS_GENERATING]);

        $pattern  = $batch->pattern;
        $count    = $batch->remainingToGenerate();
        $settings = $batch->settings ?? [];

        $defaults = [
            'type'          => Coupon::TYPE_FIXED,
            'discount_value' => 0,
            'start_date'    => now(),
            'active'        => true,
        ];

        for ($i = 0; $i < $count; $i++) {
            // Retry up to 5 times on duplicate code
            $attempts = 0;
            do {
                $code = $this->generateCodeFromPattern($pattern, $i + $attempts);
                $exists = Coupon::where('code', $code)->exists();
                $attempts++;
            } while ($exists && $attempts < 5);

            if ($exists) {
                continue; // skip if still colliding after 5 tries
            }

            Coupon::create(array_merge($defaults, $settings, [
                'name'     => $batch->name . ' #' . ($i + 1),
                'code'     => $code,
                'batch_id' => $batch->id,
            ]));

            $batch->increment('generated_count');
        }

        $batch->update(['status' => CouponBatch::STATUS_COMPLETED]);
    }

    /**
     * Generate a single coupon code from pattern.
     */
    private function generateCodeFromPattern(string $pattern, int $index = 0): string
    {
        $code = $pattern;

        // Random placeholders like {RANDOM4}, {RANDOM6}, etc.
        if (preg_match_all('/{RANDOM(\d+)}/', $code, $matches)) {
            foreach ($matches[0] as $i => $placeholder) {
                $length = (int)$matches[1][$i];
                // Use random_bytes for better entropy, then convert to hex and take necessary length
                $random = strtoupper(substr(bin2hex(random_bytes(max(1, ceil($length / 2)))), 0, $length));
                $code = str_replace($placeholder, $random, $code);
            }
        }

        // Sequential placeholders like {SEQUENTIAL4}
        if (preg_match_all('/{SEQUENTIAL(\d+)}/', $code, $matches)) {
            foreach ($matches[0] as $i => $placeholder) {
                $length = (int)$matches[1][$i];
                $seq = str_pad((string)($index + 1), $length, '0', STR_PAD_LEFT);
                $code = str_replace($placeholder, $seq, $code);
            }
        }

        // Date/Time placeholders
        $code = str_replace('{DATE-YMD}', date('Ymd'), $code);
        $code = str_replace('{DATE}', date('Ymd'), $code);
        $code = str_replace('{TIME}', (string)time(), $code);

        return $code;
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