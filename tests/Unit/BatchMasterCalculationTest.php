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

    public function test_non_batch_clears_dates_and_defaults_code(): void
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
        $this->assertNull($normalized['manufacturing_date']);
        $this->assertNull($normalized['expiry_date']);
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
