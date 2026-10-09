@php
    $d = $discount;
    $discountType = old('discount_type', optional($d)->discount_type ?? '');
    $sheet = [];
    if ($d) {
        $rawSheet = $d->getAttributes()['sheet_payload'] ?? null;
        if (is_string($rawSheet) && $rawSheet !== '') {
            $sheet = json_decode($rawSheet, true) ?: [];
        }
    }

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

    $productLabel = old('product_label');
    if ($productLabel === null) {
        $productLabel = $d && $d->stock ? $buildStockLabel($d->product, $d->stock) : '';
    }
    $schemeLabel = $d && $d->schemeStock ? $buildStockLabel($d->schemeStock->product, $d->schemeStock) : '';

    $roles = old('roles');
    if (!is_array($roles)) {
        $roles = $sheet['roles'] ?? null;
    }
    if (!is_array($roles) || $roles === []) {
        $roles = [[
            'role_key' => optional($d)->role_key ?? '',
            'slabs' => [[
                'qty' => optional($d)->qty_slab_from ?? '',
                'value_type' => optional($d)->value_type ?? 'flat',
                'amount' => optional($d)->value_amount ?? '',
                'percent' => optional($d)->value_percent ?? '',
                'rate' => optional($d)->rate ?? '',
                'line_amount' => optional($d)->amount ?? '',
                'effective_rate' => optional($d)->effective_rate ?? '',
                'free_qty' => optional($d)->scheme_free_qty ?? '',
                'scheme_percent' => optional($d)->scheme_percent ?? '',
                'scheme_value' => optional($d)->scheme_value ?? '',
            ]],
        ]];
    }

    $amounts = old('amounts');
    if (!is_array($amounts)) {
        $amounts = $sheet['amounts'] ?? null;
    }
    if (!is_array($amounts) || $amounts === []) {
        $pointAmount = ($d && $d->discount_type === 'pointwise') ? ($d->earn ?? '') : (optional($d)->value_amount ?? '');
        $amounts = [[
            'slab' => optional($d)->invoice_amount ?? '',
            'from' => optional($d)->qty_slab_from ?? '',
            'to' => optional($d)->qty_slab_to ?? '',
            'value_type' => optional($d)->value_type ?? 'flat',
            'amount' => $pointAmount,
            'percent' => optional($d)->value_percent ?? '',
        ]];
    }

    $same = old('same_discount');
    if ($same === null) {
        if (array_key_exists('same_discount', $sheet)) {
            $same = !empty($sheet['same_discount']) ? '1' : '0';
        } elseif ($d && in_array($d->applied_on, ['category', 'group', 'customer'], true)) {
            $same = '1';
        } else {
            $same = '0';
        }
    }

    $selectedBatches = old('batch_ids', $sheet['batch_ids'] ?? ($d && $d->batch_id ? [(string) $d->batch_id] : []));
    $selectedBatches = array_map('strval', (array) $selectedBatches);
    $couponCode = old('coupon_code', $sheet['coupon_code'] ?? ($d ? ($d->getAttributes()['coupon_code'] ?? '') : ''));
    $schemeSame = old('scheme_product_is_same', isset($d) && $d->scheme_product_is_same === false ? '0' : '1');
    $scopeRole = old('scope_role', $sheet['scope_role'] ?? '');
    $nearExpiry = old('near_expiry', !empty($sheet['near_expiry']) ? '1' : '0');
    $scopeProductIds = array_filter((array) old('scope_products', $sheet['scope_products'] ?? []));
    $scopeProducts = $scopeProductIds
        ? \App\Models\ProductStock::with('product')->whereIn('id', $scopeProductIds)->get()
        : collect();
    $roleOptions = \App\Models\DiscountMaster::ROLE_KEYS;
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Discount Master') }}</h5>
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
                    <label>{{ translate('Discount Type') }} <span class="text-danger">*</span></label>
                    <select name="discount_type" id="discount_type" class="form-control" required>
                        <option value="">{{ translate('Select') }}</option>
                        @foreach (\App\Models\DiscountMaster::DISCOUNT_TYPES as $key => $label)
                            <option value="{{ $key }}" @selected($discountType === $key)>{{ translate($label) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Discount ID') }}</label>
                    <input type="text" class="form-control" id="discount_code" value="{{ optional($d)->discount_code ?? '' }}" readonly>
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div id="dm-type-note" class="border rounded p-2 fs-12 text-muted bg-light">{{ translate('Select a discount type.') }}</div>
                </div>
            </div>
        </div>

        <div id="product-layout" class="d-none">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group dm-field">
                        <label>{{ translate('Search By SKU Or Product Name / Brand Name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control dm-typeahead" id="product_search" data-mode="sku" data-target="product_stock_id" name="product_label" value="{{ $productLabel }}" placeholder="{{ translate('Show with full variant') }}" autocomplete="off">
                        <div class="list-group dm-typeahead-results"></div>
                        <input type="hidden" name="product_id" id="product_id" value="{{ old('product_id', optional($d)->product_id ?? '') }}">
                        <input type="hidden" name="product_stock_id" id="product_stock_id" value="{{ old('product_stock_id', optional($d)->product_stock_id ?? '') }}">
                        <div id="drug_name" class="text-muted fs-11 mt-1"></div>
                    </div>
                </div>
                <div class="col-md-4 dm-batch-pick d-none">
                    <div class="form-group">
                        <label>{{ translate('Batch / Lot No') }}</label>
                        <input type="text" class="form-control mb-2" id="batch_filter" placeholder="{{ translate('Dropdown / select manually / multiple selection') }}">
                        <div id="batch-picks" class="border rounded p-2 dm-batch-box"></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ translate('SKU') }}</label>
                        <input type="text" class="form-control" id="meta_sku" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ translate('Full Detailed Variant') }}</label>
                        <input type="text" class="form-control" id="meta_variant" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ translate('Marketed By') }}</label>
                        <input type="text" class="form-control" id="meta_marketed" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ translate('Import By') }}</label>
                        <input type="text" class="form-control" id="meta_import" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ translate('Mfg. By') }}</label>
                        <input type="text" class="form-control" id="meta_mfg" readonly>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-2">
                <p class="mb-0 font-weight-bold fs-12 text-uppercase">{{ translate('Display Stock Availability With Role Wise Price And Value') }}</p>
                <button type="button" class="btn btn-sm btn-soft-secondary" id="toggle-stock">{{ translate('Open / Close') }}</button>
            </div>
            <div id="stock-panel">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ translate('Stock Available') }}</label>
                            <input type="text" class="form-control" id="stock_available" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ translate('Batch No / Lot') }}</label>
                            <input type="text" class="form-control" id="batch_summary" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ translate('Mfg. Date') }}</label>
                            <input type="text" class="form-control" id="manufacturing_date" readonly>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ translate('Expiry Date') }}</label>
                            <input type="text" class="form-control" id="expiry_date" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ translate('COA') }}</label>
                            <div id="coa-gallery" class="d-flex flex-wrap"></div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm mb-0 text-center fs-12">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('Role') }}</th>
                                <th>{{ translate('P-Rate') }}</th>
                                @foreach ($roleOptions as $key => $label)
                                    <th>{{ translate($label) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th>{{ translate('Rate / Price') }}</th>
                                <td>
                                    <span id="price-p_rate" class="font-weight-bold">******</span>
                                    <button type="button" class="btn btn-xs btn-soft-secondary ml-1" id="unlock-prate">{{ translate('Password') }}</button>
                                </td>
                                @foreach ($roleOptions as $key => $label)
                                    <td class="font-weight-bold dm-price" data-role="{{ $key }}">—</td>
                                @endforeach
                            </tr>
                            <tr>
                                <th>{{ translate('Value') }}</th>
                                <td id="value-p_rate">—</td>
                                @foreach ($roleOptions as $key => $label)
                                    <td class="dm-value" data-role="{{ $key }}">—</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="role-groups">
                @foreach ($roles as $roleIndex => $role)
                    <div class="border rounded p-2 mb-2 dm-role-group">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-2 fs-12">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="dm-col-role">{{ translate('Rolewise Price') }}</th>
                                        <th>{{ translate('Qty Slab') }}</th>
                                        <th class="dm-col-flat">{{ translate('Flat / Percent') }}</th>
                                        <th class="dm-col-scheme">{{ translate('Scheme (Free)') }}</th>
                                        <th class="dm-col-flat">{{ translate('Amount') }}</th>
                                        <th class="dm-col-flat">{{ translate('%') }}</th>
                                        <th>{{ translate('Rate') }}</th>
                                        <th class="dm-col-scheme">{{ translate('Amount') }}</th>
                                        <th class="dm-col-scheme">{{ translate('Scheme %') }}</th>
                                        <th class="dm-col-scheme">{{ translate('Scheme Value') }}</th>
                                        <th class="dm-col-flat">{{ translate('Amount') }}</th>
                                        <th>{{ translate('Effective Rate') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody class="dm-slab-body">
                                    @foreach (($role['slabs'] ?? [[]]) as $slabIndex => $slab)
                                        <tr class="dm-slab">
                                            <td class="dm-col-role">
                                                @if ($loop->first)
                                                    <select class="form-control form-control-sm dm-role-key" name="roles[{{ $roleIndex }}][role_key]">
                                                        <option value="">{{ translate('Select') }}</option>
                                                        @foreach ($roleOptions as $key => $label)
                                                            <option value="{{ $key }}" @selected(($role['role_key'] ?? '') === $key)>{{ translate($label) }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </td>
                                            <td><input type="number" step="0.001" min="0" class="form-control form-control-sm dm-qty" name="roles[{{ $roleIndex }}][slabs][{{ $slabIndex }}][qty]" value="{{ $slab['qty'] ?? '' }}"></td>
                                            <td class="dm-col-flat">
                                                <select class="form-control form-control-sm dm-value-type" name="roles[{{ $roleIndex }}][slabs][{{ $slabIndex }}][value_type]">
                                                    <option value="flat" @selected(($slab['value_type'] ?? 'flat') === 'flat')>{{ translate('Flat') }}</option>
                                                    <option value="percent" @selected(($slab['value_type'] ?? '') === 'percent')>{{ translate('Percent') }}</option>
                                                </select>
                                            </td>
                                            <td class="dm-col-scheme"><input type="number" step="0.001" min="0" class="form-control form-control-sm dm-free" name="roles[{{ $roleIndex }}][slabs][{{ $slabIndex }}][free_qty]" value="{{ $slab['free_qty'] ?? '' }}"></td>
                                            <td class="dm-col-flat"><input type="number" step="0.0001" min="0" class="form-control form-control-sm dm-amount" name="roles[{{ $roleIndex }}][slabs][{{ $slabIndex }}][amount]" value="{{ $slab['amount'] ?? '' }}"></td>
                                            <td class="dm-col-flat"><input type="number" step="0.0001" min="0" max="100" class="form-control form-control-sm dm-percent" name="roles[{{ $roleIndex }}][slabs][{{ $slabIndex }}][percent]" value="{{ $slab['percent'] ?? '' }}"></td>
                                            <td><input type="number" step="0.0001" min="0" class="form-control form-control-sm dm-rate" name="roles[{{ $roleIndex }}][slabs][{{ $slabIndex }}][rate]" value="{{ $slab['rate'] ?? '' }}" readonly></td>
                                            <td class="dm-col-scheme"><input type="number" step="0.0001" min="0" class="form-control form-control-sm dm-line-amount" name="roles[{{ $roleIndex }}][slabs][{{ $slabIndex }}][line_amount]" value="{{ $slab['line_amount'] ?? '' }}" readonly></td>
                                            <td class="dm-col-scheme"><input type="number" step="0.0001" min="0" class="form-control form-control-sm dm-scheme-percent" name="roles[{{ $roleIndex }}][slabs][{{ $slabIndex }}][scheme_percent]" value="{{ $slab['scheme_percent'] ?? '' }}" readonly></td>
                                            <td class="dm-col-scheme"><input type="number" step="0.0001" min="0" class="form-control form-control-sm dm-scheme-value" name="roles[{{ $roleIndex }}][slabs][{{ $slabIndex }}][scheme_value]" value="{{ $slab['scheme_value'] ?? '' }}" readonly></td>
                                            <td class="dm-col-flat"><input type="number" step="0.0001" min="0" class="form-control form-control-sm dm-line-amount-flat" value="{{ $slab['line_amount'] ?? '' }}" readonly></td>
                                            <td><input type="number" step="0.0001" min="0" class="form-control form-control-sm dm-effective" name="roles[{{ $roleIndex }}][slabs][{{ $slabIndex }}][effective_rate]" value="{{ $slab['effective_rate'] ?? '' }}" readonly></td>
                                            <td><button type="button" class="btn btn-xs btn-soft-danger dm-remove-slab">&times;</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-soft-primary dm-add-slab">{{ translate('Add More Slab') }}</button>
                        <button type="button" class="btn btn-sm btn-soft-secondary dm-add-role">{{ translate('Add More Role') }}</button>
                        <button type="button" class="btn btn-sm btn-soft-danger dm-remove-role">{{ translate('Remove Role') }}</button>
                    </div>
                @endforeach
            </div>

            <div id="scheme-same-row" class="d-none mt-2">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ translate('If Scheme Product Is Same') }}</label>
                            <select name="scheme_product_is_same" id="scheme_product_is_same" class="form-control">
                                <option value="1" @selected((string) $schemeSame !== '0')>{{ translate('Yes') }}</option>
                                <option value="0" @selected((string) $schemeSame === '0')>{{ translate('No') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 d-none" id="scheme-other-product">
                        <div class="form-group dm-field">
                            <label>{{ translate('Scheme Product') }}</label>
                            <input type="text" class="form-control dm-typeahead" data-mode="sku" data-target="scheme_product_stock_id" data-skip-load="1" value="{{ $schemeLabel }}" placeholder="{{ translate('Search scheme product') }}" autocomplete="off">
                            <div class="list-group dm-typeahead-results"></div>
                            <input type="hidden" name="scheme_product_stock_id" id="scheme_product_stock_id" value="{{ old('scheme_product_stock_id', optional($d)->scheme_product_stock_id ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="invoice-layout" class="d-none">
            <div id="coupon-head" class="d-none border rounded p-2 mb-3 bg-light fs-12">
                <div class="mb-2">{{ translate('Refer the existing Coupon, Gift, and Wallet before creating this.') }}</div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label>{{ translate('Coupon Number') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="coupon_code" id="coupon_code" value="{{ $couponCode }}" maxlength="80">
                        </div>
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <p class="mb-2 text-muted">{{ translate('Flat: fill the amount, for example Rs 500. Percent: fill the percent, for example 3%. Each change keeps the date and time.') }}</p>
                    </div>
                </div>
                @if (!empty($sheet['history']))
                    <div class="mt-2 fs-12">
                        <div class="font-weight-bold mb-1">{{ translate('Earlier changes') }}</div>
                        @foreach (array_reverse($sheet['history']) as $change)
                            <div>{{ $change['at'] ?? '' }} — {{ $change['coupon_code'] ?? '' }} — {{ translate('Amount') }} {{ $change['amount'] ?? '—' }} — {{ $change['percent'] ?? '—' }}%</div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="table-responsive mb-2">
                <table class="table table-bordered table-sm fs-12 mb-2">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Invoice') }} / {{ translate('Amount Slab') }}</th>
                            <th>{{ translate('From') }}</th>
                            <th>{{ translate('To') }}</th>
                            <th>{{ translate('Flat / Percent') }}</th>
                            <th id="invoice-amount-label">{{ translate('Amount') }}</th>
                            <th>{{ translate('%') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="amount-body">
                        @foreach ($amounts as $index => $row)
                            <tr class="dm-amount-row">
                                <td><input type="number" step="0.0001" min="0" class="form-control form-control-sm dm-slab-cap" name="amounts[{{ $index }}][slab]" value="{{ $row['slab'] ?? '' }}"></td>
                                <td><input type="number" step="0.0001" min="0" class="form-control form-control-sm dm-from" name="amounts[{{ $index }}][from]" value="{{ $row['from'] ?? '' }}"></td>
                                <td><input type="number" step="0.0001" min="0" class="form-control form-control-sm dm-to" name="amounts[{{ $index }}][to]" value="{{ $row['to'] ?? '' }}"></td>
                                <td>
                                    <select class="form-control form-control-sm dm-amount-type" name="amounts[{{ $index }}][value_type]">
                                        <option value="flat" @selected(($row['value_type'] ?? 'flat') === 'flat')>{{ translate('Flat') }}</option>
                                        <option value="percent" @selected(($row['value_type'] ?? '') === 'percent')>{{ translate('Percent') }}</option>
                                    </select>
                                </td>
                                <td><input type="number" step="0.0001" min="0" class="form-control form-control-sm dm-amount-value" name="amounts[{{ $index }}][amount]" value="{{ $row['amount'] ?? '' }}"></td>
                                <td><input type="number" step="0.0001" min="0" max="100" class="form-control form-control-sm dm-amount-percent" name="amounts[{{ $index }}][percent]" value="{{ $row['percent'] ?? '' }}"></td>
                                <td><button type="button" class="btn btn-xs btn-soft-danger dm-remove-amount">&times;</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-soft-primary" id="add-amount-slab">{{ translate('Add More Slab') }}</button>
        </div>

        <hr>
        <div class="row align-items-end">
            <div class="col-md-5">
                <div class="form-group">
                    <label id="same-label">{{ translate('If you want to give the same discount on the items below') }}</label>
                    <select name="same_discount" id="same_discount" class="form-control">
                        <option value="0" @selected((string) $same !== '1')>{{ translate('No') }}</option>
                        <option value="1" @selected((string) $same === '1')>{{ translate('Yes') }}</option>
                    </select>
                </div>
            </div>
        </div>
        <div id="same-body" class="{{ (string) $same === '1' ? '' : 'd-none' }}">
            <p class="fs-12 text-muted">{{ translate('Discount applied on. Leave a field as All when this discount should cover every item of that kind.') }}</p>
            <div class="mb-2" id="scope-chips">
                @foreach ($scopeProducts as $scopeStock)
                    <span class="badge badge-soft-primary mr-1 mb-1 dm-chip">
                        {{ $buildStockLabel($scopeStock->product, $scopeStock) }}
                        <input type="hidden" name="scope_products[]" value="{{ $scopeStock->id }}">
                        <a href="#" class="text-danger dm-chip-remove">&times;</a>
                    </span>
                @endforeach
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group dm-field">
                        <label>{{ translate('Products') }}</label>
                        <input type="text" class="form-control dm-typeahead" id="scope_product_search" data-mode="sku" data-scope="1" placeholder="{{ translate('All products. Search to add products.') }}" autocomplete="off">
                        <div class="list-group dm-typeahead-results"></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ translate('Near Expiry Batches') }}</label>
                        <div class="border rounded px-2 py-1">
                            <label class="mb-0">
                                <input type="checkbox" name="near_expiry" value="1" @checked((string) $nearExpiry === '1')>
                                {{ translate('All products near expiry batches') }}
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ translate('Category') }}</label>
                        <select name="category_id" id="category_id" class="form-control">
                            <option value="">{{ translate('All categories') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', optional($d)->category_id ?? '') === (string) $category->id)>{{ $category->getTranslation('name') }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ translate('Group') }}</label>
                        <select name="group_id" id="group_id" class="form-control">
                            <option value="">{{ translate('All groups') }}</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}" @selected((string) old('group_id', optional($d)->group_id ?? '') === (string) $group->id)>{{ $group->getTranslation('name') }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group dm-field">
                        <label>{{ translate('Customer') }}</label>
                        <input type="text" class="form-control dm-typeahead" id="customer_search" data-mode="customer" data-target="customer_id" value="{{ $d && $d->customer_id ? $d->customerDisplayName() : '' }}" placeholder="{{ translate('All customers. Search to select one.') }}" autocomplete="off">
                        <div class="list-group dm-typeahead-results"></div>
                        <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', optional($d)->customer_id ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4 dm-price-role d-none">
                    <div class="form-group">
                        <label>{{ translate('Price Role') }}</label>
                        <select name="scope_role" id="scope_role" class="form-control">
                            <option value="">{{ translate('All price roles') }}</option>
                            @foreach ($roleOptions as $key => $label)
                                <option value="{{ $key }}" @selected($scopeRole === $key)>{{ translate($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <hr>
        <h6 class="mb-2">{{ translate('Validity') }}</h6>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('From Date') }} <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="from_date" value="{{ old('from_date', optional(optional($d)->from_date)->format('Y-m-d')) }}" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('To Date') }}</label>
                    <input type="date" class="form-control" name="to_date" value="{{ old('to_date', optional(optional($d)->to_date)->format('Y-m-d')) }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Offer') }}</label>
                    <select name="status" class="form-control">
                        <option value="1" @selected((string) old('status', isset($d) && !$d->status ? '0' : '1') !== '0')>{{ translate('Active') }}</option>
                        <option value="0" @selected((string) old('status', isset($d) && !$d->status ? '0' : '1') === '0')>{{ translate('Deactivate') }}</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Date Of Add / Edit') }}</label>
                    <input type="text" class="form-control" value="{{ optional($d)->updated_at }}" readonly>
                </div>
            </div>
        </div>

        <div class="text-right">
            <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
        </div>
    </div>
</div>

<div class="modal fade" id="coa-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center">
                <img id="coa-modal-img" src="" alt="COA" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="prate-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">{{ translate('P-Rate') }}</h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <label>{{ translate('Password') }}</label>
                <input type="password" class="form-control" id="prate-password" autocomplete="current-password">
                <div id="prate-error" class="text-danger fs-12 mt-2 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-sm" id="prate-submit">{{ translate('Show') }}</button>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="dm-notes">{!! json_encode(\App\Models\DiscountMaster::TYPE_NOTES) !!}</script>
<script type="application/json" id="dm-selected-batches">{!! json_encode(array_values($selectedBatches)) !!}</script>
<script type="application/json" id="dm-role-options">{!! json_encode($roleOptions) !!}</script>

<style>
    .dm-typeahead-results { position: absolute; z-index: 30; max-height: 240px; overflow: auto; width: 100%; }
    .dm-field { position: relative; }
    .dm-batch-box { max-height: 160px; overflow: auto; }
    .btn-xs { padding: 0.1rem 0.35rem; font-size: 11px; }
</style>
