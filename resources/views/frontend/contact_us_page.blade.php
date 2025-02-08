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
                <h1 class="fw-600 h4">Contact Us</h1>
                 <ul class="breadcrumb bg-transparent p-0 justify-content-center">
                    <li class="breadcrumb-item has-transition opacity-50 hov-opacity-100">
                        <a class="text-reset" href="{{ route('home') }}">{{ translate('Home')}}</a>
                    </li>
                    <li class="text-dark fw-600 breadcrumb-item">
                        "{{ translate('Contact Us') }}"
                    </li>
                </ul>
            </div>
           
        </div>
    </div>
</section>


<section class="pt-5 pb-4">
    @php
        $lang = str_replace('_', '-', app()->getLocale());
        $content = json_decode($page->getTranslation('content', $lang));
    @endphp
    <div class="container">
        <div>
            <div class="row">
                <div class="col-lg-5 text-center text-lg-left">
                    <div class="">
                        
                        <!-- <p class="fs-16 fw-400 mb-5">{{ $content->description }}</p> -->
                        <div class="row">
                             <div class="col-md-12">
                                   <div class="contact_boxex">
                                        <span class="contact_icons d-flex align-items-center justify-content-center blue_bg_light_clr rounded-content">
                                            <i class="la-2x las la-map-marker text-white font_icons1"></i>
                                        </span>
                                        <span class="">
                                            <span class="fs-19 fw-600">{{ translate('Address') }}</span><br>
                                            <span class="fs-14">{!! str_replace("\n", "<br>", $content->address) !!}</span>
                                        </span>
                                   </div>
                             </div>

                             <div class="col-md-6">
                                   <div class="contact_boxex">
                                       <span class="contact_icons d-flex align-items-center justify-content-center blue_bg_light_clr rounded-content">
                                            <i class="las la-2x la-phone text-white font_icons1"></i>
                                        </span>
                                        <span class="">
                                            <span class="fs-19 fw-600">{{ translate('Phone') }}</span><br>
                                            <span class="fs-14">{{ $content->phone }}</span>
                                        </span>
                                   </div>
                             </div>

                             <div class="col-md-6">
                                   <div class="contact_boxex">
                                        <span class="contact_icons d-flex align-items-center justify-content-center blue_bg_light_clr rounded-content">
                                            <i class="las la-2x la-envelope text-white font_icons1"></i>
                                        </span>
                                        <span class="">
                                            <span class="fs-19 fw-600">{{ translate('Email') }}</span><br>
                                            <span class="fs-14">{{ $content->email }}</span>
                                        </span>
                                   </div>
                             </div>
                          
                        </div>
                        
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="">
                        <div class="contact_form_boxex">

                        <h4 class="pb-2 fw-600 text-left">Get In Touch</h4>
                            <form class="form-default" role="form" action="{{ route('contact') }}" method="POST">
                                @csrf
                              
                               <div class="row">
                                  <div class="col-md-6">
                                          <div class="form-group">
                                  
                                    <input type="text" class="form-control rounded-0" value="{{ old('name') }}" placeholder="{{  translate('Enter Name') }}" name="name" required>
                                </div>
                                  </div>

                                  <div class="col-md-6">
                                      <div class="form-group">
                                    
                                    <input type="email" class="form-control rounded-0" value="{{ old('email') }}" placeholder="{{  translate('Enter Email') }}" name="email" required>
                                </div>
                                  </div>

                                  <div class="col-md-6">
                                        <div class="form-group">
                                   
                                    <input type="tel" class="form-control rounded-0" value="{{ old('phone') }}" placeholder="{{  translate('Enter Phone') }}" name="phone">
                                </div>
                                  </div>

                                  <div class="col-md-6">
                                        <div class="form-group">
                                   
                                    <input type="text" class="form-control rounded-0" value="{{ old('name') }}" placeholder="{{  translate('Enter Subject') }}" name="name" required>
                                </div>
                                  </div>

                                   <div class="col-md-12">
                                       <div class="form-group">
                                   
                                    <textarea
                                        class="form-control rounded-0"
                                        placeholder="Write Your Message"
                                        name="content"
                                        rows="3"
                                        required
                                    ></textarea>
                                </div>
                                </div>
                               </div>
                               
                                <!-- Recaptcha -->
                                @if(get_setting('google_recaptcha') == 1)
                                    <div class="form-group">
                                        <div class="g-recaptcha" data-sitekey="{{ env('CAPTCHA_KEY') }}"></div>
                                    </div>
                                    @if ($errors->has('g-recaptcha-response'))
                                        <span class="invalid-feedback" role="alert" style="display: block;">
                                            <strong>{{ $errors->first('g-recaptcha-response') }}</strong>
                                        </span>
                                    @endif
                                @endif

                                <!-- Submit Button -->
                                <div class="mt-1">
                                    @if (env('MAIL_USERNAME') == null && env('MAIL_PASSWORD') == null)
                                        <a class="btn btn-primary fw-700 fs-14 rounded-0 w-200px"
                                            href="javascript:void(1)" onclick="showWarning()">
                                            {{  translate('Submit') }}
                                        </a>
                                    @else
                                        <button type="submit" class="btn btn-primary fw-700 fs-14 rounded-0 w-200px">{{  translate('Submit') }}</button>
                                    @endif

                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<section class="pb-5">
    <div class="container">
        <iframe src="https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d3770.237728636608!2d72.88289367418473!3d19.097224101307916!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1sUnit.No%3A209%2C%20Second%20Floor%2C%20Patel%20Industrial%20Estate%20Co-Society%20Ltd.%20Landmark%3A%20Opp.%20Bachoo%20Garage%20Safed%20Pool%20%2C%20Near%20Sakinaka%20Andheri%20Kurla%20Road%2C%20Kurla%20(W)%20Mumbai-400%20072%2C%20Maharashtra!5e0!3m2!1sen!2sin!4v1738821641449!5m2!1sen!2sin" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
</section>

@endsection

@section('script')
    @if(get_setting('google_recaptcha') == 1)
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
    
    <script type="text/javascript">
        @if(get_setting('google_recaptcha') == 1)
        // making the CAPTCHA  a required field for form submission
        $(document).ready(function(){
            $("#reg-form").on("submit", function(evt)
            {
                var response = grecaptcha.getResponse();
                if(response.length == 0)
                {
                //reCaptcha not verified
                    alert("please verify you are human!");
                    evt.preventDefault();
                    return false;
                }
                //captcha verified
                //do the rest of your validations here
                $("#reg-form").submit();
            });
        });
        @endif
    </script>


    <script type="text/javascript">
        function showWarning(){
            AIZ.plugins.notify('warning', "{{ translate('Something went wrong.') }}");
            return false;
        }
    </script>
@endsection
