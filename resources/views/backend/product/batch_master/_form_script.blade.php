<script type="text/javascript">
(function () {
    var lookupUrl = '{{ route('batch_masters.lookup.stocks') }}';
    var stockUrl = '{{ route('batch_masters.lookup.stock') }}';
    var revealUrl = '{{ route('batch_masters.reveal_rate') }}';
    var searchTimer = null;
    var isEdit = {{ isset($batch) && $batch ? 'true' : 'false' }};
    var showPurchaseRate = {{ !empty($showPurchaseRate) ? 'true' : 'false' }};
    var companyOptions = $('#batch-row-template .company-select').html() || '';

    function dash(value) {
        return (value === null || value === undefined || value === '') ? '—' : value;
    }

    function amount(qty, rate) {
        if (rate === null || rate === undefined || rate === '') {
            return '';
        }
        return (parseFloat(qty || 0) * parseFloat(rate)).toFixed(4);
    }

    function refreshAmounts() {
        $('#batch-rows-body tr').each(function () {
            var qty = $(this).find('.qty-input').val();
            var rate = $(this).find('.rate-input').val();
            $(this).find('.amount-output').val(amount(qty, rate));
        });
    }

    function renumber() {
        $('#batch-rows-body tr').each(function (index) {
            $(this).find('.sr-no').text(index + 1);
            $(this).find('[data-name], [name]').each(function () {
                var key = $(this).attr('data-name');
                if (!key) {
                    return;
                }
                $(this).attr('name', 'rows[' + index + '][' + key + ']');
            });
        });
    }

    function purchaseCode(dateValue) {
        if (!dateValue) {
            return '';
        }
        var text = String(dateValue).substring(0, 10);
        var parts = text.split('-');
        if (parts.length === 3 && parts[0].length === 4) {
            return parts[0] + parts[1] + parts[2];
        }
        var dmy = text.split('-');
        if (dmy.length === 3 && dmy[2] && dmy[2].length === 4) {
            return dmy[2] + dmy[1] + dmy[0];
        }
        return '';
    }

    function applyNonBatch() {
        var checked = $('#is_non_batch').is(':checked');
        try {
            sessionStorage.setItem('bm_non_batch', checked ? '1' : '0');
        } catch (e) {}
        if (!checked) {
            return;
        }
        var code = purchaseCode($('#purchase_date').val());
        var month = $('#purchase_date').val() ? String($('#purchase_date').val()).substring(0, 7) : '';
        $('#batch-rows-body tr').each(function () {
            var $code = $(this).find('.batch-code');
            if (!$code.val() && code) {
                $code.val(code);
            }
            var $mfg = $(this).find('.mfg-input');
            if (month && !$mfg.val()) {
                $mfg.val(month);
            }
        });
    }

    function fillRow($tr, row) {
        var map = {
            id: row.id || '',
            batch_code: row.batch_code || '',
            manufacturing_date: (row.manufacturing_date || '').toString().substring(0, 7),
            expiry_date: (row.expiry_date || '').toString().substring(0, 7),
            qty: row.qty ?? 0,
            free_qty: row.free_qty ?? '',
            mrp_price: row.mrp_price ?? '',
            purchase_rate: row.purchase_rate ?? '',
            tax_percent: row.tax_percent ?? '',
            tax_code: row.tax_code || '',
            scheme: row.scheme ?? '',
            company_id: row.company_id || '',
            coa: row.coa || '',
            source_coa: row.source_coa || '',
            source_purchase_history_id: row.source_purchase_history_id || '',
            source_product_batch_id: row.source_product_batch_id || '',
            batch_discount_percent: row.batch_discount_percent ?? '',
            product_discount_percent: row.product_discount_percent ?? '',
            scheme_discount_percent: row.scheme_discount_percent ?? '',
            price_pts: row.price_pts ?? '',
            price_ptr: row.price_ptr ?? '',
            price_ptd: row.price_ptd ?? '',
            price_gov: row.price_gov ?? '',
            price_expo: row.price_expo ?? '',
            price_customer: row.price_customer ?? ''
        };
        Object.keys(map).forEach(function (key) {
            var $input = $tr.find('[data-name="' + key + '"], [name$="[' + key + ']"], [name="' + key + '"]');
            if ($input.length && key !== 'company_id') {
                $input.val(map[key]);
            }
        });
        $tr.find('select').val(map.company_id || '');
        if (row.id) {
            if (!$tr.find('input[name$="[id]"]').length) {
                $tr.find('td').eq(1).prepend('<input type="hidden" data-name="id" value="' + row.id + '">');
            }
        }
    }

    function addRow(row) {
        var $tr = $($('#batch-row-template').html());
        if (companyOptions) {
            $tr.find('.company-select').html(companyOptions);
        }
        $('#batch-rows-body').append($tr);
        if (row) {
            fillRow($tr, row);
        }
        renumber();
        refreshAmounts();
    }

    function renderLots(lots) {
        var $body = $('#live-lots-body').empty();
        var $values = $('#live-values-body').empty();
        (lots || []).forEach(function (lot) {
            var coa = lot.coa_url ? '<a href="' + lot.coa_url + '" target="_blank">{{ translate('Zoom') }}</a>' : '—';
            var prateCell = showPurchaseRate ? dash(lot.purchase_rate) : '••••';
            $body.append('<tr><td>' + dash(lot.batch) + '</td><td>' + dash(lot.manufacturing_date) + '</td><td>' + dash(lot.expiry_date) + '</td><td>' + dash(lot.qty) + '</td><td>' + dash(lot.scheme) + '</td><td>' + dash(lot.mrp_price) + '</td><td class="bm-prate">' + prateCell + '</td><td>' + dash(lot.tax_percent) + '</td><td>' + dash(lot.amount) + '</td><td>' + dash(lot.company_id) + '</td><td>' + coa + '</td><td>' + dash(lot.upload_date) + '</td><td>' + (lot.status ? '{{ translate('On') }}' : '{{ translate('Off') }}') + '</td></tr>');
            var values = lot.values || {};
            var discounts = lot.discounts || {};
            var prateValue = showPurchaseRate ? dash(values.prate) : '••••';
            $values.append('<tr><td class="bm-prate">' + prateValue + '</td><td>' + dash(values.pts) + '</td><td>' + dash(values.ptr) + '</td><td>' + dash(values.ptd) + '</td><td>' + dash(values.gov) + '</td><td>' + dash(values.expo) + '</td><td>' + dash(values.customer) + '</td><td>' + dash(discounts.batchwise) + '</td><td>' + dash(discounts.productwise) + '</td><td>' + dash(discounts.schemewise) + '</td></tr>');
        });
    }

    function selectIds(selector, ids) {
        var values = (ids || []).map(String);
        $(selector + ' option').each(function () {
            $(this).prop('selected', values.indexOf(String($(this).val())) !== -1);
        });
    }

    function selectStock(item) {
        $('#product_id').val(item.product_id);
        $('#product_stock_id').val(item.id);
        $('#sku_search').val(item.label);
        $('#sku_display').val(item.sku || '');
        $('#variant_display').val(item.variant || '');
        $('#sku_results').empty();
        $.get(stockUrl, { product_stock_id: item.id }, function (data) {
            if (!data || !data.found) {
                return;
            }
            $('#drug_name').val(data.drug_name || '');
            $('#drug_name_label').text(data.drug_name || '');
            $('#marketed_by_id').val(data.marketed_by_id || '');
            $('#marketed_by_name').val(data.marketed_by_name || '');
            $('#purchase_date').val(data.purchase_date || '');
            selectIds('#import_by_ids', data.import_by_ids);
            selectIds('#manufactured_by_ids', data.manufactured_by_ids);
            $('#import_by_names').val(data.import_by_names || '');
            $('#manufactured_by_names').val(data.manufactured_by_names || '');
            if (!isEdit) {
                $('#batch-rows-body').empty();
                var rows = data.rows || [];
                if (!rows.length) {
                    addRow(null);
                }
                rows.forEach(function (row) {
                    addRow(row);
                });
                applyNonBatch();
            }
            renderLots(data.live_lots || []);
            $('#live-lots-wrap').removeClass('d-none');
        });
    }

    if (!isEdit) {
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

        $('#add-batch-row').on('click', function () {
            addRow(null);
            applyNonBatch();
        });

        try {
            if (sessionStorage.getItem('bm_non_batch') === '1' && !$('#is_non_batch').is(':checked')) {
                $('#is_non_batch').prop('checked', true);
            }
        } catch (e) {}
    }

    $('#is_non_batch').on('change', applyNonBatch);
    $('#batch-rows-body').on('input', '.qty-input, .rate-input', refreshAmounts);
    $('#toggle-live-lots').on('click', function () {
        $('#live-lots-wrap').toggleClass('d-none');
    });
    $('#reveal-prate').on('click', function () {
        $('#reveal-prate-box').removeClass('d-none');
    });
    $('#reveal-prate-submit').on('click', function () {
        $.post(revealUrl, {
            _token: '{{ csrf_token() }}',
            password: $('#reveal-prate-password').val()
        }).done(function () {
            showPurchaseRate = true;
            if ($('#product_stock_id').val()) {
                $.get(stockUrl, { product_stock_id: $('#product_stock_id').val() }, function (data) {
                    renderLots((data && data.live_lots) || []);
                });
            }
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ translate('Password does not match.') }}';
            AIZ.plugins.notify('danger', message);
        });
    });

    refreshAmounts();
    if (!isEdit) {
        renumber();
    }
    if ($('#product_stock_id').val()) {
        $.get(stockUrl, { product_stock_id: $('#product_stock_id').val() }, function (data) {
            if (data && data.found) {
                renderLots(data.live_lots || []);
            }
        });
    }
})();
</script>
