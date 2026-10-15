<script type="text/javascript">
    function syncHsnTaxable() {
        var notTaxable = $('#taxable').val() === '0';
        if (notTaxable) {
            $('#tax_percent').val('0').prop('readonly', true);
        } else {
            $('#tax_percent').prop('readonly', false);
        }
    }

    $('#taxable').on('change', syncHsnTaxable);
    syncHsnTaxable();
</script>
