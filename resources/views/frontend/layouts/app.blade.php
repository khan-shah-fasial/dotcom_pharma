<!DOCTYPE html>

@php
    $rtl = get_session_language()->rtl;
@endphp

@if ($rtl == 1)
    <html dir="rtl" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@else
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@endif

<head>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ getBaseURL() }}">
    <meta name="file-base-url" content="{{ getFileBaseURL() }}">

    <title>@yield('meta_title', get_setting('website_name') . ' | ' . get_setting('site_motto'))</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <meta name="description" content="@yield('meta_description', get_setting('meta_description'))" />
    <meta name="keywords" content="@yield('meta_keywords', get_setting('meta_keywords'))">

    @yield('meta')

    @if (!isset($detailedProduct) && !isset($customer_product) && !isset($shop) && !isset($page) && !isset($blog))
        @php
            $meta_image = uploaded_asset(get_setting('meta_image'));
        @endphp
        <!-- Schema.org markup for Google+ -->
        <meta itemprop="name" content="{{ get_setting('meta_title') }}">
        <meta itemprop="description" content="{{ get_setting('meta_description') }}">
        <meta itemprop="image" content="{{ $meta_image }}">

        <!-- Twitter Card data -->
        <meta name="twitter:card" content="product">
        <meta name="twitter:site" content="@publisher_handle">
        <meta name="twitter:title" content="{{ get_setting('meta_title') }}">
        <meta name="twitter:description" content="{{ get_setting('meta_description') }}">
        <meta name="twitter:creator" content="@author_handle">
        <meta name="twitter:image" content="{{ $meta_image }}">

        <!-- Open Graph data -->
        <meta property="og:title" content="{{ get_setting('meta_title') }}" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{{ route('home') }}" />
        <meta property="og:image" content="{{ $meta_image }}" />
        <meta property="og:description" content="{{ get_setting('meta_description') }}" />
        <meta property="og:site_name" content="{{ env('APP_NAME') }}" />
        <meta property="fb:app_id" content="{{ env('FACEBOOK_PIXEL_ID') }}">
    @endif

    <!-- Favicon -->
    @php
        $site_icon = uploaded_asset(get_setting('site_icon'));
    @endphp
    <link rel="icon" href="{{ $site_icon }}">
    <link rel="apple-touch-icon" href="{{ $site_icon }}">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <!-- CSS Files -->
    <link rel="stylesheet" href="{{ static_asset('assets/css/vendors.css') }}">
    @if ($rtl == 1)
        <link rel="stylesheet" href="{{ static_asset('assets/css/bootstrap-rtl.min.css') }}">
    @endif
    <link rel="stylesheet" href="{{ static_asset('assets/css/aiz-core.css?v=') }}{{ rand(1000, 9999) }}">

    <link rel="stylesheet" href="{{ static_asset('assets/css/intlTelinput.css') }}" />

    <link rel="stylesheet" href="{{ static_asset('assets/css/custom-style.css') }}?v=1.3.1">
    <link rel="stylesheet" href="{{ static_asset('assets/css/responsive.css') }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <script>
        var AIZ = AIZ || {};
        AIZ.local = {
            nothing_selected: '{!! translate('Nothing selected', null, true) !!}',
            nothing_found: '{!! translate('Nothing found', null, true) !!}',
            choose_file: '{{ translate('Choose file') }}',
            file_selected: '{{ translate('File selected') }}',
            files_selected: '{{ translate('Files selected') }}',
            add_more_files: '{{ translate('Add more files') }}',
            adding_more_files: '{{ translate('Adding more files') }}',
            drop_files_here_paste_or: '{{ translate('Drop files here, paste or') }}',
            browse: '{{ translate('Browse') }}',
            upload_complete: '{{ translate('Upload complete') }}',
            upload_paused: '{{ translate('Upload paused') }}',
            resume_upload: '{{ translate('Resume upload') }}',
            pause_upload: '{{ translate('Pause upload') }}',
            retry_upload: '{{ translate('Retry upload') }}',
            cancel_upload: '{{ translate('Cancel upload') }}',
            uploading: '{{ translate('Uploading') }}',
            processing: '{{ translate('Processing') }}',
            complete: '{{ translate('Complete') }}',
            file: '{{ translate('File') }}',
            files: '{{ translate('Files') }}',
        }
    </script>

    <style>
        :root{
            --blue: #3490f3;
            --hov-blue: #2e7fd6;
            --soft-blue: rgba(0, 123, 255, 0.15);
            --secondary-base: {{ get_setting('secondary_base_color', '#ffc519') }};
            --hov-secondary-base: {{ get_setting('secondary_base_hov_color', '#dbaa17') }};
            --soft-secondary-base: {{ hex2rgba(get_setting('secondary_base_color', '#ffc519'), 0.15) }};
            --gray: #9d9da6;
            --gray-dark: #8d8d8d;
            --secondary: #919199;
            --soft-secondary: rgba(145, 145, 153, 0.15);
            --success: #85b567;
            --soft-success: rgba(133, 181, 103, 0.15);
            --warning: #f3af3d;
            --soft-warning: rgba(243, 175, 61, 0.15);
            --light: #f5f5f5;
            --soft-light: #dfdfe6;
            --soft-white: #b5b5bf;
            --dark: #292933;
            --soft-dark: #1b1b28;
            --primary: {{ get_setting('base_color', '#d43533') }};
            --hov-primary: {{ get_setting('base_hov_color', '#9d1b1a') }};
            --soft-primary: {{ hex2rgba(get_setting('base_color', '#d43533'), 0.15) }};
        }
        body{
            font-family: "Poppins", serif;
            font-weight: 400;
        }

        .pagination .page-link,
        .page-item.disabled .page-link {
            min-width: 32px;
            min-height: 32px;
            line-height: 32px;
            text-align: center;
            padding: 0;
            border: 1px solid var(--soft-light);
            font-size: 0.875rem;
            border-radius: 0 !important;
            color: var(--dark);
        }
        .pagination .page-item {
            margin: 2px 5px;
        }

        .form-control:focus {
            border-width: 2px !important;
        }
        .iti__flag-container {
            padding: 2px;
        }
        .modal-content {
            border: 0 !important;
            border-radius: 0 !important;
        }

        .tagify.tagify--focus{
            border-width: 2px;
            border-color: var(--primary);
        }

        #map{
            width: 100%;
            height: 250px;
        }
        #edit_map{
            width: 100%;
            height: 250px;
        }

        .pac-container { z-index: 100000; }
    </style>

@if (get_setting('google_analytics') == 1)
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('TRACKING_ID') }}"></script>

    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ env('TRACKING_ID') }}');
    </script>
@endif

@if (get_setting('facebook_pixel') == 1)
    <!-- Facebook Pixel Code -->
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ env('FACEBOOK_PIXEL_ID') }}');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ env('FACEBOOK_PIXEL_ID') }}&ev=PageView&noscript=1"/>
    </noscript>
    <!-- End Facebook Pixel Code -->
@endif

@php
    echo get_setting('header_script');
@endphp

