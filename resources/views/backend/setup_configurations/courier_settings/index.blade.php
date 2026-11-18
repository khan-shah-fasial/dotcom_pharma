@extends('backend.layouts.app')

@php
    $field = function (string $type, string $label, string $help = '', string $inputType = 'text') {
        return [
            'type' => $type,
            'label' => $label,
            'help' => $help,
            'input' => $inputType,
        ];
    };

    $sections = [
        'bluedart' => [
            'title' => 'Blue Dart',
            'fields' => [
                $field('bluedart_origin_pincode', 'Origin pincode', 'Pickup warehouse pincode.'),
                $field('bluedart_origin_area', 'Origin area code', 'Area code assigned by Blue Dart (e.g. BOM).'),
                $field('bluedart_sender', 'Sender name'),
                $field('bluedart_shipper_name', 'Shipper / company name'),
                $field('bluedart_shipper_address', 'Shipper address', 'Split into 3 lines of 30 characters for the waybill.'),
                $field('bluedart_shipper_mobile', 'Shipper mobile'),
                $field('bluedart_product_code', 'Product code', 'From your Blue Dart contract, e.g. D (Domestic Priority) or A (Apex).'),
                $field('bluedart_sub_product_code', 'Prepaid sub product code', 'Usually P. COD orders always use C.'),
                $field('bluedart_default_rate', 'Flat rate shown at checkout (Rs.)', 'Blue Dart has no rate API. Leave blank to show no price.', 'number'),
            ],
        ],
        'dhl' => [
            'title' => 'DHL Express',
            'fields' => [
                $field('dhl_origin_postal_code', 'Origin postal code'),
                $field('dhl_origin_city', 'Origin city'),
                $field('dhl_origin_address', 'Origin address'),
                $field('dhl_shipper_name', 'Shipper / company name'),
                $field('dhl_shipper_phone', 'Shipper phone'),
                $field('dhl_shipper_email', 'Shipper email', '', 'email'),
                $field('dhl_default_product_code', 'Default product code', 'P = Express Worldwide (international).'),
                $field('dhl_currency', 'Currency', 'Defaults to INR.'),
            ],
        ],
    ];

    $delhiveryFields = [
        $field('delhivery_origin_pincode', 'Origin pincode', 'Used for rate lookups.'),
        $field('delhivery_client_name', 'Client name', 'Exactly as shared by Delhivery with your API token.'),
        $field('delhivery_seller_name', 'Seller name'),
        $field('delhivery_seller_address', 'Seller address'),
        $field('delhivery_seller_gst_tin', 'Seller GSTIN'),
        $field('delhivery_client_gst_tin', 'Client GSTIN'),
        $field('delhivery_default_hsn_code', 'Default HSN code', 'Medicines are usually 3004.'),
        $field('delhivery_default_charge', 'Fallback charge (Rs.)', 'Shown when the rate API returns nothing.', 'number'),
    ];

    $pickupLocations = json_decode((string) get_setting('delhivery_pickup_locations'), true);
    if (is_array($pickupLocations) && isset($pickupLocations['name'])) {
        $pickupLocations = [$pickupLocations];
    }
    if (!is_array($pickupLocations) || empty($pickupLocations)) {
        $pickupLocations = [['id' => 1, 'is_active' => 1]];
    }
