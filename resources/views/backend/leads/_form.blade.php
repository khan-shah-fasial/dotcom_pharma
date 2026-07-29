@php
    $lead = $lead ?? null;
    $photoValue = old('photo', $lead->photo ?? '');
    $oldSocialKeys = old('social_media_keys');
    $socialMediaRows = collect();

    if (is_array($oldSocialKeys)) {
        $oldSocialValues = old('social_media_values', []);
        $socialMediaRows = collect($oldSocialKeys)->map(function ($key, $index) use ($oldSocialValues) {
            return [
                'key' => $key,
                'value' => $oldSocialValues[$index] ?? '',
            ];
        });
    } else {
        $socialMediaRows = collect($lead->social_media_ids ?? []);
    }

    if ($socialMediaRows->isEmpty()) {
        $socialMediaRows = collect([['key' => '', 'value' => '']]);
    }

    $selectedDepartmentId = old('department_id', $lead->department_id ?? '');

    if ((string) $selectedDepartmentId === '' && !$lead) {
        $defaultDepartment = collect($departments)->first(function ($department) {
            return strcasecmp($department->name, 'Sales') === 0
                && strcasecmp(optional($department->category)->name ?? '', 'Commercial Departments') === 0;
        });
        $selectedDepartmentId = optional($defaultDepartment)->id ?: '';
    }

    $departmentsByCategory = collect($departments)->groupBy(function ($department) {
        return optional($department->category)->name ?: translate('Other');
    });
@endphp

<style>
    .lead-combo {
        position: relative;
    }
    .lead-combo-menu {
        display: none;
        position: absolute;
        z-index: 1050;
        width: 100%;
        max-height: 220px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #e4e5eb;
        border-top: 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
    }
    .lead-combo-option {
        padding: 9px 12px;
        cursor: pointer;
    }
    .lead-combo-option:hover {
        background: #f2f3f8;
    }
    .lead-social-media-row .btn {
        height: 38px;
    }
</style>

