<div class="col-md-12">
    <div class="form-group">
        <label>{{ translate('Address') }}</label>
        <textarea class="form-control" name="{{ $prefix }}_address" rows="2">{{ old($prefix . '_address') }}</textarea>
    </div>
</div>
<div class="col-md-4">
    <div class="form-group">
        <label>{{ translate('Country') }}</label>
        <select class="form-control country-select" name="{{ $prefix }}_country_id" data-prefix="{{ $prefix }}">
            <option value="">{{ translate('Select Country') }}</option>
            @foreach($countries as $country)
                <option value="{{ $country->id }}">{{ $country->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="col-md-4">
    <div class="form-group">
        <label>{{ translate('State') }}</label>
        <select class="form-control state-select" name="{{ $prefix }}_state_id" id="{{ $prefix }}-state-id" data-prefix="{{ $prefix }}">
            <option value="">{{ translate('Select State') }}</option>
        </select>
    </div>
</div>
<div class="col-md-4">
    <div class="form-group">
        <label>{{ translate('City') }}</label>
        <select class="form-control" name="{{ $prefix }}_city_id" id="{{ $prefix }}-city-id">
            <option value="">{{ translate('Select City') }}</option>
        </select>
    </div>
</div>
<div class="col-md-6">
    <div class="form-group">
        <label>{{ translate('Postal Code') }}</label>
        <input type="text" class="form-control" name="{{ $prefix }}_postal_code" value="{{ old($prefix . '_postal_code') }}">
    </div>
</div>
<div class="col-md-6">
    <div class="form-group">
        <label>{{ translate('Phone') }}</label>
        <input type="text" class="form-control" name="{{ $prefix }}_phone" value="{{ old($prefix . '_phone') }}">
    </div>
</div>
