<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Country;
use App\Models\UserDetails;
use App\Models\State;
use App\Models\City;
use App\Utility\EmailUtility;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
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
                $q->where('name', 'like', '%'.$sort_search.'%')->orWhere('email', 'like', '%'.$sort_search.'%')->orWhere('phone', 'like', '%'.$sort_search.'%');
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
        $account_number      = $request->account_number ?? null;
        $sortBy              = $request->get('sort_by');
        $sortOrder           = $request->get('sort_order', 'asc');

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
                ->orWhere('phone', 'like', '%'.$sort_search.'%');
                // ->orWhere('tel_number', 'like', '%'.$sort_search.'%');
            });
        }

        // Company filter
        if ($company_name !== null) {
            $users->whereHas('details', function ($q) use ($company_name) {
                $q->where('company_name', 'like', '%'.$company_name.'%');
            });
        }

        if ($account_number !== null) {
            $users->whereHas('details', function ($q) use ($account_number) {
                $q->where('crm_id', 'like', '%'.$account_number.'%');
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

        // Sorting
        if ($sortBy === 'crm_id') {
            $users = $users->orderBy(
                UserDetails::select('crm_id')->whereColumn('user_details.user_id', 'users.id'),
                $sortOrder
            );
        } elseif ($sortBy === 'company_name') {
            $users = $users->orderBy(
                UserDetails::select('company_name')->whereColumn('user_details.user_id', 'users.id'),
                $sortOrder
            );
        } else {
            $users = $users->orderBy('created_at', 'desc');
        }

        $users = $users->paginate(15)->appends($request->query());

        return view('backend.customer.customers.businessindex', compact(
            'users', 'sort_search', 'company_name', 'bank_details', 'license_details', 'gst_no', 'verification_status',
            // new
            'cityIds', 'districtIds', 'stateIds', 'countries',
            'filter_city_id', 'filter_district_id', 'filter_state_id', 'filter_country_id', 'account_number', 'sortBy', 'sortOrder'
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
     */
    public function edit($id)
    {
        $user = User::with('details')->findOrFail($id);
        $details = $user->details;

        $countries = Cache::remember('countries_for_customer_edit', 86400, function () {
            return Country::select('id', 'name')->orderBy('name')->get();
        });

        return view('backend.customer.customers.edit', compact(
            'user',
            'details',
            'countries',
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     */
    public function update(Request $request, $id)
    {
        $user = User::with('details')->findOrFail($id);
        $details = $user->details ?? new UserDetails(['user_id' => $user->id]);
        $typeOption = $request->input('type_option', $details->type_option ?? 'domestic');

        $domesticChoice = $request->input('domestic_identity_selection', 'gst');
        $internationalChoice = $request->input('international_identity_selection', 'iec');
        $businessRequired = ($typeOption === 'domestic' && $domesticChoice === 'gst') || ($typeOption === 'international' && $internationalChoice === 'iec');

        $gstRule = ($typeOption === 'domestic' && $domesticChoice === 'gst')
            ? ['nullable', 'regex:/^[0-9A-Z]{15}$/i']
            : ['nullable', 'string', 'max:255'];

        $businessRules = [
            'type_option' => 'required|in:domestic,international',
            'registration_date' => [$businessRequired ? 'required' : 'nullable'],
            'const_of_business' => [$businessRequired ? 'required' : 'nullable'],
            'con_person_name' => [$businessRequired ? 'required' : 'nullable', 'string', 'regex:/^[A-Za-z\\s]+$/', 'min:1', 'max:50'],
            'company_name' => [$businessRequired ? 'required' : 'nullable', 'string', 'min:1', 'max:150'],
            'street_add_first_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'min:1', 'max:150'],
            'street_add_sec_business' => ['nullable', 'string', 'min:1', 'max:150'],
            'locality_land_mark_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'min:1', 'max:150'],
            'village_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'min:1', 'max:150'],
            'post_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'min:1', 'max:150'],
            'district_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'min:1', 'max:150'],
            'country_code_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'min:1', 'max:150'],
            'pincode_business' => $businessRequired
                ? ['required', 'regex:/^\\d{6}$/']
                : ['nullable'],
            'city_id_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'state_id_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'country_id_business' => [$businessRequired ? 'required' : 'nullable'],
            'phone_business' => [$businessRequired ? 'required' : 'nullable', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'alternate_mob_no_business' => ['nullable', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'whats_app_no_business' => [$businessRequired ? 'required' : 'nullable', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'alternate_whats_app_no_business' => ['nullable', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'prim_email_business' => [$businessRequired ? 'required' : 'nullable', 'email'],
            'alt_email_business' => ['nullable', 'email'],
            'website_business' => ['nullable'],
            'bank_name_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'account_no_business' => [$businessRequired ? 'required' : 'nullable', 'regex:/^\\d+$/', 'max:20'],
            'account_name_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'branch_code_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'max:50'],
            'branch_name_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'branch_address_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'ifsc_code_business' => [$businessRequired ? 'required' : 'nullable', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            // Optional / conditional docs
            'gst_no' => $gstRule,
            'gst_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'gstin_current_status' => ['nullable', 'string'],
            'iec_no' => ['nullable', 'regex:/^[0-9A-Z]{10}$/i'],
            'iec_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'uin_current_status' => ['nullable', 'string'],
            'micr_code_business' => ['nullable', 'regex:/^\\d{9}$/'],
            'ad_code_business' => ['nullable', 'string', 'max:255'],
        ];

        $personalRules = [
            'photo_file' => [$details->photo_file ? 'nullable' : 'required', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'name_personal' => ['required', 'string', 'min:1', 'max:150'],
            'father_name' => ['required', 'string', 'min:1', 'max:150'],
            'dob' => ['required'],
            'street_add_first_personal' => ['required', 'string', 'min:1', 'max:150'],
            'street_add_sec_personal' => ['nullable', 'string', 'min:1', 'max:150'],
            'locality_land_mark_personal' => ['required', 'string', 'min:1', 'max:150'],
            'village_personal' => ['required', 'string', 'min:1', 'max:150'],
            'post_personal' => ['required', 'string', 'min:1', 'max:150'],
            'district_personal' => ['required', 'string', 'min:1', 'max:150'],
            'country_code_personal' => ['required', 'string', 'min:1', 'max:150'],
            'pincode_personal' => ['required', 'regex:/^\\d{6}$/'],
            'city_id_personal' => ['required', 'string', 'max:255'],
            'state_id_personal' => ['required', 'string', 'max:255'],
            'country_id_personal' => ['required'],
            'phone_personal' => ['required', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'alternate_mob_no_personal' => ['nullable', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'whats_app_no_personal' => ['required', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'alternate_whats_app_no_personal' => ['nullable', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'prim_email_personal' => ['required', 'email'],
            'alt_email_personal' => ['nullable', 'email'],
            'bank_name_personal' => ['required', 'string', 'max:255'],
            'account_no_personal' => ['required', 'regex:/^\\d+$/', 'max:20'],
            'account_name_personal' => ['required', 'string', 'max:255'],
            'branch_code_personal' => ['required', 'string', 'max:50'],
            'branch_name_personal' => ['required', 'string', 'max:255'],
            'branch_address_personal' => ['required', 'string', 'max:255'],
            'ifsc_code_personal' => ['required', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'aadhaar_no' => ['nullable', 'regex:/^[0-9]{12}$/i'],
            'aadhaar_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'pan_no' => ['nullable', 'regex:/^[0-9A-Z]{10}$/i'],
            'pan_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'passport_no' => ['nullable', 'regex:/^[0-9A-Z]{1,15}$/i'],
            'passport_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'micr_code_personal' => ['nullable', 'regex:/^\\d{9}$/'],
            'ad_code_personal' => ['nullable', 'string', 'max:255'],
        ];

        $licenseRules = [
            'd_l_no_1' => ['nullable', 'string', 'max:255'],
            'd_l_no_1_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'd_l_no_2' => ['nullable', 'string', 'max:255'],
            'd_l_no_2_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'd_l_no_3' => ['nullable', 'string', 'max:255'],
            'd_l_no_3_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'doctor_hospital_reg_no' => ['nullable', 'string', 'max:255'],
            'doctor_hospital_reg_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'dairy_trust_ngo_reg_no' => ['nullable', 'string', 'max:255'],
            'dairy_trust_ngo_reg_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'cc_mdl_reg_no' => ['nullable', 'string', 'max:255'],
            'cc_mdl_reg_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'other_reg_no' => ['nullable', 'string', 'max:255'],
            'other_reg_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
        ];

        // Simplified validation: only keep basic user email/phone checks per request; comment out the detailed rules above.
        // $validator = \Validator::make($request->all(), array_merge($businessRules, $personalRules, $licenseRules));
        // $validator->after(function ($v) use ($request, $details, $typeOption, $domesticChoice, $internationalChoice) { ... });
        $validator = \Validator::make($request->all(), [
            'prim_email_personal'  => ['required', 'email'],
            // 'prim_email_business'  => ['nullable', 'email'],
            'phone_personal'       => ['required', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            // 'phone_business'       => ['nullable'],
        ]);
        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            throw new ValidationException($validator);
        }
        // Keep full payload so downstream assignments continue to work.
        $validated = array_merge($request->all(), $validator->validated());

        $documentPath = public_path('uploads/document');
        if (!File::exists($documentPath)) {
            File::makeDirectory($documentPath, 0777, true, true);
        }

        $gstFile = $details->gst_no_file;
        if ($request->hasFile('gst_no_file')) {
            $file = $request->file('gst_no_file');
            $documentName = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $documentName);
            $gstFile = 'uploads/document/' . $documentName;
        }

        $iecFile = $details->iec_no_file;
        if ($request->hasFile('iec_no_file')) {
            $file = $request->file('iec_no_file');
            $documentName = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $documentName);
            $iecFile = 'uploads/document/' . $documentName;
        }

        $photoFile = $details->photo_file;
        if ($request->hasFile('photo_file')) {
            $file = $request->file('photo_file');
            $documentName = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $documentName);
            $photoFile = 'uploads/document/' . $documentName;
        }

        $aadhaarFile = $details->aadhaar_no_file;
        if ($request->hasFile('aadhaar_no_file')) {
            $file = $request->file('aadhaar_no_file');
            $documentName = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $documentName);
            $aadhaarFile = 'uploads/document/' . $documentName;
        }

        $panFile = $details->pan_no_file;
        if ($request->hasFile('pan_no_file')) {
            $file = $request->file('pan_no_file');
            $documentName = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $documentName);
            $panFile = 'uploads/document/' . $documentName;
        }

        $passportFile = $details->passport_no_file;
        if ($request->hasFile('passport_no_file')) {
            $file = $request->file('passport_no_file');
            $documentName = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $documentName);
            $passportFile = 'uploads/document/' . $documentName;
        }

        $removeLicense = $request->input('remove_license', []);

        $licenseFiles = [
            'd_l_no_1_file' => $details->d_l_no_1_file,
            'd_l_no_2_file' => $details->d_l_no_2_file,
            'd_l_no_3_file' => $details->d_l_no_3_file,
            'doctor_hospital_reg_no_file' => $details->doctor_hospital_reg_no_file,
            'dairy_trust_ngo_reg_no_file' => $details->dairy_trust_ngo_reg_no_file,
            'cc_mdl_reg_no_file' => $details->cc_mdl_reg_no_file,
            'other_reg_no_file' => $details->other_reg_no_file,
        ];

        foreach ($licenseFiles as $field => $current) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $documentName = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
                $file->move($documentPath, $documentName);
                $licenseFiles[$field] = 'uploads/document/' . $documentName;
            } elseif (isset($removeLicense[str_replace('_file', '', $field)])) {
                $licenseFiles[$field] = null;
            }
        }

        $businessPrimaryCode = $request->input('country_code_phone_code_business', $details->country_code_business ?? '');
        $businessAltCode = $request->input('country_code_alternate_mob_no_business', $details->country_code_business ?? '');
        $businessWhatsCode = $request->input('country_code_whats_app_no_business', $details->country_code_business ?? '');
        $businessAltWhatsCode = $request->input('country_code_alternate_whats_app_no_business', $details->country_code_business ?? '');

        $personalPrimaryCode = $request->input('country_code_phone_code_personal', $details->country_code ?? '');
        $personalAltCode = $request->input('country_code_alternate_mob_no_personal', $details->country_code ?? '');
        $personalWhatsCode = $request->input('country_code_whats_app_no_personal', $details->country_code ?? '');
        $personalAltWhatsCode = $request->input('country_code_alternate_whats_app_no_personal', $details->country_code ?? '');

        // Persist core user columns (users table)
        $user->update([
            'type_option' => $typeOption,
            'name' => $validated['name_personal'],
            'email' => $validated['prim_email_personal'],
            'phone' => '+' . $personalPrimaryCode . '-' . $validated['phone_personal'],
            'address' => implode(',', array_filter([
                $validated['street_add_first_personal'],
                $validated['street_add_sec_personal'] ?? '',
                $validated['locality_land_mark_personal'],
                $validated['village_personal'],
            ])),
            'city' => $validated['city_id_personal'],
            'state' => $validated['state_id_personal'],
            'country' => $validated['country_id_personal'],
            'postal_code' => $validated['pincode_personal'],
            'gst_no' => $typeOption === 'domestic' ? ($validated['gst_no'] ?? null) : null,
            'iec_no' => $typeOption === 'international' ? ($validated['iec_no'] ?? null) : null,
            'aadhaar_no' => $validated['aadhaar_no'] ?? null,
            'pan_no' => $validated['pan_no'] ?? null,
            'passport_no' => $validated['passport_no'] ?? null,
        ]);

        $details->fill([
            'type_option' => $typeOption,
            'gst_no' => $typeOption === 'domestic' ? ($validated['gst_no'] ?? $details->gst_no) : null,
            'gst_no_file' => $gstFile,
            'iec_no' => $typeOption === 'international' ? ($validated['iec_no'] ?? $details->iec_no) : null,
            'iec_no_file' => $iecFile,
            'registration_date' => $businessRequired ? ($validated['registration_date'] ?? $details->registration_date) : $details->registration_date,
            'const_of_business' => $businessRequired ? ($validated['const_of_business'] ?? $details->const_of_business) : $details->const_of_business,
            'gstin_current_status' => $typeOption === 'domestic' ? ($validated['gstin_current_status'] ?? $details->gstin_current_status) : null,
            'uin_current_status' => $typeOption === 'international' ? ($validated['uin_current_status'] ?? $details->uin_current_status) : null,
            'con_person_name' => $businessRequired ? ($validated['con_person_name'] ?? $details->con_person_name) : $details->con_person_name,
            'company_name' => $businessRequired ? ($validated['company_name'] ?? $details->company_name) : $details->company_name,
            'street_add_first_business' => $businessRequired ? ($validated['street_add_first_business'] ?? $details->street_add_first_business) : $details->street_add_first_business,
            'street_add_sec_business' => $businessRequired ? ($validated['street_add_sec_business'] ?? $details->street_add_sec_business) : $details->street_add_sec_business,
            'locality_land_mark_business' => $businessRequired ? ($validated['locality_land_mark_business'] ?? $details->locality_land_mark_business) : $details->locality_land_mark_business,
            'village_business' => $businessRequired ? ($validated['village_business'] ?? $details->village_business) : $details->village_business,
            'post_business' => $businessRequired ? ($validated['post_business'] ?? $details->post_business) : $details->post_business,
            'city_id_business' => $businessRequired ? ($validated['city_id_business'] ?? $details->city_id_business) : $details->city_id_business,
            'district_business' => $businessRequired ? ($validated['district_business'] ?? $details->district_business) : $details->district_business,
            'state_id_business' => $businessRequired ? ($validated['state_id_business'] ?? $details->state_id_business) : $details->state_id_business,
            'pincode_business' => $businessRequired ? ($validated['pincode_business'] ?? $details->pincode_business) : $details->pincode_business,
            'country_id_business' => $businessRequired ? ($validated['country_id_business'] ?? $details->country_id_business) : $details->country_id_business,
            'country_code_business' => $businessRequired ? ($validated['country_code_business'] ?? $details->country_code_business) : $details->country_code_business,
            'prim_mobile_no_business' => $businessRequired && !empty($validated['phone_business'])
                ? $businessPrimaryCode . '-' . $validated['phone_business']
                : ($details->prim_mobile_no_business ?? null),
            'prim_mobile_no_business_meta' => $businessRequired ? $request->input('phone_code_meta', '') : ($details->prim_mobile_no_business_meta ?? ''),
            'alt_mobile_no_business' => ($businessRequired && $businessAltCode && $request->filled('alternate_mob_no_business'))
                ? $businessAltCode . '-' . ($validated['alternate_mob_no_business'] ?? '')
                : ($details->alt_mobile_no_business ?? null),
            'alt_mobile_no_business_meta' => $businessRequired ? $request->input('alternate_mob_no_business_meta', '') : ($details->alt_mobile_no_business_meta ?? ''),
            'prim_whats_app_no_business' => $businessRequired && !empty($validated['whats_app_no_business'])
                ? $businessWhatsCode . '-' . $validated['whats_app_no_business']
                : ($details->prim_whats_app_no_business ?? null),
            'prim_whats_app_no_business_meta' => $businessRequired ? $request->input('whats_app_no_business_meta', '') : ($details->prim_whats_app_no_business_meta ?? ''),
            'alternate_whats_app_no_business' => ($businessRequired && $businessAltWhatsCode && $request->filled('alternate_whats_app_no_business'))
                ? $businessAltWhatsCode . '-' . ($validated['alternate_whats_app_no_business'] ?? '')
                : ($details->alternate_whats_app_no_business ?? null),
            'alternate_whats_app_no_business_meta' => $businessRequired ? $request->input('alternate_whats_app_no_business_meta', '') : ($details->alternate_whats_app_no_business_meta ?? ''),
            'prim_email_business' => $businessRequired ? ($validated['prim_email_business'] ?? $details->prim_email_business) : $details->prim_email_business,
            'alt_email_business' => $businessRequired ? ($validated['alt_email_business'] ?? null) : $details->alt_email_business,
            'website_business' => $businessRequired ? ($validated['website_business'] ?? null) : $details->website_business,
            'bank_name_business' => $businessRequired ? ($validated['bank_name_business'] ?? $details->bank_name_business) : $details->bank_name_business,
            'account_no_business' => $businessRequired ? ($validated['account_no_business'] ?? $details->account_no_business) : $details->account_no_business,
            'account_name_business' => $businessRequired ? ($validated['account_name_business'] ?? $details->account_name_business) : $details->account_name_business,
            'branch_code_business' => $businessRequired ? ($validated['branch_code_business'] ?? $details->branch_code_business) : $details->branch_code_business,
            'branch_name_business' => $businessRequired ? ($validated['branch_name_business'] ?? $details->branch_name_business) : $details->branch_name_business,
            'branch_address_business' => $businessRequired ? ($validated['branch_address_business'] ?? $details->branch_address_business) : $details->branch_address_business,
            'ifsc_code_business' => $businessRequired ? ($validated['ifsc_code_business'] ?? $details->ifsc_code_business) : $details->ifsc_code_business,
            'micr_code_business' => $typeOption === 'international' ? ($validated['micr_code_business'] ?? null) : null,
            'ad_code_business' => $typeOption === 'international' ? ($validated['ad_code_business'] ?? null) : null,

            // Personal
            'name' => $validated['name_personal'],
            'father_name' => $validated['father_name'],
            'dob' => $validated['dob'],
            'street_add_first' => $validated['street_add_first_personal'],
            'street_add_sec' => $validated['street_add_sec_personal'] ?? null,
            'locality_land_mark' => $validated['locality_land_mark_personal'],
            'village' => $validated['village_personal'],
            'post' => $validated['post_personal'],
            'city_id' => $validated['city_id_personal'],
            'district' => $validated['district_personal'],
            'state_id' => $validated['state_id_personal'],
            'pincode' => $validated['pincode_personal'],
            'country_id' => $validated['country_id_personal'],
            'country_code' => $validated['country_code_personal'],
            'prim_mobile_no' => $personalPrimaryCode . '-' . $validated['phone_personal'],
            'prim_mobile_no_meta' => $request->input('phone_personal_meta', ''),
            'alt_mobile_no' => $personalAltCode && $request->filled('alternate_mob_no_personal')
                ? $personalAltCode . '-' . ($validated['alternate_mob_no_personal'] ?? '')
                : null,
            'alt_mobile_no_meta' => $request->input('alternate_mob_no_personal_meta', ''),
            'prim_whats_app_no' => $personalWhatsCode . '-' . $validated['whats_app_no_personal'],
            'prim_whats_app_no_meta' => $request->input('whats_app_no_personal_meta', ''),
            'alt_whats_app_no' => $personalAltWhatsCode && $request->filled('alternate_whats_app_no_personal')
                ? $personalAltWhatsCode . '-' . ($validated['alternate_whats_app_no_personal'] ?? '')
                : null,
            'alt_whats_app_no_meta' => $request->input('alternate_whats_app_no_personal_meta', ''),
            'prim_email_personal' => $validated['prim_email_personal'],
            'alt_email_personal' => $validated['alt_email_personal'] ?? null,
            'bank_name_personal' => $validated['bank_name_personal'],
            'account_no_personal' => $validated['account_no_personal'],
            'account_name_personal' => $validated['account_name_personal'],
            'branch_code_personal' => $validated['branch_code_personal'],
            'branch_name_personal' => $validated['branch_name_personal'],
            'branch_address_personal' => $validated['branch_address_personal'],
            'ifsc_code_personal' => $validated['ifsc_code_personal'],
            'micr_code_personal' => $typeOption === 'international' ? ($validated['micr_code_personal'] ?? $details->micr_code_personal) : null,
            'ad_code_personal' => $typeOption === 'international' ? ($validated['ad_code_personal'] ?? $details->ad_code_personal) : null,
            'photo_file' => $photoFile,
            'aadhaar_no' => $validated['aadhaar_no'] ?? $details->aadhaar_no,
            'aadhaar_no_file' => $aadhaarFile,
            'pan_no' => $validated['pan_no'] ?? $details->pan_no,
            'pan_no_file' => $panFile,
            'passport_no' => $validated['passport_no'] ?? $details->passport_no,
            'passport_no_file' => $passportFile,

            // License
            'd_l_no_1' => isset($removeLicense['d_l_no_1']) ? null : ($request->filled('d_l_no_1') ? $validated['d_l_no_1'] : ($request->has('d_l_no_1') ? null : $details->d_l_no_1)),
            'd_l_no_1_file' => $licenseFiles['d_l_no_1_file'],
            'd_l_no_2' => isset($removeLicense['d_l_no_2']) ? null : ($request->filled('d_l_no_2') ? $validated['d_l_no_2'] : ($request->has('d_l_no_2') ? null : $details->d_l_no_2)),
            'd_l_no_2_file' => $licenseFiles['d_l_no_2_file'],
            'd_l_no_3' => isset($removeLicense['d_l_no_3']) ? null : ($request->filled('d_l_no_3') ? $validated['d_l_no_3'] : ($request->has('d_l_no_3') ? null : $details->d_l_no_3)),
            'd_l_no_3_file' => $licenseFiles['d_l_no_3_file'],
            'doctor_hospital_reg_no' => isset($removeLicense['doctor_hospital_reg_no']) ? null : ($request->filled('doctor_hospital_reg_no') ? $validated['doctor_hospital_reg_no'] : ($request->has('doctor_hospital_reg_no') ? null : $details->doctor_hospital_reg_no)),
            'doctor_hospital_reg_no_file' => $licenseFiles['doctor_hospital_reg_no_file'],
            'dairy_trust_ngo_reg_no' => isset($removeLicense['dairy_trust_ngo_reg_no']) ? null : ($request->filled('dairy_trust_ngo_reg_no') ? $validated['dairy_trust_ngo_reg_no'] : ($request->has('dairy_trust_ngo_reg_no') ? null : $details->dairy_trust_ngo_reg_no)),
            'dairy_trust_ngo_reg_no_file' => $licenseFiles['dairy_trust_ngo_reg_no_file'],
            'cc_mdl_reg_no' => isset($removeLicense['cc_mdl_reg_no']) ? null : ($request->filled('cc_mdl_reg_no') ? $validated['cc_mdl_reg_no'] : ($request->has('cc_mdl_reg_no') ? null : $details->cc_mdl_reg_no)),
            'cc_mdl_reg_no_file' => $licenseFiles['cc_mdl_reg_no_file'],
            'other_reg_no' => isset($removeLicense['other_reg_no']) ? null : ($request->filled('other_reg_no') ? $validated['other_reg_no'] : ($request->has('other_reg_no') ? null : $details->other_reg_no)),
            'other_reg_no_file' => $licenseFiles['other_reg_no_file'],
        ]);
        $details->save();

          if ($request->ajax()) {
              return response()->json([
                  'message' => translate('Customer details updated successfully'),
                //   'redirect_url' => back()
                //   'redirect_url' => route('customers.business')
              ]);
          }

          flash(translate('Customer details updated successfully'))->success();

          return redirect()->back();
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


    public function update_credit(Request $request)
    {
        try {
            // ✅ Validate request data
            $validator = \Validator::make($request->all(), [
                'user_id'       => 'required|exists:users,id',
                'credit_status' => 'required|in:active,deactive',
                'credit_limit'  => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                // Laravel will automatically redirect back with errors
                return back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('open_modal', true); // Optional flag if you reopen a modal
            }

            $validated = $validator->validated();

            $user = User::findOrFail($validated['user_id']);

            $statusStr = $validated['credit_status'];
            $statusVal = $statusStr === 'active' ? 1 : 0;
            $newLimit  = (int) $validated['credit_limit'];

            DB::beginTransaction();

            $oldLimit  = (int) ($user->credit_limit ?? 0);
            $oldRemain = (int) ($user->credit_remain ?? 0);

            // ✅ Safe rule for remain
            $delta     = $newLimit - $oldLimit;
            $newRemain = $oldRemain;

            if ($delta > 0) {
                $newRemain = min($newLimit, $oldRemain + $delta);
            } elseif ($delta < 0) {
                $newRemain = min($newLimit, $oldRemain);
            }

            $newRemain = max(0, $newRemain);

            // ✅ Update user
            $user->update([
                'credit_status' => $statusVal,
                'credit_limit'  => $newLimit,
                'credit_remain' => $newRemain,
            ]);

            DB::commit();

            // ✅ Flash success + redirect back
            flash(translate('Credit details updated successfully'))->success();
            return back();

        } catch (\Throwable $e) {
            DB::rollBack(); // ✅ Always rollback on error
            report($e);

            flash(translate('Something went wrong: ' . $e->getMessage()))->error();
            return back()->withInput();
        }
    }


}