</head>
<body>
    <!-- aiz-main-wrapper -->
    <div class="aiz-main-wrapper d-flex flex-column bg-white">
        @php
            $user = auth()->user();
            $user_avatar = null;
            $carts = [];
            if ($user && $user->avatar_original != null) {
                $user_avatar = uploaded_asset($user->avatar_original);
            }

            $system_language = get_system_language();
        @endphp

        {{-- @php
            $newRegUrl = url(route('user.new_registration'));
        @endphp

        @if (!Auth::check() && request()->url() != $newRegUrl)

            {{-- - //------------------------------ login and register -----------------------// -- 

            <div class="modal fade login_form_popup" id="login_reg_model" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
                aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content py-3">
                        <div class="modal-header">
                          <div class="heading">
                            <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                            <h5 class="modal-title" id="exampleModalLabel_phone">Welcome to </h5>
                        </div>

                        <div class="modal-body">
                            <h3 class="login_heds"><span class="blue_light_clr">Pharmvet</span> - <span class="green_light_clr">Easy</span></h3>

                            <div class="login_border"></div>


                            <div class="login_flex">
                            <div class="green_lg_btn ">
                           <a href="{{ route('user.login') }}"
                            class="">{{ translate('Login') }}</a>
                            </div>


                            <div class="blue_lg_btn ">
                                 <a href="{{ route('user.new_registration') }}"
                                class="">{{ translate('Registration') }}</a>
                            </div>
                           </div>
                    
                    </div>
                           

                        </div>

                    </div>
                </div>
            </div>

            {{-- - //------------------------------  login and register -----------------------// -- -
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var modalElement = document.getElementById('login_reg_model');
                    if (!window.location.pathname.includes('/user/registration')) {
                        var modalInstance = new bootstrap.Modal(modalElement, {
                            backdrop: 'static',
                            keyboard: false
                        });
                        modalInstance.show();
                    }
                });
            </script>

        @endif --}}


            <!-- Header -->
            @include('frontend.inc.nav')

            @yield('content')

            <!-- footer -->
            @include('frontend.inc.footer')


    </div>

    @if(get_setting('use_floating_buttons') == 1)
        <!-- Floating Buttons -->
        @include('frontend.inc.floating_buttons')
    @endif

    {{-- <div class="aiz-refresh">
        <div class="aiz-refresh-content"><div></div><div></div><div></div></div>
    </div> --}}


    @if (env("DEMO_MODE") == "On")
        <!-- demo nav -->
        @include('frontend.inc.demo_nav')
    @endif

    <!-- cookies agreement -->
    @php
        $alert_location = get_setting('custom_alert_location');
        $order = in_array($alert_location, ['top-left', 'top-right']) ? 'asc' : 'desc';
        $custom_alerts = App\Models\CustomAlert::where('status', 1)->orderBy('id', $order)->get();
    @endphp

    <div class="aiz-custom-alert {{ get_setting('custom_alert_location') }}">
        @foreach ($custom_alerts as $custom_alert)
            @if($custom_alert->id == 1)
                <div class="aiz-cookie-alert mb-3" style="box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.24);">
                    <div class="p-3 px-lg-2rem rounded-0" style="background: {{ $custom_alert->background_color }};">
                        <div class="text-{{ $custom_alert->text_color }} mb-3">
                            {!! $custom_alert->description !!}
                        </div>
                        <button class="btn btn-block btn-primary rounded-0 aiz-cookie-accept">
                            {{ translate('Ok. I Understood') }}
                        </button>
                    </div>
                </div>
            @else
                <div class="mb-3 custom-alert-box removable-session d-none" data-key="custom-alert-box-{{ $custom_alert->id }}" data-value="removed" style="box-shadow: 0px 6px 10px rgba(0, 0, 0, 0.24);">
                    <div class="rounded-0 position-relative" style="background: {{ $custom_alert->background_color }};">
                        <a href="{{ $custom_alert->link }}" class="d-block h-100 w-100">
                            <div class="@if ($custom_alert->type == 'small') d-flex @endif">
                                <img class="@if ($custom_alert->type == 'small') h-140px w-120px img-fit @else w-100 @endif" src="{{ uploaded_asset($custom_alert->banner) }}" alt="custom_alert">
                                <div class="text-{{ $custom_alert->text_color }} p-2rem">
                                    {!! $custom_alert->description !!}
                                </div>
                            </div>
                        </a>
                        <button class="absolute-top-right bg-transparent btn btn-circle btn-icon d-flex align-items-center justify-content-center text-{{ $custom_alert->text_color }} hov-text-primary set-session" data-key="custom-alert-box-{{ $custom_alert->id }}" data-value="removed" data-toggle="remove-parent" data-parent=".custom-alert-box">
                            <i class="la la-close fs-20"></i>
                        </button>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <!-- website popup -->
    @php
        $dynamic_popups = App\Models\DynamicPopup::where('status', 1)->orderBy('id', 'asc')->get();
    @endphp
    @foreach ($dynamic_popups as $key => $dynamic_popup)
        @if($dynamic_popup->id == 1)
            <div class="modal website-popup removable-session" data-key="website-popup" data-value="removed">
                <div class="absolute-full bg-black opacity-60"></div>
                <div class="modal-dialog modal-dialog-centered modal-dialog-zoom modal-md mx-4 mx-md-auto">
                    <div class="modal-content position-relative border-0 rounded-0">
                        <div class="aiz-editor-data">
                            <div class="d-block">
                                <img class="w-100" src="{{ uploaded_asset($dynamic_popup->banner) }}" alt="dynamic_popup">
                            </div>
                        </div>
                        <div class="pb-5 pt-4 px-3 px-md-2rem">
                            <h1 class="fs-30 fw-700 text-dark">{{ $dynamic_popup->title }}</h1>
                            <p class="fs-14 fw-400 mt-3 mb-4">{{ $dynamic_popup->summary }}</p>
                            @if ($dynamic_popup->show_subscribe_form == 'on')
                                <form class="" method="POST" action="{{ route('subscribers.store') }}">
                                    @csrf
                                    <div class="form-group mb-0">
                                        <input type="email" class="form-control" placeholder="{{ translate('Your Email Address') }}" name="email" required>
                                    </div>
                                    <button type="submit" class="btn btn-block mt-3 rounded-0 text-{{ $dynamic_popup->btn_text_color }}" style="background: {{ $dynamic_popup->btn_background_color }};">
                                        {{ $dynamic_popup->btn_text }}
                                    </button>
                                </form>
                            @endif
                        </div>
                        <button class="absolute-top-right bg-white shadow-lg btn btn-circle btn-icon mr-n3 mt-n3 set-session" data-key="website-popup" data-value="removed" data-toggle="remove-parent" data-parent=".website-popup">
                            <i class="la la-close fs-20"></i>
                        </button>
                    </div>
                </div>
            </div>
        @else
            <div class="modal website-popup removable-session d-none" data-key="website-popup-{{ $dynamic_popup->id }}" data-value="removed">
                <div class="absolute-full bg-black opacity-60"></div>
                <div class="modal-dialog modal-dialog-centered modal-dialog-zoom modal-md mx-4 mx-md-auto">
                    <div class="modal-content position-relative border-0 rounded-0">
                        <div class="aiz-editor-data">
                            <div class="d-block">
                                <img class="w-100" src="{{ uploaded_asset($dynamic_popup->banner) }}" alt="dynamic_popup">
                            </div>
                        </div>
                        <div class="pb-5 pt-4 px-3 px-md-2rem">
                            <h1 class="fs-30 fw-700 text-dark">{{ $dynamic_popup->title }}</h1>
                            <p class="fs-14 fw-400 mt-3 mb-4">{{ $dynamic_popup->summary }}</p>
                            <a href="{{ $dynamic_popup->btn_link }}" class="btn btn-block mt-3 rounded-0 text-{{ $dynamic_popup->btn_text_color }}" style="background: {{ $dynamic_popup->btn_background_color }};">
                                {{ $dynamic_popup->btn_text }}
                            </a>
                        </div>
                        <button class="absolute-top-right bg-white shadow-lg btn btn-circle btn-icon mr-n3 mt-n3 set-session" data-key="website-popup-{{ $dynamic_popup->id }}" data-value="removed" data-toggle="remove-parent" data-parent=".website-popup">
                            <i class="la la-close fs-20"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    @include('frontend.partials.modal')

    @include('frontend.partials.account_delete_modal')

    <div class="modal fade" id="addToCart">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-zoom product-modal" id="modal-size" role="document">
            <div class="modal-content position-relative">
                <div class="c-preloader text-center p-3">
                    <i class="las la-spinner la-spin la-3x"></i>
                </div>
                <button type="button" class="close absolute-top-right btn-icon close z-1 btn-circle bg-gray mr-2 mt-2 d-flex justify-content-center align-items-center" data-dismiss="modal" aria-label="Close" style="background: #ededf2; width: calc(2rem + 2px); height: calc(2rem + 2px);">
                    <span aria-hidden="true" class="fs-24 fw-700" style="margin-left: 2px;">&times;</span>
                </button>
                <div id="addToCart-modal-body">

                </div>
            </div>
        </div>
    </div>

    @yield('modal')



    @auth
        <div class="modal fade" id="prescriptionModal" tabindex="-1" role="dialog" aria-labelledby="prescriptionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form action="{{ route('prescription.store') }}" method="POST" enctype="multipart/form-data" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="prescriptionModalLabel">Upload Prescription</h5>
                        <!-- Bootstrap 4 uses "close" not "btn-close" -->
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body modal-body-prescription">
                        {{-- Name --}}
                        <div class="mb-3">
                            <label for="presc_name" class="form-label mb-0 pl-1">Name</label>
                            <input type="text" class="form-control" id="presc_name" name="name"
                                value="{{ old('name', auth()->user()->name ?? '') }}" required>
                        </div>

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="presc_email" class="form-label mb-0 pl-1">Email</label>
                            <input type="email" class="form-control" id="presc_email" name="email"
                                value="{{ old('email', auth()->user()->email ?? '') }}">
                            <div class="form-text form-text-disc">Either email or phone is required.</div>
                        </div>

                        {{-- Phone --}}
                        <div class="mb-3">
                            <label for="presc_phone" class="form-label mb-0 pl-1">Phone</label>
                            <input type="text" class="form-control" id="presc_phone" name="phone"
                                value="{{ old('phone', auth()->user()->phone ?? '') }}">
                            <div class="form-text form-text-disc">Either phone or email is required.</div>
                        </div>

                        {{-- File --}}
                        <div class="mb-3">
                            <label for="presc_file" class="form-label mb-0 pl-1">Prescription (image or PDF)*</label>
                            <input class="form-control" type="file" id="presc_file" name="prescription_file"
                                accept="image/*,application/pdf" required>
                            <small class="form-text text-muted">Accepted: jpg, jpeg, png, gif, pdf. Max 5MB.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <!-- Bootstrap 4 close button -->
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    @endauth

    <!-- Hidden Google widget (kept in DOM but hidden) -->
    <div id="google_translate_element" style="display: none;"></div>

    <!-- ======= Language + Currency Modal ======= -->
    <div class="modal fade" id="languageCurrencyModal" tabindex="-1" role="dialog"
        aria-labelledby="languageCurrencyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered language-change-main-div" role="document">
            <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-dark text-white" style="background: #2b56a1 !important;">
                <h5 class="modal-title" id="languageCurrencyModalLabel"><i class="fa fa-globe mr-1"></i> Choose Language & Currency</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body pl-4 pr-4 pt-4 pb-0">
                <!-- Language -->
                <div class="mb-md-4 mb-3">
                <h6 class="font-weight-bold mb-2">Select Language</h6>
                <select id="languageDropdown" class="form-control" style="width:100%;">
                    <!-- options injected by JS (with flags) -->
                </select>
                </div>

                <!-- Currency -->
                @if (get_setting('show_currency_switcher') == 'on')
                @php $system_currency = get_system_currency(); @endphp
                <div class="mb-3">
                    <h6 class="font-weight-bold mb-2">Select Currency</h6>
                    <select id="currencyDropdown"
                            class="form-control"
                            data-initial="{{ optional($system_currency)->code }}">
                    @foreach (get_all_active_currency() as $currency)
                        <option
                        value="{{ $currency->code }}"
                        data-name="{{ $currency->name }}"
                        data-symbol="{{ $currency->symbol }}"
                        @selected(optional($system_currency)->code === $currency->code)
                        >
                        {{ $currency->name }} ({{ $currency->symbol }})
                        </option>
                    @endforeach
                    </select>
                </div>
                @endif
            </div>

            <div class="modal-footer border-0 justify-content-between">
                <button type="button" class="btn btn-light rounded-pill" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-pill" id="saveLangCurrency" style="background: #2b56a1;">Save Changes</button>
            </div>
            </div>
        </div>
    </div>



    <!-- SCRIPTS -->
    <script src="{{ static_asset('assets/js/vendors.js') }}"></script>
    <script src="{{ static_asset('assets/js/aiz-core.js?v=') }}{{ rand(1000, 9999) }}"></script>

    <!-- to show country code and flags in mobile view field -->
    {{-- <script src="{{ static_asset('assets/js/utils.min.js') }}"></script>
    <script src="{{ static_asset('assets/js/inteliput.min.js') }}"></script> --}}
    
    <script src="{{ static_asset('assets/js/jquery.validate.min.js') }}"></script>
    <script src="{{ static_asset('assets/js/script.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    @if (get_setting('facebook_chat') == 1)
        <script type="text/javascript">
            window.fbAsyncInit = function() {
                FB.init({
                  xfbml            : true,
                  version          : 'v3.3'
                });
              };

              (function(d, s, id) {
              var js, fjs = d.getElementsByTagName(s)[0];
              if (d.getElementById(id)) return;
              js = d.createElement(s); js.id = id;
              js.src = 'https://connect.facebook.net/en_US/sdk/xfbml.customerchat.js';
              fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
        </script>
        <div id="fb-root"></div>
        <!-- Your customer chat code -->
        <div class="fb-customerchat"
          attribution=setup_tool
          page_id="{{ env('FACEBOOK_PAGE_ID') }}">
        </div>
    @endif

    <script>
        @foreach (session('flash_notification', collect())->toArray() as $message)
            AIZ.plugins.notify('{{ $message['level'] }}', '{{ $message['message'] }}');
        @endforeach
    </script>

    @yield('custom-script-section')

    <script>
        @if (Route::currentRouteName() == 'home' || Route::currentRouteName() == '/' || Route::currentRouteName() == 'human')

            $.post('{{ route('home.section.featured') }}', {
                _token: '{{ csrf_token() }}'
            }, function(data) {
                $('#section_featured').html(data);
                console.dir('featured loaded');
                AIZ.plugins.slickCarousel();
            });

            $.post('{{ route('home.section.todays_deal') }}', {
                _token: '{{ csrf_token() }}'
            }, function(data) {
                $('#todays_deal').html(data);
                AIZ.plugins.slickCarousel();
            });

            $.post('{{ route('home.section.best_selling') }}', {
                _token: '{{ csrf_token() }}'
            }, function(data) {
                $('#section_best_selling').html(data);
                AIZ.plugins.slickCarousel();
            });

            $.post('{{ route('home.section.newest_products') }}', {
                _token: '{{ csrf_token() }}'
            }, function(data) {
                $('#section_newest').html(data);
                AIZ.plugins.slickCarousel();
            });

            $.post('{{ route('home.section.auction_products') }}', {
                _token: '{{ csrf_token() }}'
            }, function(data) {
                $('#auction_products').html(data);
                AIZ.plugins.slickCarousel();
            });

            $.post('{{ route('home.section.home_categories') }}', {
                _token: '{{ csrf_token() }}'
            }, function(data) {
                $('#section_home_categories').html(data);
                AIZ.plugins.slickCarousel();
            });

        @endif

        $(document).ready(function() {
            $('.category-nav-element').each(function(i, el) {

                $(el).on('mouseover', function(){
                    if(!$(el).find('.sub-cat-menu').hasClass('loaded')){
                        $.post('{{ route('category.elements') }}', {
                            _token: AIZ.data.csrf,
                            id:$(el).data('id'
                            )}, function(data){
                            $(el).find('.sub-cat-menu').addClass('loaded').html(data);
                        });
                    }
                });
            });

            if ($('#lang-change').length > 0) {
                $('#lang-change .dropdown-menu a').each(function() {
                    $(this).on('click', function(e){
                        e.preventDefault();
                        var $this = $(this);
                        var locale = $this.data('flag');
                        $.post('{{ route('language.change') }}',{_token: AIZ.data.csrf, locale:locale}, function(data){
                            location.reload();
                        });

                    });
                });
            }

            // if ($('#currency-change').length > 0) {
            //     $('#currency-change .dropdown-menu a').each(function() {
            //         $(this).on('click', function(e){
            //             e.preventDefault();
            //             var $this = $(this);
            //             var currency_code = $this.data('currency');
            //             $.post('{{ route('currency.change') }}',{_token: AIZ.data.csrf, currency_code:currency_code}, function(data){
            //                 location.reload();
            //             });

            //         });
            //     });
            // }
        });

        let searchTimeout = null;
        let searchRequest = null;

        $('#search').on('keyup focus', function(){
            scheduleSearch();
        });

        function scheduleSearch(){
            if (searchTimeout) clearTimeout(searchTimeout);
            searchTimeout = setTimeout(runSearch, 200);
        }

        function runSearch(){
            var searchKey = $('#search').val();
            if(searchKey.length >= 3){
                $('body').addClass("typed-search-box-shown");

                $('.typed-search-box').removeClass('d-none');
                $('.search-preloader').removeClass('d-none');

                if (searchRequest && typeof searchRequest.abort === 'function') {
                    searchRequest.abort();
                }

                searchRequest = $.post('{{ route('search.ajax') }}', { _token: AIZ.data.csrf, search:searchKey}, function(data){
                    if(data == '0'){
                        // $('.typed-search-box').addClass('d-none');
                        $('#search-content').html(null);
                        $('.typed-search-box .search-nothing').removeClass('d-none').html('{{ translate('Sorry, nothing found for') }} <strong>"'+searchKey+'"</strong>');
                        $('.search-preloader').addClass('d-none');

                    }
                    else{
                        $('.typed-search-box .search-nothing').addClass('d-none').html(null);
                        $('#search-content').html(data);
                        $('.search-preloader').addClass('d-none');
                    }
                });
            }
            else {
                $('.typed-search-box').addClass('d-none');
                $('body').removeClass("typed-search-box-shown");
                $('.typed-search-box .search-nothing').addClass('d-none').html(null);
            }
        }

        $(".aiz-user-top-menu").on("mouseover", function (event) {
            $(".hover-user-top-menu").addClass('active');
        })
        .on("mouseout", function (event) {
            $(".hover-user-top-menu").removeClass('active');
        });

        $(document).on("click", function(event){
            var $trigger = $("#category-menu-bar");
            if($trigger !== event.target && !$trigger.has(event.target).length){
                $("#click-category-menu").slideUp("fast");;
                $("#category-menu-bar-icon").removeClass('show');
            }
        });

        function updateNavCart(view,count){
            $('.cart-count').html(count);
            $('#cart_items').html(view);
        }

        function removeFromCart(key){
            $.post('{{ route('cart.removeFromCart') }}', {
                _token  : AIZ.data.csrf,
                id      :  key
            }, function(data){
                updateNavCart(data.nav_cart_view,data.cart_count);
                $('#cart-details').html(data.cart_view);
                AIZ.plugins.notify('success', "{{ translate('Item has been removed from cart') }}");
                $('#cart_items_sidenav').html(parseInt($('#cart_items_sidenav').html())-1);
            });
        }

        function showLoginModal() {
            $('#login_modal').modal();
        }

        function addToCompare(id){
            $.post('{{ route('compare.addToCompare') }}', {_token: AIZ.data.csrf, id:id}, function(data){
                $('#compare').html(data);
                AIZ.plugins.notify('success', "{{ translate('Item has been added to compare list') }}");
                $('#compare_items_sidenav').html(parseInt($('#compare_items_sidenav').html())+1);
            });
        }

        function addToWishList(id){
            @if (Auth::check() && Auth::user()->user_type == 'customer')
                $.post('{{ route('wishlists.store') }}', {_token: AIZ.data.csrf, id:id}, function(data){
                    if(data != 0){
                        $('#wishlist').html(data);
                        AIZ.plugins.notify('success', "{{ translate('Item has been added to wishlist') }}");
                    }
                    else{
                        AIZ.plugins.notify('warning', "{{ translate('Please login first') }}");
                    }
                });
            @elseif(Auth::check() && Auth::user()->user_type != 'customer')
                AIZ.plugins.notify('warning', "{{ translate('Please Login as a customer to add products to the WishList.') }}");
            @else
                AIZ.plugins.notify('warning', "{{ translate('Please login first') }}");
            @endif
        }

        function showAddToCartModal(id){
            if(!$('#modal-size').hasClass('modal-lg')){
                $('#modal-size').addClass('modal-lg');
            }
            $('#addToCart-modal-body').html(null);
            $('#addToCart').modal();
            $('.c-preloader').show();
            $.post('{{ route('cart.showCartModal') }}', {_token: AIZ.data.csrf, id:id}, function(data){
                $('.c-preloader').hide();
                $('#addToCart-modal-body').html(data);
                AIZ.plugins.slickCarousel();
                AIZ.plugins.zoom();
                AIZ.extra.plusMinus();
                getVariantPrice();
            });
        }

        $('#option-choice-form input').on('change', function(){
            getVariantPrice();
        });

        let lastVariantKey = null; // remember last variant to decide when to reset qty
        let variantPriceTimer = null;
        const variantPriceDebounceMs = 250;

        function getVariantPrice(immediate = false){
            const invoke = function(){
                const requestedQty = parseInt($('#product_quantity').val(), 10) || 0;

                if($('#option-choice-form input[name=quantity]').val() > 0 && checkAddToCartValidity()){
                    $.ajax({
                        type:"POST",
                        url: '{{ route('products.variant_price') }}',
                        data: $('#option-choice-form').serializeArray(),
                        success: function(data){

                        const $roleTableBody = $('#rolePriceTable tbody');
                        const $coaDiv = $('#coaDiv'); 

                        $coaDiv.empty();
                        $roleTableBody.empty();

                        $('.product-gallery-thumb .carousel-box').each(function (i) {
                            if($(this).data('variation') && data.variation == $(this).data('variation')){
                                $('.product-gallery-thumb').slick('slickGoTo', i);
                            }
                        })

                        $('#option-choice-form #chosen_price_div').removeClass('d-none');
                        $('#option-choice-form #chosen_price_div #chosen_price').html(data.price);
                        $('#sku-product-details').html(data?.sku ?? '-');
                        $('#available-quantity').html(data.quantity);
                        
                        let qnt = data?.quantity ?? 0;
                        $('#qnt-product-details').html(qnt > 0 ? data?.quantity : 'Not Available');

                        // $('#per-piece-price-product-details').html('Rs. ' + (data?.per_piece_price ?? '-'));
                        let price = data?.per_piece_price ?? '-';

                        // remove decimal part if it exists
                        if (typeof price === "string") {
                            //price = price.split('.')[0];
                        }

                        $('#per-piece-price-product-details').html(price);

                        let package_count = data?.package_count ?? 1;
                        let stock_min_qty = data?.stock_min_qty ?? 1;
                        let temp_per_piece_price = data?.per_piece_price?.replace(/[^0-9.]/g, "") || "";

                        let original = data?.per_piece_price || "";

                        // Extract string part (all characters except digits and decimal point)
                        let stringPart = original.replace(/[0-9.]/g, "");

                        let per_piece_price = temp_per_piece_price / package_count;

                        $('#package-count-product-details').html(
                            stringPart + ' ' + (per_piece_price ? per_piece_price.toFixed(2) + ' / Count' : '-')
                        );

                        $('#dimentions-product-details').html(data?.dimension ?? '-');

                        // Reset qty when variant changes; otherwise respect user input but clamp to min
                        const $qtyInput = $('#product_quantity');
                        const isNewVariant = data?.variation !== lastVariantKey;
                        let currentQty = isNewVariant ? stock_min_qty : Math.max(stock_min_qty, requestedQty);

                        $qtyInput.attr('min', stock_min_qty)
                                 .attr('max', data?.max_limit ?? $qtyInput.attr('max'))
                                 .val(currentQty);
                        // Re-enable +/- before recalculating state
                        $qtyInput.siblings('[data-type="minus"], [data-type="plus"]').prop('disabled', false);
                        lastVariantKey = data?.variation;
                        $('#weight-volume-product-details').html(data?.weight_volume ?? '-');

                        $('#min-package-count-product-details').html((data?.package_count ?? '-') + ' Pcs');

                        // $('#mrp-unit').html(stringPart + ' ' + (data?.original_price ?? '-'));
                        $('#mrp-unit').html(data?.original_price ?? '-');

                        $('#tax-product-details').html('Rs. ' + (data?.tax ?? '-'));
                        $('#product-expiry-date').html(data?.expiry_date ?? '-');

                        let withoutTaxPrice = data?.without_tax_price ?? '-';
                        // remove decimal part if it's a number/string with decimals
                        if (typeof withoutTaxPrice === "string") {
                            withoutTaxPrice = withoutTaxPrice.split('.')[0];
                        } else if (typeof withoutTaxPrice === "number") {
                            withoutTaxPrice = Math.floor(withoutTaxPrice); // removes decimals
                        }

                        $('#without-tax-product').html(withoutTaxPrice);

                        if (data?.discount_percentage > 0) {
                            $('#discount-show').removeClass('d-none');
                            $('#discount-product-price').html('Rs. ' + data.discount_price);
                            $('#dis_per').html('( ' + data.discount_percentage + '% )');
                        } else {
                            $('#discount-show').addClass('d-none');
                        }

                        // packaging breakdown dynamic fill
                        $('#qty-per-piece-details').html(data?.qty_per_piece ?? '-');
                        $('#weight-per-piece-details').html(data?.weight_volume ?? '-');
                        $('#dimension-per-piece-details').html(data?.dimension ?? '-');

                        $('#qty-per-buffer-details').html(data?.qty_per_buffer_box ?? '-');
                        $('#weight-buffer-details').html(data?.weight_buffer_box ?? '-');
                        $('#dimension-buffer-details').html(data?.buffer_dimension ?? '-');

                        $('#qty-per-case-details').html(data?.total_qty_per_case ?? '-');
                        $('#weight-case-details').html(data?.weight_case ?? '-');
                        $('#dimension-case-details').html(data?.case_dimension ?? '-');

                        if (data?.dimension) {
                            $('#product-dimentions-div').removeClass('d-none');
                            $('#product-dimentions').html(data.dimension);
                        } else {
                            $('#product-dimentions-div').addClass('d-none');
                            $('#product-dimentions').html('-'); // optional fallback if needed
                        }


                        // let Roleprices = data?.role_base_price ?? [];
                        // if (Roleprices && Object.keys(Roleprices).length > 0) {
                        //     $('#rolePriceDiv').show(); // show the div
                        //     $('#rolePriceParentDiv').show(); // show the div
                        //     let tableBody = $('#rolePriceTable tbody');
                        //     tableBody.empty();

                        //     $.each(Roleprices, function(role, price) {
                        //         tableBody.append(`
                        //             <tr>
                        //                 <td>${role.toUpperCase()}</td>
                        //                 <td>${price}</td>
                        //             </tr>
                        //         `);
                        //     });
                        // } else {
                        //     $('#rolePriceParentDiv').hide(); // hide the div
                        //     $('#rolePriceDiv').hide(); // hide if empty
                        // }

                        let coa_url = data?.coa_url ?? null;

                        if (coa_url) {
                            $('#coaParentDiv').show();
                            $coaDiv.html(`
                                <div class="mt-4 text-center">
                                    <a href="${coa_url}" target="_blank"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-black text-sm font-semibold rounded hover:bg-blue-700 transition">
                                        View COA
                                    </a>
                                </div>
                            `);
                        } else {
                            $('#coaParentDiv').hide();
                            $coaDiv.empty(); // Hide if no COA
                        }


                        $('.input-number').prop('max', data.max_limit);
                        if(parseInt(data.in_stock) == 0 && data.digital  == 0){
                           $('.buy-now').addClass('d-none');
                           $('.add-to-cart').addClass('d-none');
                           $('.out-of-stock').removeClass('d-none');
                        }
                        else{
                           $('.buy-now').removeClass('d-none');
                           $('.add-to-cart').removeClass('d-none');
                           $('.out-of-stock').addClass('d-none');
                        }

                        AIZ.extra.plusMinus();
                    }
                });
            };

            if (immediate) {
                clearTimeout(variantPriceTimer);
                invoke();
                return;
            }
            }

            clearTimeout(variantPriceTimer);
            variantPriceTimer = setTimeout(invoke, variantPriceDebounceMs);
        }

        function checkAddToCartValidity(){
            var names = {};
            $('#option-choice-form input:radio').each(function() { // find unique names
                names[$(this).attr('name')] = true;
            });
            var count = 0;
            $.each(names, function() { // then count them
                count++;
            });

            if($('#option-choice-form input:radio:checked').length == count){
                return true;
            }

            return false;
        }

        function addToCart(){
            @if (Auth::check() && Auth::user()->user_type != 'customer')
                AIZ.plugins.notify('warning', "{{ translate('Please Login as a customer to add products to the Cart.') }}");
                return false;
            @endif

            if(checkAddToCartValidity()) {
                $('#addToCart').modal();
                $('.c-preloader').show();
                $.ajax({
                    type:"POST",
                    url: '{{ route('cart.addToCart') }}',
                    data: $('#option-choice-form').serializeArray(),
                    success: function(data){
                       $('#addToCart-modal-body').html(null);
                       $('.c-preloader').hide();
                       $('#modal-size').removeClass('modal-lg');
                       $('#addToCart-modal-body').html(data.modal_view);
                       AIZ.extra.plusMinus();
                       AIZ.plugins.slickCarousel();
                       updateNavCart(data.nav_cart_view,data.cart_count);
                    }
                });

                if ("{{ get_setting('facebook_pixel') }}" == 1){
                    // Facebook Pixel AddToCart Event
                    fbq('track', 'AddToCart', {content_type: 'product'});
                    // Facebook Pixel AddToCart Event
                }
            }
            else{
                AIZ.plugins.notify('warning', "{{ translate('Please choose all the options') }}");
            }
        }

        function buyNow(){
            @if (Auth::check() && Auth::user()->user_type != 'customer')
                AIZ.plugins.notify('warning', "{{ translate('Please Login as a customer to add products to the Cart.') }}");
                return false;
            @endif

            if(checkAddToCartValidity()) {
                $('#addToCart-modal-body').html(null);
                $('#addToCart').modal();
                $('.c-preloader').show();
                $.ajax({
                    type:"POST",
                    url: '{{ route('cart.addToCart') }}',
                    data: $('#option-choice-form').serializeArray(),
                    success: function(data){
                        if(data.status == 1){
                            $('#addToCart-modal-body').html(data.modal_view);
                            updateNavCart(data.nav_cart_view,data.cart_count);
                            window.location.replace("{{ route('cart') }}");
                        }
                        else{
                            $('#addToCart-modal-body').html(null);
                            $('.c-preloader').hide();
                            $('#modal-size').removeClass('modal-lg');
                            $('#addToCart-modal-body').html(data.modal_view);
                        }
                    }
               });
            }
            else{
                AIZ.plugins.notify('warning', "{{ translate('Please choose all the options') }}");
            }
        }

        function bid_single_modal(bid_product_id, min_bid_amount){
            @if (Auth::check() && (isCustomer() || isSeller()))
                var min_bid_amount_text = "({{ translate('Min Bid Amount: ') }}"+min_bid_amount+")";
                $('#min_bid_amount').text(min_bid_amount_text);
                $('#bid_product_id').val(bid_product_id);
                $('#bid_amount').attr('min', min_bid_amount);
                $('#bid_for_product').modal('show');
            @elseif (Auth::check() && isAdmin())
                AIZ.plugins.notify('warning', '{{ translate('Sorry, Only customers & Sellers can Bid.') }}');
            @else
                $('#login_modal').modal('show');
            @endif
        }

        function clickToSlide(btn,id){
            $('#'+id+' .aiz-carousel').find('.'+btn).trigger('click');
            $('#'+id+' .slide-arrow').removeClass('link-disable');
            var arrow = btn=='slick-prev' ? 'arrow-prev' : 'arrow-next';
            if ($('#'+id+' .aiz-carousel').find('.'+btn).hasClass('slick-disabled')) {
                $('#'+id).find('.'+arrow).addClass('link-disable');
            }
        }

        function goToView(params) {
            document.getElementById(params).scrollIntoView({behavior: "smooth", block: "center"});
        }

        function copyCouponCode(code){
            navigator.clipboard.writeText(code);
            AIZ.plugins.notify('success', "{{ translate('Coupon Code Copied') }}");
        }

        $(document).ready(function(){
            $('.cart-animate').animate({margin : 0}, "slow");

            $({deg: 0}).animate({deg: 360}, {
                duration: 2000,
                step: function(now) {
                    $('.cart-rotate').css({
                        transform: 'rotate(' + now + 'deg)'
                    });
                }
            });

            setTimeout(function(){
                $('.cart-ok').css({ fill: '#d43533' });
            }, 2000);

        });

        function nonLinkableNotificationRead(){
            $.get('{{ route('non-linkable-notification-read') }}',function(data){
                $('.unread-notification-count').html(data);
            });
        }
    </script>


    <script type="text/javascript">
        if ($('input[name=country_code]').length > 0){
            // Country Code
            var isPhoneShown = true,
                countryData = window.intlTelInputGlobals.getCountryData(),
                input = document.querySelector("#phone-code");

            for (var i = 0; i < countryData.length; i++) {
                var country = countryData[i];
                if (country.iso2 == 'bd') {
                    country.dialCode = '88';
                }
            }

            var iti = intlTelInput(input, {
                separateDialCode: true,
                utilsScript: "{{ static_asset('assets/js/intlTelutils.js') }}?1590403638580",
                onlyCountries: @php echo get_active_countries()->pluck('code') @endphp,
                customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
                    if (selectedCountryData.iso2 == 'bd') {
                        return "01xxxxxxxxx";
                    }
                    return selectedCountryPlaceholder;
                }
            });


            // Set default country code to +91 (India)
            iti.setCountry('in'); // 'in' is the ISO2 code for India

            var country = iti.getSelectedCountryData();
            $('input[name=country_code]').val(country.dialCode);

            input.addEventListener("countrychange", function(e) {
                // var currentMask = e.currentTarget.placeholder;
                var country = iti.getSelectedCountryData();
                $('input[name=country_code]').val(country.dialCode);

            });

            {{--
            function toggleEmailPhone(el) {
                if (isPhoneShown) {
                    $('.phone-form-group').addClass('d-none');
                    $('.email-form-group').removeClass('d-none');
                    $('input[name=phone]').val(null);
                    isPhoneShown = false;
                    $(el).html('*{{ translate('Use Phone Number Instead') }}');
                } else {
                    $('.phone-form-group').removeClass('d-none');
                    $('.email-form-group').addClass('d-none');
                    $('input[name=email]').val(null);
                    isPhoneShown = true;
                    $(el).html('<i>*{{ translate('Use Email Instead') }}</i>');
                }
            }
            --}}


            function toggleEmailPhone(el) {
                if (isPhoneShown) {
                    $('.phone-form-group').addClass('d-none');
                    $('.email-form-group').removeClass('d-none');
                    $('input[name=phone]').val(null);
                    isPhoneShown = false;
                    $(el).html('*{{ translate('Use Phone Number Instead') }}');

                    $('.toggle-login-with-otp').addClass('d-none');

                } else {
                    $('.phone-form-group').removeClass('d-none');
                    $('.email-form-group').addClass('d-none');
                    $('input[name=email]').val(null);
                    isPhoneShown = true;
                    $(el).html('<i>*{{ translate('Use Email Instead') }}</i>');

                    $('.toggle-login-with-otp').removeClass('d-none');
                }
                
                $('.submit-button').html('{{ translate('Login') }}');
                $('.password-login-block').removeClass('d-none');
                
                var url = '{{ route('login') }}';
                $('.loginForm').attr('action', url);
            }

            function toggleLoginPassOTP() {
                $('.password-login-block').addClass('d-none');
                $('.submit-button').html('{{ translate('Login With OTP') }}');

                var url = '{{ route('send-otp') }}';
                $('.loginForm').attr('action', url);
            }


        }
    </script>

    <script>
        var acc = document.getElementsByClassName("aiz-accordion-heading");
        var i;
        for (i = 0; i < acc.length; i++) {
            acc[i].addEventListener("click", function() {
                this.classList.toggle("active");
                var panel = this.nextElementSibling;
                if (panel.style.maxHeight) {
                    panel.style.maxHeight = null;
                } else {
                    panel.style.maxHeight = panel.scrollHeight + "px";
                }
            });
        }
    </script>

    <script>
        function showFloatingButtons() {
            document.querySelector('.floating-buttons-section').classList.toggle('show');;
        }
    </script>

    <script>
   $(document).ready(function(){
    $('#customers-testimonials').slick({
        infinite: true,
        slidesToShow: 3,
        centerPadding: '0px',
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 5000,
        centerMode: true, // Keeps the centered slide
        focusOnSelect: true,  // Ensures that the selected testimonial is active
        responsive: [
            {
                breakpoint: 1170,
                settings: {
                    slidesToShow: 3,
                     // Add padding for larger screens
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                    centerPadding: '0px', // Add padding for medium screens
                }
            },
             {
                breakpoint: 992,
                settings: {
                    slidesToShow: 1,
                    centerPadding: '0px', // Add padding for medium screens
                }
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 1,
                    centerPadding: '0px', // Add padding for smaller screens
                }
            }
        ]
    });

    // Apply opacity to inactive items
    $('#customers-testimonials').on('beforeChange', function(event, slick, currentSlide, nextSlide){
        var $slides = $(slick.$slides);
        $slides.each(function(index, slide) {
            $(slide).css('opacity', 1);  // Set all to opacity 0.5 initially
        });
    });

    $('#customers-testimonials').on('afterChange', function(event, slick, currentSlide){
        var $slides = $(slick.$slides);
        $slides.each(function(index, slide) {
            $(slide).css('opacity', 1);  // Reset opacity of all
        });
        $($slides[currentSlide]).css('opacity', 1);  // Set opacity of active slide to 1
    });
});
</script>


    @if (env("DEMO_MODE") == "On")
        <script>
            var demoNav = document.querySelector('.aiz-demo-nav');
            var menuBtn = document.querySelector('.aiz-demo-nav-toggler');
            var lineOne = document.querySelector('.aiz-demo-nav-toggler .aiz-demo-nav-btn .line--1');
            var lineTwo = document.querySelector('.aiz-demo-nav-toggler .aiz-demo-nav-btn .line--2');
            var lineThree = document.querySelector('.aiz-demo-nav-toggler .aiz-demo-nav-btn .line--3');
            menuBtn.addEventListener('click', () => {
                toggleDemoNav();
            });

            function toggleDemoNav() {
                // demoNav.classList.toggle('show');
                demoNav.classList.toggle('shadow-none');
                lineOne.classList.toggle('line-cross');
                lineTwo.classList.toggle('line-fade-out');
                lineThree.classList.toggle('line-cross');
                if ($('.aiz-demo-nav-toggler').hasClass('show')) {
                    $('.aiz-demo-nav-toggler').removeClass('show');
                    demoHideOverlay();
                }else{
                    $('.aiz-demo-nav-toggler').addClass('show');
                    demoShowOverlay();
                }
            }

            $('.aiz-demos').click(function(e){
                if (!e.target.closest('.aiz-demos .aiz-demo-content')) {
                    toggleDemoNav();
                }
            });

            function demoShowOverlay(){
                $('.top-banner').removeClass('z-1035').addClass('z-1');
                $('.top-navbar').removeClass('z-1035').addClass('z-1');
                $('header').removeClass('z-1020').addClass('z-1');
                $('.aiz-demos').addClass('show');
            }

            function demoHideOverlay(cls=null){
                if($('.aiz-demos').hasClass('show')){
                    $('.aiz-demos').removeClass('show');
                    $('.top-banner').delay(800).removeClass('z-1').addClass('z-1035');
                    $('.top-navbar').delay(800).removeClass('z-1').addClass('z-1035');
                    $('header').delay(800).removeClass('z-1').addClass('z-1020');
                }
            }
        </script>
    @endif


    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>



    @yield('script')

    @stack('scripts')


    @php
        echo get_setting('footer_script');
    @endphp

    
    @include('frontend.not_approval_model')


    @yield('custome-script')

    @if (Session::has('registartion_status'))
        @php
            Session::forget('registartion_status');
        @endphp

        <script>
            $('#not_approval_model').modal('show');
        </script>
    @endif

    @yield('custome-script-addr-edit')

    
        <!-- Start of LiveChat (www.livechat.com) code -->
        <script>
            window.__lc = window.__lc || {};
            window.__lc.license = 18993779;
            window.__lc.integration_name = "manual_channels";
            window.__lc.product_name = "livechat";
            ;(function(n,t,c){function i(n){return e._h?e._h.apply(null,n):e._q.push(n)}var e={_q:[],_h:null,_v:"2.0",on:function(){i(["on",c.call(arguments)])},once:function(){i(["once",c.call(arguments)])},off:function(){i(["off",c.call(arguments)])},get:function(){if(!e._h)throw new Error("[LiveChatWidget] You can't use getters before load.");return i(["get",c.call(arguments)])},call:function(){i(["call",c.call(arguments)])},init:function(){var n=t.createElement("script");n.async=!0,n.type="text/javascript",n.src="https://cdn.livechatinc.com/tracking.js",t.head.appendChild(n)}};!n.__lc.asyncInit&&e.init(),n.LiveChatWidget=n.LiveChatWidget||e}(window,document,[].slice))
        </script>
        <noscript><a href="https://www.livechat.com/chat-with/18993779/" rel="nofollow">Chat with us</a>, powered by <a href="https://www.livechat.com/?welcome" rel="noopener nofollow" target="_blank">LiveChat</a></noscript>
        <!-- End of LiveChat code -->
    
        <script>
            /*
var a = 0;
$(window).scroll(function() {

  var oTop = $('#counter').offset().top - window.innerHeight;
  if (a == 0 && $(window).scrollTop() > oTop) {
    $('.counter-value').each(function() {
      var $this = $(this),
        countTo = $this.attr('data-count');
      $({
        countNum: $this.text()
      }).animate({
          countNum: countTo
        },

        {

          duration: 2000,
          easing: 'swing',
          step: function() {
            $this.text(Math.floor(this.countNum));
          },
          complete: function() {
            $this.text(this.countNum);
            //alert('finished');
          }

        });
    });
    a = 1;
  }

});
*/

$(function() {
  var a = 0;

  $(window).scroll(function() {
    var $counter = $('#counter');
    if ($counter.length === 0) return; // safety check

    var oTop = $counter.offset().top - window.innerHeight;
    if (a === 0 && $(window).scrollTop() > oTop) {
      $('.counter-value').each(function() {
        var $this = $(this),
            countTo = $this.attr('data-count');
        $({ countNum: $this.text() }).animate(
          { countNum: countTo },
          {
            duration: 2000,
            easing: 'swing',
            step: function() {
              $this.text(Math.floor(this.countNum));
            },
            complete: function() {
              $this.text(this.countNum);
            }
          }
        );
      });
      a = 1;
    }
  });
});
</script>

<!-- jQuery Script for Hover Effect -->
<script>
    $(document).ready(function() {
        $('.dropdown').hover(function() {
            $(this).find('.dropdown-menu').stop(true, true).delay(200).fadeIn(200);
        }, function() {
            $(this).find('.dropdown-menu').stop(true, true).delay(200).fadeOut(200);
        });
    });
</script>

<script>
function scrollTabs(direction) {
  const wrapper = document.querySelector('.tab-scroll-wrapper-new');
  const scrollAmount = 150;

  if (direction === 'left') {
    wrapper.scrollLeft -= scrollAmount;
  } else {
    wrapper.scrollLeft += scrollAmount;
  }
}
</script>

<script>
    $(document).ready(function() {
        $(".human_btn, .veterinary_btn, .home_btn").click(function(e) {
            e.preventDefault();

            let type; // Declare type here so it has function scope

            if ($(this).hasClass("home_btn")) {
                type = "Veterinary";
            } else {
                type = $(this).hasClass("human_btn") ? "Human" : "Veterinary";
                var home = "{{ route('home') }}"; 
            }


            $.ajax({
                url: "{{ route('set.web.type') }}", 
                type: "POST",
                data: {
                    type: type,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        if (type === "Human") {
                            home = "{{ route('human') }}";
                            window.location.href = home;
                        } else if (type === "Veterinary") {
                            home = "{{ route('home') }}";
                            window.location.href = home;
                        }

                    } else {
                        window.location.href = home;
                    }
                },
                error: function() {
                    window.location.href = home;
                }
            });
        });
    });
</script>
@yield('custom_script')

@auth
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var prescBtn = document.getElementById('prescription-btn');
        if (prescBtn) {
            prescBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var prescModal = new bootstrap.Modal(document.getElementById('prescriptionModal'));
                prescModal.show();
            });
        }
    });


    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('#prescriptionModal form');
        const emailInput = document.getElementById('presc_email');
        const phoneInput = document.getElementById('presc_phone');

        form.addEventListener('submit', function (e) {
            if (!emailInput.value.trim() && !phoneInput.value.trim()) {
                e.preventDefault();
                toastr.error("Please enter either Email or Phone.");
            }
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        window.closePrescriptionModal = function() {
            $('#prescriptionModal').modal('hide'); // requires jQuery + bootstrap.js
            console.dir("Modal closed (Bootstrap 4)");
        };
    });

