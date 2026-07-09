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
            color: #000;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .party-consolidated-table .sort-icon {
            font-size: 13px;
            margin-left: 3px;
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
        $sortLink = function (string $column) use ($sortBy, $sortDir) {
            $nextDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';

            return route('admin.purchase_history.consolidated', array_merge(request()->except('page'), [
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
        $preservedConsolidatedFilters = [
            'search',
            'serial_number',
            'order_number',
            'invoice_number',
            'sales_man_name',
            'sales_man_code',
            'lr_number',
            'party_name',
            'user_name',
            'state',
            'city',
            'district',
            'transport',
            'expiry_date_from',
            'expiry_date_to',
        ];
        $filtersApplied = collect(array_merge([
            $billDateFromValue,
            $billDateToValue,
            request('product_sku'),
            request('product_name'),
        ], array_map(fn ($key) => request($key), $preservedConsolidatedFilters)))
            ->contains(fn ($value) => $value !== null && $value !== '');
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
            'PTS' => 'PTS',
            'PTR' => 'PTR',
            'PTD' => 'PTD',
            'Govt.' => 'Govt.',
            'Exp' => 'Exp',
            'Customer' => 'Customer',
            'M.R.P' => 'M.R.P',
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
            @foreach($preservedConsolidatedFilters as $filterKey)
                @if(request()->filled($filterKey))
                    <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                @endif
            @endforeach

            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <div class="mb-2">
                    <h5 class="mb-0 h6">{{ translate('Filters') }}</h5>
                    @if($filtersApplied)
                        <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
                    @endif
                </div>
                <div class="mb-2">
                    <a href="{{ route('admin.purchase_history.consolidated', ['account' => $account]) }}"
                       class="btn btn-outline-danger mr-2">
                        {{ translate('Reset') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ translate('Apply Filters') }}
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="row gutters-5">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="bill_date_range">{{ translate('Bill Date') }}</label>
                        <input type="text" class="form-control aiz-date-range" id="bill_date_range"
                               name="bill_date_range"
                               value="{{ $dateRangeValue($billDateFromValue, $billDateToValue) }}"
                               data-time-picker="false" data-format="DD-MM-YYYY"
                               data-from-field="#bill_date_from" data-to-field="#bill_date_to"
                               placeholder="{{ translate('DD-MM-YYYY to DD-MM-YYYY') }}">
                        <input type="hidden" name="bill_date_from" id="bill_date_from" value="{{ $billDateFromValue }}">
                        <input type="hidden" name="bill_date_to" id="bill_date_to" value="{{ $billDateToValue }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="product_sku">{{ translate('SKU') }}</label>
                        <input type="text" class="form-control" id="product_sku" name="product_sku"
                               value="{{ request('product_sku') }}" placeholder="{{ translate('Enter SKU') }}">
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label" for="product_name">{{ translate('Product') }}</label>
                        <input type="text" class="form-control" id="product_name" name="product_name"
                               value="{{ request('product_name') }}" placeholder="{{ translate('Product') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="sort_by">{{ translate('Sort By') }}</label>
                        <select class="form-control aiz-selectpicker" id="sort_by" name="sort_by">
                            <option value="bill_date" @if($sortBy === 'bill_date') selected @endif>{{ translate('Bill Date') }}</option>
                            <option value="bill_series" @if($sortBy === 'bill_series') selected @endif>{{ translate('Bill Series') }}</option>
                            <option value="product_sku" @if($sortBy === 'product_sku') selected @endif>{{ translate('SKU') }}</option>
                            <option value="product_name" @if($sortBy === 'product_name') selected @endif>{{ translate('Product') }}</option>
                            <option value="packing" @if($sortBy === 'packing') selected @endif>{{ translate('Pack') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="sort_dir">{{ translate('Direction') }}</label>
                        <select class="form-control aiz-selectpicker" id="sort_dir" name="sort_dir">
                            <option value="asc" @if($sortDir === 'asc') selected @endif>{{ translate('Ascending') }}</option>
                            <option value="desc" @if($sortDir === 'desc') selected @endif>{{ translate('Descending') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="party-consolidated-sheet">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 party-consolidated-table">
                        <thead>
                        <tr>
                            <th style="width: 45px">{{ translate('Sr no.') }}</th>
                            <th style="width: 90px">{!! $sortHeading('bill_date', translate('Bill Date')) !!}</th>
                            <th style="width: 80px">{!! $sortHeading('bill_series', translate('Bill Series')) !!}</th>
                            <th style="width: 70px">{{ translate('Bill No') }}</th>
                            <th style="width: 95px">{!! $sortHeading('product_sku', translate('SKU')) !!}</th>
                            <th>{!! $sortHeading('product_name', translate('Product Name')) !!}</th>
                            <th style="width: 70px">{!! $sortHeading('packing', translate('Pack')) !!}</th>
                            <th style="width: 65px">{{ translate('Qty') }}</th>
                            <th style="width: 80px">{{ translate('S.Rate') }}</th>
                            <th style="width: 65px">{{ translate('Tax') }}</th>
                            <th style="width: 80px">{{ translate('M R P') }}</th>
                            <th style="width: 110px">{{ translate('Gross amount') }}</th>
                            @foreach($currentPriceColumns as $columnLabel)
                                <th style="width: 80px">{{ translate($columnLabel) }}</th>
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
                                @foreach($currentPriceColumns as $priceLabel => $columnLabel)
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
    </script>
@endsection
