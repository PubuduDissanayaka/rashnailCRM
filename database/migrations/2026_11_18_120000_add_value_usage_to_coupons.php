<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Value-based ("wallet") coupons: a fixed-amount code worth, say, Rs 10,000 that can
 * be spent across several sales until the balance runs out, instead of being consumed
 * in one go.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // When true, a fixed coupon is spent like a prepaid balance.
            $table->boolean('allow_partial_use')->default(false)->after('discount_value');
            // Total the code may ever give; defaults to discount_value when enabled.
            $table->decimal('value_limit', 10, 2)->nullable()->after('allow_partial_use');
            // Running total already given. Source of truth for the remaining balance.
            $table->decimal('consumed_value', 10, 2)->default(0)->after('value_limit');
        });

        // Existing coupons keep their count semantics; give the new flag a sane
        // starting value for fixed coupons so an owner can switch one on later.
        DB::table('coupons')->where('type', 'fixed')->update([
            'value_limit' => DB::raw('discount_value'),
        ]);

        // Rebuild consumed_value from what was actually redeemed, so any coupon that
        // is switched to value mode later starts from the truth.
        DB::statement('
            UPDATE coupons c
            SET consumed_value = COALESCE((
                SELECT SUM(r.discount_amount) FROM coupon_redemptions r WHERE r.coupon_id = c.id
            ), 0)
        ');
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['allow_partial_use', 'value_limit', 'consumed_value']);
        });
    }
};
