<?php

namespace App\Notifications;

use App\Models\GiftRequest;
use App\Models\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GiftRequestStatusChanged extends Notification
{
    use Queueable;

    public $data;
    public $className;

    /**
     * Accepts either a GiftRequest model (legacy usage) or an array payload.
     */
    public function __construct($giftRequestData, ?string $note = null)
    {
        if ($giftRequestData instanceof GiftRequest) {
            $giftRequestData = [
                'gift_request_id' => $giftRequestData->id,
                'gift_name'       => optional($giftRequestData->gift)->name,
                'quantity'        => $giftRequestData->quantity,
                'status'          => $giftRequestData->status,
                'note'            => $note,
            ];
        }

        if (!is_array($giftRequestData)) {
            $giftRequestData = [];
        }

        // Ensure notification type id is present.
        $giftRequestData['notification_type_id'] = $giftRequestData['notification_type_id']
            ?? $this->resolveNotificationTypeId('gift_request_status_changed');

        $this->data = $giftRequestData;
        $this->className = self::class;
    }

    public function via($notifiable)
    {
        return [DbNotification::class];
    }

    public function toArray($notifiable)
    {
        return [
            'notification_type_id' => $this->data['notification_type_id'],
            'data' => [
                'gift_request_id' => $this->data['gift_request_id'] ?? null,
                'gift_name'       => $this->data['gift_name'] ?? null,
                'quantity'        => $this->data['quantity'] ?? null,
                'status'          => $this->data['status'] ?? null,
                'note'            => $this->data['note'] ?? null,
            ],
        ];
    }

    /**
     * Resolve notification type id without relying on a global helper.
     */
    protected function resolveNotificationTypeId(string $type): ?int
    {
        if (function_exists('get_notification_type')) {
            return optional(get_notification_type($type, 'type'))->id;
        }

        return NotificationType::where('type', $type)->value('id');
    }
}
