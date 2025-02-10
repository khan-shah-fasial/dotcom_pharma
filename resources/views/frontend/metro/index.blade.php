@extends('frontend.layouts.app')

@section('content')
    <style>
        #section_featured .slick-slider .slick-list{
            background: #fff;
        }
        #section_featured .slick-slider .slick-list .slick-slide {
            margin-bottom: -5px;
        }
        @media (max-width: 575px){
            #section_featured .slick-slider .slick-list .slick-slide {
                margin-bottom: -4px;
            }
        }
    </style>

    @php $lang = get_system_language()->code;  @endphp

    <!-- Sliders -->
    <div class="home-banner-area mb-3">
        <div class="p-0">
            <!-- Sliders -->
            <div class="home-slider slider-full">
                @if (get_setting('home_slider_images', null, $lang) != null)
                    <div class="aiz-carousel dots-inside-bottom mobile-img-auto-height" data-autoplay="true" data-infinite="true">
                        @php
                            $decoded_slider_images = json_decode(get_setting('home_slider_images', null, $lang), true);
                            $sliders = get_slider_images($decoded_slider_images);
                            $home_slider_links = get_setting('home_slider_links', null, $lang);
                        @endphp
                        @foreach ($sliders as $key => $slider)
                            <div class="carousel-box">
                                <a href="{{ isset(json_decode($home_slider_links, true)[$key]) ? json_decode($home_slider_links, true)[$key] : '' }}">
                                    <!-- Image -->
                                    <div class="d-block mw-100 img-fit overflow-hidden overflow-hidden">
                                        <img class="img-fit  m-auto has-transition ls-is-cached lazyloaded"
                                        src="{{ $slider ? my_asset($slider->file_name) : static_asset('assets/img/placeholder.jpg') }}"
                                        alt="{{ env('APP_NAME') }} promo"
                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>


   <section>
       <div class="container">
        <div class="searc_box_product">
               <form action="{{ route('search') }}" method="GET" class="stop-propagation">
                    <div class="row">
                          <div class="col-md-12"><h3>Search Product</h3></div>

                          <div class="col-md-3">
                              <div class="form-group">
                                 <select class="form-control form-select" aria-label="Default select example">
                                    <option selected>All Category</option>
                                    <option value="1">Ointments</option>
                                    <option value="2">Sprays</option>
                                    <option value="3">External Insecticide</option>
                                  </select>
                              </div>
                          </div>

                          <div class="col-md-3">
                              <div class="form-group">
                                 <select class="form-control form-select" aria-label="Default select example">
                                    <option selected>All Brand</option>
                                    <option value="1">Ointments</option>
                                    <option value="2">Sprays</option>
                                    <option value="3">External Insecticide</option>
                                  </select>
                              </div>
                          </div>

                           <div class="col-md-4">
                              <div class="form-group">
                               <input type="text" name="" class="form-control" placeholder="Enter Key..." />
                              </div>
                          </div>

                           <div class="col-md-2">
                              <div class="form-group">
                                 <button value="Search" type="button" class="animate_button black1_buttons" > <i class="las la-search la-flip-horizontal la-1x" style="color:#fff;"></i>Search</button>
                              </div>
                          </div>

                    </div>
              </form>
        </div>
       </div>
   </section>


    <!-- Featured Categories -->
    @if (count($featured_categories) > 0)
        <section class="mb-4 mb-md-5 mt-4 mt-md-5">
            <div class="container">
                <div class="bg-white">
                    <!-- Top Section -->
                    <div class="text-center">
                        <!-- Title -->
                        <h3 class="fs-16 fs-md-24 text-center fw-700 pb-4 pt-0">
                            <span class="">{{ translate('Top Category') }}</span>
                        </h3>
                    </div>
                </div>
                <!-- Categories -->
                <div class="bg-white px-sm-3">
                    <div class="aiz-carousel sm-gutters-17" data-items="7" data-xxl-items="7" data-xl-items="7"
                        data-lg-items="7" data-md-items="5" data-sm-items="2" data-xs-items="1" data-arrows="true"
                        data-dots="false" data-autoplay="false" data-infinite="true">
                        @foreach ($featured_categories as $key => $category)
                            @php
                                $category_name = $category->getTranslation('name');
                            @endphp
                            <div class="carousel-box position-relative p-0 has-transition @if ($key == 0) @endif">
                                <div class="category_boxex_main">
                                    <div class="w-xl-auto position-relative hov-scale-img overflow-hidden">

                                        <div class="category_borders">
                                        <div class="category_images">
                                            <img src="{{ isset($category->coverImage->file_name) ? my_asset($category->coverImage->file_name) : static_asset('assets/img/placeholder.jpg') }}"
                                                alt="{{ $category_name }}"
                                                class="img-fit has-transition"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                        </div>
                                        </div>
                                        <div class="w-100 d-flex flex-column align-items-center justify-content-end">
                                            <div class="w-100 text-center pt-3">
                                                <a class="w-100 fs-18 fw-500 black_light_clr animate-underline-white home-category-name d-flex align-items-center justify-content-center hov-column-gap-1"
                                                    href="{{ route('products.category', $category->slug) }}"
                                                    >
                                                    {{ $category_name }}&nbsp;
                                                    <!-- <i class="las la-angle-right"></i> -->
                                                </a>
                                                <div class="d-none">
                                                    @foreach ($category->childrenCategories->take(6) as $key => $child_category)
                                                    <a href="{{ route('products.category', $child_category->slug) }}" class="fs-13 fw-300 black_light_clr hov-text-white pr-3 pt-1">
                                                        {{ $child_category->getTranslation('name') }}
                                                    </a>
                                                    @endforeach
                                                </div>
                                                <p class="fs-14 fw-500 blue_light_clr">30 Items</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- Banner section 1 -->
    @php $homeBanner1Images = get_setting('home_banner1_images', null, $lang);   @endphp
    @if ($homeBanner1Images != null)
        <div class="pb-4">
            <div class="container">
                @php
                    $banner_1_imags = json_decode($homeBanner1Images);
                    $data_md = count($banner_1_imags) >= 2 ? 2 : 1;
                    $home_banner1_links = get_setting('home_banner1_links', null, $lang);
                @endphp
                <div class="w-100">
                    <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
                        data-items="{{ count($banner_1_imags) }}" data-xxl-items="{{ count($banner_1_imags) }}"
                        data-xl-items="{{ count($banner_1_imags) }}" data-lg-items="{{ $data_md }}"
                        data-md-items="{{ $data_md }}" data-sm-items="1" data-xs-items="1" data-arrows="true"
                        data-dots="false">
                        @foreach ($banner_1_imags as $key => $value)
                            <div class="carousel-box overflow-hidden">
                                <a href="{{ isset(json_decode($home_banner1_links, true)[$key]) ? json_decode($home_banner1_links, true)[$key] : '' }}"
                                    class="d-block text-reset overflow-hidden">
                                    <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                        data-src="{{ uploaded_asset($value) }}" alt="{{ env('APP_NAME') }} promo"
                                        class="img-fluid lazyload w-100 has-transition"
                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Featured Products -->
    <div id="section_featured">

    </div>

    <!-- Banner Section 2 -->
    @php $homeBanner2Images = get_setting('home_banner2_images', null, $lang);   @endphp
    @if ($homeBanner2Images != null)
        <div class="pt-3">
            <div class="container">
                @php
                    $banner_2_imags = json_decode($homeBanner2Images);
                    $data_md = count($banner_2_imags) >= 2 ? 2 : 1;
                    $home_banner2_links = get_setting('home_banner2_links', null, $lang);
                @endphp
                <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
                    data-items="{{ count($banner_2_imags) }}" data-xxl-items="{{ count($banner_2_imags) }}"
                    data-xl-items="{{ count($banner_2_imags) }}" data-lg-items="{{ $data_md }}"
                    data-md-items="{{ $data_md }}" data-sm-items="1" data-xs-items="1" data-arrows="true"
                    data-dots="false">
                    @foreach ($banner_2_imags as $key => $value)
                        <div class="carousel-box overflow-hidden hov-scale-img">
                            <a href="{{ isset(json_decode($home_banner2_links, true)[$key]) ? json_decode($home_banner2_links, true)[$key] : '' }}" style="border-radius:20px;"
                                class="d-block text-reset overflow-hidden">
                                <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                    data-src="{{ uploaded_asset($value) }}" alt="{{ env('APP_NAME') }} promo"
                                    class="img-fluid lazyload w-100 has-transition"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    
    <!-- Best Selling  -->
    <div id="section_best_selling">

    </div>



    <section class="pt-2 pb-3">
       <div class="container">
        <div class="payment_safe_secrtion">
                    <div class="row">
                          <div class="col-md-3">
                              <div class="payment_boxs align-items-center gap-3">
                                   <img class="" src="{{ static_asset('assets/img/free_delivery_icons.svg') }}" />
                                   <div class="">
                                      <h6 class="text-white mb-1 fw-500 mb-0">Free Delivery</h6>
                                      <p class="text-white mb-0 pb-0 fs-14 fw-400">Order Over 250.00</p>
                                   </div>
                              </div>
                          </div>

                          <div class="col-md-3">
                              <div class="payment_boxs align-items-center gap-3">
                                   <img src="{{ static_asset('assets/img/refund_icons.svg') }}" />
                                   <div class="">
                                       <h6 class="text-white mb-1 fw-500 mb-0">Get Refund</h6>
                                      <p class="text-white mb-0 pb-0 fs-14 fw-400">Within 30 Days Return</p>
                                   </div>
                              </div>
                          </div>

                           <div class="col-md-3">
                              <div class="payment_boxs align-items-center gap-3">
                                   <img src="{{ static_asset('assets/img/safe_payment_icons.svg') }}" />
                                   <div class="">
                                       <h6 class="text-white mb-1 fw-500 mb-0">Safe Payment</h6>
                                      <p class="text-white mb-0 pb-0 fs-14 fw-400">100% Secure Payment</p>
                                   </div>
                              </div>
                          </div>

                            <div class="col-md-3">
                              <div class="payment_boxs align-items-center gap-3">
                                   <img src="{{ static_asset('assets/img/support_icons.svg') }}" />
                                   <div class="">
                                      <h6 class="text-white mb-1 fw-500 mb-0">24/7 Support</h6>
                                      <p class="text-white mb-0 pb-0 fs-14 fw-400">Feel Free To Call Us</p>
                                   </div>
                              </div>
                          </div>

                    </div>
        </div>
       </div>
   </section>

  
    <!-- New Products -->
    <div id="section_newest">

    </div>

     <!-- Banner Section 3 -->
    @php $homeBanner3Images = get_setting('home_banner3_images', null, $lang);   @endphp
    @if ($homeBanner3Images != null)
        <div class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="container">
                @php
                    $banner_3_imags = json_decode($homeBanner3Images);
                    $data_md = count($banner_3_imags) >= 2 ? 2 : 1;
                    $home_banner3_links = get_setting('home_banner3_links', null, $lang);
                @endphp
                <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
                    data-items="{{ count($banner_3_imags) }}" data-xxl-items="{{ count($banner_3_imags) }}"
                    data-xl-items="{{ count($banner_3_imags) }}" data-lg-items="{{ $data_md }}"
                    data-md-items="{{ $data_md }}" data-sm-items="1" data-xs-items="1" data-arrows="true"
                    data-dots="false">
                    @foreach ($banner_3_imags as $key => $value)
                        <div class="carousel-box overflow-hidden">
                            <a href="{{ isset(json_decode($home_banner3_links, true)[$key]) ? json_decode($home_banner3_links, true)[$key] : '' }}"
                                class="d-block text-reset overflow-hidden">
                                <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                    data-src="{{ uploaded_asset($value) }}" alt="{{ env('APP_NAME') }} promo"
                                    class="img-fluid lazyload w-100 has-transition"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif



      <!-- Today's deal -->
    @php
        $todays_deal_section_bg = get_setting('todays_deal_section_bg_color');
    @endphp
    <div id="todays_deal" class="mt-4" @if(get_setting('todays_deal_section_bg') == 1) style="background: {{ $todays_deal_section_bg }};" @endif>

    </div>


    <section class="sale_section">
            <img class="w-100" src="{{ static_asset('assets/img/video_img_sec.webp') }}" />
            <div class="container">
                <div class="row">
                    <div class="col-md-4">
                        <div class="sale_box_main">
                        @php
                            $best_selling_products = get_best_selling_products(20);
                        @endphp
                        @if (get_setting('best_selling') == 1 && count($best_selling_products) > 0)
                            <section class="mb-2 mb-md-3 mt-2 mt-md-3">
                                <div class="container">
                                    <!-- Top Section -->
                                    <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                                        <!-- Title -->
                                        <h3 class="sale_heading sale_blue_a">
                                            <span class="">{{ translate('On Sale') }}</span>
                                        </h3>
                                        <!-- Links -->
                                        <!-- <div class="d-flex">
                                            <a type="button" class="arrow-prev slide-arrow link-disable text-secondary mr-2" onclick="clickToSlide('slick-prev','section_best_selling')"><i class="las la-angle-left fs-20 fw-600"></i></a>
                                            <a type="button" class="arrow-next slide-arrow text-secondary ml-2" onclick="clickToSlide('slick-next','section_best_selling')"><i class="las la-angle-right fs-20 fw-600"></i></a>
                                        </div> -->
                                    </div>
                                    <!-- Product Section -->
                                    <div class="px-sm-3">
                                        <div class="aiz-carousel sm-gutters-16 arrow-none vertical-slider" data-items="3" data-xl-items="3" data-lg-items="1"  data-md-items="3" data-sm-items="2" data-xs-items="2" data-arrows='true' data-infinite='false' data-vertical="true">
                                            @foreach ($best_selling_products as $key => $product)
                                                <div class="carousel-box px-0 position-relative has-transition @if($key == 0) @endif">
                                                    @include('frontend.'.get_setting('homepage_select').'.partials.product_box_1',['product' => $product])
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </section>
                        @endif
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="sale_box_main sales_box_gren">
                        @php
                            $best_selling_products = get_best_selling_products(20);
                        @endphp
                        @if (get_setting('best_selling') == 1 && count($best_selling_products) > 0)
                            <section class="mb-2 mb-md-3 mt-2 mt-md-3">
                                <div class="container">
                                    <!-- Top Section -->
                                    <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                                        <!-- Title -->
                                        <h3 class="sale_heading sale_green_a">
                                            <span class="">{{ translate('Best Seller') }}</span>
                                        </h3>
                                        <!-- Links -->
                                        <!-- <div class="d-flex">
                                            <a type="button" class="arrow-prev slide-arrow link-disable text-secondary mr-2" onclick="clickToSlide('slick-prev','section_best_selling')"><i class="las la-angle-left fs-20 fw-600"></i></a>
                                            <a type="button" class="arrow-next slide-arrow text-secondary ml-2" onclick="clickToSlide('slick-next','section_best_selling')"><i class="las la-angle-right fs-20 fw-600"></i></a>
                                        </div> -->
                                    </div>
                                    <!-- Product Section -->
                                    <div class="px-sm-3">
                                        <div class="aiz-carousel sm-gutters-16 arrow-none vertical-slider" data-items="3" data-xl-items="3" data-lg-items="1"  data-md-items="3" data-sm-items="2" data-xs-items="2" data-arrows='true' data-infinite='false' data-vertical="true">
                                            @foreach ($best_selling_products as $key => $product)
                                                <div class="carousel-box px-0 position-relative has-transition @if($key == 0) @endif">
                                                    @include('frontend.'.get_setting('homepage_select').'.partials.product_box_1',['product' => $product])
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </section>
                        @endif
                          </div>
                    </div>
                   
                    <div class="col-md-4">
                        <div class="sale_box_main">
                        @php
                            $best_selling_products = get_best_selling_products(20);
                        @endphp
                        @if (get_setting('best_selling') == 1 && count($best_selling_products) > 0)
                            <section class="mb-2 mb-md-3 mt-2 mt-md-3">
                                <div class="container">
                                    <!-- Top Section -->
                                    <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                                        <!-- Title -->
                                        <h3 class="sale_heading sale_blue_a">
                                            <span class="">{{ translate('Top Rated') }}</span>
                                        </h3>
                                        <!-- Links -->
                                        <!-- <div class="d-flex">
                                            <a type="button" class="arrow-prev slide-arrow link-disable text-secondary mr-2" onclick="clickToSlide('slick-prev','section_best_selling')"><i class="las la-angle-left fs-20 fw-600"></i></a>
                                            <a type="button" class="arrow-next slide-arrow text-secondary ml-2" onclick="clickToSlide('slick-next','section_best_selling')"><i class="las la-angle-right fs-20 fw-600"></i></a>
                                        </div> -->
                                    </div>
                                    <!-- Product Section -->
                                    <div class="px-sm-3">
                                        <div class="aiz-carousel sm-gutters-16 arrow-none vertical-slider" data-items="3" data-xl-items="3" data-lg-items="1"  data-md-items="3" data-sm-items="2" data-xs-items="2" data-arrows='true' data-infinite='false' data-vertical="true">
                                            @foreach ($best_selling_products as $key => $product)
                                                <div class="carousel-box px-0 position-relative has-transition @if($key == 0) @endif">
                                                    @include('frontend.'.get_setting('homepage_select').'.partials.product_box_1',['product' => $product])
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </section>
                        @endif
                        </div>
                    </div>

                </div>
            </div>
    </section>
    
    <!-- Flash Deal -->
    @php
        $flash_deal = get_featured_flash_deal();
        $flash_deal_bg = get_setting('flash_deal_bg_color');
        $flash_deal_bg_full_width = (get_setting('flash_deal_bg_full_width') == 1) ? true : false;
        $flash_deal_banner_menu_text = ((get_setting('flash_deal_banner_menu_text') == 'dark') ||  (get_setting('flash_deal_banner_menu_text') == null)) ? 'text-dark' : 'text-white';

    @endphp
    @if ($flash_deal != null)
        <section class="flash_deals_section mt-5 pt-5 pb-5 " style="background-image: url('{{ static_asset('assets/img/bg_deals_images.png') }}');" >
            <div class="container">
                <div class="row align-items-center">

                <div class="col-md-5">
                    <p class="fw-600 fs-18 green_light_clr pb-0 mb-0">WEEKLY DEAL</p>
                     <h3 class="fw-600 headeing_size">Best Deal for This Week</h3>
                     <p>This week at <b>Pharm Vet Easy,</b> we bring you unbeatable discounts on top-quality veterinary products. Whether you need premium pet supplements, livestock medications, or healthcare essentials, our <b>Best Deal of the Week</b> ensures you get the best value for your money.
