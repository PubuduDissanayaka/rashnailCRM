<?php

namespace App\Listeners;

use App\Events\AttendanceCheckedOut;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Setting;

class SendAttendanceCheckOutNotification
{
    /**
     * The notification service.
     *
     * @var \App\Services\NotificationService
     */
    protected $notificationService;

    /**
     * Create the event listener.
     *
     * @param \App\Services\NotificationService $notificationService
     * @return void
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     *
     * @param \App\Events\AttendanceCheckedOut $event
     * @return void
     */
    public function handle(AttendanceCheckedOut $event): void
    {
        $attendance = $event->attendance;
        $user = $event->user;

        $variables = [
            'user_name' => $user->name,
            'check_in_time' => $attendance->check_in->format('Y-m-d H:i:s'),
            'check_in_time_formatted' => $attendance->check_in->format('h:i A'),
            'check_out_time' => $attendance->check_out->format('Y-m-d H:i:s'),
            'check_out_time_formatted' => $attendance->check_out->format('h:i A'),
            'attendance_date' => $attendance->date->format('Y-m-d'),
            'attendance_date_formatted' => $attendance->date->format('M d, Y'),
            'hours_worked' => $attendance->hours_worked ?? 0,
            'hours_worked_formatted' => $this->formatHours($attendance->hours_worked ?? 0),
            'status' => $attendance->status,
            'status_label' => $attendance->status_label,
            'location' => $attendance->location?->name ?? 'Not specified',
            'overtime_hours' => $attendance->overtime_hours ?? 0,
            'overtime_hours_formatted' => $attendance->overtime_hours_formatted ?? '0h',
            'early_departure_minutes' => $attendance->early_departure_minutes ?? 0,
            'total_break_minutes' => $attendance->total_break_minutes ?? 0,
            'check_out_method' => $attendance->check_out_method_label,
            'net_working_hours' => $attendance->net_working_hours ?? 0,
        ];

        // Send to manager if configured
        $notifyManager = Setting::get('attendance.notifications.notify_manager_on_check_out', true);
        if ($notifyManager && $manager = $this->getUserManager($user)) {
            $variables['manager_name'] = $manager->name;
            $this->notificationService->sendToUser(
                $manager,
                'attendance_check_out_manager',
                $variables,
                ['email', 'in_app']
            );
        }

        // Send to user if configured
        $notifyUser = Setting::get('attendance.notifications.notify_user_on_check_out', false);
        if ($notifyUser) {
            $this->notificationService->sendToUser(
                $user,
                'attendance_check_out_user',
                $variables,
                ['email', 'in_app']
            );
        }

        // Send early departure notification if applicable
        if ($attendance->early_departure_minutes > 0) {
            $notifyEarlyDeparture = Setting::get('attendance.notifications.notify_on_early_departure', true);
            if ($notifyEarlyDeparture) {
                $this->notificationService->sendToUser(
                    $user,
                    'attendance_early_departure',
                    array_merge($variables, [
                        'early_departure_minutes' => $attendance->early_departure_minutes,
                        'scheduled_end_time' => $this->getScheduledEndTime($attendance),
                    ]),
                    ['email', 'in_app']
                );

                // Also notify manager
                if ($manager) {
                    $this->notificationService->sendToUser(
                        $manager,
                        'attendance_early_departure_manager',
                        array_merge($variables, [
                            'early_departure_minutes' => $attendance->early_departure_minutes,
                            'scheduled_end_time' => $this->getScheduledEndTime($attendance),
                        ]),
                        ['email', 'in_app']
                    );
                }
            }
        }

        // Send overtime notification if applicable
        if ($attendance->overtime_hours > 0) {
            $notifyOvertime = Setting::get('attendance.notifications.notify_on_overtime', true);
            if ($notifyOvertime) {
                $this->notificationService->sendToUser(
                    $user,
                    'attendance_overtime',
                    array_merge($variables, [
                        'overtime_hours' => $attendance->overtime_hours,
                        'overtime_minutes' => $attendance->overtime_minutes ?? 0,
                    ]),
                    ['email', 'in_app']
                );

                // Also notify manager
                if ($manager) {
                    $this->notificationService->sendToUser(
                        $manager,
                        'attendance_overtime_manager',
                        array_merge($variables, [
                            'overtime_hours' => $attendance->overtime_hours,
                            'overtime_minutes' => $attendance->overtime_minutes ?? 0,
                        ]),
                        ['email', 'in_app']
                    );
                }
            }
        }
    }

    /**
     * Get user's manager.
     *
     * @param \App\Models\User $user
     * @return \App\Models\User|null
     */
    protected function getUserManager(User $user): ?User
    {
        // Assuming there's a manager relationship
        // You may need to adjust this based on your actual implementation
        return $user->manager ?? null;
    }

    /**
     * Get scheduled end time for the attendance.
     *
     * @param \App\Models\Attendance $attendance
     * @return string
     */
    protected function getScheduledEndTime($attendance): string
    {
        // This should be based on business hours or user's schedule
        // For now, return a default or extract from business hours
        return '05:00 PM';
    }

    /**
     * Format hours to a readable string.
     *
     * @param float $hours
     * @return string
     */
    protected function formatHours(float $hours): string
    {
        $wholeHours = floor($hours);
        $minutes = round(($hours - $wholeHours) * 60);
        
        if ($wholeHours > 0 && $minutes > 0) {
            return "{$wholeHours}h {$minutes}m";
        }
        
        if ($wholeHours > 0) {
            return "{$wholeHours}h";
        }
        
        return "{$minutes}m";
    }
}