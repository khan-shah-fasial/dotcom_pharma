@php
    $photoValue = old('photos', $gift->photos ? implode(',', (array) $gift->photos) : '');
    $isActiveChecked = old('is_active', (bool) $gift->is_active);
@endphp

<form enctype="multipart/form-data" action="{{ $action }}" method="POST" class="row g-3">
    @csrf
    <div class="col-md-4">
        <label class="form-label">{{ translate('Name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $gift->name) }}" required>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ translate('Cost') }}</label>
        <input type="number" step="0.01" min="0" name="cost" class="form-control" value="{{ old('cost', $gift->cost) }}" required>
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ translate('Stock') }}</label>
        <input type="number" min="0" name="stock" class="form-control" value="{{ old('stock', $gift->stock) }}" required>
    </div>
    <div class="col-md-4">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" id="giftActive" value="1" {{ $isActiveChecked ? 'checked' : '' }}>
            <label class="form-check-label" for="giftActive">{{ translate('Active') }}</label>
        </div>
    </div>

    <div class="col-md-8">
        <label class="form-label">{{ translate('Description') }}</label>
        <textarea name="description" class="form-control aiz-text-editor" rows="4">{{ old('description', $gift->description) }}</textarea>
    </div>

    <div class="col-md-4">
        <label class="form-label d-block">{{ translate('Images') }}</label>
        <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true">
            <div class="input-group-prepend">
                <div class="input-group-text bg-soft-secondary">{{ translate('Browse') }}</div>
            </div>
            <div class="form-control file-amount">{{ translate('Choose Files') }}</div>
            <input type="hidden" name="photos" class="selected-files" value="{{ $photoValue }}">
        </div>
        <div class="file-preview box sm"></div>
        @if(!empty($photoValue))
            <small class="text-muted d-block mt-1">{{ translate('Existing images will stay unless replaced.') }}</small>
        @endif
    </div>

    <div class="col-12">
        <button type="submit" class="float-right btn btn-primary">{{ $submitLabel ?? translate('Save') }}</button>
        <a href="{{ route('gifts.index') }}" class="btn btn-link text-muted">{{ translate('Back to list') }}</a>
    </div>
</form>
