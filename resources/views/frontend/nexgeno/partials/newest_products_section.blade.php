@php
    // $best_selling_products = get_best_selling_products(20);

    // use App\Models\Product;
    // use App\Models\Category;
    // use Illuminate\Support\Facades\Cache;

    // $webType = session('web_type_name') ?? 'default';
    // $categoriesCacheKey = 'popular_categories_' . $webType;
    // $productsCacheKey = 'newest_products_' . $webType;

    // // Cache popular categories
    // $pop_categories = Cache::rememberForever($categoriesCacheKey, function () use ($webType) {
    //     if ($webType == 'human') {
    //         $popularItems = json_decode(get_setting('popular_items_categories_human'), true) ?: [];
    //     } elseif ($webType == 'veterinary') {
    //         $popularItems = json_decode(get_setting('popular_items_categories_veterinary'), true) ?: [];
    //     } else {
    //         $popularItems = [];
    //     }

    //     return Category::select('id', 'name')
    //         ->whereIn('id', $popularItems)
    //         ->get();
    // });

    // // Cache newest products
    // $newest_products = Cache::rememberForever($productsCacheKey, function () use ($webType) {
    //     if ($webType == 'human') {
    //         $popularItems = json_decode(get_setting('popular_items_categories_human'), true) ?: [];
    //     } elseif ($webType == 'veterinary') {
    //         $popularItems = json_decode(get_setting('popular_items_categories_veterinary'), true) ?: [];
    //     } else {
    //         $popularItems = [];
    //     }

    //     return Product::whereIn('category_id', $popularItems)
    //         ->get();
    // });
    $pop_categories = getPopularCategories();
    $newest_products = getNewestProducts();
@endphp

@if (count($newest_products) > 0 && $newest_products->isNotEmpty())
    <section class="pt-0 pt-md-0 pb-2 pb-lg-3 pb-md-0">
        <div class="container">
            <div class="row">
                <div class="col-lg-9 col-12 width_80 pt-4 pr-md-4">
                    <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                        <!-- Title -->
                        <h3 class="fs-16 fs-md-24 fw-600 mb-2 mb-md-2">
                            <span>{{ translate('Popular Items') }}</span>
                            <div class="heading_border blue_bg1"></div>
                        </h3>

                        <!-- Links -->
                        <div class="d-flex">
                            <a class="blue_light_clr fs-14 fw-400 hov-text-primary animate-underline-primary"
                                href="{{ route('search', ['sort_by' => 'newest']) }}">
                                {{ translate('View More') }} <i class="las la-angle-double-right"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Category Tabs -->
                    <ul class="nav nav-pills mb-3 tabs_products" id="pills-tab" role="tablist">
                        @foreach ($pop_categories as $key => $row_catg)
                            @php $slug = Str::slug($row_catg->name); @endphp
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $key === 0 ? 'active' : '' }}" id="{{ $slug }}-tab"
                                    data-toggle="pill" data-target="#pills-{{ $slug }}" type="button"
                                    role="tab" aria-controls="pills-{{ $slug }}"
                                    aria-selected="{{ $key === 0 ? 'true' : 'false' }}">
                                    {{ ucfirst($row_catg->name) }}
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Tab Content (grid, no carousel) -->
                    <div class="tab-content" id="pills-tabContent">
                        @foreach ($pop_categories as $key => $row_catg)
                            @php
                                $slug = Str::slug($row_catg->name);
                                $filter_newest_products = $newest_products
                                    ->filter(function ($item) use ($row_catg) {
                                        return $item->pc_category_id == $row_catg->id || $item->category_id == $row_catg->id;
                                    })
                                    ->take(4);
                            @endphp

                            <div class="tab-pane fade {{ $key === 0 ? 'show active' : '' }}"
                                id="pills-{{ $slug }}" role="tabpanel"
                                aria-labelledby="{{ $slug }}-tab">

                                <div class="row">
                                    @forelse ($filter_newest_products as $new_product)
                                        <div class="col-6 col-md-4 col-lg-3 mb-md-3 mb-0">
                                            @include(
                                                'frontend.' .
                                                    get_setting('homepage_select') .
                                                    '.partials.product_box_1',
                                                ['product' => $new_product]
                                            )
                                        </div>
                                    @empty
                                        <div class="col-12">
                                            <div class="text-muted py-4">
                                                {{ translate('No products found in this category.') }}</div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-md-3 col-12 d-lg-block d-md-none d-none width_20 mt-4">
                    <img class="w-100" src="{{ static_asset('assets/img/product_items_photo.png') }}"
                        alt="Popular items">
                </div>
            </div>
        </div>
    </section>

@endif

@section('custom-script-section')
    {{-- <script>
        $(document).ready(function() {
            // Listen for tab change event
            $('#pills-tab a').on('shown.bs.tab', function(e) {

                console.log('goku');

                var target = $(e.target).attr('href'); // Get the target tab's href
                var $carousel = $(target).find('.aiz-carousel'); // Find the carousel within the active tab

                // Check if the carousel exists and reinitialize
                if ($carousel.length > 0) {
                    $carousel.each(function() {
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
    </script> --}}
@endsection
