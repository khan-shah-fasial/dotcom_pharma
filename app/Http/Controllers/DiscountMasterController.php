<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscountMasterRequest;
use App\Models\BatchMaster;
use App\Models\Category;
use App\Models\DiscountMaster;
use App\Models\Group;
use App\Models\ProductBatch;
use App\Models\ProductStock;
use App\Models\Upload;
use App\Services\OrderPlacementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DiscountMasterController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_all_discount_masters'])->only(['index', 'lookupStocks', 'lookupBatches', 'lookupCustomers', 'lookupTarget', 'nextCode']);
        $this->middleware(['permission:add_discount_master|edit_discount_master'])->only(['revealPurchaseRate']);
        $this->middleware(['permission:add_discount_master'])->only(['create', 'store']);
        $this->middleware(['permission:edit_discount_master'])->only(['edit', 'update', 'updateStatus']);
        $this->middleware(['permission:delete_discount_master'])->only(['destroy']);
    }

    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->input('search')),
            'applied_on' => (string) $request->input('applied_on'),
            'discount_type' => (string) $request->input('discount_type'),
            'status' => (string) $request->input('status'),
            'date_from' => (string) $request->input('date_from'),
            'date_to' => (string) $request->input('date_to'),
        ];

        $tableReady = Schema::hasTable('discount_masters');
        $discounts = null;
        $sortBy = '';
        $sortDir = 'desc';

        if ($tableReady) {
            $query = DiscountMaster::query()
                ->with([
                    'product',
                    'stock.batches',
                    'batch',
                    'category',
                    'group',
                    'customer.user_details',
                    'schemeStock',
                ]);

            if ($filters['search'] !== '') {
                $like = '%' . $filters['search'] . '%';
                $query->where(function ($nested) use ($like) {
                    $nested->where('discount_code', 'like', $like)
                        ->when(Schema::hasColumn('discount_masters', 'coupon_code'), function ($nested) use ($like) {
                            $nested->orWhere('coupon_code', 'like', $like);
                        })
                        ->orWhereHas('stock', function ($stockQuery) use ($like) {
                            $stockQuery->where('sku', 'like', $like)
                                ->orWhere('variant', 'like', $like);
                        })
                        ->orWhereHas('batch', function ($batchQuery) use ($like) {
                            $batchQuery->where('batch', 'like', $like);
                        })
                        ->orWhereHas('product', function ($productQuery) use ($like) {
                            $productQuery->where('name', 'like', $like);
                        })
                        ->orWhereHas('category', function ($categoryQuery) use ($like) {
                            $categoryQuery->where('name', 'like', $like);
                        })
                        ->orWhereHas('group', function ($groupQuery) use ($like) {
                            $groupQuery->where('name', 'like', $like);
                        })
                        ->orWhereHas('customer', function ($customerQuery) use ($like) {
                            $customerQuery->where('name', 'like', $like)
                                ->orWhereHas('user_details', function ($detailsQuery) use ($like) {
                                    $detailsQuery->where('company_name', 'like', $like);
                                });
                        });
                });
            }

            if (array_key_exists($filters['applied_on'], DiscountMaster::APPLIED_ON)) {
                $query->where('applied_on', $filters['applied_on']);
            }

            if (array_key_exists($filters['discount_type'], DiscountMaster::DISCOUNT_TYPES)) {
                $query->where('discount_type', $filters['discount_type']);
            }

            if ($filters['status'] === '1' || $filters['status'] === '0') {
                $query->where('status', (int) $filters['status']);
            }

            if ($filters['date_from'] !== '') {
                $query->whereDate('from_date', '>=', $filters['date_from']);
            }

            if ($filters['date_to'] !== '') {
                $query->where(function ($dateQuery) use ($filters) {
                    $dateQuery->whereNull('to_date')
                        ->orWhereDate('to_date', '<=', $filters['date_to']);
                });
            }

            $allowedSorts = [
                'applied_on', 'sku', 'variant', 'batch', 'mfg_date', 'stock_role', 'role_key',
                'qty_slab', 'discount_code', 'batchwise', 'productwise', 'pointwise',
                'amount_wise', 'scheme', 'from_date',
            ];
            $requestedSort = (string) $request->get('sort_by', '');
            $sortBy = in_array($requestedSort, $allowedSorts, true) ? $requestedSort : '';
            $sortDir = strtolower((string) $request->get('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
            $this->applyDiscountMasterSort($query, $sortBy, $sortDir);

            $discounts = $query->paginate(15)->withQueryString();
        }

        return view('backend.marketing.discount_master.index', compact('discounts', 'filters', 'tableReady', 'sortBy', 'sortDir'));
    }

    private function applyDiscountMasterSort($query, string $sortBy, string $sortDir): void
    {
        $dir = $sortDir === 'asc' ? 'asc' : 'desc';
        $columns = [
            'applied_on' => 'discount_masters.applied_on',
            'stock_role' => 'discount_masters.role_key',
            'role_key' => 'discount_masters.role_key',
            'qty_slab' => 'discount_masters.qty_slab_from',
            'discount_code' => 'discount_masters.discount_code',
            'batchwise' => 'discount_masters.value_amount',
            'productwise' => 'discount_masters.value_amount',
            'pointwise' => 'discount_masters.earn',
            'amount_wise' => 'discount_masters.invoice_amount',
            'scheme' => 'discount_masters.scheme_free_qty',
            'from_date' => 'discount_masters.from_date',
        ];

        if ($sortBy === '') {
            $query->orderByDesc('discount_masters.id');
            return;
        }
        if (isset($columns[$sortBy])) {
            $query->orderBy($columns[$sortBy], $dir)->orderByDesc('discount_masters.id');
            return;
        }
        if ($sortBy === 'sku') {
            $query->orderByRaw('(select sku from product_stocks where product_stocks.id = discount_masters.product_stock_id limit 1) ' . $dir);
        } elseif ($sortBy === 'variant') {
            $query->orderByRaw('(select variant from product_stocks where product_stocks.id = discount_masters.product_stock_id limit 1) ' . $dir);
        } elseif ($sortBy === 'batch') {
            $query->orderByRaw("COALESCE((select batch from product_batches where product_batches.id = discount_masters.batch_id limit 1), (select name from categories where categories.id = discount_masters.category_id limit 1), (select name from groups where groups.id = discount_masters.group_id limit 1), (select name from users where users.id = discount_masters.customer_id limit 1)) " . $dir);
        } elseif ($sortBy === 'mfg_date') {
            $query->orderByRaw('COALESCE((select manufacturing_date from product_batches where product_batches.id = discount_masters.batch_id limit 1), (select manufacturing_date from product_batches where product_batches.product_stock_id = discount_masters.product_stock_id order by product_batches.id desc limit 1)) ' . $dir);
        }
        $query->orderByDesc('discount_masters.id');
    }

    public function create()
    {
        return view('backend.marketing.discount_master.create', $this->formPayload());
    }

    public function store(DiscountMasterRequest $request)
    {
        if (!Schema::hasTable('discount_masters')) {
            flash(translate('Discount Master table is not ready. Run the discount_masters migration first.'))->error();

            return back()->withInput();
        }

        $discount = new DiscountMaster();
        $discount->forceFill(array_merge($this->payloadFromRequest($request), $this->extraColumns($request)));
        $discount->discount_code = DiscountMaster::nextCode($discount->discount_type);
        $discount->save();

        $this->flashSaved($request, 'Discount Master saved successfully.');

        return redirect()->route('discount_masters.index');
    }

    public function edit($id)
    {
        if (!Schema::hasTable('discount_masters')) {
            flash(translate('Discount Master table is not ready. Run the discount_masters migration first.'))->error();

            return redirect()->route('discount_masters.index');
        }

        $discount = DiscountMaster::with([
            'product',
            'stock',
            'batch',
            'category',
            'group',
            'customer.user_details',
            'schemeStock',
        ])->findOrFail($id);

        return view('backend.marketing.discount_master.edit', array_merge(
            $this->formPayload(),
            compact('discount')
        ));
    }

    public function update(DiscountMasterRequest $request, $id)
    {
        $discount = DiscountMaster::findOrFail($id);
        $payload = $this->payloadFromRequest($request);

        if ($discount->discount_type !== $payload['discount_type']) {
            $payload['discount_code'] = DiscountMaster::nextCode($payload['discount_type']);
        }

        $discount->forceFill(array_merge($payload, $this->extraColumns($request, $this->couponHistory($discount, $request))));
        $discount->save();

        $this->flashSaved($request, 'Discount Master updated successfully.');

        return redirect()->route('discount_masters.index');
    }

    public function destroy($id)
    {
        $discount = DiscountMaster::findOrFail($id);
        $discount->delete();

        flash(translate('Discount Master deleted successfully.'))->success();

        return redirect()->route('discount_masters.index');
    }

    public function updateStatus(Request $request)
    {
        $discount = DiscountMaster::findOrFail($request->input('id'));
        $discount->status = (int) $request->input('status', 0) === 1;
        $discount->save();

        return response()->json(1);
    }

    public function nextCode(Request $request)
    {
        $type = (string) $request->input('discount_type');
        if (!array_key_exists($type, DiscountMaster::DISCOUNT_TYPES)) {
            return response()->json(['code' => '']);
        }

        if (!Schema::hasTable('discount_masters')) {
            return response()->json(['code' => DiscountMaster::TYPE_PREFIXES[$type] . '001']);
        }

        return response()->json(['code' => DiscountMaster::nextCode($type)]);
    }

    public function lookupStocks(Request $request)
    {
        $query = trim((string) $request->input('q'));
        $mode = (string) $request->input('mode', 'sku');

        $stocks = ProductStock::query()
            ->with('product')
            ->when($query !== '', function ($builder) use ($query, $mode) {
                $like = '%' . $query . '%';
                $builder->where(function ($nested) use ($like, $mode) {
                    if ($mode === 'variant') {
                        $nested->where('variant', 'like', $like)
                            ->orWhere('sku', 'like', $like)
                            ->orWhereHas('product', function ($productQuery) use ($like) {
                                $productQuery->where('name', 'like', $like);
                            });
                    } else {
                        $nested->where('sku', 'like', $like)
                            ->orWhere('variant', 'like', $like)
                            ->orWhereHas('product', function ($productQuery) use ($like) {
                                $productQuery->where('name', 'like', $like);
                                if (Schema::hasColumn('products', 'drug_name')) {
                                    $productQuery->orWhere('drug_name', 'like', $like);
                                }
                            })
                            ->orWhereHas('product.brand', function ($brandQuery) use ($like) {
                                $brandQuery->where('name', 'like', $like);
                            });
                    }
                });
            })
            ->orderBy('sku')
            ->limit(20)
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

        return response()->json($stocks->map(function (ProductStock $stock) {
            $productName = $stock->product ? $stock->product->getTranslation('name') : '';
            $expanded = $stock->expandedVariantLabel();

            return [
                'id' => $stock->id,
                'product_id' => $stock->product_id,
                'sku' => $stock->sku,
                'variant' => $stock->variant,
                'id_variant' => $stock->id_variant,
                'expanded_variant' => $expanded,
                'label' => $stock->fullLookupLabel($stock->product),
            ];
        })->values());
    }

    public function lookupBatches(Request $request)
    {
        $query = trim((string) $request->input('q'));

        $batches = ProductBatch::query()
            ->with(['stock', 'product'])
            ->when($query !== '', function ($builder) use ($query) {
                $like = '%' . $query . '%';
                $builder->where(function ($nested) use ($like) {
                    $nested->where('batch', 'like', $like)
                        ->orWhereHas('stock', function ($stockQuery) use ($like) {
                            $stockQuery->where('sku', 'like', $like)
                                ->orWhere('variant', 'like', $like);
                        })
                        ->orWhereHas('product', function ($productQuery) use ($like) {
                            $productQuery->where('name', 'like', $like);
                        });
                });
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return response()->json($batches->map(function (ProductBatch $batch) {
            $productName = $batch->product ? $batch->product->getTranslation('name') : '';

            return [
                'id' => $batch->id,
                'product_id' => $batch->product_id,
                'product_stock_id' => $batch->product_stock_id,
                'batch' => $batch->batch,
                'label' => trim(($batch->batch ?: '-') . ' / ' . $productName),
            ];
        })->values());
    }

    public function lookupCustomers(Request $request, OrderPlacementService $orders)
    {
        $query = trim((string) $request->input('q'));

        $customers = $orders->approvedCustomerQuery()
            ->with('user_details')
            ->when($query !== '', function ($builder) use ($query) {
                $like = '%' . $query . '%';
                $builder->where(function ($nested) use ($like) {
                    $nested->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhereHas('user_details', function ($details) use ($like) {
                            $details->where('company_name', 'like', $like)
                                ->orWhere('con_person_name', 'like', $like)
                                ->orWhere('crm_id', 'like', $like);
                        });
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json($customers->map(function ($customer) {
            $company = optional($customer->user_details)->company_name;

            return [
                'id' => $customer->id,
                'label' => $company ?: $customer->name,
            ];
        })->values());
    }

    public function lookupTarget(Request $request)
    {
        $stockId = $request->filled('product_stock_id') ? (int) $request->input('product_stock_id') : null;
        $batchId = $request->filled('batch_id') ? (int) $request->input('batch_id') : null;

        $batch = null;
        $stock = null;
        $product = null;

        if ($stockId) {
            $stock = ProductStock::with(['product', 'batches'])->find($stockId);
            $product = $stock?->product;
            $batch = $stock?->batches->sortByDesc('id')->first();
        } elseif ($batchId) {
            $batch = ProductBatch::with(['stock', 'product'])->find($batchId);
            $stock = $batch?->stock;
            $product = $batch?->product ?? $stock?->product;
        }

        $header = ($stock && method_exists(BatchMaster::class, 'headerFromStock'))
            ? BatchMaster::headerFromStock($stock)
            : [];
        $batches = $stock ? $stock->batches->sortBy('batch')->values() : collect();
        if ($batches->isEmpty() && $batch) {
            $batches = collect([$batch]);
        }

        return response()->json([
            'product_id' => $product?->id,
            'product_stock_id' => $stock?->id,
            'sku' => $stock?->sku,
            'variant' => $stock ? $stock->expandedVariantLabel() : null,
            'product_name' => $product ? $product->getTranslation('name') : null,
            'drug_name' => $header['drug_name'] ?? null,
            'marketed_by' => $header['marketed_by_name'] ?? null,
            'import_by' => $this->nameList($header['import_by_names'] ?? null),
            'mfg_by' => $this->nameList($header['manufactured_by_names'] ?? null),
            'stock_available' => $stock?->qty,
            'role_prices' => DiscountMaster::decodeRolePrices($batch, $stock, $product),
            'batches' => $batches->map(function (ProductBatch $row) use ($stock, $product) {
                return [
                    'id' => $row->id,
                    'batch' => $row->batch,
                    'qty' => $row->qty,
                    'mfg' => $row->manufacturing_date,
                    'expiry' => $row->product_exp_date,
                    'coa_url' => $this->coaUrl($row->coa),
                    'coa_label' => $this->coaLabel($row->coa),
                    'role_prices' => DiscountMaster::decodeRolePrices($row, $stock, $product),
                ];
            })->values(),
        ]);
    }

    public function revealPurchaseRate(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
            'product_stock_id' => ['nullable', 'integer'],
            'batch_id' => ['nullable', 'integer'],
        ]);

        $user = auth()->user();
        if (!$user || !Hash::check((string) $request->input('password'), (string) $user->password)) {
            return response()->json(['message' => translate('Password does not match.')], 422);
        }

        return response()->json([
            'purchase_rate' => $this->purchaseRate(
                $request->filled('product_stock_id') ? (int) $request->input('product_stock_id') : null,
                $request->filled('batch_id') ? (int) $request->input('batch_id') : null
            ),
        ]);
    }

    private function formPayload(): array
    {
        $categories = Category::query()->orderBy('name');
        if (Schema::hasColumn('categories', 'digital')) {
            $categories->where('digital', 0);
        }
        $categories = $categories->get(['id', 'name']);

        $groups = Group::query()->orderBy('name');
        if (Schema::hasColumn('groups', 'digital')) {
            $groups->where('digital', 0);
        }
        $groups = $groups->get(['id', 'name']);

        return compact('categories', 'groups');
    }

    private function payloadFromRequest(DiscountMasterRequest $request): array
    {
        $type = (string) $request->input('discount_type');
        $data = $request->validated();
        foreach ([
            'roles', 'amounts', 'batch_ids', 'same_discount', 'near_expiry',
            'scope_products', 'scope_role', 'coupon_code', 'product_label',
        ] as $extra) {
            unset($data[$extra]);
        }

        $data['product_id'] = null;
        $data['product_stock_id'] = null;
        $data['batch_id'] = null;
        $data['category_id'] = null;
        $data['group_id'] = null;
        $data['customer_id'] = null;

        if (in_array($type, DiscountMaster::PRODUCT_TYPES, true)) {
            $stock = ProductStock::find($request->input('product_stock_id'));
            $data['product_stock_id'] = $stock?->id;
            $data['product_id'] = $stock?->product_id;
        }

        if (in_array($type, DiscountMaster::BATCH_TYPES, true)) {
            $batch = ProductBatch::find($request->input('batch_id'));
            $data['batch_id'] = $batch?->id;
            if ($batch) {
                $data['product_stock_id'] = $batch->product_stock_id;
                $data['product_id'] = $batch->product_id;
            }
        }

        if ($request->boolean('same_discount')) {
            $data['category_id'] = $request->input('category_id') ?: null;
            $data['group_id'] = $request->input('group_id') ?: null;
            $data['customer_id'] = $request->input('customer_id') ?: null;
        }

        if ($type !== 'pointwise') {
            $data['earn'] = null;
        }
        if (!in_array($type, DiscountMaster::INVOICE_TYPES, true)) {
            $data['invoice_amount'] = null;
        }
        if ($type !== 'schemewise') {
            $data['scheme_free_qty'] = null;
            $data['scheme_percent'] = null;
            $data['scheme_value'] = null;
            $data['scheme_product_is_same'] = null;
            $data['scheme_product_stock_id'] = null;
        } elseif ($request->boolean('scheme_product_is_same')) {
            $data['scheme_product_stock_id'] = null;
        }
        if (!in_array($type, ['productwise', 'batchwise', 'amount_wise', 'pointwise', 'couponwise'], true)) {
            $data['value_type'] = null;
            $data['value_amount'] = null;
            $data['value_percent'] = null;
        }

        $data['status'] = $request->boolean('status');

        return $data;
    }

    private function couponHistory(DiscountMaster $discount, DiscountMasterRequest $request): array
    {
        if ($request->input('discount_type') !== 'couponwise') {
            return [];
        }

        $old = json_decode($discount->getAttributes()['sheet_payload'] ?? '', true) ?: [];
        $history = $old['history'] ?? [];
        $history[] = [
            'at' => now()->format('d-m-Y H:i'),
            'coupon_code' => (string) $request->input('coupon_code'),
            'amount' => $request->input('value_amount'),
            'percent' => $request->input('value_percent'),
        ];

        return array_slice($history, -15);
    }

    private function extraColumns(DiscountMasterRequest $request, array $history = []): array
    {
        $extra = [];
        $payload = json_encode([
            'roles' => $request->input('roles', []),
            'amounts' => $request->input('amounts', []),
            'batch_ids' => array_values(array_filter((array) $request->input('batch_ids', []))),
            'same_discount' => $request->boolean('same_discount'),
            'near_expiry' => $request->boolean('near_expiry'),
            'scope_products' => array_values(array_filter((array) $request->input('scope_products', []))),
            'scope_role' => $request->input('scope_role'),
            'coupon_code' => $request->input('coupon_code'),
            'history' => $history,
        ]);

        if (Schema::hasColumn('discount_masters', 'sheet_payload')) {
            $extra['sheet_payload'] = $payload;
        }
        if (Schema::hasColumn('discount_masters', 'coupon_code')) {
            $extra['coupon_code'] = $request->input('coupon_code') ?: null;
        }

        return $extra;
    }

    private function sheetDataDropped(DiscountMasterRequest $request): bool
    {
        if (Schema::hasColumn('discount_masters', 'sheet_payload')) {
            return false;
        }

        $roles = (array) $request->input('roles', []);
        $slabs = (array) ($roles[0]['slabs'] ?? []);
        $amounts = (array) $request->input('amounts', []);
        $batches = array_filter((array) $request->input('batch_ids', []));

        return count($roles) > 1
            || count($slabs) > 1
            || count($amounts) > 1
            || count($batches) > 1
            || ($request->filled('coupon_code') && !Schema::hasColumn('discount_masters', 'coupon_code'));
    }

    private function flashSaved(DiscountMasterRequest $request, string $message): void
    {
        if ($this->sheetDataDropped($request)) {
            flash(translate('Discount Master saved the first slab only. Extra slabs, extra batches, and the coupon number need the extra columns.'))->warning();

            return;
        }

        flash(translate($message))->success();
    }

    private function nameList($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_array($value)) {
            return implode(', ', $value);
        }
        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            return implode(', ', $decoded);
        }

        return (string) $value;
    }

    private function purchaseRate(?int $stockId, ?int $batchId): ?float
    {
        if (!BatchMaster::tableReady() || !Schema::hasColumn('batch_masters', 'purchase_rate')) {
            return null;
        }

        $query = BatchMaster::query()->whereNotNull('purchase_rate');
        if ($batchId) {
            $batch = ProductBatch::find($batchId);
            if (!$batch) {
                return null;
            }
            $query->where('product_stock_id', $batch->product_stock_id);
            if ($batch->batch) {
                $query->where('batch_code', $batch->batch);
            }
        } elseif ($stockId) {
            $query->where('product_stock_id', $stockId);
        } else {
            return null;
        }

        $rate = $query->orderByDesc('id')->value('purchase_rate');

        return $rate === null ? null : (float) $rate;
    }

    private function coaLabel($coa): ?string
    {
        if ($coa === null || $coa === '') {
            return null;
        }

        if (is_numeric($coa) && Schema::hasTable('uploads')) {
            $fileName = Upload::where('id', (int) $coa)->value('file_original_name')
                ?: Upload::where('id', (int) $coa)->value('file_name');
            if ($fileName) {
                return $fileName;
            }
        }

        return (string) $coa;
    }

    private function coaUrl($coa): ?string
    {
        if ($coa === null || $coa === '') {
            return null;
        }

        if (is_numeric($coa)) {
            return uploaded_asset((int) $coa) ?: null;
        }

        return null;
    }
}