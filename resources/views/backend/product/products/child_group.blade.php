@php
    $value = null;
    for ($i = 0; $i < $child_group->level; $i++){
        $value .= '-';
    }
@endphp
<li id="{{ $child_group->id }}">{{ $value }}{{ $child_group->getTranslation('name') }}</li>
@if ($child_group->childrenGroups)
    @foreach ($child_group->childrenGroups as $childGroup)
        @include('backend.product.products.child_group', ['child_group' => $childGroup])
    @endforeach
@endif
