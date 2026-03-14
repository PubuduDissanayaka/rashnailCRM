<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponBatch;
use App\Models\CustomerGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CouponAdminTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create required permissions
        Permission::firstOrCreate(['name' => 'manage system']);
        Permission::firstOrCreate(['name' => 'view coupons']);
        Permission::firstOrCreate(['name' => 'create coupons']);
        Permission::firstOrCreate(['name' => 'edit coupons']);
        Permission::firstOrCreate(['name' => 'delete coupons']);
        Permission::firstOrCreate(['name' => 'manage coupon batches']);

        // Create admin user with required permissions
        $this->adminUser = User::factory()->create();
        $this->adminUser->givePermissionTo('manage system');
        $this->adminUser->givePermissionTo('view coupons');
        $this->adminUser->givePermissionTo('create coupons');
        $this->adminUser->givePermissionTo('edit coupons');
        $this->adminUser->givePermissionTo('delete coupons');
        $this->adminUser->givePermissionTo('manage coupon batches');

        // Create regular user without permission
        $this->regularUser = User::factory()->create();
    }

    /** @test */
    public function admin_can_access_coupons_index_page()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/coupons');

        $response->assertOk();
        $response->assertViewIs('admin.coupons.index');
        $response->assertSee('Coupons');
    }

    /** @test */
    public function admin_can_access_coupons_create_page()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/coupons/create');

        $response->assertOk();
        $response->assertViewIs('admin.coupons.create');
        $response->assertSee('Create Coupon');
    }

    /** @test */
    public function admin_can_store_new_coupon()
    {
        $couponData = [
            'code' => $this->uniqueCode('COUPON'),
            'name' => 'Test Coupon',
            'type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 10,
            'start_date' => now()->format('Y-m-d'),
            'timezone' => 'UTC',
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'active' => true,
            'minimum_purchase_amount' => 0,
            'location_restriction_type' => 'all',
            'customer_eligibility_type' => 'all',
            'product_restriction_type' => 'all',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post('/coupons', $couponData);

        $response->assertRedirect(route('coupons.index'));
        $this->assertDatabaseHas('coupons', ['code' => $couponData['code']]);
    }

    /** @test */
    public function admin_can_view_coupon_show_page()
    {
        $coupon = Coupon::create([
            'code' => $this->uniqueCode('SHOW'),
            'name' => 'Show Coupon',
            'type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 15,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'active' => true,
            'minimum_purchase_amount' => 0,
            'location_restriction_type' => 'all',
            'customer_eligibility_type' => 'all',
            'product_restriction_type' => 'all',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/coupons/' . $coupon->id);

        $response->assertOk();
        $response->assertViewIs('admin.coupons.show');
        $response->assertSee($coupon->name);
    }

    /** @test */
    public function admin_can_access_coupon_edit_page()
    {
        $coupon = Coupon::create([
            'code' => $this->uniqueCode('EDIT'),
            'name' => 'Edit Coupon',
            'type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 20,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'active' => true,
            'minimum_purchase_amount' => 0,
            'location_restriction_type' => 'all',
            'customer_eligibility_type' => 'all',
            'product_restriction_type' => 'all',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/coupons/' . $coupon->id . '/edit');

        $response->assertOk();
        $response->assertViewIs('admin.coupons.edit');
        $response->assertSee('Edit Coupon');
    }

    /** @test */
    public function admin_can_update_coupon()
    {
        $coupon = Coupon::create([
            'code' => $this->uniqueCode('UPDATE'),
            'name' => 'Original Name',
            'type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 10,
            'start_date' => now(),
            'timezone' => 'UTC',
            'end_date' => now()->addMonth(),
            'active' => true,
            'minimum_purchase_amount' => 0,
            'location_restriction_type' => 'all',
            'customer_eligibility_type' => 'all',
            'product_restriction_type' => 'all',
        ]);

        $updateData = [
            'code' => $coupon->code,
            'name' => 'Updated Name',
            'type' => $coupon->type,
            'discount_value' => 25,
            'start_date' => $coupon->start_date->format('Y-m-d H:i:s'),
            'timezone' => $coupon->timezone,
            'location_restriction_type' => $coupon->location_restriction_type,
            'customer_eligibility_type' => $coupon->customer_eligibility_type,
            'product_restriction_type' => $coupon->product_restriction_type,
            'end_date' => now()->addMonths(2)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->adminUser)
            ->put('/coupons/' . $coupon->id, $updateData);

        $response->assertRedirect(route('coupons.index'));
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'name' => 'Updated Name',
            'discount_value' => 25,
        ]);
    }

    /** @test */
    public function admin_can_delete_coupon()
    {
        $coupon = Coupon::create([
            'code' => $this->uniqueCode('DELETE'),
            'name' => 'Delete Coupon',
            'type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 10,
            'start_date' => now(),
            'timezone' => 'UTC',
            'end_date' => now()->addMonth(),
            'active' => true,
            'minimum_purchase_amount' => 0,
            'location_restriction_type' => 'all',
            'customer_eligibility_type' => 'all',
            'product_restriction_type' => 'all',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete('/coupons/' . $coupon->id);

        $response->assertRedirect(route('coupons.index'));
        $this->assertSoftDeleted('coupons', ['id' => $coupon->id]);
    }

    /** @test */
    public function admin_can_access_bulk_coupon_create_page()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/coupons/bulk/create');

        $response->assertOk();
        $response->assertViewIs('admin.coupons.bulk');
        $response->assertSee('Bulk Coupon Generation');
    }

    /** @test */
    public function admin_can_generate_bulk_coupons()
    {
        $batchData = [
            'name' => 'Test Batch',
            'pattern' => 'BATCH{RANDOM6}',
            'count' => 5,
            'settings' => json_encode([
                'name' => 'Bulk Coupon',
                'type' => Coupon::TYPE_PERCENTAGE,
                'discount_value' => 10,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addMonth()->format('Y-m-d'),
                'active' => true,
                'minimum_purchase_amount' => 0,
                'location_restriction_type' => 'all',
                'customer_eligibility_type' => 'all',
                'product_restriction_type' => 'all',
            ]),
        ];

        $response = $this->actingAs($this->adminUser)
            ->post('/coupons/bulk/generate', $batchData);

        $response->assertRedirect(route('coupons.batches.index'));
        $this->assertDatabaseHas('coupon_batches', ['name' => 'Test Batch']);
        
        // Get the created batch and verify its coupons count
        $batch = CouponBatch::where('name', 'Test Batch')->first();
        $this->assertNotNull($batch, 'Batch should have been created');
        $this->assertEquals(5, $batch->coupons()->count(), 'Batch should have generated 5 coupons');
    }

    /** @test */
    public function admin_can_access_batches_index_page()
    {
        CouponBatch::factory()->create(['name' => 'Test Batch']);

        $response = $this->actingAs($this->adminUser)
            ->get('/coupons/batches');

        $response->assertOk();
        $response->assertViewIs('admin.coupons.batches');
        $response->assertSee('Batches');
    }

    /** @test */
    public function admin_can_access_batch_show_page()
    {
        $batch = CouponBatch::factory()->create(['name' => 'Test Batch']);

        $response = $this->actingAs($this->adminUser)
            ->get('/coupons/batches/' . $batch->id);

        $response->assertOk();
        $response->assertViewIs('admin.coupons.batch_show');
        $response->assertSee($batch->name);
    }

    /** @test */
    public function admin_can_access_customer_groups_index_page()
    {
        CustomerGroup::factory()->create(['name' => 'VIP Customers']);

        $response = $this->actingAs($this->adminUser)
            ->get('/customer-groups');

        $response->assertOk();
        $response->assertViewIs('admin.customer-groups.index');
        $response->assertSee('Customer Groups');
    }

    /** @test */
    public function admin_can_store_customer_group()
    {
        $groupName = 'New Group ' . mt_rand(1000, 9999);
        $groupData = [
            'name' => $groupName,
            'description' => 'Group description',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post('/customer-groups', $groupData);

        $response->assertRedirect(route('customer-groups.index'));
        $this->assertDatabaseHas('customer_groups', ['name' => $groupName]);
    }

    /** @test */
    public function admin_can_update_customer_group()
    {
        $originalName = 'Original Name ' . mt_rand(1000, 9999);
        $updatedName = 'Updated Name ' . mt_rand(1000, 9999);
        $group = CustomerGroup::factory()->create(['name' => $originalName]);

        $updateData = [
            'name' => $updatedName,
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->adminUser)
            ->put('/customer-groups/' . $group->id, $updateData);

        $response->assertRedirect(route('customer-groups.index'));
        $this->assertDatabaseHas('customer_groups', [
            'id' => $group->id,
            'name' => $updatedName,
        ]);
    }

    /** @test */
    public function admin_can_delete_customer_group()
    {
        $groupName = 'Delete Group ' . mt_rand(1000, 9999);
        $group = CustomerGroup::factory()->create(['name' => $groupName]);

        $response = $this->actingAs($this->adminUser)
            ->delete('/customer-groups/' . $group->id);

        $response->assertRedirect(route('customer-groups.index'));
        $this->assertDatabaseMissing('customer_groups', ['id' => $group->id]);
    }

    /** @test */
    public function unauthorized_user_cannot_access_coupon_pages()
    {
        $coupon = Coupon::create([
            'code' => $this->uniqueCode('UNAUTH'),
            'name' => 'Unauth Coupon',
            'type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 10,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'active' => true,
            'minimum_purchase_amount' => 0,
            'location_restriction_type' => 'all',
            'customer_eligibility_type' => 'all',
            'product_restriction_type' => 'all',
        ]);

        $routes = [
            '/coupons',
            '/coupons/create',
            '/coupons/' . $coupon->id,
            '/coupons/' . $coupon->id . '/edit',
            '/coupons/bulk/create',
            '/coupons/batches',
            '/customer-groups',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->regularUser)
                ->get($route);
            $response->assertForbidden();
        }
    }

    /** @test */
    public function unauthorized_user_cannot_perform_coupon_actions()
    {
        $coupon = Coupon::create([
            'code' => $this->uniqueCode('ACTION'),
            'name' => 'Action Coupon',
            'type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 10,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'active' => true,
            'minimum_purchase_amount' => 0,
            'location_restriction_type' => 'all',
            'customer_eligibility_type' => 'all',
            'product_restriction_type' => 'all',
        ]);

        $group = CustomerGroup::factory()->create(['name' => 'Test Group']);

        // POST store coupon
        $response = $this->actingAs($this->regularUser)
            ->post('/coupons', [
                'code' => $this->uniqueCode('POST'),
                'name' => 'New Coupon',
                'type' => Coupon::TYPE_PERCENTAGE,
                'discount_value' => 10,
            ]);
        $response->assertForbidden();

        // PUT update coupon
        $response = $this->actingAs($this->regularUser)
            ->put('/coupons/' . $coupon->id, ['name' => 'Updated']);
        $response->assertForbidden();

        // DELETE coupon
        $response = $this->actingAs($this->regularUser)
            ->delete('/coupons/' . $coupon->id);
        $response->assertForbidden();

        // POST bulk generate
        $response = $this->actingAs($this->regularUser)
            ->post('/coupons/bulk/generate', [
                'name' => 'Batch',
                'pattern' => 'BATCH{RANDOM6}',
                'count' => 5,
            ]);
        $response->assertForbidden();

        // POST store customer group
        $response = $this->actingAs($this->regularUser)
            ->post('/customer-groups', ['name' => 'New Group']);
        $response->assertForbidden();

        // PUT update customer group
        $response = $this->actingAs($this->regularUser)
            ->put('/customer-groups/' . $group->id, ['name' => 'Updated Group']);
        $response->assertForbidden();

        // DELETE customer group
        $response = $this->actingAs($this->regularUser)
            ->delete('/customer-groups/' . $group->id);
        $response->assertForbidden();
    }

    /**
     * Generate a unique coupon code with a prefix.
     */
    private function uniqueCode(string $prefix): string
    {
        return $prefix . '_' . mt_rand(1000, 9999);
    }
}