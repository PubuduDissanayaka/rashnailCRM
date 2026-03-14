<?php

namespace Database\Factories;

use App\Models\CouponBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CouponBatch>
 */
class CouponBatchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CouponBatch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'pattern' => 'BATCH{RANDOM6}',
            'count' => fake()->numberBetween(10, 100),
            'generated_count' => 0,
            'status' => CouponBatch::STATUS_PENDING,
            'settings' => [
                'type' => 'percentage',
                'discount_value' => 10,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addMonth()->format('Y-m-d'),
            ],
        ];
    }
}