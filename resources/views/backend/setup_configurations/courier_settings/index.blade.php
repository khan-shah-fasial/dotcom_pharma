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
                $field('bluedart_default_rate', 'Fallback flat rate (Rs.)', 'Used only when the rate card below has no price for a zone. Leave blank to hide Blue Dart instead.', 'number'),
            ],
        ],
        'dhl' => [
            'title' => 'DHL Express (international only)',
            'fields' => [
                $field('dhl_origin_postal_code', 'Origin postal code'),
                $field('dhl_origin_city', 'Origin city'),
                $field('dhl_origin_address', 'Origin address'),
                $field('dhl_shipper_name', 'Shipper / company name'),
                $field('dhl_shipper_phone', 'Shipper phone'),
                $field('dhl_shipper_email', 'Shipper email', '', 'email'),
                $field('dhl_shipper_gstin', 'Exporter GSTIN'),
                $field('dhl_shipper_iec', 'Exporter IEC', 'Import Export Code. DHL needs it on every export shipment.'),
                $field('dhl_incoterm', 'Incoterm', 'DAP (receiver pays duties) by default. DDP = you pay duties.'),
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
        <div class="table-responsive mb-3">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Courier') }}</th>
                        <th>{{ translate('Environment') }}</th>
                        <th>{{ translate('Keys in .env') }}</th>
                        <th>{{ translate('Connection') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (['delhivery' => 'Delhivery', 'bluedart' => 'Blue Dart', 'dhl' => 'DHL Express'] as $slug => $name)
                        @php $live = ($environments[$slug] ?? '') === 'LIVE'; @endphp
                        <tr>
                            <td class="align-middle fw-600">{{ $name }}</td>
                            <td class="align-middle">
                                <span class="badge badge-inline {{ $live ? 'badge-danger' : 'badge-warning' }}" title="{{ $live ? translate('Real shipments, real charges') : translate('Test environment, nothing is shipped') }}">
                                    {{ $environments[$slug] ?? '-' }}
                                </span>
                            </td>
                            <td class="align-middle">
                                <span class="badge badge-inline {{ $credentialStatus[$slug] ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $credentialStatus[$slug] ? translate('Set') : translate('Missing') }}
                                </span>
                            </td>
                            <td class="align-middle">
                                <button type="button" class="btn btn-xs btn-soft-primary courier-test-btn" data-url="{{ route('courier_shipment.test', $slug) }}">
                                    {{ translate('Test connection') }}
                                </button>
                                <span class="courier-test-result fs-12 ml-2"></span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="text-muted fs-12 mt-2 mb-0">
                {{ translate('Going live: put the live keys in .env, set DELHIVERY_ENV=production, BLUEDART_ENV=production or DHL_ENV=production, run php artisan config:clear, then click Test connection. Nothing is booked by the test.') }}
            </p>
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

@php
    $rateCard = \App\Support\BluedartRateCard::fromSetting(get_setting('bluedart_rate_card'));
    $cardValue = fn ($path, $default = '') => data_get($rateCard, $path, $default);
@endphp
<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Blue Dart rate card') }}</h5>
    </div>
    <div class="card-body">
        <p class="text-muted fs-12">
            {{ translate('Blue Dart has no price API, so checkout prices come from your contract rates. Weight is charged in 500 g steps on the higher of actual and volumetric weight. Zones are worked out from the pickup and delivery pincodes.') }}
        </p>
        <form action="{{ route('business_settings.update') }}" method="POST">
            @csrf
            <input type="hidden" name="types[]" value="bluedart_rate_card">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-3">
                    <thead>
                        <tr>
                            <th>{{ translate('Zone') }}</th>
                            <th>{{ translate('First 500 g (Rs.)') }}</th>
                            <th>{{ translate('Each extra 500 g (Rs.)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (\App\Support\BluedartRateCard::ZONES as $zone => $label)
                            <tr>
                                <td class="align-middle">{{ translate($label) }}</td>
                                <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="bluedart_rate_card[zones][{{ $zone }}][first]" value="{{ $cardValue("zones.$zone.first") }}"></td>
                                <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="bluedart_rate_card[zones][{{ $zone }}][additional]" value="{{ $cardValue("zones.$zone.additional") }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-row">
                <div class="col-md-3 form-group">
                    <label class="fs-12">{{ translate('Fuel surcharge %') }}</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="bluedart_rate_card[fuel_pct]" value="{{ $cardValue('fuel_pct') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label class="fs-12">{{ translate('COD fee, minimum (Rs.)') }}</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="bluedart_rate_card[cod_flat]" value="{{ $cardValue('cod_flat') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label class="fs-12">{{ translate('COD fee, % of order value') }}</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="bluedart_rate_card[cod_pct]" value="{{ $cardValue('cod_pct') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label class="fs-12">{{ translate('GST %') }}</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="bluedart_rate_card[gst_pct]" value="{{ $cardValue('gst_pct', 18) }}">
                </div>
            </div>
            <div class="text-right">
                <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save rate card') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script')
<script>
    $(document).on('click', '.courier-test-btn', function () {
        var button = $(this);
        var result = button.siblings('.courier-test-result');
        button.prop('disabled', true);
        result.removeClass('text-success text-danger').addClass('text-muted').text('{{ translate('Checking...') }}');

        $.post(button.data('url'), { _token: '{{ csrf_token() }}' })
            .done(function (payload) {
                result.removeClass('text-muted').addClass(payload && payload.success ? 'text-success' : 'text-danger')
                    .text((payload && payload.message) || '{{ translate('No response') }}');
            })
            .fail(function (xhr) {
                result.removeClass('text-muted').addClass('text-danger')
                    .text((xhr.responseJSON && xhr.responseJSON.message) || '{{ translate('Request failed') }}');
            })
            .always(function () {
                button.prop('disabled', false);
            });
    });
</script>
@endsection
