@extends('backend.layouts.app')

@section('content')

@php
    $rate = function ($value) {
        return \App\Models\TaxMaster::formatRate($value);
    };
@endphp

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Tax Master') }}</h1>
        </div>
        @can('add_tax_master')
            <div class="col-md-6 text-md-right">
                <a href="{{ route('tax_masters.create') }}" class="btn btn-circle btn-info">
                    <span>{{ translate('Add New Tax') }}</span>
                </a>
            </div>
        @endcan
    </div>
</div>

@if (!$tableReady)
    <div class="alert alert-warning">
        {{ translate('Tax Master table is not ready yet.') }}
    </div>
@endif

@php
    $filtersApplied = collect($filters)->contains(function ($value) {
        return $value !== null && $value !== '';
    });
@endphp

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-2">
            <h5 class="mb-0 h6">{{ translate('Tax Master') }}</h5>
            @if ($filtersApplied)
                <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-center">
            <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#taxMasterFilterModal">
                {{ translate('Open Filters') }}
            </button>
            <a href="{{ route('tax_masters.index') }}" class="btn btn-danger mb-2">
                {{ translate('Reset') }}
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered aiz-table mb-0 tm-listing">
                <thead>
                    <tr>
                        <th>{{ translate('Tax ID') }}</th>
                        <th>{{ translate('Tax Type') }}</th>
                        <th>{{ translate('Tax Code') }}</th>
                        <th>{{ translate('Description') }}</th>
                        <th>{{ translate('Purchase Tax') }}<br>{{ translate('Tax % / CGST % / SGST % / IGST % / TOTAL GST%') }}</th>
                        <th>{{ translate('Same as purchase') }}<br>Y/N</th>
                        <th>{{ translate('Sale Tax') }}<br>{{ translate('Tax % / CGST % / SGST % / IGST % / TOTAL GST%') }}</th>
                        <th>{{ translate('Status') }}<br>{{ translate('Date Of Add / Edit') }}</th>
                        <th class="text-right">{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($tableReady && $taxes && $taxes->count())
                        @foreach ($taxes as $tax)
                            <tr>
                                <td>{{ $tax->id }}</td>
                                <td>{{ translate($tax->kindLabel()) }}</td>
                                <td>{{ $tax->tax_code }}</td>
                                <td>{{ $tax->description ?: '—' }}</td>
                                <td>
                                    {{ $rate($tax->purchase_tax) }} /
                                    {{ $rate($tax->purchase_cgst) }} /
                                    {{ $rate($tax->purchase_sgst) }} /
                                    {{ $rate($tax->purchase_igst) }} /
                                    {{ $rate($tax->purchaseTotal()) }}
                                </td>
                                <td>{{ $tax->sale_same_as_purchase ? 'Y' : 'N' }}</td>
                                <td>
                                    {{ $rate($tax->sale_tax) }} /
                                    {{ $rate($tax->sale_cgst) }} /
                                    {{ $rate($tax->sale_sgst) }} /
                                    {{ $rate($tax->sale_igst) }} /
                                    {{ $rate($tax->saleTotal()) }}
                                </td>
                                <td>
                                    <label class="aiz-switch aiz-switch-success mb-0">
                                        <input type="checkbox" onchange="updateTaxMasterStatus(this)" value="{{ $tax->id }}" {{ $tax->status ? 'checked' : '' }} @cannot('edit_tax_master') disabled @endcannot>
                                        <span class="slider round"></span>
                                    </label>
                                    <div class="text-muted fs-11">{{ optional($tax->updated_at)->format('d-m-Y H:i') }}</div>
                                </td>
                                <td class="text-right">
                                    @can('edit_tax_master')
                                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('tax_masters.edit', $tax->id) }}" title="{{ translate('Edit') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete_tax_master')
                                        <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('tax_masters.destroy', $tax->id) }}" title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="9" class="text-center text-muted">{{ translate('No tax master entries found.') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if ($tableReady && $taxes)
            <div class="clearfix mt-3">
                <div class="pull-right">
                    {{ $taxes->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<style>
    .tm-listing th {
        white-space: normal;
        vertical-align: bottom;
        min-width: 90px;
        font-size: 12px;
    }
    .tm-listing td {
        vertical-align: top;
        font-size: 13px;
    }
</style>

@endsection

@section('modal')
    @include('modals.delete_modal')

    <form action="{{ route('tax_masters.index') }}" method="GET" id="tax-master-filters">
        <div class="modal fade" id="taxMasterFilterModal" tabindex="-1" role="dialog" aria-labelledby="taxMasterFilterModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="taxMasterFilterModalLabel">{{ translate('Filter Tax Master') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="tm_search">{{ translate('Tax ID, Tax Code, Description') }}</label>
                            <input type="text" class="form-control" id="tm_search" name="search" value="{{ $filters['search'] }}" placeholder="{{ translate('Tax ID, Tax Code, Description') }}">
                        </div>
                        <div class="form-group">
                            <label for="tm_kind">{{ translate('Tax Type') }}</label>
                            <select name="kind" id="tm_kind" class="form-control">
                                <option value="">{{ translate('All Types') }}</option>
                                @foreach (\App\Models\TaxMaster::KINDS as $key => $label)
                                    <option value="{{ $key }}" @selected($filters['kind'] === $key)>{{ translate($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tm_status">{{ translate('Status') }}</label>
                            <select name="status" id="tm_status" class="form-control">
                                <option value="">{{ translate('All Status') }}</option>
                                <option value="1" @selected($filters['status'] === '1')>{{ translate('Active') }}</option>
                                <option value="0" @selected($filters['status'] === '0')>{{ translate('Deactivate') }}</option>
                            </select>
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
    function updateTaxMasterStatus(el) {
        $.post('{{ route('tax_masters.update_status') }}', {
            _token: '{{ csrf_token() }}',
            id: el.value,
            status: el.checked ? 1 : 0
        }, function() {
            AIZ.plugins.notify('success', '{{ translate('Status updated successfully') }}');
        });
    }
</script>
@endsection
