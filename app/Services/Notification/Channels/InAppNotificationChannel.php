<?php

namespace App\Services\Notification\Channels;

use App\Models\Notification;
use App\Models\User;

class InAppNotificationChannel implements NotificationChannel
{
    /**
     * Send the notification via in-app channel.
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

        // In-app notifications are already stored in the notifications table
        // The notification is already created, so we just need to mark it as sent
        // In a real implementation, you might trigger real-time events
        
        return true;
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
        return $notifiable instanceof User;
    }

    /**
     * Get the channel name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'in_app';
    }

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return 'database';
    }

    /**
     * Get the subject for the notification.
     *
     * @param Notification $notification
     * @return string|null
     */
    public function getSubject(Notification $notification): ?string
    {
        // In-app notifications don't have subjects
        return null;
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
        return isset($data['message']) ? $data['message'] : 'In-app notification';
    }
}