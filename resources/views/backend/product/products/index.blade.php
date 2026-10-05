@extends('backend.layouts.app')

@section('content')

    <style>
        .stock-item.hidden {
            display: none;
        }
        .product-category-hierarchy + .product-category-hierarchy {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #eef0f4;
        }
        .product-category-item {
            display: block;
            margin-bottom: 4px;
        }
        .listing-stack-line {
            line-height: 1.35;
        }
        .listing-flag-fallback {
            display: none;
            border-bottom: 1px solid #eee;
            margin-bottom: 8px;
            padding-bottom: 8px;
        }
        @media (max-width: 1199.98px) {
            .listing-flag-fallback {
                display: block;
            }
        }
    </style>

    @php
        CoreComponentRepository::instantiateShopRepository();
        CoreComponentRepository::initializeCache();
        $categoryById = $categories->keyBy('id');
        $categoryPath = function ($category) use ($categoryById) {
            $path = collect();
            $seen = [];

            while ($category && !in_array((int) $category->id, $seen, true)) {
                $seen[] = (int) $category->id;
                $path->prepend($category);
                $parentId = $category->parent_id ?? null;
                $category = $parentId ? $categoryById->get($parentId) : null;
            }

            return $path;
        };
        $categoryIsAncestorOfSelected = function ($category, $selectedCategories) use ($categoryById) {
            foreach ($selectedCategories as $selectedCategory) {
                if ((int) $selectedCategory->id === (int) $category->id) {
                    continue;
                }

                $parentId = $selectedCategory->parent_id ?? null;
                $seen = [];

                while ($parentId && !in_array((int) $parentId, $seen, true)) {
                    if ((int) $parentId === (int) $category->id) {
                        return true;
                    }

                    $seen[] = (int) $parentId;
                    $parent = $categoryById->get($parentId);
                    $parentId = $parent->parent_id ?? null;
                }
            }

            return false;
        };
        $sortBy = (string) request('sort_by', '');
        $sortDir = strtolower((string) request('sort_dir', request('sort_order', 'asc'))) === 'desc' ? 'desc' : 'asc';
        $productRoute = Route::currentRouteName();
        $productRouteParams = $productRoute === 'products.seller'
            ? ['product_type' => request()->route('product_type')]
            : [];
        $listingFilters = $listingFilters ?? [
            'sku' => '', 'product_name' => '', 'brand' => '', 'drug_name' => '', 'drug_role' => '',
            'product_type' => '', 'schedule' => '', 'group_id' => '', 'marketed_by' => '',
            'manufactured_by' => '', 'imported_by' => '', 'hsn' => '', 'hs' => '', 'origin' => '',
            'shipping_days' => '', 'cash_on_delivery' => '', 'free_shipping' => '', 'has_warranty' => '',
            'refundable' => '', 'todays_deal' => '', 'featured' => '', 'approved' => '',
        ];
        $listingGroups = $listingGroups ?? collect();
        $filtersApplied = filled($sort_search ?? null)
            || filled($selected_category_id ?? null)
            || filled($seller_id ?? null)
            || (isset($published_status) && $published_status !== null && $published_status !== '' && $published_status !== 'All')
            || filled(request('type'))
            || collect($listingFilters)->contains(function ($value) {
                return $value !== null && $value !== '';
            });
        $listingDiscounts = $listingDiscounts ?? [];
        $listingCoupons = $listingCoupons ?? [];
        $listingCompanies = $listingCompanies ?? [];
        $companyColumnsReady = $companyColumnsReady ?? false;
        $listingSortUrl = function ($column) use ($productRoute, $productRouteParams, $sortBy, $sortDir) {
            $nextDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';

            return route($productRoute, array_merge($productRouteParams, request()->except('page'), [
                'sort_by' => $column,
                'sort_dir' => $nextDir,
            ]));
        };
        $listingSortIcon = function ($column) use ($sortBy, $sortDir) {
            if ($sortBy !== $column) {
                return 'la-sort text-muted';
            }

            return 'la-sort-amount-' . ($sortDir === 'asc' ? 'up' : 'down');
        };
        $listingValue = function ($value) {
            $text = trim((string) $value);

            return $text === '' ? '-' : $text;
        };
        $companyNamesFor = function ($product, $kind) use ($companyColumnsReady, $listingCompanies) {
            if (!$companyColumnsReady) {
                return '-';
            }

            if ($kind === 'marketed') {
                $manual = trim((string) ($product->marketed_by_name ?? ''));
                if ($manual !== '') {
                    return $manual;
                }
                $companyId = (int) ($product->marketed_by_id ?? 0);

                return $listingCompanies[$companyId] ?? '-';
            }

            $idColumn = $kind === 'manufactured' ? 'manufactured_by_ids' : 'import_by_ids';
            $nameColumn = $kind === 'manufactured' ? 'manufactured_by_names' : 'import_by_names';
            $names = [];
            foreach (json_decode($product->{$idColumn} ?? '[]', true) ?: [] as $companyId) {
                $companyName = $listingCompanies[(int) $companyId] ?? null;
                if ($companyName) {
                    $names[] = $companyName;
                }
            }
            foreach (json_decode($product->{$nameColumn} ?? '[]', true) ?: [] as $companyName) {
                $companyName = trim((string) $companyName);
                if ($companyName !== '') {
                    $names[] = $companyName;
                }
            }
            $names = array_values(array_unique($names));

            return $names ? implode(', ', $names) : '-';
        };
    @endphp

    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-auto">
                <h1 class="h3">{{ translate('All products') }}</h1>
            </div>
            @if ($type != 'Seller' && auth()->user()->can('add_new_product'))
                <div class="col text-right">
                    <a href="{{ route('products.create') }}" class="btn btn-circle btn-info">
                        <span>{{ translate('Create Product') }}</span>
                    </a>
                    <button id="downloadExcelBtn" class="btn btn-circle btn-success mx-1">Export Products</button>
                    <button type="button" class="btn btn-primary btn-circle mx-1" id="openModalBtn">
                        Upload Product Prices
                    </button>
                </div>
            @endif
        </div>
    </div>
    <br>

    <div class="card">
            <div class="card-header row gutters-5">
                <div class="col">
                    <h5 class="mb-md-0 h6">{{ translate('All Product') }}</h5>
                    @if ($filtersApplied)
                        <span class="badge badge-info">{{ translate('Filters applied') }}</span>
                    @endif
                </div>

                @can('product_delete')
                    <div class="dropdown mb-2 mb-md-0">
                        <button class="btn border dropdown-toggle" type="button" data-toggle="dropdown">
                            {{ translate('Bulk Action') }}
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item confirm-alert" href="javascript:void(0)" data-target="#bulk-delete-modal">
                                {{ translate('Delete selection') }}</a>
                        </div>
                    </div>
                @endcan

                <div class="col-auto ml-auto">
                    <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#productFilterModal">
                        {{ translate('Open Filters') }}
                    </button>
                    <a href="{{ url()->current() }}" class="btn btn-danger">{{ translate('Reset') }}</a>
                </div>
            </div>

        <form class="" id="sort_products" action="" method="GET">
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            @if (auth()->user()->can('product_delete'))
                                <th>
                                    <div class="form-group">
                                        <div class="aiz-checkbox-inline">
                                            <label class="aiz-checkbox">
                                                <input type="checkbox" class="check-all">
                                                <span class="aiz-square-check"></span>
                                            </label>
                                        </div>
                                    </div>
                                </th>
                            @endif
                            <th>{{ translate('Sr No.') }}</th>
                            @include('backend.inc.sortable_th', ['column' => 'sku', 'label' => translate('SKU'), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'name', 'labelHtml' => e(translate('Product / Brand Name')).'<br>'.e(translate('Drug Name')).'<br>'.e(translate('Drug Role')), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'product_type', 'labelHtml' => e(translate('Product Type')).'<br>'.e(translate('Schedule')), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'stock', 'label' => translate('Total Stock'), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'md'])
                            @include('backend.inc.sortable_th', ['column' => 'role_price', 'label' => translate('Role Prices'), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            <th>{{ translate('Discounts') }}<br>{{ translate('Scheme') }}<br>{{ translate('Coupon') }}<br>{{ translate('Pointwise') }}</th>
                            <th>{{ translate('Product wise') }}<br>{{ translate('Batchwise') }}<br>{{ translate('Amount wise') }}</th>
                            <th>
                                <a href="{{ $listingSortUrl('category') }}" class="text-reset d-inline-flex align-items-end">
                                    <span>{{ translate('Category') }}</span>
                                    <i class="las {{ $listingSortIcon('category') }} ml-1"></i>
                                </a>
                                <br>
                                <a href="{{ $listingSortUrl('group') }}" class="text-reset d-inline-flex align-items-end">
                                    <span>{{ translate('Group') }}</span>
                                    <i class="las {{ $listingSortIcon('group') }} ml-1"></i>
                                </a>
                            </th>
                            <th>{{ translate('Company') }}<br>{{ translate('Marketed By') }}<br>{{ translate('Manufactured By') }}<br>{{ translate('Imported By') }}</th>
                            @include('backend.inc.sortable_th', ['column' => 'hsn', 'labelHtml' => e(translate('HSN Code')).'<br>'.e(translate('HS Code')).'<br>'.e(translate('Origin')).'<br>'.e(translate('Shipping Days')), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            <th data-breakpoints="lg">{{ translate('COD') }}<br>{{ translate('Free Shipping') }}<br>{{ translate('Warranty') }}<br>{{ translate('Refundable') }}</th>
                            <th data-breakpoints="lg">
                                <a href="{{ $listingSortUrl('todays_deal') }}" class="text-reset d-inline-flex align-items-end">
                                    <span>{{ translate('Todays Deal') }}</span>
                                    <i class="las {{ $listingSortIcon('todays_deal') }} ml-1"></i>
                                </a>
                                <br>
                                <a href="{{ $listingSortUrl('published') }}" class="text-reset d-inline-flex align-items-end">
                                    <span>{{ translate('Published') }}</span>
                                    <i class="las {{ $listingSortIcon('published') }} ml-1"></i>
                                </a>
                                <br>
                                <a href="{{ $listingSortUrl('featured') }}" class="text-reset d-inline-flex align-items-end">
                                    <span>{{ translate('Featured') }}</span>
                                    <i class="las {{ $listingSortIcon('featured') }} ml-1"></i>
                                </a>
                                @if (get_setting('product_approve_by_admin') == 1 && $type == 'Seller')
                                    <br>
                                    <a href="{{ $listingSortUrl('approved') }}" class="text-reset d-inline-flex align-items-end">
                                        <span>{{ translate('Approved') }}</span>
                                        <i class="las {{ $listingSortIcon('approved') }} ml-1"></i>
                                    </a>
                                @endif
                            </th>
                            <th data-breakpoints="sm" class="">{{ translate('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $key => $product)
                            <tr>
                                @if (auth()->user()->can('product_delete'))
                                    <td>
                                        <div class="form-group d-inline-block">
                                            <label class="aiz-checkbox">
                                                <input type="checkbox" class="check-one" name="id[]"
                                                    value="{{ $product->id }}">
                                                <span class="aiz-square-check"></span>
                                            </label>
                                        </div>
                                    </td>
                                @endif
                                <td>{{ $key + 1 + ($products->currentPage() - 1) * $products->perPage() }}</td>
                                <td>
                                    @php
                                        $skus = $product->stocks
                                            ->pluck('sku')
                                            ->map(function ($sku) {
                                                return trim((string) $sku);
                                            })
                                            ->filter()
                                            ->unique()
                                            ->values();
                                    @endphp

                                    @if ($skus->isNotEmpty())
                                        @foreach ($skus as $sku)
                                            <div>
                                                <a href="{{ route('admin.purchase_history.consolidated_productwise', ['product_sku' => $sku]) }}"
                                                   class="text-primary" target="_blank" rel="noopener noreferrer">
                                                    {{ $sku }}
                                                </a>
                                            </div>
                                        @endforeach
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <div class="row gutters-5 w-200px w-md-300px mw-100">
                                        <div class="col-auto">
                                            <img src="{{ uploaded_asset($product->thumbnail_img) }}" alt="Image"
                                                class="size-50px img-fit">
                                        </div>
                                        <div class="col">
                                            <span class="text-muted text-truncate-2">{{ $product->getTranslation('name') }}</span>
                                            <div class="listing-stack-line">{{ $listingValue(optional($product->brand)->getTranslation('name')) }}</div>
                                            <div class="listing-stack-line">{{ $listingValue($product->drug_name) }}</div>
                                            <div class="listing-stack-line">{{ $listingValue($product->role_label) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="listing-stack-line">{{ $listingValue($product->product_type) }}</div>
                                    <div class="listing-stack-line">{{ $listingValue($product->schedule) }}</div>
                                </td>
                                <td>
                                    @if ($product->digital == 1)
                                        <span class="badge badge-inline badge-info">{{ translate('Digital Product') }}</span>
                                    @else
                                        @php
                                            $qty = 0;
                                            $stocks = [];

                                            if ($product->variant_product) {
                                                foreach ($product->stocks as $key => $stock) {
                                                    $hasBatches = $stock->batches && $stock->batches->count() > 0;
                                                    $stockQty = $hasBatches
                                                        ? (int) $stock->batches->sum('qty')
                                                        : (int) ($stock->qty ?? 0);

                                                    $stocks[] = ['variant' => $stock->variant, 'qty' => $stockQty];
                                                    $qty += $stockQty;
                                                }
                                            } else {
                                                $firstStock = $product->stocks->first();
                                                $hasBatches = $firstStock && $firstStock->batches && $firstStock->batches->count() > 0;
                                                $stockQty = $hasBatches
                                                    ? (int) $firstStock->batches->sum('qty')
                                                    : (int) (optional($firstStock)->qty ?? 0);

                                                $qty = $stockQty;
                                                $stocks[] = ['variant' => optional($firstStock)->variant ?? '-', 'qty' => $stockQty];
                                            }
                                        @endphp

                                        @if (count($stocks) > 4)
                                            <div class="stock-list" id="stock-list-{{ $product->id }}">
                                                <div class="stock-items">
                                                    @foreach($stocks as $index => $stock)
                                                        <div class="stock-item {{ $index >= 4 ? 'hidden' : '' }}" data-index="{{ $index }}">
                                                            {{ $stock['variant'] }} - {{ $stock['qty'] }}
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <a class="badge badge-inline badge-primary text-light view-more-all-product btn-sm btn-link view-more-toggle" onclick="toggleViewMore('stock-list-{{ $product->id }}')">View More</a>
                                            </div>
                                        @else
                                            <div class="stock-items">
                                                @foreach($stocks as $stock)
                                                    <div class="stock-item">
                                                        {{ $stock['variant'] }} - {{ $stock['qty'] }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if ($qty <= $product->low_stock_quantity)
                                            <span class="badge badge-inline badge-danger">{{ translate('Low') }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $rolePriceValues = collect();

                                        foreach ($product->stocks as $stock) {
                                            foreach (($stock->batches ?? collect()) as $batch) {
                                                $batchRolePrices = is_string($batch->role_price)
                                                    ? json_decode($batch->role_price, true)
                                                    : $batch->role_price;

                                                if (is_array($batchRolePrices)) {
                                                    foreach ($batchRolePrices as $role => $price) {
                                                        if (is_numeric($price)) {
                                                            $rolePriceValues->push(['role' => $role, 'price' => (float) $price]);
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        if ($rolePriceValues->isEmpty()) {
                                            $productRolePrices = is_string($product->role_price)
                                                ? json_decode($product->role_price, true)
                                                : $product->role_price;

                                            if (is_array($productRolePrices)) {
                                                foreach ($productRolePrices as $role => $price) {
                                                    if (is_numeric($price)) {
                                                        $rolePriceValues->push(['role' => $role, 'price' => (float) $price]);
                                                    }
                                                }
                                            }
                                        }

                                        $rolePriceGroups = $rolePriceValues->groupBy('role');
                                    @endphp

                                    @forelse ($rolePriceGroups as $role => $prices)
                                        @php
                                            $minimumRolePrice = $prices->min('price');
                                            $maximumRolePrice = $prices->max('price');
                                        @endphp
                                        <div class="text-nowrap">
                                            <strong>{{ strtoupper($role) }}:</strong>
                                            {{ single_price($minimumRolePrice) }}
                                            @if ($maximumRolePrice > $minimumRolePrice)
                                                - {{ single_price($maximumRolePrice) }}
                                            @endif
                                        </div>
                                    @empty
                                        -
                                    @endforelse
                                </td>
                                @php
                                    $productDiscountCodes = $listingDiscounts[$product->id] ?? [];
                                    $discountCodeText = function ($type) use ($productDiscountCodes) {
                                        $codes = array_values(array_filter($productDiscountCodes[$type] ?? []));

                                        return $codes ? implode(', ', $codes) : '-';
                                    };
                                    $couponCodeText = implode(', ', array_filter($listingCoupons[$product->id] ?? []));
                                @endphp
                                <td>
                                    <div class="listing-stack-line">{{ $discountCodeText('schemewise') }}</div>
                                    <div class="listing-stack-line">{{ $couponCodeText !== '' ? $couponCodeText : '-' }}</div>
                                    <div class="listing-stack-line">{{ $discountCodeText('pointwise') }}</div>
                                </td>
                                <td>
                                    <div class="listing-stack-line">{{ $discountCodeText('productwise') }}</div>
                                    <div class="listing-stack-line">{{ $discountCodeText('batchwise') }}</div>
                                    <div class="listing-stack-line">{{ $discountCodeText('amount_wise') }}</div>
                                </td>
                                <td>
                                    @php
                                        $productCategories = $product->categories;
                                        if ($product->main_category && !$productCategories->contains('id', $product->main_category->id)) {
                                            $productCategories = $productCategories->prepend($product->main_category);
                                        }
                                        $productCategories = $productCategories->unique('id')->values();
                                        $leafCategories = $productCategories->reject(function ($category) use ($productCategories, $categoryIsAncestorOfSelected) {
                                            return $categoryIsAncestorOfSelected($category, $productCategories);
                                        })->values();

                                        if ($leafCategories->isEmpty()) {
                                            $leafCategories = $productCategories;
                                        }
                                    @endphp
                                    @forelse ($leafCategories as $category)
                                        <div class="product-category-hierarchy">
                                            @foreach ($categoryPath($category) as $pathCategory)
                                                <span class="product-category-item">
                                                    <span class="badge badge-inline {{ (int) $pathCategory->id === (int) $product->category_id ? 'badge-primary' : 'badge-soft-secondary' }}"
                                                        @if ((int) $pathCategory->id === (int) $product->category_id) title="{{ translate('Main Category') }}" @endif>
                                                        {{ $pathCategory->getTranslation('name') }}
                                                        @if ((int) $pathCategory->id === (int) $product->category_id)
                                                            ({{ translate('Main') }})
                                                        @endif
                                                    </span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @empty
                                        -
                                    @endforelse
                                    <div class="listing-stack-line mt-1">{{ $listingValue(optional($product->main_group)->getTranslation('name')) }}</div>
                                </td>
                                <td>
                                    <div class="listing-stack-line">{{ $companyNamesFor($product, 'marketed') }}</div>
                                    <div class="listing-stack-line">{{ $companyNamesFor($product, 'manufactured') }}</div>
                                    <div class="listing-stack-line">{{ $companyNamesFor($product, 'imported') }}</div>
                                </td>
                                <td>
                                    <div class="listing-stack-line">{{ $listingValue($product->product_hsn) }}</div>
                                    <div class="listing-stack-line">{{ $listingValue($product->product_hs) }}</div>
                                    <div class="listing-stack-line">{{ $listingValue($product->product_origin) }}</div>
                                    <div class="listing-stack-line">{{ $listingValue($product->est_shipping_days) }}</div>
                                </td>
                                <td>
                                    @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('COD'), 'showLabel' => false, 'field' => 'cash_on_delivery', 'checked' => $product->cash_on_delivery == 1, 'onchange' => 'update_listing_flag(this)'])
                                    @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('Free Shipping'), 'showLabel' => false, 'field' => 'free_shipping', 'checked' => $product->shipping_type == 'free', 'onchange' => 'update_listing_flag(this)'])
                                    @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('Warranty'), 'showLabel' => false, 'field' => 'has_warranty', 'checked' => $product->has_warranty == 1, 'onchange' => 'update_listing_flag(this)'])
                                    @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('Refundable'), 'showLabel' => false, 'field' => 'refundable', 'checked' => $product->refundable == 1, 'onchange' => 'update_listing_flag(this)'])
                                </td>
                                <td>
                                    @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('Todays Deal'), 'showLabel' => false, 'field' => 'todays_deal', 'checked' => $product->todays_deal == 1, 'onchange' => 'update_todays_deal(this)'])
                                    @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('Published'), 'showLabel' => false, 'field' => 'published', 'checked' => $product->published == 1, 'onchange' => 'update_published(this)'])
                                    @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('Featured'), 'showLabel' => false, 'field' => 'featured', 'checked' => $product->featured == 1, 'onchange' => 'update_featured(this)'])
                                    @if (get_setting('product_approve_by_admin') == 1 && $type == 'Seller')
                                        @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('Approved'), 'showLabel' => false, 'field' => 'approved', 'checked' => $product->approved == 1, 'onchange' => 'update_approved(this)'])
                                    @endif
                                </td>
                                <td class="text-right drop-down-text-icon">
                                   <div class="dropdown">
    <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" id="productActionDropdown{{ $product->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="las la-ellipsis-v"></i>
    </button>

                                        <div class="dropdown-menu dropdown-menu-right p-2" aria-labelledby="productActionDropdown{{ $product->id }}">
                                            <div class="listing-flag-fallback text-left">
                                                @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('COD'), 'field' => 'cash_on_delivery', 'checked' => $product->cash_on_delivery == 1, 'onchange' => 'update_listing_flag(this)'])
                                                @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('Free Shipping'), 'field' => 'free_shipping', 'checked' => $product->shipping_type == 'free', 'onchange' => 'update_listing_flag(this)'])
                                                @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('Warranty'), 'field' => 'has_warranty', 'checked' => $product->has_warranty == 1, 'onchange' => 'update_listing_flag(this)'])
                                                @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('Refundable'), 'field' => 'refundable', 'checked' => $product->refundable == 1, 'onchange' => 'update_listing_flag(this)'])
                                                @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('Todays Deal'), 'field' => 'todays_deal', 'checked' => $product->todays_deal == 1, 'onchange' => 'update_todays_deal(this)'])
                                                @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('Published'), 'field' => 'published', 'checked' => $product->published == 1, 'onchange' => 'update_published(this)'])
                                                @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('Featured'), 'field' => 'featured', 'checked' => $product->featured == 1, 'onchange' => 'update_featured(this)'])
                                                @if (get_setting('product_approve_by_admin') == 1 && $type == 'Seller')
                                                    @include('backend.product.products.partials.listing_switch', ['product' => $product, 'label' => translate('Approved'), 'field' => 'approved', 'checked' => $product->approved == 1, 'onchange' => 'update_approved(this)'])
                                                @endif
                                            </div>
                                            <!-- View -->
                                            <a class="btn"
                                            href="{{ route('product', $product->slug) }}" target="_blank"
                                            title="{{ translate('View') }}">
                                                <i class="las la-eye btn-soft-success btn-icon btn-circle btn-sm mr-2"></i> <span class="ms-1">{{ translate('View') }}</span>
                                            </a>

                                            @can('product_edit')
                                                @if ($type == 'Seller')
                                                    <a class="btn"
                                                    href="{{ route('products.seller.edit', ['id' => $product->id, 'lang' => env('DEFAULT_LANGUAGE')]) }}"
                                                    title="{{ translate('Edit') }}">
                                                        <i class="las la-edit btn-soft-primary btn-icon btn-circle btn-sm mr-2"></i> <span class="ms-1">{{ translate('Edit') }}</span>
                                                    </a>
                                                @else
                                                    <a class="btn"
                                                    href="{{ route('products.admin.edit', ['id' => $product->id, 'lang' => env('DEFAULT_LANGUAGE')]) }}"
                                                    title="{{ translate('Edit') }}">
                                                        <i class="las la-edit btn-soft-primary btn-icon btn-circle btn-sm mr-2"></i> <span class="ms-1">{{ translate('Edit') }}</span>
                                                    </a>
                                                @endif
                                            @endcan

                                            @can('product_duplicate')
                                                {{-- 
                                                <a class="btn"
                                                href="{{ route('products.duplicate', ['id' => $product->id, 'type' => $type]) }}"
                                                title="{{ translate('Duplicate') }}">
                                                    <i class="las la-copy btn-soft-warning btn-icon btn-circle btn-sm mr-2"></i> <span class="ms-1">{{ translate('Duplicate') }}</span>
                                                </a>
                                                --}}
                                            @endcan

                                            @can('product_delete')
                                                <a href="#"
                                                class="btn confirm-delete"
                                                data-href="{{ route('products.destroy', $product->id) }}"
                                                title="{{ translate('Delete') }}">
                                                    <i class="las la-trash btn-soft-danger btn-icon btn-circle btn-sm mr-2"></i> <span class="ms-1">{{ translate('Delete') }}</span>
                                                </a>
                                            @endcan
                                        </div>
                                    </div>

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="aiz-pagination">
                    {{ $products->appends(request()->input())->links() }}
                </div>
            </div>
        </form>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="uploadForm-stock-excel" action="{{ route('price-update.upload') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadModalLabel">Upload Excel or CSV</h5>
                        <button type="button" class="btn-close upload-close-btn-admin" data-bs-dismiss="modal" id="closeModalBtn"><i class="las fs-18 la-minus"></i></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info small mb-3">
                            <strong>Note:</strong><br>
                            During bulk price updates, any rows with blank <em>Price</em> or <em>PTS Percentage</em> values 
                            will be skipped automatically.<br><br>
                            Before uploading, always <strong>download the latest product Excel file</strong> and update prices 
                            in that file only. This ensures data accuracy, as product variants may have been added or removed 
                            since the last update.
                        </div>                        
                        <input type="file" name="price_file" accept=".xlsx,.xls,.csv" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success btn-circle">Upload and Update</button>
                        <button type="button" class="btn btn-secondary btn-circle" data-bs-dismiss="modal"
                            id="cancelModalBtn">Close</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@section('modal')
    <!-- Delete modal -->
    @include('modals.delete_modal')
    <!-- Bulk Delete modal -->
    @include('modals.bulk_delete_modal')

    <form action="{{ url()->current() }}" method="GET">
        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
        <div class="modal fade" id="productFilterModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Filter Products') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row gutters-5">
                            <div class="col-md-12 mb-3">
                                <label>{{ translate('Search') }}</label>
                                <input type="text" class="form-control" name="search" value="{{ $sort_search ?? '' }}"
                                    placeholder="{{ translate('Search by Name, Drug, Role, Attribute, SKU, Brand, Category, Attribute or Schedule') }}">
                            </div>
                            @if ($type == 'Seller')
                                <div class="col-md-6 mb-3">
                                    <label>{{ translate('Seller') }}</label>
                                    <select class="form-control" name="user_id">
                                        <option value="">{{ translate('All Sellers') }}</option>
                                        @foreach (App\Models\User::where('user_type', '=', 'seller')->get() as $seller)
                                            <option value="{{ $seller->id }}" @selected($seller->id == ($seller_id ?? null))>
                                                {{ optional($seller->shop)->name }} ({{ $seller->name }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @if ($type == 'All' && get_setting('vendor_system_activation') == 1)
                                <div class="col-md-6 mb-3">
                                    <label>{{ translate('Seller') }}</label>
                                    <select class="form-control" name="user_id">
                                        <option value="">{{ translate('All Sellers') }}</option>
                                        @foreach (App\Models\User::where('user_type', '=', 'admin')->orWhere('user_type', '=', 'seller')->get() as $seller)
                                            <option value="{{ $seller->id }}" @selected($seller->id == ($seller_id ?? null))>{{ $seller->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Category') }}</label>
                                <select class="form-control" name="category_id">
                                    <option value="">{{ translate('All Categories') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((string) ($selected_category_id ?? '') === (string) $category->id)>
                                            {{ $category->getTranslation('name') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('SKU') }}</label>
                                <input type="text" class="form-control" name="sku" value="{{ $listingFilters['sku'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Product / Brand Name') }}</label>
                                <input type="text" class="form-control" name="product_name" value="{{ $listingFilters['product_name'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Brand') }}</label>
                                <input type="text" class="form-control" name="brand" value="{{ $listingFilters['brand'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Drug Name') }}</label>
                                <input type="text" class="form-control" name="drug_name" value="{{ $listingFilters['drug_name'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Drug Role') }}</label>
                                <input type="text" class="form-control" name="drug_role" value="{{ $listingFilters['drug_role'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Product Type') }}</label>
                                <input type="text" class="form-control" name="product_type" value="{{ $listingFilters['product_type'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Schedule') }}</label>
                                <input type="text" class="form-control" name="schedule" value="{{ $listingFilters['schedule'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Group') }}</label>
                                <select class="form-control" name="group_id">
                                    <option value="">{{ translate('All Groups') }}</option>
                                    @foreach ($listingGroups as $group)
                                        <option value="{{ $group->id }}" @selected((string) $listingFilters['group_id'] === (string) $group->id)>{{ $group->getTranslation('name') }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Marketed By') }}</label>
                                <input type="text" class="form-control" name="marketed_by" value="{{ $listingFilters['marketed_by'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Manufactured By') }}</label>
                                <input type="text" class="form-control" name="manufactured_by" value="{{ $listingFilters['manufactured_by'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Imported By') }}</label>
                                <input type="text" class="form-control" name="imported_by" value="{{ $listingFilters['imported_by'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('HSN Code') }}</label>
                                <input type="text" class="form-control" name="hsn" value="{{ $listingFilters['hsn'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('HS Code') }}</label>
                                <input type="text" class="form-control" name="hs" value="{{ $listingFilters['hs'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Origin') }}</label>
                                <input type="text" class="form-control" name="origin" value="{{ $listingFilters['origin'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Shipping Days') }}</label>
                                <input type="text" class="form-control" name="shipping_days" value="{{ $listingFilters['shipping_days'] }}">
                            </div>
                            @php
                                $listingYesNo = function ($name, $label) use ($listingFilters) {
                                    return [
                                        'name' => $name,
                                        'label' => $label,
                                        'value' => $listingFilters[$name] ?? '',
                                    ];
                                };
                            @endphp
                            @foreach ([
                                $listingYesNo('cash_on_delivery', translate('COD')),
                                $listingYesNo('free_shipping', translate('Free Shipping')),
                                $listingYesNo('has_warranty', translate('Warranty')),
                                $listingYesNo('refundable', translate('Refundable')),
                                $listingYesNo('todays_deal', translate('Todays Deal')),
                                $listingYesNo('featured', translate('Featured')),
                            ] as $flagFilter)
                                <div class="col-md-6 mb-3">
                                    <label>{{ $flagFilter['label'] }}</label>
                                    <select class="form-control" name="{{ $flagFilter['name'] }}">
                                        <option value="">{{ translate('All') }}</option>
                                        <option value="1" @selected($flagFilter['value'] === '1')>{{ translate('Yes') }}</option>
                                        <option value="0" @selected($flagFilter['value'] === '0')>{{ translate('No') }}</option>
                                    </select>
                                </div>
                            @endforeach
                            @if (get_setting('product_approve_by_admin') == 1 && ($type ?? '') == 'Seller')
                                <div class="col-md-6 mb-3">
                                    <label>{{ translate('Approved') }}</label>
                                    <select class="form-control" name="approved">
                                        <option value="">{{ translate('All') }}</option>
                                        <option value="1" @selected($listingFilters['approved'] === '1')>{{ translate('Yes') }}</option>
                                        <option value="0" @selected($listingFilters['approved'] === '0')>{{ translate('No') }}</option>
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Published') }}</label>
                                <select class="form-control" name="published_status">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="1" @selected(($published_status ?? '') == '1')>{{ translate('Published') }}</option>
                                    <option value="0" @selected(($published_status ?? '') == '0')>{{ translate('Unpublished') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Sort By') }}</label>
                                <select class="form-control" name="type">
                                    <option value="">{{ translate('Default') }}</option>
                                    <option value="rating,desc" @selected(($col_name ?? null) == 'rating' && ($query ?? null) == 'desc')>{{ translate('Rating (High > Low)') }}</option>
                                    <option value="rating,asc" @selected(($col_name ?? null) == 'rating' && ($query ?? null) == 'asc')>{{ translate('Rating (Low > High)') }}</option>
                                    <option value="num_of_sale,desc" @selected(($col_name ?? null) == 'num_of_sale' && ($query ?? null) == 'desc')>{{ translate('Num of Sale (High > Low)') }}</option>
                                    <option value="num_of_sale,asc" @selected(($col_name ?? null) == 'num_of_sale' && ($query ?? null) == 'asc')>{{ translate('Num of Sale (Low > High)') }}</option>
                                    <option value="unit_price,desc" @selected(($col_name ?? null) == 'unit_price' && ($query ?? null) == 'desc')>{{ translate('Base Price (High > Low)') }}</option>
                                    <option value="unit_price,asc" @selected(($col_name ?? null) == 'unit_price' && ($query ?? null) == 'asc')>{{ translate('Base Price (Low > High)') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ url()->current() }}" class="btn btn-danger">{{ translate('Reset') }}</a>
                        <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection


@section('script')
    <script type="text/javascript">
        $(document).on("change", ".check-all", function() {
            if (this.checked) {
                // Iterate each checkbox
                $('.check-one:checkbox').each(function() {
                    this.checked = true;
                });
            } else {
                $('.check-one:checkbox').each(function() {
                    this.checked = false;
                });
            }

        });

        $(document).ready(function() {
            //$('#container').removeClass('mainnav-lg').addClass('mainnav-sm');
        });

        function sync_listing_flag(el) {
            var key = el.getAttribute('data-flag-sync');
            if (!key) {
                return;
            }
            document.querySelectorAll('[data-flag-sync="' + key + '"]').forEach(function(other) {
                if (other !== el) {
                    other.checked = el.checked;
                }
            });
        }

        function update_listing_flag(el) {
            sync_listing_flag(el);

            if ('{{ env('DEMO_MODE') }}' == 'On') {
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                return;
            }

            $.post('{{ route('products.listing_flag') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                field: el.getAttribute('data-field'),
                status: el.checked ? 1 : 0
            }, function(data) {
                if (data == 1) {
                    AIZ.plugins.notify('success', '{{ translate('Product updated successfully') }}');
                } else {
                    AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                }
            });
        }

        function update_todays_deal(el) {
            sync_listing_flag(el);

            if ('{{ env('DEMO_MODE') }}' == 'On') {
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                return;
            }

            if (el.checked) {
                var status = 1;
            } else {
                var status = 0;
            }
            $.post('{{ route('products.todays_deal') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function(data) {
                if (data == 1) {
                    AIZ.plugins.notify('success', '{{ translate('Todays Deal updated successfully') }}');
                } else {
                    AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                }
            });
        }

        function update_published(el) {
            sync_listing_flag(el);

            if ('{{ env('DEMO_MODE') }}' == 'On') {
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                return;
            }

            if (el.checked) {
                var status = 1;
            } else {
                var status = 0;
            }
            $.post('{{ route('products.published') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function(data) {
                if (data == 1) {
                    AIZ.plugins.notify('success', '{{ translate('Published products updated successfully') }}');
                } else {
                    AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                }
            });
        }

        function update_approved(el) {
            sync_listing_flag(el);

            if ('{{ env('DEMO_MODE') }}' == 'On') {
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                return;
            }

            if (el.checked) {
                var approved = 1;
            } else {
                var approved = 0;
            }
            $.post('{{ route('products.approved') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                approved: approved
            }, function(data) {
                if (data == 1) {
                    AIZ.plugins.notify('success', '{{ translate('Product approval update successfully') }}');
                } else {
                    AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                }
            });
        }

        function update_featured(el) {
            sync_listing_flag(el);
            if ('{{ env('DEMO_MODE') }}' == 'On') {
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                return;
            }

            if (el.checked) {
                var status = 1;
            } else {
                var status = 0;
            }
            $.post('{{ route('products.featured') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function(data) {
                if (data == 1) {
                    AIZ.plugins.notify('success', '{{ translate('Featured products updated successfully') }}');
                } else {
                    AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                }
            });
        }

        function sort_products(el) {
            $('#sort_products').submit();
        }

        function bulk_delete() {
            var data = new FormData($('#sort_products')[0]);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{ route('bulk-product-delete') }}",
                type: 'POST',
                data: data,
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response == 1) {
                        location.reload();
                    }
                }
            });
        }
    </script>
    <script>
        $('#downloadExcelBtn').on('click', function() {
            var button = $(this);
            button.prop('disabled', true).text('Please wait...');

            let queryParams = window.location.search; // includes "?" if exists

            // Base download route
            let downloadUrl = '{{ route('download-product-stock-excel') }}';

            // Append query parameters if they exist
            if (queryParams) {
                downloadUrl += queryParams;
            }

            // Redirect to the final URL
            window.location.href = downloadUrl;

            // Re-enable the button after some delay or if needed
            setTimeout(function() {
                button.prop('disabled', false).text('Download Excel');
            }, 3000);
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var myModal = new bootstrap.Modal(document.getElementById('uploadModal'));

            // Open modal on button click
            document.getElementById('openModalBtn').addEventListener('click', function() {
                myModal.show();
            });

            // Close modal on close button
            document.getElementById('closeModalBtn').addEventListener('click', function() {
                myModal.hide();
            });

            // Close modal on cancel button
            document.getElementById('cancelModalBtn').addEventListener('click', function() {
                myModal.hide();
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#uploadForm-stock-excel').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                var form = $(this);
                var button = form.find('button[type="submit"]');
                button.prop('disabled', true).text('Uploading...');

                var formData = new FormData(this);

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        button.prop('disabled', false).text('Upload and Update');
                        AIZ.plugins.notify('success', response.message);
                        setTimeout(function() {
                            $('#uploadModal').modal('hide');
                            location.reload();
                        }, 1000);
                    },
                    error: function(xhr) {
                        button.prop('disabled', false).text('Upload and Update');

                        if (xhr.status === 422) {
                            // Validation errors
                            var errorMsg = `${xhr.responseJSON.message}`;

                            if (xhr.responseJSON && xhr.responseJSON.file) {
                                errorMsg += ' <a href="' + xhr.responseJSON.file +
                                    '" target="_blank">Download error file</a>';
                            }
                            AIZ.plugins.notify('danger', errorMsg);
                            setTimeout(function() {
                                $('#uploadModal').modal('hide');
                                location.reload();
                            }, 7000);
                        } else {
                            // Other errors
                            AIZ.plugins.notify('danger', 'An unexpected error occurred.');
                        }
                    }
                });
            });
        });
    </script>

    <script>
        let isExpanded = {}; // Track expanded state for each stock list

        // Toggle view for specific stock list
        function toggleViewMore(stockListId) {
            const stockList = document.getElementById(stockListId);
            const items = stockList.querySelectorAll('.stock-item');
            const button = stockList.querySelector('.view-more-toggle');

            // Toggle visibility of items beyond the first 4
            items.forEach((item, index) => {
                if (index >= 4) {
                    item.classList.toggle('hidden');
                }
            });

            // Update the button text based on whether the list is expanded or not
            if (isExpanded[stockListId]) {
                button.innerText = 'View More';
            } else {
                button.innerText = 'View Less';
            }

            // Toggle the expanded state for the specific list
            isExpanded[stockListId] = !isExpanded[stockListId];
        }
    </script>
@endsection
