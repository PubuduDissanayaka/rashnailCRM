<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$migrations = [
    '2025_12_25_000003_create_supply_stock_movements_table',
    '2025_12_25_000004_create_supply_usage_logs_table',
    '2025_12_25_000005_create_purchase_orders_table',
    '2025_12_25_000006_create_purchase_order_items_table',
    '2025_12_25_000007_create_supply_alerts_table',
    '2025_12_25_000008_create_service_supplies_table',
    '2026_01_11_000001_create_expense_categories_table',
    '2026_01_11_000002_create_expenses_table',
    '2026_01_11_000003_create_expense_attachments_table',
    '2026_01_11_000004_create_expense_budgets_table',
    '2026_01_11_000005_create_expense_comments_table',
    '2026_02_18_154500_fix_service_packages_columns',
    '2026_02_18_183210_make_reference_nullable_in_supply_stock_movements_table',
];

foreach ($migrations as $migration) {
    if (!DB::table('migrations')->where('migration', $migration)->exists()) {
        DB::table('migrations')->insert(['migration' => $migration, 'batch' => 1]);
        echo "Inserted $migration\n";
    } else {
        echo "$migration already exists\n";
    }
}

echo "Done.\n";