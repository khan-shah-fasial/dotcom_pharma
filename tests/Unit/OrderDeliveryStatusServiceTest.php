<?php

namespace Tests\Unit;

use App\Services\OrderDeliveryStatusService as S;
use PHPUnit\Framework\TestCase;

class OrderDeliveryStatusServiceTest extends TestCase
{
    public function test_courier_stages_map_to_order_statuses(): void
    {
        $this->assertNull(S::statusForCourierStage('manifested', 'Manifested'));
        $this->assertSame('picked_up', S::statusForCourierStage('in_transit', 'Picked Up'));
        $this->assertSame('on_the_way', S::statusForCourierStage('in_transit', 'In Transit'));
        $this->assertSame('on_the_way', S::statusForCourierStage('out_for_delivery', 'Dispatched'));
        $this->assertSame('delivered', S::statusForCourierStage('delivered', 'Delivered'));
        $this->assertNull(S::statusForCourierStage('returned', 'RTO'));
        $this->assertNull(S::statusForCourierStage('cancelled', 'Canceled'));
    }

    public function test_only_forward_moves_are_allowed(): void
    {
        $this->assertTrue(S::isForward('pending', 'on_the_way'));
        $this->assertTrue(S::isForward('confirmed', 'picked_up'));
        $this->assertTrue(S::isForward('on_the_way', 'delivered'));

        $this->assertFalse(S::isForward('on_the_way', 'picked_up'));
        $this->assertFalse(S::isForward('delivered', 'delivered'));
        $this->assertFalse(S::isForward('cancelled', 'delivered'));
        $this->assertFalse(S::isForward(null, 'on_the_way'));
    }
}
