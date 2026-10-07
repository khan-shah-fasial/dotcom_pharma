@php
    $currentOrder = $order ?? null;
    $storedType = old('document_type', $currentOrder->document_type ?? '');
    $storedCustom = old('document_type_custom', $currentOrder->document_type_custom ?? '');
    $invoiceTypeOptions = $invoiceTypeOptions ?? \App\Models\SeriesMaster::invoiceTypeOptions();
    $documentTypeIsCustom = (string) $storedType === '__not_in_list__';
    $selectedInvoiceName = '';
    if (!$documentTypeIsCustom) {
        $label = \App\Support\DocumentType::label($storedType, $storedCustom);
        if ($label === '—') {
            $label = trim((string) $storedType);
        }
        foreach ($invoiceTypeOptions as $option) {
            if (strcasecmp($option['name'], $label) === 0 || strcasecmp($option['name'], (string) $storedType) === 0) {
                $selectedInvoiceName = $option['name'];
                break;
            }
        }
        if ($selectedInvoiceName === '' && (trim((string) $storedType) !== '' || trim((string) $storedCustom) !== '')) {
            $documentTypeIsCustom = true;
        }
    }
@endphp
<label for="document-type">{{ translate('Invoice Type') }} @if (!empty($documentTypeRequired))<span class="text-danger">*</span>@endif</label>
<select class="form-control" name="document_type" id="document-type" @if (!empty($documentTypeRequired)) required @endif>
    <option value="">{{ translate('Select Invoice Type') }}</option>
    @foreach ($invoiceTypeOptions as $option)
        <option value="{{ $option['name'] }}" data-code="{{ $option['code'] }}" @selected(!$documentTypeIsCustom && strcasecmp($selectedInvoiceName, $option['name']) === 0)>{{ $option['name'] }}</option>
    @endforeach
    <option value="__not_in_list__" @selected($documentTypeIsCustom)>{{ translate('Not in List') }}</option>
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
