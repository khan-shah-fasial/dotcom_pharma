@extends('backend.layouts.app')

@section('content')
@include('backend.product.batch_master._tabs', ['activeTab' => 'listing'])

<form action="{{ route('batch_masters.update', $batch->id) }}" method="POST" id="batch-master-form" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('backend.product.batch_master._form', ['batch' => $batch])
</form>
@endsection

@section('script')
    @include('backend.product.batch_master._form_script')
@endsection
