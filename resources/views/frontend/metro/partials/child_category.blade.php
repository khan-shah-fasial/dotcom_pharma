@php
    $value = null;
    for ($i=0; $i < $child_category->level; $i++){
        $value .= '-';
    }
@endphp
<option value="{{ $childCategory->id }}">{{ $value }}{{ $childCategory->getTranslation('name') }}</option>

