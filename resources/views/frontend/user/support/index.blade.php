@extends('frontend.layouts.user_panel')

<style>
.width100 {
   width: 108px;
}
body .mt-15 {
    margin-top: 15px !important;
}
    .support-staff-card .card-body {
        padding: 20px !important;
        min-height: 230px;
    }
</style>

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

                            // Compute dynamic support rating for this staff from contacts table
                            $ratingQuery = \App\Models\Contact::query()
                                ->where('type', 'support')
                                ->where('status', 'closed')
                                ->whereNotNull('review->rating')
                                ->where('data->staff->staff_id', $staff->id);

                            $ratingCount = (int) $ratingQuery->count();
                            $ratingValue = $ratingCount > 0
                                ? round((float) $ratingQuery->avg('review->rating'), 1)
                                : 0;

                            $fullStars    = (int) floor($ratingValue);
                            $hasHalfStar  = $ratingValue - $fullStars >= 0.5;
                            $emptyStars   = 5 - $fullStars - ($hasHalfStar ? 1 : 0);

                            $staffEmail = $user->email ?? null;
                            $staffPhone = $user->phone ?? null;
                        @endphp

                        <div class="col-4 mb-3">
                            <div class="atm_cards card h-100 border rounded-2 overflow-hidden support-staff-card">
                                <div class="card-body d-flex flex-column px-3 py-2">
                                    <div class="d-flex align-items-center mb-2 w-100">
                                        <span class="avatar avatar-md flex-shrink-0 mr-2 rounded-circle border bg-white">
                                            <img src="{{ $avatarUrl }}"
                                                 alt="{{ $user->name ?? translate('Staff') }}"
                                                 class="img-fit rounded-circle"
                                                 onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                                        </span>
                                        <div class="min-w-0">
                                            <h6 class="mb-0 fs-14 fw-700 text-dark text-truncate">
                                                {{ $user->name ?? translate('Staff') }}
                                            </h6>
                                            <div class="fs-11 text-muted text-truncate">
                                                {{ $staff->designation ?: translate('Support Executive') }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-between">
                                    <div class="mb-2 w-100">
                                        <div class="d-flex align-items-center mb-1 w-100">
                                            <span class="fs-10 text-uppercase text-muted mr-1">{{ translate('Area') }}:</span>
                                            <span class="fs-12 fw-600 text-dark text-truncate">
                                                {{ $locationLabel ?: translate('Not specified') }}
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center w-100">
                                            <span class="fs-10 text-uppercase text-muted mr-1">{{ translate('District') }}:</span>
                                            <span class="fs-12 fw-600 text-dark text-truncate">
                                                {{ $districtText }}
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center w-100 mt-2">
                                            <i class="las la-envelope fs-16 text-muted mr-1"></i>
                                            <span class="fs-12 text-dark text-truncate">
                                                {{ $staffEmail ?: translate('Email not available') }}
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center w-100 mt-1">
                                            <i class="las la-phone fs-16 text-muted mr-1"></i>
                                            <span class="fs-12 text-dark text-truncate">
                                                {{ $staffPhone ?: translate('Phone not available') }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="width100 mb-1 ">
                                        <div class="d-flex align-items-center mb-0">
                                            @for ($i = 0; $i < $fullStars; $i++)
                                                <i class="las la-star fs-14 text-warning"></i>
                                            @endfor

                                            @if ($hasHalfStar)
                                                <i class="las la-star-half-alt fs-14 text-warning"></i>
                                            @endif

                                            @for ($i = 0; $i < $emptyStars; $i++)
                                                <i class="lar la-star fs-14 text-muted"></i>
                                            @endfor
                                        </div>
                                        @if($ratingCount > 0)
                                            <div class="mt-1">
                                                <span class="fs-11 text-dark font-weight-semibold">
                                                    {{ $ratingValue }}/5
                                                </span>
                                                <span class="fs-10 text-muted ml-1">
                                                    • {{ $ratingCount }} {{ \Illuminate\Support\Str::plural(translate('Review'), $ratingCount) }}
                                                </span>
                                            </div>
                                        @else
                                            <div class="mt-1 fs-10 text-muted">
                                                {{ translate('No reviews yet') }}
                                            </div>
                                        @endif
                                    </div>
                                    </div>

                                    <div class="mt-auto pt-2 d-flex flex-wrap mt-15 justify-content-center">
                                        <button type="button"
                                                class="btn btn-outline-primary btn-xs rounded-pill px-3 mb-2 mr-2 js-open-support-modal"
                                                data-staff-id="{{ $staff->id }}"
                                                data-staff-name="{{ $user->name ?? '' }}"
                                                data-channel="video">
                                            <i class="las la-video mr-1 fs-12"></i>
                                            {{ translate('Video Meet') }}
                                        </button>
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-xs rounded-pill px-3 mb-2 js-open-support-modal"
                                                data-staff-id="{{ $staff->id }}"
                                                data-staff-name="{{ $user->name ?? '' }}"
                                                data-channel="callback">
                                            <i class="las la-phone-volume mr-1 fs-12"></i>
                                            {{ translate('Call Back') }}
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

@section('modal')
    @php $currentUser = auth()->user(); @endphp

    <div class="modal fade" id="supportRequestModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-700 fs-16 text-dark">
                        {{ translate('Schedule Support') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('support_request.store') }}">
                    @csrf
                    <input type="hidden" name="staff_id" id="support-staff-id">
                    <input type="hidden" name="channel" id="support-channel">

                    <div class="modal-body pt-0">
                        {{-- <div class="mb-3">
                            <span class="badge badge-soft-primary text-uppercase fs-11 px-3 py-1 rounded-pill" id="support-channel-badge">
                                {{ translate('Video Meet') }}
                            </span>
                        </div> --}}

                        <div class="form-group mb-3">
                            <label class="fs-12 text-uppercase text-muted mb-1 d-flex justify-content-between">
                                <span>{{ translate('Your Details') }}</span>
                                <span class="fs-11 text-muted">
                                    {{ translate('You can adjust your contact details for this support request.') }}
                                </span>
                            </label>
                            <div class="row gutters-5">
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <input type="text"
                                           class="form-control"
                                           name="name"
                                           value="{{ old('name', $currentUser->name ?? '') }}"
                                           placeholder="{{ translate('Name') }}"
                                           required>
                                </div>
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <input type="email"
                                           class="form-control"
                                           name="email"
                                           value="{{ old('email', $currentUser->email ?? '') }}"
                                           placeholder="{{ translate('Email') }}">
                                </div>
                                <div class="col-md-4">
                                    <input type="text"
                                           class="form-control"
                                           name="phone"
                                           value="{{ old('phone', $currentUser->phone ?? '') }}"
                                           placeholder="{{ translate('Phone') }}">
                                </div>
                            </div>
                            <small class="fs-11 text-muted d-block mt-1" id="support-contact-hint">
                                {{ translate('For video meetings, email is required. For call back, phone is required.') }}
                            </small>
                        </div>

                        <div class="form-group mb-3">
                            <label class="fs-12 text-uppercase text-muted mb-1">{{ translate('Support Staff') }}</label>
                            <div class="row gutters-5">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <input type="text"
                                           class="form-control"
                                           id="support-staff-name"
                                           placeholder="{{ translate('Staff name') }}"
                                           readonly>
                                </div>
                                <div class="col-md-6">
                                    <input type="text"
                                           class="form-control"
                                           id="support-staff-channel-label"
                                           placeholder="{{ translate('Channel') }}"
                                           readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label for="support-scheduled-at" class="fs-12 text-uppercase text-muted mb-1">
                                {{ translate('Preferred Date & Time') }}
                            </label>
                            <input type="datetime-local"
                                   class="form-control"
                                   id="support-scheduled-at"
                                   name="scheduled_at"
                                   required>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-dismiss="modal">
                            {{ translate('Cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">
                            <i class="las la-check-circle mr-1"></i>
                            {{ translate('Confirm') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <style>
        .support-staff-card {
            border-color: #e5e7eb;
            border-radius: 10px;
            min-height: 180px;
        }

        @media (min-width: 992px) {
            .support-staff-card .card-body {
                min-height: 190px;
            }
        }
    </style>
    <script>
        (function () {
            'use strict';

            $(document).on('click', '.js-open-support-modal', function () {
                var staffId   = $(this).data('staff-id');
                var staffName = $(this).data('staff-name') || '';
                var channel   = $(this).data('channel');

                $('#support-staff-id').val(staffId);
                $('#support-staff-name').val(staffName);
                $('#support-channel').val(channel);

                var channelLabel = '';
                if (channel === 'video') {
                    channelLabel = "{{ translate('Video Meet') }}";
                } else if (channel === 'callback') {
                    channelLabel = "{{ translate('Call Back') }}";
                }
                $('#support-staff-channel-label').val(channelLabel);

                var badge = $('#support-channel-badge');
                var contactHint = $('#support-contact-hint');
                var $emailInput = $('input[name="email"]');
                var $phoneInput = $('input[name="phone"]');

                if (channel === 'video') {
                    badge.text("{{ translate('Video Meet') }}")
                         .removeClass('badge-soft-secondary')
                         .addClass('badge-soft-primary');

                    $emailInput.prop('required', true);
                    $phoneInput.prop('required', false);
                    contactHint.text("{{ translate('For video meetings, email is required and phone is optional.') }}");
                } else {
                    badge.text("{{ translate('Call Back') }}")
                         .removeClass('badge-soft-primary')
                         .addClass('badge-soft-secondary');

                    $emailInput.prop('required', false);
                    $phoneInput.prop('required', true);
                    contactHint.text("{{ translate('For call back, phone is required and email is optional.') }}");
                }

                var dtInput = $('#support-scheduled-at');
                if (!dtInput.val()) {
                    var now = new Date();
                    now.setMinutes(now.getMinutes() + 30);
                    var iso = now.toISOString().slice(0, 16);
                    dtInput.val(iso);
                }

                $('#supportRequestModal').modal('show');
            });
        })();
    </script>
@endsection
