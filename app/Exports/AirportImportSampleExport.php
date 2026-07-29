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

class AirportImportSampleExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithEvents, WithTitle
{
    public function headings(): array
    {
        return [
            'port_id',
            'country',
            'iso2',
            'iso3',
            'iata',
            'icao',
            'name',
            'city',
            'terminal_type',
            'cargo_airport',
            'customs_airport',
            'cold_chain_facility',
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
            'latitude',
            'longitude',
            'status',
        ];
    }

    public function array(): array
    {
        return [[
            'AIR-BOM',
            'India',
            'IN',
            'IND',
            'BOM',
            'VABB',
            'Chhatrapati Shivaji Maharaj International Airport',
            'Mumbai',
            'International',
            'Yes',
            'Yes',
            'Yes',
            'Airports Authority of India',
            'Cargo Operations Manager',
            '+91 22 6685 1010',
            '+91 98765 43210',
            'authority@example.com',
            'International Cargo Desk',
            'Export Coordinator',
            '+91 22 6685 2020',
            '+91 91234 56789',
            'coordinator@example.com',
            'Available Monday to Friday, 09:00-18:00',
            19.0896,
            72.8656,
            'Active',
        ]];
    }

    public function title(): string
    {
        return 'Airports';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16, 'B' => 18, 'C' => 8, 'D' => 8, 'E' => 10, 'F' => 10,
            'G' => 36, 'H' => 18, 'I' => 20, 'J' => 16, 'K' => 18, 'L' => 20,
            'M' => 30, 'N' => 24, 'O' => 18, 'P' => 18, 'Q' => 28, 'R' => 28,
            'S' => 24, 'T' => 18, 'U' => 18, 'V' => 28, 'W' => 32, 'X' => 12,
            'Y' => 12, 'Z' => 12,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF059669'],
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
                $sheet->getStyle('X2:Y2')->getNumberFormat()->setFormatCode('0.000000');
            },
        ];
    }
}
