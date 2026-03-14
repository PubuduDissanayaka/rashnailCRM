<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

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
            'subject' => $this->faker->sentence,
            'content' => $this->faker->paragraph,
            'channels' => $this->faker->randomElements(['email', 'in_app', 'sms'], $this->faker->numberBetween(1, 2)),
            'status' => $this->faker->randomElement(['pending', 'sent', 'failed', 'delivered']),
            'metadata' => [
                'priority' => $this->faker->randomElement(['low', 'normal', 'high']),
                'template_variables' => [
                    'name' => $this->faker->name,
                    'date' => $this->faker->date(),
                ],
            ],
            'sent_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'read_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'retry_count' => $this->faker->numberBetween(0, 3),
            'last_attempt_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'scheduled_at' => $this->faker->optional()->dateTimeBetween('now', '+1 week'),
        ];
    }

    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'sent_at' => null,
                'read_at' => null,
            ];
        });
    }

    public function sent()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'sent',
                'sent_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }

    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'sent_at' => null,
                'retry_count' => $this->faker->numberBetween(1, 3),
                'last_attempt_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            ];
        });
    }

    public function delivered()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'delivered',
                'sent_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'read_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }

    public function withChannels(array $channels)
    {
        return $this->state(function (array $attributes) use ($channels) {
            return [
                'channels' => $channels,
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

    public function forUser(User $user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id,
            ];
        });
    }
}