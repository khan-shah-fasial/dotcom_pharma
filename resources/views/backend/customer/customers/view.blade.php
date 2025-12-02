@extends('backend.layouts.app')

@section('content')
    <div class="container my-4">
        @if (!empty($user->type_option))
            <div class="card">

                <div class="container my-3 mx-2">
                    <h3> View Details of Customer - {{ $user->name ?? '-' }} </h3>
                    <hr>
                    <br>

                    {{-- @php
                        $limit = (int) ($user2->credit_limit ?? 0);
                        $remainRaw = (int) ($user2->credit_remain ?? 0);
                        $remain = max(0, min($remainRaw, $limit)); // clamp 0..limit
                        $used = max(0, $limit - $remain);
                        $pctUsed = $limit > 0 ? round(($used / $limit) * 100) : 0;
                        $pctRemain = 100 - $pctUsed;
                        $isActive = (int) ($user2->credit_status ?? 0) === 1;
                        $fmt = fn($n) => number_format((int) $n); // simple integer format
                    @endphp

                    <div class="card p-3 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0">Credit Overview</h6>
                            @if ($isActive)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-secondary">Not Active</span>
                            @endif
                        </div>

                        @if (!$isActive)
                            <div class="alert alert-warning py-2 mb-3">
                                Credit not active.
                            </div>
                        @endif

                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <div class="small text-muted">Credit Limit</div>
                                <div class="h5 mb-0">₹ {{ $fmt($limit) }}</div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Credit Remaining</div>
                                <div class="h5 mb-0 {{ $isActive ? '' : 'text-muted' }}">₹ {{ $fmt($remain) }}</div>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Used</span>
                                <span>{{ $pctUsed }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar {{ $isActive ? 'bg-info' : 'bg-secondary' }}" role="progressbar"
                                    style="width: {{ $pctUsed }}%;" aria-valuenow="{{ $pctUsed }}"
                                    aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between small mt-1 text-muted">
                                <span>₹ {{ $fmt($used) }} used</span>
                                <span>₹ {{ $fmt($remain) }} left</span>
                            </div>
                        </div>
                    </div> --}}

                    <div class="card p-3">
                        <h5> Business Details </h5>
                        <hr>
                        <br>
                        <div class="row">

                            @if ($user->type_option == 'domestic')
                                <div class="col-md-4 mb-4">

                                    <div class="form-group">
                                        <label class="form-label" for="name">GST No</label>
                                        <p>{{ $user->gst_no ?? '-' }}</p>
                                        <br>

                                        @if (!empty($user->gst_no_file))
                                            <a href="{{ asset(custom_file($user->gst_no_file)) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                view
                                            </a>
                                        @endif
                                    </div>

                                </div>
                            @else
                                <div class="col-md-4 mb-4">

                                    <div class="form-group">
                                        <label class="form-label" for="name">IEC.No</label>
                                        <p>{{ $user->iec_no ?? '-' }}</p>
                                        <br>

                                        @if (!empty($user->iec_no_file))
                                            <a href="{{ asset(custom_file($user->iec_no_file)) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                view
                                            </a>
                                        @endif
                                    </div>

                                </div>
                            @endif

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Registration Date</label>
                                    <p>{{ $user->registration_date ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Constitution of Business</label>
                                    <p>{{ $user->const_of_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">
                                @if ($user->type_option == 'domestic')
                                    <div class="form-group">
                                        <label class="form-label" for="name">GSTIN Status / Current Status</label>
                                        <p>{{ $user->gstin_current_status ?? '-' }}</p>
                                    </div>
                                @else
                                    <div class="form-group">
                                        <label class="form-label" for="name">UIN Status / Current Status</label>
                                        <p>{{ $user->uin_current_status ?? '-' }}</p>
                                    </div>
                                @endif

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Concerned Person Name</label>
                                    <p>{{ $user->con_person_name ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Company Name</label>
                                    <p>{{ $user->company_name ?? '-' }}</p>
                                </div>

                            </div>


                            <div class="col-md-12 mb-4">
                                <h5> Address </h5>
                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Street Address 1</label>
                                    <p>{{ $user->street_add_first_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Street Address 2</label>
                                    <p>{{ $user->street_add_sec_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Locality/Suburb/Land Mark</label>
                                    <p>{{ $user->locality_land_mark_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Village</label>
                                    <p>{{ $user->village_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Post</label>
                                    <p>{{ $user->post_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Post</label>
                                    <p>{{ $user->post_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="country__code">Country</label>
                                    <p> {{ getParticularData('countries', 'name', $user->country_id_business ?? 0) ?? '-' }}
                                    </p>

                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="country__code">state</label>
                                    {{-- <p> {{ getParticularData('states','name',$user->state_id_business ?? 0) ?? "-" }}</p> --}}
                                    <p> {{ $user->state_id_business ?? '-' }}</p>
                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="country__code">City</label>
                                    {{-- <p> {{ getParticularData('cities','name',$user->city_id_business ?? 0) ?? "-" }}</p> --}}
                                    <p> {{ $user->city_id_business ?? '-' }}</p>
                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">District</label>
                                    <p> {{ $user->district_business ?? '-' }}</p>
                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Pincode</label>
                                    <p> {{ $user->pincode_business ?? '-' }}</p>
                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Country Code</label>
                                    <p> {{ $user->country_code_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label text-capitalize" for="pincode">Mobile No</label>
                                    <p> {{ $user->prim_mobile_no_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Whatapp No</label>
                                    <p> {{ $user->prim_whats_app_no_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Alternate Mobile (Contact Person)</label>
                                    <p> {{ $user->alt_mobile_no_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Alternate Whatapp No</label>
                                    <p> {{ $user->alternate_whats_app_no_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Primary E-Mail</label>
                                    <p> {{ $user->prim_email_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Alternate E-Mail</label>
                                    <p> {{ $user->alt_email_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Website</label>
                                    <p> {{ $user->website_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-12 mb-4">
                                <h5> Bank Details </h5>
                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Bank Name</label>
                                    <p> {{ $user->bank_name_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Account No</label>
                                    <p> {{ $user->account_no_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Account Name</label>
                                    <p> {{ $user->account_name_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Branch Code</label>
                                    <p> {{ $user->branch_code_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Branch Name</label>
                                    <p> {{ $user->branch_name_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Branch Address</label>
                                    <p> {{ $user->branch_address_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">IFSC Code</label>
                                    <p> {{ $user->ifsc_code_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">MICR Code</label>
                                    <p> {{ $user->micr_code_business ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">AD code</label>
                                    <p> {{ $user->ad_code_business ?? '-' }}</p>
                                </div>

                            </div>

                        </div>
                    </div>



                    <div class="card p-3">
                        <h5> Personal Details </h5>
                        <hr>
                        <br>
                        <div class="row">

                            @if ($user->type_option == 'domestic')
                                <div class="col-md-4 mb-4">

                                    <div class="form-group">
                                        <label class="form-label" for="name">Aadhaar.No</label>
                                        <p>{{ $user->aadhaar_no ?? '-' }}</p>
                                        <br>

                                        @if (!empty($user->aadhaar_no_file))
                                            <a href="{{ asset(custom_file($user->aadhaar_no_file)) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                view
                                            </a>
                                        @endif
                                    </div>

                                </div>
                                <div class="col-md-4 mb-4">

                                    <div class="form-group">
                                        <label class="form-label" for="name">PAN.No</label>
                                        <p>{{ $user->pan_no ?? '-' }}</p>
                                        <br>

                                        @if (!empty($user->pan_no_file))
                                            <a href="{{ asset(custom_file($user->pan_no_file)) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                view
                                            </a>
                                        @endif
                                    </div>

                                </div>
                            @else
                                <div class="col-md-4 mb-4">

                                    <div class="form-group">
                                        <label class="form-label" for="name">Passport No</label>
                                        <p>{{ $user->passport_no ?? '-' }}</p>
                                        <br>

                                        @if (!empty($user->passport_no_file))
                                            <a href="{{ asset(custom_file($user->passport_no_file)) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                view
                                            </a>
                                        @endif
                                    </div>

                                </div>
                            @endif

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Photo Upload</label>
                                    <br>

                                    @if (!empty($user->photo_file))
                                        <a href="{{ asset(custom_file($user->photo_file)) }}" target="_blank"
                                            class="btn btn-sm btn-outline-primary">
                                            view
                                        </a>
                                    @endif
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Name</label>
                                    <p>{{ $user->name ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Father Name</label>
                                    <p>{{ $user->father_name ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">D.O.B</label>
                                    <p>{{ $user->dob ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-12 mb-4">
                                <h5> Address </h5>
                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Street Address 1</label>
                                    <p>{{ $user->street_add_first ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Street Address 2</label>
                                    <p>{{ $user->street_add_sec ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Locality/Suburb/Land Mark</label>
                                    <p>{{ $user->locality_land_mark ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Village</label>
                                    <p>{{ $user->village ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Post</label>
                                    <p>{{ $user->post ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="country__code">Country</label>
                                    <p> {{ getParticularData('countries', 'name', $user->country_id ?? 0) ?? '-' }}</p>

                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="country__code">state</label>
                                    {{-- <p> {{ getParticularData('states','name',$user->state_id ?? 0) ?? "-" }}</p> --}}
                                    <p> {{ $user->state_id ?? '-' }}</p>
                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="country__code">City</label>
                                    {{-- <p> {{ getParticularData('cities','name',$user->city_id ?? 0) ?? "-" }}</p> --}}
                                    <p> {{ $user->city_id ?? '-' }}</p>
                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">District</label>
                                    <p> {{ $user->district ?? '-' }}</p>
                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Pincode</label>
                                    <p> {{ $user->pincode ?? '-' }}</p>
                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Country Code</label>
                                    <p> {{ $user->country_code ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Primary Mobile</label>
                                    <p> {{ $user->prim_mobile_no ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Primary Whatapp No</label>
                                    <p> {{ $user->prim_whats_app_no ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Alternate Mobile</label>
                                    <p> {{ $user->alt_mobile_no ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Alternate Whatapp No</label>
                                    <p> {{ $user->alt_whats_app_no ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Primary E-Mail</label>
                                    <p> {{ $user->prim_email_personal ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Alternate E-Mail</label>
                                    <p> {{ $user->alt_email_personal ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-12 mb-4">
                                <h5> Personal Bank Details </h5>
                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Bank Name</label>
                                    <p> {{ $user->bank_name_personal ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Account No</label>
                                    <p> {{ $user->account_no_personal ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Account Name</label>
                                    <p> {{ $user->account_name_personal ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Branch Code</label>
                                    <p> {{ $user->branch_code_personal ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Branch Name</label>
                                    <p> {{ $user->branch_name_personal ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">Branch Address</label>
                                    <p> {{ $user->branch_address_personal ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">IFSC Code</label>
                                    <p> {{ $user->ifsc_code_personal ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">MICR Code</label>
                                    <p> {{ $user->micr_code_personal ?? '-' }}</p>
                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="pincode">AD code</label>
                                    <p> {{ $user->ad_code_personal ?? '-' }}</p>
                                </div>

                            </div>

                        </div>
                    </div>


                    <div class="card p-3">
                        <h5> License Details </h5>
                        <hr>
                        <br>
                        <div class="row">

                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Drug / Pharmacy Licence No 1 </label>
                                    <p>{{ $user->d_l_no_1 ?? '-' }}</p>
                                    <br>

                                    @if (!empty($user->d_l_no_1_file))
                                        <a href="{{ asset(custom_file($user->d_l_no_1_file)) }}" target="_blank"
                                            class="btn btn-sm btn-outline-primary">
                                            view
                                        </a>
                                    @endif
                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Drug / Pharmacy Licence No 2 </label>
                                    <p>{{ $user->d_l_no_2 ?? '-' }}</p>
                                    <br>

                                    @if (!empty($user->d_l_no_2_file))
                                        <a href="{{ asset(custom_file($user->d_l_no_2_file)) }}" target="_blank"
                                            class="btn btn-sm btn-outline-primary">
                                            view
                                        </a>
                                    @endif
                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Drug / Pharmacy Licence No 3 </label>
                                    <p>{{ $user->d_l_no_3 ?? '-' }}</p>
                                    <br>

                                    @if (!empty($user->d_l_no_3_file))
                                        <a href="{{ asset(custom_file($user->d_l_no_3_file)) }}" target="_blank"
                                            class="btn btn-sm btn-outline-primary">
                                            view
                                        </a>
                                    @endif
                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Doctor / Pharmacist / Hospital Reg.No
                                    </label>
                                    <p>{{ $user->doctor_hospital_reg_no ?? '-' }}</p>
                                    <br>

                                    @if (!empty($user->doctor_hospital_reg_no_file))
                                        <a href="{{ asset(custom_file($user->doctor_hospital_reg_no_file)) }}"
                                            target="_blank" class="btn btn-sm btn-outline-primary">
                                            view
                                        </a>
                                    @endif
                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Dairy / Trust / NGO / Other Reg.No </label>
                                    <p>{{ $user->dairy_trust_ngo_reg_no ?? '-' }}</p>
                                    <br>

                                    @if (!empty($user->dairy_trust_ngo_reg_no_file))
                                        <a href="{{ asset(custom_file($user->dairy_trust_ngo_reg_no_file)) }}"
                                            target="_blank" class="btn btn-sm btn-outline-primary">
                                            view
                                        </a>
                                    @endif
                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">CC / MDL Registration No </label>
                                    <p>{{ $user->cc_mdl_reg_no ?? '-' }}</p>
                                    <br>

                                    @if (!empty($user->cc_mdl_reg_no_file))
                                        <a href="{{ asset(custom_file($user->cc_mdl_reg_no_file)) }}" target="_blank"
                                            class="btn btn-sm btn-outline-primary">
                                            view
                                        </a>
                                    @endif
                                </div>

                            </div>
                            <div class="col-md-4 mb-4">

                                <div class="form-group">
                                    <label class="form-label" for="name">Other Registration No </label>
                                    <p>{{ $user->other_reg_no ?? '-' }}</p>
                                    <br>

                                    @if (!empty($user->other_reg_no_file))
                                        <a href="{{ asset(custom_file($user->other_reg_no_file)) }}" target="_blank"
                                            class="btn btn-sm btn-outline-primary">
                                            view
                                        </a>
                                    @endif
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- <div class="row">
                    <div>
                        @can('ban_customer')
                            @if ($user->approval_status != 1)
                                <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm" onclick="show_Approval_model({{ $user->id }});" title="{{ translate('Approval this Customer') }}">
                                    <i class="las la-thumbs-up"></i>
                                </a>
                                @else
                                <a href="#" class="btn btn-soft-success btn-icon btn-circle btn-sm" onclick="show_Approval_model({{ $user->id }});" title="{{ translate('Not Approve this Customer') }}">
                                    <i class="las la-thumbs-down"></i>
                                </a>
                            @endif
                        @endcan
                    </div>
                </div> --}}

                </div>
            </div>
        @else
            <h3> View Details of Customer {{ $user2->name ?? '-' }} </h3>
            <div class="col-md-4 mb-4">
                <div class="form-group">
                    <label class="form-label" for="name">Email ID</label>
                    <p>{{ $user2->email ?? '-' }}</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="form-group">
                    <label class="form-label" for="name">Phone</label>
                    <p>{{ $user2->phone ?? '-' }}</p>
                </div>
            </div>

        @endif
    </div>



    {{-- - //------------------------------ approval modal -----------------------// -- --}}

    {{-- <div class="modal fade" id="approval_model" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel_phone"
    aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content py-3">
                <div class="modal-header">
                    <div class="heading">
                        <h5 class="modal-title" id="exampleModalLabel_phone">Approval Statusr</h5>
                    </div>
                    <div class="purple_btn_close">
                        <button type="button" class="close p-1 px-3" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true" style="font-size: 24px;">&times;</span>
                        </button>
                    </div>
                </div>
                <form id="approval-status-model" action="{{ url(route('customers.approval')) }}"
                    method="post">
                    @csrf

                    <input type="hidden" name="id">

                    <div class="modal-body">
                            <div class="form-group">
                                <label for="recipient-name" class="col-form-label form-label">Note :</label>
                                <textarea type="text" class="form-control" id="note" name="note"></textarea>
                            </div>
                    </div>
                    <div class="modal-footer">
                        <div class="blue_btn">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                        <div class="purple_btn">
                            <button type="submit" class="btn btn-primary">Proceed</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div> --}}

    {{-- - //------------------------------ approval modal -----------------------// -- --}}


    @include('components.location-info', ['data' => getStoredIPLocation('users', $user->user_id ?? $user2->id)])

@endsection

@section('script')
    <script type="text/javascript">
        // // Global scope
        function show_Approval_model(id) {
            // // Set the value of the hidden input field
            $('#approval_model input[name="id"]').val(id);

            // Show the modal
            $('#approval_model').modal('show');
        }
    </script>
@endsection