</p>

                          <!-- Countdown for small device -->
                <div class="mb-3 d-md-none">
                    <div class="aiz-count-down-circle" end-date="{{ date('Y/m/d H:i:s', $flash_deal->end_date) }}"></div>
                </div>

                  <!-- Flash Deals Baner & Countdown -->
                    <div class="col-xxl-4 col-lg-5 col-6 p-md-0">
                        <a href="{{ route('flash-deal-details', $flash_deal->slug) }}">
                            <div class="">
                                <div class="d-none d-md-block">
                                    <div class="">
                                        <div class="aiz-count-down-circle"
                                            end-date="{{ date('Y/m/d H:i:s', $flash_deal->end_date) }}"></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                     <a href="{{ route('flash-deals') }}"
                                            class="mt-5 has-transition {{ $flash_deal_banner_menu_text }} @if (get_setting('flash_deal_banner_menu_text') == 'light') animate_button white_buttons @endif mr-3">
                                            {{ translate('View All Flash Sale') }} <i class="las la-angle-double-right"></i>
                                        </a>

                                       

                </div>
                <div class="col-md-6">
                    <img class="w-100 flash_images" src="{{ uploaded_asset($flash_deal->banner) }}" />
                </div>
                <div class="col-md-1">
                    <div class="deal_heading">
                        <h4>DEAL</h4>
                    </div>
                </div>

                </div>
            </div>
        </section>
    @endif

  

   
   <section class="pt-md-5 pt-4 pb-md-5 pb-4">
       <div class="container">
         <div class="row align-items-center">
               <div class="col-md-6">
                     <img class="w-100" src="{{ static_asset('assets/img/about_us_images.png') }}" />
               </div> 
               
               <div class="col-md-6">
                     <p class="fw-600 fs-18 blue_light_clr pb-0 mb-0">About Us</p>
                     <h3 class="fw-600 headeing_size">Welcome to <span class="blue_light_clr">Pharm Vet Easy</span></h3>
                     <p>At <b>Pharm Vet Easy,</b> we have a strong vision for the future of global pharmaceuticals, with a primary focus on high-quality veterinary formulations. Guided by our core values of <b>PEOPLE – TRUST – VALUE & TECHNOLOGY,</b> we are committed to delivering world-class products and services while ensuring cost-effectiveness.
