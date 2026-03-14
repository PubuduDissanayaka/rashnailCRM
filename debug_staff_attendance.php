<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

echo "=== Staff Attendance Debug ===\n\n";

// 1. Check users with roles
echo "1. Checking users with roles:\n";
$users = User::all();
foreach ($users as $user) {
    $roles = $user->getRoleNames()->toArray();
    echo "  - User #{$user->id}: {$user->name} (Roles: " . implode(', ', $roles) . ")\n";
}

// 2. Check attendance records
echo "\n2. Checking attendance records:\n";
$attendances = Attendance::with('user')->get();
foreach ($attendances as $att) {
    $userName = $att->user ? $att->user->name : 'Unknown';
    echo "  - Attendance #{$att->id}: User {$userName}, Date: {$att->date}, Status: {$att->status}\n";
}

// 3. Test the staff query logic
echo "\n3. Testing staff query logic:\n";
$staffMembers = User::whereHas('roles', function ($q) {
    $q->whereIn('name', ['staff', 'administrator']);
})->get();

echo "  Found " . $staffMembers->count() . " staff/administrator users\n";
foreach ($staffMembers as $staff) {
    echo "    - {$staff->name} (ID: {$staff->id})\n";
}

// 4. Test with a specific staff member if exists
if ($staffMembers->count() > 0) {
    $staffId = $staffMembers->first()->id;
    echo "\n4. Testing attendance query for staff ID {$staffId}:\n";
    
    $startDate = today()->startOfMonth()->format('Y-m-d');
    $endDate = today()->format('Y-m-d');
    
    $query = Attendance::where('user_id', $staffId)
        ->whereBetween('date', [$startDate, $endDate]);
    
    echo "  Date range: {$startDate} to {$endDate}\n";
    echo "  Query SQL: " . $query->toSql() . "\n";
    echo "  Parameters: " . json_encode($query->getBindings()) . "\n";
    
    $records = $query->orderBy('date', 'desc')->get();
    echo "  Found " . $records->count() . " attendance records\n";
    
    // 5. Check the stats query bug
    echo "\n5. Checking stats query bug:\n";
    $statsQuery = Attendance::where('user_id', $staffId)
        ->whereBetween('date', [$startDate, $endDate]);
    
    $totalDays = $statsQuery->count();
    echo "  Total days: {$totalDays}\n";
    
    // This is the bug: $statsQuery already has conditions, but we're adding more without cloning
    $presentDays = $statsQuery->whereIn('status', ['present', 'late'])->count();
    echo "  Present days (with bug): {$presentDays}\n";
    
    // Correct way: clone the query
    $correctPresentDays = Attendance::where('user_id', $staffId)
        ->whereBetween('date', [$startDate, $endDate])
        ->whereIn('status', ['present', 'late'])
        ->count();
    echo "  Present days (correct): {$correctPresentDays}\n";
}

// 6. Check role names in database
echo "\n6. Checking actual role names in database:\n";
$roles = DB::table('roles')->select('name')->get();
echo "  Roles in database: ";
foreach ($roles as $role) {
    echo "{$role->name}, ";
}
echo "\n";

echo "\n=== Debug Complete ===\n";