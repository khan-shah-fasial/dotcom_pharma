@extends('frontend.layouts.app')

@section('meta_title'){{ $meta_title ?? ($category?->meta_title ?? get_setting('meta_title')) }}@stop
@section('meta_description'){{ $meta_description ?? ($category?->meta_description ?? get_setting('meta_description')) }}@stop

@section('content')
<section class="pt-2">
  <div class="container">
    <ul class="breadcrumb bg-transparent p-0 justify-content-start mb-0 pb-3">
      <li class="breadcrumb-item"><a class="text-reset fs-14" href="{{ route('home') }}">{{ translate('Home') }}</a></li>
      <li class="breadcrumb-item">
        @if(!$category_id) {{ translate('All Products') }}
        @else <a class="text-reset fs-14" href="{{ route('search') }}">{{ translate('All Products') }}</a>
        @endif
      </li>
      @if($category_id)
        <li class="breadcrumb-item text-dark fw-400 fs-14" id="crumb-selected">
          "{{ $category->getTranslation('name') }}"
        </li>
      @endif
    </ul>
  </div>
</section>

<section class="mb-4 pt-2">
  <div class="container sm-px-0">
    <div class="row">
      <!-- Left Filters -->
      <div class="col-xl-3">

        {{-- CATEGORY FILTER COMPONENT --}}
        {{-- CATEGORY FILTER (server-rendered tree with preloaded branches) --}}
        @include('frontend.'.get_setting('homepage_select').'.partials.filters.category_filter', [
          'categories'            => $categories,
          'category'              => $category,
          'category_id'           => $category_id,
          'selected_category_ids' => $selected_category_id ?? null,
          'preloadedChildren'     => $preloadedChildren ?? [],
          'expandedIds'           => $expandedIds ?? [],
        ])

        {{-- PRICE FILTER COMPONENT --}}
        @include('frontend.'.get_setting('homepage_select').'.partials.filters.price_filter', [
          'globalMin'   => $globalMin,
          'globalMax'   => $globalMax,
          'min_price'   => $min_price,
          'max_price'   => $max_price,
        ])

        {{-- ATTRIBUTES FILTER COMPONENT --}}
        <div id="attributes-filter">
          @include('frontend.'.get_setting('homepage_select').'.partials.filters.attributes_filter', [
            'attributes' => $attributes,
            'selected_attribute_values' => $selected_attribute_values ?? [],
          ])
        </div>

        {{-- COLOR FILTER (AJAX-replaceable) --}}
        @include('frontend.'.get_setting('homepage_select').'.partials.filters.color_filter', [
          'colors'         => $colors,
          'selected_color' => $color ?? null,
        ])

      </div>

      <!-- Products -->
      <div class="col-xl-9">
        <div id="page-metrics" class="mb-2 text-muted fs-12">
          Per page: {{ $perPage }} • Total pages: {{ $totalPages }} • Total products: {{ $total }}
        </div>
        <!-- Active filters toolbar -->
        <div id="active-filters" class="mb-3 d-flex align-items-center flex-wrap gap-2">
          <!-- Pills will be injected by JS -->
          <button id="clear-filters" type="button" class="btn btn-sm btn-outline-secondary d-none">
            {{ translate('Clear all') }}
          </button>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-2">
          <h1 class="fs-20 fs-md-24 fw-700 text-dark text-capitalize mb-0" id="list-title">
            @if($category_id)
              {{ $category->getTranslation('name') }}
            @elseif($query)
              {{ translate('Search result for ') }}"{{ $query }}"
            @else
              {{ translate('All Products') }}
            @endif
          </h1>

          <div class="w-lg-200px">
            <select class="form-control form-control-sm aiz-selectpicker rounded-0" id="sort_by">
              <option value="">{{ translate('Sort by') }}</option>
              <option value="newest"     @selected(($sort_by??'')==='newest')>{{ translate('Newest') }}</option>
              <option value="oldest"     @selected(($sort_by??'')==='oldest')>{{ translate('Oldest') }}</option>
              <option value="price-asc"  @selected(($sort_by??'')==='price-asc')>{{ translate('Price low to high') }}</option>
              <option value="price-desc" @selected(($sort_by??'')==='price-desc')>{{ translate('Price high to low') }}</option>
            </select>
          </div>
        </div>

        {{-- PRODUCT GRID COMPONENT --}}
        <div id="product-grid">
          @include('frontend.'.get_setting('homepage_select').'.partials.product_grid', ['products'=>$products])
        </div>

        {{-- Infinite scroll sentinel --}}
        <div id="infinite-sentinel" class="py-4 text-center text-muted">{{ translate('Loading…') }}</div>
      </div>
    </div>
  </div>
