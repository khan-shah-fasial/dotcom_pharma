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
    $utReady = $utReady ?? false;
    $categories = $categories ?? collect();
    $rate = function ($field, $default = 0) use ($t) {
        $value = old($field, optional($t)->$field ?? $default);
        return \App\Models\TaxMaster::formatRate($value);
    };
    $taxPercent = $rate('purchase_tax');
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Tax Master') }}</h5>
        <p class="text-muted mb-0 fs-12">{{ translate('TOTAL GST% equals State CGST + SGST, or UT CGST + UTGST, or IGST.') }}</p>
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
        @if (empty($utReady))
            <div class="alert alert-warning">
                {{ translate('UT GST columns are not on the table yet. Run sqlupdates/tax_master_utgst.sql before saving UT CGST and UTGST.') }}
            </div>
        @endif

        <div class="table-responsive mb-3 tm-identity-wrap">
            <table class="table table-bordered table-sm mb-0 tm-identity-grid">
                <thead>
                    <tr>
                        <th style="width:8%">{{ translate('Tax ID') }}</th>
                        <th style="width:16%">{{ translate('Search HSN Code') }}</th>
                        <th style="width:10%">{{ translate('HS Code') }}</th>
                        <th style="width:18%">{{ translate('Tax Type') }} *</th>
                        <th style="width:10%">{{ translate('Tax %') }}</th>
                        <th style="width:12%">{{ translate('Tax Code') }} *</th>
                        <th>{{ translate('Description') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" class="form-control form-control-sm" value="{{ optional($t)->id ?: translate('Auto') }}" readonly></td>
                        <td>
                            <div class="tm-hsn-wrap">
                                <input type="text" name="hsn_code" id="hsn_code" class="form-control form-control-sm" value="{{ old('hsn_code', optional($t)->hsn_code) }}" maxlength="50" autocomplete="off" placeholder="{{ translate('Code or description') }}">
                                <div id="hsn-results" class="tm-hsn-results" hidden></div>
                            </div>
                            <small class="text-muted d-block">{{ translate('Official GST HSN list. Type a code that is not listed to add it.') }}</small>
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
                            <small class="text-muted d-block">{{ translate('Taxable = GST extra. Inclusive = GST already in price. Exempted = no tax (all rates 0). LUT = no tax (all rates 0), for international customers only.') }}</small>
                        </td>
                        <td>
                            <input type="number" lang="en" step="0.0001" min="0" max="100" id="tax_percent" class="form-control form-control-sm" value="{{ $taxPercent }}">
                        </td>
                        <td>
                            <input type="text" name="tax_code" id="tax_code" class="form-control form-control-sm @error('tax_code') is-invalid @enderror" value="{{ old('tax_code', optional($t)->tax_code) }}" maxlength="20" placeholder="G5" data-auto="{{ $t || old('tax_code') ? '0' : '1' }}" data-ignore-id="{{ optional($t)->id }}">
                        </td>
                        <td>
                            <input type="text" name="description" id="description" class="form-control form-control-sm" value="{{ old('description', optional($t)->description) }}" maxlength="255">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        @foreach (['purchase' => 'Purchase Tax', 'sale' => 'Sale Tax'] as $side => $heading)
            <h6 class="mt-2 mb-2">{{ translate($heading) }}</h6>
            <div class="table-responsive mb-2">
                <table class="table table-bordered table-sm mb-0 tm-rate-grid" data-side="{{ $side }}">
                    <thead>
                        <tr>
                            <th>{{ translate('Total Tax') }}</th>
                            <th colspan="2">{{ translate('State Tax') }}</th>
                            <th colspan="2">{{ translate('UT Tax') }}</th>
                            <th>{{ translate('Central Tax') }}</th>
                        </tr>
                        <tr>
                            <th>{{ translate('Tax %') }}</th>
                            <th>{{ translate('CGST %') }}</th>
                            <th>{{ translate('SGST %') }}</th>
                            <th>{{ translate('CGST %') }}</th>
                            <th>{{ translate('UTGST %') }}</th>
                            <th>{{ translate('IGST %') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="{{ $side }}_tax" id="{{ $side }}_tax" class="form-control form-control-sm tm-tax" value="{{ $rate($side . '_tax') }}" readonly></td>
                            <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="{{ $side }}_cgst" id="{{ $side }}_cgst" class="form-control form-control-sm" value="{{ $rate($side . '_cgst') }}" readonly></td>
                            <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="{{ $side }}_sgst" id="{{ $side }}_sgst" class="form-control form-control-sm" value="{{ $rate($side . '_sgst') }}" readonly></td>
                            <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="{{ $side }}_ut_cgst" id="{{ $side }}_ut_cgst" class="form-control form-control-sm tm-ut" value="{{ $rate($side . '_ut_cgst') }}"></td>
                            <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="{{ $side }}_utgst" id="{{ $side }}_utgst" class="form-control form-control-sm tm-ut" value="{{ $rate($side . '_utgst') }}"></td>
                            <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="{{ $side }}_igst" id="{{ $side }}_igst" class="form-control form-control-sm" value="{{ $rate($side . '_igst') }}" readonly></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <small class="text-danger tm-mismatch d-none mb-3 d-block" data-side="{{ $side }}">{{ translate('UT CGST % + UTGST % must equal Tax %.') }}</small>
            @if ($side === 'purchase')
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group mb-2">
                            <label>{{ translate('Sale tax same as purchase?') }}</label>
                            <div class="mt-1">
                                <label class="mr-4 mb-0">
                                    <input type="radio" name="sale_same_as_purchase" id="sale_same_as_purchase_y" value="1" {{ $same ? 'checked' : '' }}>
                                    {{ translate('Yes') }}
                                </label>
                                <label class="mb-0">
                                    <input type="radio" name="sale_same_as_purchase" id="sale_same_as_purchase_n" value="0" {{ !$same ? 'checked' : '' }}>
                                    {{ translate('No') }}
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">{{ translate('Yes copies purchase GST into sale. No lets you type the sale Tax %. Default is Yes.') }}</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-2">
                            <label>{{ translate('Status') }}</label>
                            <div class="mt-2">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="hidden" name="status" value="0">
                                    <input type="checkbox" name="status" value="1" {{ $status ? 'checked' : '' }}>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            <small class="text-muted d-block">{{ translate('Active / Non-Active') }}</small>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
        <small class="text-muted d-block mb-3">{{ translate('State CGST and SGST split Tax % in half. IGST copies Tax %. UT CGST and UTGST start as the same split and stay editable.') }}</small>

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
                            <select name="applied_on_category" id="applied_on_category" class="form-control form-control-sm">
                                <option value="">{{ translate('Select') }}</option>
                                @foreach ($categories ?? [] as $category)
                                    <option value="{{ $category->getTranslation('name') }}" @selected(old('applied_on_category', optional($t)->applied_on_category) === $category->getTranslation('name'))>{{ $category->getTranslation('name') }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" name="applied_on_sku" class="form-control form-control-sm" value="{{ old('applied_on_sku', optional($t)->applied_on_sku) }}" maxlength="255"></td>
                        <td><input type="text" name="applied_on_product" class="form-control form-control-sm" value="{{ old('applied_on_product', optional($t)->applied_on_product) }}" maxlength="255"></td>
                        <td><input type="text" name="applied_on_variant" class="form-control form-control-sm" value="{{ old('applied_on_variant', optional($t)->applied_on_variant) }}" maxlength="255"></td>
                    </tr>
                </tbody>
            </table>
            <small class="text-muted d-block mt-1">{{ translate('Tax is applied later from the customer territory. International customers use the customer account form. These labels do not change live product tax rows.') }}</small>
        </div>

        <div class="text-right">
            <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
        </div>
    </div>
</div>
<style>
    .tm-identity-grid th, .tm-rate-grid th { background: #f3f6f9; font-size: 12px; white-space: normal; }
    .tm-identity-grid td, .tm-rate-grid td { vertical-align: top; }
    .tm-identity-wrap.table-responsive { overflow: visible; }
    .tm-hsn-wrap { position: relative; }
    .tm-hsn-results {
        position: absolute;
        z-index: 1050;
        left: 0;
        right: 0;
        max-height: 220px;
        overflow: auto;
        background: #fff;
        border: 1px solid #d8dde6;
        border-radius: 4px;
    }
    .tm-hsn-results button {
        display: block;
        width: 100%;
        text-align: left;
        border: 0;
        border-bottom: 1px solid #eef1f6;
        background: #fff;
        padding: 6px 8px;
        font-size: 12px;
    }
    .tm-hsn-results button:hover { background: #f3f6f9; }
</style>
