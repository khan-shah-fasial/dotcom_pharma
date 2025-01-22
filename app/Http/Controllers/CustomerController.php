<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Utility\EmailUtility;
use Hash;

class CustomerController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:view_all_customers'])->only('index');
        $this->middleware(['permission:add_customer'])->only('create');
        $this->middleware(['permission:login_as_customer'])->only('login');
        $this->middleware(['permission:ban_customer'])->only('ban');
        $this->middleware(['permission:delete_customer'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sort_search = $request->search ?? null;
        $company_name = $request->company_name ?? null;
        $verification_status =  $request->verification_status ?? null;
        $bank_details =  $request->bank_details ?? null;
        $license_details =  $request->license_details ?? null;
        $dl_expiry_Data =  $request->dl_expiry_Data ?? null;
        $gst_no =  $request->gst_no ?? null;
        $transport_Details =  $request->transport_Details ?? null;

        $users = User::where('user_type', 'customer')->orderBy('created_at', 'desc');
        // if($verification_status != null){
        //     $users = $verification_status == 'verified' ? $users->where('email_verified_at', '!=', null) : $users->where('email_verified_at', null);
        // }
        if($verification_status != null){
            $users = $verification_status == 'verified' ? $users->where('approval_Status', 1) : $users->where('approval_Status', 0);
        }
        if ($sort_search != null){
            $sort_search = $request->search;
            $users->where(function ($q) use ($sort_search){
                $q->where('name', 'like', '%'.$sort_search.'%')->orWhere('email', 'like', '%'.$sort_search.'%')->orWhere('phone', 'like', '%'.$sort_search.'%')->orWhere('tel_number', 'like', '%'.$sort_search.'%');
            });
        }
        if ($company_name != null){
            $company_name = $request->company_name;
            $users->where(function ($q) use ($company_name){
                $q->where('company_name', 'like', '%'.$company_name.'%');
            });
        }
        if ($gst_no != null){
            $gst_no = $request->gst_no;
            $users->where(function ($q) use ($gst_no){
                $q->where('gst_no', 'like', '%'.$gst_no.'%');
            });
        }
        if ($bank_details != null){
            $bank_details = $request->bank_details;
            $users->where(function ($q) use ($bank_details){
                $q->where('bank_name', 'like', '%'.$bank_details.'%')->orWhere('account_no', 'like', '%'.$bank_details.'%')->orWhere('branch_no', 'like', '%'.$bank_details.'%')->orWhere('branch_code', 'like', '%'.$bank_details.'%')->orwhere('ifsc_code', 'like', '%'.$bank_details.'%')->orwhere('micr_code', 'like', '%'.$bank_details.'%')->orwhere('customer_care_executive', 'like', '%'.$bank_details.'%');
            });
        }
        if ($license_details != null){
            $license_details = $request->license_details;
            $users->where(function ($q) use ($license_details){
                $q->where('cc_no', 'like', '%'.$license_details.'%')->orWhere('d_l_no_1', 'like', '%'.$license_details.'%')->orWhere('d_l_no_2', 'like', '%'.$license_details.'%')->orWhere('d_l_no_3', 'like', '%'.$license_details.'%');
            });
        }
        if ($dl_expiry_Data != null){
            $dl_expiry_Data = $request->dl_expiry_Data;
            $users->where(function ($q) use ($dl_expiry_Data){
                $q->where('d_l_exp_Date', $dl_expiry_Data);
            });
        }
        if ($transport_Details != null){
            $transport_Details = $request->transport_Details;
            $users->where(function ($q) use ($transport_Details){
                $q->where('transport', 'like', '%'.$transport_Details.'%')->orWhere('cargo', 'like', '%'.$transport_Details.'%')->orWhere('booked_to', 'like', '%'.$transport_Details.'%');
            });
        }
        $users = $users->paginate(15);
        return view('backend.customer.customers.index', compact('users', 'sort_search','company_name','bank_details','license_details','dl_expiry_Data','gst_no','transport_Details','verification_status'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('backend.customer.customers.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(
            ['name' => 'required|max:255',],
            ['name.required' => translate('Name is required'),'name.max' => translate('Max 255 Character'),]
        );

        // Phone & email both can't be null
        if($request->email == null && $request->phone == null){
            flash(translate('Email and phone number both can not be null.'))->error();
                return back();
        }

        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            if(User::where('email', $request->email)->first() != null){
                flash(translate('Email already exists.'))->error();
                return back();
            }
        }
        elseif (User::where('phone', '+'.$request->country_code.$request->phone)->first() != null) {
            flash(translate('Phone already exists.'))->error();
            return back();
        }

        $password = substr(hash('sha512', rand()), 0, 8);
        $email = null;
        $phone = null;
        
        // Register By email
        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            $email = $request->email;
            $user = User::create([
                'name' => $request->name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            // Account Opening Email to customer
            try {
                EmailUtility::customer_registration_email('registration_from_system_email_to_customer', $user, $password);
            } catch (\Exception $e) {
                $user->delete();
                flash(translate('Registration failed. Please try again later.'))->error();
                return back();
            }

            // Email Verification mail to Customer
            if(get_setting('email_verification') != 1){
                $user->email_verified_at = date('Y-m-d H:m:s');
                $user->save();
                offerUserWelcomeCoupon();
            }
            else {
                EmailUtility::email_verification($user, 'customer');
            }
            flash(translate('Registration successful.'))->success();

        }
        // Register by phone
        else {
            if (addon_is_activated('otp_system')){
                $phone = '+'.$request->country_code.$request->phone;
                $user = User::create([
                    'name' => $request->name,
                    'phone' => $phone,
                    'password' => Hash::make($password),
                    'verification_code' => rand(100000, 999999)
                ]);

                $otpController = new OTPVerificationController;
                $otpController->account_opening($user, $password);
                flash(translate('Registration successful.'))->success();
            }
        }

        // Customer Account Opening Email to Admin
        if ((get_email_template_data('customer_reg_email_to_admin', 'status') == 1)) {
            try {
                EmailUtility::customer_registration_email('customer_reg_email_to_admin', $user, null);
            } catch (\Exception $e) {}
        }

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $customer = User::findOrFail($id);
        $customer->customer_products()->delete(); 

        User::destroy($id);
        flash(translate('Customer has been deleted successfully'))->success();
        return redirect()->route('customers.index');
    }
    
    public function bulk_customer_delete(Request $request) {
        if($request->id) {
            foreach ($request->id as $customer_id) {
                $customer = User::findOrFail($customer_id);
                $customer->customer_products()->delete(); 
                $this->destroy($customer_id);
            }
        }
        
        return 1;
    }

    public function login($id)
    {
        $user = User::findOrFail(decrypt($id));

        auth()->login($user, true);

        return redirect()->route('dashboard');
    }

    public function ban($id) {
        $user = User::findOrFail(decrypt($id));

        if($user->banned == 1) {
            $user->banned = 0;
            flash(translate('Customer UnBanned Successfully'))->success();
        } else {
            $user->banned = 1;
            flash(translate('Customer Banned Successfully'))->success();
        }

        $user->save();
        
        return back();
    }


    public function view($id)
    {
        $user = User::findOrFail(decrypt($id));
        return view('backend.customer.customers.view', compact('user'));
    }

    public function approval(Request $request) {
        $user = User::findOrFail($request->id);

        $approval = ($request->approval_status == 'approve') ? '1' : '0';

        if($approval == 1) {


            $user->approval_status = 1;
            $user->note = $request->note;
            $user->user_subtype = $request->user_subtype;

            $user->save();

            try {
                EmailUtility::approval_registration_email($user);
            } catch (\Exception $e) {}

            flash(translate('Customer Approve Successfully'))->success();

        } else {

            $user->approval_status = 2;
            $user->note = $request->note;
            $user->user_subtype = $request->user_subtype;

            $user->save();

            try {
                EmailUtility::approval_reject_email($user);
            } catch (\Exception $e) {}

            flash(translate('Customer Not Approve Successfully'))->success();

        }

        return back();

    }
}
