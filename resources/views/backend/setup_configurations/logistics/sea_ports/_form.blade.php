@php
    $seaPort = $seaPort ?? null;
    $supportFields = [
        'customs_port' => 'Customs Port',
        'export_supported' => 'Export Supported',
        'import_supported' => 'Import Supported',
        'container_supported' => 'Container Supported',
        'bulk_cargo_supported' => 'Bulk Cargo Supported',
        'liquid_cargo_supported' => 'Liquid Cargo Supported',
        'ro_ro_supported' => 'Ro-Ro Supported',
        'cruise_supported' => 'Cruise Supported',
        'ferry_supported' => 'Ferry Supported',
        'fishing_supported' => 'Fishing Supported',
        'ship_repair_supported' => 'Ship Repair Supported',
    ];
    $supportOptions = ['Yes', 'No', 'Limited', 'N/A'];
@endphp

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Port Identification') }}</h5></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ translate('Country') }} <span class="text-danger">*</span></label>
                    <select name="country_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                        <option value="">{{ translate('Select Country') }}</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) old('country_id', $seaPort?->country_id) === (string) $country->id)>
                                {{ $country->name }} ({{ $country->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('country_id') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ translate('Port Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $seaPort?->name) }}" required maxlength="255">
                    @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>{{ translate('UN/LOCODE') }} <span class="text-danger">*</span></label>
                    <input type="text" name="un_locode" class="form-control text-uppercase" value="{{ old('un_locode', $seaPort?->un_locode) }}" required maxlength="10">
                    @error('un_locode') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>{{ translate('Continent') }}</label>
                    <input type="text" name="continent" class="form-control" value="{{ old('continent', $seaPort?->continent) }}" maxlength="255">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>{{ translate('State / Region') }}</label>
                    <input type="text" name="state_region" class="form-control" value="{{ old('state_region', $seaPort?->state_region) }}" maxlength="255">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Port Details') }}</h5></div>
    <div class="card-body">
        <div class="row">
            @foreach([
                'port_type' => 'Port Type',
                'terminal_type' => 'Terminal Type',
                'classification' => 'Major / Minor Classification',
                'water_body' => 'Water Body',
                'ocean' => 'Ocean',
                'nearest_airport' => 'Nearest Airport',
            ] as $field => $label)
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ translate($label) }}</label>
                        <input type="text" name="{{ $field }}" class="form-control" value="{{ old($field, $seaPort?->{$field}) }}" maxlength="255">
                        @error($field) <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>
            @endforeach
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Latitude') }}</label>
                    <input type="number" step="0.000001" min="-90" max="90" name="latitude" class="form-control" value="{{ old('latitude', $seaPort?->latitude) }}">
                    @error('latitude') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ translate('Longitude') }}</label>
                    <input type="number" step="0.000001" min="-180" max="180" name="longitude" class="form-control" value="{{ old('longitude', $seaPort?->longitude) }}">
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
            @foreach($supportFields as $field => $label)
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ translate($label) }}</label>
                        <select name="{{ $field }}" class="form-control aiz-selectpicker">
                            <option value="">{{ translate('Not Specified') }}</option>
                            @foreach($supportOptions as $option)
                                <option value="{{ $option }}" @selected(old($field, $seaPort?->{$field}) === $option)>{{ translate($option) }}</option>
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
                    <label>{{ translate('Port Authority') }}</label>
                    <input type="text" name="authority_name" class="form-control" value="{{ old('authority_name', $seaPort?->authority_name) }}" maxlength="255">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>{{ translate('Authority Contact') }}</label>
                    <textarea name="authority_contact" class="form-control" rows="3">{{ old('authority_contact', $seaPort?->authority_contact) }}</textarea>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>{{ translate('Status') }} <span class="text-danger">*</span></label>
                    <select name="status" class="form-control aiz-selectpicker" required>
                        <option value="1" @selected((int) old('status', (int) ($seaPort?->status ?? 1)) === 1)>{{ translate('Active') }}</option>
                        <option value="0" @selected((int) old('status', (int) ($seaPort?->status ?? 1)) === 0)>{{ translate('Inactive') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="text-right">
    <a href="{{ route('sea-ports.index') }}" class="btn btn-light">{{ translate('Cancel') }}</a>
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
</div>
