@extends('backend.layouts.app')

@section('content')
    <style>
        .business-customer-table-wrap {
            overflow-y: hidden;
        }

        .business-customer-table-wrap.business-customer-table-wrap-short {
            min-height: 430px;
            padding-bottom: 230px;
        }

        .drop-down-text-icon-business .dropdown-menu {
            max-height: 360px;
            overflow-y: auto;
        }
    </style>

    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="align-items-center">
            <h1 class="h3">{{ translate('All Business Customers') }}</h1>
        </div>
        @can('add_customer')
            <div class="col text-right">
                <a href="{{ route('customers.business.create') }}" class="btn btn-circle btn-info">
                    <span>{{ translate('Add New Customer') }}</span>
                </a>
            </div>
        @endcan
    </div>


    <div class="card">
        <form class="" id="sort_customers" action="" method="GET">
            @php
                $filtersApplied = $sort_search || $company_name || $account_number || $gst_no || $verification_status || $filter_transport || $hasBusinessLocationFilters || $hasPersonalLocationFilters;
            @endphp
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <div class="mb-2">
                    <h5 class="mb-0 h6">{{ translate('Customers') }}</h5>
                    @if ($filtersApplied)
                        <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap align-items-center">
                    <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal"
                        data-target="#customerFilterModal">
                        {{ translate('Open Filters') }}
                    </button>
                    <a class="btn btn-danger mb-2" href="{{ route('customers.business') }}">{{ translate('Reset') }}</a>
                </div>
            </div>

            {{-- Filter Modal --}}
            <div class="modal fade" id="customerFilterModal" tabindex="-1" role="dialog"
                aria-labelledby="customerFilterModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="customerFilterModalLabel">{{ translate('Filter Customers') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row gutters-5">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="search">{{ translate('Search') }}</label>
                                    <input type="text" class="form-control" id="search"
                                        name="search" value="{{ $sort_search ?? '' }}"
                                        placeholder="{{ translate('Type email or name & Phone & Telephone No Enter') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="company_name">{{ translate('Company Name') }}</label>
                                    <input type="text" class="form-control" id="company_name"
                                        name="company_name" value="{{ $company_name ?? '' }}"
                                        placeholder="{{ translate('Type Company Name') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="account_number">{{ translate('Account Number') }}</label>
                                    <input type="text" class="form-control" id="account_number"
                                        name="account_number" value="{{ $account_number ?? '' }}"
                                        placeholder="{{ translate('Account Number') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="gst_no">{{ translate('GST / IEC / Aadhaar / Passport / PAN') }}</label>
                                    <input type="text" class="form-control" id="gst_no"
                                        name="gst_no" value="{{ $gst_no ?? '' }}"
                                        placeholder="{{ translate(' GST  / IEC / Aadhar / Passport / PAN ') }}">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="verification_status">{{ translate('Approval Status') }}</label>
                                    <select class="form-control aiz-selectpicker" id="verification_status"
                                        name="verification_status" data-live-search="true">
                                        <option value="">{{ translate('All') }}</option>
                                        <option value="verified" {{ $verification_status === 'verified' ? 'selected' : '' }}>
                                            {{ translate('Verified') }}</option>
                                        <option value="un_verified" {{ $verification_status === 'un_verified' ? 'selected' : '' }}>
                                            {{ translate('Unverified') }}</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="transport">{{ translate('Transport') }}</label>
                                    <select class="form-control aiz-selectpicker" id="transport" name="transport"
                                        data-live-search="true">
                                        <option value="">{{ translate('All') }}</option>
                                        @foreach ($transportList as $transport)
                                            <option value="{{ $transport }}" {{ $filter_transport === $transport ? 'selected' : '' }}>
                                            {{ ucwords($transport) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <h6 class="mb-3">{{ translate('Business Location') }}</h6>
                                        <div class="row gutters-5">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="pincode_business">{{ translate('Pincode') }}</label>
                                                <input type="text" class="form-control" id="pincode_business"
                                                    name="pincode_business" value="{{ $businessPincode ?? '' }}"
                                                    placeholder="{{ translate('Pincode') }}">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="business_country_id">{{ translate('Country') }}</label>
                                                <select class="form-control aiz-selectpicker js-location-filter" id="business_country_id"
                                                    name="business_country_id" data-live-search="true">
                                                    <option value="">{{ translate('All') }}</option>
                                                    @foreach ($businessCountryOptions as $c)
                                                        <option value="{{ $c['id'] }}" {{ (string) ($businessCountryId ?? '') === (string) $c['id'] ? 'selected' : '' }}>
                                                            {{ $c['name'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="business_state_id">{{ translate('State') }}</label>
                                                <select class="form-control aiz-selectpicker js-location-filter" id="business_state_id"
                                                    name="business_state_id" data-selected="{{ $businessStateId ?? '' }}"
                                                    data-live-search="true">
                                                    <option value="">{{ translate('All') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="business_district">{{ translate('District') }}</label>
                                                <select class="form-control aiz-selectpicker js-location-filter" id="business_district"
                                                    name="business_district" data-selected="{{ $businessDistrict ?? '' }}"
                                                    data-live-search="true">
                                                    <option value="">{{ translate('All') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="business_city_id">{{ translate('City') }}</label>
                                                <select class="form-control aiz-selectpicker js-location-filter" id="business_city_id"
                                                    name="business_city_id" data-selected="{{ $businessCityId ?? '' }}"
                                                    data-live-search="true">
                                                    <option value="">{{ translate('All') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="business_post">{{ translate('Post') }}</label>
                                                <select class="form-control aiz-selectpicker js-location-filter" id="business_post"
                                                    name="business_post" data-selected="{{ $businessPost ?? '' }}"
                                                    data-live-search="true">
                                                    <option value="">{{ translate('All') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-0">
                                                <label class="form-label" for="business_village">{{ translate('Village') }}</label>
                                                <select class="form-control aiz-selectpicker js-location-filter" id="business_village"
                                                    name="business_village" data-selected="{{ $businessVillage ?? '' }}"
                                                    data-live-search="true">
                                                    <option value="">{{ translate('All') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3 h-100">
                                        <h6 class="mb-3">{{ translate('Personal Location') }}</h6>
                                        <div class="row gutters-5">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="pincode">{{ translate('Pincode') }}</label>
                                                <input type="text" class="form-control" id="pincode"
                                                    name="pincode" value="{{ $personalPincode ?? '' }}"
                                                    placeholder="{{ translate('Pincode') }}">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="personal_country_id">{{ translate('Country') }}</label>
                                                <select class="form-control aiz-selectpicker js-location-filter" id="personal_country_id"
                                                    name="personal_country_id" data-live-search="true">
                                                    <option value="">{{ translate('All') }}</option>
                                                    @foreach ($personalCountryOptions as $c)
                                                        <option value="{{ $c['id'] }}" {{ (string) ($personalCountryId ?? '') === (string) $c['id'] ? 'selected' : '' }}>
                                                            {{ $c['name'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="personal_state_id">{{ translate('State') }}</label>
                                                <select class="form-control aiz-selectpicker js-location-filter" id="personal_state_id"
                                                    name="personal_state_id" data-selected="{{ $personalStateId ?? '' }}"
                                                    data-live-search="true">
                                                    <option value="">{{ translate('All') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="personal_district">{{ translate('District') }}</label>
                                                <select class="form-control aiz-selectpicker js-location-filter" id="personal_district"
                                                    name="personal_district" data-selected="{{ $personalDistrict ?? '' }}"
                                                    data-live-search="true">
                                                    <option value="">{{ translate('All') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="personal_city_id">{{ translate('City') }}</label>
                                                <select class="form-control aiz-selectpicker js-location-filter" id="personal_city_id"
                                                    name="personal_city_id" data-selected="{{ $personalCityId ?? '' }}"
                                                    data-live-search="true">
                                                    <option value="">{{ translate('All') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="personal_post">{{ translate('Post') }}</label>
                                                <select class="form-control aiz-selectpicker js-location-filter" id="personal_post"
                                                    name="personal_post" data-selected="{{ $personalPost ?? '' }}"
                                                    data-live-search="true">
                                                    <option value="">{{ translate('All') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-0">
                                                <label class="form-label" for="personal_village">{{ translate('Village') }}</label>
                                                <select class="form-control aiz-selectpicker js-location-filter" id="personal_village"
                                                    name="personal_village" data-selected="{{ $personalVillage ?? '' }}"
                                                    data-live-search="true">
                                                    <option value="">{{ translate('All') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Close') }}</button>
                    <button type="button" class="btn btn-primary btn-apply-filters">{{ translate('Apply Filters') }}</button>
                </div>
            </div>
        </div>
    </div>

            <div class="card-body">
                @php
                    $columnGroups = [
                        [['sr_no', 'Sr.No']],
                        [['crm_id', 'Account Number']],
                        [
                            ['company_name', 'Company Name'],
                            ['person_name', 'Person Name'],
                            ['city', 'City'],
                        ],
                        [
                            ['village', 'Village'],
                            ['post', 'Post'],
                            ['district', 'District'],
                        ],
                        [
                            ['state', 'State'],
                            ['pincode', 'Pincode'],
                            ['country', 'Country'],
                        ],
                        [
                            ['mobile', 'Mobile'],
                            ['alt_mobile', 'Alt.Mobile'],
                            ['whatsapp', 'Wahtsup'],
                        ],
                        [
                            ['alt_whatsapp', 'Alt.Whatsup'],
                            ['email', 'E-mail'],
                            ['alt_email', 'Alt-Email'],
                        ],
                        [
                            ['gst_no', 'GST.No'],
                            ['aadhaar_no', 'Adhar.No'],
                            ['pan_no', 'PAN.No'],
                        ],
                        [
                            ['approval_status', 'Approval Status'],
                            ['customer_role', 'Customer Role'],
                            ['current_status', 'Current Status'],
                        ],
                        [
                            ['credit_status', 'Credit Status'],
                            ['credit_days', 'Number of Days'],
                            ['credit_limit', 'Credit Limit'],
                        ],
                        [
                            ['transport', 'Transport'],
                            ['booked_to', 'Booked To'],
                            ['salesman', 'Salesman Name'],
                        ],
                        [
                            ['iec_no', 'IEC.No'],
                            ['passport_no', 'Passport.No'],
                        ],
                        [
                            ['dl1', 'Drug / Pharmacy Licence No 1'],
                            ['dl2', 'Drug / Pharmacy Licence No 2'],
                            ['dl3', 'Drug / Pharmacy Licence No 3'],
                        ],
                        [
                            ['doctor_hospital_reg_no', 'Doctor / Pharmacist / Hospital Reg. No'],
                            ['dairy_trust_ngo_reg_no', 'Dairy / Trust / NGO / Other Reg. No'],
                            ['other_registration_no', 'Other Registration No'],
                        ],
                    ];

                    $sortHeading = function ($key, $label) {
                        $active = request('sort_by', 'crm_id') === $key;
                        $nextOrder = $active && request('sort_order', 'asc') === 'asc' ? 'desc' : 'asc';
                        $url = route('customers.business', array_merge(request()->all(), [
                            'sort_by' => $key,
                            'sort_order' => $nextOrder,
                        ]));
                        $icon = $active
                            ? '<i class="las la-sort-amount-' . (request('sort_order', 'asc') === 'asc' ? 'up' : 'down') . '"></i>'
                            : '<i class="las la-sort"></i>';

                        return '<a href="' . e($url) . '" class="d-block text-primary text-nowrap font-weight-bold">' . e(translate($label)) . ' ' . $icon . '</a>';
                    };
                @endphp
                <div class="table-responsive business-customer-table-wrap @if ($users->count() <= 2) business-customer-table-wrap-short @endif">
                    <table class="table table-bordered aiz-table mb-0">
                        <thead>
                            <tr>
                                @foreach ($columnGroups as $group)
                                    <th class="align-top text-center">
                                        @foreach ($group as $heading)
                                            {!! $sortHeading($heading[0], $heading[1]) !!}
                                        @endforeach
                                    </th>
                                @endforeach
                                <th>{{ translate('Options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $key => $user)
                                @if ($user != null)
                                    @php
                                        $details = $user->details;
                                        $stateId = $details?->state_id_business ?? $details?->state_id;
                                        $cityId = $details?->city_id_business ?? $details?->city_id;
                                        $countryId = $details?->country_id_business ?? $details?->country_id;
                                        $stateName = $stateId ? ($stateNames[$stateId] ?? $stateId) : null;
                                        $cityName = $cityId ? ($cityNames[$cityId] ?? $cityId) : null;
                                        $countryName = $countryId ? ($countryNames[$countryId] ?? $countryId) : null;
                                    @endphp
                                    <tr>
                                        <td>{{ $key + 1 + ($users->currentPage() - 1) * $users->perPage() }}</td>
                                        <td>{{ $details->crm_id ?? '-' }}</td>
                                        <td>
                                            <div>
                                                @if ($user->banned == 1)
                                                    <i class="fa fa-ban text-danger" aria-hidden="true"></i>
                                                @endif
                                                {{ $details->company_name ?? '-' }}
                                            </div>
                                            <div>{{ $details->name ?? $user->name ?? '-' }}</div>
                                            <div>{{ $cityName ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $details->village_business ?? ($details->village ?? '-') }}</div>
                                            <div>{{ $details->post_business ?? ($details->post ?? '-') }}</div>
                                            <div>{{ $details->district_business ?? ($details->district ?? '-') }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $stateName ?? '-' }}</div>
                                            <div>{{ $details->pincode_business ?? ($details->pincode ?? '-') }}</div>
                                            <div>{{ $countryName ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $details->prim_mobile_no_business ?? ($details->prim_mobile_no ?? ($user->phone ?? '-')) }}</div>
                                            <div>{{ $details->alt_mobile_no_business ?? ($details->alt_mobile_no ?? '-') }}</div>
                                            <div>{{ $details->prim_whats_app_no_business ?? ($details->prim_whats_app_no ?? '-') }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $details->alternate_whats_app_no_business ?? ($details->alt_whats_app_no ?? '-') }}</div>
                                            <div>{{ $details->prim_email_business ?? ($details->prim_email_personal ?? ($user->email ?? '-')) }}</div>
                                            <div>{{ $details->alt_email_business ?? ($details->alt_email_personal ?? '-') }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $details->gst_no ?? '-' }}</div>
                                            <div>{{ $details->aadhaar_no ?? '-' }}</div>
                                            <div>{{ $details->pan_no ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $user->approval_status == 1 ? translate('Verified') : translate('Unverified') }}</div>
                                            <div>{{ $user->user_subtype ?: translate('Customer') }}</div>
                                            <div>{{ $details->current_status ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $user->credit_status == 1 ? translate('Active') : translate('Deactive') }}</div>
                                            <div>{{ $user->credit_days ?? '-' }}</div>
                                            <div>{{ $user->credit_limit ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $details->transport ?? '-' }}</div>
                                            <div>{{ $details->booked_to ?? '-' }}</div>
                                            <div>{{ $details->salesman ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $details->iec_no ?? '-' }}</div>
                                            <div>{{ $details->passport_no ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $details->d_l_no_1 ?? '-' }}</div>
                                            <div>{{ $details->d_l_no_2 ?? '-' }}</div>
                                            <div>{{ $details->d_l_no_3 ?? '-' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $details->doctor_hospital_reg_no ?? '-' }}</div>
                                            <div>{{ $details->dairy_trust_ngo_reg_no ?? '-' }}</div>
                                            <div>{{ $details->cc_mdl_reg_no ?? '-' }}</div>
                                        </td>
                                        <td class="text-right drop-down-text-icon drop-down-text-icon-business">
                                        <div class="dropdown">
                                            <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button"
                                                id="customerActionDropdown{{ $user->id }}" data-toggle="dropdown"
                                            aria-haspopup="true" aria-expanded="false">
                                                <i class="las la-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right p-2"
                                                aria-labelledby="customerActionDropdown{{ $user->id }}">

                                                <!-- Edit -->
                                                <a href="{{ route('customers.edit', $user->id) }}" class="btn"
                                                    title="{{ translate('Edit this Customer') }}">
                                                    <i
                                                        class="las la-pen btn-soft-primary btn-icon btn-circle btn-sm mr-2"></i>
                                                    <span class="ms-1">{{ translate('Edit') }}</span>
                                                </a>

                                                <!-- View -->
                                                <a href="{{ route('customers.view', encrypt($user->id)) }}"
                                                    class="btn"
                                                    title="{{ translate('View Details of this Customer') }}">
                                                    <i
                                                        class="las la-eye btn-soft-success btn-icon btn-circle btn-sm mr-2"></i>
                                                    <span class="ms-1">{{ translate('View') }}</span>
                                                </a>

                                                <!-- Approval -->
                                                @can('ban_customer')
                                                    @if ($user->approval_status != 1)
                                                        <a href="#" class="btn"
                                                            onclick="show_Approval_model({{ $user->id }}, 'approve', '{{ $user->user_subtype ?? 'null' }}');"
                                                            title="{{ translate('Approval this Customer') }}">
                                                            <i
                                                                class="las la-edit btn-soft-success btn-icon btn-circle btn-sm mr-2"></i>
                                                            <span class="ms-1">{{ translate('Approve') }}</span>
                                                        </a>
                                                    @else
                                                        <a href="#" class="btn"
                                                            onclick="show_Approval_model({{ $user->id }}, 'not_approve', '{{ $user->user_subtype ?? 'null' }}');"
                                                            title="{{ translate('Not Approve this Customer') }}">
                                                            <i
                                                                class="las la-edit btn-soft-success btn-icon btn-circle btn-sm mr-2"></i>
                                                            <span class="ms-1">{{ translate('Not Approve') }}</span>
                                                        </a>
                                                    @endif
                                                @endcan

                                                <!-- Login as Customer -->
                                                @if ($user->email_verified_at != null && auth()->user()->can('login_as_customer'))
                                                    <a href="{{ route('customers.login', encrypt($user->id)) }}"
                                                        class="btn"
                                                        title="{{ translate('Log in as this Customer') }}">
                                                        <i
                                                            class="las la-sign-in-alt btn-soft-primary btn-icon btn-circle btn-sm mr-2"></i>
                                                        <span class="ms-1">{{ translate('Login') }}</span>
                                                    </a>
                                                @endif

                                                <!-- Ban / Unban -->
                                                @can('ban_customer')
                                                    @if ($user->banned != 1)
                                                        <a href="#" class="btn"
                                                            onclick="confirm_ban('{{ route('customers.ban', encrypt($user->id)) }}');"
                                                            title="{{ translate('Ban this Customer') }}">
                                                            <i
                                                                class="las la-user-slash btn-soft-danger btn-icon btn-circle btn-sm mr-2"></i>
                                                            <span class="ms-1">{{ translate('Ban') }}</span>
                                                        </a>
                                                    @else
                                                        <a href="#" class="btn"
                                                            onclick="confirm_unban('{{ route('customers.ban', encrypt($user->id)) }}');"
                                                            title="{{ translate('Unban this Customer') }}">
                                                            <i
                                                                class="las la-user-check btn-soft-success btn-icon btn-circle btn-sm mr-2"></i>
                                                            <span class="ms-1">{{ translate('Unban') }}</span>
                                                        </a>
                                                    @endif


                                                    <a href="#"
                                                        onclick="show_credit_modal({{ $user->id }}, '{{ $user->credit_status }}', {{ (int) $user->credit_limit }}, {{ $user->credit_days }}); return false;"
                                                        {{-- onclick="show_credit_modal({{ $user->id }}, '{{ $user->credit_status == 1 ? 'active' : 'deactive' }}', {{ (int) $user->credit_limit }}); return false;" --}}
                                                        title="Credit Manage" class="btn">
                                                        <i
                                                            class="las la-edit btn-soft-success btn-icon btn-circle btn-sm mr-2"></i>
                                                        <span class="ms-1">Credit</span>
                                                    </a>

                                                    <a href="{{ route('financial-archives.customer', $user->id) }}"
                                                        title="{{ translate('Financial Archive') }}" class="btn">
                                                        <i
                                                            class="las la-folder-plus btn-soft-primary btn-icon btn-circle btn-sm mr-2"></i>
                                                        <span class="ms-1">{{ translate('Financial Archive') }}</span>
                                                    </a>
                                                @endcan

                                                <!-- Delete -->
                                                @can('delete_customer')
                                                    <a href="#" class="btn"
                                                        data-href="{{ route('customers.destroy', $user->id) }}"
                                                        title="{{ translate('Delete') }}">
                                                        <i
                                                            class="las la-trash btn-soft-danger btn-icon btn-circle btn-sm confirm-delete mr-2"></i>
                                                        <span class="ms-1">{{ translate('Delete') }}</span>
                                                    </a>
                                                @endcan

                                            </div>
                                        </div>


                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="aiz-pagination">
                    {{ $users->appends(request()->input())->links() }}
                </div>
            </div>
        </form>
    </div>


    <div class="modal fade" id="confirm-ban">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h6">{{ translate('Confirmation') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ translate('Do you really want to ban this Customer?') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <a type="button" id="confirmation" class="btn btn-primary">{{ translate('Proceed!') }}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirm-unban">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h6">{{ translate('Confirmation') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ translate('Do you really want to unban this Customer?') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <a type="button" id="confirmationunban" class="btn btn-primary">{{ translate('Proceed!') }}</a>
                </div>
            </div>
        </div>
    </div>
    {{-- - //------------------------------ approval modal -----------------------// -- --}}

    <div class="modal fade" id="approval_model" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel_phone"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="heading">
                        <h5 class="modal-title" id="exampleModalLabel_phone">Approval Status User</h5>
                    </div>
                    <div class="purple_btn_close">
                        <button type="button" class="close p-1 px-3" data-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                </div>

                <div class="modal-body">
                    <form id="approval-status-model" action="{{ url(route('customers.approval')) }}" method="post">
                        @csrf

                        <input type="hidden" name="id">

                        <!-- Approval Status Dropdown -->
                        <div class="form-group">
                            <label for="approval-status" class="col-form-label form-label">Approval Status:</label>
                            <select class="form-control" id="approval-status" name="approval_status"
                                onchange="toggleNote()">
                                <option value="approve">Approve</option>
                                <option value="not_approve">Not Approve</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="approval-status" class=" col-form-label form-label">User Role:</label>
                            <select class="form-control" id="user-role" name="user_subtype">
                                <option value="">Customer</option>
                                <option value="pts">pts</option>
                                <option value="ptr">ptr</option>
                                <option value="ptd">ptd</option>
                                <option value="gov">gov</option>
                                <option value="expo">expo</option>
                            </select>
                        </div>

                        <div id="note-section" style="display: none;" class="modal-body">
                            <div class="form-group">
                                <label for="recipient-name" class="col-form-label form-label">Note :</label>
                                <textarea type="text" class="form-control" id="note" name="note"></textarea>
                            </div>
                        </div>


                        <div class="modal-footer" style="padding: 0; border-top: 0;">
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
        </div>
    </div>

    {{-- - //------------------------------ approval modal -----------------------// -- --}}

    {{-- - //------------------------------ Credit Manage modal -----------------------// -- --}}
    <!-- Credit Manage Modal -->
    <div class="modal fade" id="creditManageModal" tabindex="-1" role="dialog" aria-labelledby="creditManageLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="creditManageForm" action="{{ route('customers.credits.update') }}" method="POST"
                class="modal-content">
                @csrf
                {{-- If you prefer PATCH: @method('PATCH') --}}

                <div class="modal-header py-2">
                    <h5 class="modal-title" id="creditManageLabel">Credit Manage</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="credit_user_id" name="user_id" value="{{ old('user_id') }}">

                    <div class="form-group">
                        <label for="credit_status" class="mb-1">Credit Status</label>
                        <select class="form-control @error('credit_status') is-invalid @enderror" id="credit_status"
                            name="credit_status" required>
                            <option value="active" {{ old('credit_status') === 'active' ? 'selected' : '' }}>Active
                            </option>
                            <option value="deactive" {{ old('credit_status') === 'deactive' ? 'selected' : '' }}>Deactive
                            </option>
                        </select>
                        @error('credit_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="credit_days" class="mb-1">Number of Days</label>
                        <input type="number" min="0" step="1"
                            class="form-control @error('credit_days') is-invalid @enderror" id="credit_days"
                            name="credit_days" placeholder="Enter number of days" value="{{ old('credit_days') }}"
                            required>
                        @error('credit_days')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-0">
                        <label for="credit_limit" class="mb-1">Credit Limit</label>
                        <input type="number" min="0" step="1"
                            class="form-control @error('credit_limit') is-invalid @enderror" id="credit_limit"
                            name="credit_limit" placeholder="Enter credit limit" value="{{ old('credit_limit') }}"
                            required>
                        @error('credit_limit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
    {{-- - //------------------------------ Credit Manage modal -----------------------// -- --}}
@endsection

@section('modal')
    <!-- Delete modal -->
    @include('modals.delete_modal')
    <!-- Bulk Delete modal -->
    @include('modals.bulk_delete_modal')
@endsection

@section('script')
    <script type="text/javascript">
        const defaultLocationOption = @json(translate('All'));
        const locationLookupTimers = {};

        function refreshPicker($el) {
            if (window.AIZ && AIZ.plugins && typeof AIZ.plugins.bootstrapSelect === 'function') {
                AIZ.plugins.bootstrapSelect('refresh');
            } else if ($.fn.selectpicker) {
                $el.selectpicker('refresh');
            }
        }

        function setLocationOptions(scope, field, options, selected) {
            const $select = $('#' + scope + '_' + field);
            const fallbackSelected = $select.val() || $select.data('selected') || '';
            const finalSelected = selected !== undefined ? selected : fallbackSelected;

            $select.empty();
            $select.append(`<option value="">${defaultLocationOption}</option>`);

            (options || []).forEach(function (opt) {
                $select.append(`<option value="${opt.id}">${opt.name}</option>`);
            });

            if (finalSelected !== null && finalSelected !== undefined && finalSelected !== '') {
                $select.val(String(finalSelected));
            }

            $select.data('selected', '');
            refreshPicker($select);
        }

        function populateStates(scope, preserveSelected = false) {
            const countryId = $('#' + scope + '_country_id').val();
            if (!countryId) {
                setLocationOptions(scope, 'state_id', [], '');
                setLocationOptions(scope, 'district', [], '');
                setLocationOptions(scope, 'city_id', [], '');
                setLocationOptions(scope, 'post', [], '');
                setLocationOptions(scope, 'village', [], '');
                return $.Deferred().resolve().promise();
            }

            return $.get("{{ route('customers.location.options') }}", { country_id: countryId, scope: scope })
                .done(function (resp) {
                    const selected = preserveSelected ? $('#' + scope + '_state_id').data('selected') : '';
                    setLocationOptions(scope, 'state_id', resp.states || [], selected);
                    populateDistricts(scope, preserveSelected);
                });
        }

        function populateDistricts(scope, preserveSelected = false) {
            const countryId = $('#' + scope + '_country_id').val();
            const stateId = $('#' + scope + '_state_id').val();
            if (!countryId || !stateId) {
                setLocationOptions(scope, 'district', [], '');
                setLocationOptions(scope, 'city_id', [], '');
                setLocationOptions(scope, 'post', [], '');
                setLocationOptions(scope, 'village', [], '');
                return $.Deferred().resolve().promise();
            }

            return $.get("{{ route('customers.location.options') }}", { country_id: countryId, state: stateId, scope: scope })
                .done(function (resp) {
                    const selectedDistrict = preserveSelected ? $('#' + scope + '_district').data('selected') : '';
                    const selectedCity = preserveSelected ? $('#' + scope + '_city_id').data('selected') : '';
                    setLocationOptions(scope, 'district', resp.districts || [], selectedDistrict);
                    setLocationOptions(scope, 'city_id', resp.cities || [], selectedCity);
                    populatePosts(scope, preserveSelected);
                });
        }

        function populatePosts(scope, preserveSelected = false) {
            const countryId = $('#' + scope + '_country_id').val();
            const stateId = $('#' + scope + '_state_id').val();
            const districtId = $('#' + scope + '_district').val();
            const cityId = $('#' + scope + '_city_id').val();
            if (!countryId || !stateId || !districtId || !cityId) {
                setLocationOptions(scope, 'post', [], '');
                setLocationOptions(scope, 'village', [], '');
                return;
            }

            $.get("{{ route('customers.location.options') }}", {
                country_id: countryId,
                state: stateId,
                district: districtId,
                city: cityId,
                scope: scope
            }).done(function (resp) {
                const selected = preserveSelected ? $('#' + scope + '_post').data('selected') : '';
                setLocationOptions(scope, 'post', resp.posts || [], selected);
                populateVillages(scope, preserveSelected);
            });
        }

        function populateVillages(scope, preserveSelected = false) {
            const countryId = $('#' + scope + '_country_id').val();
            const stateId = $('#' + scope + '_state_id').val();
            const districtId = $('#' + scope + '_district').val();
            const cityId = $('#' + scope + '_city_id').val();
            const postId = $('#' + scope + '_post').val();
            if (!countryId || !stateId || !districtId || !cityId || !postId) {
                setLocationOptions(scope, 'village', [], '');
                return;
            }

            $.get("{{ route('customers.location.options') }}", {
                country_id: countryId,
                state: stateId,
                district: districtId,
                city: cityId,
                post: postId,
                scope: scope
            }).done(function (resp) {
                const selected = preserveSelected ? $('#' + scope + '_village').data('selected') : '';
                setLocationOptions(scope, 'village', resp.villages || [], selected);
            });
        }

        function applyPincodeLocation(scope, location) {
            if (!location || !location.country_id) {
                if (window.AIZ && AIZ.plugins && AIZ.plugins.notify) {
                    AIZ.plugins.notify('warning', '{{ translate('No customer location found for this pincode') }}');
                }
                return;
            }

            const selectedFields = {
                state_id: location.state_id,
                district: location.district,
                city_id: location.city_id,
                post: location.post,
                village: location.village
            };

            Object.keys(selectedFields).forEach(function (field) {
                $('#' + scope + '_' + field).data('selected', selectedFields[field] || '');
            });

            const $country = $('#' + scope + '_country_id');
            $country.val(String(location.country_id));
            refreshPicker($country);
            populateStates(scope, true);
        }

        function lookupPincode(scope) {
            const inputSelector = scope === 'business' ? '#pincode_business' : '#pincode';
            const pincode = $(inputSelector).val().trim();
            if (pincode.length < 3) {
                return;
            }

            $.get("{{ route('customers.location.options') }}", {
                scope: scope,
                pincode: pincode,
                country_id: $('#' + scope + '_country_id').val() || ''
            }).done(function (resp) {
                applyPincodeLocation(scope, resp.location || null);
            }).fail(function (xhr) {
                if (xhr.status !== 403 && window.AIZ && AIZ.plugins && AIZ.plugins.notify) {
                    AIZ.plugins.notify('danger', '{{ translate('Unable to fetch location details') }}');
                }
            });
        }

        $(function () {
            initLocationFilters();
        });

        function initLocationFilters() {
            ['business', 'personal'].forEach(function (scope) {
                populateStates(scope, true);

                $('#' + scope + '_country_id').on('change', function () {
                    populateStates(scope, false);
                });

                $('#' + scope + '_state_id').on('change', function () {
                    populateDistricts(scope, false);
                });

                $('#' + scope + '_district').on('change', function () {
                    populatePosts(scope, false);
                });

                $('#' + scope + '_city_id').on('change', function () {
                    populatePosts(scope, false);
                });

                $('#' + scope + '_post').on('change', function () {
                    populateVillages(scope, false);
                });

                const pincodeSelector = scope === 'business' ? '#pincode_business' : '#pincode';
                $(pincodeSelector).on('input blur', function () {
                    clearTimeout(locationLookupTimers[scope]);
                    locationLookupTimers[scope] = setTimeout(function () {
                        lookupPincode(scope);
                    }, 350);
                });

                if ($(pincodeSelector).val().trim() && !$('#' + scope + '_country_id').val()) {
                    lookupPincode(scope);
                }
            });

            $('.btn-apply-filters').on('click', function () {
                submitFilters();
            });
        }

        function submitFilters() {
            $('#customerFilterModal').modal('hide');
            $('#sort_customers').submit();
        }

        $(document).on("change", ".check-all", function() {
            if (this.checked) {
                // Iterate each checkbox
                $('.check-one:checkbox').each(function() {
                    this.checked = true;
                });
            } else {
                $('.check-one:checkbox').each(function() {
                    this.checked = false;
                });
            }

        });

        function sort_customers(el) {
            $('#sort_customers').submit();
        }

        function confirm_ban(url) {
            if ('{{ env('DEMO_MODE') }}' == 'On') {
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                return;
            }

            $('#confirm-ban').modal('show', {
                backdrop: 'static'
            });
            document.getElementById('confirmation').setAttribute('href', url);
        }

        function confirm_unban(url) {
            if ('{{ env('DEMO_MODE') }}' == 'On') {
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                return;
            }

            $('#confirm-unban').modal('show', {
                backdrop: 'static'
            });
            document.getElementById('confirmationunban').setAttribute('href', url);
        }

        function bulk_delete() {
            var data = new FormData($('#sort_customers')[0]);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{ route('bulk-customer-delete') }}",
                type: 'POST',
                data: data,
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response == 1) {
                        location.reload();
                    }
                }
            });
        }

        function toggleNote() {
            const approvalStatus = document.getElementById('approval-status').value;
            const noteSection = document.getElementById('note-section');

            if (approvalStatus === 'not_approve') {
                noteSection.style.display = 'block'; // Show the note section
            } else {
                noteSection.style.display = 'none'; // Hide the note section
            }
        }


        // // Global scope
        function show_Approval_model(id, status, role) {
            // // Set the value of the hidden input field
            $('#approval_model input[name="id"]').val(id);

            // Set the selected option in the dropdown
            $('#approval-status').val(status);

            if (role !== 'null') {
                // Set the selected option in the dropdown
                $('#user-role').val(role);
            }

            {{--
            // $('#approval-status option').each(function () {
            //     if ($(this).val() !== status) {
            //         $(this).hide();
            //     } else {
            //         $(this).show();
            //     }
            // });
            --}}

            // Trigger the toggleNote function to ensure the note section visibility is updated
            toggleNote();

            // Show the modal
            $('#approval_model').modal('show');
        }
    </script>
    <script>
        function show_credit_modal(userId, status, limit, days) {
            $('#credit_user_id').val(userId);
            if(status){
                $('#credit_status').val(status);
            }
            $('#credit_limit').val(limit || 0);
            $('#credit_days').val(days || 0);
            $('#creditManageModal').modal('show');
        }

    </script>
@endsection
