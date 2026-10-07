<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyConfigurationRequest;
use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyConfiguration;
use App\Models\CompanyFile;
use App\Models\Country;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CompanyConfigurationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:general_settings']);
    }

    public function edit()
    {
        $tableReady = CompanyConfiguration::tableReady();
        $company = $tableReady
            ? (CompanyConfiguration::current() ?? new CompanyConfiguration())
            : new CompanyConfiguration();

        $filesReady = CompanyFile::tableReady();
        if ($tableReady && $company->exists) {
            $relations = ['categories'];
            if ($filesReady) {
                $relations[] = 'certificates.upload';
                $relations[] = 'documents.upload';
            }
            $company->load($relations);
        }

        return view('backend.setup_configurations.company_configuration.edit', array_merge(
            $this->formData(),
            [
                'company' => $company,
                'tableReady' => $tableReady,
                'locationReady' => $tableReady,
                'filesReady' => $filesReady,
                'lockCompanyName' => true,
            ]
        ));
    }

    public function update(CompanyConfigurationRequest $request)
    {
        if (!CompanyConfiguration::tableReady()) {
            flash(translate('Company Configuration table is not ready yet. Run the database SQL first.'))->warning();

            return back()->withInput();
        }

        $data = $request->safe()->except(['deal_in_category_ids', 'security_password', 'certificates', 'documents']);
        $data['company_name'] = CompanyConfiguration::BILLING_NAME;

        try {
            $company = DB::transaction(function () use ($data, $request) {
                $existing = CompanyConfiguration::query()->lockForUpdate()->orderBy('id')->first();

                if ($existing) {
                    $existing->update($data);
                    $existing->categories()->sync($request->validated('deal_in_category_ids'));
                    $this->syncFiles($existing, $request);

                    return $existing;
                }

                $data['singleton'] = 1;
                $data['created_by'] = auth()->id();
                $company = CompanyConfiguration::create($data);
                $company->categories()->sync($request->validated('deal_in_category_ids'));
                $this->syncFiles($company, $request);

                return $company;
            });
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? null) !== '23000') {
                throw $exception;
            }

            flash(translate('Only one billing company can be saved.'))->error();

            return back()->withInput();
        }

        flash(translate('Billing company has been saved successfully'))->success();

        return redirect()->route('company_configuration.edit');
    }

    private function syncFiles(CompanyConfiguration $company, CompanyConfigurationRequest $request): void
    {
        if (!CompanyFile::tableReady()) {
            return;
        }

        CompanyFile::replaceFor(
            CompanyFile::OWNER_BILLING,
            $company->id,
            $request->validated('certificates') ?? [],
            $request->validated('documents') ?? []
        );
    }

    private function formData(): array
    {
        return [
            'categories' => Category::where('parent_id', 0)
                ->where('digital', 0)
                ->with('childrenCategories')
                ->orderBy('name')
                ->get(),
            'companyTypes' => collect(Company::COMPANY_TYPES)
                ->merge(
                    Company::query()
                        ->whereNotNull('company_type')
                        ->where('company_type', '!=', '')
                        ->distinct()
                        ->orderBy('company_type')
                        ->pluck('company_type')
                )
                ->unique()
                ->values(),
            'countries' => Country::query()->isEnabled()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
