@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <h1 class="h3">{{ translate('Add Sea Port') }}</h1>
</div>

<form action="{{ route('sea-ports.store') }}" method="POST">
    @csrf
    @include('backend.setup_configurations.logistics.sea_ports._form', ['submitLabel' => translate('Save Sea Port')])
</form>
@endsection
