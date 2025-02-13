<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use App\Models\ProductsImport;
use App\Models\BulkProductVariantImport;
use App\Models\ProductsExport;
use PDF;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;



class ProductBulkUploadController extends Controller
{
    public function __construct()
    {

        $this->middleware(['permission:product_bulk_import'])->only('index');
        $this->middleware(['permission:product_bulk_export'])->only('export');
    }

    public function index()
    {
        if (Auth::user()->user_type == 'seller') {
            if (Auth::user()->shop->verification_status) {
                return view('seller.product_bulk_upload.index');
            } else {
                flash(translate('Your shop is not verified yet!'))->warning();
                return back();
            }
        } elseif (Auth::user()->user_type == 'admin' || Auth::user()->user_type == 'staff') {
            return view('backend.product.bulk_upload.index');
        }
    }

    public function export()
    {
        return Excel::download(new ProductsExport, 'products.xlsx');
    }

    public function pdf_download_category()
    {
        $categories = Category::all();

        return PDF::loadView('backend.downloads.category', [
            'categories' => $categories,
        ], [], [])->download('category.pdf');
    }

    public function pdf_download_brand()
    {
        $brands = Brand::all();

        return PDF::loadView('backend.downloads.brand', [
            'brands' => $brands,
        ], [], [])->download('brands.pdf');
    }

    public function pdf_download_seller()
    {
        $users = User::where('user_type', 'seller')->get();

        return PDF::loadView('backend.downloads.user', [
            'users' => $users,
        ], [], [])->download('user.pdf');
    }

    public function bulk_upload(Request $request)
    {
        if ($request->hasFile('bulk_file')) {
            $import = new ProductsImport;
            Excel::import($import, request()->file('bulk_file'));
        }

        return back();
    }


