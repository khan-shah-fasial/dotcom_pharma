<style>
    .div_disable {
        pointer-events: none;
        opacity: 0.5;
    }
</style>

<div class="text-left product_disc_text">
    <!-- Product Name -->
    <h2 class="mb-1">
        {{ $detailedProduct->getTranslation('name') }}
    </h2>



    <div class="row">
        <div class="col-xl-2 col-lg-3 col-md-12 col-12">
            <!-- Drug Name -->
            @if (!empty($detailedProduct->drug_name))
                <p class="mb-0 detail-gray-color fs-14">
                    <span class="">{{ translate('Drug Name') }} </span>
                </p>
            @endif
        </div>

        <div class="col-xl-10 col-lg-9 col-md-12 col-12">
            <!-- Drug Name -->
            @if (!empty($detailedProduct->drug_name))
                <p class="mb-0 detail-gray-color fs-14">
                    :&nbsp;{{ $detailedProduct->drug_name }}
                </p>
            @endif
        </div>

        <div class="col-xl-2 col-lg-3 col-md-12 col-12 mb-lg-0 mb-0">
            @if (!empty($detailedProduct->role_label))
                <p class="mb-0 detail-gray-color fs-14">
                    <span class="">{{ translate('Drug Role') }} </span>
                </p>
            @endif
        </div>

        <div class="col-xl-10 col-lg-9 col-md-12 col-12 mb-3">
            @if (!empty($detailedProduct->role_label))
                <p class="mb-0 detail-gray-color fs-14">
                    :&nbsp;{{ $detailedProduct->role_label }}
                </p>
            @endif
        </div>

        @if (!empty($detailedProduct->schedule))
            <div class="col-sm-6 col-12 mb-3">
                <span class="detail-font-14px detail-gray-color">{{ translate('Schedule') }}:</span><br>
                <span class="fw-500 fs-14">{{ $detailedProduct->schedule }}</span>
            </div>
        @endif

        @if (!is_null($detailedProduct->prescription_req))
            <!-- Discount percentage -->
            <div class="col-sm-6 col-12 mb-3">
                <span class="detail-font-14px detail-gray-color">{{ translate('Prescription Required') }}:</span><br>
                <span
                    class="fw-500 fs-14 detail-red-color">{{ $detailedProduct->prescription_req == 1 ? 'Yes' : 'No' }}</span>
            </div>
        @endif

        @if (!empty($detailedProduct->brand->name))
            <div class="col-sm-6 col-12">
                <span class="detail-font-14px detail-gray-color">{{ translate('Brand / Mfg') }}:</span><br>
                <span class="fw-500 fs-14">{{ $detailedProduct->brand->name ?? '-' }}</span>
            </div>
        @endif

    </div>

    <!-- Reviews -->
    @if ($detailedProduct->auction_product != 1)
        @if ($detailedProduct->reviews->where('status', 1)->count() != 0)
            <div class="mb-2 d-flex align-items-center gap-2">
                <span class="rating rating-mr-1 detail-gray-color">
                    {{ renderStarRating($detailedProduct->rating) }}
                </span>
                <span
                    class="detail-gray-color detail-font-14px fs-6">({{ $detailedProduct->reviews->where('status', 1)->count() }}
                    {{ translate('Customer Reviews') }})</span>
            </div>
        @endif
    @endif

    <div class="row ml-0 mr-0 pt-md-4 pt-0">



        <div class="col-12 pl-0 d-flex mt-md-3">
            <span class="detail-font-14px detail-gray-color">{{ translate('MRP') }}:</span>
            <span id="mrp-unit" class="detail-font-14px detail-gray-color"></span>
        </div>



        {{-- Pricing Row --}}

        @auth
            @if (auth()->user()->user_subtype !== null)
                <div class="col-12 pl-0 mt-3 pb-0">
                    <span class="detail-font-14px detail-gray-color">{{ translate('Price') }}:</span>
                    <span id="without-tax-product" class="without-tax-product-1"></span>
                    <!-- <span class="without-tax-product-gst without-tax-product-1"> excl. GST</span> -->
                </div>
            @endif
        @endauth

        <div class="col-12 pl-0 mt-md-3 mt-2 pt-1 pt-md-0 pb-0">
            <span class="text-secondary fs-14">
                <span id="per-piece-price-product-details" class="per-piece-price-product-details-gst"></span>
                <!-- <span id="per-piece-price-product-details" class="                    
                    @if (auth()->check() && auth()->user()->user_subtype !== null) per-piece-price-product-details-gst 
                    @else 
                        per-piece-price-product-details-gst @endif"></span> -->
                <span class="">
                    @if (auth()->check() && auth()->user()->user_subtype !== null)
                        incl. GST
                    @endif
                </span>
                / Piece
            </span>
            {{-- <span class="fw-500 fs-14 text-dark ml-3">{{ translate('Count') }}:</span>
            <span id="package-count-product-details" class="text-secondary fs-14 ">
                {{ $detailedProduct->product_count ?? '-' }} / Count</span> --}}
        </div>



        <div id="discount-show" class="col-12 pl-0 mt-md-3 mt-2 pb-0 d-none">
            {{-- @if (discount_in_percentage($detailedProduct) > 0)
                @php echo "here"; @endphp
                <span class=" fs-18 text-center"
                    style="color: #E31E24 !important;"><span class="detail-font-14px detail-gray-color">You Save: </span> <span id="discount-product-price" class="fs-18 text-center" style="color: #E31E24 !important;"></span> ({{ discount_in_percentage($detailedProduct) }}%)</span>
            @else --}}
            <span class=" fs-18 text-center" style="color: #E31E24 !important;"><span
                    class="detail-font-14px detail-gray-color">You Save: </span> <span id="discount-product-price"
                    class="fs-18 text-center" style="color: #E31E24 !important;"></span>
                <span id="dis_per" class="fs-18 text-center" style="color: #E31E24 !important;"></span>
            </span>
            {{-- @endif --}}
        </div>

        {{-- Unit/MRP --}}

        {{-- @auth
            @if (auth()->user()->user_subtype !== null)
                <div class="col-12 pl-0 mt-3 pb-0">
                    <span class="detail-font-14px detail-gray-color">{{ translate('Without Tax') }}:</span>
                    <span id="without-tax-product" class=""></span> <span class="without-tax-product-gst">excl. GST</span>
                </div>
                <div class="col-12 pl-0 mt-md-3 mt-2 pb-0">
                    {{-- <span class="fw-500 fs-14 text-dark">{{ translate('Tax Amount') }}:</span> --}}
        {{-- <span id="tax-product-details" class="text-secondary fs-14"></span>
                    <span class="fw-500 fs-14 text-dark">Inclusive of all taxes</span>
                </div>
            @endif --}}
        {{-- @else --}}
        {{-- <div class="col-12 pl-0 mt-md-3 mt-2 pb-0">
                <p class="fw-500 fs-14 text-dark">
                    <span class="fw-500 fs-14 text-dark">Inclusive of all taxes</span>
                </p>
            </div> --}}
        {{-- @endauth --}}

        <div class="col-12 pl-0 mt-md-3 mt-2 pb-0">
            {{-- <span class="fw-500 fs-14 text-dark">{{ translate('Tax Amount') }}:</span> --}}
            {{-- <span id="tax-product-details" class="text-secondary fs-14"></span> --}}
            <span class="fw-500 fs-14 text-dark">Inclusive of all taxes</span>
        </div>




        {{-- Pack Size --}}
        {{--
    <div class="col-12 mt-2">
        <button class="btn btn-outline-secondary btn-sm">10ml</button>
        <button class="btn btn-outline-secondary btn-sm">20ml</button>
        <button class="btn btn-outline-secondary btn-sm">30ml</button>
        <button class="btn btn-outline-secondary btn-sm">40ml</button>
    </div> --}}




        @if ($detailedProduct->auction_product != 1)
            <div class="col-12 pl-0 pb-0">
                <!--Display price & vairation to only loggedin user [by nexgeno]-->
                <form id="option-choice-form">
                    @csrf
                    <input type="hidden" name="id" value="{{ $detailedProduct->id }}">

                    @if ($detailedProduct->digital == 0)
                        <!-- Choice Options -->
                        @if ($detailedProduct->choice_options != null)
                            @foreach (json_decode($detailedProduct->choice_options) as $key => $choice)
                                <!--<div class="row no-gutters mb-3">--> <!--old code-->
                                <div
                                    class="row no-gutters mt-md-2 mt-2 @if (strtolower(get_single_attribute_name($choice->attribute_id)) == 'role') div_disable @endif">
                                    <!--hiding 1st attribute ROLE [by nexgeno]-->
                                    <div class="col-sm-12">
                                        <div class="text-dark fs-14 fw-500 mt-0 mb-2">
                                            {{ get_single_attribute_name($choice->attribute_id) }}
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="aiz-radio-inline">
                                            @foreach ($choice->values as $key => $value)
                                                <label class="aiz-megabox pl-0 mr-1 mb-2">
                                                    <!--<input type="radio" name="attribute_id_{{ $choice->attribute_id }}"
                                                        value="{{ $value }}"
                                                        @if ($key == 0) checked @endif>-->
                                                    <!--old code-->
                                                    <input type="radio"
                                                        name="attribute_id_{{ $choice->attribute_id }}"
                                                        value="{{ $value }}"
                                                        @if ($key == 0 || get_user_subtype() == strtolower($value)) checked @endif>
                                                    <!--added user_subtype role condition for role wise price based on session [by nexgeno]-->
                                                    <span
                                                        class="aiz-megabox-elem rounded-0 d-flex align-items-center justify-content-center py-1 px-3 fs-12 text-secondary"
                                                        style="border-radius:5px !important">
                                                        {{ $value }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        <!-- Color Options -->
                        @if ($detailedProduct->colors != null && count(json_decode($detailedProduct->colors)) > 0)
                            <div class="row no-gutters mb-3">
                                <div class="col-sm-2">
                                    <div class="text-secondary fs-14 fw-400 mt-2">{{ translate('Color') }}</div>
                                </div>
                                <div class="col-sm-10">
                                    <div class="aiz-radio-inline">
                                        @foreach (json_decode($detailedProduct->colors) as $key => $color)
                                            <label class="aiz-megabox pl-0 mr-1 mb-2" data-toggle="tooltip"
                                                data-title="{{ get_single_color_name($color) }}">
                                                <input type="radio" name="color"
                                                    value="{{ get_single_color_name($color) }}"
                                                    @if ($key == 0) checked @endif>
                                                <span
                                                    class="aiz-megabox-elem rounded-0 d-flex align-items-center justify-content-center p-1">
                                                    <span class="size-25px d-inline-block rounded"
                                                        style="background: {{ $color }};"></span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif


                        <!-- Quantity + Add to cart -->
                        <div class="row no-gutters">
                            <div class="col-md-3 col-12 pl-0 mt-md-3 mt-0 pb-0">
                                {{--  --}}
                                <div class="">
                                    <div class="fw-500 fs-14 text-dark mt-2 mb-2">{{ translate('Quantity') }}</div>
                                </div>
                                <div class="">
                                    <div class="product-quantity d-flex align-items-center">
                                        <div class="row no-gutters align-items-center aiz-plus-minus mr-3"
                                            style="width: 130px; border: 1px solid #dfdfdf">
                                            <button
                                                class="btn col-auto btn-icon btn-sm btn-light rounded-0 new-bg-color"
                                                type="button" data-type="minus" data-field="quantity" disabled="">
                                                <i class="las la-minus"></i>
                                            </button>
                                            <input type="number" name="quantity" id="product_quantity"
                                                class="col border-0 text-center flex-grow-1 fs-16 input-number new-bg-color"
                                                placeholder="1" value="{{ $detailedProduct->min_qty }}"
                                                min="{{ $detailedProduct->min_qty }}" max="10" lang="en">
                                            <button
                                                class="btn col-auto btn-icon btn-sm btn-light rounded-0 new-bg-color"
                                                type="button" data-type="plus" data-field="quantity">
                                                <i class="las la-plus"></i>
                                            </button>
                                        </div>
                                        @php
                                            $qty = 0;
                                            foreach (
                                                $detailedProduct->stocks->where('is_hidden', 0)
                                                as $key => $stock
                                            ) {
                                                $qty += $stock->qty;
                                            }
                                        @endphp
                                        <div class="avialable-amount opacity-60 d-none">
                                            @if ($detailedProduct->stock_visibility_state == 'quantity')
                                                (<span id="available-quantity">{{ $qty }}</span>
                                                {{ translate('available') }})
                                            @elseif($detailedProduct->stock_visibility_state == 'text' && $qty >= 1)
                                                (<span id="available-quantity"
                                                    class="">{{ translate('In Stock') }}</span>)
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                {{--  --}}
                            </div>
                            <div class="col-md-9 col-12 pl-0 mt-md-3 mt-2 pb-0">
                                <!-- Total Price -->
                                <div class="" id="chosen_price_div">
                                    <div class="">
                                        <div class="fw-500 fs-14 text-dark mt-2 mb-2">
                                            {{ translate('Total Amount Product Wise') }}:</div>
                                    </div>
                                    <div class="">
                                        <div class="product-price">
                                            <strong id="chosen_price" class="fs-24 fw-500" style="color:#123498;">

                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Quantity -->
                        <input type="hidden" name="quantity" value="1">
                    @endif



                </form>
            </div>
        @endif



        <!-- Add to cart & Buy now Buttons -->
        <div class="mt-md-4 mt-2">

            @if ($detailedProduct->digital == 0)
                @if (
                    (get_setting('product_external_link_for_seller') == 1 &&
                        $detailedProduct->added_by == 'seller' &&
                        $detailedProduct->external_link != null) ||
                        ($detailedProduct->added_by != 'seller' && $detailedProduct->external_link != null))
                    <a type="button" class="btn btn-primary buy-now fw-600 add-to-cart px-4 rounded-0"
                        href="{{ $detailedProduct->external_link }}">
                        <i class="la la-share"></i> {{ translate($detailedProduct->external_link_btn) }}
                    </a>
                @else
                    <button type="button"
                        class="btn btn-success mr-2 add-to-cart fw-600 min-w-100px rounded-0 text-white border-radius-50 mb-md-0 mb-2 mt-2"
                        @if (Auth::check() || get_Setting('guest_checkout_activation') == 1) onclick="addToCart()" @else onclick="showLoginModal()" @endif>
                        <i class="las la-shopping-bag"></i> {{ translate('Add to cart') }}
                    </button>
                    <button type="button"
                        class="btn detail-buy-now-btn btn-primary mr-2 buy-now fw-600 add-to-cart min-w-100px rounded-0 border-radius-50 mb-md-0 mb-2 mt-2"
                        @if (Auth::check() || get_Setting('guest_checkout_activation') == 1) onclick="addToCart()" @else onclick="showLoginModal()" @endif>
                        <i class="la la-shopping-cart"></i> {{ translate('Buy Now') }}
                    </button>
                @endif
                <button type="button"
                    class="btn btn-secondary out-of-stock fw-600 d-none border-radius-50 mb-md-0 mb-2 mt-2" disabled>
                    <i class="la la-cart-arrow-down"></i> {{ translate('Out of Stock') }}
                </button>
            @elseif ($detailedProduct->digital == 1)
                <button type="button"
                    class="btn btn-success mr-3 add-to-cart fw-600 min-w-150px rounded-0 text-white border-radius-50 mb-md-0 mb-2"
                    @if (Auth::check() || get_Setting('guest_checkout_activation') == 1) onclick="addToCart()" @else onclick="showLoginModal()" @endif>
                    <i class="las la-shopping-bag"></i> {{ translate('Add to cart') }}
                </button>
                <button type="button"
                    class="btn btn-primary buy-now fw-600 add-to-cart min-w-150px rounded-0 border-radius-50 mb-md-0 md-2"
                    @if (Auth::check() || get_Setting('guest_checkout_activation') == 1) onclick="addToCart()" @else onclick="showLoginModal()" @endif>
                    <i class="la la-shopping-cart"></i> {{ translate('Buy Now') }}
                </button>
            @endif
            <button type="button"
                class="btn detail-product-enquiry-btn btn-info buy-now fw-600 add-to-cart min-w-150px rounded-0 border-radius-50 product-enquiry-btn mb-md-0 mb-2 mt-2 mr-3"
                data-product-id="{{ $detailedProduct->id }}" data-product-name="{{ $detailedProduct->name }}">
                <i class="las la-question-circle fs-20 position_btn"></i> {{ translate('Product Enquiry') }}
            </button>
        </div>

        @if (!is_user_loggedin())
            <p class="fs-14 pt-3">Please login / register to buy or to get detailed information of the product</p>
        @endif

        @if ($detailedProduct->gem_portal_link)
            <div class="col-12 d-flex flex-wrap mt-4 pt-4 pb-2 pl-4 pr-2 detail-border-1px bg-white">
                <div class="col-12 col-md-12 text-left pl-0 pr-0">
                    <h5 class="fe-semibold mb-3">{{ translate('GEM Portal') }}</h5>
                </div>
                <div class="col-12 col-md-12 pl-0 mb-2">
                    <div
                        class="detail-product-specs rounded h-100 d-flex align-items-center justify-content-between flex-wrap">
                        <div class="d-flex align-items-center">
                            <span
                                class="mr-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-soft-primary p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-external-link">
                                    <path d="M15 3h6v6" />
                                    <path d="M10 14 21 3" />
                                    <path d="M18 13v7a1 1 0 0 1-1 1H5a2 2 0 0 1-2-2V7a1 1 0 0 1 1-1h7" />
                                </svg>
                            </span>
                            <div>
                                <p class="detail-font-14px detail-gray-color mb-1">
                                    {{ translate('View on GEM Portal') }}</p>
                                <a href="{{ $detailedProduct->gem_portal_link }}"
                                    class="fw-500 fs-14 text-primary text-break" target="_blank" rel="noopener"
                                    style="word-break: break-all;">
                                    {{ $detailedProduct->gem_portal_link }}
                                </a>
                            </div>
                        </div>
                        <a href="{{ $detailedProduct->gem_portal_link }}"
                            class="btn btn-outline-primary btn-sm mt-3 mt-md-0" target="_blank"
                            rel="noopener">{{ translate('Open Link') }}</a>
                    </div>
                </div>
            </div>
        @endif




        @php
            $initialExpiry = optional($detailedProduct->stocks->first())->product_exp_date;
            $initialExpiryFormatted = $initialExpiry ? \Carbon\Carbon::parse($initialExpiry)->format('d M Y') : '-';
        @endphp

        <div class="col-12 d-flex flex-wrap mt-4 pt-4 pb-2 pl-md-4 pl-3 pr-md-2 pr-0 detail-border-1px bg-white">
            <div class="col-12 col-md-12 text-left pl-0 pr-0">
                <h5 class="fe-semibold mb-4">Batch & Stock Details</h5>
            </div>

            <div class="col-12 col-md-6 pl-0 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class="">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-barcode w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path d="M3 5v14"></path>
                                <path d="M7 5v14"></path>
                                <path d="M10 5v14"></path>
                                <path d="M14 5v14"></path>
                                <path d="M18 5v14"></path>
                                <path d="M21 5v14"></path>
                            </svg>
                        </div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">{{ translate('SKU') }}:</p>
                            <p id="sku-product-details" class="fw-500 fs-14 mb-0"></p>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12 col-md-6 pl-0 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class="">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-hash w-5 h-5">
                                <line x1="4" x2="20" y1="9" y2="9"></line>
                                <line x1="4" x2="20" y1="15" y2="15"></line>
                                <line x1="10" x2="8" y1="3" y2="21"></line>
                                <line x1="16" x2="14" y1="3" y2="21"></line>
                            </svg>
                        </div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">Batch / Lot. No:</p>
                            <p id="batch-lot-product-details" class="fw-500 fs-14 mb-0">AEJ-1301H@3</p>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12 col-md-6 pl-0 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-activity w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path
                                    d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2">
                                </path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">{{ translate('Stock Available') }}:</p>
                            <p id="qnt-product-details" class="fw-500 fs-14 mb-0 pl21"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 pl-0 mb-md-3 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class="">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-clock">
                                <path d="M21 7.5V6a2 2 0 0 0-2-2h-1" />
                                <path d="M16 2v2" />
                                <path d="M7 2v2" />
                                <path d="M3 13V6a2 2 0 0 1 2-2h1" />
                                <path d="M3 10h18" />
                                <path d="M17.5 21.5 16 20v-3" />
                                <circle cx="16" cy="16" r="6" />
                            </svg>
                        </div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">{{ translate('Expiry Date') }}:</p>
                            <p id="product-expiry-date" class="fw-500 fs-14 mb-0">{{ $initialExpiryFormatted }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 pl-0 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-package w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path
                                    d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z">
                                </path>
                                <path d="M12 22V12"></path>
                                <path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7"></path>
                                <path d="m7.5 4.27 9 5.15"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">{{ translate('Minimum Pack Size') }}:
                            </p>
                            <p id="min-package-count-product-details" class="fw-500 fs-14 mb-0"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 pl-0 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-shopping-cart w-5 h-5">
                                <circle cx="8" cy="21" r="1"></circle>
                                <circle cx="19" cy="21" r="1"></circle>
                                <path
                                    d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12">
                                </path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">Minimum Order Qty:
                            </p>
                            <p class="fw-500 fs-14 mb-0">06</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- =================================================== --}}
        </div>

        <div class="col-12 d-flex flex-wrap mt-4 pt-4 pb-2 pl-md-4 pl-3 pr-md-2 pr-0 detail-border-1px bg-white">
            <div class="col-12 col-md-12 text-left pl-0 pr-0">
                <h5 class="fe-semibold mb-4">Product Specifications</h5>
            </div>

            @if ($detailedProduct->pharma_categories)
                <div class="col-12 col-md-12 pl-0 mb-md-3 mb-3">
                    <div class="detail-product-specs rounded h-100">


                        <div class="display_flex3">
                            <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20"
                                    height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-copy-check-icon lucide-copy-check">
                                    <path d="m12 15 2 2 4-4" />
                                    <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                                    <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                                </svg></div>
                            <div class="">
                                <p class="detail-font-14px detail-gray-color mb-0">
                                    {{ translate('Pharma Categories') }}:</p>
                                <p class="fw-500 fs-14 mb-0">{{ $detailedProduct->pharma_categories ?? '-' }}</p>
                            </div>
                        </div>

                    </div>
                </div>
            @endif

            @if ($detailedProduct->product_form)
                <div class="col-12 col-md-12 pl-0 mb-md-3 mb-3">
                    <div class="detail-product-specs rounded h-100">
                        <div class="display_flex3">
                            <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20"
                                    height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-book-copy-icon lucide-book-copy">
                                    <path d="M5 7a2 2 0 0 0-2 2v11" />
                                    <path d="M5.803 18H5a2 2 0 0 0 0 4h9.5a.5.5 0 0 0 .5-.5V21" />
                                    <path
                                        d="M9 15V4a2 2 0 0 1 2-2h9.5a.5.5 0 0 1 .5.5v14a.5.5 0 0 1-.5.5H11a2 2 0 0 1 0-4h10" />
                                </svg></div>
                            <div class="">
                                <p class="detail-font-14px detail-gray-color mb-0">{{ translate('Product Form') }}:
                                </p>
                                <p class="fw-500 fs-14 mb-0">{{ $detailedProduct->product_form ?? '-' }}</p>
                            </div>
                        </div>

                    </div>
                </div>
            @endif

            <div class="col-12 col-md-6 pl-0 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-file-text w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                                <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                                <path d="M10 9H8"></path>
                                <path d="M16 13H8"></path>
                                <path d="M16 17H8"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">{{ translate('Category') }}:</p>
                            <p class="fw-500 fs-14 mb-0 pl21">{{ ucfirst($category_name ?? '-') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 pl-0 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users w-5 h-5">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">Group:</p>
                            <p class="fw-500 fs-14 mb-0 pl21">VI</p>
                        </div>
                    </div>
                </div>
            </div>

            @if ($detailedProduct->product_type || $detailedProduct->product_material)
                {{-- Type --}}
                @if ($detailedProduct->product_type)
                    <div class="col-6 col-md-6 pl-0 mb-3">
                        <div class="detail-product-specs rounded h-100">
                            <div class="display_flex3">
                                <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20"
                                        height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-package w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                        <path
                                            d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z">
                                        </path>
                                        <path d="M12 22V12"></path>
                                        <path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7"></path>
                                        <path d="m7.5 4.27 9 5.15"></path>
                                    </svg></div>
                                <div class="">
                                    <p class="detail-font-14px detail-gray-color mb-0"> {{ translate('Type') }}:</p>
                                    <p class="fw-500 fs-14 mb-0">{{ $detailedProduct->product_type ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Material --}}
                @if ($detailedProduct->product_material)
                    <div class="col-6 col-md-6 pl-0 mb-3">
                        <div class="detail-product-specs rounded h-100">
                            <div class="display_flex3">
                                <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20"
                                        height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-beaker w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                        <path d="M4.5 3h15"></path>
                                        <path d="M6 3v16a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V3"></path>
                                        <path d="M6 14h12"></path>
                                    </svg></div>
                                <div class="">
                                    <p class="detail-font-14px detail-gray-color mb-0">{{ translate('Material') }}:
                                    </p>
                                    <p class="fw-500 fs-14 mb-0 pl21">{{ $detailedProduct->product_material ?? '-' }}
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                @endif
            @endif

            {{-- Min Pack Size --}}
            {{-- @if ($detailedProduct->product_min_pack_size) --}}



            {{-- <div class="col-12 col-md-6 pl-0 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-package w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path
                                    d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z">
                                </path>
                                <path d="M12 22V12"></path>
                                <path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7"></path>
                                <path d="m7.5 4.27 9 5.15"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">{{ translate('Per Count') }}:</p>
                            <p id="package-count-product-details" class="fw-500 fs-14 mb-0"></p>
                        </div>
                    </div>

                </div>
            </div> --}}

            {{-- @endif --}}

            @if (!empty($detailedProduct->product_exp_date))
                <div class="col-12 col-md-6 pl-0 mb-3">
                    <div class="detail-product-specs rounded h-100">
                        <div class="display_flex3">
                            <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20"
                                    height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-calendar w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                    <path d="M8 2v4"></path>
                                    <path d="M16 2v4"></path>
                                    <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                                    <path d="M3 10h18"></path>
                                </svg></div>
                            <div class="">
                                <p class="detail-font-14px detail-gray-color mb-0">{{ translate('Expiry Date') }}:</p>
                                <p class="fw-500 fs-14 mb-0 pl21">{{ $detailedProduct->product_exp_date ?? '-' }}</p>
                            </div>
                        </div>

                    </div>
                </div>
            @endif


            @if (!empty($detailedProduct->tags))
                <div class="col-12 col-md-12 pl-0 mb-3">
                    <div class="detail-product-specs rounded h-100">
                        <div class="display_flex3">
                            <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20"
                                    height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-tags-icon lucide-tags">
                                    <path
                                        d="M13.172 2a2 2 0 0 1 1.414.586l6.71 6.71a2.4 2.4 0 0 1 0 3.408l-4.592 4.592a2.4 2.4 0 0 1-3.408 0l-6.71-6.71A2 2 0 0 1 6 9.172V3a1 1 0 0 1 1-1z" />
                                    <path d="M2 7v6.172a2 2 0 0 0 .586 1.414l6.71 6.71a2.4 2.4 0 0 0 3.191.193" />
                                    <circle cx="10.5" cy="6.5" r=".5" fill="currentColor" />
                                </svg></div>
                            <div class="">
                                <p class="detail-font-14pxx detail-gray-color mb-1">{{ translate('Tags') }}</p>
                                <p class="fw-400 fs-12 mb-0 pl21 tags-span-main">
                                    @foreach (explode(',', $detailedProduct->tags) as $tag)
                                        <span> <a href="/search" target="_blank">{{ trim($tag) }}</a> </span>
                                    @endforeach
                                </p>



                            </div>
                        </div>

                    </div>
                </div>
            @endif



            {{-- <div id="rolePriceParentDiv" class="col-12 col-md-12 pl-0 mb-3" style="display: none;">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""> <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-file-text w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                                <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                                <path d="M10 9H8"></path>
                                <path d="M16 13H8"></path>
                                <path d="M16 17H8"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">{{ translate('Role Base Price') }}:</p>

                            <div id="rolePriceDiv" style="display: none;">
                                <div class="overflow-x-auto border border-gray-200 rounded-lg shadow-sm">
                                    <table id="rolePriceTable" class="min-w-[300px] border border-gray-200 rounded-lg text-center">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th
                                                    class="px-6 py-3 text-sm font-medium text-gray-600 uppercase tracking-wider text-center">
                                                    Role
                                                </th>
                                                <th
                                                    class="px-6 py-3 text-sm font-medium text-gray-600 uppercase tracking-wider text-center">
                                                    Price (₹)
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white"></tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div> --}}


            <div id="coaParentDiv" class="col-12 col-md-12 pl-0 mb-3" style="display: none;">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""> <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-file-text w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                                <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                                <path d="M10 9H8"></path>
                                <path d="M16 13H8"></path>
                                <path d="M16 17H8"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">{{ translate('COA') }}:</p>

                            <div id="coaDiv"></div>

                        </div>
                    </div>

                </div>
            </div>



        </div>

        <div class="col-12 d-flex flex-wrap mt-4 pt-4 pb-2 pl-md-4 pl-3 pr-md-2 pr-0 detail-border-1px bg-white">
            <div class="col-12 col-md-12 text-left pl-0 pr-0">
                <h5 class="fe-semibold mb-4">Taz And Origin Details</h5>
            </div>

            @if (!empty($detailedProduct->product_hsn))
                <div class="col-12 col-md-6 pl-0 mb-3">
                    <div class="detail-product-specs rounded h-100">
                        <div class="display_flex3">
                            <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20"
                                    height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-file-text w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                                    <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                                    <path d="M10 9H8"></path>
                                    <path d="M16 13H8"></path>
                                    <path d="M16 17H8"></path>
                                </svg></div>
                            <div class="">
                                <p class="detail-font-14px detail-gray-color mb-0">{{ translate('HSN Code') }}:</p>
                                <p class="fw-500 fs-14 mb-0 pl21">{{ $detailedProduct->product_hsn ?? '-' }}</p>
                            </div>
                        </div>

                    </div>
                </div>
            @endif

            @if (!empty($detailedProduct->product_hs))
                <div class="col-12 col-md-6 pl-0 mb-3">
                    <div class="detail-product-specs rounded h-100">
                        <div class="display_flex3">
                            <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20"
                                    height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-file-text w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
                                    <path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
                                    <path d="M10 9H8"></path>
                                    <path d="M16 13H8"></path>
                                    <path d="M16 17H8"></path>
                                </svg></div>
                            <div class="">
                                <p class="detail-font-14px detail-gray-color mb-0">{{ translate('HS Code') }}:</p>
                                <p class="fw-500 fs-14 mb-0 pl21">{{ $detailedProduct->product_hs ?? '-' }}</p>
                            </div>
                        </div>

                    </div>
                </div>
            @endif

            {{-- Origin --}}
            @if ($detailedProduct->product_origin)
                <div class="col-12 col-md-6 pl-0 mb-3">
                    <div class="detail-product-specs rounded h-100">
                        <div class="display_flex3">
                            <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20"
                                    height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-map-pin w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                    <path
                                        d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0">
                                    </path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg></div>
                            <div class="">
                                <p class="detail-font-14px detail-gray-color mb-0">
                                    {{ translate('Country of Origin') }}:</p>
                                <p class="fw-500 fs-14 mb-0">{{ $detailedProduct->product_origin ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="col-12 col-md-6 pl-0 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-beaker w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path d="M4.5 3h15"></path>
                                <path d="M6 3v16a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V3"></path>
                                <path d="M6 14h12"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">TAX:</p>
                            <p class="fw-500 fs-14 mb-0">5%</p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ==================== --}}
        </div>




        <div class="col-12 d-flex flex-wrap mt-4 pt-4 pb-2 pl-md-4 pl-3 pr-md-2 pr-0 detail-border-1px bg-white">
            <div class="col-12 col-md-12 text-left pl-0 pr-0">
                <h5 class="fe-semibold mb-4">Packing Details</h5>
            </div>

            {{-- Packaging Breakdown (individual cards) --}}
            <div class="col-12 col-md-4 pl-0 mb-md-3 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-box w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path d="M12 22V12"></path>
                                <path d="M3.3 7 12 12l8.7-5L12 2Z"></path>
                                <path d="M3 7v10l9 5 9-5V7"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">{{ translate('Qty per Piece') }}:</p>
                            <p id="qty-per-piece-details" class="fw-500 fs-14 mb-0 clamped-text"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 pl-0 mb-md-3 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-activity w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path
                                    d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2">
                                </path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">
                                {{ translate('Weight of Each Piece') }}:</p>
                            <p id="weight-per-piece-details" class="fw-500 fs-14 mb-0 clamped-text"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 pl-0 mb-md-3 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-ruler w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path d="M3 18 17.94 3.06a2.12 2.12 0 1 1 3 3L6 21H3z"></path>
                                <path d="m14.5 5.5 2 2"></path>
                                <path d="m11 9 2 2"></path>
                                <path d="m7.5 12.5 2 2"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">
                                {{ translate('Piece Dimensions (L×W×H)') }}:</p>
                            <p id="dimension-per-piece-details" class="fw-500 fs-14 mb-0 clamped-text"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 pl-0 mb-md-3 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-layers w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path d="m12 2 8 4-8 4-8-4Z"></path>
                                <path d="m4 10 8 4 8-4"></path>
                                <path d="m4 14 8 4 8-4"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">
                                {{ translate('Qty per Buffer Box / Shrink Pack') }}:</p>
                            <p id="qty-per-buffer-details" class="fw-500 fs-14 mb-0 clamped-text"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 pl-0 mb-md-3 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-weight w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path d="M5 22h14"></path>
                                <path d="M5 11h14"></path>
                                <path d="M5 11a7 7 0 1 1 14 0"></path>
                                <path d="M6 11v11"></path>
                                <path d="M18 11v11"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">
                                {{ translate('Weight of Buffer Box / Shrink Pack') }}:</p>
                            <p id="weight-buffer-details" class="fw-500 fs-14 mb-0 clamped-text"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 pl-0 mb-md-3 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-ruler w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path d="M3 18 17.94 3.06a2.12 2.12 0 1 1 3 3L6 21H3z"></path>
                                <path d="m14.5 5.5 2 2"></path>
                                <path d="m11 9 2 2"></path>
                                <path d="m7.5 12.5 2 2"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">
                                {{ translate('Buffer Dimensions (L×W×H)') }}:</p>
                            <p id="dimension-buffer-details" class="fw-500 fs-14 mb-0 clamped-text"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 pl-0 mb-md-3 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-layers w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path d="m12 2 8 4-8 4-8-4Z"></path>
                                <path d="m4 10 8 4 8-4"></path>
                                <path d="m4 14 8 4 8-4"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">{{ translate('Total Qty per Case') }}:
                            </p>
                            <p id="qty-per-case-details" class="fw-500 fs-14 mb-0 clamped-text"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 pl-0 mb-md-3 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-weight w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path d="M5 22h14"></path>
                                <path d="M5 11h14"></path>
                                <path d="M5 11a7 7 0 1 1 14 0"></path>
                                <path d="M6 11v11"></path>
                                <path d="M18 11v11"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">
                                {{ translate('Total Weight per Case') }}:</p>
                            <p id="weight-case-details" class="fw-500 fs-14 mb-0 clamped-text"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 pl-0 mb-md-3 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-ruler w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path d="M3 18 17.94 3.06a2.12 2.12 0 1 1 3 3L6 21H3z"></path>
                                <path d="m14.5 5.5 2 2"></path>
                                <path d="m11 9 2 2"></path>
                                <path d="m7.5 12.5 2 2"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">
                                {{ translate('Case Dimensions (L×W×H)') }}:</p>
                            <p id="dimension-case-details" class="fw-500 fs-14 mb-0 clamped-text"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="line-detail-page mb-3"></div>

            <div class="col-12 col-md-6 pl-0 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-beaker w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path d="M4.5 3h15"></path>
                                <path d="M6 3v16a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V3"></path>
                                <path d="M6 14h12"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">{{ translate('Weight / Volume') }}:</p>
                            <p id="weight-volume-product-details" class="fw-500 fs-14 mb-0"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 pl-0 mb-3">
                <div class="detail-product-specs rounded h-100">
                    <div class="display_flex3">
                        <div class=""><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-package w-5 h-5 text-medical-info mt-0.5 flex-shrink-0">
                                <path
                                    d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z">
                                </path>
                                <path d="M12 22V12"></path>
                                <path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7"></path>
                                <path d="m7.5 4.27 9 5.15"></path>
                            </svg></div>
                        <div class="">
                            <p class="detail-font-14px detail-gray-color mb-0">{{ translate('Dimentions') }}:</p>
                            <p id="dimentions-product-details" class="fw-500 fs-14 mb-0"></p>
                        </div>
                    </div>
                </div>
            </div>


            {{-- =============================== --}}
        </div>






    </div>


    <!-- Estimate Shipping Time -->
    @if ($detailedProduct->est_shipping_days)
        <div class="col-auto fs-14 mt-1 d-none">
            <small class="mr-1 opacity-50 fs-14">{{ translate('Estimate Shipping Time') }}:</small>
            <span class="fw-500">{{ $detailedProduct->est_shipping_days }} {{ translate('Days') }}</span>
        </div>
    @endif
    <!-- In stock -->
    @if ($detailedProduct->digital == 1)
        <div class="col-12 mt-1">
            <span class="badge badge-md badge-inline badge-pill badge-success">{{ translate('In stock') }}</span>
        </div>
    @endif
</div>

<p>{{ $detailedProduct->short_description ?? '' }}</p>

<div class="row align-items-center d-none">
    @if (get_setting('product_query_activation') == 1)
        <!-- Ask about this product -->
        <div class="col-xl-3 col-lg-4 col-md-3 col-sm-4 mb-3">
            <a href="javascript:void();" onclick="goToView('product_query')"
                class="text-primary fs-14 fw-600 d-flex">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 32 32">
                    <g id="Group_25571" data-name="Group 25571" transform="translate(-975 -411)">
                        <g id="Path_32843" data-name="Path 32843" transform="translate(975 411)" fill="#fff">
                            <path
                                d="M 16 31 C 11.9933500289917 31 8.226519584655762 29.43972969055176 5.393400192260742 26.60659980773926 C 2.560270071029663 23.77347946166992 1 20.00665092468262 1 16 C 1 11.9933500289917 2.560270071029663 8.226519584655762 5.393400192260742 5.393400192260742 C 8.226519584655762 2.560270071029663 11.9933500289917 1 16 1 C 20.00665092468262 1 23.77347946166992 2.560270071029663 26.60659980773926 5.393400192260742 C 29.43972969055176 8.226519584655762 31 11.9933500289917 31 16 C 31 20.00665092468262 29.43972969055176 23.77347946166992 26.60659980773926 26.60659980773926 C 23.77347946166992 29.43972969055176 20.00665092468262 31 16 31 Z"
                                stroke="none" />
                            <path
                                d="M 16 2 C 12.26045989990234 2 8.744749069213867 3.456249237060547 6.100500106811523 6.100500106811523 C 3.456249237060547 8.744749069213867 2 12.26045989990234 2 16 C 2 19.73954010009766 3.456249237060547 23.2552490234375 6.100500106811523 25.89949989318848 C 8.744749069213867 28.54375076293945 12.26045989990234 30 16 30 C 19.73954010009766 30 23.2552490234375 28.54375076293945 25.89949989318848 25.89949989318848 C 28.54375076293945 23.2552490234375 30 19.73954010009766 30 16 C 30 12.26045989990234 28.54375076293945 8.744749069213867 25.89949989318848 6.100500106811523 C 23.2552490234375 3.456249237060547 19.73954010009766 2 16 2 M 16 0 C 24.8365592956543 0 32 7.163440704345703 32 16 C 32 24.8365592956543 24.8365592956543 32 16 32 C 7.163440704345703 32 0 24.8365592956543 0 16 C 0 7.163440704345703 7.163440704345703 0 16 0 Z"
                                stroke="none" fill="{{ get_setting('secondary_base_color', '#ffc519') }}" />
                        </g>
                        <path id="Path_32842" data-name="Path 32842"
                            d="M28.738,30.935a1.185,1.185,0,0,1-1.185-1.185,3.964,3.964,0,0,1,.942-2.613c.089-.095.213-.207.361-.344.735-.658,2.252-2.032,2.252-3.555a2.228,2.228,0,0,0-2.37-2.37,2.228,2.228,0,0,0-2.37,2.37,1.185,1.185,0,1,1-2.37,0,4.592,4.592,0,0,1,4.74-4.74,4.592,4.592,0,0,1,4.74,4.74c0,2.577-2.044,4.432-3.028,5.333l-.284.255a1.89,1.89,0,0,0-.243.948A1.185,1.185,0,0,1,28.738,30.935Zm0,3.561a1.185,1.185,0,0,1-.835-2.026,1.226,1.226,0,0,1,1.671,0,1.061,1.061,0,0,1,.148.184,1.345,1.345,0,0,1,.113.2,1.41,1.41,0,0,1,.065.225,1.138,1.138,0,0,1,0,.462,1.338,1.338,0,0,1-.065.219,1.185,1.185,0,0,1-.113.207,1.06,1.06,0,0,1-.148.184A1.185,1.185,0,0,1,28.738,34.5Z"
                            transform="translate(962.004 400.504)"
                            fill="{{ get_setting('secondary_base_color', '#ffc519') }}" />
                    </g>
                </svg>
                <span class="ml-2 text-primary animate-underline-blue">{{ translate('Product Inquiry') }}</span>
            </a>
        </div>
    @endif
    <div class="col mb-3">
        @if ($detailedProduct->auction_product != 1)
            <div class="d-flex">
                <!-- Add to wishlist button -->
                <a href="javascript:void(0)" onclick="addToWishList({{ $detailedProduct->id }})"
                    class="mr-3 fs-14 text-dark opacity-60 has-transitiuon hov-opacity-100">
                    <i class="la la-heart-o mr-1"></i>
                    {{ translate('Add to Wishlist') }}
                </a>
                <!-- Add to compare button -->
                <a href="javascript:void(0)" onclick="addToCompare({{ $detailedProduct->id }})"
                    class="fs-14 text-dark opacity-60 has-transitiuon hov-opacity-100">
                    <i class="las la-sync mr-1"></i>
                    {{ translate('Add to Compare') }}
                </a>
            </div>
        @endif
    </div>
</div>




{{-- Warranty --}}
@if ($detailedProduct->has_warranty == 1 && $detailedProduct->warranty_id != null)
    <div class="d-flex flex-wrap align-items-center mb-3">
        <span class="text-secondary fs-14 fw-400 mr-4 w-80px">{{ translate('Warranty') }}</span><br>
        <img src="{{ uploaded_asset($detailedProduct->warranty->logo) }}" height="40">
        <span class="border border-secondary-base btn fs-12 ml-3 px-3 py-1 rounded-1 text-secondary">
            {{ $detailedProduct->warranty->getTranslation('text') }}
            @if ($detailedProduct->warranty_note_id != null)
                <span href="javascript:void(1);" data-toggle="modal" data-target="#warranty-note-modal"
                    class="border-bottom border-bottom-4 ml-2 text-secondary-base">
                    {{ translate('View Details') }}
                </span>
            @endif
        </span>
    </div>
@endif

<!-- Seller Info -->
<div class="align-items-center d-none">
    <div class="d-flex align-items-center mr-4">
        <!-- Shop Name -->
        @if ($detailedProduct->added_by == 'seller' && get_setting('vendor_system_activation') == 1)
            <span class="text-secondary fs-14 fw-400 mr-4 w-80px">{{ translate('Sold by') }}</span>
            <a href="{{ route('shop.visit', $detailedProduct->user->shop->slug) }}"
                class="text-reset hov-text-primary fs-14 fw-700">{{ $detailedProduct->user->shop->name }}</a>
        @else
            <p class="mb-0 fs-14 fw-700">{{ translate('Inhouse product') }}</p>
        @endif
    </div>
    <!-- Messase to seller -->
    @if (get_setting('conversation_system') == 1)
        <div class="">
            <button
                class="btn btn-sm btn-soft-secondary-base btn-outline-secondary-base hov-svg-white hov-text-white rounded-4"
                onclick="show_chat_modal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"
                    class="mr-2 has-transition">
                    <g id="Group_23918" data-name="Group 23918" transform="translate(1053.151 256.688)">
                        <path id="Path_3012" data-name="Path 3012"
                            d="M134.849,88.312h-8a2,2,0,0,0-2,2v5a2,2,0,0,0,2,2v3l2.4-3h5.6a2,2,0,0,0,2-2v-5a2,2,0,0,0-2-2m1,7a1,1,0,0,1-1,1h-8a1,1,0,0,1-1-1v-5a1,1,0,0,1,1-1h8a1,1,0,0,1,1,1Z"
                            transform="translate(-1178 -341)"
                            fill="{{ get_setting('secondary_base_color', '#ffc519') }}" />
                        <path id="Path_3013" data-name="Path 3013"
                            d="M134.849,81.312h8a1,1,0,0,1,1,1v5a1,1,0,0,1-1,1h-.5a.5.5,0,0,0,0,1h.5a2,2,0,0,0,2-2v-5a2,2,0,0,0-2-2h-8a2,2,0,0,0-2,2v.5a.5.5,0,0,0,1,0v-.5a1,1,0,0,1,1-1"
                            transform="translate(-1182 -337)"
                            fill="{{ get_setting('secondary_base_color', '#ffc519') }}" />
                        <path id="Path_3014" data-name="Path 3014"
                            d="M131.349,93.312h5a.5.5,0,0,1,0,1h-5a.5.5,0,0,1,0-1" transform="translate(-1181 -343.5)"
                            fill="{{ get_setting('secondary_base_color', '#ffc519') }}" />
                        <path id="Path_3015" data-name="Path 3015"
                            d="M131.349,99.312h5a.5.5,0,1,1,0,1h-5a.5.5,0,1,1,0-1" transform="translate(-1181 -346.5)"
                            fill="{{ get_setting('secondary_base_color', '#ffc519') }}" />
                    </g>
                </svg>

                {{ translate('Message Seller') }}
            </button>
        </div>
    @endif
    <!-- Size guide -->
    @php
        $sizeChartId =
            $detailedProduct->main_category && $detailedProduct->main_category->sizeChart
                ? $detailedProduct->main_category->sizeChart->id
                : 0;
        $sizeChartName =
            $detailedProduct->main_category && $detailedProduct->main_category->sizeChart
                ? $detailedProduct->main_category->sizeChart->name
                : null;
    @endphp
    @if ($sizeChartId != 0)
        <div class=" ml-4">
            <a href="javascript:void(1);"
                onclick='showSizeChartDetail({{ $sizeChartId }}, "{{ $sizeChartName }}")'
                class="animate-underline-primary">{{ translate('Show size guide') }}</a>
        </div>
    @endif
</div>

{{-- <hr> --}}
@if (is_user_loggedin())
    <!--Display price to only loggedin user [by nexgeno]-->
    <!-- For auction product -->
    @if ($detailedProduct->auction_product)
        <div class="row no-gutters mb-3">
            <div class="col-sm-2">
                <div class="text-secondary fs-14 fw-400 mt-1">{{ translate('Auction Will End') }}</div>
            </div>
            <div class="col-sm-10">
                @if ($detailedProduct->auction_end_date > strtotime('now'))
                    <div class="aiz-count-down align-items-center"
                        data-date="{{ date('Y/m/d H:i:s', $detailedProduct->auction_end_date) }}"></div>
                @else
                    <p>{{ translate('Ended') }}</p>
                @endif

            </div>
        </div>

        <div class="row no-gutters mb-3">
            <div class="col-sm-2">
                <div class="text-secondary fs-14 fw-400 mt-1">{{ translate('Starting Bid') }}</div>
            </div>
            <div class="col-sm-10">
                <span class="opacity-50 fs-20">
                    {{ single_price($detailedProduct->starting_bid) }}
                </span>
                @if ($detailedProduct->unit != null)
                    <span class="opacity-70">/{{ $detailedProduct->getTranslation('unit') }}</span>
                @endif
            </div>
        </div>

        @if (Auth::check() && Auth::user()->product_bids->where('product_id', $detailedProduct->id)->first() != null)
            <div class="row no-gutters mb-3">
                <div class="col-sm-2">
                    <div class="text-secondary fs-14 fw-400 mt-1">{{ translate('My Bidded Amount') }}</div>
                </div>
                <div class="col-sm-10">
                    <span class="opacity-50 fs-20">
                        {{ single_price(Auth::user()->product_bids->where('product_id', $detailedProduct->id)->first()->amount) }}
                    </span>
                </div>
            </div>
            <hr>
        @endif

        @php $highest_bid = $detailedProduct->bids->max('amount'); @endphp
        <div class="row no-gutters my-2 mb-3">
            <div class="col-sm-2">
                <div class="text-secondary fs-14 fw-400 mt-1">{{ translate('Highest Bid') }}</div>
            </div>
            <div class="col-sm-10">
                <strong class="h3 fw-600 text-primary">
                    @if ($highest_bid != null)
                        {{ single_price($highest_bid) }}
                    @endif
                </strong>
            </div>
        </div>
    @else
        <!-- Without auction product -->
        @if ($detailedProduct->wholesale_product == 1)
            <!-- Wholesale -->
            <table class="table mb-3">
                <thead>
                    <tr>
                        <th class="border-top-0">{{ translate('Min Qty') }}</th>
                        <th class="border-top-0">{{ translate('Max Qty') }}</th>
                        <th class="border-top-0">{{ translate('Unit Price') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($detailedProduct->stocks->first()->wholesalePrices as $wholesalePrice)
                        <tr>
                            <td>{{ $wholesalePrice->min_qty }}</td>
                            <td>{{ $wholesalePrice->max_qty }}</td>
                            <td>{{ single_price($wholesalePrice->price) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <!-- Without Wholesale -->
            {{--
                @if (home_price($detailedProduct) != home_discounted_price($detailedProduct))
                    <div class="row no-gutters mb-3">
                        <div class="col-sm-2">
                            <div class="text-secondary fs-14 fw-400">{{ translate('Price') }}</div>
                        </div>
                        <div class="col-sm-10">
                            <div class="d-flex align-items-center">
                                <!-- Discount Price -->
                                <strong class="fs-16 fw-700 text-primary">
                                    {{ home_discounted_price($detailedProduct) }}
                                </strong>
                                <!-- Home Price -->
                                <del class="fs-14 opacity-60 ml-2">
                                    {{ home_price($detailedProduct) }}
                                </del>
                                <!-- Unit -->
                                @if ($detailedProduct->unit != null)
                                    <span
                                        class="opacity-70 ml-1">/{{ $detailedProduct->getTranslation('unit') }}</span>
                                @endif
                                <!-- Discount percentage -->
                                @if (discount_in_percentage($detailedProduct) > 0)
                                    <span class="bg-primary ml-2 fs-11 fw-700 text-white w-35px text-center p-1"
                                        style="padding-top:2px;padding-bottom:2px;">-{{ discount_in_percentage($detailedProduct) }}%</span>
                                @endif
                                <!-- Club Point -->
                                @if (addon_is_activated('club_point') && $detailedProduct->earn_point > 0)
                                    <div class="ml-2 bg-secondary-base d-flex justify-content-center align-items-center px-3 py-1"
                                        style="width: fit-content;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                            viewBox="0 0 12 12">
                                            <g id="Group_23922" data-name="Group 23922"
                                                transform="translate(-973 -633)">
                                                <circle id="Ellipse_39" data-name="Ellipse 39" cx="6"
                                                    cy="6" r="6" transform="translate(973 633)"
                                                    fill="#fff" />
                                                <g id="Group_23920" data-name="Group 23920"
                                                    transform="translate(973 633)">
                                                    <path id="Path_28698" data-name="Path 28698"
                                                        d="M7.667,3H4.333L3,5,6,9,9,5Z" transform="translate(0 0)"
                                                        fill="#f3af3d" />
                                                    <path id="Path_28699" data-name="Path 28699"
                                                        d="M5.33,3h-1L3,5,6,9,4.331,5Z" transform="translate(0 0)"
                                                        fill="#f3af3d" opacity="0.5" />
                                                    <path id="Path_28700" data-name="Path 28700"
                                                        d="M12.666,3h1L15,5,12,9l1.664-4Z"
                                                        transform="translate(-5.995 0)" fill="#f3af3d" />
                                                </g>
                                            </g>
                                        </svg>
                                        <small class="fs-11 fw-500 text-white ml-2">{{ translate('Club Point') }}:
                                            {{ $detailedProduct->earn_point }}</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="row no-gutters mb-3">
                        <div class="col-sm-2">
                            <div class="text-secondary fs-14 fw-400">{{ translate('Price') }}</div>
                        </div>
                        <div class="col-sm-10">
                            <div class="d-flex align-items-center">
                                <!-- Discount Price -->
                                <strong class="fs-16 fw-700 text-primary">
                                    {{ home_discounted_price($detailedProduct) }}
                                </strong>
                                <!-- Unit -->
                                @if ($detailedProduct->unit != null)
                                    <span class="opacity-70">/{{ $detailedProduct->getTranslation('unit') }}</span>
                                @endif
                                <!-- Club Point -->
                                @if (addon_is_activated('club_point') && $detailedProduct->earn_point > 0)
                                    <div class="ml-2 bg-secondary-base d-flex justify-content-center align-items-center px-3 py-1"
                                        style="width: fit-content;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                            viewBox="0 0 12 12">
                                            <g id="Group_23922" data-name="Group 23922"
                                                transform="translate(-973 -633)">
                                                <circle id="Ellipse_39" data-name="Ellipse 39" cx="6"
                                                    cy="6" r="6" transform="translate(973 633)"
                                                    fill="#fff" />
                                                <g id="Group_23920" data-name="Group 23920"
                                                    transform="translate(973 633)">
                                                    <path id="Path_28698" data-name="Path 28698"
                                                        d="M7.667,3H4.333L3,5,6,9,9,5Z" transform="translate(0 0)"
                                                        fill="#f3af3d" />
                                                    <path id="Path_28699" data-name="Path 28699"
                                                        d="M5.33,3h-1L3,5,6,9,4.331,5Z" transform="translate(0 0)"
                                                        fill="#f3af3d" opacity="0.5" />
                                                    <path id="Path_28700" data-name="Path 28700"
                                                        d="M12.666,3h1L15,5,12,9l1.664-4Z"
                                                        transform="translate(-5.995 0)" fill="#f3af3d" />
                                                </g>
                                            </g>
                                        </svg>
                                        <small class="fs-11 fw-500 text-white ml-2">{{ translate('Club Point') }}:
                                            {{ $detailedProduct->earn_point }}</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                --}}
        @endif
    @endif
@endif





@if ($detailedProduct->auction_product)
    @php
        $highest_bid = $detailedProduct->bids->max('amount');
        $min_bid_amount = $highest_bid != null ? $highest_bid + 1 : $detailedProduct->starting_bid;
    @endphp
    @if ($detailedProduct->auction_end_date >= strtotime('now'))
        <div class="mt-4">
            @if (Auth::check() && $detailedProduct->user_id == Auth::user()->id)
                <span
                    class="badge badge-inline badge-danger">{{ translate('Seller cannot Place Bid to His Own Product') }}</span>
            @else
                <button type="button" class="btn btn-primary buy-now  fw-600 min-w-150px rounded-0"
                    onclick="bid_modal()">
                    <i class="las la-gavel"></i>
                    @if (Auth::check() && Auth::user()->product_bids->where('product_id', $detailedProduct->id)->first() != null)
                        {{ translate('Change Bid') }}
                    @else
                        {{ translate('Place Bid') }}
                    @endif
                </button>
            @endif
        </div>
    @endif
@else
    {{-- <div class="d-flex flex-wrap align-items-center mb-1">
            <span class="fs-14 fw-500 mr-4 w-80px">{{ translate('Stock Available') }}</span><br>
            <p id="qnt-product-details" class="text-secondary fs-14 fw-400 pb-0 mb-0"></p>
        </div> --}}

    {{-- <div class="d-flex flex-wrap align-items-center mb-1">
            <span class="fs-14 fw-500 mr-4 w-80px">SKU</span><br>
            <p id="sku-product-details" class="text-secondary fs-14 fw-400 pb-0 mb-0"></p>
        </div> --}}

    {{-- <div class="d-flex flex-wrap align-items-center mb-1">
            <span class="fs-14 fw-500 mr-4 w-80px">Per Piece Price</span><br>
            <p id="per-piece-price-product-details" class="text-secondary fs-14 fw-400 pb-0 mb-0"></p>
        </div> --}}

    {{-- <div class="d-flex flex-wrap align-items-center mb-1">
            <span class="fs-14 fw-500 mr-4 w-80px">{{ translate('Category') }}</span><br>
            <p class="text-secondary fs-14 fw-400 pb-0 mb-0">{{ ucfirst($category_name) }}</p>
        </div> --}}

    <!-- Brand Logo & Name -->
    @if ($detailedProduct->brand != null)
        {{-- <div class="d-flex flex-wrap align-items-center mb-1">
                <span class="fs-14 fw-500 mr-4 w-80px">{{ translate('Brand') }}</span><br>
                <a href="{{ route('products.brand', $detailedProduct->brand->slug) }}"
                    class="text-secondary hov-text-primary fs-14 fw-400">{{ $detailedProduct->brand->name }}</a>
            </div> --}}
    @endif




    {{-- <hr> --}}



    <div class="delivery_section">

        {{-- <div class="delivery_boxex">
            <div class="delivery_boxex_img"><img src="{{ static_asset('assets/img/free_delivery.svg') }}"></div>
            <p>Free Delivery</p>
        </div> --}}

        <div class="delivery_boxex">
            <div class="delivery_boxex_img"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-shield w-6 h-6 text-medical-info">
                    <path
                        d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z">
                    </path>
                </svg></div>
            <p>Secure Transaction</p>
            <p>100% secure payment</p>
        </div>

        <div class="delivery_boxex">
            <div class="delivery_boxex_img"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-award w-6 h-6 text-medical-info">
                    <path
                        d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526">
                    </path>
                    <circle cx="12" cy="8" r="6"></circle>
                </svg></div>
            <p>Top Brand</p>
            <p>Trusted quality</p>
        </div>

        {{-- <div class="delivery_boxex">
            <div class="delivery_boxex_img"><img src="{{ static_asset('assets/img/cash_dilevery.svg') }}"></div>
            <p>Cash on Delivery</p>
        </div> --}}

        <div class="delivery_boxex">
            <div class="delivery_boxex_img"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-rotate-ccw w-6 h-6 text-medical-info">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                    <path d="M3 3v5h5"></path>
                </svg></div>
            <p>Non Return</p>
            <p>Due to hygiene</p>
        </div>

    </div>

    @php
        $dynamicTabs = json_decode($detailedProduct->contents, true) ?? [];
        $hasDescription = !empty(
            trim(str_replace('\u00a0', '', strip_tags(html_entity_decode($detailedProduct->description))))
        );
        $hasVideo = !empty(
            trim(str_replace('\u00a0', '', strip_tags(html_entity_decode($detailedProduct->video_link))))
        );
        $hasPdf = !empty(trim(str_replace('\u00a0', '', strip_tags(html_entity_decode($detailedProduct->pdf)))));
    @endphp

    @if ($hasDescription || (!empty($dynamicTabs) && count($dynamicTabs) > 0) || $hasVideo || $hasPdf)
        <div class="productAccordion_box">
            <div class="accordion accordion-custom" id="productAccordion">

                {{-- Description --}}
                @if ($hasDescription)
                    <div class="card">
                        <div class="card-header" id="headingDescription">
                            <h2 class="mb-0">
                                <button class="btn btn-link" type="button" data-toggle="collapse"
                                    data-target="#collapseDescription" aria-expanded="true"
                                    aria-controls="collapseDescription">
                                    {{ translate('Description') }}
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </h2>
                        </div>
                        <div id="collapseDescription" class="collapse show" aria-labelledby="headingDescription"
                            data-parent="#productAccordion">
                            <div class="card-body aiz-editor-data">
                                {!! $detailedProduct->getTranslation('description') !!}
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Dynamic Tabs --}}
                @if (!empty($dynamicTabs) && count($dynamicTabs) > 0)
                    @foreach ($dynamicTabs as $index => $tab)
                        <div class="card">
                            <div class="card-header" id="headingDynamic{{ $index }}">
                                <h2 class="mb-0">
                                    <button class="btn btn-link collapsed" type="button" data-toggle="collapse"
                                        data-target="#collapseDynamic{{ $index }}" aria-expanded="false"
                                        aria-controls="collapseDynamic{{ $index }}">
                                        {{ $tab['title'] ?? 'Tab ' . ($index + 1) }}
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </h2>
                            </div>
                            <div id="collapseDynamic{{ $index }}" class="collapse"
                                aria-labelledby="headingDynamic{{ $index }}" data-parent="#productAccordion">
                                <div class="card-body aiz-editor-data">
                                    {!! $tab['content'] ?? '' !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif


                {{-- Video 
                @if ($hasVideo)
                    <div class="card">
                        <div class="card-header" id="headingVideo">
                            <h2 class="mb-0">
                                <button class="btn btn-link collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapseVideo" aria-expanded="false" aria-controls="collapseVideo">
                                    {{ translate('Video') }}
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </h2>
                        </div>
                        <div id="collapseVideo" class="collapse" aria-labelledby="headingVideo"
                            data-parent="#productAccordion">
                            <div class="card-body">
                                <div class="embed-responsive embed-responsive-16by9">
                                    @if ($detailedProduct->video_provider == 'youtube')
                                        @php
                                            $youtubeId = null;
                                            if (preg_match('/youtu\\.be\\/([^?&]+)/', $detailedProduct->video_link, $match)) {
                                                $youtubeId = $match[1];
                                            } elseif (preg_match('/v=([^&]+)/', $detailedProduct->video_link, $match)) {
                                                $youtubeId = $match[1];
                                            } elseif (preg_match('/embed\\/([^?&]+)/', $detailedProduct->video_link, $match)) {
                                                $youtubeId = $match[1];
                                            }
                                            $youtubeEmbed = $youtubeId ? 'https://www.youtube.com/embed/' . $youtubeId : $detailedProduct->video_link;
                                        @endphp
                                        <iframe class="embed-responsive-item"
                                            src="{{ $youtubeEmbed }}" allowfullscreen></iframe>
                                    @elseif ($detailedProduct->video_provider == 'dailymotion' && isset(explode('video/', $detailedProduct->video_link)[1]))
                                        <iframe class="embed-responsive-item"
                                            src="https://www.dailymotion.com/embed/video/{{ explode('video/', $detailedProduct->video_link)[1] }}"></iframe>
                                    @elseif ($detailedProduct->video_provider == 'vimeo' && isset(explode('vimeo.com/', $detailedProduct->video_link)[1]))
                                        <iframe class="embed-responsive-item"
                                            src="https://player.vimeo.com/video/{{ explode('vimeo.com/', $detailedProduct->video_link)[1] }}"
                                            width="500" height="281" frameborder="0" webkitallowfullscreen
                                            mozallowfullscreen allowfullscreen></iframe>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                --}}

                {{-- PDF Download --}}
                @if ($hasPdf)
                    <div class="card">
                        <div class="card-header" id="headingPdf">
                            <h2 class="mb-0">
                                <button class="btn btn-link collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapsePdf" aria-expanded="false" aria-controls="collapsePdf">
                                    {{ translate('Downloads') }}
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </h2>
                        </div>
                        <div id="collapsePdf" class="collapse" aria-labelledby="headingPdf"
                            data-parent="#productAccordion">
                            <div class="card-body text-center">
                                <a href="{{ uploaded_asset($detailedProduct->pdf) }}" class="btn btn-primary"
                                    target="_blank">
                                    {{ translate('Download') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    @endif

    <!-- Promote Link -->
    <div class="d-table width-100 mt-3">
        <div class="d-table-cell">
            @if (Auth::check() &&
                    addon_is_activated('affiliate_system') &&
                    get_affliate_option_status() &&
                    Auth::user()->affiliate_user != null &&
                    Auth::user()->affiliate_user->status)
                @php
                    if (Auth::check()) {
                        if (Auth::user()->referral_code == null) {
                            Auth::user()->referral_code = substr(Auth::user()->id . Str::random(10), 0, 10);
                            Auth::user()->save();
                        }
                        $referral_code = Auth::user()->referral_code;
                        $referral_code_url =
                            URL::to('/product') .
                            '/' .
                            $detailedProduct->slug .
                            "?product_referral_code=$referral_code";
                    }
                @endphp
                <div>
                    <button type="button" id="ref-cpurl-btn" class="btn btn-secondary w-200px rounded-0"
                        data-attrcpy="{{ translate('Copied') }}" onclick="CopyToClipboard(this)"
                        data-url="{{ $referral_code_url }}">{{ translate('Copy the Promote Link') }}</button>
                </div>
            @endif
        </div>
    </div>

    <!-- Refund -->
    @php
        $refund_sticker = get_setting('refund_sticker');
    @endphp
    @if (addon_is_activated('refund_request'))
        <div class="row no-gutters mt-3 d-none">
            <div class="col-sm-2">
                <div class="text-secondary fs-14 fw-400 mt-2">{{ translate('Refund') }}</div>
            </div>
            <div class="col-sm-10">
                @if ($detailedProduct->refundable == 1)
                    <a href="{{ route('returnpolicy') }}" target="_blank">
                        @if ($refund_sticker != null)
                            <img src="{{ uploaded_asset($refund_sticker) }}" height="36">
                        @else
                            <img src="{{ static_asset('assets/img/refund-sticker.jpg') }}" height="36">
                        @endif
                    </a>
                    <a href="{{ route('returnpolicy') }}" class="text-blue hov-text-primary fs-14 ml-3"
                        target="_blank">{{ translate('View Policy') }}</a>
                @else
                    <div class="text-dark fs-14 fw-400 mt-2">{{ translate('Not Applicable') }}</div>
                @endif
            </div>
        </div>
    @endif

    <!-- Seller Guarantees -->
    @if ($detailedProduct->digital == 1)
        @if ($detailedProduct->added_by == 'seller')
            <div class="row no-gutters mt-3 d-none">
                <div class="col-2">
                    <div class="text-secondary fs-14 fw-400">{{ translate('Seller Guarantees') }}</div>
                </div>
                <div class="col-10">
                    @if ($detailedProduct->user->shop->verification_status == 1)
                        <span class="text-success fs-14 fw-700">{{ translate('Verified seller') }}</span>
                    @else
                        <span class="text-danger fs-14 fw-700">{{ translate('Non verified seller') }}</span>
                    @endif
                </div>
            </div>
        @endif
    @endif
@endif

<!-- Share -->
<div class="row no-gutters mt-4 d-none">
    <div class="col-sm-2">
        <div class="text-secondary fs-14 fw-400 mt-2">{{ translate('Share') }}</div>
    </div>
    <div class="col-sm-10">
        <div class="aiz-share"></div>
    </div>
</div>


<!-- Product Enquiry Modal -->
<div class="modal fade" id="productEnquiryModal" tabindex="-1" role="dialog"
    aria-labelledby="productEnquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productEnquiryModalLabel">{{ translate('Product Enquiry') }}</h5>
                <button type="button" class="close" data-dismiss="modal"
                    aria-label="{{ translate('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="product_enquiry_store" class="form-default" role="form"
                    action="{{ route('product_enquiry_store') }}" method="POST">
                    @csrf
                    <!-- Hidden Fields -->
                    <input type="hidden" name="type" value="product">
                    <input type="hidden" name="product_id" id="enquiry_product_id" value="">
                    <input type="hidden" name="current_url" id="current_url" value="">

                    <!-- Product Name (Optional Display) -->
                    <div class="form-group">
                        <label for="enquiry_product_name"
                            class="fs-16 fw-700 text-soft-dark d-none">{{ translate('Product') }}</label>
                        <input type="text" id="enquiry_product_name" class="form-control rounded-0" readonly>
                    </div>

                    <div class="d-flex justify-content-between product-enquiry-form-name-email">
                        <!-- Name -->
                        <div class="form-group">
                            <label for="name"
                                class="fs-16 fw-700 text-soft-dark d-none">{{ translate('Name') }}
                                <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-0"
                                placeholder="{{ translate('Enter Your Name') }}" name="name" required>
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label for="email"
                                class="fs-16 fw-700 text-soft-dark d-none">{{ translate('Email') }} <span
                                    class="text-danger">*</span></label>
                            <input type="email" class="form-control rounded-0"
                                placeholder="{{ translate('Enter Your Email') }}" name="email" required>
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label for="phone"
                            class="fs-16 fw-700 text-soft-dark d-none">{{ translate('Phone no.') }} <span
                                class="text-danger">*</span></label>
                        <input type="tel" class="form-control rounded-0"
                            placeholder="{{ translate('Enter Your Phone') }}" name="phone" required>
                    </div>

                    <!-- Pincode -->
                    {{-- <div class="form-group">
                        <label for="pincode" class="fs-16 fw-700 text-soft-dark">{{ translate('Pincode') }} <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-0"
                            placeholder="{{ translate('Enter Pincode') }}" name="pincode" required>
                    </div> --}}

                    <!-- Query -->
                    <div class="form-group">
                        <label for="query"
                            class="fs-16 fw-700 text-soft-dark d-none">{{ translate('Tell us about your query') }}<span
                                class="text-danger">*</span></label>
                        <textarea class="form-control rounded-0" placeholder="{{ translate('Tell us about your query') }}"
                            name="content" rows="3" required></textarea>
                    </div>

                    <!-- Recaptcha (if enabled) -->
                    @if (get_setting('google_recaptcha') == 1)
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
                    <div class="mt-4 text-right">
                        @if (env('MAIL_USERNAME') == null && env('MAIL_PASSWORD') == null)
                            <a class="btn btn-primary fw-700 fs-16 rounded-0 w-200px" href="javascript:void(1)"
                                onclick="showWarning()">
                                {{ translate('Submit') }}
                            </a>
                        @else
                            <button type="submit"
                                class="btn btn-primary fw-700 fs-16 rounded-0 w-200px">{{ translate('Submit') }}</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


</div>

@section('custom_script')
    <script>
        // Using jQuery
        $(document).ready(function() {


            // initValidate(`#product_enquiry_store`);

            $('.product-enquiry-btn').on('click', function() {
                // Get the product_id from the button's data attribute
                var productId = $(this).data('product-id');
                // Set the value in the hidden input field inside the modal
                $('#enquiry_product_id').val(productId);
                // Set the current URL dynamically
                $('#current_url').val(window.location.href);
                // Show the modal
                $('#productEnquiryModal').modal('show');
            });
        });
    </script>
@endsection



<script>
    document.addEventListener('DOMContentLoaded', function() {
        const enquiryButtons = document.querySelectorAll('.detail-product-enquiry-btn');

        enquiryButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const productName = this.getAttribute('data-product-name');

                document.getElementById('enquiry_product_id').value = productId;
                document.getElementById('current_url').value = window.location.href;
                document.getElementById('enquiry_product_name').value = productName;

                $('#productEnquiryModal').modal('show'); // Bootstrap 4 modal open
            });
        });
    });
</script>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select the <p> that comes right after your label
        const targets = document.querySelectorAll('.detail-font-14px + p');

        targets.forEach(function(p) {
            // add clamp class
            p.classList.add('clamped-text');

            // create toggle button
            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'view-toggle';
            toggle.textContent = 'View More';

            // insert toggle after the paragraph
            p.insertAdjacentElement('afterend', toggle);

            // check if text overflows
            requestAnimationFrame(() => {
                if (p.scrollHeight <= p.clientHeight + 1) {
                    toggle.style.display = 'none'; // hide toggle if short text
                }
            });

            // toggle expand/collapse
            toggle.addEventListener('click', function() {
                const expanded = p.classList.toggle('expanded');
                toggle.textContent = expanded ? 'View Less' : 'View More';
            });
        });
    });
</script>


<style>
    /* clamp styles */
    .clamped-text {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        /* show only 2 lines */
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        transition: max-height .2s ease;
        line-height: 1.6;
    }

    /* expanded state */
    .clamped-text.expanded {
        -webkit-line-clamp: initial;
        max-height: none;
    }

    /* toggle button (looks like a link) */
    .view-toggle {
        cursor: pointer;
        display: inline-block;
        margin-top: 0px !important;
        font-size: 13px;
        border: none;
        background: none;
        padding: 0;
        color: #2b56a1;
        /* bootstrap primary */
    }

    @media (min-width: 768px) {

        /* md and up */
        #productEnquiryModal .modal-dialog {
            max-width: 440px;
            /* width adjust kar sakte ho */
            display: flex;
            align-items: center;
            /* vertically center */
            justify-content: center;
            /* horizontally center */
        }

        #productEnquiryModal .modal-content {
            height: 100%;
            /* modal-content modal-dialog ka height fill kare */
            overflow-y: auto;
            /* agar content zyada ho toh scroll */
        }
    }
</style>
