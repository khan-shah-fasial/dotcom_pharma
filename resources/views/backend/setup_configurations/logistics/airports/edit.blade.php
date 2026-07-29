@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <h1 class="h3">{{ translate('Edit Airport') }}</h1>
</div>

<form action="{{ route('airports.update', $airport) }}" method="POST">
    @csrf
    @method('PUT')
    @include('backend.setup_configurations.logistics.airports._form', ['submitLabel' => translate('Update Airport')])
</form>
@endsection
