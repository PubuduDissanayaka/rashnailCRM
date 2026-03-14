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
            // Location tracking
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null');
            
            // Device and network information
            $table->string('ip_address', 45)->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->string('user_agent')->nullable();
            
            // Geolocation coordinates
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Verification methods
            $table->string('verification_photo_url')->nullable();
            $table->enum('check_in_method', ['web', 'mobile', 'biometric', 'card', 'manual'])->default('web');
            $table->enum('check_out_method', ['web', 'mobile', 'biometric', 'card', 'manual'])->default('web');
            
            // Time calculations
            $table->integer('overtime_minutes')->default(0);
            $table->integer('early_departure_minutes')->default(0);
            $table->integer('late_arrival_minutes')->default(0);
            $table->integer('total_break_minutes')->default(0);
            
            // Approval workflow
            $table->boolean('is_approved')->default(true);
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            // Additional status fields
            $table->enum('attendance_type', ['regular', 'overtime', 'holiday', 'weekend'])->default('regular');
            $table->decimal('overtime_hours', 5, 2)->default(0);
            
            // Indexes
            $table->index('location_id');
            $table->index('ip_address');
            $table->index('is_approved');
            $table->index('approved_by');
            $table->index('attendance_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['location_id']);
            $table->dropForeign(['approved_by']);
            
            // Drop columns
            $table->dropColumn([
                'location_id',
                'ip_address',
                'device_fingerprint',
                'user_agent',
                'latitude',
                'longitude',
                'verification_photo_url',
                'check_in_method',
                'check_out_method',
                'overtime_minutes',
                'early_departure_minutes',
                'late_arrival_minutes',
                'total_break_minutes',
                'is_approved',
                'approved_by',
                'approved_at',
                'attendance_type',
                'overtime_hours'
            ]);
        });
    }
};