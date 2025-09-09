@php
    $dynamicTabs = json_decode($detailedProduct->contents, true) ?? [];
    $active = true;
@endphp

@if($detailedProduct->description != null && !empty(trim(str_replace("\u00a0", '', strip_tags(html_entity_decode($detailedProduct->description))))) || !empty($dynamicTabs) && count($dynamicTabs) > 0)

<div class="pt-4 pb-4">
    <!-- Tabs -->
    <div class="position-relative main_disc_scroll">
    <button class="scroll-btn left" onclick="scrollTabs('left')">&#10094;</button>

    <div class="tab-scroll-wrapper-new">
    <ul class="nav nav-tabs flex-nowrap overflow-auto" id="myTab" role="tablist">

        @if ($detailedProduct->description != null && !empty(trim(str_replace("\u00a0", '', strip_tags(html_entity_decode($detailedProduct->description))))) )

            @php $active = false; @endphp
            <li class="nav-item">
            <a href="#tab_default_1" data-toggle="tab"
                class="text-reset active show">{{ translate('Description') }}</a>
            </li>
        @endif

        {{-- // Dynamic Tab Headers --}}
        @if (!empty($dynamicTabs) && count($dynamicTabs) > 0)
            @foreach ($dynamicTabs as $index => $tab)
            <li class="nav-item">
                <a href="#tab_dynamic_{{ $index }}" data-toggle="tab"
                class="specs-text-rest-class text-reset {{ $index === 0 && $active ? 'active show' : '' }}">
                    {{ $tab['title'] ?? 'Tab ' . ($index + 1) }}
                </a>
            </li>
            @endforeach
        @endif 

        @if ($detailedProduct->video_link != null)
        <li class="nav-item">
            <a href="#tab_default_2" data-toggle="tab"
                class="text-reset {{ $index === 0 && $active ? 'active show' : '' }}">{{ translate('Video') }}</a>
                </li>
            @php $active = false; @endphp
        @endif
        @if ($detailedProduct->pdf != null)
        <li class="nav-item">
            <a href="#tab_default_3" data-toggle="tab"
                class="text-reset {{ $index === 0 && $active ? 'active show' : '' }}">{{ translate('Downloads') }}</a>
                </li>
            @php $active = false; @endphp
        @endif

        </ul>
    </div>

    <button class="scroll-btn right" onclick="scrollTabs('right')">&#10095;</button>
    </div>


    <!-- Description -->
    <div class="tab-content pt-0">

        @php $show = true; @endphp

        @if(!empty(trim(str_replace("\u00a0", '', strip_tags(html_entity_decode($detailedProduct->description))))))

            @php $show = false; @endphp

            <!-- Description -->
            <div class="tab-pane fade active show" id="tab_default_1">
                <div class="py-4">
                    <div class="mw-100 overflow-hidden text-left aiz-editor-data">
                        <?php echo $detailedProduct->getTranslation('description'); ?>
                    </div>
                </div>
            </div>

        @endif

        @if (!empty($dynamicTabs) && count($dynamicTabs) > 0)
            @foreach ($dynamicTabs as $index => $tab)
                <div class="tab-pane fade {{ $index === 0 && $show ? 'active show' : '' }}" id="tab_dynamic_{{ $index }}">
                    <div class="py-4">
                        <div class="mw-100 overflow-hidden text-left aiz-editor-data">
                            {!! $tab['content'] ?? '' !!}
                        </div>
                    </div>
                </div>
            @endforeach
        @endif  


        @if(!empty(trim(str_replace("\u00a0", '', strip_tags(html_entity_decode($detailedProduct->video_link))))))
            @php $show = false; @endphp

            <!-- Video -->
            <div class="tab-pane fade" id="tab_default_2">
                <div class="py-4">
                    <div class="embed-responsive embed-responsive-16by9">
                        {{-- @if ($detailedProduct->video_provider == 'youtube' && isset(explode('=', $detailedProduct->video_link)[1])) --}}
                        @if ($detailedProduct->video_provider == 'youtube')
                            {{-- <iframe class="embed-responsive-item"
                                src="https://www.youtube.com/embed/{{ get_url_params($detailedProduct->video_link, 'v') }}"></iframe> --}}
                            <iframe class="embed-responsive-item"
                                src="{{ $detailedProduct->video_link }}"></iframe>
                        @elseif ($detailedProduct->video_provider == 'dailymotion' && isset(explode('video/', $detailedProduct->video_link)[1]))
                            <iframe class="embed-responsive-item"
                                src="https://www.dailymotion.com/embed/video/{{ explode('video/', $detailedProduct->video_link)[1] }}"></iframe>
                        @elseif ($detailedProduct->video_provider == 'vimeo' && isset(explode('vimeo.com/', $detailedProduct->video_link)[1]))
                            <iframe
                                src="https://player.vimeo.com/video/{{ explode('vimeo.com/', $detailedProduct->video_link)[1] }}"
                                width="500" height="281" frameborder="0" webkitallowfullscreen
                                mozallowfullscreen allowfullscreen></iframe>
                        @endif
                    </div>
                </div>
            </div>

        @endif
        
        @if(!empty(trim(str_replace("\u00a0", '', strip_tags(html_entity_decode($detailedProduct->pdf))))))

            @php $show = false; @endphp

            <!-- Download -->
            <div class="tab-pane fade" id="tab_default_3">
                <div class="py-4 text-center ">
                    <a href="{{ uploaded_asset($detailedProduct->pdf) }}"
                        class="btn btn-primary" target="_blank">{{ translate('Download') }}</a>
                </div>
            </div>

        @endif
 
    </div>
</div>
@endif

