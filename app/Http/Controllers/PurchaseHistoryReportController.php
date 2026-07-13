<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PurchaseHistory;
use App\Models\ProductStock;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use App\Models\PurchaseHistoryImport;
use App\Models\PurchaseHistoryExport;
use Illuminate\Support\Facades\Log;

class PurchaseHistoryReportController extends Controller
{
    /**
     * Display a listing of the purchase history records.
     */
    public function index(Request $request)
    {
        $query = PurchaseHistory::query()
            ->with([
                'customerDetails' => function ($customerQuery) {
                    $customerQuery
                        ->select([
                            'crm_id',
                            'user_id',
                            'company_name',
                            'post_business',
                            'city_id_business',
                            'district_business',
                            'state_id_business',
                            'pincode_business',
                            'country_id_business',
                        ])
                        ->with([
                            'user:id,name',
                            'businessCity:id,name',
                            'businessState:id,name',
                            'businessCountry:id,name',
                        ]);
                },
                'productStock' => function ($stockQuery) {
                    $stockQuery
                        ->select(['id', 'sku', 'product_id'])
                        ->with(['product' => function ($productQuery) {
                            $productQuery
                                ->select(['id', 'name', 'brand_id'])
                                ->without(['product_translations', 'taxes', 'thumbnail'])
                                ->with('brand:id,name');
                        }]);
                },
            ]);

        // Global search across key fields
        if ($search = $request->get('search')) {
            $like = '%' . trim($search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('serial_number', 'like', $like)
                    ->orWhere('order_number', 'like', $like)
                    ->orWhere('invoice_number', 'like', $like)
                    ->orWhere('product_sku', 'like', $like)
                    ->orWhere('sales_man_name', 'like', $like)
                    ->orWhere('state', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('ac_number', 'like', $like)
                    ->orWhereHas('customerDetails', function ($customerQuery) use ($like) {
                        $customerQuery->where('company_name', 'like', $like)
                            ->orWhereHas('user', function ($userQuery) use ($like) {
                                $userQuery->where('name', 'like', $like);
                            });
                    });
            });
        }

        $account = trim((string) $request->get('account', ''));
        if ($account !== '') {
            $prefixLike = $account . '%';
            $query->where(function ($q) use ($account, $prefixLike) {
                $q->where('ac_number', $account)
                    ->orWhere('ac_number', 'like', $prefixLike)
                    ->orWhereHas('customerDetails', function ($customerQuery) use ($account, $prefixLike) {
                        $customerQuery->where('crm_id', $account)
                            ->orWhere('crm_id', 'like', $prefixLike);
                    });
            });
        }

        // Optional filters
        $this->applyParsedDateFilter(
            $query,
            'purchase_history.order_date',
            $request->get('order_date_from'),
            $request->get('order_date_to')
        );
        if ($sku = $request->get('product_sku')) {
            $query->where('product_sku', $sku);
        }
        if ($productName = trim((string) $request->get('product_name', ''))) {
            $query->whereHas('productStock.product', function ($productQuery) use ($productName) {
                $productQuery->where('name', 'like', '%' . $productName . '%');
            });
        }
        if ($salesman = $request->get('sales_man_name')) {
            $query->where('sales_man_name', 'like', '%' . trim($salesman) . '%');
        }
        if ($serialNumber = trim((string) $request->get('serial_number', ''))) {
            $query->where('serial_number', 'like', $serialNumber . '%');
        }
        if ($orderNumber = trim((string) $request->get('order_number', ''))) {
            $query->where('order_number', 'like', $orderNumber . '%');
        }
        if ($invoiceNumber = trim((string) $request->get('invoice_number', ''))) {
            $query->where('invoice_number', 'like', $invoiceNumber . '%');
        }
        if ($salesmanCode = trim((string) $request->get('sales_man_code', ''))) {
            $query->where('sales_man_code', 'like', $salesmanCode . '%');
        }
        if ($lrNumber = trim((string) $request->get('lr_number', ''))) {
            $query->where('lr_number', 'like', $lrNumber . '%');
        }
        if ($state = trim((string) $request->get('state', ''))) {
            $query->where('state', 'like', $state . '%');
        }
        if ($city = trim((string) $request->get('city', ''))) {
            $query->where('city', 'like', $city . '%');
        }
        if ($district = trim((string) $request->get('district', ''))) {
            $query->where(function ($districtQuery) use ($district) {
                $districtQuery->where('district', 'like', $district . '%')
                    ->orWhereHas('customerDetails', function ($customerQuery) use ($district) {
                        $customerQuery->where('district_business', 'like', $district . '%');
                    });
            });
        }
        if ($transport = trim((string) $request->get('transport', ''))) {
            $query->where('transport', 'like', $transport . '%');
        }
        $this->applyParsedDateFilter(
            $query,
            'purchase_history.expiry_date',
            $request->get('expiry_date_from'),
            $request->get('expiry_date_to')
        );
        if ($partyName = trim((string) $request->get('party_name', ''))) {
            $query->whereHas('customerDetails', function ($customerQuery) use ($partyName) {
                $customerQuery->where('company_name', 'like', '%' . $partyName . '%');
            });
        }
        if ($userName = trim((string) $request->get('user_name', ''))) {
            $query->whereHas('customerDetails.user', function ($userQuery) use ($userName) {
                $userQuery->where('name', 'like', '%' . $userName . '%');
            });
        }

        $sortableColumns = [
            'sr_no' => 'id',
            'ac_number' => 'ac_number',
            'account_name' => 'account_name_sort',
            'party_name' => 'party_name_sort',
            'area' => 'area_sort',
            'town' => 'town_sort',
            'district' => 'district_sort',
            'state' => 'state_sort',
            'pincode' => 'pincode_sort',
            'country' => 'country_sort',
            'order_date' => 'order_date_sort',
            'order_number' => 'order_number',
            'sales_man_name' => 'sales_man_name_sort',
            'sales_man_code' => 'sales_man_code_sort',
            'invoice_date' => 'invoice_date_sort',
            'invoice_series' => 'invoice_series_sort',
            'invoice_number' => 'invoice_number',
            'product_sku' => 'product_sku',
            'product_name' => 'product_name_sort',
            'packing' => 'packing_sort',
            'batch_number' => 'batch_number',
            'expiry_date' => 'expiry_date_sort',
            'mfd_by' => 'mfd_by_sort',
            'quantity' => 'quantity',
            'free' => 'free',
            'total_quantity' => 'total_quantity',
            'sale_rate' => 'sale_rate_sort',
            'discount' => 'discount_sort',
            'mrp_rate' => 'mrp_rate_sort',
            'taxable_amount' => 'taxable_amount',
            'gst_amount' => 'gst_amount',
            'final_amount' => 'final_amount',
            'tax_code' => 'tax_code',
            'gst_percentage' => 'gst_percentage',
            'transport' => 'transport_sort',
            'book_to' => 'book_to_sort',
            'case_value' => 'case_sort',
            'lr_number' => 'lr_number_sort',
            'lr_date' => 'lr_date_sort',
            'late_by' => 'late_by',
        ];
        $sortBy = $request->get('sort_by', 'order_date');
        $sortDir = $request->get('sort_dir', 'desc');
        if (! array_key_exists($sortBy, $sortableColumns)) {
            $sortBy = 'order_date';
        }
        $sortDir = strtolower($sortDir);
        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $perPage = (int) $request->get('per_page', 25);
        if ($perPage <= 0 || $perPage > 200) {
            $perPage = 25;
        }

        $orderDateSql = $this->parsedDateSql('purchase_history.order_date');
        $lrDateSql = $this->parsedDateSql('purchase_history.lr_date');

        $purchaseHistory = $query
            ->leftJoin('user_details as customer_sort', 'customer_sort.crm_id', '=', 'purchase_history.ac_number')
            ->leftJoin('users as user_sort', 'user_sort.id', '=', 'customer_sort.user_id')
            ->leftJoin('cities as city_sort', 'city_sort.id', '=', 'customer_sort.city_id_business')
            ->leftJoin('states as state_sort', 'state_sort.id', '=', 'customer_sort.state_id_business')
            ->leftJoin('countries as country_sort', 'country_sort.id', '=', 'customer_sort.country_id_business')
            ->leftJoin('product_stocks as stock_sort', 'stock_sort.sku', '=', 'purchase_history.product_sku')
            ->leftJoin('products as product_sort', 'product_sort.id', '=', 'stock_sort.product_id')
            ->leftJoin('brands as brand_sort', 'brand_sort.id', '=', 'product_sort.brand_id')
            ->select([
                'purchase_history.ac_number',
                'purchase_history.order_number',
                'purchase_history.invoice_series',
                'purchase_history.invoice_number',
                'purchase_history.product_sku',
                'purchase_history.batch_number',
                'purchase_history.sale_rate',
                'purchase_history.discount',
                'purchase_history.mrp_rate',
                'purchase_history.tax_code',
                'purchase_history.gst_percentage',
            ])
            ->selectRaw('MIN(purchase_history.id) AS id')
            ->selectRaw('MIN(purchase_history.order_date) AS order_date')
            ->selectRaw('MIN(purchase_history.invoice_date) AS invoice_date')
            ->selectRaw('MIN(purchase_history.expiry_date) AS expiry_date')
            ->selectRaw('MIN(purchase_history.sales_man_name) AS sales_man_name')
            ->selectRaw('MIN(purchase_history.sales_man_code) AS sales_man_code')
            ->selectRaw('MIN(purchase_history.case_value) AS case_value')
            ->selectRaw('MIN(purchase_history.packing) AS packing')
            ->selectRaw('MIN(purchase_history.transport) AS transport')
            ->selectRaw('MIN(purchase_history.book_to) AS book_to')
            ->selectRaw("MIN(NULLIF(TRIM(purchase_history.lr_number), '')) AS lr_number")
            ->selectRaw("MIN(NULLIF(TRIM(purchase_history.lr_date), '')) AS lr_date")
            ->selectRaw('MIN(user_sort.name) AS account_name_sort')
            ->selectRaw('MIN(customer_sort.company_name) AS party_name_sort')
            ->selectRaw('MIN(customer_sort.post_business) AS area_sort')
            ->selectRaw('MIN(city_sort.name) AS town_sort')
            ->selectRaw('MIN(customer_sort.district_business) AS district_sort')
            ->selectRaw('MIN(state_sort.name) AS state_sort')
            ->selectRaw('MIN(customer_sort.pincode_business) AS pincode_sort')
            ->selectRaw('MIN(country_sort.name) AS country_sort')
            ->selectRaw('MIN(product_sort.name) AS product_name_sort')
            ->selectRaw("MIN({$orderDateSql}) AS order_date_sort")
            ->selectRaw("MIN({$this->parsedDateSql('purchase_history.invoice_date')}) AS invoice_date_sort")
            ->selectRaw('MIN(purchase_history.sales_man_name) AS sales_man_name_sort')
            ->selectRaw('MIN(purchase_history.sales_man_code) AS sales_man_code_sort')
            ->selectRaw('MIN(purchase_history.invoice_series) AS invoice_series_sort')
            ->selectRaw('MIN(purchase_history.packing) AS packing_sort')
            ->selectRaw("MIN({$this->parsedDateSql('purchase_history.expiry_date')}) AS expiry_date_sort")
            ->selectRaw('MIN(brand_sort.name) AS mfd_by_sort')
            ->selectRaw('MIN(CAST(NULLIF(REPLACE(purchase_history.sale_rate, \',\', \'\'), \'\') AS DECIMAL(20, 4))) AS sale_rate_sort')
            ->selectRaw('MIN(CAST(NULLIF(REPLACE(purchase_history.discount, \',\', \'\'), \'\') AS DECIMAL(20, 4))) AS discount_sort')
            ->selectRaw('MIN(CAST(NULLIF(REPLACE(purchase_history.mrp_rate, \',\', \'\'), \'\') AS DECIMAL(20, 4))) AS mrp_rate_sort')
            ->selectRaw('MIN(purchase_history.transport) AS transport_sort')
            ->selectRaw('MIN(purchase_history.book_to) AS book_to_sort')
            ->selectRaw('MIN(purchase_history.case_value) AS case_sort')
            ->selectRaw("MIN(NULLIF(TRIM(purchase_history.lr_number), '')) AS lr_number_sort")
            ->selectRaw("MIN({$this->parsedDateSql('purchase_history.lr_date')}) AS lr_date_sort")
            ->selectRaw("CASE WHEN MIN({$orderDateSql}) IS NULL OR MIN({$lrDateSql}) IS NULL THEN NULL ELSE DATEDIFF(MIN({$lrDateSql}), MIN({$orderDateSql})) END AS late_by")
            ->selectRaw($this->sumSql('quantity'))
            ->selectRaw($this->sumSql('free'))
            ->selectRaw('(' . $this->sumExpression('quantity') . ' + ' . $this->sumExpression('free') . ') AS total_quantity')
            ->selectRaw($this->sumSql('taxable_amount'))
            ->selectRaw($this->sumSql('gst_amount'))
            ->selectRaw($this->sumSql('final_amount'))
            ->groupBy([
                'purchase_history.ac_number',
                'purchase_history.order_number',
                'purchase_history.invoice_series',
                'purchase_history.invoice_number',
                'purchase_history.product_sku',
                'purchase_history.batch_number',
                'purchase_history.sale_rate',
                'purchase_history.discount',
                'purchase_history.mrp_rate',
                'purchase_history.tax_code',
                'purchase_history.gst_percentage',
            ])
            ->groupByRaw("CASE WHEN COALESCE(TRIM(purchase_history.order_number), '') = '' OR COALESCE(TRIM(purchase_history.invoice_number), '') = '' THEN purchase_history.id ELSE 0 END")
            ->orderBy($sortableColumns[$sortBy], $sortDir)
            ->orderBy('order_number')
            ->orderBy('invoice_number')
            ->orderBy('product_sku')
            ->orderBy('batch_number')
            ->paginate($perPage)
            ->appends($request->query());

        return view('backend.purchase_history.index', [
            'purchaseHistory' => $purchaseHistory,
            'search'          => $search ?? null,
            'sortBy'          => $sortBy,
            'sortDir'         => $sortDir,
        ]);
    }

