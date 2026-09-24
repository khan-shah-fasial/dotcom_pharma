@php
    $companyColumnsReady = \Illuminate\Support\Facades\Schema::hasColumn('products', 'marketed_by_id')
        && \Illuminate\Support\Facades\Schema::hasColumn('products', 'manufactured_by_ids')
        && \Illuminate\Support\Facades\Schema::hasColumn('products', 'import_by_ids');
    $productCompanies = \App\Models\Company::query()->orderBy('company_name')->get(['id', 'company_name']);
    $selectedMarketed = (string) old('marketed_by_id', isset($product) ? ($product->marketed_by_id ?? '') : '');
    $marketedManual = (string) old('marketed_by_name', isset($product) ? ($product->marketed_by_name ?? '') : '');
    $marketedNotInList = old('marketed_by_id') === '__not_in_list__' || ($selectedMarketed === '' && $marketedManual !== '');
    $selectedManufactured = collect(old('manufactured_by_ids', isset($product) ? (json_decode($product->manufactured_by_ids ?? '[]', true) ?: []) : []))->map(fn ($id) => (string) $id);
    $selectedImported = collect(old('import_by_ids', isset($product) ? (json_decode($product->import_by_ids ?? '[]', true) ?: []) : []))->map(fn ($id) => (string) $id);
    $manufacturedManual = old('manufactured_by_manual', isset($product) ? implode(', ', json_decode($product->manufactured_by_names ?? '[]', true) ?: []) : '');
    $importedManual = old('import_by_manual', isset($product) ? implode(', ', json_decode($product->import_by_names ?? '[]', true) ?: []) : '');
@endphp
@if (!$companyColumnsReady)
    <div class="alert alert-soft-warning">
        {{ translate('Marketed By, Manufactured By, and Import By are ready on this form. They are saved after the product columns in the SQL note are added.') }}
    </div>
@endif
<input type="hidden" name="product_company_fields" value="1">
<div class="form-group row">
    <label class="col-xxl-3 col-from-label fs-13">{{ translate('Marketed By') }}</label>
    <div class="col-xxl-9">
        <select class="form-control aiz-selectpicker" name="marketed_by_id" id="marketed_by_id" data-live-search="true">
            <option value="">{{ translate('Select Company') }}</option>
            @foreach ($productCompanies as $companyOption)
                <option value="{{ $companyOption->id }}" @selected(!$marketedNotInList && $selectedMarketed === (string) $companyOption->id)>{{ $companyOption->company_name }}</option>
            @endforeach
            <option value="__not_in_list__" @selected($marketedNotInList)>{{ translate('Not In List') }}</option>
        </select>
        <input type="text" name="marketed_by_name" id="marketed_by_name" class="form-control mt-2 {{ $marketedNotInList ? '' : 'd-none' }}" value="{{ $marketedManual }}" placeholder="{{ translate('Add company manually') }}">
    </div>
</div>
<div class="form-group row">
    <label class="col-xxl-3 col-from-label fs-13">{{ translate('Manufactured By') }}</label>
    <div class="col-xxl-9">
        <select class="form-control aiz-selectpicker" name="manufactured_by_ids[]" data-live-search="true" multiple data-selected-text-format="count">
            @foreach ($productCompanies as $companyOption)
                <option value="{{ $companyOption->id }}" @selected($selectedManufactured->contains((string) $companyOption->id))>{{ $companyOption->company_name }}</option>
            @endforeach
        </select>
        <input type="text" name="manufactured_by_manual" class="form-control mt-2" value="{{ $manufacturedManual }}" placeholder="{{ translate('Not In List: comma separated company names') }}">
        <label class="aiz-checkbox mt-2">
            <input type="checkbox" name="manufactured_by_hidden" value="1" @checked(old('manufactured_by_hidden', isset($product) ? ($product->manufactured_by_hidden ?? 0) : 0))>
            <span class="aiz-square-check"></span>
            <span>{{ translate('Hide') }}</span>
        </label>
    </div>
</div>
<div class="form-group row">
    <label class="col-xxl-3 col-from-label fs-13">{{ translate('Import By') }}</label>
    <div class="col-xxl-9">
        <select class="form-control aiz-selectpicker" name="import_by_ids[]" data-live-search="true" multiple data-selected-text-format="count">
            @foreach ($productCompanies as $companyOption)
                <option value="{{ $companyOption->id }}" @selected($selectedImported->contains((string) $companyOption->id))>{{ $companyOption->company_name }}</option>
            @endforeach
        </select>
        <input type="text" name="import_by_manual" class="form-control mt-2" value="{{ $importedManual }}" placeholder="{{ translate('Not In List: comma separated company names') }}">
        <label class="aiz-checkbox mt-2">
            <input type="checkbox" name="import_by_hidden" value="1" @checked(old('import_by_hidden', isset($product) ? ($product->import_by_hidden ?? 0) : 0))>
            <span class="aiz-square-check"></span>
            <span>{{ translate('Hide') }}</span>
        </label>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.jQuery) {
            return;
        }
        jQuery('#marketed_by_id').on('changed.bs.select', function () {
            jQuery('#marketed_by_name').toggleClass('d-none', jQuery(this).val() !== '__not_in_list__');
        });
    });
</script>
