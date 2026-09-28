@php
    $accountTypeValue = old('account_type', $details->account_type ?? '');
    $accountTypeCustom = old('account_type_custom', $details->account_type_custom ?? '');
    $accountTypeIsCustom = $accountTypeValue === '__not_in_list__' || ($accountTypeCustom !== '' && $accountTypeCustom !== null && $accountTypeValue === '');
    if ($accountTypeCustom !== '' && $accountTypeCustom !== null && !in_array($accountTypeValue, \App\Models\UserDetails::ACCOUNT_TYPES, true) && $accountTypeValue !== '' && $accountTypeValue !== '__not_in_list__') {
        $accountTypeIsCustom = true;
        $accountTypeCustom = $accountTypeValue;
        $accountTypeValue = '__not_in_list__';
    }

    $territoryValue = old('territory', $details->territory ?? '');
    $territoryCustom = old('territory_custom', $details->territory_custom ?? '');
    $territoryNames = collect($territoryStates ?? [])->pluck('name')->all();
    $territoryIsCustom = $territoryValue === '__not_in_list__';
    if (!$territoryIsCustom && $territoryValue !== '' && !in_array($territoryValue, $territoryNames, true)) {
        $territoryIsCustom = true;
        $territoryCustom = $territoryCustom !== '' ? $territoryCustom : $territoryValue;
        $territoryValue = '__not_in_list__';
    }

    $sellerCompaniesJson = collect($sellerCompanies ?? [])->map(function ($company) {
        return [
            'id' => $company->id,
            'name' => $company->company_name,
            'address' => $company->full_address,
        ];
    })->values();
    $taxRowsJson = collect($taxMasterRows ?? [])->map(function ($tax) {
        return [
            'tax_code' => $tax['tax_code'] ?? $tax->tax_code ?? '',
            'description' => $tax['description'] ?? $tax->description ?? '',
            'kind' => $tax['kind'] ?? $tax->kind ?? '',
            'sale_tax' => $tax['sale_tax'] ?? $tax->sale_tax ?? 0,
            'sale_cgst' => $tax['sale_cgst'] ?? $tax->sale_cgst ?? 0,
            'sale_sgst' => $tax['sale_sgst'] ?? $tax->sale_sgst ?? 0,
            'sale_igst' => $tax['sale_igst'] ?? $tax->sale_igst ?? 0,
        ];
    })->values();
@endphp

@if (empty($accountColumnsReady))
    <div class="alert alert-warning">
        {{ translate('Account Type, Territory, and the international tax choice will display here. Run the SQL note for user_details before those values can be saved.') }}
    </div>
@endif

<div class="row" id="customer-account-tax-row">
    <div class="col-md-3 mb-3">
        <label class="form-label" for="account_type">{{ translate('Account Type') }}</label>
        <select id="account_type" name="account_type" class="form-control aiz-selectpicker js-not-in-list" data-live-search="true" data-custom-input="#account_type_custom">
            <option value="">{{ translate('Select Account Type') }}</option>
            @foreach (\App\Models\UserDetails::ACCOUNT_TYPES as $accountType)
                <option value="{{ $accountType }}" @selected($accountTypeValue === $accountType)>{{ translate($accountType) }}</option>
            @endforeach
            <option value="__not_in_list__" @selected($accountTypeIsCustom || $accountTypeValue === '__not_in_list__')>{{ translate('Not In List') }}</option>
        </select>
        <input type="text" id="account_type_custom" name="account_type_custom" class="form-control mt-2 {{ ($accountTypeIsCustom || $accountTypeValue === '__not_in_list__') ? '' : 'd-none' }}" value="{{ $accountTypeCustom }}" placeholder="{{ translate('Enter account type') }}" maxlength="255">
        @error('account_type') <div class="text-danger small">{{ $message }}</div> @enderror
        @error('account_type_custom') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label" for="territory">{{ translate('Territory') }}</label>
        <select id="territory" name="territory" class="form-control aiz-selectpicker js-not-in-list" data-live-search="true" data-custom-input="#territory_custom">
            <option value="">{{ translate('Select Territory') }}</option>
            @foreach ($territoryStates ?? [] as $state)
                <option value="{{ $state->name }}" @selected(!$territoryIsCustom && $territoryValue === $state->name)>{{ $state->name }}</option>
            @endforeach
            <option value="__not_in_list__" @selected($territoryIsCustom)>{{ translate('Not In List') }}</option>
        </select>
        <input type="text" id="territory_custom" name="territory_custom" class="form-control mt-2 {{ $territoryIsCustom ? '' : 'd-none' }}" value="{{ $territoryCustom }}" placeholder="{{ translate('Enter territory') }}" maxlength="255">
        <small class="text-muted">{{ translate('Auto-selected from the business state. You can change it.') }}</small>
        @error('territory') <div class="text-danger small">{{ $message }}</div> @enderror
        @error('territory_custom') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <div class="border rounded p-3 h-100" id="customer-account-sidebar">
            <div class="text-muted small">{{ translate('Account to be used') }}</div>
            <div class="font-weight-bold" id="customer-account-used-name">{{ old('account_name_business', $details->account_name_business ?? '') ?: translate('Account name') }}</div>
            <div class="small mt-2" id="customer-seller-line">{{ translate('Seller from Company Master') }}</div>
            <div class="mt-2 font-weight-medium" id="customer-tax-label">{{ translate('Tax') }}</div>
            <div id="customer-international-tax" class="mt-2 d-none">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="international_tax_choice" id="international_tax_lut" value="lut" @checked(old('international_tax_choice', $details->international_tax_choice ?? '') === 'lut')>
                    <label class="form-check-label" for="international_tax_lut">{{ translate('International - LUT') }}</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="international_tax_choice" id="international_tax_igst" value="igst" @checked(old('international_tax_choice', $details->international_tax_choice ?? '') === 'igst')>
                    <label class="form-check-label" for="international_tax_igst">{{ translate('International - IGST') }}</label>
                </div>
            </div>
            <div class="small text-muted mt-2" id="customer-tax-rate"></div>
        </div>
    </div>
</div>

<script type="application/json" id="customer-place-of-supply-data">
{!! json_encode([
    'companies' => $sellerCompaniesJson,
    'states' => collect($territoryStates ?? [])->map(function ($state) {
        return ['id' => $state->id, 'name' => $state->name];
    })->values(),
    'taxes' => $taxRowsJson,
    'utgstStates' => [
        'chandigarh',
        'andaman and nicobar islands',
        'dadra and nagar haveli and daman and diu',
        'dadra and nagar haveli',
        'daman and diu',
        'ladakh',
        'lakshadweep',
    ],
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>
