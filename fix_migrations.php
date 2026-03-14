<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

$migrations = [
    '2026_03_08_114235_create_coupon_locations_table',
    '2026_03_08_114305_create_coupon_products_table',
    '2026_03_08_114336_create_coupon_categories_table',
    '2026_03_08_114406_create_coupon_redemptions_table',
    '2026_03_08_114439_create_sale_coupons_table',
    '2026_03_08_114512_add_coupon_fields_to_sales_table',
];

echo "Deleting migration records...\n";
DB::table('migrations')->whereIn('migration', $migrations)->delete();
echo "Running migrations...\n";

foreach ($migrations as $migration) {
    $path = 'database/migrations/' . $migration . '.php';
    if (file_exists($path)) {
        echo "Migrating $migration...\n";
        Artisan::call('migrate', ['--path' => $path]);
        echo Artisan::output();
    } else {
        echo "File $path not found.\n";
    }
}

echo "Done.\n";