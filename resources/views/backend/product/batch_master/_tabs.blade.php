@php
    $activeTab = $activeTab ?? 'listing';
    $showAddButton = $showAddButton ?? ($activeTab === 'listing');
@endphp
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3 mb-0">{{ translate('Batch / Lot Master') }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            @if ($activeTab === 'add')
                <a href="{{ route('batch_masters.index') }}" class="btn btn-circle btn-light">{{ translate('Back') }}</a>
            @elseif ($showAddButton)
                @can('add_batch_master')
                    <a href="{{ route('batch_masters.create') }}" class="btn btn-circle btn-info">
                        <span>{{ translate('Add New Batch / Lot') }}</span>
                    </a>
                @endcan
            @endif
        </div>
    </div>
</div>
<div class="nav border-bottom aiz-nav-tabs mb-3">
    <a class="px-3 py-2 fs-15 text-reset {{ $activeTab === 'listing' ? 'show active font-weight-bold' : '' }}" href="{{ route('batch_masters.index') }}">
        {{ translate('Listing Page') }}
    </a>
    <a class="px-3 py-2 fs-15 text-reset {{ $activeTab === 'adjust' ? 'show active font-weight-bold' : '' }}" href="{{ route('batch_masters.adjust') }}">
        {{ translate('Next Tab Batch Adjustment') }}
    </a>
</div>
