<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\BrandTranslation;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:view_all_brands'])->only('index');
        $this->middleware(['permission:add_brand'])->only('create');
        $this->middleware(['permission:edit_brand'])->only('edit');
        $this->middleware(['permission:delete_brand'])->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sort_search = null;
        $brands = Brand::query()
            ->with([
                'company.categories',
            ])
            ->orderBy('name', 'asc');

        if ($request->filled('search')) {
            $sort_search = trim((string) $request->search);
            $brands->where(function ($query) use ($sort_search) {
                $query->where('name', 'like', '%' . $sort_search . '%')
                    ->orWhereHas('company', function ($companyQuery) use ($sort_search) {
                        $companyQuery->where('code', 'like', '%' . $sort_search . '%')
                            ->orWhere('company_name', 'like', '%' . $sort_search . '%')
                            ->orWhere('company_type', 'like', '%' . $sort_search . '%')
                            ->orWhereHas('categories', function ($categoryQuery) use ($sort_search) {
                                $categoryQuery->where('categories.name', 'like', '%' . $sort_search . '%')
                                    ->orWhereHas('category_translations', function ($translationQuery) use ($sort_search) {
                                        $translationQuery->where('name', 'like', '%' . $sort_search . '%');
                                    });
                            });
                    });
            });
        }

        $brands = $brands->paginate(15);
        $companies = Company::orderBy('company_name')->get(['id', 'company_name']);
        $categories = Category::orderBy('name')->get(['id', 'name', 'parent_id']);

        return view('backend.product.brands.index', compact('brands', 'sort_search', 'companies', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        $brand = new Brand;
        $brand->company_id = $request->company_id;
        $brand->name = $request->name;
        $brand->meta_title = $request->meta_title;
        $brand->meta_description = $request->meta_description;
        if ($request->slug != null) {
            $brand->slug = str_replace(' ', '-', $request->slug);
        }
        else {
            $brand->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)).'-'.Str::random(5);
        }

        $brand->logo = $request->logo;
        $brand->save();

        $brand_translation = BrandTranslation::firstOrNew(['lang' => env('DEFAULT_LANGUAGE'), 'brand_id' => $brand->id]);
        $brand_translation->name = $request->name;
        $brand_translation->save();

        flash(translate('Brand has been inserted successfully'))->success();
        return redirect()->route('brands.index');

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
        $lang   = $request->lang;
        $brand  = Brand::findOrFail($id);
        $companies = Company::orderBy('company_name')->get(['id', 'company_name']);

        return view('backend.product.brands.edit', compact('brand', 'lang', 'companies'));
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
        $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        $brand = Brand::findOrFail($id);
        $brand->company_id = $request->company_id;
        if($request->lang == env("DEFAULT_LANGUAGE")){
            $brand->name = $request->name;
        }
        $brand->meta_title = $request->meta_title;
        $brand->meta_description = $request->meta_description;
        if ($request->slug != null) {
            $brand->slug = strtolower($request->slug);
        }
        else {
            $brand->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)).'-'.Str::random(5);
        }
        $brand->logo = $request->logo;
        $brand->save();

        $brand_translation = BrandTranslation::firstOrNew(['lang' => $request->lang, 'brand_id' => $brand->id]);
        $brand_translation->name = $request->name;
        $brand_translation->save();

        flash(translate('Brand has been updated successfully'))->success();
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
        $brand = Brand::findOrFail($id);
        $brand->brand_translations()->delete();
        Product::where('brand_id', $brand->id)->update(['brand_id' => null]);
        Brand::destroy($id);

        flash(translate('Brand has been deleted successfully'))->success();
        return redirect()->route('brands.index');

    }
}
