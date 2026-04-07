<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftRequest extends Model
{
    protected $table = 'tbl_gift_requests';

    protected $fillable = [
        'user_id',
        'gift_id',
        'quantity',
        'cost_snapshot',
        'status',
        'idempotency_key',
        'processed_by',
        'processed_at',
        'refund_txn_id',
        'admin_note',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'processed_at' => 'datetime',
    ];

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'delivered' => 'primary',
            default => 'info',
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gift()
    {
        return $this->belongsTo(Gift::class, 'gift_id');
    }
}
