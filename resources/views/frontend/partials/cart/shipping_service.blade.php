@if (isset($shipping_methods) && $shipping_methods->count())
    <style>
        .aiz-megabox .aiz-megabox-elem:hover {
            background-color: #2b56a1 !important;
        }

        .aiz-megabox>input:checked~.aiz-megabox-elem {
            border-color: #2b56a1 !important;
        }
    </style>

    <div class="mb-4" id="shipping-root" data-order-id="{{ $order->id ?? '' }}">
        <h3 class="fs-16 fw-700 text-dark">{{ translate('Select Shipping Type') }}</h3>

        {{-- main selector: FOD or Courier (Courier default) --}}
        <div class="row gutters-10 mb-3">
            <div class="col-md-3 col-6">
                <label class="aiz-megabox d-block mb-2">
                    <input type="radio" name="shipping_type" value="fod">
                    <span class="d-flex align-items-center justify-content-between aiz-megabox-elem rounded-0 p-3">
                        <span class="d-block fw-400 fs-14">{{ translate('FOD') }}</span>
                    </span>
                </label>
            </div>
            <div class="col-md-3 col-6">
                <label class="aiz-megabox d-block mb-2">
                    <input type="radio" name="shipping_type" value="courier" checked>
                    <span class="d-flex align-items-center justify-content-between aiz-megabox-elem rounded-0 p-3">
                        <span class="d-block fw-400 fs-14">{{ translate('Courier') }}</span>
                    </span>
                </label>
            </div>
        </div>

        {{-- FOD modes (hidden initially because Courier is default) --}}
        <div id="fod-block" style="display:none;">
            <h4 class="fs-15 fw-600 mb-2">{{ translate('Select FOD Mode') }}</h4>
            <div class="row gutters-10">
                @php $fodModes = ['air'=>'Air','sea'=>'Sea','surface'=>'Surface']; @endphp
                @foreach ($fodModes as $key => $label)
                    <div class="col-md-4 col-sm-6">
                        <label class="aiz-megabox d-block mb-3">
                            <input type="radio" name="fod_mode" value="{{ $key }}"
                                {{ $loop->first ? 'checked' : '' }}>
                            <span
                                class="d-flex align-items-center justify-content-between aiz-megabox-elem rounded-0 p-3">
                                <span class="d-block fw-400 fs-14">{{ translate($label) }}</span>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Courier providers from $shipping_methods (visible initially) --}}
        <div id="courier-block">
            <h4 class="fs-15 fw-600 mb-2">{{ translate('Select a shipping provider') }}</h4>

            <div class="row gutters-10" id="courier-provider-list">
                @foreach ($shipping_methods as $method)
                    <div class="col-xl-4 col-md-6">
                        <label class="aiz-megabox d-block mb-3">
                            <input type="radio" name="shipping_method_id" value="{{ $method->id }}"
                                data-provider="{{ $method->slug }}" {{ $loop->first ? 'checked' : '' }}>
                            <span
                                class="d-flex align-items-center justify-content-between aiz-megabox-elem rounded-0 p-3">
                                <span class="d-block fw-400 fs-14">{{ $method->name }}</span>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>

            {{-- Appended: Available Courier Services (name + charges) --}}
            <div id="provider-services" class="mt-2" style="display:none;">
                <h5 class="mb-2">{{ translate('Available Courier Services') }}</h5>
                <div id="courier-services-list"></div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                // Blocks
                var fodBlock = document.getElementById('fod-block');
                var courierBlock = document.getElementById('courier-block');
                var servicesWrap = document.getElementById('provider-services');
                var servicesList = document.getElementById('courier-services-list');

                var ratesUrl = "{{ route('shipment.rates') }}";

                function currentShipType() {
                    var el = document.querySelector('input[name="shipping_type"]:checked');
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

                function renderServices(items){
                    if(!items || !items.length){ servicesList.innerHTML = '<p class="text-muted mb-0">No services.</p>'; return; }
                    var html = '<div class="row gutters-10">';
                    items.forEach(function(it, i){
                        var id = 'svc_'+(it.carrier_id||i);
                        var priceText = (it.price==null)? '' : ('₹'+Number(it.price).toFixed(2));
                        html += `
                        <div class="col-xl-4 col-md-6">
                            <label class="aiz-megabox d-block mb-3" for="${id}">
                            <input id="${id}" type="radio" name="courier_service"
                                    value="${it.carrier_id||''}" ${i===0?'checked':''}
                                    data-provider="${it.provider||''}"
                                    data-carrier-id="${it.carrier_id||''}"
                                    data-charge="${it.price??''}"
                                    onchange="updateDeliveryInfoByShipping(this)">
                            <span class="d-flex flex-column aiz-megabox-elem rounded-0 p-3">
                                <span class="fw-600">${it.name||'Carrier'}</span>
                                ${priceText?`<span class="fs-13 text-muted">${priceText}</span>`:''}
                                <span class="fs-11 text-muted">${(it.provider||'').toUpperCase()}</span>
                            </span>
                            </label>
                        </div>`;
                    });
                    html += '</div>';
                    servicesList.innerHTML = html;

                    // fire once for default-checked
                    var first = servicesList.querySelector('input[name="courier_service"]:checked');
                    if (first) first.dispatchEvent(new Event('change', {bubbles:true}));
                }

                // function renderServices(items) {
                //     if (!servicesList) return;

                //     if (!items || !items.length) {
                //         servicesList.innerHTML = '<p class="text-muted mb-0">{{ translate('No services available for this provider.') }}</p>';
                //         return;
                //     }

                //     var html = '<div class="row gutters-10">';
                //     items.forEach(function(it, idx){
                //         var priceText = (it.price == null) ? '' : ('₹' + Number(it.price).toFixed(2));
                //         var checked   = idx === 0 ? 'checked' : '';

                //         html += ''
                //         + '<div class="col-xl-4 col-md-6">'
                //         + '  <label class="aiz-megabox d-block mb-3">'
                //         + '    <input type="radio" name="courier_service"'
                //         + '           value="'+ (it.carrier_id || '') +'" '+ checked
                //         + '           data-provider="'+ (it.provider || '') +'"'
                //         + '           data-charge="'+ (it.price ?? '') +'"'
                //         + '           onchange="updateDeliveryInfoByShipping(this.dataset.provider, this.value, (window.checkoutOwnerId||1), 0, 0, this.dataset.charge)">'
                //         + '    <span class="d-flex flex-column aiz-megabox-elem rounded-0 p-3">'
                //         + '      <span class="fw-600">'+ (it.name || 'Carrier') +'</span>'
                //         +        (priceText ? '<span class="fs-13 text-muted">'+ priceText +'</span>' : '')
                //         +        (it.eta ? '<span class="fs-12 text-secondary">'+ it.eta +'</span>' : '')
                //         + '      <span class="fs-11 text-muted">'+ ((it.provider||'').toUpperCase()) +'</span>'
                //         + '    </span>'
                //         + '  </label>'
                //         + '</div>';
                //     });
                //     html += '</div>';
                //     servicesList.innerHTML = html;

                //     // Trigger once for the default-checked service
                //     var first = servicesList.querySelector('input[name="courier_service"]:checked');
                //     if (first) first.dispatchEvent(new Event('change', {bubbles:true}));
                // }
                
                function loadCourierRates() {
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
                    if (selected === 'courier') {
                        fodBlock.style.display = 'none';
                        courierBlock.style.display = 'block';

                        // Ensure one provider is checked
                        var firstProvider = courierBlock.querySelector('input[name="shipping_method_id"]');
                        if (firstProvider && !document.querySelector(
                                '#courier-block input[name="shipping_method_id"]:checked')) {
                            firstProvider.checked = true;
                        }
                        loadCourierRates();
                    } else {
                        fodBlock.style.display = 'block';
                        courierBlock.style.display = 'none';
                        if (servicesWrap) servicesWrap.style.display = 'none';
                        setFodFreeShipping();
                    }
                }

                // === INIT: show Courier by default and fetch ===
                toggleShippingBlocks('courier');

                // === EVENTS ===
                // Toggle FOD/Courier
                $(document).on('change', 'input[name="shipping_type"]', function() {
                    toggleShippingBlocks(this.value);
                });

                // Change provider
                $(document).on('change', '#courier-block input[name="shipping_method_id"]', function() {
                    if (currentShipType() === 'courier') loadCourierRates();
                });

                // Address changed (logged in)
                $(document).on('change', '#shipping_info input[name="address_id"]', function() {
                    // your existing updateDeliveryAddress will re-render blocks; re-load once DOM updates
                    setTimeout(loadCourierRates, 300);
                });

                document.querySelectorAll('#shipping_info input[name="address_id"]').forEach(function(radio) {
                    radio.addEventListener('change', function() {
                        loadCourierRates();
                    });
                });
                
                // Guest pincode typed
                // $(document).on('blur', '#shipping_info input[name="postal_code"], #shipping_info input[name="zipcode"]',
                //     function() {
                //         loadCourierRates();
                //     });


            })();
        </script>
    @endpush
@endif
