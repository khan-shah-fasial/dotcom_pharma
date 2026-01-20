@php
    $children = $category->childrenCategories ?? collect();
    $hasChildren = $children->isNotEmpty();
    $toggleId = 'mobile-drawer-' . $category->id . '-' . $level;
@endphp

<li class="mobile-drawer__list-item" style="--mobile-drawer-level: {{ $level }}">
    <div class="mobile-drawer__item">
        <a class="mobile-drawer__link @if ($hasChildren) mobile-drawer__link--has-children @endif"
            href="/category/{{ $category->slug }}"
            @if ($hasChildren) data-submenu-target="{{ $toggleId }}" @endif>
            {{ $category->name }}
        </a>
        @if ($hasChildren)
            <label class="mobile-drawer__chevron" for="{{ $toggleId }}"
                aria-label="{{ $category->name }} {{ translate('submenu') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </label>
        @endif
    </div>

    @if ($hasChildren)
        <input type="checkbox" id="{{ $toggleId }}" class="mobile-drawer__submenu-toggle">
        <div class="mobile-drawer__submenu">
            <ul class="mobile-drawer__list mobile-drawer__list--nested">
                @foreach ($children as $child)
                    @include('frontend.inc.mobile_category_menu', [
                        'category' => $child,
                        'level' => $level + 1,
                    ])
                @endforeach
            </ul>
        </div>
    @endif
</li>
