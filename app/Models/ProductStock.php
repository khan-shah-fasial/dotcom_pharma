<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class ProductStock extends Model
{
    use PreventDemoModeChanges;

    // protected $fillable = ['product_id', 'variant', 'sku', 'price', 'per_piece_price', 'qty', 'image'];
    // protected $fillable = ['product_id', 'variant', 'sku', 'price','mrp_price','mrp_role_price','dimension','length','width','height','weight','count','qty', 'image'];
    // protected $fillable = ['product_id', 'variant', 'sku', 'price','mrp_price','mrp_role_price','length','width','height','weight','count','qty', 'image'];
    protected $fillable = [
        'product_id',
        'variant',
        'sku',
        'price',
        'mrp_price',
        'length',
        'width',
        'height',
        'weight',
        'count',
        'min_qty',
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
    //
    public function product(){
    	return $this->belongsTo(Product::class);
    }

    public function wholesalePrices() {
        return $this->hasMany(WholesalePrice::class);
    }
}
