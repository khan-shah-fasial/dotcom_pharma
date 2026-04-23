

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
.search-clear-btn {
    position: absolute;
    right: 5.4rem;
    top: 50%;
    transform: translateY(-50%);
    padding: 4px 8px;
    line-height: 1;
    border: none;
    background: transparent;
    color: #888;
}
.search-clear-btn:hover {
    color: #555;
}
.search-voice-btn {
    position: absolute;
    right: 4.6rem;
    top: 50%;
    transform: translateY(-50%);
    padding: 6px 8px;
    border: none;
    background: transparent;
    color: #888;
}
.search-voice-btn:hover {
    color: #555;
}
.search-image-btn {
    position: absolute;
    right: 3rem;
    top: 50%;
    transform: translateY(-50%);
    padding: 6px 8px;
    border: none;
    background: transparent;
    color: #888;
}
.search-image-btn:hover {
    color: #555;
}
.search-image-thumb {
    position: absolute;
    right: 7rem;
    top: 50%;
    transform: translateY(-50%);
    width: 38px;
    height: 38px;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #cbd5e1;
    background: #fff;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
.search-image-thumb button {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: none;
    background: #ef4444;
    color: #fff;
    font-size: 12px;
    line-height: 18px;
    padding: 0;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0,0,0,0.12);
}

.image-search-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1200;
}
.image-search-modal {
    background: #2b2b2f;
    color: #e8e8e8;
    width: min(720px, 92vw);
    border-radius: 14px;
    box-shadow: 0 16px 60px rgba(0,0,0,0.35);
    overflow: hidden;
}
.image-search-modal__header {
    padding: 16px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 700;
    background: #1f1f22;
}
.image-search-modal__body {
    padding: 22px;
}
.drop-area {
    border: 1px dashed #4c4c50;
    border-radius: 12px;
    background: #1f1f22;
    min-height: 160px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    text-align: center;
    color: #cfd3dc;
    cursor: pointer;
}
.drop-area:hover {
    border-color: #5a8dee;
    background: #242428;
}
.drop-area svg {
    width: 46px;
    height: 46px;
    color: #8da8ff;
}
.image-search-divider {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #8b8b90;
    margin: 18px 0;
}
.image-search-divider::before,
.image-search-divider::after {
    content: "";
    flex: 1;
    height: 1px;
    background: #3a3a3f;
}
.image-search-url {
    display: flex;
    gap: 10px;
}
.image-search-url input {
    flex: 1;
    border-radius: 40px;
    border: 1px solid #3a3a3f;
    background: #1b1b1f;
    color: #e8e8e8;
    padding: 12px 16px;
}
.image-search-url button {
    border-radius: 40px;
    padding: 0 18px;
}
.image-search-close {
    background: transparent;
    border: none;
    color: #aaa;
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
    font-weight: 600;
    display: block;
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

/* Mobile drawer navigation */
.mobile-drawer {
    position: relative;
    z-index: 1055;
}

.mobile-drawer__trigger,
.mobile-drawer__submenu-toggle {
    display: none;
}

.mobile-drawer__overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.35s ease, visibility 0.35s ease;
    z-index: 1045;
}

.mobile-drawer__panel {
    position: fixed;
    top: 0;
    left: 0;
    width: 86%;
    max-width: 420px;
    height: 100vh;
    background: #fff;
    transform: translateX(-100%);
    transition: transform 0.35s ease;
    z-index: 1050;
    box-shadow: 4px 0 16px rgba(0, 0, 0, 0.18);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.mobile-drawer__panel > .mobile-drawer__list,
.mobile-drawer__submenu > .mobile-drawer__list {
    flex: 1;
    overflow-y: auto;
}

.mobile-drawer__trigger:checked ~ .mobile-drawer__overlay {
    opacity: 1;
    visibility: visible;
}

.mobile-drawer__trigger:checked ~ .mobile-drawer__panel {
    transform: translateX(0);
}

.mobile-drawer__header {
    height: 54px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #e8e8e8;
    padding: 0 14px;
    flex-shrink: 0;
}

.mobile-drawer__title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-left: 8px;
}

.mobile-drawer__toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.mobile-drawer__toggle:hover {
    background: #f4f4f4;
}

.mobile-drawer__list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.mobile-drawer__list--nested {
    background: #f8f9fb;
    padding-left: 8px;
}

.mobile-drawer__list-item {
    --mobile-drawer-level: 0;
    position: relative;
}

.mobile-drawer__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #f1f1f1;
    padding-left: calc(12px + var(--mobile-drawer-level) * 8px);
}

.mobile-drawer__link {
    flex: 1;
    padding: 7px 0;
    text-decoration: none;
    color: #000;
    font-weight: 400;
    font-size: 14px;
}

    .mobile-drawer__chevron {
        width: 44px;
        height: 40px;
        display: inline-flex;
        align-items: center;
    justify-content: center;
    cursor: pointer;
    margin-bottom: 0 !important;
}

.mobile-drawer__title-link {
    color: #000;
    text-decoration: none;
    font-weight: 400;
    font-size: 14px;
}

.mobile-drawer__submenu {
    position: relative;
    background: #f8f9fb;
    transition: max-height 0.3s ease;
    max-height: 0;
    overflow: hidden;
    padding-left: 0;
}

