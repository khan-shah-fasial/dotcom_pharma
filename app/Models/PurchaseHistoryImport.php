<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use App\Models\UserDetails;
use App\Models\ProductStock;
use App\Models\PurchaseHistory;

class PurchaseHistoryImport implements ToCollection, WithHeadingRow, WithValidation, WithChunkReading
{
    /**
     * @var int
     */
    private $rows = 0;

    /**
     * @var int
     */
    private $errorCount = 0;

    /**
     * Path (relative to storage/app) for the error log file.
     *
     * @var string
     */
    private $errorFilePath = 'purchase_history_import/error.txt';

    public function __construct()
    {
        // Ensure directory exists and reset error file for each import run on LOCAL disk (not S3)
        $disk = Storage::disk('local');
        $dir  = dirname($this->errorFilePath);

        if (! $disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        if ($disk->exists($this->errorFilePath)) {
            $disk->delete($this->errorFilePath);
        }
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        // Per-run caches to avoid repeated DB hits per unique value
        $acCache = [];  // normalized AC -> bool exists
        $skuCache = []; // normalized SKU -> bool exists

        // Validate per-row and insert
        foreach ($rows as $index => $row) {
            $raw = $row->toArray();
            $hasValue = collect($raw)->contains(function ($value) {
                return !is_null($value) && trim((string) $value) !== '';
            });
            if (! $hasValue) {
                continue;
            }

            // Excel/CSV first data row is row 2 (row 1 is headers)
            $rowNumber = $index + 2;

            // Normalize header keys to be case-insensitive and punctuation/space agnostic
            // e.g. "Order.No", "Order No", "order-no" -> "order_no"
            $normalizedRow = [];
            foreach ($raw as $key => $value) {
                if ($key === null) {
                    continue;
                }
                $normKey = strtolower(trim((string) $key));
                $normKey = str_replace([' ', '.', '-', '/', "\t"], '_', $normKey);
                $normalizedRow[$normKey] = $value;
            }

            // Map incoming columns (party-wise sheet) to variables using normalized keys
            $serialNumber   = $normalizedRow['sr'] ?? $normalizedRow['sr_no'] ?? null;
            $orderDate      = $normalizedRow['order_date'] ?? null;
            $orderNumber    = $normalizedRow['order_no']
                ?? $normalizedRow['orderno']
                ?? $normalizedRow['order_number']
                ?? null;
            $invoiceDate    = $normalizedRow['date'] ?? $normalizedRow['invoice_date'] ?? null;
            $invoiceSeries  = $normalizedRow['series'] ?? $normalizedRow['invoice_series'] ?? null;
            $invoiceNumber  = $normalizedRow['bill'] ?? $normalizedRow['invoice_no'] ?? $normalizedRow['invoice_number'] ?? null;
            $acNo           = $normalizedRow['ac_no']
                ?? $normalizedRow['acno']
                ?? $normalizedRow['ac_number']
                ?? null;
            $acNo           = isset($acNo) ? trim((string) $acNo) : null;
            $sku            = isset($normalizedRow['sku']) ? trim((string) $normalizedRow['sku']) : null;
            $qty            = $normalizedRow['qty'] ?? null;
            $free           = $normalizedRow['free'] ?? null;
            $saleRate       = $normalizedRow['sale_rate'] ?? null;
            $mrp            = $normalizedRow['mrp'] ?? null;
            $disc           = $normalizedRow['disc'] ?? null;
            $taxable        = $normalizedRow['taxable'] ?? null;
            $taxCode        = $normalizedRow['tax_code'] ?? null;
            $gst            = $normalizedRow['gst'] ?? null;
            $gstAmt         = $normalizedRow['gst_amt'] ?? null;
            $final          = $normalizedRow['final'] ?? null;
            $caseValue      = $normalizedRow['case'] ?? null;
            $transport      = $normalizedRow['transport'] ?? null;
            $bookedTo       = $normalizedRow['booked_to'] ?? null;
            $lrNo           = $normalizedRow['l_r_no'] ?? $normalizedRow['lr_no'] ?? null;
            $lrDate         = $normalizedRow['lr_date'] ?? null;
            $lateBy         = $normalizedRow['late_by'] ?? null;
            $country        = $normalizedRow['country'] ?? null;
            $state          = $normalizedRow['state'] ?? null;
            $city           = $normalizedRow['area'] ?? $normalizedRow['city'] ?? null;
            $district       = $normalizedRow['district'] ?? null;
            $pincode        = $normalizedRow['pincode'] ?? null;

            // Validate required references: AC number and SKU must exist
            $errors = [];
            $acKey  = $acNo !== null ? strtoupper(trim((string) $acNo)) : '';
            $skuKey = $sku !== null ? strtoupper(trim((string) $sku)) : '';

            if ($acKey === '') {
                $errors[] = 'AC Number (Ac.No) is empty';
            } else {
                if (! array_key_exists($acKey, $acCache)) {
                    $acCache[$acKey] = UserDetails::whereRaw('TRIM(UPPER(crm_id)) = ?', [$acKey])->exists();
                }
                if (! $acCache[$acKey]) {
                    $errors[] = "AC Number '{$acNo}' not found in user_details.crm_id";
                }
            }

            if ($skuKey === '') {
                $errors[] = 'Product SKU (SKU) is empty';
            } else {
                if (! array_key_exists($skuKey, $skuCache)) {
                    $skuCache[$skuKey] = ProductStock::whereRaw('TRIM(UPPER(sku)) = ?', [$skuKey])->exists();
                }
                if (! $skuCache[$skuKey]) {
                    $errors[] = "Product SKU '{$sku}' not found in product_stocks.sku";
                }
            }

            if (! empty($errors)) {
                $this->logError($rowNumber, implode(' | ', $errors));
                continue;
            }

            // Build data array for upsert
            $data = [
                'serial_number'   => $serialNumber,
                'order_date'      => $orderDate,
                'order_number'    => $orderNumber,
                'invoice_date'    => $invoiceDate,
                'invoice_series'  => $invoiceSeries,
                'invoice_number'  => $invoiceNumber,
                'ac_number'       => $acNo,
                'product_sku'     => $sku,
                'packing'         => $row['packing'] ?? null,
                'batch_number'    => $row['batch'] ?? null,
                'expiry_date'     => $row['exp'] ?? null,
                'quantity'        => isset($qty) ? (string) $qty : null,
                'free'            => isset($free) ? (string) $free : null,
                'sale_rate'       => isset($saleRate) ? (string) $saleRate : null,
                'mrp_rate'        => isset($mrp) ? (string) $mrp : null,
                'discount'        => isset($disc) ? (string) $disc : null,
                'taxable_amount'  => isset($taxable) ? (string) $taxable : null,
                'tax_code'        => $taxCode,
                'gst_percentage'  => isset($gst) ? (string) $gst : null,
                'gst_amount'      => isset($gstAmt) ? (string) $gstAmt : null,
                'final_amount'    => isset($final) ? (string) $final : null,
                'sales_man_name'  => $row['name'] ?? null,
                'sales_man_code'  => $row['salesman'] ?? null,
                'case_value'      => isset($caseValue) ? (string) $caseValue : null,
                'transport'       => $transport,
                'book_to'         => $bookedTo,
                'lr_number'       => $lrNo,
                'lr_date'         => $lrDate,
                'late_by'         => $lateBy,
                'country'         => $country,
                'state'           => $state,
                'city'            => $city,
                'district'        => $district,
                'pincode'         => $pincode,
            ];

            // Upsert based on composite key: invoice_date, invoice_series, invoice_number, ac_number, product_sku
            $existing = PurchaseHistory::where('invoice_date',   $invoiceDate)
                ->where('invoice_series', $invoiceSeries)
                ->where('invoice_number', $invoiceNumber)
                ->where('ac_number',      $acNo)
                ->where('product_sku',    $sku)
                ->first();

            if ($existing) {
                $existing->fill($data);
                $existing->save();
            } else {
                PurchaseHistory::create($data);
            }

            $this->rows++;
        }
    }

    private function logError(int $rowNumber, string $message): void
    {
        $line = 'Row ' . $rowNumber . ': ' . $message;
        Storage::disk('local')->append($this->errorFilePath, $line . PHP_EOL);
        $this->errorCount++;
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function getErrorFilePath(): ?string
    {
        return $this->errorCount > 0 ? storage_path('app/' . $this->errorFilePath) : null;
    }

    public function rules(): array
    {
        return [
            'qty' => ['nullable', 'numeric'],
            'free' => ['nullable', 'numeric'],
            'sale_rate' => ['nullable', 'numeric'],
            'mrp' => ['nullable', 'numeric'],
            'disc' => ['nullable', 'numeric'],
            'taxable' => ['nullable', 'numeric'],
            'gst' => ['nullable', 'numeric'],
            'gst_amt' => ['nullable', 'numeric'],
            'final' => ['nullable', 'numeric'],
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}

