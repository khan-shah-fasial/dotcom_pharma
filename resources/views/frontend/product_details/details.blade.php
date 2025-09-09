<style>
    .div_disable {
        pointer-events: none;
        opacity: 0.5;
    }
</style>

<div class="text-left product_disc_text">
    <!-- Product Name -->
    <h2 class="mb-3 fs-md-34 fs-24 fw-600">
        {{ $detailedProduct->getTranslation('name') }}
    </h2>

    <!-- Drug Name -->
    @if (!empty($detailedProduct->drug_name))
        <p class="mb-2 fs-14 text-dark">
            <span class="fw-500 text-dark">{{ translate('Drug Name') }}:</span>
            {{ $detailedProduct->drug_name }}
        </p>
    @endif

    <!-- Reviews -->
    @if ($detailedProduct->auction_product != 1)
        <div class="mb-3 d-flex align-items-center gap-2">
            <span class="rating rating-mr-2">
                {{ renderStarRating($detailedProduct->rating) }}
            </span>
            <span class="text-muted fs-6">({{ $detailedProduct->reviews->where('status', 1)->count() }}
                {{ translate('Customer Reviews') }})</span>
        </div>
    @endif

    <div class="row">

        @if (!empty($detailedProduct->brand->name))
            <div class="col-12 mt-1">
                <span class="fw-500 fs-14 text-dark">{{ translate('Brand / Mfg') }}:</span>
                <span class="text-secondary  fs-14 ">{{ $detailedProduct->brand->name ?? '-' }}</span>
            </div>
        @endif

        @if ($detailedProduct->pharma_categories)
            <div class="col-12 mt-1">
                <span class="fw-500 fs-14 text-dark">{{ translate('Pharma Categories') }}:</span>
                <span class="text-secondary  fs-14 ">{{ $detailedProduct->pharma_categories ?? '-' }}</span>
            </div>
        @endif

        @if ($detailedProduct->product_form)
            <div class="col-12 mt-1">
                <span class="fw-500 fs-14 text-dark">{{ translate('Product Form') }}:</span>
                <span class="text-secondary  fs-14 ">{{ $detailedProduct->product_form ?? '-' }}</span>
            </div>
        @endif

        @if (!is_null($detailedProduct->prescription_req))
            <!-- Discount percentage -->


            <div class="col-12 mt-1">
                <span class="fw-500 fs-14 text-dark">{{ translate('Prescription Required') }}:</span>
                <span
                    class="text-secondary  fs-14 ">{{ $detailedProduct->prescription_req == 1 ? 'Yes' : 'No' }}</span>
            </div>
        @endif

        <div class="col-12 mt-1">
            <span class="fw-500 fs-14 text-dark">{{ translate('SKU') }}:</span>
            <span id="sku-product-details" class="text-secondary  fs-14 "></span>
        </div>

        {{-- Pricing Row --}}

        <div class="col-12 mt-3 pb-0">

            @if (discount_in_percentage($detailedProduct) > 0)
                <span class=" ml-0 fs-18 fw-500 text-white w-35px text-center p-1"
                    style="color: #E31E24 !important;">-{{ discount_in_percentage($detailedProduct) }}%off</span>
            @endif

            <span class="text-secondary fs-14"><span id="per-piece-price-product-details"
                    class="text-primary fs-26 font-600 fw-500"> </span> / Piece</span>
            <span class="fw-500 fs-14 text-dark ml-3">{{ translate('Count') }}:</span>
            <span id="package-count-product-details" class="text-secondary fs-14 "> {{ $detailedProduct->product_count ?? '-' }} / Count</span>
        </div>

        {{-- Unit/MRP --}}

        @auth
            @if(auth()->user()->user_subtype !== null)
                <div class="col-12 mt-2">
                    <span class="fw-500 fs-14 text-dark">{{ translate('Without Tax') }}:</span>
                    <span id="without-tax-product" class="text-secondary fs-14"></span>
                    <span class="fw-500 fs-14 text-dark ml-3">{{ translate('Tax Amount') }}:</span>
                    <span id="tax-product-details" class="text-secondary fs-14"></span>
                </div>
            @endif
        @endauth

        <div class="col-12 mt-1">
            <span class="fw-500 fs-14 text-dark">{{ translate('Unit/MRP') }}:</span>
            <span id="mrp-unit" class="text-secondary fs-14"></span><br>
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

        {{-- Type --}}
        @if ($detailedProduct->product_type)
            <div class="col-12 mt-2">
                <span class="fw-500 fs-14 text-dark">{{ translate('Type') }}:</span>
                <span class="text-secondary  fs-14 ">{{ $detailedProduct->product_type ?? '-' }}</span>
            </div>
        @endif

        {{-- Material --}}
        @if ($detailedProduct->product_material)
            <div class="col-12 mt-1">
                <span class="fw-500 fs-14 text-dark">{{ translate('Material') }}:</span>
                <span class="text-secondary  fs-14 ">{{ $detailedProduct->product_material ?? '-' }}</span>
            </div>
        @endif

        {{-- Origin --}}
        @if ($detailedProduct->product_origin)
            <div class="col-12 mt-1">
                <span class="fw-500 fs-14 text-dark">{{ translate('Country of Origin') }}:</span>
                <span class="text-secondary  fs-14 ">{{ $detailedProduct->product_origin ?? '-' }}</span>
            </div>
        @endif

        {{-- Min Pack Size --}}
        {{-- @if ($detailedProduct->product_min_pack_size) --}}
            <div class="col-12 mt-1">
                <span class="fw-500 fs-14 text-dark">{{ translate('Minimum Pack Size') }}:</span>
                <span id="min-package-count-product-details" class="text-secondary  fs-14 "></span>
            </div>
        {{-- @endif --}}

        {{-- Final 6 fields --}}
        <div class="col-4 mt-1">
            <span class="fw-500 fs-14 text-dark">{{ translate('Stock Available') }}:</span>
            <span id="qnt-product-details" class="text-secondary  fs-14"></span>
        </div>

        @if (!empty($detailedProduct->product_exp_date))
            <div class="col-8 mt-1">
                <span class="fw-500 fs-14 text-dark">{{ translate('Expiry Date') }}:</span>
                <span class="text-secondary  fs-14 ">{{ $detailedProduct->product_exp_date ?? '-' }}</span>
            </div>
        @endif

        <div class="col-4 mt-1">
            <span class="fw-500 fs-14 text-dark">{{ translate('Category') }}:</span>
            <span class="text-secondary  fs-14 ">{{ ucfirst($category_name ?? '-') }}</span>
        </div>

        @if (!empty($detailedProduct->product_hsn))
            <div class="col-8 mt-1">
                <span class="fw-500 fs-14 text-dark">{{ translate('HSN / HS Code') }}:</span>
                <span class="text-secondary  fs-14 ">{{ $detailedProduct->product_hsn ?? '-' }}</span>
            </div>
        @endif

        <div class="col-4 mt-1">
            <span class="fw-500 fs-14 text-dark">{{ translate('Dimentions') }}:</span>
            <span id="dimentions-product-details" class="text-secondary  fs-14 "></span>
        </div>

        <div class="col-8 mt-1">
            <span class="fw-500 fs-14 text-dark">{{ translate('Weight / Volume') }}:</span>
            <span id="weight-volume-product-details" class="text-secondary  fs-14 "></span>
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
            <a href="javascript:void();" onclick="goToView('product_query')" class="text-primary fs-14 fw-600 d-flex">
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
            <a href="javascript:void(1);" onclick='showSizeChartDetail({{ $sizeChartId }}, "{{ $sizeChartName }}")'
                class="animate-underline-primary">{{ translate('Show size guide') }}</a>
        </div>
    @endif
