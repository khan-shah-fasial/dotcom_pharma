<script type="text/javascript">
(function () {
    var lookupStocksUrl = '{{ route('discount_masters.lookup.stocks') }}';
    var lookupCustomersUrl = '{{ route('discount_masters.lookup.customers') }}';
    var lookupTargetUrl = '{{ route('discount_masters.lookup.target') }}';
    var nextCodeUrl = '{{ route('discount_masters.next_code') }}';
    var revealUrl = '{{ route('discount_masters.reveal_purchase_rate') }}';
    var existingCode = $('#discount-master-form').data('existing-code') || '';
    var existingType = $('#discount-master-form').data('existing-type') || '';
    var notes = JSON.parse($('#dm-notes').text() || '{}');
    var roleOptions = JSON.parse($('#dm-role-options').text() || '{}');
    var selectedBatchIds = JSON.parse($('#dm-selected-batches').text() || '[]').map(String);
    var rolePrices = {};
    var purchaseRate = null;
    var stockQty = 0;
    var loadedBatches = [];
    var searchTimer = null;
    var csrf = '{{ csrf_token() }}';

    function discountType() {
        return $('#discount_type').val();
    }

    function isProductType(type) {
        return ['productwise', 'batchwise', 'schemewise'].indexOf(type) !== -1;
    }

    function isBatchType(type) {
        return type === 'batchwise' || type === 'schemewise';
    }

    function money(value) {
        var number = parseFloat(value);
        if (isNaN(number)) {
            return '';
        }
        return number.toFixed(2);
    }

    function showLayout() {
        var type = discountType();
        $('#product-layout').toggleClass('d-none', !isProductType(type));
        $('#invoice-layout').toggleClass('d-none', isProductType(type) || !type);
        $('.dm-batch-pick').toggleClass('d-none', !isBatchType(type));
        $('#scheme-same-row').toggleClass('d-none', type !== 'schemewise');
        $('#coupon-head').toggleClass('d-none', type !== 'couponwise');
        $('.dm-price-role').toggleClass('d-none', ['amount_wise', 'pointwise', 'couponwise'].indexOf(type) === -1);
        $('.dm-col-scheme').toggleClass('d-none', type !== 'schemewise');
        $('.dm-col-flat').toggleClass('d-none', type === 'schemewise');
        $('#invoice-amount-label').text(type === 'pointwise' ? '{{ translate('Points') }}' : '{{ translate('Amount') }}');
        $('#same-label').text(type === 'schemewise'
            ? '{{ translate('If you want to give the same scheme on the items below') }}'
            : '{{ translate('If you want to give the same discount on the items below') }}');
        $('#dm-type-note').text(notes[type] || '{{ translate('Select a discount type.') }}');
        toggleSchemeOther();
        toggleSame();
        if (type && (!existingCode || existingType !== type)) {
            $.get(nextCodeUrl, { discount_type: type }, function (res) {
                $('#discount_code').val(res.code || '');
            });
        } else if (existingCode && existingType === type) {
            $('#discount_code').val(existingCode);
        }
        recalcAll();
    }

    function toggleSchemeOther() {
        var different = $('#scheme_product_is_same').val() === '0';
        $('#scheme-other-product').toggleClass('d-none', !(discountType() === 'schemewise' && different));
    }

    function toggleSame() {
        var open = $('#same_discount').val() === '1';
        $('#same-body').toggleClass('d-none', !open);
        $('#same-body').find('input, select').prop('disabled', !open);
    }

    function paintPrices() {
        $('.dm-price').each(function () {
            var key = $(this).data('role');
            var rate = rolePrices[key];
            $(this).text(rate == null ? '—' : money(rate));
            var valueCell = $('.dm-value[data-role="' + key + '"]');
            valueCell.text(rate == null || !stockQty ? '—' : money(stockQty * rate));
        });
        if (purchaseRate == null) {
            $('#price-p_rate').text('******');
            $('#value-p_rate').text('—');
        } else {
            $('#price-p_rate').text(money(purchaseRate));
            $('#value-p_rate').text(stockQty ? money(stockQty * purchaseRate) : '—');
        }
    }

    function selectedBatches() {
        var ids = [];
        $('#batch-picks input:checked').each(function () {
            ids.push(String($(this).val()));
        });
        return ids;
    }

    function paintStock() {
        var type = discountType();
        var rows = loadedBatches;
        if (isBatchType(type)) {
            var ids = selectedBatches();
            rows = loadedBatches.filter(function (row) {
                return ids.indexOf(String(row.id)) !== -1;
            });
        }
        if (!rows.length && loadedBatches.length) {
            rows = [loadedBatches[0]];
        }
        var qty = 0;
        var codes = [];
        rows.forEach(function (row) {
            qty += parseFloat(row.qty || 0);
            if (row.batch) {
                codes.push(row.batch);
            }
        });
        if (!rows.length) {
            qty = stockQty;
        } else {
            stockQty = qty;
        }
        var first = rows[0] || {};
        $('#stock_available').val(qty || '');
        $('#batch_summary').val(codes.join(', '));
        $('#manufacturing_date').val(first.mfg || '');
        $('#expiry_date').val(first.expiry || '');
        var $gallery = $('#coa-gallery').empty();
        rows.forEach(function (row) {
            if (!row.coa_url) {
                return;
            }
            if (/\.(png|jpe?g|gif|webp)(\?|$)/i.test(row.coa_url)) {
                var $img = $('<img class="img-thumbnail mr-1 mb-1" style="height:48px;cursor:pointer;">').attr('src', row.coa_url);
                $img.on('click', function () {
                    $('#coa-modal-img').attr('src', row.coa_url);
                    $('#coa-modal').modal('show');
                });
                $gallery.append($img);
            } else {
                $gallery.append($('<a class="mr-2" target="_blank"></a>').attr('href', row.coa_url).text(row.coa_label || '{{ translate('COA') }}'));
            }
        });
        if (rows.length && rows[0].role_prices) {
            rolePrices = rows[0].role_prices;
        }
        paintPrices();
        syncRates();
    }

    function renderBatches() {
        var $box = $('#batch-picks').empty();
        var filter = $.trim($('#batch_filter').val()).toLowerCase();
        loadedBatches.forEach(function (row) {
            var label = (row.batch || '-') + ' / ' + (row.qty == null ? '' : row.qty);
            if (filter && label.toLowerCase().indexOf(filter) === -1) {
                return;
            }
            var checked = selectedBatchIds.indexOf(String(row.id)) !== -1 ? ' checked' : '';
            $box.append(
                '<label class="d-block mb-1"><input type="checkbox" name="batch_ids[]" value="' + row.id + '"' + checked + '> ' + $('<div>').text(label).html() + '</label>'
            );
        });
        if (!$box.children().length) {
            $box.append('<span class="text-muted">{{ translate('Select a product to list batches.') }}</span>');
        }
    }

    function fillTarget(res) {
        $('#product_id').val(res.product_id || '');
        $('#product_stock_id').val(res.product_stock_id || '');
        $('#meta_sku').val(res.sku || '');
        $('#meta_variant').val(res.variant || '');
        $('#meta_marketed').val(res.marketed_by || '');
        $('#meta_import').val(res.import_by || '');
        $('#meta_mfg').val(res.mfg_by || '');
        $('#drug_name').text(res.drug_name || '');
        stockQty = parseFloat(res.stock_available || 0) || 0;
        rolePrices = res.role_prices || {};
        purchaseRate = null;
        loadedBatches = res.batches || [];
        renderBatches();
        paintStock();
    }

    function loadTarget() {
        var stockId = $('#product_stock_id').val();
        if (!stockId) {
            return;
        }
        $.get(lookupTargetUrl, { product_stock_id: stockId }, fillTarget);
    }

    function rateForGroup($group) {
        var key = $group.find('.dm-role-key').first().val();
        if (key && rolePrices[key] != null) {
            return parseFloat(rolePrices[key]);
        }
        return null;
    }

    function syncRates() {
        $('.dm-role-group').each(function () {
            var rate = rateForGroup($(this));
            if (rate != null) {
                $(this).find('.dm-rate').val(money(rate));
            }
        });
        recalcAll();
    }

    function recalcSlab($row) {
        var qty = parseFloat($row.find('.dm-qty').val() || '0');
        var rate = parseFloat($row.find('.dm-rate').val() || '0');
        var type = discountType();
        var line = qty > 0 && rate > 0 ? qty * rate : 0;
        var effective = rate;

        if (type === 'schemewise') {
            var free = parseFloat($row.find('.dm-free').val() || '0');
            var schemePercent = qty > 0 ? (free / qty) * 100 : 0;
            var schemeValue = free * rate;
            $row.find('.dm-scheme-percent').val(schemePercent ? money(schemePercent) : '');
            $row.find('.dm-scheme-value').val(schemeValue ? money(schemeValue) : '');
            $row.find('.dm-line-amount').val(line ? money(line) : '');
            if ((qty + free) > 0 && rate > 0) {
                effective = (qty * rate) / (qty + free);
            }
        } else {
            var mode = $row.find('.dm-value-type').val();
            var amount = parseFloat($row.find('.dm-amount').val() || '0');
            var percent = parseFloat($row.find('.dm-percent').val() || '0');
            if (mode === 'percent') {
                amount = rate * percent / 100;
                $row.find('.dm-amount').val(percent ? money(amount) : '').prop('readonly', true);
                $row.find('.dm-percent').prop('readonly', false);
            } else {
                percent = rate > 0 ? (amount / rate) * 100 : 0;
                $row.find('.dm-percent').val(amount ? money(percent) : '').prop('readonly', true);
                $row.find('.dm-amount').prop('readonly', false);
            }
            $row.find('.dm-line-amount, .dm-line-amount-flat').val(line ? money(line) : '');
            if (mode === 'percent' && percent > 0) {
                effective = rate - (rate * percent / 100);
            } else if (amount > 0) {
                effective = rate - amount;
            }
        }
        $row.find('.dm-effective').val(rate ? money(Math.max(effective, 0)) : '');
    }

    function recalcAmount($row) {
        var base = parseFloat($row.find('.dm-to').val() || $row.find('.dm-slab-cap').val() || $row.find('.dm-from').val() || '0');
        var mode = $row.find('.dm-amount-type').val();
        var amount = parseFloat($row.find('.dm-amount-value').val() || '0');
        var percent = parseFloat($row.find('.dm-amount-percent').val() || '0');
        if (mode === 'percent') {
            amount = base * percent / 100;
            $row.find('.dm-amount-value').val(percent ? money(amount) : '').prop('readonly', true);
            $row.find('.dm-amount-percent').prop('readonly', false);
        } else {
            percent = base > 0 ? (amount / base) * 100 : 0;
            $row.find('.dm-amount-percent').val(amount ? money(percent) : '').prop('readonly', true);
            $row.find('.dm-amount-value').prop('readonly', false);
        }
    }

    function recalcAll() {
        $('.dm-slab').each(function () {
            recalcSlab($(this));
        });
        $('.dm-amount-row').each(function () {
            recalcAmount($(this));
        });
    }

    function reindex($group) {
        var roleIndex = $group.index();
        $group.find('.dm-role-key').attr('name', 'roles[' + roleIndex + '][role_key]');
        $group.find('.dm-slab').each(function (slabIndex) {
            $(this).find('input, select').each(function () {
                var name = $(this).attr('name') || '';
                if (!name) {
                    return;
                }
                $(this).attr('name', name.replace(/roles\[\d+\]\[slabs\]\[\d+\]/, 'roles[' + roleIndex + '][slabs][' + slabIndex + ']'));
            });
        });
    }

    function reindexAll() {
        $('#role-groups .dm-role-group').each(function () {
            reindex($(this));
        });
        $('#amount-body .dm-amount-row').each(function (index) {
            $(this).find('input, select').each(function () {
                var name = $(this).attr('name') || '';
                $(this).attr('name', name.replace(/amounts\[\d+\]/, 'amounts[' + index + ']'));
            });
        });
    }

    function blankSlab() {
        var $row = $('.dm-slab').first().clone();
        $row.find('input').val('');
        $row.find('select').val('flat');
        $row.find('.dm-col-role').empty();
        return $row;
    }

    function roleSelect(index) {
        var html = '<select class="form-control form-control-sm dm-role-key" name="roles[' + index + '][role_key]"><option value="">{{ translate('Select') }}</option>';
        Object.keys(roleOptions).forEach(function (key) {
            html += '<option value="' + key + '">' + $('<div>').text(roleOptions[key]).html() + '</option>';
        });
        return html + '</select>';
    }

    function bindTypeahead($input) {
        $input.on('keyup', function () {
            var $el = $(this);
            var mode = $el.data('mode');
            var q = $.trim($el.val());
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                var url = mode === 'customer' ? lookupCustomersUrl : lookupStocksUrl;
                var data = mode === 'customer' ? { q: q } : { q: q, mode: mode };
                $.get(url, data, function (items) {
                    var $box = $el.siblings('.dm-typeahead-results');
                    $box.empty();
                    (items || []).forEach(function (item) {
                        var $a = $('<a href="#" class="list-group-item list-group-item-action"></a>').text(item.label);
                        $a.on('click', function (e) {
                            e.preventDefault();
                            $box.empty();
                            if ($el.data('scope')) {
                                if ($('#scope-chips input[value="' + item.id + '"]').length) {
                                    return;
                                }
                                var $chip = $('<span class="badge badge-soft-primary mr-1 mb-1 dm-chip"></span>').text(item.label + ' ');
                                $chip.append($('<input type="hidden" name="scope_products[]">').val(item.id));
                                $chip.append($('<a href="#" class="text-danger dm-chip-remove">&times;</a>'));
                                $('#scope-chips').append($chip);
                                $el.val('');
                                return;
                            }
                            $el.val(item.label);
                            var target = $el.data('target');
                            if (target) {
                                $('#' + target).val(item.id);
                            }
                            if (item.product_id) {
                                $('#product_id').val(item.product_id);
                            }
                            if (!$el.data('skip-load') && target === 'product_stock_id') {
                                selectedBatchIds = [];
                                loadTarget();
                            }
                        });
                        $box.append($a);
                    });
                });
            }, 250);
        });
    }

    $('#discount_type').on('change', showLayout);
    $('#same_discount').on('change', toggleSame);
    $('#scheme_product_is_same').on('change', toggleSchemeOther);
    $('#toggle-stock').on('click', function () {
        $('#stock-panel').toggle();
    });
    $('#batch_filter').on('input', function () {
        selectedBatchIds = selectedBatches();
        renderBatches();
    });
    $(document).on('change', '#batch-picks input', function () {
        selectedBatchIds = selectedBatches();
        paintStock();
    });
    $(document).on('change', '.dm-role-key', syncRates);
    $(document).on('input change', '.dm-qty, .dm-free, .dm-value-type, .dm-amount, .dm-percent, .dm-amount-type, .dm-amount-value, .dm-amount-percent, .dm-from, .dm-to, .dm-slab-cap', recalcAll);
    $(document).on('click', '.dm-add-slab', function () {
        var $group = $(this).closest('.dm-role-group');
        $group.find('.dm-slab-body').append(blankSlab());
        reindexAll();
        syncRates();
    });
    $(document).on('click', '.dm-add-role', function () {
        var $clone = $('.dm-role-group').first().clone();
        $clone.find('.dm-slab').not(':first').remove();
        $clone.find('input').val('');
        $clone.find('.dm-slab-body .dm-col-role').html(roleSelect(0));
        $('#role-groups').append($clone);
        reindexAll();
    });
    $(document).on('click', '.dm-remove-role', function () {
        if ($('.dm-role-group').length < 2) {
            return;
        }
        $(this).closest('.dm-role-group').remove();
        reindexAll();
    });
    $(document).on('click', '.dm-remove-slab', function () {
        var $body = $(this).closest('.dm-slab-body');
        if ($body.find('.dm-slab').length < 2) {
            return;
        }
        $(this).closest('.dm-slab').remove();
        reindexAll();
    });
    $('#add-amount-slab').on('click', function () {
        var $row = $('.dm-amount-row').first().clone();
        $row.find('input').val('');
        $row.find('select').val('flat');
        $('#amount-body').append($row);
        reindexAll();
    });
    $(document).on('click', '.dm-remove-amount', function () {
        if ($('.dm-amount-row').length < 2) {
            return;
        }
        $(this).closest('.dm-amount-row').remove();
        reindexAll();
    });
    $(document).on('click', '.dm-chip-remove', function (e) {
        e.preventDefault();
        $(this).closest('.dm-chip').remove();
    });
    $('#unlock-prate').on('click', function () {
        $('#prate-error').addClass('d-none').text('');
        $('#prate-password').val('');
        $('#prate-modal').modal('show');
    });
    $('#prate-submit').on('click', function () {
        $.post(revealUrl, {
            _token: csrf,
            password: $('#prate-password').val(),
            product_stock_id: $('#product_stock_id').val(),
            batch_id: selectedBatches()[0] || ''
        }).done(function (res) {
            purchaseRate = res.purchase_rate;
            paintPrices();
            $('#prate-modal').modal('hide');
        }).fail(function (xhr) {
            var message = (xhr.responseJSON && xhr.responseJSON.message) || '{{ translate('Password does not match.') }}';
            $('#prate-error').removeClass('d-none').text(message);
        });
    });

    $('.dm-typeahead').each(function () {
        bindTypeahead($(this));
    });

    showLayout();
    if ($('#product_stock_id').val()) {
        loadTarget();
    }
})();
</script>
