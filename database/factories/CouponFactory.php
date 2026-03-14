<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\CouponBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            Coupon::TYPE_PERCENTAGE,
            Coupon::TYPE_FIXED,
            Coupon::TYPE_BOGO,
            Coupon::TYPE_FREE_SHIPPING,
            Coupon::TYPE_TIERED,
        ];
        $type = $this->faker->randomElement($types);
        
        // Determine discount value based on type
        $discountValue = match($type) {
            Coupon::TYPE_PERCENTAGE => $this->faker->randomElement([10, 15, 20, 25, 30, 50]),
            Coupon::TYPE_FIXED => $this->faker->randomElement([5, 10, 15, 20, 25, 50]),
            Coupon::TYPE_BOGO => $this->faker->randomElement([1, 2]), // buy X get Y
            Coupon::TYPE_FREE_SHIPPING => 0,
            Coupon::TYPE_TIERED => $this->faker->randomElement([10, 20, 30]),
        };

        $startDate = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $endDate = $this->faker->optional(0.7)->dateTimeBetween($startDate, '+6 months');
        
        return [
            'code' => strtoupper($this->faker->bothify('???###')),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'type' => $type,
            'discount_value' => $discountValue,
            'max_discount_amount' => $type === Coupon::TYPE_PERCENTAGE ? $this->faker->optional(0.5)->randomFloat(2, 10, 100) : null,
            'minimum_purchase_amount' => $this->faker->optional(0.6)->randomFloat(2, 10, 200) ?? 0,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'timezone' => $this->faker->timezone(),
            'total_usage_limit' => $this->faker->optional(0.7)->numberBetween(10, 1000),
            'per_customer_limit' => $this->faker->optional(0.8)->numberBetween(1, 5) ?? 1,
            'stackable' => $this->faker->boolean(30),
            'active' => $this->faker->boolean(80),
            'location_restriction_type' => $this->faker->randomElement([
                Coupon::LOCATION_RESTRICTION_ALL,
                Coupon::LOCATION_RESTRICTION_SPECIFIC,
            ]),
            'customer_eligibility_type' => $this->faker->randomElement([
                Coupon::CUSTOMER_ELIGIBILITY_ALL,
                Coupon::CUSTOMER_ELIGIBILITY_NEW,
                Coupon::CUSTOMER_ELIGIBILITY_EXISTING,
                Coupon::CUSTOMER_ELIGIBILITY_GROUPS,
            ]),
            'product_restriction_type' => $this->faker->randomElement([
                Coupon::PRODUCT_RESTRICTION_ALL,
                Coupon::PRODUCT_RESTRICTION_SPECIFIC,
                Coupon::PRODUCT_RESTRICTION_CATEGORIES,
            ]),
            'metadata' => $this->faker->optional()->passthrough([
                'notes' => $this->faker->sentence(),
                'created_by' => $this->faker->name(),
            ]),
            'batch_id' => $this->faker->optional(0.5)->passthrough(CouponBatch::inRandomOrder()->first()?->id ?? CouponBatch::factory()),
        ];
    }

    /**
     * Indicate that the coupon is a percentage discount.
     */
    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => $this->faker->randomElement([10, 15, 20, 25, 30, 50]),
        ]);
    }

    /**
     * Indicate that the coupon is a fixed amount discount.
     */
    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Coupon::TYPE_FIXED,
            'discount_value' => $this->faker->randomElement([5, 10, 15, 20, 25, 50]),
        ]);
    }

    /**
     * Indicate that the coupon is a BOGO (buy X get Y).
     */
    public function bogo(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Coupon::TYPE_BOGO,
            'discount_value' => $this->faker->randomElement([1, 2]),
            'metadata' => [
                'buy_quantity' => $this->faker->numberBetween(1, 3),
                'get_quantity' => 1,
                'free_product_id' => $this->faker->optional()->numberBetween(1, 100),
            ],
        ]);
    }

    /**
     * Indicate that the coupon offers free shipping.
     */
    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Coupon::TYPE_FREE_SHIPPING,
            'discount_value' => 0,
        ]);
    }

    /**
     * Indicate that the coupon is tiered.
     */
    public function tiered(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Coupon::TYPE_TIERED,
            'discount_value' => $this->faker->randomElement([10, 20, 30]),
            'metadata' => [
                'tiers' => [
                    ['min_amount' => 100, 'discount' => 10],
                    ['min_amount' => 200, 'discount' => 20],
                    ['min_amount' => 500, 'discount' => 30],
                ],
            ],
        ]);
    }

    /**
     * Indicate that the coupon is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the coupon is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Indicate that the coupon has no usage limit.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_usage_limit' => null,
            'per_customer_limit' => null,
        ]);
    }

    /**
     * Indicate that the coupon has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $this->faker->dateTimeBetween('-6 months', '-1 month'),
            'end_date' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    /**
     * Indicate that the coupon is scheduled for future.
     */
    public function future(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $this->faker->dateTimeBetween('+1 week', '+2 months'),
            'end_date' => $this->faker->dateTimeBetween('+3 months', '+6 months'),
        ]);
    }
}