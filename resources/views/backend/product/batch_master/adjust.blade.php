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
@endphp

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Batch Adjustment') }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('batch_masters.index') }}" class="btn btn-soft-secondary btn-sm mr-1">{{ translate('Listing Page') }}</a>
            <a href="{{ route('batch_masters.adjust') }}" class="btn btn-soft-secondary btn-sm">{{ translate('Next Tab Batch Adjustment') }}</a>
        </div>
    </div>
</div>

@if (!$extended || !$adjustmentsReady)
    <div class="alert alert-warning">
        {{ translate('Batch Adjustment needs sqlupdates/batch_lot_master_extend.sql. Nothing will be written until then.') }}
    </div>
@endif

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Batch Adjustment') }}</h5>
        <p class="text-muted mb-0 fs-12">{{ translate('Saving here creates a new batch id and leaves the old row in place. Destroy keeps the row and stores the reason and biowaste certificate.') }}</p>
    </div>
    <div class="card-body">
        @foreach ($batches as $row)
            <form action="{{ route('batch_masters.adjust.store', $row->id) }}" method="POST" enctype="multipart/form-data" class="border rounded p-3 mb-3">
                @csrf
                <div class="row">
                    <div class="col-md-2 mb-2">
                        <label class="fs-12">{{ translate('SKU') }} / {{ translate('Batch ID') }}</label>
                        <div>{{ $cell(optional($row->stock)->sku) }}</div>
                        <div class="text-muted">{{ $row->id }}</div>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="fs-12">{{ translate('Product Name') }} / {{ translate('Full Variant') }}</label>
                        <div>{{ $cell(optional($row->product)->name) }}</div>
                        <div class="text-muted">{{ $cell(optional($row->stock)->variant) }}</div>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="fs-12">{{ translate('Batch Code') }}</label>
                        <input type="text" name="batch_code" class="form-control form-control-sm" value="{{ $row->batch_code }}">
                        <div class="text-muted fs-12">{{ $row->is_non_batch ? 'Y' : 'N' }}</div>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="fs-12">{{ translate('Mfg Date') }}</label>
                        <input type="month" name="manufacturing_date" class="form-control form-control-sm" value="{{ $row->monthValue('manufacturing_date') }}">
                        <label class="fs-12 mt-1">{{ translate('Expiry Date') }}</label>
                        <input type="month" name="expiry_date" class="form-control form-control-sm" value="{{ $row->monthValue('expiry_date') }}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="fs-12">{{ translate('Qty') }}</label>
                        <input type="number" lang="en" step="0.001" min="0" name="qty" class="form-control form-control-sm" value="{{ $row->qty }}">
                        <label class="fs-12 mt-1">{{ translate('Scheme') }}</label>
                        <input type="number" lang="en" step="0.001" min="0" name="scheme" class="form-control form-control-sm" value="{{ $row->scheme }}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="fs-12">{{ translate('P-Rate') }}</label>
                        <input type="number" lang="en" step="0.0001" min="0" name="purchase_rate" class="form-control form-control-sm" value="{{ $row->purchase_rate }}">
                        <label class="fs-12 mt-1">{{ translate('MRP') }}</label>
                        <input type="number" lang="en" step="0.01" min="0" name="mrp_price" class="form-control form-control-sm" value="{{ $row->mrp_price }}">
                    </div>
                </div>
                <div class="row fs-12 text-muted mb-2">
                    <div class="col-md-2">{{ translate('Tax') }} {{ $cell($row->tax_percent) }} / {{ translate('Amount') }} {{ $cell($row->lineAmount()) }}</div>
                    <div class="col-md-4">PTS {{ $cell($row->roleLineValue('pts')) }} / PTR {{ $cell($row->roleLineValue('ptr')) }} / PTD {{ $cell($row->roleLineValue('ptd')) }} / Govt. {{ $cell($row->roleLineValue('gov')) }} / Export {{ $cell($row->roleLineValue('expo')) }} / B2C {{ $cell($row->roleLineValue('customer')) }}</div>
                    <div class="col-md-3">{{ translate('Batchwise Discount') }} {{ $cell($row->batch_discount_percent) }} / {{ translate('Productwise Discount') }} {{ $cell($row->product_discount_percent) }} / {{ translate('Schemewise Discount') }} {{ $cell($row->scheme_discount_percent) }}</div>
                    <div class="col-md-3">{{ translate('Upload Date') }} {{ optional($row->created_at)->format('d-m-Y') }} / {{ optional($row->updated_at)->format('d-m-Y H:i') }} / {{ $row->status ? translate('On') : translate('Off') }}</div>
                </div>
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="fs-12">{{ translate('Reason') }}</label>
                        <input type="text" name="reason" class="form-control form-control-sm" maxlength="1000">
                    </div>
                    <div class="col-md-3">
                        <label class="fs-12">{{ translate('Biowaste certificate') }}</label>
                        <input type="file" name="biowaste" class="form-control-file">
                    </div>
                    <div class="col-md-5 text-md-right">
                        <button type="submit" name="action" value="convert" class="btn btn-primary btn-sm" {{ (!$extended || !$adjustmentsReady) ? 'disabled' : '' }}>{{ translate('Convert') }}</button>
                        <button type="submit" name="action" value="destroy" class="btn btn-danger btn-sm" {{ (!$extended || !$adjustmentsReady) ? 'disabled' : '' }}>{{ translate('Destroy') }}</button>
                    </div>
                </div>
            </form>
        @endforeach
        @if (!$batches || !$batches->count())
            <p class="text-center text-muted mb-0">{{ translate('No batch / lot master entries found.') }}</p>
        @endif
        @if ($batches)
            <div class="clearfix mt-3">
                <div class="pull-right">{{ $batches->links() }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
