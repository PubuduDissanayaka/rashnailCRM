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
        // Add composite index for date-based queries with business hours filtering
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['date', 'user_id', 'calculated_using_business_hours'], 'idx_attendance_date_user_business');
            $table->index(['user_id', 'date'], 'idx_attendance_user_date');
        });

        // Add index for model_has_roles table for role-based filtering
        // Note: We can't modify Spatie's table directly in this migration,
        // but we'll check if index exists and create it if not
        $this->addModelHasRolesIndex();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendance_date_user_business');
            $table->dropIndex('idx_attendance_user_date');
        });

        $this->dropModelHasRolesIndex();
    }

    /**
     * Add index to model_has_roles table if it doesn't exist
     */
    private function addModelHasRolesIndex(): void
    {
        $connection = config('database.default');
        $prefix = config("database.connections.{$connection}.prefix");
        $tableName = $prefix . 'model_has_roles';
        
        $indexName = 'idx_model_role';
        
        // Check if index already exists
        $indexExists = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = '{$tableName}' 
            AND index_name = '{$indexName}'
        ");
        
        if ($indexExists[0]->count == 0) {
            DB::statement("CREATE INDEX {$indexName} ON {$tableName} (role_id, model_id)");
        }
    }

    /**
     * Drop index from model_has_roles table if it exists
     */
    private function dropModelHasRolesIndex(): void
    {
        $connection = config('database.default');
        $prefix = config("database.connections.{$connection}.prefix");
        $tableName = $prefix . 'model_has_roles';
        
        $indexName = 'idx_model_role';
        
        // Check if index exists
        $indexExists = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = '{$tableName}' 
            AND index_name = '{$indexName}'
        ");
        
        if ($indexExists[0]->count > 0) {
            DB::statement("DROP INDEX {$indexName} ON {$tableName}");
        }
    }
};