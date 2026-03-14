<?php

namespace Database\Factories;

use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationSettingFactory extends Factory
{
    protected $model = NotificationSetting::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'notification_type' => $this->faker->randomElement([
                'attendance_check_in',
                'attendance_check_out',
                'late_check_in',
                'report_generated',
                'appointment_reminder',
                'system_announcement',
                'welcome_message',
                'password_reset',
            ]),
            'channel' => $this->faker->randomElement(['email', 'in_app', 'sms']),
            'is_enabled' => $this->faker->boolean(80), // 80% chance of being enabled
            'preferences' => [
                'priority' => $this->faker->randomElement(['low', 'normal', 'high']),
                'sound' => $this->faker->randomElement(['default', 'chime', 'bell']),
                'vibrate' => $this->faker->boolean(),
            ],
        ];
    }

    public function enabled()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_enabled' => true,
            ];
        });
    }

    public function disabled()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_enabled' => false,
            ];
        });
    }

    public function forUser(User $user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id,
            ];
        });
    }

    public function systemDefault()
    {
        return $this->state(function (array $attributes) {
            return [
                'user_id' => null,
            ];
        });
    }

    public function ofType(string $type)
    {
        return $this->state(function (array $attributes) use ($type) {
            return [
                'notification_type' => $type,
            ];
        });
    }

    public function forChannel(string $channel)
    {
        return $this->state(function (array $attributes) use ($channel) {
            return [
                'channel' => $channel,
            ];
        });
    }

    public function withPreferences(array $preferences)
    {
        return $this->state(function (array $attributes) use ($preferences) {
            return [
                'preferences' => array_merge($attributes['preferences'] ?? [], $preferences),
            ];
        });
    }

    public function doNotDisturb()
    {
        return $this->state(function (array $attributes) {
            return [
                'notification_type' => 'system',
                'channel' => 'do_not_disturb',
                'is_enabled' => true,
                'preferences' => [
                    'enabled' => true,
                    'start_time' => '22:00',
                    'end_time' => '08:00',
                    'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
                    'exceptions' => [],
                ],
            ];
        });
    }
}