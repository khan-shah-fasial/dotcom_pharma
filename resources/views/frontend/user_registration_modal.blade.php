@if (Session::has('step') && Session::get('step') == 1)
{{-- - //------------------------------ Registration 1 modal -----------------------// -- --}}

<div class="modal fade login_form_popup" id="reg_model_1" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content py-3">
            <div class="modal-header">
                <div class="heading">
                    <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                    <h5 class="modal-title" id="exampleModalLabel_phone">Register As</h5>
                    <hr>
                </div>
            </div>
            @php
                $formAction = route('new.user.account.create', ['param' => 'registration-locality']);
            @endphp
            <form id="reg_model_form_1" action="{{ url($formAction) }}" method="POST">
                <div class="modal-body row">

                        <input type="hidden" name="type" id="reg_type" value="">
                        <div class="btn-group w-100 d-flex" role="group" aria-label="Type selection">

                            <label class="btn btn-success w-100 mx-3" for="domestic">
                            <input type="radio" class="btn-check" name="type_option" id="domestic" value="domestic" autocomplete="off">
                            Domestic</label>
                    
                            <label class="btn btn-primary w-100 mx-3" for="international">
                            <input type="radio" class="btn-check" name="type_option" id="international" value="international" autocomplete="off">
                            International</label>

                        </div>

                </div>
                <div class="modal-footer">
                    <div class="purple_btn d-none">
                        <button type="submit" id="reg_model_form_1_submit" class="animate_button black1_buttons">Next <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                    </div>
                </div>

                <script>
                    document.querySelectorAll('input[name="type_option"]').forEach(function(radio) {
                        radio.addEventListener('change', function () {
                            document.getElementById('reg_type').value = this.value;
                            document.getElementById('reg_model_form_1_submit').click();
                        });
                    });
                </script>
            </form>

        </div>
    </div>
</div>

{{-- - //------------------------------ Registration 1 modal -----------------------// -- --}}
@endif

@if (Session::has('step') && Session::get('step') == 2)

{{-- - //------------------------------ Registration 2 modal -----------------------// -- --}}

@php
    $session_data_user = session()->get('user_data_business') ?? []; 
@endphp

<div class="modal fade login_form_popup" id="reg_model_2" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content py-3">
            <div class="modal-header">
                <div class="heading">
                    <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                    <h5 class="modal-title" id="exampleModalLabel_phone">Registration Details</h5>
                </div>
            </div>
            <form id="reg_model_form_2" action="{{ url(route('new.user.account.create', ['param' => 'registration-bussiness-details'])) }}"
                method="post">
            
                <div class="modal-body">

                    <div class="btn-group d-flex" role="group" aria-label="Type selection">

                        <label class="btn btn-success mx-3" for="domestic">
                        <input type="radio" class="btn-check" name="type_option" id="domestic" value="domestic" 
                        @if(($session_data_user['type_option'] ?? '') === 'domestic' || Session::get('reg_locality') === 'domestic')
                            checked
                        @endif
                        onclick="toggleLocalityFields();">
                        Domestic</label>
                
                        <label class="btn btn-primary mx-3" for="international">
                        <input type="radio" class="btn-check" name="type_option" id="international" value="international"
                        @if(($session_data_user['type_option'] ?? '') === 'international' || Session::get('reg_locality') === 'international')
                            checked
                        @endif
                        onclick="toggleLocalityFields();">
                        International</label>

                    </div>

                    <div class="row">

                        <div class="col-md-12 mb-3 text-center">
                            <h3>Business Details</h3>
                        </div>

                        <div class="locality-base-domestic col-md-12">
                            <div class="row">

                                <div class="col-md-5 mb-md-4 mb-3">
                                    <div class="form-group">
                                        <label for="gst_no" class="col-form-label form-label">GST No: *</label>
                                        <input type="text" class="form-control form-control-lg" id="gst_no" name="gst_no"
                                        minlength="10" maxlength="15" placeholder="Please Enter GST No Ex: 22AAAAA0000A1Z5 " value="{{ $data['gst_no'] ?? $session_data_user['gst_no'] ?? '' }}" required>
                                    </div>
                                </div>
    
                                <div class="col-md-5 mb-md-4 mb-3">
                                    <div class="form-group">
                                        <label for="gst_no" class="col-form-label form-label">GST No Upload : *</label>
                                        <input type="file" class="form-control form-control-lg" id="gst_no_file" name="gst_no_file"
                                        accept=".jpg, .jpeg, .webp, .png, .pdf"
                                        required>
                                    </div>
                                </div>

                            </div> 
                        </div>

                        <div class="locality-base-international col-md-12 d-none">
                            <div class="row">
                                <div class="col-md-5 mb-md-4 mb-3">
                                    <div class="form-group">
                                        <label for="iec_no" class="col-form-label form-label">IEC.No: *</label>
                                        <input type="text" class="form-control form-control-lg" id="iec_no" name="iec_no"
                                        minlength="10" maxlength="10" placeholder="Please Enter IEC.No Ex: 1234567890" value="{{ $data['iec_no'] ?? $session_data_user['iec_no'] ?? '' }}" >
                                    </div>
                                </div>

                                <div class="col-md-5 mb-md-4 mb-3">
                                    <div class="form-group">
                                        <label for="gst_no" class="col-form-label form-label">IEC.No Upload : *</label>
                                        <input type="file" class="form-control form-control-lg" id="iec_no_file" name="iec_no_file"
                                        accept=".jpg, .jpeg, .webp, .png, .pdf"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="registration_date">Registration Date *</label>
                                <input type="date" id="registration_date" name="registration_date"
                                    class="form-control form-control-lg" value="{{ old('registration_date', $data['registration_date'] ?? $session_data_user['registration_date'] ?? '') }}" required />
                            </div>

                        </div>
                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="const_of_business">Constitution of Business *</label>
                                <input type="text" id="const_of_business" name="const_of_business"
                                    class="form-control form-control-lg" value="{{ $data['const_of_business'] ?? $session_data_user['const_of_business'] ?? '' }}" required placeholder="Enter Constitution of Business Ex: ABCD"/>
                            </div>

                        </div>

                        <div class="locality-base-domestic"> 
                            <div class="col-md-12 mb-md-4 mb-3">

                                <div class="form-group mb-1">
                                    <label class="form-label" for="gstin_current_status">GSTIN Status / Current Status *</label>
                                    <input type="text" id="gstin_current_status" name="gstin_current_status"
                                        class="form-control form-control-lg" value="{{ $data['gstin_current_status'] ?? $session_data_user['gstin_current_status'] ?? '' }}" required placeholder="Enter GSTIN Status / Current Status Ex: Active"/>
                                </div>

                            </div>
                        </div>

                        <div class="locality-base-international d-none"> 
                            <div class="col-md-12 mb-md-4 mb-3">

                                <div class="form-group mb-1">
                                    <label class="form-label" for="uin_current_status">UIN Status / Current Status *</label>
                                    <input type="text" id="uin_current_status" name="uin_current_status"
                                        class="form-control form-control-lg" value="{{ $data['uin_current_status'] ?? $session_data_user['uin_current_status'] ?? '' }}" placeholder="Enter UIN Status / Current Status Ex: Active"/>
                                </div>

                            </div>
                        </div>


                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="name">Concerned Person Name *</label>
                                <input type="text" id="con_person_name" name="con_person_name"
                                    class="form-control form-control-lg" value="{{ $data['con_person_name'] ?? $session_data_user['con_person_name'] ?? '' }}" required placeholder="Enter Concerned Person Name Ex: ABCD"/>
                            </div>

                        </div>
                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="name">Company Name *</label>
                                <input type="text" id="company_name" name="company_name"
                                    class="form-control form-control-lg" value="{{ $data['company_name'] ?? $session_data_user['company_name'] ?? '' }}" required placeholder="Enter Company Name"/>
                            </div>

                        </div>


                        <div class="col-md-12 mb-3 text-center">
                            <h3>Address</h3>
                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="street_add_first_business">Street Address 1 *</label>
                                <input type="text" id="street_add_first_business" name="street_add_first_business"
                                    class="form-control form-control-lg" value="{{ $data['street_add_first_business'] ?? $session_data_user['street_add_first_business'] ?? '' }}" required placeholder="Enter Street Address"/>
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="street_add_sec_business">Street Address 2 *</label>
                                <input type="text" id="street_add_sec_business" name="street_add_sec_business"
                                    class="form-control form-control-lg" value="{{ $data['street_add_sec_business'] ?? $session_data_user['street_add_sec_business'] ?? '' }}" required placeholder="Enter Street Address 2"/>
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="locality_land_mark_business">Locality/Suburb/Land Mark *</label>
                                <input type="text" id="locality_land_mark_business" name="locality_land_mark_business"
                                    class="form-control form-control-lg" value="{{ $data['locality_land_mark_business'] ?? $session_data_user['locality_land_mark_business'] ?? '' }}" required placeholder="Enter Locality/Suburb/Land Mark"/>
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="village_business">Village *</label>
                                <input type="text" id="village_business" name="village_business"
                                    class="form-control form-control-lg" value="{{ $data['village_business'] ?? $session_data_user['village_business'] ?? '' }}" required placeholder="Enter Village"/>
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="post_business">Post *</label>
                                <input type="text" id="post_business" name="post_business" value="{{ $data['post_business'] ?? $session_data_user['post_business'] ?? '' }}"
                                    class="form-control form-control-lg" placeholder="Enter Post" required/>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="city_id_business">City / Town *</label>
                                {{-- <input type="text" id="district" name="district"
                                    class="form-control form-control-lg" value="{{ $data['district'] ?? '' }}" required /> --}}

                                <select class="form-control aiz-selectpicker rounded-0" data-live-search="true" name="city_id" required placeholder="Select City">

                                </select>
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="district_business">District *</label>
                                <input type="text" id="district_business" name="district_business" value="{{ $data['district_business'] ?? $session_data_user['district_business'] ?? '' }}"
                                    class="form-control form-control-lg" placeholder="Enter District" required/>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="state_business">State/Province/Region *</label>
                                {{-- <input type="text" id="state" name="state"
                                    class="form-control form-control-lg" value="{{ $data['state'] ?? '' }}" required /> --}}

                                    <select class="form-control aiz-selectpicker rounded-0" data-live-search="true" name="state_id" required placeholder="Select State">

                                    </select>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="pincode">Pincode Or Postal Code *</label>
                                <input type="text" id="pincode_business" name="pincode_business"
                                    class="form-control form-control-lg" value="{{ $session_data_user['pincode_business'] ?? '' }}" placeholder="Enter Pincode Or Postal Code" required />
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="country_business">Country *</label>
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

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="country_code_business">Country Code *</label>
                                <input type="text" id="country_code_business" name="country_code_business"
                                    class="form-control form-control-lg" value="{{ $session_data_user['country_code_business'] ?? '' }}" placeholder="Enter Country Code Ex: 91" required />
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            @php
                                if(!empty($session_data_user['phone_business'])){
                                    $Phone_parts = explode('-', $session_data_user['phone_business']);
                                    $Phone_parts_number = $Phone_parts[1] ?? ''; 
                                } 
                            @endphp

                            <div class="form-group phone-form-group mb-1">
                                <label for="phone-code" class="fs-12 fw-700 text-soft-dark">Primary Mobile (this number is user for your Login details) *</label>
                                <input type="tel" id="phone_code" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }} rounded-0" placeholder="Enter Phone No" name="phone" autocomplete="off" 
                                value="{{ $Phone_parts_number ?? '' }}" required>
                            </div>

                            <input type="hidden" name="country_code_phone_code" value="">
                            <input type="hidden" name="phone_code_meta" value="">

                        </div>
                        <div class="col-md-3 mb-md-4 mb-3">

                            @php
                                if(!empty($session_data_user['whats_app_no_business'])){
                                    $whats_app_no_parts = explode('-', $session_data_user['whats_app_no_business']);
                                    $whats_app_no_parts_number = $whats_app_no_parts[1] ?? ''; 
                                }
                            @endphp

                            <div class="form-group phone-form-group mb-1">
                                <label for="phone" class="fs-12 fw-700 text-soft-dark">Primary Whatapp No *</label>
                                <input type="tel" id="whats_app_no" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }} rounded-0" placeholder="Enter Whatsapp No" name="whats_app_no" autocomplete="off" value="{{ $whats_app_no_parts_number ?? '' }}" required>
                            </div>

                            <input type="hidden" name="country_code_whats_app_no" value="">
                            <input type="hidden" name="whats_app_no_meta" value="">

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            @php
                                if(!empty($session_data_user['alternate_mob_no_business'])){
                                    $alternate_mob_no_business_parts = explode('-', $session_data_user['alternate_mob_no_business']);
                                    $alternate_mob_no_business_number = $alternate_mob_no_business_parts[1] ?? ''; 
                                }
                            @endphp

                            <div class="form-group phone-form-group mb-1">
                                <label for="phone" class="fs-12 fw-700 text-soft-dark">Alternate Mobile (contact Person)</label>
                                <input type="tel" id="alternate_mob_no_business" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }} rounded-0" placeholder="Enter Alternate Mobile No" name="alternate_mob_no_business" autocomplete="off" value="{{ $alternate_mob_no_business_number ?? '' }}" >
                            </div>

                            <input type="hidden" name="country_code_alternate_mob_no_business" value="">
                            <input type="hidden" name="alternate_mob_no_business_meta" value="">

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            @php
                                if(!empty($session_data_user['alternate_whats_app_no_business'])){
                                    $alternate_whats_app_no_parts = explode('-', $session_data_user['alternate_whats_app_no_business']);
                                    $alternate_whats_app_no_parts_number = $alternate_whats_app_no_parts[1] ?? ''; 
                                }
                            @endphp

                            <div class="form-group phone-form-group mb-1">
                                <label for="phone" class="fs-12 fw-700 text-soft-dark">Alternate Whatapp No</label>
                                <input type="tel" id="alternate_whats_app_no_business" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }} rounded-0" placeholder="Enter Whatsapp No" name="alternate_whats_app_no_business" autocomplete="off" value="{{ $alternate_whats_app_no_parts_number ?? '' }}" >
                            </div>

                            <input type="hidden" name="country_code_alternate_whats_app_no_business" value="">
                            <input type="hidden" name="alternate_whats_app_no_business_meta" value="">

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="prim_email_business">Primary E-mail * (this eamil is user for login details)</label>
                                <input type="email" id="prim_email_business" name="prim_email_business"
                                    class="form-control form-control-lg" value="{{ $data['prim_email_business'] ?? $session_data_user['prim_email_business'] ?? '' }}" required placeholder="Enter Primary E-mail"/>
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="alt_email_business">Alternate E-mail</label>
                                <input type="email" id="alt_email_business" name="alt_email_business"
                                    class="form-control form-control-lg" value="{{ $data['alt_email_business'] ?? $session_data_user['alt_email_business'] ?? '' }}" placeholder="Enter Alternate E-mail"/>
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="website_business">Website</label>
                                <input type="url" id="website_business" name="website_business"
                                    class="form-control form-control-lg" value="{{ $data['website_business'] ?? $session_data_user['website_business'] ?? '' }}"  placeholder="Enter Website URL"/>
                            </div>

                        </div>

                        <div class="col-md-12 mb-3 text-center">
                            <h3>Bank Details</h3>
                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="bank_name_business">Bank Name *</label>
                                <input type="text" id="bank_name_business" name="bank_name_business"
                                    class="form-control form-control-lg" value="{{ $session_data_user['bank_name_business'] ?? '' }}" required placeholder="Enter Bank Name" />
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="account_no_business">Account No *</label>
                                <input type="text" id="account_no_business" name="account_no_business"
                                    class="form-control form-control-lg" value="{{ $session_data_user['account_no_business'] ?? '' }}" required placeholder="Enter Account No EX: 123456..."/>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="account_name_business">Account Name *</label>
                                <input type="text" id="account_name_business" name="account_name_business"
                                    class="form-control form-control-lg" value="{{ $session_data_user['account_name_business'] ?? '' }}" required placeholder="Enter Account Name"/>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="branch_code_business">Branch Code *</label>
                                <input type="text" id="branch_code_business" name="branch_code_business"
                                    class="form-control form-control-lg" value="{{ $session_data_user['branch_code_business'] ?? '' }}" required placeholder="Enter Branch Code Ex: 99922"/>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="branch_name_business">Branch Name *</label>
                                <input type="text" id="branch_name_business" name="branch_name_business"
                                    class="form-control form-control-lg" value="{{ $session_data_user['branch_name_business'] ?? '' }}" required placeholder="Enter Branch Name"/>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="branch_address_business">Branch Address *</label>
                                <input type="text" id="branch_address_business" name="branch_address_business"
                                    class="form-control form-control-lg" value="{{ $session_data_user['branch_address_business'] ?? '' }}" required placeholder="Enter Branch Address"/>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="ifsc_code_business">IFSC Code *</label>
                                <input type="text" id="ifsc_code_business" name="ifsc_code_business"
                                    class="form-control form-control-lg" value="{{ $session_data_user['ifsc_code_business'] ?? '' }}" required placeholder="Enter IFSC Code Ex: AAAA0000000"/>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="micr_code_business">MICR Code *</label>
                                <input type="text" id="micr_code_business" name="micr_code_business"
                                    class="form-control form-control-lg" value="{{ $session_data_user['micr_code_business'] ?? '' }}" required placeholder="Enter MICR Code Ex: 600002025"/>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="ad_code_business">AD code *</label>
                                <input type="text" id="ad_code_business" name="ad_code_business"
                                    class="form-control form-control-lg" value="{{ $session_data_user['ad_code_business'] ?? '' }}" required placeholder="Enter AD Code"/>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="blue_btn black_buttons">
                        <button type="button" onclick="back_to_prev_reg();" class=""><img src="{{ static_asset('assets/img/arrow_right.svg') }}" /> Previous</button>
                    </div>
                    <div class="purple_btn">
                        <button type="submit" class="animate_button black1_buttons">Next <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- - //------------------------------  Registration 2 modal -----------------------// -- --}}

