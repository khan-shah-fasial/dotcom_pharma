<script type="text/javascript">
(function () {
    var epsilon = 0.01;
    var hsnTimer = null;
    var codeTimer = null;

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

    function zeroKind() {
        return kind() === 'exempted' || kind() === 'lut';
    }

    function sameAsPurchase() {
        return $('input[name="sale_same_as_purchase"]:checked').val() === '1';
    }

    function setSameAsPurchase(same) {
        $('#sale_same_as_purchase_y').prop('checked', same);
        $('#sale_same_as_purchase_n').prop('checked', !same);
    }

    function halfSplit(tax) {
        var first = round4(tax / 2);
        return [first, round4(tax - first)];
    }

    function setField(selector, value, locked) {
        $(selector).val(formatRate(value)).prop('readonly', !!locked);
    }

    function utSum(side) {
        return round4(numberValue('#' + side + '_ut_cgst') + numberValue('#' + side + '_utgst'));
    }

    function refreshUt(side, tax) {
        var balanced = Math.abs(tax - utSum(side)) <= epsilon;
        $('#' + side + '_ut_cgst, #' + side + '_utgst').toggleClass('is-invalid', !balanced);
        $('.tm-mismatch[data-side="' + side + '"]').toggleClass('d-none', balanced);
        return balanced;
    }

    function applyBreakup(side, tax, keepUt) {
        var state = halfSplit(tax);
        setField('#' + side + '_tax', tax, true);
        setField('#' + side + '_cgst', state[0], true);
        setField('#' + side + '_sgst', state[1], true);
        setField('#' + side + '_igst', tax, true);
        if (!keepUt || (utSum(side) === 0 && tax > 0)) {
            setField('#' + side + '_ut_cgst', state[0], false);
            setField('#' + side + '_utgst', state[1], false);
        }
        return refreshUt(side, tax);
    }

    function copyPurchaseToSale() {
        ['tax', 'cgst', 'sgst', 'ut_cgst', 'utgst', 'igst'].forEach(function (part) {
            $('#sale_' + part).val($('#purchase_' + part).val());
        });
        refreshUt('sale', numberValue('#sale_tax'));
    }

    function lockSale(locked) {
        $('#sale_ut_cgst, #sale_utgst').prop('readonly', locked);
    }

    function suggestCode() {
        if ($('#tax_code').data('auto') !== 1 && $('#tax_code').data('auto') !== '1') {
            return;
        }
        if (zeroKind() || numberValue('#tax_percent') <= 0) {
            return;
        }
        clearTimeout(codeTimer);
        codeTimer = setTimeout(function () {
            $.get('{{ route('tax_masters.suggest_code') }}', {
                tax: numberValue('#tax_percent'),
                ignore_id: $('#tax_code').data('ignore-id') || ''
            }).done(function (response) {
                if (($('#tax_code').data('auto') === 1 || $('#tax_code').data('auto') === '1') && response.tax_code) {
                    $('#tax_code').val(response.tax_code);
                }
            });
        }, 200);
    }

    function syncForm() {
        if (zeroKind()) {
            $('#tax_percent').val('0').prop('readonly', true);
            applyBreakup('purchase', 0, false);
            setSameAsPurchase(true);
            copyPurchaseToSale();
            lockSale(true);
            $('#sale_same_as_purchase_y, #sale_same_as_purchase_n').prop('disabled', true);
            return;
        }

        $('#tax_percent').prop('readonly', false);
        $('#sale_same_as_purchase_y, #sale_same_as_purchase_n').prop('disabled', false);
        var tax = numberValue('#tax_percent');
        applyBreakup('purchase', tax, true);
        if (sameAsPurchase()) {
            copyPurchaseToSale();
            lockSale(true);
            $('#sale_tax').prop('readonly', true);
        } else {
            var saleTax = numberValue('#sale_tax');
            applyBreakup('sale', saleTax, true);
            lockSale(false);
            $('#sale_tax').prop('readonly', false);
        }
    }

    $('#kind').on('change', function () {
        syncForm();
        suggestCode();
    });

    $('input[name="sale_same_as_purchase"]').on('change', syncForm);

    $('#tax_percent').on('input change', function () {
        applyBreakup('purchase', numberValue('#tax_percent'), false);
        if (sameAsPurchase()) {
            copyPurchaseToSale();
        }
        suggestCode();
    });

    $('#sale_tax').on('input change', function () {
        if (!sameAsPurchase() && !zeroKind()) {
            applyBreakup('sale', numberValue('#sale_tax'), false);
            $('#sale_tax').prop('readonly', false);
        }
    });

    $(document).on('input change', '#purchase_ut_cgst, #purchase_utgst', function () {
        refreshUt('purchase', numberValue('#purchase_tax'));
        if (sameAsPurchase()) {
            copyPurchaseToSale();
        }
    });

    $(document).on('input change', '#sale_ut_cgst, #sale_utgst', function () {
        if (!sameAsPurchase() && !zeroKind()) {
            refreshUt('sale', numberValue('#sale_tax'));
        }
    });

    $('#tax_code').on('input', function () {
        $(this).data('auto', 0);
    });

    function hsFromHsn(value) {
        var digits = String(value || '').replace(/\D/g, '');
        if (!digits) {
            return '';
        }
        return digits.length >= 6 ? digits.substring(0, 6) : digits;
    }

    function hideHsnResults() {
        $('#hsn-results').attr('hidden', true).empty();
    }

    $('#hsn_code').on('input', function () {
        var query = $(this).val();
        $('#hs_code').val(hsFromHsn(query));
        clearTimeout(hsnTimer);
        if (!query || query.length < 2) {
            hideHsnResults();
            return;
        }
        hsnTimer = setTimeout(function () {
            $.get('{{ route('tax_masters.hsn_search') }}', { q: query }).done(function (rows) {
                var $box = $('#hsn-results').empty();
                if (!rows || !rows.length) {
                    $box.attr('hidden', true);
                    return;
                }
                rows.forEach(function (row) {
                    var button = $('<button type="button"></button>');
                    button.text(row.code + ' — ' + row.description);
                    button.data('row', row);
                    $box.append(button);
                });
                $box.removeAttr('hidden');
            });
        }, 250);
    });

    $('#hsn-results').on('mousedown', 'button', function (event) {
        event.preventDefault();
        var row = $(this).data('row');
        $('#hsn_code').val(row.code);
        $('#hs_code').val(row.hs_code || hsFromHsn(row.code));
        $('#description').val((row.description || '').slice(0, 255));
        hideHsnResults();
    });

    $(document).on('click', function (event) {
        if (!$(event.target).closest('.tm-hsn-wrap').length) {
            hideHsnResults();
        }
    });

    $('#tax-master-form').on('submit', function (event) {
        syncForm();
        $('#sale_tax').prop('readonly', false);
        var purchaseOk = refreshUt('purchase', numberValue('#purchase_tax'));
        var saleOk = refreshUt('sale', numberValue('#sale_tax'));
        if (!purchaseOk || !saleOk) {
            event.preventDefault();
            AIZ.plugins.notify('danger', '{{ translate('UT CGST % + UTGST % must equal Tax %.') }}');
        }
    });

    syncForm();
    if (!$('#tax_code').val()) {
        $('#tax_code').data('auto', 1);
        suggestCode();
    }
})();
</script>
