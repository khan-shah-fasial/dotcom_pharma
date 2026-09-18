@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Edit Batch / Lot') }} — {{ $batch->batch_code }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('batch_masters.index') }}" class="btn btn-circle btn-light">{{ translate('Back') }}</a>
        </div>
    </div>
</div>

<form action="{{ route('batch_masters.update', $batch->id) }}" method="POST" id="batch-master-form">
    @csrf
    @method('PUT')
    @include('backend.product.batch_master._form', ['batch' => $batch])
</form>
@endsection

@section('script')
    @include('backend.product.batch_master._form_script')
@endsection
