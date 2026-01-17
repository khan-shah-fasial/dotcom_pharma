<option value="0">{{ translate('No Parent') }}</option>
@foreach ($groups as $p_group)
    <option value="{{ $p_group->id }}">{{ $p_group->getTranslation('name') }}</option>
    @foreach ($p_group->childrenGroups as $childGroup)
        @include('backend.product.groups.child_group_option', ['child_group' => $childGroup])
    @endforeach
@endforeach
