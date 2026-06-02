<div class="modal-body px-4 py-5 c-scrollbar-light">
    <div class="row">
        <!-- Product Image gallery -->
        <div class="col-lg-6">
            <div class="row gutters-10 flex-row-reverse">
                @php
                    $photos = explode(',',$product->photos);
                @endphp
                <div class="col">
                    <div class="aiz-carousel product-gallery" data-nav-for='.product-gallery-thumb' data-fade='true' data-auto-height='true'>
                        @foreach ($photos as $key => $photo)
                        <div class="carousel-box img-zoom rounded-0">
                            <img class="img-fluid lazyload"
                                src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                data-src="{{ uploaded_asset($photo) }}"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                        </div>
                        @endforeach
                        @foreach ($product->stocks as $key => $stock)
                            @if ($stock->image != null)
                                <div class="carousel-box img-zoom rounded-0">
                                    <img class="img-fluid lazyload"
                                        src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                        data-src="{{ uploaded_asset($stock->image) }}"
                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="col-auto w-90px">
                    <div class="aiz-carousel carousel-thumb product-gallery-thumb" data-items='5' data-nav-for='.product-gallery' data-vertical='true' data-focus-select='true'>
                        @foreach ($photos as $key => $photo)
                        <div class="carousel-box c-pointer border rounded-0">
                            <img class="lazyload mw-100 size-60px mx-auto"
                                src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                data-src="{{ uploaded_asset($photo) }}"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                        </div>
                        @endforeach
                        @foreach ($product->stocks as $key => $stock)
                            @if ($stock->image != null)
                                <div class="carousel-box c-pointer border rounded-0" data-variation="{{ $stock->variant }}">
                                    <img class="lazyload mw-100 size-50px mx-auto"
                                        src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                        data-src="{{ uploaded_asset($stock->image) }}"
                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Info -->
        <div class="col-lg-6">
            <div class="text-left">
                <!-- Product name -->
                <h2 class="mb-2 fs-16 fw-700 text-dark">
                    {{  $product->getTranslation('name')  }}
                </h2>

                <!-- Product Price & Club Point -->
                <div class="row no-gutters mt-3">
                    <div class="col-3">
                        <div class="text-secondary fs-14 fw-400">{{ translate('Price')}}</div>
                    </div>
                    <div class="col-9">
                        <div class="">
                            <strong id="per-piece-price-product-details" class="fs-16 fw-700 text-primary">
                                {{ home_discounted_price($product) }}
                            </strong>
                            <span class="opacity-70 ml-1">/ Piece</span>
                            {{-- @if($product->unit != null)
                                <span class="opacity-70 ml-1">/{{ $product->getTranslation('unit') }}</span>
                            @endif --}}
                        </div>
                    </div>
                </div>
                <div id="discount-show" class="row no-gutters mt-2 d-none">
                    <div class="col-3"></div>
                    <div class="col-9">
                        <span class="fs-14 text-danger">
                            {{ translate('You Save:') }} <span id="discount-product-price"></span> <span id="dis_per"></span>
                        </span>
                    </div>
                </div>

                @php
                    $qty = 0;
                    foreach ($product->stocks as $key => $stock) {
                        $qty += $stock->qty;
                    }
                @endphp

                <!-- Product Choice options form -->
                <form id="option-choice-form">
                    @csrf
                    <input type="hidden" name="id" value="{{ $product->id }}">
                    
                    @if($product->digital !=1)
                        @php
                            $visibleVariantParts = $product->stocks
                                ->where('is_hidden', 0)
                                ->pluck('variant')
                                ->flatMap(function ($variant) {
                                    return explode('-', (string) $variant);
                                })
                                ->map(function ($part) {
                                    return strtolower(str_replace(' ', '', trim((string) $part)));
                                })
                                ->filter()
                                ->unique()
                                ->values();
                        @endphp
                        <!-- Product Choice options -->
                        @if ($product->choice_options != null)
                            @foreach (json_decode($product->choice_options) as $key => $choice)
                                @php
                                    $visibleValues = collect($choice->values)
                                        ->filter(function ($value) use ($visibleVariantParts) {
                                            return $visibleVariantParts->contains(strtolower(str_replace(' ', '', trim((string) $value))));
                                        })
                                        ->values();
                                @endphp
                                @continue($visibleValues->isEmpty())

                                <!--<div class="row no-gutters mt-3">--> <!--old code-->
                                    <div class="row no-gutters mb-3 @if($key == 1) d-none @endif"> <!--hiding 1st attribute ROLE [by nexgeno]-->
                                    <div class="col-3">
                                        <div class="text-secondary fs-14 fw-400 mt-2 ">{{ get_single_attribute_name($choice->attribute_id) }}</div>
                                    </div>
                                    <div class="col-9">
                                        <div class="aiz-radio-inline">
                                            @foreach ($visibleValues as $key => $value)
                                            <label class="aiz-megabox pl-0 mr-2 mb-0">
                                                <!--<input
                                                    type="radio"
                                                    name="attribute_id_{{ $choice->attribute_id }}"
                                                    value="{{ $value }}"
                                                    @if($key == 0) checked @endif
                                                >--> <!--old code-->
                                                <input
                                                    type="radio"
                                                    name="attribute_id_{{ $choice->attribute_id }}"
                                                    value="{{ $value }}"
                                                    @if($key == 0 || get_user_subtype() == strtolower($value)) checked @endif
                                                > <!--added user_subtype role condition for role wise price based on session [by nexgeno]--> 
                                                <span class="aiz-megabox-elem rounded-0 d-flex align-items-center justify-content-center py-1 px-3">
                                                    {{ $value }}
                                                </span>
                                            </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                            @endforeach
                        @endif

                        <!-- Color -->
                        @if ($product->colors && count(json_decode($product->colors)) > 0)
                            @php
                                $visibleColors = collect(json_decode($product->colors))
                                    ->filter(function ($color) use ($visibleVariantParts) {
                                        return $visibleVariantParts->contains(strtolower(str_replace(' ', '', get_single_color_name($color))));
                                    })
                                    ->values();
                            @endphp
                            @if($visibleColors->isNotEmpty())
                            <div class="row no-gutters mt-3">
                                <div class="col-3">
                                    <div class="text-secondary fs-14 fw-400 mt-2">{{ translate('Color')}}</div>
                                </div>
                                <div class="col-9">
                                    <div class="aiz-radio-inline">
                                        @foreach ($visibleColors as $key => $color)
                                        <label class="aiz-megabox pl-0 mr-2 mb-0" data-toggle="tooltip" data-title="{{ get_single_color_name($color) }}">
                                            <input
                                                type="radio"
                                                name="color"
                                                value="{{ get_single_color_name($color) }}"
                                                @if($key == 0) checked @endif
                                            >
                                            <span class="aiz-megabox-elem rounded-0 d-flex align-items-center justify-content-center p-1">
                                                <span class="size-25px d-inline-block rounded" style="background: {{ $color }};"></span>
                                            </span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endif

                        <!-- Batch selection (matches product details behaviour) -->
                        <div class="row no-gutters mt-3" id="batch-selection-section" style="display: none;">
                            <div class="col-3">
                                <div class="text-secondary fs-14 fw-400 mt-2">{{ translate('Choose Batch') }}</div>
                            </div>
                            <div class="col-9">
                                <select id="batch-dropdown" class="form-control form-control-sm" data-placeholder="{{ translate('Search batch code') }}">
                                    <option value="">{{ translate('Choose Batch') }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Quantity -->
                        <div class="row no-gutters mt-3">
                            <div class="col-3">
                                <div class="text-secondary fs-14 fw-400 mt-2">{{ translate('Quantity')}}</div>
                            </div>
                            <div class="col-9">
                                <div class="product-quantity d-flex align-items-center">
                                    <div class="row no-gutters align-items-center aiz-plus-minus mr-3" style="width: 130px;">
                                        <button class="btn col-auto btn-icon btn-sm btn-light rounded-0" type="button" data-type="minus" data-field="quantity" disabled="">
                                            <i class="las la-minus"></i>
                                        </button>
                                        <input type="number" name="quantity" id="product_quantity" class="col border-0 text-center flex-grow-1 fs-16 input-number" placeholder="1" value="{{ $product->min_qty }}" min="{{ $product->min_qty }}" max="10" lang="en">
                                        <button class="btn col-auto btn-icon btn-sm btn-light rounded-0" type="button" data-type="plus" data-field="quantity">
                                            <i class="las la-plus"></i>
                                        </button>
                                    </div>
                                    <div class="avialable-amount opacity-60">
                                        @if($product->stock_visibility_state == 'quantity')
                                        (<span id="available-quantity">{{ $qty }}</span> {{ translate('available')}})
                                        @elseif($product->stock_visibility_state == 'text' && $qty >= 1)
                                            (<span id="available-quantity">{{ translate('In Stock') }}</span>)
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Quantity -->
                        <input type="hidden" name="quantity" value="1">
                    @endif
                    
                    <!-- Batch meta info -->
                    <div class="row no-gutters mt-3">
                        <div class="col-4">
                            <div class="text-secondary fs-13 fw-400">{{ translate('Batch / Lot No.') }}</div>
                            <div id="batch-lot-product-details" class="fw-600 text-dark fs-14">-</div>
                        </div>
                        <div class="col-4">
                            <div class="text-secondary fs-13 fw-400">{{ translate('MRP') }}</div>
                            <div id="mrp-unit" class="fw-600 text-dark fs-14">-</div>
                        </div>
                        <div class="col-4">
                            <div class="text-secondary fs-13 fw-400">{{ translate('Stock') }}</div>
                            <div id="qnt-product-details" class="fw-600 text-dark fs-14">-</div>
                        </div>
                        <div class="col-6 mt-2">
                            <div class="text-secondary fs-13 fw-400">{{ translate('Expiry') }}</div>
                            <div id="product-expiry-date" class="fw-600 text-dark fs-14">-</div>
                        </div>
                        <div class="col-6 mt-2">
                            <div class="text-secondary fs-13 fw-400">{{ translate('Mfg Date') }}</div>
                            <div id="product-manufacturing-date" class="fw-600 text-dark fs-14">-</div>
                        </div>
                        <div class="col-12 mt-2" id="scheme-product-row" data-scheme-row style="display:none;">
                            <div class="text-secondary fs-13 fw-400">{{ translate('Scheme Free Qty') }}</div>
                            <div id="scheme-product-details" data-scheme-value class="fw-600 text-success fs-14">0</div>
                        </div>
                    </div>

                    <div class="row no-gutters mt-3" id="coaParentDiv" style="display:none;">
                        <div class="col-12" id="coaDiv"></div>
                    </div>

                    <!-- Total Price -->
                    <div class="row no-gutters mt-3 pb-3 d-none" id="chosen_price_div">
                        <div class="col-3">
                            <div class="text-secondary fs-14 fw-400 mt-1">{{ translate('Total Price')}}</div>
                        </div>
                        <div class="col-9">
                            <div class="product-price">
                                <strong id="chosen_price" class="fs-20 fw-700 text-primary">

                                </strong>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden input for selected batch -->
                    <input type="hidden" name="batch_id" id="selected_batch_id" value="">

                </form>

                <!-- Add to cart -->
                <div class="mt-3">
                    @if ($product->digital == 1)
                        <button type="button" class="btn btn-primary rounded-0 buy-now fw-600 add-to-cart" 
                            @if (Auth::check() || get_Setting('guest_checkout_activation') == 1) onclick="addToCart()" @else onclick="showLoginModal()" @endif
                        >
                            <i class="la la-shopping-cart"></i>
                            <span class="d-none d-md-inline-block">{{ translate('Add to cart')}}</span>
                        </button>
                    @elseif($qty > 0)
                        @if ($product->external_link != null)
                            <a type="button" class="btn btn-soft-primary rounded-0 mr-2 add-to-cart fw-600" href="{{ $product->external_link }}">
                                <i class="las la-share"></i>
                                <span class="d-none d-md-inline-block">{{ translate($product->external_link_btn)}}</span>
                            </a>
                        @else
                            <button type="button" class="btn btn-primary rounded-0 buy-now fw-600 add-to-cart" 
                                @if (Auth::check() || get_Setting('guest_checkout_activation') == 1) onclick="addToCart()" @else onclick="showLoginModal()" @endif
                            >
                                <i class="la la-shopping-cart"></i>
                                <span class="d-none d-md-inline-block">{{ translate('Add to cart')}}</span>
                            </button>
                        @endif
                    @endif
                    <button type="button" class="btn btn-secondary rounded-0 out-of-stock fw-600 d-none" disabled>
                        <i class="la la-cart-arrow-down"></i>{{ translate('Out of Stock')}}
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $('#option-choice-form input').on('change', function () {
        // Avoid firing when batch_id is updated programmatically
        if ($(this).attr('name') !== 'batch_id') {
            getVariantPrice();
        }
    });
</script>
