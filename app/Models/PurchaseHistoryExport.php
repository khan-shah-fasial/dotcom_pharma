<?php

namespace App\Models;

use App\Models\PurchaseHistory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseHistoryExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithEvents, WithStyles
{
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

    public function collection()
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

        return PurchaseHistory::mergeReportRows($query
            ->orderBy('order_number')
            ->orderBy('invoice_number')
            ->orderBy('product_sku')
            ->orderBy('batch_number')
            ->get());
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

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(48);
                }
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
}

