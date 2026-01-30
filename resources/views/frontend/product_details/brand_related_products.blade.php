<div class="col-12 p-0">
    <div class="">
        <h3 class="fs-18 fs-md-18 fw-500 text-dark text-capitalize pl-md-0">
            <span class="">
                {{ translate('More From') }} {{ optional($detailedProduct->main_category)->getTranslation('name') }}
            </span>
        </h3>
    </div>
    <div class="">
        @php $brandCategoryProducts = $brandCategoryProducts ?? get_brand_related_products($detailedProduct); @endphp
        <div class="aiz-carousel brand-related-products-carousel gutters-5 half-outside-arrow" data-items="8" data-xl-items="8"
            data-lg-items="8" data-md-items="8" data-sm-items="6" data-xs-items="2"
            data-arrows='true' data-infinite='true'>
            @foreach ($brandCategoryProducts as $key => $brand_product)
                <div class="carousel-box product_listing_box product_img_bg related_bottom_section ">
                    <div class="aiz-card-box hov-shadow-md my-2 has-transition hov-scale-img h-100 product_listing_box product_img_bg">
                        <div class="">
                            <a href="{{ route('product', $brand_product->slug) }}" class="d-block">
                                <img class="img-fit lazyload mx-auto has-transition"
                                    src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                    data-src="{{ uploaded_asset($brand_product->thumbnail_img) }}"
                                    alt="{{ $brand_product->getTranslation('name') }}"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                            </a>
                        </div>
                        <div class="px-3 pt-2 pb-3 start">
                            <h3 class="fw-400 fs-13 text-truncate-1 lh-1-4 mb-1">
                                <a href="{{ route('product', $brand_product->slug) }}"
                                    class="d-block text-reset hov-text-primary">{{ $brand_product->getTranslation('name') }}</a>
                            </h3>

                            @if ($brand_product->auction_product != 1 && $brand_product->rating > 0)
                                <div class="mb-3 d-flex align-items-center gap-2">
                                    <span class="rating rating-mr-2">
                                        {{ renderStarRating($brand_product->rating) }}
                                    </span>
                                    <span class="text-muted fs-6">
                                        ({{ $brand_product->reviews->where('status', 1)->count() }} {{ translate('Customer Reviews') }})
                                    </span>
                                </div>
                            @endif

                            <div class="fs-14">
                                <span class="fw-700 text-primary">{{ home_discounted_base_price($brand_product) }}</span>
                                @if (home_base_price($brand_product) != home_discounted_base_price($brand_product))
                                    <del class="fw-400 text-secondary mr-1 fs-14 d-none">{{ home_base_price($brand_product) }}</del>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
