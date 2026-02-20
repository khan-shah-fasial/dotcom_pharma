<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductRestockNotification extends Notification
{
    use Queueable;

    public $data;
    public $className;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->className = ProductRestockNotification::class;
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
                'product_id'     => $this->data['product_id'],
                'product_slug'   => $this->data['product_slug'],
                'product_name'   => $this->data['product_name'],
                'variant_count'  => $this->data['variant_count'],
                'variant_names'  => $this->data['variant_names'],
                'link'           => $this->data['link'] ?? null,
            ]
        ];
    }
}
