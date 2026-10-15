@php
    $hsnValue = old('product_hsn', $productHsn ?? '');
    $hsValue = old('product_hs', $productHs ?? '');
@endphp
<div class="form-group row">
    <label class="col-xxl-3 col-from-label fs-13">{{ translate('Product HSN Code') }}</label>
    <div class="col-xxl-9">
        <input type="text" class="form-control @error('product_hsn') is-invalid @enderror" name="product_hsn" id="product_hsn" value="{{ $hsnValue }}" placeholder="{{ translate('Product HSN Code') }}">
        <button type="button" class="btn btn-link btn-sm px-0" data-toggle="collapse" data-target="#product-tax-master-fields" aria-expanded="false">
            {{ translate('Show tax from Tax Master') }}
        </button>
        <div id="product-tax-master-fields" class="collapse border rounded p-3 mt-1">
            <div class="form-group mb-2">
                <label class="fs-13">{{ translate('Product HS Code') }}</label>
                <input type="text" class="form-control @error('product_hs') is-invalid @enderror" name="product_hs" id="product_hs" value="{{ $hsValue }}" placeholder="{{ translate('Product HS Code') }}">
            </div>
            <div class="form-group mb-2">
                <label class="fs-13">{{ translate('Tax Type') }}</label>
                <input type="text" class="form-control" id="product_tax_type_display" value="" readonly>
            </div>
            <div class="form-group mb-2">
                <label class="fs-13">{{ translate('Purchase Tax %') }}</label>
                <input type="text" class="form-control" id="product_purchase_tax_percent_display" value="" readonly>
            </div>
            <div class="form-group mb-2">
                <label class="fs-13">{{ translate('Sale Tax %') }}</label>
                <input type="text" class="form-control" id="product_sale_tax_percent_display" value="" readonly>
            </div>
            <div class="form-group mb-1">
                <label class="fs-13">{{ translate('Tax Code') }}</label>
                <input type="text" class="form-control" id="product_tax_code_display" value="" readonly>
            </div>
            <small class="text-muted d-block" id="product_tax_description_display"></small>
        </div>
    </div>
</div>