</p>
                     <p class="mb-3">Established in 2000 in Mumbai, the financial hub of India, <b>Pharm Vet Easy</b> has built a reputation as a trusted manufacturer and supplier of premium veterinary formulations. The driving force behind our success is Mr. A.Y. Jaliawala, a Computer Engineer with extensive experience in the pharmaceutical industry, whose expertise and leadership continue to shape our journey toward excellence in animal healthcare.</p>
                         <!-- <ul class="list_none">
                            <li class="fw-600"><img src="{{ static_asset('assets/img/checked_icons.png') }}" /> Streamlined Shipping Experience</li>
                            <li class="fw-600"><img src="{{ static_asset('assets/img/checked_icons.png') }}" /> Streamlined Shipping Experience</li>
                            <li class="fw-600"><img src="{{ static_asset('assets/img/checked_icons.png') }}" /> Streamlined Shipping Experience</li>
                            <li class="fw-600"><img src="{{ static_asset('assets/img/checked_icons.png') }}" /> Streamlined Shipping Experience</li>
                         </ul> -->

                         <div class="mt-3">
                            <a href="/about-us" class="animate_button black1_buttons">Discover More <i class="las la-angle-double-right"></i></a>
                         </div>
               </div> 
        </div>
       </div>
   </section>


   <section class="why_choose_us pt-5 pb-5">
       <div class="container">
         <div class="row align-items-center">
               <div class="col-md-4">
                     <img class="w-100" src="{{ static_asset('assets/img/why_choose_images.png') }}" />
               </div> 
               
               <div class="col-md-4">
                     <p class="fw-600 fs-18 blue_light_clr pb-0 mb-0">WHY CHOOSE US</p>
                     <h3 class="fw-600 headeing_size">Your Trusted Partner in Veterinary Healthcare</h3>
               </div> 

                <div class="col-md-4">
                      <p>At <b>Pharm Vet Easy,</b> provides top-quality veterinary products for animal health and well-being. We prioritize reliability, innovation, and affordability for veterinarians, pet owners, and farmers.</p>
               </div> 

                <div class="col-md-4">
                    <div class="whu_choose_box mt-5">
                        <img src="{{ static_asset('assets/img/why_choose_icon1.png') }}" />
                        <div class="">
                            <h4>Original Products</h4>
                             <p class="mb-0">Pharm Vet Easy offers 100% authentic veterinary medicines and pet care products for your pet’s health.</p>
                        </div>
                        
                    </div> 
               </div> 

               <div class="col-md-4">
                    <div class="whu_choose_box mt-5">
                        <img src="{{ static_asset('assets/img/why_choose_icon2.png') }}" />
                         <div class="">
                            <h4>Affordable Price</h4>
                             <p class="mb-0">We offer quality pet care at competitive prices, ensuring premium products remain budget-friendly.</p>
                        </div>
                    </div> 
               </div> 

               <div class="col-md-4">
                    <div class="whu_choose_box mt-5">
                        <img src="{{ static_asset('assets/img/why_choose_icon3.png') }}" />
                         <div class="">
                            <h4>Free Shipping</h4>
                             <p class="mb-0">Enjoy free shipping with no hidden fees! Get your pet’s essentials delivered quickly and securely to your doorstep.</p>
                        </div>
                     </div> 
               </div> 

        </div>
       </div>
   </section>


  <!-- TESTIMONIALS -->
   <section class="testimonials gray_bg pt-md-5 pb-md-5 pt-4 pb-4" style="background-image: url('{{ static_asset('assets/img/testi_bg.png') }}');">
     <div class="container">
       <div class="text-center">
        <p class="text-white mb-0">TESTIMONIALS</p>
         <h3 class="text_clr_green pb-md-4 pt-3 pb-2 text-white headeing_size">What Our Client Say’s About Us</h3>
       </div>
      
           <div id="customers-testimonials" class="slick-slider" >
             <!-- TESTIMONIAL 1 -->
             <div class="item">
               <div class="shadow-effect">
                  <div class="testimnl_box ">
                     <img src="{{ static_asset('assets/img/testi_img.png') }}" />
                     <div class="text-left">
                        <h6 class="mb-0 text-white fs-18">Dr. Ananya Mehta</h6>
                        <p class="mb-0 pb-0 text-white fw-300 fs-14">Veterinarian</p>
                     </div>
                  </div>
                 <p class="pt-4">Pharm Vet Easy has been our go-to provider for veterinary medicines and supplements. The quality of their products is outstanding, and their prompt delivery ensures our clinic never runs out of essential supplies. Highly recommended!</p>
                 <div class="rating1 text-left">
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                 </div>
               </div>
             </div>
             <!-- TESTIMONIAL 2 -->
            <div class="item">
               <div class="shadow-effect">
                  <div class="testimnl_box">
                     <img src="{{ static_asset('assets/img/testi_img.png') }}" />
                     <div class="text-left">
                        <h6 class="mb-0 text-white fs-18">Ramesh Patil</h6>
                        <p class="mb-0 pb-0 text-white fw-300 fs-14">Livestock Farmer</p>
                     </div>
                  </div>
                 <p class="pt-4">s a livestock farmer, I need reliable and effective veterinary products. Pharm Vet Easy has consistently provided top-notch solutions that keep my animals healthy. Their customer service is excellent, making them a trusted partner in animal care.</p>
                 <div class="rating1 text-left">
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                 </div>
               </div>
             </div>
             
             
             <!-- TESTIMONIAL 3 -->
            <div class="item">
               <div class="shadow-effect">
                  <div class="testimnl_box">
                     <img src="{{ static_asset('assets/img/testi_img.png') }}" />
                     <div class="text-left">
                        <h6 class="mb-0 text-white fs-18">Sneha Kapoor</h6>
                        <p class="mb-0 pb-0 text-white fw-300 fs-14">Pet Owner</p>
                     </div>
                  </div>
                 <p class="pt-4">What sets Pharm Vet Easy apart is their knowledgeable team and dedication to customer satisfaction. They helped me choose the right supplements for my pet, and I’ve seen remarkable improvements in my dog's health. Thank you for the great service!</p>
                 <div class="rating1 text-left">
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                 </div>
               </div>
             </div>


              <!-- TESTIMONIAL 4 -->
          <div class="item">
               <div class="shadow-effect">
                  <div class="testimnl_box ">
                     <img src="{{ static_asset('assets/img/testi_img.png') }}" />
                     <div class="text-left">
                        <h6 class="mb-0 text-white fs-18">Dr. Ananya Mehta</h6>
                        <p class="mb-0 pb-0 text-white fw-300 fs-14">Veterinarian</p>
                     </div>
                  </div>
                 <p class="pt-4">Pharm Vet Easy has been our go-to provider for veterinary medicines and supplements. The quality of their products is outstanding, and their prompt delivery ensures our clinic never runs out of essential supplies. Highly recommended!</p>
                 <div class="rating1 text-left">
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                   <i class="las la-star"></i>
                 </div>
               </div>
             </div>
            
             <!-- Additional Testimonials as needed -->
           </div>
     </div>
   </section>
   <!-- testiminial slider close-->


   <section class="pt-5 pb-5 accordion_section">
    