@endphp

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <h1 class="h3">{{ translate('Courier APIs') }}</h1>
    <p class="text-muted mb-0">
        {{ translate('API keys and tokens are read from the server .env file only. This page holds shipper, pickup and booking details.') }}
    </p>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Credentials and booking') }}</h5>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap mb-3" style="gap: 8px;">
            @foreach (['delhivery' => 'Delhivery', 'bluedart' => 'Blue Dart', 'dhl' => 'DHL'] as $slug => $name)
                <span class="badge badge-inline {{ $credentialStatus[$slug] ? 'badge-success' : 'badge-danger' }}">
                    {{ $name }}: {{ $credentialStatus[$slug] ? translate('API credentials set') : translate('API credentials missing in .env') }}
                </span>
            @endforeach
        </div>

        <form action="{{ route('business_settings.update') }}" method="POST">
            @csrf
            <input type="hidden" name="types[]" value="courier_auto_create_shipment">
            <div class="form-group row mb-2">
                <label class="col-md-4 col-form-label">{{ translate('Create AWB automatically at checkout') }}</label>
                <div class="col-md-8">
                    <select name="courier_auto_create_shipment" class="form-control aiz-selectpicker">
                        <option value="1" @selected(get_setting('courier_auto_create_shipment') !== '0')>{{ translate('Yes, for paid, COD and credit orders') }}</option>
                        <option value="0" @selected(get_setting('courier_auto_create_shipment') === '0')>{{ translate('No, admin creates shipments from the order page') }}</option>
                    </select>
                </div>
            </div>
            <div class="text-right">
                <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Delhivery') }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('business_settings.update') }}" method="POST">
            @csrf
            @foreach ($delhiveryFields as $f)
                <input type="hidden" name="types[]" value="{{ $f['type'] }}">
                <div class="form-group row">
                    <label class="col-md-4 col-form-label">{{ translate($f['label']) }}</label>
                    <div class="col-md-8">
                        <input type="{{ $f['input'] }}" @if($f['input'] === 'number') step="0.01" min="0" @endif
                               name="{{ $f['type'] }}" value="{{ get_setting($f['type']) }}" class="form-control">
                        @if ($f['help'])<small class="text-muted">{{ translate($f['help']) }}</small>@endif
                    </div>
                </div>
            @endforeach

            <input type="hidden" name="types[]" value="delhivery_shipment_payment_mode">
            <div class="form-group row">
                <label class="col-md-4 col-form-label">{{ translate('Shipment payment mode') }}</label>
                <div class="col-md-8">
                    @php $mode = get_setting('delhivery_shipment_payment_mode') ?: 'match-order'; @endphp
                    <select name="delhivery_shipment_payment_mode" class="form-control aiz-selectpicker">
                        <option value="match-order" @selected($mode === 'match-order')>{{ translate('Match order (COD orders booked as COD)') }}</option>
                        <option value="Prepaid" @selected($mode === 'Prepaid')>{{ translate('Always prepaid') }}</option>
                    </select>
                </div>
            </div>

            <input type="hidden" name="types[]" value="delhivery_shipping_mode">
            <div class="form-group row">
                <label class="col-md-4 col-form-label">{{ translate('Shipping mode') }}</label>
                <div class="col-md-8">
                    @php $shipMode = get_setting('delhivery_shipping_mode') ?: 'Express'; @endphp
                    <select name="delhivery_shipping_mode" class="form-control aiz-selectpicker">
                        <option value="Express" @selected($shipMode === 'Express')>{{ translate('Express') }}</option>
                        <option value="Surface" @selected($shipMode === 'Surface')>{{ translate('Surface') }}</option>
                    </select>
                </div>
            </div>

            <h6 class="fs-14 fw-700 mt-4">{{ translate('Pickup location') }}</h6>
            <p class="text-muted fs-12">{{ translate('The name must match the warehouse registered in Delhivery One exactly.') }}</p>
            <input type="hidden" name="types[]" value="delhivery_pickup_locations">
            @foreach ($pickupLocations as $i => $loc)
                <div class="border rounded p-3 mb-3">
                    <input type="hidden" name="delhivery_pickup_locations[{{ $i }}][id]" value="{{ $loc['id'] ?? $i + 1 }}">
                    <input type="hidden" name="delhivery_pickup_locations[{{ $i }}][created_at]" value="{{ $loc['created_at'] ?? now()->toDateTimeString() }}">
                    <input type="hidden" name="delhivery_pickup_locations[{{ $i }}][country]" value="{{ $loc['country'] ?? 'India' }}">
                    @foreach (['name' => 'Warehouse name', 'add' => 'Address', 'city' => 'City', 'pin_code' => 'Pincode', 'phone' => 'Phone'] as $key => $label)
                        <div class="form-group row mb-2">
                            <label class="col-md-4 col-form-label">{{ translate($label) }}</label>
                            <div class="col-md-8">
                                <input type="text" name="delhivery_pickup_locations[{{ $i }}][{{ $key }}]" value="{{ $loc[$key] ?? '' }}" class="form-control">
                            </div>
                        </div>
                    @endforeach
                    <div class="form-group row mb-0">
                        <label class="col-md-4 col-form-label">{{ translate('Active') }}</label>
                        <div class="col-md-8">
                            @php $active = $loc['is_active'] ?? 1; @endphp
                            <select name="delhivery_pickup_locations[{{ $i }}][is_active]" class="form-control">
                                <option value="1" @selected((string) $active === '1')>{{ translate('Yes') }}</option>
                                <option value="0" @selected((string) $active === '0')>{{ translate('No') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="text-right">
                <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save Delhivery') }}</button>
            </div>
        </form>

        <h6 class="fs-14 fw-700 mt-4">{{ translate('Request a pickup') }}</h6>
        <p class="text-muted fs-12">{{ translate('Books a Delhivery pickup at the active pickup location for AWBs already created.') }}</p>
        <form action="{{ route('courier_shipment.pickup', 'delhivery') }}" method="POST" class="form-row align-items-end">
            @csrf
            <div class="col-md-4 form-group">
                <label class="fs-12">{{ translate('Date') }}</label>
                <input type="date" name="pickup_date" class="form-control" value="{{ old('pickup_date', now()->addDay()->toDateString()) }}" min="{{ now()->toDateString() }}" required>
            </div>
            <div class="col-md-3 form-group">
                <label class="fs-12">{{ translate('Time') }}</label>
                <input type="time" name="pickup_time" class="form-control" value="{{ old('pickup_time', '14:00') }}" required>
            </div>
            <div class="col-md-3 form-group">
                <label class="fs-12">{{ translate('Packages') }}</label>
                <input type="number" name="package_count" class="form-control" value="{{ old('package_count', 1) }}" min="1" max="500" required>
            </div>
            <div class="col-md-2 form-group text-right">
                <button type="submit" class="btn btn-sm btn-soft-primary">{{ translate('Book pickup') }}</button>
            </div>
        </form>
    </div>
</div>

@foreach ($sections as $slug => $section)
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate($section['title']) }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('business_settings.update') }}" method="POST">
                @csrf
                @foreach ($section['fields'] as $f)
                    <input type="hidden" name="types[]" value="{{ $f['type'] }}">
                    <div class="form-group row">
                        <label class="col-md-4 col-form-label">{{ translate($f['label']) }}</label>
                        <div class="col-md-8">
                            <input type="{{ $f['input'] }}" @if($f['input'] === 'number') step="0.01" min="0" @endif
                                   name="{{ $f['type'] }}" value="{{ get_setting($f['type']) }}" class="form-control">
                            @if ($f['help'])<small class="text-muted">{{ translate($f['help']) }}</small>@endif
                        </div>
                    </div>
                @endforeach
                <div class="text-right">
                    <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }} {{ $section['title'] }}</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection
