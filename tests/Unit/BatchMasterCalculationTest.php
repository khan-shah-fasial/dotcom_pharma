<?php

namespace Tests\Unit;

use App\Models\BatchMaster;
use Tests\TestCase;

class BatchMasterCalculationTest extends TestCase
{
    public function test_sku_ids_are_required_by_normalize_nulls(): void
    {
        $normalized = BatchMaster::normalize([
            'product_id' => '',
            'product_stock_id' => null,
            'batch_code' => 'LOT1',
            'qty' => 1,
        ]);

        $this->assertNull($normalized['product_id']);
        $this->assertNull($normalized['product_stock_id']);
    }

    public function test_non_batch_without_purchase_date_defaults_code_and_keeps_dates(): void
    {
        $normalized = BatchMaster::normalize([
            'product_id' => 10,
            'product_stock_id' => 20,
            'batch_code' => '',
            'is_non_batch' => 1,
            'manufacturing_date' => '2026-01',
            'expiry_date' => '2027-12',
            'qty' => 5,
        ]);

        $this->assertTrue($normalized['is_non_batch']);
        $this->assertSame('-', $normalized['batch_code']);
        $this->assertSame('2026-01-01', $normalized['manufacturing_date']);
        $this->assertSame('2027-12-31', $normalized['expiry_date']);
    }

    public function test_non_batch_purchase_date_sets_code_and_mfg_and_keeps_expiry(): void
    {
        $normalized = BatchMaster::normalize([
            'product_id' => 10,
            'product_stock_id' => 20,
            'batch_code' => '',
            'is_non_batch' => 1,
            'purchase_date' => '01-10-2026',
            'expiry_date' => '2028-01',
            'qty' => 1,
        ]);

        $this->assertSame('20261001', $normalized['batch_code']);
        $this->assertSame('2026-10-01', $normalized['manufacturing_date']);
        $this->assertSame('2028-01-31', $normalized['expiry_date']);
    }

    public function test_assemble_copy_keeps_purchase_and_tax_numbers(): void
    {
        $payload = BatchMaster::assembleCopy([
            'header' => ['product_id' => 3, 'product_stock_id' => 4, 'drug_name' => 'Salt'],
            'live' => ['id' => 9, 'batch' => 'DMP-153', 'qty' => 10, 'mrp_price' => 80, 'scheme' => 2, 'role_price' => ['pts' => 5]],
            'purchase' => ['id' => 7, 'mrp_rate' => 85, 'sale_rate' => 40, 'free' => 1, 'tax_code' => 'G5', 'gst_percentage' => 5, 'quantity' => 99],
            'tax' => ['tax_code' => 'G5', 'purchase_tax' => 5],
            'discounts' => ['batchwise' => 2, 'productwise' => 3, 'schemewise' => 4],
        ]);

        $this->assertSame('DMP-153', $payload['batch_code']);
        $this->assertEquals(85.0, $payload['mrp_price']);
        $this->assertEquals(40.0, $payload['purchase_rate']);
        $this->assertEquals(10.0, $payload['qty']);
        $this->assertEquals(1.0, $payload['free_qty']);
        $this->assertSame('G5', $payload['tax_code']);
        $this->assertEquals(5.0, $payload['tax_percent']);
        $this->assertEquals(2.0, $payload['batch_discount_percent']);
        $this->assertSame('Salt', $payload['drug_name']);
        $this->assertEquals(40.0, BatchMaster::amountFrom(10, 4));
    }

    public function test_qty_defaults_to_zero_and_rejects_negative_via_min_zero_cast(): void
    {
        $empty = BatchMaster::normalize([
            'product_id' => 1,
            'product_stock_id' => 2,
            'batch_code' => 'A',
            'qty' => '',
        ]);
        $this->assertSame(0.0, $empty['qty']);

        $kept = BatchMaster::normalize([
            'product_id' => 1,
            'product_stock_id' => 2,
            'batch_code' => 'A',
            'qty' => 12.5,
        ]);
        $this->assertEquals(12.5, $kept['qty']);
    }

    public function test_role_price_keeps_known_keys_only(): void
    {
        $normalized = BatchMaster::normalize([
            'product_id' => 1,
            'product_stock_id' => 2,
            'batch_code' => 'B1',
            'qty' => 1,
            'role_price' => [
                'pts' => 10,
                'ptr' => 12.25,
                'ptd' => '',
                'gov' => 8,
                'expo' => 9,
                'customer' => 15,
                'extra' => 99,
            ],
        ]);

        $decoded = BatchMaster::decodeRolePrices($normalized['role_price']);
        $this->assertSame(array_keys(BatchMaster::ROLE_KEYS), array_keys($decoded));
        $this->assertEquals(10.0, $decoded['pts']);
        $this->assertEquals(12.25, $decoded['ptr']);
        $this->assertNull($decoded['ptd']);
        $this->assertEquals(8.0, $decoded['gov']);
        $this->assertEquals(9.0, $decoded['expo']);
        $this->assertEquals(15.0, $decoded['customer']);
        $this->assertArrayNotHasKey('extra', $decoded);
    }

    public function test_month_inputs_normalize_to_first_and_last_day(): void
    {
        $normalized = BatchMaster::normalize([
            'product_id' => 1,
            'product_stock_id' => 2,
            'batch_code' => 'M1',
            'qty' => 1,
            'manufacturing_date' => '2026-02',
            'expiry_date' => '2026-02',
        ]);

        $this->assertSame('2026-02-01', $normalized['manufacturing_date']);
        $this->assertSame('2026-02-28', $normalized['expiry_date']);
    }

    public function test_sortable_columns_and_resolve_sort(): void
    {
        $columns = BatchMaster::sortableColumns();
        foreach (['sku', 'product_name', 'variant', 'id', 'batch_code', 'is_non_batch', 'manufacturing_date', 'expiry_date', 'mrp_price', 'qty', 'role_price', 'created_at', 'status', 'updated_at'] as $key) {
            $this->assertArrayHasKey($key, $columns);
        }

        [$sortBy, $sortDir, $column] = BatchMaster::resolveSort('sku', 'asc');
        $this->assertSame('sku', $sortBy);
        $this->assertSame('asc', $sortDir);
        $this->assertSame('product_stocks.sku', $column);

        [$sortBy, $sortDir] = BatchMaster::resolveSort('hack', 'asc');
        $this->assertSame('id', $sortBy);
        $this->assertSame('asc', $sortDir);
    }
}
