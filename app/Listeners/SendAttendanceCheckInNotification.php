<?php

namespace App\Listeners;

use App\Events\AttendanceCheckedIn;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Setting;

class SendAttendanceCheckInNotification
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
     * @param \App\Events\AttendanceCheckedIn $event
     * @return void
     */
    public function handle(AttendanceCheckedIn $event): void
    {
        $attendance = $event->attendance;
        $user = $event->user;

        $variables = [
            'user_name' => $user->name,
            'check_in_time' => $attendance->check_in->format('Y-m-d H:i:s'),
            'check_in_time_formatted' => $attendance->check_in->format('h:i A'),
            'attendance_date' => $attendance->date->format('Y-m-d'),
            'attendance_date_formatted' => $attendance->date->format('M d, Y'),
            'status' => $attendance->status,
            'status_label' => $attendance->status_label,
            'location' => $attendance->location?->name ?? 'Not specified',
            'late_minutes' => $attendance->late_arrival_minutes ?? 0,
            'check_in_method' => $attendance->check_in_method_label,
            'ip_address' => $attendance->ip_address ?? 'Unknown',
        ];

        // Send to manager if configured
        $notifyManager = Setting::get('attendance.notifications.notify_manager_on_check_in', true);
        if ($notifyManager && $manager = $this->getUserManager($user)) {
            $variables['manager_name'] = $manager->name;
            $this->notificationService->sendToUser(
                $manager,
                'attendance_check_in_manager',
                $variables,
                ['email', 'in_app']
            );
        }

        // Send to user if configured
        $notifyUser = Setting::get('attendance.notifications.notify_user_on_check_in', false);
        if ($notifyUser) {
            $this->notificationService->sendToUser(
                $user,
                'attendance_check_in_user',
                $variables,
                ['email', 'in_app']
            );
        }

        // Send late check-in notification if applicable
        if ($attendance->status === 'late') {
            $notifyLate = Setting::get('attendance.notifications.notify_on_late_check_in', true);
            if ($notifyLate) {
                $this->notificationService->sendToUser(
                    $user,
                    'attendance_late_check_in',
                    array_merge($variables, [
                        'late_minutes' => $attendance->late_arrival_minutes,
                        'scheduled_time' => $this->getScheduledStartTime($attendance),
                    ]),
                    ['email', 'in_app']
                );

                // Also notify manager
                if ($manager) {
                    $this->notificationService->sendToUser(
                        $manager,
                        'attendance_late_check_in_manager',
                        array_merge($variables, [
                            'late_minutes' => $attendance->late_arrival_minutes,
                            'scheduled_time' => $this->getScheduledStartTime($attendance),
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
     * Get scheduled start time for the attendance.
     *
     * @param \App\Models\Attendance $attendance
     * @return string
     */
    protected function getScheduledStartTime($attendance): string
    {
        // This should be based on business hours or user's schedule
        // For now, return a default or extract from business hours
        return '09:00 AM';
    }
}