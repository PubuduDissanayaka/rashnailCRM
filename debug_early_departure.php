<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BusinessHoursService;
use Carbon\Carbon;

echo "=== Debug: Early Departure Calculation ===\n\n";

$service = new BusinessHoursService();

// Test specific case
$checkOutTime = Carbon::parse('2025-12-22 16:30');
$hours = $service->getHoursForDate($checkOutTime);

echo "Check-out time: " . $checkOutTime->format('Y-m-d H:i:s') . "\n";
echo "Business close time: " . $hours['close']->format('Y-m-d H:i:s') . "\n";
echo "\n";

// Test diffInMinutes in both directions
$diff1 = $hours['close']->diffInMinutes($checkOutTime);
$diff2 = $checkOutTime->diffInMinutes($hours['close']);
echo "hours['close']->diffInMinutes(checkOutTime): {$diff1}\n";
echo "checkOutTime->diffInMinutes(hours['close']): {$diff2}\n";
echo "\n";

// Test isEarlyDeparture
$isEarly = $service->isEarlyDeparture($checkOutTime);
echo "isEarlyDeparture: " . ($isEarly ? 'true' : 'false') . "\n";

// Manually calculate what should happen
if ($isEarly) {
    $earlyMinutes = max(0, $hours['close']->diffInMinutes($checkOutTime));
    echo "Manual calculation (max(0, diff)): {$earlyMinutes}\n";
    
    // Let's check what diffInMinutes actually returns
    echo "\nDebugging diffInMinutes:\n";
    echo "Close time: " . $hours['close']->toDateTimeString() . "\n";
    echo "Check-out: " . $checkOutTime->toDateTimeString() . "\n";
    
    // Check if checkOutTime is less than close
    echo "checkOutTime < close? " . ($checkOutTime->lessThan($hours['close']) ? 'Yes' : 'No') . "\n";
    echo "checkOutTime > close? " . ($checkOutTime->greaterThan($hours['close']) ? 'Yes' : 'No') . "\n";
    
    // Try absolute difference
    $absoluteDiff = $hours['close']->diffInMinutes($checkOutTime, false);
    echo "diffInMinutes with false (absolute): {$absoluteDiff}\n";
    
    // The issue might be that diffInMinutes always returns positive
    // We need: close - checkOutTime (positive when checkOut is earlier)
    $correctDiff = $hours['close']->diffInMinutes($checkOutTime, false);
    echo "diffInMinutes with false: {$correctDiff}\n";
    if ($correctDiff < 0) {
        echo "Negative! That means checkOut is AFTER close\n";
    } else {
        echo "Positive! That means checkOut is BEFORE close\n";
    }
}

// Test another time
echo "\n=== Testing 17:30 (after close) ===\n";
$lateTime = Carbon::parse('2025-12-22 17:30');
$isEarly2 = $service->isEarlyDeparture($lateTime);
echo "Is 17:30 early departure? " . ($isEarly2 ? 'Yes' : 'No') . "\n";
if (!$isEarly2) {
    $hours2 = $service->getHoursForDate($lateTime);
    $diff = $hours2['close']->diffInMinutes($lateTime);
    echo "diffInMinutes(close, lateTime): {$diff}\n";
    $diffWithFalse = $hours2['close']->diffInMinutes($lateTime, false);
    echo "diffInMinutes with false: {$diffWithFalse}\n";
}