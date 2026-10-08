<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Upload;
use App\Models\UploadFolder;
use Response;
use Auth;
use Illuminate\Support\Facades\Storage;
use Image;
use enshrined\svgSanitize\Sanitizer;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class AizUploadController extends Controller
{
    public function index(Request $request)
    {

        $all_uploads = (auth()->user()->user_type == 'seller')
            ? Upload::where('user_id', auth()->user()->id)
            : Upload::query();

        $search        = $request->search;
        $typeFilter    = $request->get('type');
        $extension     = strtolower(ltrim(trim((string) $request->get('extension', '')), '.'));
        $sizeMin       = $request->get('size_min');
        $sizeMax       = $request->get('size_max');
        $dateFrom      = $this->parseFilterDate($request->get('date_from'));
        $dateTo        = $this->parseFilterDate($request->get('date_to'));
        $uploader      = trim((string) $request->get('uploader', ''));
        $sortByInput   = $request->get('sort_by');
        $sortOrder     = $request->get('sort_order', 'desc');
        $legacySort    = $request->get('sort'); // keep support for existing select
        $perPage       = (int) $request->get('per_page', 60);
        $perPage       = in_array($perPage, [30, 60, 120, 240], true) ? $perPage : 60;
        $viewMode      = $request->get('view', $request->session()->get('uploads_view', 'grid'));

        $request->session()->put('uploads_view', $viewMode);

        $this->applyUploadFilters($all_uploads, $search, $typeFilter, [
            'extension' => $extension,
            'size_min' => $sizeMin,
            'size_max' => $sizeMax,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'uploader' => auth()->user()->user_type == 'seller' ? '' : $uploader,
        ]);

        $foldersReady = UploadFolder::ready() && auth()->user()->user_type != 'seller';
        $currentFolder = null;
        $childFolders = collect();
        $breadcrumbs = [];
        $folderOptions = [];

        if ($foldersReady) {
            $requestedFolder = (int) $request->get('folder');
            if ($requestedFolder > 0) {
                $currentFolder = UploadFolder::find($requestedFolder);
            }

            if ($currentFolder) {
                $all_uploads->where('folder_id', $currentFolder->id);
                $breadcrumbs = $currentFolder->breadcrumb();
            } else {
                $all_uploads->whereNull('folder_id');
            }

            $fileOnlyFilter = $typeFilter || $extension !== '' || ($sizeMin !== null && $sizeMin !== '') || ($sizeMax !== null && $sizeMax !== '') || $uploader !== '';
            $childFolders = $fileOnlyFilter
                ? collect()
                : UploadFolder::query()
                    ->when($currentFolder, function ($query) use ($currentFolder) {
                        $query->where('parent_id', $currentFolder->id);
                    }, function ($query) {
                        $query->whereNull('parent_id');
                    })
                    ->when($search, function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    })
                    ->when($dateFrom, function ($query) use ($dateFrom) {
                        $query->whereDate('created_at', '>=', $dateFrom);
                    })
                    ->when($dateTo, function ($query) use ($dateTo) {
                        $query->whereDate('created_at', '<=', $dateTo);
                    })
                    ->get();

            $folderOptions = UploadFolder::flatTree();
        }

        // Normalize sorting
        $sortBy = $sortByInput;
        if (!$sortBy && $legacySort) {
            $sortBy = in_array($legacySort, ['smallest', 'largest']) ? 'size' : 'created_at';
            $sortOrder = in_array($legacySort, ['oldest', 'smallest']) ? 'asc' : 'desc';
        }

        $allowedSorts = ['name', 'extension', 'type', 'size', 'created_at', 'updated_at', 'user'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'created_at';
        if ($sortBy === 'user' && auth()->user()->user_type == 'seller') {
            $sortBy = 'created_at';
        }
        $sortOrder = $sortOrder === 'asc' ? 'asc' : 'desc';
        $childFolders = $this->sortFolders($childFolders, $sortBy, $sortOrder);

        switch ($sortBy) {
            case 'name':
                $all_uploads->orderBy('file_original_name', $sortOrder);
                break;
            case 'extension':
                $all_uploads->orderBy('extension', $sortOrder);
                break;
            case 'type':
                $all_uploads->orderBy('type', $sortOrder);
                break;
            case 'size':
                $all_uploads->orderBy('file_size', $sortOrder);
                break;
            case 'updated_at':
                $all_uploads->orderBy('updated_at', $sortOrder);
                break;
            case 'user':
                $all_uploads->leftJoin('users', 'users.id', '=', 'uploads.user_id')
                    ->select('uploads.*')
                    ->orderBy('users.name', $sortOrder);
                break;
            case 'created_at':
            default:
                $all_uploads->orderBy('created_at', $sortOrder);
                break;
        }

        $all_uploads = $all_uploads->paginate($perPage)->appends(request()->query());

        $viewData = [
            'all_uploads' => $all_uploads,
            'search'      => $search,
            'sortBy'      => $sortBy,
            'sortOrder'   => $sortOrder,
            'sort_by'     => $sortBy, // backward compatibility with existing blade
            'typeFilter'  => $typeFilter,
            'extension'   => $extension,
            'sizeMin'     => $sizeMin,
            'sizeMax'     => $sizeMax,
            'dateFrom'    => $request->get('date_from'),
            'dateTo'      => $request->get('date_to'),
            'uploader'    => $uploader,
            'perPage'     => $perPage,
            'viewMode'    => $viewMode,
            'foldersReady' => $foldersReady,
            'currentFolder' => $currentFolder,
            'childFolders' => $childFolders,
            'breadcrumbs' => $breadcrumbs,
            'folderOptions' => $folderOptions,
        ];

        return (auth()->user()->user_type == 'seller')
            ? view('seller.uploads.index', $viewData)
            : view('backend.uploaded_files.index', $viewData);
    }

    public function create()
    {
        if(env('DEMO_MODE') == 'On'){
            flash(translate('Data can not change in demo mode.'))->info();
            return back();
        }

        return (auth()->user()->user_type == 'seller')
            ? view('seller.uploads.create')
            : view('backend.uploaded_files.create');
    }


    public function show_uploader(Request $request)
    {
        return view('uploader.aiz-uploader');
    }
    public function upload(Request $request)
    {
        $uploadId = null;
        $type = array(
            "jpg" => "image",
            "jpeg" => "image",
            "png" => "image",
            "svg" => "image",
            "webp" => "image",
            "gif" => "image",
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
            "ods" => "document",
            "xlr" => "document",
            "xls" => "document",
            "xlsx" => "document"
        );

        if ($request->hasFile('aiz_file')) {
            $upload = new Upload;
            // $upload->disk = config('filesystems.default');
            $extension = strtolower($request->file('aiz_file')->getClientOriginalExtension());
            $targetDir = trim((string) $request->input('upload_dir', 'uploads/all/' . date('Y/m')), '/');
            if ($targetDir === '') {
                $targetDir = 'uploads/all/' . date('Y/m');
            }

            if (
                env('DEMO_MODE') == 'On' &&
                isset($type[$extension]) &&
                $type[$extension] == 'archive'
            ) {
                return '{}';
            }

            if (isset($type[$extension])) {
                $upload->file_original_name = null;
                $arr = explode('.', $request->file('aiz_file')->getClientOriginalName());
                for ($i = 0; $i < count($arr) - 1; $i++) {
                    if ($i == 0) {
                        $upload->file_original_name .= $arr[$i];
                    } else {
                        $upload->file_original_name .= "." . $arr[$i];
                    }
                }

                if ($extension == 'svg') {
                    $sanitizer = new Sanitizer();
                    // Load the dirty svg
                    $dirtySVG = file_get_contents($request->file('aiz_file'));

                    // Pass it to the sanitizer and get it back clean
                    $cleanSVG = $sanitizer->sanitize($dirtySVG);

                    // Load the clean svg
                    file_put_contents($request->file('aiz_file'), $cleanSVG);
                }

                $size = $request->file('aiz_file')->getSize();

                if ($type[$extension] == 'video' && $size > 10 * 1024 * 1024) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Video file size should be less than 10 MB.'
                    ], 422);
                }

                if ($type[$extension] == 'image' && $extension != 'svg') {
                    if (get_setting('uploaded_image_format') != "default") {
                        $extension = get_setting('uploaded_image_format');
                    }
                    try {
                        
                        $dir = public_path($targetDir);

                        if (!File::exists($dir)) {
                            File::makeDirectory($dir, 0777, true);
                        }

                        $path = $targetDir . '/' . Str::random(40) . '.' . $extension;
                        $img = Image::make($request->file('aiz_file')->getRealPath())->encode($extension, 75);
                        $height = $img->height();
                        $width = $img->width();

                        // watermark
                        if (get_setting('use_image_watermark') == 'on') {
                            $watermark_position = get_setting('watermark_position', 'top-left');
                            // watermark Image
                            if (get_setting('image_watermark_type') == "image") {
                                $watermarkImg = Image::make( uploaded_asset(get_setting('watermark_image')) );
                                if ($width > $height ) {
                                    $wmarkHeight = $height/2;
                                    $watermarkImg->resize(null, $wmarkHeight, function ($constraint) {
                                        $constraint->aspectRatio();
                                    });
                                } else {
                                    $wmarkWidth = $width/2;
                                    $watermarkImg->resize(null, $wmarkWidth, function ($constraint) {
                                        $constraint->aspectRatio();
                                    });
                                }
                                $img->insert($watermarkImg, $watermark_position, 10, 10);

                                // // --------watermark Image multiple times------
                                // if ($width > 1999) {
                                //     $watermark = 'watermark-2x.png';
                                // } else {
                                //     $watermark = 'watermark-1x.png';
                                // }
                                // $watermarkImg = Image::make('public/assets/img/'.$watermark);
                                // $wmarkWidth=$watermarkImg->width();
                                // $wmarkHeight=$watermarkImg->height();
                                // $x=10;
                                // $y=10;
                                // while($y<=$height){
                                //     $img->insert($watermarkImg,'top-left',$x,$y);
                                //     $x+=$wmarkWidth+40;
                                //     if($x>=$width){
                                //         $x=0;
                                //         $y+=$wmarkHeight+30;
                                //     }
                                // }

                            // watermark Text
                            } elseif (get_setting('image_watermark_type') == "text") {
                                if ($watermark_position == 'center') {
                                    $valign = 'middle';
                                    $align = 'center';
                                    $x = round($width/2);
                                    $y =  round($height/2);
                                } else {
                                    $valign = explode('-', $watermark_position)[0];
                                    $align = explode('-', $watermark_position)[1];
                                    $x = ($align == 'right') ? ($width - 20) : 20;
                                    $y =  ($valign == 'bottom') ? ($height - 20) : 20;
                                }
                                $img->text(get_setting('watermark_text', 'Watermark Text Here'), $x, $y, function($font) use ($valign, $align) {
                                    $font->file(base_path('public/assets/fonts/robotoMedium.ttf'));
                                    $font->size(get_setting('watermark_text_size', 20));
                                    $font->color(get_setting('watermark_text_color', '#e1e1e1'));
                                    $font->align($align);
                                    $font->valign($valign);
                                });
                            }
                        }

                        // Image optimization
                        if (get_setting('disable_image_optimization') != 1) {
                            if ($width > $height && $width > 1500) {
                                $img->resize(1500, null, function ($constraint) {
                                    $constraint->aspectRatio();
                                });
                            } elseif ($height > 1500) {
                                $img->resize(null, 800, function ($constraint) {
                                    $constraint->aspectRatio();
                                });
                            }
                        }

                        $img->save(base_path('public/') . $path);
                        clearstatcache();
                        $size = $img->filesize();
                    } catch (\Exception $e) {
                        //dd($e);
                    }
                }else{
                    // $path = $request->file('aiz_file')->store('uploads/all', 'local');
                    $path = $request->file('aiz_file')->store($targetDir, 'local');
                }

                $diskUploadFailed = false;
                if (env('FILESYSTEM_DRIVER') != 'local') {
                    try {
                        // Return MIME type ala mimetype extension
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        // Get the MIME type of the file
                        $file_mime = finfo_file($finfo, base_path('public/') . $path);
                        finfo_close($finfo);

                        // Use 's3' disk name as defined in config/filesystems.php
                        $diskName = env('FILESYSTEM_DRIVER') == 's3' ? 's3' : env('FILESYSTEM_DRIVER');
                        
                        // Ensure the file exists before uploading
                        $filePath = base_path('public/') . $path;
                        if (!file_exists($filePath)) {
                            throw new \Exception("File not found: " . $filePath);
                        }

                        // Upload to S3 with proper options
                        // Note: Removed 'visibility' => 'public' as it tries to set ACLs
                        // If bucket has ACLs disabled, use bucket policy for public access instead
                        $result = Storage::disk($diskName)->put(
                            $path,
                            file_get_contents($filePath),
                            [
                                'ContentType' => $extension == 'svg' ? 'image/svg+xml' : $file_mime
                            ]
                        );

                        // If Storage::put() returns false, try direct AWS SDK for better error messages
                        if ($result === false) {
                            $diskUploadFailed = true;
                            // Get AWS config
                            $awsConfig = config('filesystems.disks.' . $diskName);
                            
                            // Validate AWS configuration
                            if (empty($awsConfig['key']) || empty($awsConfig['secret']) || empty($awsConfig['bucket'])) {
                                throw new \Exception("AWS configuration incomplete. Check AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, and AWS_BUCKET");
                            }
                            
                            // Check if AWS SDK classes are available
                            if (!class_exists('Aws\S3\S3Client')) {
                                throw new \Exception("AWS SDK not available. Please install aws/aws-sdk-php package.");
                            }
                            
                            // Create S3 client directly to get detailed error messages
                            $s3Client = new S3Client([
                                'version' => 'latest',
                                'region' => $awsConfig['region'] ?? 'ap-south-1',
                                'credentials' => [
                                    'key' => $awsConfig['key'],
                                    'secret' => $awsConfig['secret'],
                                ],
                            ]);

                            // Try upload with direct SDK
                            $fileContent = file_get_contents($filePath);
                            if ($fileContent === false) {
                                throw new \Exception("Failed to read file content from: " . $filePath);
                            }
                            
                            // Note: ACL is removed because bucket has ACLs disabled (Object Ownership: Bucket owner enforced)
                            // Public access should be controlled via bucket policy instead
                            $s3Result = $s3Client->putObject([
                                'Bucket' => $awsConfig['bucket'],
                                'Key' => $path,
                                'Body' => $fileContent,
                                'ContentType' => $extension == 'svg' ? 'image/svg+xml' : $file_mime,
                            ]);

                            // If direct SDK succeeds, use the result
                            if (isset($s3Result['ObjectURL']) || isset($s3Result['ETag'])) {
                                $result = $path; // Set result to path to indicate success
                            } else {
                                throw new \Exception("S3 upload failed - no ObjectURL or ETag returned. Response: " . json_encode($s3Result));
                            }
                        }

                        // Note: We don't verify with exists() immediately due to S3 eventual consistency
                        // The put() method throwing an exception is sufficient indication of failure
                        // If put() succeeds without exception, we consider the upload successful

                        // Delete local file after successful upload (except for updates)
                        if ($arr[0] != 'updates') {
                            unlink($filePath);
                        }
                    } catch (AwsException $e) {
                        $diskUploadFailed = true;
                        // AWS-specific exception with detailed error information
                        $awsError = [
                            'message' => $e->getAwsErrorMessage(),
                            'code' => $e->getAwsErrorCode(),
                            'request_id' => $e->getAwsRequestId(),
                        ];
                        
                        Log::error('S3 Upload AWS Error: ' . $e->getAwsErrorMessage(), [
                            'path' => $path,
                            'disk' => $diskName ?? env('FILESYSTEM_DRIVER'),
                            'region' => config('filesystems.disks.aws.region'),
                            'bucket' => config('filesystems.disks.aws.bucket'),
                            'file_exists' => isset($filePath) && file_exists($filePath ?? ''),
                            'exception' => get_class($e),
                            'aws_error' => $awsError,
                            'trace' => $e->getTraceAsString()
                        ]);
                        // If upload fails, keep local file so it's not lost
                    } catch (\Exception $e) {
                        $diskUploadFailed = true;
                        // Log error with full details for debugging
                        Log::error('S3 Upload Error: ' . $e->getMessage(), [
                            'path' => $path,
                            'disk' => $diskName ?? env('FILESYSTEM_DRIVER'),
                            'region' => config('filesystems.disks.aws.region'),
                            'bucket' => config('filesystems.disks.aws.bucket'),
                            'file_exists' => isset($filePath) && file_exists($filePath ?? ''),
                            'exception' => get_class($e),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // If upload fails, keep local file so it's not lost
                    }
                }

                $upload->extension = $extension;
                $upload->file_name = $path;
                $upload->user_id = Auth::user()->id;
                if (UploadFolder::ready() && auth()->user()->user_type != 'seller') {
                    $folderId = (int) $request->input('folder_id');
                    if ($folderId > 0 && UploadFolder::where('id', $folderId)->exists()) {
                        $upload->folder_id = $folderId;
                    }
                }
                $upload->type = $type[$upload->extension];
                $upload->file_size = $size;
                $upload->is_hidden = $request->boolean('is_hidden', false);
                // If cloud upload failed, mark as local so URL generation stays correct
                $upload->disk = $diskUploadFailed ? 'local' : config('filesystems.default');
                $upload->save();
                $uploadId = $upload->id;
            }
            if ($request->boolean('return_upload_id', false)) {
                return response()->json(['upload_id' => $uploadId]);
            }
            return '{}';
        }
    }

    public function get_uploaded_files(Request $request)
    {
        $uploads = Upload::where('user_id', Auth::user()->id);
        $this->applyUploadFilters($uploads, $request->search, $request->input('type'));

        $sortBy    = $request->get('sort_by');
        $sortOrder = $request->get('sort_order', 'desc');
        $legacy    = $request->get('sort');

        if (!$sortBy && $legacy) {
            $sortBy = in_array($legacy, ['smallest', 'largest']) ? 'size' : 'created_at';
            $sortOrder = in_array($legacy, ['oldest', 'smallest']) ? 'asc' : 'desc';
        }

        $sortBy = in_array($sortBy, ['name', 'type', 'size', 'created_at']) ? $sortBy : 'created_at';
        $sortOrder = $sortOrder === 'asc' ? 'asc' : 'desc';

        switch ($sortBy) {
            case 'name':
                $uploads->orderBy('file_original_name', $sortOrder);
                break;
            case 'type':
                $uploads->orderBy('type', $sortOrder);
                break;
            case 'size':
                $uploads->orderBy('file_size', $sortOrder);
                break;
            case 'created_at':
            default:
                $uploads->orderBy('created_at', $sortOrder);
                break;
        }

        return $uploads->paginate(60)->appends(request()->query());
    }

    public function destroy($id)
    {
        $upload = Upload::findOrFail($id);
        $result = $this->deleteStoredUpload($upload);

        if ($result === 'blocked') {
            $message = translate('File is in use and cannot be deleted.');
            if (request()->ajax()) {
                return response()->json(['status' => false, 'message' => $message], 409);
            }
            flash($message)->error();
            return back();
        }

        if ($result === 'forbidden') {
            flash(translate("You don't have permission for deleting this!"))->error();
            return back();
        }

        flash(translate('File deleted successfully'))->success();
        return back();
    }

    /**
     * Delete the stored file and its upload row.
     * Returns deleted, blocked, or forbidden.
     */
    protected function deleteStoredUpload(Upload $upload): string
    {
        if ($this->uploadInUse($upload->id)) {
            return 'blocked';
        }

        if (auth()->user()->user_type == 'seller' && $upload->user_id != auth()->user()->id) {
            return 'forbidden';
        }

        try {
            if (env('FILESYSTEM_DRIVER') != 'local') {
                $diskName = env('FILESYSTEM_DRIVER') == 's3' ? 's3' : env('FILESYSTEM_DRIVER');
                Storage::disk($diskName)->delete($upload->file_name);
                if (file_exists(public_path() . '/' . $upload->file_name)) {
                    unlink(public_path() . '/' . $upload->file_name);
                }
            } else {
                unlink(public_path() . '/' . $upload->file_name);
            }
        } catch (\Exception $e) {
            // The record is still removed when the file is already missing.
        }

        $upload->delete();

        return 'deleted';
    }

    /**
     * Check if an upload ID is referenced elsewhere (basic guard to prevent deletion).
     * Scans all tables that store upload IDs or CSV lists.
     */
    protected function uploadInUse($uploadId): bool
    {
        // Products: photos (CSV), thumbnail_img (single), meta_img (single), pdf (single)
        $inProducts = DB::table('products')
            ->where('photos', 'like', '%'.$uploadId.'%')
            ->orWhere('thumbnail_img', $uploadId)
            ->orWhere('meta_img', $uploadId)
            ->orWhere('pdf', $uploadId)
            ->exists();

        if ($inProducts) {
            return true;
        }

        // Brands: logo (single)
        $inBrands = DB::table('brands')
            ->where('logo', $uploadId)
            ->exists();

        if ($inBrands) {
            return true;
        }

        // Categories: banner (single), icon (single)
        $inCategories = DB::table('categories')
            ->where('banner', $uploadId)
            ->orWhere('icon', $uploadId)
            ->exists();

        if ($inCategories) {
            return true;
        }

        // Flash Deals: banner (single)
        $inFlashDeals = DB::table('flash_deals')
            ->where('banner', $uploadId)
            ->exists();

        if ($inFlashDeals) {
            return true;
        }

        // Financial Archives: upload_id (single)
        $inFinancialArchives = DB::table('financial_archive')
            ->where('upload_id', $uploadId)
            ->exists();

        if ($inFinancialArchives) {
            return true;
        }

        // Users: avatar_original (single), avatar (single)
        $inUsers = DB::table('users')
            ->where('avatar_original', $uploadId)
            ->orWhere('avatar', $uploadId)
            ->exists();

        if ($inUsers) {
            return true;
        }

        return false;
    }

    public function bulk_uploaded_files_delete(Request $request)
    {
        if ($request->id) {
            $blocked = null;
            foreach ($request->id as $file_id) {
                $resp = $this->destroy($file_id);
                if ($resp instanceof \Illuminate\Http\JsonResponse && $resp->getStatusCode() === 409) {
                    $blocked = $resp->getData()->message ?? translate('Some files are in use and could not be deleted.');
                }
            }
            if ($blocked) {
                return response()->json(['status' => false, 'message' => $blocked], 409);
            }
            return 1;
        } else {
            return 0;
        }
    }

    public function get_preview_files(Request $request)
    {
        $ids = explode(',', $request->ids);
        $files = Upload::whereIn('id', $ids)->get();
        $new_file_array = [];
        foreach ($files as $file) {
            $file['file_name'] = my_asset($file->file_name);
            if ($file->external_link) {
                $file['file_name'] = $file->external_link;
            }
            $new_file_array[] = $file;
        }
        // dd($new_file_array);
        return $new_file_array;
        // return $files;
    }

    public function all_file()
    {
        $uploads = Upload::all();
        foreach ($uploads as $upload) {
            try {
                if (env('FILESYSTEM_DRIVER') != 'local') {
                    $diskName = env('FILESYSTEM_DRIVER') == 's3' ? 's3' : env('FILESYSTEM_DRIVER');
                    Storage::disk($diskName)->delete($upload->file_name);
                    if (file_exists(public_path() . '/' . $upload->file_name)) {
                        unlink(public_path() . '/' . $upload->file_name);
                    }
                } else {
                    unlink(public_path() . '/' . $upload->file_name);
                }
                $upload->delete();
                flash(translate('File deleted successfully'))->success();
            } catch (\Exception $e) {
                $upload->delete();
                flash(translate('File deleted successfully'))->success();
            }
        }

        Upload::query()->truncate();

        return back();
    }

    //Download project attachment
    public function attachment_download($id)
    {
        $project_attachment = Upload::find($id);
        try {
            $file_path = public_path($project_attachment->file_name);
            return Response::download($file_path);
        } catch (\Exception $e) {
            flash(translate('File does not exist!'))->error();
            return back();
        }
    }
    //Download project attachment
    public function file_info(Request $request)
    {
        $file = Upload::findOrFail($request['id']);

        return (auth()->user()->user_type == 'seller')
            ? view('seller.uploads.info', compact('file'))
            : view('backend.uploaded_files.info', compact('file'));
    }

    /**
     * Rename an uploaded file both on disk and in the database.
     */
    public function rename(Request $request, Upload $upload)
    {
        if (auth()->user()->user_type === 'seller' && $upload->user_id !== auth()->user()->id) {
            abort(403, translate("You don't have permission for renaming this file."));
        }

        $request->validate([
            'new_name' => 'required|string|max:190',
        ]);

        $baseName = trim(pathinfo($request->input('new_name'), PATHINFO_FILENAME));
        if ($baseName === '') {
            return response()->json(['message' => translate('A valid file name is required.')], 422);
        }

        $disk = $upload->disk ?? config('filesystems.default');
        $storage = Storage::disk($disk);

        $directory = trim(pathinfo($upload->file_name, PATHINFO_DIRNAME), '.');
        $directory = $directory === '' ? '' : $directory . '/';

        $cleanBase = Str::slug($baseName, '_');
        $extension = $upload->extension;

        $targetPath = $directory . $cleanBase . '.' . $extension;
        $counter = 1;
        while ($storage->exists($targetPath)) {
            $targetPath = $directory . $cleanBase . '_' . $counter . '.' . $extension;
            $counter++;
        }

        // Ensure the directory exists for local disk
        if ($disk === 'local' && $directory !== '') {
            $storage->makeDirectory($directory);
        }

        if ($storage->exists($upload->file_name)) {
            // move is not reliable for S3, so use copy + delete there
            if (method_exists($storage, 'move') && $disk === 'local') {
                $storage->move($upload->file_name, $targetPath);
            } else {
                $storage->copy($upload->file_name, $targetPath);
                $storage->delete($upload->file_name);
            }
        }

        $upload->file_name = $targetPath;
        $upload->file_original_name = $baseName;
        $upload->save();

        return response()->json([
            'message' => translate('File renamed successfully'),
            'file' => [
                'id' => $upload->id,
                'file_name' => $upload->file_name,
                'file_original_name' => $upload->file_original_name,
                'extension' => $upload->extension,
                'full_path' => my_asset($upload->file_name),
            ],
        ]);
    }

    public function storeFolder(Request $request)
    {
        if ($denied = $this->folderDenied()) {
            return $denied;
        }

        $name = trim((string) $request->input('name'));
        $parentId = $this->nullableFolderId($request->input('parent_id'));

        if ($name === '' || strlen($name) > 190) {
            flash(translate('Enter a folder name.'))->error();
            return back();
        }

        if ($parentId && !UploadFolder::where('id', $parentId)->exists()) {
            flash(translate('That folder was not found.'))->error();
            return back();
        }

        if ($this->folderNameTaken($name, $parentId)) {
            flash(translate('A folder with this name already exists here.'))->error();
            return back();
        }

        UploadFolder::create([
            'name' => $name,
            'parent_id' => $parentId,
            'user_id' => auth()->id(),
        ]);

        flash(translate('Folder created successfully'))->success();
        return back();
    }

    public function renameFolder(Request $request, UploadFolder $folder)
    {
        if ($denied = $this->folderDenied()) {
            return $denied;
        }

        $name = trim((string) $request->input('name'));
        if ($name === '' || strlen($name) > 190) {
            flash(translate('Enter a folder name.'))->error();
            return back();
        }

        if ($this->folderNameTaken($name, $folder->parent_id, $folder->id)) {
            flash(translate('A folder with this name already exists here.'))->error();
            return back();
        }

        $folder->name = $name;
        $folder->save();

        flash(translate('Folder renamed successfully'))->success();
        return back();
    }

    public function destroyFolder(Request $request, $id)
    {
        if ($denied = $this->folderDenied()) {
            return $denied;
        }

        $folder = UploadFolder::findOrFail($id);

        if (!$request->boolean('with_files')) {
            $parentId = $folder->parent_id;
            UploadFolder::where('parent_id', $folder->id)->update(['parent_id' => $parentId]);
            Upload::withoutGlobalScope('not_hidden')
                ->withTrashed()
                ->where('folder_id', $folder->id)
                ->update(['folder_id' => $parentId]);
            $folder->delete();

            flash(translate('Folder removed. Anything inside was moved up one level.'))->success();
            return back();
        }

        $folderIds = $this->descendantFolderIds($folder->id);
        Upload::withoutGlobalScope('not_hidden')
            ->onlyTrashed()
            ->whereIn('folder_id', $folderIds)
            ->update(['folder_id' => null]);

        $deleted = 0;
        $blocked = 0;
        $files = Upload::withoutGlobalScope('not_hidden')->whereIn('folder_id', $folderIds)->get();
        foreach ($files as $file) {
            $result = $this->deleteStoredUpload($file);
            if ($result === 'deleted') {
                $deleted++;
            } else {
                $blocked++;
            }
        }

        $pending = $folderIds;
        $guard = 0;
        while ($pending && $guard < 1000) {
            $guard++;
            $progress = false;
            foreach ($pending as $key => $folderId) {
                $hasChild = UploadFolder::where('parent_id', $folderId)->whereIn('id', $pending)->exists();
                $hasFiles = Upload::withoutGlobalScope('not_hidden')->where('folder_id', $folderId)->exists();
                if ($hasChild || $hasFiles) {
                    continue;
                }
                UploadFolder::where('id', $folderId)->delete();
                unset($pending[$key]);
                $progress = true;
            }
            if (!$progress) {
                break;
            }
        }

        if ($blocked > 0) {
            flash(translate('Deleted') . ' ' . $deleted . ' ' . translate('files. Files that are in use were kept, along with their folder.'))->warning();
        } else {
            flash(translate('Folder and its files were deleted.'))->success();
        }

        return back();
    }

    public function moveItems(Request $request)
    {
        if ($denied = $this->folderDenied()) {
            return $denied;
        }

        $destinationId = $this->nullableFolderId($request->input('destination_id'));
        $fileIds = array_values(array_unique(array_filter((array) $request->input('id', []))));
        $folderIds = array_values(array_unique(array_filter((array) $request->input('folder_ids', []))));

        if ($request->boolean('move_all')) {
            $sourceId = $this->nullableFolderId($request->input('source_id'));
            if ($sourceId === $destinationId) {
                flash(translate('Those files are already in this folder.'))->warning();
                return back();
            }

            $fileQuery = Upload::query();
            $this->applyUploadFilters($fileQuery, $request->input('search'), $request->input('type'), [
                'extension' => $request->input('extension'),
                'size_min' => $request->input('size_min'),
                'size_max' => $request->input('size_max'),
                'date_from' => $this->parseFilterDate($request->input('date_from')),
                'date_to' => $this->parseFilterDate($request->input('date_to')),
                'uploader' => $request->input('uploader'),
            ]);
            if ($sourceId) {
                $fileQuery->where('folder_id', $sourceId);
            } else {
                $fileQuery->whereNull('folder_id');
            }
            $fileIds = $fileQuery->pluck('id')->all();

            $folderIds = [];
            if (!$request->filled('type')) {
                $folderQuery = UploadFolder::query();
                if ($sourceId) {
                    $folderQuery->where('parent_id', $sourceId);
                } else {
                    $folderQuery->whereNull('parent_id');
                }
                if ($request->filled('search')) {
                    $folderQuery->where('name', 'like', '%' . $request->input('search') . '%');
                }
                $folderIds = $folderQuery->pluck('id')->all();
            }
        }

        if ($fileIds === [] && $folderIds === []) {
            flash($request->boolean('move_all')
                ? translate('Nothing to move.')
                : translate('Select files or folders to move.'))->warning();
            return back();
        }

        if ($destinationId && !UploadFolder::where('id', $destinationId)->exists()) {
            flash(translate('That folder was not found.'))->error();
            return back();
        }

        $destination = $destinationId ? UploadFolder::find($destinationId) : null;
        $skipped = false;

        if ($fileIds !== []) {
            Upload::whereIn('id', $fileIds)->update(['folder_id' => $destinationId]);
        }

        foreach ($folderIds as $folderId) {
            $folder = UploadFolder::find($folderId);
            if (!$folder) {
                continue;
            }

            if ($destination && ($destination->id === $folder->id || $folder->containsFolder($destination->id))) {
                $skipped = true;
                continue;
            }

            if ($this->folderNameTaken($folder->name, $destinationId, $folder->id)) {
                $skipped = true;
                continue;
            }

            $folder->parent_id = $destinationId;
            $folder->save();
        }

        if ($skipped) {
            flash(translate('Some folders were not moved because the destination is inside them or the name is already used there.'))->warning();
        } else {
            flash(translate('Moved successfully'))->success();
        }

        return back();
    }

    protected function folderDenied()
    {
        if (!UploadFolder::ready()) {
            flash(translate('Folders are not ready yet. Run the folder SQL first.'))->error();
            return back();
        }

        if (env('DEMO_MODE') == 'On') {
            flash(translate('Data can not change in demo mode.'))->info();
            return back();
        }

        if (auth()->user()->user_type == 'seller') {
            flash(translate("You don't have permission for this."))->error();
            return back();
        }

        return null;
    }

    protected function nullableFolderId($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected function folderNameTaken(string $name, ?int $parentId, ?int $ignoreId = null): bool
    {
        return UploadFolder::query()
            ->where('name', $name)
            ->when($parentId, function ($query) use ($parentId) {
                $query->where('parent_id', $parentId);
            }, function ($query) {
                $query->whereNull('parent_id');
            })
            ->when($ignoreId, function ($query) use ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            })
            ->exists();
    }

    protected function applyUploadFilters($query, $search, $typeFilter, array $extra = [])
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('file_original_name', 'like', '%' . $search . '%')
                    ->orWhere('extension', 'like', '%' . $search . '%')
                    ->orWhere('file_name', 'like', '%' . $search . '%');
            });
        }

        if ($typeFilter) {
            $query->where(function ($q) use ($typeFilter) {
                $normalized = strtolower($typeFilter);
                $typeBuckets = ['image', 'video', 'audio', 'archive', 'document'];
                $extensionGroups = [
                    'pdf'   => ['pdf'],
                    'doc'   => ['doc', 'docx'],
                    'docx'  => ['doc', 'docx'],
                    'excel' => ['xls', 'xlsx', 'ods', 'csv'],
                    'xls'   => ['xls'],
                    'xlsx'  => ['xlsx'],
                    'csv'   => ['csv'],
                    'zip'   => ['zip', 'rar', '7z'],
                ];
                if (in_array($normalized, $typeBuckets, true)) {
                    $q->where('type', $normalized);
                } elseif (isset($extensionGroups[$normalized])) {
                    $q->whereIn('extension', $extensionGroups[$normalized]);
                } else {
                    $q->where('extension', $normalized);
                }
            });
        }

        $extension = strtolower(ltrim(trim((string) ($extra['extension'] ?? '')), '.'));
        if ($extension !== '') {
            $query->where('extension', $extension);
        }

        if (isset($extra['size_min']) && $extra['size_min'] !== null && $extra['size_min'] !== '' && is_numeric($extra['size_min'])) {
            $query->where('file_size', '>=', (int) round(((float) $extra['size_min']) * 1024));
        }

        if (isset($extra['size_max']) && $extra['size_max'] !== null && $extra['size_max'] !== '' && is_numeric($extra['size_max'])) {
            $query->where('file_size', '<=', (int) round(((float) $extra['size_max']) * 1024));
        }

        if (!empty($extra['date_from'])) {
            $query->whereDate('created_at', '>=', $extra['date_from']);
        }

        if (!empty($extra['date_to'])) {
            $query->whereDate('created_at', '<=', $extra['date_to']);
        }

        $uploader = trim((string) ($extra['uploader'] ?? ''));
        if ($uploader !== '') {
            $query->whereHas('user', function ($userQuery) use ($uploader) {
                $userQuery->where('name', 'like', '%' . $uploader . '%');
            });
        }

        return $query;
    }

    protected function parseFilterDate($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    protected function sortFolders($folders, string $sortBy, string $sortOrder)
    {
        $descending = $sortOrder === 'desc';

        return $folders->sortBy(function ($folder) use ($sortBy) {
            if (in_array($sortBy, ['created_at', 'updated_at'], true)) {
                return optional($folder->{$sortBy})->timestamp ?? 0;
            }

            return strtolower((string) $folder->name);
        }, SORT_REGULAR, $descending)->values();
    }

    protected function descendantFolderIds(int $folderId): array
    {
        $ids = [$folderId];
        $children = UploadFolder::where('parent_id', $folderId)->pluck('id');
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->descendantFolderIds((int) $childId));
        }

        return $ids;
    }
}
