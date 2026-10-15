<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class HsnMaster extends Model
{
    public const PERMISSIONS = [
        'view_all_hsn_masters',
        'add_hsn_master',
        'edit_hsn_master',
        'delete_hsn_master',
    ];

    protected $table = 'hsn_masters';

    protected $fillable = [
        'hsn_code',
        'description',
        'taxable',
        'tax_type',
        'tax_percent',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public static function tableReady(): bool
    {
        try {
            return Schema::hasTable('hsn_masters');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function digits($hsn): string
    {
        return preg_replace('/\D/', '', (string) $hsn) ?? '';
    }

    public static function displayRate($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return TaxMaster::formatRate($value);
    }

    public function kindLabel(): string
    {
        $type = (string) ($this->tax_type ?? '');

        return TaxMaster::KINDS[$type] ?? '';
    }

    public static function findByDigits(string $digits, $ignoreId = null): ?self
    {
        $digits = self::digits($digits);
        if ($digits === '' || !self::tableReady()) {
            return null;
        }

        return self::query()
            ->where('hsn_code', $digits)
            ->when($ignoreId, function ($query) use ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            })
            ->first();
    }

    public static function normalize(array $input): array
    {
        $digits = self::digits($input['hsn_code'] ?? '');
        $description = trim((string) ($input['description'] ?? ''));
        if (mb_strlen($description) > 255) {
            $description = mb_substr($description, 0, 255);
        }

        $taxable = TaxMaster::isTruthy($input['taxable'] ?? false) ? 1 : 0;
        $taxType = (string) ($input['tax_type'] ?? '');
        if (!array_key_exists($taxType, TaxMaster::KINDS)) {
            $taxType = 'taxable';
        }

        $taxPercent = $taxable === 0
            ? 0.0
            : TaxMaster::toDecimal($input['tax_percent'] ?? 0);

        $status = array_key_exists('status', $input)
            ? (TaxMaster::isTruthy($input['status']) ? 1 : 0)
            : 1;

        return [
            'hsn_code' => $digits,
            'description' => $description === '' ? null : $description,
            'taxable' => $taxable,
            'tax_type' => $taxType,
            'tax_percent' => $taxPercent,
            'status' => $status,
        ];
    }

    public static function lookupByHsn(string $hsn): ?array
    {
        $digits = self::digits($hsn);
        if ($digits === '' || !self::tableReady()) {
            return null;
        }

        $row = self::query()
            ->where('status', 1)
            ->where(function ($query) use ($hsn, $digits) {
                $query->where('hsn_code', $digits);
                $typed = trim($hsn);
                if ($typed !== '' && $typed !== $digits) {
                    $query->orWhere('hsn_code', $typed);
                }
                $query->orWhereRaw(
                    "REPLACE(REPLACE(REPLACE(hsn_code, '.', ''), '-', ''), ' ', '') = ?",
                    [$digits]
                );
            })
            ->orderByRaw('CHAR_LENGTH(hsn_code) DESC')
            ->orderByDesc('id')
            ->first();

        if (!$row) {
            return null;
        }

        $label = $row->kindLabel();
        $percent = $row->tax_percent === null ? '' : self::displayRate($row->tax_percent);

        return [
            'found' => true,
            'id' => $row->id,
            'kind' => (string) ($row->tax_type ?? ''),
            'kind_label' => $label,
            'tax_percent' => $percent,
            'display_tax_percent' => $percent,
            'tax_code' => '',
            'description' => (string) ($row->description ?? ''),
            'hs_code' => (string) (TaxMaster::hsFromHsn($row->hsn_code) ?? ''),
            'hsn_code' => (string) $row->hsn_code,
            'taxable' => $row->taxable,
        ];
    }

    public static function sortableColumns(): array
    {
        return [
            'id' => 'hsn_masters.id',
            'hsn_code' => 'hsn_masters.hsn_code',
            'description' => 'hsn_masters.description',
            'taxable' => 'hsn_masters.taxable',
            'tax_type' => 'hsn_masters.tax_type',
            'tax_percent' => 'hsn_masters.tax_percent',
            'status' => 'hsn_masters.status',
            'updated_at' => 'hsn_masters.updated_at',
        ];
    }

    public static function resolveSort(string $sortBy, string $sortDir): array
    {
        $columns = self::sortableColumns();
        if (!array_key_exists($sortBy, $columns)) {
            $sortBy = 'id';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        return [$sortBy, $sortDir, $columns[$sortBy]];
    }

    public static function applyListingFilters($query, array $filters): void
    {
        if (($filters['search'] ?? '') !== '') {
            $like = '%' . $filters['search'] . '%';
            $query->where(function ($nested) use ($like, $filters) {
                $nested->where('hsn_code', 'like', $like)
                    ->orWhere('description', 'like', $like);
                if (ctype_digit($filters['search'])) {
                    $nested->orWhere('id', (int) $filters['search']);
                }
            });
        }

        if (($filters['hsn_code'] ?? '') !== '') {
            $query->where('hsn_code', 'like', '%' . self::digits($filters['hsn_code']) . '%');
        }

        if (($filters['description'] ?? '') !== '') {
            $query->where('description', 'like', '%' . $filters['description'] . '%');
        }

        if (($filters['taxable'] ?? '') === '1' || ($filters['taxable'] ?? '') === '0') {
            $query->where('taxable', (int) $filters['taxable']);
        } elseif (($filters['taxable'] ?? '') === 'unset') {
            $query->whereNull('taxable');
        }

        if (array_key_exists($filters['tax_type'] ?? '', TaxMaster::KINDS)) {
            $query->where('tax_type', $filters['tax_type']);
        } elseif (($filters['tax_type'] ?? '') === 'unset') {
            $query->whereNull('tax_type');
        }

        if (($filters['tax_percent_from'] ?? '') !== '') {
            $query->where('tax_percent', '>=', TaxMaster::toDecimal($filters['tax_percent_from']));
        }
        if (($filters['tax_percent_to'] ?? '') !== '') {
            $query->where('tax_percent', '<=', TaxMaster::toDecimal($filters['tax_percent_to']));
        }

        if (($filters['status'] ?? '') === '1' || ($filters['status'] ?? '') === '0') {
            $query->where('status', (int) $filters['status']);
        }
    }
}
