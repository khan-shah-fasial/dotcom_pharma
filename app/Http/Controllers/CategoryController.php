<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Models\CategoryTranslation;
use App\Models\User;
use App\Utility\CategoryUtility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Cache;

class CategoryController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:view_product_categories'])->only('index');
        $this->middleware(['permission:add_product_category'])->only('create');
        $this->middleware(['permission:edit_product_category'])->only('edit');
        $this->middleware(['permission:delete_product_category'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sort_search =null;
        $categories = Category::orderBy('order_level', 'desc');
        if ($request->has('search')){
            $sort_search = $request->search;
            $categories = $categories->where('name', 'like', '%'.$sort_search.'%');
        }
        $categories = $categories->paginate(15);
        return view('backend.product.categories.index', compact('categories', 'sort_search'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::where('parent_id', 0)
            ->where('digital', 0)
            ->with('childrenCategories')
            ->get();

        return view('backend.product.categories.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $category = new Category;
        $category->name = $request->name;
        $category->order_level = 0;
        if($request->order_level != null) {
            $category->order_level = $request->order_level;
        }
        $category->digital = $request->digital;
        $category->banner = $request->banner;
        $category->icon = $request->icon;
        $category->cover_image = $request->cover_image;
        $category->meta_title = $request->meta_title;
        $category->meta_description = $request->meta_description;

        if ($request->parent_id != "0") {
            $category->parent_id = $request->parent_id;

            $parent = Category::find($request->parent_id);
            $category->level = $parent->level + 1 ;
        }

        if ($request->slug != null) {
            $category->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug));
        }
        else {
            $category->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)).'-'.Str::random(5);
        }
        if ($request->commision_rate != null) {
            $category->commision_rate = $request->commision_rate;
        }

        $category->save();

        $category->attributes()->sync($request->filtering_attributes);

        $category_translation = CategoryTranslation::firstOrNew(['lang' => env('DEFAULT_LANGUAGE'), 'category_id' => $category->id]);
        $category_translation->name = $request->name;
        $category_translation->save();

        flash(translate('Category has been inserted successfully'))->success();
        return redirect()->route('categories.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $lang = $request->lang;
        $category = Category::findOrFail($id);
        $categories = Category::where('parent_id', 0)
            ->where('digital', $category->digital)
            // ->with('childrenCategories')
            // ->whereNotIn('id', CategoryUtility::children_ids($category->id, true))->where('id', '!=' , $category->id)
            ->with(['childrenCategories' => function ($query) use ($category) {
                $query->whereNotIn('id', CategoryUtility::children_ids($category->id, true))
                      ->where('id', '!=' , $category->id);
            }])
            ->orderBy('name','asc')
            ->get();

        return view('backend.product.categories.edit', compact('category', 'categories', 'lang'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        if($request->lang == env("DEFAULT_LANGUAGE")){
            $category->name = $request->name;
        }
        if($request->order_level != null) {
            $category->order_level = $request->order_level;
        }
        $category->digital = $request->digital;
        $category->banner = $request->banner;
        $category->icon = $request->icon;
        $category->cover_image = $request->cover_image;
        $category->meta_title = $request->meta_title;
        $category->meta_description = $request->meta_description;

        $previous_level = $category->level;

        if ($request->parent_id != "0") {
            $category->parent_id = $request->parent_id;

            $parent = Category::find($request->parent_id);
            $category->level = $parent->level + 1 ;
        }
        else{
            $category->parent_id = 0;
            $category->level = 0;
        }

        // if($category->level > $previous_level){
        //     CategoryUtility::move_level_down($category->id);
        // }
        // elseif ($category->level < $previous_level) {
        //     CategoryUtility::move_level_up($category->id);
        // }

        if ($request->slug != null) {
            $category->slug = strtolower($request->slug);
        }
        else {
            $category->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)).'-'.Str::random(5);
        }


        if ($request->commision_rate != null) {
            $category->commision_rate = $request->commision_rate;
        }

        $category->save();

        //Updating childer categories level
        CategoryUtility::update_child_level($category->id);

        $category->attributes()->sync($request->filtering_attributes);

        $category_translation = CategoryTranslation::firstOrNew(['lang' => $request->lang, 'category_id' => $category->id]);
        $category_translation->name = $request->name;
        $category_translation->save();

        Cache::forget('featured_categories');
        flash(translate('Category has been updated successfully'))->success();
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->attributes()->detach();

        // Category Translations Delete
        foreach ($category->category_translations as $key => $category_translation) {
            $category_translation->delete();
        }

        foreach (Product::where('category_id', $category->id)->get() as $product) {
            $product->category_id = null;
            $product->save();
        }

        CategoryUtility::delete_category($id);
        Cache::forget('featured_categories');

        flash(translate('Category has been deleted successfully'))->success();
        return redirect()->route('categories.index');
    }

    public function updateFeatured(Request $request)
    {
        $category = Category::findOrFail($request->id);
        $category->featured = $request->status;
        $category->save();
        Cache::forget('featured_categories');
        return 1;
    }

    public function categoriesByType(Request $request)
    {
        $categories = Category::where('parent_id', 0)
            ->where('digital', $request->digital)
            ->with('childrenCategories')
            ->get();

        return view('backend.product.categories.categories_option', compact('categories'));
    }

    public function categoriesWiseProductDiscount(Request $request){
        $filters = [
            'name' => trim((string) $request->input('name', $request->input('search', ''))),
            'parent_id' => (string) $request->input('parent_id', ''),
            'discount_from' => $request->input('discount_from'),
            'discount_to' => $request->input('discount_to'),
            'date_from' => trim((string) $request->input('date_from')),
            'date_to' => trim((string) $request->input('date_to')),
        ];

        $sortBy = in_array($request->input('sort_by'), ['name', 'parent', 'discount', 'discount_start'], true)
            ? $request->input('sort_by')
            : 'name';
        $sortDir = strtolower((string) $request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $discountSummary = DB::table('products')
            ->selectRaw('category_id, MAX(discount) as current_discount, MAX(discount_start_date) as current_discount_start, MAX(discount_end_date) as current_discount_end')
            ->where('auction_product', 0)
            ->groupBy('category_id');

        $categories = Category::query()
            ->with('parentCategory')
            ->leftJoinSub($discountSummary, 'category_discounts', function ($join) {
                $join->on('category_discounts.category_id', '=', 'categories.id');
            })
            ->leftJoin('categories as parent_categories', 'parent_categories.id', '=', 'categories.parent_id')
            ->select([
                'categories.*',
                'category_discounts.current_discount',
                'category_discounts.current_discount_start',
                'category_discounts.current_discount_end',
            ]);

        if ($filters['name'] !== '') {
            $like = '%' . $filters['name'] . '%';
            $categories->where(function ($query) use ($like) {
                $query->where('categories.name', 'like', $like)
                    ->orWhereHas('category_translations', function ($translation) use ($like) {
                        $translation->where('name', 'like', $like);
                    });
            });
        }

        if ($filters['parent_id'] === '0') {
            $categories->where(function ($query) {
                $query->whereNull('categories.parent_id')->orWhere('categories.parent_id', 0);
            });
        } elseif ($filters['parent_id'] !== '' && ctype_digit($filters['parent_id'])) {
            $categories->where('categories.parent_id', (int) $filters['parent_id']);
        }

        $discountFrom = is_numeric($filters['discount_from']) ? (float) $filters['discount_from'] : null;
        $discountTo = is_numeric($filters['discount_to']) ? (float) $filters['discount_to'] : null;
        if (! ($discountFrom !== null && $discountTo !== null && $discountFrom > $discountTo)) {
            if ($discountFrom !== null) {
                $categories->where('category_discounts.current_discount', '>=', $discountFrom);
            }
            if ($discountTo !== null) {
                $categories->where('category_discounts.current_discount', '<=', $discountTo);
            }
        }

        $dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_from']) ? strtotime($filters['date_from'] . ' 00:00:00') : null;
        $dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_to']) ? strtotime($filters['date_to'] . ' 23:59:59') : null;
        if (! ($dateFrom && $dateTo && $dateFrom > $dateTo)) {
            if ($dateFrom || $dateTo) {
                $categories->whereExists(function ($query) use ($dateFrom, $dateTo) {
                    $query->select(DB::raw(1))
                        ->from('products')
                        ->whereColumn('products.category_id', 'categories.id')
                        ->where('products.auction_product', 0)
                        ->where(function ($window) {
                            $window->whereNotNull('products.discount_start_date')
                                ->orWhereNotNull('products.discount_end_date');
                        });
                    if ($dateFrom) {
                        $query->where(function ($window) use ($dateFrom) {
                            $window->whereNull('products.discount_end_date')
                                ->orWhere('products.discount_end_date', '>=', $dateFrom);
                        });
                    }
                    if ($dateTo) {
                        $query->where(function ($window) use ($dateTo) {
                            $window->whereNull('products.discount_start_date')
                                ->orWhere('products.discount_start_date', '<=', $dateTo);
                        });
                    }
                });
            }
        }

        if ($sortBy === 'parent') {
            $categories->orderByRaw('parent_categories.name IS NULL')
                ->orderBy('parent_categories.name', $sortDir);
        } elseif ($sortBy === 'discount') {
            $categories->orderByRaw('category_discounts.current_discount IS NULL')
                ->orderBy('category_discounts.current_discount', $sortDir);
        } elseif ($sortBy === 'discount_start') {
            $categories->orderByRaw('category_discounts.current_discount_start IS NULL')
                ->orderBy('category_discounts.current_discount_start', $sortDir);
        } else {
            $categories->orderBy('categories.name', $sortDir);
        }
        $categories->orderBy('categories.id', 'desc');

        $categories = $categories->paginate(15)->withQueryString();
        $parentCategories = Category::query()
            ->where(function ($query) {
                $query->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->orderBy('name')
            ->get();

        return view('backend.product.category_wise_discount.set_discount', compact(
            'categories',
            'filters',
            'sortBy',
            'sortDir',
            'parentCategories'
        ));
    }
}
