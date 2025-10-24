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
        $color       = $request->color;

        $selected_attribute_values = (array)$request->input('selected_attribute_values', []);
        $selected_color            = (array)$request->input('selected_color', []);

        // Selected categories coming from query string (multi-select)
        $catIdsRaw = (array)$request->input('category_ids', []);
        $selected_category_ids = array_values(array_unique(array_map('intval', $catIdsRaw)));

        $pageSize    = (int)($request->input('page_size', 24) ?: 24);

        // Base collections for filters
        $categories = collect();       // top-level + (optionally) more for the sidebar
        $attributes = Attribute::all();
        $colors     = Color::all();
        $category   = null;

        $products = Product::query();

        // ---------------- BRAND ----------------
        if ($brand_id) {
            $products->where('brand_id', $brand_id);
        } elseif ($request->filled('brand')) {
            $brand = Brand::where('slug', $request->brand)->first();
            if ($brand) {
                $products->where('brand_id', $brand->id);
            }
        }

        // Always load top-level categories when not inside a route category
        if (!$category_id) {
            $categories = Category::with('childrenCategories', 'coverImage')
                ->where('level', 0)
                ->orderBy('order_level', 'desc')
                ->get();
        }

        // ---------------- CATEGORY SCOPE ----------------
        if ($category_id) {
            // Route category: include its subtree
            $catTree   = CategoryUtility::children_ids($category_id);
            $catTree[] = $category_id;

            $category  = Category::with('childrenCategories')->findOrFail($category_id);
            $products->whereIn('category_id', $catTree);

            $attribute_ids = AttributeCategory::whereIn('category_id', $catTree)->pluck('attribute_id');
            if ($attribute_ids->count() > 0) {
                $attributes = Attribute::whereIn('id', $attribute_ids)->get();
            }
        } elseif (!empty($selected_category_ids)) {
            // Multi-select from query string
            $products->whereIn('category_id', $selected_category_ids);

            // Optional: scope attributes to selected categories
            $attribute_ids = AttributeCategory::whereIn('category_id', $selected_category_ids)->pluck('attribute_id');
            if ($attribute_ids->count() > 0) {
                $attributes = Attribute::whereIn('id', $attribute_ids)->get();
            }
        }

        // ---------------- KEYWORD SEARCH ----------------
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

        // ---------------- COLOR ----------------
        if ($color) {
            $products->where('colors', 'like', '%"'.$color.'"%');
        }

        // ---------------- ATTRIBUTES (OR) ----------------
        if (!empty($selected_attribute_values)) {
            $products->where(function ($q) use ($selected_attribute_values) {
                foreach ($selected_attribute_values as $value) {
                    $q->orWhere('choice_options', 'like', '%"'.$value.'"%');
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

        // ---------------- PRICE ----------------
        if ($min_price !== null && $max_price !== null && $min_price !== '' && $max_price !== '') {
            $products->whereBetween('unit_price', [(float)$min_price, (float)$max_price]);
        }

        // ---------------- SORT ----------------
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

        // ----- Preload ancestor chains & children so sub-categories from query are visible -----
        [$preloadedChildren, $expandedIds] = $this->preloadCategoryBranches($selected_category_ids);

        // Selected label (for breadcrumb/list-title fallback)
        $selected_category_name = null;
        if (!empty($selected_category_ids)) {
            $firstCat = Category::find($selected_category_ids[0]);
            $selected_category_name = $firstCat?->getTranslation('name');
        }

        $viewData = compact(
            'query','sort_by','min_price','max_price',
            'attributes','selected_attribute_values','colors','selected_color',
            'categories','category','category_id','brand_id',
            'globalMin','globalMax','scopedMin','scopedMax',
            'selected_category_ids','selected_category_name',
            'preloadedChildren','expandedIds'
        );

        $perPage     = $products->perPage();
        $totalPages  = $products->lastPage();
        $currentPage = $products->currentPage();
        $total       = $products->total();

        // compute ajax next page url for first render
        $ajaxNextPageUrl = (clone $products)->withPath(route('search.ajax.products'))->nextPageUrl();

        $viewData = array_merge($viewData, compact('perPage','totalPages','currentPage','total','ajaxNextPageUrl'));

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

        $products->withPath(route('search.ajax.products'));

        return response()->json([
            'html'          => $html,
            'next_page_url' => $products->nextPageUrl(),
            'total'         => $products->total(),
            'per_page'      => $products->perPage(),
            'total_pages'   => $products->lastPage(),
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

    // ---------------------- Helpers ----------------------

    /**
     * Build ancestor chain (root -> ... -> parent) for a given category id.
     */
    private function ancestorIds(int $id): array
    {
        $ids = [];
        $node = Category::select(['id','parent_id'])->find($id);
        // Walk up until root (parent_id == 0 or null)
        while ($node && $node->parent_id && (int)$node->parent_id !== 0) {
            $ids[] = (int)$node->parent_id;
            $node = Category::select(['id','parent_id'])->find($node->parent_id);
        }
        return array_reverse($ids);
    }

    /**
     * For a set of selected category IDs, preload ancestors & their children,
     * and also children of the selected nodes. Returns [preloadedChildren, expandedIds]
     */
    private function preloadCategoryBranches(array $selectedIds): array
    {
        $preloadedChildren = []; // parent_id => Collection<Category>
        $expandedIds       = []; // id => true (nodes to consider expanded/visible)

        if (empty($selectedIds)) {
            return [$preloadedChildren, $expandedIds];
        }

        foreach ($selectedIds as $sid) {
            if (!$sid) continue;

            // Expand ancestors
            $chain = $this->ancestorIds((int)$sid);
            foreach ($chain as $pid) {
                $expandedIds[$pid] = true;
                if (!isset($preloadedChildren[$pid])) {
                    $preloadedChildren[$pid] = Category::where('parent_id', $pid)
                        ->orderBy('order_level', 'desc')
                        ->get();
                }
            }

            // Expand the selected node itself
            $expandedIds[$sid] = true;
            if (!isset($preloadedChildren[$sid])) {
                $preloadedChildren[$sid] = Category::where('parent_id', $sid)
                    ->orderBy('order_level', 'desc')
                    ->get();
            }
        }

        return [$preloadedChildren, $expandedIds];
    }
}
