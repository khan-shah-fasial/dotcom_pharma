@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="card shadow-none rounded-0 border-0">
        <div class="card-header border-0 px-0 pb-0">
            <div class="d-flex flex-wrap align-items-center justify-content-between w-100">
                <h5 class="mb-0 fs-20 fw-700 text-dark">
                    {{ translate('Support') }}
                </h5>
                <p class="mb-0 fs-13 text-muted">
                    {{ translate('Find your dedicated support staff based on your business and shipping locations.') }}
                </p>
            </div>
        </div>

        <div class="card-body px-0 pt-4">
            @if ($staffs->isEmpty())
                <div class="text-center py-5">
                    <img src="{{ static_asset('assets/img/placeholder.jpg') }}" alt="{{ translate('No staff') }}"
                         class="img-fluid mb-3" style="max-width: 180px;">
                    <h6 class="fw-600 text-dark mb-1">{{ translate('No support staff assigned yet') }}</h6>
                    <p class="fs-13 text-muted mb-0">
                        {{ translate('Once support staff are assigned to your region, they will appear here.') }}
                    </p>
                </div>
            @else
                <div class="row gutters-16">
                    @foreach ($staffs as $staff)
                        @php
                            $user      = $staff->user;
                            $avatarUrl = $user && $user->avatar_original
                                ? uploaded_asset($user->avatar_original)
                                : static_asset('assets/img/avatar-place.png');

                            $areas = $staff->area_assignments ? json_decode($staff->area_assignments, true) : [];

                            $primaryArea = null;
                            if (!empty($areas) && is_array($areas)) {
                                $primaryArea = $areas[0];
                            }

                            $countryName = $primaryArea && isset($primaryArea['country_id'])
                                ? (getParticularData('countries', 'name', (int) $primaryArea['country_id']) ?? null)
                                : null;

                            $stateName = $primaryArea && isset($primaryArea['state_id']) && $primaryArea['state_id']
                                ? (getParticularData('states', 'name', (int) $primaryArea['state_id']) ?? null)
                                : null;

                            $districtLabel = null;
                            if ($primaryArea) {
                                if (!empty($primaryArea['all_districts'])) {
                                    $districtLabel = translate('All');
                                } elseif (isset($primaryArea['district_id']) && $primaryArea['district_id']) {
                                    $districtLabel =
                                        getParticularData('cities', 'name', (int) $primaryArea['district_id']) ?? null;
                                }
                            }

                            $locationLabel = collect([$countryName, $stateName])
                                ->filter()
                                ->implode(' / ');

                            $districtText = $districtLabel ?? translate('Not specified');

                            $ratingValue  = 4.0;
                            $ratingCount  = 34;
                            $fullStars    = floor($ratingValue);
                            $hasHalfStar  = $ratingValue - $fullStars >= 0.5;
                            $emptyStars   = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                        @endphp

                        <div class="col-md-6 col-xl-4 mb-4">
                            <div class="card h-100 border-0 shadow-sm hover-shadow-lg has-transition rounded-3 overflow-hidden">
                                <div class="card-body d-flex flex-column p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="avatar avatar-lg flex-shrink-0 mr-3 rounded-circle border">
                                            <img src="{{ $avatarUrl }}"
                                                 alt="{{ $user->name ?? translate('Staff') }}"
                                                 class="img-fit rounded-circle"
                                                 onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                                        </span>
                                        <div class="min-w-0">
                                            <h6 class="mb-1 fs-15 fw-700 text-dark text-truncate">
                                                {{ $user->name ?? translate('Staff') }}
                                            </h6>
                                            <div class="fs-13 text-muted text-truncate">
                                                {{ $staff->designation ?: translate('Support Executive') }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="fs-12 text-uppercase text-muted mr-2">{{ translate('Area') }}:</span>
                                            <span class="fs-13 fw-600 text-dark">
                                                {{ $locationLabel ?: translate('Not specified') }}
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="fs-12 text-uppercase text-muted mr-2">{{ translate('District') }}:</span>
                                            <span class="fs-13 fw-600 text-dark">
                                                {{ $districtText }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mb-3 d-flex align-items-center">
                                        <div class="d-flex align-items-center mr-2">
                                            @for ($i = 0; $i < $fullStars; $i++)
                                                <i class="las la-star fs-16 text-warning"></i>
                                            @endfor

                                            @if ($hasHalfStar)
                                                <i class="las la-star-half-alt fs-16 text-warning"></i>
                                            @endif

                                            @for ($i = 0; $i < $emptyStars; $i++)
                                                <i class="lar la-star fs-16 text-muted"></i>
                                            @endfor
                                        </div>
                                        <span class="fs-12 text-muted">
                                            ({{ $ratingCount }} {{ translate('Reviews') }})
                                        </span>
                                    </div>

                                    <div class="mt-auto pt-2 d-flex flex-wrap gap-2">
                                        <button type="button"
                                                class="btn btn-outline-primary btn-sm rounded-pill px-3 mb-2 mr-2">
                                            <i class="las la-video mr-1"></i>
                                            {{ translate('Video Meet') }}
                                        </button>
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm rounded-pill px-3 mb-2 mr-2">
                                            <i class="las la-phone-volume mr-1"></i>
                                            {{ translate('Call Back') }}
                                        </button>
                                        <button type="button"
                                                class="btn btn-primary btn-sm rounded-pill px-3 mb-2">
                                            <i class="las la-user-circle mr-1"></i>
                                            {{ translate('View Profile') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

