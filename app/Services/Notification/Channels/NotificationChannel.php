<?php

namespace App\Services\Notification\Channels;

use App\Models\Notification;
use App\Models\User;

interface NotificationChannel
{
    /**
     * Send the notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return bool
     */
    public function send($notifiable, Notification $notification): bool;

    /**
     * Check if the channel can send the notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return bool
     */
    public function canSend($notifiable, Notification $notification): bool;

    /**
     * Get the channel name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function getProviderName(): string;

    /**
     * Get the subject for the notification.
     *
     * @param Notification $notification
     * @return string|null
     */
    public function getSubject(Notification $notification): ?string;

    /**
     * Get a content preview for logging.
     *
     * @param Notification $notification
     * @return string|null
     */
    public function getContentPreview(Notification $notification): ?string;
}