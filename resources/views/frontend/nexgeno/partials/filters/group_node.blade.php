@php
  /** @var \App\Models\Group $node */
  $selectedId   = $selected_group_id ?? null;
  $preChildren  = $preloadedGroupChildren ?? [];
  $children     = $preChildren[$node->id] ?? collect();
  $hasKids      = $children instanceof \Illuminate\Support\Collection
                  ? $children->isNotEmpty()
                  : (is_array($children) && count($children) > 0);
  $isChecked    = ($selectedId !== null && (int)$selectedId === (int)$node->id);
  $label        = $node->getTranslation('name');

  $groupCounts     = $groupCounts ?? [];
  $groupExpandedIds = $groupExpandedIds ?? [];
  $isExpanded     = $isChecked || in_array($node->id, $groupExpandedIds, true);

  $countForNode   = $groupCounts[$node->id] ?? 0;
@endphp

<div class="cat-node" data-node-id="{{ $node->id }}">
  <div class="grp-head" data-grp-toggle="{{ $node->id }}">
    <div class="grp-head-title">
      @if($hasKids)
        <span class="grp-arrow {{ $isExpanded ? 'rotated' : '' }}" data-grp-arrow="{{ $node->id }}"><i class="las la-angle-right"></i></span>
      @else
        <span style="width:1.2rem; display:inline-block;"></span>
      @endif
      <label class="grp-radio-label mb-0">
        <input
          type="radio"
          name="group_id"
          class="js-grp-radio"
          value="{{ $node->id }}"
          data-label="{{ $label }}"
          data-has-children="{{ $hasKids ? 1 : 0 }}"
          @checked($isChecked)
        >
        <span class="grp-name fw-400">
          {{ $label }}
        </span>
      </label>
    </div>
    <span class="text-muted count-number-filter">({{ $countForNode }})</span>
  </div>

  @if($hasKids)
    <div class="grp-children-panel {{ $isExpanded ? '' : 'd-none' }}" id="group-children-of-{{ $node->id }}">
      @foreach($children as $child)
        <div class="grp-child-item">
          @include('frontend.'.get_setting('homepage_select').'.partials.filters.group_node', [
            'node'                   => $child,
            'selected_group_id'      => $selectedId,
            'preloadedGroupChildren' => $preChildren,
            'groupCounts'            => $groupCounts,
            'groupExpandedIds'       => $groupExpandedIds,
          ])
        </div>
      @endforeach
    </div>
  @endif
</div>
