@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Edit HSN') }} — {{ $row->hsn_code }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('hsn_masters.index') }}" class="btn btn-circle btn-light">{{ translate('Back') }}</a>
        </div>
    </div>
</div>

<div class="col-lg-8 mx-auto px-0">
    <form action="{{ route('hsn_masters.update', $row->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('backend.setup_configurations.hsn_master._form', ['row' => $row])
    </form>
</div>
@endsection

@section('script')
    @include('backend.setup_configurations.hsn_master._form_script')
@endsection
