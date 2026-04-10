<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a nullable joined_date column to the customers table. Existing
     * rows are backfilled from created_at so historical data stays intact.
     * Does NOT drop or alter any existing columns or data.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'joined_date')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->date('joined_date')->nullable()->after('date_of_birth');
            });

            // Backfill existing rows from created_at (safe; no data loss)
            DB::statement('UPDATE customers SET joined_date = DATE(created_at) WHERE joined_date IS NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('customers', 'joined_date')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('joined_date');
            });
        }
    }
};
