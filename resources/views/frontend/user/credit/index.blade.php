@extends('frontend.layouts.user_panel')

@section('panel_content')
    @php
        $canPay = $paymentsReady && $paymentMethods->isNotEmpty();
        $canOpenTopup = $canTopUp && $canPay && !empty($prepaidReady);
        $limit = round((float) ($user->credit_limit ?? 0), 2);
        $usedPct = $limit > 0 ? min(100, round($outstanding / $limit * 100)) : 0;
        $nextDue = $openUses->filter(function ($use) {
            return $use->due_on !== null;
        })->sortBy('due_on')->first();
        $openTotal = round((float) $openUses->sum('amount'), 2);

        // "in 3 days", "today", "4 days overdue" for a credit due date.
        $dueText = function ($use) {
            $days = $use->daysLeft();
            if ($days === null) {
                return translate('Starts on dispatch');
            }
            if ($days < 0) {
                return abs($days) . ' ' . (abs($days) === 1 ? translate('day overdue') : translate('days overdue'));
            }
            if ($days === 0) {
                return translate('Due today');
            }

            return translate('Due in') . ' ' . $days . ' ' . ($days === 1 ? translate('day') : translate('days'));
        };
        $dueTone = function ($use) {
            $days = $use->daysLeft();
            if ($days === null) {
                return 'info';
            }
            if ($days < 0) {
                return 'danger';
            }

            return $days <= 7 ? 'warning' : 'muted';
        };

        $sortUrl = function ($by, $dir) {
            return route('customer.credit.index', array_merge(request()->except('open_page'), ['open_sort_by' => $by, 'open_sort_dir' => $dir]));
        };
        $openSorts = [
            ['due_on', 'asc', translate('Due date: soonest first')],
            ['due_on', 'desc', translate('Due date: latest first')],
            ['amount', 'desc', translate('Amount: high to low')],
            ['amount', 'asc', translate('Amount: low to high')],
            ['created_at', 'desc', translate('Newest first')],
        ];
        $historyUrl = function ($by, $dir) {
            return route('customer.credit.index', array_merge(request()->except('page'), ['sort_by' => $by, 'sort_dir' => $dir]));
        };
        $historySorts = [
            ['created_at', 'desc', translate('Newest first')],
            ['created_at', 'asc', translate('Oldest first')],
            ['amount', 'desc', translate('Amount: high to low')],
            ['amount', 'asc', translate('Amount: low to high')],
        ];
    @endphp

    <style>
        .cr { --cr-brand: #2b56a1; --cr-brand-dark: #1d3d78; --cr-line: #e7eaf0; --cr-muted: #6b7280; --cr-soft: #f6f8fb; }
        .cr .cr-card { background: #fff; border: 1px solid var(--cr-line); }
        .cr .cr-amount { font-variant-numeric: tabular-nums; white-space: nowrap; }
        .cr .cr-label { font-size: 11px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: var(--cr-muted); }
        .cr .cr-hero { background: linear-gradient(135deg, var(--cr-brand) 0%, var(--cr-brand-dark) 100%); color: #fff; }
        .cr .cr-hero .cr-label { color: rgba(255, 255, 255, .75); }
        .cr .cr-bar { height: 8px; border-radius: 4px; background: rgba(255, 255, 255, .22); overflow: hidden; }
        .cr .cr-bar > span { display: block; height: 100%; border-radius: 4px; background: #fff; }
        .cr .cr-bar.is-high > span { background: #ffb4a8; }
        .cr .cr-stat { padding: 14px 16px; }
        .cr .cr-stat + .cr-stat { border-left: 1px solid var(--cr-line); }
        .cr .cr-pill { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; line-height: 1.4; white-space: nowrap; }
        .cr .cr-pill-danger { background: #fdecea; color: #b42318; }
        .cr .cr-pill-warning { background: #fff4e0; color: #9a5b00; }
        .cr .cr-pill-info { background: #e8effc; color: var(--cr-brand); }
        .cr .cr-pill-success { background: #e6f5ec; color: #1b7a3d; }
        .cr .cr-pill-muted { background: #f1f3f6; color: #4b5563; }
        .cr .text-tone-danger { color: #b42318; }
        .cr .text-tone-warning { color: #9a5b00; }
        .cr .text-tone-info { color: var(--cr-brand); }
        .cr .text-tone-muted { color: var(--cr-muted); }
        .cr .cr-steps { display: flex; gap: 8px; }
        .cr .cr-step { flex: 1 1 0; display: flex; align-items: center; gap: 8px; padding: 10px 12px; background: var(--cr-soft); min-width: 0; }
        .cr .cr-step b { flex: 0 0 22px; height: 22px; border-radius: 50%; background: var(--cr-brand); color: #fff; font-size: 12px; display: flex; align-items: center; justify-content: center; }
        .cr .cr-step span { font-size: 12px; line-height: 1.3; color: #374151; }
        .cr .cr-head { padding: 16px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
        .cr .cr-cols { padding: 8px 16px 8px 50px; background: var(--cr-soft); border-top: 1px solid var(--cr-line); }
        .cr .cr-item { display: flex; align-items: flex-start; gap: 14px; padding: 14px 16px; border-top: 1px solid var(--cr-line); margin: 0; cursor: pointer; transition: background .15s; }
        .cr .cr-item:hover { background: #fafbfd; }
        .cr .cr-item.is-checked { background: #f3f6fc; }
        .cr .cr-item input[type=checkbox] { width: 20px; height: 20px; margin-top: 2px; flex: 0 0 20px; accent-color: var(--cr-brand); cursor: pointer; }
        .cr .cr-row { padding: 12px 16px; border-top: 1px solid var(--cr-line); }
        .cr .cr-pay { background: var(--cr-soft); border-top: 1px solid var(--cr-line); padding: 16px; }
        .cr .cr-input-money { position: relative; }
        .cr .cr-input-money span { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--cr-muted); pointer-events: none; }
        .cr .cr-input-money input { padding-left: 28px; }
        .cr .form-control, .cr .btn { min-height: 44px; }
        .cr .cr-sort { max-width: 230px; min-height: 38px !important; height: 38px; font-size: 13px; padding-top: 0; padding-bottom: 0; line-height: 36px; }
        .cr .cr-btn-brand { background: var(--cr-brand) !important; border-color: var(--cr-brand) !important; color: #fff !important; }
        .cr .cr-btn-brand:hover { background: var(--cr-brand-dark) !important; }
        .cr .cr-empty { padding: 40px 16px; text-align: center; color: var(--cr-muted); border-top: 1px solid var(--cr-line); }
        @media (min-width: 992px) {
            .cr .fs-lg-28 { font-size: 28px !important; }
            .cr .fs-lg-18 { font-size: 18px !important; }
        }
        @media (max-width: 767.98px) {
            .cr .cr-stat + .cr-stat { border-left: 0; }
            .cr .cr-stats > div:nth-child(2) { border-left: 1px solid var(--cr-line); }
            .cr .cr-stats > div:nth-child(3) { border-top: 1px solid var(--cr-line); }
            .cr .cr-steps { flex-direction: column; gap: 6px; }
            .cr .cr-sort { max-width: none; flex: 1 1 auto; width: auto; }
            .cr .cr-tools { width: 100%; flex-wrap: nowrap !important; }
            .cr .cr-head > div:first-child { width: 100%; }
        }
    </style>

    <div class="cr">
        <!-- Title + main actions -->
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div class="mb-2 mr-3">
                <h1 class="fs-20 fw-700 text-dark mb-1">{{ translate('My Credit') }}</h1>
                <div class="fs-13 text-secondary">
                    @if ($user->creditIsActive())
                        {{ translate('Pay within') }} {{ (int) $user->credit_days }} {{ translate('days of dispatch') }}
                    @else
                        {{ translate('Credit not active') }}
                    @endif
                </div>
            </div>
            <div class="d-flex w-100 w-sm-auto mb-2" style="gap: 8px;">
                @if ($canOpenTopup)
                    <button type="button" class="btn btn-outline-dark rounded-0 fw-600 flex-fill" data-toggle="modal" data-target="#creditTopupModal">
                        <i class="las la-plus"></i> {{ translate('Top up') }}
                    </button>
                @endif
                @if ($openUses->isNotEmpty() && $canPay)
                    <a href="#credit-pay" class="btn cr-btn-brand rounded-0 fw-600 flex-fill">
                        <i class="las la-wallet"></i> {{ translate('Pay credit') }}
                    </a>
                @endif
            </div>
        </div>

        @if (!$usesReady || !$paymentsReady)
            <div class="alert alert-warning">{{ translate('Credit payments are not ready yet. Run sqlupdates/credit_payments.sql on the database.') }}</div>
        @endif
        @if (empty($prepaidReady))
            <div class="alert alert-warning">{{ translate('Credit top up is not ready yet. Run sqlupdates/credit_prepaid.sql on the database.') }}</div>
        @endif

        @if ($blocked)
            <div class="d-flex align-items-start p-3 mb-3" style="background: #fdecea; border-left: 4px solid #b42318;">
                <i class="las la-exclamation-circle fs-24 mr-2" style="color: #b42318;"></i>
                <div class="flex-grow-1">
                    <div class="fw-700" style="color: #b42318;">{{ translate('Credit is overdue') }}</div>
                    <div class="fs-13 text-dark">{{ translate('Pay the overdue credit below before placing another order. Top up does not clear an overdue credit.') }}</div>
                </div>
                @if ($canPay)
                    <a href="#credit-pay" class="btn btn-sm rounded-0 fw-600 ml-2 flex-shrink-0 text-white" style="background: #b42318; min-height: 36px;">{{ translate('Pay now') }}</a>
                @endif
            </div>
        @endif

        <!-- Overview -->
        <div class="row no-gutters cr-card mb-3">
            <div class="col-md-5 cr-hero p-3 p-lg-4">
                <div class="cr-label">{{ translate('Available to use') }}</div>
                <div class="fs-24 fs-lg-28 fw-700 cr-amount my-1">{{ single_price($available) }}</div>
                @if ($limit > 0)
                    <div class="cr-bar {{ $usedPct >= 80 ? 'is-high' : '' }} mt-3" role="progressbar" aria-valuenow="{{ $usedPct }}" aria-valuemin="0" aria-valuemax="100"
                        aria-label="{{ translate('Credit used') }}">
                        <span style="width: {{ $usedPct }}%;"></span>
                    </div>
                    <div class="d-flex justify-content-between fs-12 mt-2" style="opacity: .85;">
                        <span>{{ $usedPct }}% {{ translate('used') }}</span>
                        <span class="cr-amount">{{ translate('Limit') }} {{ single_price($limit) }}</span>
                    </div>
                @endif
            </div>
            <div class="col-md-7 d-flex">
                <div class="row no-gutters w-100 cr-stats">
                    <div class="col-6 col-md-4 cr-stat">
                        <div class="cr-label">{{ translate('Owed') }}</div>
                        <div class="fs-16 fs-lg-18 fw-700 cr-amount mt-1 {{ $blocked ? 'text-tone-danger' : 'text-dark' }}">{{ single_price($outstanding) }}</div>
                        <div class="fs-12 text-secondary">{{ $openUses->count() }} {{ $openUses->count() === 1 ? translate('open credit') : translate('open credits') }}</div>
                    </div>
                    <div class="col-6 col-md-4 cr-stat">
                        <div class="cr-label">{{ translate('Top up balance') }}</div>
                        <div class="fs-16 fs-lg-18 fw-700 cr-amount mt-1 text-dark">{{ single_price($prepaid ?? 0) }}</div>
                        <div class="fs-12 text-secondary">{{ translate('Used after credit') }}</div>
                    </div>
                    <div class="col-12 col-md-4 cr-stat">
                        <div class="cr-label">{{ $nextDue && $nextDue->daysLeft() < 0 ? translate('Overdue since') : translate('Next due') }}</div>
                        @if ($nextDue)
                            <div class="fs-16 fs-lg-18 fw-700 cr-amount mt-1 text-dark">{{ $nextDue->due_on->format('d M Y') }}</div>
                            <div class="fs-12 fw-600 text-tone-{{ $dueTone($nextDue) }}">{{ $dueText($nextDue) }} &middot; <span class="cr-amount">{{ single_price($nextDue->amount) }}</span></div>
                        @else
                            <div class="fs-16 fw-700 mt-1 text-dark">&mdash;</div>
                            <div class="fs-12 text-secondary">{{ translate('Nothing due') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- How an order is paid -->
        <div class="mb-4">
            <div class="cr-label mb-2">{{ translate('At checkout your order is paid in this order') }}</div>
            <div class="cr-steps">
                <div class="cr-step"><b>1</b><span>{{ translate('Credit limit') }} <span class="text-secondary">&middot; {{ translate('pay later') }}</span></span></div>
                <div class="cr-step"><b>2</b><span>{{ translate('Top up balance') }} <span class="text-secondary">&middot; {{ translate('already paid') }}</span></span></div>
                <div class="cr-step"><b>3</b><span>{{ translate('Pay the rest online') }}</span></div>
            </div>
        </div>

        <!-- Open credit + pay -->
        <div class="cr-card mb-4" id="credit-pay" style="scroll-margin-top: 90px;">
            <div class="cr-head">
                <div>
                    <h2 class="fs-17 fw-700 text-dark mb-0">{{ translate('Credit to pay') }}</h2>
                    <div class="fs-12 text-secondary">{{ translate('Pay all or part. What you pay is added back to your available credit.') }}</div>
                </div>
                @if ($openUses->count() > 1)
                    <select class="form-control rounded-0 cr-sort" aria-label="{{ translate('Sort open credit') }}" onchange="window.location = this.value">
                        @foreach ($openSorts as [$by, $dir, $text])
                            <option value="{{ $sortUrl($by, $dir) }}#credit-pay" @selected($openSortBy === $by && $openSortDir === $dir)>{{ $text }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            @if ($openUses->isEmpty())
                <div class="cr-empty">
                    <i class="las la-check-circle la-3x d-block mb-2" style="color: #1b7a3d;"></i>
                    <div class="fw-600 text-dark">{{ translate('Nothing to pay') }}</div>
                    <div class="fs-13">{{ translate('Credit you use on orders shows here.') }}</div>
                </div>
            @else
                <form method="POST" action="{{ route('customer.credit.pay') }}" id="credit-pay-form">
                    @csrf
                    <div class="cr-cols d-none d-md-flex align-items-center">
                        <div class="row no-gutters flex-grow-1 cr-label">
                            <div class="col-md-3">{{ translate('Order') }}</div>
                            <div class="col-md-3">{{ translate('Credit used') }}</div>
                            <div class="col-md-3">{{ translate('Due') }}</div>
                            <div class="col-md-3 text-right">{{ translate('To pay') }}</div>
                        </div>
                    </div>
                    <label class="cr-item cr-select-all py-2 d-flex align-items-center" style="background: #fff;">
                        <input type="checkbox" id="credit-select-all" checked>
                        <span class="fs-13 fw-600 text-dark">{{ translate('Select all') }}</span>
                    </label>
                    @foreach ($openUses as $use)
                        @php
                            $used = $use->usedOnOrder();
                            $paidSoFar = max(0, round($used - (float) $use->amount, 2));
                            $tone = $dueTone($use);
                        @endphp
                        <label class="cr-item is-checked">
                            <input type="checkbox" class="credit-use-check" name="credit_use_ids[]" value="{{ $use->id }}" data-amount="{{ round((float) $use->amount, 2) }}" checked>
                            <div class="row no-gutters flex-grow-1 align-items-center" style="min-width: 0;">
                                <div class="col-7 col-md-3 order-md-1">
                                    @if ($use->order)
                                        <a href="{{ route('purchase_history.details', encrypt($use->order->id)) }}" class="fw-700 text-dark" onclick="event.stopPropagation()">{{ $use->order->code }}</a>
                                    @else
                                        <span class="fw-700 text-dark">{{ $use->order_id ? '#' . $use->order_id : '-' }}</span>
                                    @endif
                                    <div class="fs-12 text-secondary">{{ translate('Taken') }} {{ optional($use->created_at)->format('d M Y') }}</div>
                                </div>
                                <div class="col-5 col-md-3 order-md-4 text-right">
                                    <div class="fs-16 fw-700 text-dark cr-amount">{{ single_price($use->amount) }}</div>
                                    @if ($tone === 'danger')
                                        <span class="cr-pill cr-pill-danger">{{ translate('Overdue') }}</span>
                                    @elseif ($use->due_on)
                                        <span class="cr-pill cr-pill-{{ $tone === 'warning' ? 'warning' : 'muted' }}">{{ translate('Due') }}</span>
                                    @else
                                        <span class="cr-pill cr-pill-info">{{ translate('Not dispatched') }}</span>
                                    @endif
                                </div>
                                <div class="col-7 col-md-3 order-md-3 mt-2 mt-md-0">
                                    <div class="fs-13 fw-600 text-tone-{{ $tone }}">{{ $dueText($use) }}</div>
                                    @if ($use->due_on)
                                        <div class="fs-12 text-secondary">{{ $use->due_on->format('d M Y') }}</div>
                                    @endif
                                </div>
                                <div class="col-5 col-md-3 order-md-2 mt-2 mt-md-0 text-right text-md-left">
                                    <div class="fs-13 text-dark cr-amount">{{ single_price($used) }}</div>
                                    @if ($paidSoFar > 0)
                                        <div class="fs-12" style="color: #1b7a3d;">{{ single_price($paidSoFar) }} {{ translate('paid') }}</div>
                                    @endif
                                </div>
                            </div>
                        </label>
                    @endforeach

                    @if ($canPay)
                        <div class="cr-pay">
                            <div class="row align-items-start">
                                <div class="col-12 col-lg-3 mb-3 mb-lg-0">
                                    <div class="cr-label">{{ translate('Selected') }}</div>
                                    <div class="fs-20 fw-700 text-dark cr-amount" id="credit-selected-total">{{ single_price($openTotal) }}</div>
                                </div>
                                <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
                                    <label class="cr-label d-flex justify-content-between mb-1" for="credit-pay-amount">
                                        <span>{{ translate('Amount to pay') }}</span>
                                        <a href="#" class="text-tone-info text-capitalize" id="credit-pay-full" style="letter-spacing: 0;">{{ translate('Pay full') }}</a>
                                    </label>
                                    <div class="cr-input-money">
                                        <span>{{ currency_symbol() }}</span>
                                        <input type="number" lang="en" inputmode="decimal" step="0.01" min="1" class="form-control rounded-0" id="credit-pay-amount" name="amount"
                                            value="{{ $openTotal }}" max="{{ $openTotal }}" required>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3 mb-3 mb-lg-0">
                                    <label class="cr-label mb-1" for="credit-pay-method">{{ translate('Pay with') }}</label>
                                    <select class="form-control rounded-0" id="credit-pay-method" name="payment_option" required>
                                        @foreach ($paymentMethods as $paymentMethod)
                                            <option value="{{ $paymentMethod->name }}">{{ ucfirst(translate($paymentMethod->name)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-lg-3">
                                    <label class="cr-label mb-1 d-none d-lg-block">&nbsp;</label>
                                    <button type="submit" class="btn cr-btn-brand rounded-0 fw-700 btn-block" id="credit-pay-btn">{{ translate('Pay now') }}</button>
                                </div>
                            </div>
                            <div class="fs-13 mt-3 text-secondary" id="credit-pay-note" aria-live="polite"></div>
                        </div>
                    @else
                        <div class="cr-pay fs-13 text-secondary">{{ translate('No online payment method is turned on. Contact us to pay your credit.') }}</div>
                    @endif
                </form>
            @endif
        </div>

        <!-- Payment history -->
        <div class="cr-card">
            <div class="cr-head">
                <h2 class="fs-17 fw-700 text-dark mb-0">{{ translate('Payment history') }}</h2>
                <div class="d-flex align-items-center flex-wrap cr-tools" style="gap: 8px;">
                    @if ($filtersApplied)
                        <a href="{{ route('customer.credit.index') }}" class="cr-pill cr-pill-info">{{ translate('Filters on') }} &times;</a>
                    @endif
                    @if ($paymentsReady && $payments && $payments->total() > 1)
                        <select class="form-control rounded-0 cr-sort" aria-label="{{ translate('Sort payments') }}" onchange="window.location = this.value">
                            @foreach ($historySorts as [$by, $dir, $text])
                                <option value="{{ $historyUrl($by, $dir) }}" @selected($sortBy === $by && $sortDir === $dir)>{{ $text }}</option>
                            @endforeach
                        </select>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-dark rounded-0 flex-shrink-0" style="height: 38px; min-height: 38px;" data-toggle="modal" data-target="#creditFilterModal">
                        <i class="las la-filter"></i> {{ translate('Filter') }}
                    </button>
                </div>
            </div>

            @if (!$paymentsReady || !$payments || $payments->isEmpty())
                <div class="cr-empty">
                    <i class="las la-receipt la-3x d-block mb-2 opacity-60"></i>
                    {{ $filtersApplied ? translate('No payment matches these filters') : translate('No payment yet') }}
                </div>
            @else
                <div class="cr-cols d-none d-md-flex" style="padding-left: 16px;">
                    <div class="row no-gutters flex-grow-1 cr-label">
                        <div class="col-md-3">{{ translate('Date') }}</div>
                        <div class="col-md-3">{{ translate('Type') }}</div>
                        <div class="col-md-3">{{ translate('Paid with') }}</div>
                        <div class="col-md-3 text-right">{{ translate('Amount') }}</div>
                    </div>
                </div>
                @foreach ($payments as $payment)
                    <div class="cr-row">
                        <div class="row no-gutters align-items-center">
                            <div class="col-7 col-md-3 order-md-2">
                                <span class="cr-pill {{ $payment->purpose === 'topup' ? 'cr-pill-info' : 'cr-pill-success' }}">{{ $payment->purposeLabel() }}</span>
                            </div>
                            <div class="col-5 col-md-3 order-md-4 text-right">
                                <span class="fs-15 fw-700 cr-amount {{ (float) $payment->amount < 0 ? 'text-tone-danger' : 'text-dark' }}">{{ single_price($payment->amount) }}</span>
                            </div>
                            <div class="col-7 col-md-3 order-md-1 mt-1 mt-md-0 fs-13 text-secondary">
                                {{ optional($payment->created_at)->format('d M Y, H:i') }}
                            </div>
                            <div class="col-5 col-md-3 order-md-3 mt-1 mt-md-0 fs-13 text-right text-md-left text-dark">
                                {{ $payment->methodLabel() }}
                                @if ($payment->reference() !== '')
                                    <div class="fs-11 text-secondary text-truncate" title="{{ $payment->reference() }}">{{ translate('Ref') }} {{ $payment->reference() }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
                @if ($payments->hasPages())
                    <div class="aiz-pagination px-3 py-3 border-top">
                        {{ $payments->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection

@section('modal')
    <!-- Top up modal -->
    @if ($canTopUp && $canPay && !empty($prepaidReady))
        <div class="modal fade" id="creditTopupModal" tabindex="-1" role="dialog" aria-labelledby="creditTopupModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content rounded-0">
                    <div class="modal-header">
                        <h5 class="modal-title fw-700" id="creditTopupModalLabel">{{ translate('Top up credit') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <form action="{{ route('customer.credit.topup') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p class="fs-13 text-secondary mb-3">{{ translate('Pay now and use it on later orders. At checkout it is used after your credit limit and is not billed again. It does not pay an open credit.') }}</p>
                            <div class="form-group">
                                <label class="fw-600 fs-13" for="credit-topup-amount">{{ translate('Amount') }}</label>
                                <input type="number" lang="en" inputmode="decimal" step="0.01" min="1" class="form-control rounded-0" style="min-height: 44px;" id="credit-topup-amount" name="amount"
                                    placeholder="{{ translate('Enter amount') }}" required>
                                <div class="d-flex flex-wrap mt-2" style="gap: 6px;">
                                    @foreach ([1000, 5000, 10000, 25000] as $quick)
                                        <button type="button" class="btn btn-sm btn-light rounded-pill px-3 js-topup-quick" data-amount="{{ $quick }}">{{ single_price($quick) }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label class="fw-600 fs-13" for="credit-topup-method">{{ translate('Pay with') }}</label>
                                <select class="form-control rounded-0" style="min-height: 44px;" id="credit-topup-method" name="payment_option" required>
                                    @foreach ($paymentMethods as $paymentMethod)
                                        <option value="{{ $paymentMethod->name }}">{{ ucfirst(translate($paymentMethod->name)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light rounded-0" data-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary rounded-0 fw-600 px-4" style="background: #2b56a1; border-color: #2b56a1;">{{ translate('Continue to pay') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Filter modal -->
    <div class="modal fade" id="creditFilterModal" tabindex="-1" role="dialog" aria-labelledby="creditFilterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form method="GET" action="{{ route('customer.credit.index') }}" class="w-100">
                <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                <input type="hidden" name="open_sort_by" value="{{ $openSortBy }}">
                <input type="hidden" name="open_sort_dir" value="{{ $openSortDir }}">
                <div class="modal-content rounded-0">
                    <div class="modal-header">
                        <h5 class="modal-title fw-700" id="creditFilterModalLabel">{{ translate('Filter payments') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="fw-600 fs-13" for="credit-filter-purpose">{{ translate('Type') }}</label>
                            <select class="form-control rounded-0" id="credit-filter-purpose" name="purpose">
                                <option value="">{{ translate('All') }}</option>
                                <option value="topup" @selected($filters['purpose'] === 'topup')>{{ translate('Top up') }}</option>
                                <option value="settle" @selected($filters['purpose'] === 'settle')>{{ translate('Payment') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="fw-600 fs-13" for="credit-filter-method">{{ translate('Paid with') }}</label>
                            <select class="form-control rounded-0" id="credit-filter-method" name="method">
                                <option value="">{{ translate('All') }}</option>
                                @foreach ($paymentMethodsUsed as $method)
                                    <option value="{{ $method }}" @selected($filters['method'] === $method)>{{ ucfirst(str_replace('_', ' ', translate($method))) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label class="fw-600 fs-13">{{ translate('Date') }}</label>
                            <div class="row gutters-5">
                                <div class="col-6">
                                    <input type="date" class="form-control rounded-0" name="date_from" value="{{ $filters['date_from'] }}" aria-label="{{ translate('From') }}">
                                </div>
                                <div class="col-6">
                                    <input type="date" class="form-control rounded-0" name="date_to" value="{{ $filters['date_to'] }}" aria-label="{{ translate('To') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('customer.credit.index') }}" class="btn btn-light rounded-0">{{ translate('Reset') }}</a>
                        <button type="submit" class="btn btn-primary rounded-0 fw-600 px-4" style="background: #2b56a1; border-color: #2b56a1;">{{ translate('Apply') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        (function () {
            document.addEventListener('click', function (event) {
                var quick = event.target.closest('.js-topup-quick');
                if (quick) {
                    document.getElementById('credit-topup-amount').value = quick.getAttribute('data-amount');
                }
            });

            var form = document.getElementById('credit-pay-form');
            if (!form) {
                return;
            }
            // Match the site's money format by reading it off a server-formatted zero.
            var template = @json(single_price(0));
            var decimals = ((template.match(/[0-9][0-9.,]*/) || ['0'])[0].split('.')[1] || '').length;
            var text = {
                full: @json(translate('Clears the selected credit in full.')),
                part: @json(translate('pays now and goes back into your available credit.')),
                left: @json(translate('stays owed on the selected credit.')),
                over: @json(translate('This is more than the selected credit.')),
                none: @json(translate('Select at least one credit to pay.'))
            };
            var selected = 0;

            function money(sum) {
                var number = sum.toLocaleString('en-IN', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
                return template.replace(/[0-9][0-9.,]*/, number);
            }

            function boxes() {
                return Array.prototype.slice.call(form.querySelectorAll('.credit-use-check'));
            }

            function note() {
                var amount = document.getElementById('credit-pay-amount');
                var out = document.getElementById('credit-pay-note');
                var button = document.getElementById('credit-pay-btn');
                if (!amount || !out) {
                    return;
                }
                var value = Math.round((parseFloat(amount.value) || 0) * 100) / 100;
                var bad = selected <= 0 || value <= 0 || value - selected > 0.009;
                if (selected <= 0) {
                    out.textContent = text.none;
                } else if (value - selected > 0.009) {
                    out.textContent = text.over;
                } else if (selected - value <= 0.009) {
                    out.textContent = text.full;
                } else {
                    out.innerHTML = '<b class="text-dark">' + money(value) + '</b> ' + text.part + ' <b class="text-dark">' + money(selected - value) + '</b> ' + text.left;
                }
                out.style.color = bad && selected > 0 ? '#b42318' : '';
                if (button) {
                    button.disabled = bad;
                    button.textContent = bad ? @json(translate('Pay now')) : @json(translate('Pay')) + ' ' + money(value);
                }
            }

            function refresh() {
                var list = boxes();
                var sum = 0;
                var picked = 0;
                list.forEach(function (box) {
                    box.closest('.cr-item').classList.toggle('is-checked', box.checked);
                    if (box.checked) {
                        sum += parseFloat(box.getAttribute('data-amount')) || 0;
                        picked++;
                    }
                });
                selected = Math.round(sum * 100) / 100;
                var total = document.getElementById('credit-selected-total');
                var all = document.getElementById('credit-select-all');
                var amount = document.getElementById('credit-pay-amount');
                if (total) {
                    total.textContent = money(selected);
                }
                if (amount) {
                    amount.max = selected;
                    amount.min = Math.min(1, selected);
                    amount.value = selected;
                }
                if (all) {
                    all.checked = list.length > 0 && picked === list.length;
                    all.indeterminate = picked > 0 && picked < list.length;
                }
                note();
            }

            form.addEventListener('change', function (event) {
                if (event.target.id === 'credit-select-all') {
                    boxes().forEach(function (box) {
                        box.checked = event.target.checked;
                    });
                }
                if (event.target.id === 'credit-select-all' || event.target.classList.contains('credit-use-check')) {
                    refresh();
                }
            });
            form.addEventListener('input', function (event) {
                if (event.target.id === 'credit-pay-amount') {
                    note();
                }
            });
            var full = document.getElementById('credit-pay-full');
            if (full) {
                full.addEventListener('click', function (event) {
                    event.preventDefault();
                    document.getElementById('credit-pay-amount').value = selected;
                    note();
                });
            }
            refresh();
        })();
    </script>
@endsection
