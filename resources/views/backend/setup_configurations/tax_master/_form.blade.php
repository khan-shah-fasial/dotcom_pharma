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
    $groupReady = $groupReady ?? false;
    $categories = $categories ?? collect();
    $groups = $groups ?? collect();
    $all = \App\Models\TaxMaster::APPLIED_ALL;
    $rate = function ($field, $default = 0) use ($t) {
        $value = old($field, optional($t)->$field ?? $default);
        return \App\Models\TaxMaster::formatRate($value);
    };
    $appliedValue = function ($field, $fallback) use ($t) {
        $value = old($field, optional($t)->$field);
        if ($value === null || $value === '') {
            return $fallback;
        }
        return $value;
    };
    $taxPercent = $rate('purchase_tax');
    $categoryValue = $appliedValue('applied_on_category', $all['category']);
    $groupValue = $appliedValue('applied_on_group', $all['group']);
    $skuValue = $appliedValue('applied_on_sku', $all['sku']);
    $productValue = $appliedValue('applied_on_product', $all['product']);
    $variantValue = $appliedValue('applied_on_variant', $all['variant']);
    $categoryNames = $categories->map(function ($category) {
        return $category->getTranslation('name');
    })->all();
    $groupNames = $groups->map(function ($group) {
        return $group->getTranslation('name');
    })->all();
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
        @if (empty($groupReady))
            <div class="alert alert-warning">
                {{ translate('Applied On Group is not on the table yet. Run sqlupdates/tax_master_applied_group.sql before saving Group.') }}
            </div>
        @endif

        <div class="table-responsive mb-3 tm-identity-wrap">
            <table class="table table-bordered table-sm mb-0 tm-identity-grid">
                <thead>
                    <tr>
                        <th style="width:7%">{{ translate('Tax ID') }}</th>
                        <th style="width:14%">{{ translate('Search HSN Code') }}</th>
                        <th style="width:16%">{{ translate('Search HSN Chapter by Description') }}</th>
                        <th style="width:8%">{{ translate('HS Code') }}</th>
                        <th style="width:14%">{{ translate('Tax Type') }} *</th>
                        <th style="width:8%">{{ translate('Tax %') }}</th>
                        <th style="width:10%">{{ translate('Tax Code') }} *</th>
                        <th>{{ translate('Description') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" class="form-control form-control-sm" value="{{ optional($t)->id ?: translate('Auto') }}" readonly></td>
                        <td>
                            <div class="tm-hsn-wrap">
                                <input type="text" name="hsn_code" id="hsn_code" class="form-control form-control-sm" value="{{ old('hsn_code', optional($t)->hsn_code) }}" maxlength="50" autocomplete="off" placeholder="{{ translate('Code') }}">
                                <div id="hsn-results" class="tm-hsn-results" hidden></div>
                            </div>
                            <small class="text-muted d-block">{{ translate('Official GST HSN list. Type a code that is not listed to add it.') }}</small>
                        </td>
                        <td>
                            <div class="tm-hsn-wrap">
                                <input type="text" id="hsn_chapter" class="form-control form-control-sm" maxlength="255" autocomplete="off" placeholder="{{ translate('Chapter description') }}">
                                <div id="hsn-chapter-results" class="tm-hsn-results" hidden></div>
                            </div>
                            <small class="text-muted d-block">{{ translate('2-digit HSN chapters. Picking one fills HSN, HS Code, and description.') }}</small>
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
                            <textarea name="description" id="description" class="form-control form-control-sm tm-description" maxlength="255" rows="3">{{ old('description', optional($t)->description) }}</textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h6 class="mt-2 mb-2">{{ translate('Purchase Tax') }}</h6>
        <div class="table-responsive mb-2">
            <table class="table table-bordered table-sm mb-0 tm-rate-grid" data-side="purchase">
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
                        <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="purchase_tax" id="purchase_tax" class="form-control form-control-sm tm-tax" value="{{ $rate('purchase_tax') }}" readonly></td>
                        <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="purchase_cgst" id="purchase_cgst" class="form-control form-control-sm" value="{{ $rate('purchase_cgst') }}" readonly></td>
                        <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="purchase_sgst" id="purchase_sgst" class="form-control form-control-sm" value="{{ $rate('purchase_sgst') }}" readonly></td>
                        <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="purchase_ut_cgst" id="purchase_ut_cgst" class="form-control form-control-sm tm-ut" value="{{ $rate('purchase_ut_cgst') }}"></td>
                        <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="purchase_utgst" id="purchase_utgst" class="form-control form-control-sm tm-ut" value="{{ $rate('purchase_utgst') }}"></td>
                        <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="purchase_igst" id="purchase_igst" class="form-control form-control-sm" value="{{ $rate('purchase_igst') }}" readonly></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <small class="text-danger tm-mismatch d-none mb-3 d-block" data-side="purchase">{{ translate('UT CGST % + UTGST % must equal Tax %.') }}</small>

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
                    <small class="text-muted d-block mt-1">{{ translate('Yes copies purchase GST into sale and keeps the sale table closed. No shows the sale table with the same Tax %, which you can edit. Default is Yes.') }}</small>
                    <button type="button" id="sale-tax-toggle" class="btn btn-link btn-sm px-0 {{ $same ? '' : 'd-none' }}">{{ translate('Show sale tax') }}</button>
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

        <div id="sale-tax-panel" class="{{ $same ? 'd-none' : '' }}">
            <h6 class="mt-2 mb-2">{{ translate('Sale Tax') }}</h6>
            <div class="table-responsive mb-2">
                <table class="table table-bordered table-sm mb-0 tm-rate-grid" data-side="sale">
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
                            <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="sale_tax" id="sale_tax" class="form-control form-control-sm tm-tax" value="{{ $rate('sale_tax') }}" readonly></td>
                            <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="sale_cgst" id="sale_cgst" class="form-control form-control-sm" value="{{ $rate('sale_cgst') }}" readonly></td>
                            <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="sale_sgst" id="sale_sgst" class="form-control form-control-sm" value="{{ $rate('sale_sgst') }}" readonly></td>
                            <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="sale_ut_cgst" id="sale_ut_cgst" class="form-control form-control-sm tm-ut" value="{{ $rate('sale_ut_cgst') }}"></td>
                            <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="sale_utgst" id="sale_utgst" class="form-control form-control-sm tm-ut" value="{{ $rate('sale_utgst') }}"></td>
                            <td><input type="number" lang="en" step="0.0001" min="0" max="100" name="sale_igst" id="sale_igst" class="form-control form-control-sm" value="{{ $rate('sale_igst') }}" readonly></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <small class="text-danger tm-mismatch d-none mb-3 d-block" data-side="sale">{{ translate('UT CGST % + UTGST % must equal Tax %.') }}</small>
        </div>
        <small class="text-muted d-block mb-3">{{ translate('State CGST and SGST split Tax % in half. IGST copies Tax %. UT CGST and UTGST start as the same split and stay editable.') }}</small>

        <h6 class="mt-2 mb-3">{{ translate('Applied On') }}</h6>
        <div class="table-responsive mb-3 tm-applied-wrap">
            <table class="table table-bordered table-sm mb-0 tm-identity-grid tm-applied-grid">
                <thead>
                    <tr>
                        <th>{{ translate('Category') }}</th>
                        <th>{{ translate('Group') }}</th>
                        <th>{{ translate('SKU') }}</th>
                        <th>{{ translate('Product Name') }}</th>
                        <th>{{ translate('Variant') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="applied_on_category" id="applied_on_category" class="form-control form-control-sm">
                                <option value="{{ $all['category'] }}" @selected($categoryValue === $all['category'])>{{ translate('All Category') }}</option>
                                @foreach ($categories as $category)
                                    @php $categoryName = $category->getTranslation('name'); @endphp
                                    <option value="{{ $categoryName }}" @selected($categoryValue === $categoryName)>{{ $categoryName }}</option>
                                @endforeach
                                @if ($categoryValue !== $all['category'] && !in_array($categoryValue, $categoryNames, true))
                                    <option value="{{ $categoryValue }}" selected>{{ $categoryValue }}</option>
                                @endif
                            </select>
                        </td>
                        <td>
                            @if (!empty($groupReady))
                                <select name="applied_on_group" id="applied_on_group" class="form-control form-control-sm">
                                    <option value="{{ $all['group'] }}" @selected($groupValue === $all['group'])>{{ translate('All Group') }}</option>
                                    @foreach ($groups as $group)
                                        @php $groupName = $group->getTranslation('name'); @endphp
                                        <option value="{{ $groupName }}" @selected($groupValue === $groupName)>{{ $groupName }}</option>
                                    @endforeach
                                    @if ($groupValue !== $all['group'] && !in_array($groupValue, $groupNames, true))
                                        <option value="{{ $groupValue }}" selected>{{ $groupValue }}</option>
                                    @endif
                                </select>
                            @else
                                <input type="text" class="form-control form-control-sm" value="{{ translate('All Group') }}" readonly>
                            @endif
                        </td>
                        <td>
                            <div class="tm-combo tm-hsn-wrap">
                                <select name="applied_on_sku" id="applied_on_sku" class="tm-combo-select" tabindex="-1" aria-hidden="true">
                                    <option value="{{ $all['sku'] }}" @selected($skuValue === $all['sku'])>{{ translate('All SKU') }}</option>
                                    @if ($skuValue !== $all['sku'])
                                        <option value="{{ $skuValue }}" selected>{{ $skuValue }}</option>
                                    @endif
                                </select>
                                <input type="text" class="form-control form-control-sm tm-combo-input" data-mode="sku" data-for="applied_on_sku" placeholder="{{ translate('All SKU') }}" autocomplete="off">
                                <button type="button" class="tm-combo-caret" tabindex="-1" aria-label="{{ translate('SKU') }}"></button>
                                <div id="applied-sku-results" class="tm-hsn-results tm-stock-results" hidden></div>
                            </div>
                        </td>
                        <td>
                            <div class="tm-combo tm-hsn-wrap">
                                <select name="applied_on_product" id="applied_on_product" class="tm-combo-select" tabindex="-1" aria-hidden="true">
                                    <option value="{{ $all['product'] }}" @selected($productValue === $all['product'])>{{ translate('All Product') }}</option>
                                    @if ($productValue !== $all['product'])
                                        <option value="{{ $productValue }}" selected>{{ $productValue }}</option>
                                    @endif
                                </select>
                                <input type="text" class="form-control form-control-sm tm-combo-input" data-mode="product" data-for="applied_on_product" placeholder="{{ translate('All Product') }}" autocomplete="off">
                                <button type="button" class="tm-combo-caret" tabindex="-1" aria-label="{{ translate('Product Name') }}"></button>
                                <div id="applied-product-results" class="tm-hsn-results tm-stock-results" hidden></div>
                            </div>
                        </td>
                        <td>
                            <div class="tm-combo tm-hsn-wrap">
                                <select name="applied_on_variant" id="applied_on_variant" class="tm-combo-select" tabindex="-1" aria-hidden="true">
                                    <option value="{{ $all['variant'] }}" @selected($variantValue === $all['variant'])>{{ translate('All Variant') }}</option>
                                    @if ($variantValue !== $all['variant'])
                                        <option value="{{ $variantValue }}" selected>{{ $variantValue }}</option>
                                    @endif
                                </select>
                                <input type="text" class="form-control form-control-sm tm-combo-input" data-mode="variant" data-for="applied_on_variant" placeholder="{{ translate('All Variant') }}" autocomplete="off">
                                <button type="button" class="tm-combo-caret" tabindex="-1" aria-label="{{ translate('Variant') }}"></button>
                                <div id="applied-variant-results" class="tm-hsn-results tm-stock-results" hidden></div>
                            </div>
                        </td>
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
    .tm-identity-wrap.table-responsive, .tm-applied-wrap.table-responsive { overflow: visible; }
    .tm-applied-grid { table-layout: fixed; width: 100%; }
    .tm-applied-grid select, .tm-applied-grid input { width: 100%; max-width: 100%; }
    .tm-combo-select { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    .tm-combo-input { padding-right: 1.6rem; }
    .tm-combo-caret {
        position: absolute;
        top: 0;
        right: 0;
        width: 1.6rem;
        height: 31px;
        border: 0;
        background: transparent;
        padding: 0;
    }
    .tm-combo-caret::before {
        content: '';
        display: inline-block;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 5px solid #6c757d;
    }
    .tm-description { min-height: 58px; resize: vertical; white-space: pre-wrap; overflow-wrap: anywhere; }
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
        white-space: normal;
    }
    .tm-hsn-results button:hover { background: #f3f6f9; }
</style>
