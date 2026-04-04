@extends('frontend.layouts.user_panel')

@section('panel_content')
    @php
        $referralDiscountEnabled = (int) (get_setting('referral_discount_enabled') ?? 0) === 1;
        $referredUserDiscount = (float) (get_setting('referral_discount_amount') ?? 0);
        $minOrderAmount = (float) (get_setting('referral_discount_min_order_amount') ?? 0);
        $rewardAmount = (float) ($rewardAmountPerReferral ?? 0);
        $totalEarnings = (float) ($earnedReferralAmount ?? 0);
    @endphp
<style>
    button#copy_referral_link_btn {
        display: inline-grid;
        grid-auto-flow: column;
        align-content: center;
        align-items: center;
        justify-items: end;
    }
</style>
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fs-20 fw-700 text-dark mb-1">{{ translate('Refer a Friend') }}</h1>
                <p class="text-muted mb-0 fs-13">
                    {{ translate('Share your referral link. After your friend’s first successful payment, both of you receive wallet credit.') }}
                </p>
            </div>
            <div class="col-md-4 text-md-right mt-3 mt-md-0">
                @if ($referralDiscountEnabled)
                    <span class="badge badge-inline badge-success px-3 py-2">{{ translate('Program Active') }}</span>
                @else
                    <span class="badge badge-inline badge-secondary px-3 py-2">{{ translate('Program Inactive') }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row gutters-16 mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100 referral-stat-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-4">
                        <span class="referral-icon-wrap bg-soft-success text-success mr-3">
                            <i class="las la-wallet"></i>
                        </span>
                        <div class="fs-14 text-muted">{{ translate('Total Referral Earnings') }}</div>
                    </div>
                    <div class="fs-28 fw-700 text-dark">{{ single_price($totalEarnings) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100 referral-stat-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-4">
                        <span class="referral-icon-wrap bg-soft-warning text-warning mr-3">
                            <i class="las la-gift"></i>
                        </span>
                        <div class="fs-14 text-muted">{{ translate('Reward Per Referral') }}</div>
                    </div>
                    <div class="fs-28 fw-700 text-dark">{{ single_price($rewardAmount) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100 referral-stat-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-4">
                        <span class="referral-icon-wrap bg-soft-info text-info mr-3">
                            <i class="las la-users"></i>
                        </span>
                        <div class="fs-14 text-muted">{{ translate('Friend Wallet Credit') }}</div>
                    </div>
                    <div class="fs-28 fw-700 text-dark">{{ single_price($referredUserDiscount) }}</div>
                    <div class="fs-13 text-muted mt-2">
                        {{ translate('Credited after first successful payment') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 referral-block">
        <div class="card-body p-4">
            <h2 class="fs-18 fw-700 text-dark mb-3">{{ translate('Your Referral Link') }}</h2>
            <div class="d-md-flex align-items-center referral-link-group">
                <input type="text" id="referral_link_input" class="form-control referral-link-input" readonly
                    value="{{ $referralLink }}">
                <button type="button" id="copy_referral_link_btn" class="btn btn-success referral-copy-btn">
                    <i class="las la-copy fs-18 mr-2"></i>{{ translate('Copy Link') }}
                </button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 referral-block">
        <div class="card-body p-4">
            <h2 class="fs-24 fw-700 text-dark mb-4">{{ translate('How It Works') }}</h2>
            <div class="row gutters-16">
                <div class="col-md-4 mb-3">
                    <div class="referral-step-card h-100">
                        <div class="d-flex align-items-center mb-3">
                            <span class="referral-step-number mr-3">1</span>
                            <i class="las la-share-alt referral-step-icon text-success"></i>
                        </div>
                        <h3 class="fs-21 fw-600 text-dark mb-2">{{ translate('Share Link') }}</h3>
                        <p class="text-muted fs-15 mb-0">
                            {{ translate('Send your referral link to your friend.') }}
                        </p>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="referral-step-card h-100">
                        <div class="d-flex align-items-center mb-3">
                            <span class="referral-step-number mr-3">2</span>
                            <i class="las la-shopping-cart referral-step-icon text-success"></i>
                        </div>
                        <h3 class="fs-21 fw-600 text-dark mb-2">{{ translate('Friend’s First Order') }}</h3>
                        <p class="text-muted fs-15 mb-0">
                            {{ translate('Friend places and pays their first order.') }}
                        </p>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="referral-step-card h-100">
                        <div class="d-flex align-items-center mb-3">
                            <span class="referral-step-number mr-3">3</span>
                            <i class="las la-credit-card referral-step-icon text-success"></i>
                        </div>
                        <h3 class="fs-21 fw-600 text-dark mb-2">{{ translate('Both Earn Wallet Credit') }}</h3>
                        <p class="text-muted fs-15 mb-0">
                            {{ translate('After that payment, both you and your friend receive wallet credit of') }} {{ single_price($rewardAmount) }}.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm referral-note-card">
        <div class="card-body p-4">
            <h2 class="fs-18 fw-700 text-dark mb-3">{{ translate('Important Notes') }}</h2>
            <div class="referral-note-list">
                <div class="referral-note-item">
                    <i class="las la-arrow-right text-success mr-2"></i>
                    <span>{{ translate('Wallet credit is granted once after the referred user’s first paid order.') }}</span>
                </div>
                <div class="referral-note-item">
                    <i class="las la-arrow-right text-success mr-2"></i>
                    <span>{{ translate('Wallet reward is credited only after successful payment.') }}</span>
                </div>
                <div class="referral-note-item mb-0">
                    <i class="las la-arrow-right text-success mr-2"></i>
                    <span>{{ translate('You can view all referral credits in Wallet transactions.') }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <style>
        .referral-stat-card,
        .referral-block,
        .referral-note-card {
            border-radius: 16px;
        }

        .referral-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            background-color: #f4f8f6;
        }

        .bg-soft-success {
            background-color: #eaf7f0;
        }

        .bg-soft-warning {
            background-color: #fff4df;
        }

        .bg-soft-info {
            background-color: #e8f5f2;
        }

        .referral-link-input {
            height: 52px;
            border-radius: 14px;
            border: 1px solid #dbe3ea;
            padding: 0 18px;
            color: #5c6f87;
            background-color: #f9fbfd;
        }

        .referral-copy-btn {
            min-width: 132px;
            height: 52px;
            border-radius: 14px;
            font-weight: 600;
            margin-top: 12px;
        }

        .referral-step-card {
            border: 1px solid #d9e4ec;
            border-radius: 16px;
            padding: 22px 20px;
            background: #fff;
        }

        .referral-step-number {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #2ba56a;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
        }

        .referral-step-icon {
            font-size: 28px;
        }

        .referral-note-card {
            background: linear-gradient(180deg, #f7fbf8 0%, #f3f8f6 100%);
        }

        .referral-note-item {
            display: flex;
            align-items: flex-start;
            color: #5c6f87;
            font-size: 15px;
            margin-bottom: 12px;
        }

        .referral-note-item i {
            margin-top: 2px;
            font-size: 18px;
        }

        @media (min-width: 768px) {
            .referral-copy-btn {
                margin-top: 0;
                margin-left: 14px;
            }
        }
    </style>
@endsection

@section('script')
    <script>
        (function() {
            var btn = document.getElementById('copy_referral_link_btn');
            var input = document.getElementById('referral_link_input');
            if (!btn || !input) return;

            btn.addEventListener('click', function() {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(input.value).then(function() {
                        AIZ.plugins.notify('success', '{{ translate('Copied') }}');
                    }).catch(function() {
                        input.select();
                        input.setSelectionRange(0, 99999);
                        document.execCommand('copy');
                        AIZ.plugins.notify('success', '{{ translate('Copied') }}');
                    });
                } else {
                    input.select();
                    input.setSelectionRange(0, 99999);
                    try {
                        document.execCommand('copy');
                        AIZ.plugins.notify('success', '{{ translate('Copied') }}');
                    } catch (e) {
                        AIZ.plugins.notify('warning', '{{ translate('Unable to copy automatically') }}');
                    }
                }
            });
        })();
    </script>
@endsection
