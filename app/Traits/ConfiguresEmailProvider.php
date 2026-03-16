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

        // Purge any cached mailer instance before reconfiguring
        try {
            \Illuminate\Support\Facades\Mail::purge('dynamic_smtp');
        } catch (\Exception $e) {
            // Ignore — older Laravel versions may not support purge
        }

        // Configure based on provider type
        switch ($provider->provider) {
            case 'cpanel':  // cPanel / Local Mail — identical to SMTP
            case 'smtp':
                Config::set('mail.mailers.dynamic_smtp', [
                    'transport'    => 'smtp',
                    'host'         => $config['host'] ?? config('mail.mailers.smtp.host'),
                    'port'         => (int) ($config['port'] ?? config('mail.mailers.smtp.port', 587)),
                    'encryption'   => ($config['encryption'] ?? 'tls') === 'none' ? null : ($config['encryption'] ?? 'tls'),
                    'username'     => $config['username'] ?? config('mail.mailers.smtp.username'),
                    'password'     => $password ?: config('mail.mailers.smtp.password'),
                    'timeout'      => (int) ($config['timeout'] ?? 30),
                    'local_domain' => $config['local_domain'] ?? null,
                ]);
                break;

            case 'mailgun':
                // Mailgun SMTP relay: username = postmaster@{domain}, password = API key
                Config::set('mail.mailers.dynamic_smtp', [
                    'transport'  => 'smtp',
                    'host'       => 'smtp.mailgun.org',
                    'port'       => 587,
                    'encryption' => 'tls',
                    'username'   => 'postmaster@' . ($config['domain'] ?? ''),
                    'password'   => $secret ?: '',
                    'timeout'    => 30,
                ]);
                break;

            case 'sendgrid':
                // SendGrid SMTP relay: username is always the literal string "apikey"
                Config::set('mail.mailers.dynamic_smtp', [
                    'transport'  => 'smtp',
                    'host'       => 'smtp.sendgrid.net',
                    'port'       => 587,
                    'encryption' => 'tls',
                    'username'   => 'apikey',
                    'password'   => $apiKey ?: '',
                    'timeout'    => 30,
                ]);
                break;

            case 'ses':
                // Amazon SES SMTP relay — username = IAM access key ID,
                // password = SMTP credential (derived from IAM secret, NOT the raw secret).
                // The aws/aws-sdk-php package is required for the native SES transport.
                // Use SES SMTP endpoint directly; user must generate SES SMTP credentials
                // in the AWS console (IAM → SES SMTP settings → Create SMTP credentials).
                $region = $config['region'] ?? 'us-east-1';
                Config::set('mail.mailers.dynamic_smtp', [
                    'transport'  => 'smtp',
                    'host'       => "email-smtp.{$region}.amazonaws.com",
                    'port'       => 587,
                    'encryption' => 'tls',
                    'username'   => $config['key'] ?? '',
                    'password'   => $secret ?: '',
                    'timeout'    => 30,
                ]);
                break;
        }

        Config::set('mail.from.address', $config['from_address'] ?? config('mail.from.address'));
        Config::set('mail.from.name', $config['from_name'] ?? config('mail.from.name'));
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
