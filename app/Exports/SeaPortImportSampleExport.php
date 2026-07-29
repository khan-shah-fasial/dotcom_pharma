<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SeaPortImportSampleExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithEvents, WithTitle
{
    public function headings(): array
    {
        return [
            'country',
            'iso2',
            'iso3',
            'continent',
            'name',
            'un_locode',
            'port_type',
            'terminal_type',
            'classification',
            'water_body',
            'ocean',
            'state_region',
            'latitude',
            'longitude',
            'nearest_airport',
            'customs_port',
            'export_supported',
            'import_supported',
            'container_supported',
            'bulk_cargo_supported',
            'liquid_cargo_supported',
            'ro_ro_supported',
            'cruise_supported',
            'ferry_supported',
            'fishing_supported',
            'ship_repair_supported',
            'authority_name',
            'authority_contact',
            'status',
        ];
    }

    public function array(): array
    {
        return [[
            'India',
            'IN',
            'IND',
            'Asia',
            'Jawaharlal Nehru Port (Nhava Sheva)',
            'INNSA',
            'Seaport',
            'Container',
            'Major',
            'Arabian Sea',
            'Indian Ocean',
            'Maharashtra',
            18.949,
            72.949,
            'Chhatrapati Shivaji Maharaj International Airport',
            'Yes',
            'Yes',
            'Yes',
            'Yes',
            'Yes',
            'Limited',
            'Yes',
            'No',
            'Yes',
            'No',
            'Yes',
            'Jawaharlal Nehru Port Authority',
            'operations@example.com',
            'Active',
        ]];
    }

    public function title(): string
    {
        return 'Sea Ports';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, 'B' => 8, 'C' => 8, 'D' => 14, 'E' => 32, 'F' => 14,
            'G' => 14, 'H' => 18, 'I' => 16, 'J' => 18, 'K' => 18, 'L' => 18,
            'M' => 12, 'N' => 12, 'O' => 30, 'P' => 15, 'Q' => 16, 'R' => 16,
            'S' => 18, 'T' => 20, 'U' => 22, 'V' => 18, 'W' => 18, 'X' => 18,
            'Y' => 18, 'Z' => 20, 'AA' => 30, 'AB' => 28, 'AC' => 12,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2563EB'],
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                    'wrapText' => true,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastColumn}2");
                $sheet->getRowDimension(1)->setRowHeight(32);
                $sheet->getStyle("A1:{$lastColumn}2")->getAlignment()
                    ->setVertical('center')
                    ->setWrapText(true);
                $sheet->getStyle('M2:N2')->getNumberFormat()->setFormatCode('0.000000');
            },
        ];
    }
}
