@if (isset($shipping_methods) && $shipping_methods->count())

    <div class="col-6 mb-4" id="shipping-root" data-order-id="{{ $order->id ?? '' }}">
        <h3 class="fs-16 fw-700 text-dark">{{ translate('Select Shipping Type') }}</h3>

        {{-- main selector: FOD or Courier (Courier default) --}}
        <div class="row gutters-10 mb-3">
            <div class="col-md-3 col-6">
                <label class="aiz-megabox d-block mb-2">
                    <input type="radio" name="shipping_method" value="fod">
                    <span class="d-flex align-items-center justify-content-between aiz-megabox-elem rounded-0 p-3">
                        <span class="d-block fw-400 fs-14">{{ translate('FOD') }}</span>
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

@endif
