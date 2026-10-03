<div class="d-flex align-items-center justify-content-between mb-1">
    <span class="fs-12 mr-2">{{ $label }}</span>
    <label class="aiz-switch aiz-switch-success mb-0">
        <input onchange="{{ $onchange }}" value="{{ $product->id }}" data-field="{{ $field }}"
            data-flag-sync="{{ $product->id }}-{{ $field }}" type="checkbox" @checked($checked)>
        <span class="slider round"></span>
    </label>
</div>
