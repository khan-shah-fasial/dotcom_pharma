{{-- resources/views/frontend/{{ get_setting('homepage_select') }}/partials/filters/category_node.blade.php --}}

@php
  /** @var \App\Models\Category $node */
  $selectedIds = $selectedIds ?? [];
  $preChildren = $preChildren ?? [];
  $hasPreloaded = isset($preChildren[$node->id]) && count($preChildren[$node->id]) > 0;
  $hasKids = $hasPreloaded || $node->childrenCategories()->exists();
  $isChecked = in_array($node->id, $selectedIds, true);
  $indentClass = $node->level > 0 ? 'ml-3' : '';
  $label = $node->getTranslation('name');
@endphp

<label class="aiz-checkbox mb-2 d-block {{ $indentClass }}">
  <input
    type="checkbox"
    class="js-cat-checkbox"
    value="{{ $node->id }}"
    data-label="{{ $label }}"
    data-has-children="{{ $hasKids ? 1 : 0 }}"
    @checked($isChecked)
  >
  <span class="aiz-square-check"></span>
  <span class="fs-14 fw-400 text-dark">{{ str_repeat('-', max(0, (int)$node->level - 1)) }}{{ $label }}</span>
</label>

<div id="children-of-{{ $node->id }}" data-loaded="{{ $hasPreloaded ? '1' : '0' }}">
  @if($hasPreloaded)
    @foreach($preChildren[$node->id] as $child)
      @include('frontend.'.get_setting('homepage_select').'.partials.filters.category_node', [
        'node' => $child,
        'selectedIds' => $selectedIds,
        'preChildren' => $preChildren,
      ])
    @endforeach
  @endif
</div>
