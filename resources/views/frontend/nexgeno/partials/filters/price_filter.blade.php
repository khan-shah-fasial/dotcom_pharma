<div class="light_bg_gray mb-3 p-3">
  <div class="fs-16 fw-700 mb-2">{{ translate('Price Range') }}</div>

  <div class="d-flex align-items-center gap-2">
    <input id="min_price" type="number"
           class="form-control form-control-sm"
           value="{{ $min_price ?? ($scopedMin ?? $globalMin) }}"
           min="{{ $scopedMin ?? $globalMin }}"
           max="{{ $scopedMax ?? $globalMax }}"
           step="1">
    <span class="px-2">—</span>
    <input id="max_price" type="number"
           class="form-control form-control-sm"
           value="{{ $max_price ?? ($scopedMax ?? $globalMax) }}"
           min="{{ $scopedMin ?? $globalMin }}"
           max="{{ $scopedMax ?? $globalMax }}"
           step="1">
  </div>

  <button id="price-apply" type="button" class="btn btn-sm btn-primary mt-2">
    {{ translate('Apply') }}
  </button>

  {{-- This hint shows the *scoped* bounds and will be updated by AJAX --}}
  <div id="price-range-hint" class="mt-1 text-muted fs-12">
    {{ translate('Min') }}: {{ $scopedMin ?? $globalMin }} | {{ translate('Max') }}: {{ $scopedMax ?? $globalMax }}
  </div>
</div>