@php
    $productName = old('name', isset($product) ? $product->getTranslation('name', $lang ?? false) : '');
    $brandsForName = \App\Models\Brand::query()->orderBy('name')->get();
    $matchedBrand = $brandsForName->first(function ($brand) use ($productName) {
        return $productName !== '' && strcasecmp((string) $brand->getTranslation('name'), (string) $productName) === 0;
    });
    $nameNotInList = $productName !== '' && !$matchedBrand;
@endphp
<div class="form-group row">
    <label class="col-xxl-3 col-from-label fs-13" for="brand_product_name_choice">{{ translate('Brand Name/Product Name') }} <span class="text-danger h5">*</span></label>
    <div class="col-xxl-9">
        <select class="form-control aiz-selectpicker" id="brand_product_name_choice" data-live-search="true">
            <option value="">{{ translate('Select Brand Name') }}</option>
            @foreach ($brandsForName as $brandOption)
                <option value="{{ $brandOption->getTranslation('name') }}" data-brand-id="{{ $brandOption->id }}" @selected($matchedBrand && $matchedBrand->id === $brandOption->id)>
                    {{ $brandOption->getTranslation('name') }}
                </option>
            @endforeach
            <option value="__not_in_list__" @selected($nameNotInList)>{{ translate('Not In List') }}</option>
        </select>
        <input type="text" id="product_name_manual" class="form-control mt-2 @error('name') is-invalid @enderror {{ $nameNotInList || $productName === '' ? '' : 'd-none' }}" name="name" value="{{ $productName }}" placeholder="{{ translate('Brand Name/Product Name') }}" onchange="update_sku()" required>
        <small class="text-muted">{{ translate('Choose a brand name, or Not In List to type a name that is not in All Brands.') }}</small>
        @error('name') <span class="text-danger small d-block">{{ $message }}</span> @enderror
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var choice = document.getElementById('brand_product_name_choice');
        var input = document.getElementById('product_name_manual');
        if (!choice || !input) {
            return;
        }
        function applyChoice() {
            var selected = choice.options[choice.selectedIndex];
            if (!selected || selected.value === '' || selected.value === '__not_in_list__') {
                input.classList.remove('d-none');
                return;
            }
            input.value = selected.value;
            input.classList.add('d-none');
            var brandSelect = document.getElementById('brand_id');
            var brandId = selected.getAttribute('data-brand-id');
            if (brandSelect && brandId) {
                brandSelect.value = brandId;
                if (window.jQuery && jQuery(brandSelect).selectpicker) {
                    jQuery(brandSelect).selectpicker('refresh');
                }
            }
            if (window.jQuery) {
                jQuery(input).trigger('keyup').trigger('change');
            }
        }
        if (window.jQuery) {
            jQuery(choice).on('changed.bs.select', applyChoice);
        }
        choice.addEventListener('change', applyChoice);
    });
</script>
