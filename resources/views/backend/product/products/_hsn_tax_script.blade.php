<script type="text/javascript">
(function () {
    var lookupTimer = null;

    function fillTaxMaster(row, overwriteHs) {
        if (!row || !row.found) {
            $('#product_tax_type_display, #product_tax_percent_display, #product_tax_code_display').val('');
            $('#product_tax_description_display').text('');
            return;
        }
        if (overwriteHs || !$('#product_hs').val()) {
            $('#product_hs').val(row.hs_code || '');
        }
        $('#product_tax_type_display').val(row.kind_label || '');
        $('#product_tax_percent_display').val(row.display_tax_percent || '');
        $('#product_tax_code_display').val(row.tax_code || '');
        $('#product_tax_description_display').text(row.description || '');
    }

    function lookupHsn(overwriteHs) {
        var code = $('#product_hsn').val();
        if (!code) {
            fillTaxMaster(null, false);
            return;
        }
        $.get('{{ route('products.tax_master_lookup') }}', { hsn: code }).done(function (row) {
            fillTaxMaster(row, overwriteHs);
        });
    }

    $('#product_hsn').on('input', function () {
        clearTimeout(lookupTimer);
        lookupTimer = setTimeout(function () {
            lookupHsn(true);
        }, 300);
    });

    if ($('#product_hsn').val()) {
        lookupHsn(false);
    }
})();
</script>
