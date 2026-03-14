<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The notification instance.
     *
     * @var Notification
     */
    protected $notification;

    /**
     * The channels to send through.
     *
     * @var array
     */
    protected $channels;

    /**
     * Create a new job instance.
     *
     * @param Notification $notification
     * @param array $channels
     */
    public function __construct(Notification $notification, array $channels)
    {
        $this->notification = $notification;
        $this->channels = $channels;
    }

    /**
     * Execute the job.
     *
     * @param NotificationService $service
     * @return void
     */
    public function handle(NotificationService $service): void
    {
        $service->process($this->notification, $this->channels);
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        // Log the failure
        \Log::error('ProcessNotificationJob failed: ' . $exception->getMessage(), [
            'notification_id' => $this->notification->id,
            'channels' => $this->channels,
            'exception' => $exception->getTraceAsString(),
        ]);

        // Update notification logs with failure
        foreach ($this->channels as $channelName) {
            \App\Models\NotificationLog::create([
                'notification_id' => $this->notification->id,
                'channel' => $channelName,
                'provider' => 'unknown',
                'recipient' => 'unknown',
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
