<?php

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

// Create a dummy appointment
$customer = Customer::first();
$user = User::where('role', 'staff')->first() ?? User::first();
$service = Service::first();

if (!$customer || !$user || !$service) {
    echo "Error: Missing prerequisites (Customer, User, or Service).\n";
    exit(1);
}

$appointment = Appointment::create([
    'customer_id' => $customer->id,
    'user_id' => $user->id,
    'service_id' => $service->id,
    'appointment_date' => now()->addDay(),
    'status' => 'scheduled',
    'notes' => 'Test appointment for deletion API check'
]);

echo "Created test appointment ID: " . $appointment->id . " Slug: " . $appointment->slug . "\n";

// Login as admin
$admin = User::where('email', 'admin@rashnail.com')->first();
Auth::login($admin);

// Simulate DELETE request with JSON accept header
$request = Illuminate\Http\Request::create(
    '/appointments/' . $appointment->slug,
    'DELETE',
    [],
    [],
    [],
    ['HTTP_ACCEPT' => 'application/json'] // Important: Simulate Accept: application/json
);

// Resolve route and run controller logic (simplified simulation)
// In a real app test we'd use $this->deleteJson(), but here we want to test the controller logic directly or via route dispatch
$response = $kernel->handle($request);

echo "Response Status: " . $response->getStatusCode() . "\n";
echo "Response Content: " . $response->getContent() . "\n";

// Check if deleted
if (Appointment::find($appointment->id)) {
    echo "FAILURE: Appointment still exists in DB.\n";
} else {
    echo "SUCCESS: Appointment deleted from DB.\n";
}

if ($response->headers->get('content-type') === 'application/json') {
     echo "SUCCESS: Response is JSON.\n";
} else {
     echo "FAILURE: Response is NOT JSON.\n";
}
