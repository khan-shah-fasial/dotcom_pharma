@php
  /** @var \App\Models\Category $node */
  $selectedId   = $selected_category_id ?? null;
  $preChildren  = $preloadedChildren ?? [];
  $children     = $preChildren[$node->id] ?? collect();
  $hasKids      = $children instanceof \Illuminate\Support\Collection ? $children->isNotEmpty() : (is_array($children) && count($children) > 0);
  $isChecked    = ($selectedId !== null && (int)$selectedId === (int)$node->id);
  $indentClass  = $node->level > 0 ? 'ml-3' : '';
  $label        = $node->getTranslation('name');
  $childLimit   = 6; // how many children per branch before "View More"
@endphp

<div class="cat-node" data-node-id="{{ $node->id }}">
  <label class="aiz-checkbox mb-2 d-block {{ $indentClass }}">
    <input
      type="radio"
      name="category_id"
      class="js-cat-radio"
      value="{{ $node->id }}"
      data-label="{{ $label }}"
      data-has-children="{{ $hasKids ? 1 : 0 }}"
      @checked($isChecked)
    >
    <span class="aiz-square-check"></span>
    <span class="fs-14 fw-400 text-dark">
      {{ str_repeat('-', max(0, (int)$node->level - 1)) }}{{ $label }}
    </span>
  </label>

  <div class="cat-children" id="children-of-{{ $node->id }}" data-loaded="1">
    @if($hasKids)
      @php $count = $children instanceof \Illuminate\Support\Collection ? $children->count() : count($children); @endphp

      @foreach($children as $idx => $child)
        <div
          class="cat-node-wrap {{ $idx >= $childLimit ? 'is-collapsed' : '' }}"
          data-parent="{{ $node->id }}"
          @if($idx >= $childLimit) data-collapsible="1" @endif
        >
          @include('frontend.'.get_setting('homepage_select').'.partials.filters.category_node', [
            'node'                 => $child,
            'selected_category_id' => $selectedId,
            'preloadedChildren'    => $preChildren,
          ])
        </div>
      @endforeach

      @if($count > $childLimit)
        <button
          type="button"
          class="btn btn-link p-0 fs-12 js-toggle-more"
          data-parent-id="{{ $node->id }}"
          data-more-text="{{ translate('View More') }}"
          data-less-text="{{ translate('View Less') }}"
          data-state="collapsed"
        >
          {{ translate('View More') }} ({{ $count - $childLimit }})
        </button>
      @endif
    @endif
  </div>
</div>
