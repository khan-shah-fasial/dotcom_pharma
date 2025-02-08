@if (Session::has('step') && Session::get('step') == 2)

{{-- - //------------------------------ Registration 2 modal -----------------------// -- --}}

@php
    $session_data_user = session()->get('user_data') ?? []; 
@endphp

<div class="modal fade login_form_popup" id="reg_model_2" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content py-3">
            <div class="modal-header">
                <div class="heading">
                    <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                    <h5 class="modal-title" id="exampleModalLabel_phone">Personal Details</h5>
                </div>
                {{-- <div class="purple_btn_close">
                    <button type="button" onclick="close_Phone_modal();" class="close p-1 px-3"
                        data-dismiss="modal" aria-label="Close">
                    </button>
                </div> --}}
            </div>
            <form id="reg_model_form_2" action="{{ url(route('new.user.account.create', ['param' => 'personal-details'])) }}"
                method="post">
            
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">

                            <div class="form-group mb-1">
                                <label class="form-label" for="name">Company Name</label>
                                <input type="text" id="company_name" name="company_name"
                                    class="form-control form-control-lg" value="{{ $data['company_name'] ?? $session_data_user['company_name'] ?? '' }}" required placeholder="Enter Company Name"/>
                            </div>

                        </div>
                        <div class="col-md-6 mb-4">

                            <div class="form-group mb-1">
                                <label class="form-label" for="name">Concerned Person Name</label>
                                <input type="text" id="name" name="name"
                                    class="form-control form-control-lg" value="{{ $data['name'] ?? $session_data_user['name'] ?? '' }}" required placeholder="Enter Concerned Person Name"/>
                            </div>

                        </div>
                        <div class="col-md-6 mb-4">

                            <div class="form-group mb-1">
                                <label class="form-label" for="email_id">Email</label>
                                <input type="email" id="email_id" name="email_id"
                                    class="form-control form-control-lg" value="{{ $data['email'] ?? $session_data_user['email'] ?? '' }}" required placeholder="Enter Email"/>
                            </div>

                        </div>
                        <div class="col-md-6 mb-4">

                            @php
                                if(!empty($data['phone'])){
                                    $Phone_parts = explode('-', $data['phone']);
                                    $Phone_parts_number = $Phone_parts[1] ?? ''; 
                                } 
                            @endphp

                            <div class="form-group phone-form-group mb-1">
                                <label for="phone-code" class="fs-12 fw-700 text-soft-dark">{{  translate('Phone No') }}</label>
                                <input type="tel" id="phone_code" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }} rounded-0" placeholder="Enter Phone No" name="phone" autocomplete="off" 
                                value="{{ $Phone_parts_number ?? '' }}" required>
                            </div>

                            <input type="hidden" name="country_code_phone_code" value="">
                            <input type="hidden" name="phone_code_meta" value="">

                        </div>
                        <div class="col-md-6 mb-4">

                            <div class="form-group phone-form-group mb-1">
                                <label for="phone" class="fs-12 fw-700 text-soft-dark">{{  translate('Telephone No') }}</label>
                                <input type="tel" id="tel_number" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }} rounded-0" placeholder="Enter Telephone No" name="tel_number" autocomplete="off" value="{{ $data['tel_number'] ?? '' }}" required>
                            </div>

                            {{-- <input type="hidden" name="country_code_phone_no_2" value=""> --}}

                        </div>
                        <div class="col-md-6 mb-4">

                            @php
                                if(!empty($data['whats_app_no'])){
                                    $whats_app_no_parts = explode('-', $data['whats_app_no']);
                                    $whats_app_no_parts_number = $whats_app_no_parts[1] ?? ''; 
                                }
                            @endphp

                            <div class="form-group phone-form-group mb-1">
                                <label for="phone" class="fs-12 fw-700 text-soft-dark">{{  translate('Whatsapp No') }}</label>
                                <input type="tel" id="whats_app_no" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }} rounded-0" placeholder="Enter Whatsapp No" name="whats_app_no" autocomplete="off" value="{{ $whats_app_no_parts_number ?? '' }}" required>
                            </div>

                            <input type="hidden" name="country_code_whats_app_no" value="">
                            <input type="hidden" name="whats_app_no_meta" value="">

                        </div>
                       

                        <div class="col-md-6 mb-4">

                            <div class="form-group mb-1">
                                <label class="form-label" for="password">Password</label>
                                <input type="password" id="password" name="password"
                                    class="form-control form-control-lg" required placeholder="Enter Password"/>
                            </div>

                        </div>
                        <div class="col-md-6 mb-4">

                            <div class="form-group mb-1">
                                <label class="form-label" for="password_confirmation">Conform Password</label>
                                <input type="password" id="password_confirmation"
                                    name="password_confirmation" class="form-control form-control-lg"
                                    required placeholder="Enter Conform Password"/>
                            </div>

                        </div>

                         <div class="col-md-12 mb-4">

                            <div class="form-group mb-1">
                                <label class="form-label" for="post">Post</label>
                                <input type="text" id="post" name="post" value="{{ $data['post'] ?? $session_data_user['post'] ?? '' }}"
                                    class="form-control form-control-lg" placeholder="Enter Post"/>
                            </div>

                        </div>


                    </div>
                </div>
                <div class="modal-footer">
                    <div class="blue_btn black_buttons">
                        <button type="button" onclick="back_to_prev_reg();" class=""><img src="{{ static_asset('assets/img/arrow_right.svg') }}" /> Previous</button>
                    </div>
                    <div class="purple_btn">
                        <button type="submit" class="btn btn-primary">Next <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- - //------------------------------  Registration 2 modal -----------------------// -- --}}

