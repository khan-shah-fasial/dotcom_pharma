<?php

namespace App\Models;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseHistoryExport implements FromCollection, WithHeadings, WithMapping
{
    protected $search;
    protected $orderDateFrom;
    protected $orderDateTo;
    protected $productSku;
    protected $salesman;

    public function __construct($search = null, $orderDateFrom = null, $orderDateTo = null, $productSku = null, $salesman = null)
    {
        $this->search = $search;
        $this->orderDateFrom = $orderDateFrom;
        $this->orderDateTo = $orderDateTo;
        $this->productSku = $productSku;
        $this->salesman = $salesman;
    }

    public function collection()
    {
        $query = PurchaseHistory::with([
            'customerDetails',
            'productStock.product.brand',
        ]);

        if ($this->search) {
            $like = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('serial_number', 'like', $like)
                    ->orWhere('order_number', 'like', $like)
                    ->orWhere('invoice_number', 'like', $like)
                    ->orWhere('product_sku', 'like', $like)
                    ->orWhere('sales_man_name', 'like', $like)
                    ->orWhere('state', 'like', $like)
                    ->orWhere('city', 'like', $like);
            });
        }

        if ($this->orderDateFrom) {
            $query->where('order_date', '>=', $this->orderDateFrom);
        }
        if ($this->orderDateTo) {
            $query->where('order_date', '<=', $this->orderDateTo);
        }
        if ($this->productSku) {
            $query->where('product_sku', $this->productSku);
        }
        if ($this->salesman) {
            $query->where('sales_man_name', 'like', '%' . trim($this->salesman) . '%');
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Serial Number',
            'Order Date',
            'Order Number',
            'Invoice Date',
            'Invoice Series',
            'Invoice Number',
            'AC Number (Customer)',
            'Party Name',
            'Contact Person Name',
            'Primary Mobile',
            'Other Mobile',
            'Company',
            'Product SKU',
            'Product Name',
            'Mfd By',
            'Batch Number',
            'Expiry Date',
            'Quantity',
            'Free',
            'Sale Rate',
            'MRP Rate',
            'Discount %',
            'Taxable Amount',
            'Tax Code',
            'GST %',
            'GST Amount',
            'Final Amount',
            'Sales Man Name',
            'Sales Man Code',
            'Case',
            'Packing',
            'Transport',
            'Book To',
            'LR Number',
            'LR Date',
            'Country',
            'State',
            'City',
            'District',
            'Pincode',
        ];
    }

    public function map($record): array
    {
        $customer = $record->customerDetails;
        $stock = $record->productStock;
        $product = $stock ? $stock->product : null;
        $brand = $product ? $product->brand : null;

        $partyName = $customer ? ($customer->company_name ?? '') : '';
        $contactPersonName = $customer ? ($customer->con_person_name ?? '') : '';

        $primaryMobiles = [];
        $otherMobiles = [];

        if ($customer) {
            $primaryMobiles = array_filter([
                $customer->prim_mobile_no_business ?? null,
                $customer->prim_whats_app_no_business ?? null,
                $customer->prim_mobile_no ?? null,
                $customer->prim_whats_app_no ?? null,
            ]);

            $otherMobiles = array_filter([
                $customer->alt_mobile_no_business ?? null,
                $customer->alternate_whats_app_no_business ?? null,
                $customer->alt_mobile_no ?? null,
                $customer->alt_whats_app_no ?? null,
            ]);
        }

        return [
            $record->serial_number,
            $record->order_date,
            $record->order_number,
            $record->invoice_date,
            $record->invoice_series,
            $record->invoice_number,
            $record->ac_number,
            $partyName,
            $contactPersonName,
            implode(', ', $primaryMobiles),
            implode(', ', $otherMobiles),
            $partyName,
            $record->product_sku,
            $product ? $product->name : '',
            $brand ? $brand->name : '',
            $record->batch_number,
            $record->expiry_date,
            $record->quantity,
            $record->free,
            $record->sale_rate,
            $record->mrp_rate,
            $record->discount,
            $record->taxable_amount,
            $record->tax_code,
            $record->gst_percentage,
            $record->gst_amount,
            $record->final_amount,
            $record->sales_man_name,
            $record->sales_man_code,
            $record->case_value,
            $record->packing,
            $record->transport,
            $record->book_to,
            $record->lr_number,
            $record->lr_date,
            $record->country,
            $record->state,
            $record->city,
            $record->district,
            $record->pincode,
        ];
    }
}