<div class="container">
    <div class="text-center">
         <h3 class="headeing_size text_clr_green pb-2 fw-600 text-left">Frequently asked questions</h3>
       </div>
    <div id="accordion" class="accordion">
        <div class="card mb-0">
            <div class="card-header collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapse_1">
                <a class="card-title">
                  What is Pharm Vet Easy?
                </a>
            </div>
            <div id="collapse_1" class="collapse" data-parent="#accordion" >
                <div class="card-body">Pharm Vet Easy is a trusted provider of high-quality veterinary pharmaceuticals and healthcare solutions. We specialize in finished formulations for pets, livestock, and other animals, ensuring their health and well-being.

                </div>
            </div>

            <div class="card-header collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapse_2">
                <a class="card-title">
                 What types of veterinary products do you offer?
                </a>
            </div>
            <div id="collapse_2" class="collapse" data-parent="#accordion" >
                <div class="card-body">We offer a wide range of veterinary products, including medicines, supplements, nutritional support, and healthcare essentials for various animals.

                </div>
            </div>

            <div class="card-header collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapse_3">
                <a class="card-title">
                  Are your products safe and certified?
                </a>
            </div>
            <div id="collapse_3" class="collapse" data-parent="#accordion" >
                <div class="card-body">Yes, all our products are sourced from reputed manufacturers and comply with international quality and safety standards.

                </div>
            </div>

             <div class="card-header collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapse_4">
                <a class="card-title">
                  Do you provide bulk orders for veterinary clinics and farms?
                </a>
            </div>
            <div id="collapse_4" class="collapse" data-parent="#accordion" >
                <div class="card-body">Yes, we cater to veterinary clinics, hospitals, and livestock farms with bulk supply options at competitive prices.

                </div>
            </div>

             <div class="card-header collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapse_5">
                <a class="card-title">
                 How can I place an order?
                </a>
            </div>
            <div id="collapse_5" class="collapse" data-parent="#accordion" >
                <div class="card-body">You can place an order through our website or contact our customer support team for assistance with bulk or customized orders.

                </div>
            </div>

             <div class="card-header collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapse_6">
                <a class="card-title">
                 Do you offer worldwide shipping?
                </a>
            </div>
            <div id="collapse_6" class="collapse" data-parent="#accordion" >
                <div class="card-body">Yes, we provide global shipping options. Delivery times may vary based on location and regulatory approvals.
                </div>
            </div>

             <div class="card-header collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapse_7">
                <a class="card-title">
                  How can I track my order
                </a>
            </div>
            <div id="collapse_7" class="collapse" data-parent="#accordion" >
                <div class="card-body">Once your order is dispatched, you will receive a tracking ID via email or SMS to monitor the shipment’s status.

                </div>
            </div>

             <div class="card-header collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapse_8">
                <a class="card-title">
                 What payment methods do you accept?
                </a>
            </div>
            <div id="collapse_8" class="collapse" data-parent="#accordion" >
                <div class="card-body">We accept multiple payment methods, including credit/debit cards, net banking, and digital payment gateways.

                </div>
            </div>

            <div class="card-header collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapse_8">
                <a class="card-title">
                 Can I return or exchange a product?
                </a>
            </div>
            <div id="collapse_8" class="collapse" data-parent="#accordion" >
                <div class="card-body">Returns and exchanges are subject to our return policy. Please refer to our return policy section or contact customer support for details.

                </div>
            </div>

            <div class="card-header collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapse_8">
                <a class="card-title">
                 How can I contact customer support?
                </a>
            </div>
            <div id="collapse_8" class="collapse" data-parent="#accordion" >
                <div class="card-body">You can reach our customer support team via email, phone, or our website’s contact form for any queries or assistance.
                </div>
            </div>

        </div>
    </div>
