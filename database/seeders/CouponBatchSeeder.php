<?php

namespace Database\Seeders;

use App\Models\CouponBatch;
use Illuminate\Database\Seeder;

class CouponBatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Summer Sale 2024 batch
        CouponBatch::factory()->create([
            'name' => 'Summer Sale 2024',
            'description' => 'Coupons for summer sale campaign 2024',
            'pattern' => 'SUMMER{RANDOM6}',
            'count' => 500,
            'generated_count' => 500,
            'status' => CouponBatch::STATUS_COMPLETED,
            'settings' => [
                'type' => 'percentage',
                'discount_value' => 20,
                'minimum_purchase_amount' => 30,
                'start_date' => '2024-06-01',
                'end_date' => '2024-08-31',
                'location_restriction_type' => 'all',
            ],
        ]);
        
        // 2. Holiday Special batch
        CouponBatch::factory()->create([
            'name' => 'Holiday Special 2024',
            'description' => 'Holiday season discount coupons',
            'pattern' => 'HOLIDAY{RANDOM6}',
            'count' => 1000,
            'generated_count' => 750,
            'status' => CouponBatch::STATUS_GENERATING,
            'settings' => [
                'type' => 'fixed',
                'discount_value' => 15,
                'minimum_purchase_amount' => 50,
                'start_date' => '2024-11-15',
                'end_date' => '2024-12-31',
                'customer_eligibility_type' => 'all',
            ],
        ]);
        
        // 3. New Year Promotion batch
        CouponBatch::factory()->create([
            'name' => 'New Year Promotion 2025',
            'description' => 'New Year promotional coupons for 2025',
            'pattern' => 'NY2025{RANDOM6}',
            'count' => 300,
            'generated_count' => 300,
            'status' => CouponBatch::STATUS_COMPLETED,
            'settings' => [
                'type' => 'percentage',
                'discount_value' => 25,
                'minimum_purchase_amount' => 0,
                'start_date' => '2025-01-01',
                'end_date' => '2025-01-31',
                'product_restriction_type' => 'specific',
            ],
        ]);
        
        // 4. Flash Sale batch (limited quantity)
        CouponBatch::factory()->create([
            'name' => 'Flash Sale Batch',
            'description' => 'Limited quantity coupons for flash sales',
            'pattern' => 'FLASH{RANDOM4}',
            'count' => 100,
            'generated_count' => 100,
            'status' => CouponBatch::STATUS_COMPLETED,
            'settings' => [
                'type' => 'fixed',
                'discount_value' => 10,
                'minimum_purchase_amount' => 20,
                'start_date' => now()->subDays(5)->format('Y-m-d'),
                'end_date' => now()->addDays(2)->format('Y-m-d'),
                'total_usage_limit' => 1,
            ],
        ]);
        
        // 5. Referral Program batch
        CouponBatch::factory()->create([
            'name' => 'Referral Program 2024',
            'description' => 'Coupons for customer referral program',
            'pattern' => 'REFER{RANDOM6}',
            'count' => 2000,
            'generated_count' => 1200,
            'status' => CouponBatch::STATUS_PENDING,
            'settings' => [
                'type' => 'percentage',
                'discount_value' => 10,
                'minimum_purchase_amount' => 0,
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'customer_eligibility_type' => 'new',
            ],
        ]);
        
        // Additional batches for variety
        CouponBatch::factory()->create([
            'name' => 'Employee Discount Batch',
            'description' => 'Internal employee discount coupons',
            'pattern' => 'EMP{RANDOM6}',
            'count' => 50,
            'generated_count' => 50,
            'status' => CouponBatch::STATUS_COMPLETED,
            'settings' => [
                'type' => 'percentage',
                'discount_value' => 40,
                'minimum_purchase_amount' => 0,
                'location_restriction_type' => 'specific',
            ],
        ]);
        
        CouponBatch::factory()->create([
            'name' => 'Loyalty Reward Batch',
            'description' => 'Reward coupons for loyal customers',
            'pattern' => 'LOYAL{RANDOM6}',
            'count' => 800,
            'generated_count' => 0,
            'status' => CouponBatch::STATUS_PENDING,
            'settings' => [
                'type' => 'tiered',
                'discount_value' => 15,
                'minimum_purchase_amount' => 100,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addYear()->format('Y-m-d'),
            ],
        ]);
        
        CouponBatch::factory()->create([
            'name' => 'Website Signup Bonus',
            'description' => 'Coupons for new website signups',
            'pattern' => 'WELCOME{RANDOM4}',
            'count' => 10000,
            'generated_count' => 4500,
            'status' => CouponBatch::STATUS_GENERATING,
            'settings' => [
                'type' => 'fixed',
                'discount_value' => 5,
                'minimum_purchase_amount' => 0,
                'start_date' => now()->subMonths(3)->format('Y-m-d'),
                'end_date' => now()->addMonths(3)->format('Y-m-d'),
                'per_customer_limit' => 1,
            ],
        ]);
        
        // Create some random batches
        CouponBatch::factory()->count(5)->create();
        
        $this->command->info('Coupon batches seeded successfully.');
    }
}