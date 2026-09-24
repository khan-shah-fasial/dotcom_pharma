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
                            <th>{{ translate('Sr.No') }}</th>
                            @include('backend.inc.sortable_th', ['column' => 'name', 'label' => translate('Name'), 'routeName' => 'series.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'code', 'label' => translate('Code'), 'routeName' => 'series.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'status', 'label' => translate('Status'), 'routeName' => 'series.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            <th class="text-right">{{ translate('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($series as $key => $item)
                            <tr>
                                <td>{{ $series->firstItem() + $key }}</td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->code ?: '-' }}</td>
                                <td>
                                    <span class="badge badge-inline {{ $item->status ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $item->status ? translate('Active') : translate('Inactive') }}
                                    </span>
                                </td>
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
                            <tr><td colspan="5" class="text-center">{{ translate('No series found') }}</td></tr>
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
                                <label>{{ translate('Name') }}</label>
                                <input type="text" class="form-control" name="name" value="{{ $filters['name'] ?? '' }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Code') }}</label>
                                <input type="text" class="form-control" name="code" value="{{ $filters['code'] ?? '' }}">
                            </div>
                            <div class="form-group mb-0">
                                <label>{{ translate('Status') }}</label>
                                <select class="form-control" name="status">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="1" @selected(($filters['status'] ?? '') === '1')>{{ translate('Active') }}</option>
                                    <option value="0" @selected(($filters['status'] ?? '') === '0')>{{ translate('Inactive') }}</option>
                                </select>
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
