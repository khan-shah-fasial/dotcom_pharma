@php
    $customer = $payload['customer'] ?? [];
    $staff    = $payload['staff'] ?? [];
@endphp

<h3>{{ translate('How was your support experience?') }}</h3>

<p>
    {{ translate('Thank you for using our support. We would love to hear your feedback about your recent interaction with our team.') }}
</p>

<h4>{{ translate('Support Summary') }}</h4>
<ul>
    <li><strong>{{ translate('Customer') }}:</strong> {{ $customer['name'] ?? '-' }}</li>
    <li><strong>{{ translate('Support Staff') }}:</strong> {{ $staff['name'] ?? '-' }}</li>
    <li><strong>{{ translate('Preferred Date & Time') }}:</strong> {{ $payload['scheduled_at'] ?? '-' }}</li>
</ul>

<p>
    {{ translate('Please click the button below to rate your experience. You do not need to log in to submit your review.') }}
</p>

<p style="margin: 20px 0;">
    <a href="{{ $payload['review_url'] ?? '#' }}"
       style="display:inline-block;padding:10px 20px;border-radius:4px;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:600;">
        {{ translate('Leave a Review') }}
    </a>
</p>

<p style="font-size: 12px;color:#6b7280;">
    {{ translate('This link will work until you submit your review once. After that, it will show your submitted feedback.') }}
</p>

