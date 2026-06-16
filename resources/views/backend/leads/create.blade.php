@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Add New Lead') }}</h1></div>
        <div class="col-md-6 text-md-right"><a href="{{ route('leads.index') }}" class="btn btn-primary">{{ translate('Back') }}</a></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Lead Information') }}</h5></div>
    <div class="card-body">
        <form action="{{ route('leads.store') }}" method="POST">
            @include('backend.leads._form', ['buttonText' => translate('Save')])
        </form>
    </div>
</div>
@endsection

@section('script')
    @include('backend.leads._form_script')
@endsection
