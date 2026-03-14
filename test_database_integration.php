<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Attendance;
use App\Models\User;
use App\Models\Setting;
use Carbon\Carbon;

echo "=== Database Integration Test (Business Hours Attendance) ===\n\n";

// Create a test user if needed
$testUser = User::where('email', 'test-db@example.com')->first();
if (!$testUser) {
    echo "Creating test user...\n";
    $testUser = User::create([
        'name' => 'Test DB User',
        'email' => 'test-db@example.com',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);
    echo "Created test user with ID: {$testUser->id}\n";
}

// Clear any existing attendance for test dates
Attendance::where('user_id', $testUser->id)
    ->whereDate('date', '>=', Carbon::parse('2025-12-22'))
    ->whereDate('date', '<=', Carbon::parse('2025-12-24'))
    ->delete();

echo "1. Testing database schema for business hours fields:\n";

// Check if all required columns exist in the database
$requiredColumns = [
    'business_hours_type',
    'expected_hours', 
    'calculated_using_business_hours',
    'late_arrival_minutes',
    'early_departure_minutes',
    'overtime_minutes',
    'overtime_hours',
];

$tableColumns = \Illuminate\Support\Facades\Schema::getColumnListing('attendances');
$missingColumns = [];

foreach ($requiredColumns as $column) {
    if (!in_array($column, $tableColumns)) {
        $missingColumns[] = $column;
    }
}

if (empty($missingColumns)) {
    echo "   - ✓ All business hours columns exist in database\n";
} else {
    echo "   - ❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
}

// Test 2: Create attendance record with business hours data
echo "\n2. Testing attendance record creation with business hours:\n";

$testDate = Carbon::parse('2025-12-22'); // Monday
$attendance = new Attendance([
    'user_id' => $testUser->id,
    'date' => $testDate,
    'check_in' => $testDate->copy()->setTime(9, 0),
    'check_out' => $testDate->copy()->setTime(17, 0),
    'status' => 'present',
    'hours_worked' => 8.0,
    'business_hours_type' => 'regular',
    'expected_hours' => 8.0,
    'calculated_using_business_hours' => true,
    'late_arrival_minutes' => 0,
    'early_departure_minutes' => 0,
    'overtime_minutes' => 0,
    'overtime_hours' => 0.0,
]);

$attendance->save();

echo "   - Created attendance record ID: {$attendance->id}\n";
echo "   - Business hours type: {$attendance->business_hours_type}\n";
echo "   - Expected hours: {$attendance->expected_hours}\n";
echo "   - Calculated using business hours: " . ($attendance->calculated_using_business_hours ? 'Yes' : 'No') . "\n";

// Verify the record was saved correctly
$retrieved = Attendance::find($attendance->id);
if ($retrieved) {
    echo "   - ✓ Record retrieved successfully\n";
    echo "   - Business hours type matches: " . ($retrieved->business_hours_type === 'regular' ? '✓' : '✗') . "\n";
    echo "   - Expected hours matches: " . ($retrieved->expected_hours == 8.0 ? '✓' : '✗') . "\n";
} else {
    echo "   - ❌ Failed to retrieve saved record\n";
}

// Test 3: Test configuration storage in settings table
echo "\n3. Testing configuration storage in settings table:\n";

// Check current configuration
$config = Setting::get('attendance.business_hours');
if ($config) {
    echo "   - Configuration exists in settings table\n";
    echo "   - Configuration keys: " . implode(', ', array_keys($config)) . "\n";
    
    // Check for required fields
    $requiredConfigFields = ['weekdays', 'grace_period_minutes', 'overtime_start_after_hours'];
    $missingConfigFields = [];
    
    foreach ($requiredConfigFields as $field) {
        if (!isset($config[$field])) {
            $missingConfigFields[] = $field;
        }
    }
    
    if (empty($missingConfigFields)) {
        echo "   - ✓ All required configuration fields present\n";
    } else {
        echo "   - ⚠️ Missing configuration fields: " . implode(', ', $missingConfigFields) . "\n";
    }
    
    // Check weekdays structure
    if (isset($config['weekdays'])) {
        $weekdayCount = count($config['weekdays']);
        echo "   - Weekdays configured: {$weekdayCount} days\n";
        
        // Check if Monday is enabled (should be based on earlier test)
        if (isset($config['weekdays']['monday'])) {
            $monday = $config['weekdays']['monday'];
            echo "   - Monday: " . ($monday['enabled'] ? 'Enabled' : 'Disabled') . 
                 " ({$monday['open']} - {$monday['close']})\n";
        }
    }
} else {
    echo "   - ⚠️ No business hours configuration found in settings table\n";
}

// Test 4: Test relationships and constraints
echo "\n4. Testing relationships and constraints:\n";

// Test user relationship
$userAttendances = $testUser->attendances()->count();
echo "   - User has {$userAttendances} attendance record(s)\n";

// Test date constraint - shouldn't allow duplicate attendance for same user/date
echo "   - Testing duplicate prevention (same user/date):\n";
try {
    $duplicate = new Attendance([
        'user_id' => $testUser->id,
        'date' => $testDate,
        'check_in' => $testDate->copy()->setTime(8, 0),
        'status' => 'present',
    ]);
    $duplicate->save();
    echo "   - ❌ Duplicate record created (should have been prevented)\n";
} catch (\Exception $e) {
    echo "   - ✓ Duplicate prevented: " . $e->getMessage() . "\n";
}

// Test 5: Test data integrity with business hours calculations
echo "\n5. Testing data integrity with business hours calculations:\n";

// Create a test attendance using the checkIn/checkOut methods
$testDate2 = Carbon::parse('2025-12-23'); // Tuesday
$attendance2 = Attendance::getOrCreateToday($testUser->id);
$attendance2->date = $testDate2;

// Simulate check-in at 9:30 (30 minutes late based on 08:30 start with 10 min grace)
$checkInTime = $testDate2->copy()->setTime(9, 30);
$attendance2->checkIn($checkInTime, ['method' => 'web']);

echo "   - Created attendance with check-in at 09:30\n";
echo "   - Status: {$attendance2->status}\n";
echo "   - Late arrival minutes: {$attendance2->late_arrival_minutes}\n";
echo "   - Business hours type: {$attendance2->business_hours_type}\n";

// Simulate check-out at 16:30 (1 hour early based on 17:30 close)
$checkOutTime = $testDate2->copy()->setTime(16, 30);
$attendance2->checkOut($checkOutTime, ['method' => 'web']);

echo "   - Checked out at 16:30\n";
echo "   - Hours worked: {$attendance2->hours_worked}\n";
echo "   - Early departure minutes: {$attendance2->early_departure_minutes}\n";
echo "   - Overtime minutes: {$attendance2->overtime_minutes}\n";

// Verify calculations
$expectedLateMinutes = 50; // 09:30 is 50 minutes after 08:40 (08:30 + 10 min grace)
$expectedEarlyMinutes = 60; // 16:30 is 60 minutes before 17:30
$expectedOvertime = 0; // No overtime

echo "   - Late minutes expected: {$expectedLateMinutes}, actual: {$attendance2->late_arrival_minutes} " . 
     ($attendance2->late_arrival_minutes == $expectedLateMinutes ? '✓' : '✗') . "\n";
echo "   - Early minutes expected: {$expectedEarlyMinutes}, actual: {$attendance2->early_departure_minutes} " . 
     ($attendance2->early_departure_minutes == $expectedEarlyMinutes ? '✓' : '✗') . "\n";
echo "   - Overtime expected: {$expectedOvertime}, actual: {$attendance2->overtime_minutes} " . 
     ($attendance2->overtime_minutes == $expectedOvertime ? '✓' : '✗') . "\n";

// Test 6: Check database indexes and performance
echo "\n6. Checking database indexes for performance:\n";

// Get table indexes
$indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEXES FROM attendances");
$importantIndexes = [
    'attendances_user_id_date_index',
    'attendances_date_index', 
    'attendances_status_index',
    'attendances_check_in_index',
    'attendances_check_out_index',
];

$foundIndexes = [];
foreach ($indexes as $index) {
    $foundIndexes[] = $index->Key_name;
}

$missingIndexes = [];
foreach ($importantIndexes as $index) {
    if (!in_array($index, $foundIndexes)) {
        $missingIndexes[] = $index;
    }
}

if (empty($missingIndexes)) {
    echo "   - ✓ All important indexes exist\n";
} else {
    echo "   - ⚠️ Missing indexes: " . implode(', ', $missingIndexes) . "\n";
    echo "   - Consider adding these indexes for better performance\n";
}

// Test 7: Test data migration compatibility
echo "\n7. Testing data migration compatibility:\n";

// Check if old shift-related columns still exist
$oldColumns = ['shift_id', 'shift_assignment_id', 'scheduled_start', 'scheduled_end'];
$foundOldColumns = [];

foreach ($oldColumns as $column) {
    if (in_array($column, $tableColumns)) {
        $foundOldColumns[] = $column;
    }
}

if (empty($foundOldColumns)) {
    echo "   - ✓ No old shift-related columns found (good!)\n";
} else {
    echo "   - ⚠️ Old shift columns still exist: " . implode(', ', $foundOldColumns) . "\n";
    echo "   - These should be removed or migrated to business hours fields\n";
}

// Clean up
echo "\n8. Cleaning up test data:\n";
$deletedCount = Attendance::where('user_id', $testUser->id)
    ->whereDate('date', '>=', Carbon::parse('2025-12-22'))
    ->whereDate('date', '<=', Carbon::parse('2025-12-24'))
    ->delete();

echo "   - Deleted {$deletedCount} test attendance records\n";

// Optional: delete test user
// $testUser->delete();

echo "\n=== Database Integration Test Complete ===\n";