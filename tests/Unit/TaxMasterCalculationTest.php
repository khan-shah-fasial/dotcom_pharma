<?php

namespace Tests\Unit;

use App\Models\TaxMaster;
use Tests\TestCase;

class TaxMasterCalculationTest extends TestCase
{
    public function test_g5_purchase_splits_sum_to_tax_percent(): void
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
        $this->assertTrue(TaxMaster::totalsMatch(
            $normalized['purchase_tax'],
            $normalized['purchase_cgst'],
            $normalized['purchase_sgst'],
            $normalized['purchase_igst']
        ));
        $this->assertEquals(5.0, TaxMaster::splitTotal(
            $normalized['purchase_cgst'],
            $normalized['purchase_sgst'],
            $normalized['purchase_igst']
        ));
    }

    public function test_g18_splits_sum_to_eighteen(): void
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
        $this->assertTrue(TaxMaster::totalsMatch(
            $normalized['purchase_tax'],
            $normalized['purchase_cgst'],
            $normalized['purchase_sgst'],
            $normalized['purchase_igst']
        ));
    }

    public function test_igst_only_twenty_eight_does_not_invent_cgst_sgst(): void
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

        $this->assertEquals(0.0, $normalized['purchase_cgst']);
        $this->assertEquals(0.0, $normalized['purchase_sgst']);
        $this->assertEquals(28.0, $normalized['purchase_igst']);
        $this->assertTrue(TaxMaster::totalsMatch(
            $normalized['purchase_tax'],
            $normalized['purchase_cgst'],
            $normalized['purchase_sgst'],
            $normalized['purchase_igst']
        ));
    }

    public function test_same_yes_copies_purchase_onto_sale(): void
    {
        $normalized = TaxMaster::normalize([
            'kind' => 'taxable',
            'tax_code' => 'G5',
            'purchase_tax' => 5,
            'purchase_cgst' => 2.5,
            'purchase_sgst' => 2.5,
            'purchase_igst' => 0,
            'sale_same_as_purchase' => 1,
            'sale_tax' => 18,
            'sale_cgst' => 9,
            'sale_sgst' => 9,
            'sale_igst' => 0,
        ]);

        $this->assertTrue($normalized['sale_same_as_purchase']);
        $this->assertEquals($normalized['purchase_tax'], $normalized['sale_tax']);
        $this->assertEquals($normalized['purchase_cgst'], $normalized['sale_cgst']);
        $this->assertEquals($normalized['purchase_sgst'], $normalized['sale_sgst']);
        $this->assertEquals($normalized['purchase_igst'], $normalized['sale_igst']);
    }

    public function test_exempted_zeros_all_rates_and_forces_same(): void
    {
        $normalized = TaxMaster::normalize([
            'kind' => 'exempted',
            'tax_code' => 'EX',
            'description' => 'No Tax',
            'purchase_tax' => 18,
            'purchase_cgst' => 9,
            'purchase_sgst' => 9,
            'purchase_igst' => 0,
            'sale_same_as_purchase' => 0,
            'sale_tax' => 12,
            'sale_cgst' => 6,
            'sale_sgst' => 6,
            'sale_igst' => 0,
        ]);

        $this->assertTrue($normalized['sale_same_as_purchase']);
        $this->assertEquals(0.0, $normalized['purchase_tax']);
        $this->assertEquals(0.0, $normalized['purchase_cgst']);
        $this->assertEquals(0.0, $normalized['purchase_sgst']);
        $this->assertEquals(0.0, $normalized['purchase_igst']);
        $this->assertEquals(0.0, $normalized['sale_tax']);
        $this->assertEquals(0.0, $normalized['sale_cgst']);
        $this->assertEquals(0.0, $normalized['sale_sgst']);
        $this->assertEquals(0.0, $normalized['sale_igst']);
        $this->assertTrue(TaxMaster::totalsMatch(
            $normalized['purchase_tax'],
            $normalized['purchase_cgst'],
            $normalized['purchase_sgst'],
            $normalized['purchase_igst']
        ));
    }

    public function test_mismatch_is_rejected(): void
    {
        $this->assertFalse(TaxMaster::totalsMatch(5, 2, 2, 0));
        $this->assertFalse(TaxMaster::totalsMatch(18, 9, 9, 1));
    }

    public function test_inclusive_still_must_balance(): void
    {
        $normalized = TaxMaster::normalize([
            'kind' => 'inclusive',
            'tax_code' => 'INC5',
            'purchase_tax' => 5,
            'purchase_cgst' => 2.5,
            'purchase_sgst' => 2.5,
            'purchase_igst' => 0,
            'sale_same_as_purchase' => 0,
            'sale_tax' => 5,
            'sale_cgst' => 0,
            'sale_sgst' => 0,
            'sale_igst' => 5,
        ]);

        $this->assertSame('inclusive', $normalized['kind']);
        $this->assertTrue(TaxMaster::totalsMatch(
            $normalized['purchase_tax'],
            $normalized['purchase_cgst'],
            $normalized['purchase_sgst'],
            $normalized['purchase_igst']
        ));
        $this->assertTrue(TaxMaster::totalsMatch(
            $normalized['sale_tax'],
            $normalized['sale_cgst'],
            $normalized['sale_sgst'],
            $normalized['sale_igst']
        ));
        $this->assertEquals(5.0, $normalized['sale_igst']);
        $this->assertEquals(0.0, $normalized['sale_cgst']);
    }

    public function test_sample_rows_all_balance(): void
    {
        foreach (TaxMaster::sampleRows() as $row) {
            $normalized = TaxMaster::normalize($row);
            $this->assertTrue(TaxMaster::totalsMatch(
                $normalized['purchase_tax'],
                $normalized['purchase_cgst'],
                $normalized['purchase_sgst'],
                $normalized['purchase_igst']
            ), $row['tax_code'] . ' purchase');
            $this->assertTrue(TaxMaster::totalsMatch(
                $normalized['sale_tax'],
                $normalized['sale_cgst'],
                $normalized['sale_sgst'],
                $normalized['sale_igst']
            ), $row['tax_code'] . ' sale');
        }
    }
}
