<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class ProductStock extends Model
{
    use PreventDemoModeChanges;

    // protected $fillable = ['product_id', 'variant', 'sku', 'price', 'per_piece_price', 'qty', 'image'];
    protected $fillable = ['product_id', 'variant', 'sku', 'price','mrp_price','mrp_role_price','dimension','length','width','height','weight','count','qty', 'image'];
    //
    public function product(){
    	return $this->belongsTo(Product::class);
    }

    public function wholesalePrices() {
        return $this->hasMany(WholesalePrice::class);
    }
}
