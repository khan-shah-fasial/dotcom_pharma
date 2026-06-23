<?php

namespace App\Models;

use App\Models\PurchaseHistory;
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
    protected $account;

    public function __construct($search = null, $orderDateFrom = null, $orderDateTo = null, $productSku = null, $salesman = null, $account = null)
    {
        $this->search = $search;
        $this->orderDateFrom = $orderDateFrom;
        $this->orderDateTo = $orderDateTo;
        $this->productSku = $productSku;
        $this->salesman = $salesman;
        $this->account = $account;
    }

    public function collection()
    {
        $query = PurchaseHistory::with([
            'customerDetails.user',
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
                    ->orWhere('city', 'like', $like)
                    ->orWhere('ac_number', 'like', $like)
                    ->orWhereHas('customerDetails', function ($customerQuery) use ($like) {
                        $customerQuery->where('company_name', 'like', $like)
                            ->orWhereHas('user', function ($userQuery) use ($like) {
                                $userQuery->where('name', 'like', $like);
                            });
                    });
            });
        }

        if ($this->account !== null && trim((string) $this->account) !== '') {
            $like = '%' . trim($this->account) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('ac_number', 'like', $like)
                    ->orWhereHas('customerDetails', function ($customerQuery) use ($like) {
                        $customerQuery->where('crm_id', 'like', $like)
                            ->orWhere('company_name', 'like', $like)
                            ->orWhereHas('user', function ($userQuery) use ($like) {
                                $userQuery->where('name', 'like', $like);
                            });
                    });
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
        // Match the party-wise sheet column layout used for import,
        // so exported files can be edited and re-imported directly.
        return [
            'Sr.',
            'Ac.No',
            'Party Name',
            'Area',
            'Town',
            'District',
            'State',
            'Pincode',
            'Order Date',
            'Order.No',
            'Name',
            'SalesMan',
            'Date',
            'Series',
            'Bill',
            'SKU',
            'Product',
            'Packing',
            'Mfd By',
            'Batch',
            'Exp',
            'Qty',
            'Free',
            'Sale Rate',
            'MRP',
            'Disc%',
            'Taxable',
            'Tax Code',
            'GST',
            'GST Amt',
            'Final',
            'Transport',
            'Booked To',
            'Case',
            'L.R.No',
            'LR Date',
            'Late By',
        ];
    }

    public function map($record): array
    {
        $customer = $record->customerDetails;
        $stock    = $record->productStock;
        $product  = $stock ? $stock->product : null;
        $brand    = $product ? $product->brand : null;

        $partyName = $customer ? ($customer->company_name ?? '') : '';

        // Map to party-wise sheet columns so that exported rows can be edited
        // and re-imported using PurchaseHistoryImport.
        $area     = $record->city;
        $town     = $record->district ?: '';

        return [
            // Sr.
            $record->serial_number,
            // Ac.No
            $record->ac_number,
            // Party Name
            $partyName,
            // Area
            $area,
            // Town
            $town,
            // District
            $record->district,
            // State
            $record->state,
            // Pincode
            $record->pincode,
            // Order Date
            $record->order_date,
            // Order.No
            $record->order_number,
            // Name (Salesman Name)
            $record->sales_man_name,
            // SalesMan (Salesman Code)
            $record->sales_man_code,
            // Date (Invoice Date)
            $record->invoice_date,
            // Series (Invoice Series)
            $record->invoice_series,
            // Bill (Invoice Number)
            $record->invoice_number,
            // SKU
            $record->product_sku,
            // Product
            $product ? $product->name : '',
            // Packing
            $record->packing,
            // Mfd By (Brand)
            $brand ? $brand->name : '',
            // Batch
            $record->batch_number,
            // Exp
            $record->expiry_date,
            // Qty
            $record->quantity,
            // Free
            $record->free,
            // Sale Rate
            $record->sale_rate,
            // MRP
            $record->mrp_rate,
            // Disc%
            $record->discount,
            // Taxable
            $record->taxable_amount,
            // Tax Code
            $record->tax_code,
            // GST
            $record->gst_percentage,
            // GST Amt
            $record->gst_amount,
            // Final
            $record->final_amount,
            // Transport
            $record->transport,
            // Booked To
            $record->book_to,
            // Case
            $record->case_value,
            // L.R.No
            $record->lr_number,
            // LR Date
            $record->lr_date,
            // Late By
            $record->late_by,
        ];
    }
}

