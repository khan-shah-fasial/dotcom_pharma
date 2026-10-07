@extends('backend.layouts.app')

@section('content')

@php
    $cell = function ($value) {
        return ($value === null || $value === '') ? '—' : $value;
    };
    $month = function ($date) {
        if (!$date) {
            return '—';
        }
        try {
            return \Carbon\Carbon::parse($date)->format('M-Y');
        } catch (\Throwable $e) {
            return $date;
        }
    };
    $sortBy = $sortBy ?? 'id';
    $sortDir = $sortDir ?? 'desc';
    $filtersApplied = collect($filters)->contains(function ($value) {
        return $value !== null && $value !== '';
    });
@endphp

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Batch / Lot Master') }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('batch_masters.index') }}" class="btn btn-soft-secondary btn-sm mr-1">{{ translate('Listing Page') }}</a>
            <a href="{{ route('batch_masters.adjust') }}" class="btn btn-soft-secondary btn-sm mr-1">{{ translate('Next Tab Batch Adjustment') }}</a>
            @can('add_batch_master')
                <a href="{{ route('batch_masters.create') }}" class="btn btn-circle btn-info">
                    <span>{{ translate('Add New Batch / Lot') }}</span>
                </a>
            @endcan
        </div>
    </div>
</div>

@if (!$tableReady)
    <div class="alert alert-warning">
        {{ translate('Batch / Lot Master table is not ready yet.') }}
    </div>
