<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;
use App\Models\ProductBatch;

class ProductStock extends Model
{
    use PreventDemoModeChanges;

    // protected $fillable = ['product_id', 'variant', 'sku', 'price', 'per_piece_price', 'qty', 'image'];
    // protected $fillable = ['product_id', 'variant', 'sku', 'price','mrp_price','mrp_role_price','dimension','length','width','height','weight','count','qty', 'image'];
    // protected $fillable = ['product_id', 'variant', 'sku', 'price','mrp_price','mrp_role_price','length','width','height','weight','count','qty', 'image'];
    protected $fillable = [
        'product_id',
        'variant',
        'id_variant',
        'is_hidden',
        'sku',
        'price',
        'mrp_price',
        'length',
        'width',
        'height',
        'weight',
        'count',
        'min_qty',
        'scheme',
        'product_exp_date',
        'qty_per_piece',
        'qty_per_buffer_box',
        'total_qty_per_case',
        'weight_buffer_box',
        'weight_case',
        'buffer_length',
        'buffer_width',
        'buffer_height',
        'case_length',
        'case_width',
        'case_height',
        'qty',
        'coa',
        'image'
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
        'scheme' => 'integer',
    ];
    //
    public function product(){
    	return $this->belongsTo(Product::class);
    }

    public function wholesalePrices() {
        return $this->hasMany(WholesalePrice::class);
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class, 'product_stock_id');
    }

    protected static array $variantLabelCache = [];

    public static function warmVariantLabelCache(array $values): void
    {
        $values = array_values(array_unique(array_filter($values)));
        if (empty(static::$variantLabelCache) && !empty($values)) {
            $normalizedMap = [];
            $uniqueNormalized = [];
            foreach ($values as $v) {
                $normalized = str_replace(' ', '', $v);
                $normalizedMap[$normalized] = $v;
                $uniqueNormalized[] = $normalized;
            }
            $uniqueNormalized = array_values(array_unique($uniqueNormalized));

            $attrValues = AttributeValue::whereIn('value', $uniqueNormalized)
                ->with('attribute')
                ->get();

            foreach ($values as $v) {
                $normalized = str_replace(' ', '', $v);
                $match = $attrValues->firstWhere('value', $normalized);
                if ($match && $match->attribute) {
                    static::$variantLabelCache[$v] = '(' . $match->attribute->name . ') - ' . $match->value;
                } else {
                    static::$variantLabelCache[$v] = $v;
                }
            }
        }
    }

    public function expandedVariantLabel(): string
    {
        $variant = trim((string) $this->variant);
        if ($variant === '') {
            return '';
        }

        $parts = preg_split('/[-_\/]+/', $variant) ?: [];
        $details = [];

        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }

            if (isset(static::$variantLabelCache[$part])) {
                $details[] = static::$variantLabelCache[$part];
                continue;
            }

            $normalizedPart = str_replace(' ', '', $part);
            $attrValue = AttributeValue::where('value', $normalizedPart)->first();
            if ($attrValue && $attrValue->attribute) {
                static::$variantLabelCache[$part] = '(' . $attrValue->attribute->name . ') - ' . $attrValue->value;
            } else {
                static::$variantLabelCache[$part] = $part;
            }

            $details[] = static::$variantLabelCache[$part];
        }

        return $details ? implode(' / ', $details) : $variant;
    }

    public function hasExpandedVariantInfo(): bool
    {
        $expanded = $this->expandedVariantLabel();
        if ($expanded === '') {
            return false;
        }

        return strpos($expanded, '(') !== false && strpos($expanded, ')') !== false;
    }

    public function fullLookupLabel(?Product $product = null): string
    {
        $productName = $product ? $product->getTranslation('name') : ($this->product ? $this->product->getTranslation('name') : '');
        $label = $productName;
        if ($this->sku) {
            $label .= ' / ' . $this->sku;
        }
        if ($this->hasExpandedVariantInfo()) {
            $label .= ' / ' . $this->expandedVariantLabel();
        }
        if (!$this->sku && !$this->hasExpandedVariantInfo()) {
            $label .= ' / #' . $this->id;
        }
        return trim($label);
    }

    public function dimensionSheet()
    {
        return $this->hasOne(ProductStockDimension::class, 'product_stock_id');
    }
}