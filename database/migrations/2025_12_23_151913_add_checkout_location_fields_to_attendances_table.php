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
            // Add checkout location tracking fields
            $table->decimal('latitude_out', 10, 8)->nullable()->after('longitude');
            $table->decimal('longitude_out', 11, 8)->nullable()->after('latitude_out');
            $table->string('ip_address_out', 45)->nullable()->after('ip_address');
            $table->string('device_info_out')->nullable()->after('device_fingerprint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'latitude_out',
                'longitude_out',
                'ip_address_out',
                'device_info_out',
            ]);
        });
    }
};
