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
        $filtersApplied = filled($sort_search ?? null)
            || filled($selected_category_id ?? null)
            || filled($seller_id ?? null)
            || (isset($published_status) && $published_status !== null && $published_status !== '' && $published_status !== 'All')
            || filled(request('type'));
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
                            @include('backend.inc.sortable_th', ['column' => 'name', 'label' => translate('Product Name'), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            {{-- @if ($type == 'Seller' || $type == 'All')
                                <th data-breakpoints="lg">{{ translate('Added By') }}</th>
                            @endif
                            <th data-breakpoints="sm">{{ translate('Info') }}</th> --}}
                            @include('backend.inc.sortable_th', ['column' => 'category', 'label' => translate('Category'), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'stock', 'label' => translate('Total Stock'), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'md'])
                            @include('backend.inc.sortable_th', ['column' => 'brand', 'label' => translate('Brand'), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'role_price', 'label' => translate('Role Prices'), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'group', 'label' => translate('Group'), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'schedule', 'label' => translate('Schedule'), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'todays_deal', 'label' => translate('Todays Deal'), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                            @include('backend.inc.sortable_th', ['column' => 'published', 'label' => translate('Published'), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                            @if (get_setting('product_approve_by_admin') == 1 && $type == 'Seller')
                                @include('backend.inc.sortable_th', ['column' => 'approved', 'label' => translate('Approved'), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                            @endif
                            @include('backend.inc.sortable_th', ['column' => 'featured', 'label' => translate('Featured'), 'routeName' => $productRoute, 'routeParams' => $productRouteParams, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
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
                                            <span
                                                class="text-muted text-truncate-2">{{ $product->getTranslation('name') }}</span>
                                            <small class="d-block text-muted mt-1">
                                                {{ translate('Drug Name') }}: {{ $product->drug_name ?: '-' }}
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                {{-- @if ($type == 'Seller' || $type == 'All')
                                    <td>{{ optional($product->user)->name }}</td>
                                @endif
                                <td>
                                    <strong>{{ translate('Num of Sale') }}:</strong> {{ $product->num_of_sale }}
                                    {{ translate('times') }} </br>
                                    <strong>{{ translate('Base Price') }}:</strong>
                                    {{ single_price($product->unit_price) }} </br>
                                    <strong>{{ translate('Rating') }}:</strong> {{ $product->rating }} </br>
                                </td> --}}
                                {{-- <td>
                                    @if ($product->digital == 1)
                                        <span
                                            class="badge badge-inline badge-info">{{ translate('Digital Product') }}</span>
                                    @else
                                        @php
                                            $qty = 0;
                                            if ($product->variant_product) {
                                                foreach ($product->stocks as $key => $stock) {
                                                    $qty += $stock->qty;
                                                    echo $stock->variant . ' - ' . $stock->qty . '<br>';
                                                }
                                            } else {
                                                //$qty = $product->current_stock;
                                                $qty = optional($product->stocks->first())->qty;
                                                echo $qty;
                                            }
                                        @endphp
                                        @if ($qty <= $product->low_stock_quantity)
                                            <span class="badge badge-inline badge-danger">{{ translate('Low') }}</span>
                                        @endif
                                    @endif

                                </td> --}}
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
                                <td>{{ optional($product->brand)->getTranslation('name') ?? '-' }}</td>
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
                                <td>{{ optional($product->main_group)->getTranslation('name') ?? '-' }}</td>
                                <td>{{ $product->schedule ?: '-' }}</td>
                                <td>
                                    <label class="aiz-switch aiz-switch-success mb-0">
                                        <input onchange="update_todays_deal(this)" value="{{ $product->id }}"
                                            type="checkbox" <?php if ($product->todays_deal == 1) {
                                                echo 'checked';
                                            } ?>>
                                        <span class="slider round"></span>
                                    </label>
                                </td>
                                <td>
                                    <label class="aiz-switch aiz-switch-success mb-0">
                                        <input onchange="update_published(this)" value="{{ $product->id }}"
                                            type="checkbox" <?php if ($product->published == 1) {
                                                echo 'checked';
                                            } ?>>
                                        <span class="slider round"></span>
                                    </label>
                                </td>
                                @if (get_setting('product_approve_by_admin') == 1 && $type == 'Seller')
                                    <td>
                                        <label class="aiz-switch aiz-switch-success mb-0">
                                            <input onchange="update_approved(this)" value="{{ $product->id }}"
                                                type="checkbox" <?php if ($product->approved == 1) {
                                                    echo 'checked';
                                                } ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </td>
                                @endif
                                <td>
                                    <label class="aiz-switch aiz-switch-success mb-0">
                                        <input onchange="update_featured(this)" value="{{ $product->id }}"
                                            type="checkbox" <?php if ($product->featured == 1) {
                                                echo 'checked';
                                            } ?>>
                                        <span class="slider round"></span>
                                    </label>
                                </td>
                                <td class="text-right drop-down-text-icon">
                                   <div class="dropdown">
    <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" id="productActionDropdown{{ $product->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="las la-ellipsis-v"></i>
    </button>

                                        <div class="dropdown-menu dropdown-menu-right p-2" aria-labelledby="productActionDropdown{{ $product->id }}">
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

        function update_todays_deal(el) {

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
