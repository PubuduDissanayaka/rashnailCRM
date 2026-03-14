<?php

namespace App\Listeners;

use App\Events\EarlyDepartureDetected;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Setting;

class SendEarlyDepartureNotification
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
     * @param \App\Events\EarlyDepartureDetected $event
     * @return void
     */
    public function handle(EarlyDepartureDetected $event): void
    {
        $attendance = $event->attendance;
        $user = $attendance->user;

        if (!$user) {
            return;
        }

        $variables = [
            'user_name' => $user->name,
            'check_out_time' => $attendance->check_out->format('Y-m-d H:i:s'),
            'check_out_time_formatted' => $attendance->check_out->format('h:i A'),
            'check_in_time' => $attendance->check_in->format('Y-m-d H:i:s'),
            'check_in_time_formatted' => $attendance->check_in->format('h:i A'),
            'attendance_date' => $attendance->date->format('Y-m-d'),
            'attendance_date_formatted' => $attendance->date->format('M d, Y'),
            'early_departure_minutes' => $attendance->early_departure_minutes ?? 0,
            'scheduled_end_time' => $this->getScheduledEndTime($attendance),
            'hours_worked' => $attendance->hours_worked ?? 0,
            'hours_worked_formatted' => $this->formatHours($attendance->hours_worked ?? 0),
            'location' => $attendance->location?->name ?? 'Not specified',
            'check_out_method' => $attendance->check_out_method_label,
            'status' => $attendance->status,
            'status_label' => $attendance->status_label,
        ];

        // Send to user
        $this->notificationService->sendToUser(
            $user,
            'attendance_early_departure',
            $variables,
            ['email', 'in_app']
        );

        // Send to manager if configured
        $notifyManager = Setting::get('attendance.notifications.notify_manager_on_early_departure', true);
        if ($notifyManager && $manager = $this->getUserManager($user)) {
            $variables['manager_name'] = $manager->name;
            $this->notificationService->sendToUser(
                $manager,
                'attendance_early_departure_manager',
                $variables,
                ['email', 'in_app']
            );
        }

        // Send to HR if configured
        $notifyHR = Setting::get('attendance.notifications.notify_hr_on_early_departure', false);
        if ($notifyHR) {
            $hrUsers = User::role('hr')->get();
            foreach ($hrUsers as $hrUser) {
                $this->notificationService->sendToUser(
                    $hrUser,
                    'attendance_early_departure_hr',
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