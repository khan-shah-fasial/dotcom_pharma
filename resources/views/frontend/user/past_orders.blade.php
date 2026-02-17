@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="card shadow-none rounded-0 border">
        <div class="card-header border-bottom-0 d-flex align-items-center justify-content-between flex-wrap">
            <div>
                <h5 class="mb-0 fs-20 fw-700 text-dark">{{ translate('Ready List') }}</h5>
                <div class="text-muted fs-12">{{ translate('Grouped products you have purchased earlier') }}</div>
            </div>
        </div>
        <div class="card-body">
            <div class="row row-cols-xxl-3 row-cols-xl-3 row-cols-lg-3 row-cols-md-2 row-cols-sm-2 row-cols-1 gutters-16 border-top border-left mx-1 mx-md-0 product_listing_box">
                @forelse ($groupedOrders as $row)
                    @php
                        /** @var \Illuminate\Support\Collection|\App\Models\Product[]|null $productsById */
                        $product = isset($productsById) ? ($productsById[$row->product_id] ?? null) : null;
                    @endphp
                    <div class="col p-0">
                        <div class="h-100 d-flex flex-column border-right border-bottom p-2">
                            @if ($product)
                                {{-- Use customized card with variation label for Ready List --}}
                                @include('frontend.nexgeno.partials.product_box_ready_list', [
                                    'product' => $product,
                                    'variation' => $row->variation ?? null,
                                ])
                            @endif
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
