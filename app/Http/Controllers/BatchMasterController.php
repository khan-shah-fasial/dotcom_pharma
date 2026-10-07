<?php

namespace App\Http\Controllers;

use App\Http\Requests\BatchMasterRequest;
use App\Models\BatchAdjustment;
use App\Models\BatchMaster;
use App\Models\Company;
use App\Models\ProductBatch;
use App\Models\ProductStock;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BatchMasterController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_all_batch_masters'])->only(['index', 'adjust', 'lookupStocks', 'lookupStock', 'revealRate']);
        $this->middleware(['permission:add_batch_master'])->only(['create', 'store']);
        $this->middleware(['permission:edit_batch_master'])->only(['edit', 'update', 'updateStatus', 'adjustStore']);
        $this->middleware(['permission:delete_batch_master'])->only(['destroy']);
    }

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        [$sortBy, $sortDir, $sortColumn] = BatchMaster::resolveSort(
            (string) $request->input('sort_by', 'id'),
            (string) $request->input('sort_dir', 'desc')
        );

        $tableReady = BatchMaster::tableReady();
        $extended = BatchMaster::extendedColumnsReady();
        $batches = null;

        if ($tableReady) {
            $query = $this->listingQuery();
            BatchMaster::applyListingFilters($query, $filters);
            $this->applySort($query, $sortBy, $sortDir, $sortColumn);
            $batches = $query->paginate(15)->withQueryString();
        }

        return view('backend.product.batch_master.index', compact(
            'batches',
            'filters',
            'tableReady',
            'extended',
            'sortBy',
            'sortDir'
        ) + ['showPurchaseRate' => $this->purchaseRateVisible()]);
    }

    public function adjust(Request $request)
    {
        if (!BatchMaster::tableReady()) {
            flash(translate('Batch / Lot Master table is not ready yet.'))->error();

            return redirect()->route('batch_masters.index');
        }

        $filters = $this->filters($request);
        [$sortBy, $sortDir, $sortColumn] = BatchMaster::resolveSort(
            (string) $request->input('sort_by', 'id'),
            (string) $request->input('sort_dir', 'desc')
        );
        $extended = BatchMaster::extendedColumnsReady();
        $adjustmentsReady = BatchMaster::adjustmentsReady();
        $query = $this->listingQuery();
        BatchMaster::applyListingFilters($query, $filters);
        $this->applySort($query, $sortBy, $sortDir, $sortColumn);
        $batches = $query->paginate(15)->withQueryString();

        return view('backend.product.batch_master.adjust', compact(
            'batches',
            'filters',
            'extended',
            'adjustmentsReady',
            'sortBy',
            'sortDir'
        ) + ['showPurchaseRate' => $this->purchaseRateVisible()]);
    }

    public function create()
    {
        if (!BatchMaster::tableReady()) {
            flash(translate('Batch / Lot Master table is not ready yet.'))->error();

            return redirect()->route('batch_masters.index');
        }

        return view('backend.product.batch_master.create', [
            'batch' => null,
            'extended' => BatchMaster::extendedColumnsReady(),
            'companies' => $this->companies(),
            'showPurchaseRate' => $this->purchaseRateVisible(),
        ]);
    }

    public function store(BatchMasterRequest $request)
    {
        if (!BatchMaster::extendedColumnsReady()) {
            flash(translate('Batch / Lot Master needs the new columns. Run sqlupdates/batch_lot_master_extend.sql, then save again. Nothing was written.'))->error();

            return back()->withInput();
        }

        try {
            DB::transaction(function () use ($request) {
                $stockId = (int) $request->input('product_stock_id');
                $before = BatchMaster::query()
                    ->where('product_stock_id', $stockId)
                    ->pluck('id', 'batch_code');

                BatchMaster::copyMissingForStock($stockId);

                foreach ($request->input('rows', []) as $index => $row) {
                    $payload = $this->rowPayload($request, $row, $index);
                    $code = (string) $payload['batch_code'];

                    if (!empty($row['id'])) {
                        $existing = BatchMaster::query()
                            ->where('product_stock_id', $stockId)
                            ->whereKey($row['id'])
                            ->first();
                        if (!$existing) {
                            throw new \RuntimeException(translate('Batch row was not found.'));
                        }
                        $this->applyCoa($existing, $payload, $row);
                        $existing->fill($payload);
                        $existing->save();
                        continue;
                    }

                    if (isset($before[$code])) {
                        throw new \RuntimeException(translate('This Batch Code already exists for the selected SKU.') . ' ' . $code);
                    }

                    $current = BatchMaster::query()
                        ->where('product_stock_id', $stockId)
                        ->where('batch_code', $code)
                        ->first();
                    if ($current) {
                        $this->applyCoa($current, $payload, $row);
                        $current->fill($payload);
                        $current->save();
                        continue;
                    }

                    $created = new BatchMaster();
                    $created->fill($payload);
                    if (empty($created->copied_at)) {
                        $created->copied_at = now();
                    }
                    $created->save();
                }
            });
        } catch (\Throwable $e) {
            flash($e->getMessage())->error();

            return back()->withInput();
        }

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

        return view('backend.product.batch_master.edit', [
            'batch' => $batch,
            'extended' => BatchMaster::extendedColumnsReady(),
            'companies' => $this->companies(),
            'showPurchaseRate' => $this->purchaseRateVisible(),
        ]);
    }

    public function update(BatchMasterRequest $request, $id)
    {
        if (!BatchMaster::extendedColumnsReady()) {
            flash(translate('Batch / Lot Master needs the new columns. Run sqlupdates/batch_lot_master_extend.sql, then save again. Nothing was written.'))->error();

            return back()->withInput();
        }

        $row = BatchMaster::findOrFail($id);
        $payload = BatchMaster::onlyExistingColumns(BatchMaster::normalize($request->validated() + [
            'role_price' => $request->input('role_price'),
            'is_non_batch' => $request->input('is_non_batch'),
            'status' => $request->input('status'),
            'purchase_date' => $request->input('purchase_date'),
            'drug_name' => $request->input('drug_name'),
            'marketed_by_id' => $request->input('marketed_by_id'),
            'marketed_by_name' => $request->input('marketed_by_name'),
            'import_by_ids' => $request->input('import_by_ids'),
            'import_by_names' => $request->input('import_by_names'),
            'manufactured_by_ids' => $request->input('manufactured_by_ids'),
            'manufactured_by_names' => $request->input('manufactured_by_names'),
            'company_id' => $request->input('company_id'),
            'free_qty' => $request->input('free_qty'),
            'purchase_rate' => $request->input('purchase_rate'),
            'tax_code' => $request->input('tax_code'),
            'tax_percent' => $request->input('tax_percent'),
            'scheme' => $request->input('scheme'),
            'batch_discount_percent' => $request->input('batch_discount_percent'),
            'product_discount_percent' => $request->input('product_discount_percent'),
            'scheme_discount_percent' => $request->input('scheme_discount_percent'),
            'coa' => $request->input('coa'),
            'source_purchase_history_id' => $request->input('source_purchase_history_id'),
            'source_product_batch_id' => $request->input('source_product_batch_id'),
        ]));

        if (!$request->filled('coa') && !$request->hasFile('coa_file')) {
            unset($payload['coa']);
        }
        if ($request->hasFile('coa_file')) {
            $payload['coa'] = $this->storeUpload($request->file('coa_file'));
        }

        $row->fill($payload);
        $row->save();

        flash(translate('Batch / Lot Master updated successfully.'))->success();

        return redirect()->route('batch_masters.index');
    }

    public function destroy($id)
    {
        if (!BatchMaster::extendedColumnsReady()) {
            flash(translate('Batch / Lot Master needs the new columns before a row can be removed from the list. Nothing was deleted.'))->error();

            return back();
        }

        $row = BatchMaster::findOrFail($id);
        $row->removed_at = now();
        $row->status = false;
        $row->save();

        flash(translate('Batch / Lot Master was removed from the list. The row is still kept.'))->success();

        return redirect()->route('batch_masters.index');
    }

    public function updateStatus(Request $request)
    {
        $row = BatchMaster::findOrFail($request->input('id'));
        $row->status = (int) $request->input('status', 0) === 1;
        $row->save();

        return response()->json(1);
    }

    public function adjustStore(Request $request, $id)
    {
        if (!BatchMaster::extendedColumnsReady() || !BatchMaster::adjustmentsReady()) {
            flash(translate('Batch Adjustment needs sqlupdates/batch_lot_master_extend.sql. Nothing was written.'))->error();

            return back()->withInput();
        }

        $data = $request->validate([
            'action' => ['required', 'in:convert,destroy'],
            'batch_code' => ['nullable', 'string', 'max:255'],
            'manufacturing_date' => ['nullable'],
            'expiry_date' => ['nullable'],
            'qty' => ['nullable', 'numeric', 'min:0'],
            'scheme' => ['nullable', 'numeric', 'min:0'],
            'purchase_rate' => ['nullable', 'numeric', 'min:0'],
            'mrp_price' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'biowaste' => ['nullable', 'file'],
        ]);

        if ($data['action'] === 'destroy' && (trim((string) ($data['reason'] ?? '')) === '' || !$request->hasFile('biowaste'))) {
            flash(translate('Destroy needs a reason and a biowaste certificate. Nothing was written.'))->error();

            return back()->withInput();
        }

        try {
            DB::transaction(function () use ($request, $id, $data) {
                $old = BatchMaster::query()->whereNull('removed_at')->findOrFail($id);
                $newId = null;

                if ($data['action'] === 'convert') {
                    $code = trim((string) ($data['batch_code'] ?? ''));
                    if ($code === '') {
                        throw new \RuntimeException(translate('Please enter Batch Code.'));
                    }
                    $taken = BatchMaster::query()
                        ->where('product_stock_id', $old->product_stock_id)
                        ->where('batch_code', $code)
                        ->where('id', '!=', $old->id)
                        ->exists();
                    if ($taken || $code === (string) $old->batch_code) {
                        throw new \RuntimeException(translate('Convert needs a new Batch Code. The old row is unchanged.'));
                    }

                    $new = $old->replicate();
                    $new->batch_code = $code;
                    $new->manufacturing_date = BatchMaster::normalizeMonth($data['manufacturing_date'] ?? null, false);
                    $new->expiry_date = BatchMaster::normalizeMonth($data['expiry_date'] ?? null, true);
                    $new->qty = $data['qty'] ?? $old->qty;
                    $new->scheme = $data['scheme'] ?? $old->scheme;
                    $new->purchase_rate = $data['purchase_rate'] ?? $old->purchase_rate;
                    $new->mrp_price = $data['mrp_price'] ?? $old->mrp_price;
                    $new->converted_from_id = $old->id;
                    $new->copied_at = now();
                    $new->removed_at = null;
                    $new->status = true;
                    $new->save();
                    $newId = $new->id;
                } else {
                    $old->status = false;
                    $old->save();
                }

                $certificate = null;
                if ($request->hasFile('biowaste')) {
                    $certificate = $this->storeUpload($request->file('biowaste'));
                }

                BatchAdjustment::create([
                    'batch_master_id' => $old->id,
                    'new_batch_master_id' => $newId,
                    'action' => $data['action'],
                    'qty' => $data['qty'] ?? $old->qty,
                    'reason' => $data['reason'] ?? null,
                    'biowaste_upload_id' => $certificate,
                    'user_id' => auth()->id(),
                ]);
            });
        } catch (\Throwable $e) {
            flash($e->getMessage())->error();

            return back()->withInput();
        }

        flash(translate('Batch Adjustment saved. The old batch row is still kept.'))->success();

        return redirect()->route('batch_masters.adjust');
    }

    public function revealRate(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);
        $user = auth()->user();
        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json(['ok' => false, 'message' => translate('Password does not match.')], 422);
        }

        session(['batch_master_prate_until' => time() + 900]);

        return response()->json(['ok' => true]);
    }

    public function lookupStocks(Request $request)
    {
        $query = trim((string) $request->input('q'));

        $stocks = ProductStock::query()
            ->with('product.brand')
            ->when($query !== '', function ($builder) use ($query) {
                $like = '%' . $query . '%';
                $builder->where(function ($nested) use ($like) {
                    $nested->where('sku', 'like', $like)
                        ->orWhere('variant', 'like', $like)
                        ->orWhereHas('product', function ($productQuery) use ($like) {
                            $productQuery->where('name', 'like', $like)
                                ->orWhere('drug_name', 'like', $like)
                                ->orWhereHas('brand', function ($brandQuery) use ($like) {
                                    $brandQuery->where('name', 'like', $like);
                                });
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
        $stock = ProductStock::with('product')->find((int) $request->input('product_stock_id'));
        if (!$stock) {
            return response()->json(['found' => false]);
        }

        $header = BatchMaster::headerFromStock($stock);
        $productName = $stock->product ? $stock->product->getTranslation('name') : '';
        $purchases = BatchMaster::latestPurchasesForSku($stock->sku);
        $latestPurchase = collect($purchases)->sortByDesc('id')->first();
        $purchaseDate = $latestPurchase ? ($latestPurchase->invoice_date ?: $latestPurchase->order_date) : null;

        $existingCodes = [];
        $rows = [];
        if (BatchMaster::tableReady()) {
            $saved = BatchMaster::query()
                ->where('product_stock_id', $stock->id)
                ->when(BatchMaster::extendedColumnsReady(), function ($query) {
                    $query->whereNull('removed_at');
                })
                ->orderBy('id')
                ->get();
            foreach ($saved as $savedRow) {
                $existingCodes[(string) $savedRow->batch_code] = true;
                $rows[] = $this->formRowFromMaster($savedRow);
            }
        }

        if (BatchMaster::extendedColumnsReady()) {
            foreach (BatchMaster::sourceBundles($stock) as $bundle) {
                $payload = BatchMaster::assembleCopy($bundle);
                $code = (string) ($payload['batch_code'] ?? '');
                if ($code === '' || isset($existingCodes[$code])) {
                    continue;
                }
                $payload['id'] = null;
                $payload['source_coa'] = $bundle['live']['coa'] ?? null;
                $payload['coa'] = $bundle['live']['coa'] ?? null;
                $rows[] = $payload;
                $existingCodes[$code] = true;
            }
        }

        return response()->json([
            'found' => true,
            'product_id' => $stock->product_id,
            'product_stock_id' => $stock->id,
            'sku' => $stock->sku,
            'variant' => $stock->variant,
            'product_name' => $productName,
            'drug_name' => $header['drug_name'],
            'marketed_by_id' => $header['marketed_by_id'],
            'marketed_by_name' => $header['marketed_by_name'],
            'import_by_ids' => json_decode((string) ($header['import_by_ids'] ?? ''), true) ?: [],
            'import_by_names' => $header['import_by_names'],
            'manufactured_by_ids' => json_decode((string) ($header['manufactured_by_ids'] ?? ''), true) ?: [],
            'manufactured_by_names' => $header['manufactured_by_names'],
            'company_id' => $header['company_id'],
            'purchase_date' => $purchaseDate,
            'rows' => $rows,
            'live_lots' => $this->liveLots($stock, $purchases),
        ]);
    }

    private function listingQuery()
    {
        $query = BatchMaster::query()
            ->select('batch_masters.*')
            ->leftJoin('product_stocks', 'product_stocks.id', '=', 'batch_masters.product_stock_id')
            ->leftJoin('products', 'products.id', '=', 'batch_masters.product_id')
            ->with(['product', 'stock']);

        if (BatchMaster::extendedColumnsReady()) {
            $query->whereNull('batch_masters.removed_at');
        }

        return $query;
    }

    private function applySort($query, string $sortBy, string $sortDir, string $sortColumn): void
    {
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
    }

    private function filters(Request $request): array
    {
        return [
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
    }

    private function companies()
    {
        if (!Schema::hasTable('companies')) {
            return collect();
        }

        return Company::query()->orderBy('company_name')->get(['id', 'company_name', 'code']);
    }

    private function purchaseRateVisible(): bool
    {
        return (int) session('batch_master_prate_until', 0) > time();
    }

    private function rowPayload(BatchMasterRequest $request, array $row, int $index): array
    {
        $payload = BatchMaster::onlyExistingColumns(BatchMaster::normalize(array_merge($row, [
            'product_id' => $request->input('product_id'),
            'product_stock_id' => $request->input('product_stock_id'),
            'is_non_batch' => $request->input('is_non_batch'),
            'purchase_date' => $row['purchase_date'] ?? $request->input('purchase_date'),
            'drug_name' => $request->input('drug_name'),
            'marketed_by_id' => $request->input('marketed_by_id'),
            'marketed_by_name' => $request->input('marketed_by_name'),
            'import_by_ids' => $request->input('import_by_ids'),
            'import_by_names' => $request->input('import_by_names'),
            'manufactured_by_ids' => $request->input('manufactured_by_ids'),
            'manufactured_by_names' => $request->input('manufactured_by_names'),
            'company_id' => $row['company_id'] ?? $request->input('company_id'),
            'role_price' => [
                'pts' => $row['price_pts'] ?? null,
                'ptr' => $row['price_ptr'] ?? null,
                'ptd' => $row['price_ptd'] ?? null,
                'gov' => $row['price_gov'] ?? null,
                'expo' => $row['price_expo'] ?? null,
                'customer' => $row['price_customer'] ?? null,
            ],
        ])));

        $file = $request->file('rows.' . $index . '.coa_file');
        if ($file) {
            $payload['coa'] = $this->storeUpload($file);
        }

        return $payload;
    }

    private function applyCoa(BatchMaster $current, array &$payload, array $row): void
    {
        $posted = $payload['coa'] ?? null;
        $source = $row['source_coa'] ?? null;
        if (!$posted) {
            $payload['coa'] = $current->coa;
            return;
        }
        if ($source && (int) $posted === (int) $source) {
            $payload['coa'] = $current->coa ?: BatchMaster::duplicateUpload($posted);
        }
    }

    private function formRowFromMaster(BatchMaster $row): array
    {
        $prices = $row->rolePrices();

        return [
            'id' => $row->id,
            'batch_code' => $row->batch_code,
            'manufacturing_date' => $row->monthValue('manufacturing_date'),
            'expiry_date' => $row->monthValue('expiry_date'),
            'qty' => $row->qty,
            'free_qty' => $row->free_qty,
            'mrp_price' => $row->mrp_price,
            'purchase_rate' => $row->purchase_rate,
            'tax_code' => $row->tax_code,
            'tax_percent' => $row->tax_percent,
            'scheme' => $row->scheme,
            'company_id' => $row->company_id,
            'coa' => $row->coa,
            'source_coa' => null,
            'batch_discount_percent' => $row->batch_discount_percent,
            'product_discount_percent' => $row->product_discount_percent,
            'scheme_discount_percent' => $row->scheme_discount_percent,
            'price_pts' => $prices['pts'],
            'price_ptr' => $prices['ptr'],
            'price_ptd' => $prices['ptd'],
            'price_gov' => $prices['gov'],
            'price_expo' => $prices['expo'],
            'price_customer' => $prices['customer'],
            'source_purchase_history_id' => $row->source_purchase_history_id,
            'source_product_batch_id' => $row->source_product_batch_id,
        ];
    }

    private function liveLots(ProductStock $stock, array $purchases): array
    {
        $discounts = BatchMaster::discountPercentsForStock($stock);
        $header = BatchMaster::headerFromStock($stock);

        return ProductBatch::query()
            ->where('product_stock_id', $stock->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function (ProductBatch $lot) use ($purchases, $discounts, $header) {
                $code = trim((string) $lot->batch);
                $purchase = $purchases[$code] ?? null;
                $tax = BatchMaster::taxSnapshot($purchase->tax_code ?? null);
                $prices = BatchMaster::decodeRolePrices($lot->role_price);
                $rate = $purchase->sale_rate ?? null;
                $taxPercent = $tax['purchase_tax'] ?? ($purchase->gst_percentage ?? null);

                return [
                    'id' => $lot->id,
                    'batch' => $lot->batch,
                    'manufacturing_date' => $lot->manufacturing_date,
                    'expiry_date' => $lot->product_exp_date,
                    'qty' => $lot->qty,
                    'scheme' => $lot->scheme,
                    'mrp_price' => $lot->mrp_price,
                    'purchase_rate' => $rate,
                    'tax_percent' => $taxPercent,
                    'amount' => BatchMaster::amountFrom($lot->qty, $rate),
                    'company_id' => $header['company_id'],
                    'coa' => $lot->coa,
                    'coa_url' => $lot->coa ? uploaded_asset($lot->coa) : null,
                    'upload_date' => optional($lot->created_at)->format('d-m-Y'),
                    'status' => ((float) $lot->qty) > 0,
                    'values' => [
                        'prate' => BatchMaster::amountFrom($lot->qty, $rate),
                        'pts' => BatchMaster::amountFrom($lot->qty, $prices['pts'] ?? null),
                        'ptr' => BatchMaster::amountFrom($lot->qty, $prices['ptr'] ?? null),
                        'ptd' => BatchMaster::amountFrom($lot->qty, $prices['ptd'] ?? null),
                        'gov' => BatchMaster::amountFrom($lot->qty, $prices['gov'] ?? null),
                        'expo' => BatchMaster::amountFrom($lot->qty, $prices['expo'] ?? null),
                        'customer' => BatchMaster::amountFrom($lot->qty, $prices['customer'] ?? null),
                    ],
                    'discounts' => $discounts,
                ];
            })->values();
    }

    private function storeUpload($file): int
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $upload = new Upload();
        $upload->file_original_name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $upload->extension = $extension;
        $upload->file_size = $file->getSize();
        $upload->user_id = auth()->id();
        $upload->type = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? 'image' : 'document';
        $upload->file_name = $file->store('uploads/batch-master/' . date('Y/m'), 'local');
        $upload->save();

        return (int) $upload->id;
    }
}
