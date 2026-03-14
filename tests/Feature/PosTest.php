<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;
    protected $service;
    protected $package;

    public function setUp(): void
    {
        parent::setUp();
        
        // Create a user with admin role for POS access
        $this->user = User::factory()->create();
        $this->user->assignRole('administrator');
        
        // Create a customer
        $this->customer = Customer::factory()->create();
        
        // Create a service
        $this->service = Service::factory()->create([
            'price' => 50.00,
            'is_active' => true
        ]);
        
        // Create a service package
        $this->package = ServicePackage::factory()->create([
            'price' => 200.00,
            'is_active' => true,
            'is_available_for_sale' => true
        ]);
    }

    public function test_pos_index_page_accessible()
    {
        $response = $this->actingAs($this->user)->get('/pos');

        $response->assertStatus(200);
        $response->assertViewIs('pos.index');
        $response->assertSee('Point of Sale');
    }

    public function test_unauthorized_user_cannot_access_pos()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/pos');

        $response->assertStatus(403);
    }

    public function test_pos_store_creates_sale_successfully()
    {
        $this->withoutExceptionHandling();

        $saleData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'type' => 'service',
                    'id' => $this->service->id,
                    'quantity' => 1,
                    'price' => $this->service->price
                ]
            ],
            'payment_method' => 'cash',
            'amount_received' => 60.00,
            'notes' => 'Test sale'
        ];

        $response = $this->actingAs($this->user)
            ->post('/pos/sale', $saleData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('sales', [
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'total_amount' => 50.00,
            'amount_paid' => 60.00,
            'status' => 'completed',
            'notes' => 'Test sale'
        ]);

        $this->assertDatabaseHas('sale_items', [
            'item_name' => $this->service->name,
            'quantity' => 1,
            'unit_price' => $this->service->price
        ]);
    }

    public function test_pos_store_validates_item_existence()
    {
        $saleData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'type' => 'service',
                    'id' => 999999, // Non-existent service ID
                    'quantity' => 1,
                    'price' => 50.00
                ]
            ],
            'payment_method' => 'cash',
            'amount_received' => 60.00
        ];

        $response = $this->actingAs($this->user)
            ->post('/pos/sale', $saleData);

        $response->assertStatus(500); // Due to ModelNotFoundException
    }

    public function test_pos_store_validates_item_activity()
    {
        $inactiveService = Service::factory()->create([
            'is_active' => false
        ]);

        $saleData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'type' => 'service',
                    'id' => $inactiveService->id,
                    'quantity' => 1,
                    'price' => 50.00
                ]
            ],
            'payment_method' => 'cash',
            'amount_received' => 60.00
        ];

        $response = $this->actingAs($this->user)
            ->post('/pos/sale', $saleData);

        $response->assertStatus(500); // This will fail with ModelNotFoundException in controller
    }

    public function test_pos_store_validates_package_availability_for_sale()
    {
        $packageNotForSale = ServicePackage::factory()->create([
            'is_available_for_sale' => false
        ]);

        $saleData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'type' => 'package',
                    'id' => $packageNotForSale->id,
                    'quantity' => 1,
                    'price' => 200.00
                ]
            ],
            'payment_method' => 'cash',
            'amount_received' => 210.00
        ];

        $response = $this->actingAs($this->user)
            ->post('/pos/sale', $saleData);

        $response->assertStatus(500); // This will fail with ModelNotFoundException in controller
    }

    public function test_pos_store_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->post('/pos/sale', []);

        $response->assertStatus(302); // Validation failure redirects
        $response->assertSessionHasErrors([
            'customer_id',
            'items',
            'payment_method',
            'amount_received'
        ]);
    }

    public function test_pos_store_validates_items_array()
    {
        $saleData = [
            'customer_id' => $this->customer->id,
            'items' => 'invalid_items', // Not an array
            'payment_method' => 'cash',
            'amount_received' => 60.00
        ];

        $response = $this->actingAs($this->user)
            ->post('/pos/sale', $saleData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['items']);
    }

    public function test_pos_store_validates_item_structure()
    {
        $saleData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'type' => 'invalid_type', // Invalid type
                    'id' => $this->service->id,
                    'quantity' => 1,
                    'price' => 50.00
                ]
            ],
            'payment_method' => 'cash',
            'amount_received' => 60.00
        ];

        $response = $this->actingAs($this->user)
            ->post('/pos/sale', $saleData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['items.0.type']);
    }

    public function test_pos_search_items_returns_results()
    {
        $response = $this->actingAs($this->user)
            ->get('/pos/search?q=Test');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'services',
            'packages'
        ]);
    }

    public function test_pos_get_customer_details()
    {
        $response = $this->actingAs($this->user)
            ->get("/pos/customer/{$this->customer->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'customer' => [
                'id' => $this->customer->id,
                'first_name' => $this->customer->first_name,
                'last_name' => $this->customer->last_name
            ]
        ]);
    }

    public function test_pos_get_customer_details_not_found()
    {
        $response = $this->actingAs($this->user)
            ->get('/pos/customer/999999');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'message' => 'Customer not found.'
        ]);
    }

    public function test_pos_live_search_returns_results()
    {
        $response = $this->actingAs($this->user)
            ->get('/pos/live-search?q=Test');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success' => true,
            'services',
            'packages'
        ]);
    }

    public function test_pos_live_search_empty_query()
    {
        $response = $this->actingAs($this->user)
            ->get('/pos/live-search?q=a'); // Less than 2 chars

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'services' => [],
            'packages' => []
        ]);
    }

    public function test_pos_store_calculates_totals_correctly_with_tax()
    {
        // Set tax rate to 10%
        Setting::set('payment.tax_rate', 10, 'integer');

        $saleData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'type' => 'service',
                    'id' => $this->service->id,
                    'quantity' => 1,
                    'price' => $this->service->price
                ]
            ],
            'payment_method' => 'cash',
            'amount_received' => 55.00 // 50 + 5 tax
        ];

        $response = $this->actingAs($this->user)
            ->post('/pos/sale', $saleData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $sale = Sale::latest()->first();
        
        // Subtotal should be 50
        $this->assertEquals(50.00, $sale->subtotal);
        // Tax should be 5 (10% of 50)
        $this->assertEquals(5.00, $sale->tax_amount);
        // Total should be 55
        $this->assertEquals(55.00, $sale->total_amount);
        // Change should be 0
        $this->assertEquals(0.00, $sale->change_amount);
    }
}