@endif


{{-- @if (Session::has('step') && Session::get('step') == 3)



    <div class="modal fade login_form_popup" id="reg_model_3" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content py-3">
                <div class="modal-header">
                    <div class="heading">
                        <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                        <h5 class="modal-title" id="exampleModalLabel_phone">Verify Phone Number</h5>
                    </div>

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
                            <button type="submit" class="animate_button black1_buttons">Verify <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                        </div>
                       
                        </div>
                       
                    </div>
                </form>
            </div>
        </div>
    </div>



@endif --}}




{{-- - //------------------------------ Registration 1 modal -----------------------// -- --}}

@if (Session::has('step') && Session::get('step') == 3)

{{-- - //------------------------------ Registration 3 modal -----------------------// -- --}}

@php
    $session_data_user = session()->get('user_data_personal') ?? [];
    $session_data_bussines = session()->get('user_data_business') ?? []; 
@endphp

<div class="modal fade login_form_popup" id="reg_model_3" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content py-3">
            <div class="modal-header">
                <div class="heading">
                    <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                    <h5 class="modal-title" id="exampleModalLabel_phone">Registration Details</h5>
                </div>
            </div>
            <form id="reg_model_form_3" action="{{ url(route('new.user.account.create', ['param' => 'registration-personal-details'])) }}"
                method="post">
            
                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-12 mb-3 text-center">
                            <h3>Personal Details</h3>
                        </div>

                        @if($session_data_bussines['type_option'] == "domestic")
                            <div class="locality-base-domestic col-md-12">
                                <div class="row">

                                    <div class="col-md-5 mb-md-4 mb-3">
                                        <div class="form-group">
                                            <label for="gst_no" class="col-form-label form-label">Aadhaar.No: *</label>
                                            <input type="text" class="form-control form-control-lg" id="aadhaar_no" name="aadhaar_no"
                                            minlength="12" maxlength="12" placeholder="Please Enter Aadhaar No Ex: 123456789012" value="{{ $data['aadhaar_no'] ?? $session_data_user['aadhaar_no'] ?? '' }}" required>
                                        </div>
                                    </div>
        
                                    <div class="col-md-5 mb-md-4 mb-3">
                                        <div class="form-group">
                                            <label for="gst_no" class="col-form-label form-label">Aadhaar Upload : *</label>
                                            <input type="file" class="form-control form-control-lg" id="aadhaar_no_file" name="aadhaar_no_file"
                                            accept=".jpg, .jpeg, .webp, .png, .pdf"
                                            required>
                                        </div>
                                    </div>

                                    <div class="col-md-5 mb-md-4 mb-3">
                                        <div class="form-group">
                                            <label for="gst_no" class="col-form-label form-label">PAN.No: *</label>
                                            <input type="text" class="form-control form-control-lg" id="pan_no" name="pan_no"
                                            minlength="10" maxlength="10" placeholder="Please Enter PAN No Ex: 3WEKY5JOR4" value="{{ $session_data_user['pan_no'] ?? '' }}" required>
                                        </div>
                                    </div>
        
                                    <div class="col-md-5 mb-md-4 mb-3">
                                        <div class="form-group">
                                            <label for="gst_no" class="col-form-label form-label">Pan Upload : *</label>
                                            <input type="file" class="form-control form-control-lg" id="pan_no_file" name="pan_no_file"
                                            accept=".jpg, .jpeg, .webp, .png, .pdf"
                                            required>
                                        </div>
                                    </div>

                                </div> 
                            </div>
                        @else
                            <div class="locality-base-international col-md-12">
                                <div class="row">
                                    <div class="col-md-5 mb-md-4 mb-3">
                                        <div class="form-group">
                                            <label for="iec_no" class="col-form-label form-label">Passport No: *</label>
                                            <input type="text" class="form-control form-control-lg" id="passport_no" name="passport_no"
                                            minlength="8" maxlength="9" placeholder="Please Enter Passport No Ex: HYDA089153811" value="{{ $session_data_user['passport_no'] ?? '' }}" 
                                            @if($session_data_bussines['type_option'] != "domestic") required @endif
                                            >
                                        </div>
                                    </div>

                                    <div class="col-md-5 mb-md-4 mb-3">
                                        <div class="form-group">
                                            <label for="gst_no" class="col-form-label form-label">Passport Upload : *</label>
                                            <input type="file" class="form-control form-control-lg" id="passport_no_file" name="passport_no_file"
                                            accept=".jpg, .jpeg, .webp, .png, .pdf"
                                            @if($session_data_bussines['type_option'] != "domestic") required @endif
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="col-md-3">

                            <div class="form-group">
                                <label for="gst_no" class="col-form-label form-label">Photo Upload : *</label>
                                <input type="file" class="form-control form-control-lg" id="photo_file" name="photo_file"
                                accept=".jpg, .jpeg, .webp, .png"
                                >
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="name">Name *</label>
                                <input type="text" id="name" name="name"
                                    class="form-control form-control-lg" value="{{ $data['name'] ?? $session_data_user['name'] ?? '' }}" required placeholder="Enter Name"/>
                            </div>

                        </div>
                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="name">Father Name *</label>
                                <input type="text" id="father_name" name="father_name"
                                    class="form-control form-control-lg" value="{{ $data['father_name'] ?? $session_data_user['father_name'] ?? '' }}" required placeholder="Enter Father Name"/>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="registration_date">D.O.B *</label>
                                <input type="date" id="dob" name="dob"
                                    class="form-control form-control-lg" value="{{ $data['dob'] ?? $session_data_user['dob'] ?? '' }}" required />
                            </div>

                        </div>


                        <div class="col-md-12 mb-3 text-center">
                            <h3>Address</h3>
                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="street_add_first_personal">Street Address 1 *</label>
                                <input type="text" id="street_add_first_personal" name="street_add_first_personal"
                                    class="form-control form-control-lg" value="{{ $data['street_add_first_personal'] ?? $session_data_user['street_add_first_personal'] ?? '' }}" required placeholder="Enter Street Address"/>
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="street_add_sec_personal">Street Address 2 *</label>
                                <input type="text" id="street_add_sec_personal" name="street_add_sec_personal"
                                    class="form-control form-control-lg" value="{{ $data['street_add_sec_personal'] ?? $session_data_user['street_add_sec_personal'] ?? '' }}" required placeholder="Enter Street Address"/>
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="locality_land_mark_personal">Locality/Suburb/Land Mark *</label>
                                <input type="text" id="locality_land_mark_personal" name="locality_land_mark_personal"
                                    class="form-control form-control-lg" value="{{ $data['locality_land_mark_personal'] ?? $session_data_user['locality_land_mark_personal'] ?? '' }}" required placeholder="Enter Locality/Suburb/Land Mark"/>
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="village_personal">Village *</label>
                                <input type="text" id="village_personal" name="village_personal"
                                    class="form-control form-control-lg" value="{{ $data['village_personal'] ?? $session_data_user['village_personal'] ?? '' }}" required placeholder="Enter Village"/>
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="post_personal">Post *</label>
                                <input type="text" id="post_personal" name="post_personal" value="{{ $data['post_personal'] ?? $session_data_user['post_personal'] ?? '' }}"
                                    class="form-control form-control-lg" placeholder="Enter Post" required/>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="city_id_personal">City / Town *</label>
                                {{-- <input type="text" id="district" name="district"
                                    class="form-control form-control-lg" value="{{ $data['district'] ?? '' }}" required /> --}}

                                <select class="form-control aiz-selectpicker rounded-0" data-live-search="true" name="city_id" required placeholder="Select City">

                                </select>
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="district_personal">District *</label>
                                <input type="text" id="district_personal" name="district_personal" value="{{ $data['district_personal'] ?? $session_data_user['district_personal'] ?? '' }}"
                                    class="form-control form-control-lg" placeholder="Enter District" required/>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="state_personal">State/Province/Region *</label>
                                {{-- <input type="text" id="state" name="state"
                                    class="form-control form-control-lg" value="{{ $data['state'] ?? '' }}" required /> --}}

                                    <select class="form-control aiz-selectpicker rounded-0" data-live-search="true" name="state_id" required placeholder="Select State">

                                    </select>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="pincode">Pincode Or Postal Code *</label>
                                <input type="text" id="pincode_personal" name="pincode_personal"
                                    class="form-control form-control-lg" value="{{ $session_data_user['pincode_personal'] ?? '' }}" placeholder="Enter Pincode Or Postal Code" required />
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="country_personal">Country *</label>
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

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="country_code_personal">Country Code *</label>
                                <input type="text" id="country_code_personal" name="country_code_personal"
                                    class="form-control form-control-lg" value="{{ $session_data_user['country_code_personal'] ?? '' }}" required />
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            @php
                                if(!empty($session_data_user['phone'])){
                                    $Phone_parts = explode('-', $session_data_user['phone']);
                                    $Phone_parts_number = $Phone_parts[1] ?? '';
                                } 
                            @endphp

                            <div class="form-group phone-form-group mb-1">
                                <label for="phone-code" class="fs-12 fw-700 text-soft-dark">Primary Mobile *</label>
                                <input type="tel" id="phone_code" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }} rounded-0" placeholder="Enter Phone No" name="phone" autocomplete="off" 
                                value="{{ $Phone_parts_number ?? '' }}" required>
                            </div>

                            <input type="hidden" name="country_code_phone_code" value="">
                            <input type="hidden" name="phone_code_meta" value="">

                        </div>
                        <div class="col-md-3 mb-md-4 mb-3">

                            @php
                                if(!empty($session_data_user['whats_app_no'])){
                                    $whats_app_no_parts = explode('-', $session_data_user['whats_app_no']);
                                    $whats_app_no_parts_number = $whats_app_no_parts[1] ?? ''; 
                                }
                            @endphp

                            <div class="form-group phone-form-group mb-1">
                                <label for="phone" class="fs-12 fw-700 text-soft-dark">Primary Whatapp No *</label>
                                <input type="tel" id="whats_app_no" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }} rounded-0" placeholder="Enter Whatsapp No" name="whats_app_no" autocomplete="off" value="{{ $whats_app_no_parts_number ?? '' }}" required>
                            </div>

                            <input type="hidden" name="country_code_whats_app_no" value="">
                            <input type="hidden" name="whats_app_no_meta" value="">

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            @php
                                if(!empty($session_data_user['alternate_mob_no_personal'])){
                                    $alternate_mob_no_personal_parts = explode('-', $session_data_user['alternate_mob_no_personal']);
                                    $alternate_mob_no_personal_number = $alternate_mob_no_personal_parts[1] ?? ''; 
                                }
                            @endphp

                            <div class="form-group phone-form-group mb-1">
                                <label for="phone" class="fs-12 fw-700 text-soft-dark">Alternate Mobile (contact Person)</label>
                                <input type="tel" id="alternate_mob_no_personal" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }} rounded-0" placeholder="Enter Alternate Mobile No" name="alternate_mob_no_personal" autocomplete="off" value="{{ $alternate_mob_no_personal_number ?? '' }}" >
                            </div>

                            <input type="hidden" name="country_code_alternate_mob_no_personal" value="">
                            <input type="hidden" name="alternate_mob_no_personal_meta" value="">

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            @php
                                if(!empty($session_data_user['alternate_whats_app_no_personal'])){
                                    $alternate_whats_app_no_parts = explode('-', $session_data_user['alternate_whats_app_no_personal']);
                                    $alternate_whats_app_no_parts_number = $alternate_whats_app_no_parts[1] ?? ''; 
                                }
                            @endphp

                            <div class="form-group phone-form-group mb-1">
                                <label for="phone" class="fs-12 fw-700 text-soft-dark">Alternate Whatapp No</label>
                                <input type="tel" id="alternate_whats_app_no_personal" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }} rounded-0" placeholder="Enter Whatsapp No" name="alternate_whats_app_no_personal" autocomplete="off" value="{{ $alternate_whats_app_no_parts_number ?? '' }}" >
                            </div>

                            <input type="hidden" name="country_code_alternate_whats_app_no_personal" value="">
                            <input type="hidden" name="alternate_whats_app_no_personal_meta" value="">

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="prim_email_personal">Primary E-mail *</label>
                                <input type="email" id="prim_email_personal" name="prim_email_personal"
                                    class="form-control form-control-lg" value="{{ $data['prim_email_personal'] ?? $session_data_user['prim_email_personal'] ?? '' }}" required placeholder="Enter Primary E-mail"/>
                            </div>

                        </div>

                        <div class="col-md-3 mb-md-4 mb-3">

                            <div class="form-group mb-1">
                                <label class="form-label" for="alt_email_personal">Alternate E-mail</label>
                                <input type="email" id="alt_email_personal" name="alt_email_personal"
                                    class="form-control form-control-lg" value="{{ $data['alt_email_personal'] ?? $session_data_user['alt_email_personal'] ?? '' }}" placeholder="Enter Alternate E-mail"/>
                            </div>

                        </div>


                        <div class="col-md-12 mb-3 text-center">
                            <h3>Personal Bank Details</h3>
                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="bank_name_personal">Bank Name *</label>
                                <input type="text" id="bank_name_personal" name="bank_name_personal"
                                    class="form-control form-control-lg" value="{{ $session_data_user['bank_name_personal'] ?? '' }}" required placeholder="Enter Bank Name" />
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="account_no_personal">Account No *</label>
                                <input type="text" id="account_no_personal" name="account_no_personal"
                                    class="form-control form-control-lg" value="{{ $session_data_user['account_no_personal'] ?? '' }}" required placeholder="Enter Account No"/>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="account_name_personal">Account Name *</label>
                                <input type="text" id="account_name_personal" name="account_name_personal"
                                    class="form-control form-control-lg" value="{{ $session_data_user['account_name_personal'] ?? '' }}" required placeholder="Enter Account Name"/>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="branch_code_personal">Branch Code *</label>
                                <input type="text" id="branch_code_personal" name="branch_code_personal"
                                    class="form-control form-control-lg" value="{{ $session_data_user['branch_code_personal'] ?? '' }}" required placeholder="Enter Branch Code"/>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="branch_name_personal">Branch Name *</label>
                                <input type="text" id="branch_name_personal" name="branch_name_personal"
                                    class="form-control form-control-lg" value="{{ $session_data_user['branch_name_personal'] ?? '' }}" required placeholder="Enter Branch Name"/>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="branch_address_personal">Branch Address *</label>
                                <input type="text" id="branch_address_personal" name="branch_address_personal"
                                    class="form-control form-control-lg" value="{{ $session_data_user['branch_address_personal'] ?? '' }}" required placeholder="Enter Branch Address"/>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="ifsc_code_personal">IFSC Code *</label>
                                <input type="text" id="ifsc_code_personal" name="ifsc_code_personal"
                                    class="form-control form-control-lg" value="{{ $session_data_user['ifsc_code_personal'] ?? '' }}" required placeholder="Enter IFSC Code Ex: ICIC0000269"/>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="micr_code_personal">MICR Code *</label>
                                <input type="text" id="micr_code_personal" name="micr_code_personal"
                                    class="form-control form-control-lg" value="{{ $session_data_user['micr_code_personal'] ?? '' }}" required placeholder="Enter MICR Code Ex: 123456789"/>
                            </div>

                        </div>
                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="ad_code_personal">AD code *</label>
                                <input type="text" id="ad_code_personal" name="ad_code_personal"
                                    class="form-control form-control-lg" value="{{ $session_data_user['ad_code_personal'] ?? '' }}" required placeholder="Enter MICR Code Ex: 123456"/>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="blue_btn black_buttons">
                        <button type="button" onclick="back_to_prev_reg();" class=""><img src="{{ static_asset('assets/img/arrow_right.svg') }}" /> Previous</button>
                    </div>
                    <div class="purple_btn">
                        <button type="submit" class="animate_button black1_buttons">Next <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- - //------------------------------  Registration 2 modal -----------------------// -- --}}

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
                    <h5 class="modal-title" id="exampleModalLabel_phone">License Details</h5>
                </div>
            </div>
            <form id="reg_model_form_4" action="{{ url(route('new.user.account.create', ['param' => 'registration-license-details'])) }}"
                method="post">
            
                <div class="modal-body">
                    <div class="row">

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="d_l_no_1">Drug / Pharmacy Licence No 1 *</label>
                                <input type="text" id="d_l_no_1" name="d_l_no_1"
                                    class="form-control form-control-lg" value="{{ $data['d_l_no_1'] ?? '' }}" required placeholder="Enter D.L.No.1"/>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="d_l_no_1">Upload Drug / Pharmacy Licence No 1 *</label>
                                <input type="file" id="d_l_no_1"
                                    class="form-control form-control-lg" name="d_l_no_1_file"
                                    accept=".jpg, .jpeg, .webp, .png, .pdf" required/>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="doctor_hospital_reg_no">Doctor / Pharmacist / Hospital Reg.No </label>
                                <input type="text" id="doctor_hospital_reg_no" name="doctor_hospital_reg_no"
                                    class="form-control form-control-lg" value="{{ $data['doctor_hospital_reg_no'] ?? '' }}" placeholder="Enter D.L.No.2"/>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="doctor_hospital_reg_no_file">Upload Doctor / Pharmacist / Hospital Reg.No </label>
                                <input type="file" id="doctor_hospital_reg_no_file"
                                    class="form-control form-control-lg" name="doctor_hospital_reg_no_file"
                                    accept=".jpg, .jpeg, .webp, .png, .pdf"/>
                            </div>

                        </div>

                {{-- ------------------------------------------------------------------------------------- --}}

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="d_l_no_2">Drug / Pharmacy Licence No 2 *</label>
                                <input type="text" id="d_l_no_2" name="d_l_no_2"
                                    class="form-control form-control-lg" value="{{ $data['d_l_no_2'] ?? '' }}" required placeholder="Enter D.L.No.2"/>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="d_l_no_2">Upload Drug / Pharmacy Licence No 2 *</label>
                                <input type="file" id="d_l_no_2"
                                    class="form-control form-control-lg" name="d_l_no_2_file"
                                    accept=".jpg, .jpeg, .webp, .png, .pdf" required/>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="dairy_trust_ngo_reg_no">Dairy / Trust / NGO / Other Reg.No </label>
                                <input type="text" id="dairy_trust_ngo_reg_no" name="dairy_trust_ngo_reg_no"
                                    class="form-control form-control-lg" value="{{ $data['dairy_trust_ngo_reg_no'] ?? '' }}" placeholder="Enter D.L.No.2"/>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="dairy_trust_ngo_reg_no_file">Upload Dairy / Trust / NGO / Other Reg.No</label>
                                <input type="file" id="dairy_trust_ngo_reg_no_file"
                                    class="form-control form-control-lg" name="dairy_trust_ngo_reg_no_file"
                                    accept=".jpg, .jpeg, .webp, .png, .pdf" />
                            </div>

                        </div>

                {{-----------------------------------------------------------------------------------------------}}


                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="d_l_no_3">Drug / Pharmacy Licence No 3 </label>
                                <input type="text" id="d_l_no_3" name="d_l_no_3"
                                    class="form-control form-control-lg" value="{{ $data['d_l_no_3'] ?? '' }}" placeholder="Enter D.L.No.3"/>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="d_l_no_3">Upload Drug / Pharmacy Licence No 3 </label>
                                <input type="file" id="d_l_no_3"
                                    class="form-control form-control-lg" name="d_l_no_3_file"
                                    accept=".jpg, .jpeg, .webp, .png, .pdf"/>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="cc_mdl_reg_no">CC / MDL Registration No </label>
                                <input type="text" id="cc_mdl_reg_no" name="cc_mdl_reg_no"
                                    class="form-control form-control-lg" value="{{ $data['cc_mdl_reg_no'] ?? '' }}" placeholder="Enter D.L.No.2"/>
                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">
                                <label class="form-label" for="cc_mdl_reg_no_file">Upload CC / MDL Registration No </label>
                                <input type="file" id="cc_mdl_reg_no_file"
                                    class="form-control form-control-lg" name="cc_mdl_reg_no_file"
                                    accept=".jpg, .jpeg, .webp, .png, .pdf" />
                            </div>

                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <div class="blue_btn black_buttons">
                        <button type="button" onclick="back_to_prev_reg();" class=""> <img src="{{ static_asset('assets/img/arrow_right.svg') }}" /> Previous</button>
                    </div>
                    <div class="purple_btn">
                        <button type="submit" class="animate_button black1_buttons">Next <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- - //------------------------------  Registration 4 modal -----------------------// -- --}}

