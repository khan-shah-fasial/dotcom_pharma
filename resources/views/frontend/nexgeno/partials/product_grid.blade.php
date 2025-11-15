<div class="px-md-3">
    @php
        $rowClasses = 'row row-cols-xxl-3 row-cols-xl-3 row-cols-lg-3 row-cols-md-3 row-cols-2';

        if($products->isEmpty()){
            $rowClasses .= 'row row-cols-xxl-3 row-cols-xl-3 row-cols-lg-3 row-cols-md-3 row-cols-2 d-flex justify-content-center';
        }
    @endphp

    <div class="{{ $rowClasses }}">
        @forelse($products as $product)
            <div class="col has-transition z-1 product_listing_box">
                @include('frontend.'.get_setting('homepage_select').'.partials.product_box_1', ['product'=>$product])
            </div>
        @empty
            <div class="col-12 py-5 text-center text-muted d-flex justify-content-center">
                <h5 style="line-height: 1.5">{{ translate('No Products Found For The Selected Filters.') }}</h5>
            </div>
        @endforelse
    </div>
</div>
