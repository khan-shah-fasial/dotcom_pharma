@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <h1 class="h3">{{ translate('Add Airport') }}</h1>
</div>

<form action="{{ route('airports.store') }}" method="POST">
    @csrf
    @include('backend.setup_configurations.logistics.airports._form', ['submitLabel' => translate('Save Airport')])
</form>
@endsection
