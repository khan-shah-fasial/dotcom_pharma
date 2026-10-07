<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyFile;
use App\Models\Country;
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
        $this->middleware(['permission:view_all_companies|add_customer|view_all_customers|general_settings'])->only(['locationOptions']);
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
        $filesReady = CompanyFile::tableReady();
        if ($filesReady) {
            $companies->load(['certificates.upload', 'documents.upload']);
        }
        $categories = $this->allCategories();
        $companyTypes = $this->companyTypeOptions();

        return view('backend.company.index', compact(
            'companies',
            'categories',
            'companyTypes',
            'filters',
            'sortBy',
            'sortOrder',
            'filesReady'
        ));
    }

    public function create()
    {
        return view('backend.company.create', $this->formData());
    }

    public function store(CompanyRequest $request)
    {
        $data = $request->safe()->except(['deal_in_category_ids', 'certificates', 'documents']);
        $data['created_by'] = auth()->id();

        $company = DB::transaction(function () use ($data, $request) {
            $company = Company::create($data);
            $company->categories()->sync($request->validated('deal_in_category_ids'));
            $this->syncFiles($company, $request);

            return $company;
        });

        flash(translate('Company has been added successfully'))->success();

        return redirect()->route('companies.show', $company);
    }

    public function show(Company $company)
    {
        $relations = ['categories', 'creator'];
        $locationReady = Company::locationColumnsReady();
        if ($locationReady) {
            $relations = array_merge($relations, ['country', 'state', 'city']);
        }
        $filesReady = CompanyFile::tableReady();
        if ($filesReady) {
            $relations = array_merge($relations, ['certificates.upload', 'documents.upload']);
        }
        $company->load($relations);
        $categories = $this->allCategories();

        return view('backend.company.show', compact('company', 'categories', 'locationReady', 'filesReady'));
    }

    public function locationOptions(Request $request)
    {
        $validated = $request->validate([
            'country_id' => 'nullable|integer|exists:countries,id',
            'state' => 'nullable|integer|exists:states,id',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|integer|exists:cities,id',
            'post' => 'nullable|string|max:255',
            'village' => 'nullable|string|max:255',
        ]);

        return response()->json(Company::locationChoices($validated));
    }

    public function edit(Company $company)
    {
        $relations = ['categories'];
        if (CompanyFile::tableReady()) {
            $relations[] = 'certificates.upload';
            $relations[] = 'documents.upload';
        }
        $company->load($relations);

        return view('backend.company.edit', array_merge(
            $this->formData(),
            ['company' => $company]
        ));
    }

    public function update(CompanyRequest $request, Company $company)
    {
        $data = $request->safe()->except(['deal_in_category_ids', 'certificates', 'documents']);

        DB::transaction(function () use ($company, $data, $request) {
            $company->update($data);
            $company->categories()->sync($request->validated('deal_in_category_ids'));
            $this->syncFiles($company, $request);
        });

        flash(translate('Company has been updated successfully'))->success();

        return redirect()->route('companies.show', $company);
    }

    public function destroy(Company $company)
    {
        DB::transaction(function () use ($company) {
            $company->categories()->detach();
            if (CompanyFile::tableReady()) {
                CompanyFile::query()
                    ->where('owner_type', CompanyFile::OWNER_COMPANY)
                    ->where('owner_id', $company->id)
                    ->delete();
            }
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
            'companyTypes' => $this->companyTypeOptions(),
            'countries' => Country::query()->isEnabled()->orderBy('name')->get(['id', 'name']),
            'locationReady' => Company::locationColumnsReady(),
            'filesReady' => CompanyFile::tableReady(),
            'lockCompanyName' => false,
        ];
    }

    private function companyTypeOptions()
    {
        return collect(Company::COMPANY_TYPES)
            ->merge(Company::query()->whereNotNull('company_type')->where('company_type', '!=', '')->distinct()->orderBy('company_type')->pluck('company_type'))
            ->unique()
            ->values();
    }

    private function syncFiles(Company $company, CompanyRequest $request): void
    {
        if (!CompanyFile::tableReady()) {
            return;
        }

        CompanyFile::replaceFor(
            CompanyFile::OWNER_COMPANY,
            $company->id,
            $request->validated('certificates') ?? [],
            $request->validated('documents') ?? []
        );
    }

    private function allCategories()
    {
        return Category::where('digital', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
    }
}
