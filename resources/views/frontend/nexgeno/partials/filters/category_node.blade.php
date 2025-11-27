@php
  /** @var \App\Models\Category $node */
  $selectedId   = $selected_category_id ?? null;
  $preChildren  = $preloadedChildren ?? [];
  $children     = $preChildren[$node->id] ?? collect();
  $hasKids      = $children instanceof \Illuminate\Support\Collection
                  ? $children->isNotEmpty()
                  : (is_array($children) && count($children) > 0);
  $isChecked    = ($selectedId !== null && (int)$selectedId === (int)$node->id);
  $label        = $node->getTranslation('name');

  $categoryCounts = $categoryCounts ?? [];
  $expandedIds    = $expandedIds ?? [];
  $isExpanded     = $isChecked || in_array($node->id, $expandedIds, true);

  $countForNode   = $categoryCounts[$node->id] ?? 0;
@endphp

<div class="cat-node" data-node-id="{{ $node->id }}">
  <div class="cat-head" data-cat-toggle="{{ $node->id }}">
    <div class="cat-head-title">
      @if($hasKids)
        <span class="cat-arrow {{ $isExpanded ? 'rotated' : '' }}" data-arrow="{{ $node->id }}"><i class="las la-angle-right"></i></span>
      @else
        <span style="width:1.2rem; display:inline-block;"></span>
      @endif
      <label class="cat-radio-label mb-0">
        <input
          type="radio"
          name="category_id"
          class="js-cat-radio"
          value="{{ $node->id }}"
          data-label="{{ $label }}"
          data-has-children="{{ $hasKids ? 1 : 0 }}"
          @checked($isChecked)
        >
        <span class="cat-name fw-400">
          {{ $label }}

        </span>
      </label>
    </div>
    {{-- @if($countForNode > 0) --}}
      <span class="text-muted count-number-filter">({{ $countForNode }})</span>
    {{-- @endif --}}
  </div>

  @if($hasKids)
    <div class="cat-children-panel {{ $isExpanded ? '' : 'd-none' }}" id="children-of-{{ $node->id }}">
      @foreach($children as $child)
        <div class="cat-child-item">
          @include('frontend.'.get_setting('homepage_select').'.partials.filters.category_node', [
            'node'                 => $child,
            'selected_category_id' => $selectedId,
            'preloadedChildren'    => $preChildren,
            'categoryCounts'       => $categoryCounts,
            'expandedIds'          => $expandedIds,
          ])
        </div>
      @endforeach
    </div>
  @endif
</div>