</section>
@endsection

@section('script')
<script>
(function() {
  const ajaxUrl        = "{{ route('search.ajax.products') }}";
  const baseBrowseUrl  = "{{ route('search') }}"; // used when clearing to drop /category/{slug}

  const state = {
    route_category_id: @json($category_id),                 // page arrived via /category/{slug}?
    route_brand_id:    @json($brand_id ?? null),
    keyword:           @json($query ?? ''),
    sort_by:           @json($sort_by ?? ''),
    min_price:         @json($min_price ?? null),
    max_price:         @json($max_price ?? null),
    selected_attribute_values: @json($selected_attribute_values ?? []),
    color: @json($color ?? null),
    // SINGLE category id
    category_id:            @json($selected_category_id ?? ($category_id ?: null)),
    selected_category_name: @json($selected_category_name ?? null),

    page_size: 24,
    next_page_url: @json($ajaxNextPageUrl),
    loading: false,
    append: false,

    scoped_min: @json($scopedMin ?? null),
    scoped_max: @json($scopedMax ?? null),
  };

  // ========= Helpers =========
  const qs  = (s,ctx=document)=>ctx.querySelector(s);
  const qsa = (s,ctx=document)=>Array.from(ctx.querySelectorAll(s));

  const productGrid   = qs('#product-grid');
  const sentinel      = qs('#infinite-sentinel');
  const sortSelect    = qs('#sort_by');
  const pillsBar      = qs('#active-filters');
  const clearBtn      = qs('#clear-filters');

  function gatherCategorySelection() {
    const checked = qs('.js-cat-radio:checked');
    state.category_id = checked ? Number(checked.value) : null;

    const label = checked?.dataset?.label || null;
    const crumb = qs('#crumb-selected');
    if (crumb) crumb.textContent = label ? `"${label}"` : "{{ translate('All Products') }}";

    const title = qs('#list-title');
    if (title) title.textContent = label || "{{ translate('All Products') }}";
  }

  function gatherAttributeSelections() {
    state.selected_attribute_values = qsa('.js-attr-checkbox:checked').map(i => i.value);
  }

  function buildParams(url) {
    const u = new URL(url, window.location.origin);
    const p = u.searchParams;

    if (state.keyword)   p.set('keyword', state.keyword);
    if (state.sort_by)   p.set('sort_by', state.sort_by);
    if (state.min_price != null) p.set('min_price', state.min_price);
    if (state.max_price != null) p.set('max_price', state.max_price);

    // Route scope only if still present
    if (state.route_category_id != null) p.set('route_category_id', state.route_category_id);
    if (state.route_brand_id)            p.set('route_brand_id', state.route_brand_id);

    // Single category
    if (state.category_id != null) p.set('category_id', state.category_id);
    if (state.color) p.set('color', state.color);
    state.selected_attribute_values.forEach(v => p.append('selected_attribute_values[]', v));
    return u.toString();
  }

  function normalizeToAjax(url) {
    const a = new URL(url, window.location.origin);
    const ajax = new URL(ajaxUrl, window.location.origin);
    a.pathname = ajax.pathname;
    return a.toString();
  }

  // ========== Old slider safe-init ==========
  const sliderEl = document.getElementById('input-slider-range');
  if (window.AIZ?.plugins?.noUiSlider && sliderEl && !sliderEl.noUiSlider) {
    AIZ.plugins.noUiSlider();
  }

  window.filter = function () {
    state.append = false;
    fetchProducts(ajaxUrl, false);
  };

  window.rangefilter = function (arg) {
    const low  = Number(arg?.[0] ?? 0);
    const high = Number(arg?.[1] ?? 0);

    const hidLow  = document.querySelector('input[name="min_price"]');
    const hidHigh = document.querySelector('input[name="max_price"]');
    if (hidLow)  hidLow.value  = low;
    if (hidHigh) hidHigh.value = high;

    state.min_price = low;
    state.max_price = high;

    state.append = false;
    fetchProducts(ajaxUrl, false);
  };

  function syncSliderBounds(sMin, sMax) {
    const el = document.getElementById('input-slider-range');
    if (!el || !el.noUiSlider) return;
    try {
      el.noUiSlider.updateOptions({ range: { min: Number(sMin), max: Number(sMax) } }, false);
    } catch(_) {}
    let low  = (state.min_price != null) ? Number(state.min_price) : Number(sMin);
    let high = (state.max_price != null) ? Number(state.max_price) : Number(sMax);
    low  = Math.max(Number(sMin), Math.min(low,  Number(sMax)));
    high = Math.max(Number(sMin), Math.min(high, Number(sMax)));
    if (low > high) [low, high] = [high, low];
    try { el.noUiSlider.set([low, high]); } catch(_) {}
  }

  if (state.scoped_min != null && state.scoped_max != null) {
    setTimeout(() => syncSliderBounds(Number(state.scoped_min), Number(state.scoped_max)), 0);
  }

  async function fetchProducts(url, append=false) {
    if (state.loading) return;
    state.loading = true;
    sentinel.style.opacity = '1';

    const finalUrl = normalizeToAjax(buildParams(url));
    const res = await fetch(finalUrl, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
    const json = await res.json();

    if (typeof json.per_page !== 'undefined') {
      state.page_size = Number(json.per_page);
    }

    const metrics = document.querySelector('#page-metrics');
    if (metrics && json.per_page !== undefined && json.total_pages !== undefined && json.total !== undefined) {
      metrics.textContent = `Per page: ${json.per_page} • Total pages: ${json.total_pages} • Total products: ${json.total}`;
    }

    if (!append) productGrid.innerHTML = json.html;
    else productGrid.insertAdjacentHTML('beforeend', json.html);

    // swap attributes panel built from choice_options
    const attrHolder = document.getElementById('attributes-filter');
    if (attrHolder && typeof json.attributes_html !== 'undefined') {
      attrHolder.innerHTML = json.attributes_html;
    }

    // ---- FIX: replace if exists, otherwise insert after attributes ----
    if (typeof json.colors_html !== 'undefined') {
      const existing = document.getElementById('color-filter');

      if (json.colors_html) {
        if (existing) {
          // replace current color block
          existing.outerHTML = json.colors_html;
        } else if (attrHolder) {
          // first time: append AFTER the attributes section
          attrHolder.insertAdjacentHTML('afterend', json.colors_html);
        }
      } else if (existing) {
        // response says "no colors" -> remove if present
        existing.remove();
      }
    }

    state.next_page_url = json.next_page_url;
    state.loading = false;
    sentinel.style.opacity = state.next_page_url ? '1' : '0.3';

    if (json.scoped_min !== undefined && json.scoped_max !== undefined) {
      syncSliderBounds(Number(json.scoped_min), Number(json.scoped_max));
    }

    // Shareable URL (drop-in)
    const share = new URL(window.location.href);
    share.search = new URL(buildParams(ajaxUrl)).search;
    window.history.replaceState(null, '', share.toString());

    renderPills();
  }

  // ======== Pills (single category) ========
  function hasActiveFilters() {
    return (state.category_id != null)
        || (state.selected_attribute_values.length > 0)
        || (state.min_price != null && state.max_price != null)
        || (state.color != null);
  }

  function renderPills() {
    qsa('.js-filter-pill', pillsBar).forEach(n => n.remove());

    if (state.category_id != null) {
      const input = qs(`.js-cat-radio[value="${CSS.escape(String(state.category_id))}"]`);
      const label = input?.dataset?.label || '{{ translate("Category") }}';
      addPill({ type:'category', value:String(state.category_id), text: label });
    }

    state.selected_attribute_values.forEach(val => {
      addPill({ type:'attribute', value:val, text: val });
    });

    if (state.color != null) {
      addPill({ type:'color', value:state.color, text: state.color });
    }

    if (state.min_price != null && state.max_price != null) {
      addPill({ type:'price', value:'price', text: `${state.min_price} – ${state.max_price}` });
    }

    clearBtn.classList.toggle('d-none', !hasActiveFilters());
  }

  function addPill({type, value, text}) {
    const pill = document.createElement('button');
    pill.type = 'button';
    pill.className = 'btn btn-sm btn-outline-primary js-filter-pill mr-2 mb-2';
    pill.dataset.type = type;
    pill.dataset.value = value;
    pill.innerHTML = `${escapeHtml(text)} <span aria-hidden="true">×</span>`;
    pill.addEventListener('click', () => removePill(type, value));
    pillsBar.insertBefore(pill, clearBtn);
  }

  function removePill(type, value) {
    if (type === 'category') {
      const checked = qs('.js-cat-radio:checked');
      if (checked) checked.checked = false;
      state.category_id = null;
      gatherCategorySelection();
    } else if (type === 'attribute') {
      const input = qs(`.js-attr-checkbox[value="${cssValue(value)}"]`);
      if (input) input.checked = false;
      gatherAttributeSelections();
    }  else if (type === 'color') {
      // Unselect color radio (= choose blank)
      const checked = document.querySelector('.js-color-radio:checked');
      if (checked) checked.checked = false;
      const any = document.querySelector('.js-color-radio[value=""]');
      if (any) any.checked = true;
      state.color = null;
    } else if (type === 'price') {
      const minInput = qs('#min_price');
      const maxInput = qs('#max_price');
      state.min_price = null;
      state.max_price = null;
      if (minInput && maxInput) {
        minInput.value = minInput.min ?? '';
        maxInput.value = maxInput.max ?? '';
      }
    }
    state.append = false;
    fetchProducts(ajaxUrl, false);
  }

  function cssValue(v){ return v.replace(/(["\\])/g,'\\$1'); }
  function escapeHtml(s){ return (s ?? '').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

  // ========= Events =========
  // Sort
  sortSelect.addEventListener('change', () => {
    state.sort_by = sortSelect.value || '';
    state.append = false;
    fetchProducts(ajaxUrl, false);
  });

  // Category (radio)
  document.addEventListener('change', (e) => {
    if (!e.target.matches('.js-cat-radio')) return;

    gatherCategorySelection();

    // If we were on /category/{slug} and the picked category differs, drop the route scope.
    if (state.route_category_id != null && String(state.category_id) !== String(state.route_category_id)) {
      state.route_category_id = null;

      // move URL to /search with the same query (so the page is shareable)
      const share = new URL(baseBrowseUrl, window.location.origin);
      // buildParams returns a full URL — we only want its ?query part
      share.search = new URL(buildParams(ajaxUrl)).search;
      window.history.replaceState(null, '', share.toString());
    }

    state.append = false;
    fetchProducts(ajaxUrl, false);
  });

  // Attributes
  document.addEventListener('change', (e) => {
    if (!e.target.matches('.js-attr-checkbox')) return;
    gatherAttributeSelections();
    state.append = false;
    fetchProducts(ajaxUrl, false);
  });

  document.addEventListener('change', (e) => {
    if (!e.target.matches('.js-color-radio')) return;
    // radio: value "" means clear
    state.color = e.target.value || null;
    state.append = false;
    fetchProducts(ajaxUrl, false);
  });

  // Clear all (also drop route scope to show global listing)
  clearBtn.addEventListener('click', () => {
    const checked = qs('.js-cat-radio:checked');
    if (checked) checked.checked = false;

    qsa('.js-attr-checkbox:checked').forEach(i => i.checked = false);

    state.category_id = null;
    state.selected_attribute_values = [];
    state.min_price = null;
    state.max_price = null;

    // If we landed via /category/{slug}, clear route scope & rewrite URL to /search
    if (state.route_category_id != null) {
      state.route_category_id = null;
      window.history.replaceState(null, '', baseBrowseUrl);
    }

    // color
    const clrChecked = document.querySelector('.js-color-radio:checked');
    if (clrChecked) clrChecked.checked = false;
    const any = document.querySelector('.js-color-radio[value=""]');
    if (any) any.checked = true;
    state.color = null;
    
    state.append = false;
    gatherCategorySelection();
    renderPills();
    fetchProducts(ajaxUrl, false);
  });

  // Infinite Scroll
  const io = new IntersectionObserver(async (entries) => {
    const ent = entries[0];
    if (ent.isIntersecting && state.next_page_url) {
      await fetchProducts(state.next_page_url, true);
    }
  }, { rootMargin: '200px' });
  io.observe(sentinel);

  // ===== First paint =====
  // If server didn’t pre-check (should be), ensure radio matches state
  if (state.category_id != null) {
    const pre = qs(`.js-cat-radio[value="${CSS.escape(String(state.category_id))}"]`);
    if (pre && !pre.checked) pre.checked = true;
  }
  gatherCategorySelection();
  gatherAttributeSelections();
  renderPills();

  // --- View More / View Less for categories (root + each branch)
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.js-toggle-more');
  if (!btn) return;
  e.preventDefault();

  const parentId = btn.dataset.parentId;
  const selector = parentId === 'root'
    ? '.cat-node-wrap.root[data-collapsible="1"]'
    : `.cat-node-wrap[data-parent="${CSS.escape(parentId)}"][data-collapsible="1"]`;

  const rows = Array.from(document.querySelectorAll(selector));
  const expanded = btn.dataset.state === 'expanded';

  if (!expanded) {
    rows.forEach(el => el.classList.remove('is-collapsed'));
    btn.dataset.state = 'expanded';
    btn.textContent   = btn.dataset.lessText || 'View Less';
  } else {
    rows.forEach(el => el.classList.add('is-collapsed'));
    const hiddenCount = rows.length;
    const moreText    = btn.dataset.moreText || 'View More';
    btn.dataset.state = 'collapsed';
    btn.textContent   = `${moreText} (${hiddenCount})`;
  }
});

// --- Ensure the selected radio (if any) is visible (auto-expand its hidden ancestors)
(function revealSelectedCategory() {
  const checked = document.querySelector('.js-cat-radio:checked');
  if (!checked) return;

  // Uncollapse any hidden wrappers up the tree
  let wrap = checked.closest('.cat-node-wrap');
  while (wrap) {
    if (wrap.matches('[data-collapsible="1"].is-collapsed')) {
      wrap.classList.remove('is-collapsed');
      const parentId = wrap.getAttribute('data-parent') || 'root';
      const btn = document.querySelector(`.js-toggle-more[data-parent-id="${parentId}"]`);
      if (btn) { btn.dataset.state = 'expanded'; btn.textContent = btn.dataset.lessText || 'View Less'; }
    }
    wrap = wrap.parentElement?.closest('.cat-node-wrap');
  }
})();


})();
</script>
@endsection
