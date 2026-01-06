<?php

namespace App\Http\Controllers;

use App\Models\FinancialArchive;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FinancialArchiveController extends Controller
{
    private const TYPES = [
        'product_purchased' => 'Product Purchased',
        'ledger_statement' => 'Ledger Statement',
        'invoice' => 'Invoice',
        'eway_bill' => 'Eway Bill',
        'credit_debit_note' => 'Credit / Debit Note',
        'lr_copy' => 'LR Copy',
        'challan' => 'Challan',
    ];

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $archive = FinancialArchive::findOrFail($id);
        if ($archive->upload) {
            $this->deleteUploadFile($archive->upload);
        }
        $archive->delete();

        flash(translate('Financial archive deleted successfully.'))->success();

        return back();
    }

    /**
     * Validates incoming request data.
     */
    protected function validateRequest(Request $request, bool $isCreate, bool $requireUser): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'file' => [
                $isCreate ? 'required' : 'nullable',
                'file',
                'mimes:jpg,jpeg,png,gif,webp,bmp,svg,pdf,doc,docx,xls,xlsx,csv,txt,xml,zip,rar,7z',
            ],
            'user_id' => [
                $requireUser ? 'required' : 'nullable',
                'integer',
                'exists:users,id',
            ],
        ]);
    }

    /**
     * Store file via Upload model and return its ID.
     */
    protected function storeFileToUploads(Request $request, string $inputName): int
    {
        $file = $request->file($inputName);
        $extension = strtolower($file->getClientOriginalExtension());

        $typeMap = [
            "jpg" => "image",
            "jpeg" => "image",
            "png" => "image",
            "svg" => "image",
            "webp" => "image",
            "gif" => "image",
            "bmp" => "image",
            "mp4" => "video",
            "mpg" => "video",
            "mpeg" => "video",
            "webm" => "video",
            "ogg" => "video",
            "avi" => "video",
            "mov" => "video",
            "flv" => "video",
            "swf" => "video",
            "mkv" => "video",
            "wmv" => "video",
            "wma" => "audio",
            "aac" => "audio",
            "wav" => "audio",
            "mp3" => "audio",
            "zip" => "archive",
            "rar" => "archive",
            "7z" => "archive",
            "doc" => "document",
            "txt" => "document",
            "docx" => "document",
            "pdf" => "document",
            "csv" => "document",
            "xml" => "document",
            "xls" => "document",
            "xlsx" => "document"
        ];

        $upload = new Upload();
        $upload->file_original_name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $upload->extension = $extension;
        $upload->file_size = $file->getSize();
        $upload->user_id = auth()->id();
        $upload->type = $typeMap[$extension] ?? 'document';
        // $upload->file_name = $file->store('uploads/all', 'local');
        $path = 'uploads/all/' . date('Y/m');

        // ADD: ensure directory exists on SAME disk
        Storage::disk('local')->makeDirectory($path);

        // ADD: force permission
        @chmod(storage_path('app/' . $path), 0777);

        // UNCHANGED (still local disk)
        $upload->file_name = $file->store($path, 'local');
        
        // $upload->file_name = $file->store('uploads/all/' . date('Y/m'), 'local');
        $upload->save();

        return $upload->id;
    }

    /**
     * Delete upload record and physical file if present.
     */
    protected function deleteUploadFile(Upload $upload): void
    {
        try {
            if ($upload->external_link == null && $upload->file_name) {
                if (env('FILESYSTEM_DRIVER') != 'local') {
                    Storage::disk(env('FILESYSTEM_DRIVER'))->delete($upload->file_name);
                    if (file_exists(public_path($upload->file_name))) {
                        @unlink(public_path($upload->file_name));
                    }
                } else {
                    if (file_exists(public_path($upload->file_name))) {
                        @unlink(public_path($upload->file_name));
                    }
                }
            }
        } catch (\Exception $e) {
            // Best-effort deletion; continue to delete the upload record.
        }

        // Use force delete to bypass soft deletes so the row is fully removed.
        if (method_exists($upload, 'forceDelete')) {
            $upload->forceDelete();
        } else {
            $upload->delete();
        }
    }

    /**
     * Customer-specific listing with inline add form.
     */
    public function customerArchives(User $user)
    {
        $archives = FinancialArchive::with('upload')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return view('backend.financial_archives.customer', [
            'user' => $user,
            'archives' => $archives,
            'types' => self::TYPES,
        ]);
    }

    /**
     * Store archive for a specific customer.
     */
    public function storeForUser(Request $request, User $user)
    {
        $validated = $this->validateRequest($request, true, true);

        $uploadId = $this->storeFileToUploads($request, 'file');

        FinancialArchive::create([
            'type' => $validated['type'],
            'upload_id' => $uploadId,
            'user_id' => $user->id,
        ]);

        flash(translate('Financial archive added for customer.'))->success();

        return back();
    }
}
