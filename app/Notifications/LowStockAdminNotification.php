<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockAdminNotification extends Notification
{
    use Queueable;

    public $data;
    public $className;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->className = LowStockAdminNotification::class;
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
                'product_id' => $this->data['product_id'],
                'product_name' => $this->data['product_name'],
                'product_stock_id' => $this->data['product_stock_id'] ?? null,
                'variant_name' => $this->data['variant_name'] ?? null,
                'batch_id' => $this->data['batch_id'] ?? null,
                'batch_name' => $this->data['batch_name'] ?? null,
                'stock_count' => $this->data['stock_count'],
                'low_stock_quantity' => $this->data['low_stock_quantity'],
                'link' => $this->data['link'] ?? null,
            ],
        ];
    }
}
