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

        $data = Session::get('user_data');
    
        // Ensure $data exists
        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'User data not found. Please start the process again.',
            ], 200);
        }

        // $otp = mt_rand(100000, 999999);
        $otp = '123456';
        Session::put('otp', $otp);

        $timestamp = date('Y-m-d H:i:s'); // Use PHP's native date() function for timestamp
        Session::put('otp_timestamp', $timestamp);


        // $user = [
        //     'name' => $data['name'],
        //     'phone' => '+'.$data['phone'],
        //     'password' => $data['password'],
        //     'verification_code' => $otp,
        // ];

        // $otpController = new OTPVerificationController;
        // $otpController->send_code($user);


        // Return a success response
        return response()->json([
            'status' => 'success',
            'message' => 'OTP has been Resend no this Phone : ' . $data['phone'],
        ], 200);
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

        if($step == 3){
            $step = 2;
        }

        Session::put('step', $step);
    
        // Return a JSON response with the generated HTML and step
        return response()->json([
            'success' => true,
            'step' => $step,
        ]);
    }

    public function create_account($param, Request $request)
    {

        if ($param == "gst") {

            $rsp_msg = $this->store_gst($request);

        } elseif ($param == "personal-details") {

            $rsp_msg = $this->store_personal_details($request);
        
        } elseif ($param == "verify-phone") {

            $rsp_msg = $this->verify_otp($request);

        } elseif ($param == "personal-address") {

            $rsp_msg = $this->store_personal_address($request);

        } elseif ($param == "bank-details") {

            $rsp_msg = $this->store_bank_details($request);

        } elseif ($param == "license-details") {

            $rsp_msg = $this->store_license_details($request);

        } elseif ($param == "transport-details") {

            $rsp_msg = $this->store_transport_details($request);

        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid parameter: $param',
            ], 200);
        }


        return $rsp_msg;
    }


    public function store_gst($request)
    {

        $validator = Validator::make($request->all(), [
            'gst_no' => ['required', 'regex:/^[0-9]{15}$/'], // Assuming GSTIN format
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
            if($data->approval_status == 0){
                Session::put('temp_user_id', $data->id);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'GST No Already registered',
                ], 200);
            }
        }

        $user_data = [
            'gst_no' => $request->gst_no,
        ];
        
        // Store user data in session
        Session::put('user_data', $user_data);

        Session::put('step', 2);

        return response()->json([
            'status' => 'success',
            'message' => 'GST No Save Successfully',
        ], 200);
    }


    public function store_personal_details($request)
    {

        $validator = Validator::make($request->all(), [

            'name' => ['required', 'string', 'regex:/^[A-Za-z\s]+$/', 'min:1', 'max:50'],
            'company_name' => ['required', 'string', 'min:1', 'max:150'],
            'email_id' => ['required', 'email'],
            'phone' => ['required', 'regex:/^[\d\s\-\+]+$/', 'min:5'],
            'tel_number' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5'],
            'post' => ['nullable', 'string', 'max:255'],
            'whats_app_no' => ['required', 'regex:/^[\d\s\-\+]+$/', 'min:5'],

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
        
            'tel_number.regex' => 'The Tel Number format is invalid.',
            'tel_number.min' => 'The Tel Number must be at least 5 characters.',

            'whats_app_no.required' => 'The Whatsapp Number field is required.',
        
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

        if (session()->has('temp_user_id')) {

            if (filter_var($request->email_id, FILTER_VALIDATE_EMAIL)) {
                if (User::where('email', $request->email_id)->where('id', '!=', session('temp_user_id'))->first() != null) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Email already exists.',
                    ], 200);
                }
            }

            elseif (User::where('phone', $request->country_code_phone_code.$request->phone)->where('id', '!=', session('temp_user_id'))->first() != null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Phone already exists.'
                ], 200);
            }
    
        } else {

            if (filter_var($request->email_id, FILTER_VALIDATE_EMAIL)) {
                if (User::where('email', $request->email_id)->first() != null) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Email already exists.',
                    ], 200);
                }
            }

            elseif (User::where('phone', '+'.$request->country_code_phone_code.'-'.$request->phone)->first() != null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Phone already exists.' 
                ], 200);
            }

        }

        // Assuming existing user_data in the session
        $existing_user_data = session('user_data', []);  // Fetch existing session data or an empty array if not set

        $new_user_data = [
            'company_name' => $request->company_name,
            'name' => $request->name,
            'email' => $request->email_id,
            'phone' => $request->country_code_phone_code.'-'.$request->phone,
            'phone_code_meta' => $request->phone_code_meta,
            'tel_number' => $request->tel_number,
            'whats_app_no' => $request->country_code_whats_app_no.'-'.$request->whats_app_no,
            'whats_app_no_meta' => $request->whats_app_no_meta,
            'post' => $request->post,
            'password'  => Hash::make($request->password),
        ];

        // Merge the existing user data with the new user data
        $combined_user_data = array_merge($existing_user_data, $new_user_data);
        
        // Store user data in session
        Session::put('user_data', $combined_user_data);

        // $otp = mt_rand(100000, 999999);
        $otp = '123456';
        Session::put('otp', $otp);

        $timestamp = date('Y-m-d H:i:s'); // Use PHP's native date() function for timestamp
        Session::put('otp_timestamp', $timestamp);

        Session::put('step', 3);

        return response()->json([
            'status' => 'success',
            'message' => 'OTP has been this Phone'. $request->country_code_phone_code.$request->phone,
        ], 200);
    }

    public function verify_otp($request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits:6',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(), // Return the first validation error
            ], 200);
        }
    
        $otp = Session::get('otp');
        $timestamp = Session::get('otp_timestamp');
    
        // Check if OTP and timestamp exist
        if (!$otp || !$timestamp) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP not found. Please request a new one.',
            ], 200);
        }
    
        // Check if OTP has expired (2 minutes)
        $timestamp = new \DateTime($timestamp);
        $current_time = new \DateTime();
        $interval = $current_time->getTimestamp() - $timestamp->getTimestamp();
    
        if ($interval > 120) { // 2 minutes = 120 seconds
            return response()->json([
                'status' => 'error',
                'message' => 'OTP has expired. Please request a new one.',
            ], 200);
        }
    
        if ($request->otp == $otp) {
            $data = Session::get('user_data');
    
            // Ensure $data exists
            if (!$data) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User data not found. Please start the process again.',
                ], 200);
            }


            if (!session()->has('temp_user_id')) {
                // Create user
                $user = User::create([
                    'name' => $data['name'],
                    'company_name' => $data['company_name'],
                    'email' => $data['email'],
                    'email_verified_at' => now(),
                    'phone' => $data['phone'],
                    'phone_code_meta' => $data['phone_code_meta'],
                    'password' => $data['password'], // Hash password before saving
                    'tel_number' => $data['tel_number'],
                    'post' => $data['post'],
                    'whats_app_no' => $data['whats_app_no'],
                    'whats_app_no_meta' => $data['whats_app_no_meta'],
                    'gst_no' => $data['gst_no'],
                    'step' => 4,
                ]);
                Session::put('temp_user_id', $user->id);
            } else {
                $user = User::where('id', session()->get('temp_user_id'))->update([
                    'name' => $data['name'],
                    'company_name' => $data['company_name'],
                    'email' => $data['email'],
                    'email_verified_at' => now(),
                    'phone' => $data['phone'],
                    'phone_code_meta' => $data['phone_code_meta'],
                    'password' => $data['password'], // Hash password before saving
                    'tel_number' => $data['tel_number'],
                    'post' => $data['post'],
                    'whats_app_no' => $data['whats_app_no'],
                    'whats_app_no_meta' => $data['whats_app_no_meta'],
                    'gst_no' => $data['gst_no'],
                    'step' => 4,
                ]);
            }

            Session::put('step', 4);

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

    public function store_personal_address($request)
    {

        $validator = Validator::make($request->all(), [

            'address' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'regex:/^\d{6}$/'], // Assuming Indian pincode format
            'city_id' => ['required', 'string', 'max:100'],
            'state_id' => ['required', 'string', 'max:100'],
            'country_id' => 'required', // ISO 3166-1 alpha-2

        ], [

            'address.required' => 'The Address 1 field is required.',
            'address.string' => 'The Address 1 must be a string.',
            'address.max' => 'The Address 1 may not be greater than 255 characters.',
        
            'pincode.required' => 'The Pincode field is required.',
            'pincode.regex' => 'The Pincode format is invalid.',
        
            'city_id.required' => 'The city field is required.',
            'city_id.string' => 'The city must be a string.',
            'city_id.max' => 'The city may not be greater than 100 characters.',
        
            'state_id.required' => 'The State field is required.',
            'state_id.string' => 'The State must be a string.',
            'state_id.max' => 'The State may not be greater than 100 characters.',
        
            'country_id.required' => 'The Country field is required.',

        ]);
        
        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            return response()->json([
                'status' => 'error',
                'message' => $errors
            ], 200);
        }

        $user = User::where('id', session()->get('temp_user_id'))->update([
            'address' => $request->address,
            'pincode' => $request->pincode,
            'city_id' => $request->city_id,
            'state_id' => $request->state_id,
            'country_id' => $request->country_id,
            'step' => 5,
        ]);
        $data = Session::get('user_data');


        $user_Address = Address::where('user_id', session()->get('temp_user_id'))->first();

        if ($user_Address) {
            $address = DB::table('addresses')
                ->where('user_id', session()->get('temp_user_id'))
                ->update([
                    'address' => $request->address,
                    'postal_code' => $request->pincode,
                    'city_id' => $request->city_id,
                    'state_id' => $request->state_id,
                    'country_id' => $request->country_id,
                    'phone' => '+' . $data['phone'],
                    'updated_at' => now(), // Update timestamp if your table uses it
                ]);
        } else {
            $address = DB::table('addresses')->insert([
                'address' => $request->address,
                'postal_code' => $request->pincode,
                'city_id' => $request->city_id,
                'state_id' => $request->state_id,
                'country_id' => $request->country_id,
                'phone' => '+' . $data['phone'],
                'user_id' => session()->get('temp_user_id'),
                'created_at' => now(), // Add timestamps if your table uses them
                'updated_at' => now(),
            ]);
        }

        Session::put('step', 5);

        return response()->json([
            'status' => 'success',
            'message' => 'Address Details Save Successfully',
        ], 200);
    }

    public function store_bank_details($request)
    {

        $validator = Validator::make($request->all(), [
            'bank_name' => ['required', 'string', 'max:255'],
            'account_no' => ['required', 'regex:/^\d+$/', 'max:20'], // Numeric only
            'branch_no' => ['required', 'string', 'max:50'],
            'branch_code' => ['required', 'string', 'max:50'],
            'ifsc_code' => ['required', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'], // IFSC code format
            'micr_code' => ['required', 'regex:/^\d{9}$/'], // MICR code format
            'customer_care_executive' => ['required', 'string', 'max:255'],
        ], [
            // Custom error messages
            'bank_name.required' => 'The bank name is required.',
            'bank_name.string' => 'The bank name must be a valid string.',
            'bank_name.max' => 'The bank name must not exceed 255 characters.',
            
            'account_no.required' => 'The account number is required.',
            'account_no.regex' => 'The account number must contain only numeric characters.',
            'account_no.max' => 'The account number must not exceed 20 digits.',
            
            'branch_no.required' => 'The branch number is required.',
            'branch_no.string' => 'The branch number must be a valid string.',
            'branch_no.max' => 'The branch number must not exceed 50 characters.',
            
            'branch_code.required' => 'The branch code is required.',
            'branch_code.string' => 'The branch code must be a valid string.',
            'branch_code.max' => 'The branch code must not exceed 50 characters.',
            
            'ifsc_code.required' => 'The IFSC Code is required.',
            'ifsc_code.regex' => 'The IFSC Code format is invalid. It should follow the format: 4 uppercase letters, a 0, followed by 6 alphanumeric characters.',
            
            'micr_code.required' => 'The MICR Code is required.',
            'micr_code.regex' => 'The MICR Code must be exactly 9 numeric digits.',
            
            'customer_care_executive.required' => 'The customer care executive name is required.',
            'customer_care_executive.string' => 'The customer care executive name must be a valid string.',
            'customer_care_executive.max' => 'The customer care executive name must not exceed 255 characters.',
        ]);
        
        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            return response()->json([
                'status' => 'error',
                'message' => $errors
            ], 200);
        }

        $user = User::where('id', session()->get('temp_user_id'))->update([
            'bank_name' => $request->bank_name,
            'account_no' => $request->account_no,
            'branch_no' => $request->branch_no,
            'branch_code' => $request->branch_code,
            'ifsc_code' => $request->ifsc_code,
            'micr_code' => $request->micr_code,
            'customer_care_executive' => $request->customer_care_executive,
            'step' => 6,
        ]);

        Session::put('step', 6);

        return response()->json([
            'status' => 'success',
            'message' => 'Bank Details Save Successfully',
        ], 200);
    }

    public function store_license_details($request)
    {

        $validator = Validator::make($request->all(), [

            'cc_no' => ['required', 'regex:/^[\d\s\-\+]+$/', 'min:5'],
            'd_l_no_1' => ['required', 'string', 'max:50'],
            'd_l_no_2' => ['required', 'string', 'max:50'],
            'd_l_no_3' => ['required', 'string', 'max:50'],

        ],[
            // Custom error messages
            'cc_no.required' => 'The CC number is required.',
            'cc_no.regex' => 'The CC number must only contain numbers, spaces, dashes, or plus signs.',
            'cc_no.min' => 'The CC number must be at least 5 characters long.',
            
            'd_l_no_1.required' => 'The first D.L.No is required.',
            'd_l_no_1.string' => 'The first D.L.No must be a valid string.',
            'd_l_no_1.max' => 'The first D.L.No must not exceed 50 characters.',
            
            'd_l_no_2.required' => 'The second D.L.No is required.',
            'd_l_no_2.string' => 'The second D.L.No must be a valid string.',
            'd_l_no_2.max' => 'The second D.L.No must not exceed 50 characters.',
            
            'd_l_no_3.required' => 'The third D.L.No is required.',
            'd_l_no_3.string' => 'The third D.L.No must be a valid string.',
            'd_l_no_3.max' => 'The third D.L.No must not exceed 50 characters.',
        ]);
        
        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            return response()->json([
                'status' => 'error',
                'message' => $errors
            ], 200);
        }

        $user = User::where('id', session()->get('temp_user_id'))->update([
            'cc_no' => $request->cc_no,
            'd_l_no_1' => $request->d_l_no_1,
            'd_l_no_2' => $request->d_l_no_2,
            'd_l_no_3' => $request->d_l_no_3,
            'step' => 7,
        ]);

        Session::put('step', 7);

        return response()->json([
            'status' => 'success',
            'message' => 'License Details Save Successfully',
        ], 200);
    }

    public function store_transport_details($request)
    {

        $validator = Validator::make($request->all(), [

            'd_l_exp_Date' => ['required', 'date'],
            'transport' => ['required', 'string', 'max:255'],
            'cargo' => ['required', 'string', 'max:255'],
            'booked_to' => ['required', 'string', 'max:255'],

        ],[
            // Custom error messages
            'd_l_exp_Date.required' => 'The D.L expiration date is required.',
            'd_l_exp_Date.date' => 'The D.L expiration date must be a valid date.',
            
            'transport.required' => 'The transport field is required.',
            'transport.string' => 'The transport field must be a valid string.',
            'transport.max' => 'The transport field must not exceed 255 characters.',
            
            'cargo.required' => 'The cargo field is required.',
            'cargo.string' => 'The cargo field must be a valid string.',
            'cargo.max' => 'The cargo field must not exceed 255 characters.',
            
            'booked_to.required' => 'The booked-to field is required.',
            'booked_to.string' => 'The booked-to field must be a valid string.',
            'booked_to.max' => 'The booked-to field must not exceed 255 characters.',
        ]);
        
        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            return response()->json([
                'status' => 'error',
                'message' => $errors
            ], 200);
        }

        $user = User::where('id', session()->get('temp_user_id'))->update([
            'd_l_exp_Date' => $request->d_l_exp_Date,
            'transport' => $request->transport,
            'cargo' => $request->cargo,
            'booked_to' => $request->booked_to,
            'step' => 8,
        ]);

        $user = User::find(session()->get('temp_user_id'));

        $this->guard()->login($user);

        try {
            EmailUtility::customer_registration_email('registration_email_to_customer', $user, null);
        } catch (\Exception $e) {}

        // customer Account Opening Email to Admin

        try {
            EmailUtility::customer_registration_email('customer_reg_email_to_admin', $user, null);
        } catch (\Exception $e) {}

        $this->guard()->logout();

        Session::put('step', 8);

        return response()->json([
            'status' => 'success',
            'message' => 'Transport Details Save Successfully',
        ], 200);
    }

}
