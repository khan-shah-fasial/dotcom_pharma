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
        TaxMaster::ensurePermissions();

        $filters = [
            'search' => trim((string) $request->input('search')),
            'kind' => (string) $request->input('kind'),
            'status' => (string) $request->input('status'),
        ];

        $tableReady = TaxMaster::ensureTable();
        $taxes = null;

        if ($tableReady) {
            $query = TaxMaster::query()->orderByDesc('id');

            if ($filters['search'] !== '') {
                $like = '%' . $filters['search'] . '%';
                $query->where(function ($nested) use ($like, $filters) {
                    $nested->where('tax_code', 'like', $like)
                        ->orWhere('description', 'like', $like);

                    if (ctype_digit($filters['search'])) {
                        $nested->orWhere('id', (int) $filters['search']);
                    }
                });
            }

            if (array_key_exists($filters['kind'], TaxMaster::KINDS)) {
                $query->where('kind', $filters['kind']);
            }

            if ($filters['status'] === '1' || $filters['status'] === '0') {
                $query->where('status', (int) $filters['status']);
            }

            $taxes = $query->paginate(15)->withQueryString();
        }

        return view('backend.setup_configurations.tax_master.index', compact('taxes', 'filters', 'tableReady'));
    }

    public function create()
    {
        if (!TaxMaster::ensureTable()) {
            flash(translate('Tax Master table is not ready yet.'))->error();

            return redirect()->route('tax_masters.index');
        }

        return view('backend.setup_configurations.tax_master.create', [
            'tax' => null,
        ]);
    }

    public function store(TaxMasterRequest $request)
    {
        if (!TaxMaster::ensureTable()) {
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
        if (!TaxMaster::ensureTable()) {
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
