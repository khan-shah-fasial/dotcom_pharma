<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CreditPayment extends Model
{
    public const PURPOSE_TOPUP = 'topup';

    public const PURPOSE_SETTLE = 'settle';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $table = 'credit_payments';

    protected $fillable = [
        'user_id',
        'purpose',
        'amount',
        'payment_method',
        'payment_details',
        'credit_use_ids',
        'status',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function tableReady(): bool
    {
        try {
            return Schema::hasTable('credit_payments');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function purposeLabel(): string
    {
        if ($this->purpose === self::PURPOSE_TOPUP) {
            return translate('Top up');
        }

        if ($this->purpose === self::PURPOSE_SETTLE) {
            return translate('Payment');
        }

        return translate('Credit');
    }
}
