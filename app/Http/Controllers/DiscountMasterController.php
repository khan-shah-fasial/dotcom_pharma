<?php

namespace App\Http\Controllers;

use App\Http\Requests\DiscountMasterRequest;
use App\Models\Category;
use App\Models\DiscountMaster;
use App\Models\Group;
use App\Models\ProductBatch;
use App\Models\ProductStock;
use App\Models\Upload;
use App\Services\OrderPlacementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DiscountMasterController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_all_discount_masters'])->only(['index', 'lookupStocks', 'lookupBatches', 'lookupCustomers', 'lookupTarget', 'nextCode']);
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
                ])
                ->orderByDesc('id');

            if ($filters['search'] !== '') {
                $like = '%' . $filters['search'] . '%';
                $query->where(function ($nested) use ($like) {
                    $nested->where('discount_code', 'like', $like)
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

            $discounts = $query->paginate(15)->withQueryString();
        }

        return view('backend.marketing.discount_master.index', compact('discounts', 'filters', 'tableReady'));
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
        $discount->fill($this->payloadFromRequest($request));
        $discount->discount_code = DiscountMaster::nextCode($discount->discount_type);
        $discount->save();

        flash(translate('Discount Master saved successfully.'))->success();

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

        $discount->fill($payload);
        $discount->save();

        flash(translate('Discount Master updated successfully.'))->success();

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
                            });
                    }
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
                'label' => trim($productName . ' / ' . ($stock->sku ?: $stock->variant ?: ('#' . $stock->id))),
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
        $appliedOn = (string) $request->input('applied_on');
        $stockId = $request->filled('product_stock_id') ? (int) $request->input('product_stock_id') : null;
        $batchId = $request->filled('batch_id') ? (int) $request->input('batch_id') : null;

        $batch = null;
        $stock = null;
        $product = null;

        if ($appliedOn === 'batch' && $batchId) {
            $batch = ProductBatch::with(['stock', 'product'])->find($batchId);
            $stock = $batch?->stock;
            $product = $batch?->product;
        } elseif (in_array($appliedOn, ['sku', 'full_variant'], true) && $stockId) {
            $stock = ProductStock::with(['product', 'batches'])->find($stockId);
            $product = $stock?->product;
            $batch = $stock?->batches->sortByDesc('id')->first();
        }

        $rolePrices = DiscountMaster::decodeRolePrices($batch, $stock, $product);
        $coa = $batch?->coa ?? $stock?->coa;

        return response()->json([
            'product_id' => $product?->id,
            'product_stock_id' => $stock?->id,
            'batch_id' => $batch?->id,
            'sku' => $stock?->sku,
            'variant' => $stock?->variant,
            'product_name' => $product ? $product->getTranslation('name') : null,
            'batch' => $batch?->batch,
            'stock_available' => $batch ? $batch->qty : ($stock?->qty),
            'manufacturing_date' => $batch?->manufacturing_date,
            'expiry_date' => $batch?->product_exp_date ?? $stock?->product_exp_date,
            'coa' => $coa,
            'coa_label' => $this->coaLabel($coa),
            'coa_url' => $this->coaUrl($coa),
            'role_prices' => $rolePrices,
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
        $appliedOn = $request->input('applied_on');
        $data = $request->validated();

        $data['product_id'] = null;
        $data['product_stock_id'] = null;
        $data['batch_id'] = null;
        $data['category_id'] = null;
        $data['group_id'] = null;
        $data['customer_id'] = null;

        if (in_array($appliedOn, ['sku', 'full_variant'], true)) {
            $stock = ProductStock::find($request->input('product_stock_id'));
            $data['product_stock_id'] = $stock?->id;
            $data['product_id'] = $stock?->product_id;
        } elseif ($appliedOn === 'batch') {
            $batch = ProductBatch::find($request->input('batch_id'));
            $data['batch_id'] = $batch?->id;
            $data['product_stock_id'] = $batch?->product_stock_id;
            $data['product_id'] = $batch?->product_id;
        } elseif ($appliedOn === 'category') {
            $data['category_id'] = $request->input('category_id');
            $data['role_key'] = null;
            $data['qty_slab_from'] = null;
            $data['qty_slab_to'] = null;
            $data['rate'] = null;
            $data['amount'] = null;
        } elseif ($appliedOn === 'group') {
            $data['group_id'] = $request->input('group_id');
            $data['role_key'] = null;
            $data['qty_slab_from'] = null;
            $data['qty_slab_to'] = null;
            $data['rate'] = null;
            $data['amount'] = null;
        } elseif ($appliedOn === 'customer') {
            $data['customer_id'] = $request->input('customer_id');
            $data['role_key'] = null;
            $data['qty_slab_from'] = null;
            $data['qty_slab_to'] = null;
            $data['rate'] = null;
            $data['amount'] = null;
        }

        $type = $request->input('discount_type');
        if ($type !== 'pointwise') {
            $data['earn'] = null;
        }
        if ($type !== 'amount_wise') {
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

        if (!in_array($type, ['batchwise', 'productwise', 'amount_wise', 'pointwise'], true)) {
            if ($type !== 'schemewise') {
                $data['value_type'] = null;
                $data['value_amount'] = null;
                $data['value_percent'] = null;
            }
        }

        $data['status'] = $request->boolean('status');

        return $data;
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
