<?php

namespace App\Services;

use App\Models\CreditUse;
use App\Models\Order;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Utility\EmailUtility;
use App\Utility\NotificationUtility;
use App\Utility\SmsUtility;
use Illuminate\Http\Request;

/**
 * Changes an order's delivery status with all side effects: credit, wallet refund,
 * restock, seller earnings, affiliate stats, email/SMS/web/Firebase notifications.
 *
 * Used by the admin status dropdown ($actor = logged-in user) and by courier
 * tracking sync ($actor = null, treated as admin).
 */
class OrderDeliveryStatusService
{
    /** Forward order of statuses; courier sync never moves an order backwards. */
    public const FLOW = ['pending', 'confirmed', 'picked_up', 'on_the_way', 'delivered'];

    public function change(Order $order, string $status, ?User $actor = null): void
    {
        $order->delivery_viewed = '0';
        $order->delivery_status = $status;
        $order->save();

        // Limit credit and prepaid top up both go back to the customer's credit on cancel, so neither is refunded to the wallet.
        $creditOnOrder = CreditUse::appliedTo('order_id', (int) $order->id)['total'];

        if ($status == 'cancelled') {
            CreditUse::releaseForOrder($order);
        } else {
            CreditUse::startOnDispatch($order);
        }

        if ($status == 'cancelled' && $order->payment_type == 'wallet') {
            $user = User::where('id', $order->user_id)->first();
            $user->balance += max(0, (float) $order->grand_total - $creditOnOrder);
            $user->save();
        }

        if ($status == 'cancelled') {
            release_referral_discount_lock_on_order_cancel($order);
        }

        // If the order is cancelled and the seller commission is calculated, deduct seller earning
        if ($status == 'cancelled' && $order->user->user_type == 'seller' && $order->payment_status == 'paid' && $order->commission_calculated == 1) {
            $sellerEarning = $order->commissionHistory->seller_earning;
            $shop = $order->shop;
            $shop->admin_to_pay -= $sellerEarning;
            $shop->save();
        }

        if ($actor && $actor->user_type == 'seller') {
            foreach ($order->orderDetails->where('seller_id', $actor->id) as $orderDetail) {
                $orderDetail->delivery_status = $status;
                $orderDetail->save();

                if ($status == 'cancelled') {
                    product_restock($orderDetail);
                }
            }
        } else {
            foreach ($order->orderDetails as $orderDetail) {
                $orderDetail->delivery_status = $status;
                $orderDetail->save();

                if ($status == 'cancelled') {
                    product_restock($orderDetail);
                }

                if (addon_is_activated('affiliate_system')) {
                    if (($status == 'delivered' || $status == 'cancelled') && $orderDetail->product_referral_code) {
                        $no_of_delivered = $status == 'delivered' ? $orderDetail->quantity : 0;
                        $no_of_canceled = $status == 'cancelled' ? $orderDetail->quantity : 0;

                        $referred_by_user = User::where('referral_code', $orderDetail->product_referral_code)->first();
                        if ($referred_by_user && class_exists('App\\Http\\Controllers\\AffiliateController')) {
                            $affiliateController = app()->make('App\\Http\\Controllers\\AffiliateController');
                            if (method_exists($affiliateController, 'processAffiliateStats')) {
                                $affiliateController->processAffiliateStats($referred_by_user->id, 0, 0, $no_of_delivered, $no_of_canceled);
                            }
                        }
                    }
                }
            }
        }

        // Delivery Status change email notification to Admin, seller, Customer
        EmailUtility::order_email($order, $status);

        // Delivery Status change SMS notification
        if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'delivery_status_change')->first()->status == 1) {
            try {
                SmsUtility::delivery_status_change(json_decode($order->shipping_address)->phone, $order);
            } catch (\Exception $e) {
            }
        }

        // Send web Notifications to user
        NotificationUtility::sendNotification($order, $status);

        // Sends Firebase Notifications to user
        if (get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            $request = new Request();
            $request->device_token = $order->user->device_token;
            $request->title = "Order updated !";
            $statusText = str_replace("_", "", $order->delivery_status);
            $request->text = " Your order {$order->code} has been {$statusText}";
            $request->type = "order";
            $request->id = $order->id;
            $request->user_id = $order->user->id;

            NotificationUtility::sendFirebaseNotification($request);
        }

        if ($actor && addon_is_activated('delivery_boy') && $actor->user_type == 'delivery_boy' && class_exists(\App\Http\Controllers\DeliveryBoyController::class)) {
            $deliveryBoyController = new \App\Http\Controllers\DeliveryBoyController;
            $deliveryBoyController->store_delivery_history($order);
        }
    }

    /**
     * Order status a courier tracking stage implies, or null when it implies none.
     * Returns and cancellations are left to admin.
     */
    public static function statusForCourierStage(string $stage, string $courierStatus = ''): ?string
    {
        return match ($stage) {
            'in_transit' => stripos($courierStatus, 'picked') !== false ? 'picked_up' : 'on_the_way',
            'out_for_delivery' => 'on_the_way',
            'delivered' => 'delivered',
            default => null,
        };
    }

    /**
     * Move the order forward to match the courier. Never moves it back, and never
     * touches cancelled/delivered orders. Returns the new status, or null if unchanged.
     */
    public function advanceFromCourier(Order $order, string $stage, string $courierStatus = ''): ?string
    {
        $target = static::statusForCourierStage($stage, $courierStatus);

        if (!$target || !static::isForward($order->delivery_status, $target)) {
            return null;
        }

        $this->change($order, $target);

        return $target;
    }

    /**
     * True when $to is further along the normal flow than the order's current status.
     * Cancelled, delivered or unknown current statuses are never moved.
     */
    public static function isForward(?string $from, string $to): bool
    {
        $fromIndex = array_search((string) $from, self::FLOW, true);
        $toIndex = array_search($to, self::FLOW, true);

        return $fromIndex !== false && $toIndex !== false && $toIndex > $fromIndex;
    }
}
