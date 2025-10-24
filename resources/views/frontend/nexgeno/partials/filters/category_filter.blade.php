@php
  $selectedIds = $selected_category_ids ?? [];
  $preChildren = $preloadedChildren ?? [];
@endphp

<div class="aiz-filter-sidebar light_bg_gray mb-3 p-3">
  <div class="fs-16 fw-700 mb-2">{{ translate('Categories') }}</div>

  @if(!$category_id)
    @foreach($categories as $cat)
      @include('frontend.'.get_setting('homepage_select').'.partials.filters.category_node', [
        'node' => $cat,
        'selectedIds' => $selectedIds,
        'preChildren' => $preChildren,
      ])
    @endforeach
  @else
    <div class="mb-2">
      <span class="badge badge-light px-2 py-1">
        {{ translate('Selected:') }} {{ $category->getTranslation('name') }}
      </span>
    </div>

    @include('frontend.'.get_setting('homepage_select').'.partials.filters.category_node', [
      'node' => $category,
      'selectedIds' => $selectedIds,
      'preChildren' => $preChildren,
    ])
  @endif
</div>
