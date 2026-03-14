<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\AttendanceService;
use App\Services\BusinessHoursService;
use App\Models\User;
use Carbon\Carbon;

echo "=== AttendanceService Test ===\n\n";

// Create a mock user for testing
$user = User::first();
if (!$user) {
    echo "⚠️ No users found in database. Creating a test user...\n";
    // We'll just test the service methods without a real user
}

$service = new AttendanceService();
$businessHoursService = new BusinessHoursService();

// Test 1: Check business hours integration
echo "1. Testing business hours integration:\n";
$testDate = Carbon::parse('2025-12-22 09:00'); // Monday
$businessHours = $service->getBusinessHoursForDate($testDate);
if ($businessHours) {
    echo "   - ✓ Business hours retrieved: {$businessHours['open']->format('H:i')} - {$businessHours['close']->format('H:i')}\n";
} else {
    echo "   - ✗ Failed to retrieve business hours\n";
}

// Test 2: Test check-in logic calculations
echo "\n2. Testing check-in logic calculations:\n";

$testTimes = [
    ['time' => '2025-12-22 08:45', 'label' => 'Before business hours'],
    ['time' => '2025-12-22 09:00', 'label' => 'Exactly at opening'],
    ['time' => '2025-12-22 09:14', 'label' => 'Within grace period'],
    ['time' => '2025-12-22 09:16', 'label' => 'After grace period'],
    ['time' => '2025-12-22 10:00', 'label' => 'Late by 45 minutes'],
];

foreach ($testTimes as $test) {
    $time = Carbon::parse($test['time']);
    $isLate = $businessHoursService->isLateCheckIn($time);
    $lateMinutes = $businessHoursService->calculateLateArrivalMinutes($time);
    $dayType = $businessHoursService->getBusinessDayType($time);
    $expectedHours = $businessHoursService->getExpectedHoursForDate($time);
    
    echo "   - {$test['label']} ({$time->format('H:i')}):\n";
    echo "     * Day type: {$dayType}\n";
    echo "     * Late: " . ($isLate ? 'Yes' : 'No') . "\n";
    echo "     * Late minutes: {$lateMinutes}\n";
    echo "     * Expected hours: {$expectedHours}\n";
}

// Test 3: Test check-out logic calculations
echo "\n3. Testing check-out logic calculations:\n";

$checkOutTests = [
    ['time' => '2025-12-22 16:30', 'label' => 'Early departure'],
    ['time' => '2025-12-22 17:00', 'label' => 'Exactly at closing'],
    ['time' => '2025-12-22 17:15', 'label' => '15 minutes after close'],
    ['time' => '2025-12-22 18:00', 'label' => '1 hour overtime'],
];

foreach ($checkOutTests as $test) {
    $time = Carbon::parse($test['time']);
    $isEarly = $businessHoursService->isEarlyDeparture($time);
    $earlyMinutes = $businessHoursService->calculateEarlyDepartureMinutes($time);
    $overtimeMinutes = $businessHoursService->calculateOvertimeMinutes($time);
    
    echo "   - {$test['label']} ({$time->format('H:i')}):\n";
    echo "     * Early departure: " . ($isEarly ? 'Yes' : 'No') . "\n";
    echo "     * Early minutes: {$earlyMinutes}\n";
    echo "     * Overtime minutes: {$overtimeMinutes}\n";
}

// Test 4: Test hours worked calculations
echo "\n4. Testing hours worked calculations:\n";

$testCases = [
    [
        'check_in' => '2025-12-22 09:00',
        'check_out' => '2025-12-22 17:00',
        'breaks' => 60, // 1 hour break
        'expected_hours' => 7.0,
    ],
    [
        'check_in' => '2025-12-22 09:00',
        'check_out' => '2025-12-22 13:00',
        'breaks' => 0,
        'expected_hours' => 4.0,
    ],
    [
        'check_in' => '2025-12-22 09:30',
        'check_out' => '2025-12-22 17:30',
        'breaks' => 30,
        'expected_hours' => 7.5,
    ],
];

foreach ($testCases as $i => $case) {
    $checkIn = Carbon::parse($case['check_in']);
    $checkOut = Carbon::parse($case['check_out']);
    
    $totalMinutes = $checkIn->diffInMinutes($checkOut);
    $netMinutes = $totalMinutes - $case['breaks'];
    $hoursWorked = round($netMinutes / 60, 2);
    
    $status = $businessHoursService->determineStatus($hoursWorked, false);
    $isHalfDay = $hoursWorked < $businessHoursService->getHalfDayThresholdHours();
    
    echo "   - Test case " . ($i + 1) . ":\n";
    echo "     * Check-in: {$checkIn->format('H:i')}, Check-out: {$checkOut->format('H:i')}\n";
    echo "     * Total minutes: {$totalMinutes}, Breaks: {$case['breaks']}m\n";
    echo "     * Net minutes: {$netMinutes}, Hours worked: {$hoursWorked}\n";
    echo "     * Status: {$status}, Half-day: " . ($isHalfDay ? 'Yes' : 'No') . "\n";
    echo "     * Expected hours: {$case['expected_hours']}, Match: " . ($hoursWorked == $case['expected_hours'] ? '✓' : '✗') . "\n";
}

