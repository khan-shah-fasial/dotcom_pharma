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

            return date('M Y', strtotime($value));
        };

        $dimensions = function ($length, $width, $height) {
            $values = collect([$length, $width, $height])
                ->filter(fn ($value) => $value !== null && $value !== '');

            return $values->isEmpty() ? '-' : $values->implode(' × ');
        };
    @endphp

    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="align-items-center">
            <h1 class="h3">{{ translate('Product Detail Report') }}</h1>
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
                            <h5 class="modal-title" id="productDetailFilterModalLabel">{{ translate('Filter Product Detail Report') }}</h5>
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
                <table class="table table-bordered aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>
                                <a href="{{ $sortUrl('product_name') }}">
                                    {{ translate('Product') }} <i class="{{ $sortIcon('product_name') }}"></i>
                                </a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('sku') }}">
                                    {{ translate('SKU') }} <i class="{{ $sortIcon('sku') }}"></i>
                                </a>
                            </th>
                            <th>
                                <a href="{{ $sortUrl('variant') }}">
                                    {{ translate('Variant / Pack Size') }} <i class="{{ $sortIcon('variant') }}"></i>
                                </a>
                            </th>
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Category / Group') }}</th>
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Brand / Regulatory') }}</th>
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Product Specification') }}</th>
                            <th>
                                <a href="{{ $sortUrl('batch') }}">
                                    {{ translate('Batch') }} <i class="{{ $sortIcon('batch') }}"></i>
                                </a>
                            </th>
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Mfg Date') }}</th>
                            <th>
                                <a href="{{ $sortUrl('expiry') }}">
                                    {{ translate('Expiry') }} <i class="{{ $sortIcon('expiry') }}"></i>
                                </a>
                            </th>
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Base Price') }}</th>
                            <th>{{ translate('PTS') }}</th>
                            <th>{{ translate('PTR') }}</th>
                            <th>{{ translate('PTD') }}</th>
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Gov') }}</th>
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Expo') }}</th>
                            <th>{{ translate('B2C') }}</th>
                            <th>
                                <a href="{{ $sortUrl('mrp_price') }}">
                                    {{ translate('MRP') }} <i class="{{ $sortIcon('mrp_price') }}"></i>
                                </a>
                            </th>
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Tax / Codes') }}</th>
                            <th>
                                <a href="{{ $sortUrl('qty') }}">
                                    {{ translate('Stock') }} <i class="{{ $sortIcon('qty') }}"></i>
                                </a>
                            </th>
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Packaging') }}</th>
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Weights') }}</th>
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Dimensions (cm)') }}</th>
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Offer') }}</th>
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Status') }}</th>
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
                                    ? format_price((float) $rolePrices[$role])
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
                                $discountText = '-';
                                if ((int) $batch->discount_active === 1 && (float) $batch->discount > 0) {
                                    $discountText = $batch->discount_type === 'percent'
                                        ? number_format((float) $batch->discount, 2) . '%'
                                        : format_price((float) $batch->discount);
                                }
                            @endphp
                            <tr>
                                <td>{{ $key + 1 + ($reportRows->currentPage() - 1) * $reportRows->perPage() }}</td>
                                <td style="min-width: 210px;">
                                    <strong>{{ $product?->getTranslation('name') ?? '-' }}</strong>
                                    @if ($product?->drug_name)
                                        <small class="d-block text-muted">{{ $product->drug_name }}</small>
                                    @endif
                                </td>
                                <td><span class="text-nowrap">{{ $stock?->sku ?: '-' }}</span></td>
                                <td>{{ trim((string) $stock?->variant) ?: translate('Default') }}</td>
                                <td>
                                    <strong>{{ translate('Category') }}:</strong> {{ $categoryNames->isNotEmpty() ? $categoryNames->implode(', ') : '-' }}<br>
                                    <strong>{{ translate('Group') }}:</strong> {{ $groupNames->isNotEmpty() ? $groupNames->implode(', ') : '-' }}
                                    @if ($product?->pharma_categories)
                                        <small class="d-block text-muted">{{ \Illuminate\Support\Str::limit($product->pharma_categories, 100) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ translate('Brand') }}:</strong> {{ $product?->brand?->getTranslation('name') ?? '-' }}<br>
                                    <strong>{{ translate('Role') }}:</strong> {{ $product?->role_label ? \Illuminate\Support\Str::limit($product->role_label, 80) : '-' }}<br>
                                    <strong>{{ translate('Schedule') }}:</strong> {{ $product?->schedule ?: '-' }}
                                </td>
                                <td>
                                    <strong>{{ translate('Form') }}:</strong> {{ $product?->product_form ? \Illuminate\Support\Str::limit($product->product_form, 70) : '-' }}<br>
                                    <strong>{{ translate('Type') }}:</strong> {{ $product?->product_type ?: '-' }}<br>
                                    <strong>{{ translate('Material') }}:</strong> {{ $product?->product_material ? \Illuminate\Support\Str::limit($product->product_material, 70) : '-' }}<br>
                                    <strong>{{ translate('Origin') }}:</strong> {{ $product?->product_origin ?: '-' }}
                                </td>
                                <td><strong>{{ trim((string) $batch->batch) ?: '-' }}</strong></td>
                                <td><span class="text-nowrap">{{ $formatMonth($batch->manufacturing_date) }}</span></td>
                                <td>
                                    <span class="text-nowrap {{ $isExpired ? 'text-danger fw-600' : '' }}">
                                        {{ $formatMonth($batch->product_exp_date) }}
                                    </span>
                                    @if ($isExpired)
                                        <small class="d-block text-danger">{{ translate('Expired') }}</small>
                                    @endif
                                </td>
                                <td><span class="text-nowrap">{{ format_price((float) ($stock?->price ?? 0)) }}</span></td>
                                <td><span class="text-nowrap">{{ $rolePrice('pts') }}</span></td>
                                <td><span class="text-nowrap">{{ $rolePrice('ptr') }}</span></td>
                                <td><span class="text-nowrap">{{ $rolePrice('ptd') }}</span></td>
                                <td><span class="text-nowrap">{{ $rolePrice('gov') }}</span></td>
                                <td><span class="text-nowrap">{{ $rolePrice('expo') }}</span></td>
                                <td><span class="text-nowrap">{{ $rolePrice('customer') }}</span></td>
                                <td><strong class="text-nowrap">{{ $mrpPrice !== null ? format_price((float) $mrpPrice) : '-' }}</strong></td>
                                <td>
                                    <strong>{{ translate('Tax') }}:</strong> {{ $taxPercent ? number_format((float) $taxPercent, 2) . '%' : '-' }}<br>
                                    <strong>{{ translate('HSN') }}:</strong> {{ $product?->product_hsn ?: '-' }}<br>
                                    <strong>{{ translate('HS') }}:</strong> {{ $product?->product_hs ?: '-' }}
                                </td>
                                <td>
                                    <strong>{{ (int) $batch->qty }}</strong>
                                    @if ((int) $batch->scheme > 0)
                                        <small class="d-block text-muted">{{ translate('Scheme') }}: {{ (int) $batch->scheme }}</small>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ translate('Qty/Piece') }}:</strong> {{ $stock?->qty_per_piece ?? '-' }}<br>
                                    <strong>{{ translate('Qty/Buffer') }}:</strong> {{ $stock?->qty_per_buffer_box ?? '-' }}<br>
                                    <strong>{{ translate('Buffers/Case') }}:</strong> {{ $stock?->count ?? '-' }}<br>
                                    <strong>{{ translate('Total/Case') }}:</strong> {{ $stock?->total_qty_per_case ?? '-' }}<br>
                                    <strong>{{ translate('Min Pack') }}:</strong> {{ $product?->product_min_pack_size ?: '-' }}<br>
                                    <strong>{{ translate('Min Order') }}:</strong> {{ $stock?->min_qty ?? '-' }}
                                </td>
                                <td>
                                    <strong>{{ translate('Piece') }}:</strong> {{ $stock?->weight ?? $product?->product_weight_vol ?? '-' }}<br>
                                    <strong>{{ translate('Buffer') }}:</strong> {{ $stock?->weight_buffer_box ?? '-' }}<br>
                                    <strong>{{ translate('Case') }}:</strong> {{ $stock?->weight_case ?? '-' }}
                                </td>
                                <td>
                                    <strong>{{ translate('Piece') }}:</strong> {{ $dimensions($stock?->length, $stock?->width, $stock?->height) }}<br>
                                    <strong>{{ translate('Buffer') }}:</strong> {{ $dimensions($stock?->buffer_length, $stock?->buffer_width, $stock?->buffer_height) }}<br>
                                    <strong>{{ translate('Case') }}:</strong> {{ $dimensions($stock?->case_length, $stock?->case_width, $stock?->case_height) }}
                                </td>
                                <td>
                                    <strong>{{ $discountText }}</strong>
                                    @if ($discountText !== '-' && $batch->discount_start_date)
                                        <small class="d-block text-muted">
                                            {{ date('d M Y', (int) $batch->discount_start_date) }}
                                            @if ($batch->discount_end_date)
                                                {{ translate('to') }} {{ date('d M Y', (int) $batch->discount_end_date) }}
                                            @endif
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-inline {{ $product?->published ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $product?->published ? translate('Published') : translate('Unpublished') }}
                                    </span>
                                    @if ($stock?->is_hidden)
                                        <span class="badge badge-inline badge-warning mt-1">{{ translate('Hidden Variant') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="25" class="text-center py-4">{{ translate('No product detail records found') }}</td>
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
