

<style>
    .flag-option {
      display: flex;
      align-items: center;
      gap: 6px;
          font-size: 12px;
    }
    .flag-option img {
      width: 20px;
      height: 14px;
    }
    .translater_menu span.select2-selection.select2-selection--single {
    background-color: #fff;
    border: 1px solid #2b56a1;
    border-radius: 4px;
    font-size: 13px;
    margin-top: 10px;
    border-radius: 8px;
    height: 25px;
}
.translater_menu .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: #2b56a1 !important;
    color: white;
}
    .translater_menu .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #444;
    line-height: 23px;
    color: #2b56a1;
    font-size: 11px;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #444;
    line-height: 35px;
}
.translater_menu select#languageDropdown {
    width: 140px;
    border-radius: 8px;
    border: 1px solid #2b56a1;
    margin-top: 9px;
}
body .translater_menu .select2-container {
    width: 100px !important;
}


.select2-container--default .select2-selection--single {
    background-color: #fff;
    border: 1px solid #aaa;
    border-radius: 4px;
    height: 38px;
    border-radius: 10px;
    border: 1px solid #dfdfe6;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 33px;
    position: absolute;
    top: 1px;
    right: 1px;
    width: 20px
}

.translater_menu .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 41px;
}
.select2-results__option {
    padding: 1px 6px;
    user-select: none;
    -webkit-user-select: none;
}
.translater_menu .select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: #2b56a1 transparent transparent transparent;
}

.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: #2b56a1 !important;
    color: white;
}

.select2-container--default .select2-results>.select2-results__options {
    max-height: 110px;
    overflow-y: auto
}

/* Custom placeholder slider animation */
.search-input-box {
    position: relative;
}

.custom-placeholder {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #999;
    font-size: 14px;
    white-space: nowrap;
    z-index: 1;
}

.search-input-box input:focus ~ .custom-placeholder,
.search-input-box input:not(:placeholder-shown) ~ .custom-placeholder,
.search-input-box input[value]:not([value=""]) ~ .custom-placeholder {
    display: none;
}

.placeholder-fixed {
    color: #999;
    font-weight: normal;
}

.placeholder-sliding-container {
    display: inline-block;
    height: 20px;
    overflow: hidden;
    vertical-align: middle;
    position: relative;
}

.placeholder-sliding {
    font-weight: bold;
    display: block;
    color: #000000;
    line-height: 20px;
    transform: translateY(0);
    opacity: 1;
}

