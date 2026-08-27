@extends('backend.layouts.app')

@section('content')
    <style>
        .purchase-history-sheet {
            min-width: 1900px;
            table-layout: fixed;
            color: #111;
            font-size: 11px;
        }
        .purchase-history-sheet th,
        .purchase-history-sheet td {
            border-color: #222 !important;
            padding: 6px 5px !important;
            vertical-align: middle !important;
            white-space: normal;
            overflow-wrap: anywhere;
        }
        .purchase-history-sheet th {
            font-family: Georgia, serif;
            font-weight: 700;
            line-height: 1.15;
            text-align: center;
        }
        .purchase-history-sheet th a {
            color: #007bff;
            display: block;
            min-height: 16px;
        }
        .purchase-history-sheet th a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .purchase-history-sheet .cell-lines span {
            display: block;
            min-height: 16px;
        }
        .purchase-history-sheet .party-cell {
            text-align: left;
        }
        .purchase-history-sheet .text-red {
            color: #ff0000;
            font-weight: 700;
        }
        .purchase-history-sheet .sort-icon {
            color: #007bff;
            font-size: 12px;
            line-height: 1;
            margin-left: 3px;
            vertical-align: 1px;
        }
        .purchase-history-sheet th a:hover .sort-icon {
            color: #0056b3;
        }
        .purchase-history-sheet .account-report-link {
            color: #007bff;
            font-weight: 700;
            text-decoration: underline;
        }
    </style>
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="align-items-center">
            <h1 class="h3">{{ translate('Purchase History') }}</h1>
        </div>
    </div>

    @if(session('purchase_history_error_log_available'))
        <div class="alert alert-warning mb-3 d-flex justify-content-between align-items-center">
            <div>
                {{ translate('Some rows failed during the last import.') }}
            </div>
            <a href="{{ route('admin.purchase_history.error_log') }}"
               class="btn btn-sm btn-outline-primary"
               target="_blank">
                {{ translate('Open error report') }}
            </a>
        </div>
    @endif

    <div class="card">
        <form action="{{ route('admin.purchase_history.index') }}" method="GET" id="purchase-history-filters">
            @php
                $sortLink = function (string $column) use ($sortBy, $sortDir) {
                    $nextDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';

                    return route('admin.purchase_history.index', array_merge(request()->except('page'), [
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
                $numberValue = function ($value) {
                    if ($value === null || $value === '') {
                        return 0;
                    }

                    return (float) str_replace(',', '', (string) $value);
                };
                $formatQty = fn ($value) => number_format($numberValue($value), 0, '.', '');
                $formatAmount = fn ($value) => number_format($numberValue($value), 2, '.', '');
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
                $dateRangeValue = function (string $fromKey, string $toKey) use ($formatFilterDate) {
                    if (! request($fromKey) || ! request($toKey)) {
                        return '';
                    }

                    return $formatFilterDate(request($fromKey)) . ' to ' . $formatFilterDate(request($toKey));
                };
                $filtersApplied = collect([
                    request('account'),
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
                    request('pincode'),
                    request('country_id'),
                    request('state_id'),
                    request('city_id'),
                    request('district'),
                    request('post'),
                    request('transport'),
                    request('order_date_from'),
                    request('order_date_to'),
                    request('expiry_date_from'),
                    request('expiry_date_to'),
                ])->contains(fn ($value) => $value !== null && $value !== '');
            @endphp
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <div class="mb-2">
                    <h5 class="mb-0 h6">{{ translate('Purchase History Records') }}</h5>
                    @if($filtersApplied)
                        <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap align-items-center">
                    <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal"
                            data-target="#purchaseHistoryFilterModal">
                        {{ translate('Open Filters') }}
                    </button>
                    <a href="{{ route('admin.purchase_history.index') }}" class="btn btn-danger mr-2 mb-2">
                        {{ translate('Reset') }}
                    </a>
                    <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal"
                            data-target="#purchase-history-import-modal">
                        <i class="las la-file-import mr-1"></i>{{ translate('Import Purchase History') }}
                    </button>
                    <a href="{{ route('admin.purchase_history.export', request()->query()) }}"
                       class="btn btn-success mb-2">
                        <i class="las la-download mr-1"></i>{{ translate('Export') }}
                    </a>
                </div>
            </div>

            <div class="modal fade" id="purchaseHistoryFilterModal" tabindex="-1" role="dialog"
                 aria-labelledby="purchaseHistoryFilterModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="purchaseHistoryFilterModalLabel">
                                {{ translate('Filter Purchase History') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row gutters-5">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="account">{{ translate('Account / CRM No') }}</label>
                                    <input type="text" class="form-control" id="account" name="account"
                                           value="{{ request('account') }}"
                                           placeholder="{{ translate('Account / CRM No') }}">
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
                                    <label class="form-label" for="product_sku">{{ translate('Product SKU') }}</label>
                                    <input type="text" class="form-control" id="product_sku" name="product_sku"
                                           value="{{ request('product_sku') }}" placeholder="{{ translate('Enter SKU') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="product_name">{{ translate('Product Name') }}</label>
                                    <input type="text" class="form-control" id="product_name" name="product_name"
                                           value="{{ request('product_name') }}" placeholder="{{ translate('Product Name') }}">
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
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="order_date_range">{{ translate('Order Date Range') }}</label>
                                    <input type="text" class="form-control aiz-date-range" id="order_date_range"
                                           name="order_date_range"
                                           value="{{ $dateRangeValue('order_date_from', 'order_date_to') }}"
                                           data-time-picker="false" data-format="DD-MM-YYYY"
                                           data-from-field="#order_date_from" data-to-field="#order_date_to"
                                           placeholder="{{ translate('DD-MM-YYYY to DD-MM-YYYY') }}">
                                    <input type="hidden" name="order_date_from" id="order_date_from" value="{{ request('order_date_from') }}">
                                    <input type="hidden" name="order_date_to" id="order_date_to" value="{{ request('order_date_to') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="expiry_date_range">{{ translate('Expiry Date') }}</label>
                                    <input type="text" class="form-control aiz-date-range" id="expiry_date_range"
                                           name="expiry_date_range"
                                           value="{{ $dateRangeValue('expiry_date_from', 'expiry_date_to') }}"
                                           data-time-picker="false" data-format="DD-MM-YYYY"
                                           data-from-field="#expiry_date_from" data-to-field="#expiry_date_to"
                                           placeholder="{{ translate('DD-MM-YYYY to DD-MM-YYYY') }}">
                                    <input type="hidden" name="expiry_date_from" id="expiry_date_from" value="{{ request('expiry_date_from') }}">
                                    <input type="hidden" name="expiry_date_to" id="expiry_date_to" value="{{ request('expiry_date_to') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="sort_by">{{ translate('Sort By') }}</label>
                                    <select class="form-control aiz-selectpicker" id="sort_by" name="sort_by">
                                        <option value="order_date" @if($sortBy=='order_date') selected @endif>{{ translate('Order Date') }}</option>
                                        <option value="sr_no" @if($sortBy=='sr_no') selected @endif>{{ translate('Sr.No') }}</option>
                                        <option value="ac_number" @if($sortBy=='ac_number') selected @endif>{{ translate('Ac.No') }}</option>
                                        <option value="account_name" @if($sortBy=='account_name') selected @endif>{{ translate('Name') }}</option>
                                        <option value="party_name" @if($sortBy=='party_name') selected @endif>{{ translate('Party Name') }}</option>
                                        <option value="area" @if($sortBy=='area') selected @endif>{{ translate('Area') }}</option>
                                        <option value="town" @if($sortBy=='town') selected @endif>{{ translate('Town') }}</option>
                                        <option value="district" @if($sortBy=='district') selected @endif>{{ translate('District') }}</option>
                                        <option value="state" @if($sortBy=='state') selected @endif>{{ translate('State') }}</option>
                                        <option value="pincode" @if($sortBy=='pincode') selected @endif>{{ translate('Pincode') }}</option>
                                        <option value="country" @if($sortBy=='country') selected @endif>{{ translate('Country') }}</option>
                                        <option value="order_number" @if($sortBy=='order_number') selected @endif>{{ translate('Order Number') }}</option>
                                        <option value="sales_man_name" @if($sortBy=='sales_man_name') selected @endif>{{ translate('Salesman') }}</option>
                                        <option value="sales_man_code" @if($sortBy=='sales_man_code') selected @endif>{{ translate('Sales Man Code') }}</option>
                                        <option value="invoice_date" @if($sortBy=='invoice_date') selected @endif>{{ translate('Invoice Date') }}</option>
                                        <option value="invoice_series" @if($sortBy=='invoice_series') selected @endif>{{ translate('Series') }}</option>
                                        <option value="invoice_number" @if($sortBy=='invoice_number') selected @endif>{{ translate('Invoice Number') }}</option>
                                        <option value="product_sku" @if($sortBy=='product_sku') selected @endif>{{ translate('Product SKU') }}</option>
                                        <option value="product_name" @if($sortBy=='product_name') selected @endif>{{ translate('Product Name') }}</option>
                                        <option value="packing" @if($sortBy=='packing') selected @endif>{{ translate('Pack Size') }}</option>
                                        <option value="batch_number" @if($sortBy=='batch_number') selected @endif>{{ translate('Batch Number') }}</option>
                                        <option value="expiry_date" @if($sortBy=='expiry_date') selected @endif>{{ translate('Expiry') }}</option>
                                        <option value="mfd_by" @if($sortBy=='mfd_by') selected @endif>{{ translate('Mfd By') }}</option>
                                        <option value="quantity" @if($sortBy=='quantity') selected @endif>{{ translate('Quantity') }}</option>
                                        <option value="free" @if($sortBy=='free') selected @endif>{{ translate('Free') }}</option>
                                        <option value="total_quantity" @if($sortBy=='total_quantity') selected @endif>{{ translate('Qty Total') }}</option>
                                        <option value="sale_rate" @if($sortBy=='sale_rate') selected @endif>{{ translate('Sale Rate') }}</option>
                                        <option value="discount" @if($sortBy=='discount') selected @endif>{{ translate('Disc%') }}</option>
                                        <option value="mrp_rate" @if($sortBy=='mrp_rate') selected @endif>{{ translate('MRP') }}</option>
                                        <option value="taxable_amount" @if($sortBy=='taxable_amount') selected @endif>{{ translate('Taxable Amount') }}</option>
                                        <option value="gst_amount" @if($sortBy=='gst_amount') selected @endif>{{ translate('GST') }}</option>
                                        <option value="final_amount" @if($sortBy=='final_amount') selected @endif>{{ translate('Final Amount') }}</option>
                                        <option value="tax_code" @if($sortBy=='tax_code') selected @endif>{{ translate('Tax Code') }}</option>
                                        <option value="gst_percentage" @if($sortBy=='gst_percentage') selected @endif>{{ translate('GST%') }}</option>
                                        <option value="transport" @if($sortBy=='transport') selected @endif>{{ translate('Transport') }}</option>
                                        <option value="book_to" @if($sortBy=='book_to') selected @endif>{{ translate('Booked To') }}</option>
                                        <option value="case_value" @if($sortBy=='case_value') selected @endif>{{ translate('Case') }}</option>
                                        <option value="lr_number" @if($sortBy=='lr_number') selected @endif>{{ translate('LR Number') }}</option>
                                        <option value="lr_date" @if($sortBy=='lr_date') selected @endif>{{ translate('LR Date') }}</option>
                                        <option value="late_by" @if($sortBy=='late_by') selected @endif>{{ translate('Late By') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label" for="sort_dir">{{ translate('Direction') }}</label>
                                    <select class="form-control aiz-selectpicker" id="sort_dir" name="sort_dir">
                                        <option value="asc" @if($sortDir=='asc') selected @endif>{{ translate('Ascending') }}</option>
                                        <option value="desc" @if($sortDir=='desc') selected @endif>{{ translate('Descending') }}</option>
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

        <div class="card-body">
            <div class="table-responsive">
                <table class="table mb-0 table-bordered purchase-history-sheet">
                    <thead>
                    <tr>
                        <th style="width: 55px">{!! $sortHeading('sr_no', translate('Sr.No')) !!}</th>
                        <th style="width: 125px">{!! $sortHeading('ac_number', translate('Ac.No')) !!}{!! $sortHeading('account_name', translate('Name')) !!}{!! $sortHeading('sales_man_name', translate('SalesMan')) !!}</th>
                        <th style="width: 210px">{!! $sortHeading('party_name', translate('Party Name')) !!}{!! $sortHeading('area', translate('Area')) !!}, {!! $sortHeading('town', translate('Town')) !!}{!! $sortHeading('district', translate('District')) !!}</th>
                        <th style="width: 135px">{!! $sortHeading('state', translate('State')) !!}{!! $sortHeading('pincode', translate('Pincode')) !!}{!! $sortHeading('country', translate('Country')) !!}</th>
                        <th style="width: 135px">{!! $sortHeading('order_date', translate('Order Date')) !!}{!! $sortHeading('order_number', translate('Order.No')) !!}{!! $sortHeading('sales_man_code', translate('Sales Man Code')) !!}</th>
                        <th style="width: 120px">{!! $sortHeading('invoice_date', translate('Date')) !!}{!! $sortHeading('invoice_series', translate('Series')) !!}{!! $sortHeading('invoice_number', translate('Bill')) !!}</th>
                        <th style="width: 220px">{!! $sortHeading('product_sku', translate('SKU')) !!}{!! $sortHeading('product_name', translate('Product')) !!}{!! $sortHeading('packing', translate('Pack Size')) !!}</th>
                        <th style="width: 145px">{!! $sortHeading('batch_number', translate('Batch')) !!}{!! $sortHeading('expiry_date', translate('Expiry')) !!}{!! $sortHeading('mfd_by', translate('Mfd By')) !!}</th>
                        <th style="width: 75px">{!! $sortHeading('quantity', translate('Qty')) !!}{!! $sortHeading('free', translate('Free')) !!}{!! $sortHeading('total_quantity', translate('Total')) !!}</th>
                        <th style="width: 90px">{!! $sortHeading('sale_rate', translate('Sale Rate')) !!}{!! $sortHeading('discount', translate('Disc%')) !!}{!! $sortHeading('mrp_rate', translate('MRP')) !!}</th>
                        <th style="width: 95px">{!! $sortHeading('taxable_amount', translate('Taxable')) !!}{!! $sortHeading('gst_amount', translate('GST')) !!}{!! $sortHeading('final_amount', translate('Total')) !!}</th>
                        <th style="width: 80px">{!! $sortHeading('tax_code', translate('Tax Code')) !!}{!! $sortHeading('gst_percentage', translate('GST%')) !!}</th>
                        <th style="width: 160px">{!! $sortHeading('transport', translate('Transport')) !!}{!! $sortHeading('book_to', translate('Booked To')) !!}{!! $sortHeading('case_value', translate('Case')) !!}</th>
                        <th style="width: 125px">{!! $sortHeading('lr_number', translate('L.R.No')) !!}{!! $sortHeading('lr_date', translate('LR Date')) !!}{!! $sortHeading('late_by', translate('Late By')) !!}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($purchaseHistory as $history)
                        @php
                            $customer = $history->customerDetails;
                            $product = $history->productStock?->product;
                            $quantity = $numberValue($history->quantity);
                            $free = $numberValue($history->free);
                            $displayOrderNumber = filled($history->order_number) ? $history->order_number : $history->invoice_number;
                            $accountNumber = $customer?->crm_id ?? $history->ac_number;
                            $consolidatedReportUrl = filled($accountNumber)
                                ? route('admin.purchase_history.consolidated', array_merge(request()->except(['page', 'sort_by', 'sort_dir', 'per_page', 'account']), ['account' => $accountNumber]))
                                : null;
                            $productwiseReportUrl = filled($history->product_sku)
                                ? route('admin.purchase_history.consolidated_productwise', ['product_sku' => $history->product_sku])
                                : null;
                        @endphp
                        <tr>
                            <td class="text-right">{{ $purchaseHistory->firstItem() + $loop->index }}</td>
                            <td class="cell-lines text-left">
                                <span>
                                    @if($consolidatedReportUrl)
                                        <a href="{{ $consolidatedReportUrl }}"
                                           class="account-report-link"
                                           target="_blank"
                                           rel="noopener"
                                           title="{{ translate('Open consolidated report') }}">
                                            {{ $accountNumber }}
                                        </a>
                                    @else
                                        {{ $accountNumber }}
                                    @endif
                                </span>
                                <span>{{ $customer?->user?->name }}</span>
                                <span>{{ $history->sales_man_name }}</span>
                            </td>
                            <td class="cell-lines party-cell">
                                <span class="text-red">{{ $customer?->company_name }}</span>
                                <span>{{ collect([$customer?->post_business, $customer?->businessCity?->name])->filter()->implode(', ') }}</span>
                                <span>{{ $customer?->district_business }}</span>
                            </td>
                            <td class="cell-lines text-left">
                                <span>{{ $customer?->businessState?->name }}</span>
                                <span>{{ $customer?->pincode_business }}</span>
                                <span>{{ $customer?->businessCountry?->name }}</span>
                            </td>
                            <td class="cell-lines text-right"><span>{{ $history->order_date }}</span><span class="{{ filled($history->order_number) ? '' : 'text-red' }}">{{ $displayOrderNumber }}</span><span>{{ $history->sales_man_code }}</span></td>
                            <td class="cell-lines text-right"><span>{{ $history->invoice_date }}</span><span>{{ $history->invoice_series }}</span><span class="text-red">{{ $history->invoice_number }}</span></td>
                            <td class="cell-lines text-left"><span>@if($productwiseReportUrl)<a href="{{ $productwiseReportUrl }}" class="account-report-link" target="_blank" rel="noopener" title="{{ translate('Open consolidated productwise report') }}">{{ $history->product_sku }}</a>@else{{ $history->product_sku }}@endif</span><span class="text-red">{{ $product?->name }}</span><span>{{ $history->packing }}</span></td>
                            <td class="cell-lines text-left"><span>{{ $history->batch_number }}</span><span>{{ $history->expiry_date }}</span><span>{{ $product?->brand?->name }}</span></td>
                            <td class="cell-lines text-right"><span>{{ $formatQty($history->quantity) }}</span><span>{{ $formatQty($history->free) }}</span><span class="text-red">{{ $formatQty($quantity + $free) }}</span></td>
                            <td class="cell-lines text-right"><span>{{ $formatAmount($history->sale_rate) }}</span><span>{{ $formatAmount($history->discount) }}</span><span>{{ $formatAmount($history->mrp_rate) }}</span></td>
                            <td class="cell-lines text-right"><span>{{ $formatAmount($history->taxable_amount) }}</span><span>{{ $formatAmount($history->gst_amount) }}</span><span class="text-red">{{ $formatAmount($history->final_amount) }}</span></td>
                            <td class="cell-lines text-center"><span>{{ $history->tax_code }}</span><span>{{ $formatAmount($history->gst_percentage) }}</span></td>
                            <td class="cell-lines text-left"><span>{{ $history->transport }}</span><span>{{ $history->book_to }}</span><span>{{ $history->case_value }}</span></td>
                            <td class="cell-lines text-right">
                                <span>
                                    <a href="javascript:void(0);" onclick="show_purchase_history_detail('{{ route('admin.purchase_history.show', $history->id) }}')" title="{{ translate('View Details') }}">
                                        {{ $history->lr_number }}
                                    </a>
                                </span>
                                <span>{{ $history->lr_date }}</span>
                                <span class="text-red">{{ $history->late_by }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100" class="text-center">{{ translate('No records found') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="aiz-pagination mt-3">
                {{ $purchaseHistory->links() }}
            </div>
        </div>
    </div>

    <div class="modal fade" id="purchase-history-detail-modal" tabindex="-1" role="dialog" aria-labelledby="purchaseHistoryDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Purchase History Details') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="purchase-history-detail-body"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="purchase-history-import-modal" tabindex="-1" role="dialog" aria-labelledby="purchaseHistoryImportLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="purchaseHistoryImportLabel">{{ translate('Import Purchase History') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.purchase_history.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted mb-3">
                            {{ translate('Upload a CSV or Excel file with a header row matching the party wise sheet columns.') }}
                        </p>
                        <div class="form-group">
                            <label class="mb-1">{{ translate('File (CSV, XLSX, XLS)') }}</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" name="file" id="purchase_history_import_file_modal" required>
                                <label class="custom-file-label" for="purchase_history_import_file_modal">{{ translate('Choose file') }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ translate('Import') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="purchase-history-delete-modal" tabindex="-1" role="dialog" aria-labelledby="purchaseHistoryDeleteLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="purchaseHistoryDeleteLabel">{{ translate('Delete Purchase History Record') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="purchase-history-delete-form" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        <p class="mb-3">
                            {{ translate('Are you sure you want to delete this record? This action cannot be undone.') }}
                        </p>
                        <ul class="list-unstyled mb-0 small">
                            <li><strong>{{ translate('Serial') }}:</strong> <span id="ph-delete-serial">-</span></li>
                            <li><strong>{{ translate('Order') }}:</strong> <span id="ph-delete-order">-</span></li>
                            <li><strong>{{ translate('Invoice') }}:</strong> <span id="ph-delete-invoice">-</span></li>
                            <li><strong>{{ translate('SKU') }}:</strong> <span id="ph-delete-sku">-</span></li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                            {{ translate('Cancel') }}
                        </button>
                        <button type="submit" class="btn btn-danger">
                            {{ translate('Delete') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function show_purchase_history_detail(url) {
            $('#purchase-history-detail-body').html('');
            $('#purchase-history-detail-modal').modal('show');
            $.get(url, function (html) {
                $('#purchase-history-detail-body').html(html);
            }).fail(function () {
                $('#purchase-history-detail-body').html('<div class="alert alert-danger mb-0">{{ translate('Failed to load details.') }}</div>');
            });
        }

        function confirm_delete_purchase_history(url, serial, orderNo, invoiceNo, sku) {
            $('#purchase-history-delete-form').attr('action', url);
            $('#ph-delete-serial').text(serial || '-');
            $('#ph-delete-order').text(orderNo || '-');
            $('#ph-delete-invoice').text(invoiceNo || '-');
            $('#ph-delete-sku').text(sku || '-');
            $('#purchase-history-delete-modal').modal('show');
        }

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

        // Update custom-file label text when a file is selected (import modal)
        $(document).on('change', '#purchase_history_import_file_modal', function () {
            var fileName = $(this).val().split('\\').pop();
            if (fileName) {
                $(this).siblings('.custom-file-label').addClass('selected').text(fileName);
            }
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

            $('#purchaseHistoryFilterModal').on('shown.bs.modal', function () {
                ['#country_id', '#state_id', '#city_id', '#district', '#post'].forEach(function (sel) {
                    refreshPicker($(sel));
                });
            });

            restoreSelectedLocationFilters();
        })();
    </script>
@endsection

