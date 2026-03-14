<?php

namespace App\Listeners;

use App\Events\LateCheckInDetected;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Setting;

class SendLateCheckInNotification
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
     * @param \App\Events\LateCheckInDetected $event
     * @return void
     */
    public function handle(LateCheckInDetected $event): void
    {
        $attendance = $event->attendance;
        $user = $attendance->user;

        if (!$user) {
            return;
        }

        $variables = [
            'user_name' => $user->name,
            'check_in_time' => $attendance->check_in->format('Y-m-d H:i:s'),
            'check_in_time_formatted' => $attendance->check_in->format('h:i A'),
            'attendance_date' => $attendance->date->format('Y-m-d'),
            'attendance_date_formatted' => $attendance->date->format('M d, Y'),
            'late_minutes' => $attendance->late_arrival_minutes ?? 0,
            'scheduled_start_time' => $this->getScheduledStartTime($attendance),
            'location' => $attendance->location?->name ?? 'Not specified',
            'check_in_method' => $attendance->check_in_method_label,
            'status' => $attendance->status,
            'status_label' => $attendance->status_label,
        ];

        // Send to user
        $this->notificationService->sendToUser(
            $user,
            'attendance_late_check_in',
            $variables,
            ['email', 'in_app']
        );

        // Send to manager if configured
        $notifyManager = Setting::get('attendance.notifications.notify_manager_on_late_check_in', true);
        if ($notifyManager && $manager = $this->getUserManager($user)) {
            $variables['manager_name'] = $manager->name;
            $this->notificationService->sendToUser(
                $manager,
                'attendance_late_check_in_manager',
                $variables,
                ['email', 'in_app']
            );
        }

        // Send to HR if configured
        $notifyHR = Setting::get('attendance.notifications.notify_hr_on_late_check_in', false);
        if ($notifyHR) {
            $hrUsers = User::role('hr')->get();
            foreach ($hrUsers as $hrUser) {
                $this->notificationService->sendToUser(
                    $hrUser,
                    'attendance_late_check_in_hr',
                    $variables,
                    ['email', 'in_app']
                );
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