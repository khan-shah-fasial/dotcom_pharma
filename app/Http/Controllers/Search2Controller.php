<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeCategory;
use App\Utility\CategoryUtility;
use Illuminate\Http\Request;
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
     * Returns [LengthAwarePaginator $products, array $viewData]
     */
    private function buildListing(Request $request, $category_id = null, $brand_id = null)
    {
        $query       = trim((string)$request->keyword);
        $sort_by     = $request->sort_by;
        $min_price   = $request->min_price;
        $max_price   = $request->max_price;

        $selected_attribute_values = (array)$request->input('selected_attribute_values', []);
        $selected_color            = null;

        // Single selected category id (from radio OR from array OR from route)
        $selected_category_id = null;

        // Query string can send category_id (radio)
        if ($request->filled('category_id')) {
            $selected_category_id = (int)$request->input('category_id');
        }

        // Backward compat: if someone still hits with category_ids[]=.. take the first
        if ($selected_category_id === null) {
            $catIdsRaw = (array)$request->input('category_ids', []);
            if (!empty($catIdsRaw)) {
                $selected_category_id = (int)array_values($catIdsRaw)[0];
            }
        }

        // If on /category/{slug} route and still nothing selected, default to route category
        if ($selected_category_id === null && $category_id) {
            $selected_category_id = (int)$category_id;
        }


        $pageSize    = (int)($request->input('page_size', 24) ?: 24);

        // Base filter datasets
        $attributes = Attribute::all();
        // $colors     = Color::all();
        $category   = null;

        $products = Product::query();

        // -------- BRAND --------
        if ($brand_id) {
            $products->where('brand_id', $brand_id);
        } elseif ($request->filled('brand')) {
            $brand = Brand::where('slug', $request->brand)->first();
            if ($brand) $products->where('brand_id', $brand->id);
        }

        // -------- CATEGORY SCOPE (affects products) --------
        if ($category_id) {
            // Route page still scopes by route subtree
            $catTree   = CategoryUtility::children_ids($category_id);
            $catTree[] = $category_id;
            $category  = Category::with('childrenCategories')->findOrFail($category_id);
            $products->whereIn('category_id', $catTree);

            // $attribute_ids = AttributeCategory::whereIn('category_id', $catTree)->pluck('attribute_id');
            // if ($attribute_ids->count() > 0) $attributes = Attribute::whereIn('id', $attribute_ids)->get();

        } elseif ($selected_category_id) {
            // Search page + radio selected: filter by the single id (or its subtree if you prefer)
            $products->where('category_id', $selected_category_id);

            // $attribute_ids = AttributeCategory::where('category_id', $selected_category_id)->pluck('attribute_id');
            // if ($attribute_ids->count() > 0) $attributes = Attribute::whereIn('id', $attribute_ids)->get();
        }

        // -------- KEYWORD --------
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

        // Did we scope by category (route or radio)?
        $hasCategoryScope = !is_null($category_id) || !is_null($selected_category_id);

        /**
         * ======= Build ATTRIBUTES for the panel from choice_options =======
         * Use the product scope BEFORE applying attribute/price filters,
         * so the panel reflects what exists in the current pool.
         */
        $attributes = $this->buildAttributesFromChoiceOptions(clone $products);
        $colors     = $hasCategoryScope
            ? $this->buildColorsFromProducts(clone $products) // only colors present in this pool
            : Color::orderBy('name')->get();
          
        // -------- ATTRIBUTES (OR across selected) --------
        if (!empty($selected_attribute_values)) {
            $products->where(function ($q) use ($selected_attribute_values) {
                foreach ($selected_attribute_values as $value) {
                    $q->orWhere('choice_options', 'like', '%"'.$value.'"%');
                }
            });
        }
        // -------- COLOR --------
        // -------- COLOR (robust & case-insensitive) --------
        $color = $request->filled('color') ? urldecode((string) $request->color) : null;

        if ($request->has('color')) {
            $str = '"' . $request->color . '"';
            $products->where('colors', 'like', '%' . $str . '%');
            $selected_color = $request->color;
        }


        // ----- SCOPED BOUNDS (pre-price) -----
        $scopedForBounds = filter_products(clone $products);
        $bounds = (clone $scopedForBounds)
            ->selectRaw('MIN(unit_price) AS min_price, MAX(unit_price) AS max_price')
            ->first();
        $scopedMin = (int)($bounds->min_price ?? 0);
        $scopedMax = (int)($bounds->max_price ?? 0);

        // -------- PRICE --------
        if ($min_price !== null && $max_price !== null && $min_price !== '' && $max_price !== '') {
            $products->whereBetween('unit_price', [(float)$min_price, (float)$max_price]);
        }

        // -------- SORT --------
        match ($sort_by) {
            'newest'     => $products->orderBy('created_at', 'desc'),
            'oldest'     => $products->orderBy('created_at', 'asc'),
            'price-asc'  => $products->orderBy('unit_price', 'asc'),
            'price-desc' => $products->orderBy('unit_price', 'desc'),
            default      => $products->orderBy('id', 'desc'),
        };

        // Eager + counts + paginate
        $products = filter_products($products)
            ->with(['taxes'])
            ->withCount(['reviews as approved_reviews_count' => fn($q) => $q->where('status', 1)])
            ->paginate($pageSize);

        // -------- Build FULL category tree (for sidebar) --------
        $allCats = Category::select('id','parent_id','name','level','order_level')
            ->orderBy('order_level','desc')->get();
        $preloadedChildren = $allCats->groupBy('parent_id');
        $roots = $preloadedChildren->get(0, collect())->merge($preloadedChildren->get(null, collect()));
        $categories = $roots;

        // For breadcrumbs/title
        $selected_category_name = null;
        if ($selected_category_id) {
            $selected_category_name = optional(Category::find($selected_category_id))->getTranslation('name');
        } elseif ($category_id) {
            $selected_category_name = optional(Category::find($category_id))->getTranslation('name');
        }

        // Bounds for slider
        $globalMin = (int) get_product_min_unit_price();
        $globalMax = (int) get_product_max_unit_price();

        $viewData = compact(
            'query','sort_by','min_price','max_price',
            'attributes','selected_attribute_values','colors','selected_color',
            'categories','category','category_id','brand_id',
            'globalMin','globalMax','scopedMin','scopedMax', 'selected_category_id','selected_category_name',
            'preloadedChildren', 'color'
        );

        $perPage     = $products->perPage();
        $totalPages  = $products->lastPage();
        $currentPage = $products->currentPage();
        $total       = $products->total();

        $ajaxNextPageUrl = (clone $products)->withPath(route('search.ajax.products'))->nextPageUrl();

        $viewData = array_merge($viewData, compact('perPage','totalPages','currentPage','total','ajaxNextPageUrl'));

        return [$products, $viewData];
    }

    /**
     * AJAX: returns HTML grid + next page url (for infinite scroll)
     */
    public function ajaxProducts(Request $request)
    {
        $category_id = $request->input('route_category_id');
        $brand_id    = $request->input('route_brand_id');

        [$products, $viewData] = $this->buildListing($request, $category_id, $brand_id);

        // Product grid
        $gridHtml = View::make(
            'frontend.'.get_setting('homepage_select').'.partials.product_grid',
            array_merge($viewData, compact('products'))
        )->render();

        // Attributes panel (derived from choice_options)
        $attributesHtml = View::make(
            'frontend.'.get_setting('homepage_select').'.partials.filters.attributes_filter',
            [
                'attributes'                => $viewData['attributes'],
                'selected_attribute_values' => $viewData['selected_attribute_values'] ?? [],
            ]
        )->render();

        $colorsHtml = '';
        if (($viewData['colors'] ?? collect())->count() > 0) {
            $colorsHtml = View::make(
                'frontend.'.get_setting('homepage_select').'.partials.filters.color_filter',
                [
                    'colors'         => $viewData['colors'],
                    'selected_color' => $viewData['color'] ?? null,
                ]
            )->render();
        }


        $products->withPath(route('search.ajax.products'));

        return response()->json([
            'attributes_html'   => $attributesHtml,
            'colors_html'       => $colorsHtml,
            'html'              => $gridHtml,
            'next_page_url'     => $products->nextPageUrl(),
            'total'             => $products->total(),
            'per_page'          => $products->perPage(),
            'total_pages'       => $products->lastPage(),
            'current_page'      => $products->currentPage(),
            'scoped_min'        => $viewData['scopedMin'],
            'scoped_max'        => $viewData['scopedMax'],
        ]);
    }

    public function ajaxCategoryChildren($id)
    {
        // Kept for compatibility; not used when rendering full tree.
        $category = Category::with('childrenCategories')->findOrFail($id);
        return response()->json([
            'id'        => $category->id,
            'name'      => $category->getTranslation('name'),
            'children'  => $category->childrenCategories->map(fn($c) => [
                'id'           => $c->id,
                'name'         => $c->getTranslation('name'),
                'has_children' => $c->childrenCategories()->exists(),
            ])->values(),
        ]);
    }

    // Suggestional Search (unchanged)
    public function ajax_search(Request $request)
    {
        $keywords = [];
        $query = $request->search;

        $products = Product::where('published', 1)->where('tags', 'like', '%' . $query . '%')->get();
        foreach ($products as $product) {
            foreach (explode(',', $product->tags) as $tag) {
                if (stripos($tag, $query) !== false) {
                    if (count($keywords) > 5) break;
                    $tagLower = strtolower($tag);
                    if (!in_array($tagLower, $keywords)) $keywords[] = $tagLower;
                }
            }
        }

        $products_query = filter_products(Product::query());
        $products_query = $products_query->where('published', 1)
            ->where(function ($q) use ($query) {
                foreach (explode(' ', trim($query)) as $word) {
                    $q->where('name', 'like', '%'.$word.'%')
                        ->orWhere('tags', 'like', '%'.$word.'%')
                        ->orWhereHas('product_translations', function ($q) use ($word) {
                            $q->where('name', 'like', '%'.$word.'%');
                        })
                        ->orWhereHas('stocks', function ($q) use ($word) {
                            $q->where('sku', 'like', '%'.$word.'%');
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

        $products    = $products_query->limit(3)->get();
        $categories  = Category::where('name', 'like', '%' . $query . '%')->take(3)->get();
        $shops       = Shop::whereIn('user_id', verified_sellers_id())->where('name', 'like', '%' . $query . '%')->take(3)->get();

        if (count($keywords) > 0 || count($categories) > 0 || count($products) > 0 || count($shops) > 0) {
            return view('frontend.partials.search_content', compact('products', 'categories', 'keywords', 'shops'));
        }
        return '0';
    }

        // -------------------- Helper --------------------

    /**
     * Build attributes collection from products' choice_options JSON.
     * - Reads current scope (brand/category/keyword) BEFORE attribute/price filters
     * - Returns Attribute models with attribute_values filtered to only values present
     */
    private function buildAttributesFromChoiceOptions($baseProducts)
    {
        // pluck only the JSON column to keep it light
        $rows = (clone $baseProducts)->select('choice_options')->get()->pluck('choice_options')->filter();

        $valueMap = []; // [attribute_id => ['val1'=>true, 'val2'=>true, ...]]
        foreach ($rows as $json) {
            $arr = json_decode($json, true);
            if (!is_array($arr)) continue;

            foreach ($arr as $item) {
                // Some dbs may have 'attribute_at' by mistake; support both
                $aid = (int)($item['attribute_id'] ?? ($item['attribute_at'] ?? 0));
                if (!$aid) continue;

                $vals = (array)($item['values'] ?? []);
                foreach ($vals as $v) {
                    if ($v === null || $v === '') continue;
                    $valueMap[$aid][$v] = true;
                }
            }
        }

        if (empty($valueMap)) {
            return collect(); // nothing to show
        }

        $attrIds = array_keys($valueMap);

        // Load attributes with all their values, then shrink in-memory to only the present ones
        $attributes = Attribute::with('attribute_values')->whereIn('id', $attrIds)->get();

        foreach ($attributes as $attr) {
            $allowed = array_keys($valueMap[$attr->id] ?? []);
            $attr->setRelation(
                'attribute_values',
                $attr->attribute_values
                     ->filter(fn($v) => in_array($v->value, $allowed, true))
                     ->values()
            );
        }

        // Optional: sort attributes by name if you prefer
        // return $attributes->sortBy('name')->values();

        return $attributes;
    }

    private function buildColorsFromProducts($baseProducts)
    {
        $rows  = (clone $baseProducts)->select('colors')->get()->pluck('colors')->filter();
        $codes = [];
        foreach ($rows as $json) {
            $arr = json_decode($json, true);
            if (!is_array($arr)) continue;
            foreach ($arr as $code) {
                if (!is_string($code) || $code === '') continue;
                $codes[strtoupper(trim($code))] = true;   // normalize to uppercase
            }
        }
        $codes = array_keys($codes);
        if (empty($codes)) return collect();

        $tableColors = \App\Models\Color::whereIn('code', $codes)->get()->keyBy(function($c){
            return strtoupper($c->code);
        });

        $out = collect();
        foreach ($codes as $code) {
            $key = strtoupper($code);
            if (isset($tableColors[$key])) {
                $out->push($tableColors[$key]);
            } else {
                $o = new \stdClass();
                $o->id   = null;
                $o->name = $code;
                $o->code = $code;
                $out->push($o);
            }
        }
        return $out;
    }


}
