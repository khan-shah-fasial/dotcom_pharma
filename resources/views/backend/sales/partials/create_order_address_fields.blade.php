<div class="col-md-6">
    <div class="form-group">
        <label>{{ translate('Contact Person') }}</label>
        <input type="text" class="form-control auto-capitalize-first" name="{{ $prefix }}_contact_person"
            value="{{ old($prefix . '_contact_person') }}">
        @error($prefix . '_contact_person') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="col-md-6">
    <div class="form-group">
        <label>{{ translate('Phone') }}</label>
        <input type="text" class="form-control" name="{{ $prefix }}_phone" value="{{ old($prefix . '_phone') }}">
        @error($prefix . '_phone') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="col-md-12">
    <div class="form-group">
        <label>{{ translate('Address') }}</label>
        <textarea class="form-control auto-capitalize-first" name="{{ $prefix }}_address" rows="2">{{ old($prefix . '_address') }}</textarea>
        @error($prefix . '_address') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="col-md-4">
    <div class="form-group">
        <label>{{ translate('Pincode') }}</label>
        <input type="text" inputmode="numeric" maxlength="10" class="form-control pincode-lookup"
            name="{{ $prefix }}_postal_code" data-prefix="{{ $prefix }}" value="{{ old($prefix . '_postal_code') }}">
        <small class="pincode-status text-muted" id="{{ $prefix }}-pincode-status"></small>
        @error($prefix . '_postal_code') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="col-md-8">
    <div class="form-group">
        <label>{{ translate('Village / Post') }}</label>
        <input type="text" class="form-control auto-capitalize-first" name="{{ $prefix }}_village"
            id="{{ $prefix }}-village" value="{{ old($prefix . '_village') }}">
    </div>
</div>
<div class="col-md-6">
    <div class="form-group">
        <label>{{ translate('Country') }}</label>
        <select class="form-control country-select" name="{{ $prefix }}_country_id" id="{{ $prefix }}-country-id" data-prefix="{{ $prefix }}">
            <option value="">{{ translate('Select Country') }}</option>
            @foreach($countries as $country)
                <option value="{{ $country->id }}" @selected((string) old($prefix . '_country_id') === (string) $country->id)>{{ $country->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="col-md-6">
    <div class="form-group">
        <label>{{ translate('State') }}</label>
        <select class="form-control state-select" name="{{ $prefix }}_state_id" id="{{ $prefix }}-state-id" data-prefix="{{ $prefix }}">
            <option value="">{{ translate('Select State') }}</option>
        </select>
    </div>
</div>
<div class="col-md-6">
    <div class="form-group">
        <label>{{ translate('City') }}</label>
        <select class="form-control" name="{{ $prefix }}_city_id" id="{{ $prefix }}-city-id">
            <option value="">{{ translate('Select City') }}</option>
        </select>
    </div>
</div>
<div class="col-md-6">
    <div class="form-group">
        <label>{{ translate('District') }}</label>
        <input type="text" class="form-control auto-capitalize-first" name="{{ $prefix }}_district"
            id="{{ $prefix }}-district" value="{{ old($prefix . '_district') }}">
    </div>
</div>
