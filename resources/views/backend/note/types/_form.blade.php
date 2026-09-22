@php
    $name = old('name', $type->name ?? '');
    $slug = old('slug', $type->slug ?? '');
    $status = old('status', isset($type) ? (int) $type->status : 1);
@endphp

<div class="form-group row">
    <label class="col-md-3 col-form-label" for="name">{{ translate('Name') }} *</label>
    <div class="col-md-9">
        <input type="text" id="name" name="name" class="form-control" required maxlength="100"
            value="{{ $name }}" placeholder="{{ translate('e.g. Shipping') }}">
        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label" for="slug">{{ translate('Slug') }}</label>
    <div class="col-md-9">
        <input type="text" id="slug" name="slug" class="form-control" maxlength="50"
            value="{{ $slug }}" placeholder="{{ translate('Auto from name if left blank') }}">
        <small class="text-muted">{{ translate('Stored on notes as type key (lowercase, underscores). Changing slug updates existing notes.') }}</small>
        @error('slug') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label">{{ translate('Status') }}</label>
    <div class="col-md-9">
        <label class="aiz-switch aiz-switch-success mb-0">
            <input type="checkbox" name="status" value="1" {{ $status ? 'checked' : '' }}>
            <span class="slider round"></span>
        </label>
    </div>
</div>
