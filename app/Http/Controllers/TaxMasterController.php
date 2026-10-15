<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaxMasterRequest;
use App\Models\HsnMaster;
use App\Models\ProductStock;
use App\Models\TaxMaster;
use Illuminate\Http\Request;

class TaxMasterController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_all_tax_masters'])->only(['index']);
        $this->middleware(['permission:add_tax_master'])->only(['create', 'store']);
        $this->middleware(['permission:edit_tax_master'])->only(['edit', 'update', 'updateStatus']);
        $this->middleware(['permission:add_tax_master|edit_tax_master'])->only(['hsnSearch', 'hsnChapterSearch', 'byHsn', 'lookupStocks', 'lookupTargets', 'suggestCode']);
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
            'hsn_code' => trim((string) $request->input('hsn_code')),
            'hs_code' => trim((string) $request->input('hs_code')),
            'applied_on' => trim((string) $request->input('applied_on')),
        ];

        [$sortBy, $sortDir, $sortColumn] = TaxMaster::resolveSort(
            (string) $request->input('sort_by', 'id'),
            (string) $request->input('sort_dir', 'desc')
        );

        $tableReady = TaxMaster::tableReady();
        $hsnReady = TaxMaster::hsnColumnsReady();
        $utReady = TaxMaster::utColumnsReady();
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
            'hsnReady',
            'utReady',
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

        return view('backend.setup_configurations.tax_master.create', $this->formData(null));
    }

    public function store(TaxMasterRequest $request)
    {
        if (!TaxMaster::tableReady()) {
            flash(translate('Tax Master table is not ready yet.'))->error();

            return back()->withInput();
        }

        $payload = $this->payloadFromRequest($request);
        $tax = TaxMaster::findForRate($payload['kind'] ?? '', $payload['purchase_tax'] ?? 0);
        $reused = (bool) $tax;
        if (!$tax) {
            $tax = new TaxMaster();
        }
        $tax->fill($payload);
        try {
            $tax->save();
        } catch (\Illuminate\Database\QueryException $e) {
            if ((string) $e->getCode() === '23000') {
                flash(translate('This tax code needs its own Tax ID. Run sqlupdates/tax_master_code_kind.sql, then save again.'))->error();

                return back()->withInput();
            }
            throw $e;
        }

        flash(translate($reused
            ? 'This tax type and tax % already exist, so that Tax ID was updated.'
            : 'Tax Master saved successfully.'))->success();

        return redirect()->route('tax_masters.index');
    }

    public function edit($id)
    {
        if (!TaxMaster::tableReady()) {
            flash(translate('Tax Master table is not ready yet.'))->error();

            return redirect()->route('tax_masters.index');
        }

        $tax = TaxMaster::findOrFail($id);

        return view('backend.setup_configurations.tax_master.edit', $this->formData($tax));
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

    public function hsnSearch(Request $request)
    {
        $query = trim((string) $request->input('q'));

        return response()->json(TaxMaster::searchHsnDirectory($query));
    }

    public function hsnChapterSearch(Request $request)
    {
        $query = trim((string) $request->input('q'));

        return response()->json(TaxMaster::searchHsnChapters($query));
    }

    public function byHsn(Request $request)
    {
        $row = HsnMaster::lookupByHsn(trim((string) $request->input('hsn')));

        return response()->json($row ?? ['found' => false]);
    }

    public function lookupStocks(Request $request)
    {
        $query = trim((string) $request->input('q'));
        $mode = (string) $request->input('mode', 'sku');
        $productId = (int) $request->input('product_id', 0);

        if ($productId <= 0 && $query === '') {
            return response()->json([]);
        }

        $stocks = ProductStock::query()
            ->with('product')
            ->when($productId > 0, function ($builder) use ($productId) {
                $builder->where('product_id', $productId);
            })
            ->when($productId <= 0 && $query !== '', function ($builder) use ($query, $mode) {
                $like = '%' . $query . '%';
                $builder->where(function ($nested) use ($like, $mode) {
                    if ($mode === 'product') {
                        $nested->whereHas('product', function ($productQuery) use ($like) {
                            $productQuery->where('name', 'like', $like);
                        });
                    } elseif ($mode === 'variant') {
                        $nested->where('variant', 'like', $like)
                            ->orWhere('sku', 'like', $like);
                    } else {
                        $nested->where('sku', 'like', $like);
                    }
                });
            })
            ->orderBy('sku')
            ->limit($productId > 0 ? 100 : 20)
            ->get();

        $allParts = [];
        foreach ($stocks as $stock) {
            foreach (preg_split('/[-_\/]+/', trim((string) $stock->variant)) ?: [] as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $allParts[] = $part;
                }
            }
        }
        ProductStock::warmVariantLabelCache($allParts);

        $rows = $stocks->map(function (ProductStock $stock) {
            $productName = $stock->product ? $stock->product->getTranslation('name') : '';
            $expanded = $stock->expandedVariantLabel();

            return [
                'id' => $stock->id,
                'product_id' => $stock->product_id,
                'sku' => (string) ($stock->sku ?? ''),
                'product_name' => $productName,
                'variant' => $expanded !== '' ? $expanded : (string) ($stock->variant ?? ''),
                'label' => $stock->fullLookupLabel($stock->product),
            ];
        })->values();

        if ($mode === 'product') {
            $rows = $rows->unique('product_id')->values();
        }

        return response()->json($rows);
    }

    public function lookupTargets(Request $request)
    {
        $type = (string) $request->input('type', 'category');
        $query = trim((string) $request->input('q'));

        return response()->json(TaxMaster::searchAppliedTargets($type, $query));
    }

    public function suggestCode(Request $request)
    {
        $ignoreId = $request->input('ignore_id');
        $kind = (string) $request->input('kind', 'taxable');
        if (!array_key_exists($kind, TaxMaster::KINDS)) {
            $kind = 'taxable';
        }
        $tax = TaxMaster::toDecimal($request->input('tax'));
        $ignore = $ignoreId !== null && $ignoreId !== '' ? (int) $ignoreId : null;
        $existing = TaxMaster::findForRate($kind, $tax, $ignore);

        return response()->json([
            'tax_code' => TaxMaster::suggestTaxCode($tax, $ignore, $kind),
            'existing' => (bool) $existing,
        ]);
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
        $payload = TaxMaster::normalize($request->validated());
        if (!TaxMaster::hsnColumnsReady()) {
            foreach (TaxMaster::HSN_COLUMNS as $column) {
                unset($payload[$column]);
            }
        }
        if (!TaxMaster::utColumnsReady()) {
            foreach (TaxMaster::UT_COLUMNS as $column) {
                unset($payload[$column]);
            }
        }
        if (!TaxMaster::groupColumnReady()) {
            unset($payload['applied_on_group']);
        }

        return $payload;
    }

    private function formData($tax): array
    {
        return [
            'tax' => $tax,
            'hsnReady' => TaxMaster::hsnColumnsReady(),
            'utReady' => TaxMaster::utColumnsReady(),
            'groupReady' => TaxMaster::groupColumnReady(),
        ];
    }
}
