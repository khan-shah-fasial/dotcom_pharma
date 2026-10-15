@extends('backend.layouts.app')

@section('content')

@php
    $sortBy = $sortBy ?? 'id';
    $sortDir = $sortDir ?? 'desc';
    $filtersApplied = collect($filters)->contains(function ($value) {
        return $value !== null && $value !== '';
    });
    $taxableLabel = function ($value) {
        if ($value === null || $value === '') {
            return '—';
        }
        return (int) $value === 1 ? translate('Yes') : translate('No');
    };
@endphp

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('HSN Master') }}</h1>
        </div>
        @can('add_hsn_master')
            <div class="col-md-6 text-md-right">
                <a href="{{ route('hsn_masters.create') }}" class="btn btn-circle btn-info">
                    <span>{{ translate('Add New HSN') }}</span>
                </a>
            </div>
        @endcan
    </div>
</div>

@if (!$tableReady)
    <div class="alert alert-warning">
        {{ translate('HSN Master table is not ready yet. Run sqlupdates/hsn_masters.sql, then sqlupdates/hsn_masters_seed.sql.') }}
    </div>
@endif

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-2">
            <h5 class="mb-0 h6">{{ translate('HSN Master') }}</h5>
            @if ($filtersApplied)
                <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-center">
            <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#hsnMasterFilterModal">
                {{ translate('Open Filters') }}
            </button>
            <a href="{{ route('hsn_masters.index') }}" class="btn btn-danger mb-2">
                {{ translate('Reset') }}
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered aiz-table mb-0">
                <thead>
                    <tr>
                        @include('backend.inc.sortable_th', ['column' => 'id', 'label' => translate('ID'), 'routeName' => 'hsn_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'hsn_code', 'label' => translate('HSN Code'), 'routeName' => 'hsn_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'description', 'label' => translate('Description'), 'routeName' => 'hsn_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'taxable', 'label' => translate('Taxable'), 'routeName' => 'hsn_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'tax_type', 'label' => translate('Tax Type'), 'routeName' => 'hsn_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'tax_percent', 'label' => translate('Tax %'), 'routeName' => 'hsn_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'status', 'label' => translate('Status'), 'routeName' => 'hsn_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        <th class="text-right">{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($tableReady && $rows && $rows->count())
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td><strong>{{ $row->hsn_code }}</strong></td>
                                <td>{{ $row->description ?: '—' }}</td>
                                <td>{{ $taxableLabel($row->taxable) }}</td>
                                <td>
                                    @if ($row->kindLabel() !== '')
                                        <span class="badge badge-inline badge-soft-secondary">{{ translate($row->kindLabel()) }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $row->tax_percent === null ? '—' : \App\Models\HsnMaster::displayRate($row->tax_percent) }}</td>
                                <td>
                                    <label class="aiz-switch aiz-switch-success mb-0">
                                        <input type="checkbox" onchange="updateHsnMasterStatus(this)" value="{{ $row->id }}" {{ $row->status ? 'checked' : '' }} @cannot('edit_hsn_master') disabled @endcannot>
                                        <span class="slider round"></span>
                                    </label>
                                </td>
                                <td class="text-right">
                                    @can('edit_hsn_master')
                                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('hsn_masters.edit', $row->id) }}" title="{{ translate('Edit') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete_hsn_master')
                                        <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('hsn_masters.destroy', $row->id) }}" title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="8" class="text-center text-muted">{{ translate('No HSN codes found.') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if ($tableReady && $rows)
            <div class="clearfix mt-3">
                <div class="pull-right">
                    {{ $rows->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

@endsection

@section('modal')
    @include('modals.delete_modal')

    <form action="{{ route('hsn_masters.index') }}" method="GET" id="hsn-master-filters">
        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
        <div class="modal fade" id="hsnMasterFilterModal" tabindex="-1" role="dialog" aria-labelledby="hsnMasterFilterModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="hsnMasterFilterModalLabel">{{ translate('Filter HSN Master') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row gutters-5">
                            <div class="col-md-6 mb-3">
                                <label for="hm_search">{{ translate('Search') }}</label>
                                <input type="text" class="form-control" id="hm_search" name="search" value="{{ $filters['search'] }}" placeholder="{{ translate('HSN Code or Description') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="hm_hsn">{{ translate('HSN Code') }}</label>
                                <input type="text" class="form-control" id="hm_hsn" name="hsn_code" value="{{ $filters['hsn_code'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="hm_description">{{ translate('Description') }}</label>
                                <input type="text" class="form-control" id="hm_description" name="description" value="{{ $filters['description'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="hm_taxable">{{ translate('Taxable') }}</label>
                                <select name="taxable" id="hm_taxable" class="form-control">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="1" @selected($filters['taxable'] === '1')>{{ translate('Yes') }}</option>
                                    <option value="0" @selected($filters['taxable'] === '0')>{{ translate('No') }}</option>
                                    <option value="unset" @selected($filters['taxable'] === 'unset')>{{ translate('Not set') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="hm_tax_type">{{ translate('Tax Type') }}</label>
                                <select name="tax_type" id="hm_tax_type" class="form-control">
                                    <option value="">{{ translate('All Types') }}</option>
                                    @foreach (\App\Models\TaxMaster::KINDS as $key => $label)
                                        <option value="{{ $key }}" @selected($filters['tax_type'] === $key)>{{ translate($label) }}</option>
                                    @endforeach
                                    <option value="unset" @selected($filters['tax_type'] === 'unset')>{{ translate('Not set') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="hm_percent_from">{{ translate('Tax % from') }}</label>
                                <input type="number" lang="en" step="0.0001" min="0" class="form-control" id="hm_percent_from" name="tax_percent_from" value="{{ $filters['tax_percent_from'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="hm_percent_to">{{ translate('Tax % to') }}</label>
                                <input type="number" lang="en" step="0.0001" min="0" class="form-control" id="hm_percent_to" name="tax_percent_to" value="{{ $filters['tax_percent_to'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="hm_status">{{ translate('Status') }}</label>
                                <select name="status" id="hm_status" class="form-control">
                                    <option value="">{{ translate('All Status') }}</option>
                                    <option value="1" @selected($filters['status'] === '1')>{{ translate('Active') }}</option>
                                    <option value="0" @selected($filters['status'] === '0')>{{ translate('Deactivate') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script type="text/javascript">
    function updateHsnMasterStatus(el) {
        $.post('{{ route('hsn_masters.update_status') }}', {
            _token: '{{ csrf_token() }}',
            id: el.value,
            status: el.checked ? 1 : 0
        }, function() {
            AIZ.plugins.notify('success', '{{ translate('Status updated successfully') }}');
        });
    }
</script>
@endsection
