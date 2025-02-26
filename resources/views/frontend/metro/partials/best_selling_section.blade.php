@php
    $best_selling_products = get_best_selling_products(20);
@endphp
@if (get_setting('best_selling') == 1 && count($best_selling_products) > 0)
    <section class="pt-4 pt-md-5 pb-0 pb-md-4">
        <div class="container">
            <!-- Top Section -->
            <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                
                <!-- Title -->
                <h3 class="fs-16 fs-md-24 fw-600 mb-2 mb-md-2 ">
                    <span class="">{{ translate('Trending Items') }}</span>
                    <div class="heading_border red_bg1"></div>
                </h3>
                
                <!-- Links -->
                <div class="d-flex">
                        <a class="blue_light_clr fs-16 fw-400 hov-text-primary animate-underline-primary" href="{{ route('search',['sort_by'=>'newest']) }}">{{ translate('View More') }} <i class="las la-angle-double-right"></i></a>
                    </div>
            </div>
            <!-- Product Section -->
            <div class="px-sm-3">
                <div class="aiz-carousel sm-gutters-16 arrow-none" data-items="5" data-xl-items="5" data-lg-items="4"  data-md-items="3" data-sm-items="2" data-xs-items="2" data-arrows='true' data-infinite='false'>
                    @foreach ($best_selling_products as $key => $product)
                        <div class="carousel-box px-0 position-relative has-transition product_listing_box @if($key == 0) @endif">
                            @include('frontend.'.get_setting('homepage_select').'.partials.product_box_1',['product' => $product])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
