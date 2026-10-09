<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TaxMaster extends Model
{
    public const KINDS = [
        'taxable' => 'Taxable',
        'inclusive' => 'Inclusive',
        'exempted' => 'Exempted',
        'lut' => 'LUT',
    ];

    public const EPSILON = 0.01;

    public const PERMISSIONS = [
        'view_all_tax_masters',
        'add_tax_master',
        'edit_tax_master',
        'delete_tax_master',
    ];

    public const HSN_COLUMNS = [
        'hsn_code',
        'hs_code',
        'applied_on_category',
        'applied_on_sku',
        'applied_on_product',
        'applied_on_variant',
    ];

    public const UT_COLUMNS = [
        'purchase_ut_cgst',
        'purchase_utgst',
        'sale_ut_cgst',
        'sale_utgst',
    ];

    protected $table = 'tax_masters';

    protected $fillable = [
        'kind',
        'tax_code',
        'description',
        'hsn_code',
        'hs_code',
        'applied_on_category',
        'applied_on_sku',
        'applied_on_product',
        'applied_on_variant',
        'purchase_tax',
        'purchase_cgst',
        'purchase_sgst',
        'purchase_ut_cgst',
        'purchase_utgst',
        'purchase_igst',
        'sale_same_as_purchase',
        'sale_tax',
        'sale_cgst',
        'sale_sgst',
        'sale_ut_cgst',
        'sale_utgst',
        'sale_igst',
        'status',
    ];

    protected $casts = [
        'purchase_tax' => 'float',
        'purchase_cgst' => 'float',
        'purchase_sgst' => 'float',
        'purchase_ut_cgst' => 'float',
        'purchase_utgst' => 'float',
        'purchase_igst' => 'float',
        'sale_tax' => 'float',
        'sale_cgst' => 'float',
        'sale_sgst' => 'float',
        'sale_ut_cgst' => 'float',
        'sale_utgst' => 'float',
        'sale_igst' => 'float',
        'sale_same_as_purchase' => 'boolean',
        'status' => 'boolean',
    ];

    public static function tableReady(): bool
    {
        try {
            return Schema::hasTable('tax_masters');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function hsnColumnsReady(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            if (!self::tableReady()) {
                return $ready = false;
            }

            foreach (self::HSN_COLUMNS as $column) {
                if (!Schema::hasColumn('tax_masters', $column)) {
                    return $ready = false;
                }
            }

            return $ready = true;
        } catch (\Throwable $e) {
            return $ready = false;
        }
    }

    public static function utColumnsReady(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            if (!self::tableReady()) {
                return $ready = false;
            }

            foreach (self::UT_COLUMNS as $column) {
                if (!Schema::hasColumn('tax_masters', $column)) {
                    return $ready = false;
                }
            }

            return $ready = true;
        } catch (\Throwable $e) {
            return $ready = false;
        }
    }

    public static function hsnOptions()
    {
        try {
            if (!Schema::hasTable('products') || !Schema::hasColumn('products', 'product_hsn')) {
                return collect();
            }

            $query = DB::table('products')
                ->whereNotNull('product_hsn')
                ->where('product_hsn', '!=', '')
                ->orderBy('product_hsn');

            if (Schema::hasColumn('products', 'product_hs')) {
                return $query->select('product_hsn', 'product_hs')->distinct()->get();
            }

            return $query->select('product_hsn')->distinct()->get();
        } catch (\Throwable $e) {
            return collect();
        }
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

    public static function halfSplit(float $tax): array
    {
        $first = round($tax / 2, 4);
        $second = round($tax - $first, 4);

        return [$first, $second];
    }

    public static function pathsMatch(
        float $tax,
        float $cgst,
        float $sgst,
        float $utCgst,
        float $utgst,
        float $igst,
        bool $checkUt = true
    ): bool {
        $stateOk = abs($tax - round($cgst + $sgst, 4)) <= self::EPSILON;
        $centralOk = abs($tax - round($igst, 4)) <= self::EPSILON;
        if (!$checkUt) {
            return $stateOk && $centralOk;
        }

        $utOk = abs($tax - round($utCgst + $utgst, 4)) <= self::EPSILON;

        return $stateOk && $utOk && $centralOk;
    }

    public static function totalsMatch(float $tax, float $cgst, float $sgst, float $igst): bool
    {
        return abs($tax - self::splitTotal($cgst, $sgst, $igst)) <= self::EPSILON;
    }

    public static function zeroRateKind(string $kind): bool
    {
        return in_array($kind, ['exempted', 'lut'], true);
    }

    public static function hsFromHsn($hsn): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $hsn);
        if ($digits === null || $digits === '') {
            return null;
        }

        return strlen($digits) >= 6 ? substr($digits, 0, 6) : $digits;
    }

    public static function taxCodeBase($taxPercent): string
    {
        return 'G' . str_replace('.', '-', self::formatRate($taxPercent));
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
                'purchase_ut_cgst' => 2.5,
                'purchase_utgst' => 2.5,
                'purchase_igst' => 5,
                'sale_same_as_purchase' => true,
                'sale_tax' => 5,
                'sale_cgst' => 2.5,
                'sale_sgst' => 2.5,
                'sale_ut_cgst' => 2.5,
                'sale_utgst' => 2.5,
                'sale_igst' => 5,
                'status' => true,
            ],
            [
                'kind' => 'taxable',
                'tax_code' => 'G18',
                'description' => null,
                'purchase_tax' => 18,
                'purchase_cgst' => 9,
                'purchase_sgst' => 9,
                'purchase_ut_cgst' => 9,
                'purchase_utgst' => 9,
                'purchase_igst' => 18,
                'sale_same_as_purchase' => true,
                'sale_tax' => 18,
                'sale_cgst' => 9,
                'sale_sgst' => 9,
                'sale_ut_cgst' => 9,
                'sale_utgst' => 9,
                'sale_igst' => 18,
                'status' => true,
            ],
            [
                'kind' => 'taxable',
                'tax_code' => 'G28',
                'description' => null,
                'purchase_tax' => 28,
                'purchase_cgst' => 14,
                'purchase_sgst' => 14,
                'purchase_ut_cgst' => 14,
                'purchase_utgst' => 14,
                'purchase_igst' => 28,
                'sale_same_as_purchase' => true,
                'sale_tax' => 28,
                'sale_cgst' => 14,
                'sale_sgst' => 14,
                'sale_ut_cgst' => 14,
                'sale_utgst' => 14,
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
                'purchase_ut_cgst' => 0,
                'purchase_utgst' => 0,
                'purchase_igst' => 0,
                'sale_same_as_purchase' => true,
                'sale_tax' => 0,
                'sale_cgst' => 0,
                'sale_sgst' => 0,
                'sale_ut_cgst' => 0,
                'sale_utgst' => 0,
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

        if (self::zeroRateKind($kind)) {
            $purchase = self::zeroBreakup('purchase');
            $same = true;
            $sale = self::zeroBreakup('sale');
        } else {
            $purchase = self::breakupFromInput($input, 'purchase');
            $sale = $same
                ? self::copyBreakup($purchase, 'purchase', 'sale')
                : self::breakupFromInput($input, 'sale');
        }

        $blank = function ($value) {
            $text = trim((string) ($value ?? ''));

            return $text === '' ? null : $text;
        };

        $hsn = $blank($input['hsn_code'] ?? null);
        $hs = $blank($input['hs_code'] ?? null);
        if ($hs === null) {
            $hs = self::hsFromHsn($hsn);
        }
        if ($description === '') {
            $official = self::describeHsn($hsn);
            if ($official !== null) {
                $description = $official;
            }
        }
        if (mb_strlen($description) > 255) {
            $description = mb_substr($description, 0, 255);
        }

        return array_merge([
            'kind' => $kind,
            'tax_code' => $taxCode,
            'description' => $description === '' ? null : $description,
            'hsn_code' => $hsn,
            'hs_code' => $hs,
            'applied_on_category' => $blank($input['applied_on_category'] ?? null),
            'applied_on_sku' => $blank($input['applied_on_sku'] ?? null),
            'applied_on_product' => $blank($input['applied_on_product'] ?? null),
            'applied_on_variant' => $blank($input['applied_on_variant'] ?? null),
            'sale_same_as_purchase' => $same,
            'status' => $status,
        ], $purchase, $sale);
    }

    public function purchaseTotal(): float
    {
        return self::toDecimal($this->purchase_tax);
    }

    public function saleTotal(): float
    {
        return self::toDecimal($this->sale_tax);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? (string) $this->kind;
    }

    public static function sortableColumns(): array
    {
        $columns = [
            'id' => 'tax_masters.id',
            'kind' => 'tax_masters.kind',
            'tax_code' => 'tax_masters.tax_code',
            'description' => 'tax_masters.description',
            'purchase_tax' => 'tax_masters.purchase_tax',
            'sale_same_as_purchase' => 'tax_masters.sale_same_as_purchase',
            'sale_tax' => 'tax_masters.sale_tax',
            'status' => 'tax_masters.status',
            'updated_at' => 'tax_masters.updated_at',
            'hsn_code' => 'tax_masters.hsn_code',
            'hs_code' => 'tax_masters.hs_code',
            'applied_on_category' => 'tax_masters.applied_on_category',
        ];

        return $columns;
    }

    public function appliedOnSummary(): string
    {
        $parts = [];
        foreach ([
            'applied_on_category' => 'Category',
            'applied_on_sku' => 'SKU',
            'applied_on_product' => 'Product Name',
            'applied_on_variant' => 'Variant',
        ] as $column => $label) {
            $value = trim((string) ($this->getAttribute($column) ?? ''));
            if ($value !== '') {
                $parts[] = $label . ': ' . $value;
            }
        }

        return $parts ? implode(' / ', $parts) : '—';
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

                if (self::hsnColumnsReady()) {
                    $nested->orWhere('hsn_code', 'like', $like)
                        ->orWhere('hs_code', 'like', $like)
                        ->orWhere('applied_on_category', 'like', $like)
                        ->orWhere('applied_on_sku', 'like', $like)
                        ->orWhere('applied_on_product', 'like', $like)
                        ->orWhere('applied_on_variant', 'like', $like);
                }

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

        if (self::hsnColumnsReady()) {
            if (($filters['hsn_code'] ?? '') !== '') {
                $query->where('hsn_code', 'like', '%' . $filters['hsn_code'] . '%');
            }
            if (($filters['hs_code'] ?? '') !== '') {
                $query->where('hs_code', 'like', '%' . $filters['hs_code'] . '%');
            }
            if (($filters['applied_on'] ?? '') !== '') {
                $likeApplied = '%' . $filters['applied_on'] . '%';
                $query->where(function ($nested) use ($likeApplied) {
                    $nested->where('applied_on_category', 'like', $likeApplied)
                        ->orWhere('applied_on_sku', 'like', $likeApplied)
                        ->orWhere('applied_on_product', 'like', $likeApplied)
                        ->orWhere('applied_on_variant', 'like', $likeApplied);
                });
            }
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

    public static function suggestTaxCode($taxPercent, $ignoreId = null): string
    {
        $base = self::taxCodeBase($taxPercent);
        if (!self::tableReady()) {
            return $base;
        }

        $candidate = $base;
        $suffix = 2;
        while (
            self::query()
                ->where('tax_code', $candidate)
                ->when($ignoreId, function ($query) use ($ignoreId) {
                    $query->where('id', '!=', $ignoreId);
                })
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
            if ($suffix > 100) {
                break;
            }
        }

        return $candidate;
    }

    public static function searchHsnDirectory(string $query, int $limit = 15): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $prefix = [];
        $text = [];
        foreach (self::hsnDirectory() as $row) {
            $code = (string) ($row[0] ?? '');
            $description = (string) ($row[1] ?? '');
            if ($code === '' || !ctype_digit($code)) {
                continue;
            }

            if (strpos($code, $query) === 0) {
                if (count($prefix) < $limit) {
                    $prefix[] = self::hsnResult($code, $description, (string) ($row[2] ?? 'goods'));
                }
            } elseif (count($text) < $limit && mb_stripos($description, $query) !== false) {
                $text[] = self::hsnResult($code, $description, (string) ($row[2] ?? 'goods'));
            }

            if (count($prefix) >= $limit) {
                break;
            }
        }

        $rows = count($prefix) ? $prefix : $text;
        usort($rows, function ($left, $right) use ($query) {
            $leftExact = $left['code'] === $query ? 0 : 1;
            $rightExact = $right['code'] === $query ? 0 : 1;
            if ($leftExact !== $rightExact) {
                return $leftExact <=> $rightExact;
            }

            return strlen($left['code']) <=> strlen($right['code']);
        });

        return array_slice($rows, 0, $limit);
    }

    public static function describeHsn($hsn): ?string
    {
        $code = trim((string) $hsn);
        if ($code === '') {
            return null;
        }

        foreach (self::hsnDirectory() as $row) {
            if ((string) ($row[0] ?? '') === $code) {
                $description = trim((string) ($row[1] ?? ''));

                return $description === '' ? null : $description;
            }
        }

        return null;
    }

    private static function hsnDirectory(): array
    {
        static $rows = null;
        if ($rows !== null) {
            return $rows;
        }

        $path = resource_path('data/gst_hsn_directory.php');
        if (!is_file($path)) {
            return $rows = [];
        }

        $loaded = require $path;

        return $rows = is_array($loaded) ? $loaded : [];
    }

    private static function hsnResult(string $code, string $description, string $kind): array
    {
        return [
            'code' => $code,
            'description' => $description,
            'hs_code' => self::hsFromHsn($code),
            'kind' => $kind === 'services' ? 'services' : 'goods',
        ];
    }

    private static function zeroBreakup(string $side): array
    {
        return [
            $side . '_tax' => 0.0,
            $side . '_cgst' => 0.0,
            $side . '_sgst' => 0.0,
            $side . '_ut_cgst' => 0.0,
            $side . '_utgst' => 0.0,
            $side . '_igst' => 0.0,
        ];
    }

    private static function copyBreakup(array $source, string $from, string $to): array
    {
        $copy = [];
        foreach (['tax', 'cgst', 'sgst', 'ut_cgst', 'utgst', 'igst'] as $part) {
            $copy[$to . '_' . $part] = $source[$from . '_' . $part];
        }

        return $copy;
    }

    private static function breakupFromInput(array $input, string $side): array
    {
        $taxKey = $side . '_tax';
        $taxEmpty = self::isEmptyRate($input[$taxKey] ?? null);
        $tax = self::toDecimal($input[$taxKey] ?? 0);

        if ($taxEmpty) {
            $state = round(
                self::toDecimal($input[$side . '_cgst'] ?? 0) + self::toDecimal($input[$side . '_sgst'] ?? 0),
                4
            );
            $igst = self::toDecimal($input[$side . '_igst'] ?? 0);
            $tax = $state > 0 ? $state : $igst;
        }

        [$cgst, $sgst] = self::halfSplit($tax);
        $utCgstIn = $input[$side . '_ut_cgst'] ?? null;
        $utgstIn = $input[$side . '_utgst'] ?? null;
        $utProvided = !self::isEmptyRate($utCgstIn) || !self::isEmptyRate($utgstIn);
        $utCgst = self::toDecimal($utCgstIn);
        $utgst = self::toDecimal($utgstIn);
        if (!$utProvided || (round($utCgst + $utgst, 4) == 0.0 && $tax > 0)) {
            [$utCgst, $utgst] = self::halfSplit($tax);
        }

        return [
            $taxKey => $tax,
            $side . '_cgst' => $cgst,
            $side . '_sgst' => $sgst,
            $side . '_ut_cgst' => $utCgst,
            $side . '_utgst' => $utgst,
            $side . '_igst' => $tax,
        ];
    }
}
