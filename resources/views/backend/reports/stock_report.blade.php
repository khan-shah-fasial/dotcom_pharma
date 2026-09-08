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

        $sheetValue = function ($value) {
            if ($value === null) {
                return '-';
            }

            $text = trim((string) $value);

            return $text === '' ? '-' : $text;
        };

        $sortUrl = function (string $column) use ($sortBy, $sortOrder) {
            return route('stock_report.index', array_merge(request()->query(), [
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

        $sortHeading = function (string $column, string $label) use ($sortUrl, $sortIcon) {
            return '<a href="' . e($sortUrl($column)) . '" class="sort-header-link" data-sort-key="' . e($column) . '">'
                . e(translate($label))
                . ' <i class="' . e($sortIcon($column)) . '"></i></a>';
        };

        $plainHeading = fn (string $label) => '<span class="plain-header-label">' . e(translate($label)) . '</span>';

        $lineValue = fn ($rate, int $qty) => $rate === null || $rate === ''
            ? '-'
            : number_format((float) $rate * $qty, 2, '.', '');

        $gpPercent = function ($rate, $mrp) {
            if ($rate === null || $rate === '' || $mrp === null || $mrp === '' || (float) $mrp <= 0) {
                return '-';
            }

            return number_format((((float) $mrp - (float) $rate) / (float) $mrp) * 100, 2, '.', '');
        };

        $variantLabel = function ($variant) {
            $variant = trim((string) $variant);
            if ($variant === '') {
                return translate('Default');
            }

            $parts = preg_split('/[-_\/]+/', $variant) ?: [];
            $details = [];

            foreach ($parts as $part) {
                $part = trim((string) $part);
                if ($part === '') {
                    continue;
                }

                $attrValue = \App\Models\AttributeValue::where('value', $part)->first();
                if ($attrValue && $attrValue->attribute) {
                    $details[] = '(' . $attrValue->attribute->name . ') - ' . $attrValue->value;
                } else {
                    $details[] = $part;
                }
            }

            return $details ? implode(' / ', $details) : $variant;
        };

        $activeFilterCount = collect([
            $search ?? null,
            $categoryId,
            $groupId ?? null,
            $brandId ?? null,
            $productId,
            $variantId,
            $batchId,
            $sku ?? null,
            $schedule ?? null,
            $origin ?? null,
            $hsn ?? null,
            $publishedStatus ?? null,
            $stockStatus ?? null,
            $expiryStatus ?? null,
        ])->filter(fn ($value) => $value !== null && $value !== '')->count();
    @endphp

    <style>
        .product-detail-sheet { color: #202124; font-size: 11px; min-width: 2850px; table-layout: auto; }
        .product-detail-sheet th,
        .product-detail-sheet td { border-color: #242424 !important; padding: 0 !important; text-align: center; vertical-align: middle !important; }
        .product-detail-sheet thead th { background: #fff; color: #111; font-size: 11px; line-height: 1.25; min-width: 54px; white-space: nowrap; }
        .product-detail-sheet thead th > span { white-space: nowrap; }
        .product-detail-sheet .sort-header-link { color: inherit; display: block; padding: 2px 7px; text-decoration: none; white-space: nowrap; }
        .product-detail-sheet .sort-header-link:hover { background: #f1f5f9; color: #111; }
        .product-detail-sheet .sort-header-link i { margin-left: 4px; }
        .product-detail-sheet .plain-header-label { color: #6c757d; display: block; padding: 2px 7px; white-space: nowrap; }
        .product-detail-sheet thead th.unsupported-col { background: #f8f9fb; }
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
            <p class="text-muted mb-0">{{ translate('Columns follow the Stock Book Master order. Yellow fields can be edited inline. A dash means no value is stored for that field in the system.') }}</p>
        </div>
    </div>

    <div class="card">
        <form id="stock-report-filter-form" action="{{ route('stock_report.index') }}" method="GET">
            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
            <input type="hidden" name="sort_order" value="{{ $sortOrder }}">

            <div class="card-header d-block d-md-flex align-items-center">
                <h5 class="mb-2 mb-md-0 h6">
                    {{ translate('Stock Book') }}
                    <span class="badge badge-soft-secondary ml-1">{{ $reportRows->total() }}</span>
                    @if ($activeFilterCount > 0)
                        <span class="badge badge-info ml-1">{{ translate('Filters applied') }}</span>
                    @endif
                </h5>

                <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center ml-md-auto">
                    <div class="input-group mb-2 mb-sm-0 mr-sm-2" style="min-width: 280px;">
                        <input type="text" class="form-control" name="search" value="{{ $search }}"
                            placeholder="{{ translate('Product, SKU, variant, batch, HSN or origin') }}">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit" title="{{ translate('Search') }}">
                                <i class="las la-search"></i>
                            </button>
                        </div>
                    </div>

                    @if ($activeFilterCount > 0)
                        <a class="btn btn-soft-danger mb-2 mb-sm-0 mr-sm-2" href="{{ route('stock_report.index') }}">
                            <i class="las la-redo-alt"></i> {{ translate('Reset') }}
                        </a>
                    @endif

                    <button type="button" class="btn btn-soft-primary" data-toggle="modal" data-target="#stockReportFilterModal">
                        <i class="las la-filter"></i> {{ translate('Open Filters') }}
                        @if ($activeFilterCount > 0)
                            <span class="badge badge-primary ml-1">{{ $activeFilterCount }}</span>
                        @endif
                    </button>
                </div>
            </div>

            <div class="modal fade" id="stockReportFilterModal" tabindex="-1" role="dialog"
                aria-labelledby="stockReportFilterModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="stockReportFilterModalLabel">{{ translate('Filter Stock Report') }}</h5>
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
                                            <option value="{{ $category->id }}" @selected($categoryId == $category->id)>
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
                                            <option value="{{ $group->id }}" @selected($groupId == $group->id)>
                                                {{ $group->getTranslation('name') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="brand_select">{{ translate('Brand / Company') }}</label>
                                    <select id="brand_select" class="form-control aiz-selectpicker product-classification-filter"
                                        name="brand_id" data-live-search="true">
                                        <option value="">{{ translate('All Brands') }}</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}" @selected($brandId == $brand->id)>
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
                                            <option value="{{ $product->id }}" @selected($productId == $product->id)>
                                                {{ $product->getTranslation('name') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="variant_select">{{ translate('Pack Size / Variant') }}</label>
                                    <select id="variant_select" class="form-control aiz-selectpicker" name="variant_id" data-live-search="true">
                                        <option value="">{{ translate('All Variants') }}</option>
                                        @foreach ($variants as $variant)
                                            <option value="{{ $variant->id }}" @selected($variantId == $variant->id)>
                                                {{ trim((string) $variant->variant) ?: translate('Default') }}
                                                @if (!empty($variant->sku))
                                                    ({{ $variant->sku }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="batch_select">{{ translate('Batch / Lot No') }}</label>
                                    <select id="batch_select" class="form-control aiz-selectpicker" name="batch_id" data-live-search="true">
                                        <option value="">{{ translate('All Batches') }}</option>
                                        @foreach ($batches as $batch)
                                            <option value="{{ $batch->id }}" @selected($batchId == $batch->id)>
                                                {{ trim((string) $batch->batch) ?: '-' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="sku">{{ translate('SKU') }}</label>
                                    <input type="text" class="form-control" id="sku" name="sku" value="{{ $sku }}"
                                        placeholder="{{ translate('SKU') }}">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="origin_select">{{ translate('Origin') }}</label>
                                    <select id="origin_select" class="form-control aiz-selectpicker" name="origin" data-live-search="true">
                                        <option value="">{{ translate('All Origins') }}</option>
                                        @foreach ($origins as $originOption)
                                            <option value="{{ $originOption }}" @selected($origin === $originOption)>
                                                {{ $originOption }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="schedule_select">{{ translate('Schedule') }}</label>
                                    <select id="schedule_select" class="form-control aiz-selectpicker" name="schedule" data-live-search="true">
                                        <option value="">{{ translate('All Schedules') }}</option>
                                        @foreach ($schedules as $scheduleOption)
                                            <option value="{{ $scheduleOption }}" @selected($schedule === $scheduleOption)>
                                                {{ $scheduleOption }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="hsn">{{ translate('HSN / HS Code') }}</label>
                                    <input type="text" class="form-control" id="hsn" name="hsn" value="{{ $hsn }}"
                                        placeholder="{{ translate('HSN or HS Code') }}">
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
                            <a href="{{ route('stock_report.index') }}" class="btn btn-light border">
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
                <table class="table table-bordered product-detail-sheet mb-0">
                    <thead>
                        <tr>
                            <th>
                                {!! $sortHeading('sr_no', 'SR.No') !!}
                                {!! $sortHeading('sku', 'SKU') !!}
                                {!! $sortHeading('origin', 'Origin') !!}
                            </th>
                            <th>
                                {!! $sortHeading('category', 'Category') !!}
                                {!! $sortHeading('group', 'Group') !!}
                                {!! $sortHeading('schedule', 'Schedule') !!}
                            </th>
                            <th>
                                {!! $sortHeading('product_name', 'Product/Brand Name') !!}
                                {!! $sortHeading('composition', 'Composition') !!}
                                {!! $sortHeading('company', 'Company') !!}
                            </th>
                            <th>
                                {!! $sortHeading('pack_size', 'Pack Size') !!}
                                {!! $sortHeading('full_variant', 'Full Variant') !!}
                            </th>
                            <th>
                                {!! $sortHeading('batch', 'Batch / Lot No') !!}
                                {!! $sortHeading('manufacturing_date', 'Mfg.Date') !!}
                                {!! $sortHeading('expiry', 'Exp Date') !!}
                            </th>
                            <th>
                                {!! $sortHeading('qty', 'Qty') !!}
                                {!! $sortHeading('scheme', 'Scheme') !!}
                                {!! $sortHeading('total_qty', 'Total Qty') !!}
                            </th>
                            <th class="unsupported-col">
                                {!! $plainHeading('P.Date') !!}
                                {!! $plainHeading('P.SRNo') !!}
                                {!! $plainHeading('P.PBNo') !!}
                            </th>
                            <th class="unsupported-col">
                                {!! $plainHeading('SPBNo') !!}
                                {!! $plainHeading('Supplier Code') !!}
                                {!! $plainHeading('Supplier Name') !!}
                            </th>
                            <th>
                                {!! $sortHeading('purchase_rate', 'P.Rate') !!}
                                {!! $sortHeading('purchase_value', 'Pur.Value') !!}
                                {!! $plainHeading('Last Purchase Date') !!}
                            </th>
                            <th>
                                {!! $plainHeading('P.TaxCode') !!}
                                {!! $sortHeading('purchase_tax_percent', 'P.Tax%') !!}
                                {!! $sortHeading('purchase_tax_value', 'Tax Value') !!}
                            </th>
                            <th>
                                {!! $sortHeading('pts', 'PTS') !!}
                                {!! $sortHeading('pts_value', 'Value') !!}
                                {!! $sortHeading('pts_gp', 'GP%') !!}
                            </th>
                            <th>
                                {!! $sortHeading('ptr', 'PTR') !!}
                                {!! $sortHeading('ptr_value', 'Value') !!}
                                {!! $sortHeading('ptr_gp', 'GP%') !!}
                            </th>
                            <th>
                                {!! $sortHeading('ptd', 'PTD') !!}
                                {!! $sortHeading('ptd_value', 'Value') !!}
                                {!! $sortHeading('ptd_gp', 'GP%') !!}
                            </th>
                            <th>
                                {!! $sortHeading('gov', 'Govt.') !!}
                                {!! $sortHeading('gov_value', 'Value') !!}
                                {!! $sortHeading('gov_gp', 'GP%') !!}
                            </th>
                            <th>
                                {!! $sortHeading('export', 'Export') !!}
                                {!! $sortHeading('export_value', 'Value') !!}
                                {!! $sortHeading('export_gp', 'GP%') !!}
                            </th>
                            <th>
                                {!! $sortHeading('customer', 'Customer') !!}
                                {!! $sortHeading('customer_value', 'Value') !!}
                                {!! $sortHeading('customer_gp', 'GP%') !!}
                            </th>
                            <th>
                                {!! $sortHeading('mrp', 'MRP') !!}
                                {!! $sortHeading('mrp_value', 'Value') !!}
                                {!! $plainHeading('GP%') !!}
                            </th>
                            <th>
                                {!! $plainHeading('S TaxCode') !!}
                                {!! $sortHeading('sales_tax_percent', 'S Tax %') !!}
                                {!! $sortHeading('sales_tax_value', 'Tax Value') !!}
                            </th>
                            <th>
                                {!! $sortHeading('hsn', 'HSN Code') !!}
                                {!! $sortHeading('hs_code', 'HS Code') !!}
                                {!! $sortHeading('last_update', 'Last Update Date') !!}
                            </th>
                            <th>
                                {!! $plainHeading('Avg.') !!}
                                {!! $sortHeading('average_gp', 'GP%') !!}
                            </th>
                            <th class="packaging-compact">
                                {!! $plainHeading('Piece') !!}
                                {!! $sortHeading('piece_qty', 'QTY') !!}
                                {!! $sortHeading('piece_weight', 'Weight (gm)') !!}
                                {!! $plainHeading('Dimensions (cm)') !!}
                            </th>
                            <th>
                                {!! $plainHeading('Buffer Box / Shrink Pack') !!}
                                {!! $sortHeading('buffer_qty', 'QTY') !!}
                                {!! $sortHeading('buffer_weight', 'Weight (gm)') !!}
                                {!! $plainHeading('Dimensions (cm)') !!}
                            </th>
                            <th>
                                {!! $plainHeading('Buffer Box / Shrink Pack Per Case') !!}
                                {!! $sortHeading('buffer_per_case_qty', 'QTY') !!}
                                {!! $sortHeading('case_weight', 'Weight (gm)') !!}
                                {!! $plainHeading('Dimensions (cm)') !!}
                            </th>
                            <th class="packaging-compact">
                                {!! $plainHeading('Per Case') !!}
                                {!! $sortHeading('per_case_qty', 'QTY') !!}
                                {!! $sortHeading('case_weight', 'Weight (gm)') !!}
                                {!! $plainHeading('Dimensions (cm)') !!}
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
                                $roleValue = fn (string $role) => $rolePrices[$role] ?? null;

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
                                $taxCode = $taxPercent
                                    ? 'GST' . rtrim(rtrim(number_format((float) $taxPercent, 2, '.', ''), '0'), '.')
                                    : null;
                                $purchaseRate = $product?->purchase_price;
                                $hasPurchaseRate = $purchaseRate !== null && $purchaseRate !== '';
                                $mrpPrice = $batch->mrp_price ?? $stock?->mrp_price ?? $product?->mrp_price;
                                $isExpired = $batch->product_exp_date
                                    && strtotime($batch->product_exp_date) < strtotime(now()->toDateString());
                                $contentTabs = json_decode((string) $product?->contents, true);
                                $compositionTab = collect(is_array($contentTabs) ? $contentTabs : [])
                                    ->first(fn ($tab) => str_contains(strtolower((string) ($tab['title'] ?? '')), 'composition'));
                                $composition = trim(strip_tags((string) ($product?->drug_name ?: ($compositionTab['content'] ?? ''))));
                                $customerPrice = $roleValue('customer') ?? $stock?->price;
                                $scheme = (int) ($batch->scheme ?? $stock?->scheme ?? 0);
                                $qty = (int) $batch->qty;
                                $minimumQty = max(1, (int) ($stock?->min_qty ?? 1));
                                $schemeQty = function_exists('calculate_scheme_qty')
                                    ? calculate_scheme_qty($qty, $minimumQty, $scheme)
                                    : 0;
                                $totalQty = $qty + (int) $schemeQty;
                                $taxValue = $taxPercent && $customerPrice !== null && $customerPrice !== ''
                                    ? ((float) $customerPrice * (float) $taxPercent / 100) * $qty
                                    : null;
                                $purchaseTaxValue = $taxPercent && $hasPurchaseRate
                                    ? ((float) $purchaseRate * (float) $taxPercent / 100) * $qty
                                    : null;
                                $rowNumber = $key + 1 + ($reportRows->currentPage() - 1) * $reportRows->perPage();
                                $variant = trim((string) $stock?->variant);
                                $pieceWeight = $stock?->weight ?? $product?->product_weight_vol ?? $product?->weight;
                                $gpValues = collect(['pts', 'ptr', 'ptd', 'gov', 'expo', 'customer'])
                                    ->map(function ($role) use ($roleValue, $customerPrice, $mrpPrice, $gpPercent) {
                                        $rate = $role === 'customer' ? ($roleValue('customer') ?? $customerPrice) : $roleValue($role);
                                        $gp = $gpPercent($rate, $mrpPrice);

                                        return $gp === '-' ? null : (float) $gp;
                                    })
                                    ->filter(fn ($value) => $value !== null);
                                $averageGp = $gpValues->isNotEmpty()
                                    ? number_format($gpValues->avg(), 2, '.', '')
                                    : '-';
                            @endphp
                            <tr data-batch-row="{{ $batch->id }}" data-mrp="{{ $decimal($mrpPrice) }}" data-purchase-rate="{{ $hasPurchaseRate ? $decimal($purchaseRate) : '' }}" data-tax-percent="{{ $taxPercent ?: '' }}">
                                <td class="sheet-lines">
                                    <span>{{ $rowNumber }}</span>
                                    <span>{{ $sheetValue($stock?->sku) }}</span>
                                    <span>{{ $sheetValue($product?->product_origin) }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <span>{{ $categoryNames->isNotEmpty() ? $categoryNames->implode(', ') : '-' }}</span>
                                    <span>{{ $groupNames->isNotEmpty() ? $groupNames->implode(', ') : '-' }}</span>
                                    <span>{{ $sheetValue($product?->schedule) }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <span class="product-name-line">{{ $sheetValue($product?->getTranslation('name')) }}</span>
                                    <span class="text-left-line wrap-line">{{ $composition !== '' ? $composition : '-' }}</span>
                                    <span class="brand-name-line">{{ $sheetValue($product?->brand?->getTranslation('name')) }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <span>{{ $variant !== '' ? $variant : translate('Default') }}</span>
                                    <span class="wrap-line">{{ $variantLabel($variant) }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <input type="text" class="stock-inline-input" data-batch-id="{{ $batch->id }}" data-field="batch" value="{{ $batch->batch }}" title="{{ translate('Batch / Lot No') }}" required>
                                    <input type="month" class="stock-inline-input" data-batch-id="{{ $batch->id }}" data-field="manufacturing_date" value="{{ $monthValue($batch->manufacturing_date) }}" title="{{ translate('Mfg.Date') }}">
                                    <input type="month" class="stock-inline-input {{ $isExpired ? 'text-danger' : '' }}" data-batch-id="{{ $batch->id }}" data-field="product_exp_date" value="{{ $monthValue($batch->product_exp_date) }}" title="{{ translate('Exp Date') }}">
                                </td>
                                <td class="sheet-lines">
                                    <input type="number" min="0" step="1" lang="en" class="stock-inline-input js-stock-qty" data-batch-id="{{ $batch->id }}" data-field="qty" value="{{ $qty }}" title="{{ translate('Qty') }}">
                                    <input type="number" min="0" step="1" lang="en" class="stock-inline-input js-stock-scheme" data-batch-id="{{ $batch->id }}" data-field="scheme" value="{{ $scheme }}" title="{{ translate('Scheme') }}">
                                    <span class="js-stock-total-qty" data-scheme="{{ $scheme }}" data-min-qty="{{ $minimumQty }}">{{ $totalQty }}</span>
                                </td>
                                <td class="sheet-lines unsupported-col">
                                    <span>-</span>
                                    <span>-</span>
                                    <span>-</span>
                                </td>
                                <td class="sheet-lines unsupported-col">
                                    <span>-</span>
                                    <span>-</span>
                                    <span>-</span>
                                </td>
                                <td class="sheet-lines">
                                    <span class="js-purchase-rate">{{ $hasPurchaseRate ? $decimal($purchaseRate) : '-' }}</span>
                                    <span class="js-purchase-value">{{ $hasPurchaseRate ? $lineValue($purchaseRate, $qty) : '-' }}</span>
                                    <span>-</span>
                                </td>
                                <td class="sheet-lines">
                                    <span>{{ $sheetValue($taxCode) }}</span>
                                    <span>{{ $taxPercent ? $decimal($taxPercent) : '-' }}</span>
                                    <span class="js-purchase-tax-value">{{ $purchaseTaxValue === null ? '-' : $decimal($purchaseTaxValue) }}</span>
                                </td>
                                @foreach (['pts' => 'pts', 'ptr' => 'ptr', 'ptd' => 'ptd', 'gov' => 'gov', 'expo' => 'export'] as $role => $prefix)
                                    @php($rate = $roleValue($role))
                                    <td class="sheet-lines">
                                        <input type="number" min="0" step="0.01" lang="en"
                                            class="stock-inline-input {{ $role === 'pts' ? 'price-pts' : ($role === 'ptr' ? 'price-ptr' : ($role === 'ptd' ? 'price-ptd' : '')) }}"
                                            data-batch-id="{{ $batch->id }}" data-field="{{ $role }}" data-role="{{ $prefix }}" value="{{ $decimal($rate) }}" title="{{ strtoupper($role) }}">
                                        <span class="js-value-{{ $prefix }}">{{ $lineValue($rate, $qty) }}</span>
                                        <span class="js-gp-{{ $prefix }}">{{ $gpPercent($rate, $mrpPrice) }}</span>
                                    </td>
                                @endforeach
                                <td class="sheet-lines">
                                    <input type="number" min="0" step="0.01" lang="en" class="stock-inline-input" data-batch-id="{{ $batch->id }}" data-field="customer" data-role="customer" value="{{ $decimal($customerPrice) }}" title="{{ translate('Customer') }}">
                                    <span class="js-value-customer">{{ $lineValue($customerPrice, $qty) }}</span>
                                    <span class="js-gp-customer">{{ $gpPercent($customerPrice, $mrpPrice) }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <input type="number" min="0" step="0.01" lang="en" class="stock-inline-input" data-batch-id="{{ $batch->id }}" data-field="mrp_price" value="{{ $decimal($mrpPrice) }}" title="{{ translate('MRP') }}">
                                    <span class="js-value-mrp">{{ $lineValue($mrpPrice, $qty) }}</span>
                                    <span class="js-gp-mrp">{{ $hasPurchaseRate ? $gpPercent($purchaseRate, $mrpPrice) : '-' }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <span>{{ $sheetValue($taxCode) }}</span>
                                    <span>{{ $taxPercent ? $decimal($taxPercent) : '-' }}</span>
                                    <span class="js-tax-value">{{ $taxValue === null ? '-' : $decimal($taxValue) }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <span>{{ $sheetValue($product?->product_hsn) }}</span>
                                    <span>{{ $sheetValue($product?->product_hs) }}</span>
                                    <span class="upload-date">{{ $formatUploadDate($product?->updated_at) }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <span>{{ translate('Avg.') }}</span>
                                    <span class="js-avg-gp">{{ $averageGp }}</span>
                                </td>
                                <td class="sheet-lines packaging-compact">
                                    <span>{{ translate('Piece') }}</span>
                                    <span>{{ $sheetValue($stock?->qty_per_piece) }}</span>
                                    <span>{{ $sheetValue($pieceWeight) }}</span>
                                    <span class="wrap-line">{{ $dimensions($stock?->length, $stock?->width, $stock?->height) }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <span>{{ translate('Buffer') }}</span>
                                    <span>{{ $sheetValue($stock?->qty_per_buffer_box) }}</span>
                                    <span>{{ $sheetValue($stock?->weight_buffer_box) }}</span>
                                    <span class="wrap-line">{{ $dimensions($stock?->buffer_length, $stock?->buffer_width, $stock?->buffer_height) }}</span>
                                </td>
                                <td class="sheet-lines">
                                    <span>{{ translate('Per Case') }}</span>
                                    <span>{{ $sheetValue($stock?->count) }}</span>
                                    <span>{{ $sheetValue($stock?->weight_case) }}</span>
                                    <span class="wrap-line">{{ $dimensions($stock?->case_length, $stock?->case_width, $stock?->case_height) }}</span>
                                </td>
                                <td class="sheet-lines packaging-compact">
                                    <span>{{ translate('Case') }}</span>
                                    <span>{{ $sheetValue($stock?->total_qty_per_case) }}</span>
                                    <span>{{ $sheetValue($stock?->weight_case) }}</span>
                                    <span class="wrap-line">{{ $dimensions($stock?->case_length, $stock?->case_width, $stock?->case_height) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="24" class="text-center py-4">{{ translate('No stock data found') }}</td>
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
                url: '{{ route("stock_report.filter_options") }}',
                type: 'GET',
                data: {
                    category_id: $('#category_select').val(),
                    group_id: $('#group_select').val(),
                    brand_id: $('#brand_select').val(),
                    product_id: selectedProductId,
                    variant_id: selectedVariantId
                },
                success: function (response) {
                    updateSelect(productSelect, '{{ translate("All Products") }}', response.products, selectedProductId);
                    updateSelect(variantSelect, '{{ translate("All Variants") }}', response.variants, selectedVariantId);
                    updateSelect(batchSelect, '{{ translate("All Batches") }}', response.batches, selectedBatchId);
                },
                error: function () {
                    if (typeof AIZ !== 'undefined' && AIZ.plugins && AIZ.plugins.notify) {
                        AIZ.plugins.notify('danger', '{{ translate("Unable to load filter options") }}');
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

        $('#stockReportFilterModal').on('shown.bs.modal', function () {
            $(this).find('.aiz-selectpicker').selectpicker('refresh');
        });

        @if($productId)
            loadFilterOptions('{{ $productId }}', '{{ $variantId }}', '{{ $batchId }}');
        @endif

        function notify(type, message) {
            if (typeof AIZ !== 'undefined' && AIZ.plugins && AIZ.plugins.notify) {
                AIZ.plugins.notify(type, message);
            }
        }

        function formatAmount(value) {
            if (value === null || value === undefined || value === '' || !isFinite(Number(value))) {
                return '-';
            }

            return Number(value).toFixed(2);
        }

        function gpFromMrp(rate, mrp) {
            const rateNum = parseFloat(rate);
            const mrpNum = parseFloat(mrp);
            if (!isFinite(rateNum) || !isFinite(mrpNum) || mrpNum <= 0) {
                return '-';
            }

            return (((mrpNum - rateNum) / mrpNum) * 100).toFixed(2);
        }

        function refreshDerivedCells(row) {
            const qty = parseInt(row.find('.js-stock-qty').val(), 10) || 0;
            const schemeInput = row.find('.js-stock-scheme');
            const scheme = parseInt(schemeInput.val(), 10) || 0;
            const minimumQty = Math.max(1, parseInt(row.find('.js-stock-total-qty').data('min-qty'), 10) || 1);
            const mrp = row.find('input[data-field="mrp_price"]').val();
            row.attr('data-mrp', mrp);
            row.find('.js-stock-total-qty').data('scheme', scheme).text(qty + (Math.floor(qty / minimumQty) * scheme));
            row.find('.js-value-mrp').text(formatAmount(parseFloat(mrp) * qty));

            const purchaseRate = row.attr('data-purchase-rate');
            const taxPercent = parseFloat(row.attr('data-tax-percent'));
            if (purchaseRate !== undefined && purchaseRate !== '') {
                row.find('.js-purchase-value').text(formatAmount(parseFloat(purchaseRate) * qty));
                row.find('.js-gp-mrp').text(gpFromMrp(purchaseRate, mrp));
                if (isFinite(taxPercent) && taxPercent > 0) {
                    row.find('.js-purchase-tax-value').text(formatAmount((parseFloat(purchaseRate) * taxPercent / 100) * qty));
                }
            } else {
                row.find('.js-gp-mrp').text('-');
            }

            const customerRate = row.find('input[data-field="customer"]').val();
            if (isFinite(taxPercent) && taxPercent > 0 && customerRate !== '' && isFinite(parseFloat(customerRate))) {
                row.find('.js-tax-value').text(formatAmount((parseFloat(customerRate) * taxPercent / 100) * qty));
            }

            const gps = [];
            ['pts', 'ptr', 'ptd', 'gov', 'export', 'customer'].forEach(function (role) {
                const field = role === 'export' ? 'expo' : role;
                const rate = row.find('input[data-field="' + field + '"]').val();
                row.find('.js-value-' + role).text(formatAmount(parseFloat(rate) * qty));
                const gp = gpFromMrp(rate, mrp);
                row.find('.js-gp-' + role).text(gp);
                if (gp !== '-') {
                    gps.push(parseFloat(gp));
                }
            });

            row.find('.js-avg-gp').text(gps.length
                ? (gps.reduce((sum, value) => sum + value, 0) / gps.length).toFixed(2)
                : '-');
        }

        $('.stock-inline-input').each(function () {
            $(this).data('original', $(this).val());
        });

        $(document).on('focus', '.stock-inline-input', function () {
            $(this).data('original', $(this).val());
        });

        $(document).on('change', '.stock-inline-input', function () {
            const input = $(this);
            const row = input.closest('tr');
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
                    if (field === 'scheme' && response.scheme !== undefined) {
                        input.val(response.scheme);
                        row.find('.js-stock-total-qty').data('scheme', response.scheme);
                    }
                    refreshDerivedCells(row);
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
