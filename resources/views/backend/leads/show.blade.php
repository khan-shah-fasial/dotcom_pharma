@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Lead Details') }} {{ $lead->lead_no ? '- '.$lead->lead_no : '' }}</h1></div>
        <div class="col-md-6 text-md-right">
            @can('edit_lead')
                <a href="{{ route('leads.edit', $lead->id) }}" class="btn btn-soft-primary">{{ translate('Edit') }}</a>
            @endcan
            <a href="{{ route('leads.index') }}" class="btn btn-primary">{{ translate('Back') }}</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Lead Information') }}</h5></div>
            <div class="card-body">
                <table class="table table-bordered mb-0">
                    <tr><th width="25%">{{ translate('Lead No') }}</th><td>{{ $lead->lead_no ?? '-' }}</td></tr>
                    <tr><th>{{ translate('Name') }}</th><td>{{ $lead->name }}</td></tr>
                    <tr><th>{{ translate('Company') }}</th><td>{{ $lead->company_name ?? '-' }}</td></tr>
                    <tr><th>{{ translate('Email') }}</th><td>{{ $lead->email ?? '-' }}</td></tr>
                    <tr><th>{{ translate('Phone') }}</th><td>{{ $lead->phone ?? '-' }}</td></tr>
                    <tr><th>{{ translate('Source') }}</th><td>{{ optional($lead->source)->name ?? '-' }}</td></tr>
                    <tr>
                        <th>{{ translate('Status') }}</th>
                        <td>
                            @if($lead->status)
                                <span class="badge badge-inline text-white" style="background-color: {{ $lead->status->color ?? '#6c757d' }}">{{ $lead->status->name }}</span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr><th>{{ translate('Created By') }}</th><td>{{ optional($lead->creator)->name ?? '-' }}</td></tr>
                    <tr><th>{{ translate('Assigned To') }}</th><td>{{ optional($lead->assignedUser)->name ?? '-' }}</td></tr>
                    <tr><th>{{ translate('Expected Value') }}</th><td>{{ number_format((float) $lead->expected_value, 2) }}</td></tr>
                    <tr><th>{{ translate('Notes') }}</th><td>{!! nl2br(e($lead->notes ?? '-')) !!}</td></tr>
                </table>
            </div>
        </div>
        @include('backend.leads._activities')
    </div>
    <div class="col-lg-4">
        @can('edit_lead')
            <div class="card">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('Add Activity') }}</h5></div>
                <div class="card-body">
                    @include('backend.leads._activity_form')
                </div>
            </div>
        @endcan
    </div>
</div>
@endsection
