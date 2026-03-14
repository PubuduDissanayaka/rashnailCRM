<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

echo "=== API Endpoints Test (Business Hours Integration) ===\n\n";

// Create a test user if needed
$testUser = User::where('email', 'test@example.com')->first();
if (!$testUser) {
    echo "Creating test user...\n";
    $testUser = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);
    echo "Created test user with ID: {$testUser->id}\n";
}

// Clear any existing attendance for today
Attendance::where('user_id', $testUser->id)
    ->whereDate('date', today())
    ->delete();

echo "\n1. Testing API endpoint simulation:\n";

// Simulate check-in at different times
$testCases = [
    [
        'time' => '09:00',
        'label' => 'On time check-in',
        'expected_status' => 'present',
        'expected_late_minutes' => 0,
    ],
    [
        'time' => '09:16',
        'label' => 'Late check-in (1 minute after grace period)',
        'expected_status' => 'late',
        'expected_late_minutes' => 1,
    ],
    [
        'time' => '10:00',
        'label' => 'Late check-in (45 minutes late)',
        'expected_status' => 'late',
        'expected_late_minutes' => 45,
    ],
];

foreach ($testCases as $test) {
    echo "\n   - Test: {$test['label']} ({$test['time']})\n";
    
    // Set the current time for testing
    $testTime = Carbon::parse(today()->format('Y-m-d') . ' ' . $test['time']);
    Carbon::setTestNow($testTime);
    
    try {
        // Simulate check-in using the service directly
        $attendanceService = app(\App\Services\AttendanceService::class);
        $attendance = $attendanceService->checkIn($testUser, [
            'notes' => 'Test check-in',
            'latitude' => 6.9271,
            'longitude' => 79.8612,
        ]);
        
        echo "     * Check-in successful\n";
        echo "     * Status: {$attendance->status} (Expected: {$test['expected_status']})\n";
        echo "     * Late minutes: {$attendance->late_arrival_minutes} (Expected: {$test['expected_late_minutes']})\n";
        echo "     * Business hours type: {$attendance->business_hours_type}\n";
        echo "     * Expected hours: {$attendance->expected_hours}\n";
        echo "     * Using business hours: " . ($attendance->calculated_using_business_hours ? 'Yes' : 'No') . "\n";
        
        // Verify expectations
        $statusPass = $attendance->status === $test['expected_status'] ? '✓' : '✗';
        $latePass = $attendance->late_arrival_minutes === $test['expected_late_minutes'] ? '✓' : '✗';
        echo "     * Status match: {$statusPass}, Late minutes match: {$latePass}\n";
        
        // Clean up for next test
        $attendance->delete();
        
    } catch (\Exception $e) {
        echo "     * Error: {$e->getMessage()}\n";
    }
}

// Reset time
Carbon::setTestNow();

// Test check-out scenarios
echo "\n2. Testing check-out scenarios:\n";

// Create a fresh attendance for check-out tests
$checkInTime = Carbon::parse(today()->format('Y-m-d') . ' 09:00');
Carbon::setTestNow($checkInTime);

$attendanceService = app(\App\Services\AttendanceService::class);
$attendance = $attendanceService->checkIn($testUser, [
    'notes' => 'Test for check-out',
]);

echo "   - Created test attendance with check-in at 09:00\n";

// Test different check-out times
$checkOutTests = [
    [
        'time' => '16:30',
        'label' => 'Early departure (30 minutes early)',
        'expected_early_minutes' => 30,
        'expected_overtime' => 0,
        'expected_hours' => 7.5, // 7.5 hours worked (09:00-16:30 = 7.5 hours)
    ],
    [
        'time' => '17:00',
        'label' => 'On time check-out',
        'expected_early_minutes' => 0,
        'expected_overtime' => 0,
        'expected_hours' => 8.0, // 8 hours worked
    ],
    [
        'time' => '17:30',
        'label' => 'Overtime (30 minutes)',
        'expected_early_minutes' => 0,
        'expected_overtime' => 30,
        'expected_hours' => 8.5, // 8.5 hours worked
    ],
];

foreach ($checkOutTests as $test) {
    $checkOutTime = Carbon::parse(today()->format('Y-m-d') . ' ' . $test['time']);
    Carbon::setTestNow($checkOutTime);
    
    echo "\n   - Test: {$test['label']} ({$test['time']})\n";
    
    try {
        $updatedAttendance = $attendanceService->checkOut($testUser, [
            'notes' => 'Test check-out',
        ]);
        
        echo "     * Check-out successful\n";
        echo "     * Hours worked: {$updatedAttendance->hours_worked} (Expected: {$test['expected_hours']})\n";
        echo "     * Early departure minutes: {$updatedAttendance->early_departure_minutes} (Expected: {$test['expected_early_minutes']})\n";
        echo "     * Overtime minutes: {$updatedAttendance->overtime_minutes} (Expected: {$test['expected_overtime']})\n";
        echo "     * Status: {$updatedAttendance->status}\n";
        
        // Verify expectations
        $hoursPass = abs($updatedAttendance->hours_worked - $test['expected_hours']) < 0.1 ? '✓' : '✗';
        $earlyPass = $updatedAttendance->early_departure_minutes === $test['expected_early_minutes'] ? '✓' : '✗';
        $overtimePass = $updatedAttendance->overtime_minutes === $test['expected_overtime'] ? '✓' : '✗';
        echo "     * Hours match: {$hoursPass}, Early match: {$earlyPass}, Overtime match: {$overtimePass}\n";
        
        // Delete and recreate for next test
        $updatedAttendance->delete();
        Carbon::setTestNow($checkInTime);
        $attendance = $attendanceService->checkIn($testUser, [
            'notes' => 'Test for check-out',
        ]);
        
    } catch (\Exception $e) {
        echo "     * Error: {$e->getMessage()}\n";
    }
}

