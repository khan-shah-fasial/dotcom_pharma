<?php
// app/Http/Controllers/RequestDocController.php
namespace App\Http\Controllers;

use App\Mail\MailManager;
use App\Mail\RequestDocDisapproved;
use App\Models\RequestDoc;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\BusinessSetting;
use App\Models\Upload;
use App\Services\PdfStampService;

class RequestDocController extends Controller
{
    // 1) Public link goes here (simple anchor in layout to route('request-doc.form'))

    // 2) Show the request page
    public function form(PdfStampService $stamper)
    {
        $user = Auth::user();
        $latestRequest = RequestDoc::where('user_id', $user->id)
                    ->latest('created_at')
                    ->first();
        $setting = BusinessSetting::where('type', 'business_request_doc')->first();
        $currentPdfValue = $setting->value ?? '';

        // Default: no download link
        $stampedUrls = [];
        $downloadUrl = null;

        if ($latestRequest && (int)$latestRequest->status === 1 && $currentPdfValue) {

            // Resolve absolute source PDF paths from your comma IDs (e.g., "1531,1522")
            $sourcePaths = resolve_pdf_paths_from_ids($currentPdfValue);

            $stampedUrls = [];
            $downloadUrl = null;

            if (!empty($sourcePaths)) {
                // Header payload from latestRequest
                $header = [
                    'name'       => $latestRequest->name,
                    'type'       => $latestRequest->type,
                    'type_input' => $latestRequest->type_input,
                ];

                // Output dir per request: storage/app/public/request_docs/{request_id}/stamped_*.pdf
                $outputBase = 'request_docs/' . $latestRequest->id;

                // ✅ Stamp ALL PDFs and get an array of public URLs back
                // stampMany(array $absolutePaths, array $header, string $outputBaseDir): array
                $stampedUrls = $stamper->stampMany($sourcePaths, $header, $outputBase);

                // Primary button points to the first stamped file
                $downloadUrl = $stampedUrls[0] ?? null;

                // If you want to attach it onto the model transiently:
                $latestRequest->file_link = $downloadUrl;
            }
        }

        return view('frontend.request-doc.form', compact(
            'user',
            'latestRequest',
            'currentPdfValue',
            'downloadUrl',
            'stampedUrls'
        ));
    }

    // 3/4/5) Handle modal submission: email admin + save DB with dates
    public function store(Request $request)
    {
        try {
            $request->validate([
                'email'      => ['required', 'email'],
                'user_id'    => ['required', 'integer'],
                'name'       => ['required', 'string', 'max:255'],
                'type'       => ['required', 'in:tender,bid'],
                'type_input' => ['nullable', 'string', 'max:255'],
                'note'       => ['nullable', 'string', 'max:5000'],
            ]);

            // Timezone handling
            $todayIST   = Carbon::now('Asia/Kolkata')->toDateString();
            $expiryIST  = Carbon::now('Asia/Kolkata')->addYear()->toDateString();

            $doc = RequestDoc::create([
                'user_id'     => $request->integer('user_id'),
                'name'        => $request->string('name'),
                'email'       => $request->string('email'),
                'type'        => $request->string('type'),
                'type_input'  => $request->input('type_input'),
                'note'        => $request->input('note'),
                'start_date'  => $todayIST,
                'expiry_date' => $expiryIST,
                'status'      => 0,
            ]);

            // Notify admin
            $admin = get_admin();
            if ($admin) {
                
                $emailBody = '
                    <h2 style="color:#0d6efd;">A new document request has been received.</h2>

                    <table style="border-collapse: separate; border-spacing: 0 4px; width:100%;">
                        <tr>
                            <td style="padding:6px 8px;"><strong>Name:</strong></td>
                            <td>' . e($doc->name) . '</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 8px;"><strong>Email:</strong></td>
                            <td>' . e($doc->email) . '</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 8px;"><strong>Type:</strong></td>
                            <td>' . e(ucfirst($doc->type)) . '</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 8px;"><strong>Reference:</strong></td>
                            <td>' . e($doc->type_input ?: "N/A") . '</td>
                        </tr>
                    </table>

                    <p style="margin-top:20px;">
                        Please review and take the necessary action.
                    </p>

                    <hr style="border:0;border-top:1px solid #ddd;">
                    <p style="color:#6c757d;font-size:13px;">
                        — DotCom Pharma Notification System
                    </p>
                ';

                $array = [
                    'subject' => 'Document Request Received — DotCom Pharma',
                    'content' => $emailBody,
                ];

                try {
                    Mail::to($admin->email)->queue(new MailManager($array));
                } catch (\Exception $e) {
                    // Log the mail failure but don’t break user flow
                    \Log::error('Mail failed to send: ' . $e->getMessage());
                }
            }

            return response()->json(['status' => 'success', 'message' => 'Request submitted successfully!']);
        } catch (\Throwable $e) {
            \Log::error('RequestDoc store failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => '❌ Something went wrong! Please try again.']);
        }
    }



    // 6) Admin listing with filters
    public function adminIndex(Request $request)
    {
        $this->authorize('admin'); // or middleware
        $q = RequestDoc::query();

        if ($search = $request->get('q')) {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('type_input', 'like', "%$search%");
            });
        }

        if ($status = $request->get('status')) {
            $q->where('status', intval($status));
        }

        if ($type = $request->get('type')) {
            $q->where('type', $type);
        }

        if ($from = $request->get('from')) {
            $q->whereDate('start_date', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $q->whereDate('start_date', '<=', $to);
        }

        $docs = $q->latest()->paginate(20);
        $setting = BusinessSetting::where('type', 'business_request_doc')->first();
        $currentPdfValue = $setting->value ?? '';
        return view('backend.customer.customers.business-request-doc.index', compact('docs', 'currentPdfValue'));

    }


