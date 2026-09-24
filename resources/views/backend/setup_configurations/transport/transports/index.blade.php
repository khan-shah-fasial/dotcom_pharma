@extends('backend.layouts.app')

@section('content')
@php
    $filters = $filters ?? [];
    $sortBy = $sortBy ?? '';
    $sortDir = $sortDir ?? 'asc';
    $filtersApplied = filled($sort_search) || collect($filters)->contains(function ($value) {
        return $value !== null && $value !== '';
    });
@endphp
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Transports') }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('transports.create') }}" class="btn btn-circle btn-info">
                <span>{{ translate('Add New Transport') }}</span>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-2">
            <h5 class="mb-md-0 h6">{{ translate('Transport List') }}</h5>
            @if ($filtersApplied)
                <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-center">
            <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#transportFilterModal">
                {{ translate('Open Filters') }}
            </button>
            <a href="{{ route('transports.index') }}" class="btn btn-danger mb-2">{{ translate('Reset') }}</a>
        </div>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    @include('backend.inc.sortable_th', ['column' => 'name', 'label' => translate('Name'), 'routeName' => 'transports.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    @include('backend.inc.sortable_th', ['column' => 'mode', 'label' => translate('Mode'), 'routeName' => 'transports.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    @include('backend.inc.sortable_th', ['column' => 'url', 'label' => translate('URL'), 'routeName' => 'transports.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    @include('backend.inc.sortable_th', ['column' => 'created_by', 'label' => translate('Created By'), 'routeName' => 'transports.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    @include('backend.inc.sortable_th', ['column' => 'status', 'label' => translate('Status'), 'routeName' => 'transports.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    <th class="text-right">{{ translate('Options') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transports as $key => $transport)
                    <tr>
                        <td>{{ $transports->firstItem() + $key }}</td>
                        <td>{{ $transport->name }}</td>
                        <td>{{ translate(ucfirst($transport->mode)) }}</td>
                        <td>
                            @if($transport->url)
                                <a href="{{ $transport->url }}" target="_blank" rel="noopener">{{ translate('Open') }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ optional($transport->creator)->name ?? '-' }}</td>
                        <td>
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input onchange="update_status(this)" value="{{ $transport->id }}" type="checkbox" @if($transport->status == 'active') checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </td>
                        <td class="text-right">
                            <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('transports.edit', $transport->id) }}" title="{{ translate('Edit') }}">
                                <i class="las la-edit"></i>
                            </a>
                            <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('transports.destroy', $transport->id) }}" title="{{ translate('Delete') }}">
                                <i class="las la-trash"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $transports->appends(request()->input())->links() }}
        </div>
    </div>
</div>
@endsection

@section('modal')
    @include('modals.delete_modal')
    <form action="{{ route('transports.index') }}" method="GET">
        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
        <div class="modal fade" id="transportFilterModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Filter Transports') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row gutters-5">
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Name') }}</label>
                                <input type="text" class="form-control" name="search" value="{{ $sort_search }}" placeholder="{{ translate('Type name') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Mode') }}</label>
                                <select name="mode" class="form-control">
                                    <option value="">{{ translate('All') }}</option>
                                    @foreach (['surface' => 'Surface', 'sea' => 'Sea', 'air' => 'Air'] as $value => $label)
                                        <option value="{{ $value }}" @selected(($filters['mode'] ?? '') === $value)>{{ translate($label) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('URL') }}</label>
                                <input type="text" class="form-control" name="url" value="{{ $filters['url'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Created By') }}</label>
                                <input type="text" class="form-control" name="created_by" value="{{ $filters['created_by'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Status') }}</label>
                                <select name="status" class="form-control">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ translate('Active') }}</option>
                                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>{{ translate('Inactive') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('transports.index') }}" class="btn btn-danger">{{ translate('Reset') }}</a>
                        <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script>
    function update_status(el) {
        if ('{{ env('DEMO_MODE') }}' == 'On') {
            AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
            return;
        }
        $.post('{{ route('transports.update_status') }}', {
            _token: '{{ csrf_token() }}',
            id: el.value,
            status: el.checked ? 1 : 0
        }, function(data) {
            AIZ.plugins.notify(data == 1 ? 'success' : 'danger', data == 1 ? '{{ translate('Status updated successfully') }}' : '{{ translate('Something went wrong') }}');
        });
    }
</script>
@endsection
