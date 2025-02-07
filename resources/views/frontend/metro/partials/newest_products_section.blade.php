@if (count($newest_products) > 0)
    <section class="pt-4 pt-md-3 pb-3 pb-md-3">
        <div class="container">
            <div class="row">
                <div class="col-md-9 width_80 pt-4 pr-md-4">
                 <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                    <!-- Title -->
                    <h3 class="fs-16 fs-md-24 fw-600 mb-2 mb-md-2">
                        <span class="">{{ translate('Popular Items') }}</span>
                        <div class="heading_border blue_bg1"></div>
                    </h3>
                    
                    <!-- Links -->
                    <div class="d-flex">
                        <a class="blue_light_clr fs-16 fw-400 hov-text-primary animate-underline-primary" href="{{ route('search',['sort_by'=>'newest']) }}">{{ translate('View More') }} <i class="las la-angle-double-right"></i></a>
                    </div>
                 </div>
            <!-- Products Section -->

            <ul class="nav nav-pills mb-3 tabs_products" id="pills-tab" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="pills-home-tab" data-toggle="pill" data-target="#pills-home" type="button" role="tab" aria-controls="pills-home" aria-selected="true">Medicine</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="pills-profile-tab" data-toggle="pill" data-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile" aria-selected="false">Medicine</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="pills-contact-tab" data-toggle="pill" data-target="#pills-contact" type="button" role="tab" aria-controls="pills-contact" aria-selected="false">Medicine</button>
  </li>
</ul>
<div class="tab-content" id="pills-tabContent">
  <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
      <div class="aiz-carousel arrow-none sm-gutters-16" data-items="4" data-xl-items="4" data-lg-items="4"  data-md-items="3" data-sm-items="2" data-xs-items="2" data-arrows='true' data-infinite='false'>
                    @foreach ($newest_products as $key => $new_product)
                    <div class="carousel-box px-0 position-relative has-transition product_listing_box @if($key == 0) @endif">
                        @include('frontend.'.get_setting('homepage_select').'.partials.product_box_1',['product' => $new_product])
                    </div>
                    @endforeach
                </div>
  </div>
  <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">Medicine</div>
  <div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">Medicine</div>
</div>

                </div>
                <div class="col-md-3 width_20 mt-4">
                    <img class="w-100" src="{{ static_asset('assets/img/product_items_photo.png') }}" />
                </div>
            </div>
            <!-- Top Section -->
            
        </div>
    </section>
@endif