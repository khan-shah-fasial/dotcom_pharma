@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="card shadow-none rounded-0 border mb-4">
        <div class="card-header border-bottom-0">
            <h5 class="mb-0 fs-20 fw-700 text-dark">{{ translate('Total Spend & Save') }}</h5>
            <div class="text-muted fs-12">{{ translate('Track how much you paid versus MRP across all orders') }}</div>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="fs-16 fw-700 text-dark">{{ single_price($totalSale) }}</div>
                    <div class="text-muted fs-12">{{ translate('Total Spent') }}</div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="fs-16 fw-700 text-dark">{{ single_price($totalMrp) }}</div>
                    <div class="text-muted fs-12">{{ translate('Total MRP Value') }}</div>
                </div>
                <div class="col-md-4">
                    <div class="fs-16 fw-700 text-success">{{ single_price($totalSaved) }}</div>
                    <div class="text-muted fs-12">{{ translate('Total Saved') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-none rounded-0 border">
        <div class="card-header border-bottom-0 d-flex align-items-center justify-content-between flex-wrap">
            <div>
                <h6 class="mb-0 fs-16 fw-700 text-dark">{{ translate('Product wise summary') }}</h6>
                <div class="text-muted fs-12">{{ translate('Grouped by product and variant') }}</div>
            </div>
        </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead class="text-gray fs-12">
                    <tr>
                        <th class="pl-0">{{ translate('Product') }}</th>
                        <th class="text-center">{{ translate('Qty') }}</th>
                        <th class="text-right">{{ translate('MRP Value') }}</th>
                        <th class="text-right">{{ translate('Paid') }}</th>
                        <th class="text-right pr-0">{{ translate('Saved') }}</th>
                    </tr>
                </thead>
                <tbody class="fs-14">
                    @forelse ($groupedOrders as $row)
                        @php
                            $productName = $row->product_name ?? translate('Product unavailable');
                            $thumb = $row->product_thumbnail ? uploaded_asset($row->product_thumbnail) : static_asset('assets/img/placeholder.jpg');
                            $productSlug = $row->product_slug ?? null;
                            $mrpTotal = $row->total_mrp ?? 0;
                            $paid = $row->total_sale ?? $row->total_price ?? 0;
                            $saved = max($mrpTotal - $paid, 0);
                        @endphp
                        <tr>
                            <td class="pl-0">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm mr-2">
                                        <img src="{{ $thumb }}"
                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';"
                                            alt="{{ $productName }}">
                                    </span>
                                    <div>
                                        @if ($productSlug)
                                            <a href="{{ route('product', $productSlug) }}" class="fw-700 text-dark">{{ $productName }}</a>
                                        @else
                                            <span class="fw-700 text-dark">{{ $productName }}</span>
                                        @endif
                                        <div class="text-muted fs-12">{{ $row->variation ?: translate('Default') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center fw-700">{{ $row->total_qty }}</td>
                            <td class="text-right">{{ single_price($mrpTotal) }}</td>
                            <td class="text-right fw-700">{{ single_price($paid) }}</td>
                            <td class="text-right pr-0 fw-700 text-success">{{ single_price($saved) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                {{ translate('No orders to show yet') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-2">
                {{ $groupedOrders->links() }}
            </div>
        </div>
    </div>
@endsection
