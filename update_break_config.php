<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Updating Break Duration Configuration ===\n\n";

// Get current config
$config = DB::table('settings')->where('key', 'attendance.business_hours.config')->first();

if (!$config) {
    echo "❌ Business hours config not found in settings table!\n";
    exit(1);
}

$value = json_decode($config->value, true);

echo "Current configuration:\n";
echo json_encode($value, JSON_PRETTY_PRINT) . "\n\n";

// Check if break_duration_minutes exists
if (!isset($value['break_duration_minutes'])) {
    echo "Adding break_duration_minutes to config...\n";
    $value['break_duration_minutes'] = 60;
    
    // Update the database
    DB::table('settings')
        ->where('key', 'attendance.business_hours.config')
        ->update([
            'value' => json_encode($value),
            'updated_at' => now(),
        ]);
    
    echo "✅ Added break_duration_minutes: 60 minutes\n";
    
    // Verify the update
    $updated = DB::table('settings')->where('key', 'attendance.business_hours.config')->first();
    $newValue = json_decode($updated->value, true);
    echo "\nUpdated configuration:\n";
    echo json_encode($newValue, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "✅ break_duration_minutes already exists: {$value['break_duration_minutes']} minutes\n";
}

// Test the service with updated config
echo "\n=== Testing Service with Updated Config ===\n";
$service = new \App\Services\BusinessHoursService();
$config = $service->getConfig();
echo "break_duration_minutes from service: " . ($config['break_duration_minutes'] ?? 'NOT FOUND') . "\n";

// Test expected hours calculation
$testDate = \Carbon\Carbon::parse('2025-12-22');
$expectedHours = $service->getExpectedHoursForDate($testDate);
echo "Expected hours for business day: {$expectedHours}\n";

echo "\n=== Update Complete ===\n";