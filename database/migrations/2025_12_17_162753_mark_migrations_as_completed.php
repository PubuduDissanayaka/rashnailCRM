<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert records for already-run migrations to mark them as completed
        $completedMigrations = [
            ['migration' => '0001_01_01_000000_create_users_table', 'batch' => 1],
            ['migration' => '0001_01_01_000001_create_cache_table', 'batch' => 1],
            ['migration' => '0001_01_01_000002_create_jobs_table', 'batch' => 1],
            ['migration' => '2025_01_01_000001_create_service_packages_table', 'batch' => 1],
            ['migration' => '2025_01_01_000002_create_service_package_categories_table', 'batch' => 1],
            ['migration' => '2025_01_01_000003_create_sales_table', 'batch' => 1],
            ['migration' => '2025_01_01_000004_create_sale_items_table', 'batch' => 1],
            ['migration' => '2025_01_01_000005_create_payments_table', 'batch' => 1],
            ['migration' => '2025_01_01_000006_create_invoices_table', 'batch' => 1],
            ['migration' => '2025_01_01_000007_create_refunds_table', 'batch' => 1],
            ['migration' => '2025_01_01_000008_create_service_package_sales_table', 'batch' => 1],
            ['migration' => '2025_01_01_000009_create_cash_drawer_sessions_table', 'batch' => 1],
            ['migration' => '2025_12_09_052929_add_role_to_users_table', 'batch' => 1],
            ['migration' => '2025_12_09_061744_create_customers_table', 'batch' => 1],
            ['migration' => '2025_12_09_061747_create_services_table', 'batch' => 1],
            ['migration' => '2025_12_09_061749_create_appointments_table', 'batch' => 1],
            ['migration' => '2025_12_09_061751_create_transactions_table', 'batch' => 1],
            ['migration' => '2025_12_09_064703_add_avatar_to_users_table', 'batch' => 1],
            ['migration' => '2025_12_09_065003_add_phone_to_users_table', 'batch' => 1],
            ['migration' => '2025_12_09_065543_create_permission_tables', 'batch' => 1],
            ['migration' => '2025_12_09_141350_add_slug_to_users_table', 'batch' => 1],
            ['migration' => '2025_12_09_150155_add_status_to_users_table', 'batch' => 1],
            ['migration' => '2025_12_10_064802_add_status_to_users_table', 'batch' => 1],
            ['migration' => '2025_12_10_142447_add_soft_deletes_and_notes_to_customers_table', 'batch' => 1],
            ['migration' => '2025_12_10_155221_add_status_to_customers_table', 'batch' => 1],
            ['migration' => '2025_12_10_162646_update_customers_gender_enum_add_prefer_not_to_say', 'batch' => 1],
            ['migration' => '2025_12_11_020417_add_soft_deletes_to_services_table', 'batch' => 1],
            ['migration' => '2025_12_11_023836_add_slug_to_services_table', 'batch' => 1],
        ];

        // Insert the migration records if they don't already exist
        foreach ($completedMigrations as $migrationData) {
            \DB::table('migrations')->insertOrIgnore($migrationData);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't remove the entries as this could cause issues
        // We'll just leave the records as-is
    }
};
