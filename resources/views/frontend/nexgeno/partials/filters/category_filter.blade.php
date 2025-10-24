<div class="aiz-filter-sidebar light_bg_gray mb-3 p-3">
  <div class="fs-16 fw-700 mb-2">{{ translate('Categories') }}</div>

  @if(!$category_id)
    {{-- Top-level category list (with lazy children holders) --}}
    @foreach($categories as $cat)
      @php $hasKids = $cat->childrenCategories->isNotEmpty(); @endphp
      <label class="aiz-checkbox mb-2 d-block">
        <input type="checkbox"
               class="js-cat-checkbox"
               value="{{ $cat->id }}"
               data-label="{{ $cat->getTranslation('name') }}"
               data-has-children="{{ $hasKids ? 1 : 0 }}">
        <span class="aiz-square-check"></span>
        <span class="fs-14 fw-400 text-dark">{{ $cat->getTranslation('name') }}</span>
      </label>
      <div id="children-of-{{ $cat->id }}" data-loaded="0"></div>
    @endforeach
  @else
    {{-- When inside a specific category route: show the selected + children --}}
    <div class="mb-2">
      <span class="badge badge-light px-2 py-1">
        {{ translate('Selected:') }} {{ $category->getTranslation('name') }}
      </span>
    </div>

    <label class="aiz-checkbox mb-2 d-block">
      <input type="checkbox"
             class="js-cat-checkbox"
             value="{{ $category->id }}"
             data-label="{{ $category->getTranslation('name') }}"
             data-has-children="{{ $category->childrenCategories->isNotEmpty() ? 1 : 0 }}"
             checked>
      <span class="aiz-square-check"></span>
      <span class="fs-14 fw-400 text-dark">{{ $category->getTranslation('name') }}</span>
    </label>

    @foreach($category->childrenCategories as $child)
      @php $hasKids = $child->childrenCategories()->exists(); @endphp
      <label class="aiz-checkbox mb-2 d-block ml-3">
        <input type="checkbox"
               class="js-cat-checkbox"
               value="{{ $child->id }}"
               data-label="{{ $child->getTranslation('name') }}"
               data-has-children="{{ $hasKids ? 1 : 0 }}">
        <span class="aiz-square-check"></span>
        <span class="fs-14 fw-400 text-dark">{{ $child->getTranslation('name') }}</span>
      </label>
      <div id="children-of-{{ $child->id }}" data-loaded="0"></div>
    @endforeach
  @endif
</div>
