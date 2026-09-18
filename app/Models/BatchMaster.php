<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
    ];

    protected $casts = [
        'is_non_batch' => 'boolean',
        'status' => 'boolean',
        'mrp_price' => 'float',
        'qty' => 'float',
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
    ];

    public static function tableReady(): bool
    {
        return Schema::hasTable('batch_masters');
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

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $date = \Carbon\Carbon::parse($value);

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
        if ($isNonBatch && $batchCode === '') {
            $batchCode = '-';
        }

        $mfg = $isNonBatch ? null : self::normalizeMonth($input['manufacturing_date'] ?? null, false);
        $exp = $isNonBatch ? null : self::normalizeMonth($input['expiry_date'] ?? null, true);

        $qty = $input['qty'] ?? null;
        $qty = ($qty === null || $qty === '') ? 0.0 : (float) $qty;

        $mrp = $input['mrp_price'] ?? null;
        $mrp = ($mrp === null || $mrp === '') ? null : (float) $mrp;

        $roleInput = $input['role_price'] ?? [];
        if (!is_array($roleInput)) {
            $roleInput = self::decodeRolePrices($roleInput);
        }

        return [
            'product_id' => $input['product_id'] !== null && $input['product_id'] !== '' ? (int) $input['product_id'] : null,
            'product_stock_id' => $input['product_stock_id'] !== null && $input['product_stock_id'] !== '' ? (int) $input['product_stock_id'] : null,
            'batch_code' => $batchCode,
            'is_non_batch' => $isNonBatch,
            'manufacturing_date' => $mfg,
            'expiry_date' => $exp,
            'mrp_price' => $mrp,
            'qty' => $qty,
            'role_price' => self::encodeRolePrices($roleInput),
            'status' => array_key_exists('status', $input) ? self::isTruthy($input['status']) : true,
        ];
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
        return self::decodeRolePrices($this->role_price);
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
}
