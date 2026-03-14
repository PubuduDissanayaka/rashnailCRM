<?php

namespace App\Notifications;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AttendanceCheckInNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $attendance;
    protected $user;

    /**
     * Create a new notification instance.
     */
    public function __construct(Attendance $attendance, User $user)
    {
        $this->attendance = $attendance;
        $this->user = $user;
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
        $checkInTime = $this->attendance->check_in->format('Y-m-d H:i:s');
        $status = $this->attendance->status;
        
        return (new MailMessage)
            ->subject('Attendance Check-In Notification')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->user->name . ' has checked in at ' . $checkInTime)
            ->line('Status: ' . ucfirst(str_replace('_', ' ', $status)))
            ->line('Location: ' . ($this->attendance->location?->name ?? 'Not specified'))
            ->action('View Attendance', url('/attendances/' . $this->attendance->id))
            ->line('Thank you for using our attendance system!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'check_in_time' => $this->attendance->check_in->toDateTimeString(),
            'status' => $this->attendance->status,
            'location' => $this->attendance->location?->name,
            'message' => $this->user->name . ' has checked in at ' . $this->attendance->check_in->format('H:i:s'),
            'type' => 'attendance_check_in',
        ];
    }
}