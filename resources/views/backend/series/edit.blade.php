@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <h5 class="mb-0 h6">{{ translate('Edit Series') }}</h5>
</div>
<div class="col-lg-8 mx-auto">
    @if (!empty($billColumnsMissing))
        <div class="alert alert-warning">
            {{ translate('Payment Type, Total No of Bills, From Bill No, and To Bill No need the extra series_masters columns. Run the SQL note, then these fields will save, filter, and sort.') }}
        </div>
    @endif
    <div class="card">
        <div class="card-body">
            <form action="{{ route('series.update', $seriesItem) }}" method="POST">
                @csrf
                @include('backend.series.form', ['seriesItem' => $seriesItem])
            </form>
        </div>
    </div>
</div>
@endsection