</div>

<hr>
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



@if ($detailedProduct->auction_product != 1)
    <!--Display price & vairation to only loggedin user [by nexgeno]-->
    <form id="option-choice-form">
        @csrf
        <input type="hidden" name="id" value="{{ $detailedProduct->id }}">

        @if ($detailedProduct->digital == 0)
            <!-- Choice Options -->
            @if ($detailedProduct->choice_options != null)
                @foreach (json_decode($detailedProduct->choice_options) as $key => $choice)
                    <!--<div class="row no-gutters mb-3">--> <!--old code-->
                    <div class="row no-gutters mb-3 @if (strtolower(get_single_attribute_name($choice->attribute_id)) == 'role') div_disable @endif">
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
                                                @if ($key == 0) checked @endif>--> <!--old code-->
                                        <input type="radio" name="attribute_id_{{ $choice->attribute_id }}"
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
                                    <input type="radio" name="color" value="{{ get_single_color_name($color) }}"
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
            <div class="row no-gutters mb-3">
                <div class="col-sm-2">
                    <div class="fw-500 fs-16 text-dark mt-2">{{ translate('Quantity') }}</div>
                </div>
                <div class="col-sm-10">
                    <div class="product-quantity d-flex align-items-center">
                        <div class="row no-gutters align-items-center aiz-plus-minus mr-3" style="width: 130px;">
                            <button class="btn col-auto btn-icon btn-sm btn-light rounded-0" type="button"
                                data-type="minus" data-field="quantity" disabled="">
                                <i class="las la-minus"></i>
                            </button>
                            <input type="number" name="quantity"
                                class="col border-0 text-center flex-grow-1 fs-16 input-number" placeholder="1"
                                value="{{ $detailedProduct->min_qty }}" min="{{ $detailedProduct->min_qty }}"
                                max="10" lang="en">
                            <button class="btn col-auto btn-icon btn-sm btn-light rounded-0" type="button"
                                data-type="plus" data-field="quantity">
                                <i class="las la-plus"></i>
                            </button>
                        </div>
                        @php
                            $qty = 0;
                            foreach ($detailedProduct->stocks as $key => $stock) {
                                $qty += $stock->qty;
                            }
                        @endphp
                        <div class="avialable-amount opacity-60 d-none">
                            @if ($detailedProduct->stock_visibility_state == 'quantity')
                                (<span id="available-quantity">{{ $qty }}</span>
                                {{ translate('available') }})
                            @elseif($detailedProduct->stock_visibility_state == 'text' && $qty >= 1)
                                (<span id="available-quantity" class="">{{ translate('In Stock') }}</span>)
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Quantity -->
            <input type="hidden" name="quantity" value="1">
        @endif

        <!-- Total Price -->
        <div class="row no-gutters pb-1 d-none" id="chosen_price_div">
            <div class="col-sm-4">
                <div class="fw-500 fs-14 text-dark mt-2">{{ translate('Total Amount Product Wise') }}:</div>
            </div>
            <div class="col-sm-8">
                <div class="product-price">
                    <strong id="chosen_price" class="fs-24 fw-500" style="color:#23780E;">

                    </strong>
                </div>
            </div>
        </div>

    </form>
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

    <!-- @if (!empty($detailedProduct->tags))
