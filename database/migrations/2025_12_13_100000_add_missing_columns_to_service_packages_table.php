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
            // Add missing columns that are expected in the application
            
            // Add is_available_for_sale column if it doesn't exist
            if (!Schema::hasColumn('service_packages', 'is_available_for_sale')) {
                $table->boolean('is_available_for_sale')->default(true);
            }
            
            // Add duration column if it doesn't exist (different from total_duration)
            if (!Schema::hasColumn('service_packages', 'duration')) {
                $table->integer('duration')->default(0);
            }
            
            // Add included_services column if it doesn't exist
            if (!Schema::hasColumn('service_packages', 'included_services')) {
                $table->json('included_services')->nullable();
            }
            
            // Add session_count column if it doesn't exist
            if (!Schema::hasColumn('service_packages', 'session_count')) {
                $table->integer('session_count')->default(1);
            }
            
            // Add validity_days column if it doesn't exist
            if (!Schema::hasColumn('service_packages', 'validity_days')) {
                $table->integer('validity_days')->default(30);
            }
            
            // Add image column if it doesn't exist
            if (!Schema::hasColumn('service_packages', 'image')) {
                $table->string('image')->nullable();
            }
            
            // Add category_id column if it doesn't exist
            if (!Schema::hasColumn('service_packages', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable();
                $table->foreign('category_id')->references('id')->on('service_package_categories')->nullOnDelete();
            }
            
            // Add price column if it doesn't exist (different from existing base_price)
            if (!Schema::hasColumn('service_packages', 'price')) {
                $table->decimal('price', 10, 2)->default(0.00);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            // Drop foreign key and columns
            try {
                $table->dropForeign(['category_id']);
            } catch (\Exception $e) {
                // Foreign key may not exist, that's ok
            }

            $table->dropColumn([
                'is_available_for_sale',
                'duration',
                'included_services',
                'session_count',
                'validity_days',
                'image',
                'category_id',
                'price'
            ]);
        });
    }

    /**
     * Get foreign keys for a table
     */
    private function getForeignKeys($tableName)
    {
        $conn = Schema::getConnection();
        $schema = $conn->getDoctrineSchemaManager();
        $platform = $conn->getDoctrineConnection()->getDatabasePlatform();
        
        $sql = $platform->getListTableForeignKeysSQL($tableName);
        $results = $conn->select($sql);
        
        return array_map(function ($result) {
            return $result->name;
        }, $schema->listTableForeignKeys($tableName));
    }
};