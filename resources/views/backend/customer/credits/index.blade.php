@extends('backend.layouts.app')

@section('content')
    @php
        $accountSort = ['routeName' => 'credits.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir];
        $useSort = [
            'routeName' => 'credits.index',
            'sortBy' => $usesSortBy,
            'sortDir' => $usesSortDir,
            'sortParam' => 'uses_sort_by',
            'dirParam' => 'uses_sort_dir',
            'pageParam' => 'uses_page',
        ];
    @endphp

    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Credit') }}</h1>
            </div>
            <div class="col-md-6 text-md-right">
                @if ($filtersApplied)
                    <span class="badge badge-inline badge-info mr-2">{{ translate('Filters applied') }}</span>
                @endif
                @can('ban_customer')
                    <button type="button" class="btn btn-primary mr-2 js-credit-account" data-mode="create">
                        <i class="las la-plus"></i> {{ translate('Add credit account') }}
                    </button>
                @endcan
                <a href="{{ route('credits.payments') }}" class="btn btn-soft-primary mr-2">
                    <i class="las la-receipt"></i> {{ translate('Credit payments') }}
                </a>
                <button type="button" class="btn btn-outline-primary mr-2" data-toggle="modal" data-target="#creditFilterModal">
                    <i class="las la-filter"></i> {{ translate('Open Filters') }}
                </button>
                <a href="{{ route('credits.index') }}" class="btn btn-danger">{{ translate('Reset') }}</a>
            </div>
        </div>
    </div>

    @if (!$tableReady)
        <div class="alert alert-warning">
            {{ translate('Credit use table is not ready yet. Run sqlupdates/credit_uses.sql on the database before a customer can use credit on an order.') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Credit accounts') }}</h5>
        </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        @include('backend.inc.sortable_th', $accountSort + ['column' => 'id', 'label' => '#'])
                        @include('backend.inc.sortable_th', $accountSort + ['column' => 'name', 'label' => translate('Customer')])
                        @include('backend.inc.sortable_th', $accountSort + ['column' => 'credit_status', 'label' => translate('Credit Status'), 'breakpoints' => 'md'])
                        @include('backend.inc.sortable_th', $accountSort + ['column' => 'credit_days', 'label' => translate('Number of Days'), 'breakpoints' => 'lg'])
                        @include('backend.inc.sortable_th', $accountSort + ['column' => 'credit_limit', 'label' => translate('Credit Limit'), 'breakpoints' => 'md'])
                        @if ($prepaidReady)
                            @include('backend.inc.sortable_th', $accountSort + ['column' => 'credit_prepaid', 'label' => translate('Top up balance'), 'breakpoints' => 'lg'])
                        @endif
                        @include('backend.inc.sortable_th', $accountSort + ['column' => 'available_sum', 'label' => translate('Available Credit')])
                        @include('backend.inc.sortable_th', $accountSort + ['column' => 'outstanding_sum', 'label' => translate('Outstanding Credit')])
                        <th class="text-right" width="8%">{{ translate('Options') }}</th>
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
                            $isBlocked = in_array((int) $account->id, $overdueIds, true);
                        @endphp
                        <tr>
                            <td>{{ $key + 1 + ($accounts->currentPage() - 1) * $accounts->perPage() }}</td>
                            <td>
                                <div class="fw-600">{{ $company ?: $account->name }}</div>
                                <div class="text-muted fs-12">{{ $account->phone ?: $account->email }}</div>
                            </td>
                            <td>
                                @if ($account->creditIsActive())
                                    <span class="badge badge-inline badge-success">{{ translate('Active') }}</span>
                                @else
                                    <span class="badge badge-inline badge-secondary">{{ translate('Deactive') }}</span>
                                @endif
                                @if ($isBlocked)
                                    <span class="badge badge-inline badge-danger">{{ translate('Overdue') }}</span>
                                @endif
                            </td>
                            <td>{{ ($account->credit_days === null || $account->credit_days === '') ? '-' : (int) $account->credit_days }}</td>
                            <td class="text-nowrap">{{ ($account->credit_limit === null || $account->credit_limit === '') ? '-' : single_price($account->credit_limit) }}</td>
                            @if ($prepaidReady)
                                <td class="text-nowrap">{{ single_price(\App\Models\CreditUse::prepaidFor($account)) }}</td>
                            @endif
                            <td class="fw-600 text-nowrap">{{ single_price($availableCredit) }}</td>
                            <td class="fw-600 text-nowrap {{ $outstandingCredit > 0 ? 'text-danger' : '' }}">{{ single_price($outstandingCredit) }}</td>
                            <td class="text-right text-nowrap">
                                @can('ban_customer')
                                    <button type="button" class="btn btn-soft-success btn-icon btn-circle btn-sm js-credit-account"
                                        data-mode="edit"
                                        data-user-id="{{ $account->id }}"
                                        data-customer="{{ $company ?: $account->name }}"
                                        data-status="{{ $account->creditIsActive() ? 'active' : 'deactive' }}"
                                        data-limit="{{ (int) round((float) $account->credit_limit) }}"
                                        data-days="{{ (int) $account->credit_days }}"
                                        title="{{ translate('Edit credit') }}">
                                        <i class="las la-edit"></i>
                                    </button>
                                    @if ($account->creditIsActive() || (float) $account->credit_limit > 0 || (int) $account->credit_days > 0)
                                        <button type="button" class="btn btn-soft-danger btn-icon btn-circle btn-sm js-credit-remove"
                                            data-action="{{ route('customers.credits.remove', $account->id) }}"
                                            data-customer="{{ $company ?: $account->name }}"
                                            data-outstanding="{{ single_price($outstandingCredit) }}"
                                            data-owes="{{ $outstandingCredit > 0.009 ? 1 : 0 }}"
                                            title="{{ translate('Remove credit') }}">
                                            <i class="las la-trash"></i>
                                        </button>
                                    @endif
                                @endcan
                                <a class="btn btn-soft-info btn-icon btn-circle btn-sm"
                                    href="{{ route('credits.payments', ['search' => $account->phone ?: $account->email]) }}"
                                    title="{{ translate('Credit payments') }}">
                                    <i class="las la-receipt"></i>
                                </a>
                                <a class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                    href="{{ route('customers.view', encrypt($account->id)) }}"
                                    title="{{ translate('View') }}">
                                    <i class="las la-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $prepaidReady ? 9 : 8 }}" class="text-center text-muted">{{ translate('No credit found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            @include('backend.inc.sortable_th', $useSort + ['column' => 'id', 'label' => '#'])
                            @include('backend.inc.sortable_th', $useSort + ['column' => 'customer', 'label' => translate('Customer')])
                            @include('backend.inc.sortable_th', $useSort + ['column' => 'order', 'label' => translate('Order')])
                            @if ($kindReady)
                                @include('backend.inc.sortable_th', $useSort + ['column' => 'kind', 'label' => translate('Type'), 'breakpoints' => 'md'])
                            @endif
                            @include('backend.inc.sortable_th', $useSort + ['column' => 'amount', 'label' => translate('Credit')])
                            @include('backend.inc.sortable_th', $useSort + ['column' => 'credit_days', 'label' => translate('Number of Days'), 'breakpoints' => 'lg'])
                            @include('backend.inc.sortable_th', $useSort + ['column' => 'started_on', 'label' => translate('Credit start'), 'breakpoints' => 'lg'])
                            @include('backend.inc.sortable_th', $useSort + ['column' => 'due_on', 'label' => translate('Credit due'), 'breakpoints' => 'md'])
                            @include('backend.inc.sortable_th', $useSort + ['column' => 'status', 'label' => translate('Status')])
                            @include('backend.inc.sortable_th', $useSort + ['column' => 'created_at', 'label' => translate('Date'), 'breakpoints' => 'lg'])
                            <th class="text-right">{{ translate('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($uses as $key => $use)
                            @php
                                $company = optional(optional($use->user)->details)->company_name;
                                $isPrepaid = $kindReady && (string) $use->kind === \App\Models\CreditUse::KIND_PREPAID;
                                $statusClass = $use->isOverdue()
                                    ? 'badge-danger'
                                    : ($use->status === \App\Models\CreditUse::STATUS_RESERVED
                                        ? 'badge-warning'
                                        : ($use->status === \App\Models\CreditUse::STATUS_PAID ? 'badge-success' : 'badge-secondary'));
                            @endphp
                            <tr>
                                <td>{{ $key + 1 + ($uses->currentPage() - 1) * $uses->perPage() }}</td>
                                <td class="fw-600">{{ $company ?: optional($use->user)->name }}</td>
                                <td class="text-nowrap">{{ optional($use->order)->code ?: ($use->order_id ?: '-') }}</td>
                                @if ($kindReady)
                                    <td>{{ $isPrepaid ? translate('Top up') : translate('Credit limit') }}</td>
                                @endif
                                <td class="fw-600 text-nowrap">{{ single_price($use->amount) }}</td>
                                <td>{{ $isPrepaid ? '-' : (int) $use->credit_days }}</td>
                                <td class="text-nowrap">{{ $use->started_on ? $use->started_on->format('d-m-Y') : '—' }}</td>
                                <td class="text-nowrap">{{ $use->due_on ? $use->due_on->format('d-m-Y') : ($use->status === \App\Models\CreditUse::STATUS_RESERVED ? translate('Starts on dispatch') : '—') }}</td>
                                <td><span class="badge badge-inline {{ $statusClass }}">{{ $use->statusLabel() }}</span></td>
                                <td class="text-nowrap">{{ optional($use->created_at)->format('d-m-Y H:i') }}</td>
                                <td class="text-right">
                                    @can('ban_customer')
                                        @if ($use->status === \App\Models\CreditUse::STATUS_RESERVED)
                                            <button type="button" class="btn btn-soft-success btn-icon btn-circle btn-sm js-credit-markpaid"
                                                data-action="{{ route('credits.mark_paid', $use->id) }}"
                                                data-customer="{{ $company ?: optional($use->user)->name }}"
                                                data-order="{{ optional($use->order)->code ?: ($use->order_id ?: '-') }}"
                                                data-amount="{{ single_price($use->amount) }}"
                                                data-raw="{{ round((float) $use->amount, 2) }}"
                                                title="{{ translate('Record payment') }}">
                                                <i class="las la-check"></i>
                                            </button>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $kindReady ? 11 : 10 }}" class="text-center text-muted">{{ translate('No credit on an order yet') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="aiz-pagination mt-3">
                    {{ $uses->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@section('modal')
    <div class="modal fade" id="creditFilterModal" tabindex="-1" role="dialog" aria-labelledby="creditFilterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="GET" action="{{ route('credits.index') }}">
                {{-- Keep the current sort when filtering. --}}
                <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                <input type="hidden" name="uses_sort_by" value="{{ $usesSortBy }}">
                <input type="hidden" name="uses_sort_dir" value="{{ $usesSortDir }}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="creditFilterModalLabel">{{ translate('Filter Credit') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="credit_search">{{ translate('Customer') }}</label>
                            <input type="text" id="credit_search" name="search" class="form-control" value="{{ $filters['search'] }}"
                                placeholder="{{ translate('Name / company / phone / email') }}">
                            <small class="text-muted">{{ translate('Applies to both tables.') }}</small>
                        </div>

                        <h6 class="fs-14 fw-600 mt-4 mb-3 border-bottom pb-2">{{ translate('Credit accounts') }}</h6>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="credit_status">{{ translate('Credit Status') }}</label>
                                <select id="credit_status" name="credit_status" class="form-control aiz-selectpicker">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="active" @selected($filters['credit_status'] === 'active')>{{ translate('Active') }}</option>
                                    <option value="inactive" @selected($filters['credit_status'] === 'inactive')>{{ translate('Deactive') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="credit_balance">{{ translate('Balance') }}</label>
                                <select id="credit_balance" name="balance" class="form-control aiz-selectpicker">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="outstanding" @selected($filters['balance'] === 'outstanding')>{{ translate('Has outstanding credit') }}</option>
                                    <option value="overdue" @selected($filters['balance'] === 'overdue')>{{ translate('Overdue') }}</option>
                                    <option value="clear" @selected($filters['balance'] === 'clear')>{{ translate('Nothing outstanding') }}</option>
                                    @if ($prepaidReady)
                                        <option value="prepaid" @selected($filters['balance'] === 'prepaid')>{{ translate('Has top up balance') }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>

                        <h6 class="fs-14 fw-600 mt-2 mb-3 border-bottom pb-2">{{ translate('Credit on orders') }}</h6>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="use_status">{{ translate('Status') }}</label>
                                <select id="use_status" name="use_status" class="form-control aiz-selectpicker">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="reserved" @selected($filters['use_status'] === 'reserved')>{{ translate('Reserved') }}</option>
                                    <option value="overdue" @selected($filters['use_status'] === 'overdue')>{{ translate('Overdue') }}</option>
                                    <option value="paid" @selected($filters['use_status'] === 'paid')>{{ translate('Paid') }}</option>
                                    <option value="released" @selected($filters['use_status'] === 'released')>{{ translate('Released') }}</option>
                                </select>
                            </div>
                            @if ($kindReady)
                                <div class="col-md-6 form-group">
                                    <label for="use_kind">{{ translate('Type') }}</label>
                                    <select id="use_kind" name="kind" class="form-control aiz-selectpicker">
                                        <option value="">{{ translate('All') }}</option>
                                        <option value="facility" @selected($filters['kind'] === 'facility')>{{ translate('Credit limit') }}</option>
                                        <option value="prepaid" @selected($filters['kind'] === 'prepaid')>{{ translate('Top up') }}</option>
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-6 form-group">
                                <label for="order_code">{{ translate('Order') }}</label>
                                <input type="text" id="order_code" name="order_code" class="form-control" value="{{ $filters['order_code'] }}"
                                    placeholder="{{ translate('Order code') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ translate('Credit due') }}</label>
                                <div class="row gutters-5">
                                    <div class="col-6">
                                        <input type="date" name="due_from" class="form-control" value="{{ $filters['due_from'] }}" aria-label="{{ translate('Due from') }}">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" name="due_to" class="form-control" value="{{ $filters['due_to'] }}" aria-label="{{ translate('Due to') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('credits.index') }}" class="btn btn-light">{{ translate('Reset') }}</a>
                        <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @include('backend.customer.credits.partials.action_modals')
@endsection
