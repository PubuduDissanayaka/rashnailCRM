<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Database\Seeder;

class CouponRedemptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing coupons, customers, and sales
        $coupons = Coupon::all();
        $customers = Customer::all();
        $sales = Sale::all();
        
        if ($coupons->isEmpty() || $customers->isEmpty() || $sales->isEmpty()) {
            $this->command->warn('Skipping redemptions seeding: insufficient data.');
            return;
        }
        
        // Create redemptions for a subset of coupons (approximately 30%)
        $couponsToRedeem = $coupons->random(ceil($coupons->count() * 0.3));
        
        foreach ($couponsToRedeem as $coupon) {
            // Determine how many times this coupon has been used (1-5 times)
            $usageCount = rand(1, 5);
            
            for ($i = 0; $i < $usageCount; $i++) {
                // Select random customer and sale
                $customer = $customers->random();
                $sale = $sales->random();
                
                // Ensure coupon hasn't already been used for this sale (though possible, but avoid duplicates)
                $existing = CouponRedemption::where('coupon_id', $coupon->id)
                    ->where('sale_id', $sale->id)
                    ->exists();
                    
                if ($existing) {
                    continue;
                }
                
                // Create redemption using factory
                CouponRedemption::factory()->create([
                    'coupon_id' => $coupon->id,
                    'sale_id' => $sale->id,
                    'customer_id' => $customer->id,
                    'redeemed_by_user_id' => $sale->user_id ?? $customer->user_id ?? 1,
                    'discount_amount' => $this->calculateDiscountForCouponAndSale($coupon, $sale),
                    'redeemed_at' => $this->randomDateBetween($coupon->start_date, $coupon->end_date),
                ]);
            }
        }
        
        // Create some additional redemptions with specific patterns
        
        // 1. High-value redemptions (discount > $50)
        $highValueCoupons = $coupons->filter(function ($coupon) {
            return $coupon->type === 'fixed' && $coupon->discount_value >= 25;
        });
        
        foreach ($highValueCoupons->take(3) as $coupon) {
            CouponRedemption::factory()->create([
                'coupon_id' => $coupon->id,
                'discount_amount' => $coupon->discount_value,
                'redeemed_at' => now()->subDays(rand(1, 30)),
            ]);
        }
        
        // 2. Recent redemptions (today and past week)
        CouponRedemption::factory()->count(5)->today()->create();
        CouponRedemption::factory()->count(10)->pastWeek()->create();
        
        // 3. Redemptions with missing metadata (IP, user agent)
        CouponRedemption::factory()->count(3)->noIp()->create();
        CouponRedemption::factory()->count(2)->noUserAgent()->create();
        
        // 4. Bulk redemptions for a single popular coupon
        $popularCoupon = $coupons->where('code', 'SAVE10')->first() ?? $coupons->random();
        if ($popularCoupon) {
            CouponRedemption::factory()->count(8)->create([
                'coupon_id' => $popularCoupon->id,
                'redeemed_at' => now()->subDays(rand(1, 60)),
            ]);
        }
        
        $this->command->info('Coupon redemptions seeded successfully.');
    }
    
    /**
     * Calculate discount amount for a coupon and sale.
     */
    private function calculateDiscountForCouponAndSale(Coupon $coupon, Sale $sale): float
    {
        $saleTotal = $sale->total_amount ?? rand(20, 500);
        
        switch ($coupon->type) {
            case 'percentage':
                $discount = ($coupon->discount_value / 100) * $saleTotal;
                if ($coupon->max_discount_amount) {
                    $discount = min($discount, $coupon->max_discount_amount);
                }
                return round($discount, 2);
                
            case 'fixed':
                return min($coupon->discount_value, $saleTotal);
                
            case 'bogo':
                // Simplified: discount equals average item price
                $itemCount = $sale->items()->count() ?? 2;
                $itemPrice = $saleTotal / max(1, $itemCount);
                return round($itemPrice, 2);
                
            case 'free_shipping':
                return round(min($saleTotal * 0.1, 20), 2);
                
            case 'tiered':
                $tiers = $coupon->metadata['tiers'] ?? [['min_amount' => 100, 'discount' => 10]];
                $applicableTier = null;
                foreach ($tiers as $tier) {
                    if ($saleTotal >= $tier['min_amount']) {
                        $applicableTier = $tier;
                    }
                }
                if ($applicableTier) {
                    return ($applicableTier['discount'] / 100) * $saleTotal;
                }
                return 0;
                
            default:
                return 0;
        }
    }
    
    /**
     * Generate random date between start and end dates (or reasonable defaults).
     */
    private function randomDateBetween($startDate, $endDate)
    {
        $start = $startDate ? clone $startDate : now()->subMonths(3);
        $end = $endDate ? clone $endDate : now();
        
        if ($start > $end) {
            $temp = $start;
            $start = $end;
            $end = $temp;
        }
        
        $randomTimestamp = rand($start->timestamp, $end->timestamp);
        return \Carbon\Carbon::createFromTimestamp($randomTimestamp);
    }
}