@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <h5 class="mb-0 h6">{{ translate('Add New Series') }}</h5>
</div>
<div class="col-lg-8 mx-auto">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('series.store') }}" method="POST">
                @csrf
                @include('backend.series.form')
            </form>
        </div>
    </div>
</div>
@endsection
