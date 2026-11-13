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

    // Pending gateway payments older than this are marked failed by credit:daily.
    public const PENDING_EXPIRY_HOURS = 24;

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

    public static function purposes(): array
    {
        return [self::PURPOSE_TOPUP, self::PURPOSE_SETTLE];
    }

    public static function statuses(): array
    {
        return [self::STATUS_PENDING, self::STATUS_COMPLETED, self::STATUS_FAILED];
    }

    /**
     * Ways admin can record money received outside the payment gateway.
     */
    public static function offlineMethods(): array
    {
        return [
            'cash' => translate('Cash'),
            'bank_transfer' => translate('Bank transfer'),
            'cheque' => translate('Cheque'),
            'upi' => translate('UPI'),
            'other' => translate('Other'),
        ];
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

    public function statusLabel(): string
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return translate('Completed');
        }

        if ($this->status === self::STATUS_FAILED) {
            return translate('Failed');
        }

        return translate('Pending');
    }

    public function methodLabel(): string
    {
        $method = (string) $this->payment_method;
        if ($method === '') {
            return '-';
        }

        $offline = self::offlineMethods();
        if (array_key_exists($method, $offline)) {
            return $offline[$method];
        }

        return ucfirst(str_replace('_', ' ', translate($method)));
    }

    public function detailsArray(): array
    {
        $decoded = json_decode((string) $this->payment_details, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function recordedByAdmin(): bool
    {
        return ($this->detailsArray()['source'] ?? null) === 'admin';
    }

    public function reference(): string
    {
        $details = $this->detailsArray();
        if ($this->recordedByAdmin()) {
            return (string) ($details['reference'] ?? '');
        }

        // Gateway payments: Razorpay stores id, Payumoney stores txnid / mihpayid.
        return (string) ($details['id'] ?? $details['mihpayid'] ?? $details['txnid'] ?? '');
    }

    public function note(): string
    {
        return $this->recordedByAdmin() ? (string) ($this->detailsArray()['note'] ?? '') : '';
    }

    public function recordedByName(): string
    {
        return $this->recordedByAdmin() ? (string) ($this->detailsArray()['recorded_by_name'] ?? '') : '';
    }

    /**
     * payment_details for a record entered by admin.
     */
    public static function adminDetails(?string $reference, ?string $note): string
    {
        $admin = auth()->user();

        return json_encode([
            'source' => 'admin',
            'recorded_by' => $admin ? $admin->id : null,
            'recorded_by_name' => $admin ? $admin->name : null,
            'reference' => trim((string) $reference),
            'note' => trim((string) $note),
        ]);
    }
}
