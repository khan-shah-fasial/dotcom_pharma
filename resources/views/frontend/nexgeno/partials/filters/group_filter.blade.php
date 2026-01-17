@php
  $selectedId   = $selected_group_id ?? null;
  $preChildren  = $preloadedGroupChildren ?? [];
  $roots        = $groupsTree ?? collect();
  $accordionId  = 'filters_accordion_group';
  $collapseId   = 'collapse_groups';
  $shouldShow   = 'show';
@endphp

<style>
  #group-root {
    max-height: 250px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #888 #f1f1f1;
  }
  #group-root::-webkit-scrollbar { width: 8px; }
  #group-root::-webkit-scrollbar-track { background: #f1f1f1; }
  #group-root::-webkit-scrollbar-thumb { background-color: #888; border-radius: 10px; }
  #group-root::-webkit-scrollbar-thumb:hover { background: #555; }

  .grp-head { display:flex; align-items:center; justify-content:space-between; gap:.5rem; padding-bottom:10px; cursor:pointer; }
  .grp-head-title { display:flex; align-items:center; gap:.45rem; width:100%; }
  .grp-arrow { display:inline-flex; width:1.2rem; justify-content:center; transition:transform .2s ease; color:#555; }
  .grp-arrow.rotated { transform: rotate(90deg); }
  .grp-radio-label { display:flex; align-items:center; gap:.35rem; }
  .grp-radio-label span.grp-name { font-size:12px !important; color:#333; cursor:pointer; }
  .grp-radio-label input[type="radio"]:checked + span.grp-name { font-weight:700; color:#096c9a; }
  .grp-child-item { padding:.20rem 0; }
  .grp-children-panel { margin-left:1.7rem; padding-bottom:.35rem; }
  .js-grp-radio { display:none; }
</style>

<div class="card px-3 pt-3 pb-2 shadow-none border-0">
  <div id="{{ $accordionId }}" class="accordion">
    <div class="">
      <a class="d-flex align-items-center justify-content-between text-reset" data-toggle="collapse" href="#{{ $collapseId }}" role="button" aria-expanded="true" aria-controls="{{ $collapseId }}">
        <span class="fs-14 fw-700">{{ translate('Medical Group') }}</span>
        <span class="la la-angle-down fs-16"></span>
      </a>
    </div>

    <div class="collapse {{ $shouldShow }}" id="{{ $collapseId }}">
      <div class="pl-3 pr-3 pb-3 pt-2 cat-dropdown-block" id="group-root">
        @foreach($roots as $grp)
          @include('frontend.'.get_setting('homepage_select').'.partials.filters.group_node', [
            'node'                => $grp,
            'selected_group_id'   => $selectedId,
            'preloadedGroupChildren' => $preChildren,
            'groupCounts'         => $groupCounts ?? [],
            'groupExpandedIds'    => $groupExpandedIds ?? [],
          ])
        @endforeach
      </div>
    </div>
  </div>
</div>
