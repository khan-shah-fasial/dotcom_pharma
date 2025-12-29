<div class="sticky-top z-3 row gutters-10">
    @php
        $photos = [];
    @endphp
    @if ($detailedProduct->photos != null)
        @php
            $photos = explode(',', $detailedProduct->photos);
        @endphp
    @endif
    @php
        $videoEmbed = null;
        $videoThumb = null;
        $videoLink = trim(str_replace('\u00a0', '', strip_tags(html_entity_decode($detailedProduct->video_link ?? ''))));
        if (!empty($videoLink)) {
            if ($detailedProduct->video_provider == 'youtube') {
                if (preg_match('/youtu\\.be\\/([^?&]+)/', $videoLink, $match)) {
                    $videoId = $match[1];
                } elseif (preg_match('/v=([^&]+)/', $videoLink, $match)) {
                    $videoId = $match[1];
                } elseif (preg_match('/embed\\/([^?&]+)/', $videoLink, $match)) {
                    $videoId = $match[1];
                } else {
                    $videoId = null;
                }
                if (!empty($videoId)) {
                    $videoEmbed = 'https://www.youtube.com/embed/' . $videoId;
                    $videoThumb = 'https://img.youtube.com/vi/' . $videoId . '/hqdefault.jpg';
                }
            } elseif ($detailedProduct->video_provider == 'dailymotion' && isset(explode('video/', $videoLink)[1])) {
                $videoId = explode('video/', $videoLink)[1];
                $videoEmbed = 'https://www.dailymotion.com/embed/video/' . $videoId;
                $videoThumb = 'https://www.dailymotion.com/thumbnail/video/' . $videoId;
            } elseif ($detailedProduct->video_provider == 'vimeo' && isset(explode('vimeo.com/', $videoLink)[1])) {
                $videoId = explode('vimeo.com/', $videoLink)[1];
                $videoEmbed = 'https://player.vimeo.com/video/' . $videoId;
                $videoThumb = 'https://vumbnail.com/' . $videoId . '.jpg';
            }
        }
    @endphp
    <!-- Gallery Images -->
    <div class="col-12 pl-md-0">
        <div class="aiz-carousel product-gallery arrow-inactive-transparent arrow-lg-none product_dt_img"
            data-nav-for='.product-gallery-thumb' data-fade='true' data-auto-height='true' data-arrows='true'>

            @if ($detailedProduct->digital == 0)
                @foreach ($detailedProduct->stocks as $key => $stock)
                    @if ($stock->image != null)
                        <div class="carousel-box img-zoom rounded-0">
                            <img class="img-fluid h-auto lazyload mx-auto"
                                src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                data-src="{{ uploaded_asset($stock->image) }}"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                        </div>
                    @endif
                @endforeach
            @endif

            {{-- @foreach ($photos as $key => $photo)
                <div class="carousel-box img-zoom rounded-0">
                    <img class="img-fluid h-auto lazyload mx-auto"
                        src="{{ static_asset('assets/img/placeholder.jpg') }}" data-src="{{ uploaded_asset($photo) }}"
                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                </div>
            @endforeach --}}
            @foreach ($photos as $key => $photo)
                <div class="carousel-box img-zoom rounded-0">
                    @php $type = check_asset_type($photo); @endphp

                    @if ($type == 'image')
                        <img class="img-fluid h-auto lazyload mx-auto"
                            src="{{ static_asset('assets/img/placeholder.jpg') }}"
                            data-src="{{ uploaded_asset($photo) }}"
                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                    
                    @elseif ($type == 'video')
                        <video class="img-fluid h-auto lazyload mx-auto" controls>
                            <source src="{{ uploaded_asset($photo) }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    
                    @elseif ($type == 'file')
                        <a href="{{ uploaded_asset($photo) }}" target="_blank" class="btn btn-outline-secondary">
                            {{ translate('Download File') }}
                        </a>
                    @endif
                </div>
            @endforeach
            @if ($videoEmbed)
                <div class="carousel-box img-zoom rounded-0">
                    <div class="embed-responsive embed-responsive-16by9">
                        <iframe class="embed-responsive-item" src="{{ $videoEmbed }}" allowfullscreen></iframe>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <!-- Thumbnail Images -->
    <div class="col-12 mt-3 d-none d-lg-block">
        <div class="aiz-carousel half-outside-arrow product-gallery-thumb" data-items='7' data-nav-for='.product-gallery'
            data-focus-select='true' data-arrows='true' data-vertical='false' data-auto-height='true'>


            @if ($detailedProduct->digital == 0)
                @foreach ($detailedProduct->stocks as $key => $stock)
                    @if ($stock->image != null)
                        <div class="carousel-box c-pointer rounded-0" data-variation="{{ $stock->variant }}">
                            <img class="lazyload mw-100 size-60px mx-auto border p-1"
                                src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                data-src="{{ uploaded_asset($stock->image) }}"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                        </div>
                    @endif
                @endforeach
            @endif

            {{-- @foreach ($photos as $key => $photo)
                <div class="carousel-box c-pointer rounded-0">
                    <img class="lazyload mw-100 size-60px mx-auto border p-1"
                        src="{{ static_asset('assets/img/placeholder.jpg') }}" data-src="{{ uploaded_asset($photo) }}"
                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                </div>
            @endforeach --}}

            @foreach ($photos as $key => $photo)
                <div class="carousel-box c-pointer rounded-0">
                    @php $type = check_asset_type($photo); @endphp

                    @if ($type == 'image')
                        <img class="lazyload mw-100 size-60px mx-auto border p-1"
                            src="{{ static_asset('assets/img/placeholder.jpg') }}"
                            data-src="{{ uploaded_asset($photo) }}"
                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">

                    @elseif ($type == 'video')
                        <div class="mw-100 size-60px mx-auto border p-1 d-flex align-items-center justify-content-center bg-light">
                            <i class="las la-video text-primary" style="font-size: 24px;"></i>
                        </div>

                    @elseif ($type == 'file')
                        <div class="mw-100 size-60px mx-auto border p-1 d-flex align-items-center justify-content-center bg-light">
                            <i class="las la-file-alt text-secondary" style="font-size: 24px;"></i>
                        </div>
                    @endif
                </div>
            @endforeach

            @if ($videoEmbed)
                <div class="carousel-box c-pointer rounded-0">
                    @if ($videoThumb)
                        <div class="position-relative d-inline-block">
                            <img class="lazyload mw-100 size-60px mx-auto border p-1"
                                src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                data-src="{{ $videoThumb }}"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                            <span class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center" style="top:0;left:0;">
                                <i class="las la-play-circle text-primary" style="font-size: 24px;"></i>
                            </span>
                        </div>
                    @else
                        <div class="mw-100 size-60px mx-auto border p-1 d-flex align-items-center justify-content-center bg-light">
                            <i class="las la-play-circle text-primary" style="font-size: 24px;"></i>
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>


</div>

@push('scripts')
<script>
    (function ($) {
        "use strict";
        $(document).ready(function () {
            var $productGallery = $('.product_dt_img');

            if ($productGallery.length) {
                $productGallery.on('beforeChange', function (event, slick, currentSlide) {
                    var $current = $(slick.$slides[currentSlide]);

                    // Pause HTML5 videos
                    $current.find('video').each(function () {
                        this.pause();
                        this.currentTime = 0;
                    });

                    // Reset iframe sources (e.g., YouTube/Vimeo) to stop playback
                    $current.find('iframe').each(function () {
                        var $iframe = $(this);
                        var src = $iframe.attr('src');
                        $iframe.attr('src', src);
                    });
                });
            }
        });
    })(jQuery);
</script>
@endpush
