<?php

namespace App\Services;

use App\Models\CreditPayment;
use App\Models\CreditUse;
use App\Models\User;
use App\Notifications\CreditNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Sends credit notifications. A failed notification (for example mail not set up) is logged
 * and never stops the credit change itself.
 */
class CreditNotifier
{
    public static function accountUpdated(User $user, array $before): void
    {
        $after = self::accountSnapshot($user);
        if ($before === $after) {
            return;
        }

        self::toCustomer($user, 'credit_account_updated', [
            'status' => $after['active'] ? translate('Active') : translate('Deactive'),
            'limit' => single_price($after['limit']),
            'days' => $after['days'],
        ]);
    }

    /**
     * Values a customer is told about when admin edits their credit.
     */
    public static function accountSnapshot(User $user): array
    {
        return [
            'active' => (bool) $user->creditIsActive(),
            'limit' => round((float) ($user->credit_limit ?? 0), 2),
            'days' => (int) ($user->credit_days ?? 0),
        ];
    }

    /**
     * A credit payment the customer made online, or one admin recorded with "Mark paid".
     */
    public static function paymentReceived(User $user, float $amount, ?CreditPayment $payment, bool $online): void
    {
        self::toCustomer($user, 'credit_payment_received', ['amount' => single_price($amount)]);

        if ($online) {
            self::toAdmins('credit_online_payment_admin', [
                'customer' => self::customerName($user),
                'amount' => single_price($amount),
                'purpose' => translate('Payment'),
            ], self::adminPaymentsLink($user));
        }
    }

    public static function topupReceived(User $user, CreditPayment $payment): void
    {
        self::toCustomer($user, 'credit_topup_received', ['amount' => single_price($payment->amount)]);

        self::toAdmins('credit_online_payment_admin', [
            'customer' => self::customerName($user),
            'amount' => single_price($payment->amount),
            'purpose' => translate('Top up'),
        ], self::adminPaymentsLink($user));
    }

    public static function dueStarted(CreditUse $use): void
    {
        if (!$use->user || !$use->due_on) {
            return;
        }

        self::toCustomer($use->user, 'credit_due_started', self::usePlaceholders($use));
    }

    public static function dueSoon(CreditUse $use): bool
    {
        if (!$use->user || !$use->due_on) {
            return false;
        }

        return self::toCustomer($use->user, 'credit_due_soon', self::usePlaceholders($use));
    }

    public static function overdue(CreditUse $use): bool
    {
        if (!$use->user || !$use->due_on) {
            return false;
        }

        $placeholders = self::usePlaceholders($use);
        $sent = self::toCustomer($use->user, 'credit_overdue', $placeholders);
        self::toAdmins('credit_overdue_admin', $placeholders + [
            'customer' => self::customerName($use->user),
        ], route('credits.index', ['use_status' => 'overdue', 'search' => $use->user->phone ?: $use->user->email]));

        return $sent;
    }

    /**
     * [[used]] credit taken on the order, [[amount]] still owed (less after a part payment),
     * [[used_on]] the order date, [[due_date]], and [[days_left]] (negative once overdue).
     */
    protected static function usePlaceholders(CreditUse $use): array
    {
        $daysLeft = $use->daysLeft();
        // The open row keeps its created_at through part payments, so this is when credit was taken.
        $usedOn = optional($use->created_at)->format('d-m-Y');

        return [
            'amount' => single_price($use->amount),
            'used' => single_price($use->usedOnOrder()),
            'order_code' => optional($use->order)->code ?: ('#' . $use->order_id),
            'used_on' => (string) $usedOn,
            'due_date' => optional($use->due_on)->format('d-m-Y'),
            'days_left' => $daysLeft === null ? '' : (string) $daysLeft,
            'days_overdue' => $daysLeft === null ? '' : (string) max(0, -$daysLeft),
        ];
    }

    protected static function toCustomer(User $user, string $event, array $placeholders): bool
    {
        return self::send($user, new CreditNotification($event, $placeholders, route('customer.credit.index')));
    }

    protected static function toAdmins(string $event, array $placeholders, string $link): void
    {
        try {
            $admins = User::query()->where('user_type', 'admin')->where('banned', 0)->get();
        } catch (\Throwable $e) {
            Log::warning('Credit notification: could not load admins. ' . $e->getMessage());
            return;
        }

        foreach ($admins as $admin) {
            self::send($admin, new CreditNotification($event, $placeholders, $link));
        }
    }

    protected static function send($notifiable, CreditNotification $notification): bool
    {
        try {
            Notification::send($notifiable, $notification);
            return true;
        } catch (\Throwable $e) {
            Log::warning('Credit notification ' . $notification->event . ' for user ' . ($notifiable->id ?? '?') . ' failed: ' . $e->getMessage());
            return false;
        }
    }

    protected static function customerName(User $user): string
    {
        return (string) (optional($user->details)->company_name ?: $user->name);
    }

    protected static function adminPaymentsLink(User $user): string
    {
        return route('credits.payments', ['search' => $user->phone ?: $user->email]);
    }
}
