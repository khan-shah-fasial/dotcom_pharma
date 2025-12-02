@php 
$data = $data['data'] ?? [];
@endphp

<div class="card">
    <div class="card-header">
        <h1 class="h2 fs-16 mb-0">{{ __('Location Details') }}</h1>
    </div>

    <div class="card-body">

        <div>
            <p><strong>IP:</strong> {{ $data['ip'] ?? 'N/A' }}</p>
            <p><strong>City:</strong> {{ $data['city'] ?? 'N/A' }}</p>
            <p><strong>Region / State:</strong> {{ $data['region'] ?? 'N/A' }}</p>
            <p><strong>Country:</strong> {{ $data['country'] ?? 'N/A' }}</p>

            @if(!empty($data['latitude']) && !empty($data['longitude']))
                <h5 style="margin-top:15px;">Map</h5>

                <iframe 
                    width="100%" 
                    height="250"
                    frameborder="0"
                    style="border:0; border-radius:6px;"
                    src="https://www.google.com/maps?q={{ $data['latitude'] }},{{ $data['longitude'] }}&output=embed">
                </iframe>
            @endif

        </div>

    </div>
</div>