@endif

{{-- - //------------------------------  verification  5 modal -----------------------// -- --}}

 @if (Session::has('step') && Session::get('step') == 5)

@php
    $session_data_personal = session()->get('user_data_personal') ?? [];
    $session_data_bussines = session()->get('user_data_business') ?? []; 
@endphp


    <div class="modal fade login_form_popup" id="reg_model_5" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content py-3">
                <div class="modal-header">
                    <div class="heading">
                        <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                        <h5 class="modal-title" id="exampleModalLabel_phone">Verify Phone Number</h5>
                    </div>

                </div>
                <form id="reg_model_form_5" action="{{ url(route('new.user.account.create', ['param' => 'verify-otps'])) }}"
                    method="post">
                    @csrf

                    <div class="modal-body">
                        <div class="form-group">
                            <label for="recipient-name" class="col-form-label form-label">Verification Code For this Business Number {{ $session_data_bussines['phone_business'] }}:</label>
                            <input type="number" class="form-control form-control-lg" id="recipient-name" name="otp_business"
                                pattern="[0-9]+" minlength="6" maxlength="6" placeholder="Please Enter Code" required>
                        </div>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="recipient-name" class="col-form-label form-label">Verification Code For this Personal Number {{ $session_data_personal['phone'] }}:</label>
                            <input type="number" class="form-control form-control-lg" id="recipient-name" name="otp_personal"
                                pattern="[0-9]+" minlength="6" maxlength="6" placeholder="Please Enter Code" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="blue_btn black_buttons">
                            <button type="button" onclick="back_to_prev_reg();" class=""><img src="{{ static_asset('assets/img/arrow_right.svg') }}" /> Previous</button>
                        </div>

                        <div class="display_flexx">
                             <div class="resend_otp">
                                <a class="ms-4" class="btn btn-primary" onclick="resendOTPButton_Phone('business','{{ $session_data_bussines['phone_business'] }}');">Resend OTP for Business Number</a>
                             </div>
                             <div class="resend_otp">
                                <a class="ms-4" class="btn btn-primary" onclick="resendOTPButton_Phone('personal','{{ $session_data_personal['phone'] }}');">Resend OTP for Business Number</a>
                             </div>
                             <div class="purple_btn">
                            <button type="submit" class="animate_button black1_buttons">Verify <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                        </div>
                       
                        </div>
                       
                    </div>
                </form>
            </div>
        </div>
    </div>



@endif --}}




