<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Appointment;

echo "Checking Appointments in DB...\n";
$appointments = Appointment::with('customer')->orderBy('id', 'desc')->get();

if ($appointments->isEmpty()) {
    echo "No appointments found.\n";
} else {
    foreach ($appointments as $a) {
        echo "ID: {$a->id} | Date: {$a->appointment_date} | Status: {$a->status} | Customer: " . ($a->customer->name ?? 'N/A') . "\n";
    }
}
