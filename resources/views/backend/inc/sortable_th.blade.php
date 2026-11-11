@php
    $column = $column ?? '';
    $label = $label ?? '';
    $labelHtml = $labelHtml ?? null;
    $routeName = $routeName ?? '';
    $routeParams = $routeParams ?? [];
    $sortBy = $sortBy ?? 'id';
    $sortDir = $sortDir ?? 'desc';
    // Optional: lets two sortable tables share one page without clashing.
    $sortParam = $sortParam ?? 'sort_by';
    $dirParam = $dirParam ?? 'sort_dir';
    $pageParam = $pageParam ?? 'page';
    $linkClass = $linkClass ?? '';
    $nextDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';
    $url = $routeName !== ''
        ? route($routeName, array_merge($routeParams, request()->except($pageParam), [
            $sortParam => $column,
            $dirParam => $nextDir,
        ]))
        : '#';
    $active = $sortBy === $column;
@endphp
<th class="{{ $thClass ?? '' }}" @if(!empty($breakpoints)) data-breakpoints="{{ $breakpoints }}" @endif>
    <a href="{{ $url }}" class="listing-sort-link d-inline-flex align-items-end {{ $linkClass }}">
        <span>{!! $labelHtml ?? e($label) !!}</span>
        <i class="las {{ $active ? ('la-sort-amount-' . ($sortDir === 'asc' ? 'up' : 'down')) : 'la-sort' }} {{ $active ? '' : 'text-muted' }} ml-1"></i>
    </a>
</th>
