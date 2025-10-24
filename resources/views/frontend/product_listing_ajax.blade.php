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
          'selected_category_ids' => $selected_category_ids ?? [],
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
        @include('frontend.'.get_setting('homepage_select').'.partials.filters.attributes_filter', [
        'attributes' => $attributes
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
    const ajaxUrl   = "{{ route('search.ajax.products') }}";
    const childUrl = (id) => "{{ route('search.ajax.category.children', ['id' => '___ID___']) }}".replace('___ID___', encodeURIComponent(id));

    const state = {
        route_category_id: @json($category_id),
        route_brand_id: @json($brand_id ?? null),
        keyword: @json($query ?? ''),
        sort_by: @json($sort_by ?? ''),
        min_price: @json($min_price ?? null),
        max_price: @json($max_price ?? null),
        selected_attribute_values: @json($selected_attribute_values ?? []),
        category_ids: @json($selected_category_ids ?? []),
        selected_category_name: @json($selected_category_name ?? ($category_id ? ($category?->getTranslation('name')) : null)),
        page_size: 24,
        next_page_url: @json($ajaxNextPageUrl),
        loading: false,
        append: false,
            
        // For first-paint slider align
        scoped_min: @json($scopedMin ?? null),
        scoped_max: @json($scopedMax ?? null),
      };
      
      // ========== Helpers ==========
      const qs = (sel,ctx=document)=>ctx.querySelector(sel);
      const qsa= (sel,ctx=document)=>Array.from(ctx.querySelectorAll(sel));
      const productGrid   = qs('#product-grid');
      const sentinel      = qs('#infinite-sentinel');
      const sortSelect    = qs('#sort_by');
      const crumbSelected = qs('#crumb-selected');
      const pillsBar      = qs('#active-filters');
      const clearBtn      = qs('#clear-filters');

      function gatherCategorySelections() {
        state.category_ids = qsa('.js-cat-checkbox:checked').map(i => i.value);
        const label = qsa('.js-cat-checkbox:checked')[0]?.dataset?.label || state.selected_category_name || null;
        if (crumbSelected && label) crumbSelected.textContent = `"${label}"`;
        qs('#list-title').textContent = label || "{{ translate('All Products') }}";
      }
      
      function gatherAttributeSelections() {
        state.selected_attribute_values = qsa('.js-attr-checkbox:checked').map(i => i.value);
      }
      
      function buildParams(url) {
        const u = new URL(url, window.location.origin);
        const p = u.searchParams;
        // p.set('page_size', state.page_size);
        if (state.keyword) p.set('keyword', state.keyword);
        if (state.sort_by) p.set('sort_by', state.sort_by);
        if (state.min_price != null) p.set('min_price', state.min_price);
        if (state.max_price != null) p.set('max_price', state.max_price);
        if (state.route_category_id) p.set('route_category_id', state.route_category_id);
        if (state.route_brand_id)    p.set('route_brand_id', state.route_brand_id);
        state.category_ids.forEach(id => p.append('category_ids[]', id));
        state.selected_attribute_values.forEach(v => p.append('selected_attribute_values[]', v));
        return u.toString();
      }

      function normalizeToAjax(url) {
        const a = new URL(url, window.location.origin);
        const ajax = new URL(ajaxUrl, window.location.origin);
        a.pathname = ajax.pathname;
        return a.toString();
      }
      
        // -------------- AIZ noUiSlider auto-init (safe-call) --------------
        // Init the old slider only if it isn't already initialized
        const sliderEl = document.getElementById('input-slider-range');
        if (window.AIZ?.plugins?.noUiSlider && sliderEl && !sliderEl.noUiSlider) {
        AIZ.plugins.noUiSlider();
        }


        // -------------- Expose current scoped bounds (from server) --------------
        state.scoped_min = @json($scopedMin ?? null);
        state.scoped_max = @json($scopedMax ?? null);

        // -------------- Shim: old global functions --------------
        window.filter = function () {
            state.append = false;
            fetchProducts(ajaxUrl, false);
        };

        window.rangefilter = function (arg) {
            const low  = Number(arg?.[0] ?? 0);
            const high = Number(arg?.[1] ?? 0);

            // Keep hidden inputs in sync (old template expects them)
            const hidLow  = document.querySelector('input[name="min_price"]');
            const hidHigh = document.querySelector('input[name="max_price"]');
            if (hidLow)  hidLow.value  = low;
            if (hidHigh) hidHigh.value = high;

            state.min_price = low;
            state.max_price = high;

            state.append = false;
            fetchProducts(ajaxUrl, false);
        };


        // -------------- Helper: sync slider range & handles --------------
        function syncSliderBounds(sMin, sMax) {
            const el = document.getElementById('input-slider-range');
            if (!el || !el.noUiSlider) return;

            // Update min/max range
            try {
                el.noUiSlider.updateOptions({
                range: { min: Number(sMin), max: Number(sMax) }
                }, false); // don't trigger extra events
            } catch (_) {}

            // Respect current state if set; otherwise snap to scoped bounds
            let low  = (state.min_price != null) ? Number(state.min_price) : Number(sMin);
            let high = (state.max_price != null) ? Number(state.max_price) : Number(sMax);

            // Clamp & fix ordering
            low  = Math.max(Number(sMin), Math.min(low,  Number(sMax)));
            high = Math.max(Number(sMin), Math.min(high, Number(sMax)));
            if (low > high) [low, high] = [high, low];

            try { el.noUiSlider.set([low, high]); } catch (_) {}
        }


        // -------------- First paint: align slider to current scoped bounds --------------
        if (state.scoped_min != null && state.scoped_max != null) {
            // Defer a tick so AIZ's auto-init has time to create the slider
            setTimeout(function(){
            syncSliderBounds(Number(state.scoped_min), Number(state.scoped_max));
            }, 0);
        }

  
      async function fetchProducts(url, append=false) {
        if (state.loading) return;
        state.loading = true;
        sentinel.style.opacity = '1';
        
        const finalUrl = normalizeToAjax(buildParams(url));
        const res = await fetch(finalUrl, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
        const json = await res.json();
        // Update local state (optional)
        if (typeof json.per_page !== 'undefined') {
          state.page_size = Number(json.per_page);
        }

        // Update a small metrics label in the UI
        const metrics = document.querySelector('#page-metrics');
        if (metrics && typeof json.per_page !== 'undefined' && typeof json.total_pages !== 'undefined' && typeof json.total !== 'undefined') {
          metrics.textContent = `Per page: ${json.per_page} • Total pages: ${json.total_pages} • Total products: ${json.total}`;
        }

        if (!append) productGrid.innerHTML = json.html;
        else productGrid.insertAdjacentHTML('beforeend', json.html);
        
        state.next_page_url = json.next_page_url;
        state.loading = false;
        sentinel.style.opacity = state.next_page_url ? '1' : '0.3';
        
        // ====== NEW: update price bounds to SCOPED values from server        
        // Keep the slider in sync with server-scoped bounds on every AJAX response
        if (typeof json.scoped_min !== 'undefined' && typeof json.scoped_max !== 'undefined') {
        syncSliderBounds(Number(json.scoped_min), Number(json.scoped_max));
        }

        
        // Update address bar (shallow) for shareable filters
        const share = new URL(window.location.href);
        share.search = new URL(buildParams(ajaxUrl)).search;
        window.history.replaceState(null, '', share.toString());

        // ADDED: re-render pills after each fetch
        renderPills();
    }
    
      // ========== NEW: Pills rendering ==========
  function hasActiveFilters() {
    return (state.category_ids.length > 0)
        || (state.selected_attribute_values.length > 0)
        || (state.min_price != null && state.max_price != null);
  }

  function renderPills() {
    // remove old pills (keep Clear button element)
    qsa('.js-filter-pill', pillsBar).forEach(n => n.remove());

    // Category pills (use checkbox labels)
    state.category_ids.forEach(id => {
      const input = qs(`.js-cat-checkbox[value="${CSS.escape(id)}"]`);
      const label = input?.dataset?.label || '{{ translate("Category") }}';
      addPill({ type:'category', value:id, text: label });
    });

    // Attribute pills
    state.selected_attribute_values.forEach(val => {
      addPill({ type:'attribute', value:val, text: val });
    });

    // Price pill
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
      const input = qs(`.js-cat-checkbox[value="${CSS.escape(value)}"]`);
      if (input) { input.checked = false; }
      gatherCategorySelections();
    } else if (type === 'attribute') {
      const input = qs(`.js-attr-checkbox[value="${cssValue(value)}"]`);
      if (input) { input.checked = false; }
      gatherAttributeSelections();
    } else if (type === 'price') {
      const minInput = qs('#min_price');
      const maxInput = qs('#max_price');
      state.min_price = null;
      state.max_price = null;
      if (minInput && maxInput) {
        // reset to scoped bounds currently in inputs' min/max
        minInput.value = minInput.min ?? '';
        maxInput.value = maxInput.max ?? '';
      }
    }
    state.append = false;
    fetchProducts(ajaxUrl, false);
  }

  function cssValue(v){ return v.replace(/(["\\])/g,'\\$1'); }
  function escapeHtml(s){ return (s ?? '').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }


    // ========== Events ==========
    // Sort
    sortSelect.addEventListener('change', () => {
      state.sort_by = sortSelect.value || '';
      state.append = false;
      fetchProducts(ajaxUrl, false);
    });
    
    // Category checkboxes (change)
    document.addEventListener('change', async (e) => {
      if (!e.target.matches('.js-cat-checkbox')) return;
      gatherCategorySelections();
      gatherAttributeSelections();
      
      // Drilldown: if the clicked item has children, fetch and render them below
      if (e.target.dataset.hasChildren === '1') {
        const holder = document.getElementById('children-of-' + e.target.value);
        if (holder && holder.dataset.loaded !== '1' && e.target.checked) {
          const r = await fetch(childUrl(e.target.value), { headers: {'X-Requested-With':'XMLHttpRequest'} });
          const j = await r.json();
          holder.innerHTML = j.children.map(c => `
          <label class="aiz-checkbox mb-2 d-block ml-3">
            <input type="checkbox" class="js-cat-checkbox" value="${c.id}" data-label="${c.name}" data-has-children="${c.has_children?1:0}">
                <span class="aiz-square-check"></span>
                <span class="fs-14 fw-400 text-dark">${c.name}</span>
                </label>
            <div id="children-of-${c.id}" data-loaded="0"></div>
            `).join('');
            holder.dataset.loaded = '1';
          }
        }
        state.append = false;
        fetchProducts(ajaxUrl, false);
      });

      // ADDED: Attribute checkboxes (change)
      document.addEventListener('change', (e) => {
        if (!e.target.matches('.js-attr-checkbox')) return;
        gatherAttributeSelections();
        state.append = false;
        fetchProducts(ajaxUrl, false);
      });

      // ADDED: Clear all button
      clearBtn.addEventListener('click', () => {
        // uncheck all categories & attributes
        qsa('.js-cat-checkbox:checked').forEach(i => i.checked = false);
        qsa('.js-attr-checkbox:checked').forEach(i => i.checked = false);

        // reset state
        state.category_ids = [];
        state.selected_attribute_values = [];
        state.min_price = null;
        state.max_price = null;

        // reset inputs to current scoped bounds
        const mn = qs('#min_price'), mx = qs('#max_price');
        if (mn && mx) { mn.value = mn.min ?? ''; mx.value = mx.max ?? ''; }

        state.append = false;
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

    // ===== First paint: pre-check DOM from server & sync UI =====
    qsa('.js-cat-checkbox').forEach(inp => {
        const id = isNaN(+inp.value) ? inp.value : +inp.value;
        if (state.category_ids.includes(id)) inp.checked = true;
    });

    // Initial gather (if some are pre-checked server-side)
    gatherCategorySelections();
    gatherAttributeSelections();
    renderPills();
  })();
  </script>
  @endsection
  