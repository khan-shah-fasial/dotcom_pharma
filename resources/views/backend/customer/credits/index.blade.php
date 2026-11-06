@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Credit') }}</h1>
            </div>
        </div>
    </div>

    @if (!$tableReady)
        <div class="alert alert-warning">
            {{ translate('Credit use table is not ready yet. Run sqlupdates/credit_uses.sql on the database before a customer can use credit on an order.') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header d-block d-md-flex justify-content-between align-items-center">
            <h5 class="mb-0 h6">{{ translate('Credit') }}</h5>
            <form class="mt-2 mt-md-0" method="GET" action="{{ route('credits.index') }}">
                <div class="d-flex flex-wrap" style="gap: 8px;">
                    <input type="text" class="form-control" name="search" value="{{ $search }}"
                        placeholder="{{ translate('Search name / company / phone') }}" style="min-width: 220px;">
                    <select class="form-control" name="use_status" style="min-width: 160px;">
                        <option value="">{{ translate('All') }}</option>
                        <option value="reserved" @selected($useStatus === 'reserved')>{{ translate('Reserved') }}</option>
                        <option value="released" @selected($useStatus === 'released')>{{ translate('Released') }}</option>
                        <option value="paid" @selected($useStatus === 'paid')>{{ translate('Paid') }}</option>
                    </select>
                    <button class="btn btn-primary" type="submit">{{ translate('Search') }}</button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Customer') }}</th>
                            <th>{{ translate('Credit Status') }}</th>
                            <th>{{ translate('Number of Days') }}</th>
                            <th>{{ translate('Credit Limit') }}</th>
                            <th>{{ translate('Available Credit') }}</th>
                            <th>{{ translate('Outstanding Credit') }}</th>
                            <th class="text-right">{{ translate('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accounts as $key => $account)
                            @php
                                $outstandingCredit = (float) ($outstanding[$account->id] ?? 0);
                                $availableCredit = $account->creditIsActive()
                                    ? max(0, (float) $account->credit_limit - $outstandingCredit)
                                    : 0;
                                $company = optional($account->details)->company_name;
                            @endphp
                            <tr>
                                <td>{{ $key + 1 + ($accounts->currentPage() - 1) * $accounts->perPage() }}</td>
                                <td>
                                    <div>{{ $company ?: $account->name }}</div>
                                    <div class="text-muted">{{ $account->phone ?: $account->email }}</div>
                                </td>
                                <td>{{ $account->creditIsActive() ? translate('Active') : translate('Deactive') }}</td>
                                <td>{{ ($account->credit_days === null || $account->credit_days === '') ? '-' : (int) $account->credit_days }}</td>
                                <td>{{ ($account->credit_limit === null || $account->credit_limit === '') ? '-' : single_price($account->credit_limit) }}</td>
                                <td>{{ single_price($availableCredit) }}</td>
                                <td>{{ single_price($outstandingCredit) }}</td>
                                <td class="text-right">
                                    <a class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                        href="{{ route('customers.view', encrypt($account->id)) }}"
                                        title="{{ translate('View') }}">
                                        <i class="las la-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">{{ translate('No credit found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-3">
                {{ $accounts->links() }}
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Credit on orders') }}</h5>
        </div>
        <div class="card-body">
            @if (!$tableReady)
                <div class="text-center text-muted">{{ translate('Credit on orders will show here after the credit table is ready.') }}</div>
            @else
                <div class="table-responsive">
                    <table class="table aiz-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ translate('Customer') }}</th>
                                <th>{{ translate('Order') }}</th>
                                <th>{{ translate('Credit') }}</th>
                                <th>{{ translate('Number of Days') }}</th>
                                <th>{{ translate('Credit start') }}</th>
                                <th>{{ translate('Credit due') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Date') }}</th>
                                <th class="text-right">{{ translate('Options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($uses as $key => $use)
                                @php
                                    $company = optional(optional($use->user)->details)->company_name;
                                @endphp
                                <tr>
                                    <td>{{ $key + 1 + ($uses->currentPage() - 1) * $uses->perPage() }}</td>
                                    <td>{{ $company ?: optional($use->user)->name }}</td>
                                    <td>{{ optional($use->order)->code ?: ($use->order_id ?: '-') }}</td>
                                    <td>{{ single_price($use->amount) }}</td>
                                    <td>{{ (int) $use->credit_days }}</td>
                                    <td>{{ $use->started_on ? $use->started_on->format('d-m-Y') : '—' }}</td>
                                    <td>{{ $use->due_on ? $use->due_on->format('d-m-Y') : ($use->status === \App\Models\CreditUse::STATUS_RESERVED ? translate('Starts on dispatch') : '—') }}</td>
                                    <td>{{ $use->statusLabel() }}</td>
                                    <td>{{ optional($use->created_at)->format('d-m-Y H:i') }}</td>
                                    <td class="text-right">
                                        @can('ban_customer')
                                            @if ($use->status === \App\Models\CreditUse::STATUS_RESERVED)
                                                <form method="POST" action="{{ route('credits.mark_paid', $use->id) }}" class="d-inline"
                                                    onsubmit="return confirm(@json(translate('Mark this credit as paid?')))">
                                                    @csrf
                                                    <button type="submit" class="btn btn-soft-success btn-sm">{{ translate('Mark paid') }}</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">{{ translate('No credit on an order yet') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="aiz-pagination mt-3">
                    {{ $uses->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
