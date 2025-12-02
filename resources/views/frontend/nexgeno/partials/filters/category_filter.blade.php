@php
  $selectedId   = $selected_category_id ?? null;
  $preChildren  = $preloadedChildren ?? [];
  $roots        = $categories ?? collect();
      $accordionId    = 'filters_accordion';
    $catCollapseId  = 'collapse_categories';
    $catShouldShow = 'show';
@endphp

<style>
  /* dropdown style container */
  /* .cat-dropdown-block {
    border: 1px solid #eee;
    border-radius: 4px;
    background: #fff;
  } */
  .cat-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    /* padding: .45rem .25rem .45rem 0; */
    padding-bottom:10px;
    cursor: pointer;
  }
  .cat-head-title {
    display: flex;
    align-items: center;
    gap: .45rem;
    justify-content: start;
    width: 100%
  }

  .cat-head .count-number-filter{
    font-size: 11px;
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
    font-size: 12px !important;
    color: #333;
    cursor: pointer;
  }

  .cat-radio-label input[type="radio"]:checked + span.cat-name {
  font-weight: 700;
  color: #096c9a;
}

  /* child item */
  .cat-child-item {
    padding: .20rem 0;
  }

  /* keep it neat on mobile drawer too */
  .aiz-filter-sidebar .cat-dropdown-block {
    background: transparent;
    border: 0;
    max-height: 250px;
    overflow-y: auto;
  }

  .js-cat-radio {
    display: none;
  }

  /* .cat-radio-label .count-number-filter{
      position: relative;
      float: right;
      right: -175px;
  } */

  #cat-root {
    max-height: 250px;
    overflow-y: auto;

      /* Firefox scrollbar styling */
  scrollbar-width: thin;           /* options: auto | thin | none */
  scrollbar-color: #888 #f1f1f1;   /* thumb color | track color */
}

.cat-child-item .cat-head{
  padding-top: 0px;
  padding-bottom: 5px !important;
}

#cat-root #children-of-90{
  margin-left: 15px;
  /* border-left: 2px solid #a9a2a2;
  padding-left: 10px; */
}

#children-of-118 {
  margin-left: 15px;
}

.cat-child-item .cat-children-panel{
  margin-left: 15px;
  /* border-left: 2px solid #a9a2a2; */
}

/* Chrome, Edge, Safari scrollbar styling */
#cat-root::-webkit-scrollbar {
  width: 8px;                      /* scrollbar width */
}

#cat-root::-webkit-scrollbar-track {
  background: #f1f1f1;             /* track color */
}

#cat-root::-webkit-scrollbar-thumb {
  background-color: #888;          /* handle color */
  border-radius: 10px;             /* rounded corners */
}

#cat-root::-webkit-scrollbar-thumb:hover {
  background: #555;                /* handle color on hover */
}

</style>

<div id="{{ $accordionId }}">
  <div class=" background-none-filterlight_bg_gray mb-0">
    <div class="fs-14 fw-600 p-3">
      <a
        href="javascript:void(0)"
        class="fs-14 dropdown-toggle filter-section text-dark d-flex align-items-center justify-content-between {{ $catShouldShow ? '' : 'collapsed' }}"
        data-toggle="collapse"
        data-target="#{{ $catCollapseId }}"
        data-parent="#{{ $accordionId }}"
        style="white-space: normal;"
        aria-expanded="{{ $catShouldShow ? 'true' : 'false' }}"
        aria-controls="{{ $catCollapseId }}"
      >
        {{ translate('Categories') }}
      </a>
    </div>

    <div class="collapse {{ $catShouldShow }}" id="{{ $catCollapseId }}">
      <div class="pl-3 pr-3 pb-3 pt-2 cat-dropdown-block" id="cat-root">
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
  </div>
</div>
