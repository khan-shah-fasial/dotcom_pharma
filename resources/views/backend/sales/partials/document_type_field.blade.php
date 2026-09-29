@php
    $currentOrder = $order ?? null;
    $documentTypeValue = old('document_type', $currentOrder->document_type ?? '');
    $documentTypeCustom = old('document_type_custom', $currentOrder->document_type_custom ?? '');
    $documentTypes = $documentTypes ?? \App\Support\DocumentType::TYPES;
    $knownDocumentType = array_key_exists((string) $documentTypeValue, $documentTypes);
    $documentTypeIsCustom = $documentTypeValue === '__not_in_list__'
        || ($documentTypeCustom !== '' && $documentTypeCustom !== null && !$knownDocumentType);
    if ($documentTypeIsCustom) {
        $documentTypeValue = '__not_in_list__';
    } elseif ($documentTypeValue === '' && count($documentTypes) === 1) {
        $documentTypeValue = array_key_first($documentTypes);
    }
@endphp
<label for="document-type">{{ translate('Invoice Type') }} @if (!empty($documentTypeRequired))<span class="text-danger">*</span>@endif</label>
<select class="form-control" name="document_type" id="document-type" @if (!empty($documentTypeRequired)) required @endif>
    <option value="">{{ translate('Select Invoice Type') }}</option>
    @foreach ($documentTypes as $value => $label)
        <option value="{{ $value }}" @selected((string) $documentTypeValue === (string) $value)>{{ translate($label) }}</option>
    @endforeach
    <option value="__not_in_list__" @selected($documentTypeIsCustom)>{{ translate('Not in List') }}</option>
</select>
<input type="text" class="form-control mt-2 {{ $documentTypeIsCustom ? '' : 'd-none' }}" name="document_type_custom" id="document-type-custom" value="{{ $documentTypeIsCustom ? $documentTypeCustom : '' }}" placeholder="{{ translate('Enter invoice type') }}" maxlength="255">
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
