<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductStock;
use App\Models\User;
use App\Services\OrderPlacementService;
use ReflectionMethod;
use Tests\TestCase;

class BackendOrderPricingTest extends TestCase
{
    public function test_batch_discount_replaces_product_discount_for_backend_order_rate(): void
    {
        $product = new Product([
            'discount' => 2,
            'discount_type' => 'percent',
            'discount_start_date' => null,
            'discount_end_date' => null,
            'auction_product' => 0,
            'wholesale_product' => 0,
        ]);

        $stock = new ProductStock([
            'id' => 4692,
            'price' => 16.5,
            'role_price' => json_encode(['pts' => 29.7, 'customer' => 43.12]),
        ]);
        $stock->id = 4692;
        $stock->setRelation('wholesalePrices', collect());

        $batch = new ProductBatch([
            'product_stock_id' => 4692,
            'role_price' => json_encode(['pts' => 29.7, 'customer' => 43.12]),
            'qty' => 1000,
            'product_exp_date' => '2099-12-31',
            'discount' => 2,
            'discount_type' => 'percent',
            'discount_active' => 1,
            'discount_start_date' => null,
            'discount_end_date' => null,
        ]);
        $batch->setRelation('stock', $stock);

        $pricingUser = new User(['user_subtype' => 'pts', 'user_type' => 'customer']);
        app()->instance('pricing_user', $pricingUser);

        try {
            $resolved = resolvePrice($product, $stock, $batch, 360);

            $this->assertEqualsWithDelta(29.700, $resolved['before_productandbatch_discount'], 0.0001);
            $this->assertEqualsWithDelta(29.106, $resolved['sale_price'], 0.0001);
            $this->assertEqualsWithDelta(0.594, $resolved['discount'], 0.0001);
            $this->assertEqualsWithDelta(2.000, $resolved['discount_percent'], 0.0001);

            $method = new ReflectionMethod(OrderPlacementService::class, 'discountBackendBasePrice');
            $method->setAccessible(true);
            $manualDiscountRate = $method->invoke(
                app(OrderPlacementService::class),
                $product,
                $batch,
                360,
                29.700,
                $resolved
            );

            $this->assertEqualsWithDelta(29.106, $manualDiscountRate, 0.0001);
            $this->assertEqualsWithDelta(213.840, (29.700 - $manualDiscountRate) * 360, 0.0001);
            $this->assertEqualsWithDelta(523.908, ($manualDiscountRate * 360) * 0.05, 0.0001);
        } finally {
            app()->forgetInstance('pricing_user');
        }
    }
}
