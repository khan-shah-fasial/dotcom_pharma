<p style="margin-bottom: 2rem !important;">{!! $content !!}</p>

@php
    $hideDetails = isset($hide_contact_details) && $hide_contact_details === true;
@endphp

@unless($hideDetails)
    <p style="margin-bottom: 2rem !important;">
        <strong>{{ translate('Name') }}:</strong> {{ $name }}<br>
        <strong>{{ translate('Email') }}:</strong> {{ $email }}
        @if ($phone != null)
            <br>
            <strong>{{ translate('Phone') }}:</strong> {{ $phone }}
        @endif
    </p>
@endunless
<a href="{{ env('APP_URL') }}">{{ translate('Go to the website') }}</a>
