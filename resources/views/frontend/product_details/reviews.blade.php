<div class="py-3 reviews-area">
    <ul class="list-group list-group-flush">
        @foreach ($reviews as $key => $review)
            @php
                $customerName = null;
                $customerAvatar = null;
                if($review->type == "real"){
                    $customerName = $review->user != null ? $review->user->name : translate('Use is Not Available');
                    $customerAvatar = $review->user != null ? uploaded_asset($review->user->avatar_original) : static_asset('assets/img/placeholder.jpg');
                }
                else{
                    $customerName = $review->custom_reviewer_name;
                    $customerAvatar = uploaded_asset($review->custom_reviewer_image);
                }
            @endphp
            <li class="media list-group-item d-flex px-3 px-md-4 border-0">
                <!-- Review User Image -->
                <span class="avatar avatar-md mr-3">
                    <img class="lazyload"
                        src="{{ static_asset('assets/img/placeholder.jpg') }}"
                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';"
                            data-src="{{ $customerAvatar }}"
                        >
                </span>
                <div class="media-body text-left">
                    <!-- Review User Name -->
                    <h3 class="fs-15 fw-600 mb-0">{{ $customerName }}
                    </h3>
                    <!-- Review Date -->
                    <div class="opacity-60 mb-1">
                        {{ date('d-m-Y', strtotime($review->created_at)) }}
                    </div>
                    <!-- Review ratting -->
                    <span class="rating rating-mr-2">
                        @for ($i = 0; $i < $review->rating; $i++)
                            <i class="las la-star active"></i>
                        @endfor
                        @for ($i = 0; $i < 5 - $review->rating; $i++)
                            <i class="las la-star"></i>
                        @endfor
                    </span>
                    <!-- Review Comment -->
                    <p class="comment-text mt-2 fs-14">
                        {{ $review->comment }}
                    </p>
                    <!-- Review Images -->
                    <div class="spotlight-group d-flex flex-wrap">
                        @if($review->photos != null)
                            @foreach (explode(',', $review->photos) as $photo)
                            <a class="spotlight mr-2 mr-md-3 mb-2 mb-md-3 size-60px size-md-90px border overflow-hidden has-transition hov-scale-img hov-border-primary" href="{{ uploaded_asset($photo) }}">
                                <img class="img-fit h-100 lazyload has-transition"
                                        src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                        data-src="{{ uploaded_asset($photo) }}"
                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                            </a>
                            @endforeach
                        @endif
                    </div>
                    <!-- Variation -->
                    @php
                        $OrderDetail = get_order_details_by_review($review);
                    @endphp
                    @if ($OrderDetail && $OrderDetail->variation)
                        <small class="text-secondary fs-12">{{ translate('Variation :') }} {{ $OrderDetail->variation }}</small>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>

    @if (count($reviews) <= 0)
        <div class="text-center fs-18 opacity-70">
            {{ translate('There have been no reviews for this product yet.') }}
        </div>
    @endif

    <!-- Pagination -->
    <div class="aiz-pagination product-reviews-pagination py-2 px-4 d-flex justify-content-end">
        {{ $reviews->links() }}
    </div>
</div>
