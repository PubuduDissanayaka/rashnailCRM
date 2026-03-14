<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Attendance;
use App\Models\Setting;
use App\Services\BusinessHoursService;
use Carbon\Carbon;

echo "=== FINAL VALIDATION REPORT: Business Hours Attendance System ===\n\n";
echo "Test Date/Time: " . now()->format('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

// 1. Database Migration Validation
echo "1. DATABASE MIGRATION VALIDATION\n";
echo "   -----------------------------\n";

$migrationChecks = [
    'settings_business_hours' => false,
    'attendances_business_hours_fields' => false,
    'shifts_tables_dropped' => false,
];

// Check settings table
$settingsCount = Setting::where('key', 'like', 'attendance.business_hours.%')->count();
$migrationChecks['settings_business_hours'] = $settingsCount >= 6;
echo "   - Business hours settings in database: {$settingsCount} records " . 
     ($migrationChecks['settings_business_hours'] ? "✓" : "✗") . "\n";

// Check attendances table columns
try {
    $columns = DB::select('DESCRIBE attendances');
    $requiredColumns = ['business_hours_type', 'expected_hours', 'calculated_using_business_hours'];
    $foundColumns = [];
    
    foreach ($columns as $column) {
        if (in_array($column->Field, $requiredColumns)) {
            $foundColumns[] = $column->Field;
        }
    }
    
    $migrationChecks['attendances_business_hours_fields'] = count($foundColumns) === count($requiredColumns);
    echo "   - Business hours columns in attendances table: " . implode(', ', $foundColumns) . 
         " " . ($migrationChecks['attendances_business_hours_fields'] ? "✓" : "✗") . "\n";
} catch (Exception $e) {
    echo "   - Error checking attendances table: {$e->getMessage()}\n";
}

// Check if shifts tables were dropped
try {
    $shiftsExists = DB::select("SHOW TABLES LIKE 'shifts'");
    $assignmentsExists = DB::select("SHOW TABLES LIKE 'shift_assignments'");
    
    $migrationChecks['shifts_tables_dropped'] = (count($shiftsExists) === 0 && count($assignmentsExists) === 0);
    echo "   - Shifts tables dropped: " . 
         ($migrationChecks['shifts_tables_dropped'] ? "✓" : "✗") . 
         " (shifts: " . (count($shiftsExists) > 0 ? "exists" : "dropped") . 
         ", shift_assignments: " . (count($assignmentsExists) > 0 ? "exists" : "dropped") . ")\n";
} catch (Exception $e) {
    echo "   - Error checking shifts tables: {$e->getMessage()}\n";
}

// 2. Service Functionality Validation
echo "\n2. SERVICE FUNCTIONALITY VALIDATION\n";
echo "   -------------------------------\n";

$service = new BusinessHoursService();
$serviceChecks = [
    'config_retrieval' => false,
    'business_day_detection' => false,
    'time_calculations' => false,
    'status_determination' => false,
];

// Config retrieval
try {
    $config = $service->getConfig();
    $serviceChecks['config_retrieval'] = is_array($config) && isset($config['business_hours']);
    echo "   - Configuration retrieval: " . ($serviceChecks['config_retrieval'] ? "✓" : "✗") . "\n";
} catch (Exception $e) {
    echo "   - Configuration retrieval error: {$e->getMessage()}\n";
}

// Business day detection
try {
    $monday = Carbon::parse('2025-12-22'); // Monday
    $saturday = Carbon::parse('2025-12-20'); // Saturday
    
    $mondayIsBusiness = $service->isBusinessDay($monday);
    $saturdayIsBusiness = $service->isBusinessDay($saturday);
    
    $serviceChecks['business_day_detection'] = ($mondayIsBusiness === true && $saturdayIsBusiness === false);
    echo "   - Business day detection: " . 
         ($serviceChecks['business_day_detection'] ? "✓" : "✗") . 
         " (Monday: " . ($mondayIsBusiness ? "business" : "non-business") . 
         ", Saturday: " . ($saturdayIsBusiness ? "business" : "non-business") . ")\n";
} catch (Exception $e) {
    echo "   - Business day detection error: {$e->getMessage()}\n";
}

// Time calculations
try {
    $testTime = Carbon::parse('2025-12-22 09:30');
    $lateCheck = $service->isLateCheckIn($testTime);
    $lateMinutes = $service->calculateLateArrivalMinutes($testTime);
    
    $serviceChecks['time_calculations'] = ($lateCheck === true && $lateMinutes === 15);
    echo "   - Time calculations: " . ($serviceChecks['time_calculations'] ? "✓" : "✗") . 
         " (09:30 is late: " . ($lateCheck ? "Yes" : "No") . 
         ", minutes: {$lateMinutes})\n";
} catch (Exception $e) {
    echo "   - Time calculations error: {$e->getMessage()}\n";
}

// Status determination
try {
    $status1 = $service->determineStatus(3, false); // half-day
    $status2 = $service->determineStatus(8, true);  // late
    $status3 = $service->determineStatus(0, false); // absent
    
    $serviceChecks['status_determination'] = ($status1 === 'half_day' && $status2 === 'late' && $status3 === 'absent');
    echo "   - Status determination: " . ($serviceChecks['status_determination'] ? "✓" : "✗") . 
         " (3hrs: {$status1}, 8hrs+late: {$status2}, 0hrs: {$status3})\n";
} catch (Exception $e) {
    echo "   - Status determination error: {$e->getMessage()}\n";
}

// 3. Data Migration Validation
echo "\n3. DATA MIGRATION VALIDATION\n";
echo "   -------------------------\n";

$dataChecks = [
    'existing_attendances' => false,
    'business_hours_populated' => false,
];

// Check existing attendance records
$totalAttendances = Attendance::count();
$businessHoursAttendances = Attendance::where('calculated_using_business_hours', true)->count();
$withExpectedHours = Attendance::whereNotNull('expected_hours')->count();

echo "   - Total attendance records: {$totalAttendances}\n";
echo "   - Records using business hours: {$businessHoursAttendances}\n";
echo "   - Records with expected hours: {$withExpectedHours}\n";

if ($totalAttendances > 0) {
    $dataChecks['existing_attendances'] = true;
    $percentage = $totalAttendances > 0 ? round(($businessHoursAttendances / $totalAttendances) * 100, 1) : 0;
    echo "   - Business hours adoption rate: {$percentage}%\n";
    
    // Check a sample record
    $sample = Attendance::first();
    if ($sample) {
        echo "   - Sample record check:\n";
        echo "     * ID: {$sample->id}\n";
        echo "     * Date: {$sample->date}\n";
        echo "     * Business hours type: {$sample->business_hours_type}\n";
        echo "     * Expected hours: {$sample->expected_hours}\n";
        echo "     * Using business hours: " . ($sample->calculated_using_business_hours ? 'Yes' : 'No') . "\n";
        
        $dataChecks['business_hours_populated'] = !empty($sample->business_hours_type) || !is_null($sample->expected_hours);
    }
}

// 4. Configuration Issues Check
echo "\n4. CONFIGURATION ISSUES CHECK\n";
echo "   --------------------------\n";

$configIssues = [];

// Check configuration structure
$config = $service->getConfig();

// Check for break_duration_minutes
if (!isset($config['break_duration_minutes'])) {
    $configIssues[] = "Missing 'break_duration_minutes' in config (used in getExpectedHoursForDate)";
    echo "   - ⚠️ Missing break_duration_minutes in config\n";
} else {
    echo "   - ✓ break_duration_minutes: {$config['break_duration_minutes']} minutes\n";
}

// Check overtime threshold naming
if (isset($config['overtime_start_after_hours'])) {
    $configIssues[] = "Unexpected 'overtime_start_after_hours' (should be 'overtime_threshold_minutes')";
    echo "   - ⚠️ Found 'overtime_start_after_hours' instead of 'overtime_threshold_minutes'\n";
} elseif (!isset($config['overtime_threshold_minutes'])) {
    $configIssues[] = "Missing 'overtime_threshold_minutes' in config";
    echo "   - ⚠️ Missing 'overtime_threshold_minutes' in config\n";
} else {
    echo "   - ✓ overtime_threshold_minutes: {$config['overtime_threshold_minutes']} minutes\n";
}

// Check UI vs Service structure mismatch
$dbSetting = Setting::where('key', 'attendance.business_hours.config')->first();
if ($dbSetting) {
    $dbValue = $dbSetting->value;
    if (is_array($dbValue)) {
        // Check structure
        if (isset($dbValue['weekdays']) && !isset($dbValue['business_hours'])) {
            $configIssues[] = "UI stores as 'weekdays' but service expects 'business_hours' structure";
            echo "   - ❌ CRITICAL: Structure mismatch - UI uses 'weekdays', service expects 'business_hours'\n";
        }
    }
}

// 5. Summary and Recommendations
echo "\n5. SUMMARY AND RECOMMENDATIONS\n";
echo "   ----------------------------\n";

$totalChecks = array_merge($migrationChecks, $serviceChecks, $dataChecks);
$passedChecks = array_sum($totalChecks);
$totalCheckCount = count($totalChecks);

echo "   Overall Status: " . round(($passedChecks / $totalCheckCount) * 100, 1) . "% passed\n";
echo "   (" . $passedChecks . " of " . $totalCheckCount . " checks passed)\n\n";

echo "   CRITICAL ISSUES FOUND:\n";
if (empty($configIssues)) {
    echo "   - None ✓\n";
} else {
    foreach ($configIssues as $issue) {
        echo "   - {$issue}\n";
    }
}

echo "\n   RECOMMENDATIONS:\n";

// Based on findings
$recommendations = [];

if (in_array(false, $migrationChecks, true)) {
    $recommendations[] = "Run missing migrations or fix database schema issues";
}

if (!empty($configIssues)) {
    $recommendations[] = "Fix configuration structure mismatch between UI and Service";
    $recommendations[] = "Update BusinessHoursService to handle 'weekdays' structure or fix UI to save as 'business_hours'";
}

if ($businessHoursAttendances < $totalAttendances && $totalAttendances > 0) {
    $recommendations[] = "Run data migration to update existing attendance records with business hours data";
}

if (empty($recommendations)) {
    echo "   - System is functioning correctly. No immediate action required. ✓\n";
} else {
    foreach ($recommendations as $i => $rec) {
        echo "   " . ($i + 1) . ". {$rec}\n";
    }
}

// 6. Final Verdict
echo "\n6. FINAL VERDICT\n";
echo "   -------------\n";

$criticalIssues = count($configIssues);
$allMigrationsPassed = !in_array(false, $migrationChecks, true);
$allServicesPassed = !in_array(false, $serviceChecks, true);

if ($criticalIssues === 0 && $allMigrationsPassed && $allServicesPassed) {
    echo "   ✅ BUSINESS HOURS ATTENDANCE SYSTEM IS READY FOR PRODUCTION\n";
    echo "   The system has been successfully migrated from shift-based to business hours-based.\n";
} elseif ($criticalIssues > 0) {
    echo "   ⚠️ SYSTEM HAS CRITICAL ISSUES THAT NEED FIXING\n";
    echo "   Address the configuration issues before deploying to production.\n";
} else {
    echo "   ⚠️ SYSTEM HAS MINOR ISSUES\n";
    echo "   The core functionality works, but some improvements are needed.\n";
}

echo "\n========================================\n";
echo "Validation completed at: " . now()->format('Y-m-d H:i:s') . "\n";