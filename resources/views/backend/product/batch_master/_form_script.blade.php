<script type="text/javascript">
(function () {
    var lookupUrl = '{{ route('batch_masters.lookup.stocks') }}';
    var stockUrl = '{{ route('batch_masters.lookup.stock') }}';
    var searchTimer = null;

    function toggleNonBatch() {
        var nonBatch = $('#is_non_batch').is(':checked');
        $('.bm-date-field').toggleClass('d-none', nonBatch);
        if (nonBatch) {
            $('#manufacturing_date').val('');
            $('#expiry_date').val('');
            if (!$('#batch_code').val()) {
                $('#batch_code').val('-');
            }
        }
    }

    function renderLots(lots) {
        var $wrap = $('#live-lots-wrap');
        var $body = $('#live-lots-body');
        $body.empty();
        if (!lots || !lots.length) {
            $wrap.addClass('d-none');
            return;
        }
        lots.forEach(function (lot) {
            $body.append(
                '<tr><td>' + (lot.batch || '—') + '</td><td>' + (lot.manufacturing_date || '—') +
                '</td><td>' + (lot.expiry_date || '—') + '</td><td>' + (lot.qty ?? '—') +
                '</td><td>' + (lot.mrp_price ?? '—') + '</td></tr>'
            );
        });
        $wrap.removeClass('d-none');
    }

    function selectStock(item) {
        $('#product_id').val(item.product_id);
        $('#product_stock_id').val(item.id);
        $('#sku_search').val(item.label);
        $('#sku_display').val(item.sku || '');
        $('#variant_display').val(item.variant || '');
        $('#sku_results').empty();
        $.get(stockUrl, { product_stock_id: item.id }, function (data) {
            if (data && data.found) {
                renderLots(data.live_lots || []);
            }
        });
    }

    $('#sku_search').on('input', function () {
        var q = $(this).val();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            $.get(lookupUrl, { q: q }, function (rows) {
                var $box = $('#sku_results').empty();
                (rows || []).forEach(function (row) {
                    var $a = $('<a href="#" class="list-group-item list-group-item-action"></a>').text(row.label);
                    $a.on('click', function (e) {
                        e.preventDefault();
                        selectStock(row);
                    });
                    $box.append($a);
                });
            });
        }, 250);
    });

    $('#is_non_batch').on('change', toggleNonBatch);
    toggleNonBatch();

    if ($('#product_stock_id').val()) {
        $.get(stockUrl, { product_stock_id: $('#product_stock_id').val() }, function (data) {
            if (data && data.found) {
                renderLots(data.live_lots || []);
            }
        });
    }
})();
</script>
