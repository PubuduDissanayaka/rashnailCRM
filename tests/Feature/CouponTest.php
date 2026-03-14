<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponBatch;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use App\Models\Location;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected CouponService $couponService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->couponService = new CouponService();
    }

    private function uniqueCode(string $prefix): string
    {
        return $prefix . '_' . mt_rand(1000, 9999);
    }

    /** @test */
    public function it_creates_a_coupon()
    {
        $code = $this->uniqueCode('TEST10');
        $coupon = Coupon::create([
            'code' => $code,
            'name' => 'Test Coupon',
            'type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 10,
            'minimum_purchase_amount' => 0,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'total_usage_limit' => 100,
            'per_customer_limit' => 1,
            'stackable' => false,
            'active' => true,
            'location_restriction_type' => 'all',
            'customer_eligibility_type' => 'all',
            'product_restriction_type' => 'all',
        ]);

        $this->assertDatabaseHas('coupons', ['code' => $code]);
        $this->assertEquals('percentage', $coupon->type);
        $this->assertTrue($coupon->isActive());
    }

    /** @test */
    public function it_validates_active_coupon()
    {
        $coupon = Coupon::create([
            'code' => $this->uniqueCode('ACTIVE20'),
            'name' => 'Active Coupon',
            'type' => Coupon::TYPE_FIXED,
            'discount_value' => 20,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'active' => true,
            'minimum_purchase_amount' => 0,
        ]);

        $sale = Sale::create([
            'sale_number' => 'SALE-' . mt_rand(1000, 9999),
            'subtotal' => 100,
            'total_amount' => 100,
            'sale_date' => now(),
            'user_id' => 1,
        ]);

        $validation = $this->couponService->validate($coupon, $sale);
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
    }

    /** @test */
    public function it_rejects_expired_coupon()
    {
        $coupon = Coupon::create([
            'code' => $this->uniqueCode('EXPIRED'),
            'name' => 'Expired Coupon',
            'type' => Coupon::TYPE_FIXED,
            'discount_value' => 10,
            'start_date' => now()->subMonth(),
            'end_date' => now()->subDay(),
            'active' => true,
            'minimum_purchase_amount' => 0,
        ]);

        $sale = Sale::create([
            'sale_number' => 'TEST_SALE-' . mt_rand(1000, 9999),
            'subtotal' => 100,
            'total_amount' => 100,
            'sale_date' => now(),
            'user_id' => 1,
        ]);

        $validation = $this->couponService->validate($coupon, $sale);
        $this->assertFalse($validation['valid']);
        $this->assertContains('Coupon is not active or has expired.', $validation['errors']);
    }

    /** @test */
    public function it_rejects_coupon_with_usage_limit_reached()
    {
        $coupon = Coupon::create([
            'code' => $this->uniqueCode('LIMIT5'),
            'name' => 'Limited Coupon',
            'type' => Coupon::TYPE_FIXED,
            'discount_value' => 5,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'total_usage_limit' => 1,
            'active' => true,
            'minimum_purchase_amount' => 0,
        ]);

        // Create a redemption to reach limit
        \App\Models\CouponRedemption::create([
            'coupon_id' => $coupon->id,
            'sale_id' => 999, // dummy sale ID
            'redeemed_at' => now(),
            'discount_amount' => 5,
        ]);

        $sale = Sale::create([
            'sale_number' => 'TEST_SALE-' . mt_rand(1000, 9999),
            'subtotal' => 100,
            'total_amount' => 100,
            'sale_date' => now(),
            'user_id' => 1,
        ]);

        $validation = $this->couponService->validate($coupon, $sale);
        $this->assertFalse($validation['valid']);
        $this->assertContains('Coupon usage limit reached.', $validation['errors']);
    }

    /** @test */
    public function it_calculates_percentage_discount()
    {
        $coupon = Coupon::create([
            'code' => $this->uniqueCode('PERCENT15'),
            'name' => '15% Off',
            'type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 15,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'active' => true,
            'minimum_purchase_amount' => 0,
        ]);

        $discount = $this->couponService->calculateDiscount($coupon, 200);
        $this->assertEquals(30, $discount); // 15% of 200 = 30
    }

    /** @test */
    public function it_respects_max_discount_amount()
    {
        $coupon = Coupon::create([
            'code' => $this->uniqueCode('PERCENT10MAX20'),
            'name' => '10% off max $20',
            'type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 10,
            'max_discount_amount' => 20,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'active' => true,
            'minimum_purchase_amount' => 0,
        ]);

        $discount = $this->couponService->calculateDiscount($coupon, 500); // 10% = 50, but max 20
        $this->assertEquals(20, $discount);
    }

    /** @test */
    public function it_calculates_fixed_discount()
    {
        $coupon = Coupon::create([
            'code' => $this->uniqueCode('FIXED25'),
            'name' => '$25 Off',
            'type' => Coupon::TYPE_FIXED,
            'discount_value' => 25,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'active' => true,
            'minimum_purchase_amount' => 0,
        ]);

        $discount = $this->couponService->calculateDiscount($coupon, 100);
        $this->assertEquals(25, $discount);

        $discount = $this->couponService->calculateDiscount($coupon, 20); // subtotal less than discount
        $this->assertEquals(20, $discount); // should not exceed subtotal
    }

    /** @test */
    public function it_validates_minimum_purchase_amount()
    {
        $coupon = Coupon::create([
            'code' => $this->uniqueCode('MIN100'),
            'name' => 'Minimum $100',
            'type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 10,
            'minimum_purchase_amount' => 100,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'active' => true,
        ]);

        $saleLow = Sale::create([
            'sale_number' => 'TEST_SALE-LOW-' . mt_rand(1000, 9999),
            'subtotal' => 80,
            'total_amount' => 80,
            'sale_date' => now(),
            'user_id' => 1,
        ]);

        $validation = $this->couponService->validate($coupon, $saleLow);
        $this->assertFalse($validation['valid']);
        $this->assertContains('Minimum purchase amount not met.', $validation['errors']);

        $saleHigh = Sale::create([
            'sale_number' => 'TEST_SALE-HIGH-' . mt_rand(1000, 9999),
            'subtotal' => 150,
            'total_amount' => 150,
            'sale_date' => now(),
            'user_id' => 1,
        ]);

        $validation = $this->couponService->validate($coupon, $saleHigh);
        $this->assertTrue($validation['valid']);
    }

    /** @test */
    public function it_applies_coupon_to_sale()
    {
        $couponCode = 'APPLY10_' . mt_rand(1000, 9999);
        $coupon = Coupon::create([
            'code' => $couponCode,
            'name' => 'Apply Test',
            'type' => Coupon::TYPE_FIXED,
            'discount_value' => 10,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'active' => true,
            'minimum_purchase_amount' => 0,
            'total_usage_limit' => 10,
        ]);

        $saleNumber = 'TEST_SALE-APPLY-' . mt_rand(1000, 9999);
        $sale = Sale::create([
            'sale_number' => $saleNumber,
            'subtotal' => 100,
            'total_amount' => 100,
            'sale_date' => now(),
            'user_id' => 1,
        ]);

        // Create a service as sellable
        $service = Service::create([
            'name' => 'Test Service ' . mt_rand(1000, 9999),
            'price' => 100,
            'duration' => 60,
            'is_active' => true,
        ]);

        // Create a sale item to match subtotal
        SaleItem::create([
            'sale_id' => $sale->id,
            'sellable_type' => Service::class,
            'sellable_id' => $service->id,
            'item_name' => 'Test Product',
            'item_code' => 'TEST001',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        // Mock authentication
        $this->actingAs(\App\Models\User::factory()->create());

        $redemption = $this->couponService->applyCoupon($sale, $coupon->code);

        $this->assertDatabaseHas('coupon_redemptions', [
            'coupon_id' => $coupon->id,
            'sale_id' => $sale->id,
        ]);

        $sale->refresh();
        $this->assertEquals(10, $sale->coupon_discount_amount);
        $this->assertEquals(90, $sale->total_amount); // subtotal - discount
    }

    /** @test */
    public function it_generates_bulk_coupons_from_batch()
    {
        $batch = CouponBatch::create([
            'name' => 'Test Batch',
            'pattern' => 'BATCH-{RANDOM6}',
            'count' => 5,
            'settings' => [
                'name' => 'Bulk Coupon',
                'type' => Coupon::TYPE_FIXED,
                'discount_value' => 5,
                'start_date' => now()->subDay(),
                'end_date' => now()->addMonth(),
                'active' => true,
            ],
        ]);

        $this->couponService->generateBulkCoupons($batch);

        $this->assertEquals(5, $batch->generated_count);
        $this->assertEquals('completed', $batch->status);
        $this->assertEquals(5, Coupon::where('batch_id', $batch->id)->count());
    }
}