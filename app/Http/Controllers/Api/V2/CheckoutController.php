<?php


namespace App\Http\Controllers\Api\V2;


use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;

class CheckoutController
{
    public function apply_coupon_code(Request $request)
    {
        $coupon = Coupon::where('code', $request->coupon_code)->first();
        if ($coupon == null) {
            return response()->json([
                'result' => false,
                'message' => translate('Invalid coupon code!')
            ]);
        }

        $user_id        = $request->user_id;
        $temp_user_id   = $request->temp_user_id;
        
        $cart_items     = ($user_id != null) ?
                            Cart::where('user_id', $user_id)->where('owner_id', $coupon->user_id)->active()->get():
                            Cart::where('temp_user_id', $temp_user_id)->where('owner_id', $coupon->user_id)->active()->get();

        if ($cart_items->isEmpty()) {
            return response()->json([
                'result' => false,
                'message' => translate('This coupon is not applicable to your cart products!')
            ]);
        }

        $in_range = strtotime(date('d-m-Y')) >= $coupon->start_date && strtotime(date('d-m-Y')) <= $coupon->end_date;

        if (!$in_range) {
            return response()->json([
                'result' => false,
                'message' => translate('Coupon expired!')
            ]);
        }

        // check if user already used this coupon
        if($user_id != null){
            $is_used = CouponUsage::where('user_id', $user_id)->where('coupon_id', $coupon->id)->first() != null;
            if ($is_used) {
                return response()->json([
                    'result' => false,
                    'message' => translate('You already used this coupon!')
                ]);
            }
        }
        
        $coupon_details = json_decode($coupon->details);


        $couponResult = coupon_cart_discount_allocations($coupon, $cart_items, $coupon_details);
        $coupon_discount = $couponResult['discount'];
        $couponAllocations = $couponResult['allocations'];

        if($coupon_discount>0){
            $cart_query = $user_id != null ? Cart::where('user_id', $user_id) : Cart::where('temp_user_id', $temp_user_id);
            $cart_query->where('owner_id', $coupon->user_id)->active()->update([
                'discount' => 0,
                'coupon_code' => null,
                'coupon_applied' => 0
            ]);

            foreach ($cart_items as $cartItem) {
                $lineDiscount = $couponAllocations[(int) $cartItem->id] ?? 0;
                if ($lineDiscount <= 0) {
                    continue;
                }

                $cartItem->discount = $lineDiscount;
                $cartItem->coupon_code = $request->coupon_code;
                $cartItem->coupon_applied = 1;
                $cartItem->save();
            }
            

            return response()->json([
                'result' => true,
                'message' => translate('Coupon Applied')
            ]);
        }else{
            return response()->json([
                'result' => false,
                'message' => ($couponResult['excluded_discounted_items_count'] ?? 0) > 0
                    ? translate('Coupon discount is not applied to products that already have product or batch discounts.')
                    : translate('This coupon is not applicable to your cart products!')
            ]);
        }

    }


    public function remove_coupon_code(Request $request)
    {
        $user_id        = $request->user_id;
        $temp_user_id   = $request->temp_user_id;
        $cart_query = $user_id != null ? Cart::where('user_id', $user_id) : Cart::where('temp_user_id', $temp_user_id);
        $cart_query->update([
            'discount' => 0.00,
            'coupon_code' => "",
            'coupon_applied' => 0
        ]);

        return response()->json([
            'result' => true,
            'message' => translate('Coupon Removed')
        ]);
    }
}
