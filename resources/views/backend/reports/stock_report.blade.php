@extends('backend.layouts.app')

@section('content')
    @php
        $monthValue = function ($value) {
            if (!$value || strtotime($value) === false) {
                return '';
            }

            return date('Y-m', strtotime($value));
        };

        $dimensions = function ($length, $width, $height) {
            $values = collect([$length, $width, $height])
                ->filter(fn ($value) => $value !== null && $value !== '');

            return $values->isEmpty() ? '-' : $values->implode(' × ');
        };

        $formatUploadDate = function ($value) {
            if (!$value || strtotime($value) === false) {
                return '-';
            }

            return date('d.m.Y', strtotime($value));
        };

        $decimal = fn ($value, int $precision = 2) => $value === null || $value === ''
            ? ''
            : number_format((float) $value, $precision, '.', '');
    @endphp

    <style>
        .product-detail-sheet { color: #202124; font-size: 11px; min-width: 1900px; table-layout: auto; }
        .product-detail-sheet th,
        .product-detail-sheet td { border-color: #242424 !important; padding: 0 !important; text-align: center; vertical-align: middle !important; }
        .product-detail-sheet thead th { background: #fff; color: #111; font-size: 11px; line-height: 1.25; min-width: 54px; white-space: nowrap; }
        .product-detail-sheet thead th > span { white-space: nowrap; }
        .product-detail-sheet .sheet-lines > span,
        .product-detail-sheet .sheet-lines > strong,
        .product-detail-sheet .sheet-lines > small,
        .product-detail-sheet .sheet-lines > input { border-bottom: 1px solid #d7d7d7; display: block; line-height: 18px; min-height: 18px; padding: 0 4px; white-space: nowrap; }
        .product-detail-sheet .sheet-lines > :last-child { border-bottom: 0; }
        .product-detail-sheet .wrap-line { line-height: 15px !important; min-width: 170px; padding: 5px 7px !important; white-space: normal !important; }
        .product-detail-sheet .packaging-compact { max-width: 92px; min-width: 82px !important; width: 82px; }
        .product-detail-sheet thead th.packaging-compact,
        .product-detail-sheet thead th.packaging-compact > span { white-space: normal; }
        .product-detail-sheet .packaging-compact .wrap-line { max-width: 92px; min-width: 82px; overflow-wrap: anywhere; padding: 3px !important; }
        .product-detail-sheet .composition-line { min-width: 400px; max-width: none; width: 400px; padding: 6px !important; text-align: left; vertical-align: middle !important; }
        .product-detail-sheet .product-name-line { color: #f01818; font-weight: 700; text-align: left; }
        .product-detail-sheet .brand-name-line { color: #00a651; font-weight: 700; text-align: left; }
        .product-detail-sheet .text-left-line { text-align: left; }
        .product-detail-sheet .price-pts { background: #df9b9b; }
        .product-detail-sheet .price-ptr { background: #a9dc70; }
        .product-detail-sheet .price-ptd { background: #f4f817; }
        .product-detail-sheet .upload-date { background: #91d050; }
        .product-detail-sheet .minimum-order { background: #fff900; color: #ff1a1a; font-weight: 700; }
        .product-detail-sheet .header-accent-red { color: #f01818; font-weight: 700; }
        .stock-inline-input {
            background: #fffce8;
            border: 0 !important;
            border-radius: 0;
            box-shadow: none !important;
            color: inherit;
            font-size: 11px;
            height: 22px !important;
            min-height: 22px !important;
            text-align: center;
            width: 100%;
        }
        .stock-inline-input:focus {
            background: #fff;
            outline: 1px solid #80bdff;
        }
        .stock-inline-input.is-saving { opacity: 0.55; }
        .stock-inline-input.is-error { outline: 1px solid #dc3545; }
        .composition-clamp { max-height: 72px; overflow: hidden; }
        @media (max-width: 767.98px) {
            .product-detail-sheet .composition-line { min-width: 190px; }
            .product-detail-sheet .wrap-line { min-width: 140px; }
        }
    </style>

    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="align-items-center">
            <h1 class="h3">{{ translate('Product wise stock report') }}</h1>
            <p class="text-muted mb-0">{{ translate('Yellow cells can be edited inline. Qty, Scheme and Total Qty are shown together; only Qty is saved.') }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('stock_report.index') }}" method="GET" class="mb-3">
                <div class="border rounded p-3">
                    <div class="row">
                        <div class="col-12 mb-2">
                            <h6 class="mb-0">{{ translate('Filter Stock Report') }}</h6>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3 mb-3">
                            <label for="category_select" class="mb-1">{{ translate('Category') }}</label>
                            <select id="category_select" class="form-control aiz-selectpicker" name="category_id" data-live-search="true">
                                <option value="">{{ translate('All Categories') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected($categoryId == $category->id)>
                                        {{ $category->getTranslation('name') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3 mb-3">
                            <label for="product_select" class="mb-1">{{ translate('Product') }}</label>
                            <select id="product_select" class="form-control aiz-selectpicker" name="product_id" data-live-search="true">
                                <option value="">{{ translate('All Products') }}</option>
                                @foreach ($productsForFilter as $product)
                                    <option value="{{ $product->id }}" @selected($productId == $product->id)>
                                        {{ $product->getTranslation('name') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3 mb-3">
                            <label for="variant_select" class="mb-1">{{ translate('Variant') }}</label>
                            <select id="variant_select" class="form-control aiz-selectpicker" name="variant_id" data-live-search="true">
                                <option value="">{{ translate('All Variants') }}</option>
                                @foreach ($variants as $variant)
                                    <option value="{{ $variant->id }}" @selected($variantId == $variant->id)>
                                        {{ $variant->variant }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3 mb-3">
                            <label for="batch_select" class="mb-1">{{ translate('Batch') }}</label>
                            <select id="batch_select" class="form-control aiz-selectpicker" name="batch_id" data-live-search="true">
                                <option value="">{{ translate('All Batches') }}</option>
                                @foreach ($batches as $batch)
                                    @continue(function_exists('is_batch_expired') && is_batch_expired($batch))
                                    <option value="{{ $batch->id }}" @selected($batchId == $batch->id)>
                                        {{ $batch->batch }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 d-flex flex-column flex-sm-row justify-content-end">
                            <a href="{{ route('stock_report.index') }}" class="btn btn-light border mb-2 mb-sm-0 mr-sm-2">
                                {{ translate('Reset') }}
                            </a>
                            <button class="btn btn-primary" type="submit">
                                {{ translate('Apply Filter') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered product-detail-sheet mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Sr No.') }}</th>
                            <th>{{ translate('Action') }}</th>
                            <th>
                                <span>{{ translate('SKU') }}</span><br>
                                <span class="header-accent-red">{{ translate('MOQ') }}</span>
                            </th>
                            <th>
                                <span>{{ translate('Category') }}</span><br><br>
                                <span>{{ translate('Group') }}</span>
                            </th>
                            <th>
                                <span>{{ translate('Brand Name') }}</span><br>
                                <span>{{ translate('Brand/ Mfg') }}</span><br>
                                <span>{{ translate('Drug Role') }}</span><br>
                                <span>{{ translate('Schedule') }}</span>
                            </th>
                            <th>{{ translate('Composition') }}</th>
                            <th>{{ translate('Pack Size') }}</th>
                            <th>
                                {{ translate('Type') }}<br>
                                {{ translate('Quality / Material') }}<br>
                                {{ translate('Size') }}<br>
                                {{ translate('Country of Origin') }}
                            </th>
                            <th>{{ translate('PTS') }}<br>{{ translate('PTR') }}<br>{{ translate('PTD') }}<br>{{ translate('B2C') }}</th>
                            <th>
                                {{ translate('Govt.') }}<br>{{ translate('Export') }}<br>
                                <span>&nbsp;</span>
                                <span class="header-accent-red">{{ translate('M.R.P') }}</span>
                            </th>
                            <th>
                                {{ translate('Batch / Lot. No') }}<br>
                                {{ translate('Mfg. Date') }}<br>
                                {{ translate('Expiry Date') }}
                            </th>
                            <th>
                                {{ translate('Qty') }}<br>
                                {{ translate('Scheme') }}<br>
                                {{ translate('Total Qty') }}
                            </th>
                            <th>
                                {{ translate('Tax %') }}<br>{{ translate('HSN Code') }}<br>{{ translate('HS Code') }}<br>
                                <span style="background:#91d050;display:block;">{{ translate('Upload Date') }}</span>
                            </th>
                            <th class="packaging-compact">
                                {{ translate('Piece') }}<br>
                                {{ translate('Qty') }}<br>
                                {{ translate('Weight (gm)') }}<br>
                                {{ translate('Dimensions (cm)') }}
                            </th>
                            <th>
                                {{ translate('Buffer Box / Shrink Pack') }}<br>
                                {{ translate('Qty') }}<br>
                                {{ translate('Weight (gm)') }}<br>
                                {{ translate('Dimensions (cm)') }}
                            </th>
                            <th>
                                {{ translate('Buffer Box / Shrink Pack Per Case') }}<br>
                                {{ translate('Qty') }}<br>
                                {{ translate('Weight (gm)') }}<br>
                                {{ translate('Dimensions (cm)') }}
                            </th>
                            <th class="packaging-compact">
                                {{ translate('Per Case') }}<br>
                                {{ translate('Qty') }}<br>
                                {{ translate('Weight (gm)') }}<br>
                                {{ translate('Dimensions (cm)') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reportRows as $key => $batch)
                            @php
                                $product = $batch->product;
                                $stock = $batch->stock;
                                $rolePrices = is_array($batch->role_price)
                                    ? $batch->role_price
                                    : json_decode((string) $batch->role_price, true);
                                $rolePrices = is_array($rolePrices) ? $rolePrices : [];
                                $roleAmount = function (string $role) use ($rolePrices, $decimal) {
                                    return array_key_exists($role, $rolePrices) ? $decimal($rolePrices[$role]) : '';
                                };

                                $categoryNames = collect([$product?->main_category])
                                    ->merge($product?->categories ?? collect())
                                    ->filter()
                                    ->map(fn ($category) => $category->getTranslation('name'))
                                    ->filter()
                                    ->unique()
                                    ->values();
                                $groupNames = collect([$product?->main_group])
                                    ->merge($product?->groups ?? collect())
                                    ->filter()
                                    ->map(fn ($group) => $group->getTranslation('name'))
                                    ->filter()
                                    ->unique()
                                    ->values();
                                $taxPercent = $product?->taxes?->where('tax_type', 'percent')->sum('tax') ?? 0;
                                $mrpPrice = $batch->mrp_price ?? $stock?->mrp_price ?? $product?->mrp_price;
                                $isExpired = $batch->product_exp_date
                                    && strtotime($batch->product_exp_date) < strtotime(now()->toDateString());
                                $contentTabs = json_decode((string) $product?->contents, true);
                                $compositionTab = collect(is_array($contentTabs) ? $contentTabs : [])
                                    ->first(fn ($tab) => str_contains(strtolower((string) ($tab['title'] ?? '')), 'composition'));
                                $composition = trim(strip_tags((string) ($product?->drug_name ?: ($compositionTab['content'] ?? ''))));
                                $b2cPrice = array_key_exists('customer', $rolePrices)
                                    ? $rolePrices['customer']
                                    : ($stock?->price ?? null);
                                $scheme = (int) ($stock->scheme ?? 0);
                                $qty = (int) $batch->qty;
                            @endphp
                            <tr data-batch-row="{{ $batch->id }}">
                                <td>{{ $key + 1 + ($reportRows->currentPage() - 1) * $reportRows->perPage() }}</td>
                                <td>
                                    @if ($product)
                                        <a href="{{ route('products.admin.edit', ['id' => $product->id, 'lang' => fallback_lang()]) }}"
                                            class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                            title="{{ translate('Edit Product') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="sheet-lines">
                                    <span class="text-nowrap">{{ $stock?->sku ?: '-' }}</span>
                                    <span class="minimum-order">{{ $stock?->min_qty ?? '-' }}</span>
                                </td>
                                <td class="sheet-lines">
                                    @forelse ($categoryNames as $categoryName)
                                        <span>{{ $categoryName }}</span>
                                    @empty
                                        <span>-</span>
                                    @endforelse
                                    <span>{{ $groupNames->isNotEmpty() ? $groupNames->implode(', ') : '-' }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <span class="product-name-line">{{ $product?->getTranslation('name') ?? '-' }}</span>
                                    <span class="brand-name-line">{{ $product?->brand?->getTranslation('name') ?? '-' }}</span>
                                    <span class="text-left-line">{{ $product?->role_label ?: '-' }}</span>
                                    <span class="text-left-line">{{ $product?->schedule ?: '-' }}</span>
                                </td>
                                <td class="composition-line">
                                    <div class="composition-clamp" title="{{ $composition !== '' ? $composition : '-' }}">{{ $composition !== '' ? $composition : '-' }}</div>
                                </td>
                                <td>{{ trim((string) $stock?->variant) ?: translate('Default') }}</td>
                                <td class="sheet-lines">
                                    <span>{{ $product?->product_type ?: ($product?->product_form ?: '-') }}</span>
                                    <span>{{ $product?->product_material ?: '-' }}</span>
                                    <span>{{ trim((string) $stock?->variant) ?: '-' }}</span>
                                    <span>{{ $product?->product_origin ?: '-' }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <input type="number" min="0" step="0.01" lang="en" class="stock-inline-input price-pts" data-batch-id="{{ $batch->id }}" data-field="pts" value="{{ $roleAmount('pts') }}" title="{{ translate('PTS') }}">
                                    <input type="number" min="0" step="0.01" lang="en" class="stock-inline-input price-ptr" data-batch-id="{{ $batch->id }}" data-field="ptr" value="{{ $roleAmount('ptr') }}" title="{{ translate('PTR') }}">
                                    <input type="number" min="0" step="0.01" lang="en" class="stock-inline-input price-ptd" data-batch-id="{{ $batch->id }}" data-field="ptd" value="{{ $roleAmount('ptd') }}" title="{{ translate('PTD') }}">
                                    <input type="number" min="0" step="0.01" lang="en" class="stock-inline-input" data-batch-id="{{ $batch->id }}" data-field="customer" value="{{ $decimal($b2cPrice) }}" title="{{ translate('B2C') }}">
                                </td>
                                <td class="sheet-lines">
                                    <input type="number" min="0" step="0.01" lang="en" class="stock-inline-input" data-batch-id="{{ $batch->id }}" data-field="gov" value="{{ $roleAmount('gov') }}" title="{{ translate('Govt.') }}">
                                    <input type="number" min="0" step="0.01" lang="en" class="stock-inline-input" data-batch-id="{{ $batch->id }}" data-field="expo" value="{{ $roleAmount('expo') }}" title="{{ translate('Export') }}">
                                    <span>&nbsp;</span>
                                    <input type="number" min="0" step="0.01" lang="en" class="stock-inline-input" data-batch-id="{{ $batch->id }}" data-field="mrp_price" value="{{ $decimal($mrpPrice) }}" title="{{ translate('M.R.P') }}">
                                </td>
                                <td class="sheet-lines">
                                    <input type="text" class="stock-inline-input" data-batch-id="{{ $batch->id }}" data-field="batch" value="{{ $batch->batch }}" title="{{ translate('Batch / Lot. No') }}" required>
                                    <input type="month" class="stock-inline-input" data-batch-id="{{ $batch->id }}" data-field="manufacturing_date" value="{{ $monthValue($batch->manufacturing_date) }}" title="{{ translate('Mfg. Date') }}">
                                    <input type="month" class="stock-inline-input {{ $isExpired ? 'text-danger' : '' }}" data-batch-id="{{ $batch->id }}" data-field="product_exp_date" value="{{ $monthValue($batch->product_exp_date) }}" title="{{ translate('Expiry Date') }}">
                                </td>
                                <td class="sheet-lines">
                                    <input type="number" min="0" step="1" lang="en" class="stock-inline-input js-stock-qty" data-batch-id="{{ $batch->id }}" data-field="qty" value="{{ $qty }}" title="{{ translate('Qty') }}">
                                    <span>{{ $scheme }}</span>
                                    <span class="js-stock-total-qty">{{ $qty }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <span>{{ $taxPercent ? number_format((float) $taxPercent, 2, '.', '') : '-' }}</span>
                                    <span>{{ $product?->product_hsn ?: '-' }}</span>
                                    <span>{{ $product?->product_hs ?: '-' }}</span>
                                    <span class="upload-date">{{ $formatUploadDate($product?->updated_at) }}</span>
                                </td>
                                <td class="sheet-lines packaging-compact">
                                    <span>{{ $stock?->qty_per_piece ?? '-' }}</span>
                                    <span>{{ $stock?->weight ?? $product?->product_weight_vol ?? '-' }}</span>
                                    <span class="wrap-line">{{ $dimensions($stock?->length, $stock?->width, $stock?->height) }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <span>{{ $stock?->qty_per_buffer_box ?? '-' }}</span>
                                    <span>{{ $stock?->weight_buffer_box ?? '-' }}</span>
                                    <span class="wrap-line">{{ $dimensions($stock?->buffer_length, $stock?->buffer_width, $stock?->buffer_height) }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <span>{{ $stock?->count ?? '-' }}</span>
                                    <span>{{ $stock?->weight_case ?? '-' }}</span>
                                    <span class="wrap-line">{{ $dimensions($stock?->case_length, $stock?->case_width, $stock?->case_height) }}</span>
                                </td>
                                <td class="sheet-lines packaging-compact">
                                    <span>{{ $stock?->total_qty_per_case ?? '-' }}</span>
                                    <span>{{ $stock?->weight_case ?? '-' }}</span>
                                    <span class="wrap-line">{{ $dimensions($stock?->case_length, $stock?->case_width, $stock?->case_height) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="17" class="text-center py-4">{{ translate('No stock data found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-4">
                {{ $reportRows->appends(request()->input())->links() }}
            </div>
        </div>
    </div>
@endsection
@section('script')
<script>
    $(document).ready(function () {
        const productSelect = $('#product_select');
        const variantSelect = $('#variant_select');
        const batchSelect = $('#batch_select');
        const saveUrl = '{{ route("stock_report.update_batch") }}';
        const csrfToken = '{{ csrf_token() }}';

        function resetVariantAndBatch() {
            variantSelect.empty().append('<option value="">{{ translate("All Variants") }}</option>');
            batchSelect.empty().append('<option value="">{{ translate("All Batches") }}</option>');
            variantSelect.selectpicker('refresh');
            batchSelect.selectpicker('refresh');
        }

        function loadVariantAndBatchOptions(productId, selectedVariantId = '', selectedBatchId = '') {
            if (!productId) {
                resetVariantAndBatch();
                return;
            }

            $.ajax({
                url: '{{ route("stock_report.filter_options") }}',
                type: 'GET',
                data: {
                    product_id: productId,
                    variant_id: selectedVariantId
                },
                success: function (response) {
                    variantSelect.empty().append('<option value="">{{ translate("All Variants") }}</option>');
                    batchSelect.empty().append('<option value="">{{ translate("All Batches") }}</option>');

                    $.each(response.variants, function (key, variant) {
                        const isSelected = String(variant.id) === String(selectedVariantId) ? 'selected' : '';
                        variantSelect.append('<option value="' + variant.id + '" ' + isSelected + '>' + variant.name + '</option>');
                    });

                    $.each(response.batches, function (key, batch) {
                        const isSelected = String(batch.id) === String(selectedBatchId) ? 'selected' : '';
                        batchSelect.append('<option value="' + batch.id + '" ' + isSelected + '>' + batch.name + '</option>');
                    });

                    variantSelect.selectpicker('refresh');
                    batchSelect.selectpicker('refresh');
                },
                error: function () {
                    resetVariantAndBatch();
                }
            });
        }

        $('#category_select').on('change', function () {
            var categoryId = $(this).val();

            $.ajax({
                url: '{{ route("get.products.by.category") }}',
                type: 'GET',
                data: { category_id: categoryId },
                success: function (response) {
                    productSelect.empty();
                    productSelect.append('<option value="">{{ translate("All Products") }}</option>');

                    $.each(response.products, function (key, product) {
                        productSelect.append('<option value="' + product.id + '">' + product.name + '</option>');
                    });

                    productSelect.selectpicker('refresh');
                    resetVariantAndBatch();
                },
                error: function () {
                    alert('Failed to load products.');
                }
            });
        });

        $('#product_select').on('change', function () {
            loadVariantAndBatchOptions($(this).val());
        });

        $('#variant_select').on('change', function () {
            loadVariantAndBatchOptions(productSelect.val(), $(this).val(), batchSelect.val());
        });

        @if($productId)
            loadVariantAndBatchOptions('{{ $productId }}', '{{ $variantId }}', '{{ $batchId }}');
        @endif

        function notify(type, message) {
            if (typeof AIZ !== 'undefined' && AIZ.plugins && AIZ.plugins.notify) {
                AIZ.plugins.notify(type, message);
            }
        }

        $('.stock-inline-input').each(function () {
            $(this).data('original', $(this).val());
        });

        $(document).on('focus', '.stock-inline-input', function () {
            $(this).data('original', $(this).val());
        });

        $(document).on('change', '.stock-inline-input', function () {
            const input = $(this);
            const batchId = input.data('batch-id');
            const field = input.data('field');
            const value = input.val();
            const original = input.data('original');

            if (String(value) === String(original ?? '')) {
                return;
            }

            input.addClass('is-saving').removeClass('is-error').prop('disabled', true);

            $.ajax({
                url: saveUrl,
                type: 'POST',
                data: {
                    _token: csrfToken,
                    batch_id: batchId,
                    field: field,
                    value: value
                },
                success: function (response) {
                    input.removeClass('is-saving is-error').prop('disabled', false);
                    if (response.display !== undefined && (field === 'mrp_price' || ['pts', 'ptr', 'ptd', 'gov', 'expo', 'customer'].indexOf(field) !== -1)) {
                        input.val(response.display);
                    }
                    if (field === 'qty') {
                        input.closest('tr').find('.js-stock-total-qty').text(response.qty);
                    }
                    input.data('original', input.val());
                    notify('success', response.message || '{{ translate("Saved") }}');
                },
                error: function (xhr) {
                    input.removeClass('is-saving').addClass('is-error').prop('disabled', false);
                    if (original !== undefined) {
                        input.val(original);
                    }
                    const message = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0])))
                        || '{{ translate("Unable to save") }}';
                    notify('danger', message);
                }
            });
        });
    });
</script>
@endsection