// Reset time
Carbon::setTestNow();

// Test half-day scenario
echo "\n3. Testing half-day scenario:\n";
$halfDayCheckIn = Carbon::parse(today()->format('Y-m-d') . ' 09:00');
$halfDayCheckOut = Carbon::parse(today()->format('Y-m-d') . ' 12:00'); // 3 hours worked

Carbon::setTestNow($halfDayCheckIn);
$attendance = $attendanceService->checkIn($testUser, ['notes' => 'Half-day test']);

Carbon::setTestNow($halfDayCheckOut);
$updatedAttendance = $attendanceService->checkOut($testUser, ['notes' => 'Half-day check-out']);

echo "   - Half-day test (09:00-12:00 = 3 hours):\n";
echo "     * Hours worked: {$updatedAttendance->hours_worked}\n";
echo "     * Status: {$updatedAttendance->status}\n";
echo "     * Expected status: half_day (since 3 < 4 hour threshold)\n";
echo "     * Status match: " . ($updatedAttendance->status === 'half_day' ? '✓' : '✗') . "\n";

$updatedAttendance->delete();

// Test weekend/holiday scenarios
echo "\n4. Testing non-business day scenarios:\n";

// Test Saturday (disabled in config)
$saturday = Carbon::parse('2025-12-20 09:00'); // A Saturday
Carbon::setTestNow($saturday);

try {
    $attendance = $attendanceService->checkIn($testUser, ['notes' => 'Saturday test']);
    echo "   - Saturday check-in:\n";
    echo "     * Business hours type: {$attendance->business_hours_type}\n";
    echo "     * Expected hours: {$attendance->expected_hours}\n";
    echo "     * Status: {$attendance->status}\n";
    
    // Check-out after a few hours
    $saturdayCheckOut = $saturday->copy()->addHours(4);
    Carbon::setTestNow($saturdayCheckOut);
    $updatedAttendance = $attendanceService->checkOut($testUser, []);
    
    echo "     * Hours worked on Saturday: {$updatedAttendance->hours_worked}\n";
    echo "     * Weekend handling: " . ($attendance->business_hours_type === 'weekend' ? '✓' : '✗') . "\n";
    
    $updatedAttendance->delete();
} catch (\Exception $e) {
    echo "   - Saturday test error: {$e->getMessage()}\n";
}

// Reset time
Carbon::setTestNow();

// Test API response structure
echo "\n5. Testing API response structure:\n";
echo "   - Check-in API should return:\n";
echo "     * success: true\n";
echo "     * attendance object with business_hours_type, expected_hours, calculated_using_business_hours\n";
echo "     * late_arrival_minutes if applicable\n";
echo "   - Check-out API should return:\n";
echo "     * success: true\n";
echo "     * hours_worked, overtime_minutes, early_departure_minutes\n";
echo "     * status (should be 'half_day' if hours < threshold)\n";

// Check if shift-related fields are still being used
echo "\n6. Checking for shift-related code remnants:\n";
$controllerCode = file_get_contents(__DIR__.'/app/Http/Controllers/AttendanceController.php');
$serviceCode = file_get_contents(__DIR__.'/app/Services/AttendanceService.php');

$shiftReferences = [
    'Shift',
    'ShiftAssignment',
    'shift_id',
    'shift_',
];

$foundShifts = [];
foreach ($shiftReferences as $ref) {
    if (strpos($controllerCode, $ref) !== false || strpos($serviceCode, $ref) !== false) {
        $foundShifts[] = $ref;
    }
}

if (empty($foundShifts)) {
    echo "   - ✓ No shift-related code found in controller or service\n";
} else {
    echo "   - ⚠️ Shift references still found: " . implode(', ', $foundShifts) . "\n";
}

// Check business hours integration in API responses
echo "\n7. Checking business hours integration in API responses:\n";
$requiredBusinessHoursFields = [
    'business_hours_type',
    'expected_hours',
    'calculated_using_business_hours',
    'late_arrival_minutes',
    'early_departure_minutes',
    'overtime_minutes',
];

$missingInController = [];
foreach ($requiredBusinessHoursFields as $field) {
    if (strpos($controllerCode, $field) === false) {
        $missingInController[] = $field;
    }
}

if (empty($missingInController)) {
    echo "   - ✓ All business hours fields referenced in controller\n";
} else {
    echo "   - ⚠️ Missing business hours fields in controller: " . implode(', ', $missingInController) . "\n";
}

echo "\n=== API Endpoints Test Complete ===\n";

// Clean up
if ($testUser && $testUser->email === 'test@example.com') {
    // Optional: delete test user
    // $testUser->delete();
}