<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\CouponBatch;
use App\Models\CustomerGroup;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a variety of coupon types using firstOrCreate to avoid duplicates
        
        // 1. Percentage discounts (10%, 20%, 50% off)
        Coupon::firstOrCreate(
            ['code' => 'SAVE10'],
            [
                'name' => '10% Off Everything',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_PERCENTAGE,
                'discount_value' => 10,
                'max_discount_amount' => 50,
                'minimum_purchase_amount' => 25,
                'start_date' => now()->subDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(30, 90)),
                'timezone' => 'UTC',
                'total_usage_limit' => 100,
                'per_customer_limit' => 1,
                'stackable' => false,
                'active' => true,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => null,
            ]
        );
        
        Coupon::firstOrCreate(
            ['code' => 'SAVE20'],
            [
                'name' => '20% Off Sitewide',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_PERCENTAGE,
                'discount_value' => 20,
                'max_discount_amount' => 100,
                'minimum_purchase_amount' => 50,
                'start_date' => now()->subDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(30, 90)),
                'timezone' => 'UTC',
                'total_usage_limit' => 50,
                'per_customer_limit' => 2,
                'stackable' => false,
                'active' => true,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => null,
            ]
        );
        
        Coupon::firstOrCreate(
            ['code' => 'HALFOFF'],
            [
                'name' => '50% Off Clearance',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_PERCENTAGE,
                'discount_value' => 50,
                'max_discount_amount' => null,
                'minimum_purchase_amount' => 0,
                'start_date' => now()->subDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(30, 90)),
                'timezone' => 'UTC',
                'total_usage_limit' => 20,
                'per_customer_limit' => 1,
                'stackable' => false,
                'active' => false,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => null,
            ]
        );
        
        // 2. Fixed amount discounts ($5, $10, $25 off)
        Coupon::firstOrCreate(
            ['code' => 'SAVE5'],
            [
                'name' => '$5 Off Your Order',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_FIXED,
                'discount_value' => 5,
                'max_discount_amount' => null,
                'minimum_purchase_amount' => 10,
                'start_date' => now()->subDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(30, 90)),
                'timezone' => 'UTC',
                'total_usage_limit' => 200,
                'per_customer_limit' => 1,
                'stackable' => false,
                'active' => true,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => null,
            ]
        );
        
        Coupon::firstOrCreate(
            ['code' => 'SAVE10DOLLAR'],
            [
                'name' => '$10 Off $50+',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_FIXED,
                'discount_value' => 10,
                'max_discount_amount' => null,
                'minimum_purchase_amount' => 50,
                'start_date' => now()->subDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(30, 90)),
                'timezone' => 'UTC',
                'total_usage_limit' => 100,
                'per_customer_limit' => 1,
                'stackable' => false,
                'active' => true,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => null,
            ]
        );
        
        Coupon::firstOrCreate(
            ['code' => 'BIG25'],
            [
                'name' => '$25 Off $100+',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_FIXED,
                'discount_value' => 25,
                'max_discount_amount' => null,
                'minimum_purchase_amount' => 100,
                'start_date' => now()->subDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(30, 90)),
                'timezone' => 'UTC',
                'total_usage_limit' => 50,
                'per_customer_limit' => 1,
                'stackable' => false,
                'active' => true,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => null,
            ]
        );
        
        // 3. Buy X Get Y (BOGO) coupons
        Coupon::firstOrCreate(
            ['code' => 'BOGO1'],
            [
                'name' => 'Buy One Get One Free',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_BOGO,
                'discount_value' => 1,
                'max_discount_amount' => null,
                'minimum_purchase_amount' => 0,
                'start_date' => now()->subDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(30, 90)),
                'timezone' => 'UTC',
                'total_usage_limit' => 30,
                'per_customer_limit' => 1,
                'stackable' => false,
                'active' => true,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => [
                    'buy_quantity' => 1,
                    'get_quantity' => 1,
                    'free_product_id' => null,
                ],
            ]
        );
        
        Coupon::firstOrCreate(
            ['code' => 'BUY2GET1'],
            [
                'name' => 'Buy 2 Get 1 Free',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_BOGO,
                'discount_value' => 1,
                'max_discount_amount' => null,
                'minimum_purchase_amount' => 0,
                'start_date' => now()->subDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(30, 90)),
                'timezone' => 'UTC',
                'total_usage_limit' => 20,
                'per_customer_limit' => 1,
                'stackable' => false,
                'active' => true,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => [
                    'buy_quantity' => 2,
                    'get_quantity' => 1,
                    'free_product_id' => null,
                ],
            ]
        );
        
        // 4. Free shipping coupons
        Coupon::firstOrCreate(
            ['code' => 'FREESHIP'],
            [
                'name' => 'Free Shipping on All Orders',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_FREE_SHIPPING,
                'discount_value' => 0,
                'max_discount_amount' => null,
                'minimum_purchase_amount' => 0,
                'start_date' => now()->subDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(30, 90)),
                'timezone' => 'UTC',
                'total_usage_limit' => null,
                'per_customer_limit' => 1,
                'stackable' => false,
                'active' => true,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => null,
            ]
        );
        
        Coupon::firstOrCreate(
            ['code' => 'SHIP50'],
            [
                'name' => 'Free Shipping on Orders $50+',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_FREE_SHIPPING,
                'discount_value' => 0,
                'max_discount_amount' => null,
                'minimum_purchase_amount' => 50,
                'start_date' => now()->subDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(30, 90)),
                'timezone' => 'UTC',
                'total_usage_limit' => 100,
                'per_customer_limit' => 1,
                'stackable' => false,
                'active' => true,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => null,
            ]
        );
        
        // 5. Tiered discounts (spend $100 get $20 off)
        Coupon::firstOrCreate(
            ['code' => 'TIERED100'],
            [
                'name' => 'Spend $100 Get $20 Off',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_TIERED,
                'discount_value' => 20,
                'max_discount_amount' => null,
                'minimum_purchase_amount' => 100,
                'start_date' => now()->subDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(30, 90)),
                'timezone' => 'UTC',
                'total_usage_limit' => 40,
                'per_customer_limit' => 1,
                'stackable' => false,
                'active' => true,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => [
                    'tiers' => [
                        ['min_amount' => 100, 'discount' => 20],
                    ],
                ],
            ]
        );
        
        Coupon::firstOrCreate(
            ['code' => 'TIEREDMULTI'],
            [
                'name' => 'Multi-Tier Discount',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_TIERED,
                'discount_value' => 30,
                'max_discount_amount' => null,
                'minimum_purchase_amount' => 50,
                'start_date' => now()->subDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(30, 90)),
                'timezone' => 'UTC',
                'total_usage_limit' => 25,
                'per_customer_limit' => 1,
                'stackable' => false,
                'active' => true,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => [
                    'tiers' => [
                        ['min_amount' => 50, 'discount' => 10],
                        ['min_amount' => 100, 'discount' => 20],
                        ['min_amount' => 200, 'discount' => 30],
                    ],
                ],
            ]
        );
        
        // 6. Mix of active and inactive coupons
        Coupon::firstOrCreate(
            ['code' => 'OLDCODE'],
            [
                'name' => 'Expired Coupon',
                'description' => 'This coupon is no longer valid',
                'type' => Coupon::TYPE_PERCENTAGE,
                'discount_value' => 15,
                'max_discount_amount' => null,
                'minimum_purchase_amount' => 0,
                'start_date' => now()->subMonths(3),
                'end_date' => now()->subMonths(1),
                'timezone' => 'UTC',
                'total_usage_limit' => 10,
                'per_customer_limit' => 1,
                'stackable' => false,
                'active' => false,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => null,
            ]
        );
        
        Coupon::firstOrCreate(
            ['code' => 'NEWYEAR'],
            [
                'name' => 'New Year Special',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_PERCENTAGE,
                'discount_value' => 20,
                'max_discount_amount' => null,
                'minimum_purchase_amount' => 0,
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(30),
                'timezone' => 'UTC',
                'total_usage_limit' => 200,
                'per_customer_limit' => 1,
                'stackable' => false,
                'active' => true,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => null,
            ]
        );
        
        // 7. Some with usage limits, some without
        Coupon::firstOrCreate(
            ['code' => 'UNLIMITED'],
            [
                'name' => 'Unlimited Use Coupon',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_FIXED,
                'discount_value' => 5,
                'max_discount_amount' => null,
                'minimum_purchase_amount' => 0,
                'start_date' => now()->subDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(30, 90)),
                'timezone' => 'UTC',
                'total_usage_limit' => null,
                'per_customer_limit' => 999,
                'stackable' => false,
                'active' => true,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => null,
            ]
        );
        
        // 8. Various date ranges (some expired, some future, some current)
        Coupon::firstOrCreate(
            ['code' => 'EXPIRED'],
            [
                'name' => 'Expired Discount',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_PERCENTAGE,
                'discount_value' => 15,
                'max_discount_amount' => null,
                'minimum_purchase_amount' => 0,
                'start_date' => now()->subMonths(3),
                'end_date' => now()->subMonths(1),
                'timezone' => 'UTC',
                'total_usage_limit' => 50,
                'per_customer_limit' => 1,
                'stackable' => false,
                'active' => true,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => null,
            ]
        );
        
        Coupon::firstOrCreate(
            ['code' => 'FUTURE'],
            [
                'name' => 'Upcoming Promotion',
                'description' => fake()->paragraph(),
                'type' => Coupon::TYPE_FIXED,
                'discount_value' => 10,
                'max_discount_amount' => null,
                'minimum_purchase_amount' => 0,
                'start_date' => now()->addDays(7),
                'end_date' => now()->addDays(60),
                'timezone' => 'UTC',
                'total_usage_limit' => 100,
                'per_customer_limit' => 1,
                'stackable' => false,
                'active' => true,
                'location_restriction_type' => Coupon::LOCATION_RESTRICTION_ALL,
                'customer_eligibility_type' => Coupon::CUSTOMER_ELIGIBILITY_ALL,
                'product_restriction_type' => Coupon::PRODUCT_RESTRICTION_ALL,
                'metadata' => null,
            ]
        );
        
        // 9. Additional random coupons for variety
        // Use factory for random coupons (they have random codes, duplicates unlikely)
        Coupon::factory()->count(15)->create();
        
        // 10. Link some coupons to batches
        $batch = CouponBatch::inRandomOrder()->first();
        if ($batch) {
            Coupon::factory()->count(5)->create(['batch_id' => $batch->id]);
        }
        
        // 11. Link some coupons to customer groups
        $coupons = Coupon::inRandomOrder()->limit(10)->get();
        $customerGroups = CustomerGroup::all();
        if ($coupons->isNotEmpty() && $customerGroups->isNotEmpty()) {
            foreach ($coupons as $coupon) {
                $coupon->customerGroups()->attach(
                    $customerGroups->random(rand(1, 3))->pluck('id')->toArray()
                );
            }
        }
        
        $this->command->info('Coupons seeded successfully.');
    }
}