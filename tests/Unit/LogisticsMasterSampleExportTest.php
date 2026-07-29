<?php

namespace Tests\Unit;

use App\Exports\AirportImportSampleExport;
use App\Exports\SeaPortImportSampleExport;
use App\Imports\Concerns\NormalizesLogisticsImportValues;
use PHPUnit\Framework\TestCase;

class LogisticsMasterSampleExportTest extends TestCase
{
    public function test_sea_port_sample_headers_match_import_contract(): void
    {
        $export = new SeaPortImportSampleExport();

        $this->assertCount(29, $export->headings());
        $this->assertSame(
            ['country', 'iso2', 'iso3', 'continent', 'name', 'un_locode'],
            array_slice($export->headings(), 0, 6)
        );
        $this->assertCount(count($export->headings()), $export->array()[0]);
    }

    public function test_airport_sample_headers_match_import_contract(): void
    {
        $export = new AirportImportSampleExport();

        $this->assertCount(16, $export->headings());
        $this->assertSame(
            ['country', 'iso2', 'iso3', 'iata', 'icao', 'name'],
            array_slice($export->headings(), 0, 6)
        );
        $this->assertCount(count($export->headings()), $export->array()[0]);
    }

    public function test_import_status_values_are_normalized_consistently(): void
    {
        $normalizer = new class {
            use NormalizesLogisticsImportValues;

            public function status($value): ?bool
            {
                return $this->statusValue($value);
            }
        };

        $this->assertTrue($normalizer->status('Active'));
        $this->assertTrue($normalizer->status('yes'));
        $this->assertFalse($normalizer->status('Inactive'));
        $this->assertFalse($normalizer->status('0'));
        $this->assertNull($normalizer->status('unknown'));
    }
}
