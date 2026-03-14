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
        // Add business hours configuration to settings table
        $businessHoursConfig = [
            'business_hours' => [
                'monday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
                'tuesday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
                'wednesday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
                'thursday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
                'friday' => ['open' => '09:00', 'close' => '17:00', 'enabled' => true],
                'saturday' => ['open' => null, 'close' => null, 'enabled' => false],
                'sunday' => ['open' => null, 'close' => null, 'enabled' => false],
            ],
            'grace_period_minutes' => 15,
            'overtime_threshold_minutes' => 0,
            'minimum_shift_hours' => 0,
            'maximum_shift_hours' => 12,
            'half_day_threshold_hours' => 4,
        ];

        // Insert business hours settings
        DB::table('settings')->insert([
            [
                'key' => 'attendance.business_hours.config',
                'value' => json_encode($businessHoursConfig),
                'type' => 'json',
                'group' => 'attendance',
                'description' => 'Business hours configuration for attendance tracking',
                'order' => 100,
                'encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'attendance.business_hours.grace_period',
                'value' => '15',
                'type' => 'integer',
                'group' => 'attendance',
                'description' => 'Grace period in minutes for late arrivals',
                'order' => 101,
                'encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'attendance.business_hours.overtime_threshold',
                'value' => '0',
                'type' => 'integer',
                'group' => 'attendance',
                'description' => 'Minutes after business close before overtime starts',
                'order' => 102,
                'encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'attendance.business_hours.minimum_hours',
                'value' => '0',
                'type' => 'integer',
                'group' => 'attendance',
                'description' => 'Minimum hours required to count as a full day',
                'order' => 103,
                'encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'attendance.business_hours.maximum_hours',
                'value' => '12',
                'type' => 'integer',
                'group' => 'attendance',
                'description' => 'Maximum hours allowed per day',
                'order' => 104,
                'encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'attendance.business_hours.half_day_threshold',
                'value' => '4',
                'type' => 'integer',
                'group' => 'attendance',
                'description' => 'Hours worked below this threshold counts as half day',
                'order' => 105,
                'encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove business hours settings
        DB::table('settings')->whereIn('key', [
            'attendance.business_hours.config',
            'attendance.business_hours.grace_period',
            'attendance.business_hours.overtime_threshold',
            'attendance.business_hours.minimum_hours',
            'attendance.business_hours.maximum_hours',
            'attendance.business_hours.half_day_threshold',
        ])->delete();
    }
};