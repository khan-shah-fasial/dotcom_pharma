@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="card shadow-none rounded-0 border mb-4 overflow-hidden spend-hero-card w-100">
        <div class="spend-hero px-3 px-md-5 py-4 py-md-5">
            <div class="row align-items-center gy-4">
                <div class="col-12 col-lg-8 text-center text-lg-left">
                    <div class="text-uppercase text-primary spend-label-12 fw-700 mb-1">{{ translate('Savings dashboard') }}</div>
                    <h3 class="spend-title fw-800 mb-2 text-dark">{{ translate('Wow! Look how much you saved') }}</h3>
                    <div class="text-muted spend-body-13 mb-3">{{ translate('Explore your savings with us') }}</div>
                </div>
                <div class="col-12 col-lg-4 text-center">
                    <div class="d-inline-flex flex-column align-items-center justify-content-center rounded-circle bg-white shadow-sm p-4 spend-hero-circle mx-auto">
                        <span class="spend-label-12 text-secondary">{{ translate('Total Saved') }}</span>
                        <span class="spend-amount fw-800 text-success">{{ single_price($totalSaved) }}</span>
                        <span class="spend-label-12 text-secondary">{{ translate('so far') }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row text-center gy-3 spend-metrics">
                <div class="col-6 col-md-6 col-lg-3">
                    <div class="p-3 bg-soft-primary rounded h-100 metric-card">
                        <div class="spend-label-12 text-muted">{{ translate('Total Spends') }}</div>
                        <div class="spend-value fw-800 text-dark">{{ single_price($totalSale) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-lg-3">
                    <div class="p-3 bg-soft-success rounded h-100 metric-card">
                        <div class="spend-label-12 text-muted">{{ translate('Actual MRP Value') }}</div>
                        <div class="spend-value fw-800 text-dark">{{ single_price($totalMrp) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-lg-3">
                    <div class="p-3 bg-soft-info rounded h-100 metric-card">
                        <div class="spend-label-12 text-muted">{{ translate('Total Order Count') }}</div>
                        <div class="spend-value fw-800 text-dark">{{ $orderCount }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-lg-3">
                    <div class="p-3 bg-soft-warning rounded h-100 metric-card">
                        <div class="spend-label-12 text-muted">{{ translate('Avg Savings per order') }}</div>
                        <div class="spend-value fw-800 text-dark">{{ single_price($avgSavePerOrder) }}</div>
                    </div>
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
            <div class="table-responsive">
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
            </div>
            <div class="aiz-pagination mt-2">
                {{ $groupedOrders->links() }}
            </div>
        </div>
    </div>
@endsection

@section('style')
<style>
    .spend-hero { background: linear-gradient(135deg, #e8f3ff, #f2f8ff); }
    .spend-hero-card { width: 100%; }
    .spend-label-12 { font-size: 12px; }
    .spend-body-13 { font-size: 13px; }
    .spend-title { font-size: 22px; line-height: 1.3; }
    .spend-amount { font-size: 24px; }
    .spend-value { font-size: 18px; }
    .spend-hero-circle { width: clamp(140px, 48vw, 170px); height: clamp(140px, 48vw, 170px); border: 6px solid #d7ecff; }
    .spend-hero-pill { max-width: 320px; width: auto; }
    .metric-card { min-height: 100%; }
    .table-responsive { overflow-x: auto; }
    @media (max-width: 768px) {
        .spend-title { font-size: 19px; }
        .spend-amount { font-size: 21px; }
        .spend-value { font-size: 16px; }
        .spend-hero-pill { width: 100%; justify-content: center; max-width: none; }
        .spend-hero { padding: 1.5rem 1.25rem; }
    }
    @media (max-width: 480px) {
        .spend-title { font-size: 18px; }
        .spend-amount { font-size: 20px; }
        .spend-value { font-size: 15px; }
        .spend-hero { padding: 1.25rem 1rem; }
    }
</style>
@endsection
