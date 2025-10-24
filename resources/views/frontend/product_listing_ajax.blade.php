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
        @include('frontend.'.get_setting('homepage_select').'.partials.filters.category_filter', [
          'categories'  => $categories,
          'category'    => $category,
          'category_id' => $category_id,
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
        category_ids: [], // populated by checked boxes
        selected_category_name: @json($category_id ? ($category?->getTranslation('name')) : null),
        page_size: 24,
        next_page_url: @json($ajaxNextPageUrl),
        loading: false,
        append: false,
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
        const minInput = document.querySelector('#min_price');
        const maxInput = document.querySelector('#max_price');
        const hint     = document.querySelector('#price-range-hint');
        
        if (typeof json.scoped_min !== 'undefined' && typeof json.scoped_max !== 'undefined') {
          // Update min/max bounds of inputs
          if (minInput && maxInput) {
            const sMin = Number(json.scoped_min);
            const sMax = Number(json.scoped_max);
            
            minInput.min = sMin;
            minInput.max = sMax;
            maxInput.min = sMin;
            maxInput.max = sMax;

            // Clamp current values into new bounds
            if (Number(minInput.value) < sMin)  minInput.value = sMin;
            if (Number(maxInput.value) > sMax)  maxInput.value = sMax;
            if (Number(minInput.value) > Number(maxInput.value)) {
              // swap if needed
              const t = minInput.value;
              minInput.value = maxInput.value;
              maxInput.value = t;
            }
          }
          // Update hint text
          if (hint) hint.textContent = `Min: ${json.scoped_min} | Max: ${json.scoped_max}`;
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
      if (e.target.dataset.hasChildren === '1' && e.target.checked) {
        const holder = document.getElementById('children-of-' + e.target.value);
        if (holder && holder.dataset.loaded !== '1') {
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
      
      // Price filter (apply button & input)
      const priceApplyBtn = qs('#price-apply');
      const minInput = qs('#min_price');
      const maxInput = qs('#max_price');
      
      function clamp(n, lo, hi) { return Math.max(lo, Math.min(hi, n)); }
      
      priceApplyBtn?.addEventListener('click', () => {
        const gMin = Number(minInput.min), gMax = Number(maxInput.max);
        const min = clamp(Number(minInput.value || gMin), gMin, gMax);
        const max = clamp(Number(maxInput.value || gMax), gMin, gMax);
        if (min > max) { [state.min_price, state.max_price] = [max, min]; }
        else { state.min_price = min; state.max_price = max; }
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

      // Initial gather (if some are pre-checked server-side)
      gatherCategorySelections();
      gatherAttributeSelections();
      renderPills();
  })();
  </script>
  @endsection
  