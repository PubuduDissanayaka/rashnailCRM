<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Setting;

echo "Updating Business Hours Settings...\n";

$hours = [
    'monday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
    'tuesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
    'wednesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
    'thursday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
    'friday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
    'saturday' => ['open' => '10:00', 'close' => '16:00', 'closed' => false],
    'sunday' => ['open' => null, 'close' => null, 'closed' => true],
];

Setting::set('business.hours', $hours, 'json');
echo "Settings Updated Successfully to JSON type.\n";

// Verify
$newHours = Setting::get('business.hours');
print_r($newHours);
