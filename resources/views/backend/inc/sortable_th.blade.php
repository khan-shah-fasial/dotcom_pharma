@php
    $column = $column ?? '';
    $label = $label ?? '';
    $labelHtml = $labelHtml ?? null;
    $routeName = $routeName ?? '';
    $sortBy = $sortBy ?? 'id';
    $sortDir = $sortDir ?? 'desc';
    $nextDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';
    $url = $routeName !== ''
        ? route($routeName, array_merge(request()->except('page'), [
            'sort_by' => $column,
            'sort_dir' => $nextDir,
        ]))
        : '#';
    $active = $sortBy === $column;
@endphp
<th class="{{ $thClass ?? '' }}">
    <a href="{{ $url }}" class="text-reset d-inline-flex align-items-end">
        <span>{!! $labelHtml ?? e($label) !!}</span>
        <i class="las {{ $active ? ('la-sort-amount-' . ($sortDir === 'asc' ? 'up' : 'down')) : 'la-sort' }} {{ $active ? '' : 'text-muted' }} ml-1"></i>
    </a>
</th>
