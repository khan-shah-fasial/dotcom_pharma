@php
    $paid_carts = collect($carts)->where('is_scheme', 0);
    $admin_cart_items = array();
    $seller_cart_items = array();
    foreach ($paid_carts as $key => $cartItem){
        $product = get_single_product($cartItem['product_id']);

        if($product->added_by == 'admin'){
            array_push($admin_cart_items, $cartItem);
        }
        else{
            $cart_items = array();
            if(isset($seller_cart_items[$product->user_id])){
                $cart_items = $seller_cart_items[$product->user_id];
            }
            array_push($cart_items, $cartItem);
            $seller_cart_items[$product->user_id] = $cart_items;
        }
    }

    $pickup_point_list = array();
    if (get_setting('pickup_point') == 1) {
        $pickup_point_list = get_all_pickup_points();
    }
@endphp

<!-- Inhouse Products -->
@if (!empty($admin_cart_items))
    <div class="card mb-3 border-left-0 border-top-0 border-right-0 border-bottom rounded-0 shadow-none">
        <div class="card-header py-3 px-0 border-left-0 border-top-0 border-right-0 border-bottom border-dashed">
            <h5 class="fs-16 fw-700 text-dark mb-0">{{ get_setting('site_name') }} {{ translate('Inhouse Products') }} ({{ sprintf("%02d", count($admin_cart_items)) }})</h5>
        </div>
        <div class="card-body p-0">
            @include('frontend.partials.cart.delivery_info_details', ['cart_items' => $admin_cart_items, 'owner_id' => get_admin()->id, 'carts' => $paid_carts])
        </div>
    </div>
@endif

<!-- Seller Products -->
@if (!empty($seller_cart_items))
    @foreach ($seller_cart_items as $key => $seller_cart_item)
        <div class="card @if($loop->last) mb-0 @else mb-3 @endif border-left-0 border-top-0 border-right-0 @if($loop->last) border-bottom-0 @else border-bottom @endif rounded-0 shadow-none">
            <div class="card-header py-3 px-0 border-left-0 border-top-0 border-right-0 border-bottom border-dashed">
                <h5 class="fs-16 fw-700 text-dark mb-0">{{ get_shop_by_user_id($key)->name }} {{ translate('Products') }} ({{ sprintf("%02d", count($seller_cart_item)) }})</h5>
            </div>
            <div class="card-body p-0">
                @include('frontend.partials.cart.delivery_info_details', ['cart_items' => $seller_cart_item, 'owner_id' => $key, 'carts' => $paid_carts])
            </div>
        </div>
    @endforeach
@endif
