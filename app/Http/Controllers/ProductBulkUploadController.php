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




}
