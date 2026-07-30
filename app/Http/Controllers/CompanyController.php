<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Models\Category;
use App\Models\Company;
use App\Models\UserDetails;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_all_companies'])->only(['index', 'show']);
        $this->middleware(['permission:view_all_customers'])->only(['edit', 'update']);
        $this->middleware(['permission:add_customer'])->only(['create', 'store']);
        $this->middleware(['permission:delete_customer'])->only(['destroy']);
    }

    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->input('search')),
            'company_type' => (string) $request->input('company_type'),
            'category_id' => (string) $request->input('category_id'),
            'date_from' => (string) $request->input('date_from'),
            'date_to' => (string) $request->input('date_to'),
        ];

        $sortBy = (string) $request->input('sort_by', 'created_at');
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $sortableColumns = [
            'id' => 'companies.id',
            'code' => 'companies.code',
            'company_name' => 'companies.company_name',
            'full_address' => 'companies.full_address',
            'contact_person' => 'companies.contact_person',
            'designation' => 'companies.designation',
            'mobile' => 'companies.mobile',
            'whatsapp' => 'companies.whatsapp',
            'email' => 'companies.email',
            'company_type' => 'companies.company_type',
            'created_at' => 'companies.created_at',
        ];

        if (!array_key_exists($sortBy, $sortableColumns) && $sortBy !== 'deal_in_category') {
            $sortBy = 'created_at';
        }

        $companies = Company::query()->with(['categories', 'creator']);

        $this->applyFilters($companies, $filters);

        if ($sortBy === 'deal_in_category') {
            $firstCategoryName = DB::table('company_category')
                ->join('categories', 'categories.id', '=', 'company_category.category_id')
                ->select('categories.name')
                ->whereColumn('company_category.company_id', 'companies.id')
                ->orderBy('categories.name')
                ->limit(1);

            $companies->orderBy($firstCategoryName, $sortOrder);
        } else {
            $companies->orderBy($sortableColumns[$sortBy], $sortOrder);
        }

        if ($sortBy !== 'id') {
            $companies->orderBy('companies.id', 'desc');
        }

        $companies = $companies->paginate(15)->appends($request->query());
        $categories = $this->allCategories();
        $companyTypes = UserDetails::CUSTOMER_TYPES;

        return view('backend.company.index', compact(
            'companies',
            'categories',
            'companyTypes',
            'filters',
            'sortBy',
            'sortOrder'
        ));
    }

    public function create()
    {
        return view('backend.company.create', $this->formData());
    }

    public function store(CompanyRequest $request)
    {
        $data = $request->safe()->except('deal_in_category_ids');
        $data['created_by'] = auth()->id();

        $company = DB::transaction(function () use ($data, $request) {
            $company = Company::create($data);
            $company->categories()->sync($request->validated('deal_in_category_ids'));

            return $company;
        });

        flash(translate('Company has been added successfully'))->success();

        return redirect()->route('companies.show', $company);
    }

    public function show(Company $company)
    {
        $company->load(['categories', 'creator']);
        $categories = $this->allCategories();

        return view('backend.company.show', compact('company', 'categories'));
    }

    public function edit(Company $company)
    {
        $company->load('categories');

        return view('backend.company.edit', array_merge(
            $this->formData(),
            ['company' => $company]
        ));
    }

    public function update(CompanyRequest $request, Company $company)
    {
        $data = $request->safe()->except('deal_in_category_ids');

        DB::transaction(function () use ($company, $data, $request) {
            $company->update($data);
            $company->categories()->sync($request->validated('deal_in_category_ids'));
        });

        flash(translate('Company has been updated successfully'))->success();

        return redirect()->route('companies.show', $company);
    }

    public function destroy(Company $company)
    {
        DB::transaction(function () use ($company) {
            $company->categories()->detach();
            $company->delete();
        });

        flash(translate('Company has been deleted successfully'))->success();

        return redirect()->route('companies.index');
    }

    private function applyFilters(Builder $companies, array $filters): void
    {
        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $companies->where(function (Builder $query) use ($search) {
                $query->where('code', 'like', '%' . $search . '%')
                    ->orWhere('company_name', 'like', '%' . $search . '%')
                    ->orWhere('full_address', 'like', '%' . $search . '%')
                    ->orWhere('contact_person', 'like', '%' . $search . '%')
                    ->orWhere('designation', 'like', '%' . $search . '%')
                    ->orWhere('mobile', 'like', '%' . $search . '%')
                    ->orWhere('whatsapp', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('company_type', 'like', '%' . $search . '%')
                    ->orWhereHas('categories', function (Builder $categoryQuery) use ($search) {
                        $categoryQuery->where('categories.name', 'like', '%' . $search . '%')
                            ->orWhereHas('category_translations', function (Builder $translationQuery) use ($search) {
                                $translationQuery->where('name', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        if ($filters['company_type'] !== '') {
            $companies->where('company_type', $filters['company_type']);
        }

        if ($filters['category_id'] !== '') {
            $companies->whereHas('categories', function (Builder $query) use ($filters) {
                $query->where('categories.id', $filters['category_id']);
            });
        }

        if ($filters['date_from'] !== '') {
            $companies->whereDate('companies.created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $companies->whereDate('companies.created_at', '<=', $filters['date_to']);
        }
    }

    private function formData(): array
    {
        return [
            'categories' => Category::where('parent_id', 0)
                ->where('digital', 0)
                ->with('childrenCategories')
                ->orderBy('name')
                ->get(),
            'companyTypes' => UserDetails::CUSTOMER_TYPES,
        ];
    }

    private function allCategories()
    {
        return Category::where('digital', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
    }
}
