@extends('backend.layouts.app')

@section('content')
    <style>
        .party-consolidated-sheet {
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }
        .party-consolidated-summary {
            border: 1px solid #000;
            border-bottom: 0;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.25;
            padding: 3px 6px;
        }
        .party-consolidated-summary span {
            display: inline-block;
            margin-right: 18px;
        }
        .party-consolidated-table {
            min-width: 1580px;
            table-layout: fixed;
        }
        .party-consolidated-table th,
        .party-consolidated-table td {
            border-color: #000 !important;
            padding: 3px 5px !important;
            vertical-align: middle !important;
        }
        .party-consolidated-table th {
            background: #fff;
            color: #000;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
        }
        .party-consolidated-table th a {
            color: #007bff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .party-consolidated-table .sort-icon {
            color: #007bff;
            font-size: 13px;
            margin-left: 3px;
        }
        .party-consolidated-table th a:hover,
        .party-consolidated-table th a:hover .sort-icon {
            color: #0056b3;
            text-decoration: underline;
        }
        .party-consolidated-table td {
            background: #fff;
            color: #000;
            line-height: 1.25;
        }
        .party-consolidated-table .total-label {
            font-weight: 700;
            text-align: right;
        }
        .party-consolidated-table .total-amount {
            background: #ffff00;
            font-weight: 700;
        }
        .party-consolidated-table .product-cell {
            overflow-wrap: anywhere;
        }
        .party-consolidated-table .compact-heading {
            white-space: normal;
            line-height: 1.15;
        }
        .party-consolidated-table.compact-report-table {
            min-width: 1960px;
            table-layout: auto;
        }
        .party-consolidated-table.compact-report-table th,
        .party-consolidated-table.compact-report-table td {
            text-align: right;
            white-space: nowrap;
        }
        .party-consolidated-table.compact-report-table th.compact-left,
        .party-consolidated-table.compact-report-table td.compact-left {
            text-align: left;
        }
        .party-consolidated-table.compact-report-table .compact-heading,
        .party-consolidated-table.compact-report-table .compact-heading a {
            white-space: nowrap;
        }
        .party-consolidated-table.compact-report-table .product-cell {
            min-width: 240px;
            max-width: 360px;
            white-space: normal;
            overflow-wrap: break-word;
        }
        .party-consolidated-table.compact-report-table .pack-cell {
            min-width: 90px;
            max-width: 140px;
            white-space: normal;
        }
        @media print {
            .aiz-sidebar-wrap,
            .aiz-topbar,
            .aiz-main-content > .border-top,
            .d-print-none {
                display: none !important;
            }
            .aiz-content-wrapper,
            .aiz-main-content,
            .px-15px,
            .px-lg-25px {
                margin: 0 !important;
                padding: 0 !important;
            }
            .card {
                border: 0 !important;
                box-shadow: none !important;
            }
            .card-body {
                padding: 0 !important;
            }
            body {
                background: #fff !important;
            }
        }
    </style>

    @php
        $numberValue = function ($value) {
            if ($value === null || $value === '') {
                return 0;
            }

            return (float) str_replace(',', '', (string) $value);
        };
        $formatQty = fn ($value) => number_format($numberValue($value), 0, '.', '');
        $formatAmount = fn ($value) => number_format($numberValue($value), 2, '.', '');
        $formatDate = function ($value) {
            $value = trim((string) $value);
            if ($value === '') {
                return '';
            }

            foreach (['Y-m-d H:i:s', 'Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
                $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->format('d-m-Y');
                }
            }

            return $value;
        };
        $formatFilterDate = function ($value) {
            $value = trim((string) $value);
            if ($value === '') {
                return '';
            }

            foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
                $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->format('d-m-Y');
                }
            }

            return $value;
        };
        $dateRangeValue = function ($from, $to) use ($formatFilterDate) {
            if (! $from || ! $to) {
                return '';
            }

            return $formatFilterDate($from) . ' to ' . $formatFilterDate($to);
        };
        $billDateFromValue = $filterBillDateFrom ?? (request('bill_date_from') ?: request('order_date_from'));
        $billDateToValue = $filterBillDateTo ?? (request('bill_date_to') ?: request('order_date_to'));
        $activeTab = $activeTab ?? 'detailed';
        $compactSortKeys = $compactSortKeys ?? [];
        $detailedSortKeys = [
            'sr_no', 'bill_date', 'bill_series', 'bill_number', 'product_sku', 'product_name',
            'packing', 'quantity', 'sale_rate', 'gst_amount', 'mrp_rate', 'gross_amount',
            'pts', 'ptr', 'ptd', 'govt', 'export', 'customer_price', 'current_mrp',
        ];
        $tabLink = function (string $tab) use ($account, $detailedSortKeys, $compactSortKeys) {
            $params = request()->except('page');
            $params['account'] = $account;
            $params['tab'] = $tab;
            $sort = (string) ($params['sort_by'] ?? '');
            $allowed = $tab === 'compact' ? $compactSortKeys : $detailedSortKeys;
            if ($sort !== '' && ! in_array($sort, $allowed, true)) {
                unset($params['sort_by'], $params['sort_dir']);
            }

            return route('admin.purchase_history.consolidated', $params);
        };
        $sortLink = function (string $column) use ($sortBy, $sortDir, $activeTab) {
            $nextDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';

            return route('admin.purchase_history.consolidated', array_merge(request()->except('page'), [
                'tab' => $activeTab,
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
        $sortHeadingHtml = function (string $column, string $html) use ($sortLink, $sortIcon) {
            return '<a href="'.e($sortLink($column)).'">'.$html.$sortIcon($column).'</a>';
        };
        $filtersApplied = collect([
            $billDateFromValue,
            $billDateToValue,
            request('search'),
            request('serial_number'),
            request('order_number'),
            request('invoice_number'),
            request('product_sku'),
            request('product_name'),
            request('sales_man_name'),
            request('sales_man_code'),
            request('lr_number'),
            request('party_name'),
            request('user_name'),
            request('transport'),
            request('pincode'),
            request('country_id'),
            request('state_id'),
            request('city_id'),
            request('district'),
            request('post'),
            request('expiry_date_from'),
            request('expiry_date_to'),
        ])->contains(fn ($value) => $value !== null && $value !== '');
        $partyName = $customer?->company_name ?: $account;
        $mobileNumbers = collect([
            $customer?->prim_mobile_no_business,
            $customer?->prim_mobile_no,
        ])->filter(fn ($value) => filled($value))->unique()->values();
        $whatsAppNumbers = collect([
            $customer?->prim_whats_app_no_business,
            $customer?->prim_whats_app_no,
        ])->filter(fn ($value) => filled($value))->unique()->values();
        $totalGross = $reportRows->sum(fn ($row) => $numberValue($row->gross_amount));
        $currentPriceColumns = [
            'PTS' => ['label' => 'PTS', 'sort' => 'pts'],
            'PTR' => ['label' => 'PTR', 'sort' => 'ptr'],
            'PTD' => ['label' => 'PTD', 'sort' => 'ptd'],
            'Govt.' => ['label' => 'Govt.', 'sort' => 'govt'],
            'Exp' => ['label' => 'Exp', 'sort' => 'export'],
            'Customer' => ['label' => 'Customer', 'sort' => 'customer_price'],
            'M.R.P' => ['label' => 'M.R.P', 'sort' => 'current_mrp'],
        ];
        $detailedSortOptions = [
            'sr_no' => 'Sr no.', 'bill_date' => 'Bill Date', 'bill_series' => 'Bill Series',
            'bill_number' => 'Bill No', 'product_sku' => 'SKU', 'product_name' => 'Product Name',
            'packing' => 'Pack', 'quantity' => 'Qty', 'sale_rate' => 'S.Rate', 'gst_amount' => 'Tax',
            'mrp_rate' => 'M R P', 'gross_amount' => 'Gross amount', 'pts' => 'PTS', 'ptr' => 'PTR',
            'ptd' => 'PTD', 'govt' => 'Govt.', 'export' => 'Exp', 'customer_price' => 'Customer',
            'current_mrp' => 'Current M.R.P',
        ];
        $compactSortOptions = [
            'product_sku' => 'SKU', 'product_name' => 'Product Name', 'packing' => 'Pack',
            'quantity' => 'Total QTY', 'sale_rate' => 'S.Rate Range', 'gst_amount' => 'Total Tax',
            'mrp_rate' => 'M R P Range', 'gross_amount' => 'Total Gross amount',
            'bill_count' => 'Total No Of Bills', 'bill_year' => 'Bill Date',
            'pts' => 'PTS', 'ptr' => 'PTR', 'ptd' => 'PTD', 'govt' => 'Govt.', 'export' => 'Exg',
            'customer_price' => 'Customer', 'current_mrp' => 'MRP',
        ];
        $sortOptions = $activeTab === 'compact' ? $compactSortOptions : $detailedSortOptions;
        $compactPriceColumns = [
            'PTS' => ['label' => 'PTS', 'sort' => 'pts'],
            'PTR' => ['label' => 'PTR', 'sort' => 'ptr'],
            'PTD' => ['label' => 'PTD', 'sort' => 'ptd'],
            'Govt.' => ['label' => 'Govt.', 'sort' => 'govt'],
            'Exp' => ['label' => 'Exg', 'sort' => 'export'],
            'Customer' => ['label' => 'Customer', 'sort' => 'customer_price'],
            'M.R.P' => ['label' => 'MRP', 'sort' => 'current_mrp'],
        ];
    @endphp

    <div class="aiz-titlebar text-left mt-2 mb-3 d-print-none">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1">{{ translate('Consolidated Purchase History') }}</h1>
                <div class="text-muted">
                    {{ translate('Account') }}: {{ $account }}
                    <span class="mx-2">|</span>{{ translate('Party Name') }}: {{ $partyName }}
                    @if($mobileNumbers->isNotEmpty())
                        <span class="mx-2">|</span>{{ translate('Mobile') }}: {{ $mobileNumbers->implode(' / ') }}
                    @endif
                    @if($whatsAppNumbers->isNotEmpty())
                        <span class="mx-2">|</span>{{ translate('Whatsup Number') }}: {{ $whatsAppNumbers->implode(' / ') }}
                    @endif
                </div>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="{{ route('admin.purchase_history.index', request()->except(['page'])) }}"
                   class="btn btn-outline-secondary mr-2">
                    {{ translate('Back to Purchase History') }}
                </a>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="las la-print mr-1"></i>{{ translate('Print') }}
                </button>
            </div>
        </div>
    </div>

    <div class="card d-print-none mb-3">
        <form action="{{ route('admin.purchase_history.consolidated') }}" method="GET">
            <input type="hidden" name="account" value="{{ $account }}">
            <input type="hidden" name="tab" value="{{ $activeTab }}">

            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <div class="mb-2">
                    <h5 class="mb-0 h6">{{ translate('Filters') }}</h5>
                    @if($filtersApplied)
                        <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap align-items-center">
                    <div class="d-flex align-items-center mr-3 mb-2">
                        <label class="mb-0 mr-2 text-nowrap" for="report_years">{{ translate('Report For') }}</label>
                        <select class="form-control" id="report_years" name="report_years" style="width: 190px;">
                            <option value="">{{ translate('All') }}</option>
                            @foreach([10, 8, 5, 3, 2, 1] as $reportYearOption)
                                <option value="{{ $reportYearOption }}" @if((int) ($reportYears ?? request('report_years')) === $reportYearOption) selected @endif>
                                    {{ translate('Last') }} {{ $reportYearOption }} {{ translate($reportYearOption === 1 ? 'Year' : 'Years') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal"
                            data-target="#consolidatedPurchaseHistoryFilterModal">
                        {{ translate('Open Filters') }}
                    </button>
                    <a href="{{ route('admin.purchase_history.consolidated', ['account' => $account, 'tab' => $activeTab]) }}"
                       class="btn btn-danger mb-2">
                        {{ translate('Reset') }}
                    </a>
                </div>
            </div>

            <div class="modal fade" id="consolidatedPurchaseHistoryFilterModal" tabindex="-1" role="dialog"
                 aria-labelledby="consolidatedPurchaseHistoryFilterModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="consolidatedPurchaseHistoryFilterModalLabel">
                                {{ translate('Filter Consolidated Purchase History') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row gutters-5">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="search">{{ translate('Search') }}</label>
                                    <input type="text" class="form-control" id="search" name="search"
                                           value="{{ request('search') }}" placeholder="{{ translate('Search') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="party_name">{{ translate('Party Name') }}</label>
                                    <input type="text" class="form-control" id="party_name" name="party_name"
                                           value="{{ request('party_name') }}" placeholder="{{ translate('Party Name') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="user_name">{{ translate('Name') }}</label>
                                    <input type="text" class="form-control" id="user_name" name="user_name"
                                           value="{{ request('user_name') }}" placeholder="{{ translate('User Name') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="serial_number">{{ translate('Serial No') }}</label>
                                    <input type="text" class="form-control" id="serial_number" name="serial_number"
                                           value="{{ request('serial_number') }}" placeholder="{{ translate('Serial No') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="order_number">{{ translate('Order Number') }}</label>
                                    <input type="text" class="form-control" id="order_number" name="order_number"
                                           value="{{ request('order_number') }}" placeholder="{{ translate('Order Number') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="invoice_number">{{ translate('Bill Number') }}</label>
                                    <input type="text" class="form-control" id="invoice_number" name="invoice_number"
                                           value="{{ request('invoice_number') }}" placeholder="{{ translate('Bill Number') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="product_sku">{{ translate('SKU') }}</label>
                                    <input type="text" class="form-control" id="product_sku" name="product_sku"
                                           value="{{ request('product_sku') }}" placeholder="{{ translate('Enter SKU') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="product_name">{{ translate('Product') }}</label>
                                    <input type="text" class="form-control" id="product_name" name="product_name"
                                           value="{{ request('product_name') }}" placeholder="{{ translate('Product') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="sales_man_name">{{ translate('Salesman Name') }}</label>
                                    <input type="text" class="form-control" id="sales_man_name" name="sales_man_name"
                                           value="{{ request('sales_man_name') }}" placeholder="{{ translate('Salesman') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="sales_man_code">{{ translate('Sales Man Code') }}</label>
                                    <input type="text" class="form-control" id="sales_man_code" name="sales_man_code"
                                           value="{{ request('sales_man_code') }}" placeholder="{{ translate('Sales Man Code') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="lr_number">{{ translate('L.R.No') }}</label>
                                    <input type="text" class="form-control" id="lr_number" name="lr_number"
                                           value="{{ request('lr_number') }}" placeholder="{{ translate('L.R.No') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="transport">{{ translate('Transport') }}</label>
                                    <input type="text" class="form-control" id="transport" name="transport"
                                           value="{{ request('transport') }}" placeholder="{{ translate('Transport') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="pincode">{{ translate('Pincode') }}</label>
                                    <input type="text" inputmode="numeric" maxlength="10" class="form-control"
                                           id="pincode" name="pincode" value="{{ request('pincode') }}"
                                           placeholder="{{ translate('Pincode') }}" autocomplete="off">
                                    <small class="text-muted" id="pincode-status"></small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="country_id">{{ translate('Country') }}</label>
                                    <select class="form-control aiz-selectpicker" id="country_id" name="country_id"
                                            data-live-search="true">
                                        <option value="">{{ translate('All') }}</option>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}"
                                                {{ (string) request('country_id') === (string) $country->id ? 'selected' : '' }}>
                                                {{ $country->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="state_id">{{ translate('State') }}</label>
                                    <select class="form-control aiz-selectpicker" id="state_id" name="state_id"
                                            data-live-search="true">
                                        <option value="">{{ translate('All') }}</option>
                                        @foreach($states as $state)
                                            <option value="{{ $state->id }}"
                                                {{ (string) request('state_id') === (string) $state->id ? 'selected' : '' }}>
                                                {{ $state->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="city_id">{{ translate('City') }}</label>
                                    <select class="form-control aiz-selectpicker" id="city_id" name="city_id"
                                            data-live-search="true">
                                        <option value="">{{ translate('All') }}</option>
                                        @foreach($cities as $city)
                                            <option value="{{ $city->id }}"
                                                {{ (string) request('city_id') === (string) $city->id ? 'selected' : '' }}>
                                                {{ $city->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="district">{{ translate('District') }}</label>
                                    <select class="form-control aiz-selectpicker" id="district" name="district"
                                            data-live-search="true" data-selected="{{ request('district') }}">
                                        <option value="">{{ translate('All') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="post">{{ translate('Post') }}</label>
                                    <select class="form-control aiz-selectpicker" id="post" name="post"
                                            data-live-search="true" data-selected="{{ request('post') }}">
                                        <option value="">{{ translate('All') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="bill_date_range">{{ translate('Date Range') }}</label>
                                    <input type="text" class="form-control aiz-date-range" id="bill_date_range"
                                           name="bill_date_range"
                                           value="{{ $dateRangeValue($billDateFromValue, $billDateToValue) }}"
                                           data-time-picker="false" data-format="DD-MM-YYYY"
                                           data-from-field="#bill_date_from" data-to-field="#bill_date_to"
                                           placeholder="{{ translate('DD-MM-YYYY to DD-MM-YYYY') }}">
                                    <input type="hidden" name="bill_date_from" id="bill_date_from" value="{{ $billDateFromValue }}">
                                    <input type="hidden" name="bill_date_to" id="bill_date_to" value="{{ $billDateToValue }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="expiry_date_range">{{ translate('Expiry Date') }}</label>
                                    <input type="text" class="form-control aiz-date-range" id="expiry_date_range"
                                           name="expiry_date_range"
                                           value="{{ $dateRangeValue(request('expiry_date_from'), request('expiry_date_to')) }}"
                                           data-time-picker="false" data-format="DD-MM-YYYY"
                                           data-from-field="#expiry_date_from" data-to-field="#expiry_date_to"
                                           placeholder="{{ translate('DD-MM-YYYY to DD-MM-YYYY') }}">
                                    <input type="hidden" name="expiry_date_from" id="expiry_date_from" value="{{ request('expiry_date_from') }}">
                                    <input type="hidden" name="expiry_date_to" id="expiry_date_to" value="{{ request('expiry_date_to') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="sort_by">{{ translate('Sort By') }}</label>
                                    <select class="form-control aiz-selectpicker" id="sort_by" name="sort_by">
                                        @foreach($sortOptions as $value => $label)
                                            <option value="{{ $value }}" @if($sortBy === $value) selected @endif>{{ translate($label) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="sort_dir">{{ translate('Direction') }}</label>
                                    <select class="form-control aiz-selectpicker" id="sort_dir" name="sort_dir">
                                        <option value="asc" @if($sortDir === 'asc') selected @endif>{{ translate('Ascending') }}</option>
                                        <option value="desc" @if($sortDir === 'desc') selected @endif>{{ translate('Descending') }}</option>
                                    </select>
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
    </div>

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs d-print-none mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'detailed' ? 'active' : '' }}" href="{{ $tabLink('detailed') }}">
                        {{ translate('Detailed Report') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'compact' ? 'active' : '' }}" href="{{ $tabLink('compact') }}">
                        {{ translate('Compact Report') }}
                    </a>
                </li>
            </ul>
            <div class="party-consolidated-sheet {{ $activeTab === 'detailed' ? '' : 'd-none' }}">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 party-consolidated-table">
                        <thead>
                        <tr>
                            <th style="width: 45px">{!! $sortHeading('sr_no', translate('Sr no.')) !!}</th>
                            <th style="width: 90px">{!! $sortHeading('bill_date', translate('Bill Date')) !!}</th>
                            <th style="width: 80px">{!! $sortHeading('bill_series', translate('Bill Series')) !!}</th>
                            <th style="width: 70px">{!! $sortHeading('bill_number', translate('Bill No')) !!}</th>
                            <th style="width: 95px">{!! $sortHeading('product_sku', translate('SKU')) !!}</th>
                            <th>{!! $sortHeading('product_name', translate('Product Name')) !!}</th>
                            <th style="width: 70px">{!! $sortHeading('packing', translate('Pack')) !!}</th>
                            <th style="width: 65px">{!! $sortHeading('quantity', translate('Qty')) !!}</th>
                            <th style="width: 80px">{!! $sortHeading('sale_rate', translate('S.Rate')) !!}</th>
                            <th style="width: 65px">{!! $sortHeading('gst_amount', translate('Tax')) !!}</th>
                            <th style="width: 80px">{!! $sortHeading('mrp_rate', translate('M R P')) !!}</th>
                            <th style="width: 110px">{!! $sortHeading('gross_amount', translate('Gross amount')) !!}</th>
                            @foreach($currentPriceColumns as $priceColumn)
                                <th style="width: 80px">{!! $sortHeading($priceColumn['sort'], translate($priceColumn['label'])) !!}</th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($reportRows as $row)
                            @php
                                $productName = $row->product_name ?: $row->product_sku;
                                if (filled($row->product_variant) && ! str_contains((string) $productName, (string) $row->product_variant)) {
                                    $productName = trim($productName . ' ' . $row->product_variant);
                                }

                                $currentSkuPrice = $currentPriceMap[$row->product_sku] ?? null;
                                $currentPriceLines = [];
                                if ($currentSkuPrice) {
                                    $batchNumber = trim((string) ($row->batch_number ?? ''));
                                    $usesSingleBatch = (int) ($row->batch_count ?? 0) === 1;
                                    $currentPriceLines = ($usesSingleBatch && $batchNumber !== '')
                                        ? ($currentSkuPrice['batches'][$batchNumber] ?? $currentSkuPrice['default'])
                                        : $currentSkuPrice['default'];
                                }
                                $currentPriceValues = collect($currentPriceLines)
                                    ->mapWithKeys(fn ($priceLine) => [(string) ($priceLine['label'] ?? '') => $priceLine['value'] ?? '-']);
                            @endphp
                            <tr>
                                <td class="text-right">{{ $loop->iteration }}</td>
                                <td>{{ $formatDate($row->bill_date) }}</td>
                                <td>{{ $row->invoice_series }}</td>
                                <td>{{ $row->invoice_number }}</td>
                                <td>{{ $row->product_sku }}</td>
                                <td class="product-cell">{{ $productName }}</td>
                                <td>{{ $row->packing }}</td>
                                <td class="text-right">{{ $formatQty($row->quantity) }}</td>
                                <td class="text-right">{{ $formatAmount($row->sale_rate) }}</td>
                                <td class="text-center">{{ $formatAmount($row->gst_amount) }}</td>
                                <td class="text-right">{{ $formatAmount($row->mrp_rate) }}</td>
                                <td class="text-right">{{ $formatAmount($row->gross_amount) }}</td>
                                @foreach($currentPriceColumns as $priceLabel => $priceColumn)
                                    <td class="text-right">{{ $currentPriceValues->get($priceLabel, '-') }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="19" class="text-center">{{ translate('No records found') }}</td>
                            </tr>
                        @endforelse

                        @if($reportRows->isNotEmpty())
                            <tr>
                                <td colspan="11" class="total-label">{{ translate('Total') }}</td>
                                <td class="text-right total-amount">{{ $formatAmount($totalGross) }}</td>
                                <td colspan="7"></td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="party-consolidated-sheet {{ $activeTab === 'compact' ? '' : 'd-none' }}">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 party-consolidated-table compact-report-table">
                        <colgroup>
                            <col style="width: 56px">
                            <col style="width: 210px">
                            <col style="width: 180px">
                            <col style="width: 78px">
                            <col style="width: 110px">
                            <col style="width: 280px">
                            <col style="width: 110px">
                            <col style="width: 72px">
                            <col style="width: 130px">
                            <col style="width: 84px">
                            <col style="width: 140px">
                            <col style="width: 110px">
                            <col style="width: 72px">
                            <col style="width: 72px">
                            <col style="width: 72px">
                            <col style="width: 72px">
                            <col style="width: 72px">
                            <col style="width: 84px">
                            <col style="width: 72px">
                        </colgroup>
                        <thead>
                        <tr>
                            <th class="compact-heading">{{ translate('Sr No.') }}</th>
                            <th class="compact-heading">{!! $sortHeading('bill_year', translate('Bill Date')) !!}</th>
                            <th class="compact-heading">{{ translate('Bill Series') }}<br>{{ translate('From-To') }}</th>
                            <th class="compact-heading">{!! $sortHeadingHtml('bill_count', e(translate('Total No')).'<br>'.e(translate('Of Bills'))) !!}</th>
                            <th class="compact-left">{!! $sortHeading('product_sku', translate('SKU')) !!}</th>
                            <th class="compact-left">{!! $sortHeading('product_name', translate('Product Name')) !!}</th>
                            <th>{!! $sortHeading('packing', translate('Pack')) !!}</th>
                            <th class="compact-heading">{!! $sortHeadingHtml('quantity', e(translate('Total')).'<br>'.e(translate('QTY'))) !!}</th>
                            <th class="compact-heading">{!! $sortHeadingHtml('sale_rate', e(translate('S.Rate')).'<br>'.e(translate('Range'))) !!}</th>
                            <th class="compact-heading">{!! $sortHeadingHtml('gst_amount', e(translate('Total')).'<br>'.e(translate('Tax'))) !!}</th>
                            <th class="compact-heading">{!! $sortHeadingHtml('mrp_rate', e(translate('M R P')).'<br>'.e(translate('Range'))) !!}</th>
                            <th class="compact-heading">{!! $sortHeadingHtml('gross_amount', e(translate('Total')).'<br>'.e(translate('Gross amount'))) !!}</th>
                            @foreach($compactPriceColumns as $priceColumn)
                                <th style="width: 80px">{!! $sortHeading($priceColumn['sort'], translate($priceColumn['label'])) !!}</th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($compactRows as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $row->bill_date_label }}</td>
                                <td>{{ $row->bill_series_label }}</td>
                                <td>{{ $formatQty($row->bill_count) }}</td>
                                <td class="compact-left">{{ $row->sku }}</td>
                                <td class="product-cell compact-left">{{ $row->product_name }}</td>
                                <td class="pack-cell">{{ $row->pack }}</td>
                                <td>{{ $formatQty($row->total_qty) }}</td>
                                <td>{{ $row->sale_rate_label }}</td>
                                <td>{{ $formatAmount($row->total_tax) }}</td>
                                <td>{{ $row->mrp_label }}</td>
                                <td>{{ $formatAmount($row->total_gross) }}</td>
                                @foreach($compactPriceColumns as $priceLabel => $priceColumn)
                                    <td class="text-right">{{ $row->prices->get($priceLabel, '-') }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="19" class="text-center">{{ translate('No records found') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).on('change', '.aiz-date-range', function () {
            var val = $(this).val();
            var fromField = $($(this).data('from-field'));
            var toField = $($(this).data('to-field'));
            fromField.val('');
            toField.val('');
            if (val && val.indexOf(' to ') !== -1) {
                var parts = val.split(' to ');
                fromField.val(parts[0]);
                toField.val(parts[1]);
            }
        });

        $('#bill_date_range').on('apply.daterangepicker', function () {
            $('#report_years').val('');
        });

        $('#report_years').on('change', function () {
            this.form.submit();
        });

        (function () {
            var csrf = '{{ csrf_token() }}';
            var locationUrl = @json(route('get-location'));
            var locationOptionsUrl = @json(route('customers.location.options'));
            var allLabel = @json(translate('All'));
            var pincodeTimer = null;
            var suppressLocationChange = 0;

            function beginSuppress() {
                suppressLocationChange += 1;
            }

            function endSuppress() {
                setTimeout(function () {
                    suppressLocationChange = Math.max(0, suppressLocationChange - 1);
                }, 0);
            }

            function refreshPicker($el) {
                if ($.fn.selectpicker) {
                    $el.selectpicker('refresh');
                    return;
                }
                if (window.AIZ && AIZ.plugins && typeof AIZ.plugins.bootstrapSelect === 'function') {
                    AIZ.plugins.bootstrapSelect('refresh');
                }
            }

            function selectedValue($select) {
                var value = $select.val();
                if ((value === null || value === undefined || value === '') && $.fn.selectpicker) {
                    value = $select.selectpicker('val');
                }
                return value || '';
            }

            function setSelectOptions($select, options, selected) {
                $select.empty();
                $select.append($('<option>', { value: '', text: allLabel }));
                (options || []).forEach(function (opt) {
                    $select.append($('<option>', { value: String(opt.id), text: opt.name }));
                });
                if (selected !== null && selected !== undefined && selected !== '') {
                    var selectedValue = String(selected);
                    var hasOption = $select.find('option').filter(function () {
                        return String($(this).val()) === selectedValue;
                    }).length > 0;
                    if (!hasOption) {
                        $select.append($('<option>', { value: selectedValue, text: selectedValue }));
                    }
                    $select.val(selectedValue);
                } else {
                    $select.val('');
                }
                $select.data('selected', '');
                refreshPicker($select);
            }

            function resetCityDistrictPost() {
                setSelectOptions($('#city_id'), [], '');
                setSelectOptions($('#district'), [], '');
                setSelectOptions($('#post'), [], '');
            }

            function fetchLocationOptions(params) {
                return $.get(locationOptionsUrl, $.extend({ scope: 'business' }, params));
            }

            function loadStates(countryId, selectedStateId) {
                if (!countryId) {
                    setSelectOptions($('#state_id'), [], '');
                    resetCityDistrictPost();
                    return $.Deferred().resolve().promise();
                }

                beginSuppress();
                return fetchLocationOptions({ country_id: countryId }).done(function (resp) {
                    setSelectOptions($('#state_id'), (resp && resp.states) || [], selectedStateId || '');
                    if (!selectedStateId) {
                        resetCityDistrictPost();
                    }
                }).fail(function () {
                    setSelectOptions($('#state_id'), [], '');
                    resetCityDistrictPost();
                }).always(endSuppress);
            }

            function loadCityDistrictPost(countryId, stateId, selected) {
                selected = selected || {};
                if (!countryId || !stateId) {
                    resetCityDistrictPost();
                    return $.Deferred().resolve().promise();
                }

                beginSuppress();
                return fetchLocationOptions({
                    country_id: countryId,
                    state: stateId
                }).done(function (resp) {
                    setSelectOptions($('#city_id'), (resp && resp.cities) || [], selected.city_id || '');
                    setSelectOptions($('#district'), (resp && resp.districts) || [], selected.district || '');
                    setSelectOptions($('#post'), (resp && resp.posts) || [], selected.post || '');
                }).fail(function () {
                    resetCityDistrictPost();
                }).always(endSuppress);
            }

            function loadPosts(countryId, stateId, district, cityId, selectedPost) {
                if (!countryId || !stateId) {
                    setSelectOptions($('#post'), [], '');
                    return;
                }

                var params = {
                    country_id: countryId,
                    state: stateId
                };
                if (district) {
                    params.district = district;
                }
                if (cityId) {
                    params.city = cityId;
                }

                fetchLocationOptions(params).done(function (resp) {
                    setSelectOptions($('#post'), (resp && resp.posts) || [], selectedPost || '');
                }).fail(function () {
                    setSelectOptions($('#post'), [], '');
                });
            }

            function applyFetchedLocation(location) {
                if (!location || !location.country_id) {
                    return $.Deferred().reject().promise();
                }

                beginSuppress();
                $('#country_id').val(String(location.country_id));
                refreshPicker($('#country_id'));

                return loadStates(location.country_id, location.state_id || '').then(function () {
                    if (!location.state_id) {
                        resetCityDistrictPost();
                        return;
                    }
                    return loadCityDistrictPost(location.country_id, location.state_id, {
                        city_id: location.city_id || '',
                        district: location.district || '',
                        post: location.post || location.village || ''
                    });
                }).always(endSuppress);
            }

            function lookupPincode() {
                var postalCode = $.trim($('#pincode').val());
                var $status = $('#pincode-status');

                if (postalCode.length < 4) {
                    $status.removeClass('text-danger text-success').addClass('text-muted').text('');
                    return;
                }

                $status.removeClass('text-danger text-success').addClass('text-muted')
                    .html('<i class="las la-spinner la-spin"></i> {{ translate('Loading location...') }}');

                fetchLocationOptions({
                    pincode: postalCode,
                    country_id: selectedValue($('#country_id')) || undefined
                }).done(function (resp) {
                    if (resp && resp.location && resp.location.country_id) {
                        applyFetchedLocation(resp.location).done(function () {
                            $status.removeClass('text-muted text-danger').addClass('text-success')
                                .text('{{ translate('Location loaded.') }}');
                        });
                        return;
                    }

                    $.ajax({
                        url: locationUrl,
                        method: 'POST',
                        data: {
                            _token: csrf,
                            postal_code: postalCode,
                            country_id: selectedValue($('#country_id')) || ''
                        }
                    }).done(function (location) {
                        if (!location || (!location.country_id && !location.state_id && !location.city_id)) {
                            $status.removeClass('text-muted text-success').addClass('text-danger')
                                .text('{{ translate('No location found for this pincode.') }}');
                            return;
                        }

                        applyFetchedLocation({
                            country_id: location.country_id,
                            state_id: location.state_id,
                            city_id: location.city_id,
                            district: location.district,
                            post: location.post || location.village || ''
                        }).done(function () {
                            $status.removeClass('text-muted text-danger').addClass('text-success')
                                .text('{{ translate('Location loaded.') }}');
                        });
                    }).fail(function (xhr) {
                        var message = xhr.responseJSON && (xhr.responseJSON.message || Object.values(xhr.responseJSON.errors || {})[0]);
                        $status.removeClass('text-muted text-success').addClass('text-danger')
                            .text(message || '{{ translate('Unable to fetch location. Please select manually.') }}');
                    });
                }).fail(function () {
                    $status.removeClass('text-muted text-success').addClass('text-danger')
                        .text('{{ translate('Unable to fetch location. Please select manually.') }}');
                });
            }

            function restoreSelectedLocationFilters() {
                var countryId = selectedValue($('#country_id'));
                var stateId = selectedValue($('#state_id'));
                if (!countryId || !stateId) {
                    return;
                }

                loadCityDistrictPost(countryId, stateId, {
                    city_id: selectedValue($('#city_id')),
                    district: $('#district').data('selected') || '',
                    post: $('#post').data('selected') || ''
                });
            }

            function bindLocationChange($el, handler) {
                $el.on('changed.bs.select change', function () {
                    if (suppressLocationChange || $el.data('handling-location-change')) {
                        return;
                    }
                    $el.data('handling-location-change', true);
                    handler.call(this);
                    setTimeout(function () {
                        $el.data('handling-location-change', false);
                    }, 0);
                });
            }

            bindLocationChange($('#country_id'), function () {
                loadStates(selectedValue($('#country_id')), '');
            });

            bindLocationChange($('#state_id'), function () {
                loadCityDistrictPost(selectedValue($('#country_id')), selectedValue($('#state_id')));
            });

            bindLocationChange($('#district'), function () {
                loadPosts(
                    selectedValue($('#country_id')),
                    selectedValue($('#state_id')),
                    selectedValue($('#district')),
                    selectedValue($('#city_id')),
                    ''
                );
            });

            bindLocationChange($('#city_id'), function () {
                loadPosts(
                    selectedValue($('#country_id')),
                    selectedValue($('#state_id')),
                    selectedValue($('#district')),
                    selectedValue($('#city_id')),
                    ''
                );
            });

            $('#pincode').on('input', function () {
                clearTimeout(pincodeTimer);
                pincodeTimer = setTimeout(lookupPincode, 450);
            });

            $('#consolidatedPurchaseHistoryFilterModal').on('shown.bs.modal', function () {
                ['#country_id', '#state_id', '#city_id', '#district', '#post', '#sort_by', '#sort_dir'].forEach(function (sel) {
                    refreshPicker($(sel));
                });
            });

            restoreSelectedLocationFilters();
        })();
    </script>
@endsection
