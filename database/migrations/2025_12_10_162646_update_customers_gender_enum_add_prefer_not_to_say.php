<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip for SQLite as it doesn't support MODIFY COLUMN ENUM
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }
        // Modify the gender enum to include 'prefer_not_to_say'
        DB::statement("ALTER TABLE customers MODIFY COLUMN gender ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }
        // Revert back to original enum values
        DB::statement("ALTER TABLE customers MODIFY COLUMN gender ENUM('male', 'female', 'other') NULL");
    }
};
