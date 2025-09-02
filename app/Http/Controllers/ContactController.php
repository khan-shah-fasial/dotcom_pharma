<?php

namespace App\Http\Controllers;

use App\Mail\ContactMailManager;
use App\Mail\ProductEnquiryMailManager;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Mail;

class ContactController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:view_all_contacts'])->only('index');
        $this->middleware(['permission:reply_to_contact'])->only('reply_modal');
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
            
        // Default sorting by newest first
        $contacts = $contacts->orderBy('created_at', 'desc')->paginate(20);

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
}
