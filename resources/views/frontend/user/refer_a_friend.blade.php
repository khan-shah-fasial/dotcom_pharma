@extends('frontend.layouts.app')

@section('content')
    <section class="py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card rounded-0 border">
                        <div class="card-header">
                            <h1 class="h5 mb-0">{{ translate('Refer a Friend') }}</h1>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">
                                {{ translate('Share this link with your friends. They get discount on first successful order and you get referral points.') }}
                            </p>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted small">{{ translate('Your Referral Points') }}</div>
                                        <div class="h4 mb-0 fw-700">{{ $referralPoints ?? 0 }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-muted small">{{ translate('Points Per Successful Referral') }}</div>
                                        <div class="h4 mb-0 fw-700">{{ $pointsPerReferral ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="input-group">
                                <input type="text" id="referral_link_input" class="form-control rounded-0" readonly value="{{ $referralLink }}">
                                <div class="input-group-append">
                                    <button type="button" id="copy_referral_link_btn" class="btn btn-primary rounded-0">
                                        {{ translate('Copy Link') }}
                                    </button>
                                </div>
                            </div>

                            <small class="text-muted d-block mt-2">
                                {{ translate('Referral rewards are applied based on admin settings and successful payment.') }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
    <script>
        (function () {
            var btn = document.getElementById('copy_referral_link_btn');
            var input = document.getElementById('referral_link_input');
            if (!btn || !input) return;

            btn.addEventListener('click', function () {
                input.select();
                input.setSelectionRange(0, 99999);
                try {
                    document.execCommand('copy');
                    AIZ.plugins.notify('success', '{{ translate('Copied') }}');
                } catch (e) {
                    AIZ.plugins.notify('warning', '{{ translate('Unable to copy automatically') }}');
                }
            });
        })();
    </script>
@endsection
