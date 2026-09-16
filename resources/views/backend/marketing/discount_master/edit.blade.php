@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Edit Discount') }} — {{ $discount->discount_code }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('discount_masters.index') }}" class="btn btn-circle btn-light">{{ translate('Back') }}</a>
        </div>
    </div>
</div>

<form action="{{ route('discount_masters.update', $discount->id) }}" method="POST" id="discount-master-form" data-existing-code="{{ $discount->discount_code }}" data-existing-type="{{ $discount->discount_type }}">
    @csrf
    @method('PUT')
    @include('backend.marketing.discount_master._form', ['discount' => $discount])
</form>
@endsection

@section('script')
    @include('backend.marketing.discount_master._form_script')
@endsection
