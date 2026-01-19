@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="card shadow-none rounded-0 border">
        <div class="card-header border-bottom-0 d-flex align-items-center justify-content-between flex-wrap">
            <div>
                <h5 class="mb-0 fs-20 fw-700 text-dark">{{ translate('Past Orders') }}</h5>
                <div class="text-muted fs-12">{{ translate('Grouped products you have purchased earlier') }}</div>
            </div>
        </div>
        <div class="card-body">
            <div class="row row-cols-xxl-3 row-cols-xl-3 row-cols-lg-3 row-cols-md-2 row-cols-sm-2 row-cols-1 gutters-16 border-top border-left mx-1 mx-md-0 product_listing_box">
                @forelse ($groupedOrders as $row)
                    @php
                        $productName = $row->product_name ?? translate('Product unavailable');
                        $productSlug = $row->product_slug ?? null;
                        $thumb = $row->product_thumbnail ? uploaded_asset($row->product_thumbnail) : static_asset('assets/img/placeholder.jpg');
                        $spend = $row->total_sale ?? $row->total_price ?? 0;
                        $mrpTotal = $row->total_mrp ?? 0;
                        $saved = max($mrpTotal - $spend, 0);
                        $lastOrdered = $row->last_purchase_at ? date('d-m-Y', strtotime($row->last_purchase_at)) : '-';
                        $variant = $row->variation ?: translate('Default');
                    @endphp
                    <div class="col p-0">
                        <div class="aiz-card-box h-100 d-flex flex-column p-3 border-right border-bottom">
                            <div class="position-relative img-fit overflow-hidden mb-3 product_img_bg">
                                @if ($productSlug)
                                    <a href="{{ route('product', $productSlug) }}" class="d-block h-100">
                                        <img src="{{ $thumb }}"
                                            class="mx-auto img-fit lazyload h-140px"
                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';"
                                            alt="{{ $productName }}">
                                    </a>
                                @else
                                    <span class="d-block h-100">
                                        <img src="{{ $thumb }}"
                                            class="mx-auto img-fit lazyload h-140px"
                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';"
                                            alt="{{ $productName }}">
                                    </span>
                                @endif
                            </div>
                            <div class="flex-grow-1 d-flex flex-column">
                                <h5 class="fw-600 fs-16 mb-1 lh-1-5 text-truncate-2">
                                    @if ($productSlug)
                                        <a href="{{ route('product', $productSlug) }}" class="text-reset hov-text-primary" title="{{ $productName }}">
                                            {{ $productName }}
                                        </a>
                                    @else
                                        <span class="text-reset" title="{{ $productName }}">{{ $productName }}</span>
                                    @endif
                                </h5>
                                <div class="text-muted fs-13 mb-2">{{ translate('Variant') }}: {{ $variant }}</div>
                                <div class="d-flex justify-content-between text-secondary fs-13 mb-2">
                                    <span>{{ translate('Last ordered') }}</span>
                                    <span class="fw-600">{{ $lastOrdered }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2 mt-auto border-top">
                                    <div>
                                        <div class="text-uppercase fs-11 text-secondary">{{ translate('Qty') }}</div>
                                        <div class="fw-700">{{ $row->total_qty }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-uppercase fs-11 text-secondary">{{ translate('Spent') }}</div>
                                        <div class="fw-700">{{ single_price($spend) }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-uppercase fs-11 text-secondary">{{ translate('Saved') }}</div>
                                        <div class="fw-700 text-success">{{ single_price($saved) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 p-0">
                        <div class="text-center bg-white p-4 border">
                            <img class="mw-100 h-200px" src="{{ static_asset('assets/img/nothing.svg') }}" alt="Image">
                            <h5 class="mb-0 h5 mt-3 text-muted">{{ translate('No past orders found') }}</h5>
                        </div>
                    </div>
                @endforelse
            </div>
            <div class="aiz-pagination mt-3">
                {{ $groupedOrders->links() }}
            </div>
        </div>
    </div>
@endsection
