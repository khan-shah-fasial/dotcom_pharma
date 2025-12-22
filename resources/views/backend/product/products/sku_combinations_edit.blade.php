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
                                    <div class="border rounded p-3 h-100">
                                        <h6 class="text-muted mb-3">{{ translate('Identifiers') }}</h6>
                                        <div class="form-group mb-3">
                                            <label class="form-label mb-1">{{ translate('SKU') }}</label>
                                            <input type="text" name="sku_{{ $str }}"
                                                value="{{ $stock->sku ?? '' }}" class="form-control" required>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label class="form-label mb-1">{{ translate('Hide this variant') }}</label>
                                            <label class="aiz-switch aiz-switch-success mb-0 d-block">
                                                <input type="checkbox" name="is_hidden_{{ $str }}" value="1" @if (($stock->is_hidden ?? false) == true) checked @endif>
                                                <span></span>
                                            </label>
                                            <small class="text-muted">{{ translate('If enabled, this variant will be hidden on the product details page.') }}</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="border rounded p-3 h-100">
                                        <h6 class="text-muted mb-3">{{ translate('Pricing & Inventory') }}</h6>
                                        <div class="form-row">
                                            <div class="col-sm-6 col-lg-3 mb-3">
                                                <label class="form-label mb-1">{{ translate('MRP Price') }}</label>
                                                <input type="number" lang="en"
                                                    name="mrp_price_{{ $str }}"
                                                    value="{{ $stock && $stock->mrp_price !== null ? $stock->mrp_price : '' }}"
                                                    min="0" step="0.01" class="form-control" required>
                                            </div>
                                            <div class="col-sm-6 col-lg-3 mb-3 d-none">
                                                <label class="form-label mb-1">{{ translate('Selling Price') }}</label>
                                                <input type="number" lang="en" name="price_{{ $str }}"
                                                    value="{{ $stock && $stock->price !== null ? $stock->price : 0 }}"
                                                    min="0" step="0.01" class="form-control" required
                                                    readonly>
                                            </div>
                                            <div class="col-sm-6 col-lg-3 mb-3">
                                                <label class="form-label mb-1">{{ translate('Min Order Qty') }}</label>
                                                <input type="number" lang="en" name="min_qty_{{ $str }}"
                                                    value="{{ $stock && $stock->min_qty ? $stock->min_qty : 1 }}"
                                                    min="1" step="1" class="form-control" required>
                                            </div>
                                            <div class="col-sm-6 col-lg-3 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Stock Quantity') }}</label>
                                                <input type="number" lang="en" name="qty_{{ $str }}"
                                                    value="{{ $stock && $stock->qty !== null ? $stock->qty : 10 }}"
                                                    min="0" step="1" class="form-control" required>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <label
                                                    class="form-label mb-1">{{ translate('Product Expiry Date') }}</label>
                                                <input type="date" name="product_exp_date_{{ $str }}"
                                                    value="{{ $stock->product_exp_date ?? null }}"
                                                    class="form-control">
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label class="form-label mb-1">{{ translate('Package Count') }}</label>
                                                <input type="text" name="count_{{ $str }}"
                                                    value="{{ $stock->count ?? '' }}" class="form-control"
                                                    placeholder="{{ translate('Package Count') }}" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="row gutters-10 mt-3">
                                <div class="col-lg-4">
                                    <div class="border rounded p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="text-muted mb-0">{{ translate('Each Piece (Base)') }}</h6>
                                            <span class="badge badge-light">{{ translate('Each') }}</span>
                                        </div>
                                        <div class="form-row">
                                            <div class="col-6 mb-3">
                                                <label class="form-label mb-1">{{ translate('Qty per Piece') }}</label>
                                                <input type="number" name="qty_per_piece_{{ $str }}"
                                                    value="{{ $stock->qty_per_piece ?? '' }}" class="form-control"
                                                    placeholder="{{ translate('Enter qty per piece (usually 1)') }}"
                                                    step="0.01" min="0">
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Weight Of Each Piece') }}</label>
                                                <input type="number" name="weight_{{ $str }}"
                                                    value="{{ $stock->weight ?? '' }}" class="form-control"
                                                    placeholder="{{ translate('Weight per piece') }}" step="0.001"
                                                    min="0" required>
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Piece Length (cm)') }}</label>
                                                <input type="number" lang="en"
                                                    name="length_{{ $str }}"
                                                    value="{{ $stock->length ?? '' }}" class="form-control"
                                                    placeholder="{{ translate('Length (cm)') }}" step="0.01"
                                                    min="0" required>
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Piece Width (cm)') }}</label>
                                                <input type="number" lang="en"
                                                    name="width_{{ $str }}"
                                                    value="{{ $stock->width ?? '' }}" class="form-control"
                                                    placeholder="{{ translate('Width (cm)') }}" step="0.01"
                                                    min="0" required>
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Piece Height (cm)') }}</label>
                                                <input type="number" lang="en"
                                                    name="height_{{ $str }}"
                                                    value="{{ $stock->height ?? '' }}" class="form-control"
                                                    placeholder="{{ translate('Height (cm)') }}" step="0.01"
                                                    min="0" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="border rounded p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="text-muted mb-0">
                                                {{ translate('Inner Buffer Box / Shrink Pack') }}</h6>
                                            <span class="badge badge-light">{{ translate('Inner Pack') }}</span>
                                        </div>
                                        <div class="form-row">
                                            <div class="col-6 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Qty Per Inner Buffer Box / Shrink Pack') }}</label>
                                                <input type="number" name="qty_per_buffer_box_{{ $str }}"
                                                    value="{{ $stock->qty_per_buffer_box ?? '' }}"
                                                    class="form-control" step="1" min="0"
                                                    placeholder="{{ translate('Units per buffer box') }}">
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Weight Of Inner Buffer Box / Shrink Pack') }}</label>
                                                <input type="number" name="weight_buffer_box_{{ $str }}"
                                                    value="{{ $stock->weight_buffer_box ?? '' }}"
                                                    class="form-control" step="0.001" min="0"
                                                    placeholder="{{ translate('Weight per buffer box') }}">
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Buffer Length (cm)') }}</label>
                                                <input type="number" name="buffer_length_{{ $str }}"
                                                    value="{{ $stock->buffer_length ?? '' }}" class="form-control"
                                                    step="0.01" min="0"
                                                    placeholder="{{ translate('Length') }}">
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Buffer Width (cm)') }}</label>
                                                <input type="number" name="buffer_width_{{ $str }}"
                                                    value="{{ $stock->buffer_width ?? '' }}" class="form-control"
                                                    step="0.01" min="0"
                                                    placeholder="{{ translate('Width') }}">
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Buffer Height (cm)') }}</label>
                                                <input type="number" name="buffer_height_{{ $str }}"
                                                    value="{{ $stock->buffer_height ?? '' }}" class="form-control"
                                                    step="0.01" min="0"
                                                    placeholder="{{ translate('Height') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="border rounded p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="text-muted mb-0">{{ translate('Outer Case/Shipper/Carton') }}
                                            </h6>
                                            <span class="badge badge-light">{{ translate('Outer Pack') }}</span>
                                        </div>
                                        <div class="form-row">
                                            <div class="col-6 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Total Qty of Outer Case/Shipper/Carton') }}</label>
                                                <input type="number" name="total_qty_per_case_{{ $str }}"
                                                    value="{{ $stock->total_qty_per_case ?? '' }}"
                                                    class="form-control" step="1" min="0"
                                                    placeholder="{{ translate('Total units per case') }}">
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Total Weight Of Outer Case/Shipper/Carton') }}</label>
                                                <input type="number" name="weight_case_{{ $str }}"
                                                    value="{{ $stock->weight_case ?? '' }}" class="form-control"
                                                    step="0.001" min="0"
                                                    placeholder="{{ translate('Weight per case') }}">
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Case Length (cm)') }}</label>
                                                <input type="number" name="case_length_{{ $str }}"
                                                    value="{{ $stock->case_length ?? '' }}" class="form-control"
                                                    step="0.01" min="0"
                                                    placeholder="{{ translate('Length') }}">
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Case Width (cm)') }}</label>
                                                <input type="number" name="case_width_{{ $str }}"
                                                    value="{{ $stock->case_width ?? '' }}" class="form-control"
                                                    step="0.01" min="0"
                                                    placeholder="{{ translate('Width') }}">
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label
                                                    class="form-label mb-1">{{ translate('Case Height (cm)') }}</label>
                                                <input type="number" name="case_height_{{ $str }}"
                                                    value="{{ $stock->case_height ?? '' }}" class="form-control"
                                                    step="0.01" min="0"
                                                    placeholder="{{ translate('Height') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row gutters-10">
                                <div class="col-md-6 mt-3">
                                    <h6 class="text-muted mb-2">{{ translate('COA') }}</h6>
                                    <div class="input-group" data-toggle="aizuploader" data-type="document">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text bg-soft-secondary font-weight-medium">
                                                {{ translate('Browse') }}
                                            </div>
                                        </div>
                                        <div class="form-control file-amount text-truncate">
                                            {{ translate('Choose PDF File') }}
                                        </div>
                                        <input type="hidden" name="coa_{{ $str }}" class="selected-files"
                                            value="{{ $stock && $stock->coa ? $stock->coa : '' }}">
                                    </div>
                                    <div class="file-preview box sm"></div>
                                </div>

                                <div class="col-md-6 mt-3">
                                    <h6 class="text-muted mb-2">{{ translate('Photo') }}</h6>
                                    <div class="input-group" data-toggle="aizuploader" data-type="image">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text bg-soft-secondary font-weight-medium">
                                                {{ translate('Browse') }}</div>
                                        </div>
                                        <div class="form-control file-amount text-truncate">
                                            {{ translate('Choose File') }}</div>
                                        <input type="hidden" name="img_{{ $str }}" class="selected-files"
                                            value="{{ $stock->image ?? null }}">
                                    </div>
                                    <div class="file-preview box sm"></div>
                                </div>

                                <div class="col-md-12 mt-3">
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif
