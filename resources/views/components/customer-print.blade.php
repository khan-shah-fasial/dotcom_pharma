@props(['user' => null, 'user2' => null])

@php
    $hasFullProfile = $user && !empty($user->type_option);
@endphp

<div class="customer-print">
    @if ($hasFullProfile)
        <div class="card mb-3">
            <div class="card-body py-3">
                <h6 class="section-title mb-3">Customer Overview</h6>
                <div class="row g-2">
                    <div class="col-md-4 col-sm-6 col-12">
                        <div class="field-label">Name</div>
                        <div class="field-value">{{ $user->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-4 col-sm-6 col-12">
                        <div class="field-label">Customer Type</div>
                        <div class="field-value text-capitalize">{{ $user->type_option ?? '-' }}</div>
                    </div>
                    <div class="col-md-4 col-sm-6 col-12">
                        <div class="field-label">Company Name</div>
                        <div class="field-value">{{ $user->company_name ?? '-' }}</div>
                    </div>
                    <div class="col-md-4 col-sm-6 col-12">
                        <div class="field-label">Concerned Person</div>
                        <div class="field-value">{{ $user->con_person_name ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $businessIdentity =
                $user->type_option === 'domestic'
                    ? [
                        'GST No' => $user->gst_no ?? '-',
                        'GSTIN Status / Current Status' => $user->gstin_current_status ?? '-',
                    ]
                    : [
                        'IEC.No' => $user->iec_no ?? '-',
                        'UIN Status / Current Status' => $user->uin_current_status ?? '-',
                    ];
            $businessInfo = array_merge(
                $businessIdentity,
                [
                    'Registration Date' => $user->registration_date ?? '-',
                    'Constitution of Business' => $user->const_of_business ?? '-',
                    'Website' => $user->website_business ?? '-',
                ],
            );
            $businessAddress = [
                'Street Address 1' => $user->street_add_first_business ?? '-',
                'Street Address 2' => $user->street_add_sec_business ?? '-',
                'Locality / Landmark' => $user->locality_land_mark_business ?? '-',
                'Village' => $user->village_business ?? '-',
                'Post' => $user->post_business ?? '-',
                'City' => $user->city_id_business ?? '-',
                'State' => $user->state_id_business ?? '-',
                'District' => $user->district_business ?? '-',
                'Pincode' => $user->pincode_business ?? '-',
                'Country' => getParticularData('countries', 'name', (int) ($user->country_id_business ?? 0)) ?? '-',
            ];
            $businessContact = [
                'Country Code' => $user->country_code_business ?? '-',
                'Primary Mobile' => $user->prim_mobile_no_business ?? '-',
                'Primary Whatsapp' => $user->prim_whats_app_no_business ?? '-',
                'Alternate Mobile' => $user->alt_mobile_no_business ?? '-',
                'Alternate Whatsapp' => $user->alternate_whats_app_no_business ?? '-',
                'Primary Email' => $user->prim_email_business ?? '-',
                'Alternate Email' => $user->alt_email_business ?? '-',
            ];
            $businessBank = [
                'Bank Name' => $user->bank_name_business ?? '-',
                'Account Name' => $user->account_name_business ?? '-',
                'Account No' => $user->account_no_business ?? '-',
                'Branch Name' => $user->branch_name_business ?? '-',
                'Branch Address' => $user->branch_address_business ?? '-',
                'Branch Code' => $user->branch_code_business ?? '-',
                'IFSC Code' => $user->ifsc_code_business ?? '-',
                'MICR Code' => $user->micr_code_business ?? '-',
                'AD Code' => $user->ad_code_business ?? '-',
            ];

            $personalIdentity =
                $user->type_option === 'domestic'
                    ? [
                        'Aadhaar No' => $user->aadhaar_no ?? '-',
                        'PAN No' => $user->pan_no ?? '-',
                    ]
                    : [
                        'Passport No' => $user->passport_no ?? '-',
                    ];

            $personalInfo = array_merge(
                $personalIdentity,
                [
                    'Name' => $user->name ?? '-',
                    'Father Name' => $user->father_name ?? '-',
                    'D.O.B' => $user->dob ?? '-',
                ],
            );
            $personalAddress = [
                'Street Address 1' => $user->street_add_first ?? '-',
                'Street Address 2' => $user->street_add_sec ?? '-',
                'Locality / Landmark' => $user->locality_land_mark ?? '-',
                'Village' => $user->village ?? '-',
                'Post' => $user->post ?? '-',
                'City' => $user->city_id ?? '-',
                'State' => $user->state_id ?? '-',
                'District' => $user->district ?? '-',
                'Pincode' => $user->pincode ?? '-',
                'Country' => getParticularData('countries', 'name', (int) ($user->country_id ?? 0)) ?? '-',
            ];
            $personalContact = [
                'Country Code' => $user->country_code ?? '-',
                'Primary Mobile' => $user->prim_mobile_no ?? '-',
                'Primary Whatsapp' => $user->prim_whats_app_no ?? '-',
                'Alternate Mobile' => $user->alt_mobile_no ?? '-',
                'Alternate Whatsapp' => $user->alt_whats_app_no ?? '-',
                'Primary Email' => $user->prim_email_personal ?? '-',
                'Alternate Email' => $user->alt_email_personal ?? '-',
            ];
            $personalBank = [
                'Bank Name' => $user->bank_name_personal ?? '-',
                'Account Name' => $user->account_name_personal ?? '-',
                'Account No' => $user->account_no_personal ?? '-',
                'Branch Name' => $user->branch_name_personal ?? '-',
                'Branch Address' => $user->branch_address_personal ?? '-',
                'Branch Code' => $user->branch_code_personal ?? '-',
                'IFSC Code' => $user->ifsc_code_personal ?? '-',
                'MICR Code' => $user->micr_code_personal ?? '-',
                'AD Code' => $user->ad_code_personal ?? '-',
            ];
            $licenseFields = [
                'Drug / Pharmacy Licence No 1' => $user->d_l_no_1 ?? '-',
                'Drug / Pharmacy Licence No 2' => $user->d_l_no_2 ?? '-',
                'Drug / Pharmacy Licence No 3' => $user->d_l_no_3 ?? '-',
                'Doctor / Pharmacist / Hospital Reg. No' => $user->doctor_hospital_reg_no ?? '-',
                'Dairy / Trust / NGO / Other Reg. No' => $user->dairy_trust_ngo_reg_no ?? '-',
                'CC / MDL Registration No' => $user->cc_mdl_reg_no ?? '-',
                'Other Registration No' => $user->other_reg_no ?? '-',
            ];
        @endphp

        <div class="card mb-3">
            <div class="card-body py-3">
                <h6 class="section-title mb-3">Business Details</h6>
                <div class="row g-2">
                    @foreach ($businessInfo as $label => $value)
                        <div class="col-md-4 col-sm-6 col-12">
                            <div class="field-label">{{ $label }}</div>
                            <div class="field-value">{{ $value !== '' ? $value : '-' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body py-3">
                <h6 class="section-title mb-3">Business Address & Contact</h6>
                <div class="row g-2">
                    @foreach ($businessAddress as $label => $value)
                        <div class="col-md-4 col-sm-6 col-12">
                            <div class="field-label">{{ $label }}</div>
                            <div class="field-value">{{ $value !== '' ? $value : '-' }}</div>
                        </div>
                    @endforeach
                    @foreach ($businessContact as $label => $value)
                        <div class="col-md-4 col-sm-6 col-12">
                            <div class="field-label">{{ $label }}</div>
                            <div class="field-value">{{ $value !== '' ? $value : '-' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body py-3">
                <h6 class="section-title mb-3">Business Bank</h6>
                <div class="row g-2">
                    @foreach ($businessBank as $label => $value)
                        <div class="col-md-4 col-sm-6 col-12">
                            <div class="field-label">{{ $label }}</div>
                            <div class="field-value">{{ $value !== '' ? $value : '-' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body py-3">
                <h6 class="section-title mb-3">Personal Details</h6>
                <div class="row g-2">
                    @foreach ($personalInfo as $label => $value)
                        <div class="col-md-4 col-sm-6 col-12">
                            <div class="field-label">{{ $label }}</div>
                            <div class="field-value">{{ $value !== '' ? $value : '-' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body py-3">
                <h6 class="section-title mb-3">Personal Address & Contact</h6>
                <div class="row g-2">
                    @foreach ($personalAddress as $label => $value)
                        <div class="col-md-4 col-sm-6 col-12">
                            <div class="field-label">{{ $label }}</div>
                            <div class="field-value">{{ $value !== '' ? $value : '-' }}</div>
                        </div>
                    @endforeach
                    @foreach ($personalContact as $label => $value)
                        <div class="col-md-4 col-sm-6 col-12">
                            <div class="field-label">{{ $label }}</div>
                            <div class="field-value">{{ $value !== '' ? $value : '-' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body py-3">
                <h6 class="section-title mb-3">Personal Bank</h6>
                <div class="row g-2">
                    @foreach ($personalBank as $label => $value)
                        <div class="col-md-4 col-sm-6 col-12">
                            <div class="field-label">{{ $label }}</div>
                            <div class="field-value">{{ $value !== '' ? $value : '-' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body py-3">
                <h6 class="section-title mb-3">License Details</h6>
                <div class="row g-2">
                    @foreach ($licenseFields as $label => $value)
                        <div class="col-md-4 col-sm-6 col-12">
                            <div class="field-label">{{ $label }}</div>
                            <div class="field-value">{{ $value !== '' ? $value : '-' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="card mb-3">
            <div class="card-body py-3">
                <h6 class="section-title mb-3">Customer Overview</h6>
                <div class="row g-2">
                    <div class="col-sm-6 col-12">
                        <div class="field-label">Name</div>
                        <div class="field-value">{{ $user2->name ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6 col-12">
                        <div class="field-label">Email</div>
                        <div class="field-value">{{ $user2->email ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6 col-12">
                        <div class="field-label">Phone</div>
                        <div class="field-value">{{ $user2->phone ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
