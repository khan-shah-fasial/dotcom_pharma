<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Country;
use App\Models\UserDetails;
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
        $verification_status =  $request->verification_status ?? null;

        $users = User::where('user_type', 'customer')->whereNull('user_subtype')->orderBy('created_at', 'desc');
        if($verification_status != null){
            $users = $verification_status == 'verified' ? $users->where('email_verified_at', '!=', null) : $users->where('email_verified_at', null);
        }
        if($verification_status != null){
            $users = $verification_status == 'verified' ? $users->where('approval_Status', 1) : $users->where('approval_Status', 0);
        }
        if ($sort_search != null){
            $sort_search = $request->search;
            $users->where(function ($q) use ($sort_search){
                $q->where('name', 'like', '%'.$sort_search.'%')->orWhere('email', 'like', '%'.$sort_search.'%')->orWhere('phone', 'like', '%'.$sort_search.'%')->orWhere('tel_number', 'like', '%'.$sort_search.'%');
            });
        }

        $users = $users->paginate(15);
        return view('backend.customer.customers.index', compact('users', 'sort_search','verification_status'));
    }

    // public function business_index(Request $request)
    // {
    //     $sort_search = $request->search ?? null;
    //     $company_name = $request->company_name ?? null;
    //     $verification_status =  $request->verification_status ?? null;
    //     $bank_details =  $request->bank_details ?? null;
    //     $license_details =  $request->license_details ?? null;
    //     $dl_expiry_Data =  $request->dl_expiry_Data ?? null;
    //     $gst_no =  $request->gst_no ?? null;
    //     $transport_Details =  $request->transport_Details ?? null;

    //     $users = User::with('details')->where('user_type', 'customer')->whereNotNull('step')->orderBy('created_at', 'desc');
    //     // if($verification_status != null){
    //     //     $users = $verification_status == 'verified' ? $users->where('email_verified_at', '!=', null) : $users->where('email_verified_at', null);
    //     // }
    //     if($verification_status != null){
    //         $users = $verification_status == 'verified' ? $users->where('approval_Status', 1) : $users->where('approval_Status', 0);
    //     }
    //     if ($sort_search != null){
    //         $sort_search = $request->search;
    //         $users->where(function ($q) use ($sort_search){
    //             $q->where('name', 'like', '%'.$sort_search.'%')->orWhere('email', 'like', '%'.$sort_search.'%')->orWhere('phone', 'like', '%'.$sort_search.'%')->orWhere('tel_number', 'like', '%'.$sort_search.'%');
    //         });
    //     }
    //     if ($company_name != null){
    //         $company_name = $request->company_name;
    //         $users->whereHas('details',function ($q) use ($company_name){
    //             $q->where('company_name', 'like', '%'.$company_name.'%');
    //         });
    //     }

    //     if ($gst_no != null){
    //         $gst_no = $request->gst_no;
    //         $users->where(function ($q) use ($gst_no){
    //             $q->where('gst_no', 'like', '%'.$gst_no.'%')
    //             ->orWhere('iec_no','like', '%'.$gst_no.'%')
    //             ->orWhere('aadhaar_no','like', '%'.$gst_no.'%')
    //             ->orWhere('pan_no','like', '%'.$gst_no.'%')
    //             ->orWhere('passport_no','like', '%'.$gst_no.'%');
    //         });
    //     }
    //     // if ($bank_details != null){
    //     //     $bank_details = $request->bank_details;
    //     //     $users->whereHas('details', function ($q) use ($bank_details){
    //     //         $q->where('bank_name_business', 'like', '%'.$bank_details.'%')->orWhere('bank_name_personal', 'like', '%'.$bank_details.'%')->orWhere('account_no_business', 'like', '%'.$bank_details.'%')->orWhere('account_no_personal', 'like', '%'.$bank_details.'%')->orWhere('branch_code_business', 'like', '%'.$bank_details.'%')->orWhere('branch_code_personal', 'like', '%'.$bank_details.'%')->orwhere('ifsc_code_business', 'like', '%'.$bank_details.'%')->orwhere('ifsc_code_personal', 'like', '%'.$bank_details.'%')->orwhere('micr_code_business', 'like', '%'.$bank_details.'%')->orwhere('micr_code_personal', 'like', '%'.$bank_details.'%');
    //     //     });
    //     // }
    //     // if ($license_details != null){
    //     //     $license_details = $request->license_details;
    //     //     $users->whereHas('details', function ($q) use ($license_details){
    //     //         $q->where('cc_no', 'like', '%'.$license_details.'%')->orWhere('d_l_no_1', 'like', '%'.$license_details.'%')->orWhere('d_l_no_2', 'like', '%'.$license_details.'%')->orWhere('d_l_no_3', 'like', '%'.$license_details.'%');
    //     //     });
    //     // }
    //     $users = $users->paginate(15);
    //     return view('backend.customer.customers.businessindex', compact('users', 'sort_search','company_name','bank_details','license_details','gst_no','verification_status'));
    // }

    public function business_index(Request $request)
    {
        $sort_search         = $request->search ?? null;
        $company_name        = $request->company_name ?? null;
        $verification_status = $request->verification_status ?? null;
        $bank_details        = $request->bank_details ?? null;
        $license_details     = $request->license_details ?? null;
        $dl_expiry_Data      = $request->dl_expiry_Data ?? null;
        $gst_no              = $request->gst_no ?? null;

        // NEW: location filters (request)
        $filter_city_id     = $request->city_id ?? null;
        $filter_district_id = $request->district_id ?? null;
        $filter_state_id    = $request->state_id ?? null;
        $filter_country_id  = $request->country_id ?? null;

        // Base query
        $users = User::with('details')
            ->where('user_type', 'customer')
            ->whereNotNull('step')
            ->orderBy('created_at', 'desc');

        // Approval filter
        if ($verification_status !== null) {
            $users = $verification_status === 'verified'
                ? $users->where('approval_Status', 1)
                : $users->where('approval_Status', 0);
        }

        // Text search
        if ($sort_search !== null) {
            $users->where(function ($q) use ($sort_search) {
                $q->where('name', 'like', '%'.$sort_search.'%')
                ->orWhere('email', 'like', '%'.$sort_search.'%')
                ->orWhere('phone', 'like', '%'.$sort_search.'%')
                ->orWhere('tel_number', 'like', '%'.$sort_search.'%');
            });
        }

        // Company filter
        if ($company_name !== null) {
            $users->whereHas('details', function ($q) use ($company_name) {
                $q->where('company_name', 'like', '%'.$company_name.'%');
            });
        }

        // IDs (GST/Aadhaar/PAN/Passport/IEC) filter
        if ($gst_no !== null) {
            $users->where(function ($q) use ($gst_no) {
                $q->where('gst_no', 'like', '%'.$gst_no.'%')
                ->orWhere('iec_no', 'like', '%'.$gst_no.'%')
                ->orWhere('aadhaar_no', 'like', '%'.$gst_no.'%')
                ->orWhere('pan_no', 'like', '%'.$gst_no.'%')
                ->orWhere('passport_no', 'like', '%'.$gst_no.'%');
            });
        }

        // NEW: Location filters (match against business + personal)
        if ($filter_city_id !== null && $filter_city_id !== '') {
            $users->whereHas('details', function ($q) use ($filter_city_id) {
                $q->where(function ($qq) use ($filter_city_id) {
                    $qq->where('city_id_business', $filter_city_id)
                    ->orWhere('city_id', $filter_city_id);
                });
            });
        }

        if ($filter_district_id !== null && $filter_district_id !== '') {
            $users->whereHas('details', function ($q) use ($filter_district_id) {
                $q->where(function ($qq) use ($filter_district_id) {
                    $qq->where('district_business', $filter_district_id)
                    ->orWhere('district', $filter_district_id);
                });
            });
        }

        if ($filter_state_id !== null && $filter_state_id !== '') {
            $users->whereHas('details', function ($q) use ($filter_state_id) {
                $q->where(function ($qq) use ($filter_state_id) {
                    $qq->where('state_id_business', $filter_state_id)
                    ->orWhere('state_id', $filter_state_id);
                });
            });
        }

        // Country: try both business & personal if you have both columns.
        // If you only have one (e.g., `country_id`), keep just that condition.
        if ($filter_country_id !== null && $filter_country_id !== '') {
            $users->whereHas('details', function ($q) use ($filter_country_id) {
                $q->where(function ($qq) use ($filter_country_id) {
                    $qq->where('country_id_business', $filter_country_id)
                    ->orWhere('country_id', $filter_country_id);
                });
            });
        }

        // Build dropdown options (unique & cleaned)
        // CITY
        $cityIds = collect()
            ->merge(
                UserDetails::whereNotNull('city_id_business')
                    ->where('city_id_business', '!=', '')
                    ->where('city_id_business', '!=', '0')
                    ->pluck('city_id_business')
            )
            ->merge(
                UserDetails::whereNotNull('city_id')
                    ->where('city_id', '!=', '')
                    ->where('city_id', '!=', '0')
                    ->pluck('city_id')
            )
            ->unique()
            ->sort()
            ->values();


        // ✅ DISTRICT
        $districtIds = collect()
            ->merge(
                UserDetails::whereNotNull('district_business')
                    ->where('district_business', '!=', '')
                    ->where('district_business', '!=', '0')
                    ->pluck('district_business')
            )
            ->merge(
                UserDetails::whereNotNull('district')
                    ->where('district', '!=', '')
                    ->where('district', '!=', '0')
                    ->pluck('district')
            )
            ->unique()
            ->sort()
            ->values();


        // ✅ STATE
        $stateIds = collect()
            ->merge(
                UserDetails::whereNotNull('state_id_business')
                    ->where('state_id_business', '!=', '')
                    ->where('state_id_business', '!=', '0')
                    ->pluck('state_id_business')
            )
            ->merge(
                UserDetails::whereNotNull('state_id')
                    ->where('state_id', '!=', '')
                    ->where('state_id', '!=', '0')
                    ->pluck('state_id')
            )
            ->unique()
            ->sort()
            ->values();

        // COUNTRIES from table
        $countries = Country::select('id', 'name')
            ->orderBy('name')
            ->get();

        $users = $users->paginate(15)->appends($request->query());

        return view('backend.customer.customers.businessindex', compact(
            'users', 'sort_search', 'company_name', 'bank_details', 'license_details', 'gst_no', 'verification_status',
            // new
            'cityIds', 'districtIds', 'stateIds', 'countries',
            'filter_city_id', 'filter_district_id', 'filter_state_id', 'filter_country_id'
        ));
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
        $customer->user_details()->delete();
        $customer->addresses()->delete();

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
        $user = UserDetails::where('user_id', decrypt($id))->first();
        $user2 = User::where('id', decrypt($id))->first();
        return view('backend.customer.customers.view', compact('user', 'user2'));
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
            $user->user_subtype = null;

            $user->save();

            try {
                EmailUtility::approval_reject_email($user);
            } catch (\Exception $e) {}

            flash(translate('Customer Not Approve Successfully'))->success();

        }

        return back();

    }
}
