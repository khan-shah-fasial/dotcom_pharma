@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Add New Discount') }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('discount_masters.index') }}" class="btn btn-circle btn-light">{{ translate('Back') }}</a>
        </div>
    </div>
</div>

<form action="{{ route('discount_masters.store') }}" method="POST" id="discount-master-form" data-existing-code="" data-existing-type="">
    @csrf
    @include('backend.marketing.discount_master._form', ['discount' => null])
</form>
@endsection

@section('script')
    @include('backend.marketing.discount_master._form_script')
@endsection
