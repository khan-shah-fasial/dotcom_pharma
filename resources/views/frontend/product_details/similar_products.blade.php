<div class="col-12 pl-0 ps-0 pe-0 mt-md-4 mt-3 p-">
    <div class="col-12 p-0">
        <h3 class="fs-20 fs-md-24 fw-500 text-dark text-capitalize pl-md-0">
            <span class="">{{ translate('Similar Products') }}:</span>
        </h3>
    </div>
    <div class="col-12 p-0">
        @php $similarProducts = $similarProducts ?? get_similar_products($detailedProduct, 10); @endphp
        <div class="aiz-carousel similar-products-carousel" data-items="6" data-xl-items="6"
            data-lg-items="6" data-md-items="6" data-sm-items="4" data-xs-items="2"
            data-arrows='false' data-infinite='true'>
            @foreach ($similarProducts as $key => $similar_product)
                <div class="carousel-box product_listing_box related_product_boxex product_img_bg">
                    <div class="aiz-card-box hov-shadow-md my-2 has-transition hov-scale-img h-100 product_listing_box product_img_bg">
                        <div class="">
                            <a href="{{ route('product', $similar_product->slug) }}" class="d-block">
                                <img class="img-fit lazyload mx-auto has-transition"
                                    src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                    data-src="{{ uploaded_asset($similar_product->thumbnail_img) }}"
                                    alt="{{ $similar_product->getTranslation('name') }}"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                            </a>
                        </div>
                        <div class="px-3 pt-2 pb-3 start">
                            <h3 class="fw-500 fs-14 text-truncate-1 lh-1-4 mb-1">
                                <a href="{{ route('product', $similar_product->slug) }}"
                                    class="d-block text-reset hov-text-primary">{{ $similar_product->getTranslation('name') }}</a>
                            </h3>

                            @if ($similar_product->auction_product != 1 && $similar_product->rating > 0)
                                <div class="mb-3 d-flex align-items-center gap-2">
                                    <span class="rating rating-mr-2">
                                        {{ renderStarRating($similar_product->rating) }}
                                    </span>
                                    <span class="text-muted fs-6">
                                        ({{ $similar_product->reviews->where('status', 1)->count() }} {{ translate('Customer Reviews') }})
                                    </span>
                                </div>
                            @endif

                            <div class="fs-14">
                                <span class="fw-700 text-primary">{{ home_discounted_base_price($similar_product) }}</span>
                                @if (home_base_price($similar_product) != home_discounted_base_price($similar_product))
                                    <del class="fw-400 text-secondary mr-1 fs-14 d-none">{{ home_base_price($similar_product) }}</del>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
