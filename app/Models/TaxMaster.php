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

    public const APPLIED_ALL = [
        'category' => 'All Category',
        'group' => 'All Group',
        'sku' => 'All SKU',
        'product' => 'All Product',
        'variant' => 'All Variant',
    ];

    public const APPLIED_TYPES = [
        'category' => 'Category',
        'group' => 'Group',
        'sku' => 'SKU',
        'product' => 'Product',
        'variant' => 'Variant',
    ];

    public const APPLIED_COLUMNS = [
        'category' => 'applied_on_category',
        'group' => 'applied_on_group',
        'sku' => 'applied_on_sku',
        'product' => 'applied_on_product',
        'variant' => 'applied_on_variant',
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
        'applied_on_group',
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

    public static function groupColumnReady(): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            return $ready = self::tableReady() && Schema::hasColumn('tax_masters', 'applied_on_group');
        } catch (\Throwable $e) {
            return $ready = false;
        }
    }

    public static function appliedStorageLimit(): int
    {
        static $limit = null;
        if ($limit !== null) {
            return $limit;
        }

        try {
            if (!self::tableReady()) {
                return $limit = 60000;
            }

            foreach (Schema::getColumns('tax_masters') as $column) {
                if (($column['name'] ?? '') !== 'applied_on_category') {
                    continue;
                }
                $type = strtolower((string) ($column['type_name'] ?? $column['type'] ?? ''));
                if (str_contains($type, 'text')) {
                    return $limit = 60000;
                }

                return $limit = 255;
            }
        } catch (\Throwable $e) {
            return $limit = 255;
        }

        return $limit = 255;
    }

    public static function appliedItems($value): array
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '' || in_array($text, self::APPLIED_ALL, true)) {
            return [];
        }

        if (str_starts_with($text, '[')) {
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                $items = [];
                $seen = [];
                foreach ($decoded as $row) {
                    if (is_array($row)) {
                        $id = trim((string) ($row['id'] ?? ''));
                        $label = trim((string) ($row['label'] ?? ''));
                    } else {
                        $id = trim((string) $row);
                        $label = $id;
                    }
                    if ($id === '' || isset($seen[$id])) {
                        continue;
                    }
                    $seen[$id] = true;
                    $items[] = [
                        'id' => $id,
                        'label' => $label !== '' ? $label : $id,
                    ];
                }

                return $items;
            }
        }

        return [['id' => $text, 'label' => $text]];
    }

    public static function encodeAppliedItems(array $items): ?string
    {
        $clean = [];
        $seen = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                $item = ['id' => $item, 'label' => $item];
            }
            $id = trim((string) ($item['id'] ?? ''));
            if ($id === '' || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $label = trim((string) ($item['label'] ?? ''));
            $clean[] = [
                'id' => $id,
                'label' => $label !== '' ? $label : $id,
            ];
        }

        if ($clean === []) {
            return null;
        }

        return json_encode($clean, JSON_UNESCAPED_UNICODE);
    }

    public static function activeAppliedType($tax): string
    {
        foreach (['variant', 'sku', 'product', 'group', 'category'] as $type) {
            $raw = $tax ? $tax->getAttribute(self::APPLIED_COLUMNS[$type]) : null;
            if (self::appliedItems($raw) !== []) {
                return $type;
            }
        }

        foreach (['category', 'group', 'sku', 'product', 'variant'] as $type) {
            $raw = trim((string) ($tax ? $tax->getAttribute(self::APPLIED_COLUMNS[$type]) : ''));
            if ($raw !== '') {
                return $type;
            }
        }

        return 'category';
    }

    public static function itemsFromTargetIds(string $type, array $ids): array
    {
        $clean = [];
        foreach ($ids as $id) {
            $id = trim((string) $id);
            if ($id === '' || isset($clean[$id])) {
                continue;
            }
            $clean[$id] = $id;
        }

        $labels = self::lookupAppliedLabels($type, array_keys($clean));
        $items = [];
        foreach ($clean as $id) {
            $items[] = [
                'id' => $id,
                'label' => $labels[$id] ?? $id,
            ];
        }

        return $items;
    }

    public static function presentAppliedItems(string $type, $raw): array
    {
        $items = self::appliedItems($raw);
        if ($items === [] || !isset(self::APPLIED_COLUMNS[$type])) {
            return $items;
        }

        $labels = self::lookupAppliedLabels($type, array_column($items, 'id'));
        foreach ($items as $index => $item) {
            if (!empty($labels[$item['id']])) {
                $items[$index]['label'] = $labels[$item['id']];
            }
        }

        return $items;
    }

    public static function lookupAppliedLabels(string $type, array $ids): array
    {
        try {
            return self::queryAppliedLabels($type, $ids);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function searchAppliedTargets(string $type, string $query, int $limit = 20): array
    {
        try {
            $query = trim($query);
            $limit = max(1, min($limit, 30));
            if (!isset(self::APPLIED_COLUMNS[$type])) {
                return [];
            }
            if ($type === 'group' && !self::groupColumnReady()) {
                return [];
            }

            if ($type === 'category') {
                return self::searchNamedTargets(Category::query(), 'category_translations', $query, $limit);
            }
            if ($type === 'group') {
                $builder = Group::query();
                if (Schema::hasColumn('groups', 'digital')) {
                    $builder->where('digital', 0);
                }

                return self::searchNamedTargets($builder, 'group_translations', $query, $limit);
            }
            if ($type === 'product') {
                return self::searchProductTargets($query, $limit);
            }

            return self::searchStockTargets($type, $query, $limit);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function queryAppliedLabels(string $type, array $ids): array
    {
        $ids = array_values(array_filter($ids, function ($id) {
            return ctype_digit((string) $id);
        }));
        if ($ids === [] || !isset(self::APPLIED_COLUMNS[$type])) {
            return [];
        }

        $labels = [];
        if ($type === 'category' && Schema::hasTable('categories')) {
            foreach (Category::query()->whereIn('id', $ids)->get() as $row) {
                $name = trim((string) $row->getTranslation('name'));
                if ($name !== '') {
                    $labels[(string) $row->id] = $name;
                }
            }
        } elseif ($type === 'group' && Schema::hasTable('groups')) {
            foreach (Group::query()->whereIn('id', $ids)->get() as $row) {
                $name = trim((string) $row->getTranslation('name'));
                if ($name !== '') {
                    $labels[(string) $row->id] = $name;
                }
            }
        } elseif ($type === 'product' && Schema::hasTable('products')) {
            $products = Product::query()->without(['taxes', 'thumbnail'])->whereIn('id', $ids)->get();
            foreach (self::productTargetRows($products) as $row) {
                $labels[$row['id']] = $row['label'];
            }
        } elseif (in_array($type, ['sku', 'variant'], true) && Schema::hasTable('product_stocks')) {
            $stocks = ProductStock::query()->with('product')->whereIn('id', $ids)->get();
            self::warmStockVariantLabels($stocks);
            foreach ($stocks as $stock) {
                $row = self::stockAppliedRow($stock, $type);
                if ($row) {
                    $labels[$row['id']] = $row['label'];
                }
            }
        }

        return $labels;
    }

    private static function searchNamedTargets($builder, string $relation, string $query, int $limit): array
    {
        if ($query !== '') {
            $like = '%' . $query . '%';
            $builder->where(function ($nested) use ($like, $relation) {
                $nested->where('name', 'like', $like)
                    ->orWhereHas($relation, function ($translation) use ($like) {
                        $translation->where('name', 'like', $like);
                    });
            });
        }

        return $builder->orderBy('name')->limit($limit)->get()->map(function ($row) {
            $name = trim((string) $row->getTranslation('name'));

            return [
                'id' => (string) $row->id,
                'label' => $name !== '' ? $name : ('#' . $row->id),
            ];
        })->all();
    }

    private static function searchProductTargets(string $query, int $limit): array
    {
        if (!Schema::hasTable('products')) {
            return [];
        }

        $builder = Product::query()->without(['taxes', 'thumbnail']);
        if ($query !== '') {
            $like = '%' . $query . '%';
            $builder->where(function ($nested) use ($like) {
                $nested->where('name', 'like', $like)
                    ->orWhereHas('product_translations', function ($translation) use ($like) {
                        $translation->where('name', 'like', $like);
                    });
                if (Schema::hasTable('product_stocks')) {
                    $nested->orWhereHas('stocks', function ($stocks) use ($like) {
                        $stocks->where('variant', 'like', $like);
                    });
                }
            });
        }

        return self::productTargetRows($builder->orderBy('name')->limit($limit)->get());
    }

    private static function productTargetRows($products): array
    {
        $variantsByProduct = self::productVariantLists($products->pluck('id')->all());

        return $products->map(function ($product) use ($variantsByProduct) {
            $name = trim((string) $product->getTranslation('name'));
            if ($name === '') {
                $name = '#' . $product->id;
            }
            $variants = $variantsByProduct[(int) $product->id] ?? [];

            return [
                'id' => (string) $product->id,
                'label' => $variants !== [] ? $name . ' ' . implode('/', $variants) : $name,
            ];
        })->all();
    }

    private static function productVariantLists(array $ids): array
    {
        $ids = array_values(array_filter($ids, function ($id) {
            return ctype_digit((string) $id);
        }));
        if ($ids === [] || !Schema::hasTable('product_stocks')) {
            return [];
        }

        $rows = ProductStock::query()
            ->whereIn('product_id', $ids)
            ->orderBy('id')
            ->get(['id', 'product_id', 'variant']);

        $lists = [];
        foreach ($rows as $row) {
            $variant = trim((string) $row->variant);
            if ($variant === '') {
                continue;
            }
            $productId = (int) $row->product_id;
            if (!isset($lists[$productId])) {
                $lists[$productId] = [];
            }
            if (!in_array($variant, $lists[$productId], true)) {
                $lists[$productId][] = $variant;
            }
        }

        return $lists;
    }

    private static function searchStockTargets(string $type, string $query, int $limit): array
    {
        if (!Schema::hasTable('product_stocks')) {
            return [];
        }

        $builder = ProductStock::query()->with('product');
        if ($type === 'variant') {
            $builder->whereNotNull('variant')->where('variant', '!=', '');
        } else {
            $builder->whereNotNull('sku')->where('sku', '!=', '');
        }

        if ($query !== '') {
            $like = '%' . $query . '%';
            $builder->where(function ($nested) use ($like, $type) {
                if ($type === 'variant') {
                    $nested->where('variant', 'like', $like)->orWhere('sku', 'like', $like);
                } else {
                    $nested->where('sku', 'like', $like);
                }
                $nested->orWhereHas('product', function ($product) use ($like) {
                    $product->where('name', 'like', $like);
                });
            });
        }

        $stocks = $builder->orderBy('id', 'desc')->limit($limit)->get();
        self::warmStockVariantLabels($stocks);

        $rows = [];
        foreach ($stocks as $stock) {
            $row = self::stockAppliedRow($stock, $type);
            if ($row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private static function warmStockVariantLabels($stocks): void
    {
        $parts = [];
        foreach ($stocks as $stock) {
            foreach (preg_split('/[-_\/]+/', trim((string) $stock->variant)) ?: [] as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $parts[] = $part;
                }
            }
        }
        if ($parts !== []) {
            ProductStock::warmVariantLabelCache($parts);
        }
    }

    private static function stockAppliedRow(ProductStock $stock, string $type): ?array
    {
        $productName = $stock->product ? trim((string) $stock->product->getTranslation('name')) : '';
        $sku = trim((string) $stock->sku);
        $variant = trim($stock->expandedVariantLabel());
        if ($variant === '') {
            $variant = trim((string) $stock->variant);
        }

        if ($type === 'sku') {
            if ($sku === '') {
                return null;
            }
            $variantRaw = trim((string) $stock->variant);
            $parts = array_values(array_filter([$sku, $productName, $variantRaw], function ($part) {
                return $part !== '';
            }));
            $label = implode(' ', $parts);
        } elseif ($type === 'variant') {
            if ($variant === '') {
                return null;
            }
            $label = $productName !== '' ? $productName . ' — ' . $variant : $variant;
            if ($sku !== '') {
                $label .= ' (' . $sku . ')';
            }
        } else {
            return null;
        }

        return [
            'id' => (string) $stock->id,
            'label' => $label,
        ];
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

        $appliedType = trim((string) ($input['applied_on_type'] ?? ''));
        if ($appliedType !== '' && isset(self::APPLIED_COLUMNS[$appliedType])) {
            $targets = $input['applied_targets'] ?? [];
            if (!is_array($targets)) {
                $targets = ($targets === null || $targets === '') ? [] : [$targets];
            }
            $encoded = self::encodeAppliedItems(self::itemsFromTargetIds($appliedType, $targets));
            $applied = [];
            foreach (self::APPLIED_COLUMNS as $key => $column) {
                if ($key === $appliedType) {
                    $applied[$column] = $encoded ?? self::APPLIED_ALL[$key];
                } else {
                    $applied[$column] = null;
                }
            }
        } else {
            $applied = [
                'applied_on_category' => $blank($input['applied_on_category'] ?? null),
                'applied_on_group' => $blank($input['applied_on_group'] ?? null),
                'applied_on_sku' => $blank($input['applied_on_sku'] ?? null),
                'applied_on_product' => $blank($input['applied_on_product'] ?? null),
                'applied_on_variant' => $blank($input['applied_on_variant'] ?? null),
            ];
        }

        return array_merge([
            'kind' => $kind,
            'tax_code' => $taxCode,
            'description' => $description === '' ? null : $description,
            'hsn_code' => $hsn,
            'hs_code' => $hs,
            'sale_same_as_purchase' => $same,
            'status' => $status,
        ], $applied, $purchase, $sale);
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
        $labels = [
            'applied_on_category' => 'Category',
            'applied_on_sku' => 'SKU',
            'applied_on_product' => 'Product Name',
            'applied_on_variant' => 'Variant',
        ];
        if (self::groupColumnReady()) {
            $labels = [
                'applied_on_category' => 'Category',
                'applied_on_group' => 'Group',
                'applied_on_sku' => 'SKU',
                'applied_on_product' => 'Product Name',
                'applied_on_variant' => 'Variant',
            ];
        }
        foreach ($labels as $column => $label) {
            $value = trim((string) ($this->getAttribute($column) ?? ''));
            if ($value === '') {
                continue;
            }
            $items = self::appliedItems($value);
            if ($items !== []) {
                $names = array_map(function ($item) {
                    return $item['label'];
                }, $items);
                $parts[] = $label . ': ' . implode(', ', $names);
                continue;
            }
            $parts[] = $label . ': ' . $value;
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
                    if (self::groupColumnReady()) {
                        $nested->orWhere('applied_on_group', 'like', $like);
                    }
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
                    if (self::groupColumnReady()) {
                        $nested->orWhere('applied_on_group', 'like', $likeApplied);
                    }
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

    public static function findForRate($kind, $taxPercent, $ignoreId = null): ?self
    {
        $kind = (string) $kind;
        if (!self::tableReady() || !array_key_exists($kind, self::KINDS)) {
            return null;
        }

        $target = self::toDecimal($taxPercent);
        $query = self::query()
            ->where('kind', $kind)
            ->whereBetween('purchase_tax', [$target - 0.00005, $target + 0.00005])
            ->orderBy('id');

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return null;
        }

        $base = self::taxCodeBase($target);
        $canonical = $rows->first(function ($row) use ($base) {
            return strtoupper((string) $row->tax_code) === $base;
        });

        return $canonical ?: $rows->first();
    }

    public static function suggestTaxCode($taxPercent, $ignoreId = null, $kind = 'taxable'): string
    {
        $kind = array_key_exists((string) $kind, self::KINDS) ? (string) $kind : 'taxable';
        $existing = self::findForRate($kind, $taxPercent, $ignoreId);
        if ($existing) {
            return (string) $existing->tax_code;
        }

        return self::taxCodeBase($taxPercent);
    }

    public static function searchHsnDirectory(string $query, int $limit = 15): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $prefix = [];
        $text = [];
        $digits = preg_replace('/\D/', '', $query) ?? '';
        foreach (self::hsnDirectory() as $row) {
            $code = (string) ($row[0] ?? '');
            $description = (string) ($row[1] ?? '');
            if ($code === '' || !ctype_digit($code)) {
                continue;
            }

            $codeHit = $digits !== '' && strpos($code, $digits) === 0;
            if ($codeHit) {
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

        $digits = preg_replace('/\D/', '', $query) ?? '';
        $rows = count($prefix) ? $prefix : $text;
        usort($rows, function ($left, $right) use ($query, $digits) {
            $leftExact = ($left['code'] === $query || ($digits !== '' && $left['code'] === $digits)) ? 0 : 1;
            $rightExact = ($right['code'] === $query || ($digits !== '' && $right['code'] === $digits)) ? 0 : 1;
            if ($leftExact !== $rightExact) {
                return $leftExact <=> $rightExact;
            }

            return strlen($left['code']) <=> strlen($right['code']);
        });

        return array_slice($rows, 0, $limit);
    }

    public static function searchHsnChapters(string $query, int $limit = 15): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        $rows = [];
        foreach (self::hsnDirectory() as $row) {
            $code = (string) ($row[0] ?? '');
            $description = (string) ($row[1] ?? '');
            if (!ctype_digit($code) || strlen($code) !== 2) {
                continue;
            }

            $codeHit = strpos($code, $query) === 0;
            $textHit = mb_stripos($description, $query) !== false;
            if (!$codeHit && !$textHit) {
                continue;
            }

            $rows[] = self::hsnResult($code, $description, (string) ($row[2] ?? 'goods'));
            if (count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }

    public static function lookupByHsn(string $hsn): ?array
    {
        $hsn = trim($hsn);
        if ($hsn === '' || !self::tableReady() || !self::hsnColumnsReady()) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $hsn) ?? '';
        $tax = self::query()
            ->where('status', 1)
            ->where(function ($query) use ($hsn, $digits) {
                $query->where('hsn_code', $hsn);
                if ($digits !== '') {
                    $query->orWhere('hsn_code', $digits);
                    $query->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(hsn_code, '.', ''), '-', ''), ' ', '') = ?",
                        [$digits]
                    );
                }
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if (!$tax) {
            return null;
        }

        $same = (bool) $tax->sale_same_as_purchase;
        $displayTax = $same ? $tax->purchase_tax : $tax->sale_tax;

        return [
            'found' => true,
            'id' => $tax->id,
            'kind' => $tax->kind,
            'kind_label' => $tax->kindLabel(),
            'tax_percent' => self::formatRate($tax->purchase_tax),
            'display_tax_percent' => self::formatRate($displayTax),
            'tax_code' => $tax->tax_code,
            'description' => (string) ($tax->description ?? ''),
            'hs_code' => (string) ($tax->hs_code ?? ''),
            'hsn_code' => (string) ($tax->hsn_code ?? ''),
            'sale_same_as_purchase' => $same,
        ];
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
