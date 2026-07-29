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
            'authority_contact',
            'latitude',
            'longitude',
            'status',
        ];
    }

    public function array(): array
    {
        return [[
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
            'cargo@example.com',
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
            'A' => 18, 'B' => 8, 'C' => 8, 'D' => 10, 'E' => 10, 'F' => 36,
            'G' => 18, 'H' => 20, 'I' => 16, 'J' => 18, 'K' => 20, 'L' => 30,
            'M' => 28, 'N' => 12, 'O' => 12, 'P' => 12,
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
                $sheet->getStyle('N2:O2')->getNumberFormat()->setFormatCode('0.000000');
            },
        ];
    }
}
