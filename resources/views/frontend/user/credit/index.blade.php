@extends('frontend.layouts.user_panel')

@section('panel_content')
    @php
        $openSort = [
            'routeName' => 'customer.credit.index',
            'sortBy' => $openSortBy,
            'sortDir' => $openSortDir,
            'sortParam' => 'open_sort_by',
            'dirParam' => 'open_sort_dir',
            'pageParam' => 'open_page',
            'linkClass' => 'text-reset',
        ];
        $paymentSort = [
            'routeName' => 'customer.credit.index',
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'linkClass' => 'text-reset',
        ];
        $pill = 'badge badge-inline p-3 fs-12';
        $pillStyle = 'border-radius: 25px; min-width: 80px !important;';
        $canPay = $paymentsReady && $paymentMethods->isNotEmpty();
    @endphp

    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('My Credit') }}</h1>
            </div>
        </div>
    </div>

    @if (!$usesReady || !$paymentsReady)
        <div class="alert alert-warning">
            {{ translate('Credit payments are not ready yet. Run sqlupdates/credit_payments.sql on the database.') }}
        </div>
    @endif

    @if (empty($prepaidReady))
        <div class="alert alert-warning">
            {{ translate('Credit top up is not ready yet. Run sqlupdates/credit_prepaid.sql on the database.') }}
        </div>
    @endif

    @if ($blocked)
        <div class="alert alert-danger d-flex align-items-center">
            <i class="las la-exclamation-circle fs-24 mr-2"></i>
            <span>{{ translate('Credit is overdue. Pay the open credit below before placing another order. Top up does not clear an overdue credit.') }}</span>
        </div>
    @endif

    <div class="row gutters-16 mb-2">
        <!-- Available credit -->
        <div class="col-lg-3 col-6 mb-4">
            <div class="bg-dark text-white text-center p-3 p-md-4 h-100 d-flex flex-column justify-content-center">
                <div class="fs-14 fw-400 opacity-80">{{ translate('Available Credit') }}</div>
                <div class="fs-16 fs-md-24 fw-700 my-1 text-nowrap">{{ single_price($available) }}</div>
                <div class="fs-12 opacity-70">
                    @if ($user->creditIsActive())
                        {{ translate('Limit') }} {{ single_price($user->credit_limit ?? 0) }}
                        &middot; {{ (int) $user->credit_days }} {{ translate('days') }}
                    @else
                        {{ translate('Credit not active') }}
                    @endif
                </div>
            </div>
        </div>

        <!-- Outstanding credit -->
        <div class="col-lg-3 col-6 mb-4">
            <div class="text-white text-center p-3 p-md-4 h-100 d-flex flex-column justify-content-center" style="background: {{ $blocked ? '#c0392b' : '#2b56a1' }};">
                <div class="fs-14 fw-400 opacity-80">{{ translate('Outstanding Credit') }}</div>
                <div class="fs-16 fs-md-24 fw-700 my-1 text-nowrap">{{ single_price($outstanding) }}</div>
                <div class="fs-12 opacity-70">
                    {{ $openUses->count() }} {{ translate('open credit') }}
                </div>
            </div>
        </div>

        <!-- Top up balance -->
        <div class="col-lg-3 col-6 mb-4">
            <div class="bg-light border text-center p-3 p-md-4 h-100 d-flex flex-column justify-content-center">
                <div class="fs-14 fw-400 text-secondary">{{ translate('Top up balance') }}</div>
                <div class="fs-16 fs-md-24 fw-700 text-dark my-1 text-nowrap">{{ single_price($prepaid ?? 0) }}</div>
                <div class="fs-12 text-secondary">{{ translate('Used after credit at checkout') }}</div>
            </div>
        </div>

        <!-- Top up action -->
        <div class="col-lg-3 col-6 mb-4">
            @if ($canTopUp && $canPay && !empty($prepaidReady))
                <div class="p-4 text-center bg-light has-transition border h-100 hov-bg-soft-light c-pointer"
                    data-toggle="modal" data-target="#creditTopupModal" role="button" tabindex="0">
                    <span class="size-60px rounded-circle mx-auto bg-dark d-flex align-items-center justify-content-center mb-3">
                        <i class="las la-plus la-3x text-white"></i>
                    </span>
                    <div class="fs-14 fw-600 text-dark">{{ translate('Top up credit') }}</div>
                </div>
            @else
                <div class="p-4 text-center bg-light border h-100 opacity-60">
                    <span class="size-60px rounded-circle mx-auto bg-secondary d-flex align-items-center justify-content-center mb-3">
                        <i class="las la-plus la-3x text-white"></i>
                    </span>
                    <div class="fs-14 fw-600 text-dark">{{ translate('Top up credit') }}</div>
                    <div class="fs-12 text-secondary mt-1">
                        {{ !$canTopUp ? translate('Credit is not active, so it cannot be topped up.') : translate('No online payment method is turned on.') }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Open credit -->
    <div class="card rounded-0 shadow-none border mb-4">
        <div class="card-header border-bottom-0">
            <div>
                <h5 class="mb-0 fs-20 fw-700 text-dark">{{ translate('Pay credit') }}</h5>
                <p class="fs-13 text-secondary mb-0 mt-1">{{ translate('Pay all or part of your open credit. Whatever you pay is added back to your available credit; the oldest due is cleared first. An overdue block lifts when nothing overdue is left.') }}</p>
            </div>
        </div>
        <div class="card-body py-0">
            @if ($openUses->isEmpty())
                <div class="text-center text-secondary py-5">
                    <i class="las la-check-circle la-3x mb-2 d-block opacity-60"></i>
                    {{ translate('No credit to pay') }}
                </div>
            @else
                <form method="POST" action="{{ route('customer.credit.pay') }}" id="credit-pay-form">
                    @csrf
                    <table class="table aiz-table mb-3">
                        <thead class="text-gray fs-12">
                            <tr>
                                <th class="pl-0" width="40">
                                    <label class="aiz-checkbox mb-0 d-block pl-0" style="height: 18px; width: 18px;">
                                        <input type="checkbox" id="credit-select-all" checked aria-label="{{ translate('Select all') }}">
                                        <span class="aiz-square-check"></span>
                                    </label>
                                </th>
                                @include('backend.inc.sortable_th', $openSort + ['column' => 'order', 'label' => translate('Order')])
                                @include('backend.inc.sortable_th', $openSort + ['column' => 'amount', 'label' => translate('Credit')])
                                @include('backend.inc.sortable_th', $openSort + ['column' => 'due_on', 'label' => translate('Credit due'), 'breakpoints' => 'md'])
                                <th class="text-right pr-0">{{ translate('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="fs-14">
                            @foreach ($openUses as $use)
                                <tr>
                                    <td class="pl-0">
                                        <label class="aiz-checkbox mb-0 d-block pl-0" style="height: 18px; width: 18px;">
                                            <input type="checkbox" class="credit-use-check" name="credit_use_ids[]" value="{{ $use->id }}" data-amount="{{ $use->amount }}" checked>
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </td>
                                    <td>
                                        @if ($use->order)
                                            <a href="{{ route('purchase_history.details', encrypt($use->order->id)) }}">{{ $use->order->code }}</a>
                                        @else
                                            {{ $use->order_id ?: '-' }}
                                        @endif
                                    </td>
                                    <td class="fw-700">{{ single_price($use->amount) }}</td>
                                    <td class="text-secondary">{{ $use->dueLabel() }}</td>
                                    <td class="text-right pr-0">
                                        @if ($use->isOverdue())
                                            <span class="{{ $pill }} badge-danger" style="{{ $pillStyle }}">{{ translate('Overdue') }}</span>
                                        @elseif ($use->due_on)
                                            <span class="{{ $pill }} badge-warning" style="{{ $pillStyle }}">{{ translate('Due') }}</span>
                                        @else
                                            <span class="{{ $pill }} badge-info" style="{{ $pillStyle }}">{{ translate('Reserved') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($canPay)
                        <div class="row align-items-end border-top pt-3 pb-4">
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="fs-13 text-secondary">{{ translate('Selected') }}</div>
                                <div class="fs-20 fw-700 text-dark" id="credit-selected-total">{{ single_price($openUses->sum('amount')) }}</div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <label class="fs-13 text-secondary" for="credit-pay-amount">{{ translate('Amount to pay') }}</label>
                                <input type="number" lang="en" step="0.01" min="1" class="form-control rounded-0" id="credit-pay-amount" name="amount"
                                    value="{{ round((float) $openUses->sum('amount'), 2) }}" max="{{ round((float) $openUses->sum('amount'), 2) }}" required>
                                <small class="text-secondary fs-11">{{ translate('Pay part now: what you pay is available to use again, the rest stays due.') }}</small>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <label class="fs-13 text-secondary" for="credit-pay-method">{{ translate('Payment Method') }}</label>
                                <select class="form-control rounded-0" id="credit-pay-method" name="payment_option" required>
                                    @foreach ($paymentMethods as $paymentMethod)
                                        <option value="{{ $paymentMethod->name }}">{{ ucfirst(translate($paymentMethod->name)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 text-md-right">
                                <button type="submit" class="btn btn-primary rounded-0 fw-700 px-4" id="credit-pay-btn" style="background: #2b56a1 !important; border-color: #2b56a1;">
                                    {{ translate('Pay credit') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="text-secondary pb-4">{{ translate('No online payment method is turned on.') }}</div>
                    @endif
                </form>
            @endif
        </div>
    </div>

    <!-- Credit payment history -->
    <div class="card rounded-0 shadow-none border">
        <div class="card-header border-bottom-0 d-flex flex-wrap justify-content-between align-items-center">
            <h5 class="mb-0 fs-20 fw-700 text-dark">{{ translate('Credit payments') }}</h5>
            <div class="d-flex align-items-center mt-2 mt-md-0">
                @if ($filtersApplied)
                    <span class="badge badge-inline badge-info mr-2">{{ translate('Filters applied') }}</span>
                    <a href="{{ route('customer.credit.index') }}" class="btn btn-sm btn-link text-secondary mr-1">{{ translate('Reset') }}</a>
                @endif
                <button type="button" class="btn btn-sm btn-outline-dark rounded-0" data-toggle="modal" data-target="#creditFilterModal">
                    <i class="las la-filter"></i> {{ translate('Filter') }}
                </button>
            </div>
        </div>
        <div class="card-body py-0">
            @if (!$paymentsReady || !$payments || $payments->isEmpty())
                <div class="text-center text-secondary py-5">
                    {{ $filtersApplied ? translate('No credit payment matches these filters') : translate('No credit payment yet') }}
                </div>
            @else
                <table class="table aiz-table mb-4">
                    <thead class="text-gray fs-12">
                        <tr>
                            @include('backend.inc.sortable_th', $paymentSort + ['column' => 'created_at', 'label' => translate('Date'), 'thClass' => 'pl-0'])
                            @include('backend.inc.sortable_th', $paymentSort + ['column' => 'purpose', 'label' => translate('Type')])
                            @include('backend.inc.sortable_th', $paymentSort + ['column' => 'amount', 'label' => translate('Amount')])
                            @include('backend.inc.sortable_th', $paymentSort + ['column' => 'payment_method', 'label' => translate('Payment Method'), 'breakpoints' => 'md', 'thClass' => 'text-right pr-0'])
                        </tr>
                    </thead>
                    <tbody class="fs-14">
                        @foreach ($payments as $payment)
                            <tr>
                                <td class="pl-0 text-secondary">{{ optional($payment->created_at)->format('d-m-Y H:i') }}</td>
                                <td>
                                    <span class="{{ $pill }} {{ $payment->purpose === 'topup' ? 'badge-info' : 'badge-success' }}" style="{{ $pillStyle }}">{{ $payment->purposeLabel() }}</span>
                                    @if ($payment->recordedByAdmin() && $payment->reference() !== '')
                                        <div class="text-secondary fs-12 mt-1">{{ translate('Ref') }}: {{ $payment->reference() }}</div>
                                    @endif
                                </td>
                                <td class="fw-700 {{ (float) $payment->amount < 0 ? 'text-danger' : '' }}">{{ single_price($payment->amount) }}</td>
                                <td class="text-right pr-0">{{ $payment->methodLabel() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="aiz-pagination mb-4">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@section('modal')
    <!-- Top up modal -->
    @if ($canTopUp && $canPay && !empty($prepaidReady))
        <div class="modal fade" id="creditTopupModal" tabindex="-1" role="dialog" aria-labelledby="creditTopupModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="creditTopupModalLabel">{{ translate('Top up credit') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body gry-bg px-3 pt-3">
                        <p class="fs-13 text-secondary">{{ translate('Top up is money you pay now. At checkout it is used after your credit limit and is not billed again. It does not change the wallet balance and does not pay an open credit.') }}</p>
                        <form action="{{ route('customer.credit.topup') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="credit-topup-method">{{ translate('Payment Method') }} <span class="text-danger">*</span></label>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <select class="form-control rounded-0" id="credit-topup-method" name="payment_option" required>
                                        @foreach ($paymentMethods as $paymentMethod)
                                            <option value="{{ $paymentMethod->name }}">{{ ucfirst(translate($paymentMethod->name)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="credit-topup-amount">{{ translate('Amount') }} <span class="text-danger">*</span></label>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <input type="number" lang="en" step="0.01" min="1" class="form-control rounded-0" id="credit-topup-amount" name="amount"
                                        placeholder="{{ translate('Amount') }}" required>
                                </div>
                            </div>
                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-sm btn-primary rounded-0 transition-3d-hover mr-1">{{ translate('Confirm') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Filter modal -->
    <div class="modal fade" id="creditFilterModal" tabindex="-1" role="dialog" aria-labelledby="creditFilterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <form method="GET" action="{{ route('customer.credit.index') }}" class="w-100">
                <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                <input type="hidden" name="open_sort_by" value="{{ $openSortBy }}">
                <input type="hidden" name="open_sort_dir" value="{{ $openSortDir }}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="creditFilterModalLabel">{{ translate('Filter credit payments') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body gry-bg px-3 pt-3">
                        <div class="form-group">
                            <label for="credit-filter-purpose">{{ translate('Type') }}</label>
                            <select class="form-control rounded-0" id="credit-filter-purpose" name="purpose">
                                <option value="">{{ translate('All') }}</option>
                                <option value="topup" @selected($filters['purpose'] === 'topup')>{{ translate('Top up') }}</option>
                                <option value="settle" @selected($filters['purpose'] === 'settle')>{{ translate('Payment') }}</option>                            </select>
                        </div>
                        <div class="form-group">
                            <label for="credit-filter-method">{{ translate('Payment Method') }}</label>
                            <select class="form-control rounded-0" id="credit-filter-method" name="method">
                                <option value="">{{ translate('All') }}</option>
                                @foreach ($paymentMethodsUsed as $method)
                                    <option value="{{ $method }}" @selected($filters['method'] === $method)>{{ ucfirst(str_replace('_', ' ', translate($method))) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Date') }}</label>
                            <div class="row gutters-5">
                                <div class="col-6">
                                    <input type="date" class="form-control rounded-0" name="date_from" value="{{ $filters['date_from'] }}" aria-label="{{ translate('From') }}">
                                </div>
                                <div class="col-6">
                                    <input type="date" class="form-control rounded-0" name="date_to" value="{{ $filters['date_to'] }}" aria-label="{{ translate('To') }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group text-right mb-2">
                            <a href="{{ route('customer.credit.index') }}" class="btn btn-sm btn-light rounded-0 mr-1">{{ translate('Reset') }}</a>
                            <button type="submit" class="btn btn-sm btn-primary rounded-0">{{ translate('Apply Filters') }}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        (function () {
            var form = document.getElementById('credit-pay-form');
            if (!form) {
                return;
            }
            // The table plugin re-renders rows, so always look the boxes up again.
            // Match the site's money format by reading it off a server-formatted zero.
            var template = @json(single_price(0));
            var decimals = ((template.match(/[0-9][0-9.,]*/) || ['0'])[0].split('.')[1] || '').length;

            function money(sum) {
                var number = sum.toLocaleString('en-IN', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
                return template.replace(/[0-9][0-9.,]*/, number);
            }

            function boxes() {
                return Array.prototype.slice.call(form.querySelectorAll('.credit-use-check'));
            }

            function refresh() {
                var list = boxes();
                var sum = 0;
                var picked = 0;
                list.forEach(function (box) {
                    if (box.checked) {
                        sum += parseFloat(box.getAttribute('data-amount')) || 0;
                        picked++;
                    }
                });
                var total = document.getElementById('credit-selected-total');
                var button = document.getElementById('credit-pay-btn');
                var all = document.getElementById('credit-select-all');
                if (total) {
                    total.textContent = money(sum);
                }
                var amount = document.getElementById('credit-pay-amount');
                if (amount) {
                    var rounded = Math.round(sum * 100) / 100;
                    amount.max = rounded;
                    amount.min = Math.min(1, rounded);
                    amount.value = rounded;
                }
                if (button) {
                    button.disabled = picked === 0;
                }
                if (all) {
                    all.checked = list.length > 0 && picked === list.length;
                    all.indeterminate = picked > 0 && picked < list.length;
                }
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
        })();
    </script>
@endsection
