<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class PurchaseHistory extends Model
{
    protected $table = 'purchase_history';

    protected $fillable = [
        'serial_number',
        'order_date',
        'order_number',
        'invoice_date',
        'invoice_series',
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
        'tax_code',
        'gst_percentage',
        'gst_amount',
        'final_amount',
        'sales_man_name',
        'sales_man_code',
        'case_value',
        'packing',
        'transport',
        'book_to',
        'lr_number',
        'lr_date',
        'late_by',
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

    /**
     * Combine report lines only when their order, bill, batch and pricing match.
     */
    public static function mergeReportRows(Collection $records): Collection
    {
        return $records->groupBy(function (self $record) {
            if (! filled($record->order_number) || ! filled($record->invoice_number)) {
                return 'record:' . $record->getKey();
            }

            return json_encode([
                $record->ac_number,
                $record->order_number,
                $record->invoice_series,
                $record->invoice_number,
                $record->product_sku,
                $record->batch_number,
                $record->sale_rate,
                $record->discount,
                $record->mrp_rate,
                $record->tax_code,
                $record->gst_percentage,
            ]);
        })->map(function (Collection $group) {
            $record = clone $group->first();

            foreach (['quantity', 'free', 'taxable_amount', 'gst_amount', 'final_amount'] as $field) {
                $record->setAttribute($field, $group->sum(
                    fn (self $item) => (float) str_replace(',', '', (string) ($item->{$field} ?? 0))
                ));
            }

            return $record;
        })->values();
    }
}

