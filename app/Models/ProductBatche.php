<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBatche extends Model
{
    /**
     * The table associated with the model.
     *
     * Note: table name is `product_batches` (not the Laravel default `product_batchs`).
     */
    protected $table = 'product_batches';

    protected $fillable = [
        'product_id',
        'product_stock_id',
        'batch',
        'mrp_price',
        'role_price',
        'qty',
        'coa',
        'product_exp_date',
    ];

    // protected $casts = [
    //     'role_price' => 'array',
    // ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stock()
    {
        return $this->belongsTo(ProductStock::class, 'product_stock_id');
    }
}

