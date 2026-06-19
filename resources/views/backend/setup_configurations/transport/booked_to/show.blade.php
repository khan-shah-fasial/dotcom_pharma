@extends('backend.layouts.app')

@section('content')
@php
    $branchMobileHref = $bookedTo->branch_mobile_number ? preg_replace('/\D+/', '', $bookedTo->branch_mobile_number) : null;
    $alternateMobileHref = $bookedTo->branch_alternate_mobile_number ? preg_replace('/\D+/', '', $bookedTo->branch_alternate_mobile_number) : null;
@endphp

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Booked To Details') }}</h1></div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('booked-to.edit', $bookedTo->id) }}" class="btn btn-soft-primary">{{ translate('Edit') }}</a>
            <a href="{{ route('booked-to.index') }}" class="btn btn-primary">{{ translate('Back') }}</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Booked To Information') }}</h5></div>
    <div class="card-body">
        <table class="table table-bordered mb-0">
            <tr><th width="30%">{{ translate('Transport') }}</th><td>{{ optional($bookedTo->transport)->name ?? '-' }}</td></tr>
            <tr><th>{{ translate('Location') }}</th><td>{{ $bookedTo->name ?? '-' }}</td></tr>
            <tr><th>{{ translate('Branch Name') }}</th><td>{{ $bookedTo->branch_name ?? '-' }}</td></tr>
            <tr><th>{{ translate('Branch Code') }}</th><td>{{ $bookedTo->branch_code ?? '-' }}</td></tr>
            <tr><th>{{ translate('Branch GST Number') }}</th><td>{{ $bookedTo->branch_gst_number ?? '-' }}</td></tr>
            <tr>
                <th>{{ translate('Branch Mobile Number') }}</th>
                <td>
                    @if($bookedTo->branch_mobile_number && $branchMobileHref)
                        <a href="https://wa.me/{{ $branchMobileHref }}" target="_blank" rel="noopener">{{ $bookedTo->branch_mobile_number }}</a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <th>{{ translate('Branch Alternate Mobile Number') }}</th>
                <td>
                    @if($bookedTo->branch_alternate_mobile_number && $alternateMobileHref)
                        <a href="https://wa.me/{{ $alternateMobileHref }}" target="_blank" rel="noopener">{{ $bookedTo->branch_alternate_mobile_number }}</a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr><th>{{ translate('Contact - Incharge') }}</th><td>{{ $bookedTo->contact_incharge ?? '-' }}</td></tr>
            <tr>
                <th>{{ translate('Branch Email ID') }}</th>
                <td>
                    @if($bookedTo->branch_email)
                        <a href="mailto:{{ $bookedTo->branch_email }}">{{ $bookedTo->branch_email }}</a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr><th>{{ translate('Created By') }}</th><td>{{ optional($bookedTo->creator)->name ?? '-' }}</td></tr>
            <tr>
                <th>{{ translate('Status') }}</th>
                <td>
                    <span class="badge badge-inline badge-{{ $bookedTo->status == 'active' ? 'success' : 'secondary' }}">
                        {{ translate(ucfirst($bookedTo->status)) }}
                    </span>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection
