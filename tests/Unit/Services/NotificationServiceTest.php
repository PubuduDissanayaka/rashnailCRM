<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\NotificationProvider;
use App\Models\EmailTemplate;
use App\Services\NotificationService;
use App\Services\Notification\Channels\EmailNotificationChannel;
use App\Services\Notification\Channels\InAppNotificationChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessNotificationJob;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = app(NotificationService::class);
    }

    /** @test */
    public function it_can_send_notification_to_user()
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = EmailTemplate::factory()->create([
            'name' => 'Test Template',
            'subject' => 'Test Subject',
            'content' => 'Hello {{name}}!',
        ]);

        $notification = $this->notificationService->sendNotification(
            $user,
            'test_notification',
            ['name' => 'John Doe'],
            ['email', 'in_app']
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($user->id, $notification->user_id);
        $this->assertEquals('test_notification', $notification->notification_type);

        Queue::assertPushed(ProcessNotificationJob::class, function ($job) use ($notification) {
            return $job->notification->id === $notification->id;
        });
    }

    /** @test */
    public function it_respects_user_notification_settings()
    {
        $user = User::factory()->create();

        // User has disabled email notifications for this type
        NotificationSetting::create([
            'user_id' => $user->id,
            'notification_type' => 'test_notification',
            'channel' => 'email',
            'is_enabled' => false,
        ]);

        // User has enabled in-app notifications
        NotificationSetting::create([
            'user_id' => $user->id,
            'notification_type' => 'test_notification',
            'channel' => 'in_app',
            'is_enabled' => true,
        ]);

        $notification = $this->notificationService->sendNotification(
            $user,
            'test_notification',
            [],
            ['email', 'in_app']
        );

        // Only in-app channel should be used
        $this->assertCount(1, $notification->channels);
        $this->assertEquals('in_app', $notification->channels[0]);
    }

    /** @test */
    public function it_uses_system_defaults_when_user_settings_not_found()
    {
        $user = User::factory()->create();

        // Create system default for this notification type
        NotificationSetting::create([
            'user_id' => null,
            'notification_type' => 'test_notification',
            'channel' => 'email',
            'is_enabled' => true,
        ]);

        NotificationSetting::create([
            'user_id' => null,
            'notification_type' => 'test_notification',
            'channel' => 'in_app',
            'is_enabled' => false,
        ]);

        $notification = $this->notificationService->sendNotification(
            $user,
            'test_notification',
            [],
            ['email', 'in_app']
        );

        // Should use system defaults: email enabled, in_app disabled
        $this->assertCount(1, $notification->channels);
        $this->assertEquals('email', $notification->channels[0]);
    }

    /** @test */
    public function it_handles_template_variable_substitution()
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $template = EmailTemplate::factory()->create([
            'name' => 'Welcome Template',
            'subject' => 'Welcome {{name}}!',
            'content' => 'Hello {{name}}, welcome to our system!',
        ]);

        $notification = $this->notificationService->sendNotification(
            $user,
            'welcome_message',
            ['name' => $user->name],
            ['email']
        );

        $this->assertStringContainsString('John Doe', $notification->subject);
        $this->assertStringContainsString('John Doe', $notification->content);
    }

    /** @test */
    public function it_can_send_bulk_notifications()
    {
        Queue::fake();

        $users = User::factory()->count(3)->create();
        $userIds = $users->pluck('id')->toArray();

        $result = $this->notificationService->sendBulkNotifications(
            $userIds,
            'system_announcement',
            ['message' => 'System maintenance scheduled'],
            ['email', 'in_app']
        );

        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(Notification::class, $result);

        Queue::assertPushed(ProcessNotificationJob::class, 3);
    }

    /** @test */
    public function it_handles_notification_with_attachments()
    {
        $user = User::factory()->create();

        $notification = $this->notificationService->sendNotification(
            $user,
            'report_generated',
            [
                'report_name' => 'Monthly Report',
                'download_url' => 'http://example.com/report.pdf',
            ],
            ['email'],
            [
                'attachments' => [
                    [
                        'name' => 'report.pdf',
                        'url' => 'http://example.com/report.pdf',
                        'mime_type' => 'application/pdf',
                    ],
                ],
            ]
        );

        $this->assertArrayHasKey('attachments', $notification->metadata);
        $this->assertCount(1, $notification->metadata['attachments']);
    }

    /** @test */
    public function it_respects_do_not_disturb_settings()
    {
        $user = User::factory()->create();

        // Enable Do Not Disturb for the user
        NotificationSetting::create([
            'user_id' => $user->id,
            'notification_type' => 'system',
            'channel' => 'do_not_disturb',
            'is_enabled' => true,
            'preferences' => [
                'enabled' => true,
                'start_time' => '22:00',
                'end_time' => '08:00',
                'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            ],
        ]);

        // Mock current time to be within DND hours
        $this->travelTo(now()->setHour(23)->setMinute(0));

        $notification = $this->notificationService->sendNotification(
            $user,
            'test_notification',
            [],
            ['email', 'in_app']
        );

        // Notification should be scheduled for later, not sent immediately
        $this->assertNotNull($notification->scheduled_at);
        $this->assertGreaterThan(now(), $notification->scheduled_at);
    }

    /** @test */
    public function it_can_retry_failed_notifications()
    {
        $notification = Notification::factory()->create([
            'status' => 'failed',
            'retry_count' => 0,
        ]);

        $result = $this->notificationService->retryNotification($notification->id);

        $this->assertTrue($result);
        $this->assertEquals('pending', $notification->fresh()->status);
        $this->assertEquals(1, $notification->fresh()->retry_count);
    }

    /** @test */
    public function it_does_not_retry_notifications_exceeding_max_attempts()
    {
        $notification = Notification::factory()->create([
            'status' => 'failed',
            'retry_count' => 5, // Exceeds max attempts
        ]);

        $result = $this->notificationService->retryNotification($notification->id);

        $this->assertFalse($result);
        $this->assertEquals('failed', $notification->fresh()->status);
    }

    /** @test */
    public function it_can_get_notification_statistics()
    {
        // Create notifications with different statuses
        Notification::factory()->count(3)->create(['status' => 'sent']);
        Notification::factory()->count(2)->create(['status' => 'failed']);
        Notification::factory()->count(1)->create(['status' => 'pending']);

        $stats = $this->notificationService->getStatistics();

        $this->assertEquals(6, $stats['total']);
        $this->assertEquals(3, $stats['sent']);
        $this->assertEquals(2, $stats['failed']);
        $this->assertEquals(1, $stats['pending']);
        $this->assertEquals(50.0, $stats['success_rate']); // 3/6 = 50%
    }

    /** @test */
    public function it_can_get_user_notification_preferences()
    {
        $user = User::factory()->create();

        // Create user settings
        NotificationSetting::create([
            'user_id' => $user->id,
            'notification_type' => 'attendance_check_in',
            'channel' => 'email',
            'is_enabled' => true,
        ]);

        NotificationSetting::create([
            'user_id' => $user->id,
            'notification_type' => 'attendance_check_in',
            'channel' => 'in_app',
            'is_enabled' => false,
        ]);

        $preferences = $this->notificationService->getUserPreferences($user->id, 'attendance_check_in');

        $this->assertArrayHasKey('channels', $preferences);
        $this->assertTrue($preferences['channels']['email']['enabled']);
        $this->assertFalse($preferences['channels']['in_app']['enabled']);
    }

    /** @test */
    public function it_handles_missing_template_gracefully()
    {
        $user = User::factory()->create();

        $notification = $this->notificationService->sendNotification(
            $user,
            'non_existent_template',
            [],
            ['email']
        );

        $this->assertNotNull($notification);
        $this->assertEquals('non_existent_template', $notification->notification_type);
        // Should use default subject and content
        $this->assertNotEmpty($notification->subject);
        $this->assertNotEmpty($notification->content);
    }

    /** @test */
    public function it_can_check_notification_delivery_status()
    {
        $notification = Notification::factory()->create([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $status = $this->notificationService->getDeliveryStatus($notification->id);

        $this->assertEquals('sent', $status['status']);
        $this->assertNotNull($status['sent_at']);
        $this->assertTrue($status['delivered']);
    }
}