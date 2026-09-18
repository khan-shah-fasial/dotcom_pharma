<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaxMasterRequest;
use App\Models\TaxMaster;
use Illuminate\Http\Request;

class TaxMasterController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_all_tax_masters'])->only(['index']);
        $this->middleware(['permission:add_tax_master'])->only(['create', 'store']);
        $this->middleware(['permission:edit_tax_master'])->only(['edit', 'update', 'updateStatus']);
        $this->middleware(['permission:delete_tax_master'])->only(['destroy']);
    }

    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->input('search')),
            'kind' => (string) $request->input('kind'),
            'tax_code' => trim((string) $request->input('tax_code')),
            'description' => trim((string) $request->input('description')),
            'sale_same_as_purchase' => (string) $request->input('sale_same_as_purchase'),
            'status' => (string) $request->input('status'),
            'purchase_tax_from' => trim((string) $request->input('purchase_tax_from')),
            'purchase_tax_to' => trim((string) $request->input('purchase_tax_to')),
            'sale_tax_from' => trim((string) $request->input('sale_tax_from')),
            'sale_tax_to' => trim((string) $request->input('sale_tax_to')),
            'date_from' => trim((string) $request->input('date_from')),
            'date_to' => trim((string) $request->input('date_to')),
        ];

        [$sortBy, $sortDir, $sortColumn] = TaxMaster::resolveSort(
            (string) $request->input('sort_by', 'id'),
            (string) $request->input('sort_dir', 'desc')
        );

        $tableReady = TaxMaster::tableReady();
        $taxes = null;

        if ($tableReady) {
            $query = TaxMaster::query();
            TaxMaster::applyListingFilters($query, $filters);
            $query->orderBy($sortColumn, $sortDir);
            if ($sortBy !== 'id') {
                $query->orderBy('tax_masters.id', 'desc');
            }
            $taxes = $query->paginate(15)->withQueryString();
        }

        return view('backend.setup_configurations.tax_master.index', compact(
            'taxes',
            'filters',
            'tableReady',
            'sortBy',
            'sortDir'
        ));
    }

    public function create()
    {
        if (!TaxMaster::tableReady()) {
            flash(translate('Tax Master table is not ready yet.'))->error();

            return redirect()->route('tax_masters.index');
        }

        return view('backend.setup_configurations.tax_master.create', [
            'tax' => null,
        ]);
    }

    public function store(TaxMasterRequest $request)
    {
        if (!TaxMaster::tableReady()) {
            flash(translate('Tax Master table is not ready yet.'))->error();

            return back()->withInput();
        }

        $tax = new TaxMaster();
        $tax->fill($this->payloadFromRequest($request));
        $tax->save();

        flash(translate('Tax Master saved successfully.'))->success();

        return redirect()->route('tax_masters.index');
    }

    public function edit($id)
    {
        if (!TaxMaster::tableReady()) {
            flash(translate('Tax Master table is not ready yet.'))->error();

            return redirect()->route('tax_masters.index');
        }

        $tax = TaxMaster::findOrFail($id);

        return view('backend.setup_configurations.tax_master.edit', compact('tax'));
    }

    public function update(TaxMasterRequest $request, $id)
    {
        $tax = TaxMaster::findOrFail($id);
        $tax->fill($this->payloadFromRequest($request));
        $tax->save();

        flash(translate('Tax Master updated successfully.'))->success();

        return redirect()->route('tax_masters.index');
    }

    public function destroy($id)
    {
        $tax = TaxMaster::findOrFail($id);
        $tax->delete();

        flash(translate('Tax Master deleted successfully.'))->success();

        return redirect()->route('tax_masters.index');
    }

    public function updateStatus(Request $request)
    {
        $tax = TaxMaster::findOrFail($request->input('id'));
        $tax->status = (int) $request->input('status', 0) === 1;
        $tax->save();

        return response()->json(1);
    }

    private function payloadFromRequest(TaxMasterRequest $request): array
    {
        return TaxMaster::normalize($request->validated());
    }
}
