<?php

namespace App\Notifications;

use App\Models\GiftRequest;
use App\Models\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
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
        return [DbNotification::class, 'mail'];
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

    public function toMail($notifiable)
    {
        $status = ucfirst(str_replace('_', ' ', (string) ($this->data['status'] ?? 'updated')));
        $giftName = $this->data['gift_name'] ?? __('your gift');
        $quantity = $this->data['quantity'] ?? 1;
        $requestId = $this->data['gift_request_id'] ?? '-';
        $note = $this->data['note'] ?? null;

        $mail = (new MailMessage)
            ->subject(__('Gift Request Status Updated'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? '']))
            ->line(__('Your gift request status has been updated.'))
            ->line(__('Gift: :gift', ['gift' => $giftName]))
            ->line(__('Quantity: :quantity', ['quantity' => $quantity]))
            ->line(__('Request ID: #:id', ['id' => $requestId]))
            ->line(__('Status: :status', ['status' => $status]));

        if (!empty($note)) {
            $mail->line(__('Note: :note', ['note' => $note]));
        }

        return $mail
            ->action(__('View My Gift Requests'), route('gifts.requests'))
            ->salutation(__('Thanks for shopping with us!'));
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
