<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class ProductStock extends Model
{
    use PreventDemoModeChanges;

    //protected $fillable = ['product_id', 'variant', 'sku', 'price', 'per_piece_price', 'qty', 'image'];
    protected $fillable = ['product_id', 'variant', 'sku', 'price', 'role_price', 'per_piece_price', 'qty', 'image']; //price by role

    //
    public function product(){
    	return $this->belongsTo(Product::class);
    }

    public function wholesalePrices() {
        return $this->hasMany(WholesalePrice::class);
    }
}
