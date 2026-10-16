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
    $rate = function ($field, $default = 0) use ($t) {
        $value = old($field, optional($t)->$field ?? $default);
        return \App\Models\TaxMaster::formatRate($value);
    };
    $taxPercent = $rate('purchase_tax');
    $appliedTypeOptions = [];
    foreach (\App\Models\TaxMaster::APPLIED_TYPES as $key => $label) {
        if ($key === 'group' && empty($groupReady)) {
            continue;
        }
        $appliedTypeOptions[$key] = $label;
    }
    $appliedType = old('applied_on_type');
    if (!is_string($appliedType) || !isset($appliedTypeOptions[$appliedType])) {
        $appliedType = $t ? \App\Models\TaxMaster::activeAppliedType($t) : 'category';
        if (!isset($appliedTypeOptions[$appliedType])) {
            $appliedType = array_key_first($appliedTypeOptions) ?: 'category';
        }
    }
    $postedTargets = old('applied_targets');
    if (is_array($postedTargets)) {
        $appliedSelected = \App\Models\TaxMaster::itemsFromTargetIds($appliedType, $postedTargets);
    } else {
        $appliedColumn = \App\Models\TaxMaster::APPLIED_COLUMNS[$appliedType] ?? 'applied_on_category';
        $appliedSelected = $t ? \App\Models\TaxMaster::presentAppliedItems($appliedType, $t->getAttribute($appliedColumn)) : [];
    }
    $legacyApplied = [];
    if ($t && !is_array($postedTargets)) {
        foreach (\App\Models\TaxMaster::APPLIED_COLUMNS as $key => $column) {
            if (\App\Models\TaxMaster::appliedItems($t->getAttribute($column)) !== []) {
                $legacyApplied[] = $key;
            }
        }
    }
    $appliedPlaceholders = [
        'category' => translate('Search categories'),
        'group' => translate('Search groups'),
        'sku' => translate('Search SKU'),
        'product' => translate('Search products'),
        'variant' => translate('Search variants'),
    ];
    $appliedHints = [
        'category' => translate('Categories only. You can select several categories, and nothing else.'),
        'group' => translate('Groups only. You can select several groups, and nothing else.'),
        'sku' => translate('SKUs only. Each row shows the SKU, its product name, and that variant. You can select several SKUs.'),
        'product' => translate('Whole products only. Each row shows that product with all of its variants, such as 20/3ml/40ml. You can select several products.'),
        'variant' => translate('Variants only, from any product. You can select several variants, and nothing else.'),
    ];
    $appliedEmptyNotes = [
        'category' => translate('None selected, so this tax applies to every category.'),
        'group' => translate('None selected, so this tax applies to every group.'),
        'sku' => translate('None selected, so this tax applies to every SKU.'),
        'product' => translate('None selected, so this tax applies to every product.'),
        'variant' => translate('None selected, so this tax applies to every variant.'),
    ];
    $appliedLabels = [];
    foreach ($appliedTypeOptions as $key => $label) {
        $appliedLabels[$key] = translate($label);
    }
    $appliedConfig = [
        'type' => $appliedType,
        'selected' => $appliedSelected,
        'placeholders' => $appliedPlaceholders,
        'hints' => $appliedHints,
        'emptyNotes' => $appliedEmptyNotes,
        'labels' => $appliedLabels,
        'lookupUrl' => route('tax_masters.lookup.targets'),
        'none' => translate('No matches'),
    ];
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
                            <small id="tax-code-existing" class="text-muted d-none">{{ translate('This tax type and tax % already exist. Saving updates that Tax ID. A different tax type keeps this code and gets a new Tax ID.') }}</small>
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
        @if (count($legacyApplied) > 1)
            <div class="alert alert-warning">
                {{ translate('This tax was saved against more than one kind. Choose one kind and save. The other kinds will be cleared.') }}
            </div>
        @endif
        <div class="row mb-3">
            <div class="col-md-3">
                <label for="applied_on_type">{{ translate('Apply on') }}</label>
                <select name="applied_on_type" id="applied_on_type" class="form-control form-control-sm">
                    @foreach ($appliedTypeOptions as $key => $label)
                        <option value="{{ $key }}" @selected($appliedType === $key)>{{ translate($label) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-9">
                <label for="applied-search" id="applied-target-label">{{ translate($appliedTypeOptions[$appliedType] ?? 'Category') }}</label>
                <div class="tm-multi tm-hsn-wrap" id="applied-multi">
                    <div class="tm-multi-control @error('applied_targets') is-invalid @enderror">
                        <div id="applied-picks" class="tm-multi-picks">
                            @foreach ($appliedSelected as $item)
                                <span class="tm-multi-chip">
                                    <span>{{ $item['label'] }}</span>
                                    <button type="button" data-id="{{ $item['id'] }}" aria-label="{{ translate('Remove') }}">&times;</button>
                                </span>
                            @endforeach
                        </div>
                        <input type="text" id="applied-search" class="tm-multi-input" placeholder="{{ $appliedPlaceholders[$appliedType] ?? translate('Search') }}" autocomplete="off">
                        <button type="button" class="tm-combo-caret" id="applied-caret" tabindex="-1" aria-label="{{ translate('Applied On') }}"></button>
                    </div>
                    <div id="applied-results" class="tm-hsn-results tm-multi-results" hidden></div>
                    <div id="applied-hidden">
                        @foreach ($appliedSelected as $item)
                            <input type="hidden" name="applied_targets[]" value="{{ $item['id'] }}">
                        @endforeach
                    </div>
                </div>
                <small class="text-muted d-block mt-1" id="applied-hint">{{ $appliedHints[$appliedType] ?? '' }}</small>
                <small class="text-muted d-block" id="applied-empty-note">{{ count($appliedSelected) ? '' : ($appliedEmptyNotes[$appliedType] ?? '') }}</small>
            </div>
        </div>
        <small class="text-muted d-block mb-3">{{ translate('Tax is applied later from the customer territory. International customers use the customer account form. These labels do not change live product tax rows.') }}</small>
        <script type="application/json" id="tm-applied-config">@json($appliedConfig)</script>

        <div class="text-right">
            <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
        </div>
    </div>
</div>
<style>
    .tm-identity-grid th, .tm-rate-grid th { background: #f3f6f9; font-size: 12px; white-space: normal; }
    .tm-identity-grid td, .tm-rate-grid td { vertical-align: top; }
    .tm-identity-wrap.table-responsive { overflow: visible; }
    .tm-multi-control {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px;
        min-height: 34px;
        border: 1px solid #e2e5ec;
        border-radius: 4px;
        padding: 3px 28px 3px 4px;
        background: #fff;
        position: relative;
    }
    .tm-multi-control.is-invalid { border-color: #dc3545; }
    .tm-multi-picks { display: contents; }
    .tm-multi-chip {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        background: #eef3f9;
        border: 1px solid #d5deea;
        border-radius: 3px;
        padding: 1px 2px 1px 6px;
        font-size: 12px;
        line-height: 1.4;
    }
    .tm-multi-chip span { overflow-wrap: anywhere; }
    .tm-multi-chip button {
        border: 0;
        background: transparent;
        color: #6c757d;
        font-size: 14px;
        line-height: 1;
        padding: 0 4px;
    }
    .tm-multi-input {
        border: 0 !important;
        box-shadow: none !important;
        flex: 1 1 140px;
        min-width: 140px;
        height: 24px;
        padding: 0 4px;
        background: transparent;
    }
    .tm-multi .tm-combo-caret { height: 100%; }
    .tm-multi-results { top: 100%; margin-top: 2px; }
    .tm-multi-results button.tm-multi-on { background: #e7f1ff; }
    .tm-multi-empty { padding: 6px 8px; font-size: 12px; color: #6c757d; }
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
