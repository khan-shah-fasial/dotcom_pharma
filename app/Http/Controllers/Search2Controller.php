<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Product;
use App\Models\Category;
use App\Models\Group;
use App\Models\Attribute;
use Illuminate\Http\Request;
use App\Utility\CategoryUtility;
use App\Models\AttributeCategory;
use Illuminate\Support\Facades\DB;
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
        $drug_name   = trim((string)$request->input('drug_name', ''));
        $sort_by     = $request->sort_by;
        $min_price   = $request->min_price;
        $max_price   = $request->max_price;

        $selected_attribute_values = (array)$request->input('selected_attribute_values', []);
        $selected_color            = null;

        $filter_brand_id       = $request->filled('filter_brand_id') ? (int)$request->input('filter_brand_id') : null;
        $filter_role_label     = trim((string)$request->input('filter_role_label', ''));
        $filter_product_origin = trim((string)$request->input('filter_product_origin', ''));

        // Single selected category id (from radio OR from array OR from route)
        $selected_category_id = null;
        // Single selected group id (from radio OR array)
        $selected_group_id = null;

        if ($request->filled('category_id')) {
            $selected_category_id = (int)$request->input('category_id');
        }

        if ($selected_category_id === null) {
            $catIdsRaw = (array)$request->input('category_ids', []);
            if (!empty($catIdsRaw)) {
                $selected_category_id = (int)array_values($catIdsRaw)[0];
            }
        }

        if ($selected_category_id === null && $category_id) {
            $selected_category_id = (int)$category_id;
        }

        if ($request->filled('group_id')) {
            $selected_group_id = (int)$request->input('group_id');
        }
        if ($selected_group_id === null) {
            $groupIdsRaw = (array)$request->input('group_ids', []);
            if (!empty($groupIdsRaw)) {
                $selected_group_id = (int)array_values($groupIdsRaw)[0];
            }
        }

        $pageSize    = (int)($request->input('page_size', 24) ?: 24);

        $category   = null;
        $products   = Product::query();

        // -------- BRAND --------
        if ($brand_id) {
            $products->where('brand_id', $brand_id);
            // keep the filter dropdown in-sync with route
            $filter_brand_id = (int) $brand_id;
        } elseif ($request->filled('brand')) {
            $brand = Brand::where('slug', $request->brand)->first();
            if ($brand) $products->where('brand_id', $brand->id);
        }

        // -------- CATEGORY SCOPE --------
        if ($category_id) {
            $catTree   = CategoryUtility::children_ids($category_id);
            $catTree[] = $category_id;
            $category  = Category::with('childrenCategories')->findOrFail($category_id);
            // apply to products using pivot + fallback to products.category_id
            $this->applyCategoryScope($products, $catTree);

            // $products->whereIn('category_id', $catTree);
        } elseif ($selected_category_id) {
            // $products->where('category_id', $selected_category_id);
            $this->applyCategoryScope($products, [$selected_category_id]);
        }

        // -------- GROUP SCOPE --------
        if ($selected_group_id) {
            $this->applyGroupScope($products, [$selected_group_id]);
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

        // -------- DRUG NAME (left-side filter: searches drug_name + product name) --------
        if ($drug_name !== '') {
            $products->where(function ($q) use ($drug_name) {
                foreach (preg_split('/\s+/', $drug_name) as $word) {
                    $word = trim((string)$word);
                    if ($word === '') {
                        continue;
                    }
                    $q->where(function ($qq) use ($word) {
                        $qq->where('drug_name', 'like', '%'.$word.'%')
                            ->orWhere('name', 'like', '%'.$word.'%')
                            ->orWhereHas('product_translations', fn($qt) => $qt->where('name', 'like', '%'.$word.'%'));
                    });
                }
            });
        }

        // Did we scope by category (route or radio)?
        $hasCategoryScope = !is_null($category_id) || !is_null($selected_category_id);

        // ============================================================
        // 2) BASE QUERY FOR COUNTS (this one should be LESS SCOPED)
        //    -> same brand, same keyword
        //    -> BUT **NO** category filter
        // ============================================================
        $countsBase = Product::query();

        // apply brand to counts also
        if ($brand_id) {
            $countsBase->where('brand_id', $brand_id);
        } elseif ($request->filled('brand')) {
            $brand = Brand::where('slug', $request->brand)->first();
            if ($brand) {
                $countsBase->where('brand_id', $brand->id);
            }
        }

        // apply keyword to counts also
        if ($query !== '') {
            $countsBase->where(function ($q) use ($query) {
                foreach (explode(' ', $query) as $word) {
                    $q->where(function ($qq) use ($word) {
                        $qq->where('name', 'like', '%'.$word.'%')
                        ->orWhere('tags', 'like', '%'.$word.'%')
                        ->orWhereHas('product_translations', fn($qt) => $qt->where('name', 'like', '%'.$word.'%'))
                        ->orWhereHas('stocks', fn($qs) => $qs->where('sku', 'like', '%'.$word.'%'));
                    });
                }
            });
        }

        // final counts source
        $countsSource = filter_products(clone $countsBase);

        // ====== CATEGORY COUNTS (for tree) ======
        // earlier: ->groupBy('category_id') on products
        // now: get counts from pivot product_categories BUT limited to products in $countsSource
        $categoryCounts = DB::table('product_categories as pc')
            ->joinSub(
                $countsSource->select('id'),
                'src',
                'src.id',
                '=',
                'pc.product_id'
            )
            ->select('pc.category_id', DB::raw('COUNT(DISTINCT pc.product_id) as aggregate'))
            ->groupBy('pc.category_id')
            ->pluck('aggregate', 'pc.category_id')
            ->toArray();

        // also merge old direct products.category_id counts (for backward data)
        $directCategoryCounts = (clone $countsSource)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id')
            ->toArray();

        $categoryCounts = array_replace($directCategoryCounts, $categoryCounts);

        // ====== GROUP COUNTS (for tree) ======
        $groupCountsPivot = DB::table('product_groups as pg')
            ->joinSub(
                $countsSource->select('id'),
                'src',
                'src.id',
                '=',
                'pg.product_id'
            )
            ->select('pg.group_id', DB::raw('COUNT(DISTINCT pg.product_id) as aggregate'))
            ->groupBy('pg.group_id')
            ->pluck('aggregate', 'pg.group_id')
            ->toArray();

        $directGroupCounts = (clone $countsSource)
            ->whereNotNull('group_id')
            ->selectRaw('group_id, COUNT(*) as aggregate')
            ->groupBy('group_id')
            ->pluck('aggregate', 'group_id')
            ->toArray();

        $groupCounts = array_replace($directGroupCounts, $groupCountsPivot);

        // // ====== CATEGORY COUNTS (for tree) ======
        // $categoryCounts = (clone $countsSource)
        //     ->selectRaw('category_id, COUNT(*) as aggregate')
        //     ->groupBy('category_id')
        //     ->pluck('aggregate', 'category_id')
        //     ->toArray();
            
        /**
         * ------- PANEL SOURCE SCOPE (for counts) -------
         * This is the pool BEFORE attribute/color/price filters.
         */
        $scopedForPanel = filter_products(clone $products);

        // ====== ATTRIBUTES (+ COUNTS) ======
        [$attributes, $attributeValueCounts] = $this->buildAttributesFromChoiceOptions(clone $products);

        // ====== COLORS (+ COUNTS) ======
        if ($hasCategoryScope) {
            // build colors from the current product pool (products table HAS "colors" column)
            [$colors, $colorCounts] = $this->buildColorsFromProducts(clone $products);
        } else {
            // no category/brand/keyword scope → just show master colors, no counts
            $colors      = Color::orderBy('name')->get();
            $colorCounts = [];
        }

        // -------- ATTRIBUTES FILTER APPLIED --------
        if (!empty($selected_attribute_values)) {
            $products->where(function ($q) use ($selected_attribute_values) {
                foreach ($selected_attribute_values as $value) {
                    $q->orWhere('choice_options', 'like', '%"'.$value.'"%');
                }
            });
        }

        // -------- COLOR FILTER APPLIED --------
        $color = $request->filled('color') ? urldecode((string) $request->color) : null;
        if ($request->has('color')) {
            $str = '"' . $request->color . '"';
            $products->where('colors', 'like', '%' . $str . '%');
            $selected_color = $request->color;
        }

        // ----- SCOPED BOUNDS (pre-price) -----
        $bounds = (clone $scopedForPanel)
            ->selectRaw('MIN(unit_price) AS min_price, MAX(unit_price) AS max_price')
            ->first();
        $scopedMin = (int)($bounds->min_price ?? 0);
        $scopedMax = (int)($bounds->max_price ?? 0);

        // -------- PRICE --------
        if ($min_price !== null && $max_price !== null && $min_price !== '' && $max_price !== '') {
            $products->whereBetween('unit_price', [(float)$min_price, (float)$max_price]);
        }

        // ============================================================
        //  EXTRA FILTER FACETS (brand/role/origin lists)
        //  Build them from the current pool, excluding their own filter
        // ============================================================
        $facetCommon = filter_products(clone $products);

        // Brands (apply role/origin selections, exclude brand itself)
        $brandFacet = clone $facetCommon;
        if ($filter_role_label !== '') {
            $brandFacet->where('role_label', $filter_role_label);
        }
        if ($filter_product_origin !== '') {
            $brandFacet->where('product_origin', $filter_product_origin);
        }
        $brandIds = $brandFacet
            ->whereNotNull('brand_id')
            ->select('brand_id')
            ->distinct()
            ->pluck('brand_id')
            ->filter()
            ->values();
        $filter_brands = $brandIds->isEmpty()
            ? collect()
            : Brand::whereIn('id', $brandIds)->orderBy('name')->get();

        // Roles (apply brand/origin selections, exclude role itself)
        $roleFacet = clone $facetCommon;
        if (!empty($filter_brand_id)) {
            $roleFacet->where('brand_id', $filter_brand_id);
        }
        if ($filter_product_origin !== '') {
            $roleFacet->where('product_origin', $filter_product_origin);
        }
        $filter_roles = $roleFacet
            ->whereNotNull('role_label')
            ->where('role_label', '!=', '')
            ->select('role_label')
            ->distinct()
            ->orderBy('role_label')
            ->limit(200)
            ->pluck('role_label')
            ->toArray();

        // Origins (apply brand/role selections, exclude origin itself)
        $originFacet = clone $facetCommon;
        if (!empty($filter_brand_id)) {
            $originFacet->where('brand_id', $filter_brand_id);
        }
        if ($filter_role_label !== '') {
            $originFacet->where('role_label', $filter_role_label);
        }
        $filter_origins = $originFacet
            ->whereNotNull('product_origin')
            ->where('product_origin', '!=', '')
            ->select('product_origin')
            ->distinct()
            ->orderBy('product_origin')
            ->limit(200)
            ->pluck('product_origin')
            ->toArray();

        // -------- EXTRA FILTERS APPLIED (brand/role/origin) --------
        if (!empty($filter_brand_id)) {
            $products->where('brand_id', $filter_brand_id);
        }
        if ($filter_role_label !== '') {
            $products->where('role_label', $filter_role_label);
        }
        if ($filter_product_origin !== '') {
            $products->where('product_origin', $filter_product_origin);
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

        // -------- Build FULL group tree (for sidebar) --------
        $allGroups = Group::select('id','parent_id','name','level','order_level')
            ->orderBy('order_level','desc')->get();
        $preloadedGroupChildren = $allGroups->groupBy('parent_id');
        $groupRoots = $preloadedGroupChildren->get(0, collect())->merge($preloadedGroupChildren->get(null, collect()));
        $groupsTree = $groupRoots;

        // -------- Figure parent chain to auto-open (groups) --------
        $groupExpandedIds = [];
        if ($selected_group_id) {
            $byId = $allGroups->keyBy('id');
            $curr = $selected_group_id;
            while ($curr && isset($byId[$curr])) {
                $groupExpandedIds[] = $curr;
                $curr = $byId[$curr]->parent_id;
            }
        }

        // -------- Figure parent chain to auto-open --------
        $expandedIds = [];
        if ($selected_category_id) {
            $byId = $allCats->keyBy('id');
            $curr = $selected_category_id;
            while ($curr && isset($byId[$curr])) {
                $expandedIds[] = $curr;
                $curr = $byId[$curr]->parent_id;
            }
        }

        // For breadcrumbs/title
        $selected_category_name = null;
        if ($selected_category_id) {
            $selected_category_name = optional(Category::find($selected_category_id))->getTranslation('name');
        } elseif ($category_id) {
            $selected_category_name = optional(Category::find($category_id))->getTranslation('name');
        }

        // For group breadcrumb/title (optional)
        $selected_group_name = null;
        if ($selected_group_id) {
            $selected_group_name = optional(Group::find($selected_group_id))->getTranslation('name');
        }

        // Bounds for slider
        $globalMin = (int) get_product_min_unit_price();
        $globalMax = (int) get_product_max_unit_price();

        $viewData = compact(
            'query','drug_name','sort_by','min_price','max_price',
            'attributes','selected_attribute_values','colors','selected_color',
            'categories','category','category_id','brand_id',
            'globalMin','globalMax','scopedMin','scopedMax',
            'selected_category_id','selected_category_name',
            'preloadedChildren','color',
            'categoryCounts','attributeValueCounts','colorCounts','expandedIds',
            'groupsTree','preloadedGroupChildren','selected_group_id','groupCounts','selected_group_name','groupExpandedIds'
        );

        $viewData = array_merge($viewData, [
            'filter_brand_id'       => $filter_brand_id,
            'filter_role_label'     => $filter_role_label,
            'filter_product_origin' => $filter_product_origin,
            'filter_brands'         => $filter_brands,
            'filter_roles'          => $filter_roles,
            'filter_origins'        => $filter_origins,
        ]);

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
                'attributeValueCounts'      => $viewData['attributeValueCounts'] ?? [],
            ]
        )->render();

        $colorsHtml = '';
        if (($viewData['colors'] ?? collect())->count() > 0) {
            $colorsHtml = View::make(
                'frontend.'.get_setting('homepage_select').'.partials.filters.color_filter',
                [
                    'colors'         => $viewData['colors'],
                    'selected_color' => $viewData['color'] ?? null,
                    'colorCounts'    => $viewData['colorCounts'] ?? [],
                ]
            )->render();
        }

        $extraFiltersHtml = View::make(
            'frontend.'.get_setting('homepage_select').'.partials.filters.additional_filters',
            [
                'drug_name'             => $viewData['drug_name'] ?? '',
                'filter_brand_id'       => $viewData['filter_brand_id'] ?? null,
                'filter_role_label'     => $viewData['filter_role_label'] ?? '',
                'filter_product_origin' => $viewData['filter_product_origin'] ?? '',
                'brands'                => $viewData['filter_brands'] ?? collect(),
                'roles'                 => $viewData['filter_roles'] ?? [],
                'origins'               => $viewData['filter_origins'] ?? [],
            ]
        )->render();

        $extraFiltersMobileHtml = View::make(
            'frontend.'.get_setting('homepage_select').'.partials.filters.additional_filters',
            [
                'is_mobile'             => true,
                'drug_name'             => $viewData['drug_name'] ?? '',
                'filter_brand_id'       => $viewData['filter_brand_id'] ?? null,
                'filter_role_label'     => $viewData['filter_role_label'] ?? '',
                'filter_product_origin' => $viewData['filter_product_origin'] ?? '',
                'brands'                => $viewData['filter_brands'] ?? collect(),
                'roles'                 => $viewData['filter_roles'] ?? [],
                'origins'               => $viewData['filter_origins'] ?? [],
            ]
        )->render();

        $products->withPath(route('search.ajax.products'));

        return response()->json([
            'attributes_html'   => $attributesHtml,
            'colors_html'       => $colorsHtml,
            'extra_filters_html'        => $extraFiltersHtml,
            'extra_filters_mobile_html' => $extraFiltersMobileHtml,
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
        $query = trim((string) $request->search);
        if ($query === '') {
            return '0';
        }

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
                        ->orWhere('drug_name', 'like', '%'.$word.'%')
                        ->orWhere('role_label', 'like', '%'.$word.'%')
                        ->orWhere('tags', 'like', '%'.$word.'%')
                        ->orWhereHas('product_translations', function ($q) use ($word) {
                            $q->where('name', 'like', '%'.$word.'%');
                        })
                        ->orWhereHas('brand', function ($q) use ($word) {
                            $q->where('name', 'like', '%'.$word.'%')
                                ->orWhereHas('brand_translations', function ($qt) use ($word) {
                                    $qt->where('name', 'like', '%'.$word.'%');
                                });
                        })
                        ->orWhereHas('categories', function ($q) use ($word) {
                            $q->where('name', 'like', '%'.$word.'%')
                                ->orWhereHas('category_translations', function ($qt) use ($word) {
                                    $qt->where('name', 'like', '%'.$word.'%');
                                });
                        })
                        ->orWhereHas('main_category', function ($q) use ($word) {
                            $q->where('name', 'like', '%'.$word.'%')
                                ->orWhereHas('category_translations', function ($qt) use ($word) {
                                    $qt->where('name', 'like', '%'.$word.'%');
                                });
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

        $products    = $products_query->with('brand')->get();
        $categories  = Category::where(function ($q) use ($query) {
            $q->where('name', 'like', '%' . $query . '%')
                ->orWhereHas('category_translations', function ($qt) use ($query) {
                    $qt->where('name', 'like', '%' . $query . '%');
                });
        })->get();

        $brands  = Brand::where(function ($q) use ($query) {
            $q->where('name', 'like', '%' . $query . '%')
                ->orWhereHas('brand_translations', function ($qt) use ($query) {
                    $qt->where('name', 'like', '%' . $query . '%');
                });
        })->get();

        $shops       = Shop::whereIn('user_id', verified_sellers_id())
            ->where('name', 'like', '%' . $query . '%')
            ->get();

        if (count($keywords) > 0 || count($categories) > 0 || count($brands) > 0 || count($products) > 0 || count($shops) > 0) {
            return view('frontend.partials.search_content', compact('products', 'categories', 'brands', 'keywords', 'shops'));
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
        // get only choice_options from current product pool
        $rows = (clone $baseProducts)->select('choice_options')->get()->pluck('choice_options')->filter();

        $valueMap         = []; // [attribute_id => ['val1'=>true, ...]]  → for showing only used values
        $valueCountNested = []; // [attribute_id => [ 'Value' => count, ... ]]

        foreach ($rows as $json) {
            $arr = json_decode($json, true);
            if (!is_array($arr)) {
                continue;
            }

            foreach ($arr as $item) {
                // handle both attribute_id / attribute_at
                $aid = (int)($item['attribute_id'] ?? ($item['attribute_at'] ?? 0));
                if (!$aid) {
                    continue;
                }

                $vals = (array)($item['values'] ?? []);
                foreach ($vals as $v) {
                    if ($v === null || $v === '') {
                        continue;
                    }

                    // for filtering the panel
                    $valueMap[$aid][$v] = true;

                    // for counts
                    if (!isset($valueCountNested[$aid])) {
                        $valueCountNested[$aid] = [];
                    }
                    if (!isset($valueCountNested[$aid][$v])) {
                        $valueCountNested[$aid][$v] = 0;
                    }
                    $valueCountNested[$aid][$v] += 1;
                }
            }
        }

        // if nothing → return empty
        if (empty($valueMap)) {
            return [collect(), []];
        }

        $attrIds    = array_keys($valueMap);
        $attributes = Attribute::with('attribute_values')->whereIn('id', $attrIds)->get();

        // trim values to only ones actually present
        foreach ($attributes as $attr) {
            $allowed = array_keys($valueMap[$attr->id] ?? []);
            $attr->setRelation(
                'attribute_values',
                $attr->attribute_values
                    ->filter(fn($v) => in_array($v->value, $allowed, true))
                    ->values()
            );
        }

        // IMPORTANT: now we return the **nested** structure
        return [$attributes, $valueCountNested];
    }

    
    /**
     * Apply category filter using BOTH:
     * - product_categories pivot
     * - products.category_id (fallback)
     */
    private function applyCategoryScope($builder, array $categoryIds): void
    {
        $builder->where(function ($q) use ($categoryIds) {
            // from pivot
            $q->whereIn('id', function ($sub) use ($categoryIds) {
                $sub->from('product_categories')
                    ->selectRaw('DISTINCT product_id')
                    ->whereIn('category_id', $categoryIds);
            })
            // plus legacy column
            ->orWhereIn('category_id', $categoryIds);
        });
    }

    /**
     * Apply group filter using BOTH:
     * - product_groups pivot
     * - products.group_id (fallback)
     */
    private function applyGroupScope($builder, array $groupIds): void
    {
        $builder->where(function ($q) use ($groupIds) {
            $q->whereIn('id', function ($sub) use ($groupIds) {
                $sub->from('product_groups')
                    ->selectRaw('DISTINCT product_id')
                    ->whereIn('group_id', $groupIds);
            })
            ->orWhereIn('group_id', $groupIds);
        });
    }


    private function buildColorsFromProducts($baseProducts)
    {
        $rows  = (clone $baseProducts)->select('colors')->get()->pluck('colors')->filter();
        $codes = [];
        $codeCounts = [];   // "COLORCODE" => count

        foreach ($rows as $json) {
            $arr = json_decode($json, true);
            if (!is_array($arr)) continue;

            foreach ($arr as $code) {
                if (!is_string($code) || $code === '') continue;
                $norm = strtoupper(trim($code));
                $codes[$norm] = true;

                if (!isset($codeCounts[$norm])) $codeCounts[$norm] = 0;
                $codeCounts[$norm] += 1;
            }
        }

        $codes = array_keys($codes);
        if (empty($codes)) return [collect(), []];

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
        return [$out, $codeCounts];
    }



}