@if (Session::has('step') && Session::get('step') == 6)

{{-- - //------------------------------ Registration 6 modal -----------------------// -- --}}

@php
    session()->flush();
@endphp

<div class="modal fade login_form_popup" id="reg_model_6" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content py-3">
            <div class="modal-header">
                 <div class="heading">
                    <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                    <h3 class="login_heds thank_head"><span class="blue_light_clr">Pharmvet</span> - <span class="green_light_clr">Easy</span></h3>
                </div>
                
                 {{--<div class="purple_btn_close">
                    <button type="button" onclick="close_and_reload();" class="close p-1 px-3"
                        data-dismiss="modal" aria-label="Close">
                    </button>
                </div>--}}
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <img class="thank_you_clas" src="{{ static_asset('assets/img/thank_you_image.svg') }}" />
                    <h3 class="col-form-label form-label thank_you_label">Your account will be under review and will be activated by an administrator within 48 hours.</h3>
                </div>
            </div>
            <div class="modal-footer" style="justify-content: center;">
                <div class="purple_btn">
                    <!-- <button type="button" onclick="close_and_reload_reg();" class="animate_button black1_buttons"
                        data-dismiss="modal">Back to Login</button> -->
                    <a href="javascript:void(0);" onclick="close_and_reload_reg();" class="animate_button black1_buttons"
                        data-dismiss="modal">Back to Login</a>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- - //------------------------------  Registration 6 modal -----------------------// -- --}}

@endif





@if (Session::has('step') && Session::get('step') == 15)

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
                        <button type="submit" class="animate_button black1_buttons"> Next <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- - //------------------------------  Registration 5 modal -----------------------// -- --}}

@endif

@if (Session::has('step') && Session::get('step') == 16)

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
                        <button type="submit" class="animate_button black1_buttons">Next <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- - //------------------------------  Registration 6 modal -----------------------// -- --}}

@endif

@if (Session::has('step') && Session::get('step') == 17)

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
                        <button type="submit" class="animate_button black1_buttons">Sbumit <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- - //------------------------------  Registration 7 modal -----------------------// -- --}}

@endif

