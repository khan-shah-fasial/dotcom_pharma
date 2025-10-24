<?php

// app/Http/Controllers/Search2Controller.php
namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use Illuminate\Http\Request;
use App\Utility\CategoryUtility;
use App\Models\AttributeCategory;
use Illuminate\Support\Facades\View;

class Search2Controller extends Controller
{
    public function index(Request $request, $category_id = null, $brand_id = null)
    {
        [$products, $viewData] = $this->buildListing($request, $category_id, $brand_id);
        return view('frontend.product_listing_ajax', array_merge($viewData, compact('products')));
    }

    public function listingByCategory(Request $request, $category_slug)
    {
        $category = Category::where('slug', $category_slug)->firstOrFail();
        return $this->index($request, $category->id, null);
    }

    public function listingByBrand(Request $request, $brand_slug)
    {
        $brand = Brand::where('slug', $brand_slug)->firstOrFail();
        return $this->index($request, null, $brand->id);
    }

    /**
     * Core query builder used by both page and ajax.
     * Returns [LengthAwarePaginator $products, array $viewData]
     */
    private function buildListing(Request $request, $category_id = null, $brand_id = null)
    {
        $query       = trim((string)$request->keyword);
        $sort_by     = $request->sort_by;
        $min_price   = $request->min_price;
        $max_price   = $request->max_price;
        $color       = $request->color;
        $selected_attribute_values = (array)$request->input('selected_attribute_values', []);
        $selected_color = (array)$request->input('selected_color', []);
        $catIds      = (array)$request->input('category_ids', []); // for multiple category checkboxes (optional)
        $pageSize    = (int)($request->input('page_size', 24) ?: 24);

        // base collections for filters (top level when no category)
        $categories = [];
        $attributes = Attribute::all();
        $colors     = Color::all();
        $category   = null;

        $products = Product::query();

        // BRAND
        if ($brand_id) {
            $products->where('brand_id', $brand_id);
        } elseif ($request->filled('brand')) {
            $brand = Brand::where('slug', $request->brand)->first();
            if ($brand) $products->where('brand_id', $brand->id);
        }

        // CATEGORY (from route) OR multi-select from filters
        if ($category_id) {
            $catTree = CategoryUtility::children_ids($category_id);
            $catTree[] = $category_id;
            $category = Category::with('childrenCategories')->findOrFail($category_id);
            $products->whereIn('category_id', $catTree);

            $attribute_ids = AttributeCategory::whereIn('category_id', $catTree)->pluck('attribute_id');
            $attributes    = Attribute::whereIn('id', $attribute_ids)->get();
        } elseif (!empty($catIds)) {
            $products->whereIn('category_id', $catIds);
        } else {
            $categories = Category::with('childrenCategories', 'coverImage')
                ->where('level', 0)->orderBy('order_level', 'desc')->get();
        }

        // KEYWORD (ranked)
        if ($query !== '') {
            $products->where(function ($q) use ($query) {
                foreach (explode(' ', $query) as $word) {
                    $q->where(function ($qq) use ($word) {
                        $qq->where('name', 'like', '%'.$word.'%')
                           ->orWhere('tags', 'like', '%'.$word.'%')
                           ->orWhereHas('product_translations', fn($qt) => $qt->where('name', 'like', '%'.$word.'%'))
                           ->orWhereHas('stocks', fn($qs) => $qs->where('sku', 'like', '%'.$word.'%'));
                    });
                }
            });

            $products->orderByRaw(
                'CASE WHEN name LIKE ? THEN 1 WHEN name LIKE ? THEN 2 ELSE 3 END',
                [$query.'%', '%'.$query.'%']
            );
        }

        // COLOR
        if ($color) {
            $products->where('colors', 'like', '%"'.$color.'"%');
        }

        // ATTRIBUTES (OR across all selected values)
        if ($request->has('selected_attribute_values')) {
            $selected_attribute_values = $request->selected_attribute_values;
            $products->where(function ($query) use ($selected_attribute_values) {
                foreach ($selected_attribute_values as $key => $value) {
                    $str = '"' . $value . '"';

                    $query->orWhere('choice_options', 'like', '%' . $str . '%');
                }
            });
        }

        // ---------- SCOPED MIN/MAX (after all filters EXCEPT price) ----------
        $scopedForBounds = filter_products(clone $products);
        $bounds = (clone $scopedForBounds)
            ->selectRaw('MIN(unit_price) AS min_price, MAX(unit_price) AS max_price')
            ->first();
        $scopedMin = (int)($bounds->min_price ?? 0);
        $scopedMax = (int)($bounds->max_price ?? 0);
        // ---------------------------------------------------------------------

        // PRICE (apply AFTER we computed scoped bounds)
        if ($min_price !== null && $max_price !== null && $min_price !== '' && $max_price !== '') {
            $products->whereBetween('unit_price', [(float)$min_price, (float)$max_price]);
        }

        // SORT
        match ($sort_by) {
            'newest'     => $products->orderBy('created_at', 'desc'),
            'oldest'     => $products->orderBy('created_at', 'asc'),
            'price-asc'  => $products->orderBy('unit_price', 'asc'),
            'price-desc' => $products->orderBy('unit_price', 'desc'),
            default      => $products->orderBy('id', 'desc'),
        };

        // Eager + counts
        $products = filter_products($products)
            ->with(['taxes'])
            ->withCount(['reviews as approved_reviews_count' => fn($q) => $q->where('status', 1)])
            ->paginate($pageSize);

        // Global bounds for slider
        $globalMin = (int) get_product_min_unit_price();
        $globalMax = (int) get_product_max_unit_price();

        $viewData = compact(
            'query','sort_by','min_price','max_price','attributes', 'selected_attribute_values', 'colors', 'selected_color',
            'categories','category','category_id','brand_id','globalMin','globalMax','scopedMin','scopedMax'
        );
        $perPage     = $products->perPage();   // page size used
        $totalPages  = $products->lastPage();  // total number of pages
        $currentPage = $products->currentPage();
        $total       = $products->total();     // total products across all pages
        // compute ajax next page url for first render
        $ajaxNextPageUrl = (clone $products)->withPath(route('search.ajax.products'))->nextPageUrl();

        $viewData = array_merge($viewData, compact('perPage','totalPages','currentPage','total', 'ajaxNextPageUrl'));
        return [$products, $viewData];
    }

