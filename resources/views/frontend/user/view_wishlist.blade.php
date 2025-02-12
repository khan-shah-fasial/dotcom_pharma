@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <b class="fs-20 fw-700 text-dark">{{ translate('Wishlist')}}</b>
            </div>
        </div>
    </div>

    @if (count($wishlists) > 0)
        <div class="row row-cols-xxl-3 row-cols-xl3 row-cols-lg-3 row-cols-md-3 row-cols-sm-2 row-cols-2 gutters-16 border-top border-left mx-1 mx-md-0 mb-4 product_listing_box">
            @foreach($wishlists as $key => $wishlist)
                <div class="aiz-card-box col p-0 text-center has-transition z-1 product_img_bg" id="wishlist_{{ $wishlist->id }}">
                    <div class="position-relative img-fit overflow-hidden mb-3 ">
                        <!-- Image -->
                        <a href="{{ route('product', $wishlist->product->slug) }}" class="d-block h-100">
                            <img src="{{ uploaded_asset($wishlist->product->thumbnail_img) }}" class="lazyload mx-auto img-fit"
                                title="{{ $wishlist->product->getTranslation('name') }}">
                        </a>
                        <!-- Remove from wishlisht -->
                        <div class="absolute-top-right aiz-p-hov-icon">
                            <a href="javascript:void(0)" onclick="removeFromWishlist({{ $wishlist->id }})" data-toggle="tooltip" data-title="{{ translate('Remove from wishlist') }}" data-placement="left">
                                <i class="la la-trash"></i>
                            </a>
                        </div>
                       
                    </div>



                    <div class="product_box_mains">
   
    <div class="flex_boxex text-left">
        <!-- Product Name -->
        <h5 class="fw-500 fs-16 mb-0 lh-1-5 text-truncate-2 mb-0">
            <a href="{{ route('product', $wishlist->product->slug) }}" class="text-reset hov-text-primary"
                title="{{ $wishlist->product->getTranslation('name') }}">{{ $wishlist->product->getTranslation('name') }}</a>
        </h5>

        <div class="listing_rating rating rating-mr-1">
                    <i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i><i class="las la-star"></i>
                </div>

        <!-- Price -->
                    <div class="fs-16">
                        <span class="fw-600 text-primary">{{ home_discounted_base_price($wishlist->product) }}</span>
                        @if(home_base_price($wishlist->product) != home_discounted_base_price($wishlist->product))
                            <del class="opacity-60 ml-1">{{ home_base_price($wishlist->product) }}</del>
                        @endif
                    </div>
    </div>

     <div class="flex_boxex">
        <!-- add to cart -->
       <a class=""  href="javascript:void(0)" onclick="showAddToCartModal({{ $wishlist->product->id }})"> <span><i class="las la-shopping-bag la-2x"></i></span></a>
    </div>
        
    </div>


                    
                    
                </div>
            @endforeach
        </div>
    @else
        <div class="row">
            <div class="col">
                <div class="text-center bg-white p-4 border">
                    <img class="mw-100 h-200px" src="{{ static_asset('assets/img/nothing.svg') }}" alt="Image">
                    <h5 class="mb-0 h5 mt-3">{{ translate("There isn't anything added yet")}}</h5>
                </div>
            </div>
        </div>
    @endif
    <!-- Pagination -->
    <div class="aiz-pagination">
        {{ $wishlists->links() }}
    </div>
@endsection

@section('modal')
    <!-- add To Cart Modal -->
    <div class="modal fade" id="addToCart" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-zoom product-modal" id="modal-size" role="document">
            <div class="modal-content position-relative">
                <div class="c-preloader">
                    <i class="fa fa-spin fa-spinner"></i>
                </div>
                <button type="button" class="close absolute-close-btn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div id="addToCart-modal-body">

                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script type="text/javascript">
        function removeFromWishlist(id){
            $.post('{{ route('wishlists.remove') }}',{_token:'{{ csrf_token() }}', id:id}, function(data){
                $('#wishlist').html(data);
                $('#wishlist_'+id).hide();
                AIZ.plugins.notify('success', '{{ translate("Item has been renoved from wishlist") }}');
            })
        }
    </script>
@endsection
