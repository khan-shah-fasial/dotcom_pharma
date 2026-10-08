@php
    $t = $tax;
    $kind = old('kind', optional($t)->kind ?? 'taxable');
    $same = old('sale_same_as_purchase', optional($t)->sale_same_as_purchase ?? true);
    if ($same === '0' || $same === 0 || $same === false) {
        $same = false;
    } else {
        $same = (bool) $same;
    }
    $status = old('status', optional($t)->status ?? true);
    $hsnReady = $hsnReady ?? false;
    $hsnOptions = $hsnOptions ?? collect();
    $categories = $categories ?? collect();
    $rate = function ($field, $default = 0) use ($t) {
        $value = old($field, optional($t)->$field ?? $default);
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

        @if (empty($hsnReady))
            <div class="alert alert-warning">
                {{ translate('HSN / HS Code / Applied On columns are not on the table yet. Run sqlupdates/tax_master_hsn.sql before saving those fields.') }}
            </div>
        @endif

        <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm mb-0 tm-identity-grid">
                <thead>
                    <tr>
                        <th style="width:8%">{{ translate('Tax ID') }}</th>
                        <th style="width:14%">{{ translate('HSN Code') }}</th>
                        <th style="width:12%">{{ translate('HS Code') }}</th>
                        <th style="width:16%">{{ translate('Tax Type') }} *</th>
                        <th style="width:12%">{{ translate('Tax Code') }} *</th>
                        <th>{{ translate('Full Description') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" class="form-control form-control-sm" value="{{ optional($t)->id }}" readonly></td>
                        <td>
                            <input type="text" list="hsn-list" name="hsn_code" id="hsn_code" class="form-control form-control-sm" value="{{ old('hsn_code', optional($t)->hsn_code) }}" maxlength="50" {{ empty($hsnReady) ? 'disabled' : '' }}>
                            <datalist id="hsn-list">
                                @foreach ($hsnOptions ?? [] as $opt)
                                    <option value="{{ $opt->product_hsn }}" data-hs="{{ $opt->product_hs ?? '' }}">{{ $opt->product_hsn }}</option>
                                @endforeach
                            </datalist>
                            <small class="text-muted d-block">{{ translate('Dropdown from products. Free API is also available.') }}</small>
                        </td>
                        <td>
                            <input type="text" name="hs_code" id="hs_code" class="form-control form-control-sm" value="{{ old('hs_code', optional($t)->hs_code) }}" maxlength="50" readonly>
                        </td>
                        <td>
                            <select name="kind" id="kind" class="form-control form-control-sm aiz-selectpicker">
                                @foreach (\App\Models\TaxMaster::KINDS as $key => $label)
                                    <option value="{{ $key }}" @selected($kind === $key)>{{ translate($label) }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block">{{ translate('Taxable = GST extra. Inclusive = GST already in price. Exempted = no tax (all rates 0).') }}</small>
                        </td>
                        <td>
                            <input type="text" name="tax_code" id="tax_code" class="form-control form-control-sm @error('tax_code') is-invalid @enderror" value="{{ old('tax_code', optional($t)->tax_code) }}" maxlength="20" placeholder="G5">
                        </td>
                        <td>
                            <input type="text" name="description" id="description" class="form-control form-control-sm" value="{{ old('description', optional($t)->description) }}" maxlength="255">
                        </td>
                    </tr>
                </tbody>
            </table>
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
                    <input type="text" id="purchase_total" class="form-control" value="{{ $t ? \App\Models\TaxMaster::formatRate($t->purchaseTotal()) : '0' }}" readonly>
                    <small class="text-danger tm-mismatch d-none" data-side="purchase">{{ translate('Tax % must equal CGST + SGST + IGST.') }}</small>
                </div>
            </div>
        </div>

        <hr class="mt-1 mb-3">
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
                    <input type="text" id="sale_total" class="form-control" value="{{ $t ? \App\Models\TaxMaster::formatRate($t->saleTotal()) : '0' }}" readonly>
                    <small class="text-danger tm-mismatch d-none" data-side="sale">{{ translate('Tax % must equal CGST + SGST + IGST.') }}</small>
                </div>
            </div>
        </div>

        <h6 class="mt-2 mb-3">{{ translate('Applied On') }}</h6>
        <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm mb-0 tm-identity-grid">
                <thead>
                    <tr>
                        <th>{{ translate('Category') }}</th>
                        <th>{{ translate('SKU') }}</th>
                        <th>{{ translate('Product Name') }}</th>
                        <th>{{ translate('Variant') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="applied_on_category" id="applied_on_category" class="form-control form-control-sm" {{ empty($hsnReady) ? 'disabled' : '' }}>
                                <option value="">{{ translate('Select') }}</option>
                                @foreach ($categories ?? [] as $category)
                                    <option value="{{ $category->getTranslation('name') }}" @selected(old('applied_on_category', optional($t)->applied_on_category) === $category->getTranslation('name'))>{{ $category->getTranslation('name') }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" name="applied_on_sku" class="form-control form-control-sm" value="{{ old('applied_on_sku', optional($t)->applied_on_sku) }}" maxlength="255" {{ empty($hsnReady) ? 'disabled' : '' }}></td>
                        <td><input type="text" name="applied_on_product" class="form-control form-control-sm" value="{{ old('applied_on_product', optional($t)->applied_on_product) }}" maxlength="255" {{ empty($hsnReady) ? 'disabled' : '' }}></td>
                        <td><input type="text" name="applied_on_variant" class="form-control form-control-sm" value="{{ old('applied_on_variant', optional($t)->applied_on_variant) }}" maxlength="255" {{ empty($hsnReady) ? 'disabled' : '' }}></td>
                    </tr>
                </tbody>
            </table>
            <small class="text-muted d-block mt-1">{{ translate('Catalog labels only. This does not change live product tax rows.') }}</small>
        </div>

        <div class="text-right">
            <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
        </div>
    </div>
</div>
<style>
    .tm-identity-grid th { background: #f3f6f9; font-size: 12px; white-space: normal; }
    .tm-identity-grid td { vertical-align: top; }
</style>
