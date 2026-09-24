<div class="form-group">
    <label>{{ translate('Name') }} <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $seriesItem->name ?? '') }}" required maxlength="255">
    @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
</div>
<div class="form-group">
    <label>{{ translate('Code') }}</label>
    <input type="text" name="code" class="form-control" value="{{ old('code', $seriesItem->code ?? '') }}" maxlength="100">
    @error('code') <span class="text-danger small">{{ $message }}</span> @enderror
</div>
<div class="form-group">
    <label>{{ translate('Description') }}</label>
    <textarea name="description" rows="4" class="form-control">{{ old('description', $seriesItem->description ?? '') }}</textarea>
    @error('description') <span class="text-danger small">{{ $message }}</span> @enderror
</div>
<div class="form-group">
    <label class="aiz-switch aiz-switch-success mb-0">
        <input type="checkbox" name="status" value="1" @checked(old('status', $seriesItem->status ?? 1))>
        <span class="slider round"></span>
    </label>
    <span class="ml-2">{{ translate('Active') }}</span>
</div>
<div class="form-group mb-0 text-right">
    <a href="{{ route('series.index') }}" class="btn btn-soft-secondary">{{ translate('Back') }}</a>
    <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
</div>