@endif

@if (Session::has('step') && Session::get('step') == 3)

{{-- - //------------------------------ Registration 3 modal -----------------------// -- --}}

    <div class="modal fade login_form_popup" id="reg_model_3" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content py-3">
                <div class="modal-header">
                    <div class="heading">
                        <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                        <h5 class="modal-title" id="exampleModalLabel_phone">Verify Phone Number</h5>
                    </div>
                    {{-- <div class="purple_btn_close">
                        <button type="button" onclick="close_Phone_modal();" class="close p-1 px-3"
                            data-dismiss="modal" aria-label="Close"> v 
                        </button>
                    </div> --}}
                </div>
                <form id="reg_model_form_3" action="{{ url(route('new.user.account.create', ['param' => 'verify-phone'])) }}"
                    method="post">
                    @csrf

                    <div class="modal-body">
                        <div class="form-group">
                            <label for="recipient-name" class="col-form-label form-label">Verification Code:</label>
                            <input type="number" class="form-control form-control-lg" id="recipient-name" name="otp"
                                pattern="[0-9]+" minlength="6" maxlength="6" placeholder="Please Enter Code" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="blue_btn black_buttons">
                            <button type="button" onclick="back_to_prev_reg();" class=""><img src="{{ static_asset('assets/img/arrow_right.svg') }}" /> Previous</button>
                        </div>

                        <div class="display_flexx">
                             <div class="resend_otp">
                            <a class="ms-4" class="btn btn-primary" onclick="resendOTPButton_Phone();">Resend OTP</a>
                        </div>
                             <div class="purple_btn">
                            <button type="submit" class="btn btn-primary">Verify <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                        </div>
                       
                        </div>
                       
                    </div>
                </form>
            </div>
        </div>
    </div>

{{-- - //------------------------------  Registration 3 modal -----------------------// -- --}}

@endif

@if (Session::has('step') && Session::get('step') == 4)

{{-- - //------------------------------ Registration 4 modal -----------------------// -- --}}

<div class="modal fade login_form_popup" id="reg_model_4" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content py-3">
            <div class="modal-header">
                <div class="heading">
                    <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                    <h5 class="modal-title" id="exampleModalLabel_phone">Address</h5>
                </div>
                {{-- <div class="purple_btn_close">
                    <button type="button" onclick="close_Phone_modal();" class="close p-1 px-3"
                        data-dismiss="modal" aria-label="Close">
                    </button>
                </div> --}}
            </div>
            <form id="reg_model_form_4" action="{{ url(route('new.user.account.create', ['param' => 'personal-address'])) }}"
                method="post">
            
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-12">

                            <div class="form-group">
                                <label class="form-label" for="address_1">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter Address" required >{{ $data['address'] ?? '' }}</textarea>
                            </div>

                        </div>
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="country_code">Country Code</label>
                                {{-- <input type="text" id="country_code" name="country_code"
                                    class="form-control form-control-lg" value="{{ $data['country__code'] ?? '' }}" required /> --}}


                                    <select class="form-control aiz-selectpicker rounded-0" data-live-search="true" data-placeholder="{{ translate('Select your country') }}" name="country_id" placeholder="Select Country" required>
                                        <option value="">{{ translate('Select your country') }}</option>
                                        @foreach (get_active_countries() as $key => $country)
                                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                                        @endforeach
                                    </select>

                            </div>

                        </div>
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="state">State</label>
                                {{-- <input type="text" id="state" name="state"
                                    class="form-control form-control-lg" value="{{ $data['state'] ?? '' }}" required /> --}}

                                    <select class="form-control aiz-selectpicker rounded-0" data-live-search="true" name="state_id" required placeholder="Select State">

                                    </select>
                            </div>

                        </div>
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="district">City</label>
                                {{-- <input type="text" id="district" name="district"
                                    class="form-control form-control-lg" value="{{ $data['district'] ?? '' }}" required /> --}}

                                <select class="form-control aiz-selectpicker rounded-0" data-live-search="true" name="city_id" required placeholder="Select City">

                                </select>
                            </div>

                        </div>
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="pincode">Pincode</label>
                                <input type="text" id="pincode" name="pincode"
                                    class="form-control form-control-lg" value="{{ $data['pincode'] ?? '' }}" placeholder="Enter Pincode" required />
                            </div>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="blue_btn black_buttons">
                        <button type="button" onclick="back_to_prev_reg();" class=""> <img src="{{ static_asset('assets/img/arrow_right.svg') }}" /> Previous</button>
                    </div>
                    <div class="purple_btn">
                        <button type="submit" class="btn btn-primary">Next <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- - //------------------------------  Registration 4 modal -----------------------// -- --}}

@endif

@if (Session::has('step') && Session::get('step') == 5)

{{-- - //------------------------------ Registration 5 modal -----------------------// -- --}}

<div class="modal fade login_form_popup" id="reg_model_5" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content py-3">
            <div class="modal-header">
                <div class="heading">
                     <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                    <h5 class="modal-title" id="exampleModalLabel_phone">Bank Details</h5>
                </div>
                {{-- <div class="purple_btn_close">
                    <button type="button" onclick="close_Phone_modal();" class="close p-1 px-3"
                        data-dismiss="modal" aria-label="Close">
                    </button>
                </div> --}}
            </div>
            <form id="reg_model_form_5" action="{{ url(route('new.user.account.create', ['param' => 'bank-details'])) }}"
                method="post">
            
                <div class="modal-body">
                    <div class="row">

                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="bank_name">Bank Name</label>
                                <input type="text" id="bank_name" name="bank_name"
                                    class="form-control form-control-lg" value="{{ $data['bank_name'] ?? '' }}" required placeholder="Enter Bank Name" />
                            </div>

                        </div>
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="account_no">Account No</label>
                                <input type="text" id="account_no" name="account_no"
                                    class="form-control form-control-lg" value="{{ $data['account_no'] ?? '' }}" required placeholder="Enter Account No"/>
                            </div>

                        </div>
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="branch_no">Branch No</label>
                                <input type="text" id="branch_no" name="branch_no"
                                    class="form-control form-control-lg" value="{{ $data['branch_no'] ?? '' }}" required placeholder="Enter Branch No"/>
                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="branch_code">Branch Code</label>
                                <input type="text" id="branch_code" name="branch_code"
                                    class="form-control form-control-lg" value="{{ $data['branch_code'] ?? '' }}" required placeholder="Enter Branch Code"/>
                            </div>

                        </div>
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="ifsc_code">IFSC Code</label>
                                <input type="text" id="ifsc_code" name="ifsc_code"
                                    class="form-control form-control-lg" value="{{ $data['ifsc_code'] ?? '' }}" required placeholder="Enter IFSC Code"/>
                            </div>

                        </div>
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="micr_code">MICR Code</label>
                                <input type="text" id="micr_code" name="micr_code"
                                    class="form-control form-control-lg" value="{{ $data['micr_code'] ?? '' }}" required placeholder="Enter MICR Code"/>
                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="customer_care_executive">Customer Care
                                    Executive</label>
                                <input type="text" id="customer_care_executive"
                                    name="customer_care_executive" class="form-control form-control-lg" value="{{ $data['customer_care_executive'] ?? '' }}" required placeholder="Enter Customer Care Executive"/>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="blue_btn black_buttons">
                        <button type="button" onclick="back_to_prev_reg();" class=""><img src="{{ static_asset('assets/img/arrow_right.svg') }}" /> Previous</button>
                    </div>
                    <div class="purple_btn">
                        <button type="submit" class="btn btn-primary"> Next <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- - //------------------------------  Registration 5 modal -----------------------// -- --}}

@endif

@if (Session::has('step') && Session::get('step') == 6)

{{-- - //------------------------------ Registration 6 modal -----------------------// -- --}}

<div class="modal fade login_form_popup" id="reg_model_6" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content py-3">
            <div class="modal-header">
                <div class="heading">
                    <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                    <h5 class="modal-title" id="exampleModalLabel_phone">License Details</h5>
                </div>
                {{-- <div class="purple_btn_close">
                    <button type="button" onclick="close_Phone_modal();" class="close p-1 px-3"
                        data-dismiss="modal" aria-label="Close">
                    </button>
                </div> --}}
            </div>
            <form id="reg_model_form_6" action="{{ url(route('new.user.account.create', ['param' => 'license-details'])) }}"
                method="post">
            
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="cc_no">CC NO</label>
                                <input type="text" id="cc_no" name="cc_no"
                                    class="form-control form-control-lg" value="{{ $data['cc_no'] ?? '' }}" required placeholder="Enter CC.No"/>
                            </div>

                        </div>
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="d_l_no_1">D.L No 1 (Drug Licence)</label>
                                <input type="text" id="d_l_no_1" name="d_l_no_1"
                                    class="form-control form-control-lg" value="{{ $data['d_l_no_1'] ?? '' }}" required placeholder="Enter D.L.No.1"/>
                            </div>

                        </div>
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="d_l_no_2">D.L No 2</label>
                                <input type="text" id="d_l_no_2" name="d_l_no_2"
                                    class="form-control form-control-lg" value="{{ $data['d_l_no_2'] ?? '' }}" required placeholder="Enter D.L.No.2"/>
                            </div>

                        </div>
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="d_l_no_3">D.L No 3</label>
                                <input type="text" id="d_l_no_3" name="d_l_no_3"
                                    class="form-control form-control-lg" value="{{ $data['d_l_no_3'] ?? '' }}" required placeholder="Enter D.L.No.3"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="blue_btn black_buttons">
                        <button type="button" onclick="back_to_prev_reg();" class=""><img src="{{ static_asset('assets/img/arrow_right.svg') }}" /> Previous</button>
                    </div>
                    <div class="purple_btn">
                        <button type="submit" class="btn btn-primary">Next <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- - //------------------------------  Registration 6 modal -----------------------// -- --}}

@endif

@if (Session::has('step') && Session::get('step') == 7)

{{-- - //------------------------------ Registration 7 modal -----------------------// -- --}}

<div class="modal fade login_form_popup" id="reg_model_7" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content py-3">
            <div class="modal-header">
                <div class="heading">
                    <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                    <h5 class="modal-title" id="exampleModalLabel_phone">Transport Details</h5>
                </div>
                {{-- <div class="purple_btn_close">
                    <button type="button" onclick="close_Phone_modal();" class="close p-1 px-3"
                        data-dismiss="modal" aria-label="Close">
                    </button>
                </div> --}}
            </div>
            <form id="reg_model_form_7" action="{{ url(route('new.user.account.create', ['param' => 'transport-details'])) }}"
                method="post">
            
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="d_l_exp_Date">D.L Expiry Date</label>
                                <input type="date" id="d_l_exp_Date" name="d_l_exp_Date"
                                    class="form-control form-control-lg" value="{{ $data['d_l_exp_Date'] ?? '' }}" required />
                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="transport">Transport</label>
                                <input type="text" id="transport" name="transport"
                                    class="form-control form-control-lg"  value="{{ $data['transport'] ?? '' }}" required/>
                            </div>

                        </div>
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="cargo">Cargo</label>
                                <input type="text" id="cargo" name="cargo"
                                    class="form-control form-control-lg" value="{{ $data['cargo'] ?? '' }}" required />
                            </div>

                        </div>
                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="booked_to">Booked To</label>
                                <input type="text" id="booked_to" name="booked_to"
                                    class="form-control form-control-lg" value="{{ $data['booked_to'] ?? '' }}" required />
                            </div>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="blue_btn black_buttons">
                        <button type="button" onclick="back_to_prev_reg();" class=""><img src="{{ static_asset('assets/img/arrow_right.svg') }}" /> Previous</button>
                    </div>
                    <div class="purple_btn">
                        <button type="submit" class="btn btn-primary">Sbumit <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- - //------------------------------  Registration 7 modal -----------------------// -- --}}

@endif

@if (Session::has('step') && Session::get('step') == 8)

{{-- - //------------------------------ Registration 8 modal -----------------------// -- --}}

@php
    session()->forget('temp_user_id');
    session()->forget('otp');
    session()->forget('user_data');
    session()->forget('otp_timestamp');
    Session()->forget('step');
@endphp

<div class="modal fade" id="reg_model_8" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content py-3">
            <div class="modal-header">
                <div class="heading">
                    <h5 class="modal-title" id="exampleModalLabel_phone">Under Review</h5>
                </div>
                <div class="purple_btn_close">
                    <button type="button" onclick="close_and_reload();" class="close p-1 px-3"
                        data-dismiss="modal" aria-label="Close">
                    </button>
                </div>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <h3 class="col-form-label form-label">Your account will be reviewed by an administrator and activated within 48 hours</h3>
                </div>
            </div>
            <div class="modal-footer">
                <div class="blue_btn">
                    <button type="button" onclick="close_and_reload_reg();" class="btn btn-secondary"
                        data-dismiss="modal">OK</button>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- - //------------------------------  Registration 7 modal -----------------------// -- --}}

@endif