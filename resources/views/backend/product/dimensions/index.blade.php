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
        $isInputMode = function ($mode) {
            return in_array($mode, ['fill', 'auto_edit'], true);
        };
        $displayValue = function ($stock, $field, $mode, array $factors, int $decimals) use ($formatNum, $factorProduct) {
            $stored = $field ? ($stock->{$field} ?? null) : null;
            if ($stored !== null && $stored !== '') {
                return $formatNum($stored, $decimals);
            }
            if (in_array($mode, ['auto_edit', 'auto_lock'], true) && !empty($factors)) {
                return $formatNum($factorProduct($stock, $factors), $decimals);
            }

            return $formatNum($stored, $decimals);
        };
        $qtyDecimals = function ($field) {
            return in_array($field, ['qty_per_piece', 'qty_per_buffer_box', 'total_qty_per_case', 'count', 'min_qty'], true) ? 0 : 2;
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
        .dimensions-sheet thead th.group-head {
            background: var(--group-color) !important;
        }
        .dimensions-sheet thead th.sub-head { background: #f8fafc !important; }
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
            background: #fff;
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
            background: #fff;
            font-variant-numeric: tabular-nums;
            text-align: right;
            white-space: nowrap;
        }
        .dimensions-sheet .tone-cell {
            background: var(--group-tone) !important;
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
            <p class="text-muted mb-0">{{ translate('MOQ = buffer qty. Package Count = buffers per case. Buffer Net/Gross = piece × buffer qty. Buffer/Case and Qty Per Case Net/Gross = buffer × buffers per case. Outer Gross copies Qty Per Case Gross. Outer QTY is always 1. Yellow = fill or auto-but-editable. In KG = gm ÷ 1000. CBM = L × W × H (cm) ÷ 1,000,000.') }}</p>
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
                            <th colspan="9" class="group-head" style="--group-color: {{ $group['color'] }}; background: {{ $group['color'] }} !important">{{ translate($group['label']) }}</th>
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
                                    $qtyMode = $group['qty_mode'] ?? 'fill';
                                    $netMode = $group['net_mode'] ?? 'fill';
                                    $grossMode = $group['gross_mode'] ?? 'fill';
                                    $dimMode = $group['dim_mode'] ?? 'fill';
                                    $qtyDec = $qtyDecimals($group['qty_field'] ?? '');
                                    $qtyValue = $group['qty_fixed'] ?? null;
                                    if ($qtyValue === null) {
                                        $qtyValue = $displayValue($stock, $group['qty_field'] ?? null, $qtyMode, $group['qty_factors'] ?? [], $qtyDec);
                                    } else {
                                        $qtyValue = $formatNum($qtyValue, 0);
                                    }
                                    $netValue = $displayValue($stock, $group['net_field'] ?? null, $netMode, $group['net_factors'] ?? [], 3);
                                    $grossValue = $displayValue($stock, $group['gross_field'] ?? null, $grossMode, $group['gross_factors'] ?? [], 3);
                                    $tone = $group['tone'] ?? $group['color'] ?? '#ffffff';
                                @endphp
                                <td>
                                    @if($group['qty_fixed'] !== null || !$isInputMode($qtyMode) || empty($group['qty_field']))
                                        <span class="readonly-cell d-block" data-qty="{{ $groupKey }}">{{ $qtyValue }}</span>
                                    @else
                                        <input type="number" lang="en" min="0" step="{{ $qtyDec === 0 ? '1' : 'any' }}" class="dim-input" data-field="{{ $group['qty_field'] }}" value="{{ $qtyValue }}">
                                    @endif
                                </td>
                                <td>
                                    @if($isInputMode($netMode) && !empty($group['net_field']))
                                        <input type="number" lang="en" min="0" step="0.001" class="dim-input js-weight" data-group="{{ $groupKey }}" data-field="{{ $group['net_field'] }}" value="{{ $netValue }}">
                                    @else
                                        <span class="readonly-cell d-block" data-net="{{ $groupKey }}">{{ $netValue }}</span>
                                    @endif
                                </td>
                                <td class="readonly-cell tone-cell" data-net-kg="{{ $groupKey }}" style="--group-tone: {{ $tone }}; background: {{ $tone }} !important">{{ $kg($netValue === '' ? null : $netValue) }}</td>
                                <td>
                                    @if($isInputMode($grossMode) && !empty($group['gross_field']))
                                        <input type="number" lang="en" min="0" step="0.001" class="dim-input js-weight" data-group="{{ $groupKey }}" data-field="{{ $group['gross_field'] }}" value="{{ $grossValue }}">
                                    @else
                                        <span class="readonly-cell d-block" data-gross="{{ $groupKey }}">{{ $grossValue }}</span>
                                    @endif
                                </td>
                                <td class="readonly-cell tone-cell" data-gross-kg="{{ $groupKey }}" style="--group-tone: {{ $tone }}; background: {{ $tone }} !important">{{ $kg($grossValue === '' ? null : $grossValue) }}</td>
                                @if($isInputMode($dimMode))
                                    <td><input type="number" lang="en" min="0" step="0.01" class="dim-input js-dim" data-group="{{ $groupKey }}" data-field="{{ $group['length'] }}" value="{{ $formatNum($stock->{$group['length']}) }}"></td>
                                    <td><input type="number" lang="en" min="0" step="0.01" class="dim-input js-dim" data-group="{{ $groupKey }}" data-field="{{ $group['width'] }}" value="{{ $formatNum($stock->{$group['width']}) }}"></td>
                                    <td><input type="number" lang="en" min="0" step="0.01" class="dim-input js-dim" data-group="{{ $groupKey }}" data-field="{{ $group['height'] }}" value="{{ $formatNum($stock->{$group['height']}) }}"></td>
                                @else
                                    <td class="readonly-cell" data-length="{{ $groupKey }}">{{ $formatNum($stock->{$group['length']}) }}</td>
                                    <td class="readonly-cell" data-width="{{ $groupKey }}">{{ $formatNum($stock->{$group['width']}) }}</td>
                                    <td class="readonly-cell" data-height="{{ $groupKey }}">{{ $formatNum($stock->{$group['height']}) }}</td>
                                @endif
                                <td class="readonly-cell tone-cell" data-cbm="{{ $groupKey }}" style="--group-tone: {{ $tone }}; background: {{ $tone }} !important">{{ $cbm($stock->{$group['length']}, $stock->{$group['width']}, $stock->{$group['height']}) }}</td>
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
            const autoEdits = @json($jsAutoEdits);

            function uniqueFields(fields) {
                return fields.filter(function (field, index) {
                    return field && fields.indexOf(field) === index;
                });
            }

            function isInputMode(mode) {
                return mode === 'fill' || mode === 'auto_edit';
            }

            const editableFields = uniqueFields(['min_qty'].concat(Object.keys(groups).reduce(function (fields, key) {
                const group = groups[key];
                if (isInputMode(group.qtyMode)) {
                    fields.push(group.qtyField);
                }
                if (isInputMode(group.netMode)) {
                    fields.push(group.netField);
                }
                if (isInputMode(group.grossMode)) {
                    fields.push(group.grossField);
                }
                if (isInputMode(group.dimMode)) {
                    fields.push(group.length, group.width, group.height);
                }
                return fields;
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

            function fieldInputs($row, field) {
                if (!field) {
                    return $();
                }
                return $row.find('[data-field="' + field + '"]');
            }

            function fieldValue($row, field) {
                const $input = fieldInputs($row, field).first();
                return $input.length ? $.trim($input.val()) : '';
            }

            function setField($row, field, value) {
                fieldInputs($row, field).val(value == null ? '' : value);
            }

            function autoEditRule(field) {
                return autoEdits.find(function (rule) {
                    return rule.field === field;
                }) || null;
            }

            function isDirty($row, field) {
                return fieldInputs($row, field).first().attr('data-dirty') === '1';
            }

            function markDirty($row, field) {
                fieldInputs($row, field).attr('data-dirty', '1');
            }

            function markLinked($row, field) {
                fieldInputs($row, field).removeAttr('data-dirty');
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

            function numbersEqual(left, right, decimals) {
                if (left === '' && right === '') {
                    return true;
                }
                if (left === '' || right === '') {
                    return false;
                }
                const leftNumber = Number(left);
                const rightNumber = Number(right);
                if (!isFinite(leftNumber) || !isFinite(rightNumber)) {
                    return false;
                }
                return Number(leftNumber.toFixed(decimals)) === Number(rightNumber.toFixed(decimals));
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

            function applyLinkedAutoEdits($row) {
                autoEdits.forEach(function (rule) {
                    if (isDirty($row, rule.field)) {
                        return;
                    }
                    const formatted = formatCalc(factorProduct($row, rule.factors), rule.decimals);
                    if (formatted !== '') {
                        setField($row, rule.field, formatted);
                    }
                });
            }

            function refreshCalculated($row) {
                applyLinkedAutoEdits($row);
                Object.keys(groups).forEach(function (key) {
                    const group = groups[key];
                    let net = isInputMode(group.netMode) && group.netField
                        ? fieldValue($row, group.netField)
                        : factorProduct($row, group.netFactors);
                    let gross = isInputMode(group.grossMode) && group.grossField
                        ? fieldValue($row, group.grossField)
                        : factorProduct($row, group.grossFactors);
                    if (group.qtyFixed == null && group.qtyMode === 'auto_lock') {
                        $row.find('[data-qty="' + key + '"]').text(formatCalc(fieldValue($row, group.qtyField), 0));
                    }
                    if (!(isInputMode(group.netMode) && group.netField)) {
                        $row.find('[data-net="' + key + '"]').text(formatCalc(net, 3));
                    }
                    if (!(isInputMode(group.grossMode) && group.grossField)) {
                        $row.find('[data-gross="' + key + '"]').text(formatCalc(gross, 3));
                    }
                    $row.find('[data-net-kg="' + key + '"]').text(toKg(net));
                    $row.find('[data-gross-kg="' + key + '"]').text(toKg(gross));
                    const length = fieldValue($row, group.length);
                    const width = fieldValue($row, group.width);
                    const height = fieldValue($row, group.height);
                    if (group.dimMode === 'auto_lock') {
                        $row.find('[data-length="' + key + '"]').text(formatCalc(length, 2));
                        $row.find('[data-width="' + key + '"]').text(formatCalc(width, 2));
                        $row.find('[data-height="' + key + '"]').text(formatCalc(height, 2));
                    }
                    $row.find('[data-cbm="' + key + '"]').text(toCbm(length, width, height));
                });
            }

            function initLinkedState($row) {
                autoEdits.forEach(function (rule) {
                    const stored = fieldValue($row, rule.field);
                    const formatted = formatCalc(factorProduct($row, rule.factors), rule.decimals);
                    if (formatted === '') {
                        markLinked($row, rule.field);
                        return;
                    }
                    if (stored === '') {
                        setField($row, rule.field, formatted);
                        markLinked($row, rule.field);
                        return;
                    }
                    if (numbersEqual(stored, formatted, rule.decimals)) {
                        markLinked($row, rule.field);
                        return;
                    }
                    markDirty($row, rule.field);
                });
                refreshCalculated($row);
            }

            function applyFields($row, values, fields) {
                fields.forEach(function (field) {
                    if (Object.prototype.hasOwnProperty.call(values, field)) {
                        setField($row, field, values[field] != null ? values[field] : '');
                    }
                });
                initLinkedState($row);
            }

            function syncAutoEditDirty($row, field) {
                const rule = autoEditRule(field);
                if (!rule) {
                    return;
                }
                const current = fieldValue($row, field);
                if (current === '') {
                    markLinked($row, field);
                    return;
                }
                const formatted = formatCalc(factorProduct($row, rule.factors), rule.decimals);
                if (formatted !== '' && numbersEqual(current, formatted, rule.decimals)) {
                    markLinked($row, field);
                    return;
                }
                markDirty($row, field);
            }

            $('.dimension-row').each(function () {
                initLinkedState($(this));
            });

            $(document).on('input', '.dim-input', function () {
                const $input = $(this);
                const $row = $input.closest('.dimension-row');
                const field = $input.data('field');
                $row.find('[data-field="' + field + '"]').not($input).val($input.val());
                syncAutoEditDirty($row, field);
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
                refreshCalculated($row);
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
