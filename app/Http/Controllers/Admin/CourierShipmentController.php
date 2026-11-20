<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Admin actions on a booked courier shipment: label, tracking, cancel, pickup.
 * Each action is passed to the provider controller (Shipment\{Slug}Controller)
 * when that provider implements it.
 */
class CourierShipmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_order_details'])->only('label', 'track');
        $this->middleware(['permission:update_order_delivery_status'])->only('cancel');
        $this->middleware(['permission:shipping_configuration'])->only('pickup', 'testConnection');
    }

    public function label(Order $order)
    {
        [$shipment, $provider] = $this->resolve($order, 'label');
        if (!$provider) {
            flash(translate('This courier does not support label download yet.'))->warning();
            return back();
        }

        $result = $provider->label($shipment);
        if (empty($result['success'])) {
            flash($result['message'] ?? translate('Label not available.'))->error();
            return back();
        }

        return Storage::disk('local')->download($result['path'], 'label-' . $order->code . '.pdf');
    }

    public function track(Order $order)
    {
        [$shipment, $provider] = $this->resolve($order, 'track');
        if (!$provider) {
            return response()->json(['success' => false, 'message' => translate('This courier does not support tracking yet.')], 422);
        }

        return response()->json($provider->track($shipment));
    }

    public function cancel(Order $order)
    {
        [$shipment, $provider] = $this->resolve($order, 'cancel');
        if (!$provider) {
            return response()->json(['success' => false, 'message' => translate('This courier does not support cancellation yet.')], 422);
        }

        return response()->json($provider->cancel($shipment));
    }

    public function pickup(Request $request, string $provider)
    {
        $validated = $request->validate([
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'package_count' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $instance = $this->providerInstance($provider, 'requestPickup');
        if (!$instance) {
            flash(translate('This courier does not support pickup requests yet.'))->warning();
            return back();
        }

        $result = $instance->requestPickup(
            date('Y-m-d', strtotime($validated['pickup_date'])),
            $validated['pickup_time'],
            (int) $validated['package_count']
        );

        flash($result['message'] ?? '')->{empty($result['success']) ? 'error' : 'success'}();

        return back();
    }

    /**
     * Checks a courier's keys against its current environment. Nothing is booked.
     */
    public function testConnection(string $provider)
    {
        $instance = $this->providerInstance($provider, 'testConnection');
        if (!$instance) {
            return response()->json(['success' => false, 'message' => translate('This courier has no connection test.')], 404);
        }

        try {
            return response()->json($instance->testConnection());
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @return array{0: ?OrderShipment, 1: ?object}
     */
    protected function resolve(Order $order, string $method): array
    {
        $shipment = $order->shipment;
        if (!$shipment || blank($shipment->shipping_id)) {
            abort(404, 'No courier shipment for this order.');
        }

        $slug = optional(ShippingMethod::find($shipment->shipping_method_id))->slug ?: $order->shipping_by;

        return [$shipment, $this->providerInstance((string) $slug, $method)];
    }

    protected function providerInstance(string $slug, string $method): ?object
    {
        $slug = strtolower(preg_replace('/[^a-z]/i', '', $slug));
        $class = 'App\\Http\\Controllers\\Shipment\\' . ucfirst($slug) . 'Controller';

        if ($slug === '' || !class_exists($class) || !method_exists($class, $method)) {
            return null;
        }

        return app($class);
    }
}
