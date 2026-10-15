<?php

namespace App\Http\Controllers;

use App\Http\Requests\HsnMasterRequest;
use App\Models\HsnMaster;
use Illuminate\Http\Request;

class HsnMasterController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_all_hsn_masters'])->only(['index']);
        $this->middleware(['permission:add_hsn_master'])->only(['create', 'store']);
        $this->middleware(['permission:edit_hsn_master'])->only(['edit', 'update', 'updateStatus']);
        $this->middleware(['permission:delete_hsn_master'])->only(['destroy']);
    }

    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->input('search')),
            'hsn_code' => trim((string) $request->input('hsn_code')),
            'description' => trim((string) $request->input('description')),
            'taxable' => (string) $request->input('taxable'),
            'tax_type' => (string) $request->input('tax_type'),
            'tax_percent_from' => trim((string) $request->input('tax_percent_from')),
            'tax_percent_to' => trim((string) $request->input('tax_percent_to')),
            'status' => (string) $request->input('status'),
        ];

        [$sortBy, $sortDir, $sortColumn] = HsnMaster::resolveSort(
            (string) $request->input('sort_by', 'id'),
            (string) $request->input('sort_dir', 'desc')
        );

        $tableReady = HsnMaster::tableReady();
        $rows = null;

        if ($tableReady) {
            $query = HsnMaster::query();
            HsnMaster::applyListingFilters($query, $filters);
            $query->orderBy($sortColumn, $sortDir);
            if ($sortBy !== 'id') {
                $query->orderBy('hsn_masters.id', 'desc');
            }
            $rows = $query->paginate(15)->withQueryString();
        }

        return view('backend.setup_configurations.hsn_master.index', compact(
            'rows',
            'filters',
            'tableReady',
            'sortBy',
            'sortDir'
        ));
    }

    public function create()
    {
        if (!HsnMaster::tableReady()) {
            flash(translate('HSN Master table is not ready yet.'))->error();

            return redirect()->route('hsn_masters.index');
        }

        return view('backend.setup_configurations.hsn_master.create', ['row' => null]);
    }

    public function store(HsnMasterRequest $request)
    {
        if (!HsnMaster::tableReady()) {
            flash(translate('HSN Master table is not ready yet.'))->error();

            return back()->withInput();
        }

        $row = new HsnMaster();
        $row->fill(HsnMaster::normalize($request->validated()));
        $row->save();

        flash(translate('HSN Master saved successfully.'))->success();

        return redirect()->route('hsn_masters.index');
    }

    public function edit($id)
    {
        if (!HsnMaster::tableReady()) {
            flash(translate('HSN Master table is not ready yet.'))->error();

            return redirect()->route('hsn_masters.index');
        }

        $row = HsnMaster::findOrFail($id);

        return view('backend.setup_configurations.hsn_master.edit', compact('row'));
    }

    public function update(HsnMasterRequest $request, $id)
    {
        $row = HsnMaster::findOrFail($id);
        $row->fill(HsnMaster::normalize($request->validated()));
        $row->save();

        flash(translate('HSN Master updated successfully.'))->success();

        return redirect()->route('hsn_masters.index');
    }

    public function updateStatus(Request $request)
    {
        $row = HsnMaster::findOrFail($request->input('id'));
        $row->status = (int) $request->input('status', 0) === 1;
        $row->save();

        return response()->json(1);
    }

    public function destroy($id)
    {
        $row = HsnMaster::findOrFail($id);
        $row->delete();

        flash(translate('HSN Master deleted successfully.'))->success();

        return redirect()->route('hsn_masters.index');
    }
}
