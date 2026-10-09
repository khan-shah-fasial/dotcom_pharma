<?php

namespace Tests\Unit;

use App\Models\TaxMaster;
use Tests\TestCase;

class TaxMasterCalculationTest extends TestCase
{
    public function test_g5_fills_state_ut_and_igst_from_tax_percent(): void
    {
        $normalized = TaxMaster::normalize([
            'kind' => 'taxable',
            'tax_code' => 'g5',
            'purchase_tax' => 5,
            'purchase_cgst' => 2.5,
            'purchase_sgst' => 2.5,
            'purchase_igst' => 0,
            'sale_same_as_purchase' => 1,
        ]);

        $this->assertSame('G5', $normalized['tax_code']);
        $this->assertEquals(2.5, $normalized['purchase_cgst']);
        $this->assertEquals(2.5, $normalized['purchase_sgst']);
        $this->assertEquals(2.5, $normalized['purchase_ut_cgst']);
        $this->assertEquals(2.5, $normalized['purchase_utgst']);
        $this->assertEquals(5.0, $normalized['purchase_igst']);
        $this->assertTrue($this->pathsMatch($normalized, 'purchase'));
        $this->assertTrue($this->pathsMatch($normalized, 'sale'));
    }

    public function test_blank_tax_percent_uses_state_split_then_fills_igst(): void
    {
        $normalized = TaxMaster::normalize([
            'kind' => 'taxable',
            'tax_code' => 'G18',
            'purchase_cgst' => 9,
            'purchase_sgst' => 9,
            'purchase_igst' => 0,
            'sale_same_as_purchase' => true,
        ]);

        $this->assertEquals(18.0, $normalized['purchase_tax']);
        $this->assertEquals(9.0, $normalized['purchase_cgst']);
        $this->assertEquals(18.0, $normalized['purchase_igst']);
        $this->assertTrue($this->pathsMatch($normalized, 'purchase'));
    }

    public function test_tax_percent_splits_state_even_when_only_igst_was_sent(): void
    {
        $normalized = TaxMaster::normalize([
            'kind' => 'taxable',
            'tax_code' => 'G28',
            'purchase_tax' => 28,
            'purchase_cgst' => 0,
            'purchase_sgst' => 0,
            'purchase_igst' => 28,
            'sale_same_as_purchase' => 'y',
        ]);

        $this->assertEquals(14.0, $normalized['purchase_cgst']);
        $this->assertEquals(14.0, $normalized['purchase_sgst']);
        $this->assertEquals(14.0, $normalized['purchase_utgst']);
        $this->assertEquals(28.0, $normalized['purchase_igst']);
        $this->assertTrue($this->pathsMatch($normalized, 'purchase'));
    }

    public function test_same_yes_copies_purchase_onto_sale(): void
    {
        $normalized = TaxMaster::normalize([
            'kind' => 'taxable',
            'tax_code' => 'G5',
            'purchase_tax' => 5,
            'purchase_ut_cgst' => 2,
            'purchase_utgst' => 3,
            'sale_same_as_purchase' => 1,
            'sale_tax' => 18,
            'sale_cgst' => 9,
            'sale_sgst' => 9,
            'sale_igst' => 0,
        ]);

        $this->assertTrue($normalized['sale_same_as_purchase']);
        $this->assertEquals($normalized['purchase_tax'], $normalized['sale_tax']);
        $this->assertEquals(2.0, $normalized['sale_ut_cgst']);
        $this->assertEquals(3.0, $normalized['sale_utgst']);
        $this->assertEquals(5.0, $normalized['sale_igst']);
    }

    public function test_custom_ut_split_is_kept(): void
    {
        $normalized = TaxMaster::normalize([
            'kind' => 'inclusive',
            'tax_code' => 'INC5',
            'purchase_tax' => 5,
            'purchase_ut_cgst' => 1,
            'purchase_utgst' => 4,
            'sale_same_as_purchase' => 0,
            'sale_tax' => 12,
            'sale_ut_cgst' => 4,
            'sale_utgst' => 8,
        ]);

        $this->assertSame('inclusive', $normalized['kind']);
        $this->assertEquals(1.0, $normalized['purchase_ut_cgst']);
        $this->assertEquals(4.0, $normalized['purchase_utgst']);
        $this->assertEquals(2.5, $normalized['purchase_cgst']);
        $this->assertEquals(5.0, $normalized['purchase_igst']);
        $this->assertEquals(12.0, $normalized['sale_tax']);
        $this->assertEquals(6.0, $normalized['sale_cgst']);
        $this->assertEquals(4.0, $normalized['sale_ut_cgst']);
        $this->assertEquals(8.0, $normalized['sale_utgst']);
        $this->assertEquals(12.0, $normalized['sale_igst']);
        $this->assertTrue($this->pathsMatch($normalized, 'purchase'));
        $this->assertTrue($this->pathsMatch($normalized, 'sale'));
    }

