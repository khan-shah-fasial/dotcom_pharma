@php
    $viewerTitle = $viewerTitle ?? '';
    $viewerCertificates = collect($viewerCertificates ?? [])->map->toViewerArray()->values();
    $viewerDocuments = collect($viewerDocuments ?? [])->map->toViewerArray()->values();
    $viewerKey = $viewerKey ?? ('company-files-' . uniqid());
@endphp

<button type="button"
    class="btn btn-soft-warning btn-sm mb-1 js-open-company-files"
    data-title="{{ $viewerTitle }}"
    data-target="#{{ $viewerKey }}">
    {{ translate('Certificates & Documents') }}
</button>
<script type="application/json" id="{{ $viewerKey }}">
{!! json_encode(['certificates' => $viewerCertificates, 'documents' => $viewerDocuments], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}
</script>
