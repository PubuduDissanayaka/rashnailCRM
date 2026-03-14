<?php

namespace Database\Factories;

use App\Models\NotificationProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationProviderFactory extends Factory
{
    protected $model = NotificationProvider::class;

    public function definition()
    {
        $type = $this->faker->randomElement(['email', 'sms', 'push']);
        
        $config = match($type) {
            'email' => [
                'host' => $this->faker->domainName,
                'port' => $this->faker->numberBetween(25, 587),
                'username' => $this->faker->userName,
                'password' => $this->faker->password,
                'encryption' => $this->faker->randomElement(['tls', 'ssl', 'none']),
                'from_address' => $this->faker->email,
                'from_name' => $this->faker->company,
            ],
            'sms' => [
                'api_key' => $this->faker->uuid,
                'api_secret' => $this->faker->password,
                'from_number' => $this->faker->phoneNumber,
                'provider' => $this->faker->randomElement(['twilio', 'nexmo', 'plivo']),
            ],
            'push' => [
                'api_key' => $this->faker->uuid,
                'project_id' => $this->faker->uuid,
                'app_id' => $this->faker->uuid,
                'provider' => $this->faker->randomElement(['fcm', 'apns', 'onesignal']),
            ],
            default => [],
        };

        return [
            'name' => $this->faker->company . ' ' . ucfirst($type) . ' Provider',
            'type' => $type,
            'config' => $config,
            'is_active' => $this->faker->boolean(90),
            'is_default' => false,
            'priority' => $this->faker->numberBetween(1, 10),
            'health_status' => $this->faker->randomElement(['healthy', 'degraded', 'unhealthy']),
            'last_health_check' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'rate_limit' => $this->faker->numberBetween(100, 1000),
            'rate_limit_period' => $this->faker->randomElement(['minute', 'hour', 'day']),
        ];
    }

    public function email()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'email',
                'config' => [
                    'host' => 'smtp.example.com',
                    'port' => 587,
                    'username' => 'noreply@example.com',
                    'password' => 'password123',
                    'encryption' => 'tls',
                    'from_address' => 'noreply@example.com',
                    'from_name' => 'System Notifications',
                ],
            ];
        });
    }

    public function sms()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'sms',
                'config' => [
                    'api_key' => 'test_api_key',
                    'api_secret' => 'test_api_secret',
                    'from_number' => '+1234567890',
                    'provider' => 'twilio',
                ],
            ];
        });
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
                'health_status' => 'healthy',
            ];
        });
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
                'health_status' => 'unhealthy',
            ];
        });
    }

    public function default()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_default' => true,
                'is_active' => true,
                'health_status' => 'healthy',
            ];
        });
    }

    public function withRateLimit(int $limit, string $period = 'hour')
    {
        return $this->state(function (array $attributes) use ($limit, $period) {
            return [
                'rate_limit' => $limit,
                'rate_limit_period' => $period,
            ];
        });
    }

    public function withHealthStatus(string $status)
    {
        return $this->state(function (array $attributes) use ($status) {
            return [
                'health_status' => $status,
                'last_health_check' => now(),
            ];
        });
    }
}