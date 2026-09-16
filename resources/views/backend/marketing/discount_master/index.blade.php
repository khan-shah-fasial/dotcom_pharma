@extends('backend.layouts.app')

@section('content')

@php
    $cell = function ($value) {
        if ($value === null || $value === '') {
            return '—';
        }
        return $value;
    };
@endphp

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Discount Master') }}</h1>
        </div>
        @can('add_discount_master')
            <div class="col-md-6 text-md-right">
                <a href="{{ route('discount_masters.create') }}" class="btn btn-circle btn-info">
                    <span>{{ translate('Add New Discount') }}</span>
                </a>
            </div>
        @endcan
    </div>
</div>

@if (!$tableReady)
    <div class="alert alert-warning">
        {{ translate('Discount Master table is not ready yet. Run the discount_masters migration, then reload this page.') }}
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
            <h5 class="mb-0 h6">{{ translate('Discount Master') }}</h5>
            @if ($filtersApplied)
                <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-center">
            <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#discountMasterFilterModal">
                {{ translate('Open Filters') }}
            </button>
            <a href="{{ route('discount_masters.index') }}" class="btn btn-danger mb-2">
                {{ translate('Reset') }}
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered aiz-table mb-0 dm-listing">
                <thead>
                    <tr>
                        <th>{{ translate('Discount Applied On') }}</th>
                        <th>{{ translate('SKU Product') }}</th>
                        <th>{{ translate('Full Variant') }}</th>
                        <th>{{ translate('Same as') }}<br>{{ translate('Batch / Lot.No') }}<br>{{ translate('Stock Available') }}</th>
                        <th>{{ translate('Mfg.Date') }}<br>{{ translate('Expiry Date') }}<br>{{ translate('COA Document') }}</th>
                        <th>{{ translate('Stock Value As Per') }}<br>{{ translate('PTS / PTR / PTD / Govt. / Export / B2C / M.R.P') }}</th>
                        <th>{{ translate('Rolewise Price') }}</th>
                        <th>{{ translate('Qty Slab') }}<br>{{ translate('Rate') }}<br>{{ translate('Amount') }}<br>{{ translate('Effective Rate') }}</th>
                        <th>{{ translate('Discount Type') }}<br>{{ translate('Discount ID') }}</th>
                        <th>{{ translate('Batchwise') }}<br>{{ translate('Type / Amount Or % / % Or Amount') }}</th>
                        <th>{{ translate('Productwise') }}<br>{{ translate('Type / Amount Or % / % Or Amount') }}</th>
                        <th>{{ translate('Pointwise Earn') }}<br>{{ translate('Earn / Amount Or % / % Or Amount') }}</th>
                        <th>{{ translate('Amount wise') }}<br>{{ translate('Invoice Amount / Type / Amount Or %') }}</th>
                        <th>{{ translate('Schemewise') }}<br>{{ translate('Free Qty / Scheme % / Scheme Value') }}</th>
                        <th>{{ translate('From Date') }}<br>{{ translate('To Date') }}<br>{{ translate('Offer Active') }}<br>{{ translate('Date Of Add / Edit') }}</th>
                        <th class="text-right">{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($tableReady && $discounts && $discounts->count())
                        @foreach ($discounts as $discount)
                            @php
                                $isStock = in_array($discount->applied_on, ['sku', 'full_variant', 'batch'], true);
                                $isBatch = $discount->applied_on === 'batch';
                                $sourceBatch = $discount->sourceBatch();
                                $rolePrices = $isStock ? $discount->liveRolePrices() : [];
                                $coa = $discount->sourceCoa();
                                $coaUrl = $coa && is_numeric($coa) ? uploaded_asset((int) $coa) : null;
                            @endphp
                            <tr>
                                <td>{{ translate($discount->appliedOnLabel()) }}</td>
                                <td>
                                    @if ($discount->applied_on === 'sku')
                                        {{ $cell(optional($discount->stock)->sku) }}
                                        <div class="text-muted fs-11">{{ optional($discount->product)->name }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($discount->applied_on === 'full_variant')
                                        {{ $cell(optional($discount->stock)->variant) }}
                                        <div class="text-muted fs-11">{{ optional($discount->product)->name }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($isBatch)
                                        {{ $cell(optional($sourceBatch)->batch) }}
                                        <div class="text-muted fs-11">{{ translate('Stock') }}: {{ $cell($discount->sourceQty()) }}</div>
                                    @elseif ($isStock)
                                        <div class="text-muted fs-11">{{ translate('Stock') }}: {{ $cell($discount->sourceQty()) }}</div>
                                    @elseif ($discount->applied_on === 'category')
                                        {{ optional($discount->category)->name ?: '—' }}
                                    @elseif ($discount->applied_on === 'group')
                                        {{ optional($discount->group)->name ?: '—' }}
                                    @elseif ($discount->applied_on === 'customer')
                                        {{ $discount->customerDisplayName() }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($isStock)
                                        {{ $cell(optional($sourceBatch)->manufacturing_date) }}<br>
                                        {{ $cell(optional($sourceBatch)->product_exp_date ?? optional($discount->stock)->product_exp_date) }}<br>
                                        @if ($coaUrl)
                                            <a href="{{ $coaUrl }}" target="_blank">{{ translate('COA') }}</a>
                                        @else
                                            —
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="fs-11">
                                    @if ($isStock)
                                        @foreach (\App\Models\DiscountMaster::ROLE_KEYS as $key => $label)
                                            <div class="{{ $discount->role_key === $key ? 'font-weight-bold text-primary' : '' }}">
                                                {{ translate($label) }}: {{ $rolePrices[$key] === null ? '—' : $rolePrices[$key] }}
                                            </div>
                                        @endforeach
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $isStock ? $discount->roleKeyLabel() : '—' }}</td>
                                <td>
                                    {{ $cell($discount->qty_slab_from) }}{{ $discount->qty_slab_to ? ' – ' . $discount->qty_slab_to : '' }}<br>
                                    {{ $cell($discount->rate) }}<br>
                                    {{ $cell($discount->amount) }}<br>
                                    {{ $cell($discount->effective_rate) }}
                                </td>
                                <td>
                                    {{ translate($discount->discountTypeLabel()) }}<br>
                                    <strong>{{ $discount->discount_code }}</strong>
                                </td>
                                <td>
                                    @if ($discount->discount_type === 'batchwise')
                                        {{ $discount->value_type }} / {{ $cell($discount->value_amount) }} / {{ $cell($discount->value_percent) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($discount->discount_type === 'productwise')
                                        {{ $discount->value_type }} / {{ $cell($discount->value_amount) }} / {{ $cell($discount->value_percent) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($discount->discount_type === 'pointwise')
                                        {{ $cell($discount->earn) }} / {{ $cell($discount->value_amount) }} / {{ $cell($discount->value_percent) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($discount->discount_type === 'amount_wise')
                                        {{ $cell($discount->invoice_amount) }} / {{ $discount->value_type }} / {{ $cell($discount->value_amount) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($discount->discount_type === 'schemewise')
                                        {{ $cell($discount->scheme_free_qty) }} / {{ $cell($discount->scheme_percent) }} / {{ $cell($discount->scheme_value) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    {{ optional($discount->from_date)->format('d-m-Y') }}<br>
                                    {{ optional($discount->to_date)->format('d-m-Y') ?: '—' }}<br>
                                    <label class="aiz-switch aiz-switch-success mb-0">
                                        <input type="checkbox" onchange="updateDiscountMasterStatus(this)" value="{{ $discount->id }}" {{ $discount->status ? 'checked' : '' }} @cannot('edit_discount_master') disabled @endcannot>
                                        <span class="slider round"></span>
                                    </label>
                                    <div class="text-muted fs-11">{{ $discount->updated_at }}</div>
                                </td>
                                <td class="text-right">
                                    @can('edit_discount_master')
                                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('discount_masters.edit', $discount->id) }}" title="{{ translate('Edit') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete_discount_master')
                                        <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('discount_masters.destroy', $discount->id) }}" title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="16" class="text-center text-muted">{{ translate('No discount master entries found.') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @if ($tableReady && $discounts)
            <div class="clearfix mt-3">
                <div class="pull-right">
                    {{ $discounts->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<style>
    .dm-listing th {
        white-space: normal;
        vertical-align: bottom;
        min-width: 110px;
        font-size: 12px;
    }
    .dm-listing td {
        vertical-align: top;
        font-size: 13px;
    }
</style>

@endsection

@section('modal')
    @include('modals.delete_modal')

    <form action="{{ route('discount_masters.index') }}" method="GET" id="discount-master-filters">
        <div class="modal fade" id="discountMasterFilterModal" tabindex="-1" role="dialog" aria-labelledby="discountMasterFilterModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="discountMasterFilterModalLabel">{{ translate('Filter Discount Master') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row gutters-5">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="dm_search">{{ translate('Discount ID, Product, SKU, Batch') }}</label>
                                <input type="text" class="form-control" id="dm_search" name="search" value="{{ $filters['search'] }}" placeholder="{{ translate('Discount ID, Product, SKU, Batch') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="dm_applied_on">{{ translate('Discount Applied On') }}</label>
                                <select name="applied_on" id="dm_applied_on" class="form-control">
                                    <option value="">{{ translate('All Applied On') }}</option>
                                    @foreach (\App\Models\DiscountMaster::APPLIED_ON as $key => $label)
                                        <option value="{{ $key }}" @selected($filters['applied_on'] === $key)>{{ translate($label) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="dm_discount_type">{{ translate('Discount Type') }}</label>
                                <select name="discount_type" id="dm_discount_type" class="form-control">
                                    <option value="">{{ translate('All Types') }}</option>
                                    @foreach (\App\Models\DiscountMaster::DISCOUNT_TYPES as $key => $label)
                                        <option value="{{ $key }}" @selected($filters['discount_type'] === $key)>{{ translate($label) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="dm_status">{{ translate('Offer Active / Deactivate') }}</label>
                                <select name="status" id="dm_status" class="form-control">
                                    <option value="">{{ translate('All Status') }}</option>
                                    <option value="1" @selected($filters['status'] === '1')>{{ translate('Active') }}</option>
                                    <option value="0" @selected($filters['status'] === '0')>{{ translate('Deactivate') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="dm_date_from">{{ translate('From Date') }}</label>
                                <input type="date" class="form-control" id="dm_date_from" name="date_from" value="{{ $filters['date_from'] }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="dm_date_to">{{ translate('To Date') }}</label>
                                <input type="date" class="form-control" id="dm_date_to" name="date_to" value="{{ $filters['date_to'] }}">
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
    function updateDiscountMasterStatus(el) {
        $.post('{{ route('discount_masters.update_status') }}', {
            _token: '{{ csrf_token() }}',
            id: el.value,
            status: el.checked ? 1 : 0
        }, function() {
            AIZ.plugins.notify('success', '{{ translate('Status updated successfully') }}');
        });
    }
</script>
@endsection
