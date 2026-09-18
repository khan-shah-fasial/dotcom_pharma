<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TaxMaster extends Model
{
    public const KINDS = [
        'taxable' => 'Taxable',
        'inclusive' => 'Inclusive',
        'exempted' => 'Exempted',
    ];

    public const EPSILON = 0.01;

    public const PERMISSIONS = [
        'view_all_tax_masters',
        'add_tax_master',
        'edit_tax_master',
        'delete_tax_master',
    ];

    protected $table = 'tax_masters';

    protected $fillable = [
        'kind',
        'tax_code',
        'description',
        'purchase_tax',
        'purchase_cgst',
        'purchase_sgst',
        'purchase_igst',
        'sale_same_as_purchase',
        'sale_tax',
        'sale_cgst',
        'sale_sgst',
        'sale_igst',
        'status',
    ];

    protected $casts = [
        'purchase_tax' => 'float',
        'purchase_cgst' => 'float',
        'purchase_sgst' => 'float',
        'purchase_igst' => 'float',
        'sale_tax' => 'float',
        'sale_cgst' => 'float',
        'sale_sgst' => 'float',
        'sale_igst' => 'float',
        'sale_same_as_purchase' => 'boolean',
        'status' => 'boolean',
    ];

    public static function tableReady(): bool
    {
        return Schema::hasTable('tax_masters');
    }

    public static function toDecimal($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float) $value, 4);
    }

    public static function isEmptyRate($value): bool
    {
        return $value === null || $value === '';
    }

    public static function splitTotal(float $cgst, float $sgst, float $igst): float
    {
        return round($cgst + $sgst + $igst, 4);
    }

    public static function totalsMatch(float $tax, float $cgst, float $sgst, float $igst): bool
    {
        return abs($tax - self::splitTotal($cgst, $sgst, $igst)) <= self::EPSILON;
    }

    public static function isTruthy($value): bool
    {
        return in_array($value, [true, 1, '1', 'yes', 'y', 'on'], true);
    }

    public static function formatRate($value): string
    {
        $number = self::toDecimal($value);
        $formatted = number_format($number, 4, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }

    public static function sampleRows(): array
    {
        return [
            [
                'kind' => 'taxable',
                'tax_code' => 'G5',
                'description' => null,
                'purchase_tax' => 5,
                'purchase_cgst' => 2.5,
                'purchase_sgst' => 2.5,
                'purchase_igst' => 0,
                'sale_same_as_purchase' => true,
                'sale_tax' => 5,
                'sale_cgst' => 2.5,
                'sale_sgst' => 2.5,
                'sale_igst' => 0,
                'status' => true,
            ],
            [
                'kind' => 'taxable',
                'tax_code' => 'G18',
                'description' => null,
                'purchase_tax' => 18,
                'purchase_cgst' => 9,
                'purchase_sgst' => 9,
                'purchase_igst' => 0,
                'sale_same_as_purchase' => true,
                'sale_tax' => 18,
                'sale_cgst' => 9,
                'sale_sgst' => 9,
                'sale_igst' => 0,
                'status' => true,
            ],
            [
                'kind' => 'taxable',
                'tax_code' => 'G28',
                'description' => null,
                'purchase_tax' => 28,
                'purchase_cgst' => 0,
                'purchase_sgst' => 0,
                'purchase_igst' => 28,
                'sale_same_as_purchase' => true,
                'sale_tax' => 28,
                'sale_cgst' => 0,
                'sale_sgst' => 0,
                'sale_igst' => 28,
                'status' => true,
            ],
            [
                'kind' => 'exempted',
                'tax_code' => 'EX',
                'description' => 'No Tax',
                'purchase_tax' => 0,
                'purchase_cgst' => 0,
                'purchase_sgst' => 0,
                'purchase_igst' => 0,
                'sale_same_as_purchase' => true,
                'sale_tax' => 0,
                'sale_cgst' => 0,
                'sale_sgst' => 0,
                'sale_igst' => 0,
                'status' => true,
            ],
        ];
    }

    public static function normalize(array $input): array
    {
        $kind = (string) ($input['kind'] ?? '');
        $taxCode = strtoupper(trim((string) ($input['tax_code'] ?? '')));
        $description = trim((string) ($input['description'] ?? ''));
        $same = self::isTruthy($input['sale_same_as_purchase'] ?? false);
        $status = array_key_exists('status', $input)
            ? self::isTruthy($input['status'])
            : true;

        $purchaseTaxEmpty = self::isEmptyRate($input['purchase_tax'] ?? null);
        $saleTaxEmpty = self::isEmptyRate($input['sale_tax'] ?? null);

        $purchase = [
            'purchase_tax' => self::toDecimal($input['purchase_tax'] ?? 0),
            'purchase_cgst' => self::toDecimal($input['purchase_cgst'] ?? 0),
            'purchase_sgst' => self::toDecimal($input['purchase_sgst'] ?? 0),
            'purchase_igst' => self::toDecimal($input['purchase_igst'] ?? 0),
        ];

        if ($kind === 'exempted') {
            $purchase = [
                'purchase_tax' => 0.0,
                'purchase_cgst' => 0.0,
                'purchase_sgst' => 0.0,
                'purchase_igst' => 0.0,
            ];
            $same = true;
            $purchaseTaxEmpty = false;
            $saleTaxEmpty = false;
        }

        $purchaseTotal = self::splitTotal(
            $purchase['purchase_cgst'],
            $purchase['purchase_sgst'],
            $purchase['purchase_igst']
        );

        if ($purchaseTaxEmpty) {
            $purchase['purchase_tax'] = $purchaseTotal;
        }

        if ($same) {
            $sale = [
                'sale_tax' => $purchase['purchase_tax'],
                'sale_cgst' => $purchase['purchase_cgst'],
                'sale_sgst' => $purchase['purchase_sgst'],
                'sale_igst' => $purchase['purchase_igst'],
            ];
        } else {
            $sale = [
                'sale_tax' => self::toDecimal($input['sale_tax'] ?? 0),
                'sale_cgst' => self::toDecimal($input['sale_cgst'] ?? 0),
                'sale_sgst' => self::toDecimal($input['sale_sgst'] ?? 0),
                'sale_igst' => self::toDecimal($input['sale_igst'] ?? 0),
            ];

            $saleTotal = self::splitTotal(
                $sale['sale_cgst'],
                $sale['sale_sgst'],
                $sale['sale_igst']
            );

            if ($saleTaxEmpty) {
                $sale['sale_tax'] = $saleTotal;
            }
        }

        return array_merge([
            'kind' => $kind,
            'tax_code' => $taxCode,
            'description' => $description === '' ? null : $description,
            'sale_same_as_purchase' => $same,
            'status' => $status,
        ], $purchase, $sale);
    }

    public function purchaseTotal(): float
    {
        return self::splitTotal((float) $this->purchase_cgst, (float) $this->purchase_sgst, (float) $this->purchase_igst);
    }

    public function saleTotal(): float
    {
        return self::splitTotal((float) $this->sale_cgst, (float) $this->sale_sgst, (float) $this->sale_igst);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? (string) $this->kind;
    }

    public static function sortableColumns(): array
    {
        return [
            'id' => 'tax_masters.id',
            'kind' => 'tax_masters.kind',
            'tax_code' => 'tax_masters.tax_code',
            'description' => 'tax_masters.description',
            'purchase_tax' => 'tax_masters.purchase_tax',
            'sale_same_as_purchase' => 'tax_masters.sale_same_as_purchase',
            'sale_tax' => 'tax_masters.sale_tax',
            'status' => 'tax_masters.status',
            'updated_at' => 'tax_masters.updated_at',
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

    public static function applyListingFilters($query, array $filters)
    {
        if (($filters['search'] ?? '') !== '') {
            $like = '%' . $filters['search'] . '%';
            $query->where(function ($nested) use ($like, $filters) {
                $nested->where('tax_code', 'like', $like)
                    ->orWhere('description', 'like', $like);

                if (ctype_digit($filters['search'])) {
                    $nested->orWhere('id', (int) $filters['search']);
                }
            });
        }

        if (array_key_exists($filters['kind'] ?? '', self::KINDS)) {
            $query->where('kind', $filters['kind']);
        }

        if (($filters['tax_code'] ?? '') !== '') {
            $query->where('tax_code', 'like', '%' . $filters['tax_code'] . '%');
        }

        if (($filters['description'] ?? '') !== '') {
            $query->where('description', 'like', '%' . $filters['description'] . '%');
        }

        if (($filters['sale_same_as_purchase'] ?? '') === '1' || ($filters['sale_same_as_purchase'] ?? '') === '0') {
            $query->where('sale_same_as_purchase', (int) $filters['sale_same_as_purchase']);
        }

        if (($filters['status'] ?? '') === '1' || ($filters['status'] ?? '') === '0') {
            $query->where('status', (int) $filters['status']);
        }

        if (($filters['purchase_tax_from'] ?? '') !== '') {
            $query->where('purchase_tax', '>=', (float) $filters['purchase_tax_from']);
        }
        if (($filters['purchase_tax_to'] ?? '') !== '') {
            $query->where('purchase_tax', '<=', (float) $filters['purchase_tax_to']);
        }
        if (($filters['sale_tax_from'] ?? '') !== '') {
            $query->where('sale_tax', '>=', (float) $filters['sale_tax_from']);
        }
        if (($filters['sale_tax_to'] ?? '') !== '') {
            $query->where('sale_tax', '<=', (float) $filters['sale_tax_to']);
        }
        if (($filters['date_from'] ?? '') !== '') {
            $query->whereDate('updated_at', '>=', $filters['date_from']);
        }
        if (($filters['date_to'] ?? '') !== '') {
            $query->whereDate('updated_at', '<=', $filters['date_to']);
        }

        return $query;
    }
}
