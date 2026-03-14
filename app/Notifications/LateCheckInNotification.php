<?php

namespace App\Notifications;

use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateCheckInNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $attendance;

    /**
     * Create a new notification instance.
     */
    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $user = $this->attendance->user;
        $checkInTime = $this->attendance->check_in->format('Y-m-d H:i:s');
        $lateMinutes = $this->attendance->late_minutes;
        
        // Get shift information
        $shift = $this->attendance->shiftAssignment?->shift;
        $scheduledTime = 'Not scheduled';
        
        if ($shift) {
            $scheduledTime = $this->attendance->check_in->format('Y-m-d') . ' ' . $shift->start_time;
        }
        
        return (new MailMessage)
            ->subject('Late Check-In Alert')
            ->greeting('Attention ' . $notifiable->name . '!')
            ->line($user->name . ' has checked in late.')
            ->line('Scheduled Time: ' . $scheduledTime)
            ->line('Actual Check-in: ' . $checkInTime)
            ->line('Late by: ' . $lateMinutes . ' minutes')
            ->line('Status: ' . ucfirst($this->attendance->status))
            ->action('View Attendance Details', url('/attendances/' . $this->attendance->id))
            ->line('Please review this late check-in and take appropriate action if necessary.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $user = $this->attendance->user;
        
        return [
            'attendance_id' => $this->attendance->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'check_in_time' => $this->attendance->check_in->toDateTimeString(),
            'late_minutes' => $this->attendance->late_minutes,
            'status' => $this->attendance->status,
            'shift_assignment_id' => $this->attendance->shift_assignment_id,
            'message' => $user->name . ' checked in ' . $this->attendance->late_minutes . ' minutes late',
            'type' => 'late_check_in',
            'severity' => 'warning',
        ];
    }
}