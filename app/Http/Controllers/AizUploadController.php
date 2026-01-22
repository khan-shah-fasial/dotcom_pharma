<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Upload;
use Response;
use Auth;
use Storage;
use Image;
use enshrined\svgSanitize\Sanitizer;
use Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class AizUploadController extends Controller
{
    public function index(Request $request)
    {

        $all_uploads = (auth()->user()->user_type == 'seller') ? Upload::where('user_id', auth()->user()->id) : Upload::query();
        $search = null;
        $sort_by = null;

        if ($request->search != null) {
            $search = $request->search;
            $all_uploads->where('file_original_name', 'like', '%' . $request->search . '%');
        }

        $sort_by = $request->sort;
        switch ($request->sort) {
            case 'newest':
                $all_uploads->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $all_uploads->orderBy('created_at', 'asc');
                break;
            case 'smallest':
                $all_uploads->orderBy('file_size', 'asc');
                break;
            case 'largest':
                $all_uploads->orderBy('file_size', 'desc');
                break;
            default:
                $all_uploads->orderBy('created_at', 'desc');
                break;
        }

        $all_uploads = $all_uploads->paginate(60)->appends(request()->query());


        return (auth()->user()->user_type == 'seller')
            ? view('seller.uploads.index', compact('all_uploads', 'search', 'sort_by'))
            : view('backend.uploaded_files.index', compact('all_uploads', 'search', 'sort_by'));
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
            $upload->disk = config('filesystems.default');
            $extension = strtolower($request->file('aiz_file')->getClientOriginalExtension());

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
                        
                        $dir = public_path('uploads/all/' . date('Y/m'));

                        if (!File::exists($dir)) {
                            File::makeDirectory($dir, 0777, true);
                        }

                        $path = 'uploads/all/' . date('Y/m') . '/' . Str::random(40) . '.' . $extension;
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
                    $path = $request->file('aiz_file')->store('uploads/all/' . date('Y/m'), 'local');
                }

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
                $upload->type = $type[$upload->extension];
                $upload->file_size = $size;
                $upload->save();
            }
            return '{}';
        }
    }

    public function get_uploaded_files(Request $request)
    {
        $uploads = Upload::where('user_id', Auth::user()->id);
        if ($request->search != null) {
            $uploads->where('file_original_name', 'like', '%' . $request->search . '%');
        }
        if ($request->sort != null) {
            switch ($request->sort) {
                case 'newest':
                    $uploads->orderBy('created_at', 'desc');
                    break;
                case 'oldest':
                    $uploads->orderBy('created_at', 'asc');
                    break;
                case 'smallest':
                    $uploads->orderBy('file_size', 'asc');
                    break;
                case 'largest':
                    $uploads->orderBy('file_size', 'desc');
                    break;
                default:
                    $uploads->orderBy('created_at', 'desc');
                    break;
            }
        }
        return $uploads->paginate(60)->appends(request()->query());
    }

    public function destroy($id)
    {
        $upload = Upload::findOrFail($id);

        if (auth()->user()->user_type == 'seller' && $upload->user_id != auth()->user()->id) {
            flash(translate("You don't have permission for deleting this!"))->error();
            return back();
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
            $upload->delete();
            flash(translate('File deleted successfully'))->success();
        } catch (\Exception $e) {
            $upload->delete();
            flash(translate('File deleted successfully'))->success();
        }
        return back();
    }

    public function bulk_uploaded_files_delete(Request $request)
    {
        if ($request->id) {
            foreach ($request->id as $file_id) {
                $this->destroy($file_id);
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
}
