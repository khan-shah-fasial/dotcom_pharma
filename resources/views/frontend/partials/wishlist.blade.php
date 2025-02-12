<a href="{{ route('wishlists.index') }}" class="d-flex align-items-center text-dark" data-toggle="tooltip" data-title="{{ translate('Wishlist') }}" data-placement="top">
    <span class="position-relative d-inline-block mt-md-1">
       

    
    <img class="wishlist_img" src="{{ static_asset('assets/img/hearts_icons.svg') }}" />
        @if(Auth::check())
            @php $wishlistProductCount = get_wishlists()->count(); @endphp
            @if($wishlistProductCount > 0)
                <span class="badge_icons badge badge-secondary badge-inline badge-pill">{{ $wishlistProductCount}}</span>
            @endif
        @endif
    </span>
</a>
