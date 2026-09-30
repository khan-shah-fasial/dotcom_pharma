@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Series Master') }}</h1></div>
        @if (empty($tableMissing))
            @can('add_customer')
                <div class="col-md-6 text-md-right">
                    <a href="{{ route('series.create') }}" class="btn btn-circle btn-info">{{ translate('Add New Series') }}</a>
                </div>
            @endcan
        @endif
    </div>
</div>

@if (!empty($tableMissing))
    <div class="alert alert-warning">
        {{ translate('Series Master is installed in the admin menu. Add the series_masters table from the SQL note, then this list, its filters, and its sorting will open.') }}
    </div>
@else
    @if (!empty($billColumnsMissing))
        <div class="alert alert-warning">
            {{ translate('Payment Type, Total No of Bills, From Bill No, and To Bill No need the extra series_masters columns. Run the SQL note, then these fields will save, filter, and sort.') }}
        </div>
    @endif
    @php
        $filtersApplied = collect($filters)->contains(fn ($value) => $value !== null && $value !== '');
    @endphp
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-2">
                <h5 class="mb-0 h6">{{ translate('Series') }}</h5>
                @if ($filtersApplied)
                    <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
                @endif
            </div>
            <div>
                <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#seriesFilterModal">{{ translate('Open Filters') }}</button>
                <a href="{{ route('series.index') }}" class="btn btn-danger mb-2">{{ translate('Reset') }}</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            @include('backend.inc.sortable_th', ['column' => 'id', 'label' => translate('ID'), 'routeName' => 'series.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'name', 'label' => translate('Name'), 'routeName' => 'series.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'code', 'label' => translate('Code'), 'routeName' => 'series.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @if (empty($billColumnsMissing))
                                @include('backend.inc.sortable_th', ['column' => 'payment_type', 'label' => translate('Payment Type'), 'routeName' => 'series.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                                @include('backend.inc.sortable_th', ['column' => 'total_bills', 'label' => translate('Total No of Bills'), 'routeName' => 'series.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                                @include('backend.inc.sortable_th', ['column' => 'from_bill_no', 'label' => translate('From Bill No.'), 'routeName' => 'series.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                                @include('backend.inc.sortable_th', ['column' => 'to_bill_no', 'label' => translate('To Bill No.'), 'routeName' => 'series.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @else
                                <th>{{ translate('Payment Type') }}</th>
                                <th>{{ translate('Total No of Bills') }}</th>
                                <th>{{ translate('From Bill No.') }}</th>
                                <th>{{ translate('To Bill No.') }}</th>
                            @endif
                            @include('backend.inc.sortable_th', ['column' => 'description', 'label' => translate('Description'), 'routeName' => 'series.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'status', 'label' => translate('Status'), 'routeName' => 'series.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'created_at', 'label' => translate('Created'), 'routeName' => 'series.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'updated_at', 'label' => translate('Updated'), 'routeName' => 'series.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            <th class="text-right">{{ translate('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($series as $key => $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->code ?: '-' }}</td>
                                <td>{{ $item->paymentTypeLabel() }}</td>
                                <td>{{ $item->total_bills === null || $item->total_bills === '' ? '-' : $item->total_bills }}</td>
                                <td>{{ $item->from_bill_no ?: '-' }}</td>
                                <td>{{ $item->to_bill_no ?: '-' }}</td>
                                <td>{{ $item->description ? \Illuminate\Support\Str::limit($item->description, 80) : '-' }}</td>
                                <td>
                                    <span class="badge badge-inline {{ $item->status ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $item->status ? translate('Active') : translate('Inactive') }}
                                    </span>
                                </td>
                                <td>{{ optional($item->created_at)->format('d-m-Y H:i') }}</td>
                                <td>{{ optional($item->updated_at)->format('d-m-Y H:i') }}</td>
                                <td class="text-right">
                                    @can('view_all_customers')
                                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('series.edit', $item) }}" title="{{ translate('Edit') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete_customer')
                                        <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('series.destroy', $item) }}" title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="text-center">{{ translate('No series found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination">{{ $series->links() }}</div>
        </div>
    </div>
@endif
@endsection

@section('modal')
    @include('modals.delete_modal')
    @if (empty($tableMissing))
        <form action="{{ route('series.index') }}" method="GET">
            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
            <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
            <div class="modal fade" id="seriesFilterModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ translate('Filter Series') }}</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>{{ translate('ID') }}</label>
                                <input type="number" min="1" class="form-control" name="id" value="{{ $filters['id'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Name') }}</label>
                                <input type="text" class="form-control" name="name" value="{{ $filters['name'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Code') }}</label>
                                <input type="text" class="form-control" name="code" value="{{ $filters['code'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Payment Type') }}</label>
                                <select class="form-control" name="payment_type">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="cash" @selected(($filters['payment_type'] ?? '') === 'cash')>{{ translate('Cash') }}</option>
                                    <option value="credit" @selected(($filters['payment_type'] ?? '') === 'credit')>{{ translate('Credit') }}</option>
                                    <option value="__not_in_list__" @selected(($filters['payment_type'] ?? '') === '__not_in_list__')>{{ translate('Not in List') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Total No of Bills') }}</label>
                                <input type="number" min="0" class="form-control" name="total_bills" value="{{ $filters['total_bills'] ?? '' }}">
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>{{ translate('From Bill No.') }}</label>
                                    <input type="text" class="form-control" name="from_bill_no" value="{{ $filters['from_bill_no'] ?? '' }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ translate('To Bill No.') }}</label>
                                    <input type="text" class="form-control" name="to_bill_no" value="{{ $filters['to_bill_no'] ?? '' }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Description') }}</label>
                                <input type="text" class="form-control" name="description" value="{{ $filters['description'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Status') }}</label>
                                <select class="form-control" name="status">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="1" @selected(($filters['status'] ?? '') === '1')>{{ translate('Active') }}</option>
                                    <option value="0" @selected(($filters['status'] ?? '') === '0')>{{ translate('Inactive') }}</option>
                                </select>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>{{ translate('Created from') }}</label>
                                    <input type="date" class="form-control" name="created_from" value="{{ $filters['created_from'] ?? '' }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ translate('Created to') }}</label>
                                    <input type="date" class="form-control" name="created_to" value="{{ $filters['created_to'] ?? '' }}">
                                </div>
                            </div>
                            <div class="form-row mb-0">
                                <div class="form-group col-md-6 mb-0">
                                    <label>{{ translate('Updated from') }}</label>
                                    <input type="date" class="form-control" name="updated_from" value="{{ $filters['updated_from'] ?? '' }}">
                                </div>
                                <div class="form-group col-md-6 mb-0">
                                    <label>{{ translate('Updated to') }}</label>
                                    <input type="date" class="form-control" name="updated_to" value="{{ $filters['updated_to'] ?? '' }}">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="{{ route('series.index') }}" class="btn btn-danger">{{ translate('Reset') }}</a>
                            <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endif
@endsection