    public function test_exempted_and_lut_zero_every_rate(): void
    {
        foreach (['exempted', 'lut'] as $kind) {
            $normalized = TaxMaster::normalize([
                'kind' => $kind,
                'tax_code' => 'EX',
                'purchase_tax' => 18,
                'purchase_cgst' => 9,
                'purchase_sgst' => 9,
                'purchase_ut_cgst' => 9,
                'purchase_utgst' => 9,
                'purchase_igst' => 18,
                'sale_same_as_purchase' => 0,
                'sale_tax' => 12,
            ]);

            $this->assertTrue($normalized['sale_same_as_purchase']);
            $this->assertEquals(0.0, $normalized['purchase_tax']);
            $this->assertEquals(0.0, $normalized['purchase_utgst']);
            $this->assertEquals(0.0, $normalized['purchase_igst']);
            $this->assertEquals(0.0, $normalized['sale_tax']);
            $this->assertEquals(0.0, $normalized['sale_igst']);
            $this->assertTrue($this->pathsMatch($normalized, 'purchase'));
        }
    }

    public function test_ut_path_must_equal_tax_percent(): void
    {
        $this->assertFalse(TaxMaster::pathsMatch(18, 9, 9, 8, 8, 18));
        $this->assertTrue(TaxMaster::pathsMatch(18, 9, 9, 8, 10, 18));
        $this->assertFalse(TaxMaster::pathsMatch(5, 2, 2, 2.5, 2.5, 5));
    }

    public function test_sample_rows_all_balance(): void
    {
        foreach (TaxMaster::sampleRows() as $row) {
            $normalized = TaxMaster::normalize($row);
            $this->assertTrue($this->pathsMatch($normalized, 'purchase'), $row['tax_code'] . ' purchase');
            $this->assertTrue($this->pathsMatch($normalized, 'sale'), $row['tax_code'] . ' sale');
        }
    }

    public function test_normalize_keeps_hsn_and_fills_blank_description(): void
    {
        $normalized = TaxMaster::normalize([
            'kind' => 'taxable',
            'tax_code' => 'G5',
            'hsn_code' => '3004',
            'hs_code' => '3004.90',
            'applied_on_category' => 'Tablets',
            'applied_on_sku' => 'SKU-1',
            'applied_on_product' => 'Dotistrol',
            'applied_on_variant' => '10ml',
            'purchase_tax' => 5,
            'sale_same_as_purchase' => 1,
        ]);

        $this->assertSame('3004', $normalized['hsn_code']);
        $this->assertSame('3004.90', $normalized['hs_code']);
        $this->assertSame('Tablets', $normalized['applied_on_category']);
        $this->assertNotSame('', (string) $normalized['description']);
        $this->assertStringContainsString('MEDICAMENTS', (string) $normalized['description']);
    }

    public function test_hs_code_and_tax_code_base(): void
    {
        $this->assertSame('300490', TaxMaster::hsFromHsn('30049099'));
        $this->assertSame('3004', TaxMaster::hsFromHsn('3004'));
        $this->assertSame('G18', TaxMaster::taxCodeBase(18));
        $this->assertSame('G2-5', TaxMaster::taxCodeBase(2.5));
    }

    public function test_hsn_directory_search_returns_official_medicine_code(): void
    {
        $rows = TaxMaster::searchHsnDirectory('30049099');
        $this->assertNotEmpty($rows);
        $this->assertSame('30049099', $rows[0]['code']);
        $this->assertSame('300490', $rows[0]['hs_code']);
        $this->assertSame('goods', $rows[0]['kind']);
    }

    public function test_sortable_columns_and_resolve_sort(): void
    {
        $columns = TaxMaster::sortableColumns();
        foreach (['id', 'kind', 'tax_code', 'description', 'purchase_tax', 'sale_same_as_purchase', 'sale_tax', 'status', 'updated_at'] as $key) {
            $this->assertArrayHasKey($key, $columns);
        }

        [$sortBy, $sortDir, $column] = TaxMaster::resolveSort('tax_code', 'asc');
        $this->assertSame('tax_code', $sortBy);
        $this->assertSame('asc', $sortDir);
        $this->assertSame('tax_masters.tax_code', $column);

        [$sortBy, $sortDir] = TaxMaster::resolveSort('not_a_column', 'up');
        $this->assertSame('id', $sortBy);
        $this->assertSame('desc', $sortDir);
    }

    private function pathsMatch(array $row, string $side): bool
    {
        return TaxMaster::pathsMatch(
            $row[$side . '_tax'],
            $row[$side . '_cgst'],
            $row[$side . '_sgst'],
            $row[$side . '_ut_cgst'],
            $row[$side . '_utgst'],
            $row[$side . '_igst']
        );
    }
}
