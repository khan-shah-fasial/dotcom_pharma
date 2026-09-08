<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Group;
use App\Models\ProductStock;
use App\Models\ProductBatch;
use App\Models\CommissionHistory;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Search;
use App\Models\Shop;
use Auth;

class ReportController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:in_house_product_sale_report'])->only('in_house_sale_report');
        $this->middleware(['permission:seller_products_sale_report'])->only('seller_sale_report');
        $this->middleware(['permission:products_stock_report'])->only([
            'stock_report',
            'updateStockReportBatch',
            'getStockFilterOptions',
            'product_detail_report',
            'getProductDetailFilterOptions',
        ]);
        $this->middleware(['permission:product_wishlist_report'])->only('wish_report');
        $this->middleware(['permission:user_search_report'])->only('user_search_report');
        $this->middleware(['permission:commission_history_report'])->only('commission_history');
        $this->middleware(['permission:wallet_transaction_report'])->only('wallet_transaction_history');
    }

    public function stock_report(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $categoryId = $request->filled('category_id') ? (int) $request->category_id : null;
        $groupId = $request->filled('group_id') ? (int) $request->group_id : null;
        $brandId = $request->filled('brand_id') ? (int) $request->brand_id : null;
        $productId = $request->filled('product_id') ? (int) $request->product_id : null;
        $variantId = $request->filled('variant_id') ? (int) $request->variant_id : null;
        $batchId = $request->filled('batch_id') ? (int) $request->batch_id : null;
        $sku = trim((string) $request->input('sku'));
        $schedule = trim((string) $request->input('schedule'));
        $origin = trim((string) $request->input('origin'));
        $hsn = trim((string) $request->input('hsn'));
        $publishedStatus = $request->filled('published_status') ? (string) $request->input('published_status') : null;
        $stockStatus = $request->filled('stock_status') ? (string) $request->input('stock_status') : null;
        $expiryStatus = $request->filled('expiry_status') ? (string) $request->input('expiry_status') : null;
        $sortBy = (string) $request->input('sort_by', 'product_name');
        $sortOrder = strtolower((string) $request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';

        $mrpExpr = 'COALESCE(product_batches.mrp_price, product_stocks.mrp_price, products.mrp_price)';
        $roleExpr = fn (string $role) => "CAST(JSON_UNQUOTE(JSON_EXTRACT(product_batches.role_price, '$.\"{$role}\"')) AS DECIMAL(15,4))";
        $roleValueExpr = fn (string $role) => '(' . $roleExpr($role) . ' * product_batches.qty)';
        $roleGpExpr = fn (string $role) => "CASE WHEN {$mrpExpr} > 0 AND {$roleExpr($role)} IS NOT NULL THEN (({$mrpExpr} - {$roleExpr($role)}) / {$mrpExpr}) * 100 ELSE NULL END";

        // One sort key per stacked header label from the Stock Book Master sheet.
        $allowedSorts = [
            'sr_no' => ['column' => 'product_batches.id'],
            'sku' => ['column' => 'product_stocks.sku'],
            'origin' => ['column' => 'products.product_origin'],
            'category' => ['column' => 'products.category_id'],
            'group' => ['column' => 'products.group_id'],
            'schedule' => ['column' => 'products.schedule'],
            'product_name' => ['column' => 'products.name'],
            'composition' => ['column' => 'products.drug_name'],
            'company' => ['raw' => '(SELECT brands.name FROM brands WHERE brands.id = products.brand_id)'],
            'pack_size' => ['column' => 'product_stocks.variant'],
            'full_variant' => ['column' => 'product_stocks.variant'],
            'batch' => ['column' => 'product_batches.batch'],
            'manufacturing_date' => ['column' => 'product_batches.manufacturing_date'],
            'expiry' => ['column' => 'product_batches.product_exp_date'],
            'qty' => ['column' => 'product_batches.qty'],
            'scheme' => ['raw' => 'COALESCE(product_batches.scheme, product_stocks.scheme)'],
            'total_qty' => ['raw' => '(product_batches.qty + COALESCE(product_batches.scheme, product_stocks.scheme, 0))'],
            'purchase_rate' => ['column' => 'products.purchase_price'],
            'purchase_value' => ['raw' => '(products.purchase_price * product_batches.qty)'],
            'purchase_tax_percent' => ['raw' => "(SELECT COALESCE(SUM(product_taxes.tax), 0) FROM product_taxes WHERE product_taxes.product_id = products.id AND product_taxes.tax_type = 'percent')"],
            'purchase_tax_value' => ['raw' => "((SELECT COALESCE(SUM(product_taxes.tax), 0) FROM product_taxes WHERE product_taxes.product_id = products.id AND product_taxes.tax_type = 'percent') / 100) * COALESCE(products.purchase_price, 0) * product_batches.qty"],
            'pts' => ['raw' => $roleExpr('pts')],
            'pts_value' => ['raw' => $roleValueExpr('pts')],
            'pts_gp' => ['raw' => $roleGpExpr('pts')],
            'ptr' => ['raw' => $roleExpr('ptr')],
            'ptr_value' => ['raw' => $roleValueExpr('ptr')],
            'ptr_gp' => ['raw' => $roleGpExpr('ptr')],
            'ptd' => ['raw' => $roleExpr('ptd')],
            'ptd_value' => ['raw' => $roleValueExpr('ptd')],
            'ptd_gp' => ['raw' => $roleGpExpr('ptd')],
            'gov' => ['raw' => $roleExpr('gov')],
            'gov_value' => ['raw' => $roleValueExpr('gov')],
            'gov_gp' => ['raw' => $roleGpExpr('gov')],
            'export' => ['raw' => $roleExpr('expo')],
            'export_value' => ['raw' => $roleValueExpr('expo')],
            'export_gp' => ['raw' => $roleGpExpr('expo')],
            'customer' => ['raw' => $roleExpr('customer')],
            'customer_value' => ['raw' => $roleValueExpr('customer')],
            'customer_gp' => ['raw' => $roleGpExpr('customer')],
            'mrp' => ['raw' => $mrpExpr],
            'mrp_value' => ['raw' => "({$mrpExpr} * product_batches.qty)"],
            'sales_tax_percent' => ['raw' => "(SELECT COALESCE(SUM(product_taxes.tax), 0) FROM product_taxes WHERE product_taxes.product_id = products.id AND product_taxes.tax_type = 'percent')"],
            'sales_tax_value' => ['raw' => "((SELECT COALESCE(SUM(product_taxes.tax), 0) FROM product_taxes WHERE product_taxes.product_id = products.id AND product_taxes.tax_type = 'percent') / 100) * COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(product_batches.role_price, '$.\"customer\"')) AS DECIMAL(15,4)), product_stocks.price, 0) * product_batches.qty"],
            'hsn' => ['column' => 'products.product_hsn'],
            'hs_code' => ['column' => 'products.product_hs'],
            'last_update' => ['column' => 'products.updated_at'],
            'piece_qty' => ['column' => 'product_stocks.qty_per_piece'],
            'piece_weight' => ['column' => 'product_stocks.weight'],
            'buffer_qty' => ['column' => 'product_stocks.qty_per_buffer_box'],
            'buffer_weight' => ['column' => 'product_stocks.weight_buffer_box'],
            'buffer_per_case_qty' => ['column' => 'product_stocks.count'],
            'case_weight' => ['column' => 'product_stocks.weight_case'],
            'per_case_qty' => ['column' => 'product_stocks.total_qty_per_case'],
            'average_gp' => ['raw' => '((' . implode(' + ', [
                "COALESCE({$roleGpExpr('pts')}, 0)",
                "COALESCE({$roleGpExpr('ptr')}, 0)",
                "COALESCE({$roleGpExpr('ptd')}, 0)",
                "COALESCE({$roleGpExpr('gov')}, 0)",
                "COALESCE({$roleGpExpr('expo')}, 0)",
                "COALESCE({$roleGpExpr('customer')}, 0)",
            ]) . ') / NULLIF((' .
                "IF({$roleGpExpr('pts')} IS NULL, 0, 1) + " .
                "IF({$roleGpExpr('ptr')} IS NULL, 0, 1) + " .
                "IF({$roleGpExpr('ptd')} IS NULL, 0, 1) + " .
                "IF({$roleGpExpr('gov')} IS NULL, 0, 1) + " .
                "IF({$roleGpExpr('expo')} IS NULL, 0, 1) + " .
                "IF({$roleGpExpr('customer')} IS NULL, 0, 1)" .
            '), 0))'],
        ];

        if (!array_key_exists($sortBy, $allowedSorts)) {
            $sortBy = 'product_name';
        }

        $sort = $allowedSorts[$sortBy];

        $reportRows = ProductBatch::query()
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->join('product_stocks', 'product_stocks.id', '=', 'product_batches.product_stock_id')
            ->select('product_batches.*')
            ->with([
                'stock',
                'product.brand',
                'product.main_category',
                'product.main_group',
                'product.categories',
                'product.groups',
                'product.taxes',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';

                $query->where(function ($searchQuery) use ($like) {
                    $searchQuery
                        ->where('products.name', 'like', $like)
                        ->orWhere('products.drug_name', 'like', $like)
                        ->orWhere('products.role_label', 'like', $like)
                        ->orWhere('products.product_hsn', 'like', $like)
                        ->orWhere('products.product_hs', 'like', $like)
                        ->orWhere('products.product_origin', 'like', $like)
                        ->orWhere('products.schedule', 'like', $like)
                        ->orWhere('product_stocks.sku', 'like', $like)
                        ->orWhere('product_stocks.variant', 'like', $like)
                        ->orWhere('product_batches.batch', 'like', $like)
                        ->orWhereHas('product.brand', function ($brandQuery) use ($like) {
                            $brandQuery->where('name', 'like', $like);
                        })
                        ->orWhereHas('product.product_translations', function ($translationQuery) use ($like) {
                            $translationQuery->where('name', 'like', $like);
                        });
                });
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where(function ($categoryQuery) use ($categoryId) {
                    $categoryQuery
                        ->where('products.category_id', $categoryId)
                        ->orWhereHas('product.categories', function ($relationQuery) use ($categoryId) {
                            $relationQuery->where('categories.id', $categoryId);
                        });
                });
            })
            ->when($groupId, function ($query) use ($groupId) {
                $query->where(function ($groupQuery) use ($groupId) {
                    $groupQuery
                        ->where('products.group_id', $groupId)
                        ->orWhereHas('product.groups', function ($relationQuery) use ($groupId) {
                            $relationQuery->where('groups.id', $groupId);
                        });
                });
            })
            ->when($brandId, function ($query) use ($brandId) {
                $query->where('products.brand_id', $brandId);
            })
            ->when($productId, function ($query) use ($productId) {
                $query->where('product_batches.product_id', $productId);
            })
            ->when($variantId, function ($query) use ($variantId) {
                $query->where('product_batches.product_stock_id', $variantId);
            })
            ->when($batchId, function ($query) use ($batchId) {
                $query->where('product_batches.id', $batchId);
            })
            ->when($sku !== '', function ($query) use ($sku) {
                $query->where('product_stocks.sku', 'like', '%' . $sku . '%');
            })
            ->when($schedule !== '', function ($query) use ($schedule) {
                $query->where('products.schedule', $schedule);
            })
            ->when($origin !== '', function ($query) use ($origin) {
                $query->where('products.product_origin', $origin);
            })
            ->when($hsn !== '', function ($query) use ($hsn) {
                $like = '%' . $hsn . '%';
                $query->where(function ($hsnQuery) use ($like) {
                    $hsnQuery
                        ->where('products.product_hsn', 'like', $like)
                        ->orWhere('products.product_hs', 'like', $like);
                });
            })
            ->when(in_array($publishedStatus, ['0', '1'], true), function ($query) use ($publishedStatus) {
                $query->where('products.published', (int) $publishedStatus);
            })
            ->when($stockStatus === 'in_stock', function ($query) {
                $query->where('product_batches.qty', '>', 0);
            })
            ->when($stockStatus === 'out_of_stock', function ($query) {
                $query->where('product_batches.qty', '<=', 0);
            })
            ->when($expiryStatus === 'expired', function ($query) {
                $query->whereNotNull('product_batches.product_exp_date')
                    ->whereDate('product_batches.product_exp_date', '<', now()->toDateString());
            })
            ->when($expiryStatus === 'expiring_soon', function ($query) {
                $query->whereBetween('product_batches.product_exp_date', [
                    now()->toDateString(),
                    now()->addDays(90)->toDateString(),
                ]);
            })
            ->when($expiryStatus === 'valid', function ($query) {
                $query->whereDate('product_batches.product_exp_date', '>', now()->addDays(90)->toDateString());
            })
            ->when($expiryStatus === 'no_expiry', function ($query) {
                $query->whereNull('product_batches.product_exp_date');
            })
            ->when(isset($sort['raw']), function ($query) use ($sort, $sortOrder) {
                $query->orderByRaw($sort['raw'] . ' ' . $sortOrder);
            }, function ($query) use ($sort, $sortOrder) {
                $query->orderBy($sort['column'], $sortOrder);
            })
            ->orderBy('product_batches.id')
            ->paginate(25)
            ->withQueryString();

        $categories = Category::where('digital', 0)->orderBy('name')->get(['id', 'name']);
        $groups = Group::where('digital', 0)->orderBy('name')->get(['id', 'name']);
        $brands = Brand::orderBy('name')->get(['id', 'name']);
        $schedules = Product::query()
            ->whereNotNull('schedule')
            ->where('schedule', '!=', '')
            ->distinct()
            ->orderBy('schedule')
            ->pluck('schedule');
        $origins = Product::query()
            ->whereNotNull('product_origin')
            ->where('product_origin', '!=', '')
            ->distinct()
            ->orderBy('product_origin')
            ->pluck('product_origin');

        $productsForFilter = $this->applyProductDetailProductFilters(
            Product::query()->whereHas('stocks.batches'),
            $categoryId,
            $groupId,
            $brandId
        )->orderBy('name')->get(['id', 'name']);

        $variants = collect();
        $batches = collect();

        if ($productId) {
            $variants = ProductStock::where('product_id', $productId)
                ->whereHas('batches')
                ->orderBy('variant')
                ->get(['id', 'variant', 'sku']);

            $batches = ProductBatch::where('product_id', $productId)
                ->when($variantId, function ($query) use ($variantId) {
                    $query->where('product_stock_id', $variantId);
                })
                ->orderBy('batch')
                ->get(['id', 'batch', 'product_stock_id']);
        }

        return view('backend.reports.stock_report', compact(
            'reportRows',
            'categories',
            'groups',
            'brands',
            'schedules',
            'origins',
            'productsForFilter',
            'variants',
            'batches',
            'search',
            'categoryId',
            'groupId',
            'brandId',
            'productId',
            'variantId',
            'batchId',
            'sku',
            'schedule',
            'origin',
            'hsn',
            'publishedStatus',
            'stockStatus',
            'expiryStatus',
            'sortBy',
            'sortOrder'
        ));
    }

    public function updateStockReportBatch(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|integer',
            'field' => 'required|string',
            'value' => 'nullable',
        ]);

        $field = (string) $request->input('field');
        $roleKeys = ['pts', 'ptr', 'ptd', 'gov', 'expo', 'customer'];
        $allowed = array_merge(
            ['batch', 'manufacturing_date', 'product_exp_date', 'qty', 'scheme', 'mrp_price'],
            $roleKeys
        );

        if (!in_array($field, $allowed, true)) {
            return response()->json([
                'success' => false,
                'message' => translate('This field cannot be edited here.'),
            ], 422);
        }

        $batch = ProductBatch::with('stock')->find((int) $request->input('batch_id'));
        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => translate('Batch not found.'),
            ], 404);
        }

        $value = $request->input('value');

        if ($field === 'batch') {
            $batchCode = trim((string) $value);
            if ($batchCode === '') {
                return response()->json([
                    'success' => false,
                    'message' => translate('Batch / Lot No is required.'),
                ], 422);
            }
            $batch->batch = $batchCode;
        } elseif ($field === 'qty') {
            if ($value === null || $value === '' || !preg_match('/^\d+$/', (string) $value)) {
                return response()->json([
                    'success' => false,
                    'message' => translate('Qty must be a whole number.'),
                ], 422);
            }
            $batch->qty = (int) $value;
        } elseif ($field === 'scheme') {
            if ($value === null || $value === '' || !preg_match('/^\d+$/', (string) $value)) {
                return response()->json([
                    'success' => false,
                    'message' => translate('Scheme must be a whole number.'),
                ], 422);
            }
            $batch->scheme = (int) $value;
        } elseif ($field === 'mrp_price') {
            if ($value === null || $value === '') {
                $batch->mrp_price = null;
            } elseif (!is_numeric($value) || (float) $value < 0) {
                return response()->json([
                    'success' => false,
                    'message' => translate('MRP must be a valid amount.'),
                ], 422);
            } else {
                $batch->mrp_price = round((float) $value, 2);
            }
        } elseif ($field === 'manufacturing_date' || $field === 'product_exp_date') {
            $trimmed = trim((string) ($value ?? ''));
            $useEndOfMonth = $field === 'product_exp_date';
            if ($trimmed !== '' && $this->normalizeStockReportMonth($value, $useEndOfMonth) === null) {
                return response()->json([
                    'success' => false,
                    'message' => translate('Use a valid month.'),
                ], 422);
            }
            $normalized = $this->normalizeStockReportMonth($value, $useEndOfMonth);
            if ($field === 'manufacturing_date') {
                $batch->manufacturing_date = $normalized;
            } else {
                $batch->product_exp_date = $normalized;
            }
        } elseif (in_array($field, $roleKeys, true)) {
            if ($value === null || $value === '') {
                return response()->json([
                    'success' => false,
                    'message' => translate('Price cannot be empty.'),
                ], 422);
            }
            if (!is_numeric($value) || (float) $value < 0) {
                return response()->json([
                    'success' => false,
                    'message' => translate('Price must be a valid amount.'),
                ], 422);
            }

            $rolePrices = is_array($batch->role_price)
                ? $batch->role_price
                : json_decode((string) $batch->role_price, true);
            $rolePrices = is_array($rolePrices) ? $rolePrices : [];
            $rolePrices[$field] = round((float) $value, 2);
            $batch->role_price = json_encode($rolePrices);
        }

        $batch->save();

        $stock = $batch->stock;
        if ($stock) {
            if ($field === 'qty') {
                $stock->qty = (int) $stock->batches()->sum('qty');
            }
            if ($field === 'scheme') {
                $stock->scheme = (int) $batch->scheme;
            }

            $firstBatch = $stock->batches()->orderBy('id')->first();
            if ($firstBatch && (int) $firstBatch->id === (int) $batch->id) {
                if ($field === 'mrp_price') {
                    $stock->mrp_price = $batch->mrp_price;
                }
                if ($field === 'product_exp_date') {
                    $stock->product_exp_date = $batch->product_exp_date;
                }
            }

            if ($stock->isDirty()) {
                $stock->save();
            }
        }

        $display = $value;
        if ($field === 'manufacturing_date' || $field === 'product_exp_date') {
            $dateValue = $field === 'manufacturing_date' ? $batch->manufacturing_date : $batch->product_exp_date;
            $display = $dateValue && strtotime((string) $dateValue) !== false
                ? date('F-y', strtotime((string) $dateValue))
                : '';
        } elseif ($field === 'mrp_price' || in_array($field, $roleKeys, true)) {
            $amount = $field === 'mrp_price'
                ? $batch->mrp_price
                : round((float) $value, 2);
            $display = $amount === null || $amount === ''
                ? ''
                : number_format((float) $amount, 2, '.', '');
        } elseif ($field === 'qty' || $field === 'scheme') {
            $display = (string) (int) $batch->{$field};
        } elseif ($field === 'batch') {
            $display = (string) $batch->batch;
        }

        $rolePrices = is_array($batch->role_price)
            ? $batch->role_price
            : json_decode((string) $batch->role_price, true);
        $rolePrices = is_array($rolePrices) ? $rolePrices : [];
        $mrp = $batch->mrp_price ?? optional($stock)->mrp_price;
        $qty = (int) $batch->qty;
        $scheme = (int) ($batch->scheme ?? optional($stock)->scheme ?? 0);
        $minQty = max(1, (int) (optional($stock)->min_qty ?? 1));
        $totalQty = $qty + (function_exists('calculate_scheme_qty') ? calculate_scheme_qty($qty, $minQty, $scheme) : 0);

        return response()->json([
            'success' => true,
            'message' => translate('Saved'),
            'display' => $display,
            'qty' => $qty,
            'scheme' => $scheme,
            'total_qty' => $totalQty,
            'mrp_price' => $mrp === null || $mrp === '' ? null : round((float) $mrp, 2),
            'role_prices' => [
                'pts' => $rolePrices['pts'] ?? null,
                'ptr' => $rolePrices['ptr'] ?? null,
                'ptd' => $rolePrices['ptd'] ?? null,
                'gov' => $rolePrices['gov'] ?? null,
                'expo' => $rolePrices['expo'] ?? null,
                'customer' => $rolePrices['customer'] ?? null,
            ],
        ]);
    }

    public function product_detail_report(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $groupId = $request->filled('group_id') ? (int) $request->input('group_id') : null;
        $brandId = $request->filled('brand_id') ? (int) $request->input('brand_id') : null;
        $productId = $request->filled('product_id') ? (int) $request->input('product_id') : null;
        $variantId = $request->filled('variant_id') ? (int) $request->input('variant_id') : null;
        $batchId = $request->filled('batch_id') ? (int) $request->input('batch_id') : null;
        $publishedStatus = $request->filled('published_status') ? (string) $request->input('published_status') : null;
        $stockStatus = $request->filled('stock_status') ? (string) $request->input('stock_status') : null;
        $expiryStatus = $request->filled('expiry_status') ? (string) $request->input('expiry_status') : null;
        $sortBy = (string) $request->input('sort_by', 'product_name');
        $sortOrder = strtolower((string) $request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = [
            'product_name' => 'products.name',
            'sku' => 'product_stocks.sku',
            'variant' => 'product_stocks.variant',
            'batch' => 'product_batches.batch',
            'mrp_price' => 'product_batches.mrp_price',
            'qty' => 'product_batches.qty',
            'expiry' => 'product_batches.product_exp_date',
        ];
        if (!array_key_exists($sortBy, $allowedSorts)) {
            $sortBy = 'product_name';
        }

        $reportRows = ProductBatch::query()
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->join('product_stocks', 'product_stocks.id', '=', 'product_batches.product_stock_id')
            ->select('product_batches.*')
            ->with([
                'stock',
                'product.brand',
                'product.main_category',
                'product.main_group',
                'product.categories',
                'product.groups',
                'product.taxes',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';

                $query->where(function ($searchQuery) use ($like) {
                    $searchQuery
                        ->where('products.name', 'like', $like)
                        ->orWhere('products.drug_name', 'like', $like)
                        ->orWhere('products.role_label', 'like', $like)
                        ->orWhere('product_stocks.sku', 'like', $like)
                        ->orWhere('product_stocks.variant', 'like', $like)
                        ->orWhere('product_batches.batch', 'like', $like)
                        ->orWhereHas('product.product_translations', function ($translationQuery) use ($like) {
                            $translationQuery->where('name', 'like', $like);
                        });
                });
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where(function ($categoryQuery) use ($categoryId) {
                    $categoryQuery
                        ->where('products.category_id', $categoryId)
                        ->orWhereHas('product.categories', function ($relationQuery) use ($categoryId) {
                            $relationQuery->where('categories.id', $categoryId);
                        });
                });
            })
            ->when($groupId, function ($query) use ($groupId) {
                $query->where(function ($groupQuery) use ($groupId) {
                    $groupQuery
                        ->where('products.group_id', $groupId)
                        ->orWhereHas('product.groups', function ($relationQuery) use ($groupId) {
                            $relationQuery->where('groups.id', $groupId);
                        });
                });
            })
            ->when($brandId, function ($query) use ($brandId) {
                $query->where('products.brand_id', $brandId);
            })
            ->when($productId, function ($query) use ($productId) {
                $query->where('product_batches.product_id', $productId);
            })
            ->when($variantId, function ($query) use ($variantId) {
                $query->where('product_batches.product_stock_id', $variantId);
            })
            ->when($batchId, function ($query) use ($batchId) {
                $query->where('product_batches.id', $batchId);
            })
            ->when(in_array($publishedStatus, ['0', '1'], true), function ($query) use ($publishedStatus) {
                $query->where('products.published', (int) $publishedStatus);
            })
            ->when($stockStatus === 'in_stock', function ($query) {
                $query->where('product_batches.qty', '>', 0);
            })
            ->when($stockStatus === 'out_of_stock', function ($query) {
                $query->where('product_batches.qty', '<=', 0);
            })
            ->when($expiryStatus === 'expired', function ($query) {
                $query->whereNotNull('product_batches.product_exp_date')
                    ->whereDate('product_batches.product_exp_date', '<', now()->toDateString());
            })
            ->when($expiryStatus === 'expiring_soon', function ($query) {
                $query->whereBetween('product_batches.product_exp_date', [
                    now()->toDateString(),
                    now()->addDays(90)->toDateString(),
                ]);
            })
            ->when($expiryStatus === 'valid', function ($query) {
                $query->whereDate('product_batches.product_exp_date', '>', now()->addDays(90)->toDateString());
            })
            ->when($expiryStatus === 'no_expiry', function ($query) {
                $query->whereNull('product_batches.product_exp_date');
            })
            ->orderBy($allowedSorts[$sortBy], $sortOrder)
            ->orderBy('product_stocks.variant')
            ->orderBy('product_batches.batch')
            ->paginate(25)
            ->withQueryString();

        $categories = Category::where('digital', 0)->orderBy('name')->get(['id', 'name']);
        $groups = Group::where('digital', 0)->orderBy('name')->get(['id', 'name']);
        $brands = Brand::orderBy('name')->get(['id', 'name']);

        $productsForFilter = $this->applyProductDetailProductFilters(
            Product::query()->whereHas('stocks.batches'),
            $categoryId,
            $groupId,
            $brandId
        )->orderBy('name')->get(['id', 'name']);

        $variants = collect();
        $batches = collect();
        if ($productId) {
            $variants = ProductStock::where('product_id', $productId)
                ->whereHas('batches')
                ->orderBy('variant')
                ->get(['id', 'variant', 'sku']);

            $batches = ProductBatch::where('product_id', $productId)
                ->when($variantId, function ($query) use ($variantId) {
                    $query->where('product_stock_id', $variantId);
                })
                ->orderBy('batch')
                ->get(['id', 'batch', 'product_stock_id']);
        }

        return view('backend.reports.product_detail_report', compact(
            'reportRows',
            'categories',
            'groups',
            'brands',
            'productsForFilter',
            'variants',
            'batches',
            'search',
            'categoryId',
            'groupId',
            'brandId',
            'productId',
            'variantId',
            'batchId',
            'publishedStatus',
            'stockStatus',
            'expiryStatus',
            'sortBy',
            'sortOrder'
        ));
    }

    public function in_house_sale_report(Request $request)
    {
        $sort_by = null;
        $products = Product::orderBy('num_of_sale', 'desc')->where('added_by', 'admin');
        if ($request->has('category_id')) {
            $sort_by = $request->category_id;
            $products = $products->where('category_id', $sort_by);
        }
        $products = $products->paginate(15);
        return view('backend.reports.in_house_sale_report', compact('products', 'sort_by'));
    }

    public function seller_sale_report(Request $request)
    {
        $sort_by = null;
        // $sellers = User::where('user_type', 'seller')->orderBy('created_at', 'desc');
        $sellers = Shop::with('user')->orderBy('created_at', 'desc');
        if ($request->has('verification_status')) {
            $sort_by = $request->verification_status;
            $sellers = $sellers->where('verification_status', $sort_by);
        }
        $sellers = $sellers->paginate(10);
        return view('backend.reports.seller_sale_report', compact('sellers', 'sort_by'));
    }

    public function wish_report(Request $request)
    {
        $sort_by = null;
        $products = Product::orderBy('created_at', 'desc');
        if ($request->has('category_id')) {
            $sort_by = $request->category_id;
            $products = $products->where('category_id', $sort_by);
        }
        $products = $products->paginate(10);
        return view('backend.reports.wish_report', compact('products', 'sort_by'));
    }

    public function user_search_report(Request $request)
    {
        $searches = Search::orderBy('count', 'desc')->paginate(10);
        return view('backend.reports.user_search_report', compact('searches'));
    }

    public function commission_history(Request $request)
    {
        $seller_id = null;
        $date_range = null;

        if (Auth::user()->user_type == 'seller') {
            $seller_id = Auth::user()->id;
        }
        if ($request->seller_id) {
            $seller_id = $request->seller_id;
        }

        $commission_history = CommissionHistory::orderBy('created_at', 'desc');

        if ($request->date_range) {
            $date_range = $request->date_range;
            $date_range1 = explode(" / ", $request->date_range);
            $commission_history = $commission_history->where('created_at', '>=', $date_range1[0]);
            $commission_history = $commission_history->where('created_at', '<=', $date_range1[1]);
        }
        if ($seller_id) {

            $commission_history = $commission_history->where('seller_id', '=', $seller_id);
        }

        $commission_history = $commission_history->paginate(10);
        if (Auth::user()->user_type == 'seller') {
            return view('seller.reports.commission_history_report', compact('commission_history', 'seller_id', 'date_range'));
        }
        return view('backend.reports.commission_history_report', compact('commission_history', 'seller_id', 'date_range'));
    }

    public function wallet_transaction_history(Request $request)
    {
        $user_id = null;
        $date_range = null;

        if ($request->user_id) {
            $user_id = $request->user_id;
        }

        $users_with_wallet = User::whereIn('id', function ($query) {
            $query->select('user_id')->from(with(new Wallet)->getTable());
        })->get();

        $wallet_history = Wallet::orderBy('created_at', 'desc');

        if ($request->date_range) {
            $date_range = $request->date_range;
            $date_range1 = explode(" / ", $request->date_range);
            $wallet_history = $wallet_history->where('created_at', '>=', $date_range1[0]);
            $wallet_history = $wallet_history->where('created_at', '<=', $date_range1[1]);
        }
        if ($user_id) {
            $wallet_history = $wallet_history->where('user_id', '=', $user_id);
        }

        $wallets = $wallet_history->paginate(10);

        return view('backend.reports.wallet_history_report', compact('wallets', 'users_with_wallet', 'user_id', 'date_range'));
    }


    public function getProductsByCategory(Request $request)
    {
        $categoryId = $request->input('category_id');

        $products = \App\Models\Product::query()
                        ->when($categoryId, function ($query) use ($categoryId) {
                            $query->where('category_id', $categoryId);
                        })
                        ->orderBy('created_at', 'desc')
                        ->get(['id', 'name']);

        $productsFormatted = $products->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->getTranslation('name'),
            ];
        });

        return response()->json([
            'products' => $productsFormatted
        ]);
    }

    public function getProductDetailFilterOptions(Request $request)
    {
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $groupId = $request->filled('group_id') ? (int) $request->input('group_id') : null;
        $brandId = $request->filled('brand_id') ? (int) $request->input('brand_id') : null;
        $productId = $request->filled('product_id') ? (int) $request->input('product_id') : null;
        $variantId = $request->filled('variant_id') ? (int) $request->input('variant_id') : null;

        $products = $this->applyProductDetailProductFilters(
            Product::query()->whereHas('stocks.batches'),
            $categoryId,
            $groupId,
            $brandId
        )
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->getTranslation('name'),
                ];
            });

        $variants = collect();
        $batches = collect();

        if ($productId && $products->contains('id', $productId)) {
            $variants = ProductStock::where('product_id', $productId)
                ->whereHas('batches')
                ->orderBy('variant')
                ->get(['id', 'variant', 'sku'])
                ->map(function ($variant) {
                    $name = trim((string) $variant->variant) ?: translate('Default');

                    if ($variant->sku) {
                        $name .= ' (' . $variant->sku . ')';
                    }

                    return [
                        'id' => $variant->id,
                        'name' => $name,
                    ];
                });

            $batches = ProductBatch::where('product_id', $productId)
                ->when($variantId, function ($query) use ($variantId) {
                    $query->where('product_stock_id', $variantId);
                })
                ->orderBy('batch')
                ->get(['id', 'batch'])
                ->map(function ($batch) {
                    return [
                        'id' => $batch->id,
                        'name' => trim((string) $batch->batch) ?: '-',
                    ];
                });
        }

        return response()->json([
            'products' => $products->values(),
            'variants' => $variants->values(),
            'batches' => $batches->values(),
        ]);
    }

    public function getStockFilterOptions(Request $request)
    {
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $groupId = $request->filled('group_id') ? (int) $request->input('group_id') : null;
        $brandId = $request->filled('brand_id') ? (int) $request->input('brand_id') : null;
        $productId = $request->filled('product_id') ? (int) $request->input('product_id') : null;
        $variantId = $request->filled('variant_id') ? (int) $request->input('variant_id') : null;

        $products = $this->applyProductDetailProductFilters(
            Product::query()->whereHas('stocks.batches'),
            $categoryId,
            $groupId,
            $brandId
        )
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->getTranslation('name'),
                ];
            });

        $variants = collect();
        $batches = collect();

        if ($productId && $products->contains('id', $productId)) {
            $variants = ProductStock::where('product_id', $productId)
                ->whereHas('batches')
                ->orderBy('variant')
                ->get(['id', 'variant', 'sku'])
                ->map(function ($variant) {
                    $name = trim((string) $variant->variant) ?: translate('Default');

                    if ($variant->sku) {
                        $name .= ' (' . $variant->sku . ')';
                    }

                    return [
                        'id' => $variant->id,
                        'name' => $name,
                    ];
                });

            $batches = ProductBatch::where('product_id', $productId)
                ->when($variantId, function ($query) use ($variantId) {
                    $query->where('product_stock_id', $variantId);
                })
                ->orderBy('batch')
                ->get(['id', 'batch'])
                ->map(function ($batch) {
                    return [
                        'id' => $batch->id,
                        'name' => trim((string) $batch->batch) ?: '-',
                    ];
                });
        }

        return response()->json([
            'products' => $products->values(),
            'variants' => $variants->values(),
            'batches' => $batches->values(),
        ]);
    }

    private function applyProductDetailProductFilters($query, ?int $categoryId, ?int $groupId, ?int $brandId)
    {
        return $query
            ->when($categoryId, function ($productQuery) use ($categoryId) {
                $productQuery->where(function ($categoryQuery) use ($categoryId) {
                    $categoryQuery
                        ->where('category_id', $categoryId)
                        ->orWhereHas('categories', function ($relationQuery) use ($categoryId) {
                            $relationQuery->where('categories.id', $categoryId);
                        });
                });
            })
            ->when($groupId, function ($productQuery) use ($groupId) {
                $productQuery->where(function ($groupQuery) use ($groupId) {
                    $groupQuery
                        ->where('group_id', $groupId)
                        ->orWhereHas('groups', function ($relationQuery) use ($groupId) {
                            $relationQuery->where('groups.id', $groupId);
                        });
                });
            })
            ->when($brandId, function ($productQuery) use ($brandId) {
                $productQuery->where('brand_id', $brandId);
            });
    }

    private function normalizeStockReportMonth($value, bool $useEndOfMonth = false): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            if ($useEndOfMonth) {
                return \Carbon\Carbon::createFromFormat('Y-m-d', $value . '-01')->endOfMonth()->toDateString();
            }

            return $value . '-01';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            if ($useEndOfMonth) {
                return \Carbon\Carbon::parse($value)->endOfMonth()->toDateString();
            }

            return $value;
        }

        return null;
    }

}
