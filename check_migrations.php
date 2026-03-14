<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Database Migration Verification ===\n\n";

// Check settings table for business hours configuration
echo "1. Checking business hours settings in settings table:\n";
$settings = DB::table('settings')->where('key', 'like', 'attendance.business_hours.%')->get();

if ($settings->count() > 0) {
    echo "   Found {$settings->count()} business hours settings:\n";
    foreach ($settings as $setting) {
        $value = strlen($setting->value) > 50 ? substr($setting->value, 0, 50) . '...' : $setting->value;
        echo "   - {$setting->key}: {$value}\n";
    }
} else {
    echo "   ❌ No business hours settings found!\n";
}

// Check attendances table schema
echo "\n2. Checking attendances table for business hours columns:\n";
$columns = DB::select('DESCRIBE attendances');
$businessHoursColumns = ['business_hours_type', 'expected_hours', 'calculated_using_business_hours'];
$foundColumns = [];

foreach ($columns as $column) {
    if (in_array($column->Field, $businessHoursColumns)) {
        $foundColumns[] = $column->Field;
        echo "   - Found column: {$column->Field} ({$column->Type})\n";
    }
}

if (count($foundColumns) === count($businessHoursColumns)) {
    echo "   ✅ All business hours columns present\n";
} else {
    $missing = array_diff($businessHoursColumns, $foundColumns);
    echo "   ❌ Missing columns: " . implode(', ', $missing) . "\n";
}

// Check if shifts tables were dropped
echo "\n3. Checking if shift tables were dropped:\n";
$tables = ['shifts', 'shift_assignments'];
foreach ($tables as $table) {
    try {
        $exists = DB::select("SHOW TABLES LIKE '{$table}'");
        if (count($exists) > 0) {
            echo "   ❌ Table '{$table}' still exists!\n";
        } else {
            echo "   ✅ Table '{$table}' was successfully dropped\n";
        }
    } catch (Exception $e) {
        echo "   ⚠️ Error checking table '{$table}': " . $e->getMessage() . "\n";
    }
}

// Check existing attendance records
echo "\n4. Checking existing attendance records:\n";
$totalAttendances = DB::table('attendances')->count();
$businessHoursAttendances = DB::table('attendances')->where('calculated_using_business_hours', true)->count();
echo "   - Total attendance records: {$totalAttendances}\n";
echo "   - Records using business hours: {$businessHoursAttendances}\n";

echo "\n=== Verification Complete ===\n";