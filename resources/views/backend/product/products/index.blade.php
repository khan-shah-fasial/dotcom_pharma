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
        <form class="" id="sort_products" action="" method="GET">
            <div class="card-header row gutters-5">
                <div class="col">
                    <h5 class="mb-md-0 h6">{{ translate('All Product') }}</h5>
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

                @if ($type == 'Seller')
                    <div class="col-md-2 ml-auto">
                        <select class="form-control form-control-sm aiz-selectpicker mb-2 mb-md-0" id="user_id"
                            name="user_id" onchange="sort_products()">
                            <option value="">{{ translate('All Sellers') }}</option>
                            @foreach (App\Models\User::where('user_type', '=', 'seller')->get() as $key => $seller)
                                <option value="{{ $seller->id }}" @if ($seller->id == $seller_id) selected @endif>
                                    {{ $seller->shop->name }} ({{ $seller->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if ($type == 'All' && get_setting('vendor_system_activation') == 1)
                    <div class="col-md-2 ml-auto">
                        <select class="form-control form-control-sm aiz-selectpicker mb-2 mb-md-0" id="user_id"
                            name="user_id" onchange="sort_products()">
                            <option value="">{{ translate('All Sellers') }}</option>
                            @foreach (App\Models\User::where('user_type', '=', 'admin')->orWhere('user_type', '=', 'seller')->get() as $key => $seller)
                                <option value="{{ $seller->id }}" @if ($seller->id == $seller_id) selected @endif>
                                    {{ $seller->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <select class="form-control form-control-sm aiz-selectpicker mb-2 mb-md-0"
                        name="category_id" id="category_id" data-live-search="true"
                        onchange="sort_products()">
                        <option value="">{{ translate('All Categories') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) $selected_category_id === (string) $category->id)>
                                {{ $category->getTranslation('name') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 ml-auto">
                    <select class="form-control form-control-sm aiz-selectpicker mb-2 mb-md-0" name="type" id="type"
                        onchange="sort_products()">
                        <option value="">{{ translate('Sort By') }}</option>
                        <option value="rating,desc"
                            @isset($col_name, $query) @if ($col_name == 'rating' && $query == 'desc') selected @endif @endisset>
                            {{ translate('Rating (High > Low)') }}</option>
                        <option value="rating,asc"
                            @isset($col_name, $query) @if ($col_name == 'rating' && $query == 'asc') selected @endif @endisset>
                            {{ translate('Rating (Low > High)') }}</option>
                        <option value="num_of_sale,desc"
                            @isset($col_name, $query) @if ($col_name == 'num_of_sale' && $query == 'desc') selected @endif @endisset>
                            {{ translate('Num of Sale (High > Low)') }}</option>
                        <option value="num_of_sale,asc"
                            @isset($col_name, $query) @if ($col_name == 'num_of_sale' && $query == 'asc') selected @endif @endisset>
                            {{ translate('Num of Sale (Low > High)') }}</option>
                        <option value="unit_price,desc"
                            @isset($col_name, $query) @if ($col_name == 'unit_price' && $query == 'desc') selected @endif @endisset>
                            {{ translate('Base Price (High > Low)') }}</option>
                        <option value="unit_price,asc"
                            @isset($col_name, $query) @if ($col_name == 'unit_price' && $query == 'asc') selected @endif @endisset>
                            {{ translate('Base Price (Low > High)') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <input type="text" class="form-control form-control-sm" id="search"
                            name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset
                            placeholder="{{ translate('Search by Name, Drug, Role, Attribute, SKU, Brand, Category, Attribute or Schedule') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-control form-control-sm aiz-selectpicker mb-2 mb-md-0" name="published_status" id="published_status" onchange="sort_products()">
                        <option value="">{{ translate('Filter By Status') }}</option>
                        <option value="1"
                            @isset($published_status) @if ($published_status == '1') selected @endif @endisset>
                            {{ translate('Published') }}</option>
                        <option value="0"
                            @isset($published_status) @if ($published_status == '0') selected @endif @endisset>
                            {{ translate('Unpublished') }}</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary mb-2 mb-md-0">
                        {{ translate('Search') }}
                    </button>
                    <a href="{{ url()->current() }}" class="btn btn-sm btn-soft-secondary mb-2 mb-md-0">
                        {{ translate('Reset') }}
                    </a>
                </div>
            </div>

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
                            <th>
                                <a href="{{ url()->current() . '?' . http_build_query(array_merge(request()->except('page', 'type'), [
                                    'sort_by' => 'sku',
                                    'sort_order' => request('sort_by') === 'sku' && request('sort_order') === 'asc' ? 'desc' : 'asc',
                                ])) }}">
                                    {{ translate('SKU') }}
                                    @if (request('sort_by') === 'sku')
                                        <i class="las la-sort-amount-{{ request('sort_order') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>{{ translate('Product Name') }}</th>
                            {{-- @if ($type == 'Seller' || $type == 'All')
                                <th data-breakpoints="lg">{{ translate('Added By') }}</th>
                            @endif
                            <th data-breakpoints="sm">{{ translate('Info') }}</th> --}}
                            <th>{{ translate('Category') }}</th>
                            <th data-breakpoints="md">{{ translate('Total Stock') }}</th>
                            <th>{{ translate('Brand') }}</th>
                            <th>{{ translate('Role Prices') }}</th>
                            <th>{{ translate('Group') }}</th>
                            <th>{{ translate('Schedule') }}</th>
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Todays Deal') }}</th>
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Published') }}</th>
                            @if (get_setting('product_approve_by_admin') == 1 && $type == 'Seller')
                                <th data-breakpoints="lg">{{ translate('Approved') }}</th>
                            @endif
                            <th data-breakpoints="xs sm md lg xl">{{ translate('Featured') }}</th>
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
                                            <div>{{ $sku }}</div>
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
