@php
    $currentOrder = $order ?? null;
    $entryKey = $orderEntry ?? null;
    $market = $salesMarket ?? old('sales_market', $invoiceType ?? \App\Support\InvoiceType::DOMESTIC);
    $market = $market === \App\Support\InvoiceType::INTERNATIONAL
        ? \App\Support\InvoiceType::INTERNATIONAL
        : \App\Support\InvoiceType::DOMESTIC;
    $defaultTypeKey = $entryKey === 'sales_return'
        ? 'sales_return'
        : (\App\Support\DocumentType::DEFAULT_SALES_TYPE_BY_MARKET[$market] ?? 'performa_invoice');
    $storedType = old('document_type', $currentOrder->document_type ?? ($currentOrder ? '' : $defaultTypeKey));
    $storedCustom = old('document_type_custom', $currentOrder->document_type_custom ?? '');
    $canonicalSelectedKey = \App\Support\DocumentType::canonicalKey($storedType, $storedCustom);
    $invoiceTypeOptions = $invoiceTypeOptions ?? \App\Models\SeriesMaster::invoiceTypeOptions();
    $domesticKeys = array_keys(\App\Support\DocumentType::SALES_TYPES_BY_MARKET[\App\Support\InvoiceType::DOMESTIC]);
    $internationalKeys = array_keys(\App\Support\DocumentType::SALES_TYPES_BY_MARKET[\App\Support\InvoiceType::INTERNATIONAL]);
    $marketOptionCatalog = [];
    foreach (\App\Support\DocumentType::SALES_TYPES_BY_MARKET as $mKey => $typeList) {
        foreach ($typeList as $typeKey => $displayLabel) {
            if (!isset($marketOptionCatalog[$typeKey])) {
                $marketOptionCatalog[$typeKey] = [
                    'key' => $typeKey,
                    'label' => $displayLabel,
                    'code' => \App\Support\DocumentType::seriesCode($typeKey),
                    'markets' => [],
                ];
            }
            $marketOptionCatalog[$typeKey]['markets'][] = $mKey;
        }
    }
    foreach ($invoiceTypeOptions as $option) {
        $optCanonical = \App\Support\DocumentType::canonicalKey($option['name']);
        if ($optCanonical && isset($marketOptionCatalog[$optCanonical])) {
            if ($marketOptionCatalog[$optCanonical]['code'] === '' && !empty($option['code'])) {
                $marketOptionCatalog[$optCanonical]['code'] = $option['code'];
            }
            continue;
        }
        $customOptKey = 'series:' . strtolower($option['name']);
        $marketOptionCatalog[$customOptKey] = [
            'key' => $option['name'],
            'label' => $option['name'],
            'code' => $option['code'] ?? '',
            'markets' => [\App\Support\InvoiceType::DOMESTIC, \App\Support\InvoiceType::INTERNATIONAL],
        ];
    }
    $documentTypeIsCustom = (string) $storedType === '__not_in_list__';
    $selectedOptionValue = '';
    if (!$documentTypeIsCustom) {
        if ($canonicalSelectedKey && isset($marketOptionCatalog[$canonicalSelectedKey])) {
            $selectedOptionValue = $canonicalSelectedKey;
        } else {
            foreach ($marketOptionCatalog as $optItem) {
                if (strcasecmp((string) $optItem['key'], (string) $storedType) === 0 || strcasecmp((string) $optItem['label'], (string) $storedType) === 0) {
                    $selectedOptionValue = $optItem['key'];
                    break;
                }
            }
        }
        if ($selectedOptionValue === '' && (trim((string) $storedType) !== '' || trim((string) $storedCustom) !== '')) {
            $documentTypeIsCustom = true;
            if ($storedCustom === '') {
                $storedCustom = $storedType;
            }
        }
    }
@endphp
<label for="document-type">{{ translate('Invoice Type') }} @if (!empty($documentTypeRequired))<span class="text-danger">*</span>@endif</label>
<select class="form-control" name="document_type" id="document-type"
    data-default-domestic="{{ $entryKey === 'sales_return' ? 'sales_return' : \App\Support\DocumentType::DEFAULT_SALES_TYPE_BY_MARKET[\App\Support\InvoiceType::DOMESTIC] }}"
    data-default-international="{{ $entryKey === 'sales_return' ? 'sales_return' : \App\Support\DocumentType::DEFAULT_SALES_TYPE_BY_MARKET[\App\Support\InvoiceType::INTERNATIONAL] }}"
    @if (!empty($documentTypeRequired)) required @endif>
    <option value="">{{ translate('Select Invoice Type') }}</option>
    @foreach ($marketOptionCatalog as $optItem)
        @php
            $inDomestic = in_array(\App\Support\InvoiceType::DOMESTIC, $optItem['markets'], true);
            $inInternational = in_array(\App\Support\InvoiceType::INTERNATIONAL, $optItem['markets'], true);
        @endphp
        <option value="{{ $optItem['key'] }}"
            data-code="{{ $optItem['code'] }}"
            data-canonical="{{ \App\Support\DocumentType::canonicalKey($optItem['key']) ?: '' }}"
            data-domestic="{{ $inDomestic ? '1' : '0' }}"
            data-international="{{ $inInternational ? '1' : '0' }}"
            @selected(!$documentTypeIsCustom && strcasecmp((string) $selectedOptionValue, (string) $optItem['key']) === 0)>{{ translate($optItem['label']) }}</option>
    @endforeach
    <option value="__not_in_list__" data-domestic="1" data-international="1" @selected($documentTypeIsCustom)>{{ translate('Not in List') }}</option>
</select>
<input type="text" class="form-control mt-2 {{ $documentTypeIsCustom ? '' : 'd-none' }}" name="document_type_custom" id="document-type-custom" value="{{ $documentTypeIsCustom ? $storedCustom : '' }}" placeholder="{{ translate('Enter invoice type') }}" maxlength="255">
@error('document_type') <div class="text-danger small">{{ $message }}</div> @enderror
@error('document_type_custom') <div class="text-danger small">{{ $message }}</div> @enderror
<script>
    (function () {
        var select = document.getElementById('document-type');
        var input = document.getElementById('document-type-custom');
        if (!select || !input || select.dataset.bound === '1') {
            return;
        }
        select.dataset.bound = '1';
        function sync() {
            var show = select.value === '__not_in_list__';
            input.classList.toggle('d-none', !show);
            input.required = show && select.required;
            if (!show) {
                input.value = '';
            }
        }
        select.addEventListener('change', sync);
        sync();
    })();
</script>
