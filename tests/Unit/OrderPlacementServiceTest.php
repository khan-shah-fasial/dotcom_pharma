<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use App\Services\OrderPlacementService;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class OrderPlacementServiceTest extends TestCase
{
    public function test_writing_order_details_starts_with_zero_shipping(): void
    {
        $order = new class extends Order
        {
            public function save(array $options = [])
            {
                return true;
            }
        };

        $method = new ReflectionMethod(OrderPlacementService::class, 'writeOrderDetails');
        $method->setAccessible(true);
        $method->invoke(
            app(OrderPlacementService::class),
            $order,
            new Collection(),
            new User(),
            []
        );

        $this->assertSame(0.0, (float) $order->grand_total);
    }
}
