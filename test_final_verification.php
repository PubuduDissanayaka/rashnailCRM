<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\BusinessHoursService;
use Carbon\Carbon;

echo "=== Business Hours Attendance System Final Verification ===\n\n";

// Test 1: Configuration loading
echo "1. Testing BusinessHoursService configuration loading...\n";
$service = new BusinessHoursService();

try {
    $config = $service->getConfig();
    echo "   ✓ Configuration loaded successfully\n";
    echo "   - Config structure: " . json_encode(array_keys($config)) . "\n";
    
    // Check if configuration has the expected structure
    if (isset($config['weekdays'])) {
        echo "   - Using UI configuration format (weekdays array)\n";
    } elseif (isset($config['business_hours'])) {
        echo "   - Using migration configuration format (business_hours array)\n";
    } else {
        echo "   - Using default configuration\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Configuration loading failed: " . $e->getMessage() . "\n";
}

// Test 2: Method name verification
echo "\n2. Testing method name consistency...\n";
$methods = get_class_methods($service);
$requiredMethods = ['getHoursForDate', 'getConfig', 'isLateCheckIn', 'calculateLateArrivalMinutes', 'calculateOvertimeMinutes', 'calculateEarlyDepartureMinutes'];

$allMethodsExist = true;
foreach ($requiredMethods as $method) {
    if (in_array($method, $methods)) {
        echo "   ✓ Method '$method' exists\n";
    } else {
        echo "   ✗ Method '$method' NOT FOUND\n";
        $allMethodsExist = false;
    }
}

if ($allMethodsExist) {
    echo "   ✓ All required methods exist\n";
} else {
    echo "   ✗ Some methods are missing\n";
}

// Test 3: Field transformation
echo "\n3. Testing field name transformation...\n";
$testDate = Carbon::today();
try {
    $hours = $service->getHoursForDate($testDate);
    if ($hours) {
        echo "   ✓ getHoursForDate() returned data for " . $testDate->format('Y-m-d') . "\n";
        
        // Check for transformed fields
        if (isset($hours['overtime_threshold_minutes'])) {
            echo "   - Field 'overtime_threshold_minutes' exists (transformed from overtime_start_after_hours)\n";
        }
        
        if (isset($hours['break_duration_minutes'])) {
            echo "   - Field 'break_duration_minutes' exists with value: " . $hours['break_duration_minutes'] . "\n";
        }
        
        // Check required fields
        $requiredFields = ['start_time', 'end_time', 'is_enabled'];
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($hours[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (empty($missingFields)) {
            echo "   ✓ All required fields present\n";
        } else {
            echo "   ✗ Missing fields: " . implode(', ', $missingFields) . "\n";
        }
    } else {
        echo "   ✗ No business hours configured for " . $testDate->format('Y-m-d') . "\n";
    }
} catch (\Exception $e) {
    echo "   ✗ getHoursForDate() failed: " . $e->getMessage() . "\n";
}

// Test 4: Check if all weekdays are enabled
echo "\n4. Testing weekday configuration...\n";
$weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
$enabledCount = 0;
$disabledCount = 0;

foreach ($weekdays as $day) {
    $testDate = Carbon::parse('next ' . $day);
    $hours = $service->getHoursForDate($testDate);
    if ($hours && isset($hours['is_enabled']) && $hours['is_enabled']) {
        $enabledCount++;
        echo "   - $day: Enabled\n";
    } else {
        $disabledCount++;
        echo "   - $day: Disabled\n";
    }
}

echo "   Summary: $enabledCount days enabled, $disabledCount days disabled\n";
if ($enabledCount === 0) {
    echo "   ⚠️  WARNING: No weekdays are enabled! Business hours system may not work correctly.\n";
}

// Test 5: Check for method name mismatch in Attendance model
echo "\n5. Checking Attendance model for method calls...\n";
$attendanceFile = file_get_contents(__DIR__ . '/app/Models/Attendance.php');
$methodCalls = [
    'getHoursForDate' => substr_count($attendanceFile, 'getHoursForDate('),
    'getBusinessHoursForDate' => substr_count($attendanceFile, 'getBusinessHoursForDate('),
];

echo "   - Calls to getHoursForDate(): " . $methodCalls['getHoursForDate'] . "\n";
echo "   - Calls to getBusinessHoursForDate(): " . $methodCalls['getBusinessHoursForDate'] . "\n";

if ($methodCalls['getBusinessHoursForDate'] > 0) {
    echo "   ✗ Found deprecated method calls in Attendance model\n";
} else {
    echo "   ✓ No deprecated method calls found\n";
}

// Test 6: Check AttendanceController
echo "\n6. Checking AttendanceController for method calls...\n";
$controllerFile = file_get_contents(__DIR__ . '/app/Http/Controllers/AttendanceController.php');
$controllerCalls = [
    'getHoursForDate' => substr_count($controllerFile, 'getHoursForDate('),
    'getBusinessHoursForDate' => substr_count($controllerFile, 'getBusinessHoursForDate('),
];

echo "   - Calls to getHoursForDate(): " . $controllerCalls['getHoursForDate'] . "\n";
echo "   - Calls to getBusinessHoursForDate(): " . $controllerCalls['getBusinessHoursForDate'] . "\n";

if ($controllerCalls['getBusinessHoursForDate'] > 0) {
    echo "   ✗ Found deprecated method calls in AttendanceController\n";
} else {
    echo "   ✓ No deprecated method calls found\n";
}

// Test 7: Check AttendanceService
echo "\n7. Checking AttendanceService for method calls...\n";
$serviceFile = file_get_contents(__DIR__ . '/app/Services/AttendanceService.php');
$serviceCalls = [
    'getHoursForDate' => substr_count($serviceFile, 'getHoursForDate('),
    'getBusinessHoursForDate' => substr_count($serviceFile, 'getBusinessHoursForDate('),
];

echo "   - Calls to getHoursForDate(): " . $serviceCalls['getHoursForDate'] . "\n";
echo "   - Calls to getBusinessHoursForDate(): " . $serviceCalls['getBusinessHoursForDate'] . "\n";

if ($serviceCalls['getBusinessHoursForDate'] > 0) {
    echo "   ✗ Found deprecated method calls in AttendanceService\n";
} else {
    echo "   ✓ No deprecated method calls found\n";
}

echo "\n=== Verification Summary ===\n";
$issues = [];

if (!$allMethodsExist) {
    $issues[] = "Missing required methods in BusinessHoursService";
}

if ($enabledCount === 0) {
    $issues[] = "No weekdays enabled in business hours configuration";
}

if ($methodCalls['getBusinessHoursForDate'] > 0) {
    $issues[] = "Attendance model still calls deprecated getBusinessHoursForDate()";
}

if ($controllerCalls['getBusinessHoursForDate'] > 0) {
    $issues[] = "AttendanceController still calls deprecated getBusinessHoursForDate()";
}

if ($serviceCalls['getBusinessHoursForDate'] > 0) {
    $issues[] = "AttendanceService still calls deprecated getBusinessHoursForDate()";
}

if (empty($issues)) {
    echo "✓ All tests passed! Business hours system is ready.\n";
    echo "Recommendations:\n";
    echo "1. Enable more weekdays in business hours configuration\n";
    echo "2. Run comprehensive integration tests with actual attendance data\n";
    echo "3. Test UI integration with the attendance page\n";
} else {
    echo "✗ Found " . count($issues) . " issues:\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
    echo "\nRecommendations:\n";
    echo "1. Fix the method name mismatches\n";
    echo "2. Update business hours configuration to enable weekdays\n";
    echo "3. Run tests after fixes\n";
}

echo "\n=== End of Verification ===\n";