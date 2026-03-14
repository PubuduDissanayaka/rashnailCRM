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
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('coupon_discount_amount', 10, 2)->default(0)->after('discount_amount');
            $table->json('applied_coupon_ids')->nullable()->after('coupon_discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('coupon_discount_amount');
            $table->dropColumn('applied_coupon_ids');
        });
    }
};
