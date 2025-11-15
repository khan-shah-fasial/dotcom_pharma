<!-- Attributes -->
@foreach ($attributes as $attribute)
    @php
        $attrName    = $attribute->getTranslation('name');
        $collapseId  = 'collapse_' . str_replace(' ', '_', $attribute->name);
        $visibleLimit = 5;

        $selectedValues = $selected_attribute_values ?? [];
        $allValues      = $attribute->attribute_values;

        // count how many will be hidden
        $hiddenCount = 0;
        $i = 0;
        foreach ($allValues as $av) {
            $isSelected = in_array($av->value, $selectedValues, true);
            if ($i >= $visibleLimit && !$isSelected) {
                $hiddenCount++;
            }
            $i++;
        }

        // open if any selected
        $shouldShow = '';
        foreach ($allValues as $av) {
            if (in_array($av->value, $selectedValues, true)) {
                $shouldShow = 'show';
                break;
            }
        }
    @endphp

    <div class="background-none-filter light_bg_gray mb-0">
        <div class="fs-18 fw-700 p-3">
            <a href="javascript:void(0)"
               class="dropdown-toggle text-dark filter-section collapsed d-flex align-items-center justify-content-between"
               data-toggle="collapse"
               data-target="#{{ $collapseId }}"
               style="white-space: normal;">
               {{ $attrName }}
            </a>
        </div>

        <div class="collapse {{ $shouldShow }}" id="{{ $collapseId }}">
            <div class="p-3 aiz-checkbox-list">
                @php $row = 0; @endphp
                @foreach ($allValues as $attribute_value)
                    @php
                        $isSelected = in_array($attribute_value->value, $selectedValues, true);
                        $shouldHide = ($row >= $visibleLimit && !$isSelected);
                        $cnt        = $attributeValueCounts[$attribute->id][$attribute_value->value] ?? 0;
                    @endphp

                    <label
                        class="aiz-checkbox mb-2 attr-val-item {{ $shouldHide ? 'd-none' : '' }}"
                        data-attr="{{ $collapseId }}"
                    >
                        <input
                            type="checkbox"
                            class="js-attr-checkbox"
                            name="selected_attribute_values[]"
                            value="{{ $attribute_value->value }}"
                            @if ($isSelected) checked @endif
                        >
                        <span class="aiz-square-check"></span>
                        <span class="fs-16 fw-500 text-dark bd-chked">
                            {{ $attribute_value->value }}
                            
                        </span>
                        @if($cnt > 0)
                            <span class="text-muted">({{ $cnt }})</span>
                        @endif
                    </label>

                    @php $row++; @endphp
                @endforeach


                @if ($hiddenCount > 0)
                    <button
                        type="button"
                        class="btn btn-link p-0 fs-12 js-attr-show-more"
                        data-target="{{ $collapseId }}"
                        data-more-text="{{ translate('Show More') }}"
                        data-less-text="{{ translate('Show Less') }}"
                        data-state="collapsed"
                    >
                        {{ translate('Show More') }} ({{ $hiddenCount }})
                    </button>
                @endif
            </div>
        </div>
    </div>
@endforeach

<style>
    .aiz-checkbox-list .attr-val-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 12px;
    }
    .js-attr-show-more {
        margin-top: .25rem;
    }
    /* When checkbox is checked, make the .bd-chked span bolder */
    .aiz-checkbox input[type="checkbox"]:checked + .aiz-square-check + .bd-chked {
        font-weight: 600 !important;
        color: #096c9a !important;
    }

</style>
