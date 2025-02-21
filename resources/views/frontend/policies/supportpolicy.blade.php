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
                        {{ translate('About Us') }}
                    </li>
                </ul>
            </div>
           
        </div>
    </div>
</section>


<section class="pb-md-5 pt-md-5 pt-4 pb-4">
       <div class="container">
         <div class="row align-items-center">
               <div class="col-md-6 mb-md-0 mb-4">
                     <img class="w-100" src="{{ static_asset('assets/img/about_us_images.png') }}" />
               </div> 
               
               <div class="col-md-6">
                     <p class="fw-600 fs-18 blue_light_clr pb-0 mb-0">About Us</p>
                     <h3 class="fw-600 headeing_size">Welcome to <span class="blue_light_clr">Pharm Vet Easy</span></h3>
                     
                       <p>At <b>Pharm Vet Easy,</b> we have a strong vision for the future of global pharmaceuticals, with a primary focus on high-quality veterinary formulations. Guided by our core values of <b>PEOPLE – TRUST – VALUE & TECHNOLOGY,</b> we are committed to delivering world-class products and services while ensuring cost-effectiveness.
</p>
                     <p>Established in 2000 in Mumbai, the financial hub of India, <b>Pharm Vet Easy</b> has built a reputation as a trusted manufacturer and supplier of premium veterinary formulations. The driving force behind our success is Mr. A.Y. Jaliawala, a Computer Engineer with extensive experience in the pharmaceutical industry, whose expertise and leadership continue to shape our journey toward excellence in animal healthcare.</p>
                       
               </div> 


               <div class="col-md-12">

               <p class="pt-md-5">At Pharm Vet Easy, our vision is to be recognized as the Partner of Choice by combining manufacturing excellence with a strong commitment to quality. Backed by a team of skilled professionals, we ensure top-tier manufacturing, marketing, and quality assurance in veterinary pharmaceuticals.</p>
<p>With a well-equipped office in Mumbai and a branch in Bangalore, we provide comprehensive infrastructure and expert personnel to support our operations. Since our inception, we have built a strong foundation, achieving steady growth and earning the trust of customers and business partners globally.</p>
<p class="mb-0">Through vision, innovation, advanced technology, and efficient supply chain management, we have established ourselves as a leading name in the industry. As a zero-debt company with substantial reserves, we continue to invest in future projects, ensuring sustainable growth and excellence in veterinary healthcare.</p>


               </div>
        </div>
       </div>
   </section>


   <section class="green_bg_clr pt-md-5 pt-4 pb-md-5">
    <div class="container">
        <div id="counter">
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="counter_boxex">
                        <div class="counter_img"><img src="{{ static_asset('assets/img/total_sale.svg') }}" /></div>
                        <div class="counter_content">
                            <div class="display_flexs"><div class="counter-value" data-count="10000">0</div><span>+</span></div>
                            <p>Happy Clients</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="counter_boxex">
                        <div class="counter_img"><img src="{{ static_asset('assets/img/happy_clients.svg') }}" /></div>
                        <div class="counter_content">
                            <div class="display_flexs"><div class="counter-value" data-count="500">0</div><span>+</span></div>
                            <p>Veterinary Products</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="counter_boxex">
                        <div class="counter_img"><img src="{{ static_asset('assets/img/team_work.svg') }}" /></div>
                        <div class="counter_content">
                             <div class="display_flexs"><div class="counter-value" data-count="25">0</div><span>+</span></div>
                            <p>Countries Served</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="counter_boxex">
                        <div class="counter_img"><img  src="{{ static_asset('assets/img/win_awards.svg') }}" /></div>
                        <div class="counter_content">
                            <div class="display_flexs"><div class="counter-value" data-count="20">0</div><span>+</span></div>
                            <p>Years - Experience</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


   <section class="testimonials gray_bg pt-md-5 pb-md-5 pt-4 pb-4" style="background-image: url('{{ static_asset('assets/img/testi_bg.png') }}');">
     <div class="container">
       <div class="text-center">
        <p class="text-white mb-0">TESTIMONIALS</p>
         <h3 class="text_clr_green pb-md-4 pt-3 pb-2 text-white headeing_size">What Our Client Say’s About Us</h3>
       </div>
      
           <div id="customers-testimonials" class="slick-slider" >
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


    <section class="sale_section">
            <img class="w-100" src="{{ static_asset('assets/img/video_img_sec.webp') }}" />
</section>

   <section class="pt-md-5 pb-md-5 pt-4 pb-4">
       <div class="container">
        <div class="payment_safe_secrtion">
                    <div class="row">
                          <div class="col-xl-3 col-md-6">
                              <div class="payment_boxs align-items-center gap-3">
                                   <img class="" src="{{ static_asset('assets/img/free_delivery_icons.svg') }}" />
                                   <div class="">
                                      <h6 class="text-white mb-1 fw-500 mb-0">Free Delivery</h6>
                                      <p class="text-white mb-0 pb-0 fs-14 fw-400">Order Over 250.00</p>
                                   </div>
                              </div>
                          </div>

                          <div class="col-xl-3 col-md-6">
                              <div class="payment_boxs align-items-center gap-3">
                                   <img src="{{ static_asset('assets/img/refund_icons.svg') }}" />
                                   <div class="">
                                       <h6 class="text-white mb-1 fw-500 mb-0">Get Refund</h6>
                                      <p class="text-white mb-0 pb-0 fs-14 fw-400">Within 30 Days Return</p>
                                   </div>
                              </div>
                          </div>

                           <div class="col-xl-3 col-md-6">
                              <div class="payment_boxs align-items-center gap-3">
                                   <img src="{{ static_asset('assets/img/safe_payment_icons.svg') }}" />
                                   <div class="">
                                       <h6 class="text-white mb-1 fw-500 mb-0">Safe Payment</h6>
                                      <p class="text-white mb-0 pb-0 fs-14 fw-400">100% Secure Payment</p>
                                   </div>
                              </div>
                          </div>

                            <div class="col-xl-3 col-md-6">
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
