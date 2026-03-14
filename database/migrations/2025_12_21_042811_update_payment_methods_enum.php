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
        // Update the payment_method enum to include all possible payment methods
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'card', 'mobile', 'check', 'bank_transfer', 'store_credit', 'online', 'wire_transfer', 'credit_card', 'debit_card', 'paypal', 'stripe', 'payhere')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }
        // Revert to the original enum values
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'card', 'mobile', 'check', 'bank_transfer', 'store_credit')");
    }
};
