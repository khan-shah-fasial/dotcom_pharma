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
        'invoice' => 'Invoice',
    ];

    public const DISCOUNT_TYPES = [
        'productwise' => 'Productwise',
        'batchwise' => 'Batchwise',
        'schemewise' => 'Scheme',
        'amount_wise' => 'Amountwise',
        'pointwise' => 'Pointwise',
        'couponwise' => 'Couponwise',
    ];

    public const PRODUCT_TYPES = ['productwise', 'batchwise', 'schemewise'];

    public const BATCH_TYPES = ['batchwise', 'schemewise'];

    public const INVOICE_TYPES = ['amount_wise', 'pointwise', 'couponwise'];

    public const TYPE_NOTES = [
        'productwise' => 'Productwise discount applies to all batches of the selected product.',
        'batchwise' => 'Batchwise discount applies only to the selected batches of the selected product.',
        'schemewise' => 'Scheme applies only to the selected batches of the selected product.',
        'amount_wise' => 'Amountwise discount applies to all selected products.',
        'pointwise' => 'Pointwise discount applies to all selected products.',
        'couponwise' => 'Couponwise discount applies to all selected products.',
    ];

    public const ROLE_KEYS = [
        'pts' => 'PTS',
        'ptr' => 'PTR',
        'ptd' => 'PTD',
        'gov' => 'Govt.',
        'expo' => 'Export',
        'customer' => 'Customers (B2C)',
        'mrp' => 'MRP',
    ];

    public const TYPE_PREFIXES = [
        'batchwise' => 'D-BW-',
        'productwise' => 'D-PW-',
        'pointwise' => 'D-POW-',
        'amount_wise' => 'D-AW-',
        'schemewise' => 'D-SW-',
        'couponwise' => 'D-CW-',
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
        $batch = $this->sourceBatch();

        return self::resolveCoaId(
            $batch ? $batch->coa : null,
            $this->product_stock_id ? (int) $this->product_stock_id : null,
            $batch ? $batch->batch : null,
            optional($this->stock)->coa
        );
    }

    public static function resolveCoaId($primary, ?int $stockId, ?string $batchCode, $stockCoa): ?int
    {
        if ($primary !== null && $primary !== '' && is_numeric($primary)) {
            return (int) $primary;
        }

        $batchCode = trim((string) $batchCode);
        if ($stockId && BatchMaster::tableReady() && \Illuminate\Support\Facades\Schema::hasColumn('batch_masters', 'coa')) {
            $query = BatchMaster::query()
                ->where('product_stock_id', $stockId)
                ->whereNotNull('coa')
                ->where('coa', '!=', '')
                ->where('coa', '!=', 0);
            if (BatchMaster::extendedColumnsReady()) {
                $query->whereNull('removed_at');
            }
            if ($batchCode !== '') {
                $matched = (clone $query)->where('batch_code', $batchCode)->orderByDesc('id')->value('coa');
                if ($matched !== null && $matched !== '' && is_numeric($matched)) {
                    return (int) $matched;
                }
            }
        }

        if ($stockCoa !== null && $stockCoa !== '' && is_numeric($stockCoa)) {
            return (int) $stockCoa;
        }

        return null;
    }

    public function sheetPayload(): array
    {
        $raw = $this->getAttributes()['sheet_payload'] ?? null;
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function discountValueLines(): array
    {
        $type = (string) $this->discount_type;
        $lines = [];

        if (in_array($type, ['productwise', 'batchwise'], true)) {
            foreach ($this->collectedSlabs() as $index => $slab) {
                $lines[] = $this->valueLine('Type', $this->typeWord($slab['value_type'] ?? null), $index);
                $lines[] = $this->valueLine('Amount', $this->numText($slab['amount'] ?? null), $index);
                $lines[] = $this->valueLine('%', $this->numText($slab['percent'] ?? null), $index);
            }
        } elseif ($type === 'schemewise') {
            foreach ($this->collectedSlabs() as $index => $slab) {
                $lines[] = $this->valueLine('Free Qty', $this->numText($slab['free_qty'] ?? null, 3), $index);
                $lines[] = $this->valueLine('Scheme %', $this->numText($slab['scheme_percent'] ?? null), $index);
                $lines[] = $this->valueLine('Scheme value', $this->numText($slab['scheme_value'] ?? null), $index);
            }
        } elseif ($type === 'pointwise') {
            foreach ($this->collectedAmounts() as $index => $row) {
                $lines[] = $this->valueLine('Earn', $this->numText($row['amount'] ?? null, 3), $index);
                $lines[] = $this->valueLine('Amount Or %', $this->numText($row['amount'] ?? null), $index);
                $lines[] = $this->valueLine('% Or Amount', $this->numText($row['percent'] ?? null), $index);
            }
        } elseif ($type === 'amount_wise') {
            foreach ($this->collectedAmounts() as $index => $row) {
                $lines[] = $this->valueLine('Invoice Amount', $this->numText($row['to'] ?? $row['slab'] ?? null), $index);
                $lines[] = $this->valueLine('Type', $this->typeWord($row['value_type'] ?? null), $index);
                $lines[] = $this->valueLine('Amount Or %', $this->numText(($row['value_type'] ?? '') === 'percent' ? ($row['percent'] ?? null) : ($row['amount'] ?? null)), $index);
            }
        } elseif ($type === 'couponwise') {
            foreach ($this->collectedAmounts() as $index => $row) {
                $lines[] = $this->valueLine('Type', $this->typeWord($row['value_type'] ?? null), $index);
                $lines[] = $this->valueLine('Amount', $this->numText($row['amount'] ?? null), $index);
                $lines[] = $this->valueLine('%', $this->numText($row['percent'] ?? null), $index);
            }
        }

        return $lines;
    }

    public function roleSlabLines(): array
    {
        $lines = [];
        foreach ($this->collectedSlabs() as $index => $slab) {
            $qty = $this->numText($slab['qty'] ?? null, 3);
            if (!empty($slab['qty_to'])) {
                $qty .= ' – ' . $this->numText($slab['qty_to'], 3);
            }
            $lines[] = $this->valueLine('Qty Slab', $qty, $index);
            $lines[] = $this->valueLine('Rate', $this->numText($slab['rate'] ?? null), $index);
            $lines[] = $this->valueLine('Amount', $this->numText($slab['line_amount'] ?? $slab['amount'] ?? null), $index);
            $lines[] = $this->valueLine('Effective Rate', $this->numText($slab['effective_rate'] ?? null), $index);
        }

        if (in_array($this->discount_type, self::INVOICE_TYPES, true)) {
            $lines = [];
            foreach ($this->collectedAmounts() as $index => $row) {
                $lines[] = $this->valueLine('From', $this->numText($row['from'] ?? null), $index);
                $lines[] = $this->valueLine('To', $this->numText($row['to'] ?? null), $index);
                $lines[] = $this->valueLine('Amount', $this->numText($row['amount'] ?? null), $index);
                $effective = array_key_exists('effective', $row) && $row['effective'] !== '' && $row['effective'] !== null
                    ? $row['effective']
                    : ($index === 0 ? $this->effective_rate : null);
                $lines[] = $this->valueLine('Effective Rate', $this->numText($effective), $index);
            }
        }

        return $lines;
    }

    public function stockValueLines(): array
    {
        $prices = $this->liveRolePrices();
        $qty = $this->sourceQty();
        $lines = [];
        foreach (self::ROLE_KEYS as $key => $label) {
            $price = $prices[$key] ?? null;
            $value = ($price !== null && $qty !== null && $qty !== '') ? (float) $price * (float) $qty : null;
            $lines[] = [
                'key' => $key,
                'label' => $label,
                'price' => $price === null ? '—' : $this->numText($price),
                'value' => $value === null ? '—' : $this->numText($value),
            ];
        }

        return $lines;
    }

    public function totalDiscountLabel(): string
    {
        if ($this->discount_type === 'pointwise') {
            $points = 0.0;
            $seen = false;
            foreach ($this->collectedAmounts() as $row) {
                if (($row['amount'] ?? '') !== '' && $row['amount'] !== null) {
                    $points += (float) $row['amount'];
                    $seen = true;
                }
            }
            if (!$seen) {
                return '—';
            }

            return rtrim(rtrim(number_format($points, 3, '.', ''), '0'), '.') . ' ' . translate('points');
        }

        $amount = $this->totalDiscountAmount();
        if ($amount === null) {
            return '—';
        }

        return number_format($amount, 2, '.', '');
    }

    public function totalDiscountAmount(): ?float
    {
        if ($this->discount_type === 'pointwise') {
            return null;
        }

        $total = 0.0;
        $seen = false;
        if (in_array($this->discount_type, ['productwise', 'batchwise'], true)) {
            foreach ($this->collectedSlabs() as $slab) {
                $qty = (float) ($slab['qty'] ?? 0);
                $rate = (float) ($slab['rate'] ?? 0);
                if (($slab['value_type'] ?? 'flat') === 'percent') {
                    if (($slab['percent'] ?? '') === '' || $slab['percent'] === null) {
                        continue;
                    }
                    $unit = $rate * (float) $slab['percent'] / 100;
                } else {
                    if (($slab['amount'] ?? '') === '' || $slab['amount'] === null) {
                        continue;
                    }
                    $unit = (float) $slab['amount'];
                }
                $total += $qty > 0 ? $unit * $qty : $unit;
                $seen = true;
            }
        } elseif ($this->discount_type === 'schemewise') {
            foreach ($this->collectedSlabs() as $slab) {
                if (($slab['scheme_value'] ?? '') !== '' && $slab['scheme_value'] !== null) {
                    $total += (float) $slab['scheme_value'];
                    $seen = true;
                } elseif (($slab['free_qty'] ?? '') !== '' && $slab['free_qty'] !== null) {
                    $total += (float) $slab['free_qty'] * (float) ($slab['rate'] ?? 0);
                    $seen = true;
                }
            }
        } elseif (in_array($this->discount_type, ['amount_wise', 'couponwise'], true)) {
            foreach ($this->collectedAmounts() as $row) {
                if (($row['value_type'] ?? 'flat') === 'percent') {
                    if (($row['percent'] ?? '') === '' || $row['percent'] === null) {
                        continue;
                    }
                    $base = (float) ($row['to'] ?? $row['slab'] ?? $row['from'] ?? 0);
                    $total += $base * (float) $row['percent'] / 100;
                } else {
                    if (($row['amount'] ?? '') === '' || $row['amount'] === null) {
                        continue;
                    }
                    $total += (float) $row['amount'];
                }
                $seen = true;
            }
        }

        return $seen ? $total : null;
    }

    private function collectedSlabs(): array
    {
        $slabs = [];
        foreach ($this->sheetPayload()['roles'] ?? [] as $role) {
            foreach ($role['slabs'] ?? [] as $slab) {
                if (is_array($slab)) {
                    $slabs[] = $slab;
                }
            }
        }

        if ($slabs !== []) {
            return $slabs;
        }

        return [[
            'qty' => $this->qty_slab_from,
            'value_type' => $this->value_type,
            'amount' => $this->value_amount,
            'percent' => $this->value_percent,
            'rate' => $this->rate,
            'line_amount' => $this->amount,
            'effective_rate' => $this->effective_rate,
            'free_qty' => $this->scheme_free_qty,
            'scheme_percent' => $this->scheme_percent,
            'scheme_value' => $this->scheme_value,
        ]];
    }

    private function collectedAmounts(): array
    {
        $rows = [];
        foreach ($this->sheetPayload()['amounts'] ?? [] as $row) {
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        if ($rows !== []) {
            return $rows;
        }

        return [[
            'slab' => $this->invoice_amount,
            'from' => $this->qty_slab_from,
            'to' => $this->qty_slab_to,
            'value_type' => $this->value_type,
            'amount' => $this->discount_type === 'pointwise' ? $this->earn : $this->value_amount,
            'percent' => $this->value_percent,
            'effective' => $this->effective_rate,
        ]];
    }

    private function valueLine(string $label, string $value, int $index): array
    {
        return [
            'label' => $label,
            'value' => $value,
            'group' => $index,
        ];
    }

    private function typeWord($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if ($value === 'flat') {
            return 'Flat';
        }
        if ($value === 'percent') {
            return 'Percent';
        }

        return (string) $value;
    }

    private function numText($value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (!is_numeric($value)) {
            return (string) $value;
        }

        return number_format((float) $value, $decimals, '.', '');
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
