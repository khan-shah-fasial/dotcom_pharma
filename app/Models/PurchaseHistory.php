<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseHistory extends Model
{
    protected $table = 'purchase_history';

    protected $fillable = [
        'serial_number',
        'order_date',
        'order_number',
        'invoice_date',
        'invoice_number',
        'ac_number',
        'product_sku',
        'batch_number',
        'expiry_date',
        'quantity',
        'free',
        'sale_rate',
        'mrp_rate',
        'discount',
        'taxable_amount',
        'tax_percentage',
        'tax_amount',
        'final_amount',
        'sales_man_name',
        'sales_man_code',
        'case_value',
        'packing',
        'transport',
        'book_to',
        'lr_number',
        'lr_date',
        'country',
        'state',
        'city',
        'district',
        'pincode',
    ];

    /**
     * Linked customer details via AC Number (crm_id in user_details).
     */
    public function customerDetails()
    {
        return $this->belongsTo(UserDetails::class, 'ac_number', 'crm_id');
    }

    /**
     * Linked product stock via SKU.
     */
    public function productStock()
    {
        return $this->belongsTo(ProductStock::class, 'product_sku', 'sku');
    }
}

