<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition()
    {
        $notificationType = $this->faker->randomElement([
            'attendance_check_in',
            'attendance_check_out',
            'late_check_in',
            'report_generated',
            'appointment_reminder',
            'system_announcement',
            'welcome_message',
            'password_reset',
        ]);

        $templateName = str_replace('_', ' ', ucwords($notificationType)) . ' Template';

        return [
            'name' => $templateName,
            'notification_type' => $notificationType,
            'subject' => $this->faker->sentence,
            'content' => $this->generateTemplateContent($notificationType),
            'variables' => $this->getTemplateVariables($notificationType),
            'is_active' => $this->faker->boolean(90),
            'version' => $this->faker->numberBetween(1, 5),
            'language' => $this->faker->randomElement(['en', 'es', 'fr', 'de']),
            'category' => $this->faker->randomElement(['system', 'user', 'transaction', 'alert']),
        ];
    }

    private function generateTemplateContent(string $type): string
    {
        $templates = [
            'attendance_check_in' => "Hello {{name}},\n\nYou have successfully checked in at {{check_in_time}}.\n\nLocation: {{location}}\nDate: {{date}}\n\nThank you!",
            'attendance_check_out' => "Hello {{name}},\n\nYou have successfully checked out at {{check_out_time}}.\n\nTotal hours: {{total_hours}}\nDate: {{date}}\n\nThank you for your work today!",
            'late_check_in' => "Hello {{name}},\n\nYou checked in late at {{check_in_time}}.\n\nScheduled time: {{scheduled_time}}\nLate by: {{late_by}}\n\nPlease ensure timely attendance.",
            'report_generated' => "Hello {{name}},\n\nYour {{report_type}} report has been generated.\n\nReport: {{report_name}}\nDownload link: {{download_url}}\nGenerated at: {{generated_at}}\n\nThank you!",
            'appointment_reminder' => "Hello {{name}},\n\nReminder: You have an appointment scheduled.\n\nService: {{service_name}}\nDate: {{appointment_date}}\nTime: {{appointment_time}}\nLocation: {{location}}\n\nPlease arrive 10 minutes early.",
            'system_announcement' => "Hello {{name}},\n\nSystem Announcement: {{announcement_title}}\n\n{{announcement_content}}\n\nPosted by: {{posted_by}}\nDate: {{post_date}}",
            'welcome_message' => "Welcome {{name}}!\n\nThank you for joining our system. We're excited to have you on board.\n\nYour account has been successfully created.\nEmail: {{email}}\n\nPlease verify your email address to get started.",
            'password_reset' => "Hello {{name}},\n\nYou requested a password reset for your account.\n\nReset link: {{reset_link}}\nThis link will expire in {{expiry_time}}.\n\nIf you didn't request this, please ignore this email.",
        ];

        return $templates[$type] ?? "Hello {{name}},\n\nThis is a notification for {{notification_type}}.\n\nDate: {{date}}\nTime: {{time}}";
    }

    private function getTemplateVariables(string $type): array
    {
        $variables = [
            'attendance_check_in' => ['name', 'check_in_time', 'location', 'date'],
            'attendance_check_out' => ['name', 'check_out_time', 'total_hours', 'date', 'break_time'],
            'late_check_in' => ['name', 'check_in_time', 'scheduled_time', 'late_by', 'date'],
            'report_generated' => ['name', 'report_type', 'report_name', 'download_url', 'generated_at', 'file_size'],
            'appointment_reminder' => ['name', 'service_name', 'appointment_date', 'appointment_time', 'location', 'customer_name'],
            'system_announcement' => ['name', 'announcement_title', 'announcement_content', 'posted_by', 'post_date'],
            'welcome_message' => ['name', 'email', 'verification_link', 'account_type'],
            'password_reset' => ['name', 'reset_link', 'expiry_time', 'ip_address'],
        ];

        return $variables[$type] ?? ['name', 'date', 'time'];
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    public function ofType(string $type)
    {
        return $this->state(function (array $attributes) use ($type) {
            return [
                'notification_type' => $type,
                'name' => str_replace('_', ' ', ucwords($type)) . ' Template',
                'content' => $this->generateTemplateContent($type),
                'variables' => $this->getTemplateVariables($type),
            ];
        });
    }

    public function withVariables(array $variables)
    {
        return $this->state(function (array $attributes) use ($variables) {
            return [
                'variables' => $variables,
            ];
        });
    }

    public function withContent(string $content)
    {
        return $this->state(function (array $attributes) use ($content) {
            return [
                'content' => $content,
            ];
        });
    }

    public function english()
    {
        return $this->state(function (array $attributes) {
            return [
                'language' => 'en',
            ];
        });
    }

    public function spanish()
    {
        return $this->state(function (array $attributes) {
            return [
                'language' => 'es',
            ];
        });
    }
}