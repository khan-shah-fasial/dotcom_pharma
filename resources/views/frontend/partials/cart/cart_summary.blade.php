<div class="z-3 sticky-top-lg">
    <div class="card rounded-0 border">

        @php
            $paid_carts = collect($carts)->where('is_scheme', 0);
            $product_count = count($paid_carts);
            $subtotal_for_min_order_amount = 0;
            $subtotal = 0;
            $tax = 0;
            $product_shipping_cost = 0;
            $shipping = 0;
            $coupon_code = null;
            $coupon_discount = 0;
            $total_point = 0;
        @endphp
        @foreach ($carts as $key => $cartItem)
            @php
                $product = get_single_product($cartItem['product_id']);
                $subtotal_for_min_order_amount += cart_product_price($cartItem, $cartItem->product, false, false) * $cartItem['quantity'];
                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                // Use stored tax value from cart table, fallback to recalculation if not stored
                $itemTax = ($cartItem->tax ?? cart_product_tax($cartItem, $product, false)) * $cartItem['quantity'];
                $tax += $itemTax;
                $product_shipping_cost = $cartItem['shipping_cost'];
                $shipping += $product_shipping_cost;
                if ((get_setting('coupon_system') == 1) && ($cartItem->coupon_applied == 1)) {
                    $coupon_code = $cartItem->coupon_code;
                    $coupon_discount = $carts->sum('discount');
                }
                if (addon_is_activated('club_point') && !(bool) ($cartItem->is_scheme ?? false)) {
                    $total_point += $product->earn_point * $cartItem['quantity'];
                }
            @endphp
        @endforeach

        <div class="card-header pt-4 pb-1 border-bottom-0">
            <h3 class="fs-16 fw-700 mb-0">{{ translate('Order Summary') }}</h3>
            <div class="text-right">
                <!-- Minimum Order Amount -->
                @if (get_setting('minimum_order_amount_check') == 1 && $subtotal_for_min_order_amount < get_setting('minimum_order_amount'))
                    <span class="badge badge-inline badge-warning fs-12 rounded-0 px-2">
                        {{ translate('Minimum Order Amount') . ' ' . single_price(get_setting('minimum_order_amount')) }}
                    </span>
                @endif
            </div>
        </div>

        <div class="card-body pt-2">

            <div class="row gutters-5">
                <!-- Total Products -->
                <div class="@if (addon_is_activated('club_point')) col-6 @else col-12 @endif">
                    <div class="d-flex align-items-center justify-content-between bg-primary p-2" style="background: #2b56a1 !important;">
                        <span class="fs-13 text-white">{{ translate('Total Products') }}</span>
                        <span class="fs-13 fw-700 text-white">{{ sprintf("%02d", $product_count) }}</span>
                    </div>
                </div>
                @if (addon_is_activated('club_point'))
                    <!-- Total Clubpoint -->
                    <div class="col-6">
                        <div class="d-flex align-items-center justify-content-between bg-secondary-base p-2">
                            <span class="fs-13 text-white">{{ translate('Total Clubpoint') }}</span>
                            <span class="fs-13 fw-700 text-white">{{ sprintf("%02d", $total_point) }}</span>
                        </div>
                    </div>
                @endif
            </div>

            <input type="hidden" id="sub_total" value="{{ $subtotal }}">

            <table class="table my-3">
                <tfoot>
                    <!-- Subtotal -->
                    <tr class="cart-subtotal">
                        <th class="pl-0 fs-14 fw-400 pt-0 pb-2 text-dark border-top-0">{{ translate('Subtotal') }} ({{ sprintf("%02d", $product_count) }} {{ translate('Products') }})</th>
                        <td class="text-right pr-0 fs-14 pt-0 pb-2 text-dark border-top-0">{{ single_price($subtotal) }}</td>
                    </tr>
                    <!-- Tax -->
                    <tr class="cart-tax">
                        <th class="pl-0 fs-14 fw-400 pt-0 pb-2 text-dark border-top-0">{{ translate('Tax') }}</th>
                        <td class="text-right pr-0 fs-14 pt-0 pb-2 text-dark border-top-0">{{ single_price($tax) }}</td>
                    </tr>
                    @if ($proceed != 1)
                    <!-- Total Shipping -->
                    <tr class="cart-shipping">
                        <th class="pl-0 fs-14 fw-400 pt-0 pb-2 text-dark border-top-0">{{ translate('Total Shipping') }}</th>
                        <td class="text-right pr-0 fs-14 pt-0 pb-2 text-dark border-top-0">{{ single_price($shipping) }}</td>
                    </tr>
                    @endif
                    <!-- Coupon Discount -->
                    @if ($coupon_discount > 0)
                        <tr class="cart-coupon-discount">
                            <th class="pl-0 fs-14 fw-400 pt-0 pb-2 text-dark border-top-0">{{ translate('Coupon Discount') }}</th>
                            <td class="text-right pr-0 fs-14 pt-0 pb-2 text-dark border-top-0">{{ single_price($coupon_discount) }}</td>
                        </tr>
                    @endif

                    @php
                        $total = $subtotal + $tax + $shipping;
                        if ($coupon_discount > 0) {
                            $total -= $coupon_discount;
                        }

                        // Referral discount removed; wallet credit is applied post-payment.
                        $referral_discount = 0;

                        if (Session::has('club_point')) {
                            $total -= Session::get('club_point');
                        }
                        $total = max(0, $total);
                    @endphp
                    <!-- Redeem point -->
                    @if (Session::has('club_point'))
                        <tr class="cart-club-point">
                            <th class="pl-0 fs-14 fw-400 pt-0 pb-2 text-dark border-top-0">{{ translate('Redeem point') }}</th>
                            <td class="text-right pr-0 fs-14 pt-0 pb-2 text-dark border-top-0">{{ single_price(Session::get('club_point')) }}</td>
                        </tr>
                    @endif
                    <!-- Total -->
                    <tr class="cart-total">
                        <th class="pl-0 fs-14 text-dark fw-700 border-top-0 pt-3 text-uppercase">{{ translate('Total') }}</th>
                        <td class="text-right pr-0 fs-16 fw-700 text-primary border-top-0 pt-3">{{ single_price($total) }}</td>
                    </tr>
                </tfoot>
            </table>

            @php
                $rewardPreview = get_gift_reward_preview($total);
            @endphp

            @if (!empty($rewardPreview['enabled']))
                @if (!empty($rewardPreview['matched_reward']))
                    <div class="alert alert-soft-success rounded-0 py-2">
                        {{ str_replace(':amount', $rewardPreview['matched_reward'], translate("You'll earn :amount wallet point reward on this order after payment.")) }}
                    </div>
                @endif

                @if (!empty($rewardPreview['next_min']) && (!empty($rewardPreview['delta_to_next']) && $rewardPreview['delta_to_next'] > 0) && (empty($rewardPreview['matched_reward']) || $rewardPreview['next_min'] > ($rewardPreview['matched_min'] ?? 0)))
                    <div class="alert alert-soft-info rounded-0 py-2 mb-0">
                        {{ str_replace(
                            [':amount', ':reward'],
                            [single_price($rewardPreview['delta_to_next']), $rewardPreview['next_reward'] ?? 0],
                            translate('Add :amount more to earn :reward wallet reward.')
                        ) }}
                    </div>
                @endif
            @endif

            <!-- Coupon System -->
            @if (get_setting('coupon_system') == 1)
                <div class="mt-2 fs-12 text-muted">
                    {{ translate('Coupon discount applies only to products without product or batch discounts. Products already discounted are excluded.') }}
                </div>
                @if ($coupon_discount > 0 && $coupon_code)
                    <div class="mt-3">
                        <form class="" id="remove-coupon-form" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="proceed" value="{{ $proceed }}">
                            <div class="input-group">
                                <div class="form-control">{{ $coupon_code }}</div>
                                <div class="input-group-append">
                                    <button type="button" id="coupon-remove"
                                        class="btn btn-primary">{{ translate('Change Coupon') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="mt-3">
                        <form class="" id="apply-coupon-form" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="proceed" value="{{ $proceed }}">
                            <div class="input-group">
                                <input type="text" class="form-control rounded-0" name="code"
                                    onkeydown="return event.key != 'Enter';"
                                    placeholder="{{ translate('Have coupon code? Apply here') }}" required>
                                <div class="input-group-append">
                                    <button type="button" id="coupon-apply"
                                        class="btn btn-primary rounded-0" style="background: #2b56a1 !important;">{{ translate('Apply') }}</button>
                                </div>
                            </div>
                            @if (!auth()->check())
                                <small>{{ translate('You must Login as customer to apply coupon') }}</small>
                            @endif

                        </form>
                    </div>
                @endif
            @endif

            @if ($proceed == 1)
            <!-- Continue to Shipping -->
            <div class="mt-4">
                <a href="{{ route('checkout') }}" class="btn btn-primary btn-block fs-14 fw-700 rounded-0 px-4" style="background: #2b56a1 !important;">
                    {{ translate('Proceed to Checkout')}} ({{ sprintf("%02d", $product_count) }})
                </a>
            </div>
            @endif

        </div>
    </div>
</div>
