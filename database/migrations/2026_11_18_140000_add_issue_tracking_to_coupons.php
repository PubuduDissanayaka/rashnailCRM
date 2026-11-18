<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Coupon codes are printed onto physical cards and handed to customers. Until a card is
 * handed over it is only "generated": it must not be redeemable, and its expiry should be
 * counted from the day it was given out — not from the day it was printed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dateTime('issued_at')->nullable()->after('active');
            $table->foreignId('issued_by')->nullable()->after('issued_at')->constrained('users')->nullOnDelete();
            // Validity window in days, counted from the issue date. Null = use end_date as-is.
            $table->unsignedInteger('valid_days')->nullable()->after('issued_by');

            $table->index('issued_at');
        });

        // Existing codes are already printed and in circulation, so they are treated as
        // issued on the day they were created. Nothing changes for them, and an owner can
        // flip any of them back to "not issued" from the list.
        DB::table('coupons')->whereNull('issued_at')->update([
            'issued_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropForeign(['issued_by']);
            $table->dropIndex(['issued_at']);
            $table->dropColumn(['issued_at', 'issued_by', 'valid_days']);
        });
    }
};