// Test 5: Check for potential issues in AttendanceService
echo "\n5. Checking for potential issues in AttendanceService:\n";

// Check if shift-related code still exists
$serviceCode = file_get_contents(__DIR__.'/app/Services/AttendanceService.php');
if (strpos($serviceCode, 'Shift') !== false) {
    echo "   - ⚠️ Shift-related code still exists in AttendanceService\n";
} else {
    echo "   - ✓ No shift-related code found\n";
}

// Check if business hours integration is complete
$requiredBusinessHoursCalls = [
    'getBusinessDayType',
    'isLateCheckIn',
    'calculateLateArrivalMinutes',
    'getExpectedHoursForDate',
    'calculateOvertimeMinutes',
    'calculateEarlyDepartureMinutes',
    'getHalfDayThresholdHours',
];

$missingCalls = [];
foreach ($requiredBusinessHoursCalls as $method) {
    if (strpos($serviceCode, "businessHoursService->{$method}") === false) {
        $missingCalls[] = $method;
    }
}

if (empty($missingCalls)) {
    echo "   - ✓ All required business hours methods are called\n";
} else {
    echo "   - ⚠️ Missing business hours method calls: " . implode(', ', $missingCalls) . "\n";
}

// Check for the early departure bug
echo "\n6. Investigating early departure calculation bug:\n";
$testTime = Carbon::parse('2025-12-22 16:30');
$earlyMinutes = $businessHoursService->calculateEarlyDepartureMinutes($testTime);
echo "   - Test time: 16:30 (30 minutes before 17:00)\n";
echo "   - Early departure minutes calculated: {$earlyMinutes}\n";

if ($earlyMinutes === 0) {
    echo "   - ❌ BUG DETECTED: Early departure minutes should be 30, but got 0\n";
    echo "   - Checking calculateEarlyDepartureMinutes method...\n";
    
    // Let's examine the method logic
    $businessHoursCode = file_get_contents(__DIR__.'/app/Services/BusinessHoursService.php');
    $pattern = '/function calculateEarlyDepartureMinutes.*?\n.*?\n.*?\n.*?\n.*?\n/s';
    if (preg_match($pattern, $businessHoursCode, $matches)) {
        echo "   - Method found. Let me check the logic...\n";
        // The issue is likely in the diff calculation
    }
} else {
    echo "   - ✓ Early departure calculation appears correct\n";
}

// Test 7: Check status determination logic
echo "\n7. Testing status determination logic:\n";
echo "   - Note: In checkIn(), status is set to 'late' if isLate is true, otherwise 'present'\n";
echo "   - In checkOut(), status is changed to 'half_day' if hoursWorked < halfDayThreshold\n";
echo "   - Potential issue: A late check-in that results in half-day might show as 'late' instead of 'half_day'\n";

echo "\n=== Test Complete ===\n";

// Additional debug: Let's manually test the early departure calculation
echo "\n=== Debug: Early Departure Calculation ===\n";
$debugTime = Carbon::parse('2025-12-22 16:30');
$hours = $businessHoursService->getHoursForDate($debugTime);
if ($hours && $hours['close']) {
    echo "Business close time: " . $hours['close']->format('H:i') . "\n";
    echo "Check-out time: " . $debugTime->format('H:i') . "\n";
    echo "Difference in minutes: " . $hours['close']->diffInMinutes($debugTime) . "\n";
    echo "Is check-out less than close time? " . ($debugTime->lessThan($hours['close']) ? 'Yes' : 'No') . "\n";
    
    // The issue might be in calculateEarlyDepartureMinutes - let's check the logic
    echo "\nChecking calculateEarlyDepartureMinutes logic:\n";
    echo "The method uses: max(0, \$hours['close']->diffInMinutes(\$checkOutTime))\n";
    echo "But diffInMinutes between 17:00 and 16:30 should be 30, not 0.\n";
    echo "Wait, actually diffInMinutes(16:30, 17:00) = 30, so max(0, 30) = 30\n";
    echo "So why is it returning 0? Let me check if isEarlyDeparture is returning false...\n";
    
    $isEarly = $businessHoursService->isEarlyDeparture($debugTime);
    echo "isEarlyDeparture(16:30): " . ($isEarly ? 'true' : 'false') . "\n";
    
    if (!$isEarly) {
        echo "❌ BUG: isEarlyDeparture(16:30) returns false, but 16:30 is before 17:00!\n";
        echo "Checking isEarlyDeparture method logic...\n";
    }
}