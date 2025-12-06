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
            <div class="card-body">
                <div id="form-error-box" class="alert alert-danger d-none"></div>
                {{-- Type --}}
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="mb-3">{{ translate('Type') }}</h5>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input locality-toggle" type="radio" name="type_option" id="type_domestic" value="domestic" {{ (old('type_option') ?: ($user->type_option ?: 'domestic')) === 'domestic' ? 'checked' : '' }}>
                            <label class="form-check-label" for="type_domestic">{{ translate('Domestic') }}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input locality-toggle" type="radio" name="type_option" id="type_international" value="international" {{ (old('type_option') ?: $user->type_option) === 'international' ? 'checked' : '' }}>
                            <label class="form-check-label" for="type_international">{{ translate('International') }}</label>
                        </div>
                        @error('type_option')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr>

                {{-- Business Identification --}}
                @php
                    $domChoice = old('domestic_identity_selection') ?: (($details->gst_no ?? '') ? 'gst' : 'aadhaar_pan');
                @endphp
                <div class="row locality-domestic locality-block mb-2">
                    <div class="col-md-12">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input domestic-identity-toggle" type="radio" name="domestic_identity_selection" id="domestic_identity_gst" value="gst" {{ $domChoice === 'gst' ? 'checked' : '' }}>
                            <label class="form-check-label" for="domestic_identity_gst">{{ translate('GST') }}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input domestic-identity-toggle" type="radio" name="domestic_identity_selection" id="domestic_identity_aadhaar" value="aadhaar_pan" {{ $domChoice === 'aadhaar_pan' ? 'checked' : '' }}>
                            <label class="form-check-label" for="domestic_identity_aadhaar">{{ translate('Aadhaar / PAN') }}</label>
                        </div>
                    </div>
                </div>

                <div class="row locality-domestic locality-block mb-4">
                    <div class="col-md-4 domestic-gst-block">
                        <div class="form-group">
                            <label class="form-label" for="gst_no">{{ translate('GST No') }} *</label>
                            <input type="text" id="gst_no" name="gst_no" class="form-control"
                                  value="{{ old('gst_no') ?: ($details->gst_no ?: $user->gst_no) }}" placeholder="22AAAAA0000A1Z5">
                            @error('gst_no')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4 domestic-gst-block">
                        <div class="form-group">
                            <label class="form-label" for="gst_no_file">{{ translate('GST Document') }} *</label>
                            <input accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx" type="file" id="gst_no_file" name="gst_no_file" class="form-control" data-existing="{{ $details->gst_no_file ? '1' : '' }}">
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
                    <div class="col-md-4 domestic-gst-block">
                        <div class="form-group">
                            <label class="form-label" for="gstin_current_status">{{ translate('GSTIN Status / Current Status') }} *</label>
                            <input type="text" id="gstin_current_status" name="gstin_current_status" class="form-control"
                                   value="{{ old('gstin_current_status', $details->gstin_current_status) }}">
                            @error('gstin_current_status')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 domestic-aadhaar-block">
                        <!-- Aadhaar Number -->
                        <label class="form-label mb-0 mt-3" for="aadhaar_no_domestic">{{ translate('Aadhaar No') }} *</label>
                        <input type="text" id="aadhaar_no_domestic" name="aadhaar_no" class="form-control" value="{{ old('aadhaar_no', $details->aadhaar_no) }}">
                        @error('aadhaar_no')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3 domestic-aadhaar-block">
                        <!-- Aadhaar Upload -->
                        <label class="form-label mb-0 mt-3" for="aadhaar_no_file">{{ translate('Aadhaar Upload') }} *</label>
                        <input accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx" type="file" id="aadhaar_no_file" name="aadhaar_no_file" class="form-control m-0" data-existing="{{ $details->aadhaar_no_file ? '1' : '' }}">
                        @if (!empty($details->aadhaar_no_file))
                            <small class="d-block mt-1">
                                <a href="{{ asset(custom_file($details->aadhaar_no_file)) }}" target="_blank">
                                    {{ translate('Current Aadhaar file') }}
                                </a>
                            </small>
                        @endif
                        @error('aadhaar_no_file')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 domestic-aadhaar-block">
                        <!-- PAN Number -->
                        <label class="form-label mb-0 mt-3" for="pan_no_domestic">{{ translate('PAN No') }} *</label>
                        <input type="text" id="pan_no_domestic" name="pan_no" class="form-control" value="{{ old('pan_no', $details->pan_no) }}">
                        @error('pan_no')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3 domestic-aadhaar-block">
                        <!-- PAN Upload -->
                        <label class="form-label mb-0 mt-3" for="pan_no_file">{{ translate('PAN Upload') }} *</label>
                        <input accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx" type="file" id="pan_no_file" name="pan_no_file" class="form-control m-0" data-existing="{{ !empty($details->pan_no_file) ? '1' : '' }}" >
                        @if (!empty($details->pan_no_file))
                            <small class="d-block mt-1">
                                <a href="{{ asset(custom_file($details->pan_no_file)) }}" target="_blank">
                                    {{ translate('Current PAN file') }}
                                </a>
                            </small>
                        @endif
                        @error('pan_no_file')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row locality-international locality-block mb-2">
                    <div class="col-md-12">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input intl-identity-toggle" type="radio" name="international_identity_selection" id="international_identity_iec" value="iec" {{ ($details->iec_no ?: null) ? 'checked' : '' }}>
                            <label class="form-check-label" for="international_identity_iec">{{ translate('IEC') }}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input intl-identity-toggle" type="radio" name="international_identity_selection" id="international_identity_passport" value="passport" {{ ($details->iec_no ?: null) ? '' : 'checked' }}>
                            <label class="form-check-label" for="international_identity_passport">{{ translate('Passport') }}</label>
                        </div>
                    </div>
                </div>

                <div class="row locality-international locality-block mb-4">
                    <div class="col-md-4 intl-iec-block">
                        <div class="form-group">
                            <label class="form-label" for="iec_no">{{ translate('IEC No') }} *</label>
                            <input type="text" id="iec_no" name="iec_no" class="form-control" value="{{ old('iec_no', $details->iec_no) }}" placeholder="1234567890">
                            @error('iec_no')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4 intl-iec-block">
                        <div class="form-group">
                            <label class="form-label" for="iec_no_file">{{ translate('IEC Document') }}</label>
                            <input type="file" id="iec_no_file" name="iec_no_file" class="form-control" data-existing="{{ !empty($details->iec_no_file) ? '1' : '' }}" >
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
                    <div class="col-md-4 intl-iec-block">
                        <div class="form-group">
                            <label class="form-label" for="uin_current_status">{{ translate('UIN Status / Current Status') }} *</label>
                            <input type="text" id="uin_current_status" name="uin_current_status" class="form-control" value="{{ old('uin_current_status', $details->uin_current_status) }}">
                            @error('uin_current_status')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6 intl-passport-block mb-3">
                        <label class="form-label mt-3" for="passport_no">{{ translate('Passport No') }}</label>

                        <input type="text" id="passport_no" name="passport_no" class="form-control {{ $errors->has('passport_no') ? 'is-invalid' : '' }}" value="{{ old('passport_no', $details->passport_no) }}" maxlength="20" placeholder="{{ translate('Enter passport number') }}" aria-describedby="passportHelp">
                        @error('passport_no')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 intl-passport-block mb-3">
                        <!-- File input -->
                        <label class="form-label mt-3" for="passport_no_file">{{ translate('Passport Upload') }}</label>
                        <div class="input-group">
                            <input accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx" type="file" id="passport_no_file" name="passport_no_file" class="form-control {{ $errors->has('passport_no_file') ? 'is-invalid' : '' }}" accept=".pdf,image/*" data-existing="{{ !empty($details->passport_no_file) ? '1' : '' }}" aria-describedby="passportFileHelp" >
                            <button type="button" class="btn btn-outline-secondary" id="passportFileReset" style="display:none;">
                            {{ translate('Remove') }}
                            </button>
                        </div>

                        @if (!empty($details->passport_no_file))
                            <div class="mt-2" id="currentPassportFile">
                            <small class="d-block">
                                <a href="{{ asset(custom_file($details->passport_no_file)) }}" target="_blank" rel="noopener">{{ translate('Current Passport file') }} </a>
                                {{-- <span class="badge bg-secondary ms-2">{{ pathinfo($details->passport_no_file, PATHINFO_BASENAME) }}</span> --}}
                            </small>
                            </div>
                        @else
                            <div class="mt-2" id="currentPassportFile" style="display:none;"></div>
                        @endif
                        <div id="passportFileHelp" class="text-muted form-text">{{ translate('Accepted: PDF or image. Max size: 5MB.') }}</div>
                        @error('passport_no_file')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Business core --}}
                <div class="row business-requires-gst">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="registration_date">{{ translate('Registration Date') }} *</label>
                            <input type="date" id="registration_date" name="registration_date" class="form-control" required
                                   value="{{ old('registration_date', $details->registration_date) }}">
                            @error('registration_date')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="const_of_business">{{ translate('Constitution of Business') }} *</label>
                            <input type="text" id="const_of_business" name="const_of_business" class="form-control" required
                                   value="{{ old('const_of_business', $details->const_of_business) }}">
                            @error('const_of_business')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="con_person_name">{{ translate('Concerned Person Name') }} *</label>
                            <input type="text" id="con_person_name" name="con_person_name" class="form-control" required
                                   value="{{ old('con_person_name', $details->con_person_name) }}">
                            @error('con_person_name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="company_name">{{ translate('Company Name') }} *</label>
                            <input type="text" id="company_name" name="company_name" class="form-control" required
                                   value="{{ old('company_name', $details->company_name) }}">
                            @error('company_name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Business Address --}}
                <div class="row business-requires-gst">
                    <div class="col-md-12">
                        <h5 class="mb-3">{{ translate('Business Address') }}</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="street_add_first_business">{{ translate('Street Address 1') }} *</label>
                        <input type="text" name="street_add_first_business" id="street_add_first_business" class="form-control" required
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
                        <input type="text" name="locality_land_mark_business" id="locality_land_mark_business" class="form-control" required
                               value="{{ old('locality_land_mark_business', $details->locality_land_mark_business) }}">
                        @error('locality_land_mark_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="village_business">{{ translate('Village') }} *</label>
                        <input type="text" name="village_business" id="village_business" class="form-control" required
                               value="{{ old('village_business', $details->village_business) }}">
                        @error('village_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="post_business">{{ translate('Post') }} *</label>
                        <input type="text" name="post_business" id="post_business" class="form-control" required
                               value="{{ old('post_business', $details->post_business) }}">
                        @error('post_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="district_business">{{ translate('District') }} *</label>
                        <input type="text" name="district_business" id="district_business" class="form-control" required
                               value="{{ old('district_business', $details->district_business) }}">
                        @error('district_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="country_id_business">{{ translate('Country') }} *</label>
                        <select name="country_id_business" id="country_id_business" class="form-control aiz-selectpicker" data-live-search="true" required>
                            <option value="">{{ translate('Select Country') }}</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" {{ (string) (old('country_id_business') ?: ($details->country_id_business ?: $user->country)) === (string) $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('country_id_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="state_id_business">{{ translate('State') }} *</label>
                        <input type="text" name="state_id_business" id="state_id_business" class="form-control" value="{{ old('state_id_business') ?: ($details->state_id_business ?: $user->state) }}" required>
                        @error('state_id_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="city_id_business">{{ translate('City') }} *</label>
                        <input type="text" name="city_id_business" id="city_id_business" class="form-control" value="{{ old('city_id_business') ?: ($details->city_id_business ?: $user->city) }}" required>
                        @error('city_id_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="pincode_business">{{ translate('Pincode') }} *</label>
                        <input type="text" name="pincode_business" id="pincode_business" class="form-control" onchange="pincode_info(this);" required value="{{ old('pincode_business') ?: ($details->pincode_business ?: $user->postal_code) }}">
                        @error('pincode_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="country_code_business">{{ translate('Country Code') }} *</label>
                        <input type="text" name="country_code_business" id="country_code_business" class="form-control" required
                               value="{{ old('country_code_business') ?: ($details->country_code_business ?: $user->country) }}">
                        @error('country_code_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>
                @php
                    // fallback default dial
                    $defaultDial = $details->country_code_business ?? '91';

                    // Primary business phone
                    if (old('country_code_phone_code_business') !== null || old('phone_business') !== null) {
                        // if old separate fields exist combine them to parse
                        $primRaw = (old('country_code_phone_code_business', '') !== '' ? old('country_code_phone_code_business') . '-' : '')
                                . old('phone_business', '');
                    } else {
                        $primRaw = optional($details)->prim_mobile_no_business ?? ($user->phone ?? '');
                    }
                    $prim = parse_phone_number($primRaw, $defaultDial);
                    $primVisible = old('phone_business', $prim['number'] ?: ($user->phone ?? ''));

                    // Alternate business phone
                    if (old('country_code_alternate_mob_no_business') !== null || old('alternate_mob_no_business') !== null) {
                        $altRaw = (old('country_code_alternate_mob_no_business', '') !== '' ? old('country_code_alternate_mob_no_business') . '-' : '')
                                . old('alternate_mob_no_business', '');
                    } else {
                        $altRaw = optional($details)->alt_mobile_no_business ?? '';
                    }
                    $alt = parse_phone_number($altRaw, $defaultDial);

                    // Primary WhatsApp
                    if (old('country_code_whats_app_no_business') !== null || old('whats_app_no_business') !== null) {
                        $waRaw = (old('country_code_whats_app_no_business', '') !== '' ? old('country_code_whats_app_no_business') . '-' : '')
                            . old('whats_app_no_business', '');
                    } else {
                        $waRaw = optional($details)->prim_whats_app_no_business ?? '';
                    }
                    $wa = parse_phone_number($waRaw, $defaultDial);
                    if (!$wa['dial']) { $wa['dial'] = '91'; }

                    // Alternate WhatsApp
                    if (old('country_code_alternate_whats_app_no_business') !== null || old('alternate_whats_app_no_business') !== null) {
                        $altWaRaw = (old('country_code_alternate_whats_app_no_business', '') !== '' ? old('country_code_alternate_whats_app_no_business') . '-' : '')
                                . old('alternate_whats_app_no_business', '');
                    } else {
                        $altWaRaw = optional($details)->alternate_whats_app_no_business ?? '';
                    }
                    $altWa = parse_phone_number($altWaRaw, $defaultDial);
                @endphp

                {{-- Business Contact --}}
                <div class="row business-requires-gst">
                    <div class="col-md-12">
                        <h5 class="mb-3">{{ translate('Business Contact') }}</h5>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="phone_business">{{ translate('Primary Mobile') }} *</label>
                        <input type="text" id="phone_business" name="phone_business" class="form-control" required
                            value="{{ $primVisible }}" />

                        @error('phone_business') <div class="text-danger small">{{ $message }}</div> @enderror

                        {{-- meta & dial hidden fields --}}
                        <input type="hidden" name="phone_code_meta" value="{{ old('phone_code_meta', $details->prim_mobile_no_business_meta ?? '') }}">
                        <input type="hidden" name="country_code_phone_code_business" value="{{ old('country_code_phone_code_business', $prim['dial']) }}">
                        <input type="hidden" name="phone_business_meta" value="{{ old('phone_business_meta', $details->prim_mobile_no_business_meta ?? '') }}">
                        <input type="hidden" name="country_code_phone_business" value="{{ old('country_code_phone_business', $prim['dial']) }}">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="alternate_mob_no_business">{{ translate('Alternate Mobile (Contact Person)') }}</label>
                        <input type="text" id="alternate_mob_no_business" name="alternate_mob_no_business" class="form-control"
                            value="{{ old('alternate_mob_no_business', $alt['number']) }}">

                        @error('alternate_mob_no_business') <div class="text-danger small">{{ $message }}</div> @enderror

                        <input type="hidden" name="alternate_mob_no_business_meta" value="{{ old('alternate_mob_no_business_meta', $details->alt_mobile_no_business_meta ?? '') }}">
                        <input type="hidden" name="country_code_alternate_mob_no_business" value="{{ old('country_code_alternate_mob_no_business', $alt['dial']) }}">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="whats_app_no_business">{{ translate('Primary WhatsApp') }} *</label>
                        <input type="text" id="whats_app_no_business" name="whats_app_no_business" class="form-control" required
                            value="{{ old('whats_app_no_business', $wa['number']) }}">

                        @error('whats_app_no_business') <div class="text-danger small">{{ $message }}</div> @enderror

                        <input type="hidden" name="whats_app_no_business_meta" value="{{ old('whats_app_no_business_meta', $details->prim_whats_app_no_business_meta ?? '') }}">
                        <input type="hidden" name="country_code_whats_app_no_business" value="{{ old('country_code_whats_app_no_business', $wa['dial']) }}">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="alternate_whats_app_no_business">{{ translate('Alternate WhatsApp') }}</label>
                        <input type="text" id="alternate_whats_app_no_business" name="alternate_whats_app_no_business" class="form-control"
                            value="{{ old('alternate_whats_app_no_business', $altWa['number']) }}">

                        @error('alternate_whats_app_no_business') <div class="text-danger small">{{ $message }}</div> @enderror

                        <input type="hidden" name="alternate_whats_app_no_business_meta" value="{{ old('alternate_whats_app_no_business_meta', $details->alternate_whats_app_no_business_meta ?? '') }}">
                        <input type="hidden" name="country_code_alternate_whats_app_no_business" value="{{ old('country_code_alternate_whats_app_no_business', $altWa['dial']) }}">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="prim_email_business">{{ translate('Primary Email') }} *</label>
                        <input type="email" id="prim_email_business" name="prim_email_business" class="form-control" required
                               value="{{ old('prim_email_business') ?: ($details->prim_email_business ?: $user->email) }}">
                        @error('prim_email_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="alt_email_business">{{ translate('Alternate Email') }}</label>
                        <input type="email" id="alt_email_business" name="alt_email_business" class="form-control"
                               value="{{ old('alt_email_business') ?: $details->alt_email_business }}">
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
                <div class="row business-requires-gst">
                    <div class="col-md-12">
                        <h5 class="mb-3">{{ translate('Business Bank Details') }}</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="bank_name_business">{{ translate('Bank Name') }} *</label>
                        <input type="text" id="bank_name_business" name="bank_name_business" class="form-control" required
                               value="{{ old('bank_name_business', $details->bank_name_business) }}">
                        @error('bank_name_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="account_no_business">{{ translate('Account No') }} *</label>
                        <input type="text" id="account_no_business" name="account_no_business" class="form-control" required
                               value="{{ old('account_no_business', $details->account_no_business) }}">
                        @error('account_no_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="account_name_business">{{ translate('Account Name') }} *</label>
                        <input type="text" id="account_name_business" name="account_name_business" class="form-control" required
                               value="{{ old('account_name_business', $details->account_name_business) }}">
                        @error('account_name_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="branch_code_business">{{ translate('Branch Code') }} *</label>
                        <input type="text" id="branch_code_business" name="branch_code_business" class="form-control" required
                               value="{{ old('branch_code_business', $details->branch_code_business) }}">
                        @error('branch_code_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="branch_name_business">{{ translate('Branch Name') }} *</label>
                        <input type="text" id="branch_name_business" name="branch_name_business" class="form-control" required
                               value="{{ old('branch_name_business', $details->branch_name_business) }}">
                        @error('branch_name_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="branch_address_business">{{ translate('Branch Address') }} *</label>
                        <input type="text" id="branch_address_business" name="branch_address_business" class="form-control" required
                               value="{{ old('branch_address_business', $details->branch_address_business) }}">
                        @error('branch_address_business') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ifsc_code_business">{{ translate('IFSC Code') }} *</label>
                        <input type="text" id="ifsc_code_business" name="ifsc_code_business" class="form-control" required
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
                        <input accept=".jpg,.jpeg,.png,.webp" type="file" id="photo_file" name="photo_file" class="form-control" {{ $details->photo_file ? '' : 'required' }}>
                        @if (!empty($details->photo_file))
                            <small class="d-block mt-1"><a href="{{ asset(custom_file($details->photo_file)) }}" target="_blank">{{ translate('Current file') }}</a></small>
                        @endif
                        @error('photo_file') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="name_personal">{{ translate('Name') }} *</label>
                        <input type="text" id="name_personal" name="name_personal" class="form-control" value="{{ old('name_personal', $details->name ?: $user->name) }}" required>
                        @error('name_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="father_name">{{ translate('Father Name') }} *</label>
                        <input type="text" id="father_name" name="father_name" class="form-control" value="{{ old('father_name', $details->father_name) }}" required>
                        @error('father_name') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="dob">{{ translate('Date of Birth') }} *</label>
                        <input type="date" id="dob" name="dob" class="form-control" value="{{ old('dob', $details->dob) }}" required>
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
                        <input type="text" id="street_add_first_personal" name="street_add_first_personal" class="form-control" value="{{ old('street_add_first_personal', $details->street_add_first) }}" required>
                        @error('street_add_first_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="street_add_sec_personal">{{ translate('Street Address 2') }}</label>
                        <input type="text" id="street_add_sec_personal" name="street_add_sec_personal" class="form-control" value="{{ old('street_add_sec_personal', $details->street_add_sec) }}">
                        @error('street_add_sec_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="locality_land_mark_personal">{{ translate('Locality / Landmark') }} *</label>
                        <input type="text" id="locality_land_mark_personal" name="locality_land_mark_personal" class="form-control" value="{{ old('locality_land_mark_personal', $details->locality_land_mark) }}" required>
                        @error('locality_land_mark_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="village_personal">{{ translate('Village') }} *</label>
                        <input type="text" id="village_personal" name="village_personal" class="form-control" value="{{ old('village_personal', $details->village) }}" required>
                        @error('village_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="post_personal">{{ translate('Post') }} *</label>
                        <input type="text" id="post_personal" name="post_personal" class="form-control" value="{{ old('post_personal', $details->post) }}" required>
                        @error('post_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="district_personal">{{ translate('District') }} *</label>
                        <input type="text" id="district_personal" name="district_personal" class="form-control" value="{{ old('district_personal', $details->district) }}" required>
                        @error('district_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="country_id_personal">{{ translate('Country') }} *</label>
                        <select name="country_id_personal" id="country_id_personal" class="form-control aiz-selectpicker" data-live-search="true" required>
                            <option value="">{{ translate('Select Country') }}</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" {{ (string) (old('country_id_personal') ?: ($details->country_id ?: $user->country)) === (string) $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('country_id_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="state_id_personal">{{ translate('State') }} *</label>
                        <input type="text" name="state_id_personal" id="state_id_personal" class="form-control" value="{{ old('state_id_personal', $details->state_id) }}" required>
                        @error('state_id_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="city_id_personal">{{ translate('City') }} *</label>
                        <input type="text" name="city_id_personal" id="city_id_personal" class="form-control" value="{{ old('city_id_personal', $details->city_id) }}" required>
                        @error('city_id_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="pincode_personal">{{ translate('Pincode') }} *</label>
                        <input type="text" id="pincode_personal" name="pincode_personal" class="form-control" onchange="pincode_info(this);" value="{{ old('pincode_personal', $details->pincode ?: $user->postal_code) }}" required>
                        @error('pincode_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="country_code_personal">{{ translate('Country Code') }} *</label>
                        <input type="text" id="country_code_personal" name="country_code_personal" class="form-control" value="{{ old('country_code_personal', $details->country_code) }}" required>
                        @error('country_code_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Personal Contact --}}
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="mb-3">{{ translate('Personal Contact') }}</h5>
                    </div>
                    @php
                        $primPersonalDial = old('country_code_phone_code_personal')
                            ?: ((isset($details->prim_mobile_no) && str_contains($details->prim_mobile_no, '-'))
                                ? explode('-', $details->prim_mobile_no, 2)[0]
                                : ($details->country_code ?? ''));
                        $altPersonalDial = old('country_code_alternate_mob_no_personal')
                            ?: ((isset($details->alt_mobile_no) && str_contains($details->alt_mobile_no, '-'))
                                ? explode('-', $details->alt_mobile_no, 2)[0]
                                : ($details->country_code ?? ''));
                        $waPersonalDial = old('country_code_whats_app_no_personal')
                            ?: ((isset($details->prim_whats_app_no) && str_contains($details->prim_whats_app_no, '-'))
                                ? explode('-', $details->prim_whats_app_no, 2)[0]
                                : ($details->country_code ?? ''));
                        $altWaPersonalDial = old('country_code_alternate_whats_app_no_personal')
                            ?: ((isset($details->alt_whats_app_no) && str_contains($details->alt_whats_app_no, '-'))
                                ? explode('-', $details->alt_whats_app_no, 2)[0]
                                : ($details->country_code ?? ''));
                        // reuse parse helper for personal contacts
                        $personalDefaultDial = $details->country_code ?? '91';
                        $personalPrimRaw = (old('country_code_phone_code_personal') || old('phone_personal'))
                            ? (old('country_code_phone_code_personal', '') !== '' ? old('country_code_phone_code_personal') . '-' : '') . old('phone_personal', '')
                            : (optional($details)->prim_mobile_no ?? ($user->phone ?? ''));
                        $personalPrim = parse_phone_number($personalPrimRaw, $personalDefaultDial);

                        $personalAltRaw = (old('country_code_alternate_mob_no_personal') || old('alternate_mob_no_personal'))
                            ? (old('country_code_alternate_mob_no_personal', '') !== '' ? old('country_code_alternate_mob_no_personal') . '-' : '') . old('alternate_mob_no_personal', '')
                            : (optional($details)->alt_mobile_no ?? '');
                        $personalAlt = parse_phone_number($personalAltRaw, $personalDefaultDial);

                        $personalWaRaw = (old('country_code_whats_app_no_personal') || old('whats_app_no_personal'))
                            ? (old('country_code_whats_app_no_personal', '') !== '' ? old('country_code_whats_app_no_personal') . '-' : '') . old('whats_app_no_personal', '')
                            : (optional($details)->prim_whats_app_no ?? '');
                        $personalWa = parse_phone_number($personalWaRaw, $personalDefaultDial);

                        $personalAltWaRaw = (old('country_code_alternate_whats_app_no_personal') || old('alternate_whats_app_no_personal'))
                            ? (old('country_code_alternate_whats_app_no_personal', '') !== '' ? old('country_code_alternate_whats_app_no_personal') . '-' : '') . old('alternate_whats_app_no_personal', '')
                            : (optional($details)->alt_whats_app_no ?? '');
                        $personalAltWa = parse_phone_number($personalAltWaRaw, $personalDefaultDial);
                    @endphp
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="phone_personal">{{ translate('Primary Mobile') }} *</label>
                        <input type="text" id="phone_personal" name="phone_personal" class="form-control" required 
                            value="{{ $personalPrim['number'] }}">
                        @error('phone_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                        <input type="hidden" name="phone_personal_meta" value="{{ old('phone_personal_meta', $details->prim_mobile_no_meta) }}">
                        <input type="hidden" name="country_code_phone_code_personal" value="{{ $personalPrim['dial'] }}">
                        <input type="hidden" name="country_code_phone_personal" value="{{ $personalPrim['dial'] }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="alternate_mob_no_personal">{{ translate('Alternate Mobile') }}</label>
                        <input type="text" id="alternate_mob_no_personal" name="alternate_mob_no_personal" class="form-control"
                               value="{{ $personalAlt['number'] }}">
                        @error('alternate_mob_no_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                        <input type="hidden" name="alternate_mob_no_personal_meta" value="{{ old('alternate_mob_no_personal_meta', $details->alt_mobile_no_meta) }}">
                        <input type="hidden" name="country_code_alternate_mob_no_personal" value="{{ $personalAlt['dial'] }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="whats_app_no_personal">{{ translate('Primary WhatsApp') }} *</label>
                        <input type="text" id="whats_app_no_personal" name="whats_app_no_personal" class="form-control" required
                               value="{{ $personalWa['number'] }}">
                        @error('whats_app_no_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                        <input type="hidden" name="whats_app_no_personal_meta" value="{{ old('whats_app_no_personal_meta', $details->prim_whats_app_no_meta) }}">
                        <input type="hidden" name="country_code_whats_app_no_personal" value="{{ $personalWa['dial'] }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="alternate_whats_app_no_personal">{{ translate('Alternate WhatsApp') }}</label>
                        <input type="text" id="alternate_whats_app_no_personal" name="alternate_whats_app_no_personal" class="form-control"
                               value="{{ $personalAltWa['number'] }}">
                        @error('alternate_whats_app_no_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                        <input type="hidden" name="alternate_whats_app_no_personal_meta" value="{{ old('alternate_whats_app_no_personal_meta', $details->alternate_whats_app_no_meta) }}">
                        <input type="hidden" name="country_code_alternate_whats_app_no_personal" value="{{ $personalAltWa['dial'] }}">
                    </div>
                    {{-- {{ dd(old('prim_email_personal'), $details->prim_email_personal, $user->email) }} --}}
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="prim_email_personal">{{ translate('Primary Email') }} *</label>
                        <input type="email" id="prim_email_personal" name="prim_email_personal" class="form-control" value="{{ old('prim_email_personal') ?: $details->prim_email_personal ?: $user->email }}" required>
                        @error('prim_email_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="alt_email_personal">{{ translate('Alternate Email') }}</label>
                        <input type="email" id="alt_email_personal" name="alt_email_personal" class="form-control" value="{{ old('alt_email_personal') ?: $details->alt_email_personal }}">
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
                        <input type="text" id="bank_name_personal" name="bank_name_personal" class="form-control" value="{{ old('bank_name_personal', $details->bank_name_personal) }}" required>
                        @error('bank_name_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="account_no_personal">{{ translate('Account No') }} *</label>
                        <input type="text" id="account_no_personal" name="account_no_personal" class="form-control" value="{{ old('account_no_personal', $details->account_no_personal) }}" required>
                        @error('account_no_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="account_name_personal">{{ translate('Account Name') }} *</label>
                        <input type="text" id="account_name_personal" name="account_name_personal" class="form-control" value="{{ old('account_name_personal', $details->account_name_personal) }}" required>
                        @error('account_name_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="branch_code_personal">{{ translate('Branch Code') }} *</label>
                        <input type="text" id="branch_code_personal" name="branch_code_personal" class="form-control" value="{{ old('branch_code_personal', $details->branch_code_personal) }}" required>
                        @error('branch_code_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="branch_name_personal">{{ translate('Branch Name') }} *</label>
                        <input type="text" id="branch_name_personal" name="branch_name_personal" class="form-control" value="{{ old('branch_name_personal', $details->branch_name_personal) }}" required>
                        @error('branch_name_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="branch_address_personal">{{ translate('Branch Address') }} *</label>
                        <input type="text" id="branch_address_personal" name="branch_address_personal" class="form-control" value="{{ old('branch_address_personal', $details->branch_address_personal) }}" required>
                        @error('branch_address_personal') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="ifsc_code_personal">{{ translate('IFSC Code') }} *</label>
                        <input type="text" id="ifsc_code_personal" name="ifsc_code_personal" class="form-control" value="{{ old('ifsc_code_personal', $details->ifsc_code_personal) }}" required>
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
                                        <div id="license-required-error" class="text-danger small mt-1 d-none">{{ translate('At least one license / registration entry is required.') }}</div>
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
                                                        <input type="text" id="{{ $key }}" name="{{ $key }}" class="form-control mb-2" value="{{ $val }}" required>
                                                        <input accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx" type="file" name="{{ $key }}_file" class="form-control" {{ empty($item['file']) ? 'required' : '' }}>
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
        function toggleLocalityBlocks() {
            const selected = document.querySelector('input[name="type_option"]:checked')?.value || 'domestic';
            document.querySelectorAll('.locality-block').forEach(block => block.classList.add('d-none'));
            if (selected === 'domestic') {
                document.querySelectorAll('.locality-domestic').forEach(block => block.classList.remove('d-none'));
            } else {
                document.querySelectorAll('.locality-international').forEach(block => block.classList.remove('d-none'));
            }
        }

        function removeAllRequiredFlags() {
            document.querySelectorAll('#edit-customer-form [required]').forEach(el => el.removeAttribute('required'));
        }

        function toggleIdentityBlocks() {
            const domChoice = document.querySelector('input[name="domestic_identity_selection"]:checked')?.value || 'gst';
            document.querySelectorAll('.domestic-gst-block').forEach(el => el.classList.toggle('d-none', domChoice !== 'gst'));
            document.querySelectorAll('.domestic-aadhaar-block').forEach(el => el.classList.toggle('d-none', domChoice !== 'aadhaar_pan'));

            const intlChoice = document.querySelector('input[name="international_identity_selection"]:checked')?.value || 'iec';
            document.querySelectorAll('.intl-iec-block').forEach(el => el.classList.toggle('d-none', intlChoice !== 'iec'));
            document.querySelectorAll('.intl-passport-block').forEach(el => el.classList.toggle('d-none', intlChoice !== 'passport'));

            // Toggle required attributes to mirror registration flow
            const setReq = (selector, on) => {
                const el = document.querySelector(selector);
                if (!el) return;
                el.removeAttribute('required'); // requested: no required on edit form
            };
            const hasFileOrExisting = (selector) => {
                const el = document.querySelector(selector);
                if (!el) return false;
                return !!(el.getAttribute('data-existing') || (el.files && el.files.length));
            };

            // Domestic: GST vs Aadhaar/PAN
            setReq('#gst_no', domChoice === 'gst');
            setReq('#gstin_current_status', domChoice === 'gst');
            const hasGstFile = hasFileOrExisting('#gst_no_file');
            setReq('#gst_no_file', domChoice === 'gst' && !hasGstFile);

            setReq('#aadhaar_no_domestic', domChoice !== 'gst');
            const hasAadhaarFile = hasFileOrExisting('input[name="aadhaar_no_file"]');
            setReq('input[name="aadhaar_no_file"]', domChoice !== 'gst' && !hasAadhaarFile);
            setReq('#pan_no_domestic', domChoice !== 'gst');
            const hasPanFile = hasFileOrExisting('input[name="pan_no_file"]');
            setReq('input[name="pan_no_file"]', domChoice !== 'gst' && !hasPanFile);

            // International: IEC vs Passport
            setReq('#iec_no', intlChoice === 'iec');
            setReq('#uin_current_status', intlChoice === 'iec');
            const hasIecFile = hasFileOrExisting('#iec_no_file');
            setReq('#iec_no_file', intlChoice === 'iec' && !hasIecFile);

            setReq('#passport_no', intlChoice !== 'iec');
            const hasPassportFile = hasFileOrExisting('input[name="passport_no_file"]');
            setReq('input[name="passport_no_file"]', intlChoice !== 'iec' && !hasPassportFile);

            // Business sections are captured only when GST (domestic) or IEC (international) is chosen
            const typeOptionSelected = document.querySelector('input[name="type_option"]:checked')?.value || 'domestic';
            const requireBusiness = (typeOptionSelected === 'domestic' && domChoice === 'gst') || (typeOptionSelected === 'international' && intlChoice === 'iec');
            document.querySelectorAll('.business-requires-gst').forEach(section => {
                section.classList.toggle('d-none', !requireBusiness);
                section.querySelectorAll('input, select, textarea').forEach(el => {
                    el.removeAttribute('required');
                });
            });
        }

        // Fallback: define intil_input (intlTelInput initializer) if not already available from registration flow
        if (typeof intil_input !== 'function') {
            function intil_input(name) {
                const inputElement = document.querySelector(`#${name}`);
                if (!inputElement || typeof intlTelInput !== 'function') {
                    return;
                }

                const iti = intlTelInput(inputElement, {
                    separateDialCode: true,
                    formatOnDisplay: false, // avoid auto-spacing/formatting
                    nationalMode: false,
                    utilsScript: "{{ static_asset('assets/js/intlTelutils.js') }}?1590403638580",
                    onlyCountries: @php echo json_encode(get_active_countries()->pluck('code')->toArray()) @endphp,
                    customPlaceholder: function (selectedCountryPlaceholder, selectedCountryData) {
                        if (selectedCountryData.iso2 === 'bd') {
                            return "01xxxxxxxxx";
                        }
                        return selectedCountryPlaceholder;
                    }
                });

                const phoneMetaField = document.querySelector(`input[name="country_code_${name}"]`);
                const metaField = document.querySelector(`input[name="${name}_meta"]`);
                const countryData = window.intlTelInputGlobals ? window.intlTelInputGlobals.getCountryData() : [];
                const existingDial = phoneMetaField ? phoneMetaField.value : '';
                const existingIso = metaField ? metaField.value : '';

                // Prefer ISO (disambiguates shared dial codes like +1 for US vs AS)
                if (existingIso) {
                    try { iti.setCountry(existingIso); } catch (e) {}
                } else if (existingDial) {
                    const matched = countryData.find(c => String(c.dialCode) === String(existingDial));
                    if (matched) {
                        try { iti.setCountry(matched.iso2); } catch (e) {}
                    }
                } else {
                    iti.setCountry('{{ old('type_option', $user->type_option ?: 'domestic') === 'international' ? 'us' : 'in' }}');
                }

                const selected = iti.getSelectedCountryData();
                if (phoneMetaField) phoneMetaField.value = selected.dialCode;
                if (metaField) metaField.value = selected.iso2;

                inputElement.addEventListener("countrychange", function () {
                    const updated = iti.getSelectedCountryData();
                    if (phoneMetaField) phoneMetaField.value = updated.dialCode;
                    if (metaField) metaField.value = updated.iso2;
                });

                return iti;
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
                                <input type="text" name="${def.name}" class="form-control" placeholder="${def.label}" required>
                            </div>
                            <div class="form-group mb-0">
                                <label>{{ translate('Upload') }} ${def.label}</label>
                                <input type="file" name="${def.file}" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx" required>
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

        
        /* ----------------------------- Pincode ----------------------- */

        let debounceTimeout;

        function pincode_info(inputEl){
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(() => {
                const $input = inputEl ? $(inputEl) : null;
                const postalCode = ($input ? $input.val() : '').trim();
                if (!$input || !postalCode) {
                    return;
                }

                const isBusiness = $input.attr('id') === 'pincode_business';
                const $city = isBusiness ? $('#city_id_business') : $('#city_id_personal');
                const $state = isBusiness ? $('#state_id_business') : $('#state_id_personal');

                $.ajax({
                    url: 'https://secure.geonames.org/postalCodeSearchJSON',
                    dataType: 'json',
                    data: {
                        postalcode: postalCode,
                        country: '',
                        username: 'umair.makent'
                    },
                    success: function (data) {
                        if (data.postalCodes && data.postalCodes.length > 0) {
                            const entry = data.postalCodes[0];
                            $city.val(entry.placeName || entry.adminName2 || '');
                            $state.val(entry.adminName1 || '');
                        }
                    }
                });
            }, 300);
        }

        // Cache original required flags so we can toggle business sections cleanly
        function cacheOriginalRequiredFlags() {
            document.querySelectorAll('input, select, textarea').forEach(el => {
                if (el.hasAttribute('required') && !el.dataset.originalRequired) {
                    el.dataset.originalRequired = '1';
                }
            });
        }
        cacheOriginalRequiredFlags();
        removeAllRequiredFlags();
        
        // Initialize intlTelInput on edit form and sync values to hidden fields used by controller
        function initEditIntlTel(name, codeTargets = [], metaTargets = []) {
            if (typeof intil_input !== 'function') return;
            const inputEl = document.getElementById(name);
            if (!inputEl) return;

            const iti = intil_input(name);
            if (!iti) return;

            const setTargets = (dial, iso) => {
                codeTargets.forEach(sel => {
                    const t = document.querySelector(sel);
                    if (t) t.value = dial || '';
                });
                metaTargets.forEach(sel => {
                    const t = document.querySelector(sel);
                    if (t) t.value = iso || '';
                });
            };

            // Prefer ISO from backend (disambiguates shared dial codes), fall back to dial, else current selection
            const existingDial = document.querySelector(`input[name="country_code_${name}"]`)?.value || '';
            const existingIso = document.querySelector(`input[name="${name}_meta"]`)?.value || '';
            const initialData = existingIso || existingDial ? { dialCode: existingDial, iso2: existingIso } : iti.getSelectedCountryData();
            if (initialData?.iso2) {
                try { iti.setCountry(initialData.iso2); } catch (e) { /* ignore */ }
            }
            const current = iti.getSelectedCountryData();
            setTargets(current?.dialCode, current?.iso2);

            inputEl.addEventListener('countrychange', () => {
                const data = iti.getSelectedCountryData();
                setTargets(data?.dialCode, data?.iso2);
            });
            inputEl.addEventListener('input', () => {
                inputEl.value = inputEl.value.replace(/\s+/g, '');
            });
        }

        function initIntlInputsEdit() {
            initEditIntlTel(
                'phone_business',
                ['input[name="country_code_phone_business"]', 'input[name="country_code_phone_code_business"]'],
                ['input[name="phone_business_meta"]', 'input[name="phone_code_meta"]']
            );
            initEditIntlTel(
                'alternate_mob_no_business',
                ['input[name="country_code_alternate_mob_no_business"]'],
                ['input[name="alternate_mob_no_business_meta"]']
            );
            initEditIntlTel(
                'whats_app_no_business',
                ['input[name="country_code_whats_app_no_business"]'],
                ['input[name="whats_app_no_business_meta"]']
            );
            initEditIntlTel(
                'alternate_whats_app_no_business',
                ['input[name="country_code_alternate_whats_app_no_business"]'],
                ['input[name="alternate_whats_app_no_business_meta"]']
            );

            initEditIntlTel(
                'phone_personal',
                ['input[name="country_code_phone_personal"]', 'input[name="country_code_phone_code_personal"]'],
                ['input[name="phone_personal_meta"]']
            );
            initEditIntlTel(
                'alternate_mob_no_personal',
                ['input[name="country_code_alternate_mob_no_personal"]'],
                ['input[name="alternate_mob_no_personal_meta"]']
            );
            initEditIntlTel(
                'whats_app_no_personal',
                ['input[name="country_code_whats_app_no_personal"]'],
                ['input[name="whats_app_no_personal_meta"]']
            );
            initEditIntlTel(
                'alternate_whats_app_no_personal',
                ['input[name="country_code_alternate_whats_app_no_personal"]'],
                ['input[name="alternate_whats_app_no_personal_meta"]']
            );
        }


        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('locality-toggle')) {
                toggleLocalityBlocks();
            }
            if (e.target.classList.contains('domestic-identity-toggle') || e.target.classList.contains('intl-identity-toggle')) {
                toggleIdentityBlocks();
            }
        });

        toggleLocalityBlocks();
        toggleIdentityBlocks();
        initIntlInputsEdit();
        AIZ.plugins.bootstrapSelect('refresh');
        initValidate('#edit-customer-form');

        // AJAX form submit to keep user on page and handle validation gracefully
        (function setupAjaxSubmit() {
            const form = document.getElementById('edit-customer-form');
            if (!form || !window.fetch) return;
            const errorBox = document.getElementById('form-error-box');
            const submitBtn = form.querySelector('button[type="submit"]');

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (typeof $ !== 'undefined' && typeof $(form).valid === 'function' && !$(form).valid()) {
                    return;
                }

                if (errorBox) {
                    errorBox.classList.add('d-none');
                    errorBox.innerHTML = '';
                }
                // clear previous field errors
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback.dynamic-error').forEach(el => el.remove());

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.dataset.originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '{{ translate('Saving...') }}';
                }

                const formData = new FormData(form);
                // Ensure method override is aligned with route
                formData.set('_method', 'PUT');

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(async (resp) => {
                    const contentType = resp.headers.get('content-type') || '';
                    let payload = null;
                    if (contentType.includes('application/json')) {
                        payload = await resp.json();
                    }
                    if (resp.ok && payload && payload.redirect_url) {
                        window.location.reload();
                        // window.location.href = payload.redirect_url;
                        return;
                    }
                    if (!resp.ok && payload && payload.errors) {
                        const entries = Object.entries(payload.errors);
                        entries.forEach(([key, msgs]) => {
                            const field = form.querySelector(`[name="${key}"]`);
                            if (field) {
                                field.classList.add('is-invalid');
                                const fb = document.createElement('div');
                                fb.className = 'invalid-feedback dynamic-error';
                                fb.innerHTML = msgs.join('<br>');
                                field.insertAdjacentElement('afterend', fb);
                            }
                        });
                        const messages = entries.map(([, msg]) => msg.join('<br>')).join('<br>');
                        if (errorBox && messages) {
                            errorBox.innerHTML = messages;
                            errorBox.classList.remove('d-none');
                            errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        return;
                    }
                    // fallback: if not JSON or other issue, do full submit
                    form.submit();
                }).catch(() => {
                    form.submit();
                }).finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = submitBtn.dataset.originalText || submitBtn.innerHTML;
                    }
                });
            });
        })();

        
        
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

        // Frontend guard: at least one license entry required
        // function hasAnyLicense() {
        //     const licenseKeys = Object.keys(licenseFieldMap);
        //     for (const key of licenseKeys) {
        //         const textVal = (document.querySelector(`input[name="${key}"]`)?.value || '').trim();
        //         const fileEl = document.querySelector(`input[name="${key}_file"]`);
        //         const filePresent = fileEl && (fileEl.files?.length || fileEl.getAttribute('data-existing'));
        //         if (textVal || filePresent) {
        //             return true;
        //         }
        //     }
        //     return false;
        // }

        // const licenseError = document.getElementById('license-required-error');
        // const editForm = document.getElementById('edit-customer-form');
        // if (editForm) {
        //     editForm.addEventListener('submit', function (e) {
        //         if (!hasAnyLicense()) {
        //             e.preventDefault();
        //             if (licenseError) {
        //                 licenseError.classList.remove('d-none');
        //                 licenseError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        //             }
        //         } else if (licenseError) {
        //             licenseError.classList.add('d-none');
        //         }
        //     });
        // }

        // Initial sync for options based on existing data
        refreshLicenseSelect();
    </script>
@endsection
