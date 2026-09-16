<script type="text/javascript">
(function () {
    var lookupStocksUrl = '{{ route('discount_masters.lookup.stocks') }}';
    var lookupBatchesUrl = '{{ route('discount_masters.lookup.batches') }}';
    var lookupCustomersUrl = '{{ route('discount_masters.lookup.customers') }}';
    var lookupTargetUrl = '{{ route('discount_masters.lookup.target') }}';
    var nextCodeUrl = '{{ route('discount_masters.next_code') }}';
    var existingCode = $('#discount-master-form').data('existing-code') || '';
    var existingType = $('#discount-master-form').data('existing-type') || '';
    var rolePrices = {};
    var searchTimer = null;

    function appliedOn() {
        return $('#applied_on').val();
    }

    function discountType() {
        return $('#discount_type').val();
    }

    function visibleTypeBlock() {
        return $('.dm-type-block[data-show-type="' + discountType() + '"]');
    }

    function syncValueFields() {
        var $block = visibleTypeBlock();
        if (!$block.length) {
            return;
        }
        var typeVal = $block.find('.dm-value-type').val();
        var amountVal = $block.find('.dm-value-amount').val();
        var percentVal = $block.find('.dm-value-percent').val();
        if (typeVal) {
            $('#value_type').val(typeVal);
        }
        if (amountVal !== undefined) {
            $('#value_amount').val(amountVal);
        }
        if (percentVal !== undefined) {
            $('#value_percent').val(percentVal);
        }
    }

    function showAppliedOnFields() {
        var value = appliedOn();
        $('.dm-field').each(function () {
            var allowed = String($(this).data('show-on') || '').split(',').map(function (item) {
                return $.trim(item);
            }).filter(Boolean);
            $(this).toggleClass('d-none', allowed.indexOf(value) === -1);
        });
        var showPrices = ['sku', 'full_variant', 'batch'].indexOf(value) !== -1;
        $('#role-price-block').toggleClass('d-none', !showPrices);
        if (value !== 'sku' && value !== 'full_variant') {
            if (value !== 'batch') {
                $('#product_stock_id').val('');
            }
        }
        if (value !== 'batch') {
            $('#batch_id').val('');
        }
        if (value !== 'customer') {
            $('#customer_id').val('');
        }
    }

    function showTypeFields() {
        var value = discountType();
        $('.dm-type-block').addClass('d-none');
        $('.dm-type-block[data-show-type="' + value + '"]').removeClass('d-none');
        toggleSchemeOther();
        if (value) {
            $.get(nextCodeUrl, { discount_type: value }, function (res) {
                if (!existingCode || existingType !== value) {
                    $('#discount_code').val(res.code || '');
                }
            });
        }
        recalc();
    }

    function toggleSchemeOther() {
        var same = $('#scheme_product_is_same').val() !== '0';
        $('#scheme-other-product').toggleClass('d-none', !(discountType() === 'schemewise' && !same));
    }

    function highlightRole() {
        var key = $('#role_key').val();
        $('.dm-role-cell').removeClass('is-active');
        if (key) {
            $('.dm-role-cell[data-role="' + key + '"]').addClass('is-active');
            if (rolePrices[key] != null) {
                $('#rate').val(rolePrices[key]);
            }
        }
        recalc();
    }

    function fillRoleGrid(prices) {
        rolePrices = prices || {};
        $('.dm-role-cell').each(function () {
            var key = $(this).data('role');
            var val = rolePrices[key];
            $(this).find('.dm-role-value').text(val == null ? '—' : val);
        });
        highlightRole();
    }

    function loadTarget() {
        var payload = {
            applied_on: appliedOn(),
            product_stock_id: $('#product_stock_id').val(),
            batch_id: $('#batch_id').val()
        };
        if (!payload.applied_on) {
            return;
        }
        if (['sku', 'full_variant'].indexOf(payload.applied_on) !== -1 && !payload.product_stock_id) {
            return;
        }
        if (payload.applied_on === 'batch' && !payload.batch_id) {
            return;
        }
        $.get(lookupTargetUrl, payload, function (res) {
            $('#product_id').val(res.product_id || '');
            if (res.product_stock_id) {
                $('#product_stock_id').val(res.product_stock_id);
            }
            $('#stock_available').val(res.stock_available == null ? '' : res.stock_available);
            $('#manufacturing_date').val(res.manufacturing_date || '');
            $('#expiry_date').val(res.expiry_date || '');
            if (res.coa_url) {
                $('#coa_link').attr('href', res.coa_url).removeClass('d-none').text(res.coa_label || '{{ translate('Download') }}');
                $('#coa_label').addClass('d-none');
            } else {
                $('#coa_link').addClass('d-none');
                $('#coa_label').removeClass('d-none').text(res.coa_label || '—');
            }
            fillRoleGrid(res.role_prices || {});
        });
    }

    function recalc() {
        var qty = parseFloat($('#qty_slab_from').val() || '0');
        var rate = parseFloat($('#rate').val() || '0');
        var amount = (qty > 0 && rate > 0) ? (qty * rate) : (rate || 0);
        $('#amount').val(amount ? amount.toFixed(4) : '');

        syncValueFields();
        var valueType = $('#value_type').val();
        var valueAmount = parseFloat($('#value_amount').val() || '0');
        var valuePercent = parseFloat($('#value_percent').val() || '0');
        var $block = visibleTypeBlock();

        if (rate > 0 && $block.find('.dm-value-amount').length) {
            if (valueType === 'percent' && valuePercent > 0) {
                valueAmount = rate * valuePercent / 100;
                $block.find('.dm-value-amount').val(valueAmount.toFixed(4));
                $('#value_amount').val(valueAmount.toFixed(4));
            } else if (valueType === 'flat' && valueAmount > 0) {
                valuePercent = (valueAmount / rate) * 100;
                $block.find('.dm-value-percent').val(valuePercent.toFixed(4));
                $('#value_percent').val(valuePercent.toFixed(4));
            }
        }

        var effective = rate;
        var type = discountType();
        if (type === 'schemewise') {
            var buyQty = qty > 0 ? qty : 50;
            var freeQty = parseFloat($('#scheme_free_qty').val() || '0');
            var totalQty = buyQty + freeQty;
            if (totalQty > 0 && rate > 0) {
                effective = (buyQty * rate) / totalQty;
                $('#scheme_value').val((freeQty * rate).toFixed(4));
            }
        } else if (valueType === 'percent' && valuePercent > 0) {
            effective = rate - (rate * valuePercent / 100);
        } else if (valueAmount > 0) {
            effective = rate - valueAmount;
        }
        $('#effective_rate').val(effective ? Number(Math.max(effective, 0).toFixed(4)) : '');
    }

    function bindTypeahead($input) {
        $input.on('keyup', function () {
            var $el = $(this);
            var mode = $el.data('mode');
            var q = $.trim($el.val());
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                var url = lookupStocksUrl;
                var data = { q: q, mode: mode };
                if (mode === 'batch') {
                    url = lookupBatchesUrl;
                    data = { q: q };
                } else if (mode === 'customer') {
                    url = lookupCustomersUrl;
                    data = { q: q };
                }
                $.get(url, data, function (items) {
                    var $box = $el.siblings('.dm-typeahead-results');
                    $box.empty();
                    (items || []).forEach(function (item) {
                        var $a = $('<a href="#" class="list-group-item list-group-item-action"></a>').text(item.label);
                        $a.on('click', function (e) {
                            e.preventDefault();
                            $el.val(item.label);
                            $('#' + $el.data('target')).val(item.id);
                            if (item.product_id) {
                                $('#product_id').val(item.product_id);
                            }
                            if (item.product_stock_id) {
                                $('#product_stock_id').val(item.product_stock_id);
                            }
                            $box.empty();
                            loadTarget();
                        });
                        $box.append($a);
                    });
                });
            }, 250);
        });
    }

    $('#applied_on').on('change changed.bs.select', showAppliedOnFields);
    $('#discount_type').on('change changed.bs.select', showTypeFields);
    $('#role_key').on('change', highlightRole);
    $('#qty_slab_from, #qty_slab_to, #rate, #scheme_free_qty').on('input', recalc);
    $(document).on('change input', '.dm-value-type, .dm-value-amount, .dm-value-percent', recalc);
    $('#scheme_product_is_same').on('change', toggleSchemeOther);
    $('#toggle-role-grid').on('click', function () {
        $('#role-price-grid').toggle();
    });
    $('.dm-role-cell').on('click', function () {
        $('#role_key').val($(this).data('role')).trigger('change');
    });
    $('#discount-master-form').on('submit', syncValueFields);

    $('.dm-typeahead').each(function () {
        bindTypeahead($(this));
    });

    showAppliedOnFields();
    showTypeFields();
    if ($('#product_stock_id').val() || $('#batch_id').val()) {
        loadTarget();
    }
})();
</script>
