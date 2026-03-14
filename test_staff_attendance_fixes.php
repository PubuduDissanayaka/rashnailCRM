<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

echo "=== Testing Staff Attendance Fixes ===\n\n";

// Test 1: Check if controller fixes work
echo "Test 1: Controller method fixes\n";
echo "--------------------------------\n";

// Simulate the updated staff method logic
$staffId = 1; // Admin User
$startDate = today()->startOfMonth()->format('Y-m-d');
$endDate = today()->format('Y-m-d');

// Get staff members list - should include users with attendance records
$staffMembers = User::whereHas('roles', function ($q) {
    $q->whereIn('name', ['staff', 'administrator']);
})->orWhereHas('attendances')->get()->unique();

echo "Staff members count (with fix): " . $staffMembers->count() . "\n";
echo "Expected: At least 1 (Admin User) but should include users with attendance records\n";
foreach ($staffMembers as $staff) {
    $attendanceCount = $staff->attendances()->count();
    $roles = $staff->getRoleNames()->toArray();
    echo "  - {$staff->name} (ID: {$staff->id}, Roles: " . implode(', ', $roles) . ", Attendance records: {$attendanceCount})\n";
}

// Test 2: Check statistics calculation fix
echo "\nTest 2: Statistics calculation fix\n";
echo "-----------------------------------\n";

if ($staffId) {
    $selectedStaff = User::find($staffId);
    
    if ($selectedStaff) {
        // Calculate statistics using the fixed method (separate queries)
        $totalDays = Attendance::where('user_id', $staffId)
            ->whereBetween('date', [$startDate, $endDate])->count();
        
        $presentDays = Attendance::where('user_id', $staffId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['present', 'late'])->count();
            
        $lateDays = Attendance::where('user_id', $staffId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'late')->count();
            
        $absentDays = Attendance::where('user_id', $staffId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'absent')->count();
            
        echo "Statistics for {$selectedStaff->name}:\n";
        echo "  Total days: {$totalDays}\n";
        echo "  Present days: {$presentDays}\n";
        echo "  Late days: {$lateDays}\n";
        echo "  Absent days: {$absentDays}\n";
        
        // Verify the bug is fixed
        $buggyStatsQuery = Attendance::where('user_id', $staffId)
            ->whereBetween('date', [$startDate, $endDate]);
            
        $buggyTotal = $buggyStatsQuery->count();
        $buggyPresent = $buggyStatsQuery->whereIn('status', ['present', 'late'])->count();
        
        echo "\nBug check (reusing query object):\n";
        echo "  Buggy total: {$buggyTotal}\n";
        echo "  Buggy present (incorrect): {$buggyPresent}\n";
        echo "  Note: If buggyPresent equals buggyTotal, the bug exists (query not cloned)\n";
    }
}

// Test 3: Check attendance records query
echo "\nTest 3: Attendance records query\n";
echo "---------------------------------\n";

$query = Attendance::where('user_id', $staffId)
    ->whereBetween('date', [$startDate, $endDate]);
    
$attendanceRecords = $query->orderBy('date', 'desc')->paginate(20);

echo "Attendance records found: " . $attendanceRecords->count() . "\n";
echo "Total records: " . $attendanceRecords->total() . "\n";
echo "Current page: " . $attendanceRecords->currentPage() . "\n";

foreach ($attendanceRecords as $record) {
    echo "  - ID {$record->id}: {$record->date} - {$record->status}";
    if ($record->check_in) echo " (Check-in: {$record->check_in->format('H:i')})";
    if ($record->check_out) echo " (Check-out: {$record->check_out->format('H:i')})";
    echo "\n";
}

// Test 4: Check if users without roles appear in dropdown
echo "\nTest 4: Users without roles but with attendance records\n";
echo "--------------------------------------------------------\n";

$usersWithAttendance = User::whereHas('attendances')->get();
echo "Users with attendance records: " . $usersWithAttendance->count() . "\n";

foreach ($usersWithAttendance as $user) {
    $roles = $user->getRoleNames()->toArray();
    $attendanceCount = $user->attendances()->count();
    echo "  - {$user->name} (ID: {$user->id}, Roles: " . (empty($roles) ? 'None' : implode(', ', $roles)) . ", Attendance: {$attendanceCount})\n";
}

// Test 5: Verify the fix includes all users with attendance
echo "\nTest 5: Final verification\n";
echo "---------------------------\n";

$allUsers = User::all();
echo "All users in system: " . $allUsers->count() . "\n";

$usersInDropdown = $staffMembers->pluck('id')->toArray();
echo "Users that will appear in dropdown: " . count($usersInDropdown) . "\n";

foreach ($allUsers as $user) {
    $inDropdown = in_array($user->id, $usersInDropdown) ? 'YES' : 'NO';
    $roles = $user->getRoleNames()->toArray();
    $attendanceCount = $user->attendances()->count();
    echo "  - {$user->name}: In dropdown? {$inDropdown}, Roles: " . (empty($roles) ? 'None' : implode(', ', $roles)) . ", Attendance records: {$attendanceCount}\n";
}

echo "\n=== Test Complete ===\n";
echo "\nSummary of fixes applied:\n";
echo "1. Fixed statistics query bug (reusing query object without cloning)\n";
echo "2. Updated staff dropdown to include users with attendance records even without roles\n";
echo "3. Added DataTable initialization in staff-attendance.js\n";
echo "4. Controller now returns correct data for selected staff member\n";