    /**
     * AJAX: returns HTML grid + next page url (for infinite scroll)
     */
    public function ajaxProducts(Request $request)
    {
        $category_id = $request->input('route_category_id'); // allows reuse on /category/{slug}
        $brand_id    = $request->input('route_brand_id');

        [$products, $viewData] = $this->buildListing($request, $category_id, $brand_id);

        $html = View::make('frontend.'.get_setting('homepage_select').'.partials.product_grid', array_merge($viewData, compact('products')))->render();
        // IMPORTANT: make nextPageUrl() point to the AJAX route
        $products->withPath(route('search.ajax.products'));
        return response()->json([
            'html'          => $html,
            'next_page_url' => $products->nextPageUrl(),
            'total'         => $products->total(),
            'per_page'      => $products->perPage(),   // 👈 per page
            'total_pages'   => $products->lastPage(),  // 👈 total pages
            'current_page'  => $products->currentPage(),
            'scoped_min'    => $viewData['scopedMin'],
            'scoped_max'    => $viewData['scopedMax'],
        ]);
    }

    /**
     * AJAX: fetch children categories for drilldown
     */
    public function ajaxCategoryChildren($id)
    {
        $category = Category::with('childrenCategories')->findOrFail($id);
        return response()->json([
            'id'        => $category->id,
            'name'      => $category->getTranslation('name'),
            'children'  => $category->childrenCategories->map(fn($c) => [
                'id'   => $c->id,
                'name' => $c->getTranslation('name'),
                'has_children' => $c->childrenCategories()->exists(),
            ])->values(),
        ]);
    }

    
    //Suggestional Search
    public function ajax_search(Request $request)
    {
        $keywords = array();
        $query = $request->search;
        $products = Product::where('published', 1)->where('tags', 'like', '%' . $query . '%')->get();
        foreach ($products as $key => $product) {
            foreach (explode(',', $product->tags) as $key => $tag) {
                if (stripos($tag, $query) !== false) {
                    if (sizeof($keywords) > 5) {
                        break;
                    } else {
                        if (!in_array(strtolower($tag), $keywords)) {
                            array_push($keywords, strtolower($tag));
                        }
                    }
                }
            }
        }

        $products_query = filter_products(Product::query());

        $products_query = $products_query->where('published', 1)
            ->where(function ($q) use ($query) {
                foreach (explode(' ', trim($query)) as $word) {
                    $q->where('name', 'like', '%' . $word . '%')
                        ->orWhere('tags', 'like', '%' . $word . '%')
                        ->orWhereHas('product_translations', function ($q) use ($word) {
                            $q->where('name', 'like', '%' . $word . '%');
                        })
                        ->orWhereHas('stocks', function ($q) use ($word) {
                            $q->where('sku', 'like', '%' . $word . '%');
                        });
                }
            });
        $case1 = $query . '%';
        $case2 = '%' . $query . '%';

        $products_query->orderByRaw('CASE
                WHEN name LIKE "'.$case1.'" THEN 1
                WHEN name LIKE "'.$case2.'" THEN 2
                ELSE 3
                END');
        $products = $products_query->limit(3)->get();

        $categories = Category::where('name', 'like', '%' . $query . '%')->get()->take(3);

        $shops = Shop::whereIn('user_id', verified_sellers_id())->where('name', 'like', '%' . $query . '%')->get()->take(3);

        if (sizeof($keywords) > 0 || sizeof($categories) > 0 || sizeof($products) > 0 || sizeof($shops) > 0) {
            return view('frontend.partials.search_content', compact('products', 'categories', 'keywords', 'shops'));
        }
        return '0';
    }
}
