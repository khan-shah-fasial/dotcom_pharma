@php
    $d = $discount;
    $appliedOn = old('applied_on', $d->applied_on ?? '');
    $discountType = old('discount_type', $d->discount_type ?? '');
    $roleKey = old('role_key', $d->role_key ?? '');
    $buildStockLabel = function ($product, $stock) {
        $label = optional($product)->name ?? '';
        if ($stock->sku) {
            $label .= ' / ' . $stock->sku;
        }
        if ($stock->hasExpandedVariantInfo()) {
            $label .= ' / ' . $stock->expandedVariantLabel();
        }
        if (!$stock->sku && !$stock->hasExpandedVariantInfo()) {
            $label .= ' / #' . $stock->id;
        }
        return trim($label);
    };

    $skuLabel = $d && $d->stock ? $buildStockLabel($d->product, $d->stock) : '';
    $variantLabel = $skuLabel;
    $batchLabel = $d && $d->batch ? trim(($d->batch->batch ?: '-') . ' / ' . optional($d->product)->name) : '';
    $customerLabel = $d ? $d->customerDisplayName() : '';
    $schemeLabel = $d && $d->schemeStock ? $buildStockLabel($d->schemeStock->product, $d->schemeStock) : '';
    if ($customerLabel === '—') {
        $customerLabel = '';
    }
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Discount Master') }}</h5>
        <p class="text-muted mb-0 fs-12">{{ translate('Fill fields left to right. Unused fields hide in place.') }}</p>
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
            <div class="col-md-4">
                <div class="form-group">
                    <label>{{ translate('Discount Applied On') }} <span class="text-danger">*</span></label>
                    <select name="applied_on" id="applied_on" class="form-control aiz-selectpicker" data-live-search="true">
                        <option value="">{{ translate('Select') }}</option>
                        @foreach (\App\Models\DiscountMaster::APPLIED_ON as $key => $label)
                            <option value="{{ $key }}" @selected($appliedOn === $key)>{{ translate($label) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="row dm-target-row">
            <div class="col-md-4 dm-field d-none" data-show-on="sku">
                <div class="form-group">
                    <label>{{ translate('SKU Product') }}</label>
                    <input type="text" class="form-control dm-typeahead" id="sku_search" data-mode="sku" data-target="product_stock_id" value="{{ $appliedOn === 'sku' ? $skuLabel : '' }}" placeholder="{{ translate('Search SKU') }}" autocomplete="off">
                    <div class="list-group dm-typeahead-results"></div>
                </div>
            </div>
            <div class="col-md-4 dm-field d-none" data-show-on="full_variant">
                <div class="form-group">
                    <label>{{ translate('Full Variant') }}</label>
                    <input type="text" class="form-control dm-typeahead" id="variant_search" data-mode="variant" data-target="product_stock_id" value="{{ $appliedOn === 'full_variant' ? $variantLabel : '' }}" placeholder="{{ translate('Search Full Variant') }}" autocomplete="off">
                    <div class="list-group dm-typeahead-results"></div>
                </div>
            </div>
            <div class="col-md-4 dm-field d-none" data-show-on="batch">
                <div class="form-group">
                    <label>{{ translate('Same as') }} / {{ translate('Batch / Lot.No') }}</label>
                    <input type="text" class="form-control dm-typeahead" id="batch_search" data-mode="batch" data-target="batch_id" value="{{ $appliedOn === 'batch' ? $batchLabel : '' }}" placeholder="{{ translate('Search Batch / Lot.No') }}" autocomplete="off">
                    <div class="list-group dm-typeahead-results"></div>
                </div>
            </div>
            <div class="col-md-4 dm-field d-none" data-show-on="category">
                <div class="form-group">
                    <label>{{ translate('Category (Main)') }}</label>
                    <select name="category_id" id="category_id" class="form-control aiz-selectpicker" data-live-search="true">
                        <option value="">{{ translate('Select') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $d->category_id ?? '') === (string) $category->id)>{{ $category->getTranslation('name') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4 dm-field d-none" data-show-on="group">
                <div class="form-group">
                    <label>{{ translate('Group') }}</label>
                    <select name="group_id" id="group_id" class="form-control aiz-selectpicker" data-live-search="true">
                        <option value="">{{ translate('Select') }}</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" @selected((string) old('group_id', $d->group_id ?? '') === (string) $group->id)>{{ $group->getTranslation('name') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4 dm-field d-none" data-show-on="customer">
                <div class="form-group">
                    <label>{{ translate('Customer Name') }}</label>
                    <input type="text" class="form-control dm-typeahead" id="customer_search" data-mode="customer" data-target="customer_id" value="{{ $appliedOn === 'customer' ? $customerLabel : '' }}" placeholder="{{ translate('Search Customer') }}" autocomplete="off">
                    <div class="list-group dm-typeahead-results"></div>
                </div>
            </div>
        </div>

        <input type="hidden" name="product_id" id="product_id" value="{{ old('product_id', $d->product_id ?? '') }}">
        <input type="hidden" name="product_stock_id" id="product_stock_id" value="{{ old('product_stock_id', $d->product_stock_id ?? '') }}">
        <input type="hidden" name="batch_id" id="batch_id" value="{{ old('batch_id', $d->batch_id ?? '') }}">
        <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', $d->customer_id ?? '') }}">
        <input type="hidden" name="value_type" id="value_type" value="{{ old('value_type', $d->value_type ?? 'flat') }}">
        <input type="hidden" name="value_amount" id="value_amount" value="{{ old('value_amount', $d->value_amount ?? '') }}">
        <input type="hidden" name="value_percent" id="value_percent" value="{{ old('value_percent', $d->value_percent ?? '') }}">

        <div class="row dm-field d-none" data-show-on="sku,full_variant,batch">
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Stock Available') }}</label>
                    <input type="text" class="form-control" id="stock_available" value="" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Mfg.Date') }}</label>
                    <input type="text" class="form-control" id="manufacturing_date" value="" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Expiry Date') }}</label>
                    <input type="text" class="form-control" id="expiry_date" value="" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('COA Document') }}</label>
                    <div>
                        <a href="#" id="coa_link" target="_blank" class="d-none">{{ translate('Download') }}</a>
                        <span id="coa_label" class="text-muted">—</span>
                    </div>
                </div>
            </div>
        </div>

        <div id="role-price-block" class="dm-price-block d-none">
            <div class="d-flex align-items-center mb-2">
                <p class="mb-0 font-weight-bold text-uppercase fs-12">{{ translate('Stock Value As Per') }}</p>
                <button type="button" class="btn btn-sm btn-soft-secondary ml-2" id="toggle-role-grid">{{ translate('Hide / Unhide') }}</button>
            </div>
            <div id="role-price-grid" class="row mb-3">
                @foreach (\App\Models\DiscountMaster::ROLE_KEYS as $key => $label)
                    <div class="col">
                        <div class="border rounded p-2 text-center dm-role-cell" data-role="{{ $key }}">
                            <div class="fs-11 text-muted font-weight-bold">{{ translate($label) }}</div>
                            <div class="font-weight-bold dm-role-value">—</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ translate('Rolewise Price') }}</label>
                        <select name="role_key" id="role_key" class="form-control">
                            <option value="">{{ translate('Select') }}</option>
                            @foreach (\App\Models\DiscountMaster::ROLE_KEYS as $key => $label)
                                <option value="{{ $key }}" @selected($roleKey === $key)>{{ translate($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ translate('Qty Slab From') }}</label>
                        <input type="number" step="0.001" min="0" class="form-control" name="qty_slab_from" id="qty_slab_from" value="{{ old('qty_slab_from', $d->qty_slab_from ?? '') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ translate('Qty Slab To') }}</label>
                        <input type="number" step="0.001" min="0" class="form-control" name="qty_slab_to" id="qty_slab_to" value="{{ old('qty_slab_to', $d->qty_slab_to ?? '') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ translate('Rate') }}</label>
                        <input type="number" step="0.0001" min="0" class="form-control" name="rate" id="rate" value="{{ old('rate', $d->rate ?? '') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ translate('Amount') }}</label>
                        <input type="number" step="0.0001" min="0" class="form-control" name="amount" id="amount" value="{{ old('amount', $d->amount ?? '') }}" readonly>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ translate('Effective Rate') }}</label>
                        <input type="number" step="0.0001" min="0" class="form-control" name="effective_rate" id="effective_rate" value="{{ old('effective_rate', $d->effective_rate ?? '') }}" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>{{ translate('Discount Type') }} <span class="text-danger">*</span></label>
                    <select name="discount_type" id="discount_type" class="form-control aiz-selectpicker">
                        <option value="">{{ translate('Select') }}</option>
                        @foreach (\App\Models\DiscountMaster::DISCOUNT_TYPES as $key => $label)
                            <option value="{{ $key }}" @selected($discountType === $key)>{{ translate($label) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>{{ translate('Discount ID') }}</label>
                    <input type="text" class="form-control" id="discount_code" value="{{ $d->discount_code ?? '' }}" readonly>
                </div>
            </div>
        </div>

        <div class="row dm-type-block d-none" data-show-type="batchwise">
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Batchwise') }} — {{ translate('Type') }}</label>
                    <select class="form-control dm-value-type">
                        <option value="flat" @selected(old('value_type', $d->value_type ?? 'flat') === 'flat')>{{ translate('Flat') }}</option>
                        <option value="percent" @selected(old('value_type', $d->value_type ?? '') === 'percent')>{{ translate('Percent') }}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Amount Or %') }}</label>
                    <input type="number" step="0.0001" min="0" class="form-control dm-value-amount" value="{{ old('value_amount', $d->value_amount ?? '') }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('% Or Amount') }}</label>
                    <input type="number" step="0.0001" min="0" max="100" class="form-control dm-value-percent" value="{{ old('value_percent', $d->value_percent ?? '') }}">
                </div>
            </div>
        </div>

        <div class="row dm-type-block d-none" data-show-type="productwise">
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Productwise') }} — {{ translate('Type') }} ({{ translate('Flat') }} / {{ translate('Percent') }})</label>
                    <select class="form-control dm-value-type">
                        <option value="flat" @selected(old('value_type', $d->value_type ?? 'flat') === 'flat')>{{ translate('Flat') }}</option>
                        <option value="percent" @selected(old('value_type', $d->value_type ?? '') === 'percent')>{{ translate('Percent') }}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Amount Or %') }}</label>
                    <input type="number" step="0.0001" min="0" class="form-control dm-value-amount" value="{{ old('value_amount', $d->value_amount ?? '') }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('% Or Amount') }}</label>
                    <input type="number" step="0.0001" min="0" max="100" class="form-control dm-value-percent" value="{{ old('value_percent', $d->value_percent ?? '') }}">
                </div>
            </div>
        </div>

        <div class="row dm-type-block d-none" data-show-type="pointwise">
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Earn') }}</label>
                    <input type="number" step="0.001" min="0" class="form-control" name="earn" id="earn" value="{{ old('earn', $d->earn ?? '') }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Amount Or %') }}</label>
                    <input type="number" step="0.0001" min="0" class="form-control dm-value-amount" value="{{ old('value_amount', $d->value_amount ?? '') }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('% Or Amount') }}</label>
                    <input type="number" step="0.0001" min="0" class="form-control dm-value-percent" value="{{ old('value_percent', $d->value_percent ?? '') }}">
                </div>
            </div>
        </div>

        <div class="row dm-type-block d-none" data-show-type="amount_wise">
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Invoice Amount') }}</label>
                    <input type="number" step="0.0001" min="0" class="form-control" name="invoice_amount" value="{{ old('invoice_amount', $d->invoice_amount ?? '') }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Type') }}</label>
                    <select class="form-control dm-value-type">
                        <option value="percent" @selected(old('value_type', $d->value_type ?? 'percent') === 'percent')>{{ translate('Percentage (%)') }}</option>
                        <option value="flat" @selected(old('value_type', $d->value_type ?? '') === 'flat')>{{ translate('Fixed Amount') }}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Amount Or %') }}</label>
                    <input type="number" step="0.0001" min="0" class="form-control dm-value-amount" value="{{ old('value_amount', $d->value_amount ?? '') }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('% Or Amount') }}</label>
                    <input type="number" step="0.0001" min="0" max="100" class="form-control dm-value-percent" value="{{ old('value_percent', $d->value_percent ?? '') }}">
                </div>
            </div>
        </div>

        <div class="dm-type-block d-none" data-show-type="schemewise">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ translate('Scheme (Free Qty)') }}</label>
                        <input type="number" step="0.001" min="0" class="form-control" name="scheme_free_qty" id="scheme_free_qty" value="{{ old('scheme_free_qty', $d->scheme_free_qty ?? '') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ translate('Scheme %') }}</label>
                        <input type="number" step="0.0001" min="0" max="100" class="form-control" name="scheme_percent" id="scheme_percent" value="{{ old('scheme_percent', $d->scheme_percent ?? '') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ translate('Scheme Value') }}</label>
                        <input type="number" step="0.0001" min="0" class="form-control" name="scheme_value" id="scheme_value" value="{{ old('scheme_value', $d->scheme_value ?? '') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ translate('If Scheme Product Is Same') }}</label>
                        @php
                            $schemeSame = old('scheme_product_is_same', isset($d) && $d->scheme_product_is_same === false ? '0' : '1');
                        @endphp
                        <select name="scheme_product_is_same" id="scheme_product_is_same" class="form-control">
                            <option value="1" @selected((string) $schemeSame !== '0')>{{ translate('Yes') }}</option>
                            <option value="0" @selected((string) $schemeSame === '0')>{{ translate('No') }}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row d-none" id="scheme-other-product">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ translate('SKU Product') }} / {{ translate('Full Variant') }}</label>
                        <input type="text" class="form-control dm-typeahead" data-mode="sku" data-target="scheme_product_stock_id" value="{{ $schemeLabel }}" placeholder="{{ translate('Search scheme product') }}" autocomplete="off">
                        <div class="list-group dm-typeahead-results"></div>
                        <input type="hidden" name="scheme_product_stock_id" id="scheme_product_stock_id" value="{{ old('scheme_product_stock_id', $d->scheme_product_stock_id ?? '') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('From Date') }} <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="from_date" value="{{ old('from_date', optional($d->from_date ?? null)->format('Y-m-d') ?? '') }}" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('To Date') }}</label>
                    <input type="date" class="form-control" name="to_date" value="{{ old('to_date', optional($d->to_date ?? null)->format('Y-m-d') ?? '') }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Offer Active / Deactivate') }}</label>
                    <select name="status" class="form-control">
                        <option value="1" @selected(old('status', $d->status ?? true))>{{ translate('Active') }}</option>
                        <option value="0" @selected((string) old('status', isset($d) && !$d->status ? '0' : '1') === '0')>{{ translate('Deactivate') }}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Date Of Add / Edit') }}</label>
                    <input type="text" class="form-control" value="{{ $d->updated_at ?? now() }}" readonly>
                </div>
            </div>
        </div>

        <div class="text-right">
            <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
        </div>
    </div>
</div>

<style>
    .dm-typeahead-results { position: absolute; z-index: 20; max-height: 240px; overflow: auto; }
    .dm-role-cell.is-active { background: #e0f2fe; border-color: #38bdf8 !important; }
    .dm-field .form-group, .dm-typeahead { position: relative; }
</style>
