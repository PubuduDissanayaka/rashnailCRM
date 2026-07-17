<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Setting;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders
                            {--dry-run : Preview reminders without sending}
                            {--hours= : Override the reminder window (hours before)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send appointment reminders based on notification.reminder_hours setting';

    /**
     * The notification service.
     *
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if email notifications are globally enabled
        if (!Setting::get('notification.email_enabled', true)) {
            $this->warn('Email notifications are disabled (notification.email_enabled = false). Skipping reminders.');
            return 0;
        }

        $reminderHours = (int) ($this->option('hours') ?? Setting::get('notification.reminder_hours', 24));
        $isDryRun = $this->option('dry-run');

        $this->info("Looking for appointments scheduled in {$reminderHours} hours...");

        // Find appointments where appointment_date falls within the reminder window
        $targetStart = now()->addHours($reminderHours);
        $targetEnd = $targetStart->copy()->addMinutes(59);

        $appointments = Appointment::with(['customer', 'user', 'service'])
            ->where('status', 'scheduled')
            ->whereBetween('appointment_date', [$targetStart, $targetEnd])
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('No appointments found needing reminders in this window.');
            return 0;
        }

        $this->info("Found {$appointments->count()} appointment(s) needing reminders.");

        $sent = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($appointments as $appointment) {
            $customer = $appointment->customer;
            $staff = $appointment->user;
            $service = $appointment->service;

            if (!$customer || !$customer->email) {
                $this->warn("  [SKIP] Appointment #{$appointment->id}: Customer has no email address.");
                $skipped++;
                continue;
            }

            $data = [
                'customer_name' => $customer->full_name ?? $customer->first_name,
                'staff_name' => $staff?->name ?? 'N/A',
                'service_name' => $service?->name ?? 'N/A',
                'appointment_date' => $appointment->appointment_date->format('l, F j, Y'),
                'appointment_time' => $appointment->appointment_date->format('g:i A'),
                'business_name' => Setting::get('business.name', config('app.name')),
                'business_address' => Setting::get('business.address', ''),
                'business_phone' => Setting::get('business.phone', ''),
            ];

            if ($isDryRun) {
                $this->line("  [DRY-RUN] Would remind {$customer->email} for appointment #{$appointment->id} on {$data['appointment_date']} at {$data['appointment_time']}");
                $sent++;
                continue;
            }

            try {
                $notification = Notification::create([
                    'type' => 'appointment_reminder',
                    'notifiable_type' => get_class($customer),
                    'notifiable_id' => $customer->id,
                    'data' => $data,
                    'status' => 'pending',
                ]);

                $this->notificationService->send($notification, ['email']);

                $this->line("  [SENT] Reminder to {$customer->email} for appointment #{$appointment->id}");
                $sent++;

                Log::info('Appointment reminder sent', [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $customer->id,
                    'customer_email' => $customer->email,
                    'reminder_hours' => $reminderHours,
                ]);
            } catch (\Exception $e) {
                $this->error("  [ERROR] Failed to send reminder for appointment #{$appointment->id}: {$e->getMessage()}");
                $errors++;
                Log::error('Failed to send appointment reminder', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->table(
            ['', 'Count'],
            [
                ['Total found', $appointments->count()],
                $isDryRun ? ['Would send', $sent] : ['Sent', $sent],
                ['Skipped (no email)', $skipped],
                ['Errors', $errors],
            ]
        );

        return $errors > 0 ? 1 : 0;
    }
}
