<?php

namespace App\Models;

use App\Support\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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

    public static function normalizeCode(?string $code): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $code) ?? '');

        return substr($normalized, 0, 5);
    }

    public static function codeForName(?string $name): string
    {
        $name = strtolower(trim((string) $name));
        if ($name === '' || !Schema::hasTable('series_masters')) {
            return '';
        }

        $query = static::query()->whereRaw('LOWER(TRIM(name)) = ?', [$name]);
        if (Schema::hasColumn('series_masters', 'status')) {
            $query->where('status', 1);
        }

        foreach ($query->orderBy('id')->get(['code']) as $row) {
            $code = self::normalizeCode($row->code);
            if ($code !== '') {
                return $code;
            }
        }

        return '';
    }

    public static function codeForInvoiceType($documentType, $custom = null): string
    {
        $documentType = trim((string) $documentType);
        $custom = trim((string) $custom);

        if ($documentType === '__not_in_list__') {
            return self::codeForName($custom);
        }

        if ($documentType !== '' && isset(DocumentType::TYPES[$documentType])) {
            return self::codeForName(DocumentType::TYPES[$documentType]);
        }

        if ($custom !== '') {
            return self::codeForName($custom);
        }

        return '';
    }

    public static function codesByName(): array
    {
        if (!Schema::hasTable('series_masters')) {
            return [];
        }

        $query = static::query();
        if (Schema::hasColumn('series_masters', 'status')) {
            $query->where('status', 1);
        }

        $map = [];
        foreach ($query->orderBy('id')->get(['name', 'code']) as $row) {
            $name = strtolower(trim((string) $row->name));
            $code = self::normalizeCode($row->code);
            if ($name === '' || $code === '' || isset($map[$name])) {
                continue;
            }
            $map[$name] = $code;
        }

        return $map;
    }
}
