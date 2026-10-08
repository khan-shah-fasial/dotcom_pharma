@php
    $activeTab = $activeTab ?? 'listing';
@endphp
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3 mb-0">{{ translate('Batch / Lot Master') }}</h1>
        </div>
        <div class="col-md-4 text-md-right">
            @if ($activeTab === 'add')
                <a href="{{ route('batch_masters.index') }}" class="btn btn-circle btn-light">{{ translate('Back') }}</a>
            @endif
        </div>
    </div>
</div>
<div class="nav border-bottom aiz-nav-tabs mb-3">
    <a class="px-3 py-2 fs-15 text-reset {{ $activeTab === 'add' ? 'show active font-weight-bold' : '' }}" href="{{ route('batch_masters.create') }}">
        {{ translate('Add New Batch / Lot') }}
    </a>
    <a class="px-3 py-2 fs-15 text-reset {{ $activeTab === 'listing' ? 'show active font-weight-bold' : '' }}" href="{{ route('batch_masters.index') }}">
        {{ translate('Listing Page') }}
    </a>
    <a class="px-3 py-2 fs-15 text-reset {{ $activeTab === 'adjust' ? 'show active font-weight-bold' : '' }}" href="{{ route('batch_masters.adjust') }}">
        {{ translate('Next Tab Batch Adjustment') }}
    </a>
</div>
