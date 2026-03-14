<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BusinessHoursService;

echo "=== BusinessHoursService Configuration Transformation Test ===\n\n";

$service = new BusinessHoursService();

// Use reflection to access private method
$reflection = new ReflectionClass($service);
$transformMethod = $reflection->getMethod('transformConfigForCompatibility');
$transformMethod->setAccessible(true);

// Test Case 1: UI configuration (weekdays array structure)
echo "1. Testing UI configuration (weekdays structure):\n";
$uiConfig = [
    'weekdays' => [
        'monday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
        'tuesday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
    ],
    'grace_period_minutes' => 15,
    'overtime_start_after_hours' => 1.5, // 1.5 hours = 90 minutes
    'minimum_shift_hours' => 4,
    'maximum_shift_hours' => 10,
    'half_day_threshold_hours' => 3.5,
];

$transformed = $transformMethod->invoke($service, $uiConfig);

echo "   - Original has 'weekdays': " . (isset($uiConfig['weekdays']) ? 'Yes' : 'No') . "\n";
echo "   - Transformed has 'business_hours': " . (isset($transformed['business_hours']) ? 'Yes' : 'No') . "\n";
echo "   - Transformed has 'weekdays': " . (isset($transformed['weekdays']) ? 'Yes' : 'No') . "\n";
echo "   - overtime_start_after_hours mapped to overtime_threshold_minutes: " . 
     (isset($transformed['overtime_threshold_minutes']) ? $transformed['overtime_threshold_minutes'] . ' minutes' : 'No') . "\n";
echo "   - Expected overtime_threshold_minutes: 90 minutes (1.5 hours * 60)\n";
echo "   - break_duration_minutes added: " . (isset($transformed['break_duration_minutes']) ? $transformed['break_duration_minutes'] . ' minutes' : 'No') . "\n";

// Test Case 2: Migration configuration (business_hours array structure)
echo "\n2. Testing migration configuration (business_hours structure):\n";
$migrationConfig = [
    'business_hours' => [
        'monday' => ['open' => '08:00', 'close' => '16:00', 'enabled' => true],
        'friday' => ['open' => '10:00', 'close' => '18:00', 'enabled' => true],
    ],
    'grace_period_minutes' => 20,
    'overtime_threshold_minutes' => 120, // Already in minutes
    'break_duration_minutes' => 45,
];

$transformed2 = $transformMethod->invoke($service, $migrationConfig);

echo "   - Original has 'business_hours': " . (isset($migrationConfig['business_hours']) ? 'Yes' : 'No') . "\n";
echo "   - Transformed has 'business_hours': " . (isset($transformed2['business_hours']) ? 'Yes' : 'No') . "\n";
echo "   - overtime_threshold_minutes preserved: " . 
     (isset($transformed2['overtime_threshold_minutes']) ? $transformed2['overtime_threshold_minutes'] . ' minutes' : 'No') . "\n";
echo "   - break_duration_minutes preserved: " . 
     (isset($transformed2['break_duration_minutes']) ? $transformed2['break_duration_minutes'] . ' minutes' : 'No') . "\n";

// Test Case 3: No configuration (should return defaults)
echo "\n3. Testing empty configuration (should add defaults):\n";
$emptyConfig = [];
$transformed3 = $transformMethod->invoke($service, $emptyConfig);

echo "   - Added business_hours: " . (isset($transformed3['business_hours']) ? 'Yes' : 'No') . "\n";
echo "   - Added grace_period_minutes: " . (isset($transformed3['grace_period_minutes']) ? $transformed3['grace_period_minutes'] . ' minutes' : 'No') . "\n";
echo "   - Added overtime_threshold_minutes: " . (isset($transformed3['overtime_threshold_minutes']) ? $transformed3['overtime_threshold_minutes'] . ' minutes' : 'No') . "\n";
echo "   - Added break_duration_minutes: " . (isset($transformed3['break_duration_minutes']) ? $transformed3['break_duration_minutes'] . ' minutes' : 'No') . "\n";
echo "   - Added minimum_shift_hours: " . (isset($transformed3['minimum_shift_hours']) ? $transformed3['minimum_shift_hours'] . ' hours' : 'No') . "\n";
echo "   - Added maximum_shift_hours: " . (isset($transformed3['maximum_shift_hours']) ? $transformed3['maximum_shift_hours'] . ' hours' : 'No') . "\n";
echo "   - Added half_day_threshold_hours: " . (isset($transformed3['half_day_threshold_hours']) ? $transformed3['half_day_threshold_hours'] . ' hours' : 'No') . "\n";

