<?php

namespace App\Notifications;

use App\Models\NotificationType;
use App\Notifications\Channels\CreditDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One notification class for every credit event. The event is a notification_types.type
 * (see sqlupdates/credit_notifications.sql); its default_text holds [[placeholders]].
 */
class CreditNotification extends Notification
{
    use Queueable;

    public $event;

    public $placeholders;

    public $link;

    protected $notificationType = false;

    public function __construct(string $event, array $placeholders = [], ?string $link = null)
    {
        $this->event = $event;
        $this->placeholders = array_map(function ($value) {
            return (string) $value;
        }, $placeholders);
        $this->link = $link;
    }

    public function via($notifiable)
    {
        $type = $this->notificationType();
        if (!$type || (string) $type->status !== '1') {
            // Not set up yet, or switched off in Notification Types.
            return [];
        }

        $channels = [CreditDatabaseChannel::class];
        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray($notifiable)
    {
        $type = $this->notificationType();

        return [
            'notification_type_id' => $type ? $type->id : null,
            'data' => [
                'event' => $this->event,
                'placeholders' => $this->placeholders,
                'link' => $this->link,
            ],
        ];
    }

    public function toMail($notifiable)
    {
        $type = $this->notificationType();
        $text = $type ? self::fill($type->getTranslation('default_text'), ['placeholders' => $this->placeholders], false) : '';

        $mail = (new MailMessage)
            ->subject($type ? $type->getTranslation('name') : translate('Credit update'))
            ->greeting(translate('Hello') . ' ' . ($notifiable->name ?? '') . ',')
            ->line($text);

        if ($this->link) {
            $mail->action(translate('View credit'), $this->link);
        }

        return $mail;
    }

    /**
     * Puts the saved values into a notification text. Values are escaped because the views print it as HTML.
     */
    public static function fill(?string $template, $data, bool $escape = true): string
    {
        $text = (string) $template;
        $placeholders = is_array($data) ? ($data['placeholders'] ?? []) : [];
        foreach ($placeholders as $key => $value) {
            $text = str_replace('[[' . $key . ']]', $escape ? e($value) : (string) $value, $text);
        }

        return $text;
    }

    protected function notificationType()
    {
        if ($this->notificationType === false) {
            try {
                $this->notificationType = NotificationType::query()->where('type', $this->event)->first();
            } catch (\Throwable $e) {
                $this->notificationType = null;
            }
        }

        return $this->notificationType;
    }
}
