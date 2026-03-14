<?php

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeleteAppointmentTest extends TestCase
{
    // Note: We don't use RefreshDatabase here to avoid wiping the actual DB if running against it manually, 
    // but in a real test suite we would. ideally we should use a test DB.
    // For this quick check, we'll create and delete explicitly.

    public function test_admin_can_delete_appointment_via_ajax()
    {
        // 1. Create Data
        $admin = User::where('email', 'admin@rashnail.com')->first();
        if (!$admin) {
            $this->fail('Admin user not found');
        }

        $customer = Customer::first() ?? Customer::factory()->create();
        $staff = User::where('role', 'staff')->first() ?? User::factory()->create(['role' => 'staff']);
        $service = Service::first() ?? Service::factory()->create();

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'user_id' => $staff->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDays(2),
            'status' => 'scheduled',
            'notes' => 'To be deleted'
        ]);

        echo "Created Appointment ID: {$appointment->id}\n";

        // 2. Act
        $response = $this->actingAs($admin)
            ->deleteJson("/appointments/{$appointment->slug}");

        // 3. Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Appointment deleted successfully.'
            ]);

        if (Appointment::find($appointment->id)) {
            echo "FAILURE: Record still exists in DB\n";
            exit(1);
        } else {
            echo "SUCCESS: Record deleted from DB\n";
        }
    }
}

// Run the test manually
$test = new DeleteAppointmentTest('test_admin_can_delete_appointment_via_ajax');
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$test->setUp();
$test->test_admin_can_delete_appointment_via_ajax();
