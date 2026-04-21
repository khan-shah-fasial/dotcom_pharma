<style>
    .badge {
        height: auto;
        width: auto;
        font-size: 16px;
    }

    .card .sku-card-header {
        background: #616161;
        border-bottom: 1px solid #e0e6ed;
    }
    .batch-table {
        border: 1px solid #e0e6ed;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 0;
    }
    .batch-table thead {
        background: #f8f9fa;
    }
    .batch-table thead th {
        font-weight: 600;
        font-size: 13px;
        color: #495057;
        padding: 12px 8px;
        border-bottom: 2px solid #dee2e6;
        white-space: nowrap;
        text-align: left;
        vertical-align: middle;
    }
    .batch-table thead th.text-center {
        text-align: center;
    }
    .batch-table tbody td {
        padding: 12px 8px;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    .batch-table tbody td.text-center {
        text-align: center;
    }
    .batch-table tbody tr:last-child td {
        border-bottom: none;
    }
    .batch-table tbody tr:hover {
        background-color: #f8f9fa;
    }
    .batch-table .form-control-sm {
        font-size: 13px;
        padding: 6px 10px;
        height: auto;
        line-height: 1.5;
    }
    .batch-table .form-control-sm:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    .coa-uploader-cell {
        min-width: 200px;
        max-width: 250px;
    }
    .coa-uploader-wrapper {
        position: relative;
    }
    .coa-uploader-wrapper .input-group {
        margin-bottom: 0;
    }
    .coa-uploader-wrapper .input-group-text {
        font-size: 11px;
        padding: 4px 8px;
        white-space: nowrap;
    }
    .coa-uploader-wrapper .form-control.file-amount {
        font-size: 11px;
        padding: 4px 8px;
        min-height: 28px;
    }
    .coa-uploader-wrapper .file-preview {
        margin-top: 5px;
        max-height: 60px;
        overflow-y: auto;
        font-size: 11px;
    }
    .batch-table .btn-xs {
        padding: 4px 8px;
        font-size: 12px;
        line-height: 1.2;
    }
</style>
@if (count($combinations) > 0)
    <div class="accordion" id="skuAccordionEdit">
        @foreach ($combinations as $key => $combination)
            @php
                $variation_available = false;
                $sku = '';
                foreach (explode(' ', $product_name) as $value) {
                    $sku .= substr($value, 0, 1);
                }

                $str = '';
                foreach ($combination as $index => $item) {
                    if ($index > 0) {
                        $str .= '-' . str_replace(' ', '', $item);
                        $sku .= '-' . str_replace(' ', '', $item);
                    } else {
                        if ($colors_active == 1) {
                            $color_name = \App\Models\Color::where('code', $item)->first()->name;
                            $str .= $color_name;
                            $sku .= '-' . $color_name;
                        } else {
                            $str .= str_replace(' ', '', $item);
                            $sku .= '-' . str_replace(' ', '', $item);
                        }
                    }
                    $stock = $product->stocks->where('variant', $str)->first();
                }
                $role_base_price = $stock ? json_decode($stock->role_price, true) : [];
                $isOpen = 'show';
            @endphp

            @if (strlen($str) > 0)
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center sku-card-header"
                        id="heading-edit-{{ $key }}">
                        <div class="d-flex align-items-center" style="gap:10px;">
                            <span class="badge badge-primary">{{ $str }}</span>
                        </div>
                        <button class="btn btn-sm btn-outline-warning" type="button" data-toggle="collapse"
                            data-target="#collapse-edit-{{ $key }}" aria-expanded="true"
                            aria-controls="collapse-edit-{{ $key }}">
                            {{ translate('Edit fields') }}
                        </button>
                    </div>

                    <div id="collapse-edit-{{ $key }}" class="collapse {{ $isOpen }}"
                        aria-labelledby="heading-edit-{{ $key }}">
                        <div class="card-body">
                            <div class="row gutters-10">
                                <div class="col-md-4">
                                    <div class="border rounded p-2 h-100">
                                        <h6 class="text-muted mb-3">{{ translate('Identifiers') }}</h6>
                                        <div class="form-group mb-3">
                                            <label class="form-label mb-1">{{ translate('SKU') }}</label>
                                            <input
                                                type="text"
                                                name="sku_{{ $str }}"
                                                value="{{ request('sku_'.$str, $stock->sku ?? '') }}"
                                                class="form-control"
                                                required
                                            >
                                        </div>

                                        <div class="col-sm-12 mb-2">
                                            <div class="d-flex align-items-start">
                                                <label class="aiz-switch aiz-switch-success mb-0 mr-2 mt-1">
                                                    <input
                                                        type="checkbox"
                                                        name="is_hidden_{{ $str }}"
                                                        value="1"
                                                        @if (request()->has('is_hidden_'.$str) ? true : (($stock->is_hidden ?? false) == true)) checked @endif
                                                    >
                                                    <span></span>
                                                </label>
                                                <div>
                                                    <label class="form-label mb-0 d-block">{{ translate('Hide Variant from Product Details') }}</label>
                                                    <small class="text-muted">{{ translate('Enable this to keep this variant unavailable on the product page.') }}</small>
                                                </div>
                                            </div>
                                        </div>


                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="border rounded p-2 h-100">
                                        <h6 class="text-muted mb-3">{{ translate('Inventory') }}</h6>

                                        <div class="form-row mb-3">
                                            <div class="col-sm-6 col-lg-4 mb-3 d-none">
                                                <label class="form-label mb-1">{{ translate('Selling Price') }}</label>
                                                <input
                                                    type="number"
                                                    lang="en"
                                                    name="price_{{ $str }}"
                                                    value="{{ request('price_'.$str, $stock && $stock->price !== null ? $stock->price : 0) }}"
                                                    min="0"
                                                    step="0.01"
                                                    class="form-control"
                                                    readonly
                                                >
                                            </div>
                                            <div class="col-sm-6 col-lg-4 mb-3">
                                                <label class="form-label mb-1">{{ translate('Min Order Qty') }}</label>
                                                <input
                                                    type="number"
                                                    lang="en"
                                                    name="min_qty_{{ $str }}"
                                                    value="{{ request('min_qty_'.$str, $stock && $stock->min_qty ? $stock->min_qty : 1) }}"
                                                    min="1"
                                                    step="1"
                                                    class="form-control"
                                                    required
                                                >
                                            </div>
                                            <div class="col-sm-6 col-lg-4 mb-3">
                                                <label class="form-label mb-1">{{ translate('Package Count') }}</label>
                                                <input
                                                    type="text"
                                                    name="count_{{ $str }}"
                                                    value="{{ request('count_'.$str, $stock->count ?? '') }}"
                                                    class="form-control"
                                                    placeholder="{{ translate('Package Count') }}"
                                                    required
                                                >
                                            </div>

                                            <div class="col-md-6 col-lg-4">
                                                <label class="form-label mb-1">{{ translate('Photo') }}</label>
                                                <div class="input-group" data-toggle="aizuploader" data-type="image">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text bg-soft-secondary font-weight-medium">
                                                            {{ translate('Browse') }}</div>
                                                    </div>
                                                    <div class="form-control file-amount text-truncate">
                                                        {{ translate('Choose File') }}</div>
                                                    <input
                                                        type="hidden"
                                                        name="img_{{ $str }}"
                                                        class="selected-files"
                                                        value="{{ request('img_'.$str, $stock->image ?? null) }}"
                                                    >
                                                </div>
                                                <div class="file-preview box sm"></div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="row gutters-10 mt-3">
                                <div class="col-lg-4">
                                    <div class="border rounded p-2 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="text-muted mb-0">{{ translate('Each Piece (Base)') }}</h6>
                                        </div>
                                        <div class="form-row">
                                            <div class="col-6 mb-3">
                                                <label class="form-label mb-1">{{ translate('Qty per Piece') }}</label>
                                                <input
                                                    type="number"
                                                    name="qty_per_piece_{{ $str }}"
                                                    value="{{ request('qty_per_piece_'.$str, $stock->qty_per_piece ?? '') }}"
                                                    class="form-control"
                                                    placeholder="{{ translate('Enter qty per piece (usually 1)') }}"
                                                    step="1"
                                                    min="0"
                                                >
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Weight Of Each Piece') }}</label>
                                                <input
                                                    type="number"
                                                    name="weight_{{ $str }}"
                                                    value="{{ request('weight_'.$str, $stock->weight ?? '') }}"
                                                    class="form-control"
                                                    placeholder="{{ translate('Weight per piece') }}"
                                                    step="0.001"
                                                    min="0"
                                                    required
                                                >
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Piece Length (cm)') }}</label>
                                                <input
                                                    type="number"
                                                    lang="en"
                                                    name="length_{{ $str }}"
                                                    value="{{ request('length_'.$str, $stock->length ?? '') }}"
                                                    class="form-control"
                                                    placeholder="{{ translate('Length (cm)') }}"
                                                    step="0.01"
                                                    min="0"
                                                    required
                                                >
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Piece Width (cm)') }}</label>
                                                <input
                                                    type="number"
                                                    lang="en"
                                                    name="width_{{ $str }}"
                                                    value="{{ request('width_'.$str, $stock->width ?? '') }}"
                                                    class="form-control"
                                                    placeholder="{{ translate('Width (cm)') }}"
                                                    step="0.01"
                                                    min="0"
                                                    required
                                                >
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Piece Height (cm)') }}</label>
                                                <input
                                                    type="number"
                                                    lang="en"
                                                    name="height_{{ $str }}"
                                                    value="{{ request('height_'.$str, $stock->height ?? '') }}"
                                                    class="form-control"
                                                    placeholder="{{ translate('Height (cm)') }}"
                                                    step="0.01"
                                                    min="0"
                                                    required
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="border rounded p-2 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="text-muted mb-0">
                                                {{ translate('Inner Buffer Box / Shrink Pack') }}</h6>
                                        </div>
                                        <div class="form-row">
                                            <div class="col-6 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Qty Per Inner Buffer Box / Shrink Pack') }}</label>
                                                <input
                                                    type="number"
                                                    name="qty_per_buffer_box_{{ $str }}"
                                                    value="{{ request('qty_per_buffer_box_'.$str, $stock->qty_per_buffer_box ?? '') }}"
                                                    class="form-control"
                                                    step="1"
                                                    min="0"
                                                    placeholder="{{ translate('Units per buffer box') }}"
                                                >
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Weight Of Inner Buffer Box / Shrink Pack') }}</label>
                                                <input
                                                    type="number"
                                                    name="weight_buffer_box_{{ $str }}"
                                                    value="{{ request('weight_buffer_box_'.$str, $stock->weight_buffer_box ?? '') }}"
                                                    class="form-control"
                                                    step="0.001"
                                                    min="0"
                                                    placeholder="{{ translate('Weight per buffer box') }}"
                                                >
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Buffer Length (cm)') }}</label>
                                                <input
                                                    type="number"
                                                    name="buffer_length_{{ $str }}"
                                                    value="{{ request('buffer_length_'.$str, $stock->buffer_length ?? '') }}"
                                                    class="form-control"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="{{ translate('Length') }}"
                                                >
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Buffer Width (cm)') }}</label>
                                                <input
                                                    type="number"
                                                    name="buffer_width_{{ $str }}"
                                                    value="{{ request('buffer_width_'.$str, $stock->buffer_width ?? '') }}"
                                                    class="form-control"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="{{ translate('Width') }}"
                                                >
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Buffer Height (cm)') }}</label>
                                                <input
                                                    type="number"
                                                    name="buffer_height_{{ $str }}"
                                                    value="{{ request('buffer_height_'.$str, $stock->buffer_height ?? '') }}"
                                                    class="form-control"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="{{ translate('Height') }}"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="border rounded p-2 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="text-muted mb-0">{{ translate('Outer Case/Shipper/Carton') }}
                                            </h6>
                                        </div>
                                        <div class="form-row">
                                            <div class="col-6 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Total Qty of Outer Case/Shipper/Carton') }}</label>
                                                <input
                                                    type="number"
                                                    name="total_qty_per_case_{{ $str }}"
                                                    value="{{ request('total_qty_per_case_'.$str, $stock->total_qty_per_case ?? '') }}"
                                                    class="form-control"
                                                    step="1"
                                                    min="0"
                                                    placeholder="{{ translate('Total units per case') }}"
                                                >
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Total Weight Of Outer Case/Shipper/Carton') }}</label>
                                                <input
                                                    type="number"
                                                    name="weight_case_{{ $str }}"
                                                    value="{{ request('weight_case_'.$str, $stock->weight_case ?? '') }}"
                                                    class="form-control"
                                                    step="0.001"
                                                    min="0"
                                                    placeholder="{{ translate('Weight per case') }}"
                                                >
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Case Length (cm)') }}</label>
                                                <input
                                                    type="number"
                                                    name="case_length_{{ $str }}"
                                                    value="{{ request('case_length_'.$str, $stock->case_length ?? '') }}"
                                                    class="form-control"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="{{ translate('Length') }}"
                                                >
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Case Width (cm)') }}</label>
                                                <input
                                                    type="number"
                                                    name="case_width_{{ $str }}"
                                                    value="{{ request('case_width_'.$str, $stock->case_width ?? '') }}"
                                                    class="form-control"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="{{ translate('Width') }}"
                                                >
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Case Height (cm)') }}</label>
                                                <input
                                                    type="number"
                                                    name="case_height_{{ $str }}"
                                                    value="{{ request('case_height_'.$str, $stock->case_height ?? '') }}"
                                                    class="form-control"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="{{ translate('Height') }}"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row gutters-10">

                                @php
                                    $variantKey = strtolower(str_replace(['.', ' ', '-'], '_', $str));
                                    $batches = $stock ? $stock->batches()->orderBy('id')->get() : collect();
                                @endphp

                                <!-- Batches section (bottom, with Role Base Price per batch) -->
                                <div class="col-12 mt-4">
                                    <div class="border rounded p-2 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="text-muted mb-0">{{ translate('Batches') }}</h6>
                                            <button type="button" class="btn btn-sm btn-soft-primary" onclick="addBatchRow('{{ $variantKey }}')">
                                                <i class="las la-plus"></i> {{ translate('Add Batch') }}
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table batch-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 12%;">{{ translate('Batch Code') }}</th>
                                                        <th style="width: 9%;">{{ translate('Mfg Month') }}</th>
                                                        <th style="width: 9%;">{{ translate('Expiry Month') }}</th>
                                                        <th style="width: 8%;">{{ translate('MRP Price') }}</th>
                                                        <th style="width: 8%;">{{ translate('Stock Qty') }}</th>
                                                        <th style="width: 8%;">{{ translate('Scheme') }}</th>
                                                        <th style="width: 7%;">{{ translate('Offer Active') }}</th>
                                                        <th style="width: 8%;">{{ translate('Discount Type') }}</th>
                                                        <th style="width: 8%;">{{ translate('Discount') }}</th>
                                                        <th style="width: 9%;">{{ translate('Offer Start') }}</th>
                                                        <th style="width: 9%;">{{ translate('Offer End') }}</th>
                                                        <th style="width: 12%;">{{ translate('COA Document') }}</th>
                                                        <th style="width: 8%;">{{ translate('Role Base Price') }}</th>
                                                        <th style="width: 3%;" class="text-center">{{ translate('Action') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="batch-rows-{{ $variantKey }}">
                                                    @if ($batches && $batches->count() > 0)
                                                        @foreach ($batches as $batchIndex => $batch)
                                                            @php
                                                                $batchRolePrice = $batch->role_price ? (is_string($batch->role_price) ? json_decode($batch->role_price, true) : $batch->role_price) : [];
                                                                $batchDiscountActive = (int) data_get(request()->input('batches', []), $variantKey.'.'.$batchIndex.'.discount_active', $batch->discount_active) === 1;
                                                                $batchDiscountType = data_get(request()->input('batches', []), $variantKey.'.'.$batchIndex.'.discount_type', $batch->discount_type);
                                                                $batchDiscountValue = data_get(request()->input('batches', []), $variantKey.'.'.$batchIndex.'.discount', $batch->discount);
                                                                $batchDiscountStartReq = data_get(request()->input('batches', []), $variantKey.'.'.$batchIndex.'.discount_start_date');
                                                                $batchDiscountEndReq = data_get(request()->input('batches', []), $variantKey.'.'.$batchIndex.'.discount_end_date');
                                                                $batchDiscountStart = $batchDiscountStartReq !== null
                                                                    ? $batchDiscountStartReq
                                                                    : (!empty($batch->discount_start_date) ? date('Y-m-d', (int) $batch->discount_start_date) : '');
                                                                $batchDiscountEnd = $batchDiscountEndReq !== null
                                                                    ? $batchDiscountEndReq
                                                                    : (!empty($batch->discount_end_date) ? date('Y-m-d', (int) $batch->discount_end_date) : '');
                                                            @endphp
                                                            <tr class="batch-row">
                                                                <td>
                                                                    <input type="hidden" name="batches[{{ $variantKey }}][{{ $batchIndex }}][id]" value="{{ $batch->id }}">
                                                                    <input type="text" name="batches[{{ $variantKey }}][{{ $batchIndex }}][batch]" class="form-control form-control-sm" value="{{ data_get(request()->input('batches', []), $variantKey.'.'.$batchIndex.'.batch', $batch->batch) }}" placeholder="{{ translate('Batch code') }}" required>
                                                                </td>
                                                                <td>
                                                                    @php
                                                                        $reqMfg = data_get(request()->input('batches', []), $variantKey.'.'.$batchIndex.'.manufacturing_date');
                                                                        $batchMfgValue = $reqMfg !== null
                                                                            ? $reqMfg
                                                                            : ($batch->manufacturing_date ? \Carbon\Carbon::parse($batch->manufacturing_date)->format('Y-m') : '');
                                                                    @endphp
                                                                    <input type="month" name="batches[{{ $variantKey }}][{{ $batchIndex }}][manufacturing_date]" value="{{ $batchMfgValue }}" class="form-control form-control-sm">
                                                                </td>
                                                                <td>
                                                                    @php
                                                                        $reqExpiry = data_get(request()->input('batches', []), $variantKey.'.'.$batchIndex.'.product_exp_date');
                                                                        $batchExpiryValue = $reqExpiry !== null
                                                                            ? $reqExpiry
                                                                            : ($batch->product_exp_date ? \Carbon\Carbon::parse($batch->product_exp_date)->format('Y-m') : '');
                                                                    @endphp
                                                                    <input type="month" name="batches[{{ $variantKey }}][{{ $batchIndex }}][product_exp_date]" value="{{ $batchExpiryValue }}" class="form-control form-control-sm">
                                                                </td>
                                                                <td>
                                                                    <input type="number" lang="en" name="batches[{{ $variantKey }}][{{ $batchIndex }}][mrp_price]" value="{{ data_get(request()->input('batches', []), $variantKey.'.'.$batchIndex.'.mrp_price', $batch->mrp_price) }}" min="0" step="0.01" class="form-control form-control-sm" required>
                                                                </td>
                                                                <td>
                                                                    <input type="number" lang="en" name="batches[{{ $variantKey }}][{{ $batchIndex }}][qty]" value="{{ data_get(request()->input('batches', []), $variantKey.'.'.$batchIndex.'.qty', $batch->qty) }}" min="0" step="1" class="form-control form-control-sm" required>
                                                                </td>
                                                                <td>
                                                                    <input type="number" lang="en" name="batches[{{ $variantKey }}][{{ $batchIndex }}][scheme]" value="{{ data_get(request()->input('batches', []), $variantKey.'.'.$batchIndex.'.scheme', $batch->scheme ?? '') }}" min="0" step="1" class="form-control form-control-sm" placeholder="{{ translate('Scheme') }}">
                                                                </td>
                                                                <td class="text-center">
                                                                    <input type="hidden" name="batches[{{ $variantKey }}][{{ $batchIndex }}][discount_active]" value="0">
                                                                    <input
                                                                        type="checkbox"
                                                                        name="batches[{{ $variantKey }}][{{ $batchIndex }}][discount_active]"
                                                                        value="1"
                                                                        class="batch-discount-active"
                                                                        onchange="toggleBatchDiscountFields(this)"
                                                                        {{ $batchDiscountActive ? 'checked' : '' }}
                                                                    >
                                                                </td>
                                                                <td>
                                                                    <select
                                                                        name="batches[{{ $variantKey }}][{{ $batchIndex }}][discount_type]"
                                                                        class="form-control form-control-sm batch-discount-type"
                                                                        {{ $batchDiscountActive ? '' : 'disabled' }}
                                                                        {{ $batchDiscountActive ? 'required' : '' }}
                                                                    >
                                                                        <option value="">{{ translate('Select') }}</option>
                                                                        <option value="percent" {{ $batchDiscountType === 'percent' ? 'selected' : '' }}>{{ translate('Percent') }}</option>
                                                                        <option value="flat" {{ $batchDiscountType === 'flat' ? 'selected' : '' }}>{{ translate('Flat') }}</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input
                                                                        type="number"
                                                                        lang="en"
                                                                        name="batches[{{ $variantKey }}][{{ $batchIndex }}][discount]"
                                                                        value="{{ $batchDiscountValue }}"
                                                                        min="0"
                                                                        step="0.01"
                                                                        class="form-control form-control-sm batch-discount-value"
                                                                        {{ $batchDiscountActive ? '' : 'disabled' }}
                                                                        {{ $batchDiscountActive ? 'required' : '' }}
                                                                    >
                                                                </td>
                                                                <td>
                                                                    <input
                                                                        type="date"
                                                                        name="batches[{{ $variantKey }}][{{ $batchIndex }}][discount_start_date]"
                                                                        value="{{ $batchDiscountStart }}"
                                                                        class="form-control form-control-sm batch-discount-start"
                                                                        {{ $batchDiscountActive ? '' : 'disabled' }}
                                                                    >
                                                                </td>
                                                                <td>
                                                                    <input
                                                                        type="date"
                                                                        name="batches[{{ $variantKey }}][{{ $batchIndex }}][discount_end_date]"
                                                                        value="{{ $batchDiscountEnd }}"
                                                                        class="form-control form-control-sm batch-discount-end"
                                                                        {{ $batchDiscountActive ? '' : 'disabled' }}
                                                                    >
                                                                </td>
                                                                <td class="coa-uploader-cell">
                                                                    <div class="coa-uploader-wrapper" id="coa-wrapper-{{ $variantKey }}-{{ $batchIndex }}">
                                                                        <div class="input-group" data-toggle="aizuploader" data-type="document">
                                                                            <div class="input-group-prepend">
                                                                                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                                                                            </div>
                                                                            <div class="form-control file-amount text-truncate">{{ $batch->coa ? translate('File Selected') : translate('Choose PDF') }}</div>
                                                                            <input type="hidden" name="batches[{{ $variantKey }}][{{ $batchIndex }}][coa]" class="selected-files" value="{{ data_get(request()->input('batches', []), $variantKey.'.'.$batchIndex.'.coa', $batch->coa ?? '') }}">
                                                                        </div>
                                                                        <div class="file-preview box sm"></div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    @php
                                                                        $batchRolePriceRequest = data_get(request()->input('batches', []), $variantKey.'.'.$batchIndex.'.role_price');
                                                                    @endphp
                                                                    <input type="hidden" name="batches[{{ $variantKey }}][{{ $batchIndex }}][role_price]" class="batch-role-price-input" value="{{ $batchRolePriceRequest ?? ($batch->role_price ?? '') }}">
                                                                    @if (!empty($batchRolePrice) && count($batchRolePrice) > 0)
                                                                        <div class="small">
                                                                            @foreach ($batchRolePrice as $role => $price)
                                                                                <span class="d-block text-nowrap">{{ strtoupper($role) }}: {{ $price }}</span>
                                                                            @endforeach
                                                                        </div>
                                                                    @else
                                                                        <small class="text-muted">{{ translate('Auto from Selling Price') }}</small>
                                                                    @endif
                                                                </td>
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-xs btn-soft-danger" onclick="removeBatchRow(this)" title="{{ translate('Remove') }}">
                                                                        <i class="las la-trash"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @else
                                                        <tr class="batch-row">
                                                            <td>
                                                                <input type="text" name="batches[{{ $variantKey }}][0][batch]" class="form-control form-control-sm" value="{{ data_get(request()->input('batches', []), $variantKey.'.0.batch', '') }}" placeholder="{{ translate('Batch code') }}" required>
                                                            </td>
                                                            <td>
                                                                <input type="month" name="batches[{{ $variantKey }}][0][manufacturing_date]" value="{{ data_get(request()->input('batches', []), $variantKey.'.0.manufacturing_date', '') }}" class="form-control form-control-sm">
                                                            </td>
                                                            <td>
                                                                <input type="month" name="batches[{{ $variantKey }}][0][product_exp_date]" value="{{ data_get(request()->input('batches', []), $variantKey.'.0.product_exp_date', $stock && $stock->product_exp_date ? \Carbon\Carbon::parse($stock->product_exp_date)->format('Y-m') : '') }}" class="form-control form-control-sm">
                                                            </td>
                                                            <td>
                                                                <input type="number" lang="en" name="batches[{{ $variantKey }}][0][mrp_price]" value="{{ data_get(request()->input('batches', []), $variantKey.'.0.mrp_price', $stock && $stock->mrp_price !== null ? $stock->mrp_price : $unit_price) }}" min="0" step="0.01" class="form-control form-control-sm" required>
                                                            </td>
                                                            <td>
                                                                <input type="number" lang="en" name="batches[{{ $variantKey }}][0][qty]" value="{{ data_get(request()->input('batches', []), $variantKey.'.0.qty', $stock && $stock->qty !== null ? $stock->qty : 10) }}" min="0" step="1" class="form-control form-control-sm" required>
                                                            </td>
                                                            <td>
                                                                <input type="number" lang="en" name="batches[{{ $variantKey }}][0][scheme]" value="{{ data_get(request()->input('batches', []), $variantKey.'.0.scheme', '') }}" min="0" step="1" class="form-control form-control-sm" placeholder="{{ translate('Scheme') }}">
                                                            </td>
                                                            @php
                                                                $defaultDiscountActive = (int) data_get(request()->input('batches', []), $variantKey.'.0.discount_active', 0) === 1;
                                                            @endphp
                                                            <td class="text-center">
                                                                <input type="hidden" name="batches[{{ $variantKey }}][0][discount_active]" value="0">
                                                                <input
                                                                    type="checkbox"
                                                                    name="batches[{{ $variantKey }}][0][discount_active]"
                                                                    value="1"
                                                                    class="batch-discount-active"
                                                                    onchange="toggleBatchDiscountFields(this)"
                                                                    {{ $defaultDiscountActive ? 'checked' : '' }}
                                                                >
                                                            </td>
                                                            <td>
                                                                <select
                                                                    name="batches[{{ $variantKey }}][0][discount_type]"
                                                                    class="form-control form-control-sm batch-discount-type"
                                                                    {{ $defaultDiscountActive ? '' : 'disabled' }}
                                                                    {{ $defaultDiscountActive ? 'required' : '' }}
                                                                >
                                                                    <option value="">{{ translate('Select') }}</option>
                                                                    <option value="percent" {{ data_get(request()->input('batches', []), $variantKey.'.0.discount_type') === 'percent' ? 'selected' : '' }}>{{ translate('Percent') }}</option>
                                                                    <option value="flat" {{ data_get(request()->input('batches', []), $variantKey.'.0.discount_type') === 'flat' ? 'selected' : '' }}>{{ translate('Flat') }}</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input
                                                                    type="number"
                                                                    lang="en"
                                                                    name="batches[{{ $variantKey }}][0][discount]"
                                                                    value="{{ data_get(request()->input('batches', []), $variantKey.'.0.discount', '') }}"
                                                                    min="0"
                                                                    step="0.01"
                                                                    class="form-control form-control-sm batch-discount-value"
                                                                    {{ $defaultDiscountActive ? '' : 'disabled' }}
                                                                    {{ $defaultDiscountActive ? 'required' : '' }}
                                                                >
                                                            </td>
                                                            <td>
                                                                <input
                                                                    type="date"
                                                                    name="batches[{{ $variantKey }}][0][discount_start_date]"
                                                                    value="{{ data_get(request()->input('batches', []), $variantKey.'.0.discount_start_date', '') }}"
                                                                    class="form-control form-control-sm batch-discount-start"
                                                                    {{ $defaultDiscountActive ? '' : 'disabled' }}
                                                                >
                                                            </td>
                                                            <td>
                                                                <input
                                                                    type="date"
                                                                    name="batches[{{ $variantKey }}][0][discount_end_date]"
                                                                    value="{{ data_get(request()->input('batches', []), $variantKey.'.0.discount_end_date', '') }}"
                                                                    class="form-control form-control-sm batch-discount-end"
                                                                    {{ $defaultDiscountActive ? '' : 'disabled' }}
                                                                >
                                                            </td>
                                                            <td class="coa-uploader-cell">
                                                                <div class="coa-uploader-wrapper" id="coa-wrapper-{{ $variantKey }}-0">
                                                                    <div class="input-group" data-toggle="aizuploader" data-type="document">
                                                                        <div class="input-group-prepend">
                                                                            <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                                                                        </div>
                                                                        <div class="form-control file-amount text-truncate">{{ ($stock->coa ?? '') ? translate('File Selected') : translate('Choose PDF') }}</div>
                                                                        <input type="hidden" name="batches[{{ $variantKey }}][0][coa]" class="selected-files" value="{{ $stock->coa ?? '' }}">
                                                                    </div>
                                                                    <div class="file-preview box sm"></div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <input type="hidden" name="batches[{{ $variantKey }}][0][role_price]" class="batch-role-price-input" value="{{ data_get(request()->input('batches', []), $variantKey.'.0.role_price', '') }}">
                                                                <small class="text-muted">{{ translate('Auto from MRP') }}</small>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-xs btn-soft-danger" onclick="removeBatchRow(this)" title="{{ translate('Remove') }}">
                                                                    <i class="las la-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                {{-- <div class="col-md-12 mt-3">
                                    <h6 class="text-muted mb-2">{{ translate('Role Base Price') }}</h6>
                                    @if (!empty($role_base_price) && count($role_base_price) > 0)
                                        <div class="accordion" id="rolePriceAccordion_{{ md5($str) }}">
                                            @php $collapseId = 'rolePriceCollapse_'.md5($str); @endphp
                                            <div class="card mb-1 border">
                                                <div class="card-header p-1" id="heading_{{ $collapseId }}">
                                                    <h2 class="mb-0">
                                                        <button class="btn btn-link btn-block text-left p-2"
                                                            type="button" data-toggle="collapse"
                                                            data-target="#{{ $collapseId }}" aria-expanded="true"
                                                            aria-controls="{{ $collapseId }}">
                                                            {{ translate('Role price') }}
                                                        </button>
                                                    </h2>
                                                </div>
                                                <div id="{{ $collapseId }}" class="collapse"
                                                    aria-labelledby="heading_{{ $collapseId }}"
                                                    data-parent="#rolePriceAccordion_{{ md5($str) }}">
                                                    <div class="card-body py-2">
                                                        <table class="table table-sm mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th class="text-sm text-gray-700">
                                                                        {{ translate('Role') }}</th>
                                                                    <th class="text-sm text-gray-700 text-right">
                                                                        {{ translate('Price') }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($role_base_price as $role => $price)
                                                                    <tr>
                                                                        <td class="text-sm text-gray-700">
                                                                            {{ strtoupper($role) }}</td>
                                                                        <td class="text-sm text-gray-700 text-right">
                                                                            {{ $price }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <p class="mb-0">{{ translate('No data') }}</p>
                                    @endif
                                </div> --}}

                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif

<script type="text/javascript">
    function addBatchRow(variantKey) {
        var $tbody = $('#batch-rows-' + variantKey);
        if ($tbody.length === 0) {
            return;
        }

        var index = $tbody.find('tr.batch-row').length;
        var wrapperId = 'coa-wrapper-' + variantKey + '-' + index;
        var rowHtml = `
            <tr class="batch-row">
                <td>
                    <input type="text"
                        name="batches[` + variantKey + `][` + index + `][batch]"
                        class="form-control form-control-sm"
                        placeholder="{{ translate('Batch code') }}"
                        required>
                </td>
                <td>
                    <input type="month"
                        name="batches[` + variantKey + `][` + index + `][manufacturing_date]"
                        class="form-control form-control-sm">
                </td>
                <td>
                    <input type="month"
                        name="batches[` + variantKey + `][` + index + `][product_exp_date]"
                        class="form-control form-control-sm">
                </td>
                <td>
                    <input type="number" lang="en"
                        name="batches[` + variantKey + `][` + index + `][mrp_price]"
                        min="0" step="0.01"
                        class="form-control form-control-sm"
                        required>
                </td>
                <td>
                    <input type="number" lang="en"
                        name="batches[` + variantKey + `][` + index + `][qty]"
                        min="0" step="1"
                        class="form-control form-control-sm"
                        required>
                </td>
                <td>
                    <input type="number" lang="en"
                        name="batches[` + variantKey + `][` + index + `][scheme]"
                        min="0" step="1"
                        class="form-control form-control-sm"
                        placeholder="{{ translate('Scheme') }}">
                </td>
                <td class="text-center">
                    <input type="hidden" name="batches[` + variantKey + `][` + index + `][discount_active]" value="0">
                    <input type="checkbox"
                        name="batches[` + variantKey + `][` + index + `][discount_active]"
                        value="1"
                        class="batch-discount-active"
                        onchange="toggleBatchDiscountFields(this)">
                </td>
                <td>
                    <select name="batches[` + variantKey + `][` + index + `][discount_type]"
                        class="form-control form-control-sm batch-discount-type"
                        disabled>
                        <option value="">{{ translate('Select') }}</option>
                        <option value="percent">{{ translate('Percent') }}</option>
                        <option value="flat">{{ translate('Flat') }}</option>
                    </select>
                </td>
                <td>
                    <input type="number" lang="en"
                        name="batches[` + variantKey + `][` + index + `][discount]"
                        min="0" step="0.01"
                        class="form-control form-control-sm batch-discount-value"
                        disabled>
                </td>
                <td>
                    <input type="date"
                        name="batches[` + variantKey + `][` + index + `][discount_start_date]"
                        class="form-control form-control-sm batch-discount-start"
                        disabled>
                </td>
                <td>
                    <input type="date"
                        name="batches[` + variantKey + `][` + index + `][discount_end_date]"
                        class="form-control form-control-sm batch-discount-end"
                        disabled>
                </td>
                <td class="coa-uploader-cell">
                    <div class="coa-uploader-wrapper" id="` + wrapperId + `">
                        <div class="input-group" data-toggle="aizuploader" data-type="document">
                            <div class="input-group-prepend">
                                <div class="input-group-text bg-soft-secondary font-weight-medium">
                                    {{ translate('Browse') }}
                                </div>
                            </div>
                            <div class="form-control file-amount text-truncate">
                                {{ translate('Choose PDF') }}
                            </div>
                            <input type="hidden" name="batches[` + variantKey + `][` + index + `][coa]" class="selected-files">
                        </div>
                        <div class="file-preview box sm"></div>
                    </div>
                </td>
                <td>
                    <input type="hidden" name="batches[` + variantKey + `][` + index + `][role_price]" class="batch-role-price-input" value="">
                    <small class="text-muted">{{ translate('Auto from MRP') }}</small>
                </td>
                <td class="text-center">
                    <button type="button"
                        class="btn btn-xs btn-soft-danger"
                        onclick="removeBatchRow(this)"
                        title="{{ translate('Remove') }}">
                        <i class="las la-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $tbody.append(rowHtml);
        toggleBatchDiscountFields($tbody.find('tr.batch-row:last .batch-discount-active')[0]);
        
        // Initialize aizuploader for the new row
        if (typeof AIZ !== 'undefined' && AIZ.uploader) {
            setTimeout(function() {
                AIZ.uploader.previewGenerate();
            }, 100);
        }
    }

    function removeBatchRow(el) {
        var $row = $(el).closest('tr.batch-row');
        var $tbody = $row.parent();
        
        // Check if this is the last row
        if ($tbody.find('tr.batch-row').length <= 1) {
            alert('{{ translate("At least one batch is required") }}');
            return;
        }
        
        // Extract variantKey from tbody ID
        var tbodyId = $tbody.attr('id');
        var variantKey = tbodyId.replace('batch-rows-', '');
        
        $row.remove();

        // Reindex remaining rows to keep names compact
        $tbody.find('tr.batch-row').each(function (i, tr) {
            var $tr = $(tr);
            $tr.find('input, select').each(function () {
                var name = $(this).attr('name');
                if (!name) return;
                name = name.replace(/\[\d+\]/, '[' + i + ']');
                $(this).attr('name', name);
            });
            
            // Update wrapper ID
            var $wrapper = $tr.find('.coa-uploader-wrapper');
            if ($wrapper.length) {
                var newWrapperId = 'coa-wrapper-' + variantKey + '-' + i;
                $wrapper.attr('id', newWrapperId);
            }
        });
    }

    function toggleBatchDiscountFields(el) {
        var $row = $(el).closest('tr.batch-row');
        var isActive = $(el).is(':checked');

        $row.find('.batch-discount-type')
            .prop('disabled', !isActive)
            .prop('required', isActive);
        $row.find('.batch-discount-value')
            .prop('disabled', !isActive)
            .prop('required', isActive);
        $row.find('.batch-discount-start').prop('disabled', !isActive);
        $row.find('.batch-discount-end').prop('disabled', !isActive);
    }

    // Initialize aizuploader for existing batches on page load
    $(document).ready(function() {
        $('.batch-discount-active').each(function () {
            toggleBatchDiscountFields(this);
        });

        if (typeof AIZ !== 'undefined' && AIZ.uploader) {
            setTimeout(function() {
                AIZ.uploader.previewGenerate();
            }, 500);
        }
    });
</script>
