<?php

namespace App\Http\Controllers\Shipment;

use App\Http\Controllers\Controller;
use App\Models\Order;

class ShipmentController extends Controller
{
    /**
     * Resolve shipment controller by slug (like payment does)
     * e.g. 'shipway' -> Shipment\ShipwayController
     */
    public function createShipment(string $slug, Order $order, array $extra = [])
    {
        $class = __NAMESPACE__ . '\\' . ucfirst($slug) . 'Controller';
        if (class_exists($class)) {
            return (new $class)->create($order, $extra);
        }

        return null;
    }

    public function getShipmentRates(string $slug, Order $order, array $extra = [])
    {
        $class = __NAMESPACE__ . '\\' . ucfirst($slug) . 'Controller';
        if (class_exists($class) && method_exists($class, 'rates')) {
            return (new $class)->rates($order, $extra);
        }

        return [
            'success' => false,
            'data'    => [],
            'message' => 'Shipment provider not found or does not support rates.',
        ];
    }

}
