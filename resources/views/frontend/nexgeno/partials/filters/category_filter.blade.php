@php
  $selectedId   = $selected_category_id ?? null;
  $preChildren  = $preloadedChildren ?? [];
  $roots        = $categories ?? collect();
  $rootLimit    = 8; // how many root items to show before "View More"
@endphp

<style>
  /* 🌳 Category Tree Indentation (Hummingbird-style) */
.cat-node {
  position: relative;
}

/* Base level (root categories) */
.cat-node[data-node-id] {
  padding-left: 0.75rem;
}

/* Multi-level indentation using data-depth or .cat-children nesting */
.cat-children .cat-node {
  padding-left: 0rem;
  border-left: 0px dashed #ddd;
  margin-left: 0rem;
}

/* Each deeper level gets progressively indented */
.cat-children .cat-children .cat-node {
  padding-left: 2.25rem;
}

.cat-children .cat-children .cat-children .cat-node {
  padding-left: 3rem;
}

.cat-children .cat-children .cat-children .cat-children .cat-node {
  padding-left: 3.75rem;
}

/* Optional: subtle connector line styling */
.cat-children {
  position: relative;
}

.cat-children::before {
  content: "";
  position: absolute;
  left: 0.75rem;
  top: 0;
  bottom: 0;
  border-left: 0px dashed #ddd;
}

/* Tidy spacing and readability */
.cat-node label {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

  /* hide overflow items */
  .cat-node-wrap.is-collapsed { display: none; }
</style>

<div class="aiz-filter-sidebar light_bg_gray mb-3 p-3">
  <div class="fs-16 fw-700 mb-2">{{ translate('Categories') }}</div>

  <div id="cat-root">
    @foreach($roots as $i => $cat)
      <div
        class="cat-node-wrap root {{ $i >= $rootLimit ? 'is-collapsed' : '' }}"
        data-parent="root"
        @if($i >= $rootLimit) data-collapsible="1" @endif
      >
        @include('frontend.'.get_setting('homepage_select').'.partials.filters.category_node', [
          'node'                 => $cat,
          'selected_category_id' => $selectedId,
          'preloadedChildren'    => $preChildren,
        ])
      </div>
    @endforeach
  </div>

  @if($roots->count() > $rootLimit)
    <button
      type="button"
      class="btn btn-link p-0 fs-12 js-toggle-more"
      data-parent-id="root"
      data-more-text="{{ translate('View More') }}"
      data-less-text="{{ translate('View Less') }}"
      data-state="collapsed"
    >
      {{ translate('View More') }} ({{ $roots->count() - $rootLimit }})
    </button>
  @endif
</div>
