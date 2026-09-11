@extends('backend.layouts.app')

@section('content')
    @php
        $sortLink = function (string $column) use ($sortBy, $sortDir) {
            $nextDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';

            return route('products.dimensions', array_merge(request()->except('page'), [
                'sort_by' => $column,
                'sort_dir' => $nextDir,
            ]));
        };
        $sortIcon = function (string $column) use ($sortBy, $sortDir) {
            if ($sortBy !== $column) {
                return '';
            }

            return '<i class="las la-sort-amount-'.($sortDir === 'asc' ? 'up' : 'down').' sort-icon"></i>';
        };
        $sortHeading = function (string $column, string $label) use ($sortLink, $sortIcon) {
            return '<a href="'.e($sortLink($column)).'">'.e($label).$sortIcon($column).'</a>';
        };
        $formatNum = function ($value, int $decimals = 2) {
            if ($value === null || $value === '') {
                return '';
            }

            $number = (float) $value;
            if (!is_finite($number)) {
                return '';
            }
            if ($decimals === 0) {
                return (string) (int) round($number);
            }

            return rtrim(rtrim(sprintf('%.' . $decimals . 'f', $number), '0'), '.') ?: '0';
        };
        $kg = function ($grams) {
            if ($grams === null || $grams === '') {
                return '';
            }

            $value = ((float) $grams) / 1000;
            if (!is_finite($value) || $value < 0) {
                return '';
            }

            return number_format($value, 3, '.', '');
        };
        $cbm = function ($length, $width, $height) {
            if ($length === null || $width === null || $height === null || $length === '' || $width === '' || $height === '') {
                return '';
            }

            $value = ((float) $length * (float) $width * (float) $height) / 1000000;
            if (!is_finite($value) || $value < 0) {
                return '';
            }

            return number_format($value, 4, '.', '');
        };
        $factorProduct = function ($stock, array $factors) {
            $product = 1;
            foreach ($factors as $factor) {
                $value = $stock->{$factor} ?? null;
                if ($value === null || $value === '') {
                    return '';
                }
                $product *= (float) $value;
            }

            return $product;
        };
        $rangeInput = function (string $name, string $label) {
            return '<div class="col-md-3 mb-2">'
                . '<label class="form-label mb-1">'.e($label).'</label>'
                . '<div class="d-flex">'
                . '<input type="number" lang="en" step="any" min="0" class="form-control form-control-sm mr-1" name="'.e($name).'_from" value="'.e(request($name.'_from')).'" placeholder="'.e(translate('From')).'">'
                . '<input type="number" lang="en" step="any" min="0" class="form-control form-control-sm" name="'.e($name).'_to" value="'.e(request($name.'_to')).'" placeholder="'.e(translate('To')).'">'
                . '</div></div>';
        };
    @endphp

    <style>
        .dimensions-sheet {
            min-width: 3400px;
            table-layout: fixed;
            color: #111;
            font-size: 11px;
        }
        .dimensions-sheet th,
        .dimensions-sheet td {
            border-color: #222 !important;
            padding: 4px 3px !important;
            vertical-align: middle !important;
        }
        .dimensions-sheet th {
            font-family: Georgia, serif;
            font-weight: 700;
            line-height: 1.15;
            text-align: center;
            background: #fff;
        }
        .dimensions-sheet thead th.group-head { background: #f3f4f6; }
        .dimensions-sheet thead th.sub-head { background: #f8fafc; }
        .dimensions-sheet th a {
            color: #007bff;
            display: block;
            min-height: 16px;
        }
        .dimensions-sheet th a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .dimensions-sheet .sort-icon {
            color: #007bff;
            font-size: 12px;
            line-height: 1;
            margin-left: 2px;
            vertical-align: 1px;
        }
        .dimensions-sheet .cell-lines span {
            display: block;
            min-height: 16px;
        }
        .dimensions-sheet .text-red {
            color: #ff0000;
            font-weight: 700;
        }
        .dimensions-sheet .dim-input {
            background: #fffce8;
            border: 1px solid #e2e8f0;
            border-radius: 2px;
            font-size: 11px;
            height: 24px;
            padding: 1px 3px;
            text-align: center;
            width: 100%;
        }
        .dimensions-sheet .dim-input:focus {
            background: #fff;
            outline: 1px solid #80bdff;
        }
        .dimensions-sheet .readonly-cell {
            background: #f8fafc;
            font-variant-numeric: tabular-nums;
            text-align: right;
            white-space: nowrap;
        }
        .dimensions-sheet .same-as-picker { position: relative; }
        .dimensions-sheet .same-as-results {
            background: #fff;
            border: 1px solid #222;
            box-shadow: 0 8px 18px rgba(0,0,0,.12);
            left: 0;
            max-height: 220px;
            overflow-y: auto;
            position: absolute;
            right: 0;
            top: 100%;
            z-index: 30;
        }
        .dimensions-sheet .same-as-option {
            cursor: pointer;
            display: block;
            padding: 6px 8px;
            text-align: left;
            width: 100%;
        }
        .dimensions-sheet .same-as-option:hover { background: #eef6ff; }
        .dimensions-sheet .row-saving { opacity: .55; pointer-events: none; }
        .filter-group-title {
            font-size: 13px;
            font-weight: 700;
            margin: 12px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
        }
    </style>

    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="align-items-center">
            <h1 class="h3">{{ translate('Weight And Dimension Master') }}</h1>
            <p class="text-muted mb-0">{{ translate('Net = contents (qty × piece gm). Gross = stored pack/case weight. In KG = gm ÷ 1000. CBM = L × W × H (cm) ÷ 1,000,000. Same as copies packing values once; click Save.') }}</p>
        </div>
    </div>

    <div class="card">
        <form action="{{ route('products.dimensions') }}" method="GET" id="product-dimensions-filters">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <div class="mb-2">
                    <h5 class="mb-0 h6">
                        {{ translate('SKU Weight & Dimensions') }}
                        <span class="badge badge-soft-secondary ml-1">{{ $stocks->total() }}</span>
                    </h5>
                    @if($filtersApplied)
                        <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap align-items-center">
                    <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal"
                            data-target="#productDimensionsFilterModal">
                        {{ translate('Open Filters') }}
                    </button>
                    <a href="{{ route('products.dimensions') }}" class="btn btn-danger mb-2">
                        {{ translate('Reset') }}
                    </a>
                </div>
            </div>

            <div class="modal fade" id="productDimensionsFilterModal" tabindex="-1" role="dialog"
                 aria-labelledby="productDimensionsFilterModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="productDimensionsFilterModalLabel">
                                {{ translate('Filter Weight And Dimension Master') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="filter-group-title">{{ translate('Product') }}</div>
                            <div class="row gutters-5">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="product_name">{{ translate('Product Name') }}</label>
                                    <input type="text" class="form-control" id="product_name" name="product_name"
                                           value="{{ $productName }}" placeholder="{{ translate('Product Name') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="sku">{{ translate('SKU') }}</label>
                                    <input type="text" class="form-control" id="sku" name="sku"
                                           value="{{ $sku }}" placeholder="{{ translate('Enter SKU') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="variant">{{ translate('Full Variant') }}</label>
                                    <input type="text" class="form-control" id="variant" name="variant"
                                           value="{{ $variant }}" placeholder="{{ translate('Variant') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="category_id">{{ translate('Category') }}</label>
                                    <select class="form-control aiz-selectpicker" id="category_id" name="category_id" data-live-search="true">
                                        <option value="">{{ translate('All') }}</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                                                {{ $category->getTranslation('name') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="brand_id">{{ translate('Brand') }}</label>
                                    <select class="form-control aiz-selectpicker" id="brand_id" name="brand_id" data-live-search="true">
                                        <option value="">{{ translate('All') }}</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}" @selected((string) $brandId === (string) $brand->id)>
                                                {{ $brand->getTranslation('name') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                {!! $rangeInput('min_qty', translate('MOQ')) !!}
                                {!! $rangeInput('count', translate('Package Count')) !!}
                            </div>

                            @foreach($packingGroups as $groupKey => $group)
                                <div class="filter-group-title">{{ translate($group['label']) }}</div>
                                <div class="row gutters-5">
                                    {!! $rangeInput($groupKey.'_qty', translate('Qty')) !!}
                                    {!! $rangeInput($groupKey.'_net', translate('Net (gm)')) !!}
                                    {!! $rangeInput($groupKey.'_net_kg', translate('Net In KG')) !!}
                                    {!! $rangeInput($groupKey.'_gross', translate('Gross (gm)')) !!}
                                    {!! $rangeInput($groupKey.'_gross_kg', translate('Gross In KG')) !!}
                                    {!! $rangeInput($groupKey.'_length', translate('L (cm)')) !!}
                                    {!! $rangeInput($groupKey.'_width', translate('W (cm)')) !!}
                                    {!! $rangeInput($groupKey.'_height', translate('H (cm)')) !!}
                                    {!! $rangeInput($groupKey.'_cbm', translate('CBM')) !!}
                                    <div class="col-md-3 mb-2 d-flex align-items-end">
                                        <label class="aiz-checkbox">
                                            <input type="checkbox" name="missing_{{ $groupKey }}" value="1" @checked(request('missing_'.$groupKey) === '1')>
                                            <span class="aiz-square-check"></span>
                                            <span>{{ translate('Missing in this group') }}</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach

                            <div class="filter-group-title">{{ translate('Sort & missing') }}</div>
                            <div class="row gutters-5">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="sort_by">{{ translate('Sort By') }}</label>
                                    <select class="form-control aiz-selectpicker" id="sort_by" name="sort_by" data-live-search="true">
                                        <option value="product_name" @selected($sortBy === 'product_name')>{{ translate('Product Name') }}</option>
                                        <option value="sku" @selected($sortBy === 'sku')>{{ translate('SKU') }}</option>
                                        <option value="variant" @selected($sortBy === 'variant')>{{ translate('Full Variant') }}</option>
                                        <option value="min_qty" @selected($sortBy === 'min_qty')>{{ translate('MOQ') }}</option>
                                        <option value="count" @selected($sortBy === 'count')>{{ translate('Package Count') }}</option>
                                        @foreach($packingGroups as $groupKey => $group)
                                            <option value="{{ $groupKey }}_qty" @selected($sortBy === $groupKey.'_qty')>{{ translate($group['label']) }} — {{ translate('Qty') }}</option>
                                            <option value="{{ $groupKey }}_net" @selected($sortBy === $groupKey.'_net')>{{ translate($group['label']) }} — {{ translate('Net') }}</option>
                                            <option value="{{ $groupKey }}_net_kg" @selected($sortBy === $groupKey.'_net_kg')>{{ translate($group['label']) }} — {{ translate('Net In KG') }}</option>
                                            <option value="{{ $groupKey }}_gross" @selected($sortBy === $groupKey.'_gross')>{{ translate($group['label']) }} — {{ translate('Gross') }}</option>
                                            <option value="{{ $groupKey }}_gross_kg" @selected($sortBy === $groupKey.'_gross_kg')>{{ translate($group['label']) }} — {{ translate('Gross In KG') }}</option>
                                            <option value="{{ $groupKey }}_length" @selected($sortBy === $groupKey.'_length')>{{ translate($group['label']) }} — {{ translate('L') }}</option>
                                            <option value="{{ $groupKey }}_width" @selected($sortBy === $groupKey.'_width')>{{ translate($group['label']) }} — {{ translate('W') }}</option>
                                            <option value="{{ $groupKey }}_height" @selected($sortBy === $groupKey.'_height')>{{ translate($group['label']) }} — {{ translate('H') }}</option>
                                            <option value="{{ $groupKey }}_cbm" @selected($sortBy === $groupKey.'_cbm')>{{ translate($group['label']) }} — {{ translate('CBM') }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label" for="sort_dir">{{ translate('Direction') }}</label>
                                    <select class="form-control aiz-selectpicker" id="sort_dir" name="sort_dir">
                                        <option value="asc" @selected($sortDir === 'asc')>{{ translate('Ascending') }}</option>
                                        <option value="desc" @selected($sortDir === 'desc')>{{ translate('Descending') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3 d-flex align-items-end">
                                    <label class="aiz-checkbox">
                                        <input type="checkbox" name="missing_any" value="1" @checked(request('missing_any') === '1')>
                                        <span class="aiz-square-check"></span>
                                        <span>{{ translate('Missing any packing field') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table mb-0 table-bordered dimensions-sheet">
                    <thead>
                    <tr>
                        <th rowspan="3" style="width: 170px">{!! $sortHeading('sku', translate('SKU')) !!}{!! $sortHeading('product_name', translate('Product')) !!}</th>
                        <th rowspan="3" style="width: 130px">{!! $sortHeading('variant', translate('Full Variant')) !!}</th>
                        <th rowspan="3" style="width: 160px">{{ translate('Same as') }}</th>
                        <th rowspan="3" style="width: 70px">{!! $sortHeading('min_qty', translate('MOQ')) !!}</th>
                        <th rowspan="3" style="width: 80px">{!! $sortHeading('count', translate('Package Count')) !!}</th>
                        @foreach($packingGroups as $group)
                            <th colspan="9" class="group-head">{{ translate($group['label']) }}</th>
                        @endforeach
                        <th rowspan="3" style="width: 70px">{{ translate('Save') }}</th>
                    </tr>
                    <tr>
                        @foreach($packingGroups as $group)
                            <th colspan="5" class="sub-head">{{ translate('Weight ( gm )') }}</th>
                            <th colspan="4" class="sub-head">{{ translate('Diamension ( cm)') }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach($packingGroups as $groupKey => $group)
                            <th style="width: 58px">{!! $sortHeading($groupKey.'_qty', translate('Qty')) !!}</th>
                            <th style="width: 62px">{!! $sortHeading($groupKey.'_net', translate('Net')) !!}</th>
                            <th style="width: 58px">{!! $sortHeading($groupKey.'_net_kg', translate('In KG')) !!}</th>
                            <th style="width: 62px">{!! $sortHeading($groupKey.'_gross', translate('Gross')) !!}</th>
                            <th style="width: 58px">{!! $sortHeading($groupKey.'_gross_kg', translate('In KG')) !!}</th>
                            <th style="width: 58px">{!! $sortHeading($groupKey.'_length', translate('L')) !!}</th>
                            <th style="width: 58px">{!! $sortHeading($groupKey.'_width', translate('W')) !!}</th>
                            <th style="width: 58px">{!! $sortHeading($groupKey.'_height', translate('H')) !!}</th>
                            <th style="width: 64px">{!! $sortHeading($groupKey.'_cbm', translate('CBM')) !!}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($stocks as $stock)
                        @php
                            $product = $stock->product;
                            $variantLabel = trim((string) $stock->variant);
                        @endphp
                        <tr class="dimension-row" data-stock-id="{{ $stock->id }}">
                            <td class="cell-lines text-left">
                                <span>{{ $stock->sku }}</span>
                                <span class="text-red">{{ $product?->name }}</span>
                                @if($stock->is_hidden)
                                    <span class="badge badge-inline badge-soft-secondary">{{ translate('Hidden') }}</span>
                                @endif
                            </td>
                            <td class="text-left">{{ $variantLabel !== '' ? $variantLabel : translate('Default') }}</td>
                            <td>
                                <div class="same-as-picker" data-exclude="{{ $stock->id }}">
                                    <input type="text" class="form-control form-control-sm same-as-input"
                                           placeholder="{{ translate('Same as') }}" autocomplete="off">
                                    <div class="same-as-results" hidden></div>
                                </div>
                            </td>
                            <td><input type="number" lang="en" min="1" step="1" class="dim-input" data-field="min_qty" value="{{ $formatNum($stock->min_qty ?? 1, 0) }}"></td>
                            <td><input type="number" lang="en" min="0" step="1" class="dim-input" data-field="count" value="{{ $formatNum($stock->count, 0) }}"></td>
                            @foreach($packingGroups as $groupKey => $group)
                                @php
                                    $qtyValue = $group['qty_fixed'] ?? null;
                                    if ($qtyValue === null && !empty($group['qty_field'])) {
                                        $qtyValue = $stock->{$group['qty_field']};
                                    }
                                    $netValue = !empty($group['net_field'])
                                        ? $stock->{$group['net_field']}
                                        : $factorProduct($stock, $group['net_factors'] ?? []);
                                    $grossValue = !empty($group['gross_field'])
                                        ? $stock->{$group['gross_field']}
                                        : $netValue;
                                @endphp
                                <td>
                                    @if($group['qty_fixed'] !== null)
                                        <span class="readonly-cell d-block" data-qty="{{ $groupKey }}">{{ $formatNum($group['qty_fixed'], 0) }}</span>
                                    @else
                                        <input type="number" lang="en" min="0" step="any" class="dim-input" data-field="{{ $group['qty_field'] }}" value="{{ $formatNum($qtyValue) }}">
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($group['net_field']))
                                        <input type="number" lang="en" min="0" step="0.001" class="dim-input js-weight" data-group="{{ $groupKey }}" data-field="{{ $group['net_field'] }}" value="{{ $formatNum($netValue, 3) }}">
                                    @else
                                        <span class="readonly-cell d-block" data-net="{{ $groupKey }}">{{ $formatNum($netValue, 3) }}</span>
                                    @endif
                                </td>
                                <td class="readonly-cell" data-net-kg="{{ $groupKey }}">{{ $kg($netValue) }}</td>
                                <td>
                                    @if(!empty($group['gross_field']))
                                        <input type="number" lang="en" min="0" step="0.001" class="dim-input js-weight" data-group="{{ $groupKey }}" data-field="{{ $group['gross_field'] }}" value="{{ $formatNum($grossValue, 3) }}">
                                    @else
                                        <span class="readonly-cell d-block" data-gross="{{ $groupKey }}">{{ $formatNum($grossValue, 3) }}</span>
                                    @endif
                                </td>
                                <td class="readonly-cell" data-gross-kg="{{ $groupKey }}">{{ $kg($grossValue) }}</td>
                                <td><input type="number" lang="en" min="0" step="0.01" class="dim-input js-dim" data-group="{{ $groupKey }}" data-field="{{ $group['length'] }}" value="{{ $formatNum($stock->{$group['length']}) }}"></td>
                                <td><input type="number" lang="en" min="0" step="0.01" class="dim-input js-dim" data-group="{{ $groupKey }}" data-field="{{ $group['width'] }}" value="{{ $formatNum($stock->{$group['width']}) }}"></td>
                                <td><input type="number" lang="en" min="0" step="0.01" class="dim-input js-dim" data-group="{{ $groupKey }}" data-field="{{ $group['height'] }}" value="{{ $formatNum($stock->{$group['height']}) }}"></td>
                                <td class="readonly-cell" data-cbm="{{ $groupKey }}">{{ $cbm($stock->{$group['length']}, $stock->{$group['width']}, $stock->{$group['height']}) }}</td>
                            @endforeach
                            <td>
                                <button type="button" class="btn btn-sm btn-primary btn-save-dims">{{ translate('Save') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 6 + (count($packingGroups) * 9) }}" class="text-center">{{ translate('No records found') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="aiz-pagination mt-3">
                {{ $stocks->links() }}
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function () {
            const csrfToken = '{{ csrf_token() }}';
            const searchUrl = @json(route('products.dimensions.same_as'));
            const updateUrlTemplate = @json(route('products.dimensions.update', ['id' => '__ID__']));
            const groups = @json($jsGroups);

            function uniqueFields(fields) {
                return fields.filter(function (field, index) {
                    return field && fields.indexOf(field) === index;
                });
            }

            const editableFields = uniqueFields(['min_qty'].concat(Object.keys(groups).reduce(function (fields, key) {
                const group = groups[key];
                return fields.concat([
                    group.qtyField,
                    group.netField,
                    group.grossField,
                    group.length,
                    group.width,
                    group.height
                ]);
            }, [])));
            const copyFields = editableFields.filter(function (field) {
                return field !== 'min_qty';
            });

            function notify(type, message) {
                if (window.AIZ && AIZ.plugins && AIZ.plugins.notify) {
                    AIZ.plugins.notify(type, message);
                    return;
                }
                alert(message);
            }

            function fieldValue($row, field) {
                if (!field) {
                    return '';
                }
                const $input = $row.find('[data-field="' + field + '"]').first();
                return $input.length ? $.trim($input.val()) : '';
            }

            function factorProduct($row, factors) {
                if (!factors || !factors.length) {
                    return '';
                }
                let product = 1;
                for (let i = 0; i < factors.length; i += 1) {
                    const raw = fieldValue($row, factors[i]);
                    if (raw === '') {
                        return '';
                    }
                    const value = Number(raw);
                    if (!isFinite(value)) {
                        return '';
                    }
                    product *= value;
                }
                return product;
            }

            function formatCalc(value, decimals) {
                if (value === '' || value === null) {
                    return '';
                }
                const number = Number(value);
                if (!isFinite(number)) {
                    return '';
                }
                if (decimals === 0) {
                    return String(Math.round(number));
                }
                return String(Number(number.toFixed(decimals)));
            }

            function toKg(grams) {
                if (grams === '') {
                    return '';
                }
                const value = Number(grams) / 1000;
                if (!isFinite(value) || value < 0) {
                    return '';
                }
                return value.toFixed(3);
            }

            function toCbm(length, width, height) {
                if (length === '' || width === '' || height === '') {
                    return '';
                }
                const value = (Number(length) * Number(width) * Number(height)) / 1000000;
                if (!isFinite(value) || value < 0) {
                    return '';
                }
                return value.toFixed(4);
            }

            function refreshCalculated($row) {
                Object.keys(groups).forEach(function (key) {
                    const group = groups[key];
                    let net = group.netField ? fieldValue($row, group.netField) : factorProduct($row, group.netFactors);
                    let gross = group.grossField ? fieldValue($row, group.grossField) : net;
                    if (!group.netField) {
                        $row.find('[data-net="' + key + '"]').text(formatCalc(net, 3));
                    }
                    if (!group.grossField) {
                        $row.find('[data-gross="' + key + '"]').text(formatCalc(gross, 3));
                    }
                    $row.find('[data-net-kg="' + key + '"]').text(toKg(net));
                    $row.find('[data-gross-kg="' + key + '"]').text(toKg(gross));
                    $row.find('[data-cbm="' + key + '"]').text(toCbm(
                        fieldValue($row, group.length),
                        fieldValue($row, group.width),
                        fieldValue($row, group.height)
                    ));
                });
            }

            function applyFields($row, values, fields) {
                fields.forEach(function (field) {
                    if (Object.prototype.hasOwnProperty.call(values, field)) {
                        $row.find('[data-field="' + field + '"]').val(values[field] != null ? values[field] : '');
                    }
                });
                refreshCalculated($row);
            }

            $(document).on('input', '.dim-input', function () {
                const $input = $(this);
                const $row = $input.closest('.dimension-row');
                const field = $input.data('field');
                $row.find('[data-field="' + field + '"]').not($input).val($input.val());
                refreshCalculated($row);
            });

            let searchTimer = null;
            $(document).on('input', '.same-as-input', function () {
                const $input = $(this);
                const $picker = $input.closest('.same-as-picker');
                const $results = $picker.find('.same-as-results');
                const term = $.trim($input.val());
                clearTimeout(searchTimer);
                if (term.length < 2) {
                    $results.attr('hidden', true).empty();
                    return;
                }
                searchTimer = setTimeout(function () {
                    $.get(searchUrl, {
                        q: term,
                        exclude: $picker.data('exclude')
                    }).done(function (response) {
                        const items = (response && response.results) ? response.results : [];
                        if (!items.length) {
                            $results.html('<div class="same-as-option text-muted">{{ translate('No matching SKUs') }}</div>').removeAttr('hidden');
                            return;
                        }
                        const html = items.map(function (item) {
                            return '<button type="button" class="same-as-option" data-id="' + item.id + '">' +
                                $('<div>').text(item.label).html() +
                                '</button>';
                        }).join('');
                        $results.html(html).removeAttr('hidden');
                        $results.find('.same-as-option').each(function (index) {
                            $(this).data('dims', items[index].dims);
                        });
                    }).fail(function () {
                        notify('danger', '{{ translate('Unable to search SKUs') }}');
                    });
                }, 250);
            });

            $(document).on('click', '.same-as-option', function () {
                const $option = $(this);
                const $row = $option.closest('.dimension-row');
                const dims = $option.data('dims');
                if (!dims) {
                    return;
                }
                applyFields($row, dims, copyFields);
                $row.find('.same-as-input').val($option.text());
                $row.find('.same-as-results').attr('hidden', true).empty();
            });

            $(document).on('click', function (event) {
                if (!$(event.target).closest('.same-as-picker').length) {
                    $('.same-as-results').attr('hidden', true);
                }
            });

            $(document).on('click', '.btn-save-dims', function () {
                const $row = $(this).closest('.dimension-row');
                const payload = { _token: csrfToken };
                editableFields.forEach(function (field) {
                    payload[field] = fieldValue($row, field);
                });
                $row.addClass('row-saving');
                $.ajax({
                    url: updateUrlTemplate.replace('__ID__', $row.data('stock-id')),
                    method: 'POST',
                    data: payload
                }).done(function (response) {
                    if (response && response.dims) {
                        applyFields($row, response.dims, editableFields);
                    }
                    notify('success', (response && response.message) ? response.message : '{{ translate('Dimensions saved') }}');
                }).fail(function (xhr) {
                    let message = '{{ translate('Unable to save dimensions') }}';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values(xhr.responseJSON.errors)[0][0];
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    notify('danger', message);
                }).always(function () {
                    $row.removeClass('row-saving');
                });
            });
        })();
    </script>
@endsection