.placeholder-sliding.animate-out {
    animation: placeholderScrollDown 1.2s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.placeholder-sliding.animate-in {
    animation: placeholderScrollUp 1.2s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes placeholderScrollDown {
    0% {
        transform: translateY(0);
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
    100% {
        transform: translateY(-100%);
        opacity: 0;
    }
}

@keyframes placeholderScrollUp {
    0% {
        transform: translateY(100%);
        opacity: 0;
    }
    50% {
        opacity: 0.5;
    }
    100% {
        transform: translateY(0);
        opacity: 1;
    }
}

@media (max-width: 767px) {
    .translater_menu span.select2-selection.select2-selection--single {
        margin-top: 10px;
    }
    body .translater_menu .select2-container {
        width: 75px !important;
    }

    .flag-option img{
        width: 16px;
        height: 10px;
    }

    .flag-option{
        font-size: 10px;
    }
}

@media (max-width: 375px) {
    .translater_menu .select2-container--default .select2-selection--single .select2-selection__rendered{
        line-height: 20px;
    }

    .translater_menu span.select2-selection.select2-selection--single{
        height: 22px;
        margin-top: 1px;
    }

    .translater_menu .select2-container--default .select2-selection--single .select2-selection__arrow{
        height: 20.5px;
    }

}
</style>
<!-- Top Bar Banner -->
    @php
        $topbar_banner = get_setting('topbar_banner');
        $topbar_banner_medium = get_setting('topbar_banner_medium');
        $topbar_banner_small = get_setting('topbar_banner_small');
        $topbar_banner_asset = uploaded_asset($topbar_banner);
    @endphp
    @if ($topbar_banner != null)
        <div class="position-relative top-banner removable-session z-1035 d-none" data-key="top-banner"
            data-value="removed">
            <a href="{{ get_setting('topbar_banner_link') }}" class="d-block text-reset h-40px h-lg-60px">
                <!-- For Large device -->
                <img src="{{ $topbar_banner_asset }}" class="d-none d-xl-block img-fit h-100"
                    alt="{{ translate('topbar_banner') }}">
                <!-- For Medium device -->
                <img src="{{ $topbar_banner_medium != null ? uploaded_asset($topbar_banner_medium) : $topbar_banner_asset }}"
                    class="d-none d-md-block d-xl-none img-fit h-100" alt="{{ translate('topbar_banner') }}">
                <!-- For Small device -->
                <img src="{{ $topbar_banner_small != null ? uploaded_asset($topbar_banner_small) : $topbar_banner_asset }}"
                    class="d-md-none img-fit h-100" alt="{{ translate('topbar_banner') }}">
            </a>
            <button class="btn text-white h-100 absolute-top-right set-session" data-key="top-banner"
                data-value="removed" data-toggle="remove-parent" data-parent=".top-banner">
                <i class="la la-close la-2x"></i>
            </button>
        </div>
    @endif

    <!-- Top Bar -->
    <div class="top-navbar bg-white z-1035 h-35px h-sm-auto">
        <div class="container">
            <div class="row d-flex">

                <div class="col-xl-5 col-lg-6 col-md-4 col-8">
                    <ul class="list-inline d-flex justify-content-lg-start mb-0 top_baar_icons">
                        <li class="list-inline-item d-lg-block d-none">
                            <a href="tel:+918828111034" class=" text-secondary fs-12 py-2">
                                <img class="w-100" src="{{ static_asset('assets/img/call1_icons.svg') }}" />+91 88281
                                11034
                            </a>
                        </li>

                        <li class="list-inline-item d-lg-block d-none">
                            <a href="mailto:info@dotcompharmaindia.com" class=" text-secondary fs-12 py-2">
                                <img class="w-100" src="{{ static_asset('assets/img/helps_icons.svg') }}" />Need Help?
                            </a>
                        </li>

                        <li class="list-inline-item veterinary_btn">
                            <a href="javascript:void(0)"
                                class=" fs-12 py-2 {{ session('web_type_name') == 'veterinary' ? 'active_btn' : '' }}">
                                <i class="las la-dog"></i> Veterinary
                            </a>
                        </li>

                        <li class="list-inline-item human_btn">
                            <a href="javascript:void(0)"
                                class=" fs-12 py-2 {{ session('web_type_name') == 'human' ? 'active_btn' : '' }}">
                                <i class="las la-user-alt"></i> Human
                            </a>
                        </li>
                    </ul>
                </div>


                <div class="col-xl-7 col-lg-6 col-md-8 col-4 d-flex justify-content-end">


                    <ul class="list-inline d-flex justify-content-end mb-0">


                        <li class="list-inline-item d-none d-md-inline mr-md-3 mr-3">
                            <a class="b2b_buttons d-grid align-items-center" href="{{ route('user.new_registration') }}">
                                <span><i class="las la-sign-in-alt"></i></span> <span>B2B Registration</span> 
                            </a>
                        </li>

                        {{--  --}}
                        <!-- prescription -->
                         @auth
                            <li class="list-inline-item d-none d-md-inline mr-md-3 mr-2">
                                <a href="javascript:void(0)" id="prescription-btn" class="ml-0" title="Upload Prescription">
                                    <i class="fa fa-file-prescription"></i> Prescription</a>
                            </li>
                        @endauth 
                        {{--  --}}


                        <li class="list-inline-item mr-md-3 mr-3">
                            {{-- <!-- Hidden Google Translate -->
                            <div id="google-translate-dropdown" style="display:none;"></div>
                            <!-- Custom Dropdown -->
                            <select id="languageDropdown" style="display:none;"></select> --}}
                            <button type="button" class="btn language-currency btn-outline-dark btn-sm d-flex align-items-center"
                                        data-toggle="modal" data-target="#languageCurrencyModal">
                                <i class="fa fa-globe mr-1"></i>
                                <span id="selectedLang">English</span>
                                <span class="mx-1">|</span>
                                <span class="d-none d-md-inline" id="selectedCurrency">
                                    {{ get_system_currency()->symbol . ' ' . (get_system_currency()->name ?? '-') }}
                                </span>
                                <span class="d-inline d-md-none" id="selectedCurrency">
                                    {{ get_system_currency()->symbol }}
                                </span>
                            </button>
                        </li>

                        

                        <!-- Language switcher -->
                        {{-- <!-- @if (get_setting('show_language_switcher') == 'on')
                            <li class="list-inline-item dropdown mr-3" id="lang-change">

                                <a href="javascript:void(0)" class="black_light_clr dropdown-toggle fs-12 py-2"
                                    data-toggle="dropdown" data-display="static">
                                    <span class="">{{ $system_language->name }}</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-left">
                                    @foreach (get_all_active_language() as $key => $language)
                                        <li>
                                            <a href="javascript:void(0)" data-flag="{{ $language->code }}"
                                                class="dropdown-item @if ($system_language->code == $language->code) active @endif">
                                                <img src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                    data-src="{{ static_asset('assets/img/flags/' . $language->code . '.png') }} "
                                                    class="mr-1 lazyload" alt="{{ $language->name }}" height="11">
                                                <span class="language">{{ $language->name }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endif --> --}}

                        <!-- Currency Switcher -->
                        {{-- @if (get_setting('show_currency_switcher') == 'on')
                            <li class="list-inline-item dropdown ml-auto ml-lg-0 mr-0" id="currency-change">
                                @php
                                    $system_currency = get_system_currency();
                                @endphp

                                <a href="javascript:void(0)" 
                                    class="dropdown-toggle black_light_clr fs-12 pl-md-1 pr-md-1"
                                    data-toggle="dropdown" data-display="static">

                                        <span class="d-none d-md-inline">
                                            {{ $system_currency->name }} ({{ $system_currency->symbol }})
                                        </span>
                                        <span class="d-inline d-md-none">
                                            {{ $system_currency->symbol }}
                                        </span>
                                </a>

                                <ul class="dropdown-menu dropdown-menu-right dropdown-menu-lg-left">
                                    @foreach (get_all_active_currency() as $key => $currency)
                                        <li>
                                            <a class="dropdown-item @if ($system_currency->code == $currency->code) active @endif"
                                                href="javascript:void(0)"
                                                data-currency="{{ $currency->code }}">{{ $currency->name }}
                                                ({{ $currency->symbol }})
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endif --}}

                    </ul>
                </div>

                <div class="col-6 text-right d-none d-lg-block">
                    <ul class="list-inline mb-0 h-100 d-flex justify-content-end align-items-center">
                        @if (get_setting('vendor_system_activation') == 1)
                            <!-- Become a Seller -->
                            <li class="list-inline-item mr-0 pl-0 py-2">
                                <a href="{{ route('shops.create') }}"
                                    class="text-secondary fs-12 pr-3 d-inline-block border-width-2 border-right">{{ translate('Become a Seller !') }}</a>
                            </li>
                            <!-- Seller Login -->
                            <li class="list-inline-item mr-0 pl-0 py-2">
                                <a href="{{ route('seller.login') }}"
                                    class="text-secondary fs-12 pl-3 d-inline-block">{{ translate('Login to Seller') }}</a>
                            </li>
                        @endif
                        @if (get_setting('helpline_number'))
                            <!-- Helpline -->
                            <li class="list-inline-item ml-3 pl-3 mr-0 pr-0">
                                <a href="tel:{{ get_setting('helpline_number') }}"
                                    class="text-secondary fs-12 d-inline-block py-2">
                                    <span>{{ translate('Helpline') }}</span>
                                    <span>{{ get_setting('helpline_number') }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <header class="@if (get_setting('header_stikcy') == 'on') sticky-top @endif z-1020 bg-white">
        <!-- Search Bar -->
        <div class="position-relative logo-bar-area border-bottom border-md-nonea z-1025">
            <div class="container">
                <div class="">
                    <div class="row">

                        <div class="col-lg-3 col-md-4 col-2 d-lg-none d-block">
                            <!-- top menu sidebar button -->
                            <button type="button" class="btn d-lg-none mr-3 mr-sm-4 p-0 active mobile_icons_menus"
                                data-toggle="class-toggle" data-target=".aiz-top-menu-sidebar">
                                <svg id="Component_43_1" data-name="Component 43 – 1"
                                    xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 20 20">
                                    <rect id="Rectangle_19062" data-name="Rectangle 19062" width="20"
                                        height="2" transform="translate(0 7)" fill="#919199" />
                                    <rect id="Rectangle_19063" data-name="Rectangle 19063" width="20"
                                        height="2" fill="#919199" />
                                    <rect id="Rectangle_19064" data-name="Rectangle 19064" width="20"
                                        height="2" transform="translate(0 14)" fill="#919199" />
                                </svg>

                            </button>
                        </div>
                        <div class="col-lg-3 col-md-4 col-8">
                            <!-- Header Logo -->
                            <div class="col-auto pl-0 pr-lg-3 pr-md-0 d-flex align-items-center">
                                <a class="d-block home_btn" href="javascript:void(0)">
                                    @php
                                        $header_logo = get_setting('header_logo');
                                    @endphp
                                    @if ($header_logo != null)
                                        <img class="logo_main" src="{{ uploaded_asset($header_logo) }}"
                                            alt="{{ env('APP_NAME') }}">
                                    @else
                                        <img class="logo_main" src="{{ static_asset('assets/img/logo.png') }}"
                                            alt="{{ env('APP_NAME') }}">
                                    @endif
                                </a>
                            </div>
                        </div>


                        <div class="col-md-6 d-lg-block d-none">

                            <div class="flex-grow-1 front-header-search active d-flex align-items-center bg-white">
                        <div class="position-relative flex-grow-1 px-3 px-lg-0">
                            <form action="{{ route('search') }}" method="GET" class="stop-propagation">
                                <div class="d-flex position-relative align-items-center">
                                    <div class="search-toggle-side-nav-bar" data-toggle="class-toggle" data-target=".front-header-search">
                                        <button class="btn px-2" type="button"><i
                                                class="la la-2x la-long-arrow-left"></i></button>
                                    </div>
                                    <div class="search-input-box position-relative">
                                        <input type="text"
                                            class="border border-soft-light form-control fs-14 hov-animate-outline"
                                            id="search" name="keyword"
                                            @isset($query)
                                            value="{{ $query }}"
                                        @endisset
                                            placeholder=" " autocomplete="off" data-placeholder-slider="true">
                                        <span class="custom-placeholder" id="custom-placeholder">
                                            <span class="placeholder-fixed">{{ translate('Search for') }}</span>
                                            <span class="placeholder-sliding-container">
                                                <span class="placeholder-sliding"></span>
                                            </span>
                                        </span>

                                        <svg id="Group_723" data-name="Group 723" xmlns="http://www.w3.org/2000/svg"
                                            width="20.001" height="20" viewBox="0 0 20.001 20">
                                            <path id="Path_3090" data-name="Path 3090"
                                                d="M9.847,17.839a7.993,7.993,0,1,1,7.993-7.993A8,8,0,0,1,9.847,17.839Zm0-14.387a6.394,6.394,0,1,0,6.394,6.394A6.4,6.4,0,0,0,9.847,3.453Z"
                                                transform="translate(-1.854 -1.854)" fill="#b5b5bf" />
                                            <path id="Path_3091" data-name="Path 3091"
                                                d="M24.4,25.2a.8.8,0,0,1-.565-.234l-6.15-6.15a.8.8,0,0,1,1.13-1.13l6.15,6.15A.8.8,0,0,1,24.4,25.2Z"
                                                transform="translate(-5.2 -5.2)" fill="#b5b5bf" />
                                        </svg>
                                    </div>
                                </div>
                            </form>
                            <div class="typed-search-box stop-propagation document-click-d-none d-none bg-white rounded shadow-lg position-absolute left-0 top-100 w-100"
                                style="min-height: 200px">
                                <div class="search-preloader absolute-top-center">
                                    <div class="dot-loader">
                                        <div></div>
                                        <div></div>
                                        <div></div>
                                    </div>
                                </div>
                                <div class="search-nothing d-none p-3 text-center fs-16">

                                </div>
                                <div id="search-content" class="text-left">

                                </div>
                            </div>
                        </div>
                    </div>
                            {{-- <div class="w-100 logo_menu">
                                <div class="d-flex align-items-center justify-content-center h-100">
                                    <ul class="list-inline mb-0 pl-0"> --}}
                                        <!-- Dropdown for Injections -->

                                        {{-- @php
                                            use App\Models\Category;
                                            use Illuminate\Support\Facades\Cache;

                                            if (!session()->has('web_type')) {
                                                $catData = Category::whereRaw('LOWER(name) = ?', [strtolower('veterinary')])
                                                    ->first(['id', 'name']);

                                                if ($catData) {
                                                    session()->put('web_type', $catData->id);
                                                    session()->put('web_type_name', strtolower($catData->name));
                                                }
                                            }

                                            $webTypeId = session('web_type');
                                            $webTypeName = session('web_type_name');

                                            $catHumanId = [58, 43, 70, 68, 72]; // Ensure IDs are integers
                                            $catVeterinaryId = [85, 86, 87, 88, 89];

                                            // Create a cache key based on web type
                                            $cacheKey = 'category_top_menu_' . ($webTypeName ?? 'default');

                                            // Retrieve from cache or generate and cache it
                                            $category_top_menu = Cache::rememberForever($cacheKey, function () use ($webTypeName, $webTypeId, $catHumanId, $catVeterinaryId) {
                                                if ($webTypeName == 'human') {
                                                    return Category::select('id', 'parent_id', 'name', 'slug')
                                                        ->whereIn('id', $catHumanId)
                                                        ->where('parent_id', $webTypeId)
                                                        ->with('childrenCategories')
                                                        ->orderByRaw('FIELD(id, ' . implode(',', $catHumanId) . ')')
                                                        ->get();
                                                } elseif ($webTypeName == 'veterinary') {
                                                    return Category::select('id', 'parent_id', 'name', 'slug')
                                                        ->whereIn('id', $catVeterinaryId)
                                                        ->where('parent_id', $webTypeId)
                                                        ->with('childrenCategories')
                                                        ->orderByRaw('FIELD(id, ' . implode(',', $catVeterinaryId) . ')')
                                                        ->get();
                                                } else {
                                                    return collect();
                                                }
                                            });
                                        @endphp --}}
                                        {{-- @php $category_top_menu = getCategoryTopMenu(); @endphp

                                        @foreach ($category_top_menu as $cat)
                                            <li class="list-inline-item mr-0 animate-underline-white dropdown">
                                                <a href="#"
                                                    class="fs-14 d-inline-block fw-500 header_menu_links dropdown-toggle"
                                                    id="injectionsDropdown_{{ $cat->id }}"
                                                    data-toggle="dropdown" aria-haspopup="true"
                                                    aria-expanded="false">
                                                    {{ $cat->name }}
                                                </a>
                                                <div class="dropdown-menu"
                                                    aria-labelledby="injectionsDropdown_{{ $cat->id }}">
                                                    @foreach ($cat->childrenCategories as $childCategory)
                                                        <a class="dropdown-item"
                                                            href="/category/{{ $childCategory->slug }}">{{ $childCategory->name }}</a>
                                                    @endforeach
                                                </div>
                                            </li>
                                        @endforeach --}}

                                        {{-- <li class="list-inline-item mr-0 animate-underline-white dropdown">
                                            <a href="#" class="fs-14 d-inline-block fw-500 header_menu_links dropdown-toggle" id="injectionsDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                Bolus
                                            </a>
                                            <div class="dropdown-menu" aria-labelledby="injectionsDropdown">
                                                <a class="dropdown-item" href="/search">Pets</a>
                                                <a class="dropdown-item" href="/search">Large Animal</a>
                                                <a class="dropdown-item" href="/search">Small Animal</a>
                                            </div>
                                        </li>

                                        <li class="list-inline-item mr-0 animate-underline-white dropdown">
                                            <a href="#" class="fs-14 d-inline-block fw-500 header_menu_links dropdown-toggle" id="injectionsDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                Tablet & Capsules
                                            </a>
                                            <div class="dropdown-menu" aria-labelledby="injectionsDropdown">
                                                <a class="dropdown-item" href="/search">Pets</a>
                                            </div>
                                        </li>

                                        <li class="list-inline-item mr-0 animate-underline-white dropdown">
                                            <a href="#" class="fs-14 d-inline-block fw-500 header_menu_links dropdown-toggle" id="injectionsDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                               Oral
                                            </a>
                                            <div class="dropdown-menu" aria-labelledby="injectionsDropdown">
                                                <a class="dropdown-item" href="/search">Powders</a>
                                                <a class="dropdown-item" href="/search">Suspensions</a>
                                            </div>
                                        </li>

                                        <li class="list-inline-item mr-0 animate-underline-white dropdown">
                                            <a href="#" class="fs-14 d-inline-block fw-500 header_menu_links dropdown-toggle" id="injectionsDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                               Intra-Uterine
                                            </a>
                                            <div class="dropdown-menu" aria-labelledby="injectionsDropdown">
                                                <a class="dropdown-item" href="/search">Powders</a>
                                                <a class="dropdown-item" href="/search">Suspensions</a>
                                            </div>
                                        </li> --}}
                                    {{-- </ul>
                                </div>
                            </div> --}}
                        </div>

                        <div class="col-lg-3 col-md-4 col-2">
                            <div class="display_flex1">
                                <!-- Search Icon for small device -->
                                <div class="ml-auto mr-0 search-toggle-side-nav-bar">
                                    <a class="p-0 d-block" href="javascript:void(0);" data-toggle="class-toggle"
                                        data-target=".front-header-search">
                                        <i class="las la-search la-flip-horizontal la-2x pt-1"
                                            style="color:#23780E;"></i>
                                    </a>
                                </div>


                                


                                <!-- Cart -->
                                <div class="d-none d-lg-block d-xl-block align-self-stretch has-transition"
                                    data-hover="dropdown">
                                    <div class="nav-cart-box dropdown h-100" id="cart_items"
                                        style="width: max-content;">
                                        @include('frontend.partials.cart.cart')
                                    </div>
                                </div>

                                

                                @if (Auth::check() && auth()->user()->user_type == 'customer')
                                    <!-- Compare -->
                                    <div class="d-none">
                                        <div class="" id="compare">
                                            @include('frontend.partials.compare')
                                        </div>
                                    </div>
                                    <!-- Wishlist -->
                                    <div class="d-none d-lg-block">
                                        <div class="" id="wishlist">
                                            @include('frontend.partials.wishlist')
                                        </div>
                                    </div>
                                    <!-- Notifications -->
                                    <ul class=" list-inline mb-0 h-100 d-none justify-content-end align-items-center ">
                                        <li class="list-inline-item ml-3 mr-3 pr-3 pl-0 dropdown">
                                            <a class="dropdown-toggle no-arrow text-secondary fs-12"
                                                data-toggle="dropdown" href="javascript:void(0);" role="button"
                                                aria-haspopup="false" aria-expanded="false"
                                                onclick="nonLinkableNotificationRead()">
                                                <span class="position-relative d-inline-block">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14.668"
                                                        height="16" viewBox="0 0 14.668 16">
                                                        <path id="_26._Notification" data-name="26. Notification"
                                                            d="M8.333,16A3.34,3.34,0,0,0,11,14.667H5.666A3.34,3.34,0,0,0,8.333,16ZM15.06,9.78a2.457,2.457,0,0,1-.727-1.747V6a6,6,0,1,0-12,0V8.033A2.457,2.457,0,0,1,1.606,9.78,2.083,2.083,0,0,0,3.08,13.333H13.586A2.083,2.083,0,0,0,15.06,9.78Z"
                                                            transform="translate(-0.999)" fill="#91919b" />
                                                    </svg>
                                                    @if (Auth::check() && count($user->unreadNotifications) > 0)
                                                        <span
                                                            class="badge badge-secondary  badge-inline badge-pill unread-notification-count">{{ count($user->unreadNotifications) }}</span>
                                                    @endif
                                                </span>
                                            </a>
                                            @auth
                                                <div
                                                    class="dropdown-menu dropdown-menu-right dropdown-menu-lg py-0 rounded-0">
                                                    <div class="p-3 bg-light border-bottom">
                                                        <h6 class="mb-0">{{ translate('Notifications') }}</h6>
                                                    </div>
                                                    <div class="c-scrollbar-light overflow-auto"
                                                        style="max-height:300px;">
                                                        <ul class="list-group list-group-flush">
                                                            @forelse($user->unreadNotifications as $notification)
                                                                @php
                                                                    $isLinkable = true;
                                                                    $notificationType = get_notification_type(
                                                                        $notification->notification_type_id,
                                                                        'id',
                                                                    );
                                                                    $notifyContent = $notificationType->getTranslation(
                                                                        'default_text',
                                                                    );
                                                                    $notificationShowDesign = get_setting(
                                                                        'notification_show_type',
                                                                    );
                                                                    if (
                                                                        $notification->type ==
                                                                            'App\Notifications\customNotification' &&
                                                                        $notification->data['link'] == null
                                                                    ) {
                                                                        $isLinkable = false;
                                                                    }
                                                                @endphp
                                                                <li class="list-group-item">
                                                                    <div class="d-flex">
                                                                        @if ($notificationShowDesign != 'only_text')
                                                                            <div class="size-35px mr-2">
                                                                                @php
                                                                                    $notifyImageDesign = '';
                                                                                    if (
                                                                                        $notificationShowDesign ==
                                                                                        'design_2'
                                                                                    ) {
                                                                                        $notifyImageDesign =
                                                                                            'rounded-1';
                                                                                    } elseif (
                                                                                        $notificationShowDesign ==
                                                                                        'design_3'
                                                                                    ) {
                                                                                        $notifyImageDesign =
                                                                                            'rounded-circle';
                                                                                    }
                                                                                @endphp
                                                                                <img src="{{ uploaded_asset($notificationType->image) }}"
                                                                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/notification.png') }}';"
                                                                                    class="img-fit h-100 {{ $notifyImageDesign }}">
                                                                            </div>
                                                                        @endif
                                                                        <div>
                                                                            @if ($notification->type == 'App\Notifications\OrderNotification')
                                                                                @php
                                                                                    $orderCode =
                                                                                        $notification->data[
                                                                                            'order_code'
                                                                                        ];
                                                                                    $route = route(
                                                                                        'purchase_history.details',
                                                                                        encrypt(
                                                                                            $notification->data[
                                                                                                'order_id'
                                                                                            ],
                                                                                        ),
                                                                                    );
                                                                                    $orderCode =
                                                                                        "<span class='text-blue'>" .
                                                                                        $orderCode .
                                                                                        '</span>';
                                                                                    $notifyContent = str_replace(
                                                                                        '[[order_code]]',
                                                                                        $orderCode,
                                                                                        $notifyContent,
                                                                                    );
                                                                                @endphp
                                                                            @endif

                                                                            @if ($isLinkable = true)
                                                                                <a
                                                                                    href="{{ route('notification.read-and-redirect', encrypt($notification->id)) }}">
                                                                            @endif
                                                                            <span
                                                                                class="fs-12 text-dark text-truncate-2">{!! $notifyContent !!}</span>
                                                                            @if ($isLinkable = true)
                                                                                </a>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </li>
                                                            @empty
                                                                <li class="list-group-item">
                                                                    <div class="py-4 text-center fs-16">
                                                                        {{ translate('No notification found') }}
                                                                    </div>
                                                                </li>
                                                            @endforelse
                                                        </ul>
                                                    </div>
                                                    <div class="text-center border-top">
                                                        <a href="{{ route('customer.all-notifications') }}"
                                                            class="text-secondary fs-12 d-block py-2">
                                                            {{ translate('View All Notifications') }}
                                                        </a>
                                                    </div>
                                                </div>
                                            @endauth
                                        </li>
                                    </ul>
                                @endif

                                <div class="d-none d-lg-block d-xl-block">
                                    @auth
                                        <span
                                            class="d-flex align-items-center nav-user-info py-20px @if (isAdmin()) ml-lg-0 @endif"
                                            id="nav-user-info">
                                            <!-- Image -->
                                            <span
                                                class="size-40px rounded-circle overflow-hidden border border-transparent nav-user-img">
                                                @if ($user->avatar_original != null)
                                                    <img src="{{ $user_avatar }}" class="img-fit h-100"
                                                        alt="{{ translate('avatar') }}"
                                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                                                @else
                                                    <img src="{{ static_asset('assets/img/avatar-place.png') }}"
                                                        class="image" alt="{{ translate('avatar') }}"
                                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                                                @endif
                                            </span>
                                            <!-- Name -->
                                            <h4 class="h5 fs-14 fw-700 text-dark ml-2 mb-0">{{ $user->name }}</h4>
                                        </span>
                                    @else
                                        <!--Login & Registration -->
                                        <span class="d-flex align-items-center nav-user-info py-20px">
                                            <!-- Image -->
                                            <!-- <span
                                        class="size-40px rounded-circle overflow-hidden border d-flex align-items-center justify-content-center nav-user-img">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="19.902" height="20.012"
                                            viewBox="0 0 19.902 20.012">
                                            <path id="fe2df171891038b33e9624c27e96e367"
                                                d="M15.71,12.71a6,6,0,1,0-7.42,0,10,10,0,0,0-6.22,8.18,1.006,1.006,0,1,0,2,.22,8,8,0,0,1,15.9,0,1,1,0,0,0,1,.89h.11a1,1,0,0,0,.88-1.1,10,10,0,0,0-6.25-8.19ZM12,12a4,4,0,1,1,4-4A4,4,0,0,1,12,12Z"
                                                transform="translate(-2.064 -1.995)" fill="#91919b" />
                                        </svg>
                                    </span> -->

                                            <a href="{{ route('user.login') }}"><i
                                                    class="las la-user la-2x pt-1 login_icons"
                                                    style="color: #23780E;"></i></a>

                                            <!-- <a href="{{ route('user.login') }}"
                                        class="text-reset opacity-60 hov-opacity-100 hov-text-primary fs-12 d-inline-block border-right border-soft-light border-width-2 pr-2 ml-3">{{ translate('Login') }}</a>
                                    <a href="{{ route('user.registration') }}"
                                        class="text-reset opacity-60 hov-opacity-100 hov-text-primary fs-12 d-inline-block py-2 pl-2">{{ translate('Registration') }}</a> -->
                                        </span>
                                    @endauth
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Loged in user Menus -->
            <div class="hover-user-top-menu position-absolute top-100 left-0 right-0 z-3">
                <div class="container">
                    <div class="position-static float-right top_positions">
                        <div class="aiz-user-top-menu bg-white rounded-0 border-top shadow-sm" style="width:220px;">
                            <ul class="list-unstyled no-scrollbar mb-0 text-left">
                                @if (isAdmin())
                                    <li class="user-top-nav-element border border-top-0" data-id="1">
                                        <a href="{{ route('admin.dashboard') }}"
                                            class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                viewBox="0 0 16 16">
                                                <path id="Path_2916" data-name="Path 2916"
                                                    d="M15.3,5.4,9.561.481A2,2,0,0,0,8.26,0H7.74a2,2,0,0,0-1.3.481L.7,5.4A2,2,0,0,0,0,6.92V14a2,2,0,0,0,2,2H14a2,2,0,0,0,2-2V6.92A2,2,0,0,0,15.3,5.4M10,15H6V9A1,1,0,0,1,7,8H9a1,1,0,0,1,1,1Zm5-1a1,1,0,0,1-1,1H11V9A2,2,0,0,0,9,7H7A2,2,0,0,0,5,9v6H2a1,1,0,0,1-1-1V6.92a1,1,0,0,1,.349-.76l5.74-4.92A1,1,0,0,1,7.74,1h.52a1,1,0,0,1,.651.24l5.74,4.92A1,1,0,0,1,15,6.92Z"
                                                    fill="#b5b5c0" />
                                            </svg>
                                            <span
                                                class="user-top-menu-name has-transition ml-3">{{ translate('Dashboard') }}</span>
                                        </a>
                                    </li>
                                @else
                                    <li class="user-top-nav-element border border-top-0" data-id="1">
                                        <a href="{{ route('dashboard') }}"
                                            class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                viewBox="0 0 16 16">
                                                <path id="Path_2916" data-name="Path 2916"
                                                    d="M15.3,5.4,9.561.481A2,2,0,0,0,8.26,0H7.74a2,2,0,0,0-1.3.481L.7,5.4A2,2,0,0,0,0,6.92V14a2,2,0,0,0,2,2H14a2,2,0,0,0,2-2V6.92A2,2,0,0,0,15.3,5.4M10,15H6V9A1,1,0,0,1,7,8H9a1,1,0,0,1,1,1Zm5-1a1,1,0,0,1-1,1H11V9A2,2,0,0,0,9,7H7A2,2,0,0,0,5,9v6H2a1,1,0,0,1-1-1V6.92a1,1,0,0,1,.349-.76l5.74-4.92A1,1,0,0,1,7.74,1h.52a1,1,0,0,1,.651.24l5.74,4.92A1,1,0,0,1,15,6.92Z"
                                                    fill="#b5b5c0" />
                                            </svg>
                                            <span
                                                class="user-top-menu-name has-transition ml-3">{{ translate('Dashboard') }}</span>
                                        </a>
                                    </li>
                                @endif

                                @if (isCustomer())
                                    <li class="user-top-nav-element border border-top-0" data-id="1">
                                        <a href="{{ route('purchase_history.index') }}"
                                            class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                viewBox="0 0 16 16">
                                                <g id="Group_25261" data-name="Group 25261"
                                                    transform="translate(-27.466 -542.963)">
                                                    <path id="Path_2953" data-name="Path 2953"
                                                        d="M14.5,5.963h-4a1.5,1.5,0,0,0,0,3h4a1.5,1.5,0,0,0,0-3m0,2h-4a.5.5,0,0,1,0-1h4a.5.5,0,0,1,0,1"
                                                        transform="translate(22.966 537)" fill="#b5b5bf" />
                                                    <path id="Path_2954" data-name="Path 2954"
                                                        d="M12.991,8.963a.5.5,0,0,1,0-1H13.5a2.5,2.5,0,0,1,2.5,2.5v10a2.5,2.5,0,0,1-2.5,2.5H2.5a2.5,2.5,0,0,1-2.5-2.5v-10a2.5,2.5,0,0,1,2.5-2.5h.509a.5.5,0,0,1,0,1H2.5a1.5,1.5,0,0,0-1.5,1.5v10a1.5,1.5,0,0,0,1.5,1.5h11a1.5,1.5,0,0,0,1.5-1.5v-10a1.5,1.5,0,0,0-1.5-1.5Z"
                                                        transform="translate(27.466 536)" fill="#b5b5bf" />
                                                    <path id="Path_2955" data-name="Path 2955"
                                                        d="M7.5,15.963h1a.5.5,0,0,1,.5.5v1a.5.5,0,0,1-.5.5h-1a.5.5,0,0,1-.5-.5v-1a.5.5,0,0,1,.5-.5"
                                                        transform="translate(23.966 532)" fill="#b5b5bf" />
                                                    <path id="Path_2956" data-name="Path 2956"
                                                        d="M7.5,21.963h1a.5.5,0,0,1,.5.5v1a.5.5,0,0,1-.5.5h-1a.5.5,0,0,1-.5-.5v-1a.5.5,0,0,1,.5-.5"
                                                        transform="translate(23.966 529)" fill="#b5b5bf" />
                                                    <path id="Path_2957" data-name="Path 2957"
                                                        d="M7.5,27.963h1a.5.5,0,0,1,.5.5v1a.5.5,0,0,1-.5.5h-1a.5.5,0,0,1-.5-.5v-1a.5.5,0,0,1,.5-.5"
                                                        transform="translate(23.966 526)" fill="#b5b5bf" />
                                                    <path id="Path_2958" data-name="Path 2958"
                                                        d="M13.5,16.963h5a.5.5,0,0,1,0,1h-5a.5.5,0,0,1,0-1"
                                                        transform="translate(20.966 531.5)" fill="#b5b5bf" />
                                                    <path id="Path_2959" data-name="Path 2959"
                                                        d="M13.5,22.963h5a.5.5,0,0,1,0,1h-5a.5.5,0,0,1,0-1"
                                                        transform="translate(20.966 528.5)" fill="#b5b5bf" />
                                                    <path id="Path_2960" data-name="Path 2960"
                                                        d="M13.5,28.963h5a.5.5,0,0,1,0,1h-5a.5.5,0,0,1,0-1"
                                                        transform="translate(20.966 525.5)" fill="#b5b5bf" />
                                                </g>
                                            </svg>
                                            <span
                                                class="user-top-menu-name has-transition ml-3">{{ translate('My Orders') }}</span>
                                        </a>
                                    </li>

                                    @if(!empty($user->user_subtype) && $user->user_subtype != '')
                                        <li class="user-top-nav-element border border-top-0" data-id="1">
                                            <a class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1" href="{{ route('request-doc.form') }}">
                                                <i class="las la-business-time"></i>
                                                <span
                                                    class="user-top-menu-name has-transition ml-3">{{ translate('Request Document') }}</span>
                                            </a>
                                        </li>
                                    @endif


                                    <li class="user-top-nav-element border border-top-0" data-id="1">
                                        <a href="{{ route('user.new_registration') }}"
                                            class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                            <i class="las la-business-time"></i>
                                            <span
                                                class="user-top-menu-name has-transition ml-3">{{ translate('B2B Registration') }}</span></a>
                                    </li>
                                    <li class="user-top-nav-element border border-top-0" data-id="1">
                                        <a href="{{ route('financial-archives.user') }}"
                                            class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                                <g fill="none" fill-rule="evenodd">
                                                    <path fill="#b5b5bf" d="M3 2.5A1.5 1.5 0 0 1 4.5 1h3.793a1.5 1.5 0 0 1 1.06.44L10.5 2.5H12A1.5 1.5 0 0 1 13.5 4v8A1.5 1.5 0 0 1 12 13.5H4A1.5 1.5 0 0 1 2.5 12V2.5H3Zm8.5 2A.5.5 0 0 0 11 4h-1a1 1 0 0 1-1-1V2h-4a.5.5 0 0 0-.5.5V12a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5V4.5h-.5Z"/>
                                                    <path fill="#b5b5bf" d="M5.75 6.5a.5.5 0 0 1 .5-.5h3.5a.5.5 0 1 1 0 1h-3.5a.5.5 0 0 1-.5-.5Zm0 2a.5.5 0 0 1 .5-.5h3.5a.5.5 0 1 1 0 1h-3.5a.5.5 0 0 1-.5-.5Zm0 2a.5.5 0 0 1 .5-.5H8a.5.5 0 0 1 0 1H6.25a.5.5 0 0 1-.5-.5Z"/>
                                                </g>
                                            </svg>
                                            <span
                                                class="user-top-menu-name has-transition ml-3">{{ translate('Financial Archive') }}</span></a>
                                    </li>                                    
                                    <li class="d-none user-top-nav-element border border-top-0" data-id="1">
                                        <a href="{{ route('digital_purchase_history.index') }}"
                                            class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16.001" height="16"
                                                viewBox="0 0 16.001 16">
                                                <g id="Group_25262" data-name="Group 25262"
                                                    transform="translate(-1388.154 -562.604)">
                                                    <path id="Path_2963" data-name="Path 2963"
                                                        d="M77.864,98.69V92.1a.5.5,0,1,0-1,0V98.69l-1.437-1.437a.5.5,0,0,0-.707.707l1.851,1.852a1,1,0,0,0,.707.293h.172a1,1,0,0,0,.707-.293l1.851-1.852a.5.5,0,0,0-.7-.713Z"
                                                        transform="translate(1318.79 478.5)" fill="#b5b5bf" />
                                                    <path id="Path_2964" data-name="Path 2964"
                                                        d="M67.155,88.6a3,3,0,0,1-.474-5.963q-.009-.089-.015-.179a5.5,5.5,0,0,1,10.977-.718,3.5,3.5,0,0,1-.989,6.859h-1.5a.5.5,0,0,1,0-1l1.5,0a2.5,2.5,0,0,0,.417-4.967.5.5,0,0,1-.417-.5,4.5,4.5,0,1,0-8.908.866.512.512,0,0,1,.009.121.5.5,0,0,1-.52.479,2,2,0,1,0-.162,4l.081,0h2a.5.5,0,0,1,0,1Z"
                                                        transform="translate(1324 486)" fill="#b5b5bf" />
                                                </g>
                                            </svg>
                                            <span
                                                class="user-top-menu-name has-transition ml-3">{{ translate('Downloads') }}</span>
                                        </a>
                                    </li>
                                    @if (get_setting('conversation_system') == 1)
                                        <li class="user-top-nav-element border border-top-0" data-id="1">
                                            <a href="{{ route('conversations.index') }}"
                                                class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 16 16">
                                                    <g id="Group_25263" data-name="Group 25263"
                                                        transform="translate(1053.151 256.688)">
                                                        <path id="Path_3012" data-name="Path 3012"
                                                            d="M134.849,88.312h-8a2,2,0,0,0-2,2v5a2,2,0,0,0,2,2v3l2.4-3h5.6a2,2,0,0,0,2-2v-5a2,2,0,0,0-2-2m1,7a1,1,0,0,1-1,1h-8a1,1,0,0,1-1-1v-5a1,1,0,0,1,1-1h8a1,1,0,0,1,1,1Z"
                                                            transform="translate(-1178 -341)" fill="#b5b5bf" />
                                                        <path id="Path_3013" data-name="Path 3013"
                                                            d="M134.849,81.312h8a1,1,0,0,1,1,1v5a1,1,0,0,1-1,1h-.5a.5.5,0,0,0,0,1h.5a2,2,0,0,0,2-2v-5a2,2,0,0,0-2-2h-8a2,2,0,0,0-2,2v.5a.5.5,0,0,0,1,0v-.5a1,1,0,0,1,1-1"
                                                            transform="translate(-1182 -337)" fill="#b5b5bf" />
                                                        <path id="Path_3014" data-name="Path 3014"
                                                            d="M131.349,93.312h5a.5.5,0,0,1,0,1h-5a.5.5,0,0,1,0-1"
                                                            transform="translate(-1181 -343.5)" fill="#b5b5bf" />
                                                        <path id="Path_3015" data-name="Path 3015"
                                                            d="M131.349,99.312h5a.5.5,0,1,1,0,1h-5a.5.5,0,1,1,0-1"
                                                            transform="translate(-1181 -346.5)" fill="#b5b5bf" />
                                                    </g>
                                                </svg>
                                                <span
                                                    class="user-top-menu-name has-transition ml-3">{{ translate('Conversations') }}</span>
                                            </a>
                                        </li>
                                    @endif

                                    @if (get_setting('wallet_system') == 1)
                                        <li class="user-top-nav-element border border-top-0" data-id="1">
                                            <a href="{{ route('wallet.index') }}"
                                                class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    xmlns:xlink="http://www.w3.org/1999/xlink" width="16"
                                                    height="16" viewBox="0 0 16 16">
                                                    <defs>
                                                        <clipPath id="clip-path1">
                                                            <rect id="Rectangle_1386" data-name="Rectangle 1386"
                                                                width="16" height="16" fill="#b5b5bf" />
                                                        </clipPath>
                                                    </defs>
                                                    <g id="Group_8102" data-name="Group 8102"
                                                        clip-path="url(#clip-path1)">
                                                        <path id="Path_2936" data-name="Path 2936"
                                                            d="M13.5,4H13V2.5A2.5,2.5,0,0,0,10.5,0h-8A2.5,2.5,0,0,0,0,2.5v11A2.5,2.5,0,0,0,2.5,16h11A2.5,2.5,0,0,0,16,13.5v-7A2.5,2.5,0,0,0,13.5,4M2.5,1h8A1.5,1.5,0,0,1,12,2.5V4H2.5a1.5,1.5,0,0,1,0-3M15,11H10a1,1,0,0,1,0-2h5Zm0-3H10a2,2,0,0,0,0,4h5v1.5A1.5,1.5,0,0,1,13.5,15H2.5A1.5,1.5,0,0,1,1,13.5v-9A2.5,2.5,0,0,0,2.5,5h11A1.5,1.5,0,0,1,15,6.5Z"
                                                            fill="#b5b5bf" />
                                                    </g>
                                                </svg>
                                                <span
                                                    class="user-top-menu-name has-transition ml-3">{{ translate('My Wallet') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                    <li class="user-top-nav-element border border-top-0" data-id="1">
                                        <a href="{{ route('support_ticket.index') }}"
                                            class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16.001"
                                                viewBox="0 0 16 16.001">
                                                <g id="Group_25259" data-name="Group 25259"
                                                    transform="translate(-316 -1066)">
                                                    <path id="Subtraction_184" data-name="Subtraction 184"
                                                        d="M16427.109,902H16420a8.015,8.015,0,1,1,8-8,8.278,8.278,0,0,1-1.422,4.535l1.244,2.132a.81.81,0,0,1,0,.891A.791.791,0,0,1,16427.109,902ZM16420,887a7,7,0,1,0,0,14h6.283c.275,0,.414,0,.549-.111s-.209-.574-.34-.748l0,0-.018-.022-1.064-1.6A6.829,6.829,0,0,0,16427,894a6.964,6.964,0,0,0-7-7Z"
                                                        transform="translate(-16096 180)" fill="#b5b5bf" />
                                                    <path id="Union_12" data-name="Union 12"
                                                        d="M16414,895a1,1,0,1,1,1,1A1,1,0,0,1,16414,895Zm.5-2.5V891h.5a2,2,0,1,0-2-2h-1a3,3,0,1,1,3.5,2.958v.54a.5.5,0,1,1-1,0Zm-2.5-3.5h1a.5.5,0,1,1-1,0Z"
                                                        transform="translate(-16090.998 183.001)" fill="#b5b5bf" />
                                                </g>
                                            </svg>
                                            <span
                                                class="user-top-menu-name has-transition ml-3">{{ translate('Support Ticket') }}</span>
                                        </a>
                                    </li>
                                @endif
                                <li class="user-top-nav-element border border-top-0" data-id="1">
                                    <a href="{{ route('logout') }}"
                                        class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="15.999"
                                            viewBox="0 0 16 15.999">
                                            <g id="Group_25503" data-name="Group 25503"
                                                transform="translate(-24.002 -377)">
                                                <g id="Group_25265" data-name="Group 25265"
                                                    transform="translate(-216.534 -160)">
                                                    <path id="Subtraction_192" data-name="Subtraction 192"
                                                        d="M12052.535,2920a8,8,0,0,1-4.569-14.567l.721.72a7,7,0,1,0,7.7,0l.721-.72a8,8,0,0,1-4.567,14.567Z"
                                                        transform="translate(-11803.999 -2367)" fill="#d43533" />
                                                </g>
                                                <rect id="Rectangle_19022" data-name="Rectangle 19022" width="1"
                                                    height="8" rx="0.5" transform="translate(31.5 377)"
                                                    fill="#d43533" />
                                            </g>
                                        </svg>
                                        <span
                                            class="user-top-menu-name text-primary has-transition ml-3">{{ translate('Logout') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Bar -->
        <div class="d-none d-lg-block position-relative h-50px border-bottom" style="background-color: white !important;">
            <div class="container h-100">
                <div class="row pt-0">
                    <div class="col-12 d-none d-lg-block logo_menu">
                        <div class="text-center">
                            @php $category_top_menu = getCategoryTopMenu(); @endphp

                            @foreach ($category_top_menu as $cat)
                                @php $hasChildren = $cat->childrenCategories->isNotEmpty(); @endphp
                                <li class="list-inline-item mr-3 animate-underline-white @if($hasChildren) dropdown @endif">
                                    <div class="d-inline-flex align-items-center">
                                        <a href="/category/{{ $cat->slug }}"
                                            class="fs-14 black_light_clr d-inline-block fw-500 header_menu_links pt-2 pb-2">
                                            {{ $cat->name }}
                                        </a>

                                        @if($hasChildren)
                                            <button class="btn btn-link p-0 ml-1 dropdown-toggle dropdown-toggle-split fs-14 black_light_clr header_menu_links"
                                                type="button"
                                                id="injectionsDropdown_{{ $cat->id }}"
                                                data-toggle="dropdown" aria-haspopup="true"
                                                aria-expanded="false"
                                                aria-label="{{ $cat->name }} submenu">
                                            </button>
                                        @endif
                                    </div>

                                    @if($hasChildren)
                                        <div class="dropdown-menu"
                                            aria-labelledby="injectionsDropdown_{{ $cat->id }}">
                                            @foreach ($cat->childrenCategories as $childCategory)
                                                <a class="dropdown-item"
                                                    href="/category/{{ $childCategory->slug }}">{{ $childCategory->name }}</a>
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                </li>
                            @endforeach
                        </div>
                    </div>


                    <!-- Categoty Menu Button -->
                    {{-- <div class="d-none all-category has-transition bg-black-10" id="category-menu-bar">
                        <div class="px-3 h-100"
                            style="padding-top: 12px;padding-bottom: 12px; width:270px; cursor: pointer;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="fw-500 fs-16 text-white mr-3">{{ translate('Categories') }}</span>
                                    <a href="{{ route('categories.all') }}" class="text-reset categoriesAll">
                                        <span
                                            class="d-none d-lg-inline-block text-white animate-underline-white">({{ translate('See All') }})</span>
                                    </a>
                                </div>
                                <i class="las la-angle-down text-white has-transition" id="category-menu-bar-icon"
                                    style="font-size: 1.2rem !important"></i>
                            </div>
                        </div>
                    </div> --}}

                    
                    <!-- Header Menus -->
                    {{-- @php
                        $nav_txt_color =
                            get_setting('header_nav_menu_text') == 'light' ||
                            get_setting('header_nav_menu_text') == null
                                ? 'text-white'
                                : 'text-dark';
                    @endphp
                    <div class="w-100 full_menu_nav">
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <ul class="list-inline mb-0 pl-0"> --}}

                                {{-- @php
                                    $cat_human_id_raw = get_setting('header_nav_menu_human'); // Ensure IDs are integers
                                    $cat_veterinary_id_raw = get_setting('header_nav_menu_veterinary');

                                    // Decode JSON string into PHP arrays
                                    $cat_human_id = json_decode($cat_human_id_raw, true);
                                    $cat_veterinary_id = json_decode($cat_veterinary_id_raw, true);

                                    // Ensure the IDs are integers and non-empty arrays
                                    $cat_human_id = array_map('intval', $cat_human_id ?: []);
                                    $cat_veterinary_id = array_map('intval', $cat_veterinary_id ?: []);

                                    if (session('web_type_name') == 'human') {
                                        if (count($cat_human_id) > 0) {
                                            // Fetch categories based on the IDs
                                            $category_menu = Category::select('id', 'parent_id', 'name', 'slug')
                                                ->whereIn('id', $cat_human_id)
                                                // ->where('parent_id', session('web_type'))
                                                // ->with('childrenCategories')
                                                ->orderByRaw('FIELD(id, ' . implode(',', $cat_human_id) . ')') // Maintain order
                                                ->get();
                                        } else {
                                            $category_menu = collect(); // Empty collection if no IDs
                                        }
                                    } elseif (session('web_type_name') == 'veterinary') {
                                        if (count($cat_veterinary_id) > 0) {
                                            $category_menu = Category::select('id', 'parent_id', 'name', 'slug')
                                                ->whereIn('id', $cat_veterinary_id)
                                                // ->where('parent_id', session('web_type'))
                                                // ->with('childrenCategories')
                                                ->orderByRaw('FIELD(id, ' . implode(',', $cat_veterinary_id) . ')') // Maintain order
                                                ->get();
                                        } else {
                                            $category_menu = collect();
                                        }
                                    }
                                @endphp --}}

                                {{-- @php
                                    // Get category ID settings from the database
                                    $catHumanIdRaw = get_setting('header_nav_menu_human');
                                    $catVeterinaryIdRaw = get_setting('header_nav_menu_veterinary');

                                    // Decode JSON into arrays, fallback to empty arrays if null or invalid
                                    $catHumanId = array_map('intval', json_decode($catHumanIdRaw, true) ?: []);
                                    $catVeterinaryId = array_map('intval', json_decode($catVeterinaryIdRaw, true) ?: []);

                                    $webTypeName = session('web_type_name') ?? 'default';
                                    $cacheKey = 'category_menu_' . $webTypeName;

                                    // Retrieve from cache or generate it if not present
                                    $category_menu = Cache::rememberForever($cacheKey, function () use ($webTypeName, $catHumanId, $catVeterinaryId) {
                                        if ($webTypeName == 'human' && count($catHumanId) > 0) {
                                            return Category::select('id', 'parent_id', 'name', 'slug')
                                                ->whereIn('id', $catHumanId)
                                                ->orderByRaw('FIELD(id, ' . implode(',', $catHumanId) . ')')
                                                ->get();
                                        } elseif ($webTypeName == 'veterinary' && count($catVeterinaryId) > 0) {
                                            return Category::select('id', 'parent_id', 'name', 'slug')
                                                ->whereIn('id', $catVeterinaryId)
                                                ->orderByRaw('FIELD(id, ' . implode(',', $catVeterinaryId) . ')')
                                                ->get();
                                        } else {
                                            return collect(); // Return empty collection if no IDs match
                                        }
                                    });
                                @endphp --}}
                                {{-- @php $category_menu = getCategoryMenu(); @endphp --}}
                                {{-- Render the category menu items --}}

                                {{-- @foreach ($category_menu as $cat)
                                    <li class="list-inline-item mr-0 animate-underline-white">
                                        <a href="/category/{{ $cat->slug }}"
                                            class="fs-16 py-3 d-inline-block fw-500 {{ $nav_txt_color }} header_menu_links">
                                            {{ $cat->name }}
                                        </a> --}}
                                        {{-- <div class="dropdown-menu" aria-labelledby="injectionsDropdown_{{ $cat->id }}">
                                            @foreach ($cat->childrenCategories as $childCategory)
                                                <a class="dropdown-item" href="/search">{{ $childCategory->name }}</a>
                                            @endforeach
                                        </div> --}}
                                    {{-- </li>
                                @endforeach --}}

                                {{-- @if (get_setting('header_menu_labels') != null)
                                    @foreach (json_decode(get_setting('header_menu_labels'), true) as $key => $value)
                                        <li class="list-inline-item mr-0 animate-underline-white">
                                            <a href="{{ json_decode(get_setting('header_menu_links'), true)[$key] }}"
                                                class="fs-16 py-3 d-inline-block fw-500 {{ $nav_txt_color }} header_menu_links
                                            @if (url()->current() == json_decode(get_setting('header_menu_links'), true)[$key]) active @endif">
                                                {{ translate($value) }}
                                            </a>
                                        </li>
                                    @endforeach
                                @endif --}}
                            {{-- </ul>
                        </div>
                    </div> --}}

                </div>
            </div>
            <!-- Categoty Menus -->
            <div class="hover-category-menu position-absolute w-100 top-100 left-0 right-0 z-3 d-none"
                id="click-category-menu">
                <div class="container">
                    <div class="d-flex position-relative">
                        <div class="position-static">
                            @include('frontend.' . get_setting('homepage_select') . '.partials.category_menu')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Top Menu Sidebar -->
    <div class="aiz-top-menu-sidebar collapse-sidebar-wrap sidebar-xl sidebar-left d-lg-none z-1035">
        <div class="overlay overlay-fixed dark c-pointer" data-toggle="class-toggle"
            data-target=".aiz-top-menu-sidebar" data-same=".hide-top-menu-bar"></div>
        <div class="collapse-sidebar c-scrollbar-light text-left">
            <button type="button" class="btn btn-sm pl-4 pt-4 pb-2 hide-top-menu-bar" data-toggle="class-toggle"
                data-target=".aiz-top-menu-sidebar">
                <i class="las la-times la-2x text-primary"></i>
            </button>
            @auth
                <span class="d-flex align-items-center nav-user-info pl-4">
                    <!-- Image -->
                    <span class="size-40px rounded-circle overflow-hidden border border-transparent nav-user-img">
                        @if ($user->avatar_original != null)
                            <img src="{{ $user_avatar }}" class="img-fit h-100" alt="{{ translate('avatar') }}"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                        @else
                            <img src="{{ static_asset('assets/img/avatar-place.png') }}" class="image"
                                alt="{{ translate('avatar') }}"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                        @endif
                    </span>
                    <!-- Name -->
                    <h4 class="h5 fs-14 fw-700 text-dark ml-2 mb-0">{{ $user->name }}</h4>
                </span>
            @else
                <!--Login & Registration -->
                <span class="d-flex align-items-center nav-user-info pl-4">
                    <!-- Image -->
                    <span
                        class="size-40px rounded-circle overflow-hidden border d-flex align-items-center justify-content-center nav-user-img">
                        <svg xmlns="http://www.w3.org/2000/svg" width="19.902" height="20.012"
                            viewBox="0 0 19.902 20.012">
                            <path id="fe2df171891038b33e9624c27e96e367"
                                d="M15.71,12.71a6,6,0,1,0-7.42,0,10,10,0,0,0-6.22,8.18,1.006,1.006,0,1,0,2,.22,8,8,0,0,1,15.9,0,1,1,0,0,0,1,.89h.11a1,1,0,0,0,.88-1.1,10,10,0,0,0-6.25-8.19ZM12,12a4,4,0,1,1,4-4A4,4,0,0,1,12,12Z"
                                transform="translate(-2.064 -1.995)" fill="#91919b" />
                        </svg>
                    </span>
                    <a href="{{ route('user.login') }}"
                        class="text-reset opacity-60 hov-opacity-100 hov-text-primary fs-12 d-inline-block border-right border-soft-light border-width-2 pr-2 ml-3">{{ translate('Login') }}</a>
                    <a href="{{ route('user.registration') }}"
                        class="text-reset opacity-60 hov-opacity-100 hov-text-primary fs-12 d-inline-block py-2 pl-2">{{ translate('Registration') }}</a>
                </span>
            @endauth
            <hr>
            <ul class="mb-0 pl-3 pb-3 h-100">
                @if (get_setting('header_menu_labels') != null)
                    @foreach (json_decode(get_setting('header_menu_labels'), true) as $key => $value)
                        <li class="mr-0">
                            <a href="{{ json_decode(get_setting('header_menu_links'), true)[$key] }}"
                                class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                            @if (url()->current() == json_decode(get_setting('header_menu_links'), true)[$key]) active @endif">
                                {{ translate($value) }}
                            </a>
                        </li>
                    @endforeach

                    <li class="list-inline-item">
                        <a class="b2b_buttons b2b_buttons_menu" href="{{ route('user.new_registration') }}">B2B
                            Registration</a>
                    </li>

                    @auth
                        <li class="list-inline-item">
                            <a href="javascript:void(0)" id="prescription-btn" class="ml-0 prescription-btn_menu" title="Upload Prescription">
                                <i class="fa fa-file-prescription"></i> Prescription</a>
                        </li>
                    @endauth

                @endif
                @auth
                    @if (isAdmin())
                        <hr>
                        <li class="mr-0">
                            <a href="{{ route('admin.dashboard') }}"
                                class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links">
                                {{ translate('My Account') }}
                            </a>
                        </li>
                    @else
                        <hr>
                        <li class="mr-0">
                            <a href="{{ route('dashboard') }}"
                                class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                                {{ areActiveRoutes(['dashboard'], ' active') }}">
                                {{ translate('My Account') }}
                            </a>
                        </li>
                    @endif
                    @if (isCustomer())
                        <li class="mr-0">
                            <a href="{{ route('customer.all-notifications') }}"
                                class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                                {{ areActiveRoutes(['customer.all-notifications'], ' active') }}">
                                {{ translate('Notifications') }}
                            </a>
                        </li>
                        <li class="mr-0">
                            <a href="{{ route('wishlists.index') }}"
                                class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                                {{ areActiveRoutes(['wishlists.index'], ' active') }}">
                                {{ translate('Wishlist') }}
                            </a>
                        </li>
                        <li class="mr-0">
                            <a href="{{ route('compare') }}"
                                class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                                {{ areActiveRoutes(['compare'], ' active') }}">
                                {{ translate('Compare') }}
                            </a>
                        </li>
                    @endif
                    <hr>
                    <li class="mr-0">
                        <a href="{{ route('logout') }}"
                            class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-primary header_menu_links">
                            {{ translate('Logout') }}
                        </a>
                    </li>
                @endauth
            </ul>
            <br>
            <br>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="order_details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div id="order-details-modal-body">

                </div>
            </div>
        </div>
    </div>

    @section('script')
        <script type="text/javascript">
            function show_order_details(order_id) {
                $('#order-details-modal-body').html(null);

                if (!$('#modal-size').hasClass('modal-lg')) {
                    $('#modal-size').addClass('modal-lg');
                }

                $.post('{{ route('orders.details') }}', {
                    _token: AIZ.data.csrf,
                    order_id: order_id
                }, function(data) {
                    $('#order-details-modal-body').html(data);
                    $('#order_details').modal();
                    $('.c-preloader').hide();
                    AIZ.plugins.bootstrapSelect('refresh');
                });
            }
        </script>
    @endsection

    @push('scripts')
        <script type="text/javascript">
            // Placeholder text slider (with bottom-to-top animation) - Global for all pages
            $(document).ready(function() {
                var searchInput = $('#search');
                var customPlaceholder = $('#custom-placeholder .placeholder-sliding');
                
                if (searchInput.length && searchInput.attr('data-placeholder-slider') === 'true') {
                    @php
                        $category_top_menu = getCategoryTopMenu();
                        $categoryNames = $category_top_menu->map(function($cat) {
                            return method_exists($cat, 'getTranslation') ? $cat->getTranslation('name') : $cat->name;
                        })->toArray();
                    @endphp
                    var slidingTexts = @json($categoryNames);
                    
                    // Fallback to default if no categories found
                    if (!slidingTexts || slidingTexts.length === 0) {
                        slidingTexts = [
                            'Equipments',
                            'Injections',
                            'Instruments',
                            'Intra-Uterine',
                            'Ointments',
                            'Sprays',
                        ];
                    }
                    
                    var currentIndex = 0;
                    var placeholderInterval;
                    
                    function updatePlaceholder() {
                        // Only update if input is empty
                        if (!searchInput.val() || searchInput.val().trim() === '') {
                            // Remove previous animation classes
                            customPlaceholder.removeClass('animate-in animate-out');
                            
                            // Force reflow to restart animation
                            void customPlaceholder.offsetWidth;
                            
                            // First, animate out (scroll down) - same as coming but in reverse
                            customPlaceholder.addClass('animate-out');
                            
                            // After exit animation completes, update text and animate in
                            setTimeout(function() {
                                // Update text
                                customPlaceholder.text(slidingTexts[currentIndex]);
                                
                                // Remove exit animation and add entrance animation
                                customPlaceholder.removeClass('animate-out');
                                void customPlaceholder.offsetWidth; // Force reflow
                                
                                // Animate in (scroll up) - same transition as going out
                                customPlaceholder.addClass('animate-in');
                                
                                // Remove animation class after animation completes
                                setTimeout(function() {
                                    customPlaceholder.removeClass('animate-in');
                                }, 1200);
                                
                                currentIndex = (currentIndex + 1) % slidingTexts.length;
                            }, 1200);
                        }
                    }
                    
                    // Pause when user focuses on input
                    searchInput.on('focus', function() {
                        clearInterval(placeholderInterval);
                    });
                    
                    // Resume when user leaves input (if empty)
                    searchInput.on('blur', function() {
                        if (!searchInput.val() || searchInput.val().trim() === '') {
                            placeholderInterval = setInterval(updatePlaceholder, 3500);
                        }
                    });
                    
                    // Hide/show custom placeholder based on input value
                    searchInput.on('input', function() {
                        if (searchInput.val() && searchInput.val().trim() !== '') {
                            $('#custom-placeholder').hide();
                        } else {
                            $('#custom-placeholder').show();
                        }
                    });
                    
                    // Initial update
                    if (searchInput.val() && searchInput.val().trim() !== '') {
                        $('#custom-placeholder').hide();
                    } else {
                        customPlaceholder.text(slidingTexts[currentIndex]);
                        currentIndex = (currentIndex + 1) % slidingTexts.length;
                    }
                }
            });
        </script>
    @endpush
