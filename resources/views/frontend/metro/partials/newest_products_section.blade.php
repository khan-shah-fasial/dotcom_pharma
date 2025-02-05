@if (count($newest_products) > 0)
    <section class="mb-2 mb-md-3 mt-2 mt-md-3">
        <div class="container">
            <div class="row">
                <div class="col-md-9 width_80 pt-5 pr-md-3">
                      <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                <!-- Title -->
                <h3 class="fs-16 fs-md-20 fw-700 mb-2 mb-sm-0">
                    <span class="">{{ translate('New Products1') }}</span>
                </h3>
                <!-- Links -->
                <div class="d-flex">
                    <a type="button" class="arrow-prev slide-arrow link-disable text-secondary mr-2" onclick="clickToSlide('slick-prev','section_newest')"><i class="las la-angle-left fs-20 fw-600"></i></a>
                    <a class="blue_light_clr fs-10 fs-md-12 fw-700 hov-text-primary animate-underline-primary" href="{{ route('search',['sort_by'=>'newest']) }}">{{ translate('View All') }}</a>
                    <a type="button" class="arrow-next slide-arrow text-secondary ml-2" onclick="clickToSlide('slick-next','section_newest')"><i class="las la-angle-right fs-20 fw-600"></i></a>
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
                    <div class="carousel-box px-0 position-relative has-transition @if($key == 0) @endif">
                        @include('frontend.'.get_setting('homepage_select').'.partials.product_box_1',['product' => $new_product])
                    </div>
                    @endforeach
                </div>
  </div>
  <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">Medicine</div>
  <div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">Medicine</div>
</div>

                </div>
                <div class="col-md-3 width_20">
                    <img class="w-100" src="{{ static_asset('assets/img/product_items_photo.png') }}" />
                </div>
            </div>
            <!-- Top Section -->
            
        </div>
    </section>
@endif