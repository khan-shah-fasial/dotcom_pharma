@extends('backend.layouts.app')

@section('content')
    <style>
        .party-consolidated-sheet {
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }
        .party-consolidated-sheet .report-title,
        .party-consolidated-sheet .report-subtitle {
            border: 1px solid #000;
            border-bottom: 0;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.25;
            padding: 3px 6px;
            text-align: center;
        }
        .party-consolidated-table {
            min-width: 980px;
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
        $partyName = $customer?->company_name ?: $account;
        $partyDetails = collect([
            $partyName,
            $contactNumbers->implode(' / '),
        ])->filter()->implode(' - ');
        $periodText = '';
        if ($dateFrom || $dateTo) {
            $periodText = trim(
                ($dateFrom ? ' ' . translate('From') . ' ' . $dateFrom : '') .
                ($dateTo ? ' ' . translate('To') . ' ' . $dateTo : '')
            );
        }
        $totalGross = $reportRows->sum(fn ($row) => $numberValue($row->gross_amount));
    @endphp

    <div class="aiz-titlebar text-left mt-2 mb-3 d-print-none">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1">{{ translate('Consolidated Purchase History') }}</h1>
                <div class="text-muted">{{ translate('Account') }}: {{ $account }}</div>
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

    <div class="card">
        <div class="card-body">
            <div class="party-consolidated-sheet">
                <div class="report-title">
                    {{ translate('Partywise Detail Productwise Sales Report') }}{{ $periodText }}
                </div>
                <div class="report-subtitle">
                    {{ translate('Party Name') }} : {{ $partyDetails }}
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered mb-0 party-consolidated-table">
                        <thead>
                        <tr>
                            <th style="width: 45px">{{ translate('Sr no.') }}</th>
                            <th style="width: 90px">{{ translate('Bill Date') }}</th>
                            <th style="width: 80px">{{ translate('Bill Series') }}</th>
                            <th style="width: 70px">{{ translate('Bill No') }}</th>
                            <th>{{ translate('Product Name') }}</th>
                            <th style="width: 70px">{{ translate('Pack') }}</th>
                            <th style="width: 65px">{{ translate('Qty') }}</th>
                            <th style="width: 80px">{{ translate('S.Rate') }}</th>
                            <th style="width: 65px">{{ translate('Tax') }}</th>
                            <th style="width: 80px">{{ translate('M R P') }}</th>
                            <th style="width: 110px">{{ translate('Gross amount') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($reportRows as $row)
                            @php
                                $productName = $row->product_name ?: $row->product_sku;
                                if (filled($row->product_variant) && ! str_contains((string) $productName, (string) $row->product_variant)) {
                                    $productName = trim($productName . ' ' . $row->product_variant);
                                }
                            @endphp
                            <tr>
                                <td class="text-right">{{ $loop->iteration }}</td>
                                <td>{{ $formatDate($row->bill_date) }}</td>
                                <td>{{ $row->invoice_series }}</td>
                                <td>{{ $row->invoice_number }}</td>
                                <td class="product-cell">{{ $productName }}</td>
                                <td>{{ $row->packing }}</td>
                                <td class="text-right">{{ $formatQty($row->quantity) }}</td>
                                <td class="text-right">{{ $formatAmount($row->sale_rate) }}</td>
                                <td class="text-center">{{ $formatAmount($row->gst_amount) }}</td>
                                <td class="text-right">{{ $formatAmount($row->mrp_rate) }}</td>
                                <td class="text-right">{{ $formatAmount($row->gross_amount) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">{{ translate('No records found') }}</td>
                            </tr>
                        @endforelse

                        @if($reportRows->isNotEmpty())
                            <tr>
                                <td colspan="10" class="total-label">{{ translate('Total') }}</td>
                                <td class="text-right total-amount">{{ $formatAmount($totalGross) }}</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
