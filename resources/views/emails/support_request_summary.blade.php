@php
    $customer = $payload['customer'] ?? [];
    $staff    = $payload['staff'] ?? [];
@endphp

<h3>{{ translate('New Support Request') }}</h3>

<p>{{ translate('A new support request has been created from the frontend support page.') }}</p>

<h4>{{ translate('Customer Details') }}</h4>
<ul>
    <li><strong>{{ translate('Name') }}:</strong> {{ $customer['name'] ?? '-' }}</li>
    <li><strong>{{ translate('Email') }}:</strong> {{ $customer['email'] ?? '-' }}</li>
    <li><strong>{{ translate('Phone') }}:</strong> {{ $customer['phone'] ?? '-' }}</li>
</ul>

<h4>{{ translate('Staff Details') }}</h4>
<ul>
    <li><strong>{{ translate('Name') }}:</strong> {{ $staff['name'] ?? '-' }}</li>
    <li><strong>{{ translate('Email') }}:</strong> {{ $staff['email'] ?? '-' }}</li>
    <li><strong>{{ translate('Phone') }}:</strong> {{ $staff['phone'] ?? '-' }}</li>
</ul>

<h4>{{ translate('Request Details') }}</h4>
<ul>
    <li><strong>{{ translate('Channel') }}:</strong>
        @if (($payload['channel'] ?? '') === 'video')
            {{ translate('Video Meet') }}
        @elseif (($payload['channel'] ?? '') === 'callback')
            {{ translate('Call Back') }}
        @else
            {{ $payload['channel'] ?? '-' }}
        @endif
    </li>
    <li><strong>{{ translate('Preferred Date & Time') }}:</strong> {{ $payload['scheduled_at'] ?? '-' }}</li>
</ul>

@if (!empty($payload['notes']))
    <h4>{{ translate('Customer Notes') }}</h4>
    <p>{!! nl2br(e($payload['notes'])) !!}</p>
@endif

