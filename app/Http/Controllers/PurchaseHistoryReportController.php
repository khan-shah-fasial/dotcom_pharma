<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PurchaseHistory;
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
            $like = '%' . $account . '%';
            $query->where(function ($q) use ($like) {
                $q->where('ac_number', 'like', $like)
                    ->orWhereHas('customerDetails', function ($customerQuery) use ($like) {
                        $customerQuery->where('crm_id', 'like', $like)
                            ->orWhere('company_name', 'like', $like)
                            ->orWhereHas('user', function ($userQuery) use ($like) {
                                $userQuery->where('name', 'like', $like);
                            });
                    });
            });
        }

        // Optional filters
        if ($orderDateFrom = $request->get('order_date_from')) {
            $query->where('order_date', '>=', $orderDateFrom);
        }
        if ($orderDateTo = $request->get('order_date_to')) {
            $query->where('order_date', '<=', $orderDateTo);
        }
        if ($sku = $request->get('product_sku')) {
            $query->where('product_sku', $sku);
        }
        if ($salesman = $request->get('sales_man_name')) {
            $query->where('sales_man_name', 'like', '%' . trim($salesman) . '%');
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
            'sales_man' => 'sales_man_sort',
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
            ->selectRaw('MIN(purchase_history.lr_number) AS lr_number')
            ->selectRaw('MIN(purchase_history.lr_date) AS lr_date')
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
            ->selectRaw('MIN(COALESCE(NULLIF(purchase_history.sales_man_code, \'\'), purchase_history.sales_man_name)) AS sales_man_sort')
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
            ->selectRaw('MIN(purchase_history.lr_number) AS lr_number_sort')
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

        // Distinct SKU list for filter dropdown
        $skuOptions = PurchaseHistory::query()
            ->select('product_sku')
            ->whereNotNull('product_sku')
            ->distinct()
            ->orderBy('product_sku')
            ->pluck('product_sku');

        return view('backend.purchase_history.index', [
            'purchaseHistory' => $purchaseHistory,
            'search'          => $search ?? null,
            'sortBy'          => $sortBy,
            'sortDir'         => $sortDir,
            'skuOptions'      => $skuOptions,
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
        Log::info('PurchaseHistoryReport import party wise sheets called', [
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

            Log::info('PurchaseHistoryReport import party wise sheets completed', [
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
                    translate('Party wise sheets imported successfully. Rows imported: ') . $rows
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

            flash(translate('Failed to import party wise sheets. Please correct these issues:') . '<br>' . implode('<br>', $messages))
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
            $request->get('account')
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

    private function sumSql(string $column): string
    {
        return $this->sumExpression($column) . " AS {$column}";
    }

    private function sumExpression(string $column): string
    {
        return "COALESCE(SUM(CAST(NULLIF(REPLACE(purchase_history.{$column}, ',', ''), '') AS DECIMAL(20, 4))), 0)";
    }
}