// Test Case 4: Partial configuration with missing fields
echo "\n4. Testing partial configuration (missing some fields):\n";
$partialConfig = [
    'business_hours' => [
        'monday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
    ],
    'grace_period_minutes' => 25,
    // Missing overtime_threshold_minutes, break_duration_minutes, etc.
];

$transformed4 = $transformMethod->invoke($service, $partialConfig);

echo "   - grace_period_minutes preserved: " . (isset($transformed4['grace_period_minutes']) ? $transformed4['grace_period_minutes'] . ' minutes' : 'No') . "\n";
echo "   - overtime_threshold_minutes added (default): " . (isset($transformed4['overtime_threshold_minutes']) ? $transformed4['overtime_threshold_minutes'] . ' minutes' : 'No') . "\n";
echo "   - break_duration_minutes added (default): " . (isset($transformed4['break_duration_minutes']) ? $transformed4['break_duration_minutes'] . ' minutes' : 'No') . "\n";

// Test Case 5: Verify the actual configuration from database
echo "\n5. Testing actual database configuration:\n";
$actualConfig = $service->getConfig();
echo "   - Configuration keys present:\n";
foreach (array_keys($actualConfig) as $key) {
    if ($key === 'business_hours') {
        $days = count($actualConfig[$key]);
        echo "     * {$key} (contains {$days} days)\n";
    } else {
        $value = $actualConfig[$key];
        if (is_numeric($value)) {
            echo "     * {$key}: {$value}\n";
        } else {
            echo "     * {$key}: " . gettype($value) . "\n";
        }
    }
}

// Check specific transformations
echo "\n6. Verification of transformations in actual config:\n";
$hasWeekdays = isset($actualConfig['weekdays']);
$hasBusinessHours = isset($actualConfig['business_hours']);
$hasOvertimeStart = isset($actualConfig['overtime_start_after_hours']);
$hasOvertimeThreshold = isset($actualConfig['overtime_threshold_minutes']);
$hasBreakDuration = isset($actualConfig['break_duration_minutes']);

echo "   - Has 'weekdays' key (should be false): " . ($hasWeekdays ? '❌ FAIL' : '✓ PASS') . "\n";
echo "   - Has 'business_hours' key (should be true): " . ($hasBusinessHours ? '✓ PASS' : '❌ FAIL') . "\n";
echo "   - Has 'overtime_start_after_hours' key (should be false): " . ($hasOvertimeStart ? '❌ FAIL' : '✓ PASS') . "\n";
echo "   - Has 'overtime_threshold_minutes' key (should be true): " . ($hasOvertimeThreshold ? '✓ PASS' : '❌ FAIL') . "\n";
echo "   - Has 'break_duration_minutes' key (should be true): " . ($hasBreakDuration ? '✓ PASS' : '❌ FAIL') . "\n";

if ($hasOvertimeThreshold) {
    // Check if conversion from 1.5 hours to 90 minutes happened
    $expectedMinutes = 90; // 1.5 hours * 60
    $actualMinutes = $actualConfig['overtime_threshold_minutes'];
    echo "   - Overtime threshold conversion (1.5 hours → {$expectedMinutes} minutes): {$actualMinutes} minutes " . 
         ($actualMinutes == $expectedMinutes ? '✓ PASS' : '❌ FAIL') . "\n";
}

echo "\n=== Transformation Test Complete ===\n";