@csrf
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Phone') }}</label>
    <div class="col-md-4">
        <div class="input-group">
            <input type="text" name="phone" id="lead_phone" class="form-control" autocomplete="off"
                value="{{ old('phone', $lead->phone ?? '') }}">
            @if(!$lead)
                <div class="input-group-append">
                    <button type="button" class="btn btn-soft-primary js-lead-fetch-customer" data-input="#lead_phone">
                        <i class="las la-search"></i> {{ translate('Fetch') }}
                    </button>
                </div>
            @endif
        </div>
        <small id="lead_customer_lookup_status" class="form-text lead-customer-lookup-status"></small>
        @error('phone') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-1 col-form-label">{{ translate('WhatsApp Number') }}</label>
    <div class="col-md-4">
        <div class="input-group">
            <input type="text" name="whatsapp_number" id="lead_whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $lead->whatsapp_number ?? '') }}">
            @if(!$lead)
                <div class="input-group-append">
                    <button type="button" class="btn btn-soft-primary js-lead-fetch-customer" data-input="#lead_whatsapp_number">
                        <i class="las la-search"></i> {{ translate('Fetch') }}
                    </button>
                </div>
            @endif
        </div>
        <small class="form-text lead-customer-lookup-status"></small>
        @error('whatsapp_number') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Alternate Mobile Number') }}</label>
    <div class="col-md-9">
        <input type="text" name="alternate_mobile_number" id="lead_alternate_mobile_number" class="form-control"
            value="{{ old('alternate_mobile_number', $lead->alternate_mobile_number ?? '') }}">
        @error('alternate_mobile_number') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Name') }} <span class="text-danger">*</span></label>
    <div class="col-md-9">
        <input type="text" name="name" class="form-control" value="{{ old('name', $lead->name ?? '') }}" required>
        @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Company Name') }}</label>
    <div class="col-md-9">
        <div class="input-group">
            <input type="text" name="company_name" id="lead_company_name" class="form-control"
                value="{{ old('company_name', $lead->company_name ?? '') }}">
            @if(!$lead)
                <div class="input-group-append">
                    <button type="button" class="btn btn-soft-primary js-lead-fetch-customer"
                        data-input="#lead_company_name" data-lookup="company_name">
                        <i class="las la-search"></i> {{ translate('Fetch') }}
                    </button>
                </div>
            @endif
        </div>
        <small class="form-text lead-customer-lookup-status"></small>
        @error('company_name') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Designation') }}</label>
    <div class="col-md-4">
        <input type="text" name="designation" class="form-control" value="{{ old('designation', $lead->designation ?? '') }}">
        @error('designation') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-2 col-form-label">{{ translate('Customer Type') }}</label>
    <div class="col-md-3">
        <select required name="customer_type" id="lead_customer_type" class="form-control aiz-selectpicker" data-live-search="true">
            <option value="">{{ translate('Select Customer Type') }}</option>
            @foreach ($customerTypes as $customerType)
                <option value="{{ $customerType }}" @selected(old('customer_type', $lead->customer_type ?? '') === $customerType)>
                    {{ $customerType }}
                </option>
            @endforeach
        </select>
        @error('customer_type') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label" for="lead_current_status">{{ translate('Current Status') }}</label>
    <div class="col-md-9">
        <select name="current_status" id="lead_current_status" class="form-control aiz-selectpicker" data-live-search="true" title="{{ translate('Select Current Status') }}">
            <option value="">{{ translate('Select Current Status') }}</option>
            @foreach ($currentStatuses as $currentStatus)
                <option value="{{ $currentStatus }}" @selected(old('current_status', $lead->current_status ?? '') === $currentStatus)>{{ translate($currentStatus) }}</option>
            @endforeach
        </select>
        @error('current_status') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Photo') }}</label>
    <div class="col-md-9">
        <div class="input-group" data-toggle="aizuploader" data-type="image">
            <div class="input-group-prepend">
                <div class="input-group-text bg-soft-secondary">{{ translate('Browse') }}</div>
            </div>
            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
            <input type="hidden" name="photo" class="selected-files" value="{{ $photoValue }}">
        </div>
        <div class="file-preview box sm"></div>
        @error('photo') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Email') }}</label>
    <div class="col-md-9">
        <input type="email" name="email" class="form-control" value="{{ old('email', $lead->email ?? '') }}">
        @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Source') }}</label>
    <div class="col-md-4">
        <div class="lead-combo" id="lead_source_combo">
            <input type="hidden" name="source_id" id="lead_source_id" value="{{ old('source_id', $lead->source_id ?? '') }}">
            <input type="text" name="source_name" id="lead_source_name" class="form-control"
                value="{{ old('source_name', optional($lead?->source)->name) }}"
                autocomplete="off" placeholder="{{ translate('Select or enter lead source') }}">
            <div class="lead-combo-menu" id="lead_source_options">
            @foreach ($sources as $source)
                <div class="lead-combo-option" data-id="{{ $source->id }}" data-name="{{ $source->name }}">{{ $source->name }}</div>
            @endforeach
            </div>
        </div>
        @error('source_id') <span class="text-danger small">{{ $message }}</span> @enderror
        @error('source_name') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-1 col-form-label">{{ translate('Status') }}</label>
    <div class="col-md-4">
        <select name="status_id" class="form-control aiz-selectpicker" data-live-search="true">
            <option value="">{{ translate('Select Status') }}</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->id }}" @if(old('status_id', $lead->status_id ?? '') == $status->id) selected @endif>{{ $status->name }}</option>
            @endforeach
        </select>
        @error('status_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Department') }}</label>
    <div class="col-md-4">
        <select name="department_id" class="form-control aiz-selectpicker" data-live-search="true">
            <option value="">{{ translate('Select Department') }}</option>
            @foreach ($departmentsByCategory as $categoryName => $categoryDepartments)
                <optgroup label="{{ $categoryName }}">
                    @foreach ($categoryDepartments as $department)
                        <option value="{{ $department->id }}" @selected((string) $selectedDepartmentId === (string) $department->id)>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('department_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-1 col-form-label">{{ translate('Work Profile') }}</label>
    <div class="col-md-4">
        <textarea name="work_profile" rows="2" class="form-control">{{ old('work_profile', $lead->work_profile ?? '') }}</textarea>
        @error('work_profile') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Address') }}</label>
    <div class="col-md-9">
        <textarea name="address" id="lead_address" rows="2" class="form-control">{{ old('address', $lead->address ?? '') }}</textarea>
        @error('address') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Country') }}</label>
    <div class="col-md-4">
        <select name="country_id" id="lead_country_id" class="form-control aiz-selectpicker" data-live-search="true">
            <option value="">{{ translate('Select Country') }}</option>
            @foreach ($countries as $country)
                <option value="{{ $country->id }}" @selected((string) old('country_id', $lead->country_id ?? '') === (string) $country->id)>{{ $country->name }}</option>
            @endforeach
        </select>
        @error('country_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-1 col-form-label">{{ translate('State') }}</label>
    <div class="col-md-4">
        <select name="state_id" id="lead_state_id" class="form-control aiz-selectpicker" data-live-search="true">
            <option value="">{{ translate('Select State') }}</option>
            @foreach ($states as $state)
                <option value="{{ $state->id }}" @selected((string) old('state_id', $lead->state_id ?? '') === (string) $state->id)>{{ $state->name }}</option>
            @endforeach
        </select>
        @error('state_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('City') }}</label>
    <div class="col-md-4">
        <select name="city_id" id="lead_city_id" class="form-control aiz-selectpicker" data-live-search="true">
            <option value="">{{ translate('Select City') }}</option>
            @foreach ($cities as $city)
                <option value="{{ $city->id }}" @selected((string) old('city_id', $lead->city_id ?? '') === (string) $city->id)>{{ $city->name }}</option>
            @endforeach
        </select>
        @error('city_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-1 col-form-label">{{ translate('Pincode') }}</label>
    <div class="col-md-4">
        <input type="text" name="pincode" id="lead_pincode" class="form-control" value="{{ old('pincode', $lead->pincode ?? '') }}">
        @error('pincode') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Assigned To') }}</label>
    <div class="col-md-9">
        <select name="assigned_to" class="form-control aiz-selectpicker" data-live-search="true">
            <option value="">{{ translate('Unassigned') }}</option>
            @foreach ($assignees as $user)
                <option value="{{ $user->id }}" @if(old('assigned_to', $lead->assigned_to ?? '') == $user->id) selected @endif>
                    {{ $user->name }} @if($user->email) ({{ $user->email }}) @endif
                    @if(isset($user->staff_status) && (int) $user->staff_status === 0) ({{ translate('Inactive') }}) @endif
                </option>
            @endforeach
        </select>
        @error('assigned_to') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Social Media IDs') }}</label>
    <div class="col-md-9">
        <div id="lead_social_media_rows">
            @foreach ($socialMediaRows as $row)
                <div class="row gutters-5 lead-social-media-row mb-2">
                    <div class="col-md-5">
                        <input type="text" name="social_media_keys[]" class="form-control" value="{{ $row['key'] ?? '' }}" placeholder="{{ translate('Platform') }}">
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="social_media_values[]" class="form-control" value="{{ $row['value'] ?? '' }}" placeholder="{{ translate('ID / URL') }}">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-soft-danger btn-icon btn-circle js-remove-social-media-row" title="{{ translate('Remove') }}">
                            <i class="las la-trash"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-soft-primary btn-sm" id="lead_add_social_media_row">{{ translate('Add More') }}</button>
        @error('social_media_keys') <span class="text-danger small d-block">{{ $message }}</span> @enderror
        @error('social_media_keys.*') <span class="text-danger small d-block">{{ $message }}</span> @enderror
        @error('social_media_values') <span class="text-danger small d-block">{{ $message }}</span> @enderror
        @error('social_media_values.*') <span class="text-danger small d-block">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Notes') }}</label>
    <div class="col-md-9">
        <textarea name="notes" rows="4" class="form-control">{{ old('notes', $lead->notes ?? '') }}</textarea>
        @error('notes') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="text-right">
    <button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
</div>