</div>
</section>

   

    <!-- Auction Product -->
    @if (addon_is_activated('auction'))
        <div id="auction_products">

        </div>
    @endif

    <!-- Cupon -->
    <!-- @if (get_setting('coupon_system') == 1)
        <div class=" mt-2 mt-md-3"
            style="background-color: {{ get_setting('cupon_background_color', '#292933') }}">
            <div class="container">
                <div class="position-relative py-5">
                    <div class="text-center text-xl-left position-relative z-5">
                        <div class="d-lg-flex">
                            <div class="mb-3 mb-lg-0">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                    width="109.602" height="93.34" viewBox="0 0 109.602 93.34">
                                    <defs>
                                        <clipPath id="clip-pathcup">
                                            <path id="Union_10" data-name="Union 10" d="M12263,13778v-15h64v-41h12v56Z"
                                                transform="translate(-11966 -8442.865)" fill="none" stroke="#fff"
                                                stroke-width="2" />
                                        </clipPath>
                                    </defs>
                                    <g id="Group_24326" data-name="Group 24326"
                                        transform="translate(-274.201 -5254.611)">
                                        <g id="Mask_Group_23" data-name="Mask Group 23"
                                            transform="translate(-3652.459 1785.452) rotate(-45)"
                                            clip-path="url(#clip-pathcup)">
                                            <g id="Group_24322" data-name="Group 24322"
                                                transform="translate(207 18.136)">
                                                <g id="Subtraction_167" data-name="Subtraction 167"
                                                    transform="translate(-12177 -8458)" fill="none">
                                                    <path
                                                        d="M12335,13770h-56a8.009,8.009,0,0,1-8-8v-8a8,8,0,0,0,0-16v-8a8.009,8.009,0,0,1,8-8h56a8.009,8.009,0,0,1,8,8v8a8,8,0,0,0,0,16v8A8.009,8.009,0,0,1,12335,13770Z"
                                                        stroke="none" />
                                                    <path
                                                        d="M 12335.0009765625 13768.0009765625 C 12338.3095703125 13768.0009765625 12341.0009765625 13765.30859375 12341.0009765625 13762 L 12341.0009765625 13755.798828125 C 12336.4423828125 13754.8701171875 12333.0009765625 13750.8291015625 12333.0009765625 13746 C 12333.0009765625 13741.171875 12336.4423828125 13737.130859375 12341.0009765625 13736.201171875 L 12341.0009765625 13729.9990234375 C 12341.0009765625 13726.6904296875 12338.3095703125 13723.9990234375 12335.0009765625 13723.9990234375 L 12278.9990234375 13723.9990234375 C 12275.6904296875 13723.9990234375 12272.9990234375 13726.6904296875 12272.9990234375 13729.9990234375 L 12272.9990234375 13736.201171875 C 12277.5576171875 13737.1298828125 12280.9990234375 13741.1708984375 12280.9990234375 13746 C 12280.9990234375 13750.828125 12277.5576171875 13754.869140625 12272.9990234375 13755.798828125 L 12272.9990234375 13762 C 12272.9990234375 13765.30859375 12275.6904296875 13768.0009765625 12278.9990234375 13768.0009765625 L 12335.0009765625 13768.0009765625 M 12335.0009765625 13770.0009765625 L 12278.9990234375 13770.0009765625 C 12274.587890625 13770.0009765625 12270.9990234375 13766.412109375 12270.9990234375 13762 L 12270.9990234375 13754 C 12275.4111328125 13753.9990234375 12278.9990234375 13750.4111328125 12278.9990234375 13746 C 12278.9990234375 13741.5888671875 12275.41015625 13738 12270.9990234375 13738 L 12270.9990234375 13729.9990234375 C 12270.9990234375 13725.587890625 12274.587890625 13721.9990234375 12278.9990234375 13721.9990234375 L 12335.0009765625 13721.9990234375 C 12339.412109375 13721.9990234375 12343.0009765625 13725.587890625 12343.0009765625 13729.9990234375 L 12343.0009765625 13738 C 12338.5888671875 13738.0009765625 12335.0009765625 13741.5888671875 12335.0009765625 13746 C 12335.0009765625 13750.4111328125 12338.58984375 13754 12343.0009765625 13754 L 12343.0009765625 13762 C 12343.0009765625 13766.412109375 12339.412109375 13770.0009765625 12335.0009765625 13770.0009765625 Z"
                                                        stroke="none" fill="#fff" />
                                                </g>
                                            </g>
                                        </g>
                                        <g id="Group_24321" data-name="Group 24321"
                                            transform="translate(-3514.477 1653.317) rotate(-45)">
                                            <g id="Subtraction_167-2" data-name="Subtraction 167"
                                                transform="translate(-12177 -8458)" fill="none">
                                                <path
                                                    d="M12335,13770h-56a8.009,8.009,0,0,1-8-8v-8a8,8,0,0,0,0-16v-8a8.009,8.009,0,0,1,8-8h56a8.009,8.009,0,0,1,8,8v8a8,8,0,0,0,0,16v8A8.009,8.009,0,0,1,12335,13770Z"
                                                    stroke="none" />
                                                <path
                                                    d="M 12335.0009765625 13768.0009765625 C 12338.3095703125 13768.0009765625 12341.0009765625 13765.30859375 12341.0009765625 13762 L 12341.0009765625 13755.798828125 C 12336.4423828125 13754.8701171875 12333.0009765625 13750.8291015625 12333.0009765625 13746 C 12333.0009765625 13741.171875 12336.4423828125 13737.130859375 12341.0009765625 13736.201171875 L 12341.0009765625 13729.9990234375 C 12341.0009765625 13726.6904296875 12338.3095703125 13723.9990234375 12335.0009765625 13723.9990234375 L 12278.9990234375 13723.9990234375 C 12275.6904296875 13723.9990234375 12272.9990234375 13726.6904296875 12272.9990234375 13729.9990234375 L 12272.9990234375 13736.201171875 C 12277.5576171875 13737.1298828125 12280.9990234375 13741.1708984375 12280.9990234375 13746 C 12280.9990234375 13750.828125 12277.5576171875 13754.869140625 12272.9990234375 13755.798828125 L 12272.9990234375 13762 C 12272.9990234375 13765.30859375 12275.6904296875 13768.0009765625 12278.9990234375 13768.0009765625 L 12335.0009765625 13768.0009765625 M 12335.0009765625 13770.0009765625 L 12278.9990234375 13770.0009765625 C 12274.587890625 13770.0009765625 12270.9990234375 13766.412109375 12270.9990234375 13762 L 12270.9990234375 13754 C 12275.4111328125 13753.9990234375 12278.9990234375 13750.4111328125 12278.9990234375 13746 C 12278.9990234375 13741.5888671875 12275.41015625 13738 12270.9990234375 13738 L 12270.9990234375 13729.9990234375 C 12270.9990234375 13725.587890625 12274.587890625 13721.9990234375 12278.9990234375 13721.9990234375 L 12335.0009765625 13721.9990234375 C 12339.412109375 13721.9990234375 12343.0009765625 13725.587890625 12343.0009765625 13729.9990234375 L 12343.0009765625 13738 C 12338.5888671875 13738.0009765625 12335.0009765625 13741.5888671875 12335.0009765625 13746 C 12335.0009765625 13750.4111328125 12338.58984375 13754 12343.0009765625 13754 L 12343.0009765625 13762 C 12343.0009765625 13766.412109375 12339.412109375 13770.0009765625 12335.0009765625 13770.0009765625 Z"
                                                    stroke="none" fill="#fff" />
                                            </g>
                                            <g id="Group_24325" data-name="Group 24325">
                                                <rect id="Rectangle_18578" data-name="Rectangle 18578" width="8"
                                                    height="2" transform="translate(120 5287)" fill="#fff" />
                                                <rect id="Rectangle_18579" data-name="Rectangle 18579" width="8"
                                                    height="2" transform="translate(132 5287)" fill="#fff" />
                                                <rect id="Rectangle_18581" data-name="Rectangle 18581" width="8"
                                                    height="2" transform="translate(144 5287)" fill="#fff" />
                                                <rect id="Rectangle_18580" data-name="Rectangle 18580" width="8"
                                                    height="2" transform="translate(108 5287)" fill="#fff" />
                                            </g>
                                        </g>
                                    </g>
                                </svg>
                            </div>
                            <div class="ml-lg-3">
                                <h5 class="fs-36 fw-400 text-white mb-3">{{ translate(get_setting('cupon_title')) }}</h5>
                                <h5 class="fs-20 fw-400 text-gray">{{ translate(get_setting('cupon_subtitle')) }}</h5>
                                <div class="mt-5 pt-5">
                                    <a href="{{ route('coupons.all') }}"
                                        class="btn text-white hov-bg-white hov-text-dark border border-width-2 fs-16 px-5"
                                        style="border-radius: 28px;background: rgba(255, 255, 255, 0.2);box-shadow: 0px 20px 30px rgba(0, 0, 0, 0.16);">{{ translate('View All Coupons') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="position-absolute right-0 bottom-0 h-100">
                        <img class="img-fit h-100" src="{{ uploaded_asset(get_setting('coupon_background_image', null, $lang)) }}"
                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/coupon.svg') }}';"
                            alt="{{ env('APP_NAME') }} promo">
                    </div>
                </div>
            </div>
        </div>
    @endif -->

    <!-- Category wise Products -->
    <div id="section_home_categories">

    </div>

    <!-- Classified Product -->
    @if (get_setting('classified_product') == 1)
        @php
            $classified_products = get_home_page_classified_products(6);
        @endphp
        @if (count($classified_products) > 0)
            <section class="mb-2 mb-md-3 mt-3 mt-md-5">
                <div class="container">
                    <!-- Top Section -->
                    <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                        <!-- Title -->
                        <h3 class="fs-16 fs-md-20 fw-700 mb-2 mb-sm-0">
                            <span class="">{{ translate('Classified Ads') }}</span>
                        </h3>
                        <!-- Links -->
                        <div class="d-flex">
                            <a class="text-blue fs-10 fs-md-12 fw-700 hov-text-primary animate-underline-primary"
                                href="{{ route('customer.products') }}">{{ translate('View All Products') }}</a>
                        </div>
                    </div>
                    <!-- Banner -->
                    @php
                        $classifiedBannerImage = get_setting('classified_banner_image', null, $lang);
                        $classifiedBannerImageSmall = get_setting('classified_banner_image_small', null, $lang);
                    @endphp
                    @if ($classifiedBannerImage != null || $classifiedBannerImageSmall != null)
                        <div class="mb-3 overflow-hidden hov-scale-img d-none d-md-block">
                            <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                data-src="{{ uploaded_asset($classifiedBannerImage) }}"
                                alt="{{ env('APP_NAME') }} promo" class="lazyload img-fit h-100 has-transition"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                        </div>
                        <div class="mb-3 overflow-hidden hov-scale-img d-md-none">
                            <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                data-src="{{ $classifiedBannerImageSmall != null ? uploaded_asset($classifiedBannerImageSmall) : uploaded_asset($classifiedBannerImage) }}"
                                alt="{{ env('APP_NAME') }} promo" class="lazyload img-fit h-100 has-transition"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                        </div>
                    @endif
                    <!-- Products Section -->
                    <div class="bg-white pt-3">
                        <div class="row no-gutters border-top border-left">
                            @foreach ($classified_products as $key => $classified_product)
                                <div
                                    class="col-xl-4 col-md-6 border-right border-bottom has-transition hov-shadow-out z-1">
                                    <div class="aiz-card-box p-2 has-transition bg-white">
                                        <div class="row hov-scale-img">
                                            <div class="col-4 col-md-5 mb-3 mb-md-0">
                                                <a href="{{ route('customer.product', $classified_product->slug) }}"
                                                    class="d-block overflow-hidden h-auto h-md-150px text-center">
                                                    <img class="img-fluid lazyload mx-auto has-transition"
                                                        src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                        data-src="{{ isset($classified_product->thumbnail->file_name) ? my_asset($classified_product->thumbnail->file_name) : static_asset('assets/img/placeholder.jpg') }}"
                                                        alt="{{ $classified_product->getTranslation('name') }}"
                                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                                </a>
                                            </div>
                                            <div class="col">
                                                <h3
                                                    class="fw-400 fs-14 text-dark text-truncate-2 lh-1-4 mb-3 h-35px d-none d-sm-block">
                                                    <a href="{{ route('customer.product', $classified_product->slug) }}"
                                                        class="d-block text-reset hov-text-primary">{{ $classified_product->getTranslation('name') }}</a>
                                                </h3>
                                                <div class="fs-14 mb-3">
                                                    <span
                                                        class="text-secondary">{{ $classified_product->user ? $classified_product->user->name : '' }}</span><br>
                                                    <span
                                                        class="fw-700 text-primary">{{ single_price($classified_product->unit_price) }}</span>
                                                </div>
                                                @if ($classified_product->conditon == 'new')
                                                    <span
                                                        class="badge badge-inline badge-soft-info fs-13 fw-700 px-3 py-2 text-info"
                                                        style="border-radius: 20px;">{{ translate('New') }}</span>
                                                @elseif($classified_product->conditon == 'used')
                                                    <span
                                                        class="badge badge-inline badge-soft-secondary-base fs-13 fw-700 px-3 py-2 text-danger"
                                                        style="border-radius: 20px;">{{ translate('Used') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endif
    @endif

    <!-- Top Sellers -->
    @if (get_setting('vendor_system_activation') == 1)
        @php
            $best_selers = get_best_sellers(10);
        @endphp
        @if (count($best_selers) > 0)
        <section class="mb-2 mb-md-4 mt-2 mt-md-3">
            <div class="container">
                <!-- Top Section -->
                <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                    <!-- Title -->
                    <h3 class="fs-16 fs-md-20 fw-700 mb-2 mb-sm-0">
                        <span class="pb-3">{{ translate('Top Sellers') }}</span>
                    </h3>
                    <!-- Links -->
                    <div class="d-flex">
                        <a class="text-blue fs-10 fs-md-12 fw-700 hov-text-primary animate-underline-primary"
                            href="{{ route('sellers') }}">{{ translate('View All Sellers') }}</a>
                    </div>
                </div>
                <!-- Sellers Section -->
                <div class="aiz-carousel arrow-x-0 arrow-inactive-none" data-items="5" data-xxl-items="5"
                    data-xl-items="4" data-lg-items="3.4" data-md-items="2.5" data-sm-items="2" data-xs-items="1.4"
                    data-arrows="true" data-dots="false">
                    @foreach ($best_selers as $key => $seller)
                        @if ($seller->user != null)
                            <div
                                class="carousel-box h-100 position-relative text-center border-right border-top border-bottom @if ($key == 0) border-left @endif has-transition hov-animate-outline">
                                <div class="position-relative px-3" style="padding-top: 2rem; padding-bottom:2rem;">
                                    <!-- Shop logo & Verification Status -->
                                    <div class="mx-auto size-100px size-md-120px">
                                        <a href="{{ route('shop.visit', $seller->slug) }}"
                                            class="d-flex mx-auto justify-content-center align-item-center size-100px size-md-120px border overflow-hidden hov-scale-img"
                                            tabindex="0"
                                            style="border: 1px solid #e5e5e5; border-radius: 50%; box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.06);">
                                            <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                                data-src="{{ uploaded_asset($seller->logo) }}" alt="{{ $seller->name }}"
                                                class="img-fit lazyload has-transition"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                                        </a>
                                    </div>
                                    <!-- Shop name -->
                                    <h2 class="fs-14 fw-700 text-dark text-truncate-2 h-40px mt-3 mt-md-4 mb-0 mb-md-3">
                                        <a href="{{ route('shop.visit', $seller->slug) }}"
                                            class="text-reset hov-text-primary" tabindex="0">{{ $seller->name }}</a>
                                    </h2>
                                    <!-- Shop Rating -->
                                    <div class="rating rating-mr-2 text-dark mb-3">
                                        {{ renderStarRating($seller->rating) }}
                                        <span class="opacity-60 fs-14">({{ $seller->num_of_reviews }}
                                            {{ translate('Reviews') }})</span>
                                    </div>
                                    <!-- Visit Button -->
                                    <a href="{{ route('shop.visit', $seller->slug) }}" class="btn-visit">
                                        <span class="circle" aria-hidden="true">
                                            <span class="icon arrow"></span>
                                        </span>
                                        <span class="button-text">{{ translate('Visit Store') }}</span>
                                    </a>
                                    @if ($seller->verification_status == 1)
                                        <span class="absolute-top-right mr-2rem">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="31.999" height="48.001" viewBox="0 0 31.999 48.001">
                                                <g id="Group_25062" data-name="Group 25062" transform="translate(-532 -1033.999)">
                                                <path id="Union_3" data-name="Union 3" d="M1937,12304h16v14Zm-16,0h16l-16,14Zm0,0v-34h32v34Z" transform="translate(-1389 -11236)" fill="#85b567"/>
                                                <path id="Union_5" data-name="Union 5" d="M1921,12280a10,10,0,1,1,10,10A10,10,0,0,1,1921,12280Zm1,0a9,9,0,1,0,9-9A9.011,9.011,0,0,0,1922,12280Zm1,0a8,8,0,1,1,8,8A8.009,8.009,0,0,1,1923,12280Zm4.26-1.033a.891.891,0,0,0-.262.636.877.877,0,0,0,.262.632l2.551,2.551a.9.9,0,0,0,.635.266.894.894,0,0,0,.639-.266l4.247-4.244a.9.9,0,0,0-.639-1.542.893.893,0,0,0-.635.266l-3.612,3.608-1.912-1.906a.89.89,0,0,0-1.274,0Z" transform="translate(-1383 -11226)" fill="#fff"/>
                                                </g>
                                            </svg>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
        @endif
    @endif

    <!-- Top Brands -->
    @if (get_setting('top_brands') != null)
        <section class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="container">
                <!-- Top Section -->
                <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                    <!-- Title -->
                    <h3 class="fs-16 fs-md-20 fw-700 mb-2 mb-sm-0">{{ translate('Top Brands') }}</h3>
                    <!-- Links -->
                    <div class="d-flex">
                        <a class="text-blue fs-10 fs-md-12 fw-700 hov-text-primary animate-underline-primary"
                            href="{{ route('brands.all') }}">{{ translate('View All Brands') }}</a>
                    </div>
                </div>
                <!-- Brands Section -->
                <div class="bg-white px-3">
                    <div
                        class="row row-cols-xxl-6 row-cols-xl-6 row-cols-lg-4 row-cols-md-4 row-cols-3 gutters-16 border-top border-left">
                        @php
                            $top_brands = json_decode(get_setting('top_brands'));
                            $brands = get_brands($top_brands);
                        @endphp
                        @foreach ($brands as $brand)
                            <div
                                class="col text-center border-right border-bottom hov-scale-img has-transition hov-shadow-out z-1">
                                <a href="{{ route('products.brand', $brand->slug) }}" class="d-block p-sm-3">
                                    <img src="{{ $brand->logo != null ? uploaded_asset($brand->logo)  : static_asset('assets/img/placeholder.jpg') }}"
                                        class="lazyload h-100 h-md-100px mx-auto has-transition p-2 p-sm-4 mw-100"
                                        alt="{{ $brand->getTranslation('name') }}"
                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                    <p class="text-center text-dark fs-12 fs-md-14 fw-700 mt-2">
                                        {{ $brand->getTranslation('name') }}
                                    </p>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

@endsection

