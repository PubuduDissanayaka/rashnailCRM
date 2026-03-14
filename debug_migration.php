<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Attendance;
use Carbon\Carbon;

echo "Checking attendance records:\n";
$attendances = Attendance::all();
foreach($attendances as $a) {
    echo "ID: {$a->id}, Date: {$a->date}, Hours: {$a->hours_worked}, Status: {$a->status}\n";
}

echo "\nTesting time parsing:\n";
$open = Carbon::createFromFormat('H:i', '09:00');
$close = Carbon::createFromFormat('H:i', '17:00');
echo "Open: " . $open->format('Y-m-d H:i:s') . "\n";
echo "Close: " . $close->format('Y-m-d H:i:s') . "\n";
echo "Diff in minutes: " . $close->diffInMinutes($open) . "\n";
echo "Diff in hours: " . ($close->diffInMinutes($open) / 60) . "\n";

echo "\nTesting with setDate fix:\n";
$open2 = Carbon::createFromFormat('H:i', '09:00');
$close2 = Carbon::createFromFormat('H:i', '17:00');
$close2->setDate($open2->year, $open2->month, $open2->day);
echo "Open2: " . $open2->format('Y-m-d H:i:s') . "\n";
echo "Close2: " . $close2->format('Y-m-d H:i:s') . "\n";
echo "Diff in minutes: " . $close2->diffInMinutes($open2) . "\n";
echo "Diff in hours: " . ($close2->diffInMinutes($open2) / 60) . "\n";

echo "\nTesting better approach:\n";
$open3 = Carbon::createFromTime(9, 0, 0);
$close3 = Carbon::createFromTime(17, 0, 0);
echo "Open3: " . $open3->format('Y-m-d H:i:s') . " (timestamp: " . $open3->timestamp . ")\n";
echo "Close3: " . $close3->format('Y-m-d H:i:s') . " (timestamp: " . $close3->timestamp . ")\n";
echo "Diff in minutes: " . $close3->diffInMinutes($open3) . "\n";
echo "Diff in hours: " . ($close3->diffInMinutes($open3) / 60) . "\n";

echo "\nTesting absolute diff:\n";
echo "Absolute diff in minutes: " . $close3->diffInMinutes($open3, false) . "\n";
echo "Is close after open? " . ($close3->greaterThan($open3) ? 'yes' : 'no') . "\n";
echo "Is close before open? " . ($close3->lessThan($open3) ? 'yes' : 'no') . "\n";

echo "\nTesting with specific date:\n";
$baseDate = Carbon::create(2025, 12, 24);
$open4 = $baseDate->copy()->setTime(9, 0, 0);
$close4 = $baseDate->copy()->setTime(17, 0, 0);
echo "Open4: " . $open4->format('Y-m-d H:i:s') . "\n";
echo "Close4: " . $close4->format('Y-m-d H:i:s') . "\n";
echo "Diff in minutes: " . $close4->diffInMinutes($open4) . "\n";

echo "\nTesting with today's date:\n";
$date = Carbon::today();
$dayOfWeek = strtolower($date->format('l'));
echo "Today: {$date->format('Y-m-d')}, Day: {$dayOfWeek}\n";

$service = app('App\Services\BusinessHoursService');
$config = $service->getConfig();
echo "Business hours config for {$dayOfWeek}:\n";
print_r($config['business_hours'][$dayOfWeek] ?? 'Not found');