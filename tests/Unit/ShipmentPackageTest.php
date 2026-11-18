<?php

namespace Tests\Unit;

use App\Support\ShipmentPackage;
use PHPUnit\Framework\TestCase;

class ShipmentPackageTest extends TestCase
{
    public function test_box_does_not_grow_every_side_with_quantity(): void
    {
        $package = ShipmentPackage::fromLines([
            ['weight' => 0.1, 'length' => 10, 'width' => 5, 'height' => 4, 'quantity' => 10],
        ]);

        // 10 x (10x5x4) = 2000 cm3 packed on a 10x10 minimum footprint -> 20 cm high.
        $this->assertSame('10.00', $package['box_length']);
        $this->assertSame('10.00', $package['box_breadth']);
        $this->assertSame('20.00', $package['box_height']);
        $this->assertSame('1.00', $package['total_physical_weight']);
    }

    public function test_charged_weight_uses_volumetric_when_higher(): void
    {
        $package = ShipmentPackage::format(0.5, 30, 20, 20);

        // 30*20*20/5000 = 2.4 kg volumetric beats 0.5 kg physical.
        $this->assertSame('2.40', $package['volumetric_weight']);
        $this->assertSame('2.40', $package['charged_weight']);
    }

    public function test_charged_weight_uses_physical_when_higher(): void
    {
        $package = ShipmentPackage::format(3, 10, 10, 10);

        $this->assertSame('3.00', $package['charged_weight']);
    }

    public function test_items_without_dimensions_get_minimum_box_and_default_weight(): void
    {
        $line = ShipmentPackage::line(null, (object) ['weight' => 0, 'length' => null, 'width' => null, 'height' => null], 2);
        $package = ShipmentPackage::fromLines([$line]);

        $this->assertSame('0.42', $package['total_physical_weight']);
        $this->assertSame('10.00', $package['box_height']);
    }

    public function test_stock_dimensions_override_product(): void
    {
        $line = ShipmentPackage::line(
            (object) ['weight' => 0.3, 'length' => 12, 'width' => 0, 'height' => null],
            (object) ['weight' => 0.1, 'length' => 5, 'width' => 6, 'height' => 7],
            1
        );

        $this->assertSame(0.3, $line['weight']);
        $this->assertSame(12.0, $line['length']);
        $this->assertSame(6.0, $line['width']);
        $this->assertSame(7.0, $line['height']);
    }
}
