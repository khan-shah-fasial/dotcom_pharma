@php
    $lead = $lead ?? null;
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
</style>

@csrf
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Phone') }}</label>
    <div class="col-md-4">
        <input type="text" name="phone" id="lead_phone" class="form-control" autocomplete="off"
            value="{{ old('phone', $lead->phone ?? '') }}">
        <small id="lead_customer_lookup_status" class="form-text"></small>
        @error('phone') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-1 col-form-label">{{ translate('WhatsApp Number') }}</label>
    <div class="col-md-4">
        <input type="text" name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $lead->whatsapp_number ?? '') }}">
        @error('whatsapp_number') <span class="text-danger small">{{ $message }}</span> @enderror
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
        <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $lead->company_name ?? '') }}">
        @error('company_name') <span class="text-danger small">{{ $message }}</span> @enderror
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
    <div class="col-md-4">
        <select name="assigned_to" class="form-control aiz-selectpicker" data-live-search="true">
            <option value="">{{ translate('Unassigned') }}</option>
            @foreach ($assignees as $user)
                <option value="{{ $user->id }}" @if(old('assigned_to', $lead->assigned_to ?? '') == $user->id) selected @endif>
                    {{ $user->name }} @if($user->email) ({{ $user->email }}) @endif
                </option>
            @endforeach
        </select>
        @error('assigned_to') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-1 col-form-label">{{ translate('Value') }}</label>
    <div class="col-md-4">
        <input type="number" name="expected_value" class="form-control" step="0.01" min="0" value="{{ old('expected_value', $lead->expected_value ?? 0) }}">
        @error('expected_value') <span class="text-danger small">{{ $message }}</span> @enderror
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
