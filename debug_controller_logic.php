<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== Controller Logic Debug ===\n\n";

// Simulate the staff method logic
$staffId = 1; // Admin User
$startDate = today()->startOfMonth()->format('Y-m-d');
$endDate = today()->format('Y-m-d');
$status = null;

echo "Parameters:\n";
echo "  staffId: {$staffId}\n";
echo "  startDate: {$startDate}\n";
echo "  endDate: {$endDate}\n";
echo "  status: " . ($status ?: 'null') . "\n\n";

// Get staff members list
$staffMembers = User::whereHas('roles', function ($q) {
    $q->whereIn('name', ['staff', 'administrator']);
})->get();

echo "Staff members found: " . $staffMembers->count() . "\n";
foreach ($staffMembers as $staff) {
    echo "  - {$staff->name} (ID: {$staff->id})\n";
}

$selectedStaff = null;
$attendanceRecords = collect();
$staffStats = [
    'total_days' => 0,
    'present_days' => 0,
    'late_days' => 0,
    'absent_days' => 0,
    'leave_days' => 0,
    'half_day_days' => 0,
];

if ($staffId) {
    $selectedStaff = User::find($staffId);
    
    if ($selectedStaff) {
        echo "\nSelected staff: {$selectedStaff->name} (ID: {$selectedStaff->id})\n";
        
        // Build query for this staff member's attendance
        $query = Attendance::where('user_id', $staffId)
            ->whereBetween('date', [$startDate, $endDate]);

        if ($status) {
            $query->where('status', $status);
        }

        $attendanceRecords = $query->orderBy('date', 'desc')->get();
        
        echo "Attendance query SQL: " . $query->toSql() . "\n";
        echo "Query bindings: " . json_encode($query->getBindings()) . "\n";
        echo "Records found: " . $attendanceRecords->count() . "\n";
        
        foreach ($attendanceRecords as $record) {
            echo "  - ID {$record->id}: {$record->date} - {$record->status}\n";
        }

        // Calculate staff-specific statistics
        // BUG: This reuses $statsQuery without cloning
        $statsQuery = Attendance::where('user_id', $staffId)
            ->whereBetween('date', [$startDate, $endDate]);

        $staffStats = [
            'total_days' => $statsQuery->count(),
            'present_days' => $statsQuery->whereIn('status', ['present', 'late'])->count(),
            'late_days' => $statsQuery->where('status', 'late')->count(),
            'absent_days' => $statsQuery->where('status', 'absent')->count(),
            'leave_days' => $statsQuery->where('status', 'leave')->count(),
            'half_day_days' => $statsQuery->where('status', 'half_day')->count(),
        ];
        
        echo "\nStatistics (with bug):\n";
        foreach ($staffStats as $key => $value) {
            echo "  {$key}: {$value}\n";
        }
        
        // Correct statistics
        $correctStats = [
            'total_days' => Attendance::where('user_id', $staffId)
                ->whereBetween('date', [$startDate, $endDate])->count(),
            'present_days' => Attendance::where('user_id', $staffId)
                ->whereBetween('date', [$startDate, $endDate])
                ->whereIn('status', ['present', 'late'])->count(),
            'late_days' => Attendance::where('user_id', $staffId)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('status', 'late')->count(),
            'absent_days' => Attendance::where('user_id', $staffId)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('status', 'absent')->count(),
            'leave_days' => Attendance::where('user_id', $staffId)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('status', 'leave')->count(),
            'half_day_days' => Attendance::where('user_id', $staffId)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('status', 'half_day')->count(),
        ];
        
        echo "\nCorrect statistics:\n";
        foreach ($correctStats as $key => $value) {
            echo "  {$key}: {$value}\n";
        }
    }
}

// Check if there's an issue with pagination
echo "\n\nTesting pagination:\n";
$paginatedQuery = Attendance::where('user_id', $staffId)
    ->whereBetween('date', [$startDate, $endDate])
    ->orderBy('date', 'desc');
    
$paginatedResults = $paginatedQuery->paginate(20);
echo "Paginated total: " . $paginatedResults->total() . "\n";
echo "Paginated count: " . $paginatedResults->count() . "\n";
echo "Paginated items: " . json_encode($paginatedResults->items()) . "\n";

// Check view variables
echo "\n\nView variables that would be passed:\n";
echo "\$staffMembers count: " . $staffMembers->count() . "\n";
echo "\$selectedStaff: " . ($selectedStaff ? $selectedStaff->name : 'null') . "\n";
echo "\$attendanceRecords count: " . $attendanceRecords->count() . "\n";
echo "\$staffStats: " . json_encode($staffStats) . "\n";

echo "\n=== Debug Complete ===\n";