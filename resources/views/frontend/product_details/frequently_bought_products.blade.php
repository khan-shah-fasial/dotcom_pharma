<div class="">
    <div class="">
        <h3 class="fs-20 fs-md-24 fw-500 text-dark text-capitalize pl-md-3">
            <span class="mr-4">{{ translate('Related Products') }}</span>
        </h3>
    </div>
    <div class="">
        <div class="aiz-carousel gutters-5 half-outside-arrow" data-items="4" data-xl-items="4"
            data-lg-items="4" data-md-items="4" data-sm-items="2" data-xs-items="2"
            data-arrows='true' data-infinite='true'>
            @foreach (get_frequently_bought_products($detailedProduct) as $key => $related_product)
                <div class="carousel-box product_listing_box product_img_bg">
                    <div class="aiz-card-box hov-shadow-md my-2 has-transition hov-scale-img h-100 product_listing_box product_img_bg">
                        <div class="">
                            <a href="{{ route('product', $related_product->slug) }}" class="d-block">
                                <img class="img-fit lazyload mx-auto has-transition"
                                    src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                    data-src="{{ uploaded_asset($related_product->thumbnail_img) }}"
                                    alt="{{ $related_product->getTranslation('name') }}"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                            </a>

                            
                        </div>
                        <div class="px-3 pt-2 pb-3 start">
                            <h3 class="   fw-500 fs-16 text-truncate-1 lh-1-4 mb-1">
                                <a href="{{ route('product', $related_product->slug) }}"
                                    class="d-block text-reset hov-text-primary">{{ $related_product->getTranslation('name') }}</a>
                            </h3>

                             @if ($related_product->auction_product != 1 && $related_product->rating > 0)
        <div class="mb-3 d-flex align-items-center gap-2">
            <span class="rating rating-mr-2">
                {{ renderStarRating($related_product->rating) }}
            </span>
            <span class="text-muted fs-6">({{ $related_product->reviews->where('status', 1)->count() }}
                {{ translate('Customer Reviews') }})</span>
        </div>
    @endif

                            <div class="fs-16">
                                <span class="fw-700 text-primary">{{ home_discounted_base_price($related_product) }}</span>
                                @if (home_base_price($related_product) != home_discounted_base_price($related_product))
                                    <del
                                        class="fw-400 text-secondary mr-1 fs-14 d-none">{{ home_base_price($related_product) }}</del>
                                @endif
                            </div>

                            
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>