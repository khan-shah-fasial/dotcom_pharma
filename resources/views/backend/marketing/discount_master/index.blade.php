@extends('backend.layouts.app')

@section('content')

@php
    $sortBy = $sortBy ?? '';
    $sortDir = $sortDir ?? 'desc';
    $tab = $tab ?? 'discount_type';
    $scopeStocks = $scopeStocks ?? collect();
    $batchMasters = $batchMasters ?? collect();
    $filtersApplied = collect($filters)->contains(function ($value) {
        return $value !== null && $value !== '';
    });
    $tabUrl = function ($name) {
        return route('discount_masters.index', array_merge(request()->except('page'), ['tab' => $name]));
    };
    $renderLines = function ($lines) {
        $html = '';
        $lastGroup = null;
        foreach ($lines as $line) {
            if ($lastGroup !== null && $line['group'] !== $lastGroup) {
                $html .= '<div class="mt-1"></div>';
            }
            $lastGroup = $line['group'];
            $html .= '<div class="dm-stack-line"><div>' . e(translate($line['label'])) . '</div><div>' . e($line['value']) . '</div></div>';
        }

        return $html !== '' ? $html : '—';
    };
    $scopeLine = function ($discount) use ($scopeStocks) {
        $sheet = $discount->sheetPayload();
        if (empty($sheet['same_discount'])) {
            return '';
        }
        $bits = [];
        if ($discount->category) {
            $bits[] = translate('Category') . ': ' . $discount->category->getTranslation('name');
        }
        if ($discount->group) {
            $bits[] = translate('Group') . ': ' . $discount->group->getTranslation('name');
        }
        if ($discount->customer_id) {
            $bits[] = translate('Customer') . ': ' . $discount->customerDisplayName();
        }
        if (!empty($sheet['near_expiry'])) {
            $bits[] = translate('Near expiry batches');
        }
        $scopeRole = $sheet['scope_role'] ?? '';
        if ($scopeRole !== '' && isset(\App\Models\DiscountMaster::ROLE_KEYS[$scopeRole])) {
            $bits[] = translate('Price Role') . ': ' . translate(\App\Models\DiscountMaster::ROLE_KEYS[$scopeRole]);
        }
        $names = [];
        foreach ((array) ($sheet['scope_products'] ?? []) as $id) {
            $stock = $scopeStocks->get((int) $id);
            if ($stock) {
                $names[] = optional($stock->product)->getTranslation('name') ?: ('#' . $stock->id);
            }
        }
        if ($names !== []) {
            $bits[] = translate('Products') . ': ' . implode(', ', $names);
        }

        return $bits === []
            ? translate('Same discount')
            : translate('Same discount') . ' — ' . implode(' | ', $bits);
    };
    $colspan = $tab === 'applied_on' ? 10 : 8;
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

<div class="nav border-bottom aiz-nav-tabs mb-3">
    <a class="px-3 py-2 fs-15 text-reset {{ $tab === 'discount_type' ? 'show active font-weight-bold' : '' }}" href="{{ $tabUrl('discount_type') }}">
        {{ translate('Discount Type') }}
    </a>
    <a class="px-3 py-2 fs-15 text-reset {{ $tab === 'role_type' ? 'show active font-weight-bold' : '' }}" href="{{ $tabUrl('role_type') }}">
        {{ translate('Role Type') }}
    </a>
    <a class="px-3 py-2 fs-15 text-reset {{ $tab === 'applied_on' ? 'show active font-weight-bold' : '' }}" href="{{ $tabUrl('applied_on') }}">
        {{ translate('Applied On') }}
    </a>
</div>

@if (!$tableReady)
    <div class="alert alert-warning">
        {{ translate('Discount Master table is not ready yet. Run the discount_masters migration, then reload this page.') }}
    </div>
