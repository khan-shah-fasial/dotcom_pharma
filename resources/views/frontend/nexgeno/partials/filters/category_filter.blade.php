@php
  $selectedId   = $selected_category_id ?? null;
  $preChildren  = $preloadedChildren ?? [];
  $roots        = $categories ?? collect();
@endphp

<style>
  /* dropdown style container */
  .cat-dropdown-block {
    border: 1px solid #eee;
    border-radius: 4px;
    background: #fff;
  }
  .cat-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    padding: .45rem .25rem .45rem 0;
    cursor: pointer;
  }
  .cat-head-title {
    display: flex;
    align-items: center;
    gap: .45rem;
  }
  .cat-children-panel {
    margin-left: 1.7rem;  /* sit under arrow */
    padding-bottom: .35rem;
  }
  .cat-arrow {
    display: inline-flex;
    width: 1.2rem;
    justify-content: center;
    transition: transform .2s ease;
    color: #555;
  }
  .cat-arrow.rotated {
    transform: rotate(90deg);
  }

  .cat-radio-label {
    display: flex;
    align-items: center;
    gap: .35rem;
  }
  .cat-radio-label span.cat-name {
    font-size: .875rem;
    color: #333;
  }

  /* child item */
  .cat-child-item {
    padding: .20rem 0;
  }

  /* keep it neat on mobile drawer too */
  .aiz-filter-sidebar .cat-dropdown-block {
    background: transparent;
    border: 0;
  }
</style>

<div class="aiz-filter-sidebar light_bg_gray mb-3 p-3">
  <div class="fs-16 fw-700 mb-2">{{ translate('Categories') }}</div>

  <div id="cat-root" class="cat-dropdown-block">
    @foreach($roots as $cat)
      @include('frontend.'.get_setting('homepage_select').'.partials.filters.category_node', [
        'node'                 => $cat,
        'selected_category_id' => $selectedId,
        'preloadedChildren'    => $preChildren,
        'categoryCounts'       => $categoryCounts ?? [],
        'expandedIds'          => $expandedIds ?? [],
      ])
    @endforeach
  </div>
</div>
