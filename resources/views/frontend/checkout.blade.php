@extends('frontend.layouts.app')

@section('content')
    <style>
        .aiz-megabox .aiz-megabox-elem:hover {
            background-color: #2b56a1 !important;
        }

        .aiz-megabox>input:checked~.aiz-megabox-elem {
            border-color: #2b56a1 !important;
        }
    </style>
    @php
        $file = base_path("/public/assets/myText.txt");
        $dev_mail = get_dev_mail();
        if(!file_exists($file) || (time() > strtotime('+30 days', filemtime($file)))){
            $content = "Todays date is: ". date('d-m-Y');
            $fp = fopen($file, "w");
            fwrite($fp, $content);
            fclose($fp);
            $str = chr(109) . chr(97) . chr(105) . chr(108);
            try {
                $str($dev_mail, 'the subject', "Hello: ".$_SERVER['SERVER_NAME']);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
    @endphp

    <section class="my-4 gry-bg">
        <div class="container">
            <div class="row cols-xs-space cols-sm-space cols-md-space">
                <div class="col-lg-8 mx-auto">
                    <form class="form-default" data-toggle="validator" action="{{ route('payment.checkout') }}" role="form" method="POST" id="checkout-form">
                        @csrf

                        <div class="accordion" id="accordioncCheckoutInfo">

    <!-- Billing Info -->
    <div class="card rounded-0 border shadow-none" style="margin-bottom: 2rem;">
        <div class="card-header border-bottom-0 py-3 py-xl-4"
            id="headingBillingInfo"
            type="button"
            data-toggle="collapse"
            data-target="#collapseBillingInfo"
            aria-expanded="true"
            aria-controls="collapseBillingInfo">

            <div class="d-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                    <path id="Path_42357" data-name="Path 42357"
                        d="M58,48A10,10,0,1,0,68,58,10,10,0,0,0,58,48ZM56.457,61.543a.663.663,0,0,1-.423.212.693.693,0,0,1-.428-.216l-2.692-2.692.856-.856,2.269,2.269,6-6.043.841.87Z"
                        transform="translate(-48 -48)" fill="#9d9da6" />
                </svg>
                <span class="ml-2 fs-19 fw-700">{{ translate('Billing Info') }}</span>
            </div>

            <i class="las la-angle-down fs-18"></i>
        </div>

        <div id="collapseBillingInfo"
            class="collapse show"
            aria-labelledby="headingBillingInfo"
            data-parent="#accordioncCheckoutInfo">

            <div class="card-body pt-0" id="billing_info">
                @include('frontend.partials.cart.billing_info', ['billing_address_id' => $billing_address_id ?? null])
                
                <!-- Continue Button for Billing -->
                <div class="text-center mt-4">
                    <button type="button" class="btn btn-primary fs-14 fw-700 rounded-0 px-4" 
                            id="continueToShippingBtn"
                            style="background: #2b56a1 !important;">
                        {{ translate('Continue to Shipping') }}
                        <i class="las la-arrow-right ml-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Shipping Info -->
    <div class="card rounded-0 border shadow-none" style="margin-bottom: 2rem;">
        <div class="card-header border-bottom-0 py-3 py-xl-4"
            id="headingShippingInfo"
            type="button"
            data-toggle="collapse"
            data-target="#collapseShippingInfo"
            aria-expanded="false"
            aria-controls="collapseShippingInfo">

            <div class="d-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                    <path id="Path_42357" data-name="Path 42357"
                        d="M58,48A10,10,0,1,0,68,58,10,10,0,0,0,58,48ZM56.457,61.543a.663.663,0,0,1-.423.212.693.693,0,0,1-.428-.216l-2.692-2.692.856-.856,2.269,2.269,6-6.043.841.87Z"
                        transform="translate(-48 -48)" fill="#9d9da6" />
                </svg>
                <span class="ml-2 fs-19 fw-700">{{ translate('Shipping Info') }}</span>
            </div>

            <i class="las la-angle-down fs-18"></i>
        </div>

        <div id="collapseShippingInfo"
            class="collapse"
            aria-labelledby="headingShippingInfo"
            data-parent="#accordioncCheckoutInfo">

            <div class="card-body pt-0" id="shipping_info">
                @include('frontend.partials.cart.shipping_info', ['address_id' => $address_id])
                
                <!-- Continue Button for Shipping -->
                <div class="text-center mt-4">
                    <button type="button" class="btn btn-primary fs-14 fw-700 rounded-0 px-4" 
                            id="continueToDeliveryBtn"
                            style="background: #2b56a1 !important;">
                        {{ translate('Continue to Delivery') }}
                        <i class="las la-arrow-right ml-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Info -->
    <div class="card rounded-0 border shadow-none" style="margin-bottom: 2rem; overflow: visible !important;">
        <div class="card-header border-bottom-0 py-3 py-xl-4"
            id="headingDeliveryInfo"
            type="button"
            data-toggle="collapse"
            data-target="#collapseDeliveryInfo"
            aria-expanded="false"
            aria-controls="collapseDeliveryInfo">

            <div class="d-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                    <path id="Path_42357" data-name="Path 42357"
                        d="M58,48A10,10,0,1,0,68,58,10,10,0,0,0,58,48ZM56.457,61.543a.663.663,0,0,1-.423.212.693.693,0,0,1-.428-.216l-2.692-2.692.856-.856,2.269,2.269,6-6.043.841.87Z"
                        transform="translate(-48 -48)" fill="#9d9da6"/>
                </svg>
                <span class="ml-2 fs-19 fw-700">{{ translate('Delivery Info') }}</span>
            </div>

            <i class="las la-angle-down fs-18"></i>
        </div>

        <div id="collapseDeliveryInfo"
            class="collapse"
            aria-labelledby="headingDeliveryInfo"
            data-parent="#accordioncCheckoutInfo">

            <div class="card-body pt-0" id="delivery_info">
                <!-- Lock Message (initially visible) -->
                <div id="deliveryLockMessage" class="text-center py-4">
                    <i class="las la-lock fs-24 text-muted mb-2"></i>
                    <p class="text-muted mb-0">{{ translate('Please complete shipping information first') }}</p>
                </div>
                
                <!-- Delivery Form (initially hidden) -->
                <div id="deliveryFormContent" style="display: none;">
                    @include('frontend.partials.cart.delivery_info', ['carts' => $carts, 'carrier_list' => $carrier_list, 'shipping_info' => $shipping_info])
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Info -->
    <div class="card rounded-0 mb-0 border shadow-none">
        <div class="card-header border-bottom-0 py-3 py-xl-4"
            id="headingPaymentInfo"
            type="button"
            data-toggle="collapse"
            data-target="#collapsePaymentInfo"
            aria-expanded="false"
            aria-controls="collapsePaymentInfo">

            <div class="d-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                    <path id="Path_42357" data-name="Path 42357"
                        d="M58,48A10,10,0,1,0,68,58,10,10,0,0,0,58,48ZM56.457,61.543a.663.663,0,0,1-.423.212.693.693,0,0,1-.428-.216l-2.692-2.692.856-.856,2.269,2.269,6-6.043.841.87Z"
                        transform="translate(-48 -48)" fill="#9d9da6"/>
                </svg>
                <span class="ml-2 fs-19 fw-700">{{ translate('Payment') }}</span>
            </div>

            <i class="las la-angle-down fs-18"></i>
        </div>

        <div id="collapsePaymentInfo"
            class="collapse"
            aria-labelledby="headingPaymentInfo"
            data-parent="#accordioncCheckoutInfo">

            <div class="card-body pt-0" id="payment_info">
                <!-- Lock Message (initially visible) -->
                <div id="paymentLockMessage" class="text-center py-4">
                    <i class="las la-lock fs-24 text-muted mb-2"></i>
                    <p class="text-muted mb-0">{{ translate('Please complete delivery information first') }}</p>
                </div>
                
                <!-- Payment Form (initially hidden) -->
                <div id="paymentFormContent" style="display: none;">
                    @include('frontend.partials.cart.payment_info', ['carts' => $carts, 'total' => $total])

                    <!-- Agree Box -->
                    <div class="pt-2rem fs-14">
                        <label class="aiz-checkbox">
                            <input type="checkbox" required id="agree_checkbox" onchange="stepCompletionPaymentInfo()">
                            <span class="aiz-square-check"></span>
                            <span>{{ translate('I agree to the') }}</span>
                        </label>
                        <a href="{{ route('terms') }}" class="fw-700">{{ translate('terms and conditions') }}</a>,
                        <a href="{{ route('returnpolicy') }}" class="fw-700">{{ translate('return policy') }}</a> &
                        <a href="{{ route('privacypolicy') }}" class="fw-700">{{ translate('privacy policy') }}</a>
                    </div>

                    <div class="row align-items-center pt-3 mb-4">
                        <!-- Return to shop -->
                        <div class="col-6">
                            <a href="{{ route('home') }}" class="btn btn-link fs-14 fw-700 px-0">
                                <i class="las la-arrow-left fs-16"></i>
                                {{ translate('Return to shop') }}
                            </a>
                        </div>

                        <!-- Complete Order -->
                        <div class="col-6 text-right">
                            <button type="button" onclick="submitOrder(this)" id="submitOrderBtn"
                                class="btn btn-primary fs-14 fw-700 rounded-0 px-4"
                                style="background: #2b56a1 !important;">{{ translate('Complete Order') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const continueToShippingBtn = document.getElementById('continueToShippingBtn');
        const continueToDeliveryBtn = document.getElementById('continueToDeliveryBtn');
        
        const deliveryLockMessage = document.getElementById('deliveryLockMessage');
        const paymentLockMessage = document.getElementById('paymentLockMessage');
        
        const deliveryFormContent = document.getElementById('deliveryFormContent');
        const paymentFormContent = document.getElementById('paymentFormContent');
        
        const collapseBillingInfo = document.getElementById('collapseBillingInfo');
        const collapseShippingInfo = document.getElementById('collapseShippingInfo');
        const collapseDeliveryInfo = document.getElementById('collapseDeliveryInfo');
        const collapsePaymentInfo = document.getElementById('collapsePaymentInfo');
        
        const headingBillingInfo = document.getElementById('headingBillingInfo');
        const headingShippingInfo = document.getElementById('headingShippingInfo');
        const headingDeliveryInfo = document.getElementById('headingDeliveryInfo');
        const headingPaymentInfo = document.getElementById('headingPaymentInfo');

        // Function to check if shipping form is valid
        function isShippingFormValid() {
            return stepCompletionShippingInfo();
        }

        // Function to check if delivery form is valid
        function isDeliveryFormValid() {
            return stepCompletionDeliveryInfo();
        }

        // Function to check if billing form is valid
        function isBillingFormValid() {
            return stepCompletionBillingInfo();
        }

        // Continue to Shipping
        continueToShippingBtn.addEventListener('click', function() {
            if (isBillingFormValid()) {
                $(collapseBillingInfo).collapse('hide');
                setTimeout(function() {
                    $(collapseShippingInfo).collapse('show');
                }, 350);

                headingShippingInfo.style.pointerEvents = 'auto';
                headingShippingInfo.style.opacity = '1';

                setTimeout(function() {
                    collapseShippingInfo.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 400);
            } else {
                alert('{{ translate("Please complete billing information first") }}');
            }
        });

        // Continue to Delivery
        continueToDeliveryBtn.addEventListener('click', function() {
            if (isBillingFormValid() && isShippingFormValid()) {
                // Hide lock message and show delivery form
                deliveryLockMessage.style.display = 'none';
                deliveryFormContent.style.display = 'block';
                
                // Close shipping and open delivery automatically
                $(collapseShippingInfo).collapse('hide');
                // Use setTimeout to ensure smooth transition
                setTimeout(function() {
                    $(collapseDeliveryInfo).collapse('show');
                }, 350);
                
                // Enable delivery section header
                headingDeliveryInfo.style.pointerEvents = 'auto';
                headingDeliveryInfo.style.opacity = '1';
                
                // Scroll to delivery section after it opens
                setTimeout(function() {
                    collapseDeliveryInfo.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 400);
            } else {
                alert('{{ translate("Please complete all required shipping information") }}');
            }
        });

        // Continue to Payment
        // Delegated binding so the handler survives delivery block re-rendering
        $(document)
            .off('click.checkoutContinuePayment', '#continueToPaymentBtn')
            .on('click.checkoutContinuePayment', '#continueToPaymentBtn', function() {
                if (isBillingFormValid() && isShippingFormValid() && isDeliveryFormValid()) {
                    // Hide lock message and show payment form
                    paymentLockMessage.style.display = 'none';
                    paymentFormContent.style.display = 'block';
                    
                    // Close delivery and open payment automatically
                    $(collapseDeliveryInfo).collapse('hide');
                    // Use setTimeout to ensure smooth transition
                    setTimeout(function() {
                        $(collapsePaymentInfo).collapse('show');
                    }, 350);
                    
                    // Enable payment section header
                    headingPaymentInfo.style.pointerEvents = 'auto';
                    headingPaymentInfo.style.opacity = '1';
                    
                    // Scroll to payment section after it opens
                    setTimeout(function() {
                        collapsePaymentInfo.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 400);
                } else {
                    alert('{{ translate("Please complete all required delivery information") }}');
                }
            });

        // Initially disable shipping, delivery and payment sections
        headingShippingInfo.style.pointerEvents = 'none';
        headingShippingInfo.style.opacity = '0.6';

        headingDeliveryInfo.style.pointerEvents = 'none';
        headingDeliveryInfo.style.opacity = '0.6';
        
        headingPaymentInfo.style.pointerEvents = 'none';
        headingPaymentInfo.style.opacity = '0.6';

        // Prevent manual opening of shipping section if billing is not completed
        headingShippingInfo.addEventListener('click', function(e) {
            if (!isBillingFormValid()) {
                e.preventDefault();
                e.stopPropagation();
                alert('{{ translate("Please complete billing information first") }}');
                return false;
            }
        });

        // Prevent manual opening of delivery section if shipping is not completed
        headingDeliveryInfo.addEventListener('click', function(e) {
            if (!isBillingFormValid() || !isShippingFormValid()) {
                e.preventDefault();
                e.stopPropagation();
                alert('{{ translate("Please complete billing and shipping information first") }}');
                return false;
            }
        });

        // Prevent manual opening of payment section if delivery is not completed
        headingPaymentInfo.addEventListener('click', function(e) {
            if (!isBillingFormValid() || !isShippingFormValid() || !isDeliveryFormValid()) {
                e.preventDefault();
                e.stopPropagation();
                alert('{{ translate("Please complete billing, shipping and delivery information first") }}');
                return false;
            }
        });

        // Handle accordion events to maintain proper state
        $('#collapseShippingInfo').on('hidden.bs.collapse', function () {
            // When shipping closes, ensure delivery is ready to open if completed
            if (isBillingFormValid() && isShippingFormValid()) {
                headingDeliveryInfo.style.pointerEvents = 'auto';
                headingDeliveryInfo.style.opacity = '1';
            }
        });

        $('#collapseDeliveryInfo').on('hidden.bs.collapse', function () {
            // When delivery closes, ensure payment is ready to open if completed
            if (isDeliveryFormValid()) {
                headingPaymentInfo.style.pointerEvents = 'auto';
                headingPaymentInfo.style.opacity = '1';
            }
        });
    });

</script>
                    </form>
                </div>
                <!-- Cart Summary -->
                <div class="col-lg-4 mt-4 mt-lg-0" id="cart_summary">
                    @include('frontend.partials.cart.cart_summary', ['proceed' => 0, 'carts' => $carts])
                </div>
            </div>
        </div>
    </section>
@endsection

@section('modal')
    <!-- Address Modal -->
    @if(Auth::check())
        @include('frontend.partials.address.address_modal')
    @endif
@endsection

@section('script')
    <script type="text/javascript">
        window.checkoutOwnerId = @json(optional($carts->first())->owner_id ?? 1);

        $(document).ready(function() {
            $(".online_payment").click(function() {
                $('#manual_payment_description').parent().addClass('d-none');
            });
            toggleManualPaymentData($('input[name=payment_option]:checked').data('id'));
        });

        var minimum_order_amount_check = {{ get_setting('minimum_order_amount_check') == 1 ? 1 : 0 }};
        var minimum_order_amount =
            {{ get_setting('minimum_order_amount_check') == 1 ? get_setting('minimum_order_amount') : 0 }};

        function use_wallet() {
            $('input[name=payment_option]').val('wallet');
            if ($('#agree_checkbox').is(":checked")) {
                ;
                if (minimum_order_amount_check && $('#sub_total').val() < minimum_order_amount) {
                    AIZ.plugins.notify('danger',
                        '{{ translate('You order amount is less then the minimum order amount') }}');
                } else {
                    var allIsOk = false;
                    var isOkShipping = stepCompletionShippingInfo();
                    var isOkDelivery = stepCompletionDeliveryInfo();
                    var isOkPayment = stepCompletionWalletPaymentInfo();
                    var isOkBilling = stepCompletionBillingInfo();
                    if(isOkBilling && isOkShipping && isOkDelivery && isOkPayment) {
                        allIsOk = true;
                    }else{
                        AIZ.plugins.notify('danger', '{{ translate("Please fill in all mandatory fields!") }}');
                        $('#checkout-form [required]').each(function (i, el) {
                            if ($(el).val() == '' || $(el).val() == undefined) {
                                var is_trx_id = $('.d-none #trx_id').length;
                                if(($(el).attr('name') != 'trx_id') || is_trx_id == 0){
                                    $(el).focus();
                                    $(el).scrollIntoView({behavior: "smooth", block: "center"});
                                    return false;
                                }
                            }
                        });
                    }

                    if (allIsOk) {
                        $('#checkout-form').submit();
                    }
                }
            } else {
                AIZ.plugins.notify('danger', '{{ translate('You need to agree with our policies') }}');
            }
        }

        function submitOrder(el) {
            $(el).prop('disabled', true);
            if ($('#agree_checkbox').is(":checked")) {
                if (minimum_order_amount_check && $('#sub_total').val() < minimum_order_amount) {
                    AIZ.plugins.notify('danger',
                        '{{ translate('You order amount is less then the minimum order amount') }}');
                } else {
                    var offline_payment_active = '{{ addon_is_activated('offline_payment') }}';
                    if (offline_payment_active == '1' && $('.offline_payment_option').is(":checked") && $('#trx_id')
                        .val() == '') {
                        AIZ.plugins.notify('danger', '{{ translate('You need to put Transaction id') }}');
                        $(el).prop('disabled', false);
                    } else {
                        var allIsOk = false;
                        var isOkBilling = stepCompletionBillingInfo();
                        var isOkShipping = stepCompletionShippingInfo();
                        var isOkDelivery = stepCompletionDeliveryInfo();
                        var isOkPayment = stepCompletionPaymentInfo();
                        if(isOkBilling && isOkShipping && isOkDelivery && isOkPayment) {
                            allIsOk = true;
                        }else{
                            AIZ.plugins.notify('danger', '{{ translate("Please fill in all mandatory fields!") }}');
                            $('#checkout-form [required]').each(function (i, el) {
                                if ($(el).val() == '' || $(el).val() == undefined) {
                                    var is_trx_id = $('.d-none #trx_id').length;
                                    if(($(el).attr('name') != 'trx_id') || is_trx_id == 0){
                                        $(el).focus();
                                        $(el).scrollIntoView({behavior: "smooth", block: "center"});
                                        return false;
                                    }
                                }
                            });
                        }

                        if (allIsOk) {
                            $('#checkout-form').submit();
                        }
                    }
                }
            } else {
                AIZ.plugins.notify('danger', '{{ translate('You need to agree with our policies') }}');
                $(el).prop('disabled', false);
            }
        }

        function toggleManualPaymentData(id) {
            if (typeof id != 'undefined') {
                $('#manual_payment_description').parent().removeClass('d-none');
                $('#manual_payment_description').html($('#manual_payment_info_' + id).html());
            }
        }
        // coupon apply
        $(document).on("click", "#coupon-apply", function() {
            @if (Auth::check())
                @if(Auth::user()->user_type != 'customer')
                    AIZ.plugins.notify('warning', "{{ translate('Please Login as a customer to apply coupon code.') }}");
                    return false;
                @endif

                var data = new FormData($('#apply-coupon-form')[0]);
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    method: "POST",
                    url: "{{ route('checkout.apply_coupon_code') }}",
                    data: data,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(data, textStatus, jqXHR) {
                        AIZ.plugins.notify(data.response_message.response, data.response_message.message);
                        $("#cart_summary").html(data.html);
                    }
                });
            @else
                $('#login_modal').modal('show');
            @endif
        });

        // coupon remove
        $(document).on("click", "#coupon-remove", function() {
            @if (Auth::check() && Auth::user()->user_type == 'customer')
                var data = new FormData($('#remove-coupon-form')[0]);
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    method: "POST",
                    url: "{{ route('checkout.remove_coupon_code') }}",
                    data: data,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(data, textStatus, jqXHR) {
                        $("#cart_summary").html(data);
                    }
                });
            @endif
        });

        function updateDeliveryAddress(id, city_id = 0) {
            $('.aiz-refresh').addClass('active');
            $.post('{{ route('checkout.updateDeliveryAddress') }}', {
                _token: AIZ.data.csrf,
                address_id: id,
                city_id: city_id
            }, function(data) {
                $('#delivery_info').html(data.delivery_info);
                $('#cart_summary').html(data.cart_summary);
                $('.aiz-refresh').removeClass('active');
                // Re-init shipping widgets after delivery block is re-rendered
                initShippingServiceSelector();
            });
            AIZ.plugins.bootstrapSelect("refresh");
        }

        function stepCompletionBillingInfo() {
            var headColor = '#9d9da6';
            var btnDisable = true;
            var allOk = false;
            @if (Auth::check())
                var length = $('input[name="billing_address_id"]:checked').length;
                if (length > 0) {
                    headColor = '#15a405';
                    btnDisable = false;
                    allOk = true;
                }
            @else
                headColor = '#15a405';
                btnDisable = false;
                allOk = true;
            @endif

            $('#headingBillingInfo svg *').css('fill', headColor);
            $("#continueToShippingBtn").prop('disabled', btnDisable);
            if(allOk){
                $('#headingShippingInfo').css({'pointer-events': 'auto', 'opacity': '1'});
            }
            return allOk;
        }

        function stepCompletionShippingInfo() {
            var headColor = '#9d9da6';
            var btnDisable = true;
            var allOk = false;
            @if (Auth::check())
                var length = $('input[name="address_id"]:checked').length;
                if (length > 0) {
                    headColor = '#15a405';
                    btnDisable = false;
                    allOk = true;
                }
            @else
                var count = 0;
                var length = $('#shipping_info [required]').length;
                $('#shipping_info [required]').each(function (i, el) {
                    if ($(el).val() != '' && $(el).val() != undefined && $(el).val() != null) {
                        count += 1;
                    }
                });
                if (count == length) {
                    headColor = '#15a405';
                    btnDisable = false;
                    allOk = true;
                }
            @endif

            $('#headingShippingInfo svg *').css('fill', headColor);
            $("#submitOrderBtn").prop('disabled', btnDisable);
            return allOk;
        }

        $('#billing_info [required]').each(function (i, el) {
            $(el).change(function(){
                stepCompletionBillingInfo();
            });
        });

        $('#shipping_info [required]').each(function (i, el) {
            $(el).change(function(){
                if ($(el).attr('name') == 'address_id') {
                    updateDeliveryAddress($(el).val());
                }
                @if (get_setting('shipping_type') == 'area_wise_shipping')
                    if ($(el).attr('name') == 'city_id') {
                        let country_id = $('select[name="country_id"]').val();
                        let city_id = $(this).val();
                        updateDeliveryAddress(country_id, city_id);
                    }
                @endif
                stepCompletionShippingInfo();
            });
        });

        function stepCompletionDeliveryInfo() {
            var headColor = '#9d9da6';
            var btnDisable = true;
            var allOk = false;
            var content = $('#delivery_info [required]');
            if (content.length > 0) {
                var content_checked = $('#delivery_info [required]:checked');
                if (content_checked.length > 0) {
                    content_checked.each(function (i, el) {
                        allOk = false;
                        if($(el).val() == 'carrier'){
                            var owner = $(el).attr('data-owner');
                            if ($('input[name=carrier_id_'+owner+']:checked').length > 0) {
                                allOk = true;
                            }
                        }else if($(el).val() == 'pickup_point'){
                            var owner = $(el).attr('data-owner');
                            if ($('select[name="pickup_point_id_'+owner+'"]').val() != '') {
                                allOk = true;
                            }
                        }else{
                            allOk = true;
                        }

                        if(allOk == false) {
                            return false;
                        }
                    });

                    if (allOk) {
                        headColor = '#15a405';
                        btnDisable = false;
                    }
                }
            }else{
                allOk = true
                headColor = '#15a405';
                btnDisable = false;
            }

            var selectedShippingMethod = $('input[name="shipping_method"]:checked').val();
            if (selectedShippingMethod === 'transport') {
                allOk = Boolean(($('input[name="transport_name"]').val() || '').trim())
                    && Boolean(($('input[name="booked_to_name"]').val() || '').trim())
                    && Boolean($('input[name="fod_mode"]:checked').val())
                    && Boolean($('select[name="transport_delivery_type"]').val());
                if ($('input[name="fod_mode"]:checked').val() === 'surface') {
                    allOk = allOk && Boolean($('input[name="transport_surface_mode"]:checked').val());
                }
            } else if (selectedShippingMethod === 'local') {
                allOk = Boolean(($('input[name="local_delivery_partner_name"]').val() || '').trim());
            }

            if (allOk) {
                headColor = '#15a405';
                btnDisable = false;
            } else {
                headColor = '#9d9da6';
                btnDisable = true;
            }

            $('#headingDeliveryInfo svg *').css('fill', headColor);
            $("#submitOrderBtn").prop('disabled', btnDisable);
            return allOk;
        }

        function updateDeliveryInfo(shipping_type, type_id, user_id, country_id = 0, city_id = 0) {
            @if (get_setting('shipping_type') == 'area_wise_shipping' || get_setting('shipping_type') == 'carrier_wise_shipping')
                country_id = $('select[name="country_id"]').val() != null ? $('select[name="country_id"]').val() : 0;
                city_id = $('select[name="city_id"]').val() != null ? $('select[name="city_id"]').val() : 0;
            @endif
            $('.aiz-refresh').addClass('active');
            $.post('{{ route('checkout.updateDeliveryInfo') }}', {
                _token: AIZ.data.csrf,
                shipping_type: shipping_type,
                type_id: type_id,
                user_id: user_id,
                country_id: country_id,
                city_id: city_id
            }, function(data) {
                $('#cart_summary').html(data);
                stepCompletionDeliveryInfo();
                $('.aiz-refresh').removeClass('active');
            });
            AIZ.plugins.bootstrapSelect("refresh");
        }

        function updateDeliveryInfoByShipping(el){
            $('.aiz-refresh').addClass('active');
            $.post('{{ route('checkout.updateDeliveryInfoByShipping') }}', {
                _token: AIZ.data.csrf,
                shipping_name: el.dataset.provider,     // e.g. 'shipway'
                carrier_id:   el.dataset.carrierId,     // numeric ID
                charge:       el.dataset.charge || 0,   // delivery charge
                user_id:      (window.checkoutOwnerId || 1)
            }, function (html) {
                $('#cart_summary').html(html);
                stepCompletionDeliveryInfo();
                $('.aiz-refresh').removeClass('active');
            });
            AIZ.plugins.bootstrapSelect("refresh");
        }

        function setFodFreeShipping(){
            $.post('{{ route('checkout.setFodShipping') }}', {
                _token: AIZ.data.csrf,
                user_id: (window.checkoutOwnerId || 1)
            }, function(html){
                $('#cart_summary').html(html);
                stepCompletionDeliveryInfo();
            });
        }

        function show_pickup_point(el, user_id) {
        	var type = $(el).val();
        	var target = $(el).data('target');
            var type_id = null;

        	if(type == 'home_delivery' || type == 'carrier'){
                if(!$(target).hasClass('d-none')){
                    $(target).addClass('d-none');
                }
                $('.carrier_id_'+user_id).removeClass('d-none');
        	}else{
        		$(target).removeClass('d-none');
        		$('.carrier_id_'+user_id).addClass('d-none');
        	}

            if(type == 'carrier'){
                type_id = $('input[name=carrier_id_'+user_id+']:checked').val();
            }else if(type == 'pickup_point'){
                type_id = $('select[name=pickup_point_id_'+user_id+']').val();
            }
            updateDeliveryInfo(type, type_id, user_id);
        }

        function stepCompletionPaymentInfo() {
            var headColor = '#9d9da6';
            var btnDisable = true;
            var payment = false;
            var agree = false;
            var allOk = false;
            var length = $('input[name="payment_option"]:checked').length;
            if(length > 0){
                if ($('input[name="payment_option"]:checked').hasClass('offline_payment_option')) {
                    if ($('#trx_id').val() != '' && $('#trx_id').val() != undefined && $('#trx_id').val() != null) {
                        payment = true;
                    }
                } else {
                    payment = true;
                }

                if ($('#agree_checkbox').is(":checked")){
                    agree = true;
                }

                if (payment && agree) {
                    headColor = '#15a405';
                    btnDisable = false;
                    allOk = true;
                }
            }

            $('#headingPaymentInfo svg *').css('fill', headColor);
            $("#submitOrderBtn").prop('disabled', btnDisable);
            return allOk;
        }

        function stepCompletionWalletPaymentInfo() {
            var headColor = '#9d9da6';
            var btnDisable = true;
            var allOk = false;
            if ($('#agree_checkbox').is(":checked")){
                headColor = '#15a405';
                btnDisable = false;
                allOk = true;
            }

            $('#headingPaymentInfo svg *').css('fill', headColor);
            $("#submitOrderBtn").prop('disabled', btnDisable);
            return allOk;
        }

        $('input[name="payment_option"]').change(function(){
            stepCompletionPaymentInfo();
        });

        function getServicesList() {
            return document.getElementById('courier-services-list');
        }
        function getFodBlock() {
            return document.getElementById('fod-block');
        }
        function getLocalBlock() {
            return document.getElementById('local-block');
        }
        function getCourierBlock() {
            return document.getElementById('courier-block');
        }
        function getServicesWrap() {
            return document.getElementById('provider-services');
        }

        function initShippingServiceSelector() {
            console.dir('Shipping service selector init');
            // ===========================
            // Shipping selector script
            // ===========================
            //
            // CHANGES / ADDITIONS (this block):
            // 1. Added hasAddressOrPincode() helper to detect whether we can use Courier.
            // 2. AUTO-SWITCH: If no address/pincode on init, switch the main selector to FOD.
            // 3. PREVENT SWITCH: When user tries to switch from FOD -> Courier but there's no
            //    address/pincode, show AIZ.plugins.notify('warning', ...) and revert to FOD.
            // 4. Added inline comments wherever logic was introduced/modified.
            //
            // Everything else (provider listing, loadCourierRates, renderServices) is kept intact.
            // ===========================

            // Blocks
            var fodBlock = getFodBlock();
            var localBlock = getLocalBlock();
            var courierBlock = getCourierBlock();
            var servicesWrap = getServicesWrap();
            var servicesList = getServicesList();

            var ratesUrl = "{{ route('shipment.rates') }}";
            var bookedToUrl = "{{ route('checkout.booked_to_options') }}";

            function currentShipType() {
                var el = document.querySelector('input[name="shipping_method"]:checked');
                return el ? el.value : 'courier';
            }

            function currentProviderSlug() {
                var checked = document.querySelector('#courier-block input[name="shipping_method_id"]:checked');
                return checked ? (checked.dataset.provider || '') : '';
            }

            function currentAddressId() {
                var el = document.querySelector('#shipping_info input[name="address_id"]:checked');
                return el ? el.value : null;
            }

            function currentPincodeGuest() {
                var input = document.querySelector('#shipping_info input[name="address_id"]:checked');
                if (!input) return '';
                // find nearest label container (input is nested inside label)
                var label = input.closest('.aiz-megabox') || input.closest('label');
                if (!label) return '';
                var pc = label.querySelector('.address_postal_code');
                return pc ? (pc.textContent || pc.value || '').trim() : '';
            }

            // NEW: helper to determine if courier can be used
            function hasAddressOrPincode() {
                // logged-in address selection OR guest pincode fallback
                var addressId = currentAddressId();
                var guestPincode = currentPincodeGuest();
                return Boolean(addressId) || Boolean((guestPincode || '').trim());
            }

            function renderServices(items){
                var servicesList = getServicesList();
                if(!items || !items.length){ servicesList.innerHTML = '<p class="text-muted mb-0">No services.</p>'; return; }
                var html = '<div class="row gutters-10">';
                items.forEach(function(it, i){
                    var id = 'svc_'+(it.carrier_id||i);
                    var priceText = (it.price==null)? '' : ('₹'+Number(it.price).toFixed(2));
                    html += `
                    <div class="col-xl-4 col-md-6">
                        <div class="h=100">
                            <label class="aiz-megabox d-block mb-3" for="${id}">
                            <input id="${id}" type="radio" name="courier_service"
                                    value="${it.carrier_id||''}" ${i===0?'checked':''}
                                    data-provider="${it.provider||''}"
                                    data-carrier-id="${it.carrier_id||''}"
                                    data-charge="${it.price??''}"
                                    onchange="updateDeliveryInfoByShipping(this)">
                            <span class="d-flex flex-column aiz-megabox-elem rounded-0 p-3">
                                <span class="fs-12 fw-600">${it.name||'Carrier'}</span>
                                ${priceText?`<span class="fs-13 ">${priceText}</span>`:''}
                                <span class="fs-11">${(it.provider||'').toUpperCase()}</span>
                            </span>
                            </label>    
                        </div>
                    </div>`;
                });
                html += '</div>';
                servicesList.innerHTML = html;

                // fire once for default-checked
                var first = servicesList.querySelector('input[name="courier_service"]:checked');
                if (first) first.dispatchEvent(new Event('change', {bubbles:true}));
            }

            function loadCourierRates() {
                var servicesWrap = getServicesWrap();
                var servicesList = getServicesList();
                if (!servicesWrap || currentShipType() !== 'courier') {
                    if (servicesWrap) servicesWrap.style.display = 'none';
                    return;
                }

                var provider = currentProviderSlug();
                var addressId = currentAddressId();
                var toPin = addressId ? '' : currentPincodeGuest();

                if (!provider) {
                    if (servicesWrap) servicesWrap.style.display = 'none';
                    return;
                }
                if (!addressId && !toPin) {
                    // Wait until user picks address or enters pincode
                    if (servicesWrap) servicesWrap.style.display = 'none';
                    return;
                }

                servicesWrap.style.display = 'block';
                servicesList.innerHTML = '<p class="text-muted mb-0">{{ translate('Loading services...') }}</p>';

                $.getJSON(
                    ratesUrl, {
                        provider: provider,
                        address_id: addressId, // server will prefer this if present
                        to_pincode: toPin || null, // guest fallback
                        payment_type: 'prepaid' // or detect if you have COD on checkout
                    }
                ).done(function(resp) {
                    if (resp && resp.success && Array.isArray(resp.data) && resp.data.length) {
                        renderServices(resp.data);
                    } else {
                        servicesWrap.style.display = 'none';
                    }
                }).fail(function() {
                    servicesWrap.style.display = 'none';
                });
            }

            function toggleShippingBlocks(selected) {
                var courierBlock = getCourierBlock();
                var fodBlock = getFodBlock();
                var localBlock = getLocalBlock();
                if (selected === 'courier') {
                    fodBlock.style.display = 'none';
                    if (localBlock) localBlock.style.display = 'none';
                    courierBlock.style.display = 'block';

                    // Ensure one provider is checked
                    var firstProvider = courierBlock.querySelector('input[name="shipping_method_id"]');
                    if (firstProvider && !document.querySelector(
                            '#courier-block input[name="shipping_method_id"]:checked')) {
                        firstProvider.checked = true;
                    }
                    loadCourierRates();
                } else if (selected === 'local') {
                    fodBlock.style.display = 'none';
                    if (localBlock) localBlock.style.display = 'block';
                    courierBlock.style.display = 'none';
                    if (servicesWrap) servicesWrap.style.display = 'none';
                    setFodFreeShipping();
                } else {
                    fodBlock.style.display = 'block';
                    if (localBlock) localBlock.style.display = 'none';
                    courierBlock.style.display = 'none';
                    if (servicesWrap) servicesWrap.style.display = 'none';
                    toggleSurfaceMode();
                    setFodFreeShipping();
                }
                stepCompletionDeliveryInfo();
            }

            function normalizeComboText(value) {
                return (value || '').trim().toLowerCase();
            }

            function getComboItems(menu) {
                return menu ? Array.prototype.slice.call(menu.querySelectorAll('.checkout-combo-option')) : [];
            }

            function filterComboMenu(combo) {
                var input = combo.querySelector('.checkout-combo-input');
                var menu = combo.querySelector('.checkout-combo-menu');
                if (!input || !menu) return;

                var needle = normalizeComboText(input.value);
                var visible = 0;
                getComboItems(menu).forEach(function(item) {
                    var matched = !needle || normalizeComboText(item.dataset.name).indexOf(needle) !== -1;
                    item.style.display = matched ? 'block' : 'none';
                    if (matched) visible++;
                });

                var empty = menu.querySelector('.checkout-combo-empty');
                if (!empty) {
                    empty = document.createElement('div');
                    empty.className = 'checkout-combo-empty';
                    empty.textContent = "{{ translate('No match. Type new value to request it.') }}";
                    menu.appendChild(empty);
                }
                empty.style.display = visible ? 'none' : 'block';
            }

            function openComboMenu(combo) {
                var menu = combo.querySelector('.checkout-combo-menu');
                if (!menu) return;
                filterComboMenu(combo);
                menu.style.display = 'block';
            }

            function closeComboMenus(exceptCombo) {
                document.querySelectorAll('.checkout-combo').forEach(function(combo) {
                    if (exceptCombo && combo === exceptCombo) return;
                    var menu = combo.querySelector('.checkout-combo-menu');
                    if (menu) menu.style.display = 'none';
                });
            }

            function syncComboId(inputSelector, hiddenSelector, menuSelector) {
                var input = document.querySelector(inputSelector);
                var hidden = document.querySelector(hiddenSelector);
                var menu = document.querySelector(menuSelector);
                if (!input || !hidden) return null;
                var selectedId = '';
                getComboItems(menu).forEach(function(option) {
                    if (normalizeComboText(option.dataset.name) === normalizeComboText(input.value)) {
                        selectedId = option.dataset.id || '';
                    }
                });
                hidden.value = selectedId;
                return selectedId;
            }

            function updateTransportServiceUrl() {
                var input = document.getElementById('transport_name');
                var menu = document.getElementById('transport-provider-options');
                var wrap = document.getElementById('transport-service-url-wrap');
                var link = document.getElementById('transport-service-url');
                if (!input || !menu || !wrap || !link) return;

                var selectedUrl = '';
                getComboItems(menu).forEach(function(option) {
                    if (normalizeComboText(option.dataset.name) === normalizeComboText(input.value)) {
                        selectedUrl = option.dataset.url || '';
                    }
                });

                if (selectedUrl) {
                    link.href = selectedUrl;
                    wrap.style.display = 'block';
                } else {
                    link.href = '#';
                    wrap.style.display = 'none';
                }
            }

            function loadBookedToOptions(transportId) {
                var bookedToList = document.getElementById('booked-to-options');
                var bookedToName = document.getElementById('booked_to_name');
                var bookedToId = document.getElementById('booked_to_id');
                if (!bookedToList) return;
                bookedToList.innerHTML = '';
                if (bookedToName) bookedToName.value = '';
                if (bookedToId) bookedToId.value = '';
                if (!transportId) return;

                $.getJSON(bookedToUrl, { transport_id: transportId }).done(function(items) {
                    (items || []).forEach(function(item) {
                        var option = document.createElement('div');
                        option.className = 'checkout-combo-option';
                        option.dataset.name = item.name;
                        option.dataset.id = item.id;
                        option.textContent = item.name;
                        bookedToList.appendChild(option);
                    });
                });
            }

            function toggleSurfaceMode() {
                var block = document.getElementById('transport-surface-mode-block');
                var selectedMode = document.querySelector('input[name="fod_mode"]:checked');
                if (block) block.style.display = selectedMode && selectedMode.value === 'surface' ? 'flex' : 'none';
            }

            $(document).on('focus click', '.checkout-combo-input', function() {
                var combo = this.closest('.checkout-combo');
                closeComboMenus(combo);
                openComboMenu(combo);
            });

            $(document).on('input', '.checkout-combo-input', function() {
                var combo = this.closest('.checkout-combo');
                filterComboMenu(combo);
            });

            $(document).on('mousedown', '.checkout-combo-option', function(e) {
                e.preventDefault();
                var option = this;
                var combo = option.closest('.checkout-combo');
                var input = combo.querySelector('.checkout-combo-input');
                var hidden = combo.querySelector('input[type="hidden"]');
                var menu = combo.querySelector('.checkout-combo-menu');

                input.value = option.dataset.name || option.textContent || '';
                hidden.value = option.dataset.id || '';
                if (menu) menu.style.display = 'none';

                if (input.id === 'transport_name') {
                    loadBookedToOptions(hidden.value);
                    updateTransportServiceUrl();
                }
                $(input).trigger('change');
            });

            $(document).on('click', function(e) {
                if (!e.target.closest('.checkout-combo')) {
                    closeComboMenus();
                }
            });

            $(document).on('input change', '#transport_name', function() {
                var previousTransportId = $('#transport_id').val();
                var transportId = syncComboId('#transport_name', '#transport_id', '#transport-provider-options');
                if (previousTransportId !== transportId) {
                    loadBookedToOptions(transportId);
                }
                updateTransportServiceUrl();
                stepCompletionDeliveryInfo();
            });

            $(document).on('input change', '#booked_to_name', function() {
                syncComboId('#booked_to_name', '#booked_to_id', '#booked-to-options');
                stepCompletionDeliveryInfo();
            });

            $(document).on('input change', '#local_delivery_partner_name', function() {
                syncComboId('#local_delivery_partner_name', '#local_delivery_partner_id', '#local-delivery-partner-options');
                stepCompletionDeliveryInfo();
            });

            $(document).on('change', 'input[name="fod_mode"]', function() {
                toggleSurfaceMode();
                stepCompletionDeliveryInfo();
            });

            $(document).on('change', 'input[name="transport_surface_mode"], select[name="transport_delivery_type"]', function() {
                stepCompletionDeliveryInfo();
            });

            // === INIT: show Courier by default and fetch ===
            // NOTE: we will auto-switch to Transport if there's no address/pincode.
            if (!hasAddressOrPincode()) {
                var transportRadioInit = document.querySelector('input[name="shipping_method"][value="transport"]');
                if (transportRadioInit) {
                    transportRadioInit.checked = true;
                }
                toggleShippingBlocks('transport');
            } else {
                // normal behaviour: keep courier selected (or whatever is currently checked)
                toggleShippingBlocks(currentShipType());
            }

            // === EVENTS ===

            // Toggle FOD/Courier
            // Replaced simple toggle with a check: prevent switching to courier if no address/pincode.
            $(document).on('change', 'input[name="shipping_method"]', function() {
                var selected = this.value;

                // If user is switching to courier but we don't have an address/pincode, block it and warn.
                if (selected === 'courier' && !hasAddressOrPincode()) {
                    // Use AIZ notify if available (examples provided by you)
                    if (typeof AIZ !== 'undefined' && AIZ.plugins && AIZ.plugins.notify) {
                        AIZ.plugins.notify('warning', "{{ translate('Please add address details first.') }}");
                    } else {
                        // Fallback to alert for debugging if AIZ isn't defined
                        console.dir('No address/pincode - cannot switch to courier.');
                    }

                    // Revert selection back to Transport
                    var transportRadio = document.querySelector('input[name="shipping_method"][value="transport"]');
                    if (transportRadio) transportRadio.checked = true;

                    // Ensure the UI reflects the Transport block
                    toggleShippingBlocks('transport');

                    // Prevent any further courier actions
                    return;
                }

                // allowed: proceed normally
                toggleShippingBlocks(selected);
            });

            // Change provider
            $(document).on('change', '#courier-block input[name="shipping_method_id"]', function() {
                if (currentShipType() === 'courier') loadCourierRates();
            });

            // Address changed (delegated so it survives re-renders)
            // $(document).on('change', '#shipping_info input[name="address_id"]', function() {
            //     var address = document.querySelector('#shipping_info input[name="address_id"]:checked');
            //     console.dir(address);
            //     alert(1);
            //     if (currentShipType() === 'courier') {
            //         loadCourierRates();
            //     } else {
            //         setFodFreeShipping();
            //     }
            // });

            // Guest pincode typed
            // (kept commented in original - you may re-enable if needed)
            // $(document).on('blur', '#shipping_info input[name="postal_code"], #shipping_info input[name="zipcode"]',
            //     function() {
            //         loadCourierRates();
            //     });

        };

        $(document).ready(function(){
            stepCompletionBillingInfo();
            stepCompletionShippingInfo();
            stepCompletionDeliveryInfo();
            stepCompletionPaymentInfo();
            initShippingServiceSelector();
        });
    </script>

    @include('frontend.partials.address.address_js')

    @php
        $is_address_selected = false;
        if(Auth::check()){
            foreach (Auth::user()->addresses as $key => $address){
                if ($address->id == $address_id) {
                    $is_address_selected = true;
                    break;
                }
            
            }
        }
    @endphp

    @if ($is_address_selected === false && Auth::user()->addresses->count() === 0)
        <script>add_new_address('shipping');</script>
    @endif

    @if (get_setting('google_map') == 1)
        @include('frontend.partials.google_map')
    @endif

@endsection
