<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Storage;

class PdfStampService
{
    /**
     * Stamp a single PDF with a header on each page.
     *
     * @param  string $sourcePdfAbsolutePath  Absolute path to the input PDF (on disk)
     * @param  array  $header                 ['name' => ..., 'type' => ..., 'type_input' => ...]
     * @param  string $outputRelativePath     Relative path within storage/app/public to write stamped PDF
     * @return string                         Public URL to the stamped file
     */
    public function stampSingle(string $sourcePdfAbsolutePath, array $header, string $outputRelativePath): string
    {
        // Ensure output dir exists
        $dir = dirname($outputRelativePath);
        Storage::disk('public')->makeDirectory($dir);

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($sourcePdfAbsolutePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplIdx = $pdf->importPage($pageNo);
            $size   = $pdf->getTemplateSize($tplIdx);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplIdx);

            // Header styling (adjust to taste)
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->SetTextColor(13, 110, 253); // bootstrap primary
            $paddingX = 12; $paddingY = 10;

            // Header text
            // $line1 = sprintf('Name: %s', $header['name'] ?? '-');
            // $line2 = sprintf('Type: %s', strtoupper($header['type'] ?? '-'));
            // $line3 = sprintf('Reference: %s', $header['type_input'] ?? '-');

            // // Draw a light header bar
            // $pdf->SetDrawColor(222, 226, 230);
            // $pdf->SetLineWidth(0.2);
            // $pdf->Line(10, 16, $size['width'] - 10, 16);

            // // Place text at top-left
            // $pdf->SetXY($paddingX, $paddingY - 4);
            // $pdf->Cell(0, 5, $line1, 0, 1, 'L');
            // $pdf->SetFont('Helvetica', '', 10);
            // $pdf->SetTextColor(73, 80, 87);
            // $pdf->SetX($paddingX); $pdf->Cell(0, 5, $line2, 0, 1, 'L');
            // $pdf->SetX($paddingX); $pdf->Cell(0, 5, $line3, 0, 1, 'L');


            // Combine inline with slashes
            $inlineHeader = sprintf(
                'Name: %s / Type: %s / Reference: %s',
                $header['name'] ?? '-',
                strtoupper($header['type'] ?? '-'),
                $header['type_input'] ?? '-'
            );

            // Draw a subtle line below header
            $pdf->SetDrawColor(222, 226, 230);
            $pdf->SetLineWidth(0.2);
            $pdf->Line(10, 16, $size['width'] - 10, 16);

            // Header text
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor(13, 110, 253); // bootstrap primary
            $pdf->SetXY($paddingX, $paddingY - 2);
            $pdf->Cell(0, 6, $inlineHeader, 0, 1, 'L');
        }

        // Save to storage/app/public/...
        $outputAbsolutePath = Storage::disk('public')->path($outputRelativePath);
        $pdf->Output($outputAbsolutePath, 'F');

        return Storage::disk('public')->url($outputRelativePath);
    }

    /**
     * Stamp multiple PDFs. Returns array of URLs.
     *
     * @param  array  $absolutePaths    Array of absolute source PDF paths
     * @param  array  $header           Same as stampSingle
     * @param  string $outputBaseDir    Base relative dir under public disk to save files
     * @return array                    Array of public URLs
     */
    public function stampMany(array $absolutePaths, array $header, string $outputBaseDir): array
    {
        $urls = [];
        foreach ($absolutePaths as $idx => $src) {
            $outRel = rtrim($outputBaseDir, '/').'/stamped_'.($idx+1).'.pdf';
            $urls[] = $this->stampSingle($src, $header, $outRel);
        }
        return $urls;
    }
}
