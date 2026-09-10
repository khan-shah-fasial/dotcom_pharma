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
        $formatDim = function ($value) {
            if ($value === null || $value === '') {
                return '';
            }

            $number = (float) $value;
            if (!is_finite($number)) {
                return '';
            }

            return rtrim(rtrim(sprintf('%.2f', $number), '0'), '.') ?: '0';
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
    @endphp

    <style>
        .dimensions-sheet {
            min-width: 1480px;
            table-layout: fixed;
            color: #111;
            font-size: 11px;
        }
        .dimensions-sheet th,
        .dimensions-sheet td {
            border-color: #222 !important;
            padding: 6px 5px !important;
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
            background: #f8f9fa;
        }
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
            margin-left: 3px;
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
            height: 26px;
            padding: 2px 4px;
            text-align: center;
            width: 100%;
        }
        .dimensions-sheet .dim-input:focus {
            background: #fff;
            outline: 1px solid #80bdff;
        }
        .dimensions-sheet .cbm-cell {
            background: #f8fafc;
            font-variant-numeric: tabular-nums;
            text-align: right;
            white-space: nowrap;
        }
        .dimensions-sheet .same-as-picker {
            position: relative;
        }
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
            z-index: 20;
        }
        .dimensions-sheet .same-as-option {
            cursor: pointer;
            display: block;
            padding: 6px 8px;
            text-align: left;
            width: 100%;
        }
        .dimensions-sheet .same-as-option:hover,
        .dimensions-sheet .same-as-option.is-active {
            background: #eef6ff;
        }
        .dimensions-sheet .row-saving {
            opacity: .55;
            pointer-events: none;
        }
    </style>

    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="align-items-center">
            <h1 class="h3">{{ translate('Product Dimensions') }}</h1>
            <p class="text-muted mb-0">{{ translate('CBM = L × W × H (cm) ÷ 1,000,000. Same as copies values once; click Save to store them.') }}</p>
        </div>
    </div>

    <div class="card">
        <form action="{{ route('products.dimensions') }}" method="GET" id="product-dimensions-filters">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <div class="mb-2">
                    <h5 class="mb-0 h6">
                        {{ translate('SKU Dimensions') }}
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
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="productDimensionsFilterModalLabel">
                                {{ translate('Filter Product Dimensions') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row gutters-5">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="product_name">{{ translate('Product Name') }}</label>
                                    <input type="text" class="form-control" id="product_name" name="product_name"
                                           value="{{ $productName }}" placeholder="{{ translate('Product Name') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="sku">{{ translate('SKU') }}</label>
                                    <input type="text" class="form-control" id="sku" name="sku"
                                           value="{{ $sku }}" placeholder="{{ translate('Enter SKU') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="category_id">{{ translate('Category') }}</label>
                                    <select class="form-control aiz-selectpicker" id="category_id" name="category_id"
                                            data-live-search="true">
                                        <option value="">{{ translate('All') }}</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                                                {{ $category->getTranslation('name') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="brand_id">{{ translate('Brand') }}</label>
                                    <select class="form-control aiz-selectpicker" id="brand_id" name="brand_id"
                                            data-live-search="true">
                                        <option value="">{{ translate('All') }}</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}" @selected((string) $brandId === (string) $brand->id)>
                                                {{ $brand->getTranslation('name') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="sort_by">{{ translate('Sort By') }}</label>
                                    <select class="form-control aiz-selectpicker" id="sort_by" name="sort_by">
                                        <option value="product_name" @selected($sortBy === 'product_name')>{{ translate('Product Name') }}</option>
                                        <option value="sku" @selected($sortBy === 'sku')>{{ translate('SKU') }}</option>
                                        <option value="variant" @selected($sortBy === 'variant')>{{ translate('Variant') }}</option>
                                        <option value="piece_cbm" @selected($sortBy === 'piece_cbm')>{{ translate('Piece CBM') }}</option>
                                        <option value="buffer_cbm" @selected($sortBy === 'buffer_cbm')>{{ translate('Buffer CBM') }}</option>
                                        <option value="case_cbm" @selected($sortBy === 'case_cbm')>{{ translate('Case CBM') }}</option>
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
                                    <div class="form-group mb-0">
                                        <label class="aiz-checkbox">
                                            <input type="checkbox" name="missing_dimensions" value="1" @checked($missingDimensions)>
                                            <span class="aiz-square-check"></span>
                                            <span>{{ translate('Missing dimensions') }}</span>
                                        </label>
                                    </div>
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
                        <th rowspan="2" style="width: 220px">{!! $sortHeading('sku', translate('SKU')) !!}{!! $sortHeading('product_name', translate('Product')) !!}{!! $sortHeading('variant', translate('Variant')) !!}</th>
                        <th rowspan="2" style="width: 220px">{{ translate('Same as') }}</th>
                        <th colspan="4" class="group-head">{{ translate('Each Piece (Base)') }}</th>
                        <th colspan="4" class="group-head">{{ translate('Inner Buffer / Shrink') }}</th>
                        <th colspan="4" class="group-head">{{ translate('Outer Case / Shipper') }}</th>
                        <th rowspan="2" style="width: 80px">{{ translate('Save') }}</th>
                    </tr>
                    <tr>
                        <th style="width: 70px">{{ translate('L (cm)') }}</th>
                        <th style="width: 70px">{{ translate('W (cm)') }}</th>
                        <th style="width: 70px">{{ translate('H (cm)') }}</th>
                        <th style="width: 80px">{!! $sortHeading('piece_cbm', translate('CBM')) !!}</th>
                        <th style="width: 70px">{{ translate('L (cm)') }}</th>
                        <th style="width: 70px">{{ translate('W (cm)') }}</th>
                        <th style="width: 70px">{{ translate('H (cm)') }}</th>
                        <th style="width: 80px">{!! $sortHeading('buffer_cbm', translate('CBM')) !!}</th>
                        <th style="width: 70px">{{ translate('L (cm)') }}</th>
                        <th style="width: 70px">{{ translate('W (cm)') }}</th>
                        <th style="width: 70px">{{ translate('H (cm)') }}</th>
                        <th style="width: 80px">{!! $sortHeading('case_cbm', translate('CBM')) !!}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($stocks as $stock)
                        @php
                            $product = $stock->product;
                            $variant = trim((string) $stock->variant);
                        @endphp
                        <tr class="dimension-row" data-stock-id="{{ $stock->id }}">
                            <td class="cell-lines text-left">
                                <span>{{ $stock->sku }}</span>
                                <span class="text-red">{{ $product?->name }}</span>
                                <span>
                                    {{ $variant !== '' ? $variant : translate('Default') }}
                                    @if($stock->is_hidden)
                                        <span class="badge badge-inline badge-soft-secondary">{{ translate('Hidden') }}</span>
                                    @endif
                                </span>
                            </td>
                            <td>
                                <div class="same-as-picker" data-exclude="{{ $stock->id }}">
                                    <input type="text" class="form-control form-control-sm same-as-input"
                                           placeholder="{{ translate('Same as') }}" autocomplete="off">
                                    <div class="same-as-results" hidden></div>
                                </div>
                            </td>
                            <td><input type="number" lang="en" min="0" step="0.01" class="dim-input" data-field="length" value="{{ $formatDim($stock->length) }}"></td>
                            <td><input type="number" lang="en" min="0" step="0.01" class="dim-input" data-field="width" value="{{ $formatDim($stock->width) }}"></td>
                            <td><input type="number" lang="en" min="0" step="0.01" class="dim-input" data-field="height" value="{{ $formatDim($stock->height) }}"></td>
                            <td class="cbm-cell" data-cbm="piece">{{ $cbm($stock->length, $stock->width, $stock->height) }}</td>
                            <td><input type="number" lang="en" min="0" step="0.01" class="dim-input" data-field="buffer_length" value="{{ $formatDim($stock->buffer_length) }}"></td>
                            <td><input type="number" lang="en" min="0" step="0.01" class="dim-input" data-field="buffer_width" value="{{ $formatDim($stock->buffer_width) }}"></td>
                            <td><input type="number" lang="en" min="0" step="0.01" class="dim-input" data-field="buffer_height" value="{{ $formatDim($stock->buffer_height) }}"></td>
                            <td class="cbm-cell" data-cbm="buffer">{{ $cbm($stock->buffer_length, $stock->buffer_width, $stock->buffer_height) }}</td>
                            <td><input type="number" lang="en" min="0" step="0.01" class="dim-input" data-field="case_length" value="{{ $formatDim($stock->case_length) }}"></td>
                            <td><input type="number" lang="en" min="0" step="0.01" class="dim-input" data-field="case_width" value="{{ $formatDim($stock->case_width) }}"></td>
                            <td><input type="number" lang="en" min="0" step="0.01" class="dim-input" data-field="case_height" value="{{ $formatDim($stock->case_height) }}"></td>
                            <td class="cbm-cell" data-cbm="case">{{ $cbm($stock->case_length, $stock->case_width, $stock->case_height) }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary btn-save-dims">{{ translate('Save') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="text-center">{{ translate('No records found') }}</td>
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
            const dimFields = [
                'length', 'width', 'height',
                'buffer_length', 'buffer_width', 'buffer_height',
                'case_length', 'case_width', 'case_height'
            ];

            function notify(type, message) {
                if (window.AIZ && AIZ.plugins && AIZ.plugins.notify) {
                    AIZ.plugins.notify(type, message);
                    return;
                }
                alert(message);
            }

            function cbm(length, width, height) {
                if (length === '' || width === '' || height === '') {
                    return '';
                }
                const value = (Number(length) * Number(width) * Number(height)) / 1000000;
                if (!isFinite(value) || value < 0) {
                    return '';
                }
                return value.toFixed(4);
            }

            function fieldValue($row, field) {
                return $.trim($row.find('[data-field="' + field + '"]').val());
            }

            function refreshCbm($row) {
                $row.find('[data-cbm="piece"]').text(cbm(fieldValue($row, 'length'), fieldValue($row, 'width'), fieldValue($row, 'height')));
                $row.find('[data-cbm="buffer"]').text(cbm(fieldValue($row, 'buffer_length'), fieldValue($row, 'buffer_width'), fieldValue($row, 'buffer_height')));
                $row.find('[data-cbm="case"]').text(cbm(fieldValue($row, 'case_length'), fieldValue($row, 'case_width'), fieldValue($row, 'case_height')));
            }

            function applyDims($row, dims) {
                dimFields.forEach(function (field) {
                    $row.find('[data-field="' + field + '"]').val(dims[field] != null ? dims[field] : '');
                });
                refreshCbm($row);
            }

            $(document).on('input', '.dim-input', function () {
                refreshCbm($(this).closest('.dimension-row'));
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
                const $input = $row.find('.same-as-input');
                const dims = $option.data('dims');
                if (!dims) {
                    return;
                }
                applyDims($row, dims);
                $input.val($option.text());
                $row.find('.same-as-results').attr('hidden', true).empty();
            });

            $(document).on('click', function (event) {
                if (!$(event.target).closest('.same-as-picker').length) {
                    $('.same-as-results').attr('hidden', true);
                }
            });

            $(document).on('click', '.btn-save-dims', function () {
                const $row = $(this).closest('.dimension-row');
                const stockId = $row.data('stock-id');
                const payload = { _token: csrfToken };
                dimFields.forEach(function (field) {
                    payload[field] = fieldValue($row, field);
                });
                $row.addClass('row-saving');
                $.ajax({
                    url: updateUrlTemplate.replace('__ID__', stockId),
                    method: 'POST',
                    data: payload
                }).done(function (response) {
                    if (response && response.dims) {
                        applyDims($row, response.dims);
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
