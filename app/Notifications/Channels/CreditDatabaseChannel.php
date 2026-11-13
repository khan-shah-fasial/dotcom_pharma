<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;

/**
 * Saves a credit notification to the notifications table.
 * Unlike DbNotification it does not read the logged in user, so it also works from the daily credit job.
 */
class CreditDatabaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        $payload = $notification->toArray($notifiable);
        if (empty($payload['notification_type_id'])) {
            return null;
        }

        return $notifiable->routeNotificationFor('database')->create([
            'id' => $notification->id,
            'notification_type_id' => $payload['notification_type_id'],
            'type' => get_class($notification),
            'data' => $payload['data'],
            'read_at' => null,
        ]);
    }
}
