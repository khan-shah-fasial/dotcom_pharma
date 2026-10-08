@php
    $b = $batch;
    $isEdit = (bool) $b;
    $extended = $extended ?? false;
    $companies = $companies ?? collect();
    $showPurchaseRate = $showPurchaseRate ?? false;
    $isNonBatch = old('is_non_batch', optional($b)->is_non_batch ?? false);
    $isNonBatch = in_array($isNonBatch, [true, 1, '1'], true);
    $status = old('status', optional($b)->status ?? true);
    $stock = $b ? $b->stock : null;
    $skuLabel = '';
    if ($stock) {
        $skuLabel = trim(optional($b->product)->name . ' / ' . ($stock->sku ?: $stock->variant));
    }
    $postedRows = old('rows');
    if (!is_array($postedRows)) {
        $postedRows = $b ? [[
            'id' => $b->id,
            'batch_code' => $b->batch_code,
            'manufacturing_date' => $b->monthValue('manufacturing_date'),
            'expiry_date' => $b->monthValue('expiry_date'),
            'qty' => $b->qty,
            'free_qty' => $b->free_qty,
            'mrp_price' => $b->mrp_price,
            'purchase_rate' => $b->purchase_rate,
            'tax_code' => $b->tax_code,
            'tax_percent' => $b->tax_percent,
            'scheme' => $b->scheme,
            'company_id' => $b->company_id,
            'coa' => $b->coa,
            'price_pts' => $b->rolePrices()['pts'] ?? '',
            'price_ptr' => $b->rolePrices()['ptr'] ?? '',
            'price_ptd' => $b->rolePrices()['ptd'] ?? '',
            'price_gov' => $b->rolePrices()['gov'] ?? '',
            'price_expo' => $b->rolePrices()['expo'] ?? '',
            'price_customer' => $b->rolePrices()['customer'] ?? '',
            'source_purchase_history_id' => $b->source_purchase_history_id,
            'source_product_batch_id' => $b->source_product_batch_id,
            'batch_discount_percent' => $b->batch_discount_percent,
            'product_discount_percent' => $b->product_discount_percent,
            'scheme_discount_percent' => $b->scheme_discount_percent,
        ]] : [[]];
    }
    $selectedImport = collect(old('import_by_ids', $b ? (json_decode((string) ($b->import_by_ids ?? '[]'), true) ?: []) : []))->map(fn ($id) => (string) $id);
    $selectedMfg = collect(old('manufactured_by_ids', $b ? (json_decode((string) ($b->manufactured_by_ids ?? '[]'), true) ?: []) : []))->map(fn ($id) => (string) $id);
    $companyOptions = function ($selected) use ($companies) {
        $selectedIds = collect(is_array($selected) ? $selected : [$selected])->map(fn ($id) => (string) $id)->filter(fn ($id) => $id !== '');
        $html = '<option value="">' . e(translate('Select')) . '</option>';
        foreach ($companies as $company) {
            $label = trim(($company->code ? $company->code . ' — ' : '') . $company->company_name);
            $html .= '<option value="' . e($company->id) . '" ' . ($selectedIds->contains((string) $company->id) ? 'selected' : '') . '>' . e($label) . '</option>';
        }
        return $html;
    };
@endphp

