<?php

namespace App\Services\Notification\Channels;

use App\Models\Notification;
use App\Models\NotificationProvider;
use App\Models\User;
use App\Services\TemplateService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;

class EmailNotificationChannel implements NotificationChannel
{
    /**
     * The template service.
     *
     * @var TemplateService
     */
    protected $templateService;

    /**
     * The email provider.
     *
     * @var NotificationProvider|null
     */
    protected $provider;

    /**
     * Create a new email channel instance.
     *
     * @param TemplateService $templateService
     */
    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
        $this->provider = $this->getActiveProvider();
    }

    /**
     * Send the notification via email.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return bool
     */
    public function send($notifiable, Notification $notification): bool
    {
        if (!$this->canSend($notifiable, $notification)) {
            return false;
        }

        if (!$this->provider) {
            return false;
        }

        $email = $this->prepareEmail($notifiable, $notification);
        
        if (!$email) {
            return false;
        }

        try {
            $this->sendEmail($email);
            $this->incrementProviderUsage();
            return true;
        } catch (\Exception $e) {
            // Log error
            return false;
        }
    }

    /**
     * Check if the channel can send the notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return bool
     */
    public function canSend($notifiable, Notification $notification): bool
    {
        if (!$notifiable instanceof User) {
            return false;
        }

        if (empty($notifiable->email)) {
            return false;
        }

        if (!$this->provider) {
            return false;
        }

        if ($this->provider->hasReachedDailyLimit() || $this->provider->hasReachedMonthlyLimit()) {
            return false;
        }

        return true;
    }

    /**
     * Get the channel name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'email';
    }

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->provider ? $this->provider->provider : 'unknown';
    }

    /**
     * Get the subject for the notification.
     *
     * @param Notification $notification
     * @return string|null
     */
    public function getSubject(Notification $notification): ?string
    {
        // In a real implementation, you'd extract subject from template
        $type = $notification->type;
        $data = $notification->data;
        
        // Default subject based on notification type
        $subjects = [
            'attendance_check_in' => 'Attendance Check-In Notification',
            'report_generated' => 'Report Generated',
            'welcome_email' => 'Welcome to Our System',
        ];
        
        return $subjects[$type] ?? 'Notification';
    }

    /**
     * Get a content preview for logging.
     *
     * @param Notification $notification
     * @return string|null
     */
    public function getContentPreview(Notification $notification): ?string
    {
        $data = $notification->data;
        $preview = substr(json_encode($data), 0, 100);
        return $preview . '...';
    }

    /**
     * Get the active email provider.
     *
     * @return NotificationProvider|null
     */
    protected function getActiveProvider(): ?NotificationProvider
    {
        return NotificationProvider::where('channel', 'email')
            ->active()
            ->orderByPriority()
            ->first();
    }

    /**
     * Prepare email for sending.
     *
     * @param User $user
     * @param Notification $notification
     * @return array|null
     */
    protected function prepareEmail(User $user, Notification $notification): ?array
    {
        $type = $notification->type;
        $data = $notification->data;
        
        // Try to get template by slug (notification type)
        $template = $this->templateService->getTemplateBySlug($type);
        
        if ($template) {
            $rendered = $this->templateService->render($template, $data);
            $subject = $rendered['subject'] ?? $this->getSubject($notification);
            $bodyHtml = $rendered['body_html'] ?? '';
            $bodyText = $rendered['body_text'] ?? '';
        } else {
            // Fallback to basic email
            $subject = $this->getSubject($notification);
            $bodyHtml = $this->generateBasicHtml($user, $notification);
            $bodyText = $this->generateBasicText($user, $notification);
        }
        
        return [
            'to' => $user->email,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'from_address' => $this->provider->getConfigValue('from_address', config('mail.from.address')),
            'from_name' => $this->provider->getConfigValue('from_name', config('mail.from.name')),
        ];
    }

    /**
     * Send the email using the configured provider.
     *
     * @param array $email
     * @return void
     */
    protected function sendEmail(array $email): void
    {
        $providerType = $this->provider->provider;
        
        switch ($providerType) {
            case 'smtp':
                $this->sendViaSmtp($email);
                break;
            case 'mailgun':
                $this->sendViaMailgun($email);
                break;
            case 'sendgrid':
                $this->sendViaSendgrid($email);
                break;
            case 'ses':
                $this->sendViaSes($email);
                break;
            default:
                $this->sendViaSmtp($email);
        }
    }

    /**
     * Send email via SMTP.
     *
     * @param array $email
     * @return void
     */
    protected function sendViaSmtp(array $email): void
    {
        $config = $this->provider->config;

        // Decrypt password if it's encrypted
        $password = $config['password'] ?? '';
        if ($password) {
            try {
                $password = Crypt::decryptString($password);
            } catch (\Exception $e) {
                // If decryption fails, assume it's not encrypted
            }
        }

        // Configure SMTP settings dynamically
        $smtpConfig = [
            'transport' => 'smtp',
            'host' => $config['host'] ?? config('mail.mailers.smtp.host'),
            'port' => $config['port'] ?? config('mail.mailers.smtp.port'),
            'encryption' => ($config['encryption'] ?? 'tls') === 'none' ? null : ($config['encryption'] ?? 'tls'),
            'username' => $config['username'] ?? config('mail.mailers.smtp.username'),
            'password' => $password ?: config('mail.mailers.smtp.password'),
            'timeout' => $config['timeout'] ?? 30,
            'local_domain' => $config['local_domain'] ?? null,
        ];

        // Set mail configuration temporarily
        Config::set('mail.mailers.dynamic_smtp', $smtpConfig);
        Config::set('mail.from.address', $email['from_address']);
        Config::set('mail.from.name', $email['from_name']);

        // Send email using the dynamically configured mailer
        Mail::mailer('dynamic_smtp')->send([], [], function ($message) use ($email) {
            $message->to($email['to'])
                ->subject($email['subject'])
                ->from($email['from_address'], $email['from_name'])
                ->html($email['body_html']);

            if (!empty($email['body_text'])) {
                $message->text($email['body_text']);
            }
        });
    }

    /**
     * Send email via Mailgun.
     *
     * @param array $email
     * @return void
     */
    protected function sendViaMailgun(array $email): void
    {
        // Implementation would use Mailgun SDK
        // For now, fallback to SMTP
        $this->sendViaSmtp($email);
    }

    /**
     * Send email via SendGrid.
     *
     * @param array $email
     * @return void
     */
    protected function sendViaSendgrid(array $email): void
    {
        // Implementation would use SendGrid SDK
        // For now, fallback to SMTP
        $this->sendViaSmtp($email);
    }

    /**
     * Send email via Amazon SES.
     *
     * @param array $email
     * @return void
     */
    protected function sendViaSes(array $email): void
    {
        // Implementation would use SES SDK
        // For now, fallback to SMTP
        $this->sendViaSmtp($email);
    }

    /**
     * Increment provider usage count.
     *
     * @return void
     */
    protected function incrementProviderUsage(): void
    {
        if ($this->provider) {
            $this->provider->incrementUsage();
        }
    }

    /**
     * Generate basic HTML email content.
     *
     * @param User $user
     * @param Notification $notification
     * @return string
     */
    protected function generateBasicHtml(User $user, Notification $notification): string
    {
        $data = $notification->data;
        
        return "
            <!DOCTYPE html>
            <html>
            <head>
                <title>{$this->getSubject($notification)}</title>
            </head>
            <body>
                <h1>Hello {$user->name},</h1>
                <p>This is a notification of type: {$notification->type}</p>
                <p>Data: " . json_encode($data) . "</p>
                <hr>
                <p>Sent via " . config('app.name') . "</p>
            </body>
            </html>
        ";
    }

    /**
     * Generate basic text email content.
     *
     * @param User $user
     * @param Notification $notification
     * @return string
     */
    protected function generateBasicText(User $user, Notification $notification): string
    {
        $data = $notification->data;
        
        return "Hello {$user->name},\n\nThis is a notification of type: {$notification->type}\n\nData: " . json_encode($data) . "\n\nSent via " . config('app.name');
    }
}