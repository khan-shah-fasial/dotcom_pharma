<div class="px-md-3">
  <div class="row row-cols-xxl-3 row-cols-xl-3 row-cols-lg-3 row-cols-md-3 row-cols-2">
    @forelse($products as $product)
      <div class="col has-transition z-1 product_listing_box">
        @include('frontend.'.get_setting('homepage_select').'.partials.product_box_1', ['product'=>$product])
      </div>
    @empty
      <div class="col-12 py-5 text-center text-muted">
        {{ translate('No products found for the selected filters.') }}
      </div>
    @endforelse
  </div>
</div>
