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
            return \Carbon\Carbon::parse($date)->format('Y-m');
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

@include('backend.product.batch_master._tabs', ['activeTab' => 'adjust'])

@if (!$extended || !$adjustmentsReady)
    <div class="alert alert-warning">
        {{ translate('Batch Adjustment needs sqlupdates/batch_lot_master_extend.sql. Nothing will be written until then.') }}
    </div>
@endif

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-2">
            <h5 class="mb-0 h6">{{ translate('Batch Adjustment') }}</h5>
            <p class="text-muted mb-0 fs-12">{{ translate('Convert creates a new batch id and leaves the old row in place. Destroy keeps the row and stores the reason and biowaste certificate.') }}</p>
            @if ($filtersApplied)
                <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-center">
            <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#batchMasterFilterModal">
                {{ translate('Open Filters') }}
            </button>
            <a href="{{ route('batch_masters.adjust') }}" class="btn btn-danger mb-2">
                {{ translate('Reset') }}
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered aiz-table mb-0 bm-listing">
                <thead>
                    <tr>
                        @include('backend.inc.sortable_th', ['column' => 'sku', 'labelHtml' => e(translate('SKU')) . '<br>' . e(translate('Batch ID')), 'routeName' => 'batch_masters.adjust', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'product_name', 'labelHtml' => e(translate('Product Name')) . '<br>' . e(translate('Full Variant')), 'routeName' => 'batch_masters.adjust', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'batch_code', 'labelHtml' => e(translate('Batch Code')) . '<br>' . e(translate('Non-batch')), 'routeName' => 'batch_masters.adjust', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'manufacturing_date', 'labelHtml' => e(translate('Mfg Date')) . '<br>' . e(translate('Expiry Date')), 'routeName' => 'batch_masters.adjust', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'qty', 'label' => translate('Qty + Scheme'), 'routeName' => 'batch_masters.adjust', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'mrp_price', 'labelHtml' => e(translate('P-Rate')) . '<br>' . e(translate('MRP')), 'routeName' => 'batch_masters.adjust', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        <th>{{ translate('Tax') }}<br>{{ translate('Amount') }}</th>
                        @include('backend.inc.sortable_th', ['column' => 'role_price', 'label' => translate('PTS-PTR-PTD-GOVT.-EXPORT-CUSTOMER (B2C)'), 'routeName' => 'batch_masters.adjust', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        <th>{{ translate('Batchwise Discount') }}</th>
                        <th>{{ translate('Productwise Discount') }}</th>
                        <th>{{ translate('Schemewise Discount') }}</th>
                        <th>{{ translate('COA Image') }}</th>
                        @include('backend.inc.sortable_th', ['column' => 'created_at', 'labelHtml' => e(translate('Upload Date')) . '<br>' . e(translate('Date Of Add / Edit')) . '<br>' . e(translate('Status')), 'routeName' => 'batch_masters.adjust', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        <th class="text-right">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                @if ($batches && $batches->count())
                    @foreach ($batches as $row)
                        @php
                            $money = function ($value) {
                                return $value === null ? '—' : $value;
                            };
                            $formId = 'bm-adjust-' . $row->id;
                        @endphp
                        <tbody>
                            <tr>
                                <td>{{ $cell(optional($row->stock)->sku) }}<br><span class="text-muted">{{ $row->id }}</span></td>
                                <td>{{ $cell(optional($row->product)->name) }}<br><span class="text-muted">{{ $cell(optional($row->stock)->variant) }}</span></td>
                                <td>
                                    <input form="{{ $formId }}" type="text" name="batch_code" class="form-control form-control-sm" value="{{ $row->batch_code }}">
                                    <div class="text-muted fs-12">{{ $row->is_non_batch ? 'Y' : 'N' }}</div>
                                </td>
                                <td>
                                    <input form="{{ $formId }}" type="month" name="manufacturing_date" class="form-control form-control-sm mb-1" value="{{ $row->monthValue('manufacturing_date') }}">
                                    <input form="{{ $formId }}" type="month" name="expiry_date" class="form-control form-control-sm" value="{{ $row->monthValue('expiry_date') }}">
                                </td>
                                <td>
                                    <input form="{{ $formId }}" type="number" lang="en" step="0.001" min="0" name="qty" class="form-control form-control-sm mb-1" value="{{ $row->qty }}">
                                    <input form="{{ $formId }}" type="number" lang="en" step="0.001" min="0" name="scheme" class="form-control form-control-sm" value="{{ $row->scheme }}">
                                </td>
                                <td>
                                    <input form="{{ $formId }}" type="number" lang="en" step="0.0001" min="0" name="purchase_rate" class="form-control form-control-sm mb-1" value="{{ $row->purchase_rate }}">
                                    <input form="{{ $formId }}" type="number" lang="en" step="0.01" min="0" name="mrp_price" class="form-control form-control-sm" value="{{ $row->mrp_price }}">
                                </td>
                                <td>{{ $money($row->tax_percent) }}<br>{{ $money($row->lineAmount()) }}</td>
                                <td class="fs-12">
                                    @foreach (['pts' => 'PTS', 'ptr' => 'PTR', 'ptd' => 'PTD', 'gov' => 'Govt.', 'expo' => 'Export', 'customer' => 'B2C'] as $roleKey => $roleLabel)
                                        <div>{{ translate($roleLabel) }} {{ $money($row->rolePrice($roleKey)) }} / {{ translate('Value') }} {{ $money($row->roleLineValue($roleKey)) }}</div>
                                    @endforeach
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
                                    <div>{{ $row->status ? translate('On') : translate('Off') }}</div>
                                </td>
                                <td class="text-right">
                                    <button form="{{ $formId }}" type="submit" name="action" value="convert" class="btn btn-primary btn-sm mb-1" {{ (!$extended || !$adjustmentsReady) ? 'disabled' : '' }}>{{ translate('Convert') }}</button>
                                    <button form="{{ $formId }}" type="submit" name="action" value="destroy" class="btn btn-danger btn-sm" {{ (!$extended || !$adjustmentsReady) ? 'disabled' : '' }}>{{ translate('Destroy') }}</button>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="14" class="bg-light">
                                    <form id="{{ $formId }}" action="{{ route('batch_masters.adjust.store', $row->id) }}" method="POST" enctype="multipart/form-data" class="form-inline flex-wrap">
                                        @csrf
                                        <label class="mr-2 mb-2 fs-12">{{ translate('Reason') }}</label>
                                        <input type="text" name="reason" class="form-control form-control-sm mr-3 mb-2" style="min-width:240px" maxlength="1000">
                                        <label class="mr-2 mb-2 fs-12">{{ translate('Biowaste certificate') }}</label>
                                        <input type="file" name="biowaste" class="form-control-file d-inline-block mb-2" style="width:auto">
                                    </form>
                                </td>
                            </tr>
                        </tbody>
                    @endforeach
                @else
                    <tbody>
                        <tr>
                            <td colspan="14" class="text-center text-muted">{{ translate('No batch / lot master entries found.') }}</td>
                        </tr>
                    </tbody>
                @endif
            </table>
        </div>
        @if ($batches)
            <div class="clearfix mt-3">
                <div class="pull-right">{{ $batches->links() }}</div>
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
    <form action="{{ route('batch_masters.adjust') }}" method="GET">
        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
        <div class="modal fade" id="batchMasterFilterModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Filter Batch Adjustment') }}</h5>
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
