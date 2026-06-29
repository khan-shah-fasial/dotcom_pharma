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
                return $this->hasFilledValue($value);
            });
            if (! $hasValue) {
                continue;
            }

            // Excel/CSV first data row is row 2 (row 1 is headers)
            $rowNumber = $index + 2;

            // Normalize header keys to be case-insensitive and punctuation/space agnostic.
            // e.g. "Order.No", "Order No", "order-no" -> "order_no".
            $normalizedRow = [];
            foreach ($raw as $key => $value) {
                if ($key === null) {
                    continue;
                }
                $normalizedRow[$this->normalizeKey((string) $key)] = $value;
            }

            $accountLines = $this->splitValue($this->value($normalizedRow, ['ac_no_name_salesman', 'ac_no_name', 'acno_name']));
            $partyLines = $this->splitValue($this->value($normalizedRow, ['party_name_area_town_district', 'party_name_area_town']));
            $stateLines = $this->splitValue($this->value($normalizedRow, ['state_pincode_country']));
            $orderLines = $this->splitValue($this->value($normalizedRow, ['order_date_order_no_salesman', 'order_date_order_number_salesman']));
            $invoiceLines = $this->splitValue($this->value($normalizedRow, ['date_series_bill', 'invoice_date_series_bill']));
            $productLines = $this->splitValue($this->value($normalizedRow, ['sku_product_pack_size']));
            $batchLines = $this->splitValue($this->value($normalizedRow, ['batch_expiry_mfd_by', 'batch_exp_mfd_by']));
            $qtyLines = $this->splitValue($this->value($normalizedRow, ['qty_free_total', 'quantity_free_total']));
            $rateLines = $this->splitValue($this->value($normalizedRow, ['sale_rate_disc_mrp', 'sale_rate_discount_mrp']));
            $amountLines = $this->splitValue($this->value($normalizedRow, ['taxable_gst_total', 'taxable_amount_gst_total']));
            $taxLines = $this->splitValue($this->value($normalizedRow, ['tax_code_gst', 'tax_code_gst_percentage']));
            $transportLines = $this->splitValue($this->value($normalizedRow, ['transport_booked_to_case', 'transport_book_to_case']));
            $lrLines = $this->splitValue($this->value($normalizedRow, ['l_r_no_lr_date_late_by', 'lr_no_lr_date_late_by', 'lr_number_lr_date_late_by']));

            // Map incoming columns (party-wise sheet) to variables using normalized keys
            $serialNumber   = $this->value($normalizedRow, ['sr', 'sr_no', 'serial', 'serial_number']);
            $orderDate      = $this->value($normalizedRow, ['order_date'], $orderLines[0] ?? null);
            $orderNumber    = $this->value($normalizedRow, ['order_no', 'orderno', 'order_number'], $orderLines[1] ?? null);
            $invoiceDate    = $this->value($normalizedRow, ['date', 'invoice_date'], $invoiceLines[0] ?? null);
            $invoiceSeries  = $this->value($normalizedRow, ['series', 'invoice_series'], $invoiceLines[1] ?? null);
            $invoiceNumber  = $this->value($normalizedRow, ['bill', 'bill_no', 'invoice_no', 'invoice_number'], $invoiceLines[2] ?? null);
            $acNo           = $this->value($normalizedRow, ['ac_no', 'acno', 'ac_number'], $accountLines[0] ?? null);
            $acNo           = isset($acNo) ? trim((string) $acNo) : null;
            $sku            = $this->value($normalizedRow, ['sku', 'product_sku'], $productLines[0] ?? null);
            $sku            = isset($sku) ? trim((string) $sku) : null;
            $packing        = $this->value($normalizedRow, ['packing', 'pack_size', 'pack'], $productLines[2] ?? null);
            $batch          = $this->value($normalizedRow, ['batch', 'batch_no', 'batch_number'], $batchLines[0] ?? null);
            $expiry         = $this->value($normalizedRow, ['exp', 'expiry', 'expiry_date'], $batchLines[1] ?? null);
            $qty            = $this->value($normalizedRow, ['qty', 'quantity'], $qtyLines[0] ?? null);
            $free           = $this->value($normalizedRow, ['free'], $qtyLines[1] ?? null);
            $saleRate       = $this->value($normalizedRow, ['sale_rate'], $rateLines[0] ?? null);
            $mrp            = $this->value($normalizedRow, ['mrp', 'mrp_rate'], $rateLines[2] ?? null);
            $disc           = $this->value($normalizedRow, ['disc', 'discount'], $rateLines[1] ?? null);
            $taxable        = $this->value($normalizedRow, ['taxable', 'taxable_amount'], $amountLines[0] ?? null);
            $taxCode        = $this->value($normalizedRow, ['tax_code'], $taxLines[0] ?? null);
            $gst            = $this->value($normalizedRow, ['gst_percentage', 'gst_percent', 'gst'], $taxLines[1] ?? null);
            $gstAmt         = $this->value($normalizedRow, ['gst_amt', 'gst_amount'], $amountLines[1] ?? null);
            $final          = $this->value($normalizedRow, ['final', 'final_amount', 'total'], $amountLines[2] ?? null);
            $salesmanName   = $this->value($normalizedRow, ['salesman', 'sales_man_name', 'salesman_name', 'sales_man'], $accountLines[2] ?? ($orderLines[2] ?? null));
            $salesmanCode   = $this->value($normalizedRow, ['sales_man_code', 'salesman_code']);
            $caseValue      = $this->value($normalizedRow, ['case', 'case_value'], $transportLines[2] ?? null);
            $transport      = $this->value($normalizedRow, ['transport'], $transportLines[0] ?? null);
            $bookedTo       = $this->value($normalizedRow, ['booked_to', 'book_to'], $transportLines[1] ?? null);
            $lrNo           = $this->value($normalizedRow, ['l_r_no', 'lr_no', 'lr_number'], $lrLines[0] ?? null);
            $lrDate         = $this->value($normalizedRow, ['lr_date'], $lrLines[1] ?? null);
            $lateBy         = $this->value($normalizedRow, ['late_by'], $lrLines[2] ?? null);
            $country        = $this->value($normalizedRow, ['country'], $stateLines[2] ?? null);
            $state          = $this->value($normalizedRow, ['state'], $stateLines[0] ?? null);
            $city           = $this->value($normalizedRow, ['area', 'city'], $partyLines[1] ?? null);
            $district       = $this->value($normalizedRow, ['district'], $partyLines[2] ?? null);
            $pincode        = $this->value($normalizedRow, ['pincode', 'pin_code'], $stateLines[1] ?? null);

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
                'packing'         => $packing,
                'batch_number'    => $batch,
                'expiry_date'     => $expiry,
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
                'sales_man_name'  => $salesmanName,
                'sales_man_code'  => $salesmanCode,
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

    private function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);

        return trim((string) $key, '_');
    }

    private function value(array $row, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeKey($key);
            if (! array_key_exists($normalizedKey, $row)) {
                continue;
            }

            $value = $row[$normalizedKey];
            if (is_array($value)) {
                $filledValues = array_values(array_filter($value, fn ($item) => $this->hasFilledValue($item)));
                if (! empty($filledValues)) {
                    return end($filledValues);
                }

                continue;
            }

            if ($this->hasFilledValue($value)) {
                return $value;
            }
        }

        return $default;
    }

    private function splitValue($value): array
    {
        if ($value === null) {
            return [];
        }

        return array_map(
            fn ($line) => trim((string) $line),
            preg_split('/\r\n|\r|\n/', (string) $value) ?: []
        );
    }

    private function hasFilledValue($value): bool
    {
        if (is_array($value)) {
            return collect($value)->contains(fn ($item) => $this->hasFilledValue($item));
        }

        return $value !== null && trim((string) $value) !== '';
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

