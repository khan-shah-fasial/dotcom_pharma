<?php

namespace App\Http\Controllers\Auth;

use Cookie;
use Session;
use App\Models\Cart;
use App\Models\User;
use App\Rules\Recaptcha;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\BusinessSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use App\Http\Controllers\OTPVerificationController;
use App\Models\Address;
use App\Utility\EmailUtility;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\UserDetails;

use function Ramsey\Uuid\v1;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6|confirmed',
            'g-recaptcha-response' => [
                Rule::when(get_setting('google_recaptcha') == 1, ['required', new Recaptcha()], ['sometimes'])
            ]
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
        }
        else {
            if (addon_is_activated('otp_system')){
                $user = User::create([
                    'name' => $data['name'],
                    'phone' => '+'.$data['country_code'].$data['phone'],
                    'password' => Hash::make($data['password']),
                    'verification_code' => rand(100000, 999999)
                ]);

                $otpController = new OTPVerificationController;
                $otpController->send_code($user);
            }
        }
        
        if(session('temp_user_id') != null){
            if(auth()->user()->user_type == 'customer'){
                Cart::where('temp_user_id', session('temp_user_id'))
                ->update(
                    [
                        'user_id' => auth()->user()->id,
                        'temp_user_id' => null
                    ]
                );
            }
            else {
                Cart::where('temp_user_id', session('temp_user_id'))->delete();
            }
            Session::forget('temp_user_id');
        }

        if(Cookie::has('referral_code')){
            $referral_code = Cookie::get('referral_code');
            $referred_by_user = User::where('referral_code', $referral_code)->first();
            if($referred_by_user != null){
                $user->referred_by = $referred_by_user->id;
                $user->save();
            }
        }

        return $user;
    }

    public function register(Request $request)
    {
        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            if(User::where('email', $request->email)->first() != null){
                flash(translate('Email or Phone already exists.'));
                return back();
            }
        }
        elseif (User::where('phone', '+'.$request->country_code.$request->phone)->first() != null) {
            flash(translate('Phone already exists.'));
            return back();
        }

        $this->validator($request->all())->validate();

        $user = $this->create($request->all());

        $this->guard()->login($user);

        if($user->email != null){
            if(BusinessSetting::where('type', 'email_verification')->first()->value != 1){
                $user->email_verified_at = date('Y-m-d H:m:s');
                $user->save();
                offerUserWelcomeCoupon();
                flash(translate('Registration successful.'))->success();
            }
            else {
                try {
                    EmailUtility::email_verification($user, 'customer');
                    flash(translate('Registration successful. Please verify your email.'))->success();
                } catch (\Throwable $e) {
                    dd($e);
                    $user->delete();
                    flash(translate('Registration failed. Please try again later.'))->error();
                }
            }

            // Account Opening Email to customer
            if ( $user != null && (get_email_template_data('registration_email_to_customer', 'status') == 1)) {
                try {
                    EmailUtility::customer_registration_email('registration_email_to_customer', $user, null);
                } catch (\Exception $e) {}
            }
        }

        // customer Account Opening Email to Admin
        if ( $user != null && (get_email_template_data('customer_reg_email_to_admin', 'status') == 1)) {
            try {
                EmailUtility::customer_registration_email('customer_reg_email_to_admin', $user, null);
            } catch (\Exception $e) {}
        }

        return $this->registered($request, $user)
            ?: redirect($this->redirectPath());
    }

    protected function registered(Request $request, $user)
    {
        if ($user->email == null) {
            return redirect()->route('verification');
        }elseif(session('link') != null){
            return redirect(session('link'));
        }else {
            return redirect()->route('home');
        }
    }

    public function new_user_register(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'regex:/^[A-Za-z\s]+$/', 'min:1', 'max:50'],
            'email_id' => ['required', 'email'],
            'phone' => ['required', 'regex:/^[\d\s\-\+]+$/', 'min:5'],
            'ad_contact_number' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5'],
            'land_mark_village' => ['nullable', 'string', 'max:255'],
            'post' => ['nullable', 'string', 'max:255'],
            'address_1' => ['required', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'pincode' => ['required', 'regex:/^\d{6}$/'], // Assuming Indian pincode format
            'district' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'country_code' => 'nullable', // ISO 3166-1 alpha-2
            'phone_no_1' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5'],
            'phone_no_2' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5'],
            'whats_app_no' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5'],
            'gst_no' => ['nullable', 'regex:/^[0-9]{15}$/'], // Assuming GSTIN format
            'cc_no' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5'],
            'd_l_no_1' => ['nullable', 'string', 'max:50'],
            'd_l_no_2' => ['nullable', 'string', 'max:50'],
            'd_l_no_3' => ['nullable', 'string', 'max:50'],
            'd_l_exp_Date' => ['nullable', 'date'],
            'transport' => ['nullable', 'string', 'max:255'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'booked_to' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_no' => ['nullable', 'regex:/^\d+$/', 'max:20'], // Numeric only
            'branch_no' => ['nullable', 'string', 'max:50'],
            'branch_code' => ['nullable', 'string', 'max:50'],
            'ifsc_code' => ['nullable', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'], // IFSC code format
            'micr_code' => ['nullable', 'regex:/^\d{9}$/'], // MICR code format
            'customer_care_executive' => ['nullable', 'string', 'max:255'],
            'password' => [
                'required',
                'string',
                'min:8', // Minimum length of 8 characters
                'regex:/[A-Z]/', // At least one uppercase letter
                'regex:/[!@#$%^&*(),.?":{}|<>]/', // At least one special character
                'confirmed' // Ensures password and confirm_password match
            ],
            'password_confirmation' => ['required', 'string', 'min:8'], // Confirmation field
        ], [
            'name.required' => 'The Name field is required.',
            'name.string' => 'The Name must be a string.',
            'name.regex' => 'The Name must only contain letters and spaces.',
            'name.min' => 'The Name must be at least 1 character.',
            'name.max' => 'The Name may not be greater than 50 characters.',
        
            'email_id.required' => 'The Email field is required.',
            'email_id.email' => 'The Email must be a valid email address.',
        
            'phone.required' => 'The Phone Number field is required.',
            'phone.regex' => 'The Phone Number format is invalid.',
            'phone.min' => 'The Phone Number must be at least 5 characters.',
        
            'ad_contact_number.regex' => 'The AD Contact Number format is invalid.',
            'ad_contact_number.min' => 'The AD Contact Number must be at least 5 characters.',
        
            'land_mark_village.string' => 'The Landmark/Village must be a string.',
            'land_mark_village.max' => 'The Landmark/Village may not be greater than 255 characters.',
        
            'address_1.required' => 'The Address 1 field is required.',
            'address_1.string' => 'The Address 1 must be a string.',
            'address_1.max' => 'The Address 1 may not be greater than 255 characters.',
        
            'pincode.required' => 'The Pincode field is required.',
            'pincode.regex' => 'The Pincode format is invalid.',
        
            'district.required' => 'The District field is required.',
            'district.string' => 'The District must be a string.',
            'district.max' => 'The District may not be greater than 100 characters.',
        
            'state.required' => 'The State field is required.',
            'state.string' => 'The State must be a string.',
            'state.max' => 'The State may not be greater than 100 characters.',
        
            'country_code.required' => 'The Country Code field is required.',
        
            'gst_no.regex' => 'The GST Number format is invalid.',
            'ifsc_code.regex' => 'The IFSC Code format is invalid.',
            'micr_code.regex' => 'The MICR Code format is invalid.',

            'password.required' => 'The Password field is required.',
            'password.min' => 'The Password must be at least 8 characters long.',
            'password.regex' => 'The Password must contain at least one uppercase letter and one special character.',

            'password.confirmed' => 'The Password and Confirm Password do not match.',
            'password_confirmation.required' => 'The Confirm Password field is required.',
        ]);
        
        

        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            return response()->json([
                'status' => 'error',
                'message' => $errors
            ], 200);
        }

        if (filter_var($request->email_id, FILTER_VALIDATE_EMAIL)) {
            if(User::where('email', $request->email_id)->first() != null){
                flash(translate('Email or Phone already exists.'));
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email already exists.',
                ], 200);
            }
        }
        elseif (User::where('phone', '+'.$request->country__code.$request->phone)->first() != null) {
            flash(translate('Phone already exists.'));
            return response()->json([
                'status' => 'error',
                'message' => 'Phone already exists.'
            ], 200);
        }


        // $otp = mt_rand(100000, 999999); // Generate random 6-digit OTP
        $otp = '123456';
        $timestamp = date('Y-m-d H:i:s'); // Use PHP's native date() function for timestamp
        
        // Store OTP and timestamp in session
        Session::put('otp', $otp);
        Session::put('otp_timestamp', $timestamp);
        
        // Prepare user data array
        $user_data = [
            'name' => $request->name,
            'email' => $request->email_id,
            'phone' => $request->country_code . $request->phone,
            // 'phone' => str_replace(' ', '', $request->phone),
            'ad_contact_number' => $request->country_code_ad_contact_number . $request->ad_contact_number,
            'land_mark_village' => $request->land_mark_village,
            'post' => $request->post,
            'address_1' => $request->address_1,
            'address_2' => $request->address_2,
            'pincode' => $request->pincode,
            'district' => $request->district,
            'state' => $request->state,
            'country__code' => $request->country_code,
            'phone_no_1' => $request->country_code_phone_no_1 . $request->phone_no_1,
            'phone_no_2' => $request->country_code_phone_no_2 . $request->phone_no_2,
            'whats_app_no' => $request->country_code_whats_app_no . $request->whats_app_no,
            'gst_no' => $request->gst_no,
            'cc_no' => $request->cc_no,
            'd_l_no_1' => $request->d_l_no_1,
            'd_l_no_2' => $request->d_l_no_2,
            'd_l_no_3' => $request->d_l_no_3,
            'd_l_exp_Date' => $request->d_l_exp_Date,
            'transport' => $request->transport,
            'cargo' => $request->cargo,
            'booked_to' => $request->booked_to,
            'bank_name' => $request->bank_name,
            'account_no' => $request->account_no,
            'branch_no' => $request->branch_no,
            'branch_code' => $request->branch_code,
            'ifsc_code' => $request->ifsc_code,
            'micr_code' => $request->micr_code,
            'customer_care_executive' => $request->customer_care_executive,
            'password'  => Hash::make($request->password),
        ];
        
        // Store user data in session
        Session::put('user_data', $user_data);


        // $user = [
        //     'name' => $user_data['name'],
        //     'phone' => '+'.$user_data['phone'],
        //     'password' => $user_data['password'],
        //     'verification_code' => $otp,
        // ];

        // $otpController = new OTPVerificationController;
        // $otpController->send_code($user);
        
        // Return a success response
        return response()->json([
            'status' => 'success',
            'message' => 'Please verify your Phone Number: ' . $request->country__code . $request->phone,
        ], 200);

    }


    public function resendOtp(Request $request)
    {

        // $data = Session::get('user_data');
    
        // // Ensure $data exists
        // if (!$data) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'User data not found. Please start the process again.',
        //     ], 200);
        // }

        // $otp = mt_rand(100000, 999999);
        $otp = '123456';
        $timestamp = date('Y-m-d H:i:s');

        if($request->phonetype == "business"){
            Session::put('otp_business', $otp);
            Session::put('otp_business_timestamp', $timestamp);
            return response()->json([
                'status' => 'success',
                'message' => 'OTP has been Resend no this Phone : ' . $request->phone_no,
            ], 200);
        } elseif($request->phonetype == "personal") {
            Session::put('otp_personal', $otp);
            Session::put('otp_personal_timestamp', $timestamp);
            return response()->json([
                'status' => 'success',
                'message' => 'OTP has been Resend no this Phone : ' . $request->phone_no,
            ], 200);
        }

        // $user = [
        //     'name' => $data['name'],
        //     'phone' => '+'.$data['phone'],
        //     'password' => $data['password'],
        //     'verification_code' => $otp,
        // ];

        // $otpController = new OTPVerificationController;
        // $otpController->send_code($user);


    }


    // public function verify_otp(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'otp' => 'required|digits:6',
    //     ]);
    
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $validator->errors()->first(), // Return the first validation error
    //         ], 200);
    //     }
    
    //     $otp = Session::get('otp');
    //     $timestamp = Session::get('otp_timestamp');
    
    //     // Check if OTP and timestamp exist
    //     if (!$otp || !$timestamp) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'OTP not found. Please request a new one.',
    //         ], 200);
    //     }
    
    //     // Check if OTP has expired (2 minutes)
    //     $timestamp = new \DateTime($timestamp);
    //     $current_time = new \DateTime();
    //     $interval = $current_time->getTimestamp() - $timestamp->getTimestamp();
    
    //     if ($interval > 120) { // 2 minutes = 120 seconds
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'OTP has expired. Please request a new one.',
    //         ], 200);
    //     }
    
    //     if ($request->otp == $otp) {
    //         $data = Session::get('user_data');
    
    //         // Ensure $data exists
    //         if (!$data) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'User data not found. Please start the process again.',
    //             ], 200);
    //         }
    
    //         // Create user
    //         $user = User::create([
    //             'name' => $data['name'],
    //             'email' => $data['email'],
    //             'phone' => '+' . $data['phone'],
    //             'password' => $data['password'], // Hash password before saving
    //             'ad_contact_number' => $data['ad_contact_number'],
    //             'land_mark_village' => $data['land_mark_village'],
    //             'post' => $data['post'],
    //             'address_1' => $data['address_1'],
    //             'address_2' => $data['address_2'],
    //             'pincode' => $data['pincode'],
    //             'district' => $data['district'],
    //             'state' => $data['state'],
    //             'country__code' => $data['country__code'],
    //             'phone_no_1' => $data['phone_no_1'],
    //             'phone_no_2' => $data['phone_no_2'],
    //             'whats_app_no' => $data['whats_app_no'],
    //             'gst_no' => $data['gst_no'],
    //             'cc_no' => $data['cc_no'],
    //             'd_l_no_1' => $data['d_l_no_1'],
    //             'd_l_no_2' => $data['d_l_no_2'],
    //             'd_l_no_3' => $data['d_l_no_3'],
    //             'd_l_exp_Date' => $data['d_l_exp_Date'],
    //             'transport' => $data['transport'],
    //             'cargo' => $data['cargo'],
    //             'booked_to' => $data['booked_to'],
    //             'bank_name' => $data['bank_name'],
    //             'account_no' => $data['account_no'],
    //             'branch_no' => $data['branch_no'],
    //             'branch_code' => $data['branch_code'],
    //             'ifsc_code' => $data['ifsc_code'],
    //             'micr_code' => $data['micr_code'],
    //             'customer_care_executive' => $data['customer_care_executive'],
    //         ]);

    //         $this->guard()->login($user);


    //         // Account Opening Email to customer

    //         try {
    //             EmailUtility::customer_registration_email('registration_email_to_customer', $user, null);
    //         } catch (\Exception $e) {}

    //         // customer Account Opening Email to Admin
    
    //         try {
    //             EmailUtility::customer_registration_email('customer_reg_email_to_admin', $user, null);
    //         } catch (\Exception $e) {}

            
    //         if(session('temp_user_id') != null){
    //             if(auth()->user()->user_type == 'customer'){
    //                 Cart::where('temp_user_id', session('temp_user_id'))
    //                 ->update(
    //                     [
    //                         'user_id' => auth()->user()->id,
    //                         'temp_user_id' => null
    //                     ]
    //                 );
    //             }
    //             else {
    //                 Cart::where('temp_user_id', session('temp_user_id'))->delete();
    //             }
    //             Session::forget('temp_user_id');
    //         }


    
    //         if($user->approval_status == 1){
    //             return response()->json([
    //                 'status' => 'success',
    //                 'registration' => 'approve',
    //                 'message' => 'OTP has been verified.',
    //             ], 200);

    //         } else {

    //             $this->guard()->logout();

    //             return response()->json([
    //                 'status' => 'success',
    //                 'registration' => 'not approve',
    //                 'message' => 'OTP has been verified.',
    //             ], 200);
    //         }   


    //     } else {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Invalid OTP.',
    //         ], 200);
    //     }
    // }

    //--------------------------------- new registration -------------------------------

    public function get_reg_step()
    {
        if (session()->has('temp_user_id')) {
            // Retrieve the user data for the temporary user ID from the session
            $data = User::where('id', session()->get('temp_user_id'))->where('approval_status', 0)->first();

            // Check if $data exists to avoid accessing a property on null
            // $step = $data ? $data->step : session()->get('step');
            $step = session()->get('step');

            Session::put('step', $step);
        } else {
            // If temp_user_id is not in the session, set defaults
            $data = null;
            $step = session()->get('step');
        }
    
        // Render the HTML for the second modal using the retrieved data
        $html = view('frontend.user_registration_modal', compact('data','step'))->render();
    
        // Return a JSON response with the generated HTML and step
        return response()->json([
            'success' => true,
            'html' => $html,
            'step' => $step,
        ]);
    }

    public function previous_reg_form(){

        $step = session()->get('step');
        $step = $step - 1;

        // if($step == 3){
        //     $step = 2;
        // }

        if (session()->has('temp_user_id')) {
            // Retrieve the user data for the temporary user ID from the session
            $data = User::where('id', session()->get('temp_user_id'))->where('approval_status', 0)->first();

        } else {

            $data = null;
        }

        Session::put('step', $step);

        // Render the HTML for the second modal using the retrieved data
        $html = view('frontend.user_registration_modal', compact('data','step'))->render();
    
        // Return a JSON response with the generated HTML and step
        return response()->json([
            'success' => true,
            'html' => $html,
            'step' => $step,
        ]);
    
    }




    public function create_account($param, Request $request)
    {


        if ($param == "registration-locality") {

            $rsp_msg = $this->registration_locality($request);

        } elseif ($param == "registration-bussiness-details") {

            $rsp_msg = $this->registration_bussiness_details($request);

        } elseif ($param == "registration-personal-details") {

            $rsp_msg = $this->registration_personal_details($request);

        } elseif ($param == "registration-license-details") {

            $rsp_msg = $this->registration_license_details($request);

        } elseif ($param == "gst-validate") {

            $rsp_msg = $this->gst_validate($request);

        } elseif ($param == "iec-validate") {

            $rsp_msg = $this->iec_validate($request);

        } elseif ($param == "aadhaar-validate") {

            $rsp_msg = $this->aadhaar_validate($request);

        } elseif ($param == "aadhar-otp-verify") {

            $rsp_msg = $this->aadhaar_otp_validate($request);

        } elseif ($param == "pan-validate") {

            $rsp_msg = $this->pan_validate($request);
        
        } elseif ($param == "passport-validate") {

            $rsp_msg = $this->passport_validate($request);

        } elseif ($param == "personal-details") {

            $rsp_msg = $this->store_personal_details($request);
        
        } elseif ($param == "verify-otps") {

            $rsp_msg = $this->verify_otp($request);

        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid parameter: $param',
            ], 200);
        }


        return $rsp_msg;
    }


    public function registration_locality($request){

        $type = $request->input('type'); 

        // Store user data in session
        Session::put('reg_locality', $type);

        Session::put('step', 2);

        return response()->json([
            'status' => 'success',
            'message' => 'Please proceed',
        ], 200);
    }

    public function registration_bussiness_details($request){

        $rules = [
            'registration_date' => ['required'],
            'const_of_business' => ['required'],
            
            'con_person_name' => ['required', 'string', 'regex:/^[A-Za-z\s]+$/', 'min:1', 'max:50'],
            'company_name' => ['required', 'string', 'min:1', 'max:150'],
            'street_add_first_business' => ['required', 'string', 'min:1', 'max:150'],
            'street_add_sec_business' => ['required', 'string', 'min:1', 'max:150'],
            'locality_land_mark_business' => ['required', 'string', 'min:1', 'max:150'],
            'village_business' => ['required', 'string', 'min:1', 'max:150'],
            'post_business' => ['required', 'string', 'min:1', 'max:150'],
            'district_business' => ['required', 'string', 'min:1', 'max:150'],
            'country_code_business' => ['required', 'string', 'min:1', 'max:150'],
            'pincode_business' => ['required', 'regex:/^\d{6}$/'], 
            'city_id' => ['required', 'string', 'max:100'],
            'state_id' => ['required', 'string', 'max:100'],
            'country_id' => 'required',

            'phone' => ['required', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],
            'alternate_mob_no_business' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],
            'whats_app_no' => ['required', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],
            'alternate_whats_app_no_business' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],

            'prim_email_business' => ['required', 'email'],
            'alt_email_business' => ['nullable', 'email'],

            'website_business' => ['nullable'],

            'bank_name_business' => ['required', 'string', 'max:255'],
            'account_no_business' => ['required', 'regex:/^\d+$/', 'max:20'],
            'account_name_business' => ['required', 'string', 'max:255'],
            'branch_code_business' => ['required', 'string', 'max:50'],
            'branch_name_business' => ['required', 'string', 'max:255'],
            'branch_address_business' => ['required', 'string', 'max:255'],
            'ifsc_code_business' => ['required', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'micr_code_business' => ['required', 'regex:/^\d{9}$/'], 
            'ad_code_business' => ['required', 'string', 'max:255'],

        ];

        if ($request->input('type_option') === 'domestic') {

            $rules['gst_no'] = ['required', 'regex:/^[0-9A-Z]{15}$/i'];
            $rules['gst_no_file'] = ['required', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'];
            $rules['gstin_current_status'] = 'required';


        } elseif ($request->input('type_option') === 'international') {

            $rules['iec_no'] = ['required', 'regex:/^[0-9A-Z]{10}$/i'];
            $rules['iec_no_file'] = ['required', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'];
            $rules['uin_current_status'] = 'required';

        }

        $messages = [
            'registration_date.required' => 'Registration date is required.',
            'const_of_business.required' => 'Constitution of business is required.',

            'gstin_current_status.required' => 'GSTIN current status is required.',
            'uin_current_status.required' => 'UIN current status is required.',
        
            'con_person_name.required' => 'Contact person name is required.',
            'con_person_name.regex' => 'Contact person name can only contain letters and spaces.',
            'con_person_name.max' => 'Contact person name may not be greater than 50 characters.',
        
            'company_name.required' => 'Company name is required.',
            'company_name.max' => 'Company name may not be greater than 150 characters.',
        
            'street_add_first_business.required' => 'Street address (line 1) is required.',
            'street_add_sec_business.required' => 'Street address (line 2) is required.',
            'locality_land_mark_business.required' => 'Locality/Landmark is required.',
            'village_business.required' => 'Village is required.',
            'post_business.required' => 'Post is required.',
            'district_business.required' => 'District is required.',
            'country_code_business.required' => 'Country code is required.',
            'pincode_business.required' => 'Pincode is required.',
            'pincode_business.regex' => 'Pincode must be a 6-digit number.',
        
            'city_id.required' => 'City is required.',
            'state_id.required' => 'State is required.',
            'country_id.required' => 'Country is required.',
        
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Phone number format is invalid.',
            'phone.max' => 'The phone number must not exceed 15 characters.',

            'alternate_mob_no_business.regex' => 'Alternate mobile number format is invalid.',
            'alternate_mob_no_business.max' => 'The Alternate mobile number must not exceed 15 characters.',

            'whats_app_no.required' => 'WhatsApp number is required.',
            'whats_app_no.regex' => 'WhatsApp number format is invalid.',
            'whats_app_no.max' => 'The WhatsApp number must not exceed 15 characters.',

            'alternate_whats_app_no_business.regex' => 'Alternate WhatsApp number format is invalid.',
            'alternate_whats_app_no_business.max' => 'The Alternate WhatsApp number must not exceed 15 characters.',

            'prim_email_business.required' => 'Primary email address is required.',
            'prim_email_business.email' => 'Primary email address must be a valid email.',
        
            'alt_email_business.email' => 'Alternate email address must be a valid email.',

            // 'website_business.required' => 'Website is required.',
        
            'bank_name_business.required' => 'Bank name is required.',
            'bank_name_business.max' => 'Bank name may not be greater than 255 characters.',
            'account_no_business.required' => 'Account number is required.',
            'account_no_business.regex' => 'Account number must contain only digits.',
            'account_no_business.max' => 'Account number may not be greater than 20 digits.',
            'account_name_business.required' => 'Account holder name is required.',
            'account_name_business.max' => 'Account holder name may not be greater than 255 characters.',
            'branch_code_business.required' => 'Branch code is required.',
            'branch_code_business.max' => 'Branch code may not be greater than 50 characters.',
            'branch_name_business.required' => 'Branch name is required.',
            'branch_name_business.max' => 'Branch name may not be greater than 255 characters.',
            'branch_address_business.required' => 'Branch address is required.',
            'branch_address_business.max' => 'Branch address may not be greater than 255 characters.',
            'ifsc_code_business.required' => 'IFSC code is required.',
            'ifsc_code_business.regex' => 'IFSC code format is invalid.',
            'micr_code_business.required' => 'MICR code is required.',
            'micr_code_business.regex' => 'MICR code must be a 9-digit number.',
            'ad_code_business.required' => 'AD code is required.',
            'ad_code_business.max' => 'AD code may not be greater than 255 characters.',
        
            // Conditional fields
            'gst_no.required' => 'The GST Number is required.',
            'gst_no.regex' => 'The GST Number format is invalid.',
            'gst_no_file.required' => 'The GST document is required.',
            'gst_no_file.mimes' => 'Invalid file format for GST document.',
            'gst_no_file.max' => 'The GST document must not be larger than 5MB.',
        
            'iec_no.required' => 'The IEC Number is required.',
            'iec_no.regex' => 'The IEC Number format is invalid.',
            'iec_no_file.required' => 'The IEC document is required.',
            'iec_no_file.mimes' => 'Invalid file format for IEC document.',
            'iec_no_file.max' => 'The IEC document must not be larger than 5MB.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            return response()->json([
                'status' => 'error',
                'message' => $errors
            ], 200);
        }

        if (
            $request->input('type_option') == 'domestic' &&
            (!Session::has('gst_validate') || Session::get('gst_validate') != "True")
        ) {

            return response()->json([
                'status' => 'error',
                'message' => 'Please Provide a valid GST Number',
            ], 200);

        } elseif (
            $request->input('type_option') == 'international' &&
            (!Session::has('iec_validate') || Session::get('iec_validate') != "True")
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please Provide a valid IEC Number',
            ], 200);
        }


        $temp_phone = $request->country_code_phone_code.'-'.$request->phone;

        if (filter_var($request->prim_email_business, FILTER_VALIDATE_EMAIL)) {
            if (User::where('email', $request->prim_email_business)->first() != null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email already exists.',
                ], 200);
            }
        }

        if (User::where('phone', $temp_phone)->first() != null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Phone already exists.' 
            ], 200);
        }


        $gst_file = null;
        $ice_file = null;
        
        $documentPath = public_path('uploads/document');
        if (!File::exists($documentPath)) {
            File::makeDirectory($documentPath, 0777, true, true);
        }
        
        if ($request->hasFile('gst_no_file')) {
            $file = $request->file('gst_no_file');
            $document_name = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $document_name);
            $gst_file = 'uploads/document/' . $document_name;
        }
        
        if ($request->hasFile('iec_no_file')) {
            $file = $request->file('iec_no_file');
            $document_name = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $document_name);
            $ice_file = 'uploads/document/' . $document_name;
        }


        $user_data_business = [
            'type_option' => $request->type_option,
            'gst_no' => $request->gst_no,
            'iec_no' => $request->iec_no,

            'gst_no_file' => $gst_file,
            'iec_no_file' => $ice_file,

            'registration_date' => $request->registration_date,
            'const_of_business' => $request->const_of_business,
            'gstin_current_status' => $request->gstin_current_status,
            'uin_current_status' => $request->uin_current_status,
            'con_person_name' => $request->con_person_name,
            'company_name' => $request->company_name,

            'street_add_first_business' => $request->street_add_first_business,
            'street_add_sec_business' => $request->street_add_sec_business,
            'locality_land_mark_business' => $request->locality_land_mark_business,
            'village_business' => $request->village_business,
            'post_business' => $request->post_business,
            'city_id_business' => $request->city_id,
            'district_business' => $request->district_business,
            'state_id_business' => $request->state_id,
            'pincode_business' => $request->pincode_business,
            'country_id_business' => $request->country_id,
            'country_code_business' => $request->country_code_business,

            'phone_business' => $request->country_code_phone_code.'-'.$request->phone,
            'phone_business_meta' => $request->phone_code_meta,

            'whats_app_no_business' => $request->country_code_whats_app_no.'-'.$request->whats_app_no,
            'whats_app_no_business_meta' => $request->whats_app_no_meta,

            'alternate_mob_no_business' => $request->country_code_alternate_mob_no_business.'-'.$request->alternate_mob_no_business,
            'alternate_mob_no_business_meta' => $request->alternate_mob_no_business_meta,

            'alternate_whats_app_no_business' => $request->country_code_alternate_whats_app_no_business.'-'.$request->alternate_whats_app_no_business,
            'alternate_whats_app_no_business_meta' => $request->alternate_whats_app_no_business_meta,

            'prim_email_business' => $request->prim_email_business,
            'alt_email_business' => $request->alt_email_business,
            'website_business' => $request->website_business,

            'bank_name_business' => $request->bank_name_business,
            'account_no_business' => $request->account_no_business,
            'account_name_business' => $request->account_name_business,
            'branch_code_business' => $request->branch_code_business,
            'branch_name_business' => $request->branch_name_business,
            'branch_address_business' => $request->branch_address_business,
            'ifsc_code_business' => $request->ifsc_code_business,
            'micr_code_business' => $request->micr_code_business,
            'ad_code_business' => $request->ad_code_business,
        ];

        // Store user data in session
        Session::put('user_data_business', $user_data_business);
        session::forget('reg_locality');

        Session::put('step', 3);

        return response()->json([
            'status' => 'success',
            'message' => 'Business Related Details Save Successfully',
        ], 200);

    }


    public function registration_personal_details($request){

        $rules = [
            'photo_file' => ['required', 'mimes:jpg,jpeg,webp,png', 'max:5120'],
            'name' => ['required', 'string', 'min:1', 'max:150'],
            'father_name' => ['required', 'string', 'min:1', 'max:150'],
            'dob' => ['required'],

            'street_add_first_personal' => ['required', 'string', 'min:1', 'max:150'],
            'street_add_sec_personal' => ['required', 'string', 'min:1', 'max:150'],
            'locality_land_mark_personal' => ['required', 'string', 'min:1', 'max:150'],
            'village_personal' => ['required', 'string', 'min:1', 'max:150'],
            'post_personal' => ['required', 'string', 'min:1', 'max:150'],
            
            'district_personal' => ['required', 'string', 'min:1', 'max:150'],
            'country_code_personal' => ['required', 'string', 'min:1', 'max:150'],
            'pincode_personal' => ['required', 'regex:/^\d{6}$/'], 

            'city_id' => ['required', 'string', 'max:100'],
            'state_id' => ['required', 'string', 'max:100'],
            'country_id' => 'required',

            'phone' => ['required', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],
            'alternate_mob_no_personal' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],
            'whats_app_no' => ['required', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],
            'alternate_whats_app_no_personal' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],

            'prim_email_personal' => ['required', 'email'],
            'alt_email_personal' => ['nullable', 'email'],

            'bank_name_personal' => ['required', 'string', 'max:255'],
            'account_no_personal' => ['required', 'regex:/^\d+$/', 'max:20'],
            'account_name_personal' => ['required', 'string', 'max:255'],
            'branch_code_personal' => ['required', 'string', 'max:50'],
            'branch_name_personal' => ['required', 'string', 'max:255'],
            'branch_address_personal' => ['required', 'string', 'max:255'],
            'ifsc_code_personal' => ['required', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'micr_code_personal' => ['required', 'regex:/^\d{9}$/'], 
            'ad_code_personal' => ['required', 'string', 'max:255'],

        ];

        if ($request->input('type_option') === 'domestic') {

            $rules['aadhaar_no'] = ['required', 'regex:/^[0-9]{12}$/i'];
            $rules['aadhaar_no_file'] = ['required', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'];
            $rules['pan_no'] = ['required', 'regex:/^[0-9A-Z]{10}$/i'];
            $rules['pan_no_file'] = ['required', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'];

        } elseif ($request->input('type_option') === 'international') {

            $rules['passport_no'] = ['required', 'regex:/^[0-9A-Z]{1,9}$/i'];
            $rules['passport_no_file'] = ['required', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'];

        }

        $messages = [
            'photo_file.required' => 'Photo file is required.',
            'photo_file.mimes' => 'Photo file must be a file of type: jpg, jpeg, webp, png.',
            'photo_file.max' => 'The Photo must not be larger than 5MB.',
        
            'name.required' => 'Name is required.',
            'father_name.required' => 'Father name is required.',
            'dob.required' => 'Date of birth is required.',
        
            'street_add_first_personal.required' => 'Street address (line 1) is required.',
            'street_add_sec_personal.required' => 'Street address (line 2) is required.',
            'locality_land_mark_personal.required' => 'Locality/Landmark is required.',
            'village_personal.required' => 'Village is required.',
            'post_personal.required' => 'Post is required.',
        
            'district_personal.required' => 'District is required.',
            'country_code_personal.required' => 'Country code is required.',
            'pincode_personal.required' => 'Pincode is required.',
            'pincode_personal.regex' => 'Pincode must be a 6-digit number.',
        
            'city_id.required' => 'City is required.',
            'state_id.required' => 'State is required.',
            'country_id.required' => 'Country is required.',
        
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Phone number format is invalid.',
            'phone.min' => 'Phone number must be at least 5 digits.',
        
            'alternate_mob_no_personal.regex' => 'Alternate mobile number format is invalid.',
            'alternate_mob_no_personal.min' => 'Alternate mobile number must be at least 5 digits.',
        
            'whats_app_no.required' => 'WhatsApp number is required.',
            'whats_app_no.regex' => 'WhatsApp number format is invalid.',
            'whats_app_no.min' => 'WhatsApp number must be at least 5 digits.',
        
            'alternate_whats_app_no_personal.regex' => 'Alternate WhatsApp number format is invalid.',
            'alternate_whats_app_no_personal.min' => 'Alternate WhatsApp number must be at least 5 digits.',
        
            'prim_email_personal.required' => 'Primary email is required.',
            'prim_email_personal.email' => 'Primary email must be a valid email address.',
            'alt_email_personal.email' => 'Alternate email must be a valid email address.',
        
            'bank_name_personal.required' => 'Bank name is required.',
            'account_no_personal.required' => 'Account number is required.',
            'account_no_personal.regex' => 'Account number must be numeric.',
            'account_no_personal.max' => 'Account number may not be greater than 20 digits.',
            'account_name_personal.required' => 'Account holder name is required.',
            'branch_code_personal.required' => 'Branch code is required.',
            'branch_name_business.required' => 'Branch name is required.',
            'branch_address_personal.required' => 'Branch address is required.',
            'ifsc_code_personal.required' => 'IFSC code is required.',
            'ifsc_code_personal.regex' => 'IFSC code format is invalid.',
            'micr_code_personal.required' => 'MICR code is required.',
            'micr_code_personal.regex' => 'MICR code must be a 9-digit number.',
            'ad_code_personal.required' => 'AD code is required.',
        
            // Domestic specific
            'aadhaar_no.required' => 'Aadhaar number is required.',
            'aadhaar_no.regex' => 'Aadhaar number must be a 12-digit number.',
            'aadhaar_no_file.required' => 'Aadhaar file is required.',
            'aadhaar_no_file.mimes' => 'Aadhaar file must be of type: jpg, jpeg, webp, png, pdf.',
            'aadhaar_no_file.max' => 'The Aadhaar document must not be larger than 5MB.',
        
            'pan_no.required' => 'PAN number is required.',
            'pan_no.regex' => 'PAN number format is invalid.',
            'pan_no_file.required' => 'PAN file is required.',
            'pan_no_file.mimes' => 'PAN file must be of type: jpg, jpeg, webp, png, pdf.',
            'pan_no_file.max' => 'The PAN document must not be larger than 5MB.',
        
            // International specific
            'passport_no.required' => 'Passport number is required.',
            'passport_no.regex' => 'Passport number format is invalid.',
            'passport_no_file.required' => 'Passport file is required.',
            'passport_no_file.mimes' => 'Passport file must be of type: jpg, jpeg, webp, png, pdf.',
            'passport_no_file.max' => 'The Passport document must not be larger than 5MB.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            return response()->json([
                'status' => 'error',
                'message' => $errors
            ], 200);
        }


        $prev_form = Session::get('user_data_business');

        if (
            $prev_form['type_option'] == 'domestic' &&
            (!Session::has('aadhaar_validate') || Session::get('aadhaar_validate') != "True")
        ) {

            return response()->json([
                'status' => 'error',
                'message' => 'Please Provide a valid Aadhaar Number',
            ], 200);

        } 
        
        // elseif (
        //     $prev_form['type_option'] == 'domestic' &&
        //     (!Session::has('pan_validate') || Session::get('pan_validate') != "True")
        // ) {

        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Please Provide a valid Pan Number',
        //     ], 200);

        // } 
        
        elseif (
            $prev_form['type_option'] == 'international' &&
            (!Session::has('passport_validate') || Session::get('passport_validate') != "True")
        ) {

            return response()->json([
                'status' => 'error',
                'message' => 'Please Provide a valid Passport Number',
            ], 200);
            
        }


        $aadhaar_no_file = null;
        $pan_no_file = null;
        $passport_no_file = null;
        $photo_file = null;
        
        $documentPath = public_path('uploads/document');
        if (!File::exists($documentPath)) {
            File::makeDirectory($documentPath, 0777, true, true);
        }
        
        if ($request->hasFile('aadhaar_no_file')) {
            $file = $request->file('aadhaar_no_file');
            $document_name = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $document_name);
            $aadhaar_no_file = 'uploads/document/' . $document_name;
        }
        
        if ($request->hasFile('pan_no_file')) {
            $file = $request->file('pan_no_file');
            $document_name = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $document_name);
            $pan_no_file = 'uploads/document/' . $document_name;
        }
        
        if ($request->hasFile('passport_no_file')) {
            $file = $request->file('passport_no_file');
            $document_name = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $document_name);
            $passport_no_file = 'uploads/document/' . $document_name;
        }
        
        if ($request->hasFile('photo_file')) {
            $file = $request->file('photo_file');
            $document_name = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $document_name);
            $photo_file = 'uploads/document/' . $document_name;
        }


        $user_data_personal = [
            'photo_file' => $photo_file,
            'name' => $request->name,
            'father_name' => $request->father_name,
            'dob' => $request->dob,
        
            'street_add_first_personal' => $request->street_add_first_personal,
            'street_add_sec_personal' => $request->street_add_sec_personal,
            'locality_land_mark_personal' => $request->locality_land_mark_personal,
            'village_personal' => $request->village_personal,
            'post_personal' => $request->post_personal,
            'district_personal' => $request->district_personal,
            'country_code_personal' => $request->country_code_personal,
            'pincode_personal' => $request->pincode_personal,
        
            'city_id' => $request->city_id,
            'state_id' => $request->state_id,
            'country_id' => $request->country_id,
        
            'phone' => $request->country_code_phone_code.'-'.$request->phone,
            'phone_code_meta' => $request->phone_code_meta,

            'whats_app_no' => $request->country_code_whats_app_no.'-'.$request->whats_app_no,
            'whats_app_no_meta' => $request->whats_app_no_meta,

            'alternate_mob_no_personal' => $request->country_code_alternate_mob_no_personal.'-'.$request->alternate_mob_no_personal,
            'alternate_mob_no_personal_meta' => $request->alternate_mob_no_personal_meta,
            
            'alternate_whats_app_no_personal' => $request->country_code_alternate_whats_app_no_personal.'-'.$request->alternate_whats_app_no_personal,
            'alternate_whats_app_no_personal_meta' => $request->alternate_whats_app_no_personal_meta,
        
            'prim_email_personal' => $request->prim_email_personal,
            'alt_email_personal' => $request->alt_email_personal,
        
            'bank_name_personal' => $request->bank_name_personal,
            'account_no_personal' => $request->account_no_personal,
            'account_name_personal' => $request->account_name_personal,
            'branch_code_personal' => $request->branch_code_personal,
            'branch_name_personal' => $request->branch_name_personal,
            'branch_address_personal' => $request->branch_address_personal,
            'ifsc_code_personal' => $request->ifsc_code_personal,
            'micr_code_personal' => $request->micr_code_personal,
            'ad_code_personal' => $request->ad_code_personal,

            'aadhaar_no' => $request->aadhaar_no ?? null,
            'aadhaar_no_file' => $aadhaar_no_file ?? null,
            'pan_no' => $request->pan_no ?? null,
            'pan_no_file' => $pan_no_file ?? null,
            'passport_no' => $request->passport_no ?? null,
            'passport_no_file' => $passport_no_file ?? null,
        ];
        
        // Store user data in session
        Session::put('user_data_personal', $user_data_personal);

        Session::put('step', 4);

        return response()->json([
            'status' => 'success',
            'message' => 'Personal Related Details Save Successfully',
        ], 200);

    }


    public function registration_license_details($request){

        $rules = [
            'd_l_no_1' => ['required', 'string', 'max:255'],
            'd_l_no_1_file' => ['required', 'file', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],

            'd_l_no_2' => ['required', 'string', 'max:255'],
            'd_l_no_2_file' => ['required', 'file', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],

            'd_l_no_3' => ['nullable', 'string', 'max:255'],
            'd_l_no_3_file' => ['nullable', 'file', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
        
            'doctor_hospital_reg_no' => ['nullable', 'string', 'max:255'],
            'doctor_hospital_reg_no_file' => ['nullable', 'file', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
    
            'dairy_trust_ngo_reg_no' => ['nullable', 'string', 'max:255'],
            'dairy_trust_ngo_reg_no_file' => ['nullable', 'file', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],

            'cc_mdl_reg_no' => ['nullable', 'string', 'max:255'],
            'cc_mdl_reg_no_file' => ['nullable', 'file', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
        ];

        $messages = [
            'd_l_no_1.required' => 'Drug / Pharmacy Licence No 1 is required.',
            'd_l_no_1_file.required' => 'Please upload Drug / Pharmacy Licence No 1 file.',
            'd_l_no_1_file.mimes' => 'Invalid format for Drug / Pharmacy Licence No 1 file. Allowed types: jpg, jpeg, webp, png, pdf.',
            'd_l_no_1_file.max' => 'Drug / Pharmacy Licence No 1 file must not exceed 5 MB.',
        
            'd_l_no_2.required' => 'Drug / Pharmacy Licence No 2 is required.',
            'd_l_no_2_file.required' => 'Please upload Drug / Pharmacy Licence No 2 file.',
            'd_l_no_2_file.mimes' => 'Invalid format for Drug / Pharmacy Licence No 2 file. Allowed types: jpg, jpeg, webp, png, pdf.',
            'd_l_no_2_file.max' => 'Drug / Pharmacy Licence No 2 file must not exceed 5 MB.',
        
            'd_l_no_3_file.mimes' => 'Invalid format for Drug / Pharmacy Licence No 3 file. Allowed types: jpg, jpeg, webp, png, pdf.',
            'd_l_no_3_file.max' => 'Drug / Pharmacy Licence No 3 file must not exceed 5 MB.',
        
            'doctor_hospital_reg_no.max' => 'Doctor / Pharmacist / Hospital Reg. No must not exceed 255 characters.',

            'doctor_hospital_reg_no_file.mimes' => 'Invalid format for Doctor / Hospital Reg. No file. Allowed types: jpg, jpeg, webp, png, pdf.',
            'doctor_hospital_reg_no_file.max' => 'Doctor / Hospital Reg. No file must not exceed 5 MB.',
        
            'dairy_trust_ngo_reg_no.max' => 'Dairy / Trust / NGO Reg. No must not exceed 255 characters.',
            'dairy_trust_ngo_reg_no_file.mimes' => 'Invalid format for Dairy / Trust / NGO Reg. No file. Allowed types: jpg, jpeg, webp, png, pdf.',
            'dairy_trust_ngo_reg_no_file.max' => 'Dairy / Trust / NGO Reg. No file must not exceed 5 MB.',
        
            'cc_mdl_reg_no.max' => 'CC / MDL Registration No must not exceed 255 characters.',
            'cc_mdl_reg_no_file.mimes' => 'Invalid format for CC / MDL Registration No file. Allowed types: jpg, jpeg, webp, png, pdf.',
            'cc_mdl_reg_no_file.max' => 'CC / MDL Registration No file must not exceed 5 MB.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);


        $validator->after(function ($validator) use ($request) {
            $hasAny =
                ($request->filled('doctor_hospital_reg_no') && $request->hasFile('doctor_hospital_reg_no_file')) ||
                ($request->filled('dairy_trust_ngo_reg_no') && $request->hasFile('dairy_trust_ngo_reg_no_file')) ||
                ($request->filled('cc_mdl_reg_no') && $request->hasFile('cc_mdl_reg_no_file'));
        
            if (!$hasAny) {
                $validator->errors()->add(
                    'doctor_hospital_reg_no_file',
                    'At least one of the following is required: Doctor/Hospital Reg. No (with file), Dairy/Trust/NGO Reg. No (with file), or CC/MDL Reg. No (with file).'
                );
            }
        });

        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            return response()->json([
                'status' => 'error',
                'message' => $errors
            ], 200);
        }

        $documentPath = public_path('uploads/document');

        // Initialize file fields with null
        $fileFields = [
            'd_l_no_1_file' => null,
            'doctor_hospital_reg_no_file' => null,
            'd_l_no_2_file' => null,
            'dairy_trust_ngo_reg_no_file' => null,
            'd_l_no_3_file' => null,
            'cc_mdl_reg_no_file' => null,
        ];

        // Handle uploads
        foreach ($fileFields as $field => $value) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $document_name = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
                $file->move($documentPath, $document_name);
                $fileFields[$field] = 'uploads/document/' . $document_name;
            }
        }

        $user_data_license = [
            'd_l_no_1' => $request->d_l_no_1,
            'd_l_no_1_file' => $fileFields['d_l_no_1_file'],
        
            'doctor_hospital_reg_no' => $request->doctor_hospital_reg_no,
            'doctor_hospital_reg_no_file' => $fileFields['doctor_hospital_reg_no_file'],
        
            'd_l_no_2' => $request->d_l_no_2,
            'd_l_no_2_file' => $fileFields['d_l_no_2_file'],
        
            'dairy_trust_ngo_reg_no' => $request->dairy_trust_ngo_reg_no,
            'dairy_trust_ngo_reg_no_file' => $fileFields['dairy_trust_ngo_reg_no_file'],
        
            'd_l_no_3' => $request->d_l_no_3,
            'd_l_no_3_file' => $fileFields['d_l_no_3_file'],
        
            'cc_mdl_reg_no' => $request->cc_mdl_reg_no,
            'cc_mdl_reg_no_file' => $fileFields['cc_mdl_reg_no_file'],
        ];

        Session::put('user_data_license', $user_data_license);


        $otp = '123456';
        $timestamp = date('Y-m-d H:i:s');

        Session::put('otp_business', $otp);
        Session::put('otp_business_timestamp', $timestamp);

        Session::put('otp_personal', $otp);
        Session::put('otp_personal_timestamp', $timestamp);


        Session::put('step', 5);

        return response()->json([
            'status' => 'success',
            'message' => 'License Related Details Save Successfully',
        ], 200);



        // $data_business = session()->get('user_data_business');
        // $data_personal = session()->get('user_data_personal');

        // $gst_no_file = $data_business['gst_no_file'];
        // $iec_no_file = $data_business['iec_no_file'];

        // $photo_file = $data_personal['photo_file'];
        // $aadhaar_no_file = $data_personal['aadhaar_no_file'];
        // $pan_no_file = $data_personal['pan_no_file'];
        // $passport_no_file = $data_personal['passport_no_file'];
 
        // $filesToMove = [
        //     'gst_no_file' => $gst_no_file,
        //     'iec_no_file' => $iec_no_file,
        //     'photo_file' => $photo_file,
        //     'aadhaar_no_file' => $aadhaar_no_file,
        //     'pan_no_file' => $pan_no_file,
        //     'passport_no_file' => $passport_no_file,
        // ];
        
        // $destinationDir = 'uploads/all/';
        
        // // Create destination directory if it doesn't exist
        // if (!file_exists($destinationDir)) {
        //     mkdir($destinationDir, 0777, true);
        // }
        
        // foreach ($filesToMove as $key => $filePath) {
        //     if (!empty($filePath) && file_exists($filePath)) {
        //         $fileName = basename($filePath);
        //         $newPath = $destinationDir . $fileName;
        //         rename($filePath, $newPath);
        //         $$key = $newPath;
        //     }
        // }

        // $user = User::create([
        //     'type_option' => $data_business['type_option'],
        //     'name' => $data_business['con_person_name'],
        //     'email' => $data_business['prim_email_business'],
        //     'phone' => '+' . $data_business['phone_business'],
        //     'phone_code_meta' => $data_business['phone_business_meta'],

        //     'password' => bcrypt(Str::random(8)),

        //     'address' => $data_business['street_add_first_business'] . ',' . $data_business['street_add_sec_business'] . ',' . $data_business['locality_land_mark_business'] . ',' . $data_business['village_business'],

        //     'postal_code' => $data_business['pincode_business'],
        //     'city_id' => $data_business['city_id_business'],
        //     'state_id' => $data_business['state_id_business'],
        //     'country_id' => $data_business['country_id_business'],

        //     'avatar' => $photo_file,
        //     'avatar_original' => $photo_file,

        //     'whats_app_no' => $data_business['whats_app_no_business'],
        //     'whats_app_no_meta' =>$data_business['whats_app_no_business_meta'],

        //     'gst_no' => $data_business['gst_no'],
        //     'iec_no' => $data_business['iec_no'],

        //     'aadhaar_no' => $data_personal['aadhaar_no'],
        //     'pan_no' => $data_personal['pan_no'],
        //     'passport_no' => $data_personal['passport_no'],
        //     'step' => '4',
        // ]);


        // $address = DB::table('addresses')->insert([
        //     'address' => $data_business['street_add_first_business'] . ',' . $data_business['street_add_sec_business'] . ',' . $data_business['locality_land_mark_business'] . ',' . $data_business['village_business'],

        //     'postal_code' => $data_business['pincode_business'],
        //     'city_id' => $data_business['city_id_business'],
        //     'state_id' => $data_business['state_id_business'],
        //     'country_id' => $data_business['country_id_business'],

        //     'phone' => '+' . $data_business['phone_business'],
        //     'user_id' => $user->id,
        //     'created_at' => now(), // Add timestamps if your table uses them
        //     'updated_at' => now(),
        // ]);

        
        // $userDetails = UserDetails::create([
        //     'user_id' => $user->id,
        //     'type_option' => $data_business['type_option'],
        //     'gst_no' => $data_business['gst_no'],

        //     'gst_no_file' => $gst_no_file,
        //     'iec_no_file' => $iec_no_file,

        //     'iec_no' => $data_business['iec_no'],
        //     'registration_date' => $data_business['registration_date'],
        //     'const_of_business' => $data_business['const_of_business'],
        //     'gstin_current_status' => $data_business['gstin_current_status'],
        //     'uin_current_status' => $data_business['uin_current_status'],
        //     'con_person_name' => $data_business['con_person_name'],
        //     'company_name' => $data_business['company_name'],
        //     'street_add_first_business' => $data_business['street_add_first_business'],
        //     'street_add_sec_business' => $data_business['street_add_sec_business'],
        //     'locality_land_mark_business' => $data_business['locality_land_mark_business'],
        //     'village_business' => $data_business['village_business'],
        //     'post_business' => $data_business['post_business'],
        //     'city_id_business' => $data_business['city_id_business'],
        //     'district_business' => $data_business['district_business'],
        //     'state_id_business' => $data_business['state_id_business'],
        //     'pincode_business' => $data_business['pincode_business'],
        //     'country_id_business' => $data_business['country_id_business'],
        //     'country_code_business' => $data_business['country_code_business'],
        
        //     'prim_mobile_no_business' => $data_business['phone_business'],
        //     'prim_mobile_no_business_meta' => $data_business['phone_business_meta'],

        //     'alt_mobile_no_business' => $data_business['alternate_mob_no_business'],
        //     'alt_mobile_no_business_meta' => $data_business['alternate_mob_no_business_meta'],

        //     'prim_whats_app_no_business' => $data_business['whats_app_no_business'],
        //     'prim_whats_app_no_business_meta' => $data_business['whats_app_no_business_meta'],

        //     'alternate_whats_app_no_business' => $data_business['alternate_whats_app_no_business'],
        //     'alternate_whats_app_no_business_meta' => $data_business['alternate_whats_app_no_business_meta'],
        
        //     'prim_email_business' => $data_business['prim_email_business'],
        //     'alt_email_business' => $data_business['alt_email_business'],
        //     'website_business' => $data_business['website_business'],

        //     'bank_name_business' => $data_business['bank_name_business'],
        //     'account_no_business' => $data_business['account_no_business'],
        //     'account_name_business' => $data_business['account_name_business'],
        //     'branch_code_business' => $data_business['branch_code_business'],
        //     'branch_name_business' => $data_business['branch_name_business'],
        //     'branch_address_business' => $data_business['branch_address_business'],
        //     'ifsc_code_business' => $data_business['ifsc_code_business'],
        //     'micr_code_business' => $data_business['micr_code_business'],
        //     'ad_code_business' => $data_business['ad_code_business'],
        
        //     'aadhaar_no' => $data_personal['aadhaar_no'],
        //     'aadhaar_no_file' => $aadhaar_no_file,
        //     'pan_no' => $data_personal['pan_no'],
        //     'pan_no_file' => $pan_no_file,
        //     'passport_no' => $data_personal['passport_no'],
        //     'passport_no_file' => $passport_no_file,
        //     'photo_file' => $photo_file,
        //     'name' => $data_personal['name'],
        //     'father_name' => $data_personal['father_name'],
        //     'dob' => $data_personal['dob'],
        //     'street_add_first' => $data_personal['street_add_first_personal'],
        //     'street_add_sec' => $data_personal['street_add_sec_personal'],
        //     'locality_land_mark' => $data_personal['locality_land_mark_personal'],
        //     'village' => $data_personal['village_personal'],
        //     'post' => $data_personal['post_personal'],
        //     'city_id' => $data_personal['city_id'],
        //     'district' => $data_personal['district_personal'],
        //     'state_id' => $data_personal['state_id'],
        //     'pincode' => $data_personal['pincode_personal'],
        //     'country_id' => $data_personal['country_id'],
        //     'country_code' => $data_personal['country_code_personal'],
        
        //     'prim_mobile_no' => $data_personal['phone'],
        //     'prim_mobile_no_meta' => $data_personal['phone_code_meta'] ?? '',

        //     'alt_mobile_no' => $data_personal['alternate_mob_no_personal'],
        //     'alt_mobile_no_meta' => $data_personal['alternate_mob_no_personal_meta'] ?? '',

        //     'prim_whats_app_no' => $data_personal['whats_app_no'],
        //     'prim_whats_app_no_meta' => $data_personal['whats_app_no_meta'] ?? '',

        //     'alt_whats_app_no' => $data_personal['alternate_whats_app_no_personal'],
        //     'alt_whats_app_no_meta' => $data_personal['alternate_whats_app_no_personal_meta'] ?? '',
        
        //     'prim_email_personal' => $data_personal['prim_email_personal'],
        //     'alt_email_personal' => $data_personal['alt_email_personal'],
        
        //     'bank_name_personal' => $data_personal['bank_name_personal'],
        //     'account_no_personal' => $data_personal['account_no_personal'],
        //     'account_name_personal' => $data_personal['account_name_personal'],
        //     'branch_code_personal' => $data_personal['branch_code_personal'],
        //     'branch_name_personal' => $data_personal['branch_name_personal'] ?? '',
        //     'branch_address_personal' => $data_personal['branch_address_personal'],
        //     'ifsc_code_personal' => $data_personal['ifsc_code_personal'],
        //     'micr_code_personal' => $data_personal['micr_code_personal'],
        //     'ad_code_personal' => $data_personal['ad_code_personal'],
        
        //     'd_l_no_1' => $request->d_l_no_1,
        //     'd_l_no_1_file' => $fileFields['d_l_no_1_file'],
        
        //     'doctor_hospital_reg_no' => $request->doctor_hospital_reg_no,
        //     'doctor_hospital_reg_no_file' => $fileFields['doctor_hospital_reg_no_file'],
        
        //     'd_l_no_2' => $request->d_l_no_2,
        //     'd_l_no_2_file' => $fileFields['d_l_no_2_file'],
        
        //     'dairy_trust_ngo_reg_no' => $request->dairy_trust_ngo_reg_no,
        //     'dairy_trust_ngo_reg_no_file' => $fileFields['dairy_trust_ngo_reg_no_file'],
        
        //     'd_l_no_3' => $request->d_l_no_3,
        //     'd_l_no_3_file' => $fileFields['d_l_no_3_file'],
        
        //     'cc_mdl_reg_no' => $request->cc_mdl_reg_no,
        //     'cc_mdl_reg_no_file' => $fileFields['cc_mdl_reg_no_file'],
        // ]);


    }


    public function verify_otp($request)
    {
        $validator = Validator::make($request->all(), [
            'otp_business' => 'required|digits:6',
            'otp_personal' => 'required|digits:6',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(), // Return the first validation error
            ], 200);
        }
    
        $otp_business = Session::get('otp_business');
        $otp_personal = Session::get('otp_personal');

        $timestamp_business = Session::get('otp_business_timestamp');
        $timestamp_personal = Session::get('otp_personal_timestamp');
    
        // Check if OTP and timestamp exist
        if (!$otp_business || !$otp_personal || !$timestamp_business || !$timestamp_personal) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP not found. Please request a new one.',
            ], 200);
        }
    
        // // Check if OTP has expired (2 minutes)
        // $timestamp = new \DateTime($timestamp);
        // $current_time = new \DateTime();
        // $interval = $current_time->getTimestamp() - $timestamp->getTimestamp();
    
        // if ($interval > 120) 
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'OTP has expired. Please request a new one.',
        //     ], 200);
        // }
    
        if ($request->otp_business == $otp_business &&  $request->otp_personal == $otp_personal) {

            $data_business = session()->get('user_data_business');
            $data_personal = session()->get('user_data_personal');
            $data_license  = session()->get('user_data_license');

            $gst_no_file = $data_business['gst_no_file'];
            $iec_no_file = $data_business['iec_no_file'];

            $photo_file = $data_personal['photo_file'];
            $aadhaar_no_file = $data_personal['aadhaar_no_file'];
            $pan_no_file = $data_personal['pan_no_file'];
            $passport_no_file = $data_personal['passport_no_file'];



            // $filesToMove = [
            //     'gst_no_file' => $gst_no_file,
            //     'iec_no_file' => $iec_no_file,
            //     'photo_file' => $photo_file,
            //     'aadhaar_no_file' => $aadhaar_no_file,
            //     'pan_no_file' => $pan_no_file,
            //     'passport_no_file' => $passport_no_file,
            // ];
            
            // $destinationDir = 'uploads/all/';
            
            // // Create destination directory if it doesn't exist
            // if (!file_exists($destinationDir)) {
            //     mkdir($destinationDir, 0777, true);
            // }
            
            // foreach ($filesToMove as $key => $filePath) {
            //     if (!empty($filePath) && file_exists($filePath)) {
            //         $fileName = basename($filePath);
            //         $newPath = $destinationDir . $fileName;
            //         rename($filePath, $newPath);
            //         $$key = $newPath;
            //     }
            // }

            $user = User::create([
                'type_option' => $data_business['type_option'],
                'name' => $data_business['con_person_name'],
                'email' => $data_business['prim_email_business'],
                'phone' => '+' . $data_business['phone_business'],
                'phone_code_meta' => $data_business['phone_business_meta'],

                'password' => bcrypt(Str::random(8)),

                'address' => $data_business['street_add_first_business'] . ',' . $data_business['street_add_sec_business'] . ',' . $data_business['locality_land_mark_business'] . ',' . $data_business['village_business'],

                'postal_code' => $data_business['pincode_business'],
                'city_id' => $data_business['city_id_business'],
                'state_id' => $data_business['state_id_business'],
                'country_id' => $data_business['country_id_business'],

                'avatar' => $photo_file,
                'avatar_original' => $photo_file,

                'whats_app_no' => $data_business['whats_app_no_business'],
                'whats_app_no_meta' =>$data_business['whats_app_no_business_meta'],

                'gst_no' => $data_business['gst_no'],
                'iec_no' => $data_business['iec_no'],

                'aadhaar_no' => $data_personal['aadhaar_no'],
                'pan_no' => $data_personal['pan_no'],
                'passport_no' => $data_personal['passport_no'],
                'step' => '4',
            ]);


            $address = DB::table('addresses')->insert([
                'address' => $data_business['street_add_first_business'] . ',' . $data_business['street_add_sec_business'] . ',' . $data_business['locality_land_mark_business'] . ',' . $data_business['village_business'],

                'postal_code' => $data_business['pincode_business'],
                'city_id' => $data_business['city_id_business'],
                'state_id' => $data_business['state_id_business'],
                'country_id' => $data_business['country_id_business'],

                'phone' => '+' . $data_business['phone_business'],
                'user_id' => $user->id,
                'created_at' => now(), // Add timestamps if your table uses them
                'updated_at' => now(),
            ]);

            
            $userDetails = UserDetails::create([
                'user_id' => $user->id,
                'type_option' => $data_business['type_option'],
                'gst_no' => $data_business['gst_no'],

                'gst_no_file' => $gst_no_file,
                'iec_no_file' => $iec_no_file,

                'iec_no' => $data_business['iec_no'],
                'registration_date' => $data_business['registration_date'],
                'const_of_business' => $data_business['const_of_business'],
                'gstin_current_status' => $data_business['gstin_current_status'],
                'uin_current_status' => $data_business['uin_current_status'],
                'con_person_name' => $data_business['con_person_name'],
                'company_name' => $data_business['company_name'],
                'street_add_first_business' => $data_business['street_add_first_business'],
                'street_add_sec_business' => $data_business['street_add_sec_business'],
                'locality_land_mark_business' => $data_business['locality_land_mark_business'],
                'village_business' => $data_business['village_business'],
                'post_business' => $data_business['post_business'],
                'city_id_business' => $data_business['city_id_business'],
                'district_business' => $data_business['district_business'],
                'state_id_business' => $data_business['state_id_business'],
                'pincode_business' => $data_business['pincode_business'],
                'country_id_business' => $data_business['country_id_business'],
                'country_code_business' => $data_business['country_code_business'],
            
                'prim_mobile_no_business' => $data_business['phone_business'],
                'prim_mobile_no_business_meta' => $data_business['phone_business_meta'],

                'alt_mobile_no_business' => $data_business['alternate_mob_no_business'],
                'alt_mobile_no_business_meta' => $data_business['alternate_mob_no_business_meta'],

                'prim_whats_app_no_business' => $data_business['whats_app_no_business'],
                'prim_whats_app_no_business_meta' => $data_business['whats_app_no_business_meta'],

                'alternate_whats_app_no_business' => $data_business['alternate_whats_app_no_business'],
                'alternate_whats_app_no_business_meta' => $data_business['alternate_whats_app_no_business_meta'],
            
                'prim_email_business' => $data_business['prim_email_business'],
                'alt_email_business' => $data_business['alt_email_business'],
                'website_business' => $data_business['website_business'],

                'bank_name_business' => $data_business['bank_name_business'],
                'account_no_business' => $data_business['account_no_business'],
                'account_name_business' => $data_business['account_name_business'],
                'branch_code_business' => $data_business['branch_code_business'],
                'branch_name_business' => $data_business['branch_name_business'],
                'branch_address_business' => $data_business['branch_address_business'],
                'ifsc_code_business' => $data_business['ifsc_code_business'],
                'micr_code_business' => $data_business['micr_code_business'],
                'ad_code_business' => $data_business['ad_code_business'],
            
                'aadhaar_no' => $data_personal['aadhaar_no'],
                'aadhaar_no_file' => $aadhaar_no_file,
                'pan_no' => $data_personal['pan_no'],
                'pan_no_file' => $pan_no_file,
                'passport_no' => $data_personal['passport_no'],
                'passport_no_file' => $passport_no_file,
                'photo_file' => $photo_file,
                'name' => $data_personal['name'],
                'father_name' => $data_personal['father_name'],
                'dob' => $data_personal['dob'],
                'street_add_first' => $data_personal['street_add_first_personal'],
                'street_add_sec' => $data_personal['street_add_sec_personal'],
                'locality_land_mark' => $data_personal['locality_land_mark_personal'],
                'village' => $data_personal['village_personal'],
                'post' => $data_personal['post_personal'],
                'city_id' => $data_personal['city_id'],
                'district' => $data_personal['district_personal'],
                'state_id' => $data_personal['state_id'],
                'pincode' => $data_personal['pincode_personal'],
                'country_id' => $data_personal['country_id'],
                'country_code' => $data_personal['country_code_personal'],
            
                'prim_mobile_no' => $data_personal['phone'],
                'prim_mobile_no_meta' => $data_personal['phone_code_meta'] ?? '',

                'alt_mobile_no' => $data_personal['alternate_mob_no_personal'],
                'alt_mobile_no_meta' => $data_personal['alternate_mob_no_personal_meta'] ?? '',

                'prim_whats_app_no' => $data_personal['whats_app_no'],
                'prim_whats_app_no_meta' => $data_personal['whats_app_no_meta'] ?? '',

                'alt_whats_app_no' => $data_personal['alternate_whats_app_no_personal'],
                'alt_whats_app_no_meta' => $data_personal['alternate_whats_app_no_personal_meta'] ?? '',
            
                'prim_email_personal' => $data_personal['prim_email_personal'],
                'alt_email_personal' => $data_personal['alt_email_personal'],
            
                'bank_name_personal' => $data_personal['bank_name_personal'],
                'account_no_personal' => $data_personal['account_no_personal'],
                'account_name_personal' => $data_personal['account_name_personal'],
                'branch_code_personal' => $data_personal['branch_code_personal'],
                'branch_name_personal' => $data_personal['branch_name_personal'] ?? '',
                'branch_address_personal' => $data_personal['branch_address_personal'],
                'ifsc_code_personal' => $data_personal['ifsc_code_personal'],
                'micr_code_personal' => $data_personal['micr_code_personal'],
                'ad_code_personal' => $data_personal['ad_code_personal'],
            
                'd_l_no_1' => $data_license['d_l_no_1'],
                'd_l_no_1_file' => $data_license['d_l_no_1_file'],
            
                'doctor_hospital_reg_no' => $data_license['doctor_hospital_reg_no'],
                'doctor_hospital_reg_no_file' => $data_license['doctor_hospital_reg_no_file'],
            
                'd_l_no_2' => $data_license['d_l_no_2'],
                'd_l_no_2_file' => $data_license['d_l_no_2_file'],
            
                'dairy_trust_ngo_reg_no' => $data_license['dairy_trust_ngo_reg_no'],
                'dairy_trust_ngo_reg_no_file' => $data_license['dairy_trust_ngo_reg_no_file'],
            
                'd_l_no_3' => $data_license['d_l_no_3'],
                'd_l_no_3_file' => $data_license['d_l_no_3_file'],
            
                'cc_mdl_reg_no' => $data_license['cc_mdl_reg_no'],
                'cc_mdl_reg_no_file' => $data_license['cc_mdl_reg_no_file'],
            ]);

            $user = User::find($user->id);

            $this->guard()->login($user);

            try {
                EmailUtility::customer_registration_email('registration_email_to_customer', $user, null);
            } catch (\Exception $e) {}

            // customer Account Opening Email to Admin

            try {
                EmailUtility::customer_registration_email('customer_reg_email_to_admin', $user, null);
            } catch (\Exception $e) {}

            $this->guard()->logout();

            Session::put('step', 6);

            return response()->json([
                'status' => 'success',
                'message' => 'OTP has been verified.',
            ], 200);

        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid OTP.',
            ], 200);
        }
    }


    // -----------------------------------  validation code ------------------------------------- //

    public function gst_validate($request)
    {
        $validator = Validator::make($request->all(), [
            'gst_no' => [
                'required', 
                'regex:/^[0-9A-Z]{15}$/i'
            ], 
        ], [
            'gst_no.required' => 'The GST Number is required.',
            'gst_no.regex' => 'The GST Number format is invalid.',
        ]);
        
        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            return response()->json([
                'status' => 'error',
                'message' => $errors
            ], 200);
        }

        $data = User::where('gst_no', $request->gst_no)->first();

        if($data){
            // if($data->approval_status == 0){
            //     Session::put('temp_user_id', $data->id);
            // } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'GST No Already registered',
                ], 200);
            // }
        }

        $response = json_decode(fetchGstinDetails($request->gst_no));

        if (isset($response->message_code) && $response->message_code == "success") {
            Session::put('gst_validate', 'True');
            Session::put('pan_no', $response->data->pan_number);

            return response()->json([
                'status' => 'success',
                'message' => 'GST No Validate Successfully',
                'data' => $response->data,
            ], 200);

        } else {
            Session::put('gst_validate', 'false');
            
            if (Session::has('pan_no')) {
                Session::forget('pan_no');
            }

            return response()->json([
                'status' => 'error',
                'message' => $response->message ?? 'GST Not Valid',
            ], 200);
        }



    }

    public function iec_validate($request)
    {
        $validator = Validator::make($request->all(), [
            'iec_no' => [
                'required', 
                'regex:/^[0-9A-Z]{10}$/i'
            ], 
        ], [
            'iec_no.required' => 'The IEC Number is required.',
            'iec_no.regex' => 'The IEC Number format is invalid.',
        ]);
        
        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            return response()->json([
                'status' => 'error',
                'message' => $errors
            ], 200);
        }

        $data = User::where('iec_no', $request->iec_no)->first();

        if($data){
            // if($data->approval_status == 0){
            //     Session::put('temp_user_id', $data->id);
            // } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ICE No Already registered',
                ], 200);
            // }
        }

        $response = json_decode(fetchIECDetails($request->iec_no));

        if (isset($response->message_code) && $response->message_code == "success") {
            Session::put('iec_validate', 'True');
            Session::put('pan_no', $response->data->pan_number);

            return response()->json([
                'status' => 'success',
                'message' => 'ICE No Validate Successfully',
                'data' => $response->data,
            ], 200);

        } else {
            Session::put('iec_validate', 'false');

            if (Session::has('pan_no')) {
                Session::forget('pan_no');
            }

            return response()->json([
                'status' => 'error',
                'message' => $response->message ?? 'ICE No Not Valid',
            ], 200);
        }
        
    }


    public function aadhaar_validate($request)
    {
        $validator = Validator::make($request->all(), [
            'aadhaar_no' => [
                'required', 
                'regex:/^[0-9A-Z]{12}$/i'
            ], 
        ], [
            'aadhaar_no.required' => 'The Aadhaar Number is required.',
            'aadhaar_no.regex' => 'The Aadhaar Number format is invalid.',
        ]);
        
        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            return response()->json([
                'status' => 'error',
                'message' => $errors
            ], 200);
        }

        $data = User::where('aadhaar_no', $request->aadhaar_no)->first();

        if($data){

                return response()->json([
                    'status' => 'error',
                    'message' => 'Aadhaar Number Already registered',
                ], 200);
        }

        $response = json_decode(requestOtpAadhar($request->aadhaar_no));

        if ($response->success) {

            Session::put('customer_aadhar_clientId', $response->data->client_id);
            Session::put('aadhar_no', $request->aadhaar_no);

            return response()->json([
                'status' => 'success',
                'message' => "OTP Resend to linked Mobile number with ' . $request->aadhaar_no . ' Aadhar number.",
                'data' => 'open',
            ], 200);

        } else {
            //do failure stuff
            if ($response->status_code == 429) {

                return response()->json([
                    'status' => 'error',
                    'message' => "Wait 60 seconds to generate OTP for same Aadhaar Number.",
                ], 200);

            } else {

                $rsp_msg['response'] = 'error';
                $rsp_msg['message']  = "Invalid Aadhar number / No mobile number is linked with " . $request->aadhar . " Aadhar number!";

                return response()->json([
                    'status' => 'error',
                    'message' => "Invalid Aadhar number / No mobile number is linked with ' . $request->aadhar . ' Aadhar number!",
                ], 200);
            }
        }

    }

    public function aadhaar_otp_validate($request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits:6',
        ]);
        
        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            return response()->json([
                'status' => 'error',
                'message' => $errors
            ], 200);
        }

        $response = json_decode(validateOtpAadhar($request->otp,Session::get('customer_aadhar_clientId')));

        if ($response->success) {
            Session::put('aadhaar_validate', 'True');

            return response()->json([
                'status' => 'success',
                'message' => 'Aadhar Number verified successfully',
                'data' => $response->data,
            ], 200);

        } else {
            Session::put('aadhaar_validate', 'false');

            return response()->json([
                'status' => 'error',
                'message' => 'Aadhar OTP verification failed!',
            ], 200);
        }

    }

    public function pan_validate($request)
    {
        $validator = Validator::make($request->all(), [
            'pan_no' => [
                'required', 
                'regex:/^[0-9A-Z]{10}$/i'
            ], 
        ], [
            'pan_no.required' => 'The Pan Number is required.',
            'pan_no.regex' => 'The Pan Number format is invalid.',
        ]);
        
        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            return response()->json([
                'status' => 'error',
                'message' => $errors
            ], 200);
        }

        $data = User::where('pan_no', $request->pan_no)->first();

        if($data){

                return response()->json([
                    'status' => 'error',
                    'message' => 'Pan Number Already registered',
                ], 200);
        }


        Session::put('pan_validate', 'True');

        return response()->json([
            'status' => 'success',
            'message' => 'Pan No Validate Successfully',
        ], 200);
    }

    public function passport_validate($request)
    {
        $validator = Validator::make($request->all(), [
            'passport_no' => [
                'required', 
                'regex:/^[0-9A-Z]{1,15}$/i'
            ], 
            'dob' => ['required'],
        ], [
            'passport_no.required' => 'The Passport File Number is required.',
            'passport_no.regex' => 'The Passport File Number format is invalid.',
        ]);
        
        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            return response()->json([
                'status' => 'error',
                'message' => $errors
            ], 200);
        }

        $data = User::where('passport_no', $request->passport_no)->first();

        if($data){
                Session::put('passport_validate', 'False');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Passport Number Already registered',
                ], 200);
        }


        $response = json_decode(passport_details($request->passport_no, $request->dob));

        if (isset($response->message_code) && $response->message_code == "success") {
            Session::put('passport_validate', 'True');

            return response()->json([
                'status' => 'success',
                'message' => 'Passport File No Validate Successfully',
                'data' => $response->data,
            ], 200);

        } else {
            Session::put('passport_validate', 'false');

            return response()->json([
                'status' => 'error',
                'message' => 'Passport File No Not Valid',
            ], 200);
        }

    }


    // public function store_personal_details($request)
    // {

    //     $validator = Validator::make($request->all(), [

    //         'name' => ['required', 'string', 'regex:/^[A-Za-z\s]+$/', 'min:1', 'max:50'],
    //         'company_name' => ['required', 'string', 'min:1', 'max:150'],
    //         'email_id' => ['required', 'email'],
    //         'phone' => ['required', 'regex:/^[\d\s\-\+]+$/', 'min:5'],
    //         'tel_number' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5'],
    //         'post' => ['nullable', 'string', 'max:255'],
    //         'whats_app_no' => ['required', 'regex:/^[\d\s\-\+]+$/', 'min:5'],

    //         'password' => [
    //             'required',
    //             'string',
    //             'min:8', // Minimum length of 8 characters
    //             'regex:/[A-Z]/', // At least one uppercase letter
    //             'regex:/[!@#$%^&*(),.?":{}|<>]/', // At least one special character
    //             'confirmed' // Ensures password and confirm_password match
    //         ],
    //         'password_confirmation' => ['required', 'string', 'min:8'], // Confirmation field
    //     ], [

    //         'name.required' => 'The Name field is required.',
    //         'name.string' => 'The Name must be a string.',
    //         'name.regex' => 'The Name must only contain letters and spaces.',
    //         'name.min' => 'The Name must be at least 1 character.',
    //         'name.max' => 'The Name may not be greater than 50 characters.',
        
    //         'email_id.required' => 'The Email field is required.',
    //         'email_id.email' => 'The Email must be a valid email address.',
        
    //         'phone.required' => 'The Phone Number field is required.',
    //         'phone.regex' => 'The Phone Number format is invalid.',
    //         'phone.min' => 'The Phone Number must be at least 5 characters.',
        
    //         'tel_number.regex' => 'The Tel Number format is invalid.',
    //         'tel_number.min' => 'The Tel Number must be at least 5 characters.',

    //         'whats_app_no.required' => 'The Whatsapp Number field is required.',
        
    //         'password.required' => 'The Password field is required.',
    //         'password.min' => 'The Password must be at least 8 characters long.',
    //         'password.regex' => 'The Password must contain at least one uppercase letter and one special character.',

    //         'password.confirmed' => 'The Password and Confirm Password do not match.',
    //         'password_confirmation.required' => 'The Confirm Password field is required.',
    //     ]);
        
    //     if ($validator->fails()) {

    //         $errors = $validator->errors()->all();

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $errors
    //         ], 200);
    //     }

    //     if (session()->has('temp_user_id')) {

    //         $temp_phone = $request->country_code_phone_code.'-'.$request->phone;

    //         if (filter_var($request->email_id, FILTER_VALIDATE_EMAIL)) {
    //             if (User::where('email', $request->email_id)->where('id', '!=', session('temp_user_id'))->first() != null) {
    //                 return response()->json([
    //                     'status' => 'error',
    //                     'message' => 'Email already exists.',
    //                 ], 200);
    //             }
    //         }

    //         if (User::where('phone', $temp_phone)->where('id', '!=', session('temp_user_id'))->first() != null) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Phone already exists.'
    //             ], 200);
    //         }
    
    //     } else {


    //         $temp_phone = $request->country_code_phone_code.'-'.$request->phone;

    //         if (filter_var($request->email_id, FILTER_VALIDATE_EMAIL)) {
    //             if (User::where('email', $request->email_id)->first() != null) {
    //                 return response()->json([
    //                     'status' => 'error',
    //                     'message' => 'Email already exists.',
    //                 ], 200);
    //             }
    //         }

    //         if (User::where('phone', $temp_phone)->first() != null) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Phone already exists.' 
    //             ], 200);
    //         }

    //     }

    //     // Assuming existing user_data in the session
    //     $existing_user_data = session('user_data', []);  // Fetch existing session data or an empty array if not set

    //     $new_user_data = [
    //         'company_name' => $request->company_name,
    //         'name' => $request->name,
    //         'email' => $request->email_id,
    //         'phone' => $request->country_code_phone_code.'-'.$request->phone,
    //         'phone_code_meta' => $request->phone_code_meta,
    //         'tel_number' => $request->tel_number,
    //         'whats_app_no' => $request->country_code_whats_app_no.'-'.$request->whats_app_no,
    //         'whats_app_no_meta' => $request->whats_app_no_meta,
    //         'post' => $request->post,
    //         'password'  => Hash::make($request->password),
    //     ];

    //     // Merge the existing user data with the new user data
    //     $combined_user_data = array_merge($existing_user_data, $new_user_data);
        
    //     // Store user data in session
    //     Session::put('user_data', $combined_user_data);

    //     // $otp = mt_rand(100000, 999999);
    //     $otp = '123456';
    //     Session::put('otp', $otp);

    //     $timestamp = date('Y-m-d H:i:s'); // Use PHP's native date() function for timestamp
    //     Session::put('otp_timestamp', $timestamp);

    //     Session::put('step', 3);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'OTP has been this Phone'. $request->country_code_phone_code.' '.$request->phone,
    //     ], 200);
    // }



    // public function store_personal_address($request)
    // {

    //     $validator = Validator::make($request->all(), [

    //         'address' => ['required', 'string', 'max:255'],
    //         'pincode' => ['required', 'regex:/^\d{6}$/'], // Assuming Indian pincode format
    //         'city_id' => ['required', 'string', 'max:100'],
    //         'state_id' => ['required', 'string', 'max:100'],
    //         'country_id' => 'required', // ISO 3166-1 alpha-2

    //     ], [

    //         'address.required' => 'The Address 1 field is required.',
    //         'address.string' => 'The Address 1 must be a string.',
    //         'address.max' => 'The Address 1 may not be greater than 255 characters.',
        
    //         'pincode.required' => 'The Pincode field is required.',
    //         'pincode.regex' => 'The Pincode format is invalid.',
        
    //         'city_id.required' => 'The city field is required.',
    //         'city_id.string' => 'The city must be a string.',
    //         'city_id.max' => 'The city may not be greater than 100 characters.',
        
    //         'state_id.required' => 'The State field is required.',
    //         'state_id.string' => 'The State must be a string.',
    //         'state_id.max' => 'The State may not be greater than 100 characters.',
        
    //         'country_id.required' => 'The Country field is required.',

    //     ]);
        
    //     if ($validator->fails()) {

    //         $errors = $validator->errors()->all();

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $errors
    //         ], 200);
    //     }

    //     $user = User::where('id', session()->get('temp_user_id'))->update([
    //         'address' => $request->address,
    //         'pincode' => $request->pincode,
    //         'city_id' => $request->city_id,
    //         'state_id' => $request->state_id,
    //         'country_id' => $request->country_id,
    //         'step' => 5,
    //     ]);
    //     $data = Session::get('user_data');


    //     $user_Address = Address::where('user_id', session()->get('temp_user_id'))->first();

    //     if ($user_Address) {
    //         $address = DB::table('addresses')
    //             ->where('user_id', session()->get('temp_user_id'))
    //             ->update([
    //                 'address' => $request->address,
    //                 'postal_code' => $request->pincode,
    //                 'city_id' => $request->city_id,
    //                 'state_id' => $request->state_id,
    //                 'country_id' => $request->country_id,
    //                 'phone' => '+' . $data['phone'],
    //                 'updated_at' => now(), // Update timestamp if your table uses it
    //             ]);
    //     } else {
    //         $address = DB::table('addresses')->insert([
    //             'address' => $request->address,
    //             'postal_code' => $request->pincode,
    //             'city_id' => $request->city_id,
    //             'state_id' => $request->state_id,
    //             'country_id' => $request->country_id,
    //             'phone' => '+' . $data['phone'],
    //             'user_id' => session()->get('temp_user_id'),
    //             'created_at' => now(), // Add timestamps if your table uses them
    //             'updated_at' => now(),
    //         ]);
    //     }

    //     Session::put('step', 5);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Address Details Save Successfully',
    //     ], 200);
    // }

    // public function store_bank_details($request)
    // {

    //     $validator = Validator::make($request->all(), [
    //         'bank_name' => ['required', 'string', 'max:255'],
    //         'account_no' => ['required', 'regex:/^\d+$/', 'max:20'], // Numeric only
    //         'branch_no' => ['required', 'string', 'max:50'],
    //         'branch_code' => ['required', 'string', 'max:50'],
    //         'ifsc_code' => ['required', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'], // IFSC code format
    //         'micr_code' => ['required', 'regex:/^\d{9}$/'], // MICR code format
    //         'customer_care_executive' => ['required', 'string', 'max:255'],
    //     ], [
    //         // Custom error messages
    //         'bank_name.required' => 'The bank name is required.',
    //         'bank_name.string' => 'The bank name must be a valid string.',
    //         'bank_name.max' => 'The bank name must not exceed 255 characters.',
            
    //         'account_no.required' => 'The account number is required.',
    //         'account_no.regex' => 'The account number must contain only numeric characters.',
    //         'account_no.max' => 'The account number must not exceed 20 digits.',
            
    //         'branch_no.required' => 'The branch number is required.',
    //         'branch_no.string' => 'The branch number must be a valid string.',
    //         'branch_no.max' => 'The branch number must not exceed 50 characters.',
            
    //         'branch_code.required' => 'The branch code is required.',
    //         'branch_code.string' => 'The branch code must be a valid string.',
    //         'branch_code.max' => 'The branch code must not exceed 50 characters.',
            
    //         'ifsc_code.required' => 'The IFSC Code is required.',
    //         'ifsc_code.regex' => 'The IFSC Code format is invalid. It should follow the format: 4 uppercase letters, a 0, followed by 6 alphanumeric characters.',
            
    //         'micr_code.required' => 'The MICR Code is required.',
    //         'micr_code.regex' => 'The MICR Code must be exactly 9 numeric digits.',
            
    //         'customer_care_executive.required' => 'The customer care executive name is required.',
    //         'customer_care_executive.string' => 'The customer care executive name must be a valid string.',
    //         'customer_care_executive.max' => 'The customer care executive name must not exceed 255 characters.',
    //     ]);
        
    //     if ($validator->fails()) {

    //         $errors = $validator->errors()->all();

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $errors
    //         ], 200);
    //     }

    //     $user = User::where('id', session()->get('temp_user_id'))->update([
    //         'bank_name' => $request->bank_name,
    //         'account_no' => $request->account_no,
    //         'branch_no' => $request->branch_no,
    //         'branch_code' => $request->branch_code,
    //         'ifsc_code' => $request->ifsc_code,
    //         'micr_code' => $request->micr_code,
    //         'customer_care_executive' => $request->customer_care_executive,
    //         'step' => 6,
    //     ]);

    //     Session::put('step', 6);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Bank Details Save Successfully',
    //     ], 200);
    // }

    // public function store_license_details($request)
    // {

    //     $validator = Validator::make($request->all(), [

    //         'cc_no' => ['required', 'regex:/^[\d\s\-\+]+$/', 'min:5'],
    //         'd_l_no_1' => ['required', 'string', 'max:50'],
    //         'd_l_no_2' => ['required', 'string', 'max:50'],
    //         'd_l_no_3' => ['required', 'string', 'max:50'],

    //     ],[
    //         // Custom error messages
    //         'cc_no.required' => 'The CC number is required.',
    //         'cc_no.regex' => 'The CC number must only contain numbers, spaces, dashes, or plus signs.',
    //         'cc_no.min' => 'The CC number must be at least 5 characters long.',
            
    //         'd_l_no_1.required' => 'The first D.L.No is required.',
    //         'd_l_no_1.string' => 'The first D.L.No must be a valid string.',
    //         'd_l_no_1.max' => 'The first D.L.No must not exceed 50 characters.',
            
    //         'd_l_no_2.required' => 'The second D.L.No is required.',
    //         'd_l_no_2.string' => 'The second D.L.No must be a valid string.',
    //         'd_l_no_2.max' => 'The second D.L.No must not exceed 50 characters.',
            
    //         'd_l_no_3.required' => 'The third D.L.No is required.',
    //         'd_l_no_3.string' => 'The third D.L.No must be a valid string.',
    //         'd_l_no_3.max' => 'The third D.L.No must not exceed 50 characters.',
    //     ]);
        
    //     if ($validator->fails()) {

    //         $errors = $validator->errors()->all();

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $errors
    //         ], 200);
    //     }

    //     $user = User::where('id', session()->get('temp_user_id'))->update([
    //         'cc_no' => $request->cc_no,
    //         'd_l_no_1' => $request->d_l_no_1,
    //         'd_l_no_2' => $request->d_l_no_2,
    //         'd_l_no_3' => $request->d_l_no_3,
    //         'step' => 7,
    //     ]);

    //     Session::put('step', 7);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'License Details Save Successfully',
    //     ], 200);
    // }

    // public function store_transport_details($request)
    // {

    //     $validator = Validator::make($request->all(), [

    //         'd_l_exp_Date' => ['required', 'date'],
    //         'transport' => ['required', 'string', 'max:255'],
    //         'cargo' => ['required', 'string', 'max:255'],
    //         'booked_to' => ['required', 'string', 'max:255'],

    //     ],[
    //         // Custom error messages
    //         'd_l_exp_Date.required' => 'The D.L expiration date is required.',
    //         'd_l_exp_Date.date' => 'The D.L expiration date must be a valid date.',
            
    //         'transport.required' => 'The transport field is required.',
    //         'transport.string' => 'The transport field must be a valid string.',
    //         'transport.max' => 'The transport field must not exceed 255 characters.',
            
    //         'cargo.required' => 'The cargo field is required.',
    //         'cargo.string' => 'The cargo field must be a valid string.',
    //         'cargo.max' => 'The cargo field must not exceed 255 characters.',
            
    //         'booked_to.required' => 'The booked-to field is required.',
    //         'booked_to.string' => 'The booked-to field must be a valid string.',
    //         'booked_to.max' => 'The booked-to field must not exceed 255 characters.',
    //     ]);
        
    //     if ($validator->fails()) {

    //         $errors = $validator->errors()->all();

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $errors
    //         ], 200);
    //     }

    //     $user = User::where('id', session()->get('temp_user_id'))->update([
    //         'd_l_exp_Date' => $request->d_l_exp_Date,
    //         'transport' => $request->transport,
    //         'cargo' => $request->cargo,
    //         'booked_to' => $request->booked_to,
    //         'step' => 8,
    //     ]);

    //     $user = User::find(session()->get('temp_user_id'));

    //     $this->guard()->login($user);

    //     try {
    //         EmailUtility::customer_registration_email('registration_email_to_customer', $user, null);
    //     } catch (\Exception $e) {}

    //     // customer Account Opening Email to Admin

    //     try {
    //         EmailUtility::customer_registration_email('customer_reg_email_to_admin', $user, null);
    //     } catch (\Exception $e) {}

    //     $this->guard()->logout();

    //     Session::put('step', 8);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Transport Details Save Successfully',
    //     ], 200);
    // }

}
