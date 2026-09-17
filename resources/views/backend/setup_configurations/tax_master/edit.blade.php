@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Edit Tax') }} — {{ $tax->tax_code }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('tax_masters.index') }}" class="btn btn-circle btn-light">{{ translate('Back') }}</a>
        </div>
    </div>
</div>

<form action="{{ route('tax_masters.update', $tax->id) }}" method="POST" id="tax-master-form">
    @csrf
    @method('PUT')
    @include('backend.setup_configurations.tax_master._form', ['tax' => $tax])
</form>
@endsection

@section('script')
    @include('backend.setup_configurations.tax_master._form_script')
@endsection