    /**
     * Show the party purchase history as a bill-wise, product variant-wise summary.
     */
    public function consolidated(Request $request)
    {
        $account = trim((string) $request->get('account', ''));
        abort_if($account === '', 404);

        $customer = UserDetails::query()
            ->select([
                'crm_id',
                'user_id',
                'company_name',
                'post_business',
                'city_id_business',
                'district_business',
                'state_id_business',
                'pincode_business',
                'country_id_business',
                'prim_mobile_no_business',
                'prim_whats_app_no_business',
                'prim_mobile_no',
                'prim_whats_app_no',
            ])
            ->with([
                'user:id,name',
                'businessCity:id,name',
                'businessState:id,name',
                'businessCountry:id,name',
            ])
            ->where('crm_id', $account)
            ->first();

        $query = PurchaseHistory::query()
            ->leftJoin('product_stocks as stock_sort', 'stock_sort.sku', '=', 'purchase_history.product_sku')
            ->leftJoin('products as product_sort', 'product_sort.id', '=', 'stock_sort.product_id')
            ->where(function ($accountQuery) use ($account) {
                $accountQuery->where('purchase_history.ac_number', $account)
                    ->orWhereHas('customerDetails', function ($customerQuery) use ($account) {
                        $customerQuery->where('crm_id', $account);
                    });
            });

        $billDateSql = "COALESCE({$this->parsedDateSql('purchase_history.invoice_date')}, {$this->parsedDateSql('purchase_history.order_date')})";
        $billDateFrom = trim((string) $request->get('bill_date_from', ''));
        if ($billDateFrom === '') {
            $billDateFrom = trim((string) $request->get('order_date_from', ''));
        }
        $billDateTo = trim((string) $request->get('bill_date_to', ''));
        if ($billDateTo === '') {
            $billDateTo = trim((string) $request->get('order_date_to', ''));
        }

        $this->applyConsolidatedFilters($query, $request, $billDateSql, $billDateFrom, $billDateTo);

        $priceSortLabels = [
            'pts' => 'PTS',
            'ptr' => 'PTR',
            'ptd' => 'PTD',
            'govt' => 'Govt.',
            'export' => 'Exp',
            'customer_price' => 'Customer',
            'current_mrp' => 'M.R.P',
        ];
        $sortableColumns = [
            'sr_no' => 'id',
            'bill_date' => 'bill_date_sort',
            'bill_series' => 'purchase_history.invoice_series',
            'bill_number' => 'purchase_history.invoice_number',
            'product_sku' => 'purchase_history.product_sku',
            'product_name' => 'product_name',
            'packing' => 'purchase_history.packing',
            'quantity' => 'quantity',
            'sale_rate' => 'purchase_history.sale_rate',
            'gst_amount' => 'gst_amount',
            'mrp_rate' => 'purchase_history.mrp_rate',
            'gross_amount' => 'gross_amount',
        ];
        $sortAliases = [
            'invoice_series' => 'bill_series',
            'sku' => 'product_sku',
            'product' => 'product_name',
            'pack' => 'packing',
        ];
        $sortBy = (string) $request->get('sort_by', 'bill_series');
        $sortBy = $sortAliases[$sortBy] ?? $sortBy;
        if (! array_key_exists($sortBy, $sortableColumns) && ! array_key_exists($sortBy, $priceSortLabels)) {
            $sortBy = 'bill_series';
        }
        $sortDir = strtolower((string) $request->get('sort_dir', 'desc'));
        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $dateBounds = (clone $query)
            ->selectRaw("MIN({$billDateSql}) AS date_from")
            ->selectRaw("MAX({$billDateSql}) AS date_to")
            ->first();

        $grossAmountExpression = $this->sumExpression('final_amount');

        $reportQuery = $query
            ->select([
                'purchase_history.invoice_date',
                'purchase_history.invoice_series',
                'purchase_history.invoice_number',
                'purchase_history.product_sku',
                'purchase_history.packing',
                'purchase_history.sale_rate',
                'purchase_history.tax_code',
                'purchase_history.gst_percentage',
                'purchase_history.mrp_rate',
            ])
            ->selectRaw('MIN(purchase_history.id) AS id')
            ->selectRaw("COALESCE(MIN(NULLIF(TRIM(purchase_history.invoice_date), '')), MIN(NULLIF(TRIM(purchase_history.order_date), ''))) AS bill_date")
            ->selectRaw("MIN({$billDateSql}) AS bill_date_sort")
            ->selectRaw('MIN(product_sort.name) AS product_name')
            ->selectRaw('MIN(stock_sort.variant) AS product_variant')
            ->selectRaw('MIN(stock_sort.id_variant) AS product_variant_id')
            ->selectRaw("MIN(NULLIF(TRIM(purchase_history.batch_number), '')) AS batch_number")
            ->selectRaw("COUNT(DISTINCT NULLIF(TRIM(purchase_history.batch_number), '')) AS batch_count")
            ->selectRaw($this->sumSql('quantity'))
            ->selectRaw($this->sumSql('free'))
            ->selectRaw($this->sumSql('gst_amount'))
            ->selectRaw('(' . $this->sumExpression('quantity') . ' + ' . $this->sumExpression('free') . ') AS total_quantity')
            ->selectRaw("{$grossAmountExpression} AS gross_amount")
            ->groupBy([
                'purchase_history.invoice_date',
                'purchase_history.invoice_series',
                'purchase_history.invoice_number',
                'purchase_history.product_sku',
                'purchase_history.packing',
                'purchase_history.sale_rate',
                'purchase_history.tax_code',
                'purchase_history.gst_percentage',
                'purchase_history.mrp_rate',
            ])
            ->groupByRaw("CASE WHEN COALESCE(TRIM(purchase_history.invoice_number), '') = '' THEN purchase_history.id ELSE 0 END");

        $databaseSortColumn = $sortableColumns[$sortBy] ?? 'purchase_history.invoice_series';
        $reportQuery->orderBy($databaseSortColumn, isset($priceSortLabels[$sortBy]) ? 'desc' : $sortDir);

        foreach ([
            'bill_date_sort',
            'purchase_history.invoice_series',
            'purchase_history.invoice_number',
            'product_name',
            'purchase_history.product_sku',
            'purchase_history.packing',
        ] as $tieBreaker) {
            if ($tieBreaker !== $databaseSortColumn) {
                $reportQuery->orderBy($tieBreaker);
            }
        }

        $reportRows = $reportQuery->get();
        $currentPriceMap = $this->buildCurrentPriceMap($reportRows);

        if (isset($priceSortLabels[$sortBy])) {
            $priceLabel = $priceSortLabels[$sortBy];
            $priceValue = function ($row) use ($currentPriceMap, $priceLabel) {
                $currentSkuPrice = $currentPriceMap[$row->product_sku] ?? null;
                $lines = [];
                if ($currentSkuPrice) {
                    $batchNumber = trim((string) ($row->batch_number ?? ''));
                    $usesSingleBatch = (int) ($row->batch_count ?? 0) === 1;
                    $lines = ($usesSingleBatch && $batchNumber !== '')
                        ? ($currentSkuPrice['batches'][$batchNumber] ?? $currentSkuPrice['default'])
                        : $currentSkuPrice['default'];
                }
                $line = collect($lines)->firstWhere('label', $priceLabel);

                return (float) preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) ($line['value'] ?? 0)));
            };
            $reportRows = ($sortDir === 'asc' ? $reportRows->sortBy($priceValue) : $reportRows->sortByDesc($priceValue))->values();
        }

        $contactNumbers = collect([
            $customer?->prim_mobile_no_business,
            $customer?->prim_whats_app_no_business,
            $customer?->prim_mobile_no,
            $customer?->prim_whats_app_no,
        ])->filter(fn ($value) => filled($value))->unique()->values();

        return view('backend.purchase_history.consolidated', [
            'account'        => $account,
            'customer'       => $customer,
            'contactNumbers' => $contactNumbers,
            'dateFrom'       => $this->formatReportDate($billDateFrom) ?: $this->formatReportDate($dateBounds?->date_from),
            'dateTo'         => $this->formatReportDate($billDateTo) ?: $this->formatReportDate($dateBounds?->date_to),
            'filterBillDateFrom' => $billDateFrom,
            'filterBillDateTo'   => $billDateTo,
            'reportRows'     => $reportRows,
            'currentPriceMap' => $currentPriceMap,
            'sortBy'         => $sortBy,
            'sortDir'        => $sortDir,
        ]);
    }

    /**
     * Show all customers and bills for a single product SKU.
     */
    public function consolidatedProductwise(Request $request)
    {
        $sku = trim((string) $request->get('product_sku', ''));
        abort_if($sku === '', 404);

        $query = PurchaseHistory::query()
            ->leftJoin('user_details as customer', 'customer.crm_id', '=', 'purchase_history.ac_number')
            ->leftJoin('users as customer_user', 'customer_user.id', '=', 'customer.user_id')
            ->leftJoin('cities as customer_city', 'customer_city.id', '=', 'customer.city_id_business')
            ->leftJoin('states as customer_state', 'customer_state.id', '=', 'customer.state_id_business')
            ->leftJoin('countries as customer_country', 'customer_country.id', '=', 'customer.country_id_business')
            ->leftJoin('product_stocks as stock', 'stock.sku', '=', 'purchase_history.product_sku')
            ->leftJoin('products as product', 'product.id', '=', 'stock.product_id')
            ->leftJoin('brands as brand', 'brand.id', '=', 'product.brand_id')
            ->where('purchase_history.product_sku', $sku);

        $billDateSql = "COALESCE({$this->parsedDateSql('purchase_history.invoice_date')}, {$this->parsedDateSql('purchase_history.order_date')})";
        $dateFrom = trim((string) $request->get('bill_date_from', ''));
        $dateTo = trim((string) $request->get('bill_date_to', ''));
        $this->applyParsedDateExpressionFilter($query, $billDateSql, $dateFrom, $dateTo);

        if ($request->filled('account')) {
            $query->where('purchase_history.ac_number', 'like', '%' . trim((string) $request->account) . '%');
        }
        if ($request->filled('customer')) {
            $customer = trim((string) $request->customer);
            $query->where(function ($customerQuery) use ($customer) {
                $customerQuery->where('customer.company_name', 'like', "%{$customer}%")
                    ->orWhere('customer_user.name', 'like', "%{$customer}%");
            });
        }

        $dateBounds = (clone $query)
            ->selectRaw("MIN({$billDateSql}) AS date_from")
            ->selectRaw("MAX({$billDateSql}) AS date_to")
            ->first();

        $sortableColumns = [
            'sr_no' => 'id',
            'bill_date' => 'bill_date_sort',
            'invoice_series' => 'purchase_history.invoice_series',
            'invoice_number' => 'purchase_history.invoice_number',
            'ac_number' => 'purchase_history.ac_number',
            'company_name' => 'company_name',
            'customer_name' => 'customer_name',
            'district' => 'district_sort',
            'state' => 'state',
            'pincode' => 'pincode',
            'country' => 'country',
            'mobile' => 'mobile',
            'alternate_mobile' => 'alternate_mobile',
            'whatsapp' => 'whatsapp',
            'email' => 'email',
            'quantity' => 'quantity',
            'batch_number' => 'purchase_history.batch_number',
            'expiry_date' => 'expiry_date_sort',
            'manufacturer' => 'manufacturer',
            'sale_rate' => 'purchase_history.sale_rate',
            'gst_percentage' => 'purchase_history.gst_percentage',
            'gst_amount' => 'gst_amount',
            'mrp_rate' => 'purchase_history.mrp_rate',
            'final_amount' => 'final_amount',
        ];
        $sortBy = (string) $request->get('sort_by', 'invoice_series');
        if (! array_key_exists($sortBy, $sortableColumns) && ! array_key_exists($sortBy, $priceSortLabels)) {
            $sortBy = 'invoice_series';
        }
        $sortDir = strtolower((string) $request->get('sort_dir', 'desc'));
        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $rowsQuery = $query
            ->select([
                'purchase_history.ac_number',
                'purchase_history.invoice_date',
                'purchase_history.invoice_series',
                'purchase_history.invoice_number',
                'purchase_history.product_sku',
                'purchase_history.batch_number',
                'purchase_history.expiry_date',
                'purchase_history.sale_rate',
                'purchase_history.gst_percentage',
                'purchase_history.mrp_rate',
            ])
            ->selectRaw('MIN(purchase_history.id) AS id')
            ->selectRaw("COALESCE(MIN(NULLIF(TRIM(purchase_history.invoice_date), '')), MIN(NULLIF(TRIM(purchase_history.order_date), ''))) AS bill_date")
            ->selectRaw("MIN({$billDateSql}) AS bill_date_sort")
            ->selectRaw("MIN({$this->parsedDateSql('purchase_history.expiry_date')}) AS expiry_date_sort")
            ->selectRaw('MIN(customer.company_name) AS company_name')
            ->selectRaw('MIN(customer_user.name) AS customer_name')
            ->selectRaw('MIN(customer.district_business) AS district')
            ->selectRaw('MIN(customer_city.name) AS city')
            ->selectRaw("MIN(COALESCE(NULLIF(TRIM(customer.district_business), ''), customer_city.name)) AS district_sort")
            ->selectRaw('MIN(customer_state.name) AS state')
            ->selectRaw('MIN(customer.pincode_business) AS pincode')
            ->selectRaw('MIN(customer_country.name) AS country')
            ->selectRaw('MIN(customer.prim_mobile_no_business) AS mobile')
            ->selectRaw('MIN(customer.alt_mobile_no_business) AS alternate_mobile')
            ->selectRaw('MIN(customer.prim_whats_app_no_business) AS whatsapp')
            ->selectRaw('MIN(customer.prim_email_business) AS email')
            ->selectRaw('MIN(product.name) AS product_name')
            ->selectRaw('MIN(stock.variant) AS product_variant')
            ->selectRaw('MIN(brand.name) AS manufacturer')
            ->selectRaw($this->sumSql('quantity'))
            ->selectRaw($this->sumSql('gst_amount'))
            ->selectRaw($this->sumSql('final_amount'))
            ->groupBy([
                'purchase_history.ac_number',
                'purchase_history.invoice_date',
                'purchase_history.invoice_series',
                'purchase_history.invoice_number',
                'purchase_history.product_sku',
                'purchase_history.batch_number',
                'purchase_history.expiry_date',
                'purchase_history.sale_rate',
                'purchase_history.gst_percentage',
                'purchase_history.mrp_rate',
            ])
            ->groupByRaw("CASE WHEN COALESCE(TRIM(purchase_history.invoice_number), '') = '' THEN purchase_history.id ELSE 0 END");

        $databaseSortColumn = $sortableColumns[$sortBy] ?? 'purchase_history.invoice_series';
        $rowsQuery->orderBy($databaseSortColumn, isset($priceSortLabels[$sortBy]) ? 'desc' : $sortDir);

        foreach (['bill_date_sort', 'purchase_history.invoice_series', 'purchase_history.invoice_number', 'id'] as $tieBreaker) {
            if ($tieBreaker !== $databaseSortColumn) {
                $rowsQuery->orderBy($tieBreaker);
            }
        }

        $rows = $rowsQuery->get();
        $currentPriceMap = $this->buildCurrentPriceMap($rows);

        if (isset($priceSortLabels[$sortBy])) {
            $priceLabel = $priceSortLabels[$sortBy];
            $priceValue = function ($row) use ($currentPriceMap, $priceLabel) {
                $price = $currentPriceMap[$row->product_sku] ?? null;
                $lines = $price ? ($price['batches'][$row->batch_number] ?? $price['default']) : [];
                $line = collect($lines)->firstWhere('label', $priceLabel);

                return (float) preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) ($line['value'] ?? 0)));
            };
            $rows = ($sortDir === 'asc' ? $rows->sortBy($priceValue) : $rows->sortByDesc($priceValue))->values();
        }

        return view('backend.purchase_history.consolidated_productwise', [
            'sku' => $sku,
            'rows' => $rows,
            'currentPriceMap' => $currentPriceMap,
            'dateFrom' => $this->formatReportDate($dateFrom) ?: $this->formatReportDate($dateBounds?->date_from),
            'dateTo' => $this->formatReportDate($dateTo) ?: $this->formatReportDate($dateBounds?->date_to),
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
        ]);
    }

    /**
     * Show the detail view for a single record (for modal).
     */
    public function show($id)
    {
        $record = PurchaseHistory::with([
            'customerDetails.user',
            'productStock.product.brand',
        ])->findOrFail($id);

        return view('backend.purchase_history.partials.detail', [
            'record' => $record,
        ]);
    }

    /**
     * Show the form for editing a record.
     */
    public function edit($id)
    {
        $record = PurchaseHistory::with([
            'customerDetails.user',
            'productStock.product.brand',
        ])->findOrFail($id);

        return view('backend.purchase_history.edit', [
            'record' => $record,
        ]);
    }

    /**
     * Update the specified record in storage.
     */
    public function update(Request $request, $id)
    {
        $record = PurchaseHistory::findOrFail($id);

        $validated = $request->validate([
            'serial_number' => ['nullable', 'string', 'max:255'],
            'order_date' => ['nullable', 'string', 'max:255'],
            'order_number' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'ac_number' => ['nullable', 'string', 'max:255'],
            'product_sku' => ['nullable', 'string', 'max:255'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'regex:/^\d+(\.\d+)?$/'],
            'free' => ['nullable', 'regex:/^\d+(\.\d+)?$/'],
            'sale_rate' => ['nullable', 'regex:/^\d+(\.\d+)?$/'],
            'mrp_rate' => ['nullable', 'regex:/^\d+(\.\d+)?$/'],
            'discount' => ['nullable', 'regex:/^\d+(\.\d+)?$/'],
            'taxable_amount' => ['nullable', 'regex:/^\d+(\.\d+)?$/'],
            'gst_percentage' => ['nullable', 'regex:/^\d+(\.\d+)?$/'],
            'gst_amount' => ['nullable', 'regex:/^\d+(\.\d+)?$/'],
            'final_amount' => ['nullable', 'regex:/^\d+(\.\d+)?$/'],
            'sales_man_name' => ['nullable', 'string', 'max:255'],
            'sales_man_code' => ['nullable', 'string', 'max:255'],
            'case_value' => ['nullable', 'string', 'max:255'],
            'packing' => ['nullable', 'string', 'max:255'],
            'transport' => ['nullable', 'string', 'max:255'],
            'book_to' => ['nullable', 'string', 'max:255'],
            'lr_number' => ['nullable', 'string', 'max:255'],
            'lr_date' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'max:255'],
        ]);

        $record->update($validated);

        flash(translate('Purchase history record updated successfully'))->success();

        return redirect()->route('admin.purchase_history.index');
    }

    /**
     * Remove the specified record from storage.
     */
    public function destroy(Request $request, $id)
    {
        $record = PurchaseHistory::findOrFail($id);
        $record->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        flash(translate('Purchase history record deleted successfully'))->success();

        return redirect()->route('admin.purchase_history.index');
    }

    /**
     * Import purchase history from an uploaded CSV/Excel file.
     */
    public function import(Request $request)
    {
        Log::info('PurchaseHistoryReport import called', [
            'user_id' => optional($request->user())->id,
            'path'    => $request->path(),
            'method'  => $request->method(),
        ]);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        if (! $request->hasFile('file')) {
            Log::warning('PurchaseHistoryReport import: no file present after validation');
            flash(translate('No file was uploaded. Please choose a CSV or Excel file.'))->error();
            return back();
        }

        try {
            $file = $request->file('file');
            $import = new PurchaseHistoryImport();
            Excel::import($import, $file);

            $rows = method_exists($import, 'getRowCount') ? $import->getRowCount() : null;
            $errorCount = method_exists($import, 'getErrorCount') ? $import->getErrorCount() : null;

            Log::info('PurchaseHistoryReport import completed', [
                'rows_imported' => $rows,
                'errors'        => $errorCount,
            ]);

            // Expose error-log presence to the index view via session, instead of embedding HTML in flash
            $errorFile = method_exists($import, 'getErrorFilePath') ? $import->getErrorFilePath() : null;
            if ($errorFile) {
                session()->flash('purchase_history_error_log_available', true);
            }

            if ($rows !== null && $rows > 0) {
                flash(
                    translate('Purchase History imported successfully. Rows imported: ') . $rows
                )->success();
            } else {
                flash(
                    translate('File processed but no rows were imported. Please check header names and data.')
                )->warning();
            }
        } catch (ValidationException $e) {
            $messages = [];
            foreach ($e->failures() as $failure) {
                $row = $failure->row(); // row number
                $attr = $failure->attribute(); // column name
                foreach ($failure->errors() as $error) {
                    $messages[] = "Row {$row}, {$attr}: {$error}";
                }
            }

            Log::warning('PurchaseHistoryReport validation failed', [
                'errors' => $messages,
            ]);

            flash(translate('Failed to import Purchase History. Please correct these issues:') . '<br>' . implode('<br>', $messages))
                ->error();
        } catch (\Throwable $e) {
            $message = $e->getMessage();

            // Graceful handling for problematic legacy .xls encodings that PhpSpreadsheet cannot read
            if ($file->getClientOriginalExtension() === 'xls'
                && str_contains($message, 'PhpSpreadsheet\\Shared\\StringHelper::convertEncoding')
            ) {
                Log::warning('PurchaseHistoryReport import failed for legacy XLS encoding', [
                    'message' => $message,
                ]);

                flash(
                    translate('This XLS file uses an unsupported legacy text encoding. ') .
                    translate('Please open it in Excel or LibreOffice, save it as XLSX or CSV, and then import that new file.')
                )->error();

                return back();
            }

            Log::error('PurchaseHistoryReport import failed', [
                'message' => $message,
                'trace'   => substr($e->getTraceAsString(), 0, 1000),
            ]);
            report($e);
            flash(translate('Failed to import party wise sheets. Please check the file format and data. Error: ') . $message)->error();
        }

        return back();
    }

    /**
     * Export purchase history data to Excel with current filters.
     */
    public function export(Request $request)
    {
        $export = new PurchaseHistoryExport(
            $request->get('search'),
            $request->get('order_date_from'),
            $request->get('order_date_to'),
            $request->get('product_sku'),
            $request->get('sales_man_name'),
            $request->get('account'),
            $request->only([
                'serial_number',
                'order_number',
                'invoice_number',
                'sales_man_code',
                'lr_number',
                'state',
                'city',
                'district',
                'transport',
                'product_name',
                'expiry_date_from',
                'expiry_date_to',
                'party_name',
                'user_name',
            ])
        );

        if ($request->get('format') === 'csv') {
            return $this->streamPurchaseHistoryCsv($export);
        }

        $rowCount = DB::query()
            ->fromSub($export->query()->toBase(), 'purchase_history_export')
            ->count();

        if ($rowCount > PurchaseHistoryExport::XLSX_SAFE_ROW_LIMIT) {
            return $this->streamPurchaseHistoryCsv($export);
        }

        return Excel::download($export, 'purchase_history.xlsx');
    }

    private function streamPurchaseHistoryCsv(PurchaseHistoryExport $export)
    {
        return response()->streamDownload(function () use ($export) {
                if (function_exists('set_time_limit')) {
                    set_time_limit(0);
                }

                $output = fopen('php://output', 'wb');

                // UTF-8 BOM makes Excel display non-English names correctly.
                fwrite($output, "\xEF\xBB\xBF");
                fputcsv($output, $export->headings());

                $export->query()->chunk(5000, function ($records) use ($export, $output) {
                    foreach ($records as $record) {
                        fputcsv($output, $export->map($record));
                    }

                    fflush($output);
                });

                fclose($output);
        }, 'purchase_history.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function parsedDateSql(string $column): string
    {
        return "COALESCE(STR_TO_DATE(NULLIF(TRIM({$column}), ''), '%Y-%m-%d'), STR_TO_DATE(NULLIF(TRIM({$column}), ''), '%d-%m-%Y'), STR_TO_DATE(NULLIF(TRIM({$column}), ''), '%d/%m/%Y'))";
    }

    private function applyParsedDateFilter($query, string $column, $from, $to): void
    {
        $dateSql = $this->parsedDateSql($column);

        $this->applyParsedDateExpressionFilter($query, $dateSql, $from, $to);
    }

    private function applyParsedDateExpressionFilter($query, string $dateSql, $from, $to): void
    {
        if ($normalizedFrom = $this->normalizeDateInput($from)) {
            $query->whereRaw("{$dateSql} >= STR_TO_DATE(?, '%Y-%m-%d')", [$normalizedFrom]);
        }

        if ($normalizedTo = $this->normalizeDateInput($to)) {
            $query->whereRaw("{$dateSql} <= STR_TO_DATE(?, '%Y-%m-%d')", [$normalizedTo]);
        }
    }

    private function applyConsolidatedFilters($query, Request $request, string $billDateSql, string $billDateFrom, string $billDateTo): void
    {
        if ($search = $request->get('search')) {
            $like = '%' . trim($search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('purchase_history.serial_number', 'like', $like)
                    ->orWhere('purchase_history.order_number', 'like', $like)
                    ->orWhere('purchase_history.invoice_number', 'like', $like)
                    ->orWhere('purchase_history.product_sku', 'like', $like)
                    ->orWhere('purchase_history.sales_man_name', 'like', $like)
                    ->orWhere('purchase_history.state', 'like', $like)
                    ->orWhere('purchase_history.city', 'like', $like)
                    ->orWhereHas('customerDetails', function ($customerQuery) use ($like) {
                        $customerQuery->where('company_name', 'like', $like)
                            ->orWhereHas('user', function ($userQuery) use ($like) {
                                $userQuery->where('name', 'like', $like);
                            });
                    });
            });
        }

        $this->applyParsedDateExpressionFilter(
            $query,
            $billDateSql,
            $billDateFrom,
            $billDateTo
        );

        if ($sku = trim((string) $request->get('product_sku', ''))) {
            $query->where('purchase_history.product_sku', $sku);
        }
        if ($productName = trim((string) $request->get('product_name', ''))) {
            $query->where('product_sort.name', 'like', '%' . $productName . '%');
        }
        if ($salesman = trim((string) $request->get('sales_man_name', ''))) {
            $query->where('purchase_history.sales_man_name', 'like', '%' . $salesman . '%');
        }
        if ($serialNumber = trim((string) $request->get('serial_number', ''))) {
            $query->where('purchase_history.serial_number', 'like', $serialNumber . '%');
        }
        if ($orderNumber = trim((string) $request->get('order_number', ''))) {
            $query->where('purchase_history.order_number', 'like', $orderNumber . '%');
        }
        if ($invoiceNumber = trim((string) $request->get('invoice_number', ''))) {
            $query->where('purchase_history.invoice_number', 'like', $invoiceNumber . '%');
        }
        if ($salesmanCode = trim((string) $request->get('sales_man_code', ''))) {
            $query->where('purchase_history.sales_man_code', 'like', $salesmanCode . '%');
        }
        if ($lrNumber = trim((string) $request->get('lr_number', ''))) {
            $query->where('purchase_history.lr_number', 'like', $lrNumber . '%');
        }
        if ($state = trim((string) $request->get('state', ''))) {
            $query->where('purchase_history.state', 'like', $state . '%');
        }
        if ($city = trim((string) $request->get('city', ''))) {
            $query->where('purchase_history.city', 'like', $city . '%');
        }
        if ($district = trim((string) $request->get('district', ''))) {
            $query->where(function ($districtQuery) use ($district) {
                $districtQuery->where('purchase_history.district', 'like', $district . '%')
                    ->orWhereHas('customerDetails', function ($customerQuery) use ($district) {
                        $customerQuery->where('district_business', 'like', $district . '%');
                    });
            });
        }
        if ($transport = trim((string) $request->get('transport', ''))) {
            $query->where('purchase_history.transport', 'like', $transport . '%');
        }

        $this->applyParsedDateFilter(
            $query,
            'purchase_history.expiry_date',
            $request->get('expiry_date_from'),
            $request->get('expiry_date_to')
        );

        if ($partyName = trim((string) $request->get('party_name', ''))) {
            $query->whereHas('customerDetails', function ($customerQuery) use ($partyName) {
                $customerQuery->where('company_name', 'like', '%' . $partyName . '%');
            });
        }
        if ($userName = trim((string) $request->get('user_name', ''))) {
            $query->whereHas('customerDetails.user', function ($userQuery) use ($userName) {
                $userQuery->where('name', 'like', '%' . $userName . '%');
            });
        }
    }

    private function formatReportDate($value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        foreach (['Y-m-d H:i:s', 'Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('d-m-Y');
            }
        }

        return $value;
    }

    private function normalizeDateInput($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function sumSql(string $column): string
    {
        return $this->sumExpression($column) . " AS {$column}";
    }

    private function sumExpression(string $column): string
    {
        return "COALESCE(SUM(CAST(NULLIF(REPLACE(purchase_history.{$column}, ',', ''), '') AS DECIMAL(20, 4))), 0)";
    }

    private function buildCurrentPriceMap($reportRows): array
    {
        $skus = $reportRows
            ->pluck('product_sku')
            ->filter(fn ($sku) => filled($sku))
            ->map(fn ($sku) => (string) $sku)
            ->unique()
            ->values();

        if ($skus->isEmpty()) {
            return [];
        }

        $stocks = ProductStock::query()
            ->select(['id', 'product_id', 'sku', 'price', 'mrp_price'])
            ->with([
                'product' => function ($productQuery) {
                    $productQuery
                        ->select(['id', 'role_price', 'mrp_price', 'unit_price'])
                        ->without(['product_translations', 'taxes', 'thumbnail']);
                },
                'batches' => function ($batchQuery) {
                    $batchQuery
                        ->select(['id', 'product_id', 'product_stock_id', 'batch', 'mrp_price', 'role_price', 'qty', 'product_exp_date'])
                        ->orderByDesc('id');
                },
            ])
            ->whereIn('sku', $skus)
            ->get();

        $priceMap = [];

        foreach ($stocks as $stock) {
            $batches = $stock->batches ?? collect();
            $batchMap = [];

            foreach ($batches->groupBy(fn ($batch) => trim((string) $batch->batch)) as $batchNumber => $batchGroup) {
                if ($batchNumber === '') {
                    continue;
                }

                $batchMap[$batchNumber] = $this->formatCurrentPriceLines($batchGroup, $stock);
            }

            $priceMap[(string) $stock->sku] = [
                'default' => $this->formatCurrentPriceLines($batches, $stock),
                'batches' => $batchMap,
            ];
        }

        return $priceMap;
    }

    private function formatCurrentPriceLines($batches, ProductStock $stock): array
    {
        $roles = [
            'pts' => 'PTS',
            'ptr' => 'PTR',
            'ptd' => 'PTD',
            'gov' => 'Govt.',
            'expo' => 'Exp',
            'customer' => 'Customer',
        ];

        $roleValues = collect(array_keys($roles))->mapWithKeys(fn ($role) => [$role => collect()]);
        $mrpValues = collect();

        foreach ($batches as $batch) {
            foreach ($this->decodeRolePrices($batch->role_price) as $role => $price) {
                if ($roleValues->has($role) && ($numericPrice = $this->normalizePrice($price)) !== null) {
                    $roleValues[$role]->push($numericPrice);
                }
            }

            if (($mrpPrice = $this->normalizePrice($batch->mrp_price)) !== null) {
                $mrpValues->push($mrpPrice);
            }
        }

        $productRolePrices = $this->decodeRolePrices($stock->product?->role_price);
        foreach ($roles as $role => $label) {
            if ($roleValues[$role]->isEmpty()
                && array_key_exists($role, $productRolePrices)
                && ($numericPrice = $this->normalizePrice($productRolePrices[$role])) !== null
            ) {
                $roleValues[$role]->push($numericPrice);
            }
        }

        if ($roleValues['pts']->isEmpty() && ($stockPrice = $this->normalizePrice($stock->price)) !== null) {
            $roleValues['pts']->push($stockPrice);
        }

        foreach ([$stock->mrp_price, $stock->product?->mrp_price, $stock->product?->unit_price] as $fallbackMrp) {
            if ($mrpValues->isEmpty() && ($numericPrice = $this->normalizePrice($fallbackMrp)) !== null) {
                $mrpValues->push($numericPrice);
            }
        }

        $hasAnyPrice = $mrpValues->isNotEmpty()
            || $roleValues->contains(fn ($values) => $values->isNotEmpty());

        if (! $hasAnyPrice) {
            return [];
        }

        $lines = [];
        foreach ($roles as $role => $label) {
            $lines[] = [
                'label' => $label,
                'value' => $this->formatPriceRange($roleValues[$role]),
            ];
        }

        $lines[] = [
            'label' => 'M.R.P',
            'value' => $this->formatPriceRange($mrpValues),
        ];

        return $lines;
    }

    private function decodeRolePrices($rolePrices): array
    {
        if (is_array($rolePrices)) {
            return $rolePrices;
        }

        if (! filled($rolePrices)) {
            return [];
        }

        $decoded = json_decode((string) $rolePrices, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizePrice($value): ?float
    {
        $value = trim(str_replace(',', '', (string) $value));

        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function formatPriceRange($values): string
    {
        $prices = collect($values)
            ->map(fn ($value) => $this->normalizePrice($value))
            ->filter(fn ($value) => $value !== null)
            ->unique(fn ($value) => number_format($value, 2, '.', ''))
            ->sort()
            ->values();

        if ($prices->isEmpty()) {
            return '-';
        }

        $minimum = $prices->first();
        $maximum = $prices->last();

        if ($minimum === $maximum) {
            return number_format($minimum, 2, '.', '');
        }

        return number_format($minimum, 2, '.', '') . ' - ' . number_format($maximum, 2, '.', '');
    }
}
