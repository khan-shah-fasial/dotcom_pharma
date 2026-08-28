@php
    $orderPrefs = $details ?? null;
    $codEnabled = (bool) old('cash_on_delivery', $orderPrefs?->cash_on_delivery ?? true);
    $freeShippingEnabled = (bool) old('free_shipping', $orderPrefs?->free_shipping ?? false);
    $warrantyEnabled = (bool) old('has_warranty', $orderPrefs?->has_warranty ?? false);
    $refundableEnabled = (bool) old('refundable', $orderPrefs?->refundable ?? true);
@endphp

<div class="row customer-collapsible-section">
    <div class="col-md-12 customer-section-heading">
        <button type="button" class="customer-section-toggle" aria-expanded="true">
            <span>{{ translate('Shipping & Policy Configuration') }}</span>
            <i class="las la-angle-up customer-section-icon" aria-hidden="true"></i>
        </button>
    </div>

    @if (get_setting('cash_payment') == '1')
        <div class="col-md-3 mb-3">
            <label class="form-label d-block">{{ translate('Cash On Delivery') }}</label>
            <label class="aiz-switch aiz-switch-success mb-0">
                <input type="checkbox" name="cash_on_delivery" value="1" @checked($codEnabled)>
                <span></span>
            </label>
        </div>
    @endif

    <div class="col-md-3 mb-3">
        <label class="form-label d-block">{{ translate('Free Shipping') }}</label>
        <label class="aiz-switch aiz-switch-success mb-0">
            <input type="checkbox" name="free_shipping" value="1" @checked($freeShippingEnabled)>
            <span></span>
        </label>
    </div>

    <div class="col-md-3 mb-3">
        <label class="form-label d-block">{{ translate('Warranty') }}</label>
        <label class="aiz-switch aiz-switch-success mb-0">
            <input type="checkbox" name="has_warranty" value="1" @checked($warrantyEnabled)>
            <span></span>
        </label>
    </div>

    @if (addon_is_activated('refund_request'))
        <div class="col-md-3 mb-3">
            <label class="form-label d-block">{{ translate('Refundable') }}</label>
            <label class="aiz-switch aiz-switch-success mb-0">
                <input type="checkbox" name="refundable" value="1" @checked($refundableEnabled)>
                <span></span>
            </label>
        </div>
    @endif
</div>
