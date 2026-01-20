@php
    $value = null;
    for ($i = 0; $i < $child_group->level; $i++) {
        $value .= '--';
    }
    $child_groups = $child_group->groups->whereNotIn('id', App\Utility\GroupUtility::children_ids($group->id, true))->where('id', '!=', $group->id);
@endphp
<option value="{{ $child_group->id }}">{{ $value.' '.$child_group->getTranslation('name') }}</option>
@if (count($child_groups) > 0)
    @foreach ($child_groups as $childGroup)
        @include('backend.product.groups.child_group_edit', ['child_group' => $childGroup])
    @endforeach
@endif
