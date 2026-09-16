<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountMaster extends Model
{
    public const APPLIED_ON = [
        'sku' => 'SKU Product',
        'full_variant' => 'Full Variant',
        'batch' => 'Same as Batch / Lot.No',
        'category' => 'Category (Main)',
        'group' => 'Group',
        'customer' => 'Customer',
    ];

    public const DISCOUNT_TYPES = [
        'batchwise' => 'Batchwise Discount',
        'productwise' => 'Productwise Discount',
        'pointwise' => 'Pointwise Earn',
        'amount_wise' => 'Amount wise Discount',
        'schemewise' => 'Schemewise Discount',
    ];

    public const ROLE_KEYS = [
        'pts' => 'PTS',
        'ptr' => 'PTR',
        'ptd' => 'PTD',
        'gov' => 'Govt.',
        'expo' => 'Export',
        'customer' => 'Customers (B2C)',
        'mrp' => 'M.R.P',
    ];

    public const TYPE_PREFIXES = [
        'batchwise' => 'D-BW-',
        'productwise' => 'D-PW-',
        'pointwise' => 'D-POW-',
        'amount_wise' => 'D-AW-',
        'schemewise' => 'D-SW-',
    ];

    protected $fillable = [
        'applied_on',
        'product_id',
        'product_stock_id',
        'batch_id',
        'category_id',
        'group_id',
        'customer_id',
        'role_key',
        'qty_slab_from',
        'qty_slab_to',
        'rate',
        'amount',
        'effective_rate',
        'discount_type',
        'discount_code',
        'value_type',
        'value_amount',
        'value_percent',
        'earn',
        'invoice_amount',
        'scheme_free_qty',
        'scheme_percent',
        'scheme_value',
        'scheme_product_is_same',
        'scheme_product_stock_id',
        'from_date',
        'to_date',
        'status',
    ];

    protected $casts = [
        'qty_slab_from' => 'float',
        'qty_slab_to' => 'float',
        'rate' => 'float',
        'amount' => 'float',
        'effective_rate' => 'float',
        'value_amount' => 'float',
        'value_percent' => 'float',
        'earn' => 'float',
        'invoice_amount' => 'float',
        'scheme_free_qty' => 'float',
        'scheme_percent' => 'float',
        'scheme_value' => 'float',
        'scheme_product_is_same' => 'boolean',
        'status' => 'boolean',
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stock()
    {
        return $this->belongsTo(ProductStock::class, 'product_stock_id');
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function schemeStock()
    {
        return $this->belongsTo(ProductStock::class, 'scheme_product_stock_id');
    }

    public function appliedOnLabel(): string
    {
        return self::APPLIED_ON[$this->applied_on] ?? (string) $this->applied_on;
    }

    public function discountTypeLabel(): string
    {
        return self::DISCOUNT_TYPES[$this->discount_type] ?? (string) $this->discount_type;
    }

    public function roleKeyLabel(): string
    {
        return self::ROLE_KEYS[$this->role_key] ?? (string) ($this->role_key ?? '—');
    }

    public function customerDisplayName(): string
    {
        $customer = $this->customer;
        if (!$customer) {
            return '—';
        }

        $company = optional($customer->user_details)->company_name;

        return $company ?: ($customer->name ?: '—');
    }

    public static function nextCode(string $discountType): string
    {
        $prefix = self::TYPE_PREFIXES[$discountType] ?? 'D-XX-';
        $latest = static::query()
            ->where('discount_code', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('discount_code');

        $next = 1;
        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    public function sourceBatch(): ?ProductBatch
    {
        if ($this->batch) {
            return $this->batch;
        }

        $stock = $this->stock;
        if ($stock && $stock->relationLoaded('batches')) {
            return $stock->batches->sortByDesc('id')->first();
        }

        return null;
    }

    public function liveRolePrices(): array
    {
        return self::decodeRolePrices($this->sourceBatch(), $this->stock, $this->product);
    }

    public function sourceQty()
    {
        if ($this->applied_on === 'batch' && $this->batch) {
            return $this->batch->qty;
        }

        if (in_array($this->applied_on, ['sku', 'full_variant'], true) && $this->stock) {
            return $this->stock->qty;
        }

        $batch = $this->sourceBatch();

        return $batch ? $batch->qty : optional($this->stock)->qty;
    }

    public function sourceCoa()
    {
        return optional($this->sourceBatch())->coa ?? optional($this->stock)->coa;
    }

    public static function decodeRolePrices(?ProductBatch $batch, ?ProductStock $stock, ?Product $product): array
    {
        $json = null;
        if ($batch && $batch->role_price) {
            $json = $batch->role_price;
        } elseif ($product && $product->role_price) {
            $json = $product->role_price;
        }

        $prices = is_string($json) ? json_decode($json, true) : (array) $json;
        if (!is_array($prices)) {
            $prices = [];
        }

        $mrp = null;
        if ($batch && $batch->mrp_price !== null && $batch->mrp_price !== '') {
            $mrp = (float) $batch->mrp_price;
        } elseif ($stock && $stock->mrp_price !== null && $stock->mrp_price !== '') {
            $mrp = (float) $stock->mrp_price;
        } elseif ($product && $product->mrp_price !== null && $product->mrp_price !== '') {
            $mrp = (float) $product->mrp_price;
        }

        return [
            'pts' => isset($prices['pts']) ? (float) $prices['pts'] : null,
            'ptr' => isset($prices['ptr']) ? (float) $prices['ptr'] : null,
            'ptd' => isset($prices['ptd']) ? (float) $prices['ptd'] : null,
            'gov' => isset($prices['gov']) ? (float) $prices['gov'] : null,
            'expo' => isset($prices['expo']) ? (float) $prices['expo'] : null,
            'customer' => isset($prices['customer']) ? (float) $prices['customer'] : null,
            'mrp' => $mrp,
        ];
    }
}
