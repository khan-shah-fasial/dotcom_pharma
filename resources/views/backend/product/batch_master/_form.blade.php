@php
    $b = $batch;
    $isNonBatch = old('is_non_batch', optional($b)->is_non_batch ?? false);
    $isNonBatch = in_array($isNonBatch, [true, 1, '1'], true);
    $status = old('status', optional($b)->status ?? true);
    $prices = old('role_price', $b ? $b->rolePrices() : []);
    if (!is_array($prices)) {
        $prices = \App\Models\BatchMaster::decodeRolePrices($prices);
    }
    $stock = $b ? $b->stock : null;
    $skuLabel = '';
    if ($stock) {
        $skuLabel = trim(optional($b->product)->name . ' / ' . ($stock->sku ?: $stock->variant));
    }
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Batch / Lot Master') }}</h5>
        <p class="text-muted mb-0 fs-12">{{ translate('This screen saves to Batch / Lot Master only. Live product lots and stock qty are not changed.') }}</p>
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

        <input type="hidden" name="product_id" id="product_id" value="{{ old('product_id', optional($b)->product_id) }}">
        <input type="hidden" name="product_stock_id" id="product_stock_id" value="{{ old('product_stock_id', optional($b)->product_stock_id) }}">

        <div class="row">
            @if ($b)
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ translate('Batch ID') }}</label>
                        <input type="text" class="form-control" value="{{ $b->id }}" readonly>
                    </div>
                </div>
            @endif
            <div class="col-md-5">
                <div class="form-group">
                    <label>{{ translate('SKU / Product / Full Variant') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="sku_search" value="{{ $skuLabel }}" placeholder="{{ translate('Search SKU, product, or variant') }}" autocomplete="off">
                    <div class="list-group" id="sku_results"></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>{{ translate('SKU') }}</label>
                    <input type="text" class="form-control" id="sku_display" value="{{ optional($stock)->sku }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Full Variant') }}</label>
                    <input type="text" class="form-control" id="variant_display" value="{{ optional($stock)->variant }}" readonly>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="mb-0">
                <input type="hidden" name="is_non_batch" value="0">
                <input type="checkbox" name="is_non_batch" id="is_non_batch" value="1" {{ $isNonBatch ? 'checked' : '' }}>
                {{ translate('Non-batch') }}
            </label>
            <small class="text-muted d-block">{{ translate('If checked, batch code may be “-”. Auto batch codes on purchase / upload are not part of this master.') }}</small>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Batch Code') }} <span class="text-danger">*</span></label>
                    <input type="text" name="batch_code" id="batch_code" class="form-control" value="{{ old('batch_code', optional($b)->batch_code) }}">
                </div>
            </div>
            <div class="col-md-2 bm-date-field">
                <div class="form-group">
                    <label>{{ translate('Mfg Month') }}</label>
                    <input type="month" name="manufacturing_date" id="manufacturing_date" class="form-control" value="{{ old('manufacturing_date', $b ? $b->monthValue('manufacturing_date') : '') }}">
                </div>
            </div>
            <div class="col-md-2 bm-date-field">
                <div class="form-group">
                    <label>{{ translate('Expiry Month') }}</label>
                    <input type="month" name="expiry_date" id="expiry_date" class="form-control" value="{{ old('expiry_date', $b ? $b->monthValue('expiry_date') : '') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>{{ translate('MRP price') }}</label>
                    <input type="number" lang="en" step="0.01" min="0" name="mrp_price" class="form-control" value="{{ old('mrp_price', optional($b)->mrp_price) }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>{{ translate('Stock Qty') }}</label>
                    <input type="number" lang="en" step="0.001" min="0" name="qty" class="form-control" value="{{ old('qty', optional($b)->qty ?? 0) }}">
                </div>
            </div>
        </div>

        <h6 class="mt-2 mb-3">{{ translate('Stock Value As Per PTS / PTR / PTD / Govt. / Export / B2C') }}</h6>
        <div class="row">
            @foreach (\App\Models\BatchMaster::ROLE_KEYS as $key => $label)
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ translate($label) }}</label>
                        <input type="number" lang="en" step="0.0001" min="0" name="role_price[{{ $key }}]" class="form-control" value="{{ $prices[$key] ?? '' }}">
                    </div>
                </div>
            @endforeach
        </div>

        <div class="form-group">
            <label>{{ translate('Status') }}</label>
            <div>
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" name="status" value="1" {{ $status ? 'checked' : '' }}>
                    <span class="slider round"></span>
                </label>
            </div>
        </div>

        <div id="live-lots-wrap" class="d-none mb-3">
            <h6>{{ translate('Current live lots (read only)') }}</h6>
            <p class="text-muted fs-12 mb-1">{{ translate('Shown from existing product lots. Saving here does not change them.') }}</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Batch') }}</th>
                            <th>{{ translate('Mfg') }}</th>
                            <th>{{ translate('Expiry') }}</th>
                            <th>{{ translate('Qty') }}</th>
                            <th>{{ translate('MRP') }}</th>
                        </tr>
                    </thead>
                    <tbody id="live-lots-body"></tbody>
                </table>
            </div>
        </div>

        <div class="text-right">
            <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
        </div>
    </div>
</div>
