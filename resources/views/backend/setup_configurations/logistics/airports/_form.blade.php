@php
    $airport = $airport ?? null;
    $facilityFields = [
        'cargo_airport' => 'Cargo Airport',
        'customs_airport' => 'Customs Airport',
        'cold_chain_facility' => 'Cold Chain Facility',
    ];
    $facilityOptions = ['Yes', 'No', 'Limited', 'N/A'];
@endphp

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Airport Identification') }}</h5></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ translate('Country') }} <span class="text-danger">*</span></label>
                    <select name="country_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                        <option value="">{{ translate('Select Country') }}</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) old('country_id', $airport?->country_id) === (string) $country->id)>
                                {{ $country->name }} ({{ $country->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('country_id') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ translate('Airport Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $airport?->name) }}" required maxlength="255">
                    @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('IATA Code') }}</label>
                    <input type="text" name="iata" class="form-control text-uppercase" value="{{ old('iata', $airport?->iata) }}" maxlength="3">
                    @error('iata') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('ICAO Code') }}</label>
                    <input type="text" name="icao" class="form-control text-uppercase" value="{{ old('icao', $airport?->icao) }}" maxlength="4">
                    @error('icao') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('City') }}</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $airport?->city) }}" maxlength="255">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Terminal Type') }}</label>
                    <input type="text" name="terminal_type" class="form-control" value="{{ old('terminal_type', $airport?->terminal_type) }}" maxlength="255">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Latitude') }}</label>
                    <input type="number" step="0.000001" min="-90" max="90" name="latitude" class="form-control" value="{{ old('latitude', $airport?->latitude) }}">
                    @error('latitude') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Longitude') }}</label>
                    <input type="number" step="0.000001" min="-180" max="180" name="longitude" class="form-control" value="{{ old('longitude', $airport?->longitude) }}">
                    @error('longitude') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Facilities') }}</h5></div>
    <div class="card-body">
        <div class="row">
            @foreach($facilityFields as $field => $label)
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ translate($label) }}</label>
                        <select name="{{ $field }}" class="form-control aiz-selectpicker">
                            <option value="">{{ translate('Not Specified') }}</option>
                            @foreach($facilityOptions as $option)
                                <option value="{{ $option }}" @selected(old($field, $airport?->{$field}) === $option)>{{ translate($option) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Authority and Status') }}</h5></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ translate('Airport Authority') }}</label>
                    <input type="text" name="authority_name" class="form-control" value="{{ old('authority_name', $airport?->authority_name) }}" maxlength="255">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ translate('Authority Contact') }}</label>
                    <textarea name="authority_contact" class="form-control" rows="3">{{ old('authority_contact', $airport?->authority_contact) }}</textarea>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>{{ translate('Status') }} <span class="text-danger">*</span></label>
                    <select name="status" class="form-control aiz-selectpicker" required>
                        <option value="1" @selected((int) old('status', (int) ($airport?->status ?? 1)) === 1)>{{ translate('Active') }}</option>
                        <option value="0" @selected((int) old('status', (int) ($airport?->status ?? 1)) === 0)>{{ translate('Inactive') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="text-right">
    <a href="{{ route('airports.index') }}" class="btn btn-light">{{ translate('Cancel') }}</a>
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
</div>
