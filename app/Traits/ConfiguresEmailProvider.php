<?php

namespace App\Traits;

use App\Models\NotificationProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;

trait ConfiguresEmailProvider
{
    /**
     * Configure mailer for a specific provider
     */
    protected function configureMailerForProvider(NotificationProvider $provider): void
    {
        $config = $provider->config;

        // Decrypt sensitive fields
        $password = $this->decryptConfigValue($config, 'password');
        $secret = $this->decryptConfigValue($config, 'secret');
        $apiKey = $this->decryptConfigValue($config, 'api_key');

        // Configure based on provider type
        switch ($provider->provider) {
            case 'smtp':
                $smtpConfig = [
                    'transport' => 'smtp',
                    'host' => $config['host'] ?? config('mail.mailers.smtp.host'),
                    'port' => $config['port'] ?? config('mail.mailers.smtp.port'),
                    'encryption' => ($config['encryption'] ?? 'tls') === 'none' ? null : ($config['encryption'] ?? 'tls'),
                    'username' => $config['username'] ?? config('mail.mailers.smtp.username'),
                    'password' => $password ?: config('mail.mailers.smtp.password'),
                    'timeout' => $config['timeout'] ?? 30,
                    'local_domain' => $config['local_domain'] ?? env('MAIL_EHLO_DOMAIN'),
                ];

                // Purge existing instance to ensure new config is used
                try {
                    \Illuminate\Support\Facades\Mail::purge('dynamic_smtp');
                } catch (\Exception $e) {
                    // Ignore if mailer doesn't support purging or doesn't exist
                }

                Config::set('mail.mailers.dynamic_smtp', $smtpConfig);
                Config::set('mail.from.address', $config['from_address'] ?? config('mail.from.address'));
                Config::set('mail.from.name', $config['from_name'] ?? config('mail.from.name'));
                break;

            case 'mailgun':
            case 'sendgrid':
            case 'ses':
                // For now, these fall back to SMTP logic or need specific driver config
                // In a full implementation, you'd set the specific driver and its config
                // But for this project, passing through SMTP is a safe fallback if drivers aren't installed
                $smtpConfig = [
                    'transport' => 'smtp',
                    'host' => config('mail.mailers.smtp.host'), // Fallback
                    'port' => config('mail.mailers.smtp.port'),
                    'encryption' => config('mail.mailers.smtp.encryption'),
                    'username' => config('mail.mailers.smtp.username'),
                    'password' => config('mail.mailers.smtp.password'),
                ];
                
                // If using specific drivers, configuring them here would be better
                // e.g. Config::set('services.mailgun.domain', $config['domain']);
                
                Config::set('mail.mailers.dynamic_smtp', $smtpConfig);
                Config::set('mail.from.address', $config['from_address'] ?? config('mail.from.address'));
                Config::set('mail.from.name', $config['from_name'] ?? config('mail.from.name'));
                break;
        }
    }

    /**
     * Decrypt a config value if it's encrypted
     */
    protected function decryptConfigValue(array $config, string $key): ?string
    {
        $value = $config[$key] ?? null;

        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // If decryption fails, assume it's not encrypted
            return $value;
        }
    }
    
    /**
     * Get the active email provider
     */
    protected function getActiveEmailProvider(): ?NotificationProvider
    {
        return NotificationProvider::where('channel', 'email')
            ->where('is_active', true)
            ->orderBy('priority')
            ->first();
    }
}
