<?php

namespace App\Notifications;

use App\Models\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WalletRewardCredited extends Notification
{
    use Queueable;

    public $data;
    public $className;

    public function __construct($walletRewardData, $orderId = null)
    {
        // Backward compatibility: accept (amount, orderId) signature.
        if (!is_array($walletRewardData)) {
            $walletRewardData = [
                'amount' => $walletRewardData,
                'order_id' => $orderId,
            ];
        }

        // Ensure notification type id is present.
        $walletRewardData['notification_type_id'] = $walletRewardData['notification_type_id']
            ?? $this->resolveNotificationTypeId('wallet_reward_credited');

        $this->data = $walletRewardData;
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
                'amount'   => $this->data['amount'] ?? null,
                'order_id' => $this->data['order_id'] ?? null,
                'user_id'  => $this->data['user_id'] ?? null,
                'status'   => $this->data['status'] ?? null,
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
