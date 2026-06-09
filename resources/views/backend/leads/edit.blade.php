@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Edit Lead') }} {{ $lead->lead_no ? '- '.$lead->lead_no : '' }}</h1></div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('leads.show', $lead->id) }}" class="btn btn-soft-primary">{{ translate('View') }}</a>
            <a href="{{ route('leads.index') }}" class="btn btn-primary">{{ translate('Back') }}</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Lead Information') }}</h5></div>
    <div class="card-body">
        <form action="{{ route('leads.update', $lead->id) }}" method="POST">
            @include('backend.leads._form', ['buttonText' => translate('Update')])
        </form>
    </div>
</div>
@endsection
