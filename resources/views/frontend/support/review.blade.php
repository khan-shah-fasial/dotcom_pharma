@extends('frontend.layouts.app')

@section('content')
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    @if (session('success'))
                        <div class="alert alert-success mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    @php
                        $data     = $data ?? [];
                        $customer = $data['customer'] ?? [];
                        $staff    = $data['staff'] ?? [];
                        $channel  = $data['channel'] ?? null;
                    @endphp

                    <div class="card shadow-sm border-0">
                        <div class="card-header border-0 bg-white">
                            <h5 class="mb-0 fs-18 fw-700 text-dark">
                                {{ translate('Rate Your Support Experience') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="fs-13 text-uppercase text-muted mb-2">{{ translate('Support Summary') }}</h6>
                                <div class="p-3 bg-soft-primary rounded">
                                    <div class="d-flex flex-column">
                                        <span class="fs-14 fw-600 text-dark">
                                            {{ translate('Support Staff') }}: {{ $staff['name'] ?? '-' }}
                                        </span>
                                        <span class="fs-13 text-muted">
                                            {{ translate('Channel') }}:
                                            @if ($channel === 'video')
                                                {{ translate('Video Meet') }}
                                            @elseif ($channel === 'callback')
                                                {{ translate('Call Back') }}
                                            @else
                                                {{ $channel ?? '-' }}
                                            @endif
                                        </span>
                                        <span class="fs-13 text-muted">
                                            {{ translate('Preferred Date & Time') }}:
                                            @php
                                                $scheduledAt = $data['scheduled_at'] ?? null;
                                            @endphp
                                            @if ($scheduledAt)
                                                {{ \Carbon\Carbon::parse($scheduledAt)->format('d-m-Y H:i') }}
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>

                            @php
                                $reviewData = is_array($contact->review)
                                    ? $contact->review
                                    : (is_string($contact->review) ? json_decode($contact->review, true) : null);
                                $ratingValue = is_array($reviewData ?? null) ? ($reviewData['rating'] ?? null) : null;
                                $commentText = is_array($reviewData ?? null) ? ($reviewData['comment'] ?? null) : null;
                            @endphp

                            @if ($ratingValue === null)
                                <form method="POST" action="{{ route('support.review.store') }}">
                                    @csrf
                                    <input type="hidden" name="token" value="{{ $token }}">

                                    <div class="form-group mb-4">
                                        <label class="fs-13 fw-600 text-dark mb-2 d-block">
                                            {{ translate('Overall rating') }}
                                        </label>
                                        <div class="d-flex align-items-center" id="support-review-stars">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <label class="mb-0 mr-2" style="cursor:pointer;">
                                                    <input type="radio" name="rating" value="{{ $i }}" class="d-none">
                                                    <i class="las la-star fs-24 text-muted star-icon" data-value="{{ $i }}"></i>
                                                </label>
                                            @endfor
                                        </div>
                                        @error('rating')
                                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="form-group mb-4">
                                        <label class="fs-13 fw-600 text-dark mb-2" for="support-review-comment">
                                            {{ translate('Tell us more (optional)') }}
                                        </label>
                                        <textarea id="support-review-comment"
                                                  name="comment"
                                                  class="form-control"
                                                  rows="4"
                                                  placeholder="{{ translate('Share any additional feedback about your support experience') }}">{{ old('comment') }}</textarea>
                                        @error('comment')
                                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-block">
                                        {{ translate('Submit Review') }}
                                    </button>
                                </form>
                            @else
                                <div class="text-center py-4">
                                    <h6 class="fw-600 text-dark mb-2">{{ translate('Thank you!') }}</h6>
                                    <p class="fs-14 text-muted mb-3">
                                        {{ translate('You have already submitted a review for this support enquiry.') }}
                                    </p>
                                    <div class="mb-2">
                                        @php $displayRating = (int) $ratingValue; @endphp
                                        @for ($i = 0; $i < $displayRating; $i++)
                                            <i class="las la-star fs-20 text-warning"></i>
                                        @endfor
                                        @for ($i = $displayRating; $i < 5; $i++)
                                            <i class="lar la-star fs-20 text-muted"></i>
                                        @endfor
                                        <span class="ml-2 fs-14 text-dark">({{ $displayRating }}/5)</span>
                                    </div>
                                    @if ($commentText)
                                        <p class="fs-14 text-muted mb-0">
                                            "{{ $commentText }}"
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
    @if ($ratingValue === null)
        <script>
            (function () {
                'use strict';

                var starsContainer = document.getElementById('support-review-stars');
                if (!starsContainer) {
                    return;
                }

                var stars = starsContainer.querySelectorAll('.star-icon');

                function setRatingVisual(rating) {
                    stars.forEach(function (star) {
                        var value = parseInt(star.getAttribute('data-value'), 10);
                        if (value <= rating) {
                            star.classList.remove('text-muted');
                            star.classList.add('text-warning');
                        } else {
                            star.classList.remove('text-warning');
                            star.classList.add('text-muted');
                        }
                    });
                }

                stars.forEach(function (star) {
                    star.addEventListener('click', function (e) {
                        var value = parseInt(star.getAttribute('data-value'), 10);
                        var input = star.previousElementSibling;
                        if (input && input.type === 'radio') {
                            input.checked = true;
                        }
                        setRatingVisual(value);
                    });
                });
            })();
        </script>
    @endif
@endsection

