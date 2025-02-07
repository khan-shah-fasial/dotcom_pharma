<a href="{{ route('wishlists.index') }}" class="d-flex align-items-center text-dark" data-toggle="tooltip" data-title="{{ translate('Wishlist') }}" data-placement="top">
    <span class="position-relative d-inline-block">
       
       <i class="las la-heart la-2x" style="color:#23780E;"></i>
        @if(Auth::check())
            @php $wishlistProductCount = get_wishlists()->count(); @endphp
            @if($wishlistProductCount > 0)
                <span class="d-none badge_icons badge badge-secondary badge-inline badge-pill">{{ $wishlistProductCount}}</span>
            @endif
        @endif
    </span>
</a>
