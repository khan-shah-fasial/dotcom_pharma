@php
    $t = $tax;
    $kind = old('kind', $t->kind ?? 'taxable');
    $same = old('sale_same_as_purchase', $t->sale_same_as_purchase ?? true);
    if ($same === '0' || $same === 0 || $same === false) {
        $same = false;
    } else {
        $same = (bool) $same;
    }
    $status = old('status', $t->status ?? true);
    $rate = function ($field, $default = 0) use ($t) {
        $value = old($field, $t->$field ?? $default);
        return \App\Models\TaxMaster::formatRate($value);
    };
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Tax Master') }}</h5>
        <p class="text-muted mb-0 fs-12">{{ translate('TOTAL GST% is CGST + SGST + IGST and must equal Tax %.') }}</p>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            @if ($t)
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ translate('Tax ID') }}</label>
                        <input type="text" class="form-control" value="{{ $t->id }}" readonly>
                    </div>
                </div>
            @endif
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Tax Type') }} <span class="text-danger">*</span></label>
                    <select name="kind" id="kind" class="form-control aiz-selectpicker">
                        @foreach (\App\Models\TaxMaster::KINDS as $key => $label)
                            <option value="{{ $key }}" @selected($kind === $key)>{{ translate($label) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Tax Code') }} <span class="text-danger">*</span></label>
                    <input type="text" name="tax_code" id="tax_code" class="form-control @error('tax_code') is-invalid @enderror" value="{{ old('tax_code', $t->tax_code ?? '') }}" maxlength="20" placeholder="G5">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>{{ translate('Description') }}</label>
                    <input type="text" name="description" id="description" class="form-control" value="{{ old('description', $t->description ?? '') }}" maxlength="255" placeholder="{{ translate('Description') }}">
                </div>
            </div>
        </div>

        <h6 class="mt-2 mb-3">{{ translate('Purchase Tax') }}</h6>
        <div class="row tm-rate-row" data-side="purchase">
            <div class="col-md-2">
                <div class="form-group">
                    <label>{{ translate('Tax %') }}</label>
                    <input type="number" lang="en" step="0.0001" min="0" max="100" name="purchase_tax" id="purchase_tax" class="form-control tm-tax @error('purchase_tax') is-invalid @enderror" value="{{ $rate('purchase_tax') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>{{ translate('CGST %') }}</label>
                    <input type="number" lang="en" step="0.0001" min="0" max="100" name="purchase_cgst" id="purchase_cgst" class="form-control tm-split" value="{{ $rate('purchase_cgst') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>{{ translate('SGST %') }}</label>
                    <input type="number" lang="en" step="0.0001" min="0" max="100" name="purchase_sgst" id="purchase_sgst" class="form-control tm-split" value="{{ $rate('purchase_sgst') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>{{ translate('IGST %') }}</label>
                    <input type="number" lang="en" step="0.0001" min="0" max="100" name="purchase_igst" id="purchase_igst" class="form-control tm-split" value="{{ $rate('purchase_igst') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>{{ translate('TOTAL GST%') }}</label>
                    <input type="text" id="purchase_total" class="form-control" value="{{ $rate('purchase_tax', $t ? $t->purchaseTotal() : 0) }}" readonly>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="form-group">
                    <label>{{ translate('Sale tax same as purchase?') }}</label>
                    <div class="mt-1">
                        <label class="mr-4 mb-0">
                            <input type="radio" name="sale_same_as_purchase" id="sale_same_as_purchase_y" value="1" {{ $same ? 'checked' : '' }}>
                            Y
                        </label>
                        <label class="mb-0">
                            <input type="radio" name="sale_same_as_purchase" id="sale_same_as_purchase_n" value="0" {{ !$same ? 'checked' : '' }}>
                            N
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">{{ translate('Y copies purchase GST into sale. N lets you type sale GST separately.') }}</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Status') }}</label>
                    <div class="mt-2">
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="hidden" name="status" value="0">
                            <input type="checkbox" name="status" value="1" {{ $status ? 'checked' : '' }}>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <h6 class="mt-2 mb-3">{{ translate('Sale Tax') }}</h6>
        <div class="row tm-rate-row" data-side="sale">
            <div class="col-md-2">
                <div class="form-group">
                    <label>{{ translate('Tax %') }}</label>
                    <input type="number" lang="en" step="0.0001" min="0" max="100" name="sale_tax" id="sale_tax" class="form-control tm-tax @error('sale_tax') is-invalid @enderror" value="{{ $rate('sale_tax') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>{{ translate('CGST %') }}</label>
                    <input type="number" lang="en" step="0.0001" min="0" max="100" name="sale_cgst" id="sale_cgst" class="form-control tm-split" value="{{ $rate('sale_cgst') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>{{ translate('SGST %') }}</label>
                    <input type="number" lang="en" step="0.0001" min="0" max="100" name="sale_sgst" id="sale_sgst" class="form-control tm-split" value="{{ $rate('sale_sgst') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>{{ translate('IGST %') }}</label>
                    <input type="number" lang="en" step="0.0001" min="0" max="100" name="sale_igst" id="sale_igst" class="form-control tm-split" value="{{ $rate('sale_igst') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>{{ translate('TOTAL GST%') }}</label>
                    <input type="text" id="sale_total" class="form-control" value="{{ $rate('sale_tax', $t ? $t->saleTotal() : 0) }}" readonly>
                </div>
            </div>
        </div>

        <div class="text-right">
            <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
        </div>
    </div>
</div>
