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
        Schema::table('coupon_categories', function (Blueprint $table) {
            $table->string('categorizable_type')->nullable()->after('category_type');
            $table->unsignedBigInteger('categorizable_id')->nullable()->after('categorizable_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupon_categories', function (Blueprint $table) {
            $table->dropColumn(['categorizable_type', 'categorizable_id']);
        });
    }
};
