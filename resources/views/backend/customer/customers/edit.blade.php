@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="align-items-center">
            <h1 class="h3">{{ translate('Edit Business Customer') }}</h1>
        </div>
    </div>

    <div class="card">
        <form id="edit-customer-form" action="{{ route('customers.update', $user->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="card-body">
                {{-- Type --}}
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="mb-3">{{ translate('Type') }}</h5>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input locality-toggle" type="radio" name="type_option" id="type_domestic"
                                   value="domestic" {{ old('type_option', $details->type_option ?? 'domestic') === 'domestic' ? 'checked' : '' }}>
                            <label class="form-check-label" for="type_domestic">{{ translate('Domestic') }}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input locality-toggle" type="radio" name="type_option" id="type_international"
                                   value="international" {{ old('type_option', $details->type_option) === 'international' ? 'checked' : '' }}>
                            <label class="form-check-label" for="type_international">{{ translate('International') }}</label>
                        </div>
                        @error('type_option')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr>

                {{-- Business Identification --}}
                <div class="row locality-domestic locality-block mb-4">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label" for="gst_no">{{ translate('GST No') }} *</label>
                            <input type="text" id="gst_no" name="gst_no" class="form-control"
                                   value="{{ old('gst_no', $details->gst_no) }}" placeholder="22AAAAA0000A1Z5">
                            @error('gst_no')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label" for="gst_no_file">{{ translate('GST Document') }}</label>
                            <input type="file" id="gst_no_file" name="gst_no_file" class="form-control">
                            @if (!empty($details->gst_no_file))
                                <small class="d-block mt-1">
                                    <a href="{{ asset(custom_file($details->gst_no_file)) }}" target="_blank">{{ translate('Current file') }}</a>
                                </small>
                            @endif
                            @error('gst_no_file')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label" for="gstin_current_status">{{ translate('GSTIN Status / Current Status') }} *</label>
                            <input type="text" id="gstin_current_status" name="gstin_current_status" class="form-control"
                                   value="{{ old('gstin_current_status', $details->gstin_current_status) }}">
                            @error('gstin_current_status')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row locality-international locality-block mb-4">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label" for="iec_no">{{ translate('IEC No') }} *</label>
                            <input type="text" id="iec_no" name="iec_no" class="form-control"
                                   value="{{ old('iec_no', $details->iec_no) }}" placeholder="1234567890">
                            @error('iec_no')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label" for="iec_no_file">{{ translate('IEC Document') }}</label>
                            <input type="file" id="iec_no_file" name="iec_no_file" class="form-control">
                            @if (!empty($details->iec_no_file))
                                <small class="d-block mt-1">
                                    <a href="{{ asset(custom_file($details->iec_no_file)) }}" target="_blank">{{ translate('Current file') }}</a>
                                </small>
                            @endif
                            @error('iec_no_file')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label" for="uin_current_status">{{ translate('UIN Status / Current Status') }} *</label>
                            <input type="text" id="uin_current_status" name="uin_current_status" class="form-control"
                                   value="{{ old('uin_current_status', $details->uin_current_status) }}">
                            @error('uin_current_status')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Business core --}}
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="registration_date">{{ translate('Registration Date') }} *</label>
                            <input type="date" id="registration_date" name="registration_date" class="form-control"
                                   value="{{ old('registration_date', $details->registration_date) }}">
                            @error('registration_date')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="const_of_business">{{ translate('Constitution of Business') }} *</label>
                            <input type="text" id="const_of_business" name="const_of_business" class="form-control"
                                   value="{{ old('const_of_business', $details->const_of_business) }}">
                            @error('const_of_business')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="con_person_name">{{ translate('Concerned Person Name') }} *</label>
                            <input type="text" id="con_person_name" name="con_person_name" class="form-control"
                                   value="{{ old('con_person_name', $details->con_person_name) }}">
                            @error('con_person_name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="company_name">{{ translate('Company Name') }} *</label>
                            <input type="text" id="company_name" name="company_name" class="form-control"
                                   value="{{ old('company_name', $details->company_name) }}">
                            @error('company_name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Business Address --}}
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="mb-3">{{ translate('Business Address') }}</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="street_add_first_business">{{ translate('Street Address 1') }} *</label>
                        <input type="text" name="street_add_first_business" id="street_add_first_business" class="form-control"
                               value="{{ old('street_add_first_business', $details->street_add_first_business) }}">
                        @error('street_add_first_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="street_add_sec_business">{{ translate('Street Address 2') }}</label>
                        <input type="text" name="street_add_sec_business" id="street_add_sec_business" class="form-control"
                               value="{{ old('street_add_sec_business', $details->street_add_sec_business) }}">
                        @error('street_add_sec_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="locality_land_mark_business">{{ translate('Locality / Landmark') }} *</label>
                        <input type="text" name="locality_land_mark_business" id="locality_land_mark_business" class="form-control"
                               value="{{ old('locality_land_mark_business', $details->locality_land_mark_business) }}">
                        @error('locality_land_mark_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="village_business">{{ translate('Village') }} *</label>
                        <input type="text" name="village_business" id="village_business" class="form-control"
                               value="{{ old('village_business', $details->village_business) }}">
                        @error('village_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="post_business">{{ translate('Post') }} *</label>
                        <input type="text" name="post_business" id="post_business" class="form-control"
                               value="{{ old('post_business', $details->post_business) }}">
                        @error('post_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="district_business">{{ translate('District') }} *</label>
                        <input type="text" name="district_business" id="district_business" class="form-control"
                               value="{{ old('district_business', $details->district_business) }}">
                        @error('district_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="country_id_business">{{ translate('Country') }} *</label>
                        <select name="country_id_business" id="country_id_business" class="form-control aiz-selectpicker" data-live-search="true">
                            <option value="">{{ translate('Select Country') }}</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" {{ (string) old('country_id_business', $details->country_id_business) === (string) $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('country_id_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="state_id_business">{{ translate('State') }} *</label>
                        <select name="state_id_business" id="state_id_business" class="form-control aiz-selectpicker" data-live-search="true" data-selected="{{ old('state_id_business', $details->state_id_business) }}">
                            <option value="">{{ translate('Select State') }}</option>
                        </select>
                        @error('state_id_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="city_id_business">{{ translate('City') }} *</label>
                        <select name="city_id_business" id="city_id_business" class="form-control aiz-selectpicker" data-live-search="true" data-selected="{{ old('city_id_business', $details->city_id_business) }}">
                            <option value="">{{ translate('Select City') }}</option>
                        </select>
                        @error('city_id_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="pincode_business">{{ translate('Pincode') }} *</label>
                        <input type="text" name="pincode_business" id="pincode_business" class="form-control"
                               value="{{ old('pincode_business', $details->pincode_business) }}">
                        @error('pincode_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="country_code_business">{{ translate('Country Code') }} *</label>
                        <input type="text" name="country_code_business" id="country_code_business" class="form-control"
                               value="{{ old('country_code_business', $details->country_code_business) }}">
                        @error('country_code_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Business Contact --}}
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="mb-3">{{ translate('Business Contact') }}</h5>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="phone_business">{{ translate('Primary Mobile') }} *</label>
                        <input type="text" id="phone_business" name="phone_business" class="form-control"
                               value="{{ old('phone_business', optional($details)->prim_mobile_no_business ? explode('-', $details->prim_mobile_no_business)[1] ?? $details->prim_mobile_no_business : '') }}">
                        @error('phone_business') <div class="text-danger small">{{ $message }}</div> @enderror
                        <input type="hidden" name="phone_code_meta" value="">
                        <input type="hidden" name="country_code_phone_code_business" value="{{ old('country_code_phone_code_business', $details->country_code_business) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="alternate_mob_no_business">{{ translate('Alternate Mobile (Contact Person)') }}</label>
                        <input type="text" id="alternate_mob_no_business" name="alternate_mob_no_business" class="form-control"
                               value="{{ old('alternate_mob_no_business', optional($details)->alt_mobile_no_business ? explode('-', $details->alt_mobile_no_business)[1] ?? $details->alt_mobile_no_business : '') }}">
                        @error('alternate_mob_no_business') <div class="text-danger small">{{ $message }}</div> @enderror
                        <input type="hidden" name="alternate_mob_no_business_meta" value="">
                        <input type="hidden" name="country_code_alternate_mob_no_business" value="{{ old('country_code_alternate_mob_no_business', $details->country_code_business) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="whats_app_no_business">{{ translate('Primary WhatsApp') }} *</label>
                        <input type="text" id="whats_app_no_business" name="whats_app_no_business" class="form-control"
                               value="{{ old('whats_app_no_business', optional($details)->prim_whats_app_no_business ? explode('-', $details->prim_whats_app_no_business)[1] ?? $details->prim_whats_app_no_business : '') }}">
                        @error('whats_app_no_business') <div class="text-danger small">{{ $message }}</div> @enderror
                        <input type="hidden" name="whats_app_no_business_meta" value="">
                        <input type="hidden" name="country_code_whats_app_no_business" value="{{ old('country_code_whats_app_no_business', $details->country_code_business) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="alternate_whats_app_no_business">{{ translate('Alternate WhatsApp') }}</label>
                        <input type="text" id="alternate_whats_app_no_business" name="alternate_whats_app_no_business" class="form-control"
                               value="{{ old('alternate_whats_app_no_business', optional($details)->alternate_whats_app_no_business ? explode('-', $details->alternate_whats_app_no_business)[1] ?? $details->alternate_whats_app_no_business : '') }}">
                        @error('alternate_whats_app_no_business') <div class="text-danger small">{{ $message }}</div> @enderror
                        <input type="hidden" name="alternate_whats_app_no_business_meta" value="">
                        <input type="hidden" name="country_code_alternate_whats_app_no_business" value="{{ old('country_code_alternate_whats_app_no_business', $details->country_code_business) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="prim_email_business">{{ translate('Primary Email') }} *</label>
                        <input type="email" id="prim_email_business" name="prim_email_business" class="form-control"
                               value="{{ old('prim_email_business', $details->prim_email_business ?? $user->email) }}">
                        @error('prim_email_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="alt_email_business">{{ translate('Alternate Email') }}</label>
                        <input type="email" id="alt_email_business" name="alt_email_business" class="form-control"
                               value="{{ old('alt_email_business', $details->alt_email_business) }}">
                        @error('alt_email_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="website_business">{{ translate('Website') }}</label>
                        <input type="text" id="website_business" name="website_business" class="form-control"
                               value="{{ old('website_business', $details->website_business) }}">
                        @error('website_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Business Bank --}}
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="mb-3">{{ translate('Business Bank Details') }}</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="bank_name_business">{{ translate('Bank Name') }} *</label>
                        <input type="text" id="bank_name_business" name="bank_name_business" class="form-control"
                               value="{{ old('bank_name_business', $details->bank_name_business) }}">
                        @error('bank_name_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="account_no_business">{{ translate('Account No') }} *</label>
                        <input type="text" id="account_no_business" name="account_no_business" class="form-control"
                               value="{{ old('account_no_business', $details->account_no_business) }}">
                        @error('account_no_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="account_name_business">{{ translate('Account Name') }} *</label>
                        <input type="text" id="account_name_business" name="account_name_business" class="form-control"
                               value="{{ old('account_name_business', $details->account_name_business) }}">
                        @error('account_name_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="branch_code_business">{{ translate('Branch Code') }} *</label>
                        <input type="text" id="branch_code_business" name="branch_code_business" class="form-control"
                               value="{{ old('branch_code_business', $details->branch_code_business) }}">
                        @error('branch_code_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="branch_name_business">{{ translate('Branch Name') }} *</label>
                        <input type="text" id="branch_name_business" name="branch_name_business" class="form-control"
                               value="{{ old('branch_name_business', $details->branch_name_business) }}">
                        @error('branch_name_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="branch_address_business">{{ translate('Branch Address') }} *</label>
                        <input type="text" id="branch_address_business" name="branch_address_business" class="form-control"
                               value="{{ old('branch_address_business', $details->branch_address_business) }}">
                        @error('branch_address_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ifsc_code_business">{{ translate('IFSC Code') }} *</label>
                        <input type="text" id="ifsc_code_business" name="ifsc_code_business" class="form-control"
                               value="{{ old('ifsc_code_business', $details->ifsc_code_business) }}">
                        @error('ifsc_code_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3 locality-international locality-block">
                        <label class="form-label" for="micr_code_business">{{ translate('MICR Code') }}</label>
                        <input type="text" id="micr_code_business" name="micr_code_business" class="form-control"
                               value="{{ old('micr_code_business', $details->micr_code_business) }}">
                        @error('micr_code_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3 locality-international locality-block">
                        <label class="form-label" for="ad_code_business">{{ translate('AD Code') }}</label>
                        <input type="text" id="ad_code_business" name="ad_code_business" class="form-control"
                               value="{{ old('ad_code_business', $details->ad_code_business) }}">
                        @error('ad_code_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                <hr>

                {{-- Personal Details --}}
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="mb-3">{{ translate('Personal Details') }}</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="photo_file">{{ translate('Photo') }} *</label>
                        <input type="file" id="photo_file" name="photo_file" class="form-control">
                        @if (!empty($details->photo_file))
                            <small class="d-block mt-1"><a href="{{ asset(custom_file($details->photo_file)) }}" target="_blank">{{ translate('Current file') }}</a></small>
                        @endif
                        @error('photo_file') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="name_personal">{{ translate('Name') }} *</label>
                        <input type="text" id="name_personal" name="name_personal" class="form-control" value="{{ old('name_personal', $details->name ?? $user->name) }}">
                        @error('name_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="father_name">{{ translate('Father Name') }} *</label>
                        <input type="text" id="father_name" name="father_name" class="form-control" value="{{ old('father_name', $details->father_name) }}">
                        @error('father_name') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="dob">{{ translate('Date of Birth') }} *</label>
                        <input type="date" id="dob" name="dob" class="form-control" value="{{ old('dob', $details->dob) }}">
                        @error('dob') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Personal Address --}}
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="mb-3">{{ translate('Personal Address') }}</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="street_add_first_personal">{{ translate('Street Address 1') }} *</label>
                        <input type="text" id="street_add_first_personal" name="street_add_first_personal" class="form-control" value="{{ old('street_add_first_personal', $details->street_add_first) }}">
                        @error('street_add_first_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="street_add_sec_personal">{{ translate('Street Address 2') }}</label>
                        <input type="text" id="street_add_sec_personal" name="street_add_sec_personal" class="form-control" value="{{ old('street_add_sec_personal', $details->street_add_sec) }}">
                        @error('street_add_sec_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="locality_land_mark_personal">{{ translate('Locality / Landmark') }} *</label>
                        <input type="text" id="locality_land_mark_personal" name="locality_land_mark_personal" class="form-control" value="{{ old('locality_land_mark_personal', $details->locality_land_mark) }}">
                        @error('locality_land_mark_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="village_personal">{{ translate('Village') }} *</label>
                        <input type="text" id="village_personal" name="village_personal" class="form-control" value="{{ old('village_personal', $details->village) }}">
                        @error('village_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="post_personal">{{ translate('Post') }} *</label>
                        <input type="text" id="post_personal" name="post_personal" class="form-control" value="{{ old('post_personal', $details->post) }}">
                        @error('post_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="district_personal">{{ translate('District') }} *</label>
                        <input type="text" id="district_personal" name="district_personal" class="form-control" value="{{ old('district_personal', $details->district) }}">
                        @error('district_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="country_id_personal">{{ translate('Country') }} *</label>
                        <select name="country_id_personal" id="country_id_personal" class="form-control aiz-selectpicker" data-live-search="true">
                            <option value="">{{ translate('Select Country') }}</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" {{ (string) old('country_id_personal', $details->country_id) === (string) $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('country_id_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="state_id_personal">{{ translate('State') }} *</label>
                        <select name="state_id_personal" id="state_id_personal" class="form-control aiz-selectpicker" data-live-search="true" data-selected="{{ old('state_id_personal', $details->state_id) }}">
                            <option value="">{{ translate('Select State') }}</option>
                        </select>
                        @error('state_id_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="city_id_personal">{{ translate('City') }} *</label>
                        <select name="city_id_personal" id="city_id_personal" class="form-control aiz-selectpicker" data-live-search="true" data-selected="{{ old('city_id_personal', $details->city_id) }}">
                            <option value="">{{ translate('Select City') }}</option>
                        </select>
                        @error('city_id_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="pincode_personal">{{ translate('Pincode') }} *</label>
                        <input type="text" id="pincode_personal" name="pincode_personal" class="form-control" value="{{ old('pincode_personal', $details->pincode) }}">
                        @error('pincode_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="country_code_personal">{{ translate('Country Code') }} *</label>
                        <input type="text" id="country_code_personal" name="country_code_personal" class="form-control" value="{{ old('country_code_personal', $details->country_code) }}">
                        @error('country_code_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Personal Contact --}}
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="mb-3">{{ translate('Personal Contact') }}</h5>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="phone_personal">{{ translate('Primary Mobile') }} *</label>
                        <input type="text" id="phone_personal" name="phone_personal" class="form-control"
                               value="{{ old('phone_personal', optional($details)->prim_mobile_no ? explode('-', $details->prim_mobile_no)[1] ?? $details->prim_mobile_no : '') }}">
                        @error('phone_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                        <input type="hidden" name="phone_personal_meta" value="">
                        <input type="hidden" name="country_code_phone_code_personal" value="{{ old('country_code_phone_code_personal', $details->country_code) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="alternate_mob_no_personal">{{ translate('Alternate Mobile') }}</label>
                        <input type="text" id="alternate_mob_no_personal" name="alternate_mob_no_personal" class="form-control"
                               value="{{ old('alternate_mob_no_personal', optional($details)->alt_mobile_no ? explode('-', $details->alt_mobile_no)[1] ?? $details->alt_mobile_no : '') }}">
                        @error('alternate_mob_no_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                        <input type="hidden" name="alternate_mob_no_personal_meta" value="">
                        <input type="hidden" name="country_code_alternate_mob_no_personal" value="{{ old('country_code_alternate_mob_no_personal', $details->country_code) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="whats_app_no_personal">{{ translate('Primary WhatsApp') }} *</label>
                        <input type="text" id="whats_app_no_personal" name="whats_app_no_personal" class="form-control"
                               value="{{ old('whats_app_no_personal', optional($details)->prim_whats_app_no ? explode('-', $details->prim_whats_app_no)[1] ?? $details->prim_whats_app_no : '') }}">
                        @error('whats_app_no_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                        <input type="hidden" name="whats_app_no_personal_meta" value="">
                        <input type="hidden" name="country_code_whats_app_no_personal" value="{{ old('country_code_whats_app_no_personal', $details->country_code) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="alternate_whats_app_no_personal">{{ translate('Alternate WhatsApp') }}</label>
                        <input type="text" id="alternate_whats_app_no_personal" name="alternate_whats_app_no_personal" class="form-control"
                               value="{{ old('alternate_whats_app_no_personal', optional($details)->alt_whats_app_no ? explode('-', $details->alt_whats_app_no)[1] ?? $details->alt_whats_app_no : '') }}">
                        @error('alternate_whats_app_no_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                        <input type="hidden" name="alternate_whats_app_no_personal_meta" value="">
                        <input type="hidden" name="country_code_alternate_whats_app_no_personal" value="{{ old('country_code_alternate_whats_app_no_personal', $details->country_code) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="prim_email_personal">{{ translate('Primary Email') }} *</label>
                        <input type="email" id="prim_email_personal" name="prim_email_personal" class="form-control" value="{{ old('prim_email_personal', $details->prim_email_personal ?? $user->email) }}">
                        @error('prim_email_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="alt_email_personal">{{ translate('Alternate Email') }}</label>
                        <input type="email" id="alt_email_personal" name="alt_email_personal" class="form-control" value="{{ old('alt_email_personal', $details->alt_email_personal) }}">
                        @error('alt_email_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Personal Bank --}}
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="mb-3">{{ translate('Personal Bank Details') }}</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="bank_name_personal">{{ translate('Bank Name') }} *</label>
                        <input type="text" id="bank_name_personal" name="bank_name_personal" class="form-control" value="{{ old('bank_name_personal', $details->bank_name_personal) }}">
                        @error('bank_name_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="account_no_personal">{{ translate('Account No') }} *</label>
                        <input type="text" id="account_no_personal" name="account_no_personal" class="form-control" value="{{ old('account_no_personal', $details->account_no_personal) }}">
                        @error('account_no_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="account_name_personal">{{ translate('Account Name') }} *</label>
                        <input type="text" id="account_name_personal" name="account_name_personal" class="form-control" value="{{ old('account_name_personal', $details->account_name_personal) }}">
                        @error('account_name_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="branch_code_personal">{{ translate('Branch Code') }} *</label>
                        <input type="text" id="branch_code_personal" name="branch_code_personal" class="form-control" value="{{ old('branch_code_personal', $details->branch_code_personal) }}">
                        @error('branch_code_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="branch_name_personal">{{ translate('Branch Name') }} *</label>
                        <input type="text" id="branch_name_personal" name="branch_name_personal" class="form-control" value="{{ old('branch_name_personal', $details->branch_name_personal) }}">
                        @error('branch_name_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="branch_address_personal">{{ translate('Branch Address') }} *</label>
                        <input type="text" id="branch_address_personal" name="branch_address_personal" class="form-control" value="{{ old('branch_address_personal', $details->branch_address_personal) }}">
                        @error('branch_address_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ifsc_code_personal">{{ translate('IFSC Code') }} *</label>
                        <input type="text" id="ifsc_code_personal" name="ifsc_code_personal" class="form-control" value="{{ old('ifsc_code_personal', $details->ifsc_code_personal) }}">
                        @error('ifsc_code_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3 locality-international locality-block">
                        <label class="form-label" for="micr_code_personal">{{ translate('MICR Code') }}</label>
                        <input type="text" id="micr_code_personal" name="micr_code_personal" class="form-control" value="{{ old('micr_code_personal', $details->micr_code_personal) }}">
                        @error('micr_code_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3 locality-international locality-block">
                        <label class="form-label" for="ad_code_personal">{{ translate('AD Code') }}</label>
                        <input type="text" id="ad_code_personal" name="ad_code_personal" class="form-control" value="{{ old('ad_code_personal', $details->ad_code_personal) }}">
                        @error('ad_code_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Personal KYC --}}
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="mb-3">{{ translate('KYC Documents') }}</h5>
                    </div>
                    <div class="col-md-4 mb-3 locality-domestic locality-block">
                        <label class="form-label" for="aadhaar_no">{{ translate('Aadhaar No') }}</label>
                        <input type="text" id="aadhaar_no" name="aadhaar_no" class="form-control" value="{{ old('aadhaar_no', $details->aadhaar_no) }}">
                        @error('aadhaar_no') <div class="text-danger small">{{ $message }}</div> @enderror
                        <input type="file" name="aadhaar_no_file" class="form-control mt-2">
                        @if (!empty($details->aadhaar_no_file))
                            <small class="d-block mt-1"><a href="{{ asset(custom_file($details->aadhaar_no_file)) }}" target="_blank">{{ translate('Current Aadhaar file') }}</a></small>
                        @endif
                        @error('aadhaar_no_file') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3 locality-domestic locality-block">
                        <label class="form-label" for="pan_no">{{ translate('PAN No') }}</label>
                        <input type="text" id="pan_no" name="pan_no" class="form-control" value="{{ old('pan_no', $details->pan_no) }}">
                        @error('pan_no') <div class="text-danger small">{{ $message }}</div> @enderror
                        <input type="file" name="pan_no_file" class="form-control mt-2">
                        @if (!empty($details->pan_no_file))
                            <small class="d-block mt-1"><a href="{{ asset(custom_file($details->pan_no_file)) }}" target="_blank">{{ translate('Current PAN file') }}</a></small>
                        @endif
                        @error('pan_no_file') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3 locality-international locality-block">
                        <label class="form-label" for="passport_no">{{ translate('Passport No') }}</label>
                        <input type="text" id="passport_no" name="passport_no" class="form-control" value="{{ old('passport_no', $details->passport_no) }}">
                        @error('passport_no') <div class="text-danger small">{{ $message }}</div> @enderror
                        <input type="file" name="passport_no_file" class="form-control mt-2">
                        @if (!empty($details->passport_no_file))
                            <small class="d-block mt-1"><a href="{{ asset(custom_file($details->passport_no_file)) }}" target="_blank">{{ translate('Current Passport file') }}</a></small>
                        @endif
                        @error('passport_no_file') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- License Details --}}
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="mb-3">{{ translate('License / Registration Details') }}</h5>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="license_field_selector">{{ translate('Add License Field') }}</label>
                                        <select id="license_field_selector" class="form-control aiz-selectpicker" data-live-search="true">
                                            <option value="">{{ translate('Select field to add') }}</option>
                                            <option value="d_l_no_1" {{ $details->d_l_no_1 ? 'disabled' : '' }}>{{ translate('Drug / Pharmacy Licence No 1') }}</option>
                                            <option value="doctor_hospital_reg_no" {{ $details->doctor_hospital_reg_no ? 'disabled' : '' }}>{{ translate('Doctor / Pharmacist / Hospital Reg. No') }}</option>
                                            <option value="d_l_no_2" {{ $details->d_l_no_2 ? 'disabled' : '' }}>{{ translate('Drug / Pharmacy Licence No 2') }}</option>
                                            <option value="dairy_trust_ngo_reg_no" {{ $details->dairy_trust_ngo_reg_no ? 'disabled' : '' }}>{{ translate('Dairy / Trust / NGO / Other Reg. No') }}</option>
                                            <option value="d_l_no_3" {{ $details->d_l_no_3 ? 'disabled' : '' }}>{{ translate('Drug / Pharmacy Licence No 3') }}</option>
                                            <option value="cc_mdl_reg_no" {{ $details->cc_mdl_reg_no ? 'disabled' : '' }}>{{ translate('CC / MDL Registration No') }}</option>
                                            <option value="other_reg_no" {{ $details->other_reg_no ? 'disabled' : '' }}>{{ translate('Other Registration No') }}</option>
                                        </select>
                                    </div>
                                </div>
                                @php
                                    $licenseFieldsExisting = [
                                        'd_l_no_1' => ['label' => translate('Drug / Pharmacy Licence No 1'), 'file' => $details->d_l_no_1_file, 'value' => $details->d_l_no_1],
                                        'd_l_no_2' => ['label' => translate('Drug / Pharmacy Licence No 2'), 'file' => $details->d_l_no_2_file, 'value' => $details->d_l_no_2],
                                        'd_l_no_3' => ['label' => translate('Drug / Pharmacy Licence No 3'), 'file' => $details->d_l_no_3_file, 'value' => $details->d_l_no_3],
                                        'doctor_hospital_reg_no' => ['label' => translate('Doctor / Pharmacist / Hospital Reg. No'), 'file' => $details->doctor_hospital_reg_no_file, 'value' => $details->doctor_hospital_reg_no],
                                        'dairy_trust_ngo_reg_no' => ['label' => translate('Dairy / Trust / NGO / Other Reg. No'), 'file' => $details->dairy_trust_ngo_reg_no_file, 'value' => $details->dairy_trust_ngo_reg_no],
                                        'cc_mdl_reg_no' => ['label' => translate('CC / MDL Registration No'), 'file' => $details->cc_mdl_reg_no_file, 'value' => $details->cc_mdl_reg_no],
                                        'other_reg_no' => ['label' => translate('Other Registration No'), 'file' => $details->other_reg_no_file, 'value' => $details->other_reg_no],
                                    ];
                                @endphp
                                <div class="row" id="existing_license_wrapper">
                                    @foreach ($licenseFieldsExisting as $key => $item)
                                        @php
                                            $val = old($key, $item['value']);
                                            $hasData = filled($val) || !empty($item['file']);
                                        @endphp
                                        @if ($hasData)
                                            <div class="col-md-6 mb-3" data-existing-license="{{ $key }}">
                                                <div class="card h-100 border">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <label class="form-label mb-0" for="{{ $key }}">{{ $item['label'] }}</label>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" data-remove-existing="{{ $key }}">{{ translate('Remove') }}</button>
                                                        </div>
                                                        <input type="text" id="{{ $key }}" name="{{ $key }}" class="form-control mb-2" value="{{ $val }}">
                                                        <input type="file" name="{{ $key }}_file" class="form-control">
                                                        @if (!empty($item['file']))
                                                            <small class="d-block mt-1"><a href="{{ asset(custom_file($item['file'])) }}" target="_blank">{{ translate('Current file') }}</a></small>
                                                        @endif
                                                        @error($key . '_file') <div class="text-danger small">{{ $message }}</div> @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                <div class="row" id="dynamic_license_fields"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <a href="{{ route('customers.business') }}" class="btn btn-soft-secondary">{{ translate('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ translate('Update Customer') }}</button>
            </div>
        </form>
    </div>
@endsection

@section('script')
    <script>
        const statesCache = {};
        const citiesCache = {};

        function toggleLocalityBlocks() {
            const selected = document.querySelector('input[name="type_option"]:checked')?.value || 'domestic';
            document.querySelectorAll('.locality-block').forEach(block => block.classList.add('d-none'));
            if (selected === 'domestic') {
                document.querySelectorAll('.locality-domestic').forEach(block => block.classList.remove('d-none'));
            } else {
                document.querySelectorAll('.locality-international').forEach(block => block.classList.remove('d-none'));
            }
        }

        function populateSelect(selectEl, html) {
            selectEl.innerHTML = html;
            const selectedVal = selectEl.dataset.selected || '';
            if (selectedVal) {
                selectEl.value = selectedVal;
            }
            AIZ.plugins.bootstrapSelect('refresh');
        }

        function fetchStates(countryId, targetId, cityTargetId) {
            if (!countryId) {
                populateSelect(document.getElementById(targetId), '<option value="">{{ translate("Select State") }}</option>');
                return;
            }
            if (statesCache[countryId]) {
                populateSelect(document.getElementById(targetId), statesCache[countryId]);
                // if we already have the selected state, maybe load cities too
                const stateEl = document.getElementById(targetId);
                if (cityTargetId && stateEl && stateEl.value) {
                    fetchCities(stateEl.value, cityTargetId);
                }
                return;
            }
            $.post('{{ route('get-state') }}', {
                _token: '{{ csrf_token() }}',
                country_id: countryId
            }, function (data) {
                statesCache[countryId] = data;
                populateSelect(document.getElementById(targetId), data);
                const stateEl = document.getElementById(targetId);
                if (cityTargetId && stateEl && stateEl.value) {
                    fetchCities(stateEl.value, cityTargetId);
                }
            });
        }

        function fetchCities(stateId, targetId) {
            if (!stateId) {
                populateSelect(document.getElementById(targetId), '<option value="">{{ translate("Select City") }}</option>');
                return;
            }
            if (citiesCache[stateId]) {
                populateSelect(document.getElementById(targetId), citiesCache[stateId]);
                return;
            }
            $.post('{{ route('get-city') }}', {
                _token: '{{ csrf_token() }}',
                state_id: stateId
            }, function (data) {
                citiesCache[stateId] = data;
                populateSelect(document.getElementById(targetId), data);
            });
        }

        function setupLocationHandlers(prefix) {
            const countrySel = document.getElementById(`country_id_${prefix}`);
            const stateSel = document.getElementById(`state_id_${prefix}`);
            const citySel = document.getElementById(`city_id_${prefix}`);

            if (countrySel) {
                countrySel.addEventListener('change', function () {
                    fetchStates(this.value, stateSel.id, citySel.id);
                    populateSelect(citySel, '<option value="">{{ translate("Select City") }}</option>');
                });

                // load initial states/cities when page loads if there is a selected country
                if (countrySel.value) {
                    fetchStates(countrySel.value, stateSel.id, citySel.id);
                }
            }
            if (stateSel) {
                stateSel.addEventListener('change', function () {
                    fetchCities(this.value, citySel.id);
                });

                // load initial cities if state preselected
                if (stateSel.dataset.selected) {
                    fetchCities(stateSel.dataset.selected, citySel.id);
                }
            }
        }

        const licenseFieldMap = {
            d_l_no_1: { label: '{{ translate('Drug / Pharmacy Licence No 1') }}', name: 'd_l_no_1', file: 'd_l_no_1_file' },
            doctor_hospital_reg_no: { label: '{{ translate('Doctor / Pharmacist / Hospital Reg. No') }}', name: 'doctor_hospital_reg_no', file: 'doctor_hospital_reg_no_file' },
            d_l_no_2: { label: '{{ translate('Drug / Pharmacy Licence No 2') }}', name: 'd_l_no_2', file: 'd_l_no_2_file' },
            dairy_trust_ngo_reg_no: { label: '{{ translate('Dairy / Trust / NGO / Other Reg. No') }}', name: 'dairy_trust_ngo_reg_no', file: 'dairy_trust_ngo_reg_no_file' },
            d_l_no_3: { label: '{{ translate('Drug / Pharmacy Licence No 3') }}', name: 'd_l_no_3', file: 'd_l_no_3_file' },
            cc_mdl_reg_no: { label: '{{ translate('CC / MDL Registration No') }}', name: 'cc_mdl_reg_no', file: 'cc_mdl_reg_no_file' },
            other_reg_no: { label: '{{ translate('Other Registration No') }}', name: 'other_reg_no', file: 'other_reg_no_file' },
        };

        function setupDynamicLicenseFields() {
            const selector = document.getElementById('license_field_selector');
            const container = document.getElementById('dynamic_license_fields');
            if (!selector || !container) return;

            selector.addEventListener('change', function () {
                const key = this.value;
                if (!key || document.getElementById(`${key}_wrapper`)) {
                    return;
                }
                // disable option immediately
                const opt = selector.querySelector(`option[value="${key}"]`);
                if (opt) {
                    opt.disabled = true;
                }
                AIZ.plugins.bootstrapSelect('refresh');

                const def = licenseFieldMap[key];
                const wrap = document.createElement('div');
                wrap.className = 'col-md-6 mb-3';
                wrap.id = `${key}_wrapper`;
                wrap.innerHTML = `
                    <div class="card h-100 border">
                        <div class="card-body p-3 position-relative">
                            <button type="button" class="btn btn-sm btn-outline-danger position-absolute" style="top:4px; right:4px;" aria-label="Remove" data-remove-wrapper="${key}_wrapper" data-enable-option="${key}">{{ translate('Remove') }}</button>
                            <div class="form-group">
                                <label>${def.label}</label>
                                <input type="text" name="${def.name}" class="form-control" placeholder="${def.label}">
                            </div>
                            <div class="form-group mb-0">
                                <label>{{ translate('Upload') }} ${def.label}</label>
                                <input type="file" name="${def.file}" class="form-control" accept=".jpg,.jpeg,.webp,.png,.pdf">
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(wrap);

                const removeBtn = wrap.querySelector('[data-remove-wrapper]');
                removeBtn.addEventListener('click', () => {
                    const optKey = removeBtn.getAttribute('data-enable-option');
                    const opt = selector.querySelector(`option[value="${optKey}"]`);
                    if (opt) {
                        opt.disabled = false;
                    }
                    wrap.remove();
                    refreshLicenseSelect();
                });

                selector.value = '';
                refreshLicenseSelect();
            });
        }

        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('locality-toggle')) {
                toggleLocalityBlocks();
            }
        });

        toggleLocalityBlocks();
        AIZ.plugins.bootstrapSelect('refresh');

        setupLocationHandlers('business');
        setupLocationHandlers('personal');
        // helper to sync dropdown disabled state with visible license blocks
        function refreshLicenseSelect() {
            const selector = document.getElementById('license_field_selector');
            if (!selector) return;
            const activeKeys = new Set();
            document.querySelectorAll('[data-existing-license]').forEach(el => activeKeys.add(el.getAttribute('data-existing-license')));
            document.querySelectorAll('#dynamic_license_fields [id$="_wrapper"]').forEach(el => {
                const key = el.id.replace('_wrapper', '');
                activeKeys.add(key);
            });
            selector.querySelectorAll('option').forEach(opt => {
                if (!opt.value) return;
                opt.disabled = activeKeys.has(opt.value);
            });
            AIZ.plugins.bootstrapSelect('refresh');
        }

        setupDynamicLicenseFields();

        // Bind remove buttons for existing licenses to re-enable dropdown options
        document.querySelectorAll('[data-remove-existing]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const key = this.getAttribute('data-remove-existing');
                const opt = document.querySelector(`#license_field_selector option[value="${key}"]`);
                if (opt) {
                    opt.disabled = false;
                }

                const wrapper = this.closest('[data-existing-license]');
                if (wrapper) {
                    wrapper.remove();
                }

                const form = document.getElementById('edit-customer-form');
                if (form) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = `remove_license[${key}]`;
                    hidden.value = '1';
                    form.appendChild(hidden);
                }

                refreshLicenseSelect();
            });
        });

        // Initial sync for options based on existing data
        refreshLicenseSelect();
    </script>
@endsection
