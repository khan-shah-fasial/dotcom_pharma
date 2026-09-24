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
        $this->middleware(['permission:add_brand'])->only('create', 'store');
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
        $allowedSorts = ['name', 'company_name', 'company_type', 'deals_in'];
        $sortBy = in_array((string) $request->get('sort_by'), $allowedSorts, true) ? (string) $request->get('sort_by') : 'name';
        $sortDir = strtolower((string) $request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $filters = [
            'brand_name' => trim((string) $request->get('brand_name', '')),
            'company_name' => trim((string) $request->get('company_name', '')),
            'company_type' => trim((string) $request->get('company_type', '')),
            'deals_in' => trim((string) $request->get('deals_in', '')),
        ];
        $sort_search = $request->filled('search') ? trim((string) $request->search) : null;

        $brands = Brand::query()->with(['company.categories']);

        if ($sort_search) {
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
        if ($filters['brand_name'] !== '') {
            $brandName = $filters['brand_name'];
            $brands->where(function ($query) use ($brandName) {
                $query->where('brands.name', 'like', '%' . $brandName . '%')
                    ->orWhereHas('brand_translations', function ($translation) use ($brandName) {
                        $translation->where('name', 'like', '%' . $brandName . '%');
                    });
            });
        }
        if ($filters['company_name'] !== '') {
            $companyName = $filters['company_name'];
            $brands->whereHas('company', function ($query) use ($companyName) {
                $query->where('company_name', 'like', '%' . $companyName . '%');
            });
        }
        if ($filters['company_type'] !== '') {
            $companyType = $filters['company_type'];
            $brands->whereHas('company', function ($query) use ($companyType) {
                $query->where('company_type', 'like', '%' . $companyType . '%');
            });
        }
        if ($filters['deals_in'] !== '') {
            $dealsIn = $filters['deals_in'];
            $brands->whereHas('company.categories', function ($query) use ($dealsIn) {
                $query->where('categories.name', 'like', '%' . $dealsIn . '%')
                    ->orWhereHas('category_translations', function ($translation) use ($dealsIn) {
                        $translation->where('name', 'like', '%' . $dealsIn . '%');
                    });
            });
        }

        if ($sortBy === 'company_name') {
            $brands->orderByRaw('(select company_name from companies where companies.id = brands.company_id limit 1) ' . $sortDir);
        } elseif ($sortBy === 'company_type') {
            $brands->orderByRaw('(select company_type from companies where companies.id = brands.company_id limit 1) ' . $sortDir);
        } elseif ($sortBy === 'deals_in') {
            $brands->orderByRaw('(select min(categories.name) from categories inner join company_category on company_category.category_id = categories.id where company_category.company_id = brands.company_id) ' . $sortDir);
        } else {
            $brands->orderBy('brands.name', $sortDir);
        }
        $brands->orderBy('brands.id');

        $brands = $brands->paginate(15)->appends($request->query());
        $categories = Category::orderBy('name')->get(['id', 'name', 'parent_id']);

        return view('backend.product.brands.index', compact('brands', 'sort_search', 'categories', 'filters', 'sortBy', 'sortDir'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $companies = Company::orderBy('company_name')->get(['id', 'company_name']);

        return view('backend.product.brands.create', compact('companies'));
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
