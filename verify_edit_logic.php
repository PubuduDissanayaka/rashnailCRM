<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$kernel->bootstrap();

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// 1. Setup Data
$slug = 'apt-xvohcg2w'; 
$admin = User::where('email', 'admin@rashnail.com')->first();
Auth::login($admin); 

echo "Testing edit for slug: $slug\n";

$controller = new \App\Http\Controllers\AppointmentController();
$appointment = Appointment::where('slug', $slug)->first();

if (!$appointment) {
    echo "Appointment not found! Creating one...\n";
    $customer = \App\Models\Customer::first();
    $user = User::where('role', 'staff')->first();
    $service = \App\Models\Service::first();
    
    $appointment = Appointment::create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'service_id' => $service->id,
        'appointment_date' => now()->addDays(5),
        'status' => 'scheduled',
        'notes' => 'Test appointment'
    ]);
    echo "Created new appointment: " . $appointment->slug . "\n";
}

// Mock Request with valid data
$request = Request::create('/appointments/'.$appointment->id.'/ajax', 'PUT', [
    'title' => 'Updated Title',
    'service_id' => $appointment->service_id,
    'customer_id' => $appointment->customer_id,
    'user_id' => $appointment->user_id,
    'status' => 'scheduled',
    'notes' => 'Updated notes',
    'appointment_date' => now()->addDays(6)->format('Y-m-d H:i:s')
]);
$request->headers->set('Accept', 'application/json');

try {
    $response = $controller->updateViaAjax($appointment, $request);
    
    echo "Response Content: " . $response->getContent() . "\n";
    echo "Response Status: " . $response->getStatusCode() . "\n";
    
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
