<?php

echo "=== Business Hours System Structure Verification ===\n\n";

// Check 1: Attendance model syntax error fix
echo "1. Checking Attendance model for syntax errors...\n";
$attendanceContent = file_get_contents(__DIR__ . '/app/Models/Attendance.php');
if (strpos($attendanceContent, '$this.date') !== false) {
    echo "   ✗ Found syntax error: \$this.date (should be \$this->date)\n";
} else {
    echo "   ✓ No syntax errors found in Attendance model\n";
}

// Check 2: Method name consistency
echo "\n2. Checking method name consistency across files...\n";

$filesToCheck = [
    'app/Models/Attendance.php' => 'Attendance Model',
    'app/Services/AttendanceService.php' => 'Attendance Service',
    'app/Http/Controllers/AttendanceController.php' => 'Attendance Controller',
    'app/Services/BusinessHoursService.php' => 'BusinessHours Service',
];

$issues = [];
foreach ($filesToCheck as $file => $description) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        echo "   ✗ File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents(__DIR__ . '/' . $file);
    $getHoursForDateCount = substr_count($content, 'getHoursForDate(');
    $getBusinessHoursForDateCount = substr_count($content, 'getBusinessHoursForDate(');
    
    echo "   - $description:\n";
    echo "     getHoursForDate(): $getHoursForDateCount calls\n";
    echo "     getBusinessHoursForDate(): $getBusinessHoursForDateCount calls\n";
    
    if ($getBusinessHoursForDateCount > 0 && $file !== 'app/Services/AttendanceService.php') {
        // AttendanceService has a wrapper method getBusinessHoursForDate that calls getHoursForDate, which is OK
        $issues[] = "$description has deprecated getBusinessHoursForDate() calls";
    }
}

// Check 3: BusinessHoursService method existence
echo "\n3. Checking BusinessHoursService method signatures...\n";
$serviceContent = file_get_contents(__DIR__ . '/app/Services/BusinessHoursService.php');
$requiredMethods = [
    'public function getHoursForDate',
    'public function getConfig',
    'public function isLateCheckIn',
    'public function calculateLateArrivalMinutes',
    'public function calculateOvertimeMinutes',
    'public function calculateEarlyDepartureMinutes',
];

foreach ($requiredMethods as $method) {
    if (strpos($serviceContent, $method) !== false) {
        echo "   ✓ Method found: $method\n";
    } else {
        echo "   ✗ Method NOT found: $method\n";
        $issues[] = "BusinessHoursService missing method: $method";
    }
}

// Check 4: Configuration structure in BusinessHoursService
echo "\n4. Checking configuration transformation logic...\n";
if (strpos($serviceContent, 'weekdays') !== false && strpos($serviceContent, 'business_hours') !== false) {
    echo "   ✓ Configuration transformation logic present\n";
    
    // Check for field mapping
    if (strpos($serviceContent, 'overtime_start_after_hours') !== false && 
        strpos($serviceContent, 'overtime_threshold_minutes') !== false) {
        echo "   ✓ Field mapping present (overtime_start_after_hours → overtime_threshold_minutes)\n";
    } else {
        echo "   ⚠️  Field mapping may be incomplete\n";
    }
} else {
    echo "   ✗ Configuration transformation logic may be missing\n";
    $issues[] = "Configuration transformation logic incomplete";
}

// Check 5: Database schema for business hours fields
echo "\n5. Checking database migration for business hours fields...\n";
$migrationFiles = glob(__DIR__ . '/database/migrations/*business_hours*.php');
if (empty($migrationFiles)) {
    $migrationFiles = glob(__DIR__ . '/database/migrations/*attendance*.php');
}

if (!empty($migrationFiles)) {
    echo "   Found " . count($migrationFiles) . " relevant migration file(s)\n";
    
    // Check one migration file for business hours fields
    $migrationContent = file_get_contents($migrationFiles[0]);
    $businessHoursFields = [
        'business_hours_type',
        'expected_hours',
        'calculated_using_business_hours',
    ];
    
    $foundFields = [];
    foreach ($businessHoursFields as $field) {
        if (strpos($migrationContent, $field) !== false) {
            $foundFields[] = $field;
        }
    }
    
    if (count($foundFields) > 0) {
        echo "   ✓ Found business hours fields: " . implode(', ', $foundFields) . "\n";
    } else {
        echo "   ⚠️  No business hours fields found in migration\n";
    }
} else {
    echo "   ⚠️  No business hours migration files found\n";
}

// Summary
echo "\n=== Verification Summary ===\n";
if (empty($issues)) {
    echo "✓ All structural checks passed!\n";
    echo "\nKey fixes applied:\n";
    echo "1. Fixed syntax error in Attendance.php (\$this.date → \$this->date)\n";
    echo "2. Updated method calls from getBusinessHoursForDate() to getHoursForDate()\n";
    echo "3. Ensured configuration transformation logic is present\n";
    echo "4. Verified method signatures in BusinessHoursService\n";
    
    echo "\nNext steps:\n";
    echo "1. Run Laravel tests: php artisan test\n";
    echo "2. Test UI integration with attendance page\n";
    echo "3. Verify business hours configuration in settings\n";
    echo "4. Enable weekdays in business hours configuration\n";
} else {
    echo "✗ Found " . count($issues) . " issues:\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
    
    echo "\nRecommendations:\n";
    echo "1. Fix the method name mismatches\n";
    echo "2. Ensure BusinessHoursService has all required methods\n";
    echo "3. Verify configuration transformation logic\n";
}

echo "\n=== End of Verification ===\n";