    public function bulk_upload2(Request $request)
    {
        // 1. Check that a file is selected.
        if (!$request->hasFile('bulk_file_product_variant')) {
            flash(translate('No file selected'))->error();
            return back();
        }

        $file = $request->file('bulk_file_product_variant');

        // 2. Enforce a file size limit of 10MB.
        if ($file->getSize() > 10 * 1024 * 1024) { // 10MB in bytes
            flash(translate('File size exceeds the 10MB limit.'))->error();
            return back();
        }

        // // 2.5. Pre-check that required columns exist.
        // // We'll load the first row from the file to get the headers.
        // // Ensure that you have disabled the default heading formatter if you need raw headers:
        // // HeadingRowFormatter::default('none'); (set in your import class or globally)
        // // $dataArray = Excel::toArray([], $file);
        // $dataArray = Excel::toArray(new BulkProductVariantImport, $file);
        // // dd($dataArray[0]);

        // if (empty($dataArray) || !isset($dataArray[0][0])) {
        //     flash(translate('The file appears to be empty or invalid.'))->error();
        //     return back();
        // }

        // // Get the header keys from the first row.
        // $rawHeaders = array_keys($dataArray[0][0]);
        // // dd($rawHeaders);
        // // Define required columns for critical fields, using possible header names.
        // $requiredColumnsMapping = [
        //     'Product Name' => ['Product Name', 'Name'],
        //     'description' => ['Product Description', 'Description'],
        //     'category_id' => ['Category Id', 'Category'],
        //     'brand_id' => ['Brand Id', 'Brand'],
        //     'unit_price' => ['Unit Price', 'Price'],
        //     'slug' => ['Slug']
        // ];
        // foreach ($requiredColumnsMapping as $internal => $possibilities) {
        //     $found = false;
        //     foreach ($possibilities as $headerName) {
        //         if (in_array($headerName, $rawHeaders)) {
        //             $found = true;
        //             break;
        //         }
        //     }
        //     if (!$found) {
        //         flash(translate("The column for '{$internal}' is missing. Please include any one of name mentioned: " . implode(', ', $possibilities)))->error();
        //         return back();
        //     }
        // }

        // 3. Pre-validate the file using the import class.
        $import = new BulkProductVariantImport;
        Excel::import($import, $file);

        // 4. Retrieve all row-level validation failures.
        $failures = $import->failures();

        // 5. Get the count of valid rows (rows that passed validation).
        $validRows = $import->getRowCount();

        // 6. Build an error log from the failures.
        $errorMessagesByRow = [];
        foreach ($failures as $failure) {
            $row = $failure->row();
            $errors = $failure->errors(); // returns an array of error messages for this failure
            if (!isset($errorMessagesByRow[$row])) {
                $errorMessagesByRow[$row] = $errors;
            } else {
                $errorMessagesByRow[$row] = array_merge($errorMessagesByRow[$row], $errors);
            }
        }
        // Remove duplicate error messages for each row.
        foreach ($errorMessagesByRow as $row => $errors) {
            $errorMessagesByRow[$row] = array_unique($errors);
        }
        // Build the error log content (one error per row).
        $errorLogContent = "Error Log - " . now()->toDateTimeString() . "\n\n";
        foreach ($errorMessagesByRow as $row => $errors) {
            $errorLogContent .= "Row $row: " . implode(' | ', $errors) . "\n";
        }
        // Generate a unique file name and store the error log.
        $errorLogFileName = 'error_log_' . Str::random(10) . '.txt';
        Storage::put('error_logs/' . $errorLogFileName, $errorLogContent);
        $downloadUrl = url('error_logs/' . $errorLogFileName);

        // 7. If all rows have errors (i.e. no valid rows), then do not import anything.
        if ($validRows == 0) {
            flash(translate("All rows have errors. No products were imported. <br><a href='{$downloadUrl}' download target='_blank'>Download Error Log</a>"))->error();
            return back();
        }
        // ELSE: Some valid rows exist.
        else {
            // Begin a transaction.
            DB::beginTransaction();
            // Re-run the import so that valid rows are persisted.
            Excel::import(new BulkProductVariantImport, $file);
            DB::commit();

            // If there were any failures (even though some rows were valid), show a warning.
            if ($failures->isNotEmpty()) {
                flash(translate("Products imported successfully for valid rows. <br>However, some rows had errors. <br><a href='{$downloadUrl}' download target='_blank'>Download Error Log</a>"))->warning();
            } else {
                flash(translate('Products imported successfully'))->success();
            }
            return back();
        }
    }

/*
public function bulk_upload2(Request $request)
{
    if ($request->hasFile('bulk_file_product_variant')) {

        // Instantiate the import class.
        $import = new BulkProductVariantImport;

        // Begin a transaction.
        DB::beginTransaction();

        // Perform the import.
        Excel::import($import, $request->file('bulk_file_product_variant'));

        // Retrieve the failures collected by the import.
        $failures = $import->failures();

        // If there are any failures, roll back the transaction.
        if ($failures->isNotEmpty()) {
            DB::rollBack();

            // Group errors by row.
            $errorMessagesByRow = [];
            foreach ($failures as $failure) {
                $row    = $failure->row();
                $errors = $failure->errors(); // Returns an array of error messages.

                // Build an error message for each row, joining multiple errors with a pipe.
                if (!isset($errorMessagesByRow[$row])) {
                    $errorMessagesByRow[$row] = "Row $row: " . implode(' | ', $errors);
                } else {
                    $errorMessagesByRow[$row] .= " | " . implode(' | ', $errors);
                }
            }

            // Build the final error message as an ordered list.
            $errorMessages = '<ol style="margin-top:10px; margin-bottom:0;">';
            foreach ($errorMessagesByRow as $errorMessage) {
                $errorMessages .= '<li>' . $errorMessage . '</li>';
            }
            $errorMessages .= '</ol>';

            // Flash the error messages.
            flash($errorMessages)->error();

            // Return immediately so no products are imported.
            return back();
        }

        // If there are no validation failures, commit the transaction.
        DB::commit();

        flash(translate('Products imported successfully'))->success();
        return back();
    }

    flash(translate('No file selected'))->error();
    return back();
}
*/



}
