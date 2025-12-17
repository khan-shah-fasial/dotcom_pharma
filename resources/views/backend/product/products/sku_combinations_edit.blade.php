<style>
.badge {
    height: auto;
    width: auto;
    font-size: 16px;
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
                    <div class="card-header d-flex justify-content-between align-items-center" id="heading-edit-{{ $key }}">
                        <div class="d-flex align-items-center" style="gap:10px;">
                            <span class="badge badge-primary">{{ $str }}</span>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" type="button"
                            data-toggle="collapse" data-target="#collapse-edit-{{ $key }}"
                            aria-expanded="true"
                            aria-controls="collapse-edit-{{ $key }}">
                            {{ translate('Edit fields') }}
                        </button>
                    </div>

                    <div id="collapse-edit-{{ $key }}" class="collapse {{ $isOpen }}" aria-labelledby="heading-edit-{{ $key }}">
                        <div class="card-body">
                            <div class="row gutters-10">
                                <div class="col-md-4">
                                    <h6 class="text-muted mb-2">{{ translate('Identifiers') }}</h6>
                                    <div class="form-group mb-3">
                                        <label class="form-label mb-1">{{ translate('SKU') }}</label>
                                        <input type="text" name="sku_{{ $str }}" value="{{ $stock->sku ?? '' }}" class="form-control" required>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <h6 class="text-muted mb-2">{{ translate('Pricing & Inventory') }}</h6>
                                    <div class="form-row">
                                        <div class="col-6 mb-3">
                                            <label class="form-label mb-1">{{ translate('MRP Price') }}</label>
                                            <input type="number" lang="en" name="mrp_price_{{ $str }}" value="{{ $stock && $stock->mrp_price !== null ? $stock->mrp_price : '' }}" min="0" step="0.01" class="form-control" required>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="form-label mb-1">{{ translate('Selling Price') }}</label>
                                            <input type="number" lang="en" name="price_{{ $str }}" value="{{ $stock && $stock->price !== null ? $stock->price : 0 }}" min="0" step="0.01" class="form-control" required readonly>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="form-label mb-1">{{ translate('Min Purchase Qty') }}</label>
                                            <input type="number" lang="en" name="min_qty_{{ $str }}" value="{{ $stock && $stock->min_qty ? $stock->min_qty : 1 }}" min="1" step="1" class="form-control" required>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="form-label mb-1">{{ translate('Stock Quantity') }}</label>
                                            <input type="number" lang="en" name="qty_{{ $str }}" value="{{ $stock && $stock->qty !== null ? $stock->qty : 10 }}" min="0" step="1" class="form-control" required>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label class="form-label mb-1">{{ translate('Product Expiry Date') }}</label>
                                            <input type="date" name="product_exp_date_{{ $str }}" value="{{ $stock->product_exp_date ?? null }}" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <h6 class="text-muted mb-2">{{ translate('Dimensions & Packaging') }}</h6>
                                    <div class="form-row">
                                        <div class="col-4 mb-3">
                                            <label class="form-label mb-1">{{ translate('Length (cm)') }}</label>
                                            <input type="number" lang="en" name="length_{{ $str }}" value="{{ $stock->length ?? '' }}" class="form-control" placeholder="{{ translate('Length (cm)') }}" step="0.01" min="0" required>
                                        </div>
                                        <div class="col-4 mb-3">
                                            <label class="form-label mb-1">{{ translate('Width (cm)') }}</label>
                                            <input type="number" lang="en" name="width_{{ $str }}" value="{{ $stock->width ?? '' }}" class="form-control" placeholder="{{ translate('Width (cm)') }}" step="0.01" min="0" required>
                                        </div>
                                        <div class="col-4 mb-3">
                                            <label class="form-label mb-1">{{ translate('Height (cm)') }}</label>
                                            <input type="number" lang="en" name="height_{{ $str }}" value="{{ $stock->height ?? '' }}" class="form-control" placeholder="{{ translate('Height (cm)') }}" step="0.01" min="0" required>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="form-label mb-1">{{ translate('Weight / Volume') }}</label>
                                            <input type="number" name="weight_{{ $str }}" value="{{ $stock->weight ?? '' }}" class="form-control" placeholder="{{ translate('Weight / Volume') }}" step="0.001" min="0" required>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="form-label mb-1">{{ translate('Package Count') }}</label>
                                            <input type="text" name="count_{{ $str }}" value="{{ $stock->count ?? '' }}" class="form-control" placeholder="{{ translate('Package Count') }}" required>
                                        </div>
                                    </div>
                                </div>

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
                                        <input type="hidden" name="coa_{{ $str }}" class="selected-files" value="{{ $stock && $stock->coa ? $stock->coa : '' }}">
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
                                        <div class="form-control file-amount text-truncate">{{ translate('Choose File') }}</div>
                                        <input type="hidden" name="img_{{ $str }}" class="selected-files" value="{{ $stock->image ?? null }}">
                                    </div>
                                    <div class="file-preview box sm"></div>
                                </div>

                                <div class="col-md-12 mt-3">
                                    <h6 class="text-muted mb-2">{{ translate('Role Base Price') }}</h6>
                                    @if (!empty($role_base_price) && count($role_base_price) > 0)
                                        <div class="accordion" id="rolePriceAccordion_{{ $str }}">
                                            @php $collapseId = 'rolePriceCollapse_'.$str; @endphp
                                            <div class="card mb-1 border">
                                                <div class="card-header p-1" id="heading_{{ $collapseId }}">
                                                    <h2 class="mb-0">
                                                        <button class="btn btn-link btn-block text-left p-2" type="button"
                                                            data-toggle="collapse" data-target="#{{ $collapseId }}"
                                                            aria-expanded="true" aria-controls="{{ $collapseId }}">
                                                            {{ translate('Role price') }}
                                                        </button>
                                                    </h2>
                                                </div>
                                                <div id="{{ $collapseId }}" class="collapse"
                                                    aria-labelledby="heading_{{ $collapseId }}"
                                                    data-parent="#rolePriceAccordion_{{ $str }}">
                                                    <div class="card-body py-2">
                                                        <table class="table table-sm mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th class="text-sm text-gray-700">{{ translate('Role') }}</th>
                                                                    <th class="text-sm text-gray-700 text-right">{{ translate('Price') }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($role_base_price as $role => $price)
                                                                    <tr>
                                                                        <td class="text-sm text-gray-700">{{ strtoupper($role) }}</td>
                                                                        <td class="text-sm text-gray-700 text-right">{{ $price }}</td>
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
