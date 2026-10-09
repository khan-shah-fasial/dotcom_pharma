<script type="text/javascript">
(function () {
    var lookupUrl = '{{ route('batch_masters.lookup.stocks') }}';
    var stockUrl = '{{ route('batch_masters.lookup.stock') }}';
    var revealUrl = '{{ route('batch_masters.reveal_rate') }}';
    var searchTimer = null;
    var lastPurchase = {};
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

    function purchaseMonth(dateValue) {
        if (!dateValue) {
            return '';
        }
        var text = String(dateValue).substring(0, 10);
        var parts = text.split('-');
        if (parts.length === 3 && parts[0].length === 4) {
            return parts[0] + '-' + parts[1];
        }
        if (parts.length === 3 && parts[2] && parts[2].length === 4) {
            var month = parts[1].length === 1 ? '0' + parts[1] : parts[1];
            return parts[2] + '-' + month;
        }
        return '';
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
            localStorage.setItem('bm_non_batch', checked ? '1' : '0');
        } catch (e) {}
        if (!checked) {
            return;
        }
        var code = purchaseCode($('#purchase_date').val());
        var month = purchaseMonth($('#purchase_date').val());
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
        } else if (lastPurchase && (lastPurchase.mrp_price || lastPurchase.purchase_rate)) {
            fillRow($tr, lastPurchase);
        }
        renumber();
        refreshAmounts();
        filterCompanySelects();
    }

    function pv(price, value, hide) {
        if (hide) {
            return '<div class="bm-pv"><div>{{ translate('Price') }}: ••••</div><div class="text-muted">{{ translate('Value') }}: ••••</div></div>';
        }
        return '<div class="bm-pv"><div>{{ translate('Price') }}: ' + dash(price) + '</div><div class="text-muted">{{ translate('Value') }}: ' + dash(value) + '</div></div>';
    }

    function selectedMfgIds() {
        var values = $('#manufactured_by_ids').val();
        if (!values) {
            return [];
        }
        return $.isArray(values) ? values.map(String) : [String(values)];
    }

    function filterCompanySelects() {
        var ids = selectedMfgIds();
        $('#batch-rows-body .company-select').each(function () {
            var $sel = $(this);
            var current = String($sel.val() || '');
            $sel.find('option').each(function () {
                var val = String($(this).val());
                if (val === '') {
                    return;
                }
                $(this).prop('hidden', ids.length > 0 && ids.indexOf(val) === -1);
            });
            if (ids.length === 1 && (!current || (ids.indexOf(current) === -1))) {
                $sel.val(ids[0]);
            }
        });
    }

    function renderLots(lots) {
        var $wrap = $('#live-lots-blocks').empty();
        (lots || []).forEach(function (lot) {
            var prices = lot.prices || {};
            var values = lot.values || {};
            var discounts = lot.discounts || {};
            var coa = lot.coa_url ? '<a href="#" class="bm-coa-zoom" data-src="' + lot.coa_url + '">{{ translate('Zoom') }}</a>' : '—';
            var $block = $('<div class="bm-live-lot"></div>');
            $block.append(
                '<div class="table-responsive"><table class="table table-sm table-bordered">' +
                '<thead><tr>' +
                '<th>{{ translate('Batch') }}</th><th>{{ translate('Mfg.') }}</th><th>{{ translate('Expiry') }}</th><th>{{ translate('Qty') }}</th>' +
                '<th>{{ translate('Scheme') }}</th><th>{{ translate('MRP') }}</th><th class="bm-prate">{{ translate('P-Rate') }}</th>' +
                '<th>{{ translate('Tax') }}</th><th>{{ translate('Amount') }}</th><th>{{ translate('Company Code') }}</th>' +
                '<th>{{ translate('COA Image') }}</th><th>{{ translate('Upload Date') }}</th><th>{{ translate('Status') }}</th>' +
                '</tr></thead><tbody><tr>' +
                '<td>' + dash(lot.batch) + '</td><td>' + dash(lot.manufacturing_date) + '</td><td>' + dash(lot.expiry_date) + '</td>' +
                '<td>' + dash(lot.qty) + '</td><td>' + dash(lot.scheme) + '</td><td>' + dash(lot.mrp_price) + '</td>' +
                '<td class="bm-prate">' + (showPurchaseRate ? dash(lot.purchase_rate) : '••••') + '</td>' +
                '<td>' + dash(lot.tax_percent) + '</td><td>' + dash(lot.amount) + '</td><td>' + dash(lot.company_code || lot.company_id) + '</td>' +
                '<td>' + coa + '</td><td>' + dash(lot.upload_date) + '</td><td>' + (lot.status ? '{{ translate('On') }}' : '{{ translate('Off') }}') + '</td>' +
                '</tr></tbody></table></div>'
            );
            $block.append(
                '<div class="bm-live-lot-values table-responsive"><table class="table table-sm table-bordered">' +
                '<thead><tr>' +
                '<th class="bm-prate">{{ translate('P-Rate') }}</th><th>{{ translate('PTS') }}</th><th>{{ translate('PTR') }}</th>' +
                '<th>{{ translate('PTD') }}</th><th>{{ translate('Govt.') }}</th><th>{{ translate('Export') }}</th>' +
                '<th>{{ translate('Customer (B2C)') }}</th><th>{{ translate('Batchwise Discount') }}</th>' +
                '<th>{{ translate('Productwise Discount') }}</th><th>{{ translate('Schemewise Discount') }}</th>' +
                '</tr></thead><tbody><tr>' +
                '<td class="bm-prate">' + pv(prices.prate, values.prate, !showPurchaseRate) + '</td>' +
                '<td>' + pv(prices.pts, values.pts) + '</td>' +
                '<td>' + pv(prices.ptr, values.ptr) + '</td>' +
                '<td>' + pv(prices.ptd, values.ptd) + '</td>' +
                '<td>' + pv(prices.gov, values.gov) + '</td>' +
                '<td>' + pv(prices.expo, values.expo) + '</td>' +
                '<td>' + pv(prices.customer, values.customer) + '</td>' +
                '<td>' + (discounts.batchwise === null || discounts.batchwise === undefined || discounts.batchwise === '' ? '—' : dash(discounts.batchwise) + '%') + '</td>' +
                '<td>' + (discounts.productwise === null || discounts.productwise === undefined || discounts.productwise === '' ? '—' : dash(discounts.productwise) + '%') + '</td>' +
                '<td>' + (discounts.schemewise === null || discounts.schemewise === undefined || discounts.schemewise === '' ? '—' : dash(discounts.schemewise) + '%') + '</td>' +
                '</tr></tbody></table></div>'
            );
            $wrap.append($block);
        });
        if (!(lots || []).length) {
            $wrap.append('<p class="text-muted mb-0">{{ translate('No live lots for this SKU.') }}</p>');
        }
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
            lastPurchase = {
                mrp_price: data.last_mrp || '',
                purchase_rate: data.last_rate || '',
                tax_percent: data.last_tax_percent || '',
                tax_code: data.last_tax_code || ''
            };
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
            filterCompanySelects();
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
            if (localStorage.getItem('bm_non_batch') === '1' && !$('#is_non_batch').is(':checked')) {
                $('#is_non_batch').prop('checked', true);
                applyNonBatch();
            }
        } catch (e) {}
    }

    $('#is_non_batch').on('change', applyNonBatch);
    $('#manufactured_by_ids').on('change', filterCompanySelects);
    $('#batch-rows-body').on('input', '.qty-input, .rate-input', refreshAmounts);
    $('#toggle-live-lots').on('click', function () {
        $('#live-lots-wrap').toggleClass('d-none');
    });
    $(document).on('click', '.bm-coa-zoom', function (e) {
        e.preventDefault();
        $('#bm-coa-preview').attr('src', $(this).data('src'));
        $('#bmCoaModal').modal('show');
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
    filterCompanySelects();
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
