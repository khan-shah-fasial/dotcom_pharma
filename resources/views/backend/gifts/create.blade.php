@extends('backend.layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ translate('Add Gift') }}</h6>
                <a href="{{ route('gifts.index') }}" class="btn btn-link">{{ translate('Back to list') }}</a>
            </div>
            <div class="card-body">
                @include('backend.gifts._form', [
                    'action' => route('gifts.store'),
                    'submitLabel' => translate('Create Gift'),
                ])
            </div>
        </div>
    </div>
</div>
@include('uploader.aiz-uploader')
@endsection