.mobile-drawer__submenu-toggle:checked + .mobile-drawer__submenu {
    max-height: 1200px;
}
@media (min-width: 992px) {
    .mobile-drawer {
        display: none;
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

                <div class="col-xl-7 col-lg-6 col-md-4 col-8">
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

                        <li class="list-inline-item b2b_buttons_green">
                            <a href="{{ route('form_enquiry.create', ['type' => 'enquiry']) }}" class=" fs-12 py-2">
                                <i class="las la-question-circle"></i> Inquiry
                            </a>
                        </li>

                        <li class="list-inline-item b2b_buttons_green">
                            <a href="{{ route('form_enquiry.create', ['type' => 'suggestion']) }}" class=" fs-12 py-2">
                                <i class="las la-lightbulb"></i> Suggestion
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


                <div class="col-xl-5 col-lg-6 col-md-8 col-4 d-flex justify-content-end">


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
                                <span id="selectedLang">{{ optional(get_session_language())->name ?? 'English' }}</span>
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

    <!-- Mobile drawer menu -->
    <div class="mobile-drawer d-lg-none">
        <input type="checkbox" id="mobile-drawer-trigger" class="mobile-drawer__trigger">
        <label class="mobile-drawer__overlay" for="mobile-drawer-trigger"></label>

        <div class="mobile-drawer__panel">
            <div class="mobile-drawer__header">
                <label class="mobile-drawer__toggle" for="mobile-drawer-trigger" aria-label="{{ translate('Close menu') }}">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </label>
                <span class="mobile-drawer__title">{{ translate('Menu') }}</span>
            </div>

            <ul class="mobile-drawer__list">
                {!! get_cached_mobile_category_menu_html() !!}
            </ul>
        </div>
    </div>

    <header class="@if (get_setting('header_stikcy') == 'on') sticky-top @endif z-1020 bg-white">
        <!-- Search Bar -->
        <div class="position-relative logo-bar-area border-bottom border-md-nonea z-1025">
            <div class="container">
                <div class="">
                    <div class="row">

                        <div class="col-lg-3 col-md-4 col-2 d-lg-none d-block">
                            <!-- mobile menu toggle -->
                            <label for="mobile-drawer-trigger"
                                class="btn d-lg-none mr-3 mr-sm-4 p-0 active mobile_icons_menus"
                                aria-label="{{ translate('Open menu') }}">
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

                            </label>
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

                                 <a class="all-categories-btn-large d-flex align-items-center ml-4" href="/all-categories">
                                    <i class="las la-th-list mr-2"></i>
                                    <span>All Categories</span>
                                </a>
                            </div>
                        </div>



                        <div class="col-md-5 d-lg-block d-none mrgleft8">

                            <div class="flex-grow-1 front-header-search active d-flex align-items-center bg-white">
                        <div class="position-relative flex-grow-1 px-3 px-lg-0">
                            <form id="searchForm" action="{{ route('search') }}" method="GET" class="stop-propagation">
                                <div class="d-flex position-relative align-items-center">
                                    <div class="search-toggle-side-nav-bar" data-toggle="class-toggle" data-target=".front-header-search">
                                        <button class="btn px-2" type="button"><i
                                                class="la la-2x la-long-arrow-left"></i></button>
                                    </div>
                                    <div class="search-input-box position-relative">
                                        <input type="text"
                                            class="border border-soft-light form-control fs-14 hov-animate-outline"
                                            id="search" name="keyword"
                                            @if(isset($query) && !session('image_search_hide_query'))
                                                value="{{ $query }}"
                                            @endif
                                            placeholder=" " autocomplete="off" data-placeholder-slider="true">
                                        <span class="custom-placeholder" id="custom-placeholder">
                                            <span class="placeholder-fixed">{{ translate('Search for') }}</span>
                                            <span class="placeholder-sliding-container">
                                                <span class="placeholder-sliding"></span>
                                            </span>
                                        </span>
                                        <button type="button" id="voiceBtn" class="search-voice-btn d-flex align-items-center" aria-label="Voice search">
                                            <svg id="Group_mic" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <path d="M12 15a3 3 0 0 0 3-3V6a3 3 0 1 0-6 0v6a3 3 0 0 0 3 3Z" fill="currentColor"/>
                                                <path d="M6 12a1 1 0 1 0-2 0 8 8 0 0 0 7 7.93V22a1 1 0 1 0 2 0v-2.07A8.001 8.001 0 0 0 20 12a1 1 0 1 0-2 0 6 6 0 0 1-12 0Z" fill="currentColor"/>
                                            </svg>
                                        </button>
                                        <button type="button" id="imageSearchBtn" class="search-image-btn d-flex align-items-center" aria-label="Image search (AI)">
                                            <i class="fa-solid fa-camera"></i>
                                        </button>
                                        @if(session('image_search_preview'))
                                            <div class="search-image-thumb" id="imageThumb">
                                                <img src="{{ session('image_search_preview') }}" alt="{{ translate('Uploaded image preview') }}" class="w-100 h-100 object-fit-cover">
                                                <button type="button" id="clearImageThumb" aria-label="Remove image preview">&times;</button>
                                            </div>
                                        @endif

                                        <svg id="Group_723" data-name="Group 723" xmlns="http://www.w3.org/2000/svg"
                                            width="20.001" height="20" viewBox="0 0 20.001 20">
                                            <path id="Path_3090" data-name="Path 3090"
                                                d="M9.847,17.839a7.993,7.993,0,1,1,7.993-7.993A8,8,0,0,1,9.847,17.839Zm0-14.387a6.394,6.394,0,1,0,6.394,6.394A6.4,6.4,0,0,0,9.847,3.453Z"
                                                transform="translate(-1.854 -1.854)" fill="#fff" />
                                            <path id="Path_3091" data-name="Path 3091"
                                                d="M24.4,25.2a.8.8,0,0,1-.565-.234l-6.15-6.15a.8.8,0,0,1,1.13-1.13l6.15,6.15A.8.8,0,0,1,24.4,25.2Z"
                                                transform="translate(-5.2 -5.2)" fill="#fff" />
                                        </svg>
                                        <button type="button" id="clearSearch" class="search-clear-btn d-none" aria-label="Clear search">&times;</button>
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
                                    <ul class=" list-inline mb-0 h-100 justify-content-end align-items-center notificatin_style">
                                        <li class="list-inline-item dropdown">
                                            <a class="dropdown-toggle no-arrow fs-12"
                                                data-toggle="dropdown" href="javascript:void(0);" role="button"
                                                aria-haspopup="false" aria-expanded="false"
                                                onclick="nonLinkableNotificationRead()" style="color:#23780E;">
                                                <span class="position-relative d-inline-block">
                                                    <i class="las la-bell la-2x" style="color:#23780E;"></i>
                                                    @if (Auth::check() && count($user->unreadNotifications) > 0)
                                                        <span
                                                            class="badge_icons badge badge-success badge-inline badge-pill text-white cart-count">{{ count($user->unreadNotifications) }}</span>
                                                    @endif
                                                </span>
                                            </a>
                                            @auth
                                                @php
                                                    $orderNotifications = $user->unreadNotifications->where('type', 'App\\Notifications\\OrderNotification');
                                                    $restockNotifications = $user->unreadNotifications->where('type', 'App\\Notifications\\ProductRestockNotification');
                                                    $otherNotifications = $user->unreadNotifications->filter(function ($notification) {
                                                        return !in_array($notification->type, [
                                                            'App\\Notifications\\OrderNotification',
                                                            'App\\Notifications\\ProductRestockNotification',
                                                        ]);
                                                    });
                                                    $allNotifications = $user->unreadNotifications;
                                                @endphp
                                                <div
                                                    class="dropdown-menu dropdown-menu-right dropdown-menu-lg py-0 rounded-0 notification-dropdown">
                                                    <div class="notif-head">
                                                        <div class="d-flex align-items-center">
                                                            <span class="notif-icon">
                                                                <i class="las la-bell"></i>
                                                            </span>
                                                            <div class="ml-2">
                                                                <div class="fw-700 text-dark heding_noti">{{ translate('Notifications') }}</div>
                                                                <small class="text-muted d-block">{{ translate('Latest updates') }}</small>
                                                            </div>
                                                        </div>
                                                        <span class="notif-count badge badge-success badge-pill">{{ $allNotifications->count() }}</span>
                                                    </div>
                                                    <div class="notif-tabs d-flex" role="group" aria-label="Notification filters">
                                                        <button type="button" class="notif-pill active notif-filter-btn" data-section="invoice">
                                                            {{ translate('Invoices') }}
                                                            <span class="pill-count">({{ $orderNotifications->count() }})</span>
                                                        </button>
                                                        <button type="button" class="notif-pill notif-filter-btn" data-section="restock">
                                                            {{ translate('Restock') }}
                                                            <span class="pill-count">({{ $restockNotifications->count() }})</span>
                                                        </button>
                                                    </div>
                                                    <div class="c-scrollbar-light overflow-auto notif-body">
                                                        <ul class="list-group list-group-flush mb-0" id="notificationList">
                                                            @if ($allNotifications->isEmpty())
                                                                <li class="list-group-item notif-list-item">
                                                                    <div class="py-4 text-center fs-16 mb-0">
                                                                        {{ translate('No notification found') }}
                                                                    </div>
                                                                </li>
                                                            @else
                                                                @foreach ($allNotifications as $notification)
                                                                    @php
                                                                        $sectionKey = 'other';
                                                                        if ($notification->type == 'App\\Notifications\\OrderNotification') {
                                                                            $sectionKey = 'invoice';
                                                                        } elseif ($notification->type == 'App\\Notifications\\ProductRestockNotification') {
                                                                            $sectionKey = 'restock';
                                                                        }
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
                                                                        $variantNames = [];
                                                                        if (
                                                                            $notification->type ==
                                                                                'App\\Notifications\\customNotification' &&
                                                                            ($notification->data['link'] ?? null) == null
                                                                        ) {
                                                                            $isLinkable = false;
                                                                        }
                                                                        if ($notification->type == 'App\\Notifications\\ProductRestockNotification') {
                                                                            $productName = "<span class='text-blue'>" . ($notification->data['product_name'] ?? '') . "</span>";
                                                                            $variantCount = $notification->data['variant_count'] ?? 1;
                                                                            $variantNames = $notification->data['variant_names'] ?? [];
                                                                            $notifyContent = str_replace('[[product_name]]', $productName, $notifyContent);
                                                                            $notifyContent = str_replace('[[variant_count]]', $variantCount, $notifyContent);
                                                                            $notifyContent = str_replace('[[variant_names]]', implode(', ', $variantNames), $notifyContent);
                                                                        }
                                                                        if ($notification->type == 'App\\Notifications\\OrderNotification') {
                                                                            $orderCode = $notification->data['order_code'];
                                                                            $orderCode = "<span class='text-blue'>" . $orderCode . '</span>';
                                                                            $notifyContent = str_replace('[[order_code]]', $orderCode, $notifyContent);
                                                                        }
                                                                    @endphp
                                                                    <li class="list-group-item notification-item notif-list-item" data-section="{{ $sectionKey }}">
                                                                        @if ($isLinkable)
                                                                            <a class="d-block text-reset" href="{{ route('notification.read-and-redirect', encrypt($notification->id)) }}">
                                                                        @endif
                                                                            <div class="d-flex align-items-start">
                                                                                @if ($notificationShowDesign != 'only_text')
                                                                                    <div class="notif-img mr-3">
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
                                                                                            class="img-fit h-100 w-100 {{ $notifyImageDesign }}">
                                                                                    </div>
                                                                                @endif
                                                                                <div class="flex-grow-1">
                                                                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                                                                        <span class="notif-title text-truncate-2">{!! $notifyContent !!}</span>
                                                                                        <span class="notif-time ml-2">{{ optional($notification->created_at)->diffForHumans() }}</span>
                                                                                    </div>
                                                                                    @if (!empty($variantNames))
                                                                                        <small class="text-muted d-block text-truncate">
                                                                                            {{ translate('Variants') }}: {{ implode(', ', $variantNames) }}
                                                                                        </small>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        @if ($isLinkable)
                                                                            </a>
                                                                        @endif
                                                                    </li>
                                                                @endforeach
                                                            @endif
                                                            <li class="list-group-item text-center text-muted d-none notif-list-item" id="notif-empty-state">
                                                                {{ translate('No notifications in this filter') }}
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <div class="notif-footer text-center">
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
                                @push('scripts')
                                    <script>
                                        (function () {
                                            const buttons = document.querySelectorAll('.notif-filter-btn');
                                            const items = document.querySelectorAll('.notification-item');
                                            const emptyState = document.getElementById('notif-empty-state');

                                            const applyFilter = (target) => {
                                                let visibleCount = 0;
                                                items.forEach(item => {
                                                    const shouldShow = item.dataset.section === target;
                                                    item.classList.toggle('d-none', !shouldShow);
                                                    if (shouldShow) visibleCount++;
                                                });
                                                if (emptyState) {
                                                    emptyState.classList.toggle('d-none', visibleCount !== 0);
                                                }
                                            };

                                            buttons.forEach(btn => {
                                                btn.addEventListener('click', function (e) {
                                                    e.preventDefault();
                                                    buttons.forEach(b => b.classList.remove('active'));
                                                    this.classList.add('active');
                                                    const target = this.dataset.section;
                                                    applyFilter(target);
                                                });
                                            });

                                            // default to Invoices view on load
                                            applyFilter('invoice');
                                        })();
                                    </script>
                                @endpush
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
                                        @php $company = optional($user->user_details)->company_name; @endphp
                                        <h4 class="h5 fs-14 fw-700 text-dark ml-2 mb-0">{{ !empty($company) ? $company : $user->name }}</h4>
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
                                    <li class="d-none user-top-nav-element border border-top-0" data-id="1">
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
                                    <li class="d-none user-top-nav-element border border-top-0" data-id="1">
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
                                    <a href="{{ route('profile') }}"
                                        class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                        <i class="las la-user-cog text-muted"></i>
                                        <span class="user-top-menu-name has-transition ml-3">{{ translate('Manage Profile') }}</span>
                                    </a>
                                </li>

                                <li class="user-top-nav-element border border-top-0" data-id="1">
                                    <a href="{{ route('refer.a.friend') }}"
                                        class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                        <i class="las la-user-plus text-muted"></i>
                                        <span class="user-top-menu-name has-transition ml-3">{{ translate('Refer a Friend') }}</span>
                                    </a>
                                </li>

                                <li class="d-none user-top-nav-element border border-top-0" data-id="1">
                                    <a href="{{ route('purchase_history.spend_save') }}"
                                        class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                            <g fill="none" fill-rule="evenodd">
                                                <path fill="#b5b5bf" d="M2.5 2H13a1 1 0 0 1 1 1v1.5a.5.5 0 0 1-1 0V3H2.5a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5H13v-1.5a.5.5 0 0 1 1 0V13a1 1 0 0 1-1 1H2.5A1.5 1.5 0 0 1 1 12.5v-9A1.5 1.5 0 0 1 2.5 2Z"/>
                                                <path fill="#b5b5bf" d="M12 6.5a.5.5 0 0 1 .5-.5H15a.5.5 0 0 1 .354.854L13.207 9l2.147 2.146A.5.5 0 0 1 15 12h-2.5a.5.5 0 0 1 0-1h1.793l-1.146-1.146a.5.5 0 0 1 0-.708L14.293 8H12.5a.5.5 0 0 1-.5-.5Z"/>
                                                <path fill="#b5b5bf" d="M6 5.5a.5.5 0 0 1 .5.5v1.5h1A1.5 1.5 0 0 1 9 9v1a1.5 1.5 0 0 1-1.5 1.5h-1a.5.5 0 0 1 0-1H7.5A.5.5 0 0 0 8 10V9a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 1 .5-.5Z"/>
                                            </g>
                                        </svg>
                                        <span class="user-top-menu-name has-transition ml-3">{{ translate('Total Savings') }}</span>
                                    </a>
                                </li>

                                <li class="d-none user-top-nav-element border border-top-0" data-id="1">
                                    <a href="{{ route('purchase_history.past_orders') }}"
                                        class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                            <g transform="translate(-2 -2)" fill="#b5b5bf">
                                                <path d="M10,2a8,8,0,1,0,8,8A8.009,8.009,0,0,0,10,2Zm0,15A7,7,0,1,1,17,10,7.008,7.008,0,0,1,10,17Z" transform="translate(0)"/>
                                                <path d="M13.5,8a.5.5,0,0,0-.5.5v4.086l-2.8,1.682a.5.5,0,0,0,.5.864l3-1.8A.5.5,0,0,0,14,12.9V8.5A.5.5,0,0,0,13.5,8Z" transform="translate(-1 -1)"/>
                                            </g>
                                        </svg>
                                        <span class="user-top-menu-name has-transition ml-3">{{ translate('Ready List') }}</span>
                                    </a>
                                </li>

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

                                <li class="d-none user-top-nav-element border border-top-0" data-id="1">
                                    <a href="{{ route('wishlists.index') }}"
                                        class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                        <svg id="Group_8116" data-name="Group 8116" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="16" height="16" viewBox="0 0 16 16">
                                            <defs>
                                                <clipPath id="clip-path">
                                                <rect id="Rectangle_1391" data-name="Rectangle 1391" width="16" height="16" fill="#b5b5bf"/>
                                                </clipPath>
                                            </defs>
                                            <g id="Group_8115" data-name="Group 8115" clip-path="url(#clip-path)">
                                                <path id="Path_2981" data-name="Path 2981" d="M14.682,1.318a4.5,4.5,0,0,0-6.364,0L8,1.636l-.318-.318A4.5,4.5,0,0,0,1.318,7.682l6.046,6.054a.9.9,0,0,0,1.273,0l6.045-6.054a4.5,4.5,0,0,0,0-6.364m-.707,5.657L8,12.959,2.025,6.975a3.5,3.5,0,0,1,4.95-4.95l.389.389a.9.9,0,0,0,1.273,0l.388-.389a3.5,3.5,0,0,1,4.95,4.95" transform="translate(0 0)" fill="#b5b5bf"/>
                                            </g>
                                        </svg>
                                        <span class="user-top-menu-name has-transition ml-3">{{ translate('My Saved List') }}</span>
                                    </a>
                                    </li>
                                    <li class="d-none user-top-nav-element border border-top-0" data-id="1">
                                        <a href="{{ route('subscribe-list') }}"
                                            class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                                <g fill="#b5b5bf" fill-rule="evenodd">
                                                    <path d="M8 15.5a2 2 0 0 1-1.995-1.85L6 13.5h1a1 1 0 0 0 2 0h1l-.005.15A2 2 0 0 1 8 15.5Z"/>
                                                    <path d="M8 1a4 4 0 0 1 4 4v2.764l.832 2.494A.5.5 0 0 1 12.35 11H3.65a.5.5 0 0 1-.482-.642L4 7.764V5a4 4 0 0 1 4-4Zm0 1a3 3 0 0 0-3 3v2.915a.5.5 0 0 1-.027.162L4.013 10h7.974l-.96-1.923A.5.5 0 0 1 11 7.915V5a3 3 0 0 0-3-3Z"/>
                                                </g>
                                            </svg>
                                            <span class="user-top-menu-name has-transition ml-3">{{ translate('My Subscribe List') }}</span>
                                        </a>
                                    </li>


                                    @if(!empty($user->user_subtype) && $user->user_subtype != '')
                                        <li class="d-none user-top-nav-element border border-top-0" data-id="1">
                                            <a class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1" href="{{ route('request-doc.form') }}">
                                                <i class="las la-business-time"></i>
                                                <span
                                                    class="user-top-menu-name has-transition ml-3">{{ translate('Request Document') }}</span>
                                            </a>
                                        </li>
                                    @endif


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
                                        <li class="d-none user-top-nav-element border border-top-0" data-id="1">
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
                                    <li class="d-none user-top-nav-element border border-top-0" data-id="1">
                                        <a href="{{ route('financial-archives.user') }}" class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                                <g fill="none" fill-rule="evenodd">
                                                    <path fill="#b5b5bf" d="M3 2.5A1.5 1.5 0 0 1 4.5 1h3.793a1.5 1.5 0 0 1 1.06.44L10.5 2.5H12A1.5 1.5 0 0 1 13.5 4v8A1.5 1.5 0 0 1 12 13.5H4A1.5 1.5 0 0 1 2.5 12V2.5H3Zm8.5 2A.5.5 0 0 0 11 4h-1a1 1 0 0 1-1-1V2h-4a.5.5 0 0 0-.5.5V12a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5V4.5h-.5Z"/>
                                                    <path fill="#b5b5bf" d="M5.75 6.5a.5.5 0 0 1 .5-.5h3.5a.5.5 0 1 1 0 1h-3.5a.5.5 0 0 1-.5-.5Zm0 2a.5.5 0 0 1 .5-.5h3.5a.5.5 0 1 1 0 1h-3.5a.5.5 0 0 1-.5-.5Zm0 2a.5.5 0 0 1 .5-.5H8a.5.5 0 0 1 0 1H6.25a.5.5 0 0 1-.5-.5Z"/>
                                                </g>
                                            </svg>
                                            <span class="user-top-menu-name has-transition ml-3">{{ translate('Financial Archive') }}</span>
                                        </a>
                                    </li>

                                    @php $authUser = auth()->user(); @endphp

                                    @if ($authUser && $authUser->type_option !== null)
                                        <li class="user-top-nav-element border border-top-0" data-id="1">
                                            <a href="{{ route('user.support') }}" class="text-truncate text-dark px-4 fs-14 d-flex align-items-center hov-column-gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                                    <g fill="none" fill-rule="evenodd">
                                                        <path fill="#b5b5bf" d="M8 1a5 5 0 0 1 5 5v1.5a.5.5 0 0 1-1 0V6a4 4 0 1 0-8 0v4a4 4 0 0 0 6.5 3.122.5.5 0 1 1 .624.781A5 5 0 0 1 3 10V6a5 5 0 0 1 5-5z"/>
                                                        <path fill="#b5b5bf" d="M4.5 6A3.5 3.5 0 0 1 8 2.5.5.5 0 0 1 8 3 3 3 0 0 0 5 6v1.5a.5.5 0 0 1-1 0V6z"/>
                                                    </g>
                                                </svg>
                                                <span class="user-top-menu-name has-transition ml-3">{{ translate('Support') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                    <li class="d-none user-top-nav-element border border-top-0" data-id="1">
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
                                            <span class="user-top-menu-name has-transition ml-3">{{ translate('Support Ticket') }}</span>
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
                                <li class="list-inline-item mr-2 animate-underline-white @if($hasChildren) dropdown @endif">
                                    <div class="d-inline-flex align-items-center">
                                        <a href="/category/{{ $cat->slug }}"
                                            class="fs-14 black_light_clr d-inline-block fw-500 header_menu_links pt-2 pb-2">
                                            {{ $cat->name }}
                                        </a>

                                        @if($hasChildren)
                                            <button class="btn btn-link p-0 ml-0 dropdown-toggle dropdown-toggle-split fs-14 black_light_clr header_menu_links"
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
                    @php $company = optional($user->user_details)->company_name; @endphp
                    <h4 class="h5 fs-14 fw-700 text-dark ml-2 mb-0">{{ !empty($company) ? $company : $user->name }}</h4>
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
                        <hr class="d-none">
                        <li class="mr-0 d-none">
                            <a href="{{ route('admin.dashboard') }}"
                                class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links">
                                {{ translate('My Account') }}
                            </a>
                        </li>
                    @else
                        <hr class="d-none">
                        <li class="mr-0 d-none">
                            <a href="{{ route('dashboard') }}"
                                class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                                {{ areActiveRoutes(['dashboard'], ' active') }}">
                                {{ translate('My Account') }}
                            </a>
                        </li>
                    @endif
                    @if (isCustomer())
                        <li class="mr-0">
                            <a href="{{ route('profile') }}"
                                class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                                {{ areActiveRoutes(['profile'], ' active') }}">
                                {{ translate('Manage Profile') }}
                            </a>
                        </li>
                        <li class="mr-0">
                            <a href="{{ route('refer.a.friend') }}"
                                class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                                {{ areActiveRoutes(['refer.a.friend'], ' active') }}">
                                {{ translate('Refer a Friend') }}
                            </a>
                        </li>
                        <li class="mr-0">
                            <a href="{{ route('purchase_history.index') }}"
                                class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                                {{ areActiveRoutes(['purchase_history.index'], ' active') }}">
                                {{ translate('My Orders') }}
                            </a>
                        </li>
                        @if (get_setting('wallet_system') == 1)
                            <li class="mr-0">
                                <a href="{{ route('wallet.index') }}"
                                    class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                                    {{ areActiveRoutes(['wallet.index'], ' active') }}">
                                    {{ translate('My Wallet') }}
                                </a>
                            </li>
                        @endif
                        @php $authUser = auth()->user(); @endphp
                        @if ($authUser && $authUser->type_option !== null)
                            <li class="mr-0">
                                <a href="{{ route('user.support') }}"
                                    class="fs-13 px-3 py-3 w-100 d-inline-block fw-700 text-dark header_menu_links
                                    {{ areActiveRoutes(['user.support'], ' active') }}">
                                    {{ translate('Support') }}
                                </a>
                            </li>
                        @endif
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

    <style>
        /* Navigation dropdown hover effect */
        @media (min-width: 992px) {
            .logo_menu .dropdown {
                position: relative;
            }

            .logo_menu .dropdown:hover .dropdown-menu {
                display: block !important;
                opacity: 1;
                visibility: visible;
            }

            .logo_menu .dropdown .dropdown-menu {
                margin-top: 0;
                transition: opacity 0.2s ease, visibility 0.2s ease;
                max-height: 400px;
                overflow-y: auto;
                overflow-x: hidden;
            }

            /* Prevent click toggle on desktop, keep hover only */
            .logo_menu .dropdown .dropdown-toggle {
                pointer-events: none;
            }

            /* Custom scrollbar for navigation dropdown */
            .logo_menu .dropdown .dropdown-menu::-webkit-scrollbar {
                width: 6px;
            }

            .logo_menu .dropdown .dropdown-menu::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 10px;
            }

            .logo_menu .dropdown .dropdown-menu::-webkit-scrollbar-thumb {
                background: #2b56a1;
                border-radius: 10px;
            }

            .logo_menu .dropdown .dropdown-menu::-webkit-scrollbar-thumb:hover {
                background: #1e3f7a;
            }
        }
    </style>

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

            // Navigation dropdown hover on desktop
            $(document).ready(function() {
                if ($(window).width() >= 992) {
                    // Prevent Bootstrap dropdown click behavior on desktop
                    $('.logo_menu .dropdown .dropdown-toggle').on('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    });

                    // Close dropdowns when mouse leaves
                    $('.logo_menu .dropdown').on('mouseleave', function() {
                        $(this).removeClass('show');
                        $(this).find('.dropdown-menu').removeClass('show');
                    });
                }
            });
        </script>
    @endsection

    @push('scripts')
        <script type="text/javascript">
            // Placeholder text slider (with bottom-to-top animation) - Global for all pages
            $(document).ready(function() {
                var searchInput = $('#search');
                var customPlaceholder = $('#custom-placeholder .placeholder-sliding');
                var clearBtn = $('#clearSearch');

                function toggleClear() {
                    if (searchInput.val() && searchInput.val().trim() !== '') {
                        clearBtn.removeClass('d-none');
                    } else {
                        clearBtn.addClass('d-none');
                    }
                }

                clearBtn.on('click', function() {
                    searchInput.val('');
                    toggleClear();
                    $('#custom-placeholder').show();
                    searchInput.focus();
                });

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
                            toggleClear();
                        } else {
                            $('#custom-placeholder').show();
                            toggleClear();
                        }
                    });

                    // Initial update - start animation immediately
                    if (searchInput.val() && searchInput.val().trim() !== '') {
                        $('#custom-placeholder').hide();
                        toggleClear();
                    } else {
                        // Set initial text
                        customPlaceholder.text(slidingTexts[currentIndex]);
                        currentIndex = (currentIndex + 1) % slidingTexts.length;

                        // Start animation immediately (minimal delay for DOM readiness)
                        setTimeout(function() {
                            updatePlaceholder();
                            // Then continue with interval
                            placeholderInterval = setInterval(updatePlaceholder, 3500);
                        }, 100);
                    }
                }

                toggleClear();
            });
        </script>
        <script type="text/javascript">
            (function() {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                const voiceBtn   = document.getElementById('voiceBtn');
                const searchInput = document.getElementById('search');
                const clearBtn    = document.getElementById('clearSearch');
                const searchForm  = document.getElementById('searchForm');
                const searchIcon  = document.getElementById('Group_723');
                if (!voiceBtn || !searchInput || !searchForm) return;

                if (searchIcon) {
                    searchIcon.style.cursor = 'pointer';
                    searchIcon.addEventListener('click', function() {
                        searchForm.submit();
                    });
                }

                const defaultLabel = voiceBtn.innerHTML;
                let isListening = false;

                function updateClear() {
                    if (!clearBtn) return;
                    if (searchInput.value && searchInput.value.trim() !== '') {
                        clearBtn.classList.remove('d-none');
                    } else {
                        clearBtn.classList.add('d-none');
                    }
                }

                function resetBtn() {
                    voiceBtn.disabled = false;
                    voiceBtn.innerHTML = defaultLabel;
                    isListening = false;
                }

                if (!SpeechRecognition) {
                    voiceBtn.addEventListener('click', function() {
                        alert('Speech recognition is not supported in this browser. Please use Chrome.');
                    });
                    return;
                }

                voiceBtn.addEventListener('click', function() {
                    if (isListening) return;
                    searchInput.value = '';
                    updateClear();
                    const recognition = new SpeechRecognition();
                    recognition.lang = 'en-IN';
                    recognition.interimResults = false;
                    recognition.maxAlternatives = 1;

                    isListening = true;
                    voiceBtn.disabled = true;
                    voiceBtn.innerHTML = 'Listening...';

                    recognition.addEventListener('result', function(event) {
                        const transcript = event.results && event.results[0] && event.results[0][0] ? event.results[0][0].transcript : '';
                        if (transcript) {
                            searchInput.value = transcript;
                            updateClear();
                            searchInput.focus();
                        }
                    });

                    recognition.addEventListener('error', function(event) {
                        if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                            alert('Microphone permission is required for voice search.');
                        }
                        resetBtn();
                    });

                    recognition.addEventListener('end', function() {
                        resetBtn();
                    });

                    try {
                        recognition.start();
                    } catch (e) {
                        resetBtn();
                    }
                });
            })();
        </script>
        <div id="imageSearchModal" class="image-search-modal-backdrop">
            <div class="image-search-modal">
                <div class="image-search-modal__header">
                    <span>{{ translate('Search any image') }}</span>
                    <button type="button" class="image-search-close" aria-label="Close image search">&times;</button>
                </div>
                <div class="image-search-modal__body">
                    <form id="imageSearchForm" action="{{ route('search.image') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="file" id="imageSearchFile" name="image" accept="image/*" class="d-none">
                        <div id="dropArea" class="drop-area">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="currentColor" aria-hidden="true">
                                <path d="M6 10a4 4 0 0 1 4-4h28a4 4 0 0 1 4 4v20a4 4 0 0 1-4 4H10a4 4 0 0 1-4-4V10Zm4-1a1 1 0 0 0-1 1v20a1 1 0 0 0 1 1h28a1 1 0 0 0 1-1V10a1 1 0 0 0-1-1H10Zm6.5 13L23 30l5-6 4.5 5H12.5Zm15.25-12.25a2.75 2.75 0 1 1 0 5.5 2.75 2.75 0 0 1 0-5.5Z"/>
                            </svg>
                            <div class="fs-15">{{ translate('Drag an image here or') }} <a href="#" id="browseImageLink">{{ translate('upload a file') }}</a></div>
                        </div>

                        <div class="image-search-divider"><span>{{ translate('OR') }}</span></div>

                        <div class="image-search-url">
                            <input type="url" id="imageUrlInput" placeholder="{{ translate('Paste image link') }}">
                            <button type="button" class="btn btn-primary" id="submitImageSearch">{{ translate('Search') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
            (function() {
                const openBtn   = document.getElementById('imageSearchBtn');
                const backdrop  = document.getElementById('imageSearchModal');
                const closeBtn  = backdrop ? backdrop.querySelector('.image-search-close') : null;
                const dropArea  = document.getElementById('dropArea');
                const fileInput = document.getElementById('imageSearchFile');
                const browseLink= document.getElementById('browseImageLink');
                const urlInput  = document.getElementById('imageUrlInput');
        const submitBtn = document.getElementById('submitImageSearch');
        const form      = document.getElementById('imageSearchForm');
        const clearThumb= document.getElementById('clearImageThumb');
        const thumbBox  = document.getElementById('imageThumb');
        const searchInput = document.getElementById('search');

        if (!openBtn || !backdrop) return;

                const openModal = () => { backdrop.style.display = 'flex'; };
                const closeModal = () => { backdrop.style.display = 'none'; urlInput.value=''; if(fileInput) fileInput.value=''; };

                openBtn.addEventListener('click', openModal);
                closeBtn?.addEventListener('click', closeModal);
                backdrop.addEventListener('click', (e) => { if (e.target === backdrop) closeModal(); });

                browseLink.addEventListener('click', (e) => { e.preventDefault(); fileInput.click(); });
                dropArea.addEventListener('click', () => fileInput.click());

                const submitFile = () => { if (fileInput.files.length) { form.submit(); } };

                ['dragenter','dragover'].forEach(evt => dropArea.addEventListener(evt, e => { e.preventDefault(); dropArea.classList.add('border-primary'); }));
                ['dragleave','drop'].forEach(evt => dropArea.addEventListener(evt, e => { e.preventDefault(); dropArea.classList.remove('border-primary'); }));
                dropArea.addEventListener('drop', (e) => {
                    if (e.dataTransfer.files.length) {
                        const dt = new DataTransfer();
                        Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
                        fileInput.files = dt.files;
                        fileInput.dispatchEvent(new Event('change'));
                    }
                });
                ['dragover','drop'].forEach(evt => window.addEventListener(evt, e => e.preventDefault()));
                fileInput.addEventListener('change', submitFile);

        submitBtn.addEventListener('click', async () => {
            if (fileInput.files.length) { form.submit(); return; }
            const url = urlInput.value.trim();
            if (!url) { urlInput.focus(); return; }
            try {
                        const resp = await fetch(url, {mode: 'cors'});
                        const blob = await resp.blob();
                        const ext = (blob.type.split('/')[1] || 'jpg').split(';')[0];
                        const file = new File([blob], `remote.${ext}`, {type: blob.type || 'image/jpeg'});
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        fileInput.files = dt.files;
                        form.submit();
            } catch (err) {
                alert("{{ translate('Unable to fetch image from the provided link. Please upload the file instead.') }}");
            }
        });

        if (clearThumb) {
            clearThumb.addEventListener('click', () => {
                if (thumbBox) thumbBox.style.display = 'none';
                if (fileInput) fileInput.value = '';
                if (searchInput) {
                    searchInput.value = '';
                }
                // Redirect to plain search page to reset server-side flash
                window.location.href = "{{ route('search') }}";
            });
        }
    })();
</script>
    @endpush

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(e) {
                var link = e.target.closest('.mobile-drawer__link--has-children');
                if (!link) return;
                var targetId = link.getAttribute('data-submenu-target');
                if (!targetId) return;
                e.preventDefault();
                var checkbox = document.getElementById(targetId);
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                }
            });
        });
    </script>
