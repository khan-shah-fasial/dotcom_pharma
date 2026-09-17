<script type="text/javascript">
(function () {
    var epsilon = 0.01;

    function numberValue(selector) {
        var raw = $(selector).val();
        if (raw === '' || raw === null || typeof raw === 'undefined') {
            return 0;
        }
        var value = parseFloat(raw);
        return isNaN(value) ? 0 : value;
    }

    function round4(value) {
        return Math.round((value + Number.EPSILON) * 10000) / 10000;
    }

    function formatRate(value) {
        var rounded = round4(value);
        if (Math.abs(rounded) < 0.0000001) {
            return '0';
        }
        return String(rounded);
    }

    function kind() {
        return $('#kind').val();
    }

    function sameAsPurchase() {
        return $('input[name="sale_same_as_purchase"]:checked').val() === '1';
    }

    function setSameAsPurchase(same) {
        $('#sale_same_as_purchase_y').prop('checked', same);
        $('#sale_same_as_purchase_n').prop('checked', !same);
    }

    function splitTotal(side) {
        return round4(
            numberValue('#' + side + '_cgst') +
            numberValue('#' + side + '_sgst') +
            numberValue('#' + side + '_igst')
        );
    }

    function setRateRow(side, tax, cgst, sgst, igst) {
        $('#' + side + '_tax').val(formatRate(tax));
        $('#' + side + '_cgst').val(formatRate(cgst));
        $('#' + side + '_sgst').val(formatRate(sgst));
        $('#' + side + '_igst').val(formatRate(igst));
        $('#' + side + '_total').val(formatRate(round4(cgst + sgst + igst)));
    }

    function refreshTotal(side) {
        var total = splitTotal(side);
        $('#' + side + '_total').val(formatRate(total));
        var $tax = $('#' + side + '_tax');
        if ($tax.val() === '' || $tax.val() === null) {
            $tax.val(formatRate(total));
        }
        var tax = numberValue('#' + side + '_tax');
        var balanced = Math.abs(tax - total) <= epsilon;
        $tax.toggleClass('is-invalid', !balanced);
        $('#' + side + '_total').toggleClass('is-invalid', !balanced);
        return balanced;
    }

    function copyPurchaseToSale() {
        setRateRow(
            'sale',
            numberValue('#purchase_tax'),
            numberValue('#purchase_cgst'),
            numberValue('#purchase_sgst'),
            numberValue('#purchase_igst')
        );
    }

    function setSaleDisabled(disabled) {
        $('#sale_tax, #sale_cgst, #sale_sgst, #sale_igst').prop('disabled', disabled);
    }

    function setSplitsDisabled(disabled) {
        $('#purchase_tax, #purchase_cgst, #purchase_sgst, #purchase_igst, #sale_tax, #sale_cgst, #sale_sgst, #sale_igst')
            .prop('disabled', disabled);
    }

    function syncForm() {
        if (kind() === 'exempted') {
            setRateRow('purchase', 0, 0, 0, 0);
            setSameAsPurchase(true);
            copyPurchaseToSale();
            setSplitsDisabled(true);
            return;
        }

        setSplitsDisabled(false);
        refreshTotal('purchase');

        if (sameAsPurchase()) {
            copyPurchaseToSale();
            setSaleDisabled(true);
        } else {
            setSaleDisabled(false);
            refreshTotal('sale');
        }
    }

    $(document).on('change', '#kind, input[name="sale_same_as_purchase"]', syncForm);
    $(document).on('input change', '#purchase_tax, #purchase_cgst, #purchase_sgst, #purchase_igst', function () {
        refreshTotal('purchase');
        if (kind() !== 'exempted' && sameAsPurchase()) {
            copyPurchaseToSale();
        }
    });
    $(document).on('input change', '#sale_tax, #sale_cgst, #sale_sgst, #sale_igst', function () {
        if (!sameAsPurchase() && kind() !== 'exempted') {
            refreshTotal('sale');
        }
    });

    $('#tax-master-form').on('submit', function (event) {
        syncForm();
        $('#sale_tax, #sale_cgst, #sale_sgst, #sale_igst, #purchase_tax, #purchase_cgst, #purchase_sgst, #purchase_igst')
            .prop('disabled', false);

        var purchaseOk = refreshTotal('purchase');
        var saleOk = refreshTotal('sale');
        if (!purchaseOk || !saleOk) {
            event.preventDefault();
            AIZ.plugins.notify('danger', '{{ translate('Tax % must equal CGST + SGST + IGST.') }}');
        }
    });

    syncForm();
})();
</script>
