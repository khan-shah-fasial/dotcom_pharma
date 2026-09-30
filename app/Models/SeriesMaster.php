<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeriesMaster extends Model
{
    public const PAYMENT_TYPES = [
        'cash' => 'Cash',
        'credit' => 'Credit',
    ];

    protected $table = 'series_masters';

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'payment_type',
        'payment_type_custom',
        'total_bills',
        'from_bill_no',
        'to_bill_no',
    ];

    public function paymentTypeLabel(): string
    {
        $custom = trim((string) ($this->payment_type_custom ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        $value = trim((string) $this->payment_type);
        if ($value === '') {
            return '-';
        }
        if (isset(self::PAYMENT_TYPES[$value])) {
            return translate(self::PAYMENT_TYPES[$value]);
        }

        return $value;
    }
}
