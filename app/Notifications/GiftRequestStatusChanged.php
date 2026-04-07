<?php

namespace App\Notifications;

use App\Models\GiftRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GiftRequestStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected GiftRequest $giftRequest;
    protected ?string $note;

    public function __construct(GiftRequest $giftRequest, ?string $note = null)
    {
        $this->giftRequest = $giftRequest;
        $this->note = $note;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $gift = $this->giftRequest->gift;
        return (new MailMessage)
            ->subject(__('Gift request updated to :status', ['status' => ucfirst($this->giftRequest->status)]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? '']))
            ->line(__('Your gift request has been updated.'))
            ->line(__('Gift: :gift', ['gift' => optional($gift)->name]))
            ->line(__('Quantity: :qty', ['qty' => $this->giftRequest->quantity]))
            ->line(__('Status: :status', ['status' => ucfirst($this->giftRequest->status)]))
            ->when($this->note, fn ($msg) => $msg->line(__('Note: :note', ['note' => $this->note])))
            ->action(__('View requests'), route('gifts.requests'))
            ->line(__('Thank you for using our platform!'));
    }

    public function toArray($notifiable)
    {
        return [
            'gift_request_id' => $this->giftRequest->id,
            'gift_name' => optional($this->giftRequest->gift)->name,
            'quantity' => $this->giftRequest->quantity,
            'status' => $this->giftRequest->status,
            'note' => $this->note,
        ];
    }
}
