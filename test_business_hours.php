<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BusinessHoursService;
use Carbon\Carbon;

echo "=== BusinessHoursService Test ===\n\n";

$service = new BusinessHoursService();

// Test 1: Get configuration
echo "1. Testing configuration retrieval:\n";
$config = $service->getConfig();
echo "   - Business hours configured for:\n";
foreach ($config['business_hours'] as $day => $hours) {
    $status = $hours['enabled'] ? "Enabled ({$hours['open']} - {$hours['close']})" : "Disabled";
    echo "     * {$day}: {$status}\n";
}
echo "   - Grace period: {$config['grace_period_minutes']} minutes\n";
echo "   - Half day threshold: {$config['half_day_threshold_hours']} hours\n";

// Test 2: Test for specific dates
echo "\n2. Testing business hours for specific dates:\n";
$testDates = [
    'Monday' => Carbon::parse('2025-12-22'), // Monday
    'Saturday' => Carbon::parse('2025-12-20'), // Saturday
    'Sunday' => Carbon::parse('2025-12-21'), // Sunday
];

foreach ($testDates as $label => $date) {
    $hours = $service->getHoursForDate($date);
    $isBusinessDay = $service->isBusinessDay($date);
    $dayType = $service->getBusinessDayType($date);
    $expectedHours = $service->getExpectedHoursForDate($date);
    
    echo "   - {$label} ({$date->format('Y-m-d')}):\n";
    echo "     * Business day: " . ($isBusinessDay ? 'Yes' : 'No') . "\n";
    echo "     * Day type: {$dayType}\n";
    echo "     * Expected hours: {$expectedHours}\n";
    if ($hours && $hours['open']) {
        echo "     * Business hours: {$hours['open']->format('H:i')} - {$hours['close']->format('H:i')}\n";
    }
}

// Test 3: Test time calculations
echo "\n3. Testing time calculations:\n";
$testTime = Carbon::parse('2025-12-22 09:30'); // Monday at 9:30 AM
$lateTime = Carbon::parse('2025-12-22 09:45'); // Monday at 9:45 AM (after grace period)
$earlyTime = Carbon::parse('2025-12-22 16:30'); // Monday at 4:30 PM (before close)
$overtimeTime = Carbon::parse('2025-12-22 17:30'); // Monday at 5:30 PM (after close)

echo "   - Test time (09:30):\n";
echo "     * Within business hours: " . ($service->isWithinBusinessHours($testTime) ? 'Yes' : 'No') . "\n";
echo "     * Late check-in: " . ($service->isLateCheckIn($testTime) ? 'Yes' : 'No') . "\n";
echo "     * Late arrival minutes: " . $service->calculateLateArrivalMinutes($testTime) . "\n";

echo "   - Late time (09:45):\n";
echo "     * Late check-in: " . ($service->isLateCheckIn($lateTime) ? 'Yes' : 'No') . "\n";
echo "     * Late arrival minutes: " . $service->calculateLateArrivalMinutes($lateTime) . "\n";

echo "   - Early departure (16:30):\n";
echo "     * Early departure: " . ($service->isEarlyDeparture($earlyTime) ? 'Yes' : 'No') . "\n";
echo "     * Early departure minutes: " . $service->calculateEarlyDepartureMinutes($earlyTime) . "\n";

echo "   - Overtime (17:30):\n";
echo "     * Overtime minutes: " . $service->calculateOvertimeMinutes($overtimeTime) . "\n";

// Test 4: Test attendance status determination
echo "\n4. Testing attendance status determination:\n";
$testCases = [
    ['hours' => 0, 'isLate' => false, 'expected' => 'absent'],
    ['hours' => 2, 'isLate' => false, 'expected' => 'half_day'],
    ['hours' => 4, 'isLate' => false, 'expected' => 'present'],
    ['hours' => 8, 'isLate' => true, 'expected' => 'late'],
    ['hours' => 6, 'isLate' => false, 'expected' => 'present'],
];

foreach ($testCases as $case) {
    $status = $service->determineStatus($case['hours'], $case['isLate']);
    $passed = $status === $case['expected'] ? '✓' : '✗';
    echo "   - {$passed} Hours: {$case['hours']}, Late: " . ($case['isLate'] ? 'Yes' : 'No') . 
         " -> Status: {$status} (Expected: {$case['expected']})\n";
}

// Test 5: Test hours validation
echo "\n5. Testing hours validation:\n";
$validationCases = [
    ['hours' => -1, 'expected' => false],
    ['hours' => 0, 'expected' => true],
    ['hours' => 4, 'expected' => true],
    ['hours' => 8, 'expected' => true],
    ['hours' => 12, 'expected' => true],
    ['hours' => 13, 'expected' => false],
];

foreach ($validationCases as $case) {
    $valid = $service->validateHoursWorked($case['hours']);
    $passed = $valid === $case['expected'] ? '✓' : '✗';
    echo "   - {$passed} Hours: {$case['hours']} -> Valid: " . ($valid ? 'Yes' : 'No') . 
         " (Expected: " . ($case['expected'] ? 'Yes' : 'No') . ")\n";
}

// Test 6: Check for potential issues
echo "\n6. Checking for potential issues:\n";

// Check if break_duration_minutes is referenced but not in config
if (isset($config['break_duration_minutes'])) {
    echo "   - ✓ break_duration_minutes is configured: {$config['break_duration_minutes']} minutes\n";
} else {
    echo "   - ⚠️ break_duration_minutes is referenced in getExpectedHoursForDate() but not in default config\n";
}

// Check if all required methods exist
$requiredMethods = [
    'getConfig',
    'getHoursForDate', 
    'isBusinessDay',
    'getBusinessDayType',
    'getExpectedHoursForDate',
    'isWithinBusinessHours',
    'isLateCheckIn',
    'calculateLateArrivalMinutes',
    'isEarlyDeparture',
    'calculateEarlyDepartureMinutes',
    'calculateOvertimeMinutes',
    'determineStatus',
    'validateHoursWorked'
];

$missingMethods = [];
foreach ($requiredMethods as $method) {
    if (!method_exists($service, $method)) {
        $missingMethods[] = $method;
    }
}

if (empty($missingMethods)) {
    echo "   - ✓ All required methods exist\n";
} else {
    echo "   - ❌ Missing methods: " . implode(', ', $missingMethods) . "\n";
}

echo "\n=== Test Complete ===\n";