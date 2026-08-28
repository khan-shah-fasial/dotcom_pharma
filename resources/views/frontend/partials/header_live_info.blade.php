@php
    $headerLive = \App\Support\HeaderLiveContext::resolve();
@endphp

<div id="frontendHeaderLiveInfo"
     class="frontend-header-live-info text-right w-100"
     data-timezone="{{ $headerLive['timezone'] }}"
     data-live-area-url="{{ $headerLive['live_area_url'] }}"
     data-csrf-token="{{ $headerLive['csrf_token'] }}">
    <div class="frontend-header-live-info__row d-flex flex-wrap justify-content-end align-items-center notranslate" translate="no">
        <span class="frontend-header-live-info__item" title="{{ translate('Live location') }}">
            <i class="las la-map-marker-alt"></i>
            <span id="headerLiveArea" class="notranslate" translate="no">{{ $headerLive['area_name'] }}</span>
        </span>
        <span class="frontend-header-live-info__divider d-none d-md-inline">|</span>
        <span class="frontend-header-live-info__item" title="{{ translate('Local time') }}">
            <i class="las la-clock"></i>
            <span id="headerLiveClock" class="notranslate" translate="no">--:--</span>
        </span>
        @if (!empty($headerLive['forex_label']))
            <span class="frontend-header-live-info__divider d-none d-md-inline">|</span>
            <span class="frontend-header-live-info__item" title="{{ translate('Exchange rate') }}">
                <i class="las la-chart-line"></i>
                <span id="headerLiveForex" class="notranslate" translate="no">{{ $headerLive['forex_label'] }}</span>
            </span>
        @endif
    </div>
</div>

<style>
    .top-navbar--with-live-info {
        height: auto !important;
        min-height: 0;
        overflow: visible;
        padding-bottom: 6px;
    }

    .frontend-header-live-info {
        position: relative;
        z-index: 2;
        margin-top: 4px;
        padding: 2px 0 4px;
        line-height: 1.35;
    }

    .frontend-header-live-info__row {
        gap: 0.4rem 0.75rem;
        font-size: 11px;
        color: #4b5563;
        justify-content: flex-end !important;
    }

    .frontend-header-live-info__item {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        max-width: 100%;
        white-space: nowrap;
    }

    #headerLiveArea {
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: bottom;
    }

    .frontend-header-live-info__divider {
        color: #9ca3af;
    }

    @media (min-width: 768px) {
        .frontend-header-live-info {
            margin-top: 5px;
            padding: 3px 0 5px;
        }

        .frontend-header-live-info__row {
            font-size: 12px;
        }

        #headerLiveArea {
            max-width: 320px;
        }
    }

    @media (max-width: 767.98px) {
        .frontend-header-live-info__row {
            justify-content: center !important;
            flex-wrap: wrap;
        }

        #headerLiveArea {
            max-width: 140px;
        }
    }
</style>

@push('scripts')
<script>
(function () {
    var root = document.getElementById('frontendHeaderLiveInfo');
    if (!root) {
        return;
    }

    var clockEl = document.getElementById('headerLiveClock');
    var areaEl = document.getElementById('headerLiveArea');
    var timezone = root.getAttribute('data-timezone') || 'UTC';
    var liveAreaUrl = root.getAttribute('data-live-area-url');
    var csrfToken = root.getAttribute('data-csrf-token');

    function formatClock() {
        if (!clockEl) {
            return;
        }

        try {
            clockEl.textContent = new Intl.DateTimeFormat(undefined, {
                timeZone: timezone,
                weekday: 'short',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            }).format(new Date());
        } catch (error) {
            clockEl.textContent = new Date().toLocaleTimeString();
        }
    }

    function persistAreaName(areaName, latitude, longitude) {
        if (!liveAreaUrl || !areaName) {
            return;
        }

        var payload = new URLSearchParams();
        payload.append('_token', csrfToken);
        payload.append('area_name', areaName);
        if (latitude !== undefined && longitude !== undefined) {
            payload.append('latitude', latitude);
            payload.append('longitude', longitude);
        }

        fetch(liveAreaUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: payload.toString()
        }).catch(function () {});
    }

    function setAreaName(areaName, latitude, longitude, persist) {
        if (!areaEl || !areaName) {
            return;
        }

        areaEl.textContent = areaName;
        if (persist) {
            persistAreaName(areaName, latitude, longitude);
        }
    }

    function resolveAreaFromCoords(latitude, longitude) {
        var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='
            + encodeURIComponent(latitude)
            + '&lon=' + encodeURIComponent(longitude)
            + '&zoom=14&addressdetails=1';

        fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                var address = data && data.address ? data.address : {};
                var areaName = [
                    address.suburb,
                    address.neighbourhood,
                    address.city_district,
                    address.town,
                    address.city,
                    address.village,
                    address.state_district,
                    address.state
                ].filter(Boolean).join(', ');

                if (!areaName && data && data.display_name) {
                    areaName = String(data.display_name).split(',').slice(0, 2).join(', ');
                }

                if (areaName) {
                    setAreaName(areaName, latitude, longitude, true);
                }
            })
            .catch(function () {});
    }

    function detectLiveArea() {
        if (!navigator.geolocation) {
            return;
        }

        navigator.geolocation.getCurrentPosition(function (position) {
            resolveAreaFromCoords(position.coords.latitude, position.coords.longitude);
        }, function () {}, {
            enableHighAccuracy: false,
            timeout: 12000,
            maximumAge: 300000
        });
    }

    formatClock();
    setInterval(formatClock, 1000);
    detectLiveArea();
})();
</script>
@endpush
