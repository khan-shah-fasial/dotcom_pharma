@extends('backend.layouts.app')

@section('content')
    @php
        $activeFilterCount = collect([
            $categoryId,
            $groupId,
            $brandId,
            $productId,
            $variantId,
            $batchId,
            $publishedStatus,
            $stockStatus,
            $expiryStatus,
        ])->filter(fn ($value) => $value !== null && $value !== '')->count();

        $sortUrl = function (string $column) use ($sortBy, $sortOrder) {
            return route('product_detail_report.index', array_merge(request()->query(), [
                'sort_by' => $column,
                'sort_order' => $sortBy === $column && $sortOrder === 'asc' ? 'desc' : 'asc',
                'page' => null,
            ]));
        };

        $sortIcon = function (string $column) use ($sortBy, $sortOrder) {
            if ($sortBy !== $column) {
                return 'las la-sort text-muted';
            }

            return $sortOrder === 'asc' ? 'las la-sort-amount-up' : 'las la-sort-amount-down';
        };

        $formatMonth = function ($value) {
            if (!$value || strtotime($value) === false) {
                return '-';
            }

            return date('F-y', strtotime($value));
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
            ? '-'
            : number_format((float) $value, $precision, '.', '');
    @endphp

    <style>
        .product-detail-sheet { color: #202124; font-size: 11px; min-width: 1900px; table-layout: auto; }
        .product-detail-sheet th,
        .product-detail-sheet td { border-color: #242424 !important; padding: 0 !important; text-align: center; vertical-align: middle !important; }
        .product-detail-sheet thead th { background: #fff; color: #111; font-size: 11px; line-height: 1.25; min-width: 54px; white-space: nowrap; }
        .product-detail-sheet thead th > a { color: inherit; display: block; white-space: nowrap; }
        .product-detail-sheet thead th > span { white-space: nowrap; }
        .product-detail-sheet .sheet-lines > span,
        .product-detail-sheet .sheet-lines > strong,
        .product-detail-sheet .sheet-lines > small { border-bottom: 1px solid #d7d7d7; display: block; line-height: 18px; min-height: 18px; padding: 0 4px; white-space: nowrap; }
        .product-detail-sheet .sheet-lines > :last-child { border-bottom: 0; }
        .product-detail-sheet .wrap-line { line-height: 15px !important; min-width: 170px; padding: 5px 7px !important; white-space: normal !important; }
        .product-detail-sheet .packaging-compact { max-width: 92px; min-width: 82px !important; width: 82px; }
        .product-detail-sheet thead th.packaging-compact,
        .product-detail-sheet thead th.packaging-compact > span { white-space: normal; }
        .product-detail-sheet .packaging-compact .wrap-line { max-width: 92px; min-width: 82px; overflow-wrap: anywhere; padding: 3px !important; }
        .product-detail-sheet .composition-line { min-width: 270px; max-width: none; width: auto; padding: 6px !important; text-align: left; white-space: nowrap; vertical-align: middle !important; }
        .product-detail-sheet .composition-line .composition-clamp {
            display: inline-block;
            white-space: nowrap;
            overflow: visible;
            text-overflow: clip;
            word-break: normal;
            overflow-wrap: normal;
            line-height: 1.35;
            max-width: none;
        }
        .product-detail-sheet .product-name-line { color: #f01818; font-weight: 700; text-align: left; }
        .product-detail-sheet .brand-name-line { color: #00a651; font-weight: 700; text-align: left; }
        .product-detail-sheet .text-left-line { text-align: left; }
        .product-detail-sheet .price-pts { background: #df9b9b; }
        .product-detail-sheet .price-ptr { background: #a9dc70; }
        .product-detail-sheet .price-ptd { background: #f4f817; }
        .product-detail-sheet .upload-date { background: #91d050; }
        .product-detail-sheet .minimum-order { background: #fff900; color: #ff1a1a; font-weight: 700; }
        .product-detail-sheet .header-accent-red { color: #f01818; font-weight: 700; }
        .product-detail-sheet.footable-details { font-size: 11px; margin: 0; }
        .product-detail-sheet.footable-details th { background: #f7f7f7; min-width: 230px; padding: 7px !important; text-align: left; white-space: normal; }
        .product-detail-sheet.footable-details td { padding: 0 !important; }
        @media (max-width: 767.98px) {
            .product-detail-sheet .composition-line { min-width: 190px; }
            .product-detail-sheet .wrap-line { min-width: 140px; }
        }
    </style>

    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="align-items-center">
            <h1 class="h3">{{ translate('Product And Price List') }}</h1>
            <p class="text-muted mb-0">
                {{ translate('Batch-level product, variant, pricing, stock and packaging details.') }}
            </p>
        </div>
    </div>

    <div class="card">
        <form id="product-detail-report-form" action="{{ route('product_detail_report.index') }}" method="GET">
            <div class="card-header d-block d-md-flex align-items-center">
                <h5 class="mb-2 mb-md-0 h6">
                    {{ translate('Product Details') }}
                    <span class="badge badge-soft-secondary ml-1">{{ $reportRows->total() }}</span>
                </h5>

                <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center ml-md-auto">
                    <div class="input-group mb-2 mb-sm-0 mr-sm-2" style="min-width: 280px;">
                        <input type="text" class="form-control" name="search" value="{{ $search }}"
                            placeholder="{{ translate('Product, SKU, variant or batch') }}">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit" title="{{ translate('Search') }}">
                                <i class="las la-search"></i>
                            </button>
                        </div>
                    </div>

                    @if ($search !== '' || $activeFilterCount > 0)
                        <a class="btn btn-soft-danger mb-2 mb-sm-0 mr-sm-2" href="{{ route('product_detail_report.index') }}">
                            <i class="las la-redo-alt"></i> {{ translate('Reset') }}
                        </a>
                    @endif

                    <button type="button" class="btn btn-soft-primary" data-toggle="modal" data-target="#productDetailFilterModal">
                        <i class="las la-filter"></i> {{ translate('Filter') }}
                        @if ($activeFilterCount > 0)
                            <span class="badge badge-primary ml-1">{{ $activeFilterCount }}</span>
                        @endif
                    </button>
                </div>
            </div>

            <div class="modal fade" id="productDetailFilterModal" tabindex="-1" role="dialog"
                aria-labelledby="productDetailFilterModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="productDetailFilterModalLabel">{{ translate('Filter Product And Price List') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="category_select">{{ translate('Category') }}</label>
                                    <select id="category_select" class="form-control aiz-selectpicker product-classification-filter"
                                        name="category_id" data-live-search="true">
                                        <option value="">{{ translate('All Categories') }}</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" @selected($categoryId === $category->id)>
                                                {{ $category->getTranslation('name') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="group_select">{{ translate('Group') }}</label>
                                    <select id="group_select" class="form-control aiz-selectpicker product-classification-filter"
                                        name="group_id" data-live-search="true">
                                        <option value="">{{ translate('All Groups') }}</option>
                                        @foreach ($groups as $group)
                                            <option value="{{ $group->id }}" @selected($groupId === $group->id)>
                                                {{ $group->getTranslation('name') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="brand_select">{{ translate('Brand') }}</label>
                                    <select id="brand_select" class="form-control aiz-selectpicker product-classification-filter"
                                        name="brand_id" data-live-search="true">
                                        <option value="">{{ translate('All Brands') }}</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}" @selected($brandId === $brand->id)>
                                                {{ $brand->getTranslation('name') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="product_select">{{ translate('Product') }}</label>
                                    <select id="product_select" class="form-control aiz-selectpicker" name="product_id" data-live-search="true">
                                        <option value="">{{ translate('All Products') }}</option>
                                        @foreach ($productsForFilter as $product)
                                            <option value="{{ $product->id }}" @selected($productId === $product->id)>
                                                {{ $product->getTranslation('name') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="variant_select">{{ translate('Variant') }}</label>
                                    <select id="variant_select" class="form-control aiz-selectpicker" name="variant_id" data-live-search="true">
                                        <option value="">{{ translate('All Variants') }}</option>
                                        @foreach ($variants as $variant)
                                            <option value="{{ $variant->id }}" @selected($variantId === $variant->id)>
                                                {{ trim((string) $variant->variant) ?: translate('Default') }}
                                                @if ($variant->sku)
                                                    ({{ $variant->sku }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="batch_select">{{ translate('Batch') }}</label>
                                    <select id="batch_select" class="form-control aiz-selectpicker" name="batch_id" data-live-search="true">
                                        <option value="">{{ translate('All Batches') }}</option>
                                        @foreach ($batches as $batch)
                                            <option value="{{ $batch->id }}" @selected($batchId === $batch->id)>
                                                {{ trim((string) $batch->batch) ?: '-' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="stock_status">{{ translate('Stock Status') }}</label>
                                    <select id="stock_status" class="form-control aiz-selectpicker" name="stock_status">
                                        <option value="">{{ translate('All Stock Statuses') }}</option>
                                        <option value="in_stock" @selected($stockStatus === 'in_stock')>{{ translate('In Stock') }}</option>
                                        <option value="out_of_stock" @selected($stockStatus === 'out_of_stock')>{{ translate('Out of Stock') }}</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="expiry_status">{{ translate('Expiry Status') }}</label>
                                    <select id="expiry_status" class="form-control aiz-selectpicker" name="expiry_status">
                                        <option value="">{{ translate('All Expiry Statuses') }}</option>
                                        <option value="expired" @selected($expiryStatus === 'expired')>{{ translate('Expired') }}</option>
                                        <option value="expiring_soon" @selected($expiryStatus === 'expiring_soon')>{{ translate('Expiring Within 90 Days') }}</option>
                                        <option value="valid" @selected($expiryStatus === 'valid')>{{ translate('Valid Beyond 90 Days') }}</option>
                                        <option value="no_expiry" @selected($expiryStatus === 'no_expiry')>{{ translate('No Expiry Date') }}</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="published_status">{{ translate('Publication Status') }}</label>
                                    <select id="published_status" class="form-control aiz-selectpicker" name="published_status">
                                        <option value="">{{ translate('All Publication Statuses') }}</option>
                                        <option value="1" @selected($publishedStatus === '1')>{{ translate('Published') }}</option>
                                        <option value="0" @selected($publishedStatus === '0')>{{ translate('Unpublished') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <a href="{{ route('product_detail_report.index') }}" class="btn btn-light border">
                                {{ translate('Clear Filters') }}
                            </a>
                            <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-responsive">
                <table id="product-detail-sheet" class="table table-bordered product-detail-sheet mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Sr No.') }}</th>
                            <th>
                                <a href="{{ $sortUrl('sku') }}">
                                    {{ translate('SKU') }} <i class="{{ $sortIcon('sku') }}"></i>
                                </a>
                                <span class="header-accent-red">{{ translate('MOQ') }}</span>
                            </th>
                            <th data-breakpoints="xs">
                                <span>{{ translate('Category') }}</span><br><br>
                                <span>{{ translate('Group') }}</span>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('product_name') }}">
                                    <span>{{ translate('Brand Name') }}</span><br>
                                    <span>{{ translate('Brand/ Mfg') }}</span><br>
                                    <span>{{ translate('Drug Role') }}</span><br>
                                    <span>{{ translate('Schedule') }}</span>
                                    <i class="{{ $sortIcon('product_name') }}"></i>
                                </a>
                            </th>
                            <th data-breakpoints="xs sm">{{ translate('Composition') }}</th>
                            <th>
                                <a href="{{ $sortUrl('variant') }}">
                                    {{ translate('Pack Size') }} <i class="{{ $sortIcon('variant') }}"></i>
                                </a>
                            </th>
                            <th data-breakpoints="xs sm md lg">
                                {{ translate('Type') }}<br>
                                {{ translate('Quality / Material') }}<br>
                                {{ translate('Size') }}<br>
                                {{ translate('Country of Origin') }}
                            </th>
                            <th>{{ translate('PTS') }}<br>{{ translate('PTR') }}<br>{{ translate('PTD') }}<br>{{ translate('B2C') }}</th>
                            <th data-breakpoints="xs sm md lg xl">
                                {{ translate('Govt.') }}<br>{{ translate('Export') }}<br>
                                <span>&nbsp;</span>
                                <a href="{{ $sortUrl('mrp_price') }}">
                                    <span class="header-accent-red">{{ translate('M.R.P') }}</span>
                                    <i class="{{ $sortIcon('mrp_price') }}"></i>
                                </a>
                                
                            </th>
                            <th>
                                <a href="{{ $sortUrl('batch') }}">
                                    {{ translate('Batch / Lot. No') }} <i class="{{ $sortIcon('batch') }}"></i>
                                </a>
                                {{ translate('Mfg. Date') }}<br>
                                <a href="{{ $sortUrl('expiry') }}">
                                    {{ translate('Expiry Date') }} <i class="{{ $sortIcon('expiry') }}"></i>
                                </a>
                                <a href="{{ $sortUrl('qty') }}">
                                    {{ translate('Stock Available') }} <i class="{{ $sortIcon('qty') }}"></i>
                                </a>
                            </th>
                            <th data-breakpoints="xs sm md lg xl">
                                {{ translate('Tax %') }}<br>{{ translate('HSN Code') }}<br>{{ translate('HS Code') }}<br>
                                <span style="background:#91d050;display:block;">{{ translate('Upload Date') }}</span>
                            </th>
                            <th class="packaging-compact" data-breakpoints="xs sm md lg xl">
                                {{ translate('Piece') }}<br>
                                {{ translate('Qty') }}<br>
                                {{ translate('Weight (gm)') }}<br>
                                {{ translate('Dimensions (cm)') }}
                            </th>
                            <th data-breakpoints="xs sm md lg xl">
                                {{ translate('Buffer Box / Shrink Pack') }}<br>
                                {{ translate('Qty') }}<br>
                                {{ translate('Weight (gm)') }}<br>
                                {{ translate('Dimensions (cm)') }}
                            </th>
                            <th data-breakpoints="xs sm md lg xl">
                                {{ translate('Buffer Box / Shrink Pack Per Case') }}<br>
                                {{ translate('Qty') }}<br>
                                {{ translate('Weight (gm)') }}<br>
                                {{ translate('Dimensions (cm)') }}
                            </th>
                            <th class="packaging-compact" data-breakpoints="xs sm md lg xl">
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
                                $rolePrice = fn (string $role) => array_key_exists($role, $rolePrices)
                                    ? $decimal($rolePrices[$role])
                                    : '-';

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
                            @endphp
                            <tr>
                                <td>{{ $key + 1 + ($reportRows->currentPage() - 1) * $reportRows->perPage() }}</td>
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
                                    <span class="price-pts">{{ $rolePrice('pts') }}</span>
                                    <span class="price-ptr">{{ $rolePrice('ptr') }}</span>
                                    <span class="price-ptd">{{ $rolePrice('ptd') }}</span>
                                    <span>{{ $decimal($b2cPrice) }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <span>{{ $rolePrice('gov') }}</span>
                                    <span>{{ $rolePrice('expo') }}</span>
                                    <span>&nbsp;</span>
                                    <strong>{{ $decimal($mrpPrice) }}</strong>
                                </td>
                                <td class="sheet-lines">
                                    <strong>{{ trim((string) $batch->batch) ?: '-' }}</strong>
                                    <span>{{ $formatMonth($batch->manufacturing_date) }}</span>
                                    <span class="{{ $isExpired ? 'text-danger fw-600' : '' }}">{{ $formatMonth($batch->product_exp_date) }}</span>
                                    <span>{{ (int) $batch->qty }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <span>{{ $taxPercent ? $decimal($taxPercent) : '-' }}</span>
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
                                <td colspan="15" class="text-center py-4">{{ translate('No product detail records found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="aiz-pagination mt-4">
                {{ $reportRows->links() }}
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

            function updateSelect(select, placeholder, items, selectedValue) {
                select.empty().append($('<option>', { value: '', text: placeholder }));

                $.each(items || [], function (_, item) {
                    select.append($('<option>', {
                        value: item.id,
                        text: item.name,
                        selected: String(item.id) === String(selectedValue || '')
                    }));
                });

                select.selectpicker('refresh');
            }

            function loadFilterOptions(selectedProductId, selectedVariantId, selectedBatchId) {
                $.ajax({
                    url: '{{ route('product_detail_report.filter_options') }}',
                    type: 'GET',
                    data: {
                        category_id: $('#category_select').val(),
                        group_id: $('#group_select').val(),
                        brand_id: $('#brand_select').val(),
                        product_id: selectedProductId,
                        variant_id: selectedVariantId
                    },
                    success: function (response) {
                        updateSelect(productSelect, '{{ translate('All Products') }}', response.products, selectedProductId);
                        updateSelect(variantSelect, '{{ translate('All Variants') }}', response.variants, selectedVariantId);
                        updateSelect(batchSelect, '{{ translate('All Batches') }}', response.batches, selectedBatchId);
                    },
                    error: function () {
                        if (typeof AIZ !== 'undefined' && AIZ.plugins && AIZ.plugins.notify) {
                            AIZ.plugins.notify('danger', '{{ translate('Unable to load filter options') }}');
                        }
                    }
                });
            }

            $('.product-classification-filter').on('change', function () {
                loadFilterOptions('', '', '');
            });

            productSelect.on('change', function () {
                loadFilterOptions($(this).val(), '', '');
            });

            variantSelect.on('change', function () {
                loadFilterOptions(productSelect.val(), $(this).val(), '');
            });
        });
    </script>
@endsection