    // 7a) Approve
    public function approve(RequestDoc $doc)
    {
        $this->authorize('admin');
        $doc->update(['status' => 1, 'admin_note' => null]);

        if (request()->ajax()) {
            return response()->json([
                'ok' => true,
                'id' => $doc->id,
                'status_badge' => '<span class="badge badge-success">'.translate('Approved').'</span>',
                'actions_html' => '<span class="text-success">Approved</span>',
                'message' => translate('Request approved.')
            ]);
        }

        return back()->with('success', translate('Request approved.'));
    }

    public function disapprove(Request $request, RequestDoc $doc)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'admin_note' => ['required','string','max:5000']
        ]);

        // Update document status
        $doc->update([
            'status' => 2,
            'admin_note' => $validated['admin_note']
        ]);

        // Prepare email
        $admin = get_admin(); // Assuming it returns admin object
        $brand = 'DotCom Pharma'; // Define your brand name
        $noteHtml = nl2br(e($validated['admin_note'])); // Make sure notes are safe and formatted

        $emailBody = '
            <div style="background:#f6f8fa;padding:24px 12px;">
            <div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e6e8eb;">
                <div style="background:#0d6efd;padding:16px 20px;">
                    <h2 style="margin:0;font-size:20px;color:#ffffff;font-weight:600;">'.$brand.' — Document Request Update</h2>
                </div>

                <div style="padding:20px;">
                    <p style="margin:0 0 10px 0;font-size:15px;color:#333333;">
                        Hello <strong style="font-weight:600;">'.e($doc->name).'</strong>,
                    </p>
                    <p style="margin:0 0 16px 0;font-size:14px;color:#555;">
                        Your document request has been <strong style="color:#dc3545;">disapproved</strong>. Please find the details below:
                    </p>

                    <table style="width:100%;border-collapse:separate;border-spacing:0 8px;font-size:14px;color:#333;">
                        <tr>
                            <td style="width:170px;padding:8px 10px;background:#f8f9fa;border-radius:6px 0 0 6px;"><strong>Type</strong></td>
                            <td style="padding:8px 10px;background:#f8f9fa;border-radius:0 6px 6px 0;">'.e(Str::upper($doc->type)).'</td>
                        </tr>
                        <tr>
                            <td style="
                                vertical-align:top;
                                padding:8px 10px;
                                background:#fff3cd;
                                border:1px solid #ffeeba;
                                border-right:0;
                                border-radius:6px 0 0 6px;">
                                <strong>Note</strong>
                            </td>
                            <td style="
                                padding:8px 10px;
                                background:#fff;
                                border:1px solid #ffeeba;
                                border-left:0;
                                border-radius:0 6px 6px 0;
                                word-break: break-word;
                                white-space: normal;
                            ">
                                '.$noteHtml.'
                            </td>
                        </tr>
                    </table>

                    <p style="margin:16px 0 0 0;font-size:13px;color:#666;">
                        If you believe this was a mistake or need more information, please reply to this email with additional details.
                    </p>
                </div>

                <div style="padding:14px 20px;background:#f6f8fa;border-top:1px solid #e6e8eb;">
                    <p style="margin:0;font-size:12px;color:#6c757d;">— '.$brand.' Notification System</p>
                </div>
            </div>
            </div>
        ';

        $array = [
            'subject' => 'Document Request Update — '.$brand,
            'content' => $emailBody,
        ];

        try {
            // Send email to user (not admin)
            Mail::to($doc->email)
            ->cc($admin->email)        // CC to admin
            ->queue(new MailManager($array));
        } catch (\Exception $e) {
            \Log::error('Mail failed to send: '.$e->getMessage());
        }

        // AJAX response
        if ($request->ajax()) {
            return response()->json([
                'ok' => true,
                'id' => $doc->id,
                'status_badge' => '<span class="badge badge-danger">'.translate('Disapproved').'</span>',
                'actions_html' => '<span class="text-danger">Disapproved</span>',
                'message' => translate('Request disapproved and user notified.')
            ]);
        }

        return back()->with('success', translate('Request disapproved and user notified.'));
    }


    public function storeBusinessRequestPdfs(Request $request)
    {

        $raw = $request->input('docs');


        if (empty($raw)) {
            return back()
                ->withErrors(['docs' => translate('Please select at least one PDF file.')])
                ->withInput();
        }

        // Step 2️⃣: Normalize into an array of IDs
        $ids = is_array($raw)
            ? $raw
            : json_decode($raw, true);

        if (!is_array($ids)) {
            // fallback for comma-separated
            $ids = array_filter(array_map('trim', explode(',', $raw)));
        }

        if (empty($ids)) {
            return back()
                ->withErrors(['docs' => translate('Please select at least one PDF file.')])
                ->withInput();
        }

        // Step 3️⃣: Validate that all file IDs exist and are PDFs
        $uploads = Upload::whereIn('id', $ids)->get(['id', 'extension']);

        foreach ($uploads as $upload) {
            $ext = strtolower($upload->extension);
            if ($ext !== 'pdf') {
                return back()
                    ->withErrors(['docs' => translate('Only PDF files are allowed.')])
                    ->withInput();
            }
        }

        // Step 4️⃣: Save as comma-separated string (79,80,43,58)
        $value = implode(',', $ids);

        $setting = BusinessSetting::where('type', 'business_request_doc')->first();

        if ($setting) {
            // Update existing record
            $setting->update(['value' => $value]);
        } else {
            // Create new record
            BusinessSetting::create([
                'type'  => 'business_request_doc',
                'value' => $value,
            ]);
        }

        return back()->with('success', translate('Business request PDFs saved successfully.'));
    }

}
