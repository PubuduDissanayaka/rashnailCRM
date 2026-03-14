<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BusinessHoursService;
use Carbon\Carbon;

echo "=== Testing Fixes ===\n\n";

$service = new BusinessHoursService();

// Test 1: Early departure calculation fix
echo "1. Testing early departure calculation fix:\n";
$testTime = Carbon::parse('2025-12-22 16:30');
$earlyMinutes = $service->calculateEarlyDepartureMinutes($testTime);
echo "   - Check-out at 16:30 (30 minutes before 17:00):\n";
echo "     * Early departure minutes: {$earlyMinutes}\n";
echo "     * Expected: 30, Result: " . ($earlyMinutes === 30 ? '✓' : '✗') . "\n";

// Test 2: Break duration in config
echo "\n2. Testing break duration configuration:\n";
$config = $service->getConfig();
if (isset($config['break_duration_minutes'])) {
    echo "   - ✓ break_duration_minutes is in config: {$config['break_duration_minutes']} minutes\n";
} else {
    echo "   - ✗ break_duration_minutes is NOT in config\n";
}

// Test 3: Expected hours calculation with break
echo "\n3. Testing expected hours calculation:\n";
$testDate = Carbon::parse('2025-12-22');
$expectedHours = $service->getExpectedHoursForDate($testDate);
echo "   - Expected hours for a business day (09:00-17:00 with 60min break): {$expectedHours}\n";
echo "   * Calculation: 8 hours total - 1 hour break = 7 hours\n";
echo "   * Result: " . ($expectedHours === 7.0 ? '✓' : '✗') . "\n";

// Test 4: Test various early departure scenarios
echo "\n4. Testing various early departure scenarios:\n";
$testCases = [
    ['time' => '16:00', 'expected' => 60, 'label' => '1 hour early'],
    ['time' => '16:30', 'expected' => 30, 'label' => '30 minutes early'],
    ['time' => '16:45', 'expected' => 15, 'label' => '15 minutes early'],
    ['time' => '17:00', 'expected' => 0, 'label' => 'Exactly at close'],
    ['time' => '17:15', 'expected' => 0, 'label' => '15 minutes after close'],
];

foreach ($testCases as $case) {
    $time = Carbon::parse('2025-12-22 ' . $case['time']);
    $minutes = $service->calculateEarlyDepartureMinutes($time);
    $passed = $minutes === $case['expected'] ? '✓' : '✗';
    echo "   - {$passed} {$case['label']} ({$case['time']}): {$minutes} minutes (Expected: {$case['expected']})\n";
}

echo "\n=== Fix Verification Complete ===\n";