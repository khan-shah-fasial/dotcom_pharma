@php
    $transportProviders = \App\Models\Transport::active()->orderBy('name')->get();
    $localDeliveryPartners = \App\Models\LocalDeliveryPartner::active()->orderBy('name')->get();
@endphp

<style>
    .checkout-combo {
        position: relative;
    }
    .checkout-combo-menu {
        background: #fff;
        border: 1px solid #e5e5e5;
        box-shadow: 0 8px 20px rgba(0, 0, 0, .08);
        display: none;
        left: 0;
        max-height: 220px;
        overflow-y: auto;
        position: absolute;
        right: 0;
        top: 100%;
        z-index: 1060;
    }
    .checkout-combo-option {
        cursor: pointer;
        padding: 9px 12px;
    }
    .checkout-combo-option:hover {
        background: #f3f6fb;
    }
    .checkout-combo-empty {
        color: #8a8a8a;
        padding: 9px 12px;
    }
</style>

    <div class="col-6 mb-4" id="shipping-root" data-order-id="{{ $order->id ?? '' }}">
        <h3 class="fs-16 fw-700 text-dark">{{ translate('Select Shipping Type') }}</h3>

        {{-- main selector: Transport, Courier, or Local (Courier default) --}}
        <div class="row gutters-10 mb-3">
            <div class="col-md-3 col-6">
                <label class="aiz-megabox d-block mb-2">
                    <input type="radio" name="shipping_method" value="transport">
                    <span class="d-flex align-items-center justify-content-between aiz-megabox-elem rounded-0 p-3">
                        <span class="d-block fw-400 fs-14">{{ translate('Transport') }}</span>
                    </span>
                </label>
            </div>
            <div class="col-md-3 col-6">
                <label class="aiz-megabox d-block mb-2">
                    <input type="radio" name="shipping_method" value="courier" checked>
                    <span class="d-flex align-items-center justify-content-between aiz-megabox-elem rounded-0 p-3">
                        <span class="d-block fw-400 fs-14">{{ translate('Courier') }}</span>
                    </span>
                </label>
            </div>
            <div class="col-md-3 col-6">
                <label class="aiz-megabox d-block mb-2">
                    <input type="radio" name="shipping_method" value="local">
                    <span class="d-flex align-items-center justify-content-between aiz-megabox-elem rounded-0 p-3">
                        <span class="d-block fw-400 fs-14">{{ translate('Local') }}</span>
                    </span>
                </label>
            </div>
        </div>

        {{-- Transport fields (hidden initially because Courier is default) --}}
        <div id="fod-block" style="display:none;">
            <div class="form-group">
                <h4 class="fs-15 fw-600 mb-2">{{ translate('Transport Providers') }}</h4>
                <div class="checkout-combo" data-combo="transport">
                    <input type="hidden" name="transport_id" id="transport_id">
                    <input type="text" class="form-control rounded-0 checkout-combo-input" name="transport_name" id="transport_name"
                        autocomplete="off" placeholder="{{ translate('Select or enter transport provider') }}">
                    <div id="transport-service-url-wrap" class="mt-2" style="display:none;">
                        <a href="#" id="transport-service-url" target="_blank" rel="noopener" class="fs-13">
                            {{ translate('Check transport availability for your pincode') }}
                        </a>
                    </div>
                    <div class="checkout-combo-menu" id="transport-provider-options">
                        @foreach ($transportProviders as $transport)
                            <div class="checkout-combo-option" data-id="{{ $transport->id }}" data-name="{{ $transport->name }}" data-url="{{ $transport->url }}">{{ $transport->name }}</div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="form-group">
                <h4 class="fs-15 fw-600 mb-2">{{ translate('Booked To') }}</h4>
                <div class="checkout-combo" data-combo="booked-to">
                    <input type="hidden" name="booked_to_id" id="booked_to_id">
                    <input type="text" class="form-control rounded-0 checkout-combo-input" name="booked_to_name" id="booked_to_name"
                        autocomplete="off" placeholder="{{ translate('Select or enter booked to') }}">
                    <div class="checkout-combo-menu" id="booked-to-options"></div>
                </div>
            </div>

            <h4 class="fs-15 fw-600 mb-2">{{ translate('Select Transport Mode') }}</h4>
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

            <div class="row gutters-10 mb-3" id="transport-surface-mode-block" style="display:none;">
                @foreach (['road' => 'Road', 'train' => 'Train'] as $key => $label)
                    <div class="col-md-4 col-sm-6">
                        <label class="aiz-megabox d-block mb-3">
                            <input type="radio" name="transport_surface_mode" value="{{ $key }}" {{ $loop->first ? 'checked' : '' }}>
                            <span class="d-flex align-items-center justify-content-between aiz-megabox-elem rounded-0 p-3">
                                <span class="d-block fw-400 fs-14">{{ translate($label) }}</span>
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>

            <div class="form-group">
                <h4 class="fs-15 fw-600 mb-2">{{ translate('Delivery Type') }}</h4>
                <select name="transport_delivery_type" class="form-control aiz-selectpicker">
                    <option value="door_delivery">{{ translate('Door Delivery') }}</option>
                    <option value="transport_godown">{{ translate('Take from Transport Godown') }}</option>
                </select>
            </div>
        </div>

        <div id="local-block" style="display:none;">
            <h4 class="fs-15 fw-600 mb-2">{{ translate('Local Delivery Partner') }}</h4>
            <div class="checkout-combo" data-combo="local">
                <input type="hidden" name="local_delivery_partner_id" id="local_delivery_partner_id">
                <input type="text" class="form-control rounded-0 checkout-combo-input" name="local_delivery_partner_name" id="local_delivery_partner_name"
                    autocomplete="off" placeholder="{{ translate('Select or enter local delivery partner') }}">
                <div class="checkout-combo-menu" id="local-delivery-partner-options">
                    @foreach ($localDeliveryPartners as $partner)
                        <div class="checkout-combo-option" data-id="{{ $partner->id }}" data-name="{{ $partner->name }}">{{ $partner->name }}</div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Courier providers from $shipping_methods (visible initially) --}}
        <div id="courier-block">
            <h4 class="fs-15 fw-600 mb-2">{{ translate('Select a shipping provider') }}</h4>

            <div class="row gutters-10" id="courier-provider-list">
                @foreach ($shipping_methods ?? collect() as $method)
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

