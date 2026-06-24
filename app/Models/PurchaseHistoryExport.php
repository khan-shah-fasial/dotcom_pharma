<?php

namespace App\Models;

use App\Models\PurchaseHistory;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseHistoryExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithCustomChunkSize, WithEvents, WithStyles
{
    public const XLSX_SAFE_ROW_LIMIT = 10000;

    protected $search;
    protected $orderDateFrom;
    protected $orderDateTo;
    protected $productSku;
    protected $salesman;
    protected $account;
    protected $rowNumber = 0;

    public function __construct($search = null, $orderDateFrom = null, $orderDateTo = null, $productSku = null, $salesman = null, $account = null)
    {
        $this->search = $search;
        $this->orderDateFrom = $orderDateFrom;
        $this->orderDateTo = $orderDateTo;
        $this->productSku = $productSku;
        $this->salesman = $salesman;
        $this->account = $account;
    }

    /**
     * Build an SQL-grouped query. FromQuery makes Laravel Excel read this in
     * chunks instead of hydrating the complete report in PHP memory.
     */
    public function query()
    {
        $query = PurchaseHistory::with([
            'customerDetails.user',
            'customerDetails.businessCity',
            'customerDetails.businessState',
            'customerDetails.businessCountry',
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

        return $query
            ->select([
                'purchase_history.ac_number',
                'purchase_history.order_number',
                'purchase_history.invoice_series',
                'purchase_history.invoice_number',
                'purchase_history.product_sku',
                'purchase_history.batch_number',
                'purchase_history.sale_rate',
                'purchase_history.discount',
                'purchase_history.mrp_rate',
                'purchase_history.tax_code',
                'purchase_history.gst_percentage',
            ])
            ->selectRaw('MIN(purchase_history.id) AS id')
            ->selectRaw('MIN(purchase_history.order_date) AS order_date')
            ->selectRaw('MIN(purchase_history.invoice_date) AS invoice_date')
            ->selectRaw('MIN(purchase_history.expiry_date) AS expiry_date')
            ->selectRaw('MIN(purchase_history.sales_man_name) AS sales_man_name')
            ->selectRaw('MIN(purchase_history.sales_man_code) AS sales_man_code')
            ->selectRaw('MIN(purchase_history.case_value) AS case_value')
            ->selectRaw('MIN(purchase_history.packing) AS packing')
            ->selectRaw('MIN(purchase_history.transport) AS transport')
            ->selectRaw('MIN(purchase_history.book_to) AS book_to')
            ->selectRaw('MIN(purchase_history.lr_number) AS lr_number')
            ->selectRaw('MIN(purchase_history.lr_date) AS lr_date')
            ->selectRaw('MIN(purchase_history.late_by) AS late_by')
            ->selectRaw($this->sumSql('quantity'))
            ->selectRaw($this->sumSql('free'))
            ->selectRaw($this->sumSql('taxable_amount'))
            ->selectRaw($this->sumSql('gst_amount'))
            ->selectRaw($this->sumSql('final_amount'))
            ->groupBy([
                'purchase_history.ac_number',
                'purchase_history.order_number',
                'purchase_history.invoice_series',
                'purchase_history.invoice_number',
                'purchase_history.product_sku',
                'purchase_history.batch_number',
                'purchase_history.sale_rate',
                'purchase_history.discount',
                'purchase_history.mrp_rate',
                'purchase_history.tax_code',
                'purchase_history.gst_percentage',
            ])
            // Lines without an order or bill must remain independent records.
            ->groupByRaw("CASE WHEN COALESCE(TRIM(purchase_history.order_number), '') = '' OR COALESCE(TRIM(purchase_history.invoice_number), '') = '' THEN purchase_history.id ELSE 0 END")
            ->orderBy('order_number')
            ->orderBy('invoice_number')
            ->orderBy('product_sku')
            ->orderBy('batch_number')
            ->orderBy('sale_rate')
            ->orderBy('discount')
            ->orderBy('mrp_rate')
            ->orderBy('tax_code')
            ->orderBy('gst_percentage')
            ->orderBy('ac_number')
            ->orderBy('id');
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function headings(): array
    {
        return [
            'Sr.No',
            "Ac.No\nName",
            "Party Name\nArea, Town\nDistrict",
            "State\nPincode\nCountry",
            "Order Date\nOrder.No\nSalesMan",
            "Date\nSeries\nBill",
            "SKU\nProduct\nPack Size",
            "Batch\nExpiry\nMfd By",
            "Qty\nFree\nTotal",
            "Sale Rate\nDisc%\nMRP",
            "Taxable\nGST\nTotal",
            "Tax Code\nGST%",
            "Transport\nBooked To\nCase",
            "L.R.No\nLR Date\nLate By",
        ];
    }

    public function map($record): array
    {
        $customer = $record->customerDetails;
        $stock    = $record->productStock;
        $product  = $stock ? $stock->product : null;
        $brand    = $product ? $product->brand : null;

        $partyName = $customer?->company_name ?? '';
        $area = $customer?->post_business ?? '';
        $town = $customer?->businessCity?->name ?? '';
        $district = $customer?->district_business ?? '';
        $state = $customer?->businessState?->name ?? '';
        $pincode = $customer?->pincode_business ?? '';
        $country = $customer?->businessCountry?->name ?? '';
        $accountNumber = $customer?->crm_id ?? $record->ac_number ?? '';
        $userName = $customer?->user?->name ?? '';

        $this->rowNumber++;

        return [
            $this->rowNumber,
            $this->lines($accountNumber, $userName),
            $this->lines($partyName, $this->joinNonEmpty(', ', [$area, $town]), $district),
            $this->lines($state, $pincode, $country),
            $this->lines($record->order_date, $record->order_number, $record->sales_man_code ?: $record->sales_man_name),
            $this->lines($record->invoice_date, $record->invoice_series, $record->invoice_number),
            $this->lines($record->product_sku, $product?->name, $record->packing),
            $this->lines($record->batch_number, $record->expiry_date, $brand?->name),
            $this->lines($record->quantity, $record->free, $this->sum($record->quantity, $record->free)),
            $this->lines($record->sale_rate, $record->discount, $record->mrp_rate),
            $this->lines($record->taxable_amount, $record->gst_amount, $record->final_amount),
            $this->lines($record->tax_code, $record->gst_percentage),
            $this->lines($record->transport, $record->book_to, $record->case_value),
            $this->lines($record->lr_number, $record->lr_date, $record->late_by),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                    'wrapText' => true,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 7,
            'B' => 16,
            'C' => 24,
            'D' => 16,
            'E' => 16,
            'F' => 15,
            'G' => 26,
            'H' => 18,
            'I' => 10,
            'J' => 12,
            'K' => 13,
            'L' => 11,
            'M' => 20,
            'N' => 16,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(1, $sheet->getHighestRow());
                $range = 'A1:N' . $lastRow;

                $sheet->freezePane('A2');
                $sheet->setAutoFilter($range);
                $sheet->getDefaultRowDimension()->setRowHeight(48);
                $sheet->getRowDimension(1)->setRowHeight(48);
                $sheet->getStyle($range)->getAlignment()
                    ->setVertical('center')
                    ->setWrapText(true);
                if ($lastRow > 1) {
                    $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal('left');
                }
                $sheet->getStyle($range)->getBorders()->getAllBorders()
                    ->setBorderStyle('thin')
                    ->getColor()->setARGB('FF000000');

            },
        ];
    }

    private function lines(...$values): string
    {
        return implode("\n", array_map(fn ($value) => $this->displayValue($value), $values));
    }

    private function joinNonEmpty(string $separator, array $values): string
    {
        return implode($separator, array_values(array_filter($values, fn ($value) => filled($value))));
    }

    private function sum($first, $second)
    {
        return (float) ($first ?? 0) + (float) ($second ?? 0);
    }

    private function displayValue($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value)) {
            return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
        }

        return (string) $value;
    }

    private function sumSql(string $column): string
    {
        return "COALESCE(SUM(CAST(NULLIF(REPLACE(purchase_history.{$column}, ',', ''), '') AS DECIMAL(20, 4))), 0) AS {$column}";
    }
}