@endif

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-2">
            <h5 class="mb-0 h6">{{ translate('Batch / Lot Master') }}</h5>
            @if ($filtersApplied)
                <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-center">
            <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#batchMasterFilterModal">
                {{ translate('Open Filters') }}
            </button>
            <a href="{{ route('batch_masters.index') }}" class="btn btn-danger mb-2">
                {{ translate('Reset') }}
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered aiz-table mb-0 bm-listing">
                <thead>
                    <tr>
                        @include('backend.inc.sortable_th', ['column' => 'sku', 'labelHtml' => e(translate('SKU')) . '<br>' . e(translate('Batch ID')), 'routeName' => 'batch_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'product_name', 'labelHtml' => e(translate('Product Name')) . '<br>' . e(translate('Full Variant')), 'routeName' => 'batch_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'batch_code', 'labelHtml' => e(translate('Batch Code')) . '<br>' . e(translate('Non-batch')), 'routeName' => 'batch_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'manufacturing_date', 'labelHtml' => e(translate('Mfg Date')) . '<br>' . e(translate('Expiry Date')), 'routeName' => 'batch_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'qty', 'label' => translate('Qty + Scheme'), 'routeName' => 'batch_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'mrp_price', 'labelHtml' => e(translate('P-Rate')) . '<br>' . e(translate('MRP')), 'routeName' => 'batch_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        <th>{{ translate('Tax') }}<br>{{ translate('Amount') }}</th>
                        @include('backend.inc.sortable_th', ['column' => 'role_price', 'label' => translate('PTS-PTR-PTD-GOVT.-EXPORT-CUSTOMER (B2C)'), 'routeName' => 'batch_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        <th>{{ translate('Batchwise Discount') }}</th>
                        <th>{{ translate('Productwise Discount') }}</th>
                        <th>{{ translate('Schemewise Discount') }}</th>
                        <th>{{ translate('COA Image') }}</th>
                        @include('backend.inc.sortable_th', ['column' => 'created_at', 'labelHtml' => e(translate('Upload Date')) . '<br>' . e(translate('Date Of Add / Edit')) . '<br>' . e(translate('Status')), 'routeName' => 'batch_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        <th class="text-right">{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($tableReady && $batches && $batches->count())
                        @foreach ($batches as $row)
                            @php
                                $money = function ($value) {
                                    return $value === null ? '—' : $value;
                                };
                            @endphp
                            <tr>
                                <td>{{ $cell(optional($row->stock)->sku) }}<br><span class="text-muted">{{ $row->id }}</span></td>
                                <td>{{ $cell(optional($row->product)->name) }}<br><span class="text-muted">{{ $cell(optional($row->stock)->variant) }}</span></td>
                                <td>{{ $cell($row->batch_code) }}<br>{{ $row->is_non_batch ? 'Y' : 'N' }}</td>
                                <td>{{ $month($row->manufacturing_date) }}<br>{{ $month($row->expiry_date) }}</td>
                                <td>{{ $row->qty }}<br>{{ $money($row->scheme) }}</td>
                                <td>
                                    @if (!empty($showPurchaseRate))
                                        {{ $money($row->purchase_rate) }}
                                    @else
                                        ••••
                                    @endif
                                    <br>{{ $money($row->mrp_price) }}
                                </td>
                                <td>{{ $money($row->tax_percent) }}<br>{{ $money($row->lineAmount()) }}</td>
                                <td class="fs-12">
                                    {{ translate('PTS') }} {{ $money($row->roleLineValue('pts')) }} /
                                    {{ translate('PTR') }} {{ $money($row->roleLineValue('ptr')) }} /
                                    {{ translate('PTD') }} {{ $money($row->roleLineValue('ptd')) }}<br>
                                    {{ translate('Govt.') }} {{ $money($row->roleLineValue('gov')) }} /
                                    {{ translate('Export') }} {{ $money($row->roleLineValue('expo')) }} /
                                    {{ translate('B2C') }} {{ $money($row->roleLineValue('customer')) }}
                                </td>
                                <td>{{ $money($row->batch_discount_percent) }}</td>
                                <td>{{ $money($row->product_discount_percent) }}</td>
                                <td>{{ $money($row->scheme_discount_percent) }}</td>
                                <td>
                                    @if ($row->coa)
                                        <a href="{{ uploaded_asset($row->coa) }}" target="_blank">{{ translate('Zoom') }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    {{ optional($row->created_at)->format('d-m-Y') }}
                                    <div class="text-muted fs-11">{{ optional($row->updated_at)->format('d-m-Y H:i') }}</div>
                                    <label class="aiz-switch aiz-switch-success mb-0">
                                        <input type="checkbox" onchange="updateBatchMasterStatus(this)" value="{{ $row->id }}" {{ $row->status ? 'checked' : '' }} @cannot('edit_batch_master') disabled @endcannot>
                                        <span class="slider round"></span>
                                    </label>
                                </td>
                                <td class="text-right">
                                    @can('edit_batch_master')
                                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('batch_masters.edit', $row->id) }}" title="{{ translate('Edit') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete_batch_master')
                                        <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('batch_masters.destroy', $row->id) }}" title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="14" class="text-center text-muted">{{ translate('No batch / lot master entries found.') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if ($tableReady && $batches)
            <div class="clearfix mt-3">
                <div class="pull-right">
                    {{ $batches->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<style>
    .bm-listing th { white-space: normal; vertical-align: bottom; font-size: 12px; }
    .bm-listing th a { text-decoration: none; }
    .bm-listing td { vertical-align: top; font-size: 13px; }
</style>

@endsection

@section('modal')
    @include('modals.delete_modal')
    <form action="{{ route('batch_masters.index') }}" method="GET">
        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
        <div class="modal fade" id="batchMasterFilterModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Filter Batch / Lot Master') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row gutters-5">
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Search') }}</label>
                                <input type="text" class="form-control" name="search" value="{{ $filters['search'] }}" placeholder="{{ translate('SKU, Product, Variant, Batch Code, Batch ID') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('SKU') }}</label>
                                <input type="text" class="form-control" name="sku" value="{{ $filters['sku'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Product Name') }}</label>
                                <input type="text" class="form-control" name="product_name" value="{{ $filters['product_name'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Full Variant') }}</label>
                                <input type="text" class="form-control" name="variant" value="{{ $filters['variant'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Batch Code') }}</label>
                                <input type="text" class="form-control" name="batch_code" value="{{ $filters['batch_code'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Non-batch') }}</label>
                                <select name="is_non_batch" class="form-control">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="1" @selected($filters['is_non_batch'] === '1')>Y</option>
                                    <option value="0" @selected($filters['is_non_batch'] === '0')>N</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Status') }}</label>
                                <select name="status" class="form-control">
                                    <option value="">{{ translate('All Status') }}</option>
                                    <option value="1" @selected($filters['status'] === '1')>{{ translate('Active') }}</option>
                                    <option value="0" @selected($filters['status'] === '0')>{{ translate('Deactivate') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Mfg from') }}</label>
                                <input type="month" class="form-control" name="mfg_from" value="{{ $filters['mfg_from'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Mfg to') }}</label>
                                <input type="month" class="form-control" name="mfg_to" value="{{ $filters['mfg_to'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Expiry from') }}</label>
                                <input type="month" class="form-control" name="exp_from" value="{{ $filters['exp_from'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Expiry to') }}</label>
                                <input type="month" class="form-control" name="exp_to" value="{{ $filters['exp_to'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('MRP from') }}</label>
                                <input type="number" lang="en" step="0.01" min="0" class="form-control" name="mrp_from" value="{{ $filters['mrp_from'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('MRP to') }}</label>
                                <input type="number" lang="en" step="0.01" min="0" class="form-control" name="mrp_to" value="{{ $filters['mrp_to'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Qty from') }}</label>
                                <input type="number" lang="en" step="0.001" min="0" class="form-control" name="qty_from" value="{{ $filters['qty_from'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Qty to') }}</label>
                                <input type="number" lang="en" step="0.001" min="0" class="form-control" name="qty_to" value="{{ $filters['qty_to'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Upload Date from') }}</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Upload Date to') }}</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Date Of Add / Edit from') }}</label>
                                <input type="date" class="form-control" name="updated_from" value="{{ $filters['updated_from'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Date Of Add / Edit to') }}</label>
                                <input type="date" class="form-control" name="updated_to" value="{{ $filters['updated_to'] }}">
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
    function updateBatchMasterStatus(el) {
        $.post('{{ route('batch_masters.update_status') }}', {
            _token: '{{ csrf_token() }}',
            id: el.value,
            status: el.checked ? 1 : 0
        }, function() {
            AIZ.plugins.notify('success', '{{ translate('Status updated successfully') }}');
        });
    }
</script>
@endsection
