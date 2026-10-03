<?php

namespace App\Http\Controllers;

use App\Models\FinancialArchive;
use App\Models\Upload;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FinancialArchiveController extends Controller
{
    private const TYPES = [
        'order' => 'Order',
        'challan' => 'Challan',
        'invoice' => 'Invoice',
        'eway_bill' => 'E-Way Bill',
        'lr_gr_doc' => 'LR/GR/Doc',
        'credit_debit_note' => 'Credit / Debit Note',
        'account_statement' => 'Account Statement',
        'purchased_history' => 'Purchased History',
        'performa_invoice' => 'Performa Invoice',
        'other_documents' => 'Other Documents',
        // 'product_purchased' => 'Product Purchased',
        // 'ledger_statement' => 'Ledger Statement',
        // 'lr_copy' => 'LR Copy',
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
        $path = 'uploads/all/' . date('Y/m');

        Storage::disk('local')->makeDirectory($path);

        $storedPath = $file->store($path, 'local');

        $diskUploadFailed = false;
        if (env('FILESYSTEM_DRIVER') != 'local') {
            try {
                $diskName = env('FILESYSTEM_DRIVER') == 's3' ? 's3' : env('FILESYSTEM_DRIVER');
                $filePath = public_path($storedPath);
                if (!file_exists($filePath)) {
                    throw new \Exception("File not found: " . $filePath);
                }

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $file_mime = finfo_file($finfo, $filePath);
                finfo_close($finfo);

                $result = Storage::disk($diskName)->put(
                    $storedPath,
                    file_get_contents($filePath),
                    [
                        'ContentType' => $extension == 'svg' ? 'image/svg+xml' : $file_mime
                    ]
                );

                if ($result === false) {
                    $diskUploadFailed = true;
                } else {
                    unlink($filePath);
                }
            } catch (\Exception $e) {
                $diskUploadFailed = true;
            }
        }

        $upload->file_name = $storedPath;
        $upload->disk = $diskUploadFailed ? 'local' : config('filesystems.default');
        
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
     * Customer-specific listing. Add form opens from a separate button.
     */
    public function customerArchives(Request $request, User $user)
    {
        $user->loadMissing('details');
        [$sortBy, $sortOrder] = $this->archiveSort($request);

        $archives = $this->sortedArchivesQuery(
            $this->filteredArchivesQuery($request)->where('financial_archive.user_id', $user->id),
            $sortBy,
            $sortOrder
        )
            ->paginate(15)
            ->appends($request->query());

        $extensions = Upload::query()
            ->whereIn('id', FinancialArchive::where('user_id', $user->id)->pluck('upload_id'))
            ->whereNotNull('extension')
            ->where('extension', '!=', '')
            ->pluck('extension')
            ->map(fn ($extension) => strtolower($extension))
            ->unique()
            ->sort()
            ->values();

        return view('backend.financial_archives.customer', [
            'user' => $user,
            'archives' => $archives,
            'types' => self::TYPES,
            'extensions' => $extensions,
            'filterType' => $request->type,
            'filterSearch' => $request->search,
            'filterExtension' => $request->extension,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    /**
     * Rename the linked upload's display name. The stored file path stays the same.
     */
    public function rename(Request $request, FinancialArchive $archive)
    {
        $request->validate([
            'new_name' => 'required|string|max:190',
        ]);

        $upload = $archive->upload;
        if (!$upload) {
            flash(translate('File missing'))->error();

            return back();
        }

        $baseName = trim(pathinfo($request->input('new_name'), PATHINFO_FILENAME));
        if ($baseName === '') {
            flash(translate('A valid file name is required.'))->error();

            return back();
        }

        $upload->file_original_name = $baseName;
        $upload->save();

        flash(translate('File renamed successfully.'))->success();

        return back();
    }

    /**
     * Move this archive to another business customer by account number.
     */
    public function move(Request $request, FinancialArchive $archive)
    {
        $request->validate([
            'account_no' => 'required|string|max:20',
        ]);

        $accountNo = trim($request->input('account_no'));
        $details = UserDetails::where('account_no_business', $accountNo)->first();

        if (!$details || !$details->user_id) {
            flash(translate('No customer found for that account number.'))->error();

            return back();
        }

        if ((int) $details->user_id === (int) $archive->user_id) {
            flash(translate('This archive already belongs to that account.'))->error();

            return back();
        }

        $archive->user_id = $details->user_id;
        $archive->save();

        flash(translate('Archive moved to the other account.'))->success();

        return back();
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

    /**
     * Frontend user listing.
     */
    public function userArchives(Request $request)
    {
        $user = Auth::user();

        $archives = $this->filteredArchivesQuery($request)
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15)
            ->appends($request->query());

        return view('frontend.user.financial_archive', [
            'archives' => $archives,
            'types' => self::TYPES,
            'filterType' => $request->type,
            'filterSearch' => $request->search,
        ]);
    }

    /**
     * Base query with optional filters.
     */
    protected function filteredArchivesQuery(Request $request)
    {
        return FinancialArchive::with('upload')
            ->when($request->filled('type'), function ($q) use ($request) {
                $q->where('financial_archive.type', $request->type);
            })
            ->when($request->filled('extension'), function ($q) use ($request) {
                $extension = strtolower(trim($request->extension));
                $q->whereHas('upload', function ($uq) use ($extension) {
                    $uq->whereRaw('LOWER(extension) = ?', [$extension]);
                });
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->whereHas('upload', function ($uq) use ($search) {
                    $uq->where('file_original_name', 'like', '%' . $search . '%');
                });
            });
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function archiveSort(Request $request): array
    {
        $sortBy = $request->input('sort_by', 'created_at');
        $allowed = ['created_at', 'type', 'name', 'extension'];
        if (!in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        $sortOrder = $request->input('sort_order') === 'asc' ? 'asc' : 'desc';
        if ($sortBy !== 'created_at' && !$request->filled('sort_order')) {
            $sortOrder = 'asc';
        }

        return [$sortBy, $sortOrder];
    }

    protected function sortedArchivesQuery($query, string $sortBy, string $sortOrder)
    {
        if (in_array($sortBy, ['name', 'extension'], true)) {
            $column = $sortBy === 'name' ? 'uploads.file_original_name' : 'uploads.extension';

            return $query
                ->leftJoin('uploads', 'uploads.id', '=', 'financial_archive.upload_id')
                ->select('financial_archive.*')
                ->orderBy($column, $sortOrder);
        }

        $column = $sortBy === 'type' ? 'financial_archive.type' : 'financial_archive.created_at';

        return $query->orderBy($column, $sortOrder);
    }
}
