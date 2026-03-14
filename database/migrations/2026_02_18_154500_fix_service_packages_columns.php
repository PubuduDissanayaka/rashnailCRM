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
        // Add base_price
        if (!Schema::hasColumn('service_packages', 'base_price')) {
             Schema::table('service_packages', function (Blueprint $table) {
                $table->decimal('base_price', 10, 2)->default(0)->after('description');
             });
        }

        // Rename price to discounted_price or add it
        if (Schema::hasColumn('service_packages', 'price')) {
            // Renaming can be tricky if enum/json columns exist and doctrine/dbal is old.
            // Let's try to add discounted_price and copy data, then drop price.
            if (!Schema::hasColumn('service_packages', 'discounted_price')) {
                Schema::table('service_packages', function (Blueprint $table) {
                     $table->decimal('discounted_price', 10, 2)->default(0)->after('base_price');
                });
                DB::statement('UPDATE service_packages SET discounted_price = price');
                Schema::table('service_packages', function (Blueprint $table) {
                    $table->dropColumn('price');
                });
            }
        } elseif (!Schema::hasColumn('service_packages', 'discounted_price')) {
             Schema::table('service_packages', function (Blueprint $table) {
                $table->decimal('discounted_price', 10, 2)->default(0)->after('base_price');
             });
        }

        // Add discount_percentage
        if (!Schema::hasColumn('service_packages', 'discount_percentage')) {
             Schema::table('service_packages', function (Blueprint $table) {
                $table->decimal('discount_percentage', 5, 2)->nullable()->after('discounted_price');
             });
        }
        
        // Add total_duration
        if (!Schema::hasColumn('service_packages', 'total_duration')) {
             Schema::table('service_packages', function (Blueprint $table) {
                 $table->integer('total_duration')->default(0)->after('discount_percentage');
             });
        }
        
        // Drop old columns
        Schema::table('service_packages', function (Blueprint $table) {
            if (Schema::hasColumn('service_packages', 'duration')) {
                $table->dropColumn('duration');
            }
            if (Schema::hasColumn('service_packages', 'included_services')) {
                $table->dropColumn('included_services');
            }
            if (Schema::hasColumn('service_packages', 'session_count')) {
                $table->dropColumn('session_count');
            }
            if (Schema::hasColumn('service_packages', 'validity_days')) {
                $table->dropColumn('validity_days');
            }
             if (Schema::hasColumn('service_packages', 'is_available_for_sale')) {
                $table->dropColumn('is_available_for_sale');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback
    }
};
