<div class="form-group">
    <label>{{ translate('Name') }} <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $seriesItem->name ?? '') }}" required maxlength="255">
    @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
</div>
<div class="form-group">
    <label>{{ translate('Code') }}</label>
    <input type="text" name="code" class="form-control" value="{{ old('code', $seriesItem->code ?? '') }}" maxlength="100">
    @error('code') <span class="text-danger small">{{ $message }}</span> @enderror
</div>
@php
    $storedPaymentType = (string) ($seriesItem->payment_type ?? '');
    $storedPaymentTypeCustom = (string) ($seriesItem->payment_type_custom ?? '');
    $submittedPaymentType = old('payment_type');
    if ($submittedPaymentType !== null) {
        $paymentTypeValue = (string) $submittedPaymentType;
        $paymentTypeManual = (string) old('payment_type_manual', '');
    } elseif ($storedPaymentTypeCustom !== '' || ($storedPaymentType !== '' && !array_key_exists($storedPaymentType, \App\Models\SeriesMaster::PAYMENT_TYPES))) {
        $paymentTypeValue = '__not_in_list__';
        $paymentTypeManual = $storedPaymentTypeCustom !== '' ? $storedPaymentTypeCustom : $storedPaymentType;
    } else {
        $paymentTypeValue = $storedPaymentType;
        $paymentTypeManual = '';
    }
    $paymentTypeIsManual = $paymentTypeValue === '__not_in_list__';
@endphp
<div class="form-group">
    <label for="series-payment-type">{{ translate('Payment Type') }}</label>
    <select class="form-control" name="payment_type" id="series-payment-type">
        <option value="">{{ translate('Select Payment Type') }}</option>
        @foreach (\App\Models\SeriesMaster::PAYMENT_TYPES as $value => $label)
            <option value="{{ $value }}" @selected($paymentTypeValue === $value)>{{ translate($label) }}</option>
        @endforeach
        <option value="__not_in_list__" @selected($paymentTypeIsManual)>{{ translate('Not in List') }}</option>
    </select>
    <div id="series-payment-type-manual-wrap" class="mt-2 {{ $paymentTypeIsManual ? '' : 'd-none' }}">
        <input type="text" class="form-control" name="payment_type_manual" id="series-payment-type-manual" value="{{ $paymentTypeManual }}" placeholder="{{ translate('Add payment type manually') }}" maxlength="100">
    </div>
    @error('payment_type') <span class="text-danger small">{{ $message }}</span> @enderror
    @error('payment_type_manual') <span class="text-danger small">{{ $message }}</span> @enderror
</div>
<div class="form-group">
    <label for="series-total-bills">{{ translate('Total No of Bills') }}</label>
    <input type="number" min="0" step="1" class="form-control" name="total_bills" id="series-total-bills" value="{{ old('total_bills', $seriesItem->total_bills ?? '') }}">
    @error('total_bills') <span class="text-danger small">{{ $message }}</span> @enderror
</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="series-from-bill-no">{{ translate('From Bill No.') }}</label>
        <input type="text" class="form-control" name="from_bill_no" id="series-from-bill-no" value="{{ old('from_bill_no', $seriesItem->from_bill_no ?? '') }}" maxlength="50">
        @error('from_bill_no') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <div class="form-group col-md-6">
        <label for="series-to-bill-no">{{ translate('To Bill No.') }}</label>
        <input type="text" class="form-control" name="to_bill_no" id="series-to-bill-no" value="{{ old('to_bill_no', $seriesItem->to_bill_no ?? '') }}" maxlength="50">
        @error('to_bill_no') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group">
    <label>{{ translate('Description') }}</label>
    <textarea name="description" rows="4" class="form-control">{{ old('description', $seriesItem->description ?? '') }}</textarea>
    @error('description') <span class="text-danger small">{{ $message }}</span> @enderror
</div>
<div class="form-group">
    <label class="aiz-switch aiz-switch-success mb-0">
        <input type="checkbox" name="status" value="1" @checked(old('status', $seriesItem->status ?? 1))>
        <span class="slider round"></span>
    </label>
    <span class="ml-2">{{ translate('Active') }}</span>
</div>
<div class="form-group mb-0 text-right">
    <a href="{{ route('series.index') }}" class="btn btn-soft-secondary">{{ translate('Back') }}</a>
    <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
</div>
<script>
    (function () {
        var select = document.getElementById('series-payment-type');
        var wrap = document.getElementById('series-payment-type-manual-wrap');
        var input = document.getElementById('series-payment-type-manual');
        if (!select || !wrap || !input || select.dataset.bound === '1') {
            return;
        }
        select.dataset.bound = '1';
        function sync() {
            var show = select.value === '__not_in_list__';
            wrap.classList.toggle('d-none', !show);
            input.required = show;
            input.disabled = !show;
        }
        select.addEventListener('change', sync);
        sync();
    })();
</script>
