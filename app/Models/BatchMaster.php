<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BatchMaster extends Model
{
    public const ROLE_KEYS = [
        'pts' => 'PTS',
        'ptr' => 'PTR',
        'ptd' => 'PTD',
        'gov' => 'Govt.',
        'expo' => 'Export',
        'customer' => 'Customers (B2C)',
    ];

    public const PERMISSIONS = [
        'view_all_batch_masters',
        'add_batch_master',
        'edit_batch_master',
        'delete_batch_master',
    ];

    protected $table = 'batch_masters';

    public const EXTENDED_COLUMNS = [
        'drug_name',
        'marketed_by_id',
        'marketed_by_name',
        'import_by_ids',
        'import_by_names',
        'manufactured_by_ids',
        'manufactured_by_names',
        'company_id',
        'free_qty',
        'purchase_rate',
        'tax_code',
        'tax_percent',
        'scheme',
        'batch_discount_percent',
        'product_discount_percent',
        'scheme_discount_percent',
        'coa',
        'price_pts',
        'price_ptr',
        'price_ptd',
        'price_gov',
        'price_expo',
        'price_customer',
        'source_purchase_history_id',
        'source_product_batch_id',
        'copied_at',
        'converted_from_id',
        'removed_at',
    ];

    public const PRICE_COLUMNS = [
        'pts' => 'price_pts',
        'ptr' => 'price_ptr',
        'ptd' => 'price_ptd',
        'gov' => 'price_gov',
        'expo' => 'price_expo',
        'customer' => 'price_customer',
    ];

    protected $fillable = [
        'product_id',
        'product_stock_id',
        'batch_code',
        'is_non_batch',
        'manufacturing_date',
        'expiry_date',
        'mrp_price',
        'qty',
        'role_price',
        'status',
        'drug_name',
        'marketed_by_id',
        'marketed_by_name',
        'import_by_ids',
        'import_by_names',
        'manufactured_by_ids',
        'manufactured_by_names',
        'company_id',
        'free_qty',
        'purchase_rate',
        'tax_code',
        'tax_percent',
        'scheme',
        'batch_discount_percent',
        'product_discount_percent',
        'scheme_discount_percent',
        'coa',
        'price_pts',
        'price_ptr',
        'price_ptd',
        'price_gov',
        'price_expo',
        'price_customer',
        'source_purchase_history_id',
        'source_product_batch_id',
        'copied_at',
        'converted_from_id',
        'removed_at',
    ];

    protected $casts = [
        'is_non_batch' => 'boolean',
        'status' => 'boolean',
        'mrp_price' => 'float',
        'qty' => 'float',
        'free_qty' => 'float',
        'purchase_rate' => 'float',
        'tax_percent' => 'float',
        'scheme' => 'float',
        'batch_discount_percent' => 'float',
        'product_discount_percent' => 'float',
        'scheme_discount_percent' => 'float',
        'price_pts' => 'float',
        'price_ptr' => 'float',
        'price_ptd' => 'float',
        'price_gov' => 'float',
        'price_expo' => 'float',
        'price_customer' => 'float',
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'copied_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    public static function tableReady(): bool
    {
        return Schema::hasTable('batch_masters');
    }

    public static function extendedColumnsReady(): bool
    {
        return self::tableReady() && Schema::hasColumn('batch_masters', 'free_qty') && Schema::hasColumn('batch_masters', 'removed_at');
    }

    public static function adjustmentsReady(): bool
    {
        return Schema::hasTable('batch_adjustments');
    }

    public static function isTruthy($value): bool
    {
        return in_array($value, [true, 1, '1', 'yes', 'y', 'on'], true);
    }

    public static function normalizeMonth($value, bool $endOfMonth = false): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            $date = \Carbon\Carbon::createFromFormat('Y-m-d', $value . '-01');

            return $endOfMonth ? $date->endOfMonth()->toDateString() : $date->toDateString();
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $matched)) {
            $date = \Carbon\Carbon::parse($matched[1]);

            return $endOfMonth ? $date->endOfMonth()->toDateString() : $date->toDateString();
        }

        return null;
    }

    public static function decodeRolePrices($raw): array
    {
        $prices = is_string($raw) ? json_decode($raw, true) : (array) $raw;
        if (!is_array($prices)) {
            $prices = [];
        }

        $out = [];
        foreach (array_keys(self::ROLE_KEYS) as $key) {
            $value = $prices[$key] ?? null;
            $out[$key] = ($value === null || $value === '') ? null : round((float) $value, 4);
        }

        return $out;
    }

    public static function encodeRolePrices(array $input): string
    {
        return json_encode(self::decodeRolePrices($input['role_price'] ?? $input), JSON_UNESCAPED_UNICODE);
    }

    public static function normalize(array $input): array
    {
        $isNonBatch = self::isTruthy($input['is_non_batch'] ?? false);
        $batchCode = trim((string) ($input['batch_code'] ?? ''));
        $purchaseDate = self::parsePurchaseDate($input['purchase_date'] ?? null);
        if ($isNonBatch && $batchCode === '') {
            $batchCode = $purchaseDate ? $purchaseDate->format('Ymd') : '-';
        }

        $mfg = self::normalizeMonth($input['manufacturing_date'] ?? null, false);
        if ($isNonBatch && $purchaseDate) {
            $mfg = $purchaseDate->toDateString();
        }
        $exp = self::normalizeMonth($input['expiry_date'] ?? null, true);

        $qty = $input['qty'] ?? null;
        $qty = ($qty === null || $qty === '') ? 0.0 : (float) $qty;

        $mrp = $input['mrp_price'] ?? null;
        $mrp = ($mrp === null || $mrp === '') ? null : (float) $mrp;

        $roleInput = $input['role_price'] ?? [];
        if (!is_array($roleInput)) {
            $roleInput = self::decodeRolePrices($roleInput);
        }

        $prices = self::decodeRolePrices($roleInput);
        foreach (self::PRICE_COLUMNS as $key => $column) {
            if (array_key_exists($column, $input) && $input[$column] !== null && $input[$column] !== '') {
                $prices[$key] = round((float) $input[$column], 4);
            }
        }

        $nullable = function ($value) {
            return ($value === null || $value === '') ? null : $value;
        };
        $decimal = function ($value) {
            return ($value === null || $value === '') ? null : round((float) $value, 4);
        };

        return [
            'product_id' => $input['product_id'] !== null && $input['product_id'] !== '' ? (int) $input['product_id'] : null,
            'product_stock_id' => $input['product_stock_id'] !== null && $input['product_stock_id'] !== '' ? (int) $input['product_stock_id'] : null,
            'batch_code' => $batchCode,
            'is_non_batch' => $isNonBatch,
            'manufacturing_date' => $mfg,
            'expiry_date' => $exp,
            'mrp_price' => $mrp,
            'qty' => $qty,
            'role_price' => self::encodeRolePrices($prices),
            'status' => array_key_exists('status', $input) ? self::isTruthy($input['status']) : true,
            'drug_name' => $nullable($input['drug_name'] ?? null),
            'marketed_by_id' => ($input['marketed_by_id'] ?? '') === '' ? null : (int) $input['marketed_by_id'],
            'marketed_by_name' => $nullable($input['marketed_by_name'] ?? null),
            'import_by_ids' => self::encodeIdList($input['import_by_ids'] ?? null),
            'import_by_names' => self::encodeNameList($input['import_by_names'] ?? null),
            'manufactured_by_ids' => self::encodeIdList($input['manufactured_by_ids'] ?? null),
            'manufactured_by_names' => self::encodeNameList($input['manufactured_by_names'] ?? null),
            'company_id' => ($input['company_id'] ?? '') === '' ? null : (int) $input['company_id'],
            'free_qty' => $decimal($input['free_qty'] ?? null),
            'purchase_rate' => $decimal($input['purchase_rate'] ?? null),
            'tax_code' => $nullable($input['tax_code'] ?? null),
            'tax_percent' => $decimal($input['tax_percent'] ?? null),
            'scheme' => $decimal($input['scheme'] ?? null),
            'batch_discount_percent' => $decimal($input['batch_discount_percent'] ?? null),
            'product_discount_percent' => $decimal($input['product_discount_percent'] ?? null),
            'scheme_discount_percent' => $decimal($input['scheme_discount_percent'] ?? null),
            'coa' => ($input['coa'] ?? '') === '' ? null : (int) $input['coa'],
            'price_pts' => $prices['pts'],
            'price_ptr' => $prices['ptr'],
            'price_ptd' => $prices['ptd'],
            'price_gov' => $prices['gov'],
            'price_expo' => $prices['expo'],
            'price_customer' => $prices['customer'],
            'source_purchase_history_id' => ($input['source_purchase_history_id'] ?? '') === '' ? null : (int) $input['source_purchase_history_id'],
            'source_product_batch_id' => ($input['source_product_batch_id'] ?? '') === '' ? null : (int) $input['source_product_batch_id'],
            'copied_at' => $input['copied_at'] ?? null,
            'converted_from_id' => ($input['converted_from_id'] ?? '') === '' ? null : (int) $input['converted_from_id'],
            'purchase_date' => $purchaseDate ? $purchaseDate->toDateString() : null,
        ];
    }

    public static function onlyExistingColumns(array $payload): array
    {
        unset($payload['purchase_date']);
        if (!self::extendedColumnsReady()) {
            foreach (self::EXTENDED_COLUMNS as $column) {
                unset($payload[$column]);
            }
        }

        return $payload;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stock()
    {
        return $this->belongsTo(ProductStock::class, 'product_stock_id');
    }

    public function rolePrices(): array
    {
        $prices = self::decodeRolePrices($this->role_price);
        if (!self::extendedColumnsReady()) {
            return $prices;
        }

        foreach (self::PRICE_COLUMNS as $key => $column) {
            $value = $this->getAttribute($column);
            if ($value !== null && $value !== '') {
                $prices[$key] = round((float) $value, 4);
            }
        }

        return $prices;
    }

    public function lineAmount(): ?float
    {
        if ($this->purchase_rate === null || $this->purchase_rate === '') {
            return null;
        }

        return round((float) $this->qty * (float) $this->purchase_rate, 4);
    }

    public function rolePrice(string $key): ?float
    {
        $price = $this->rolePrices()[$key] ?? null;
        if ($price === null || $price === '') {
            return null;
        }

        return round((float) $price, 4);
    }

    public function roleLineValue(string $key): ?float
    {
        $price = $this->rolePrice($key);
        if ($price === null) {
            return null;
        }

        return round((float) $this->qty * $price, 4);
    }

    public static function amountFrom($qty, $rate): ?float
    {
        if ($rate === null || $rate === '') {
            return null;
        }

        return round((float) $qty * (float) $rate, 4);
    }

    public function monthValue(?string $attribute): string
    {
        $date = $this->{$attribute} ?? null;
        if (!$date) {
            return '';
        }

        return \Carbon\Carbon::parse($date)->format('Y-m');
    }

    public static function sortableColumns(): array
    {
        return [
            'sku' => 'product_stocks.sku',
            'product_name' => 'products.name',
            'variant' => 'product_stocks.variant',
            'id' => 'batch_masters.id',
            'batch_code' => 'batch_masters.batch_code',
            'is_non_batch' => 'batch_masters.is_non_batch',
            'manufacturing_date' => 'batch_masters.manufacturing_date',
            'expiry_date' => 'batch_masters.expiry_date',
            'mrp_price' => 'batch_masters.mrp_price',
            'qty' => 'batch_masters.qty',
            'role_price' => 'batch_masters.role_price',
            'created_at' => 'batch_masters.created_at',
            'status' => 'batch_masters.status',
            'updated_at' => 'batch_masters.updated_at',
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
                $nested->where('batch_masters.batch_code', 'like', $like)
                    ->orWhere('product_stocks.sku', 'like', $like)
                    ->orWhere('product_stocks.variant', 'like', $like)
                    ->orWhere('products.name', 'like', $like);

                if (ctype_digit($filters['search'])) {
                    $nested->orWhere('batch_masters.id', (int) $filters['search']);
                }
            });
        }

        if (($filters['sku'] ?? '') !== '') {
            $query->where('product_stocks.sku', 'like', '%' . $filters['sku'] . '%');
        }
        if (($filters['product_name'] ?? '') !== '') {
            $query->where('products.name', 'like', '%' . $filters['product_name'] . '%');
        }
        if (($filters['variant'] ?? '') !== '') {
            $query->where('product_stocks.variant', 'like', '%' . $filters['variant'] . '%');
        }
        if (($filters['batch_code'] ?? '') !== '') {
            $query->where('batch_masters.batch_code', 'like', '%' . $filters['batch_code'] . '%');
        }
        if (($filters['is_non_batch'] ?? '') === '1' || ($filters['is_non_batch'] ?? '') === '0') {
            $query->where('batch_masters.is_non_batch', (int) $filters['is_non_batch']);
        }
        if (($filters['status'] ?? '') === '1' || ($filters['status'] ?? '') === '0') {
            $query->where('batch_masters.status', (int) $filters['status']);
        }
        if (($filters['mfg_from'] ?? '') !== '') {
            $from = self::normalizeMonth($filters['mfg_from'], false);
            if ($from) {
                $query->whereDate('batch_masters.manufacturing_date', '>=', $from);
            }
        }
        if (($filters['mfg_to'] ?? '') !== '') {
            $to = self::normalizeMonth($filters['mfg_to'], true);
            if ($to) {
                $query->whereDate('batch_masters.manufacturing_date', '<=', $to);
            }
        }
        if (($filters['exp_from'] ?? '') !== '') {
            $from = self::normalizeMonth($filters['exp_from'], false);
            if ($from) {
                $query->whereDate('batch_masters.expiry_date', '>=', $from);
            }
        }
        if (($filters['exp_to'] ?? '') !== '') {
            $to = self::normalizeMonth($filters['exp_to'], true);
            if ($to) {
                $query->whereDate('batch_masters.expiry_date', '<=', $to);
            }
        }
        if (($filters['mrp_from'] ?? '') !== '') {
            $query->where('batch_masters.mrp_price', '>=', (float) $filters['mrp_from']);
        }
        if (($filters['mrp_to'] ?? '') !== '') {
            $query->where('batch_masters.mrp_price', '<=', (float) $filters['mrp_to']);
        }
        if (($filters['qty_from'] ?? '') !== '') {
            $query->where('batch_masters.qty', '>=', (float) $filters['qty_from']);
        }
        if (($filters['qty_to'] ?? '') !== '') {
            $query->where('batch_masters.qty', '<=', (float) $filters['qty_to']);
        }
        if (($filters['date_from'] ?? '') !== '') {
            $query->whereDate('batch_masters.created_at', '>=', $filters['date_from']);
        }
        if (($filters['date_to'] ?? '') !== '') {
            $query->whereDate('batch_masters.created_at', '<=', $filters['date_to']);
        }
        if (($filters['updated_from'] ?? '') !== '') {
            $query->whereDate('batch_masters.updated_at', '>=', $filters['updated_from']);
        }
        if (($filters['updated_to'] ?? '') !== '') {
            $query->whereDate('batch_masters.updated_at', '<=', $filters['updated_to']);
        }

        return $query;
    }

    public static function parsePurchaseDate($value): ?\Carbon\Carbon
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
            return \Carbon\Carbon::createFromFormat('d-m-Y', $value)->startOfDay();
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return \Carbon\Carbon::parse(substr($value, 0, 10))->startOfDay();
        }

        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $value . '-01')->startOfDay();
        }

        return null;
    }

    public static function encodeIdList($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $value)));
        }

        $ids = collect((array) $value)
            ->filter(function ($id) {
                return $id !== null && $id !== '';
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values()
            ->all();

        return $ids ? json_encode($ids) : null;
    }

    public static function encodeNameList($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = [$value];
            }
        }

        $names = collect((array) $value)
            ->map(function ($name) {
                return trim((string) $name);
            })
            ->filter()
            ->values()
            ->all();

        return $names ? json_encode(array_values($names)) : null;
    }

    public static function assembleCopy(array $sources): array
    {
        $live = $sources['live'] ?? [];
        $purchase = $sources['purchase'] ?? [];
        $tax = $sources['tax'] ?? [];
        $header = $sources['header'] ?? [];
        $discounts = $sources['discounts'] ?? [];
        $role = self::decodeRolePrices($live['role_price'] ?? ($header['role_price'] ?? []));
        $purchaseDate = $purchase['invoice_date'] ?? ($purchase['order_date'] ?? null);
        $isNonBatch = self::isTruthy($live['is_non_batch'] ?? false);

        return self::normalize([
            'product_id' => $header['product_id'] ?? null,
            'product_stock_id' => $header['product_stock_id'] ?? null,
            'batch_code' => $live['batch'] ?? ($purchase['batch_number'] ?? ''),
            'is_non_batch' => $isNonBatch,
            'purchase_date' => $isNonBatch ? $purchaseDate : null,
            'manufacturing_date' => $live['manufacturing_date'] ?? null,
            'expiry_date' => $live['product_exp_date'] ?? ($live['expiry_date'] ?? ($purchase['expiry_date'] ?? null)),
            'mrp_price' => $purchase['mrp_rate'] ?? ($live['mrp_price'] ?? null),
            'qty' => $live['qty'] ?? ($purchase['quantity'] ?? 0),
            'free_qty' => $purchase['free'] ?? null,
            'purchase_rate' => $purchase['sale_rate'] ?? null,
            'tax_code' => $purchase['tax_code'] ?? ($tax['tax_code'] ?? null),
            'tax_percent' => $tax['purchase_tax'] ?? ($purchase['gst_percentage'] ?? null),
            'scheme' => $live['scheme'] ?? null,
            'role_price' => $role,
            'drug_name' => $header['drug_name'] ?? null,
            'marketed_by_id' => $header['marketed_by_id'] ?? null,
            'marketed_by_name' => $header['marketed_by_name'] ?? null,
            'import_by_ids' => $header['import_by_ids'] ?? null,
            'import_by_names' => $header['import_by_names'] ?? null,
            'manufactured_by_ids' => $header['manufactured_by_ids'] ?? null,
            'manufactured_by_names' => $header['manufactured_by_names'] ?? null,
            'company_id' => $header['company_id'] ?? null,
            'batch_discount_percent' => $discounts['batchwise'] ?? null,
            'product_discount_percent' => $discounts['productwise'] ?? null,
            'scheme_discount_percent' => $discounts['schemewise'] ?? null,
            'source_purchase_history_id' => $purchase['id'] ?? null,
            'source_product_batch_id' => $live['id'] ?? null,
            'copied_at' => $sources['copied_at'] ?? null,
            'status' => true,
        ]);
    }

    public static function duplicateUpload($uploadId): ?int
    {
        if (!$uploadId) {
            return null;
        }

        $upload = Upload::withoutGlobalScopes(['not_hidden'])->find($uploadId);
        if (!$upload) {
            return null;
        }

        $copy = $upload->replicate();
        $disk = $upload->disk ?: 'local';
        $path = ltrim((string) $upload->file_name, '/');
        try {
            if ($path !== '' && Storage::disk($disk)->exists($path)) {
                $extension = pathinfo($path, PATHINFO_EXTENSION);
                $newPath = 'uploads/batch-master/' . uniqid('coa_', true) . ($extension ? '.' . $extension : '');
                Storage::disk($disk)->copy($path, $newPath);
                $copy->file_name = $newPath;
            }
        } catch (\Throwable $e) {
            $copy->file_name = $upload->file_name;
        }

        $copy->user_id = auth()->id() ?: $upload->user_id;
        $copy->save();

        return (int) $copy->id;
    }

    public static function copyMissingForStock(int $stockId): int
    {
        if (!self::extendedColumnsReady()) {
            return 0;
        }

        $stock = ProductStock::with('product')->find($stockId);
        if (!$stock) {
            return 0;
        }

        $existing = self::query()
            ->where('product_stock_id', $stock->id)
            ->pluck('batch_code')
            ->map(function ($code) {
                return (string) $code;
            })
            ->all();

        $inserted = 0;
        foreach (self::sourceBundles($stock) as $bundle) {
            $payload = self::onlyExistingColumns(self::assembleCopy($bundle));
            $code = (string) ($payload['batch_code'] ?? '');
            if ($code === '' || in_array($code, $existing, true)) {
                continue;
            }

            $sourceCoa = $bundle['live']['coa'] ?? null;
            if ($sourceCoa) {
                $payload['coa'] = self::duplicateUpload($sourceCoa);
            }

            $row = new self();
            $row->fill($payload);
            $row->copied_at = now();
            $row->save();
            $existing[] = $code;
            $inserted++;
        }

        return $inserted;
    }

    public static function sourceBundles(ProductStock $stock): array
    {
        $header = self::headerFromStock($stock);
        $discounts = self::discountPercentsForStock($stock);
        $lives = ProductBatch::query()->where('product_stock_id', $stock->id)->orderBy('id')->get();
        $latestPurchase = self::latestPurchasesForSku($stock->sku);
        $bundles = [];
        $seen = [];

        foreach ($lives as $live) {
            $code = trim((string) $live->batch);
            if ($code === '') {
                $code = '-';
            }
            $purchase = $latestPurchase[$code] ?? null;
            $bundles[] = self::bundle($header, $live->toArray(), $purchase, $discounts);
            $seen[$code] = true;
        }

        foreach ($latestPurchase as $code => $purchase) {
            if (isset($seen[$code]) || $code === '') {
                continue;
            }
            $bundles[] = self::bundle($header, ['batch' => $code], $purchase, $discounts);
        }

        return $bundles;
    }

    private static function bundle(array $header, array $live, $purchase, array $discounts): array
    {
        $purchaseRow = $purchase ? (is_array($purchase) ? $purchase : $purchase->toArray()) : [];

        return [
            'header' => $header,
            'live' => $live,
            'purchase' => $purchaseRow,
            'tax' => self::taxSnapshot($purchaseRow['tax_code'] ?? null),
            'discounts' => $discounts,
            'copied_at' => now()->toDateTimeString(),
        ];
    }

    public static function headerFromStock(ProductStock $stock): array
    {
        $product = $stock->product;
        $header = [
            'product_id' => $stock->product_id,
            'product_stock_id' => $stock->id,
            'drug_name' => null,
            'marketed_by_id' => null,
            'marketed_by_name' => null,
            'import_by_ids' => null,
            'import_by_names' => null,
            'manufactured_by_ids' => null,
            'manufactured_by_names' => null,
            'company_id' => null,
        ];

        if (!$product) {
            return $header;
        }

        if (Schema::hasColumn('products', 'drug_name')) {
            $header['drug_name'] = $product->drug_name;
        }
        if (Schema::hasColumn('products', 'marketed_by_id')) {
            $header['marketed_by_id'] = $product->marketed_by_id;
        }
        if (Schema::hasColumn('products', 'marketed_by_name')) {
            $header['marketed_by_name'] = $product->marketed_by_name;
        }
        if (Schema::hasColumn('products', 'import_by_ids')) {
            $header['import_by_ids'] = $product->import_by_ids;
        }
        if (Schema::hasColumn('products', 'import_by_names')) {
            $header['import_by_names'] = $product->import_by_names;
        }
        if (Schema::hasColumn('products', 'manufactured_by_ids')) {
            $header['manufactured_by_ids'] = $product->manufactured_by_ids;
        }
        if (Schema::hasColumn('products', 'manufactured_by_names')) {
            $header['manufactured_by_names'] = $product->manufactured_by_names;
        }

        $makerIds = json_decode((string) ($header['manufactured_by_ids'] ?? ''), true);
        if (is_array($makerIds) && isset($makerIds[0])) {
            $header['company_id'] = (int) $makerIds[0];
        } elseif (!empty($header['marketed_by_id'])) {
            $header['company_id'] = (int) $header['marketed_by_id'];
        }

        if (!empty($header['marketed_by_id']) && Schema::hasTable('companies')) {
            $company = Company::find($header['marketed_by_id']);
            if ($company && $company->company_name) {
                $header['marketed_by_name'] = $company->company_name;
            }
        }

        return $header;
    }

    public static function latestPurchasesForSku(?string $sku): array
    {
        if (!$sku || !Schema::hasTable('purchase_history')) {
            return [];
        }

        $rows = PurchaseHistory::query()->where('product_sku', $sku)->orderByDesc('id')->get();
        $latest = [];
        foreach ($rows as $row) {
            $code = trim((string) $row->batch_number);
            if ($code === '' || isset($latest[$code])) {
                continue;
            }
            $latest[$code] = $row;
        }

        return $latest;
    }

    public static function taxSnapshot(?string $taxCode): array
    {
        if (!$taxCode || !TaxMaster::tableReady()) {
            return [];
        }

        $tax = TaxMaster::query()->where('tax_code', $taxCode)->first();
        if (!$tax) {
            return [];
        }

        return [
            'tax_code' => $tax->tax_code,
            'purchase_tax' => $tax->purchase_tax,
        ];
    }

    public static function discountPercentsForStock(ProductStock $stock): array
    {
        if (!Schema::hasTable('discount_masters')) {
            return [];
        }

        $out = [];
        $map = [
            'batchwise' => 'value_percent',
            'productwise' => 'value_percent',
            'schemewise' => 'scheme_percent',
        ];
        foreach ($map as $type => $column) {
            $row = DiscountMaster::query()
                ->where('product_stock_id', $stock->id)
                ->where('discount_type', $type)
                ->orderByDesc('id')
                ->first();
            if ($row && $row->{$column} !== null) {
                $out[$type] = $row->{$column};
            }
        }

        return $out;
    }
}
