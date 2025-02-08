@extends('frontend.layouts.app')

@section('meta_title'){{ $page->meta_title }}@stop

@section('meta_description'){{ $page->meta_description }}@stop

@section('meta_keywords'){{ $page->tags }}@stop

@section('meta')
    <!-- Schema.org markup for Google+ -->
    <meta itemprop="name" content="{{ $page->meta_title }}">
    <meta itemprop="description" content="{{ $page->meta_description }}">
    <meta itemprop="image" content="{{ uploaded_asset($page->meta_image) }}">

    <!-- Twitter Card data -->
    <meta name="twitter:card" content="website">
    <meta name="twitter:site" content="@publisher_handle">
    <meta name="twitter:title" content="{{ $page->meta_title }}">
    <meta name="twitter:description" content="{{ $page->meta_description }}">
    <meta name="twitter:creator" content="@author_handle">
    <meta name="twitter:image" content="{{ uploaded_asset($page->meta_image) }}">

    <!-- Open Graph data -->
    <meta property="og:title" content="{{ $page->meta_title }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ URL($page->slug) }}" />
    <meta property="og:image" content="{{ uploaded_asset($page->meta_image) }}" />
    <meta property="og:description" content="{{ $page->meta_description }}" />
    <meta property="og:site_name" content="{{ env('APP_NAME') }}" />
@endsection

@section('content')
<section class="pt-4 bg_gray">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <h1 class="fw-600 h4">About Us</h1>
                 <ul class="breadcrumb bg-transparent p-0 justify-content-center">
                    <li class="breadcrumb-item has-transition opacity-50 hov-opacity-100">
                        <a class="text-reset" href="{{ route('home') }}">{{ translate('Home')}}</a>
                    </li>
                    <li class="text-dark fw-600 breadcrumb-item">
                        "{{ translate('About Us') }}"
                    </li>
                </ul>
            </div>
           
        </div>
    </div>
</section>


<section class="pb-md-5 pt-md-5 pt-4 pb-4">
       <div class="container">
         <div class="row align-items-center">
               <div class="col-md-6">
                     <img class="w-100" src="{{ static_asset('assets/img/about_us_images.png') }}" />
               </div> 
               
               <div class="col-md-6">
                     <p class="fw-600 fs-18 blue_light_clr pb-0 mb-0">About Us</p>
                     <h3 class="fw-600 headeing_size">We Provide Best and Original <span class="blue_light_clr">Medical</span> Product For You</h3>
                     <p>is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, 
                        when an unknown printeis simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text
                         ever since the 1500s, when an unknown printe</p>
                         <ul class="list_none">
                            <li class="fw-600"><img src="{{ static_asset('assets/img/checked_icons.png') }}" /> Streamlined Shipping Experience</li>
                            <li class="fw-600"><img src="{{ static_asset('assets/img/checked_icons.png') }}" /> Streamlined Shipping Experience</li>
                            <li class="fw-600"><img src="{{ static_asset('assets/img/checked_icons.png') }}" /> Streamlined Shipping Experience</li>
                            <li class="fw-600"><img src="{{ static_asset('assets/img/checked_icons.png') }}" /> Streamlined Shipping Experience</li>
                         </ul> 
               </div> 
        </div>
       </div>
   </section>


   <section class="green_bg_clr pt-5 pb-5">
    <div class="container">
        <div id="counter">
            <div class="row">
                <div class="col-md-3">
                    <div class="counter_boxex">
                        <div class="counter_img"><img src="{{ static_asset('assets/img/total_sale.svg') }}" /></div>
                        <div class="counter_content">
                            <div class="display_flexs"><div class="counter-value" data-count="50">0</div><span>K</span></div>
                            <p>Total Sales</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="counter_boxex">
                        <div class="counter_img"><img src="{{ static_asset('assets/img/happy_clients.svg') }}" /></div>
                        <div class="counter_content">
                            <div class="display_flexs"><div class="counter-value" data-count="90">0</div><span>K</span></div>
                            <p>Happy Clients</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="counter_boxex">
                        <div class="counter_img"><img src="{{ static_asset('assets/img/team_work.svg') }}" /></div>
                        <div class="counter_content">
                             <div class="display_flexs"><div class="counter-value" data-count="150">0</div><span>K</span></div>
                            <p>Team Workers</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="counter_boxex">
                        <div class="counter_img"><img  src="{{ static_asset('assets/img/win_awards.svg') }}" /></div>
                        <div class="counter_content">
                            <div class="display_flexs"><div class="counter-value" data-count="30">0</div><span>K</span></div>
                            <p>Win Awards</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


   <section class="testimonials gray_bg pt-md-5 pb-md-5 pt-4 pb-4 mt-md-5 mb-md-5 mt-4 mb-4" style="background-image: url('{{ static_asset('assets/img/testi_bg.png') }}');">
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
                        <h6 class="mb-0 text-white fs-18">Parkar Nez</h6>
                        <p class="mb-0 pb-0 text-white fw-300 fs-14">Petr, Belgium</p>
                     </div>
                  </div>
                 <p class="pt-4">is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text dummy text  is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the</p>
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
                        <h6 class="mb-0 text-white fs-18">Parkar Nez</h6>
                        <p class="mb-0 pb-0 text-white fw-300 fs-14">Petr, Belgium</p>
                     </div>
                  </div>
                 <p class="pt-4">is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text dummy text  is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the</p>
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
                        <h6 class="mb-0 text-white fs-18">Parkar Nez</h6>
                        <p class="mb-0 pb-0 text-white fw-300 fs-14">Petr, Belgium</p>
                     </div>
                  </div>
                 <p class="pt-4">is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text dummy text  is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the</p>
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
                  <div class="testimnl_box">
                     <img src="{{ static_asset('assets/img/testi_img.png') }}" />
                     <div class="text-left">
                         <h6 class="mb-0 text-white fs-18">Parkar Nez</h6>
                        <p class="mb-0 pb-0 text-white fw-300 fs-14">Petr, Belgium</p>
                     </div>
                  </div>
                 <p class="pt-4">is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text dummy text  is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the</p>
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


    <section class="sale_section">
            <img class="w-100" src="{{ static_asset('assets/img/video_img_sec.webp') }}" />
</section>

   <section class="pt-5 pb-5">
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
@endsection
