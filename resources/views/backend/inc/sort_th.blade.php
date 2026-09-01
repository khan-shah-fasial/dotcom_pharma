@php
    $sortBy = $sortBy ?? request('sort_by');
    $sortOrder = $sortOrder ?? request('sort_order');
    $class = $class ?? '';
    $url = url()->current() . '?' . http_build_query(array_merge(
        request()->except(['page', 'sort_by', 'sort_order']),
        [
            'sort_by' => $column,
            'sort_order' => ($sortBy === $column && $sortOrder === 'asc') ? 'desc' : 'asc',
        ]
    ));
    $icon = $sortBy !== $column
        ? 'la-sort text-muted'
        : ($sortOrder === 'asc' ? 'la-sort-amount-up' : 'la-sort-amount-down');
@endphp
<th{!! !empty($class) ? ' class="'.$class.'"' : '' !!}>
    <a href="{{ $url }}" class="text-reset text-nowrap">
        {{ $label }}
        <i class="las {{ $icon }}"></i>
    </a>
</th>
