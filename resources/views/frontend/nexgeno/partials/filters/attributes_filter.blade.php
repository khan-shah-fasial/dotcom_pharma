<!-- Attributes -->
@foreach ($attributes as $attribute)
    <div class="light_bg_gray mb-3">
        <div class="fs-16 fw-700 p-3">
            <a href="#" class="dropdown-toggle text-dark filter-section collapsed d-flex align-items-center justify-content-between" 
               data-toggle="collapse" data-target="#collapse_{{ str_replace(' ', '_', $attribute->name) }}" style="white-space: normal;">
               {{ $attribute->getTranslation('name') }}
            </a>
        </div>
        @php
            $show = '';
            foreach ($attribute->attribute_values as $attribute_value){
                if(in_array($attribute_value->value, $selected_attribute_values)){
                    $show = 'show';
                }
            }
        @endphp
        <div class="collapse {{ $show }}" id="collapse_{{ str_replace(' ', '_', $attribute->name) }}">
            <div class="p-3 aiz-checkbox-list">
                @foreach ($attribute->attribute_values as $attribute_value)
                    <label class="aiz-checkbox mb-3">
                        <input
                            type="checkbox"
                            class="js-attr-checkbox"
                            name="selected_attribute_values[]"
                            value="{{ $attribute_value->value }}"
                            @if (in_array($attribute_value->value, $selected_attribute_values)) checked @endif
                        >
                        <span class="aiz-square-check"></span>
                        <span class="fs-14 fw-400 text-dark">{{ $attribute_value->value }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>
@endforeach
