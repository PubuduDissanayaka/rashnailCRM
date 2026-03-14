<?php

namespace App\Notifications;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AttendanceCheckOutNotification extends Notification implements ShouldQueue
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
        $checkOutTime = $this->attendance->check_out->format('Y-m-d H:i:s');
        $hoursWorked = number_format($this->attendance->hours_worked, 2);
        $overtimeMinutes = $this->attendance->overtime_minutes;
        
        $mail = (new MailMessage)
            ->subject('Attendance Check-Out Notification')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->user->name . ' has checked out at ' . $checkOutTime)
            ->line('Check-in: ' . $checkInTime)
            ->line('Hours Worked: ' . $hoursWorked . ' hours');
        
        if ($overtimeMinutes > 0) {
            $mail->line('Overtime: ' . $overtimeMinutes . ' minutes');
        }
        
        $mail->line('Break Time: ' . $this->attendance->break_minutes . ' minutes')
            ->action('View Attendance', url('/attendances/' . $this->attendance->id))
            ->line('Thank you for using our attendance system!');
        
        return $mail;
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
            'check_out_time' => $this->attendance->check_out->toDateTimeString(),
            'hours_worked' => $this->attendance->hours_worked,
            'overtime_minutes' => $this->attendance->overtime_minutes,
            'break_minutes' => $this->attendance->break_minutes,
            'message' => $this->user->name . ' has checked out after working ' . number_format($this->attendance->hours_worked, 2) . ' hours',
            'type' => 'attendance_check_out',
        ];
    }
}