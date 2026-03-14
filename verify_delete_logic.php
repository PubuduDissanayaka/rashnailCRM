<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// We need to bootstrap the application to use Facades
$kernel->bootstrap();

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// 1. Setup Data
$slug = 'apt-xvohcg2w'; // The one we just created
$admin = User::where('email', 'admin@rashnail.com')->first();
Auth::login($admin); // Login as admin

echo "Testing delete for slug: $slug\n";

// 2. Simulate Request
// We need to manually construct the request and dispatch it through the router or call the controller directly.
// Calling controller directly is easier to debug logic, but dispatching checks middleware.
// Let's call controller method directly first to verify logic.

$controller = new \App\Http\Controllers\AppointmentController();
$appointment = Appointment::where('slug', $slug)->first();

if (!$appointment) {
    echo "Appointment not found!\n";
    exit(1);
}

// Mock Request with wantsJson returning true
$request = Request::create('/appointments/'.$slug, 'DELETE');
$request->headers->set('Accept', 'application/json');

echo "Wants JSON? " . ($request->wantsJson() ? 'Yes' : 'No') . "\n";

try {
    // We need to manually authorize because `authorize` check in controller relies on Gate/Policy
    // which might fail if not fully bootstrapped or if actingAs didn't work perfectly in this script context.
    // However, since we logged in via Auth::login, it might work.
    
    // Bypass authorization for this UNIT test of the controller logic if needed, but let's try.
    
    $response = $controller->destroy($appointment, $request);
    
    echo "Response Content: " . $response->getContent() . "\n";
    echo "Response Status: " . $response->getStatusCode() . "\n";
    
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

// Check DB
if (Appointment::where('slug', $slug)->exists()) {
    echo "FAILURE: Still in DB\n";
} else {
    echo "SUCCESS: Deleted from DB\n";
}
