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

    public const KIND_FACILITY = 'facility';

    public const KIND_PREPAID = 'prepaid';

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
        'kind',
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

    public static function prepaidColumnReady(): bool
    {
        try {
            return Schema::hasColumn('users', 'credit_prepaid');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function kindColumnReady(): bool
    {
        try {
            return self::tableReady() && Schema::hasColumn('credit_uses', 'kind');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function prepaidFor(User $user): float
    {
        if (!self::prepaidColumnReady()) {
            return 0.0;
        }

        return max(0, round((float) ($user->credit_prepaid ?? 0), 2));
    }

    /**
     * What credit covered on an order or combined order, split into the prepaid top up
     * (already paid, status paid) and the limit credit (still reserved, due after dispatch).
     */
    public static function appliedTo(string $column, int $id): array
    {
        $none = ['prepaid' => 0.0, 'facility' => 0.0, 'total' => 0.0];
        if (!in_array($column, ['order_id', 'combined_order_id'], true) || $id <= 0 || !self::tableReady()) {
            return $none;
        }

        $facility = round((float) self::query()
            ->where($column, $id)
            ->where('status', self::STATUS_RESERVED)
            ->sum('amount'), 2);
        $prepaid = self::kindColumnReady()
            ? round((float) self::query()
                ->where($column, $id)
                ->where('kind', self::KIND_PREPAID)
                ->where('status', self::STATUS_PAID)
                ->sum('amount'), 2)
            : 0.0;

        return ['prepaid' => $prepaid, 'facility' => $facility, 'total' => round($prepaid + $facility, 2)];
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
        if (!self::tableReady() || self::blocksPurchase($user)) {
            return false;
        }

        if (self::prepaidFor($user) > 0) {
            return true;
        }

        return $user->creditIsActive()
            && (int) $user->credit_days >= 1
            && (float) ($user->credit_limit ?? 0) > 0
            && self::availableFor($user) > 0;
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

    public static function visibleToCustomer(User $user): bool
    {
        if (self::prepaidFor($user) > 0) {
            return true;
        }

        if ($user->creditIsActive() || (float) ($user->credit_limit ?? 0) > 0 || (int) ($user->credit_days ?? 0) > 0) {
            return true;
        }

        return self::tableReady() && self::outstandingFor($user) > 0;
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

            $credit = round((float) self::appliedQuery()
                ->where('combined_order_id', $source->id)
                ->sum('amount'), 2);

            return max(0, round($total - $credit, 2));
        }

        if ($source instanceof Order) {
            $total = round((float) $source->grand_total, 2);
            if (!self::tableReady()) {
                return $total;
            }

            $credit = round((float) self::appliedQuery()
                ->where('order_id', $source->id)
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

            $existing = round((float) self::appliedQuery()
                ->where('combined_order_id', $combined->id)
                ->sum('amount'), 2);
            if ($existing > 0) {
                return $existing;
            }

            $combined->loadMissing('orders');
            $prepaidLeft = self::prepaidFor($locked);
            $facilityLeft = self::availableFor($locked);
            $applied = 0.0;
            $prepaidUsed = 0.0;
            $days = (int) $locked->credit_days;
            $hasKind = self::kindColumnReady();

            foreach ($combined->orders as $order) {
                $left = round((float) $order->grand_total, 2);
                if ($left <= 0) {
                    continue;
                }

                if ($hasKind && $prepaidLeft > 0) {
                    $slice = round(min($prepaidLeft, $left), 2);
                    if ($slice > 0) {
                        self::query()->create([
                            'user_id' => $locked->id,
                            'combined_order_id' => $combined->id,
                            'order_id' => $order->id,
                            'amount' => $slice,
                            'credit_days' => 0,
                            'status' => self::STATUS_PAID,
                            'kind' => self::KIND_PREPAID,
                        ]);
                        $prepaidLeft = round($prepaidLeft - $slice, 2);
                        $prepaidUsed = round($prepaidUsed + $slice, 2);
                        $left = round($left - $slice, 2);
                        $applied = round($applied + $slice, 2);
                    }
                }

                if ($facilityLeft > 0 && $left > 0) {
                    $slice = round(min($facilityLeft, $left), 2);
                    $row = [
                        'user_id' => $locked->id,
                        'combined_order_id' => $combined->id,
                        'order_id' => $order->id,
                        'amount' => $slice,
                        'credit_days' => $days,
                        'status' => self::STATUS_RESERVED,
                    ];
                    if ($hasKind) {
                        $row['kind'] = self::KIND_FACILITY;
                    }
                    self::query()->create($row);
                    $facilityLeft = round($facilityLeft - $slice, 2);
                    $left = round($left - $slice, 2);
                    $applied = round($applied + $slice, 2);
                }

                if ($left <= 0.009) {
                    $order->payment_type = 'credit';
                    $order->save();
                }
            }

            if ($prepaidUsed > 0 && self::prepaidColumnReady()) {
                $locked->credit_prepaid = round(max(0, (float) $locked->credit_prepaid - $prepaidUsed), 2);
                $locked->save();
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

        self::releaseUses(
            self::appliedQuery()->where('order_id', $order->id)->get(),
            User::find($order->user_id)
        );
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

        self::releaseUses(
            self::appliedQuery()
                ->where('combined_order_id', $combined->id)
                ->whereNull('started_on')
                ->get(),
            $combined->user_id ? User::find($combined->user_id) : null
        );
    }

    protected static function appliedQuery()
    {
        return self::query()->where(function ($query) {
            $query->where('status', self::STATUS_RESERVED);
            if (self::kindColumnReady()) {
                $query->orWhere(function ($inner) {
                    $inner->where('kind', self::KIND_PREPAID)
                        ->where('status', self::STATUS_PAID);
                });
            }
        });
    }

    protected static function releaseUses($uses, ?User $owner): void
    {
        if ($uses->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($uses, $owner) {
            $locked = $owner
                ? User::query()->whereKey($owner->id)->lockForUpdate()->first()
                : null;
            $prepaidBack = 0.0;

            foreach ($uses as $use) {
                $fresh = self::query()->whereKey($use->id)->lockForUpdate()->first();
                if (!$fresh || $fresh->status === self::STATUS_RELEASED) {
                    continue;
                }

                $isPrepaid = self::kindColumnReady()
                    && (string) $fresh->kind === self::KIND_PREPAID
                    && $fresh->status === self::STATUS_PAID;
                if ($isPrepaid) {
                    $prepaidBack = round($prepaidBack + (float) $fresh->amount, 2);
                }

                $fresh->status = self::STATUS_RELEASED;
                $fresh->released_at = now();
                $fresh->save();
            }

            if ($locked && $prepaidBack > 0 && self::prepaidColumnReady()) {
                $locked->credit_prepaid = round((float) $locked->credit_prepaid + $prepaidBack, 2);
                $locked->save();
            }

            if ($locked) {
                self::rememberAvailable($locked->fresh());
            }
        });
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
