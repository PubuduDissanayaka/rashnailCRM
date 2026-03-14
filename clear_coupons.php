<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
DB::table('coupons')->delete();
DB::table('coupon_redemptions')->delete();
DB::table('sale_coupons')->delete();
DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
echo "Cleared coupon tables.\n";