<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PurchaseHistoryImport implements ToCollection, WithHeadingRow, WithValidation
{
    /**
     * @var int
     */
    private $rows = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Skip completely empty rows (no meaningful data)
            $raw = $row->toArray();
            $hasValue = collect($raw)->contains(function ($value) {
                return !is_null($value) && trim((string) $value) !== '';
            });

            if (! $hasValue) {
                continue;
            }

            // Support multiple header variants (Excel/CSV heading row)
            $acNumber = $row['ac_number']
                ?? $row['ac_number_customer']
                ?? $row['ac_number__customer']
                ?? null;

            $taxPercentage = $row['tax_percentage']
                ?? $row['tax']
                ?? null;

            $discount = $row['discount']
                ?? $row['discount_']
                ?? null;

            PurchaseHistory::create([
                'serial_number'   => $row['serial_number'] ?? null,
                'order_date'      => $row['order_date'] ?? null,
                'order_number'    => $row['order_number'] ?? null,
                'invoice_date'    => $row['invoice_date'] ?? null,
                'invoice_number'  => $row['invoice_number'] ?? null,
                'ac_number'       => $acNumber,
                'product_sku'     => $row['product_sku'] ?? null,
                'batch_number'    => $row['batch_number'] ?? null,
                'expiry_date'     => $row['expiry_date'] ?? null,
                'quantity'        => isset($row['quantity']) ? (string) $row['quantity'] : null,
                'free'            => isset($row['free']) ? (string) $row['free'] : null,
                'sale_rate'       => isset($row['sale_rate']) ? (string) $row['sale_rate'] : null,
                'mrp_rate'        => isset($row['mrp_rate']) ? (string) $row['mrp_rate'] : null,
                'discount'        => isset($discount) ? (string) $discount : null,
                'taxable_amount'  => isset($row['taxable_amount']) ? (string) $row['taxable_amount'] : null,
                'tax_percentage'  => isset($taxPercentage) ? (string) $taxPercentage : null,
                'tax_amount'      => isset($row['tax_amount']) ? (string) $row['tax_amount'] : null,
                'final_amount'    => isset($row['final_amount']) ? (string) $row['final_amount'] : null,
                'sales_man_name'  => $row['sales_man_name'] ?? null,
                'sales_man_code'  => $row['sales_man_code'] ?? null,
                'case_value'      => $row['case'] ?? null,
                'packing'         => $row['packing'] ?? null,
                'transport'       => $row['transport'] ?? null,
                'book_to'         => $row['book_to'] ?? null,
                'lr_number'       => $row['lr_number'] ?? null,
                'lr_date'         => $row['lr_date'] ?? null,
                'country'         => $row['country'] ?? null,
                'state'           => $row['state'] ?? null,
                'city'            => $row['city'] ?? null,
                'district'        => $row['district'] ?? null,
                'pincode'         => $row['pincode'] ?? null,
            ]);

            $this->rows++;
        }
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }

    public function rules(): array
    {
        return [
            // Allow any date-like value; we'll store as string without strict validation
            'order_date' => ['nullable'],
            'quantity' => ['nullable', 'numeric'],
            'free' => ['nullable', 'numeric'],
            'sale_rate' => ['nullable', 'numeric'],
            'mrp_rate' => ['nullable', 'numeric'],
            'discount' => ['nullable', 'numeric'],
            'taxable_amount' => ['nullable', 'numeric'],
            'tax_percentage' => ['nullable', 'numeric'],
            'tax_amount' => ['nullable', 'numeric'],
            'final_amount' => ['nullable', 'numeric'],
        ];
    }
}

