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
        Schema::table('attendances', function (Blueprint $table) {
            // Business hours tracking fields
            $table->enum('business_hours_type', ['regular', 'weekend', 'holiday', 'special'])
                ->default('regular')
                ->after('attendance_type')
                ->comment('Type of day based on business hours configuration');
            
            $table->decimal('expected_hours', 5, 2)
                ->nullable()
                ->after('hours_worked')
                ->comment('Expected hours based on business hours configuration');
            
            $table->boolean('calculated_using_business_hours')
                ->default(false)
                ->after('expected_hours')
                ->comment('Whether this record was calculated using business hours logic');
            
            // Indexes for performance
            $table->index('business_hours_type');
            $table->index('calculated_using_business_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'business_hours_type',
                'expected_hours',
                'calculated_using_business_hours',
            ]);
            
            // Note: Dropping indexes automatically when columns are dropped
        });
    }
};