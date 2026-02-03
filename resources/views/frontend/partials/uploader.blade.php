@props([
    'name',
    'type' => 'document',
    'multiple' => false,
])

@php
    $multipleAttr = $multiple ? 'true' : 'false';
@endphp

<div class="input-group" data-toggle="aizuploader" data-type="{{ $type }}" data-multiple="{{ $multipleAttr }}">
    <div class="input-group-prepend">
        <div class="input-group-text bg-soft-secondary font-weight-medium rounded-0">{{ translate('Browse') }}</div>
    </div>
    <div class="form-control file-amount">{{ translate('Choose File') }}</div>
    <input type="hidden" name="{{ $name }}" class="selected-files">
</div>
<div class="file-preview box sm"></div>
