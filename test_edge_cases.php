<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BusinessHoursService;
use App\Models\Setting;
use Carbon\Carbon;

echo "=== Edge Cases and Special Scenarios Test ===\n\n";

$service = new BusinessHoursService();

// Test 1: Check configuration mismatch
echo "1. Testing configuration structure mismatch:\n";
$config = $service->getConfig();
echo "   - Service expects 'business_hours' array with day keys\n";
echo "   - Current config structure:\n";
echo "     * Has 'business_hours' key: " . (isset($config['business_hours']) ? 'Yes' : 'No') . "\n";
if (isset($config['business_hours'])) {
    echo "     * Days in business_hours: " . implode(', ', array_keys($config['business_hours'])) . "\n";
}

// Check database settings
$dbConfig = Setting::where('key', 'attendance.business_hours.config')->first();
if ($dbConfig) {
    $dbValue = json_decode($dbConfig->value, true);
    echo "   - Database config has 'weekdays' key: " . (isset($dbValue['weekdays']) ? 'Yes' : 'No') . "\n";
    echo "   - Database config has 'business_hours' key: " . (isset($dbValue['business_hours']) ? 'Yes' : 'No') . "\n";
    
    // Check if structure matches what service expects
    if (isset($dbValue['weekdays']) && !isset($dbValue['business_hours'])) {
        echo "   ⚠️ POTENTIAL ISSUE: Database uses 'weekdays' but service expects 'business_hours'\n";
    }
}

// Test 2: Overtime threshold mismatch
echo "\n2. Testing overtime threshold configuration:\n";
echo "   - Service uses 'overtime_threshold_minutes'\n";
echo "   - UI form uses 'overtime_start_after_hours'\n";
echo "   - Current config has overtime_threshold_minutes: " . ($config['overtime_threshold_minutes'] ?? 'NOT SET') . "\n";
echo "   - Conversion needed: hours * 60 = minutes\n";

// Test 3: Holiday handling
echo "\n3. Testing holiday handling:\n";
// Check if holidays table exists and has data
try {
    $holidayCount = \App\Models\Holiday::count();
    echo "   - Holidays in database: {$holidayCount}\n";
    
    // Test a date that might be a holiday
    $christmas = Carbon::parse('2025-12-25');
    $isHoliday = \App\Models\Holiday::isHoliday($christmas);
    echo "   - Christmas 2025 is holiday: " . ($isHoliday ? 'Yes' : 'No') . "\n";
    
    $dayType = $service->getBusinessDayType($christmas);
    echo "   - Business day type for Christmas: {$dayType}\n";
    
} catch (Exception $e) {
    echo "   - Holiday check error: {$e->getMessage()}\n";
}

// Test 4: Weekend handling
echo "\n4. Testing weekend handling:\n";
$saturday = Carbon::parse('2025-12-20'); // A Saturday
$sunday = Carbon::parse('2025-12-21'); // A Sunday

echo "   - Saturday ({$saturday->format('Y-m-d')}):\n";
echo "     * Is business day: " . ($service->isBusinessDay($saturday) ? 'Yes' : 'No') . "\n";
echo "     * Business day type: " . $service->getBusinessDayType($saturday) . "\n";
echo "     * Expected hours: " . $service->getExpectedHoursForDate($saturday) . "\n";

echo "   - Sunday ({$sunday->format('Y-m-d')}):\n";
echo "     * Is business day: " . ($service->isBusinessDay($sunday) ? 'Yes' : 'No') . "\n";
echo "     * Business day type: " . $service->getBusinessDayType($sunday) . "\n";
echo "     * Expected hours: " . $service->getExpectedHoursForDate($sunday) . "\n";

// Test 5: Edge time calculations
echo "\n5. Testing edge time calculations:\n";

$edgeCases = [
    ['time' => '00:00', 'label' => 'Midnight'],
    ['time' => '08:59', 'label' => '1 minute before opening'],
    ['time' => '09:00', 'label' => 'Exactly opening'],
    ['time' => '09:15', 'label' => 'End of grace period'],
    ['time' => '09:16', 'label' => '1 minute late'],
    ['time' => '16:59', 'label' => '1 minute before closing'],
    ['time' => '17:00', 'label' => 'Exactly closing'],
    ['time' => '17:01', 'label' => '1 minute after closing'],
    ['time' => '23:59', 'label' => 'End of day'],
];

