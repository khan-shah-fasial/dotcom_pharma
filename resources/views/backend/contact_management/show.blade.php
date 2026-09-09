@extends('backend.layouts.app')

@section('content')
@php
    $phoneHref = $contact->phone ? preg_replace('/\s+/', '', $contact->phone) : null;
    $whatsappHref = $contact->whatsapp_number ? preg_replace('/\D+/', '', $contact->whatsapp_number) : null;
@endphp
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Contact Details') }} {{ $contact->contact_no ? '- '.$contact->contact_no : '' }}</h1></div>
        <div class="col-md-6 text-md-right">
            @can('edit_contact_directory')
                <a href="{{ route('contact-directory.edit', $contact->id) }}" class="btn btn-soft-primary">{{ translate('Edit') }}</a>
            @endcan
            <a href="{{ route('contact-directory.index') }}" class="btn btn-primary">{{ translate('Back') }}</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Contact Information') }}</h5></div>
    <div class="card-body">
        <table class="table table-bordered mb-0">
            <tr><th width="25%">{{ translate('Contact No') }}</th><td>{{ $contact->contact_no ?? '-' }}</td></tr>
            <tr>
                <th>{{ translate('Photo') }}</th>
                <td>
                    @if($contact->photo)
                        <img src="{{ uploaded_asset($contact->photo) }}" alt="{{ $contact->name }}" class="img-fit size-80px rounded">
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr><th>{{ translate('Full Name') }}</th><td>{{ $contact->name }}</td></tr>
            <tr><th>{{ translate('Company Name') }}</th><td>{{ $contact->company_name ?? '-' }}</td></tr>
            <tr><th>{{ translate('Designation') }}</th><td>{{ $contact->designation ?? '-' }}</td></tr>
            <tr><th>{{ translate('Group') }}</th><td>{{ optional($contact->group)->name ?? '-' }}</td></tr>
            <tr><th>{{ translate('Category') }}</th><td>{{ optional($contact->category)->name ?? '-' }}</td></tr>
            <tr><th>{{ translate('Subcategory') }}</th><td>{{ optional($contact->subcategory)->name ?? '-' }}</td></tr>
            <tr><th>{{ translate('Type') }}</th><td>{{ optional($contact->type)->name ?? '-' }}</td></tr>
            <tr><th>{{ translate('Subject') }}</th><td>{{ optional($contact->subject)->name ?? '-' }}</td></tr>
            <tr><th>{{ translate('Industry') }}</th><td>{{ optional($contact->industry)->name ?? '-' }}</td></tr>
            <tr><th>{{ translate('Work Profile') }}</th><td>{{ optional($contact->workProfile)->name ?? '-' }}</td></tr>
            <tr><th>{{ translate('Department') }}</th><td>{{ optional($contact->department)->name ?? '-' }}</td></tr>
            <tr><th>{{ translate('Purpose') }}</th><td>{{ optional($contact->purpose)->name ?? '-' }}</td></tr>
            <tr>
                <th>{{ translate('Email') }}</th>
                <td>
                    @if ($contact->email)
                        <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <th>{{ translate('Mobile Number') }}</th>
                <td>
                    @if ($contact->phone)
                        <a href="tel:{{ $phoneHref }}">{{ $contact->phone }}</a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <th>{{ translate('Alternate Mobile No') }}</th>
                <td>
                    @if ($contact->alternate_mobile_number)
                        <a href="tel:{{ preg_replace('/\s+/', '', $contact->alternate_mobile_number) }}">{{ $contact->alternate_mobile_number }}</a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <th>{{ translate('WhatsApp Number') }}</th>
                <td>
                    @if ($contact->whatsapp_number && $whatsappHref)
                        <a href="https://wa.me/{{ $whatsappHref }}" target="_blank" rel="noopener">{{ $contact->whatsapp_number }}</a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <th>{{ translate('Social Media IDs') }}</th>
                <td>
                    @forelse (($contact->social_media_ids ?? []) as $socialMedia)
                        <div>
                            <span class="fw-600">{{ $socialMedia['key'] ?? '-' }}:</span>
                            <span>{{ $socialMedia['value'] ?? '-' }}</span>
                        </div>
                    @empty
                        -
                    @endforelse
                </td>
            </tr>
            <tr><th>{{ translate('Address') }}</th><td>{{ $contact->address ?? '-' }}</td></tr>
            <tr><th>{{ translate('Country') }}</th><td>{{ optional($contact->country)->name ?? '-' }}</td></tr>
            <tr><th>{{ translate('State/Region') }}</th><td>{{ optional($contact->state)->name ?? '-' }}</td></tr>
            <tr><th>{{ translate('District') }}</th><td>{{ $contact->district ?? '-' }}</td></tr>
            <tr><th>{{ translate('Post') }}</th><td>{{ $contact->post ?? '-' }}</td></tr>
            <tr>
                <th>{{ translate('Tags') }}</th>
                <td>
                    @forelse (($contact->tags ?? []) as $tag)
                        <span class="badge badge-inline badge-light mb-1">{{ $tag }}</span>
                    @empty
                        -
                    @endforelse
                </td>
            </tr>
            <tr><th>{{ translate('Notes') }}</th><td>{!! nl2br(e($contact->notes ?? '-')) !!}</td></tr>
            <tr><th>{{ translate('Create Date') }}</th><td>{{ $contact->created_at ? $contact->created_at->format('d-m-Y h:i A') : '-' }}</td></tr>
            <tr><th>{{ translate('Updated By') }}</th><td>{{ optional($contact->updater)->name ?? '-' }}</td></tr>
            <tr><th>{{ translate('Update Date') }}</th><td>{{ $contact->updated_at ? $contact->updated_at->format('d-m-Y h:i A') : '-' }}</td></tr>
        </table>
    </div>
</div>
@endsection
