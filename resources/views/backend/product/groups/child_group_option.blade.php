@php
    $value = null;
    for ($i = 0; $i < $child_group->level; $i++) {
        $value .= '--';
    }
@endphp
<option value="{{ $child_group->id }}">{{ $value.' '.$child_group->getTranslation('name') }}</option>
@if ($child_group->childrenGroups)
    @foreach ($child_group->childrenGroups as $childGroup)
        @include('backend.product.groups.child_group_option', ['child_group' => $childGroup])
    @endforeach
@endif
