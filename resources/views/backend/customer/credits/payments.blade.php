@extends('backend.layouts.app')

@section('content')
    @php
        $paymentSort = ['routeName' => 'credits.payments', 'sortBy' => $sortBy, 'sortDir' => $sortDir];
    @endphp

    @include('backend.customer.credits.partials.admin_styles')

    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: 10px;">
            <div>
                <a href="{{ route('credits.index') }}" class="fs-13 text-muted"><i class="las la-arrow-left"></i> {{ translate('Credit') }}</a>
                <h1 class="h3 mb-0">{{ translate('Credit payments') }}</h1>
            </div>
            <div class="crd-toolbar">
                <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#creditPaymentFilterModal">
                    <i class="las la-filter"></i> <span>{{ translate('Filter') }}</span>
                </button>
                @if ($filtersApplied)
                    <a href="{{ route('credits.payments') }}" class="btn btn-soft-danger">
                        <i class="las la-times"></i> <span>{{ translate('Clear') }}</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if (!$tableReady)
        <div class="alert alert-warning">
            {{ translate('Credit payments are not ready yet. Run sqlupdates/credit_payments.sql on the database.') }}
        </div>
    @else
        <div class="crd-kpis mb-3">
            <div class="crd-kpi" style="border-top-color: #1b7a3d;">
                <span class="crd-kpi-label">{{ translate('Credit repaid') }}</span>
                <span class="crd-kpi-value" style="color: #1b7a3d;">{{ single_price($totals['settle']) }}</span>
                <span class="crd-kpi-sub">{{ $filtersApplied ? translate('Completed, matching filters') : translate('Completed, all time') }}</span>
            </div>
            <div class="crd-kpi">
                <span class="crd-kpi-label">{{ translate('Top ups received') }}</span>
                <span class="crd-kpi-value">{{ single_price($totals['topup']) }}</span>
                <span class="crd-kpi-sub">{{ $filtersApplied ? translate('Completed, matching filters') : translate('Completed, all time') }}</span>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            @include('backend.inc.sortable_th', $paymentSort + ['column' => 'id', 'label' => '#', 'breakpoints' => 'md'])
                            @include('backend.inc.sortable_th', $paymentSort + ['column' => 'created_at', 'label' => translate('Date'), 'breakpoints' => 'sm'])
                            @include('backend.inc.sortable_th', $paymentSort + ['column' => 'customer', 'label' => translate('Customer')])
                            @include('backend.inc.sortable_th', $paymentSort + ['column' => 'purpose', 'label' => translate('Type'), 'breakpoints' => 'sm'])
                            @include('backend.inc.sortable_th', $paymentSort + ['column' => 'amount', 'label' => translate('Amount')])
                            @include('backend.inc.sortable_th', $paymentSort + ['column' => 'payment_method', 'label' => translate('Payment Method'), 'breakpoints' => 'md'])
                            <th data-breakpoints="lg">{{ translate('Reference') }}</th>
                            <th data-breakpoints="lg">{{ translate('Recorded by') }}</th>
                            @include('backend.inc.sortable_th', $paymentSort + ['column' => 'status', 'label' => translate('Status')])
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            @php
                                $company = optional(optional($payment->user)->details)->company_name;
                                $purposeClass = $payment->purpose === \App\Models\CreditPayment::PURPOSE_TOPUP ? 'badge-info' : 'badge-success';
                                $statusClass = $payment->status === \App\Models\CreditPayment::STATUS_COMPLETED
                                    ? 'badge-success'
                                    : ($payment->status === \App\Models\CreditPayment::STATUS_FAILED ? 'badge-danger' : 'badge-warning');
                            @endphp
                            <tr>
                                <td>{{ $payment->id }}</td>
                                <td class="text-nowrap">{{ optional($payment->created_at)->format('d-m-Y H:i') }}</td>
                                <td>
                                    @if ($payment->user)
                                        <a href="{{ route('customers.view', encrypt($payment->user->id)) }}" class="fw-600 text-reset">{{ $company ?: $payment->user->name }}</a>
                                        <div class="text-muted fs-12">{{ $payment->user->phone ?: $payment->user->email }}</div>
                                        <div class="text-muted fs-12 d-md-none">
                                            <span class="badge badge-inline {{ $purposeClass }}">{{ $payment->purposeLabel() }}</span>
                                            <span class="text-nowrap">{{ optional($payment->created_at)->format('d M Y, H:i') }}</span>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td><span class="badge badge-inline {{ $purposeClass }}">{{ $payment->purposeLabel() }}</span></td>
                                <td class="fw-600 text-nowrap {{ (float) $payment->amount < 0 ? 'text-danger' : '' }}">{{ single_price($payment->amount) }}</td>
                                <td>{{ $payment->methodLabel() }}</td>
                                <td>
                                    {{ $payment->reference() ?: '-' }}
                                    @if ($payment->note() !== '')
                                        <div class="text-muted fs-12">{{ $payment->note() }}</div>
                                    @endif
                                </td>
                                <td>{{ $payment->recordedByAdmin() ? ($payment->recordedByName() ?: translate('Admin')) : translate('Customer (online)') }}</td>
                                <td><span class="badge badge-inline {{ $statusClass }}">{{ $payment->statusLabel() }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">{{ translate('No credit payments found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="aiz-pagination mt-3">
                    {{ $payments->links() }}
                </div>
            </div>
        </div>
    @endif
@endsection

@section('modal')
    <div class="modal fade" id="creditPaymentFilterModal" tabindex="-1" role="dialog" aria-labelledby="creditPaymentFilterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="GET" action="{{ route('credits.payments') }}">
                <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="creditPaymentFilterModalLabel">{{ translate('Filter credit payments') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="payment_search">{{ translate('Customer or reference') }}</label>
                            <input type="text" id="payment_search" name="search" class="form-control" value="{{ $filters['search'] }}"
                                placeholder="{{ translate('Name / company / phone / email / reference') }}">
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="payment_purpose">{{ translate('Type') }}</label>
                                <select id="payment_purpose" name="purpose" class="form-control aiz-selectpicker">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="topup" @selected($filters['purpose'] === 'topup')>{{ translate('Top up') }}</option>
                                    <option value="settle" @selected($filters['purpose'] === 'settle')>{{ translate('Payment') }}</option>                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="payment_status">{{ translate('Status') }}</label>
                                <select id="payment_status" name="status" class="form-control aiz-selectpicker">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="completed" @selected($filters['status'] === 'completed')>{{ translate('Completed') }}</option>
                                    <option value="pending" @selected($filters['status'] === 'pending')>{{ translate('Pending') }}</option>
                                    <option value="failed" @selected($filters['status'] === 'failed')>{{ translate('Failed') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="payment_method_filter">{{ translate('Payment Method') }}</label>
                                <select id="payment_method_filter" name="method" class="form-control aiz-selectpicker">
                                    <option value="">{{ translate('All') }}</option>
                                    @foreach ($methods as $method)
                                        <option value="{{ $method }}" @selected($filters['method'] === $method)>
                                            {{ (new \App\Models\CreditPayment(['payment_method' => $method]))->methodLabel() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="payment_source">{{ translate('Recorded by') }}</label>
                                <select id="payment_source" name="source" class="form-control aiz-selectpicker">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="online" @selected($filters['source'] === 'online')>{{ translate('Customer (online)') }}</option>
                                    <option value="admin" @selected($filters['source'] === 'admin')>{{ translate('Admin') }}</option>
                                </select>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>{{ translate('Date') }}</label>
                                <div class="row gutters-5">
                                    <div class="col-6">
                                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}" aria-label="{{ translate('From') }}">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}" aria-label="{{ translate('To') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('credits.payments') }}" class="btn btn-light">{{ translate('Reset') }}</a>
                        <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