<div class="card bm-add-card">
    <div class="card-header py-2">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 h6">{{ $isEdit ? translate('Edit Batch / Lot') : translate('Add New Batch / Lot') }}</h5>
                <p class="text-muted mb-0 fs-12">{{ translate('This screen saves to Batch / Lot Master only. Live product lots and stock qty are not changed.') }}</p>
            </div>
            <div class="d-flex align-items-center">
                <span class="mr-2 fs-12">{{ translate('Status') }}</span>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" name="status" value="1" {{ $status ? 'checked' : '' }}>
                    <span class="slider round"></span>
                </label>
            </div>
        </div>
    </div>
    <div class="card-body">
        @if (!$extended)
            <div class="alert alert-warning">
                {{ translate('The new columns are not on the table yet. Run sqlupdates/batch_lot_master_extend.sql before saving. Nothing will be written until then.') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <input type="hidden" name="product_id" id="product_id" value="{{ old('product_id', optional($b)->product_id) }}">
        <input type="hidden" name="product_stock_id" id="product_stock_id" value="{{ old('product_stock_id', optional($b)->product_stock_id) }}">
        <input type="hidden" name="drug_name" id="drug_name" value="{{ old('drug_name', optional($b)->drug_name) }}">
        <input type="hidden" name="marketed_by_id" id="marketed_by_id" value="{{ old('marketed_by_id', optional($b)->marketed_by_id) }}">
        <input type="hidden" name="import_by_names" id="import_by_names" value="{{ old('import_by_names', optional($b)->import_by_names) }}">
        <input type="hidden" name="manufactured_by_names" id="manufactured_by_names" value="{{ old('manufactured_by_names', optional($b)->manufactured_by_names) }}">
        <input type="hidden" name="purchase_date" id="purchase_date" value="{{ old('purchase_date') }}">

        <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm mb-0 bm-header-grid">
                <thead>
                    <tr>
                        <th style="width:26%">{{ translate('Search By SKU - Product Name / Brand Name With Full Variant') }} <span class="text-danger">*</span></th>
                        <th style="width:10%">{{ translate('SKU') }}</th>
                        <th style="width:14%">{{ translate('Full Detailed Variant') }}</th>
                        <th style="width:12%">{{ translate('Marketed By') }}</th>
                        <th style="width:14%">{{ translate('Import By') }}</th>
                        <th style="width:14%">{{ translate('Mfg. By') }}</th>
                        <th style="width:10%">{{ translate('Non-batch') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="bm-sku-cell">
                            <input type="text" class="form-control form-control-sm" id="sku_search" value="{{ old('sku_search', $skuLabel) }}" placeholder="{{ translate('Search SKU, product, brand, or variant') }}" autocomplete="off" {{ $isEdit ? 'readonly' : '' }}>
                            <div class="list-group" id="sku_results"></div>
                            <small class="text-muted d-block mt-1" id="drug_name_label">{{ old('drug_name', optional($b)->drug_name) }}</small>
                        </td>
                        <td><input type="text" class="form-control form-control-sm" id="sku_display" value="{{ optional($stock)->sku }}" readonly></td>
                        <td><input type="text" class="form-control form-control-sm" id="variant_display" value="{{ optional($stock)->variant }}" readonly></td>
                        <td><input type="text" class="form-control form-control-sm" id="marketed_by_name" name="marketed_by_name" value="{{ old('marketed_by_name', optional($b)->marketed_by_name) }}" readonly></td>
                        <td>
                            <select class="form-control form-control-sm" name="import_by_ids[]" id="import_by_ids" multiple>
                                {!! $companyOptions($selectedImport->all()) !!}
                            </select>
                        </td>
                        <td>
                            <select class="form-control form-control-sm" name="manufactured_by_ids[]" id="manufactured_by_ids" multiple>
                                {!! $companyOptions($selectedMfg->all()) !!}
                            </select>
                        </td>
                        <td>
                            <label class="mb-1 d-block">
                                <input type="hidden" name="is_non_batch" value="0">
                                <input type="checkbox" name="is_non_batch" id="is_non_batch" value="1" {{ $isNonBatch ? 'checked' : '' }}>
                                {{ translate('Checked if product is non-batch') }}
                            </label>
                            <small class="text-muted d-block fs-11">{{ translate('Empty batch code follows purchase date, for example 01-10-2026 becomes 20261001. The checkbox is remembered. Manufacturing date follows the purchase date. Expiry is left as it is.') }}</small>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-2" id="batch-rows-table">
                <thead>
                    <tr>
                        <th style="width:6%">{{ translate('Sr.No') }}</th>
                        <th style="width:11%">{{ translate('Batch Code') }} *</th>
                        <th style="width:8%">{{ translate('Mfg.') }}</th>
                        <th style="width:8%">{{ translate('Expiry') }}</th>
                        <th style="width:7%">{{ translate('Qty') }}</th>
                        <th style="width:7%">{{ translate('Free') }}</th>
                        <th style="width:8%">{{ translate('MRP') }}</th>
                        <th style="width:8%">{{ translate('P-Rate') }}</th>
                        <th style="width:7%">{{ translate('Tax') }}</th>
                        <th style="width:8%">{{ translate('Amount') }}</th>
                        <th style="width:12%">{{ translate('C-Code (Mfg By)') }}</th>
                        <th style="width:10%">{{ translate('COA Upload') }}</th>
                    </tr>
                </thead>
                <tbody id="batch-rows-body">
                    @foreach ($postedRows as $index => $row)
                        @php
                            $name = $isEdit ? '' : 'rows[' . $index . ']';
                            $field = function ($key) use ($isEdit, $name) {
                                return $isEdit ? $key : $name . '[' . $key . ']';
                            };
                            $qty = $row['qty'] ?? 0;
                            $rate = $row['purchase_rate'] ?? null;
                            $amount = ($rate === null || $rate === '') ? '' : round((float) $qty * (float) $rate, 4);
                        @endphp
                        <tr class="batch-entry-row">
                            <td class="sr-no">{{ $index + 1 }}</td>
                            <td>
                                @if (!$isEdit && !empty($row['id']))
                                    <input type="hidden" name="{{ $field('id') }}" value="{{ $row['id'] }}">
                                @endif
                                <input type="hidden" name="{{ $field('source_coa') }}" value="{{ $row['source_coa'] ?? '' }}">
                                <input type="hidden" name="{{ $field('source_purchase_history_id') }}" value="{{ $row['source_purchase_history_id'] ?? '' }}">
                                <input type="hidden" name="{{ $field('source_product_batch_id') }}" value="{{ $row['source_product_batch_id'] ?? '' }}">
                                <input type="hidden" name="{{ $field('tax_code') }}" class="tax-code" value="{{ $row['tax_code'] ?? '' }}">
                                <input type="hidden" name="{{ $field('scheme') }}" value="{{ $row['scheme'] ?? '' }}">
                                <input type="hidden" name="{{ $field('batch_discount_percent') }}" value="{{ $row['batch_discount_percent'] ?? '' }}">
                                <input type="hidden" name="{{ $field('product_discount_percent') }}" value="{{ $row['product_discount_percent'] ?? '' }}">
                                <input type="hidden" name="{{ $field('scheme_discount_percent') }}" value="{{ $row['scheme_discount_percent'] ?? '' }}">
                                @foreach (['pts' => 'price_pts', 'ptr' => 'price_ptr', 'ptd' => 'price_ptd', 'gov' => 'price_gov', 'expo' => 'price_expo', 'customer' => 'price_customer'] as $key => $column)
                                    <input type="hidden" name="{{ $field($column) }}" value="{{ $row[$column] ?? '' }}">
                                @endforeach
                                <input type="text" name="{{ $field('batch_code') }}" class="form-control form-control-sm batch-code" value="{{ $row['batch_code'] ?? '' }}" required>
                            </td>
                            <td><input type="month" name="{{ $field('manufacturing_date') }}" class="form-control form-control-sm mfg-input" value="{{ $row['manufacturing_date'] ?? '' }}"></td>
                            <td><input type="month" name="{{ $field('expiry_date') }}" class="form-control form-control-sm" value="{{ $row['expiry_date'] ?? '' }}"></td>
                            <td><input type="number" lang="en" step="0.001" min="0" name="{{ $field('qty') }}" class="form-control form-control-sm qty-input" value="{{ $qty }}"></td>
                            <td><input type="number" lang="en" step="0.001" min="0" name="{{ $field('free_qty') }}" class="form-control form-control-sm" value="{{ $row['free_qty'] ?? '' }}"></td>
                            <td><input type="number" lang="en" step="0.01" min="0" name="{{ $field('mrp_price') }}" class="form-control form-control-sm" value="{{ $row['mrp_price'] ?? '' }}"></td>
                            <td><input type="number" lang="en" step="0.0001" min="0" name="{{ $field('purchase_rate') }}" class="form-control form-control-sm rate-input" value="{{ $row['purchase_rate'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $field('tax_percent') }}" class="form-control form-control-sm" value="{{ $row['tax_percent'] ?? '' }}" readonly></td>
                            <td><input type="text" class="form-control form-control-sm amount-output" value="{{ $amount }}" readonly></td>
                            <td>
                                <select name="{{ $field('company_id') }}" class="form-control form-control-sm company-select">
                                    {!! $companyOptions($row['company_id'] ?? '') !!}
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="{{ $field('coa') }}" value="{{ $row['coa'] ?? '' }}">
                                <input type="file" name="{{ $isEdit ? 'coa_file' : $field('coa_file') }}" class="form-control-file">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-muted fs-12 mb-0">{{ translate('All data is copied from the purchase entry. You can change it here. Later changes stay on this screen.') }}</p>
            @if (!$isEdit)
                <button type="button" class="btn btn-soft-primary btn-sm" id="add-batch-row">{{ translate('Add Batch') }}</button>
            @endif
        </div>

        <div class="bm-live-section">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h6 class="mb-0">{{ translate('Current live lots (read only)') }}</h6>
                    <p class="text-muted fs-12 mb-0">{{ translate('Display purpose only. Shown from existing product lots.') }}</p>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="toggle-live-lots">{{ translate('Open/Close') }}</button>
            </div>
            <div id="live-lots-wrap" class="d-none">
                <div id="live-lots-blocks"></div>
                @if (!$showPurchaseRate)
                    <button type="button" class="btn btn-sm btn-light mt-2" id="reveal-prate">{{ translate('Show P-Rate') }}</button>
                    <div class="mt-2 d-none" id="reveal-prate-box">
                        <input type="password" class="form-control form-control-sm d-inline-block" style="max-width:220px" id="reveal-prate-password" placeholder="{{ translate('Your login password') }}">
                        <button type="button" class="btn btn-sm btn-primary" id="reveal-prate-submit">{{ translate('Open') }}</button>
                    </div>
                @endif
            </div>
        </div>

        <div class="text-right mt-3">
            <button type="submit" class="btn btn-primary" {{ $extended ? '' : 'disabled' }}>{{ translate('Save') }}</button>
        </div>
    </div>
</div>

<template id="batch-row-template">
    <tr class="batch-entry-row">
        <td class="sr-no"></td>
        <td>
            <input type="hidden" data-name="source_coa" value="">
            <input type="hidden" data-name="source_purchase_history_id" value="">
            <input type="hidden" data-name="source_product_batch_id" value="">
            <input type="hidden" data-name="tax_code" class="tax-code" value="">
            <input type="hidden" data-name="scheme" value="">
            <input type="hidden" data-name="batch_discount_percent" value="">
            <input type="hidden" data-name="product_discount_percent" value="">
            <input type="hidden" data-name="scheme_discount_percent" value="">
            <input type="hidden" data-name="price_pts" value="">
            <input type="hidden" data-name="price_ptr" value="">
            <input type="hidden" data-name="price_ptd" value="">
            <input type="hidden" data-name="price_gov" value="">
            <input type="hidden" data-name="price_expo" value="">
            <input type="hidden" data-name="price_customer" value="">
            <input type="text" data-name="batch_code" class="form-control form-control-sm batch-code" required>
        </td>
        <td><input type="month" data-name="manufacturing_date" class="form-control form-control-sm mfg-input"></td>
        <td><input type="month" data-name="expiry_date" class="form-control form-control-sm"></td>
        <td><input type="number" lang="en" step="0.001" min="0" data-name="qty" class="form-control form-control-sm qty-input" value="0"></td>
        <td><input type="number" lang="en" step="0.001" min="0" data-name="free_qty" class="form-control form-control-sm"></td>
        <td><input type="number" lang="en" step="0.01" min="0" data-name="mrp_price" class="form-control form-control-sm"></td>
        <td><input type="number" lang="en" step="0.0001" min="0" data-name="purchase_rate" class="form-control form-control-sm rate-input"></td>
        <td><input type="text" data-name="tax_percent" class="form-control form-control-sm" readonly></td>
        <td><input type="text" class="form-control form-control-sm amount-output" readonly></td>
        <td>
            <select data-name="company_id" class="form-control form-control-sm company-select">
                {!! $companyOptions('') !!}
            </select>
        </td>
        <td>
            <input type="hidden" data-name="coa" value="">
            <input type="file" data-name="coa_file" class="form-control-file">
        </td>
    </tr>
</template>

<style>
    .bm-header-grid th { background: #f3f6f9; font-size: 12px; white-space: normal; vertical-align: bottom; }
    .bm-header-grid td { vertical-align: top; }
    .bm-header-grid select[multiple] { min-height: 78px; }
    .bm-sku-cell { position: relative; }
    #sku_results { position: absolute; z-index: 30; left: 8px; right: 8px; max-height: 220px; overflow: auto; }
    .bm-live-lot { border: 1px solid #e3e6ea; margin-bottom: 14px; background: #fff; }
    .bm-live-lot table { margin-bottom: 0; }
    .bm-live-lot-values { background: #fafbfc; border-top: 1px solid #e3e6ea; }
    .bm-live-lot th, .bm-live-lot td { font-size: 12px; white-space: nowrap; }
    .bm-pv { line-height: 1.25; }
</style>
