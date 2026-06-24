<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PurchaseHistory;
use App\Models\Product;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use App\Models\PurchaseHistoryImport;
use App\Models\PurchaseHistoryExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class PurchaseHistoryReportController extends Controller
{
    /**
     * Display a listing of the purchase history records.
     */
    public function index(Request $request)
    {
        $query = PurchaseHistory::query()
            ->with([
                'customerDetails.user',
                'customerDetails.businessCity',
                'customerDetails.businessState',
                'customerDetails.businessCountry',
                'productStock.product.brand',
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

        // Sorting
        $sortableColumns = [
            'serial_number',
            'order_date',
            'order_number',
            'invoice_number',
            'product_sku',
            'sales_man_name',
            'state',
            'city',
            'final_amount',
        ];
        $sortBy = $request->get('sort_by', 'order_date');
        $sortDir = $request->get('sort_dir', 'desc');
        if (! in_array($sortBy, $sortableColumns, true)) {
            $sortBy = 'order_date';
        }
        if (! in_array(strtolower($sortDir), ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }
        $query->orderBy($sortBy, $sortDir)
            ->orderBy('order_number')
            ->orderBy('invoice_number')
            ->orderBy('product_sku')
            ->orderBy('batch_number');

        $perPage = (int) $request->get('per_page', 25);
        if ($perPage <= 0 || $perPage > 200) {
            $perPage = 25;
        }

        // Group before pagination so a matching line can never be split across pages.
        $mergedRows = PurchaseHistory::mergeReportRows($query->get());
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $purchaseHistory = new LengthAwarePaginator(
            $mergedRows->forPage($currentPage, $perPage)->values(),
            $mergedRows->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

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

        $rowCount = DB::query()
            ->fromSub($export->query()->toBase(), 'purchase_history_export')
            ->count();

        if ($rowCount > PurchaseHistoryExport::XLSX_SAFE_ROW_LIMIT) {
            return response()->streamDownload(function () use ($export) {
                if (function_exists('set_time_limit')) {
                    set_time_limit(0);
                }

                $output = fopen('php://output', 'wb');

                // UTF-8 BOM makes Excel display non-English names correctly.
                fwrite($output, "\xEF\xBB\xBF");
                fputcsv($output, $export->headings());

                $export->query()->chunk($export->chunkSize(), function ($records) use ($export, $output) {
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

        return Excel::download($export, 'purchase_history.xlsx');
    }
}

