@php
    $row = $row ?? null;
    $hsnCode = old('hsn_code', optional($row)->hsn_code);
    $description = old('description', optional($row)->description);
    $taxable = old('taxable', optional($row)->taxable);
    $taxable = $taxable === null || $taxable === '' ? '' : (string) (int) $taxable;
    $taxType = old('tax_type', optional($row)->tax_type ?? '');
    $taxPercent = old('tax_percent');
    if ($taxPercent === null) {
        $taxPercent = \App\Models\HsnMaster::displayRate(optional($row)->tax_percent);
    }
    if (session()->hasOldInput()) {
        $statusOn = (string) old('status') === '1';
    } else {
        $statusOn = $row ? (bool) $row->status : true;
    }
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('HSN Master') }}</h5>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="form-group row">
            <label class="col-md-3 col-from-label" for="hsn_code">{{ translate('HSN Code') }} *</label>
            <div class="col-md-9">
                <input type="text" name="hsn_code" id="hsn_code" class="form-control @error('hsn_code') is-invalid @enderror" value="{{ $hsnCode }}" maxlength="50" required placeholder="{{ translate('HSN Code') }}">
                <small class="text-muted">{{ translate('2 to 8 digits, or longer. Dots and spaces are ignored, so 3004.9099 is the same code as 30049099.') }}</small>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3 col-from-label" for="description">{{ translate('Description') }}</label>
            <div class="col-md-9">
                <textarea name="description" id="description" class="form-control" maxlength="255" rows="3">{{ $description }}</textarea>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3 col-from-label" for="taxable">{{ translate('Taxable') }} *</label>
            <div class="col-md-9">
                <select name="taxable" id="taxable" class="form-control" required>
                    <option value="" @selected($taxable === '')>{{ translate('Select') }}</option>
                    <option value="1" @selected($taxable === '1')>{{ translate('Yes') }}</option>
                    <option value="0" @selected($taxable === '0')>{{ translate('No') }}</option>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3 col-from-label" for="tax_type">{{ translate('Tax Type') }} *</label>
            <div class="col-md-9">
                <select name="tax_type" id="tax_type" class="form-control" required>
                    <option value="" @selected($taxType === '' || $taxType === null)>{{ translate('Select') }}</option>
                    @foreach (\App\Models\TaxMaster::KINDS as $key => $label)
                        <option value="{{ $key }}" @selected($taxType === $key)>{{ translate($label) }}</option>
                    @endforeach
                </select>
                <small class="text-muted">{{ translate('Taxable = GST extra. Inclusive = GST already in price. Exempted = no tax. LUT = no tax, for international customers only.') }}</small>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3 col-from-label" for="tax_percent">{{ translate('Tax %') }} *</label>
            <div class="col-md-9">
                <input type="number" lang="en" step="0.0001" min="0" max="100" name="tax_percent" id="tax_percent" class="form-control @error('tax_percent') is-invalid @enderror" value="{{ $taxPercent }}">
                <small class="text-muted">{{ translate('Not taxable stores 0%. A blank percent on the list means no rate has been set yet.') }}</small>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Status') }}</label>
            <div class="col-md-9">
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" name="status" value="1" @checked($statusOn)>
                    <span class="slider round"></span>
                </label>
            </div>
        </div>

        <div class="form-group mb-0 text-right">
            <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
        </div>
    </div>
</div>
