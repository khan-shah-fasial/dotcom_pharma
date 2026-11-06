<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreditUse extends Model
{
    public const STATUS_RESERVED = 'reserved';

    public const STATUS_RELEASED = 'released';

    public const STATUS_PAID = 'paid';

    protected $table = 'credit_uses';

    protected $fillable = [
        'user_id',
        'combined_order_id',
        'order_id',
        'amount',
        'credit_days',
        'status',
        'started_on',
        'due_on',
        'released_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'credit_days' => 'integer',
        'started_on' => 'datetime',
        'due_on' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function combinedOrder()
    {
        return $this->belongsTo(CombinedOrder::class);
    }

    public static function tableReady(): bool
    {
        try {
            return Schema::hasTable('credit_uses');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function outstandingFor(User $user): float
    {
        if (!self::tableReady()) {
            return 0.0;
        }

        return round((float) self::query()
            ->where('user_id', $user->id)
            ->where('status', self::STATUS_RESERVED)
            ->sum('amount'), 2);
    }

    public static function availableFor(User $user): float
    {
        if (!$user->creditIsActive()) {
            return 0.0;
        }

        return max(0, round((float) ($user->credit_limit ?? 0) - self::outstandingFor($user), 2));
    }

    public static function customerCanUse(User $user): bool
    {
        return self::tableReady()
            && $user->creditIsActive()
            && (int) $user->credit_days >= 1
            && (float) ($user->credit_limit ?? 0) > 0
            && self::availableFor($user) > 0
            && !self::blocksPurchase($user);
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_RESERVED
            && $this->due_on !== null
            && $this->due_on->toDateString() < now()->toDateString();
    }

    public function statusLabel(): string
    {
        if ($this->isOverdue()) {
            return translate('Overdue');
        }
        if ($this->status === self::STATUS_RESERVED) {
            return translate('Reserved');
        }
        if ($this->status === self::STATUS_PAID) {
            return translate('Paid');
        }

        return translate('Released');
    }

    public function dueLabel(): string
    {
        if ($this->status === self::STATUS_PAID) {
            return translate('Paid');
        }
        if ($this->due_on) {
            return $this->due_on->format('d-m-Y');
        }
        if ($this->status === self::STATUS_RESERVED) {
            return translate('Starts on dispatch');
        }

        return '—';
    }

    public static function overdueFor(User $user): ?self
    {
        if (!self::tableReady()) {
            return null;
        }

        return self::query()
            ->where('user_id', $user->id)
            ->where('status', self::STATUS_RESERVED)
            ->whereNotNull('due_on')
            ->whereDate('due_on', '<', now()->toDateString())
            ->orderBy('due_on')
            ->first();
    }

    public static function blocksPurchase(User $user): bool
    {
        return self::overdueFor($user) !== null;
    }

    public static function purchaseBlockMessage(): string
    {
        return translate('Credit is overdue. Clear the outstanding credit before placing another order.');
    }

    public static function outstandingByUser($userIds): array
    {
        if (!self::tableReady()) {
            return [];
        }

        $ids = collect($userIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return self::query()
            ->select('user_id', DB::raw('SUM(amount) as outstanding'))
            ->whereIn('user_id', $ids)
            ->where('status', self::STATUS_RESERVED)
            ->groupBy('user_id')
            ->pluck('outstanding', 'user_id')
            ->map(function ($amount) {
                return round((float) $amount, 2);
            })
            ->all();
    }

    public static function amountDueNow($source): float
    {
        if ($source instanceof CombinedOrder) {
            $total = round((float) $source->grand_total, 2);
            if (!self::tableReady()) {
                return $total;
            }

            $credit = round((float) self::query()
                ->where('combined_order_id', $source->id)
                ->where('status', self::STATUS_RESERVED)
                ->sum('amount'), 2);

            return max(0, round($total - $credit, 2));
        }

        if ($source instanceof Order) {
            $total = round((float) $source->grand_total, 2);
            if (!self::tableReady()) {
                return $total;
            }

            $credit = round((float) self::query()
                ->where('order_id', $source->id)
                ->where('status', self::STATUS_RESERVED)
                ->sum('amount'), 2);

            return max(0, round($total - $credit, 2));
        }

        return 0.0;
    }

    public static function reserveForCombined(User $user, CombinedOrder $combined): float
    {
        if (!self::tableReady()) {
            return 0.0;
        }

        return (float) DB::transaction(function () use ($user, $combined) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->first();
            if (!$locked || !self::customerCanUse($locked)) {
                return 0.0;
            }

            $existing = round((float) self::query()
                ->where('combined_order_id', $combined->id)
                ->where('status', self::STATUS_RESERVED)
                ->sum('amount'), 2);
            if ($existing > 0) {
                return $existing;
            }

            $combined->loadMissing('orders');
            $remaining = self::availableFor($locked);
            $applied = 0.0;
            $days = (int) $locked->credit_days;

            foreach ($combined->orders as $order) {
                if ($remaining <= 0) {
                    break;
                }

                $orderTotal = round((float) $order->grand_total, 2);
                if ($orderTotal <= 0) {
                    continue;
                }

                $slice = round(min($remaining, $orderTotal), 2);
                if ($slice <= 0) {
                    continue;
                }

                self::query()->create([
                    'user_id' => $locked->id,
                    'combined_order_id' => $combined->id,
                    'order_id' => $order->id,
                    'amount' => $slice,
                    'credit_days' => $days,
                    'status' => self::STATUS_RESERVED,
                ]);

                if ($slice + 0.009 >= $orderTotal) {
                    $order->payment_type = 'credit';
                    $order->save();
                }

                $remaining = round($remaining - $slice, 2);
                $applied = round($applied + $slice, 2);
            }

            self::rememberAvailable($locked->fresh());

            return $applied;
        });
    }

    public static function dispatchedStatuses(): array
    {
        return ['picked_up', 'on_the_way', 'delivered'];
    }

    public static function openForOrder(int $orderId): ?self
    {
        if (!self::tableReady() || $orderId <= 0) {
            return null;
        }

        return self::query()
            ->where('order_id', $orderId)
            ->where('status', self::STATUS_RESERVED)
            ->orderByDesc('id')
            ->first();
    }

    public static function recordedForOrder(int $orderId): ?self
    {
        if (!self::tableReady() || $orderId <= 0) {
            return null;
        }

        return self::query()
            ->where('order_id', $orderId)
            ->whereIn('status', [self::STATUS_RESERVED, self::STATUS_PAID])
            ->orderByDesc('id')
            ->first();
    }

    public static function markPaid(self $use): bool
    {
        if (!self::tableReady() || !$use->id) {
            return false;
        }

        return (bool) DB::transaction(function () use ($use) {
            $owner = $use->user_id
                ? User::query()->whereKey($use->user_id)->lockForUpdate()->first()
                : null;

            $locked = self::query()->whereKey($use->id)->lockForUpdate()->first();
            if (!$locked || $locked->status !== self::STATUS_RESERVED) {
                return false;
            }

            $locked->status = self::STATUS_PAID;
            $locked->released_at = now();
            $locked->save();

            if ($owner) {
                self::rememberAvailable($owner);
            }

            return true;
        });
    }

    public static function startOnDispatch(Order $order): void
    {
        if (!self::tableReady() || !$order->id) {
            return;
        }

        if (!in_array((string) $order->delivery_status, self::dispatchedStatuses(), true)) {
            return;
        }

        $uses = self::query()
            ->where('order_id', $order->id)
            ->where('status', self::STATUS_RESERVED)
            ->whereNull('started_on')
            ->get();

        if ($uses->isEmpty()) {
            return;
        }

        $started = now();
        foreach ($uses as $use) {
            $use->started_on = $started->copy();
            $use->due_on = $started->copy()->addDays(max(0, (int) $use->credit_days));
            $use->save();
        }
    }

    public static function releaseForOrder(Order $order): void
    {
        if (!self::tableReady() || !$order->id) {
            return;
        }

        $uses = self::query()
            ->where('order_id', $order->id)
            ->where('status', self::STATUS_RESERVED)
            ->get();

        if ($uses->isEmpty()) {
            return;
        }

        foreach ($uses as $use) {
            $use->status = self::STATUS_RELEASED;
            $use->released_at = now();
            $use->save();
        }

        $owner = User::find($order->user_id);
        if ($owner) {
            self::rememberAvailable($owner);
        }
    }

    public static function releaseUnpaidPortalHold(): void
    {
        if (!self::tableReady()) {
            return;
        }

        $combinedId = session('combined_order_id');
        if (!$combinedId) {
            return;
        }

        $combined = CombinedOrder::with('orders')->find($combinedId);
        if (!$combined || $combined->orders->isEmpty()) {
            return;
        }

        if ($combined->orders->every(function ($order) {
            return (string) $order->payment_type === 'credit';
        })) {
            return;
        }

        if ($combined->orders->contains(function ($order) {
            return (string) $order->payment_status === 'paid';
        })) {
            return;
        }

        self::query()
            ->where('combined_order_id', $combined->id)
            ->where('status', self::STATUS_RESERVED)
            ->whereNull('started_on')
            ->update([
                'status' => self::STATUS_RELEASED,
                'released_at' => now(),
                'updated_at' => now(),
            ]);

        if ($combined->user) {
            self::rememberAvailable($combined->user);
        }
    }

    public static function rememberAvailable(User $user): void
    {
        if (!Schema::hasColumn('users', 'credit_remain')) {
            return;
        }

        $available = self::availableFor($user);
        if (round((float) $user->credit_remain, 2) === $available) {
            return;
        }

        $user->credit_remain = $available;
        $user->save();
    }
}
