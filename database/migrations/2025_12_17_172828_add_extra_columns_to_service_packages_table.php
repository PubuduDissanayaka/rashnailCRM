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
        Schema::table('service_packages', function (Blueprint $table) {
            // Add columns that are needed by the seeder
            if (!Schema::hasColumn('service_packages', 'base_price')) {
                $table->decimal('base_price', 10, 2)->default(0.00);
            }

            if (!Schema::hasColumn('service_packages', 'discounted_price')) {
                $table->decimal('discounted_price', 10, 2)->default(0.00);
            }

            if (!Schema::hasColumn('service_packages', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->nullable();
            }

            if (!Schema::hasColumn('service_packages', 'total_duration')) {
                $table->integer('total_duration')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn([
                'base_price',
                'discounted_price',
                'discount_percentage',
                'total_duration'
            ]);
        });
    }
};