</script>


@endauth


<!-- ===================== GOOGLE TRANSLATE INIT (keep only this one) ===================== -->
<script>
  function googleTranslateElementInit() {
    new google.translate.TranslateElement({ pageLanguage: 'en' }, 'google_translate_element');
  }
</script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<!-- ===================== APP LOGIC (full, with cookie-domain fix) ===================== -->
<script>
(function($){
  // ---- CONFIG ----
  var currencyChangeUrl = '{{ route('currency.change') }}';
  var csrfToken = (window.AIZ && AIZ.data && AIZ.data.csrf) ? AIZ.data.csrf : '{{ csrf_token() }}';

  // ---- Language list with flags ----
  var languages = [
    { code: "en",    name: "English",               flag: "https://flagcdn.com/w20/us.png" },
    { code: "fr",    name: "French",                flag: "https://flagcdn.com/w20/fr.png" },
    { code: "de",    name: "German",                flag: "https://flagcdn.com/w20/de.png" },
    { code: "es",    name: "Spanish",               flag: "https://flagcdn.com/w20/es.png" },
    { code: "hi",    name: "Hindi",                 flag: "https://flagcdn.com/w20/in.png" },
    { code: "mr",    name: "Marathi",               flag: "https://flagcdn.com/w20/in.png" },
    { code: "gu",    name: "Gujarati",              flag: "https://flagcdn.com/w20/in.png" },
    { code: "ta",    name: "Tamil",                 flag: "https://flagcdn.com/w20/in.png" },
    { code: "te",    name: "Telugu",                flag: "https://flagcdn.com/w20/in.png" },
    { code: "bn",    name: "Bengali",               flag: "https://flagcdn.com/w20/bd.png" },
    { code: "pa",    name: "Punjabi",               flag: "https://flagcdn.com/w20/in.png" },
    { code: "ur",    name: "Urdu",                  flag: "https://flagcdn.com/w20/pk.png" },
    { code: "ar",    name: "Arabic",                flag: "https://flagcdn.com/w20/sa.png" },
    { code: "it",    name: "Italian",               flag: "https://flagcdn.com/w20/it.png" },
    { code: "ja",    name: "Japanese",              flag: "https://flagcdn.com/w20/jp.png" },
    { code: "ru",    name: "Russian",               flag: "https://flagcdn.com/w20/ru.png" },
    { code: "zh-CN", name: "Chinese (Simplified)",  flag: "https://flagcdn.com/w20/cn.png" },
    { code: "zh-TW", name: "Chinese (Traditional)", flag: "https://flagcdn.com/w20/tw.png" }
  ];

  // ==================== COOKIE HELPERS (robust, base-domain only) ====================
  // Get base domain (eTLD+1) for cookies: works for most TLDs; extend two-part list if needed.
  function getBaseDomain() {
    var h = location.hostname; // e.g., "dotcompharma.webtesting.pw"
    if (h === 'localhost' || /^\d+\.\d+\.\d+\.\d+$/.test(h)) return h; // localhost or IP

    var parts = h.split('.');
    if (parts.length <= 2) return h; // already base (e.g., webtesting.pw)

    var twoPartTLDs = [
      'co.in','com.au','co.uk','org.uk','gov.uk','com.br','com.mx','com.tr','co.nz','com.sg'
    ];
    var last2 = parts.slice(-2).join('.');
    var last3 = parts.slice(-3).join('.');

    if (twoPartTLDs.indexOf(last2) !== -1 && parts.length >= 3) {
      return last3; // e.g., example.co.in
    }
    return last2; // e.g., webtesting.pw
  }

  function deleteCookieEverywhere(name) {
    var base = getBaseDomain();
    var past = 'Thu, 01 Jan 1970 00:00:01 GMT';
    var paths = ['/']; // could add more paths if you set different ones

    // Try multiple domains to ensure removal of duplicates
    var domains = [
      undefined,                             // host-only
      '.' + location.hostname,               // dot-current-host
      '.' + base,                            // dot-base
      base                                   // bare base
    ];

    domains.forEach(function(dom){
      paths.forEach(function(p){
        var c = name + '=;expires=' + past + ';path=' + p;
        if (dom) c += ';domain=' + dom;
        document.cookie = c;
      });
    });
  }

  function setCookieBaseDomain(name, value, days) {
    var base = getBaseDomain();
    var exp = new Date(Date.now() + days*24*60*60*1000).toUTCString();
    var attrs = ';path=/;SameSite=Lax' + (location.protocol === 'https:' ? ';Secure' : '');
    document.cookie = name + '=' + encodeURIComponent(value) + ';expires=' + exp + ';domain=.' + base + attrs;
  }

  function getCookie(name) {
    var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
  }

  // Write ONE shared googtrans cookie at base domain and remove duplicates.
  function setGoogleTranslateLang(langCode) {
    var val = '/auto/' + langCode;
    deleteCookieEverywhere('googtrans');
    setCookieBaseDomain('googtrans', val, 365);
  }

  function getGoogleTranslateLangFromCookie() {
    var v = getCookie('googtrans'); // e.g. "/auto/hi"
    if (!v) return null;
    var parts = v.split('/');
    return parts.length >= 3 ? parts[2] : null;
  }
  // ==================== END COOKIE HELPERS ====================

  // ---- Build language dropdown with flags + Select2 ----
  function buildLanguageDropdown() {
    var $dd = $('#languageDropdown');
    $dd.empty();
    languages.forEach(function(l){
      var $opt = $('<option/>', { value: l.code, text: l.name });
      $opt.attr('data-flag', l.flag);
      $dd.append($opt);
    });

    function tpl(opt) {
      if (!opt.id) return opt.text;
      var flag = $(opt.element).data('flag');
      var text = opt.text || '';
      return $(
        '<span style="display:flex;align-items:center;gap:8px;">' +
          (flag ? '<img src="'+flag+'" style="width:20px;height:14px;object-fit:cover;border-radius:2px;" alt="">' : '') +
          '<span>'+ text +'</span>' +
        '</span>'
      );
    }

    if ($dd.select2) {
      $dd.select2({
        width: '100%',
        templateResult: tpl,
        templateSelection: tpl,
        minimumResultsForSearch: 5
      });
    }

    // Preselect from cookie if any
    var currentLang = getGoogleTranslateLangFromCookie();
    if (currentLang && $dd.find('option[value="'+currentLang+'"]').length) {
      $dd.val(currentLang).trigger('change.select2');
    }
  }

  // ---- Update nav labels from current state (on load) ----
  function updateNavLabelsFromState() {
    var langCode = getGoogleTranslateLangFromCookie() || 'en';
    var match = languages.find(function(l){ return l.code.toLowerCase() === langCode.toLowerCase(); });
    $('#selectedLang').text(match ? match.name : 'English');
    // Currency label is server-rendered; nothing else needed here.
  }

  // ---- Save handler ----
  function wireSave() {
    $('#saveLangCurrency').on('click', function() {
      var $btn = $(this).prop('disabled', true).text('Applying...');
      var selectedLang = $('#languageDropdown').val();
      var selectedCurrency = $('#currencyDropdown').val();
      var initialCurrency  = $('#currencyDropdown').data('initial');

      if (selectedLang) setGoogleTranslateLang(selectedLang);

      var needsCurrencyPost = selectedCurrency && (selectedCurrency !== initialCurrency);
      function reload() { window.location.reload(); }

      if (needsCurrencyPost) {
        $.post(currencyChangeUrl, { _token: csrfToken, currency_code: selectedCurrency })
          .always(reload);
      } else {
        reload();
      }
    });

    // Optional live preview in nav (no persistence until Save)
    $('#languageDropdown').on('change', function() {
      $('#selectedLang').text($('#languageDropdown option:selected').text().trim());
    });
    $('#currencyDropdown').on('change', function() {
      var $opt = $('#currencyDropdown option:selected');
      $('#selectedCurrency').text(($opt.data('symbol') || '') + ' ' + ($opt.data('name') || ''));
    });
  }

  // ---- Init ----
  $(function(){
    buildLanguageDropdown();
    updateNavLabelsFromState();
    wireSave();
  });

})(jQuery);
</script>

  <!-- Google Translate script -->
  <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<script src="https://hcaptcha.com/1/api.js" async defer></script>

</body>
</html>
