@if (get_setting('color_filter_activation') && ($colors ?? collect())->count())
<div class="background-none-filter light_bg_gray mb-0" id="color-filter">
  <div class="fs-18 fw-700 p-3">
    <a href="#" class="dropdown-toggle text-dark filter-section collapsed d-flex align-items-center justify-content-between"
       data-toggle="collapse" data-target="#collapse_color">
      {{ translate('Filter by color') }}
    </a>
  </div>

  @php
    $sel  = $selected_color ?? null;
    $show = $sel ? 'show' : '';
    $colorCounts = $colorCounts ?? [];
  @endphp

  <div class="collapse {{ $show }}" id="collapse_color">
    <div class="p-3 aiz-radio-inline">
      @foreach (($colors ?? collect()) as $color)
        @php
          $code = $color->code ?? null;
          $name = $color->name ?? $code ?? '';
          $cnt  = $code ? ($colorCounts[strtoupper($code)] ?? 0) : 0;
        @endphp

        @if($code)
        <label class="aiz-megabox mega-gap-between d-flex align-items-center justify-content-start pl-0 mr-2 text-center {{ $sel === $code ? 'selected' : '' }}">
          <input
            type="radio"
            class="js-color-radio"
            name="color"
            value="{{ $code }}"
            @checked($sel === $code)
          >
          <div style="display: flex; gap: 10px;">
              <span class="aiz-megabox-elem rounded-circle d-flex align-items-center justify-content-center p-0">
              <span class="size-15px d-inline-block rounded-circle" style="background: {{ $code }};"></span>
              </span>

            {{-- Name below swatch (matches original muted small text) --}}
            <div class="fs-16 text-center text-dark bd-chked-clr" style="line-height:1;">
              {{ $name }}
            </div>
          </div>
          {{-- Count below name (muted, only when >0) --}}
          <div class="fs-16 text-center text-muted">
            {{-- {{ $cnt > 0 ? '(' . $cnt . ')' : '' }} --}}
            {{  '(' . $cnt . ')' }}
          </div>
        </label>
        @endif

      @endforeach

      {{-- Clear color (kept commented as in original) --}}
      {{-- <label class="aiz-megabox pl-0 mr-2" data-toggle="tooltip" data-title="{{ translate('Any color') }}">
        <input type="radio" class="js-color-radio" name="color" value="" @checked(!$sel)>
        <span class="aiz-megabox-elem rounded d-flex align-items-center justify-content-center p-1 mb-2">
          <span class="size-30px d-inline-block rounded border"></span>
        </span>
      </label> --}}
    </div>
  </div>
</div>
@endif