<div class="d-flex flex-wrap align-items-center mb-0">
                <span class="fs-14 fw-500 mr-4 w-80px">{{ translate('Tags') }}</span><br>
                <p class="text-secondary fs-14 fw-400 pb-0 mb-0">{{ str_replace(',', ', ', $detailedProduct->tags) }}
                </p>
            </div>
@endif -->


    <hr>


    <!-- Add to cart & Buy now Buttons -->
    <div class="mt-4">
        @if (!is_user_loggedin())
            <p class="fs-14">Please login / register to buy or to get detailed information of the product</p>
        @endif
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
                    class="btn btn-success mr-3 add-to-cart fw-600 min-w-100px rounded-0 text-white border-radius-50"
                    @if (Auth::check() || get_Setting('guest_checkout_activation') == 1) onclick="addToCart()" @else onclick="showLoginModal()" @endif>
                    <i class="las la-shopping-bag"></i> {{ translate('Add to cart') }}
                </button>
                <button type="button"
                    class="btn btn-primary buy-now fw-600 add-to-cart min-w-100px rounded-0 border-radius-50"
                    @if (Auth::check() || get_Setting('guest_checkout_activation') == 1) onclick="addToCart()" @else onclick="showLoginModal()" @endif>
                    <i class="la la-shopping-cart"></i> {{ translate('Buy Now') }}
                </button>
            @endif
            <button type="button" class="btn btn-secondary out-of-stock fw-600 d-none border-radius-50" disabled>
                <i class="la la-cart-arrow-down"></i> {{ translate('Out of Stock') }}
            </button>
        @elseif ($detailedProduct->digital == 1)
            <button type="button"
                class="btn btn-success mr-3 add-to-cart fw-600 min-w-150px rounded-0 text-white border-radius-50"
                @if (Auth::check() || get_Setting('guest_checkout_activation') == 1) onclick="addToCart()" @else onclick="showLoginModal()" @endif>
                <i class="las la-shopping-bag"></i> {{ translate('Add to cart') }}
            </button>
            <button type="button"
                class="btn btn-primary buy-now fw-600 add-to-cart min-w-150px rounded-0 border-radius-50"
                @if (Auth::check() || get_Setting('guest_checkout_activation') == 1) onclick="addToCart()" @else onclick="showLoginModal()" @endif>
                <i class="la la-shopping-cart"></i> {{ translate('Buy Now') }}
            </button>
        @endif
        <button type="button"
            class="btn btn-info buy-now fw-600 add-to-cart min-w-150px rounded-0 border-radius-50 product-enquiry-btn ml-3"
            data-product-id="{{ $detailedProduct->id }}">
            <i class="las la-question-circle fs-20 position_btn"></i> {{ translate('Product Enquiry') }}
        </button>
    </div>



    <div class="delivery_section">

        <div class="delivery_boxex">
            <div class="delivery_boxex_img"><img src="{{ static_asset('assets/img/free_delivery.svg') }}"></div>
            <p>Free Delivery</p>
        </div>

        <div class="delivery_boxex">
            <div class="delivery_boxex_img"><img src="{{ static_asset('assets/img/secure_icons.svg') }}"></div>
            <p>Secure Transaction</p>
        </div>

        <div class="delivery_boxex">
            <div class="delivery_boxex_img"><img src="{{ static_asset('assets/img/top_brands.svg') }}"></div>
            <p>Top Brand</p>
        </div>

        <div class="delivery_boxex">
            <div class="delivery_boxex_img"><img src="{{ static_asset('assets/img/cash_dilevery.svg') }}"></div>
            <p>Cash on Delivery</p>
        </div>

        <div class="delivery_boxex">
            <div class="delivery_boxex_img"><img src="{{ static_asset('assets/img/non_return_icons.svg') }}"></div>
            <p>Non Return</p>
        </div>

    </div>

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
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
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

                    <!-- Name -->
                    <div class="form-group">
                        <label for="name" class="fs-16 fw-700 text-soft-dark">{{ translate('Name') }} <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-0"
                            placeholder="{{ translate('Enter Name') }}" name="name" required>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email" class="fs-16 fw-700 text-soft-dark">{{ translate('Email') }} <span
                                class="text-danger">*</span></label>
                        <input type="email" class="form-control rounded-0"
                            placeholder="{{ translate('Enter Email') }}" name="email" required>
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label for="phone" class="fs-16 fw-700 text-soft-dark">{{ translate('Phone no.') }} <span
                                class="text-danger">*</span></label>
                        <input type="tel" class="form-control rounded-0"
                            placeholder="{{ translate('Enter Phone') }}" name="phone" required>
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
                            class="fs-16 fw-700 text-soft-dark">{{ translate('Tell us about your query') }}<span
                                class="text-danger">*</span></label>
                        <textarea class="form-control rounded-0" placeholder="{{ translate('Type here...') }}" name="content"
                            rows="3" required></textarea>
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
