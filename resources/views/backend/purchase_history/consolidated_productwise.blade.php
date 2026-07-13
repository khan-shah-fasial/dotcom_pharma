@extends('backend.layouts.app')

@section('content')
    <style>
        .productwise-sheet { min-width: 2250px; table-layout: auto; color: #111; font-size: 11px; }
        .productwise-sheet th, .productwise-sheet td { border-color: #222 !important; padding: 5px !important; vertical-align: middle !important; white-space: nowrap; }
        .productwise-sheet th { font-family: Georgia, serif; text-align: center; }
        .productwise-sheet th a { color: #007bff; display: block; min-height: 16px; }
        .productwise-sheet th a:hover { color: #0056b3; text-decoration: underline; }
        .productwise-sheet .sort-icon { color: #007bff; font-size: 12px; line-height: 1; margin-left: 3px; vertical-align: 1px; }
        .productwise-sheet th a:hover .sort-icon { color: #0056b3; }
        .productwise-sheet .customer-cell { min-width: 160px; white-space: normal; }
        .productwise-sheet .total-row { background: #f5f5f5; font-weight: 700; }
        @media print {
            .main-content { padding: 0 !important; }
            .productwise-sheet { font-size: 8px; min-width: 100%; }
        }
    </style>

    @php
        $numberValue = fn ($value) => (float) str_replace(',', '', (string) ($value ?? 0));
        $formatQty = fn ($value) => number_format($numberValue($value), 0, '.', '');
        $formatAmount = fn ($value) => number_format($numberValue($value), 2, '.', '');
        $formatDate = function ($value) {
            $value = trim((string) $value);
            if ($value === '') return '-';
            foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
                $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
                if ($date && $date->format($format) === $value) return $date->format('d-m-Y');
            }
            return $value;
        };
        $sortLink = function (string $column) use ($sortBy, $sortDir) {
            $nextDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';
            return route('admin.purchase_history.consolidated_productwise', array_merge(request()->except('page'), [
                'sort_by' => $column,
                'sort_dir' => $nextDir,
            ]));
        };
        $sortIcon = function (string $column) use ($sortBy, $sortDir) {
            if ($sortBy !== $column) return '';
            return '<i class="las la-sort-amount-'.($sortDir === 'asc' ? 'up' : 'down').' sort-icon"></i>';
        };
        $sortHeading = function (string $column, string $label) use ($sortLink, $sortIcon) {
            return '<a href="'.e($sortLink($column)).'">'.e($label).$sortIcon($column).'</a>';
        };
        $firstRow = $rows->first();
        $productName = $firstRow?->product_name ?: $sku;
        if (filled($firstRow?->product_variant) && ! str_contains((string) $productName, (string) $firstRow->product_variant)) {
            $productName = trim($productName . ' ' . $firstRow->product_variant);
        }
        $priceColumns = [
            'PTS' => ['label' => 'PTS', 'sort' => 'pts'],
            'PTR' => ['label' => 'PTR', 'sort' => 'ptr'],
            'PTD' => ['label' => 'PTD', 'sort' => 'ptd'],
            'Govt.' => ['label' => 'Govt.', 'sort' => 'govt'],
            'Exp' => ['label' => 'Export', 'sort' => 'export'],
            'Customer' => ['label' => 'Customer', 'sort' => 'customer_price'],
            'M.R.P' => ['label' => 'M R P', 'sort' => 'current_mrp'],
        ];
        $sortOptions = [
            'sr_no' => 'Sr', 'bill_date' => 'Date', 'invoice_series' => 'Series', 'invoice_number' => 'Bill No',
            'ac_number' => 'Acc.No', 'company_name' => 'Company Name', 'customer_name' => 'Name', 'district' => 'District',
            'state' => 'State', 'pincode' => 'Pincode', 'country' => 'Country', 'mobile' => 'Mobile No',
            'alternate_mobile' => 'Alt. Mobile No', 'whatsapp' => 'WhatsApp No', 'email' => 'E-mail', 'quantity' => 'Qty',
            'batch_number' => 'Batch No', 'expiry_date' => 'Expiry', 'manufacturer' => 'Mfg.', 'sale_rate' => 'S.Rate',
            'gst_percentage' => 'Tax %', 'gst_amount' => 'Tax', 'mrp_rate' => 'M R P', 'final_amount' => 'Gross Amount',
            'pts' => 'PTS', 'ptr' => 'PTR', 'ptd' => 'PTD', 'govt' => 'Govt.', 'export' => 'Export',
            'customer_price' => 'Customer', 'current_mrp' => 'Current M R P',
        ];
    @endphp

    <div class="aiz-titlebar text-left mt-2 mb-3 d-print-none">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1 text-danger">{{ translate('Consolidated Productwise Purchase History') }}</h1>
                <div class="text-muted">
                    {{ translate('SKU') }}: {{ $sku }}
                    <span class="mx-2">|</span>{{ translate('Product') }}: {{ $productName }}
                    @if($dateFrom || $dateTo)
                        <span class="mx-2">|</span>{{ translate('Period') }}: {{ $dateFrom ?: '-' }} {{ translate('to') }} {{ $dateTo ?: '-' }}
                    @endif
                </div>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="{{ route('admin.purchase_history.index') }}" class="btn btn-outline-secondary mr-2">{{ translate('Back to Purchase History') }}</a>
                <button type="button" class="btn btn-primary" onclick="window.print()"><i class="las la-print mr-1"></i>{{ translate('Print') }}</button>
            </div>
        </div>
    </div>

    <div class="card d-print-none mb-3">
        <form action="{{ route('admin.purchase_history.consolidated_productwise') }}" method="GET">
            <input type="hidden" name="product_sku" value="{{ $sku }}">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Filters') }}</h5></div>
            <div class="card-body">
                <div class="row gutters-5">
                    <div class="col-md-3 mb-2"><label>{{ translate('Account / CRM No') }}</label><input class="form-control" name="account" value="{{ request('account') }}"></div>
                    <div class="col-md-3 mb-2"><label>{{ translate('Customer / Company') }}</label><input class="form-control" name="customer" value="{{ request('customer') }}"></div>
                    <div class="col-md-2 mb-2"><label>{{ translate('Bill Date From') }}</label><input type="date" class="form-control" name="bill_date_from" value="{{ request('bill_date_from') }}"></div>
                    <div class="col-md-2 mb-2"><label>{{ translate('Bill Date To') }}</label><input type="date" class="form-control" name="bill_date_to" value="{{ request('bill_date_to') }}"></div>
                    <div class="col-md-3 mb-2"><label for="sort_by">{{ translate('Sort By') }}</label><select class="form-control aiz-selectpicker" id="sort_by" name="sort_by">@foreach($sortOptions as $value => $label)<option value="{{ $value }}" @selected($sortBy === $value)>{{ translate($label) }}</option>@endforeach</select></div>
                    <div class="col-md-2 mb-2"><label for="sort_dir">{{ translate('Direction') }}</label><select class="form-control aiz-selectpicker" id="sort_dir" name="sort_dir"><option value="asc" @selected($sortDir === 'asc')>{{ translate('Ascending') }}</option><option value="desc" @selected($sortDir === 'desc')>{{ translate('Descending') }}</option></select></div>
                    <div class="col-md-3 mb-2 d-flex align-items-end"><button class="btn btn-primary mr-2">{{ translate('Apply') }}</button><a class="btn btn-outline-danger" href="{{ route('admin.purchase_history.consolidated_productwise', ['product_sku' => $sku]) }}">{{ translate('Reset') }}</a></div>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 productwise-sheet">
                    <thead><tr>
                        <th>{!! $sortHeading('sr_no', translate('Sr')) !!}</th><th>{!! $sortHeading('bill_date', translate('Date')) !!}</th><th>{!! $sortHeading('invoice_series', translate('Series')) !!}</th><th>{!! $sortHeading('invoice_number', translate('Bill No')) !!}</th>
                        <th>{!! $sortHeading('ac_number', translate('Acc.No')) !!}</th><th>{!! $sortHeading('company_name', translate('Company Name')) !!}</th><th>{!! $sortHeading('customer_name', translate('Name')) !!}</th><th>{!! $sortHeading('district', translate('District')) !!}</th><th>{!! $sortHeading('state', translate('State')) !!}</th><th>{!! $sortHeading('pincode', translate('Pincode')) !!}</th><th>{!! $sortHeading('country', translate('Country')) !!}</th>
                        <th>{!! $sortHeading('mobile', translate('Mobile No')) !!}</th><th>{!! $sortHeading('alternate_mobile', translate('Alt. Mobile No')) !!}</th><th>{!! $sortHeading('whatsapp', translate('WhatsApp No')) !!}</th><th>{!! $sortHeading('email', translate('E-mail')) !!}</th>
                        <th>{!! $sortHeading('quantity', translate('Qty')) !!}</th><th>{!! $sortHeading('batch_number', translate('Batch No')) !!}</th><th>{!! $sortHeading('expiry_date', translate('Expiry')) !!}</th><th>{!! $sortHeading('manufacturer', translate('Mfg.')) !!}</th><th>{!! $sortHeading('sale_rate', translate('S.Rate')) !!}</th><th>{!! $sortHeading('gst_percentage', translate('Tax %')) !!}</th><th>{!! $sortHeading('gst_amount', translate('Tax')) !!}</th><th>{!! $sortHeading('mrp_rate', translate('M R P')) !!}</th><th>{!! $sortHeading('final_amount', translate('Gross Amount')) !!}</th>
                        @foreach($priceColumns as $priceColumn)<th>{!! $sortHeading($priceColumn['sort'], translate($priceColumn['label'])) !!}</th>@endforeach
                    </tr></thead>
                    <tbody>
                    @forelse($rows as $row)
                        @php
                            $price = $currentPriceMap[$row->product_sku] ?? null;
                            $lines = $price ? ($price['batches'][$row->batch_number] ?? $price['default']) : [];
                            $values = collect($lines)->mapWithKeys(fn ($line) => [(string) ($line['label'] ?? '') => $line['value'] ?? '-']);
                        @endphp
                        <tr>
                            <td class="text-right">{{ $loop->iteration }}</td><td>{{ $formatDate($row->bill_date) }}</td><td>{{ $row->invoice_series ?: '-' }}</td><td>{{ $row->invoice_number ?: '-' }}</td>
                            <td>{{ $row->ac_number ?: '-' }}</td><td class="customer-cell">{{ $row->company_name ?: '-' }}</td><td class="customer-cell">{{ $row->customer_name ?: '-' }}</td><td>{{ $row->district ?: $row->city ?: '-' }}</td><td>{{ $row->state ?: '-' }}</td><td>{{ $row->pincode ?: '-' }}</td><td>{{ $row->country ?: '-' }}</td>
                            <td>{{ $row->mobile ?: '-' }}</td><td>{{ $row->alternate_mobile ?: '-' }}</td><td>{{ $row->whatsapp ?: '-' }}</td><td>{{ $row->email ?: '-' }}</td>
                            <td class="text-right">{{ $formatQty($row->quantity) }}</td><td>{{ $row->batch_number ?: '-' }}</td><td>{{ $formatDate($row->expiry_date) }}</td><td>{{ $row->manufacturer ?: '-' }}</td><td class="text-right">{{ $formatAmount($row->sale_rate) }}</td><td class="text-right">{{ $formatAmount($row->gst_percentage) }}</td><td class="text-right">{{ $formatAmount($row->gst_amount) }}</td><td class="text-right">{{ $formatAmount($row->mrp_rate) }}</td><td class="text-right">{{ $formatAmount($row->final_amount) }}</td>
                            @foreach($priceColumns as $key => $priceColumn)<td class="text-right">{{ $values->get($key, '-') }}</td>@endforeach
                        </tr>
                    @empty
                        <tr><td colspan="31" class="text-center py-4">{{ translate('No records found') }}</td></tr>
                    @endforelse
                    @if($rows->isNotEmpty())
                        <tr class="total-row"><td colspan="15" class="text-right">{{ translate('Total') }}</td><td class="text-right">{{ $formatQty($rows->sum(fn ($row) => $numberValue($row->quantity))) }}</td><td colspan="7"></td><td class="text-right">{{ $formatAmount($rows->sum(fn ($row) => $numberValue($row->final_amount))) }}</td><td colspan="7"></td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
