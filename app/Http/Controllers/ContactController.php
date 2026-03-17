<?php

namespace App\Http\Controllers;

use App\Mail\ContactMailManager;
use App\Mail\ProductEnquiryMailManager;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mail;

class ContactController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:view_all_contacts'])->only('index');
        $this->middleware(['permission:reply_to_contact'])->only('reply_modal');
    }

    /**
     * Determine if a support contact already has a review stored.
     */
    protected function supportHasReview(Contact $contact): bool
    {
        if ($contact->review === null) {
            return false;
        }

        // Because of casts this will usually be an array, but be defensive.
        $review = $contact->review;

        if (is_array($review)) {
            return !empty($review['rating']);
        }

        // Fallback for old scalar values.
        return !empty($review);
    }

    public function product_enquiry_index(Request $request)
    {
        $contacts = Contact::query();

        // Filter by contact type if provided
        if ($request->type != null) {
            $contacts->where('type', $request->type);
        }
        
        // Search by contact name, product name, or pincode
        if ($request->search != null) {
            $sort_search = $request->search;
            $contacts->where(function ($query) use ($sort_search) {
                $query->where('name', 'like', '%' . $sort_search . '%')
                      ->orWhere('email', 'like', '%' . $sort_search . '%')
                      ->orWhere('phone', 'like', '%' . $sort_search . '%')
                      ->orWhereHas('product', function ($q) use ($sort_search) {
                          $q->where('name', 'like', '%' . $sort_search . '%'); 
                      });
            });
        }
        
        // Improved Date Range Filter (Handles same date correctly)
        if ($request->date_from && $request->date_to) {
            // Check if both dates are the same
            if ($request->date_from == $request->date_to) {
                $contacts->whereDate('created_at', $request->date_from);
            } else {
                $contacts->whereBetween('created_at', [
                    $request->date_from . ' 00:00:00',
                    $request->date_to . ' 23:59:59'
                ]);
            }
        } elseif ($request->date_from) {
            $contacts->whereDate('created_at', '>=', $request->date_from);
        } elseif ($request->date_to) {
            $contacts->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Default sorting by newest first
        $contacts = $contacts->orderBy('created_at', 'desc')->paginate(20);

        return view('backend.support.contact.product_enquiry', compact('contacts'));
    }

    public function prescription_enquiry_index(Request $request)
    {
        $contacts = Contact::query();

        // Filter by contact type if provided
        if ($request->type != null) {
            $contacts->where('type', $request->type);
        }
        
        // Search by contact name, product name, or pincode
        if ($request->search != null) {
            $sort_search = $request->search;
            $contacts->where(function ($query) use ($sort_search) {
                $query->where('name', 'like', '%' . $sort_search . '%')
                      ->orWhere('email', 'like', '%' . $sort_search . '%')
                      ->orWhere('phone', 'like', '%' . $sort_search . '%');
            });
        }
        
        // Improved Date Range Filter (Handles same date correctly)
        if ($request->date_from && $request->date_to) {
            // Check if both dates are the same
            if ($request->date_from == $request->date_to) {
                $contacts->whereDate('created_at', $request->date_from);
            } else {
                $contacts->whereBetween('created_at', [
                    $request->date_from . ' 00:00:00',
                    $request->date_to . ' 23:59:59'
                ]);
            }
        } elseif ($request->date_from) {
            $contacts->whereDate('created_at', '>=', $request->date_from);
        } elseif ($request->date_to) {
            $contacts->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Default sorting by newest first
        $contacts = $contacts->orderBy('created_at', 'desc')->paginate(20);

        return view('backend.support.contact.prescription_enquiry', compact('contacts'));
    }

    /**
     * Dedicated index for support enquiries (admin).
     * Ensures type=support and default status=open when not provided.
     */
    public function support_index(Request $request)
    {
        if (!$request->has('type')) {
            $request->merge(['type' => 'support']);
        }

        if (!$request->has('status')) {
            $request->merge(['status' => 'open']);
        }

        return $this->index($request);
    }

    public function index(Request $request)
    {
        $contacts = Contact::query();

        // Filter by contact type if provided
        if ($request->type != null) {
            $contacts->where('type', $request->type);
        }
        
        // Search by contact name, product name, or pincode
        if ($request->search != null) {
            $sort_search = $request->search;
            $contacts->where(function ($query) use ($sort_search) {
                $query->where('name', 'like', '%' . $sort_search . '%')
                      ->orWhere('email', 'like', '%' . $sort_search . '%')
                      ->orWhere('phone', 'like', '%' . $sort_search . '%')
                      ->orWhereHas('product', function ($q) use ($sort_search) {
                          $q->where('name', 'like', '%' . $sort_search . '%');
                      });
            });
        }
        
        // Improved Date Range Filter (Handles same date correctly)
        if ($request->date_from && $request->date_to) {
            // Check if both dates are the same
            if ($request->date_from == $request->date_to) {
                $contacts->whereDate('created_at', $request->date_from);
            } else {
                $contacts->whereBetween('created_at', [
                    $request->date_from . ' 00:00:00',
                    $request->date_to . ' 23:59:59'
                ]);
            }
        } elseif ($request->date_from) {
            $contacts->whereDate('created_at', '>=', $request->date_from);
        } elseif ($request->date_to) {
            $contacts->whereDate('created_at', '<=', $request->date_to);
        }
            
        // Filter by status (for support listing)
        if ($request->filled('status')) {
            $contacts->where('status', $request->status);
        }

        // Default sorting by newest first
        $contacts = $contacts->orderBy('created_at', 'desc')->paginate(20);

        if ($request->type === 'support') {
            return view('backend.support.contact.support', compact('contacts'));
        }

        return view('backend.support.contact.contacts', compact('contacts'));
    }

    public function query_modal(Request $request)
    {
        $contact = Contact::findOrFail($request->id);
        return view('backend.support.contact.query_modal', compact('contact'));
    }

    public function reply_modal(Request $request)
    {
        $contact = Contact::findOrFail($request->id);
        return view('backend.support.contact.reply_modal', compact('contact'));
    }

    public function reply(Request $request)
    {
        $contact = Contact::findOrFail($request->contact_id);
        $admin = get_admin();

        $array['name'] = $admin->name;
        $array['email'] = $admin->email;
        $array['phone'] = $admin->phone;
        $array['content'] = str_replace("\n", "<br>", $request->reply);
        $array['subject'] = translate('Query Contact Reply');
        $array['from'] = $admin->email;

        try {
            Mail::to($contact->email)->queue(new ContactMailManager($array));
            $contact->update([
                'reply' => $request->reply,
            ]);
        } catch (\Exception $e) {
            flash(translate('Something Went wrong'))->error();
            return back();
        }
        flash(translate('Reply has been sent successfully'))->success();
        return back();
    }

    public function contact(Request $request)
    {
        $admin = get_admin();

        $array['name'] = $request->name;
        $array['email'] = $request->email;
        $array['phone'] = $request->phone;
        $array['content'] = str_replace("\n", "<br>", $request->content);
        $array['subject'] = translate('Query Contact');
        $array['from'] = $request->email;

        try {
            Mail::to($admin->email)->queue(new ContactMailManager($array));
            Contact::insert([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'content' => $request->content,
            ]);
        } catch (\Exception $e) {
            flash(translate('Something Went wrong'))->error();
            return back();
        }
        flash(translate('Query has been sent successfully'))->success();
        return back();
    }

    /**
     * Update status for support contacts (open/closed).
     */
    public function support_update_status(Request $request)
    {
        $request->validate([
            'id'     => 'required|integer',
            'status' => 'required|string|in:open,closed',
        ]);

        $contact = Contact::findOrFail($request->id);

        if ($contact->type !== 'support') {
            abort(403);
        }

        $contact->status = $request->status;
        $contact->save();

        // When closing a support enquiry for the first time, send review email
        if ($request->status === 'closed' && !$this->supportHasReview($contact)) {
            $rawData = $contact->data;
            $data = is_array($rawData)
                ? $rawData
                : ($rawData ? json_decode($rawData, true) : []);
            $customer = $data['customer'] ?? [];
            $staff    = $data['staff'] ?? [];

            $tokenPayload = [
                'contact_id'  => $contact->id,
                'customer_id' => $customer['id'] ?? null,
                'type'        => 'support',
            ];

            try {
                $token = Crypt::encryptString(json_encode($tokenPayload));
            } catch (\Exception $e) {
                Log::error('Support review token generation failed', [
                    'error' => $e->getMessage(),
                    'contact_id' => $contact->id,
                ]);
                return response()->json(['success' => true]);
            }

            $reviewUrl = route('support.review', ['token' => $token]);

            $payload = array_merge($data, [
                'review_url' => $reviewUrl,
            ]);

            $array = [
                'name'                => $customer['name'] ?? $contact->name,
                'email'               => $customer['email'] ?? $contact->email,
                'phone'               => $customer['phone'] ?? $contact->phone,
                'content'             => view('emails.support_review_request', ['payload' => $payload])->render(),
                'subject'             => translate('Please review your support experience') . ' - ' . env('APP_NAME'),
                'from'                => get_admin()->email ?? config('mail.from.address'),
                'hide_contact_details'=> true,
            ];

            try {
                Mail::to($array['email'])->queue(new ContactMailManager($array));
            } catch (\Exception $e) {
                Log::error('Support review email failed', [
                    'error' => $e->getMessage(),
                    'contact_id' => $contact->id,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Store a support request created from the frontend Support page.
     */
    public function support_store(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        $rules = [
            'staff_id'     => 'required|integer',
            'channel'      => 'required|in:video,callback',
            'scheduled_at' => 'required|date',
            'name'         => 'required|string|max:255',
            'notes'        => 'nullable|string|max:1000',
        ];

        // Channel-specific contact validation
        if ($request->channel === 'video') {
            $rules['email'] = 'required|email|max:255';
            $rules['phone'] = 'nullable|regex:/^[0-9\-\+\s\(\)]*$/';
        } elseif ($request->channel === 'callback') {
            $rules['phone'] = 'required|regex:/^[0-9\-\+\s\(\)]*$/';
            $rules['email'] = 'nullable|email|max:255';
        } else {
            // Fallback: at least one contact method must be present.
            $rules['email'] = 'nullable|email|max:255';
            $rules['phone'] = 'nullable|regex:/^[0-9\-\+\s\(\)]*$/';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                flash($error)->error();
            }
            return back();
        }

        $staffUser = User::whereHas('staff', function ($q) use ($request) {
            $q->where('id', $request->staff_id);
        })->first();

        if (!$staffUser) {
            flash(translate('Selected staff could not be found.'))->error();
            return back();
        }

        $admin = get_admin();

        $customerName  = $request->name ?? $user->name;
        $customerEmail = $request->email ?? $user->email;
        $customerPhone = $request->phone ?? $user->phone;

        $payload = [
            'channel'       => $request->channel,
            'scheduled_at'  => $request->scheduled_at,
            'notes'         => $request->notes,
            'customer'      => [
                'id'    => $user->id,
                'name'  => $customerName,
                'email' => $customerEmail,
                'phone' => $customerPhone,
            ],
            'staff'         => [
                'staff_id' => (int) $request->staff_id,
                'user_id'  => $staffUser->id,
                'name'     => $staffUser->name,
                'email'    => $staffUser->email,
                'phone'    => $staffUser->phone,
            ],
        ];

        $array = [
            'name'    => $customerName,
            'email'   => $customerEmail,
            'phone'   => $customerPhone,
            'content' => view('emails.support_request_summary', ['payload' => $payload])->render(),
            'subject' => translate('Support Request') . ' - ' . env('APP_NAME'),
            'from'    => $user->email,
        ];

        try {
            // Try to send email, but don't block saving if it fails
            Mail::to($admin->email)->queue(new ContactMailManager($array));
        } catch (\Exception $e) {
            Log::error('Support request email failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'staff_id' => $request->staff_id,
            ]);
        }

        Contact::create([
            'user_id' => $user->id,
            'type'    => 'support',
            'name'    => $customerName,
            'email'   => $customerEmail,
            'phone'   => $customerPhone,
            'content' => $request->notes,
            'data'    => $payload,
            'status'  => 'open',
        ]);

        flash(translate('Your support request has been submitted successfully.'))->success();
        return back();
    }

    /**
     * Public review form (no login required).
     */
    public function support_review_form(string $token)
    {
        try {
            $decoded = json_decode(Crypt::decryptString($token), true);
        } catch (\Exception $e) {
            abort(404);
        }

        if (!is_array($decoded) || ($decoded['type'] ?? null) !== 'support') {
            abort(404);
        }

        $contact = Contact::findOrFail($decoded['contact_id']);

        if ($contact->type !== 'support') {
            abort(404);
        }

        $rawData = $contact->data;
        $data = is_array($rawData)
            ? $rawData
            : ($rawData ? json_decode($rawData, true) : []);

        return view('frontend.support.review', [
            'contact' => $contact,
            'data'    => $data,
            'token'   => $token,
        ]);
    }

    /**
     * Store support review from public form.
     */
    public function support_review_store(Request $request)
    {
        $request->validate([
            'token'   => 'required|string',
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        try {
            $decoded = json_decode(Crypt::decryptString($request->token), true);
        } catch (\Exception $e) {
            abort(404);
        }

        if (!is_array($decoded) || ($decoded['type'] ?? null) !== 'support') {
            abort(404);
        }

        $contact = Contact::findOrFail($decoded['contact_id']);

        if ($contact->type !== 'support') {
            abort(404);
        }

        // Link only works until there is no stored review
        if ($this->supportHasReview($contact)) {
            return redirect()->route('support.review', ['token' => $request->token]);
        }

        $contact->review = [
            'rating'  => (int) $request->rating,
            'comment' => $request->comment ?: null,
        ];
        $contact->save();

        return redirect()->route('support.review', ['token' => $request->token])
            ->with('success', translate('Thank you for your review!'));
    }

    public function product_enquiry_store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|regex:/^[0-9\-\+\s\(\)]*$/', 
            // 'pincode' => 'required',
            // 'g-recaptcha-response' => [
            //     Rule::when(get_setting('google_recaptcha') == 1, ['required', new Recaptcha()], ['sometimes'])
            // ]
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            foreach ($errors as $error) {
                flash($error)->error(); 
            }
            return back();
        }

        // $pincode_data = getLocationByPostalCode($request->pincode);
        $admin = get_admin();
        $product_img = getProductImage($request->product_id);

        $array['name'] = $request->name;
        $array['email'] = $request->email;
        $array['phone'] = $request->phone;
        // $array['pincode'] = $request->pincode;
        $array['url'] = $request->current_url;
        $array['product_img'] = $product_img;
        // $array['pincode_data'] = json_encode($pincode_data, JSON_UNESCAPED_UNICODE);
        $array['content'] = str_replace("\n", "<br>", $request->content);
        $array['subject'] = translate('Product Enquiry') .' - '. env('APP_NAME');
        $array['from'] = $request->email;

        try {
            Mail::to($admin->email)->queue(new ProductEnquiryMailManager($array));
            Contact::insert([
                'type' => $request->type,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'content' => $request->content,
                'product_id' => $request->product_id,
                // 'pincode' => $request->pincode,
                'url' => $request->current_url,
                // 'pincode_data'=> json_encode($pincode_data, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
        flash(translate('Product Enquiry has been sent successfully'))->success();
        return back();
    }


    public function prescription_store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|required_without:phone',
            'phone' => 'nullable|regex:/^[0-9\-\+\s\(\)]*$/|required_without:email',
            'prescription_file' => 'required|file|mimes:jpg,jpeg,png,gif,pdf|max:5120' // max 5MB
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                flash($error)->error(); // if you use laracasts/flash or your flash helper
            }
            return back();
        }

        try {
            // store file in storage/app/public/prescriptions
            $file = $request->file('prescription_file');
            $storedPath = $file->store('prescriptions', 'public'); // returns e.g. prescriptions/abc.jpg

            // optional: get public url using Storage::url($storedPath)
            $publicUrl = Storage::url($storedPath);

            $admin = get_admin(); // your helper function — optional if you want to email admin

            // prepare array for mail if needed
            $array = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'attachment_url' => $publicUrl,
                'subject' => 'Prescription Upload - ' . env('APP_NAME'),
                'from' => $request->email,
            ];

            // Optional: send mail to admin (similar style to your product enquiry)
            // Mail::to($admin->email)->queue(new PrescriptionReceivedMail($array));

            // Insert into contacts table (reuse existing Contact model/table)
            Contact::insert([
                'type' => 'prescription',
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                // store raw path so you can serve via Storage::url later
                'attachment' => $storedPath,
                
                'content' => null,
                //'url' => $request->fullUrl(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // useful for debugging in dev only
            \Log::error('Prescription upload error: ' . $e->getMessage());
            flash('Something went wrong while uploading prescription.')->error();
            return back();
        }

        flash('Prescription uploaded successfully.')->success();
        return back();
    }



}
