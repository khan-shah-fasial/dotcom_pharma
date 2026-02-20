<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductNotify extends Model
{
    protected $table = 'product_notify';

    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