@endif

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
            <a href="{{ route('discount_masters.index', ['tab' => $tab]) }}" class="btn btn-danger mb-2">
                {{ translate('Reset') }}
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered aiz-table mb-0 dm-listing">
                <thead>
                    <tr>
                        <th>{{ translate('Product') }}<br>{{ translate('Full Variant') }}</th>
                        @if ($tab === 'discount_type')
                            @include('backend.inc.sortable_th', ['column' => 'discount_code', 'label' => translate('Discount ID'), 'routeName' => 'discount_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            <th>{{ translate('Discount Type') }}</th>
                            <th>{{ translate('Discount') }}</th>
                        @elseif ($tab === 'role_type')
                            @include('backend.inc.sortable_th', ['column' => 'role_key', 'label' => translate('Role Type'), 'routeName' => 'discount_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            <th>{{ translate('Qty Slab') }}<br>{{ translate('Rate') }}<br>{{ translate('Amount') }}<br>{{ translate('Effective Rate') }}</th>
                            <th>{{ translate('Stock Value') }} {{ translate('PTS') }} / {{ translate('Value') }}</th>
                        @else
                            @include('backend.inc.sortable_th', ['column' => 'applied_on', 'label' => translate('Applied On'), 'routeName' => 'discount_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'sku', 'label' => translate('SKU Product'), 'routeName' => 'discount_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'variant', 'label' => translate('Full Variant'), 'routeName' => 'discount_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'batch', 'label' => translate('Batch / Lot.No'), 'routeName' => 'discount_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            <th>{{ translate('Mfg.Date') }}<br>{{ translate('Expiry Date') }}</th>
                        @endif
                        <th>{{ translate('COA') }}</th>
                        <th>{{ translate('Total Discount Given') }}</th>
                        @include('backend.inc.sortable_th', ['column' => 'from_date', 'labelHtml' => e(translate('From Date')) . '<br>' . e(translate('To Date')) . '<br>' . e(translate('Offer Active')) . '<br>' . e(translate('Date Of Add / Edit')), 'routeName' => 'discount_masters.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        <th class="text-right dm-actions">{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($tableReady && $discounts && $discounts->count())
                        @foreach ($discounts as $discount)
                            @php
                                $isProductRow = in_array($discount->discount_type, \App\Models\DiscountMaster::PRODUCT_TYPES, true);
                                $sourceBatch = $discount->sourceBatch();
                                $masterCodes = [];
                                $masterCoa = null;
                                foreach ((array) ($discount->sheetPayload()['batch_ids'] ?? []) as $batchPick) {
                                    $batchPick = (string) $batchPick;
                                    if (strpos($batchPick, 'bm-') !== 0) {
                                        continue;
                                    }
                                    $master = $batchMasters->get((int) substr($batchPick, 3));
                                    if (!$master) {
                                        continue;
                                    }
                                    $masterCodes[] = $master->batch_code;
                                    if (!$masterCoa && $master->coa) {
                                        $masterCoa = $master->coa;
                                    }
                                }
                                $coaId = $isProductRow ? ($discount->sourceCoa() ?: $masterCoa) : null;
                                $coaIsImage = $coaId && function_exists('check_asset_type') && check_asset_type($coaId) === 'image';
                                $sameScope = $scopeLine($discount);
                            @endphp
                            <tr>
                                <td>
                                    {{ optional($discount->product)->getTranslation('name') ?: '—' }}
                                    <div class="text-muted fs-12">{{ $discount->stock ? ($discount->stock->expandedVariantLabel() ?: '—') : '—' }}</div>
                                    @if ($sameScope !== '')
                                        <div class="text-muted fs-11">{{ $sameScope }}</div>
                                    @endif
                                </td>
                                @if ($tab === 'discount_type')
                                    <td>
                                        <strong>{{ $discount->discount_code }}</strong>
                                        @if (!empty($discount->coupon_code))
                                            <div>{{ $discount->coupon_code }}</div>
                                        @endif
                                    </td>
                                    <td>{{ translate($discount->discountTypeLabel()) }}</td>
                                    <td>{!! $renderLines($discount->discountValueLines()) !!}</td>
                                @elseif ($tab === 'role_type')
                                    <td>{{ $discount->role_key ? $discount->roleKeyLabel() : '—' }}</td>
                                    <td>{!! $renderLines($discount->roleSlabLines()) !!}</td>
                                    <td class="fs-12">
                                        @if ($isProductRow)
                                            @foreach ($discount->stockValueLines() as $stockLine)
                                                <div class="{{ $discount->role_key === $stockLine['key'] ? 'font-weight-bold text-primary' : '' }}">
                                                    {{ translate($stockLine['label']) }} {{ $stockLine['price'] }} / {{ translate('Value') }} {{ $stockLine['value'] }}
                                                </div>
                                            @endforeach
                                        @else
                                            —
                                        @endif
                                    </td>
                                @else
                                    <td>{{ translate($discount->appliedOnLabel()) }}</td>
                                    <td>{{ optional($discount->stock)->sku ?: '—' }}</td>
                                    <td>
                                        @if ($discount->stock)
                                            {{ $discount->stock->expandedVariantLabel() ?: '—' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($discount->applied_on === 'batch' || $discount->batch)
                                            <div>{{ optional($sourceBatch)->batch ?: ($masterCodes ? implode(', ', $masterCodes) : '—') }}</div>
                                            <div class="text-muted fs-11">{{ translate('Stock') }}: {{ $discount->sourceQty() ?? '—' }}</div>
                                        @elseif ($isProductRow)
                                            <div class="text-muted fs-11">{{ translate('Stock') }}: {{ $discount->sourceQty() ?? '—' }}</div>
                                        @endif
                                        @if ($discount->category)
                                            <div>{{ translate('Category') }}: {{ $discount->category->getTranslation('name') }}</div>
                                        @endif
                                        @if ($discount->group)
                                            <div>{{ translate('Group') }}: {{ $discount->group->getTranslation('name') }}</div>
                                        @endif
                                        @if ($discount->customer_id)
                                            <div>{{ translate('Customer') }}: {{ $discount->customerDisplayName() }}</div>
                                        @endif
                                        @if (!$discount->stock && !$discount->category && !$discount->group && !$discount->customer_id && $discount->applied_on !== 'batch')
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($isProductRow)
                                            <div>{{ optional($sourceBatch)->manufacturing_date ?: '—' }}</div>
                                            <div>{{ optional($sourceBatch)->product_exp_date ?? optional($discount->stock)->product_exp_date ?: '—' }}</div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    @if ($coaId && $coaIsImage)
                                        <a href="#" class="dm-coa-zoom" data-src="{{ uploaded_asset($coaId) }}">
                                            <img src="{{ uploaded_asset($coaId) }}" alt="{{ translate('COA Document') }}" class="img-thumbnail" style="height:48px;width:48px;object-fit:cover;">
                                        </a>
                                    @elseif ($coaId)
                                        <a href="{{ uploaded_asset($coaId) }}" target="_blank" class="btn btn-soft-primary btn-sm">{{ translate('COA') }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $discount->totalDiscountLabel() }}</td>
                                <td>
                                    {{ optional($discount->from_date)->format('d-m-Y') ?: '—' }}<br>
                                    {{ optional($discount->to_date)->format('d-m-Y') ?: '—' }}<br>
                                    <label class="aiz-switch aiz-switch-success mb-0">
                                        <input type="checkbox" onchange="updateDiscountMasterStatus(this)" value="{{ $discount->id }}" {{ $discount->status ? 'checked' : '' }} @cannot('edit_discount_master') disabled @endcannot>
                                        <span class="slider round"></span>
                                    </label>
                                    <div class="text-muted fs-11">{{ $discount->updated_at }}</div>
                                </td>
                                <td class="text-right dm-actions">
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
                            <td colspan="{{ $colspan }}" class="text-center text-muted">{{ translate('No discount master entries found.') }}</td>
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
        font-size: 12px;
    }
    .dm-listing th a { text-decoration: none; }
    .dm-listing td {
        vertical-align: top;
        font-size: 13px;
        white-space: normal;
    }
    .dm-stack-line { display: block; margin-bottom: 6px; line-height: 1.25; }
    .dm-stack-line div { display: block; }
    .dm-listing th.dm-actions,
    .dm-listing td.dm-actions {
        white-space: nowrap;
        width: 88px;
        text-align: right;
    }
</style>

@endsection

@section('modal')
    @include('modals.delete_modal')

    <div class="modal fade" id="dmCoaModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('COA Image') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <img id="dm-coa-preview" src="" alt="{{ translate('COA Image') }}" style="max-width:100%; max-height:70vh;">
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('discount_masters.index') }}" method="GET" id="discount-master-filters">
        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
        <input type="hidden" name="tab" value="{{ $tab }}">
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
    $(document).on('click', '.dm-coa-zoom', function (e) {
        e.preventDefault();
        $('#dm-coa-preview').attr('src', $(this).data('src'));
        $('#dmCoaModal').modal('show');
    });
</script>
@endsection
