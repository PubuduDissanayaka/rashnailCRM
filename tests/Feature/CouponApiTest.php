<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CouponApiTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create required permission
        Permission::firstOrCreate(['name' => 'manage system']);

        // Create admin user with 'manage system' permission
        $this->adminUser = User::factory()->create();
        $this->adminUser->givePermissionTo('manage system');
    }

    /** @test */
    public function it_validates_valid_coupon_via_api()
    {
        $coupon = Coupon::create([
            'code' => $this->faker->unique()->regexify('[A-Z0-9]{10}'),
            'name' => 'Test Coupon',
            'type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 10,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'active' => true,
            'minimum_purchase_amount' => 0,
            'location_restriction_type' => 'all',
            'customer_eligibility_type' => 'all',
            'product_restriction_type' => 'all',
        ]);

        $customer = Customer::factory()->create();
        $location = Location::factory()->create();
        $service = Service::factory()->create(['price' => 100]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/coupons/validate', [
                'code' => $coupon->code,
                'customer_id' => $customer->id,
                'location_id' => $location->id,
                'items' => [
                    [
                        'type' => 'service',
                        'id' => $service->id,
                        'price' => $service->price,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'valid',
            'coupon' => ['id', 'code', 'name', 'type', 'discount_value', 'max_discount_amount', 'stackable'],
            'discount_amount',
            'message',
        ]);
        $response->assertJson(['valid' => true]);
    }

    /** @test */
    public function it_rejects_invalid_coupon_code_via_api()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/coupons/validate', [
                'code' => 'INVALID_CODE',
                'items' => [],
            ]);

        $response->assertStatus(404);
        $response->assertJson([
            'valid' => false,
            'errors' => ['Coupon code not found.'],
        ]);
    }

    /** @test */
    public function it_rejects_expired_coupon_via_api()
    {
        $coupon = Coupon::create([
            'code' => $this->faker->unique()->regexify('[A-Z0-9]{10}'),
            'name' => 'Expired Coupon',
            'type' => Coupon::TYPE_FIXED,
            'discount_value' => 5,
            'start_date' => now()->subMonth(),
            'end_date' => now()->subDay(),
            'active' => true,
            'minimum_purchase_amount' => 0,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/coupons/validate', [
                'code' => $coupon->code,
                'items' => [
                    [
                        'type' => 'service',
                        'id' => 1,
                        'price' => 100,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'valid' => false,
        ]);
        $response->assertJsonFragment(['Coupon is not active or has expired.']);
    }

    /** @test */
    public function it_rejects_coupon_with_usage_limit_reached_via_api()
    {
        $coupon = Coupon::create([
            'code' => $this->faker->unique()->regexify('[A-Z0-9]{10}'),
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

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/coupons/validate', [
                'code' => $coupon->code,
                'items' => [
                    [
                        'type' => 'service',
                        'id' => 1,
                        'price' => 100,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'valid' => false,
        ]);
        $response->assertJsonFragment(['Coupon usage limit reached.']);
    }

    /** @test */
    public function it_validates_coupon_with_minimum_purchase_amount_via_api()
    {
        $coupon = Coupon::create([
            'code' => $this->faker->unique()->regexify('[A-Z0-9]{10}'),
            'name' => 'Minimum $100',
            'type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 10,
            'minimum_purchase_amount' => 100,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'active' => true,
        ]);

        // Subtotal too low
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/coupons/validate', [
                'code' => $coupon->code,
                'items' => [
                    [
                        'type' => 'service',
                        'id' => 1,
                        'price' => 80,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'valid' => false,
        ]);
        $response->assertJsonFragment(['Minimum purchase amount not met.']);

        // Subtotal meets minimum
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/coupons/validate', [
                'code' => $coupon->code,
                'items' => [
                    [
                        'type' => 'service',
                        'id' => 1,
                        'price' => 150,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertOk();
        $response->assertJson(['valid' => true]);
    }
}