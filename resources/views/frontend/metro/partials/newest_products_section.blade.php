@php
    // $best_selling_products = get_best_selling_products(20);

    use App\Models\Product;
    use App\Models\Category;
    use Illuminate\Support\Str;

    $webType = session('web_type_name');
    $popularItems = [];

    if ($webType == 'human') {
        $popularItems = json_decode(get_setting('popular_items_categories_human'), true);
    } elseif ($webType == 'veterinary') {
        $popularItems = json_decode(get_setting('popular_items_categories_veterinary'), true);
    }

    $pop_categories = Category::select('id', 'name')
        ->whereIn('id', $popularItems ?? [])
        ->get();

    $newest_products = Product::whereIn('category_id', $popularItems ?? [])->get();
@endphp

@if (count($newest_products) > 0 && $newest_products->isNotEmpty())
    <section class="pt-0 pt-md-3 pb-3 pb-lg-3 pb-md-0">
        <div class="container">
            <div class="row">
                <div class="col-md-9 col-12 width_80 pt-4 pr-md-4">
                    <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                        <!-- Title -->
                        <h3 class="fs-16 fs-md-24 fw-600 mb-2 mb-md-2">
                            <span class="">{{ translate('Popular Items') }}</span>
                            <div class="heading_border blue_bg1"></div>
                        </h3>

                        <!-- Links -->
                        <div class="d-flex">
                            <a class="blue_light_clr fs-14 fw-400 hov-text-primary animate-underline-primary"
                                href="{{ route('search', ['sort_by' => 'newest']) }}">{{ translate('View More') }} <i
                                    class="las la-angle-double-right"></i></a>
                        </div>
                    </div>
                    <!-- Products Section -->

                    <ul class="nav nav-pills mb-3 tabs_products" id="pills-tab" role="tablist">
                        @foreach ($pop_categories as $key => $row_catg)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link @if ($key == 0) active @endif"
                                    id="{{ Str::slug($row_catg->name) }}-tab" data-toggle="pill"
                                    data-target="#pills-{{ Str::slug($row_catg->name) }}" type="button"
                                    role="tab" aria-controls="pills-{{ Str::slug($row_catg->name) }}"
                                    aria-selected="{{ $key == 0 ? 'true' : 'false' }}">
                                    {{ ucfirst($row_catg->name) }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    <div class="tab-content" id="pills-tabContent">
                        @foreach ($pop_categories as $key => $row_catg)
                            <div class="tab-pane fade @if ($key == 0) show active @endif"
                                id="pills-{{ Str::slug($row_catg->name) }}" role="tabpanel"
                                aria-labelledby="pills-{{ Str::slug($row_catg->name) }}-tab">
                                <div class="aiz-carousel arrow-none sm-gutters-16" data-items="4" data-xl-items="4"
                                    data-lg-items="4" data-md-items="3" data-sm-items="2" data-xs-items="2"
                                    data-arrows='true' data-infinite='false'>
                                    @php
                                        $filter_newest_products = $newest_products->where('category_id', $row_catg->id);
                                    @endphp
                                    @foreach ($filter_newest_products as $new_product)
                                        <div
                                            class="carousel-box px-0 position-relative has-transition product_listing_box">
                                            @include(
                                                'frontend.' .
                                                    get_setting('homepage_select') .
                                                    '.partials.product_box_1',
                                                ['product' => $new_product]
                                            )
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
                <div class="col-md-3 d-lg-block d-md-none d-none width_20 mt-4">
                    <img class="w-100" src="{{ static_asset('assets/img/product_items_photo.png') }}" />
                </div>
            </div>
            <!-- Top Section -->

        </div>
    </section>
@endif

@section('custom-script-section')
<script>
    $(document).ready(function () {
        // Listen for tab change event
        $('#pills-tab a').on('shown.bs.tab', function (e) {

            console.log('goku');

            var target = $(e.target).attr('href'); // Get the target tab's href
            var $carousel = $(target).find('.aiz-carousel'); // Find the carousel within the active tab

            // Check if the carousel exists and reinitialize
            if ($carousel.length > 0) {
                $carousel.each(function () {
                    var carousel = $(this);
                    
                    // Check if it's already initialized and destroy it before reinitializing
                    if (carousel.hasClass('owl-loaded')) {
                        carousel.trigger('destroy.owl.carousel').removeClass('owl-loaded');
                    }

                    // Reinitialize the carousel
                    carousel.owlCarousel({
                        // Add any desired settings for your OwlCarousel here
                    });
                });
            }
        });
    });
</script>
@endsection
