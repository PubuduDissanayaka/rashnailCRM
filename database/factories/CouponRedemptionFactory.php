<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CouponRedemption>
 */
class CouponRedemptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CouponRedemption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $coupon = Coupon::inRandomOrder()->first() ?? Coupon::factory()->create();
        $customer = Customer::inRandomOrder()->first() ?? Customer::factory()->create();
        $sale = Sale::inRandomOrder()->first() ?? Sale::factory()->create();
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        $start = $coupon->start_date ?? now()->subMonth();
        $end = $coupon->end_date ?? now();
        if ($start > $end) {
            // Swap dates if invalid
            $temp = $start;
            $start = $end;
            $end = $temp;
        }
        $redeemedAt = $this->faker->dateTimeBetween($start, $end);
        
        // Calculate discount amount based on coupon type and sale total
        $discountAmount = $this->calculateDiscountAmount($coupon, $sale);

        return [
            'coupon_id' => $coupon->id,
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'redeemed_by_user_id' => $user->id,
            'discount_amount' => $discountAmount,
            'redeemed_at' => $redeemedAt,
            'ip_address' => $this->faker->optional()->ipv4(),
            'user_agent' => $this->faker->optional()->userAgent(),
            'metadata' => $this->faker->optional()->passthrough([
                'notes' => $this->faker->sentence(),
                'channel' => $this->faker->randomElement(['pos', 'online', 'mobile']),
            ]),
        ];
    }

    /**
     * Calculate discount amount based on coupon type and sale total.
     */
    private function calculateDiscountAmount(Coupon $coupon, Sale $sale): float
    {
        $saleTotal = $sale->total_amount ?? $this->faker->randomFloat(2, 10, 500);
        
        switch ($coupon->type) {
            case Coupon::TYPE_PERCENTAGE:
                $discount = ($coupon->discount_value / 100) * $saleTotal;
                if ($coupon->max_discount_amount) {
                    $discount = min($discount, $coupon->max_discount_amount);
                }
                return round($discount, 2);
                
            case Coupon::TYPE_FIXED:
                return min($coupon->discount_value, $saleTotal);
                
            case Coupon::TYPE_BOGO:
                // For BOGO, discount is the price of the free item
                // Simplified: assume discount equals price of one item
                $itemPrice = $saleTotal / ($coupon->metadata['buy_quantity'] ?? 2);
                return round($itemPrice, 2);
                
            case Coupon::TYPE_FREE_SHIPPING:
                // Assume shipping cost is 10% of sale total up to $20
                $shippingCost = min($saleTotal * 0.1, 20);
                return round($shippingCost, 2);
                
            case Coupon::TYPE_TIERED:
                // Apply tiered discount based on sale total
                $tiers = $coupon->metadata['tiers'] ?? [
                    ['min_amount' => 100, 'discount' => 10],
                    ['min_amount' => 200, 'discount' => 20],
                ];
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
     * Indicate that the redemption happened today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'redeemed_at' => $this->faker->dateTimeBetween('today 00:00', 'now'),
        ]);
    }

    /**
     * Indicate that the redemption happened in the past week.
     */
    public function pastWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'redeemed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the redemption happened in the past month.
     */
    public function pastMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'redeemed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the redemption has no IP address.
     */
    public function noIp(): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => null,
        ]);
    }

    /**
     * Indicate that the redemption has no user agent.
     */
    public function noUserAgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_agent' => null,
        ]);
    }
}