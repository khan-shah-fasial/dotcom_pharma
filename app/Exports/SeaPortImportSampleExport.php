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
            'port_id',
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
            'authority_designation',
            'authority_mobile',
            'authority_whatsapp',
            'authority_email',
            'coordinator_name',
            'coordinator_designation',
            'coordinator_mobile',
            'coordinator_whatsapp',
            'coordinator_email',
            'authority_contact',
            'status',
        ];
    }

    public function array(): array
    {
        return [[
            'SEA-INNSA',
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
            'Port Operations Manager',
            '+91 22 1234 5678',
            '+91 98765 43210',
            'authority@example.com',
            'Cargo Coordination Desk',
            'Export Coordinator',
            '+91 22 8765 4321',
            '+91 91234 56789',
            'coordinator@example.com',
            'Available Monday to Friday, 09:00–18:00',
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
            'A' => 16, 'B' => 18, 'C' => 8, 'D' => 8, 'E' => 14, 'F' => 32,
            'G' => 14, 'H' => 14, 'I' => 18, 'J' => 16, 'K' => 18, 'L' => 18,
            'M' => 18, 'N' => 12, 'O' => 12, 'P' => 30, 'Q' => 15, 'R' => 16,
            'S' => 16, 'T' => 18, 'U' => 20, 'V' => 22, 'W' => 18, 'X' => 18,
            'Y' => 18, 'Z' => 18, 'AA' => 20, 'AB' => 30, 'AC' => 24, 'AD' => 18,
            'AE' => 18, 'AF' => 28, 'AG' => 28, 'AH' => 24, 'AI' => 18, 'AJ' => 18,
            'AK' => 28, 'AL' => 32, 'AM' => 12,
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
                $sheet->getStyle('N2:O2')->getNumberFormat()->setFormatCode('0.000000');
            },
        ];
    }
}
