@extends('backend.layouts.app')

@section('content')
@include('backend.product.batch_master._tabs', ['activeTab' => 'add'])

<form action="{{ route('batch_masters.store') }}" method="POST" id="batch-master-form" enctype="multipart/form-data">
    @csrf
    @include('backend.product.batch_master._form', ['batch' => null])
</form>
@endsection

@section('script')
    @include('backend.product.batch_master._form_script')
@endsection
