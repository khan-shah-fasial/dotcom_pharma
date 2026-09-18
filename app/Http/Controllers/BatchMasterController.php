<?php

namespace App\Http\Controllers;

use App\Http\Requests\BatchMasterRequest;
use App\Models\BatchMaster;
use App\Models\ProductBatch;
use App\Models\ProductStock;
use Illuminate\Http\Request;

class BatchMasterController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_all_batch_masters'])->only(['index', 'lookupStocks', 'lookupStock']);
        $this->middleware(['permission:add_batch_master'])->only(['create', 'store']);
        $this->middleware(['permission:edit_batch_master'])->only(['edit', 'update', 'updateStatus']);
        $this->middleware(['permission:delete_batch_master'])->only(['destroy']);
    }

    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->input('search')),
            'sku' => trim((string) $request->input('sku')),
            'product_name' => trim((string) $request->input('product_name')),
            'variant' => trim((string) $request->input('variant')),
            'batch_code' => trim((string) $request->input('batch_code')),
            'is_non_batch' => (string) $request->input('is_non_batch'),
            'status' => (string) $request->input('status'),
            'mfg_from' => trim((string) $request->input('mfg_from')),
            'mfg_to' => trim((string) $request->input('mfg_to')),
            'exp_from' => trim((string) $request->input('exp_from')),
            'exp_to' => trim((string) $request->input('exp_to')),
            'mrp_from' => trim((string) $request->input('mrp_from')),
            'mrp_to' => trim((string) $request->input('mrp_to')),
            'qty_from' => trim((string) $request->input('qty_from')),
            'qty_to' => trim((string) $request->input('qty_to')),
            'date_from' => trim((string) $request->input('date_from')),
            'date_to' => trim((string) $request->input('date_to')),
            'updated_from' => trim((string) $request->input('updated_from')),
            'updated_to' => trim((string) $request->input('updated_to')),
        ];

        [$sortBy, $sortDir, $sortColumn] = BatchMaster::resolveSort(
            (string) $request->input('sort_by', 'id'),
            (string) $request->input('sort_dir', 'desc')
        );

        $tableReady = BatchMaster::tableReady();
        $batches = null;

        if ($tableReady) {
            $query = BatchMaster::query()
                ->select('batch_masters.*')
                ->leftJoin('product_stocks', 'product_stocks.id', '=', 'batch_masters.product_stock_id')
                ->leftJoin('products', 'products.id', '=', 'batch_masters.product_id')
                ->with(['product', 'stock']);

            BatchMaster::applyListingFilters($query, $filters);

            if ($sortBy === 'role_price') {
                $query->orderByRaw(
                    'CAST(JSON_UNQUOTE(JSON_EXTRACT(batch_masters.role_price, \'$.pts\')) AS DECIMAL(20,4)) ' . $sortDir
                );
            } else {
                $query->orderBy($sortColumn, $sortDir);
            }
            if ($sortBy !== 'id') {
                $query->orderBy('batch_masters.id', 'desc');
            }

            $batches = $query->paginate(15)->withQueryString();
        }

        return view('backend.product.batch_master.index', compact(
            'batches',
            'filters',
            'tableReady',
            'sortBy',
            'sortDir'
        ));
    }

    public function create()
    {
        if (!BatchMaster::tableReady()) {
            flash(translate('Batch / Lot Master table is not ready yet.'))->error();

            return redirect()->route('batch_masters.index');
        }

        return view('backend.product.batch_master.create', ['batch' => null]);
    }

    public function store(BatchMasterRequest $request)
    {
        if (!BatchMaster::tableReady()) {
            flash(translate('Batch / Lot Master table is not ready yet.'))->error();

            return back()->withInput();
        }

        $row = new BatchMaster();
        $row->fill($this->payloadFromRequest($request));
        $row->save();

        flash(translate('Batch / Lot Master saved successfully.'))->success();

        return redirect()->route('batch_masters.index');
    }

    public function edit($id)
    {
        if (!BatchMaster::tableReady()) {
            flash(translate('Batch / Lot Master table is not ready yet.'))->error();

            return redirect()->route('batch_masters.index');
        }

        $batch = BatchMaster::with(['product', 'stock'])->findOrFail($id);

        return view('backend.product.batch_master.edit', compact('batch'));
    }

    public function update(BatchMasterRequest $request, $id)
    {
        $row = BatchMaster::findOrFail($id);
        $row->fill($this->payloadFromRequest($request));
        $row->save();

        flash(translate('Batch / Lot Master updated successfully.'))->success();

        return redirect()->route('batch_masters.index');
    }

    public function destroy($id)
    {
        $row = BatchMaster::findOrFail($id);
        $row->delete();

        flash(translate('Batch / Lot Master deleted successfully.'))->success();

        return redirect()->route('batch_masters.index');
    }

    public function updateStatus(Request $request)
    {
        $row = BatchMaster::findOrFail($request->input('id'));
        $row->status = (int) $request->input('status', 0) === 1;
        $row->save();

        return response()->json(1);
    }

    public function lookupStocks(Request $request)
    {
        $query = trim((string) $request->input('q'));

        $stocks = ProductStock::query()
            ->with('product')
            ->when($query !== '', function ($builder) use ($query) {
                $like = '%' . $query . '%';
                $builder->where(function ($nested) use ($like) {
                    $nested->where('sku', 'like', $like)
                        ->orWhere('variant', 'like', $like)
                        ->orWhereHas('product', function ($productQuery) use ($like) {
                            $productQuery->where('name', 'like', $like);
                        });
                });
            })
            ->orderBy('sku')
            ->limit(20)
            ->get();

        return response()->json($stocks->map(function (ProductStock $stock) {
            $productName = $stock->product ? $stock->product->getTranslation('name') : '';

            return [
                'id' => $stock->id,
                'product_id' => $stock->product_id,
                'sku' => $stock->sku,
                'variant' => $stock->variant,
                'product_name' => $productName,
                'label' => trim($productName . ' / ' . ($stock->sku ?: $stock->variant ?: ('#' . $stock->id))),
            ];
        })->values());
    }

    public function lookupStock(Request $request)
    {
        $stockId = (int) $request->input('product_stock_id');
        $stock = ProductStock::with('product')->find($stockId);
        if (!$stock) {
            return response()->json(['found' => false]);
        }

        $productName = $stock->product ? $stock->product->getTranslation('name') : '';

        $liveLots = ProductBatch::query()
            ->where('product_stock_id', $stock->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'batch', 'manufacturing_date', 'product_exp_date', 'qty', 'mrp_price']);

        return response()->json([
            'found' => true,
            'product_id' => $stock->product_id,
            'product_stock_id' => $stock->id,
            'sku' => $stock->sku,
            'variant' => $stock->variant,
            'product_name' => $productName,
            'live_lots' => $liveLots->map(function (ProductBatch $lot) {
                return [
                    'id' => $lot->id,
                    'batch' => $lot->batch,
                    'manufacturing_date' => $lot->manufacturing_date,
                    'expiry_date' => $lot->product_exp_date,
                    'qty' => $lot->qty,
                    'mrp_price' => $lot->mrp_price,
                ];
            })->values(),
        ]);
    }

    private function payloadFromRequest(BatchMasterRequest $request): array
    {
        return BatchMaster::normalize($request->validated() + [
            'role_price' => $request->input('role_price'),
            'is_non_batch' => $request->input('is_non_batch'),
            'status' => $request->input('status'),
        ]);
    }
}
