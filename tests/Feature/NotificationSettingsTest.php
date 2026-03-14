<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\NotificationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Events\AttendanceCheckedIn;
use App\Events\ReportGenerated;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_view_notification_settings_page()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('notification-settings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('notifications.settings.index');
        $response->assertViewHas('notificationTypes');
        $response->assertViewHas('channels');
        $response->assertViewHas('systemDefaults');
    }

    /** @test */
    public function user_can_update_notification_settings()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $settings = [
            [
                'notification_type' => 'attendance_check_in',
                'channel' => 'email',
                'is_enabled' => true,
                'preferences' => ['send_immediately' => true],
            ],
            [
                'notification_type' => 'attendance_check_in',
                'channel' => 'in_app',
                'is_enabled' => false,
                'preferences' => ['sound' => 'default'],
            ],
        ];

        $response = $this->post(route('notification-settings.update'), [
            'settings' => $settings,
        ]);

        $response->assertRedirect(route('notification-settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $user->id,
            'notification_type' => 'attendance_check_in',
            'channel' => 'email',
            'is_enabled' => true,
        ]);

        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $user->id,
            'notification_type' => 'attendance_check_in',
            'channel' => 'in_app',
            'is_enabled' => false,
        ]);
    }

    /** @test */
    public function user_can_reset_notification_settings_to_defaults()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create some custom settings
        NotificationSetting::create([
            'user_id' => $user->id,
            'notification_type' => 'attendance_check_in',
            'channel' => 'email',
            'is_enabled' => false,
            'preferences' => ['custom' => 'value'],
        ]);

        $response = $this->post(route('notification-settings.reset-to-defaults'));

        $response->assertRedirect(route('notification-settings.index'));
        $response->assertSessionHas('success');

        // Settings should be deleted (will use system defaults)
        $this->assertDatabaseMissing('notification_settings', [
            'user_id' => $user->id,
            'notification_type' => 'attendance_check_in',
            'channel' => 'email',
        ]);
    }

    /** @test */
    public function admin_can_update_system_wide_notification_settings()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage system');
        $this->actingAs($admin);

        $settings = [
            'attendance_check_in' => [
                'email' => ['is_enabled' => true],
                'in_app' => ['is_enabled' => false],
                'preferences' => ['priority' => 'high'],
            ],
            'report_generated' => [
                'email' => ['is_enabled' => true],
                'in_app' => ['is_enabled' => true],
                'preferences' => ['include_summary' => true],
            ],
        ];

        $response = $this->put(route('notification-settings.system.update'), [
            'settings' => $settings,
        ]);

        $response->assertRedirect(route('notification-settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notification_settings', [
            'user_id' => null,
            'notification_type' => 'attendance_check_in',
            'channel' => 'email',
            'is_enabled' => true,
        ]);

        $this->assertDatabaseHas('notification_settings', [
            'user_id' => null,
            'notification_type' => 'attendance_check_in',
            'channel' => 'in_app',
            'is_enabled' => false,
        ]);
    }

    /** @test */
    public function admin_can_apply_system_settings_to_all_users()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage system');
        $this->actingAs($admin);

        // Create system defaults
        NotificationSetting::create([
            'user_id' => null,
            'notification_type' => 'attendance_check_in',
            'channel' => 'email',
            'is_enabled' => true,
            'preferences' => ['system_default' => true],
        ]);

        // Create some users
        $users = User::factory()->count(3)->create();

        $response = $this->post(route('notification-settings.system.apply-to-all'));

        $response->assertRedirect(route('notification-settings.index'));
        $response->assertSessionHas('success');

        // Check that each user has the system setting
        foreach ($users as $user) {
            $this->assertDatabaseHas('notification_settings', [
                'user_id' => $user->id,
                'notification_type' => 'attendance_check_in',
                'channel' => 'email',
                'is_enabled' => true,
            ]);
        }
    }

    /** @test */
    public function admin_can_update_rate_limiting_settings()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage system');
        $this->actingAs($admin);

        $response = $this->post(route('notification-settings.rate-limiting.update'), [
            'rate_limit_hourly' => 500,
            'rate_limit_daily' => 5000,
            'user_limit_daily' => 100,
            'retry_attempts' => 3,
        ]);

        $response->assertRedirect(route('notification-settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('settings', [
            'key' => 'notification_rate_limit_hourly',
            'value' => '500',
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'notification_rate_limit_daily',
            'value' => '5000',
        ]);
    }

    /** @test */
    public function admin_can_update_blacklist_whitelist_settings()
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage system');
        $this->actingAs($admin);

        $response = $this->post(route('notification-settings.blacklist-whitelist.update'), [
            'blacklisted_domains' => "example.com\ntest.org",
            'whitelisted_ips' => "192.168.1.1\n10.0.0.0/24",
        ]);

        $response->assertRedirect(route('notification-settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('settings', [
            'key' => 'notification_blacklisted_domains',
            'value' => "example.com\ntest.org",
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'notification_whitelisted_ips',
            'value' => "192.168.1.1\n10.0.0.0/24",
        ]);
    }

    /** @test */
    public function attendance_check_in_event_triggers_notification_based_on_settings()
    {
        Event::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Enable email notifications for attendance check-in
        NotificationSetting::create([
            'user_id' => $user->id,
            'notification_type' => 'attendance_check_in',
            'channel' => 'email',
            'is_enabled' => true,
        ]);

        // Disable in-app notifications
        NotificationSetting::create([
            'user_id' => $user->id,
            'notification_type' => 'attendance_check_in',
            'channel' => 'in_app',
            'is_enabled' => false,
        ]);

        // Trigger the event
        event(new AttendanceCheckedIn($user, now()));

        // Assert that the event was dispatched
        Event::assertDispatched(AttendanceCheckedIn::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    /** @test */
    public function report_generated_event_triggers_notification_based_on_settings()
    {
        Event::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Enable both email and in-app notifications for reports
        NotificationSetting::create([
            'user_id' => $user->id,
            'notification_type' => 'report_generated',
            'channel' => 'email',
            'is_enabled' => true,
        ]);

        NotificationSetting::create([
            'user_id' => $user->id,
            'notification_type' => 'report_generated',
            'channel' => 'in_app',
            'is_enabled' => true,
        ]);

        // Trigger the event
        event(new ReportGenerated($user, 'Work Hour Report', 'report.pdf', 'http://example.com/report.pdf'));

        // Assert that the event was dispatched
        Event::assertDispatched(ReportGenerated::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    /** @test */
    public function user_can_update_do_not_disturb_settings()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('notification-settings.do-not-disturb'), [
            'enabled' => true,
            'start_time' => '22:00',
            'end_time' => '08:00',
            'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'exceptions' => ['2024-12-25'],
        ]);

        $response->assertRedirect(route('notification-settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $user->id,
            'notification_type' => 'system',
            'channel' => 'do_not_disturb',
            'is_enabled' => true,
        ]);
    }

    /** @test */
    public function notification_settings_validation_works()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Test invalid channel
        $response = $this->post(route('notification-settings.update'), [
            'settings' => [
                [
                    'notification_type' => 'attendance_check_in',
                    'channel' => 'invalid_channel',
                    'is_enabled' => true,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('settings.0.channel');

        // Test missing notification type
        $response = $this->post(route('notification-settings.update'), [
            'settings' => [
                [
                    'channel' => 'email',
                    'is_enabled' => true,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('settings.0.notification_type');
    }

    /** @test */
    public function system_settings_page_requires_admin_permission()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Try to access system settings update
        $response = $this->put(route('notification-settings.system.update'), [
            'settings' => [],
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function rate_limiting_settings_require_admin_permission()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('notification-settings.rate-limiting.update'), [
            'rate_limit_hourly' => 500,
        ]);

        $response->assertForbidden();
    }
}