foreach ($edgeCases as $case) {
    $time = Carbon::parse('2025-12-22 ' . $case['time']);
    $isWithin = $service->isWithinBusinessHours($time);
    $isLate = $service->isLateCheckIn($time);
    $lateMinutes = $service->calculateLateArrivalMinutes($time);
    $isEarly = $service->isEarlyDeparture($time);
    $earlyMinutes = $service->calculateEarlyDepartureMinutes($time);
    $overtime = $service->calculateOvertimeMinutes($time);
    
    echo "   - {$case['label']} ({$case['time']}):\n";
    echo "     * Within business hours: " . ($isWithin ? 'Yes' : 'No') . "\n";
    echo "     * Late check-in: " . ($isLate ? 'Yes' : 'No') . " ({$lateMinutes} min)\n";
    echo "     * Early departure: " . ($isEarly ? 'Yes' : 'No') . " ({$earlyMinutes} min)\n";
    echo "     * Overtime: {$overtime} min\n";
}

// Test 6: Minimum/Maximum hours validation
echo "\n6. Testing hours validation edge cases:\n";
$validationCases = [
    ['hours' => -1, 'expected' => false, 'label' => 'Negative hours'],
    ['hours' => 0, 'expected' => true, 'label' => 'Zero hours'],
    ['hours' => 0.5, 'expected' => true, 'label' => '30 minutes'],
    ['hours' => 3.9, 'expected' => true, 'label' => 'Just below half-day threshold'],
    ['hours' => 4, 'expected' => true, 'label' => 'Exactly half-day threshold'],
    ['hours' => 4.1, 'expected' => true, 'label' => 'Just above half-day threshold'],
    ['hours' => 11.9, 'expected' => true, 'label' => 'Just below maximum'],
    ['hours' => 12, 'expected' => true, 'label' => 'Exactly maximum'],
    ['hours' => 12.1, 'expected' => false, 'label' => 'Just above maximum'],
    ['hours' => 24, 'expected' => false, 'label' => '24 hours (impossible)'],
];

foreach ($validationCases as $case) {
    $valid = $service->validateHoursWorked($case['hours']);
    $passed = $valid === $case['expected'] ? '✓' : '✗';
    echo "   - {$passed} {$case['label']} ({$case['hours']} hrs): Valid = " . ($valid ? 'Yes' : 'No') . "\n";
}

// Test 7: Status determination edge cases
echo "\n7. Testing status determination edge cases:\n";
$statusCases = [
    ['hours' => 0, 'isLate' => false, 'expected' => 'absent', 'label' => 'Zero hours'],
    ['hours' => 0.1, 'isLate' => false, 'expected' => 'half_day', 'label' => '6 minutes'],
    ['hours' => 3.9, 'isLate' => false, 'expected' => 'half_day', 'label' => 'Just below threshold'],
    ['hours' => 4, 'isLate' => false, 'expected' => 'present', 'label' => 'Exactly threshold'],
    ['hours' => 4, 'isLate' => true, 'expected' => 'late', 'label' => 'Exactly threshold but late'],
    ['hours' => 8, 'isLate' => false, 'expected' => 'present', 'label' => 'Full day'],
    ['hours' => 8, 'isLate' => true, 'expected' => 'late', 'label' => 'Full day but late'],
];

foreach ($statusCases as $case) {
    $status = $service->determineStatus($case['hours'], $case['isLate']);
    $passed = $status === $case['expected'] ? '✓' : '✗';
    echo "   - {$passed} {$case['label']} ({$case['hours']} hrs, Late: " . ($case['isLate'] ? 'Yes' : 'No') . "): {$status} (Expected: {$case['expected']})\n";
}

// Test 8: Check for potential configuration issues
echo "\n8. Checking for configuration issues:\n";

// Check if break_duration_minutes is in the right place
if (isset($config['break_duration_minutes'])) {
    echo "   - ✓ break_duration_minutes is in config: {$config['break_duration_minutes']} minutes\n";
} else {
    echo "   - ⚠️ break_duration_minutes is NOT in config (but used in getExpectedHoursForDate)\n";
}

// Check if the UI form structure matches service expectations
echo "   - UI form stores as: settings[attendance.business_hours][weekdays][monday][enabled]\n";
echo "   - Service expects: config['business_hours']['monday']['enabled']\n";
echo "   - This mismatch could cause issues!\n";

// Let's check the actual database structure
$dbSetting = Setting::where('key', 'attendance.business_hours.config')->first();
if ($dbSetting) {
    $value = json_decode($dbSetting->value, true);
    echo "   - Actual database structure keys: " . implode(', ', array_keys($value)) . "\n";
    
    // Check if we need to transform the data
    if (isset($value['weekdays']) && !isset($value['business_hours'])) {
        echo "   ❌ CRITICAL: Database uses 'weekdays' but service expects 'business_hours'!\n";
        echo "   - Need to either:\n";
        echo "     1. Update BusinessHoursService to use 'weekdays' structure\n";
        echo "     2. Transform data when saving/loading\n";
        echo "     3. Update UI to save in correct format\n";
    }
}

echo "\n=== Edge Cases Test Complete ===\n";