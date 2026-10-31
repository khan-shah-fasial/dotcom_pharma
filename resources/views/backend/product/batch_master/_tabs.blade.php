@php
    $activeTab = $activeTab ?? 'listing';
    $showAddButton = $showAddButton ?? ($activeTab === 'listing');
@endphp
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3 mb-0">{{ translate('Batch / Lot Master') }}</h1>
        </div>
        @if ($activeTab === 'add')
            <div class="col-md-6 text-md-right">
                <a href="{{ route('batch_masters.index') }}" class="btn btn-circle btn-light">{{ translate('Back') }}</a>
            </div>
        @elseif ($showAddButton)
            @can('add_batch_master')
                <div class="col-md-6 text-md-right">
                    <a href="{{ route('batch_masters.create') }}" class="btn btn-circle btn-info">{{ translate('Add New Batch / Lot') }}</a>
                </div>
            @endcan
        @endif
    </div>
</div>
<div class="nav border-bottom aiz-nav-tabs mb-3 d-flex flex-wrap align-items-center">
    <a class="px-3 py-2 fs-15 text-reset {{ $activeTab === 'listing' ? 'show active font-weight-bold' : '' }}" href="{{ route('batch_masters.index') }}">
        {{ translate('Listing Page') }}
    </a>
    <a class="px-3 py-2 fs-15 text-reset {{ $activeTab === 'adjust' ? 'show active font-weight-bold' : '' }}" href="{{ route('batch_masters.adjust') }}">
        {{ translate('Batch Adjustment') }}
    </a>
</div>
