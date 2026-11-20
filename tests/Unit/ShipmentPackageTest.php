<?php

namespace Tests\Unit;

use App\Support\ShipmentPackage;
use PHPUnit\Framework\TestCase;

class ShipmentPackageTest extends TestCase
{
    /** A product_stocks row as the Weight And Dimension Master stores it (grams, cm). */
    protected function stock(array $fields = [], ?array $sheet = null): object
    {
        $stock = (object) array_merge([
            'weight' => null, 'length' => null, 'width' => null, 'height' => null,
            'qty_per_buffer_box' => null, 'count' => null, 'total_qty_per_case' => null,
            'weight_buffer_box' => null, 'weight_case' => null,
            'case_length' => null, 'case_width' => null, 'case_height' => null,
        ], $fields);
        $stock->dimensionSheet = $sheet ? (object) $sheet : null;

        return $stock;
    }

    public function test_master_grams_are_sent_as_kg(): void
    {
        $line = ShipmentPackage::line($this->stock(['weight' => 130, 'length' => 25, 'width' => 5, 'height' => 5]), null, 1);

        $this->assertEqualsWithDelta(0.13, $line['weight'], 0.0001);
        $this->assertTrue($line['weight_known']);
    }

    public function test_gross_weight_wins_over_net(): void
    {
        $line = ShipmentPackage::line($this->stock(['weight' => 56], ['piece_gross' => 62]), null, 1);

        $this->assertEqualsWithDelta(0.062, $line['weight'], 0.0001);
    }

    public function test_placeholder_and_legacy_kg_weights_use_default(): void
    {
        foreach ([1, 0.31, 0, null] as $value) {
            $line = ShipmentPackage::line($this->stock(['weight' => $value, 'length' => 1, 'width' => 1, 'height' => 1]), null, 1);

            $this->assertSame(ShipmentPackage::DEFAULT_ITEM_WEIGHT_KG, $line['weight'], "weight {$value}");
            $this->assertFalse($line['weight_known']);
            $this->assertNull($line['length'], '1x1x1 is a placeholder size');
        }
    }

    public function test_full_cases_use_case_weight_and_size(): void
    {
        // 40 g pieces, 10 per buffer, 4 buffers per case = 40 per case; case 1.8 kg, 30x20x20 cm.
        $stock = $this->stock([
            'weight' => 40, 'length' => 10, 'width' => 5, 'height' => 4,
            'qty_per_buffer_box' => 10, 'count' => 4,
            'weight_case' => 1800, 'case_length' => 30, 'case_width' => 20, 'case_height' => 20,
        ]);

        $package = ShipmentPackage::fromLines([ShipmentPackage::line($stock, null, 85)]);

        // 2 cases (3.6 kg) + 5 loose pieces (0.2 kg) = 3.8 kg.
        $this->assertSame('3.80', $package['total_physical_weight']);
        $this->assertSame(2, $package['full_cases']);
        $this->assertSame('30.00', $package['box_length']);
        $this->assertSame('20.00', $package['box_breadth']);
    }

    public function test_case_weight_derived_from_buffer_when_case_weight_missing(): void
    {
        $stock = $this->stock([
            'weight' => 50, 'qty_per_buffer_box' => 10, 'count' => 2, 'weight_buffer_box' => 520,
            'case_length' => 25, 'case_width' => 20, 'case_height' => 15,
        ]);

        $line = ShipmentPackage::line($stock, null, 20);

        $this->assertSame(20, $line['case']['per_case']);
        $this->assertEqualsWithDelta(1.04, $line['case']['weight'], 0.0001);
    }

    public function test_small_orders_pack_pieces_not_cases(): void
    {
        $stock = $this->stock([
            'weight' => 40, 'length' => 10, 'width' => 5, 'height' => 4,
            'total_qty_per_case' => 40, 'weight_case' => 1800, 'case_length' => 30, 'case_width' => 20, 'case_height' => 20,
        ]);

        $package = ShipmentPackage::fromLines([ShipmentPackage::line($stock, null, 10)]);

        // 10 x (10x5x4) = 2000 cm3 on a 10x10 minimum footprint -> 20 cm high, 0.4 kg.
        $this->assertSame(0, $package['full_cases']);
        $this->assertSame('0.40', $package['total_physical_weight']);
        $this->assertSame('20.00', $package['box_height']);
    }

    public function test_charged_weight_uses_volumetric_when_higher(): void
    {
        $package = ShipmentPackage::format(0.5, 30, 20, 20);

        $this->assertSame('2.40', $package['volumetric_weight']);
        $this->assertSame('2.40', $package['charged_weight']);
    }

    public function test_charged_weight_uses_physical_when_higher(): void
    {
        $this->assertSame('3.00', ShipmentPackage::format(3, 10, 10, 10)['charged_weight']);
    }
}
