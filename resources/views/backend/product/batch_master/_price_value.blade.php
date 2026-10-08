@php
    $price = $price ?? null;
    $value = $value ?? null;
    $hidden = !empty($hidden);
    $priceText = ($price === null || $price === '') ? '—' : $price;
    $valueText = ($value === null || $value === '') ? '—' : $value;
@endphp
<div class="bm-pv fs-12 {{ $class ?? '' }}">
    <div>{{ translate('Price') }}: {{ $hidden ? '••••' : $priceText }}</div>
    <div class="text-muted">{{ translate('Value') }}: {{ $hidden ? '••••' : $valueText }}</div>
</div>
