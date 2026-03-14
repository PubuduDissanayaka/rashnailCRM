<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $staff = User::whereHas('roles', fn($q) => $q->whereIn('name', ['administrator', 'staff']))->get();

        if ($staff->isEmpty()) {
            $this->command->warn('No staff found. Run AdminUserSeeder and StaffSeeder first.');
            return;
        }

        $statuses = ['present', 'present', 'present', 'present', 'late', 'late', 'absent', 'half_day'];

        // Seed last 60 days
        $startDate = now()->subDays(60)->startOfDay();
        $endDate = now()->subDay(); // up to yesterday

        foreach ($staff as $user) {
            $current = $startDate->copy();

            while ($current->lte($endDate)) {
                // No attendance on Sundays
                if ($current->dayOfWeek === Carbon::SUNDAY) {
                    $current->addDay();
                    continue;
                }

                // Skip weekends randomly (Saturday 20% closed)
                if ($current->dayOfWeek === Carbon::SATURDAY && rand(0, 4) === 0) {
                    $current->addDay();
                    continue;
                }

                $status = $statuses[array_rand($statuses)];
                $checkIn = null;
                $checkOut = null;
                $hoursWorked = null;

                if ($status !== 'absent') {
                    // Check-in time: 9:00am ±30min for present, 9:15-10:00 for late
                    if ($status === 'late') {
                        $checkInHour = 9;
                        $checkInMin = rand(15, 60);
                        if ($checkInMin >= 60) {
                            $checkInHour = 10;
                            $checkInMin -= 60;
                        }
                    } else {
                        $checkInHour = 9;
                        $checkInMin = rand(0, 15);
                    }

                    $checkIn = sprintf('%02d:%02d:00', $checkInHour, $checkInMin);

                    if ($status === 'half_day') {
                        $checkOutHour = 13;
                        $checkOutMin = rand(0, 30);
                        $hoursWorked = round($checkOutHour + $checkOutMin / 60 - $checkInHour - $checkInMin / 60, 2);
                    } else {
                        $checkOutHour = rand(17, 18);
                        $checkOutMin = rand(0, 59);
                        $checkOut = sprintf('%02d:%02d:00', $checkOutHour, $checkOutMin);
                        $hoursWorked = round($checkOutHour + $checkOutMin / 60 - $checkInHour - $checkInMin / 60, 2);
                    }

                    if ($status === 'half_day') {
                        $checkOut = sprintf('%02d:%02d:00', $checkOutHour, $checkOutMin);
                    }
                }

                // Avoid duplicates
                Attendance::firstOrCreate(
                    ['user_id' => $user->id, 'date' => $current->toDateString()],
                    [
                        'status' => $status,
                        'check_in' => $checkIn,
                        'check_out' => $checkOut,
                        'hours_worked' => $hoursWorked,
                        'is_manual_entry' => false,
                    ]
                );

                $current->addDay();
            }
        }

        $this->command->info('Attendance records seeded successfully.');
    }
}
