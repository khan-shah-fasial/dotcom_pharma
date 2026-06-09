@php
    $lead = $lead ?? null;
@endphp

@csrf
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
    <div class="col-md-4">
        <input type="email" name="email" class="form-control" value="{{ old('email', $lead->email ?? '') }}">
        @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-1 col-form-label">{{ translate('Phone') }}</label>
    <div class="col-md-4">
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $lead->phone ?? '') }}">
        @error('phone') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Source') }}</label>
    <div class="col-md-4">
        <select name="source_id" class="form-control aiz-selectpicker" data-live-search="true">
            <option value="">{{ translate('Select Source') }}</option>
            @foreach ($sources as $source)
                <option value="{{ $source->id }}" @if(old('source_id', $lead->source_id ?? '') == $source->id) selected @endif>{{ $source->name }}</option>
            @endforeach
        </select>
        @error('source_id') <span class="text-danger small">{{ $message }}</span> @enderror
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
