<script type="text/javascript">
(function () {
    var epsilon = 0.01;
    var hsnTimer = null;
    var chapterTimer = null;
    var codeTimer = null;
    var stockTimer = null;
    var salePanelOpen = !$('#sale-tax-panel').hasClass('d-none');
    var showSaleText = '{{ translate('Show sale tax') }}';
    var hideSaleText = '{{ translate('Hide sale tax') }}';
    var allSku = @json(\App\Models\TaxMaster::APPLIED_ALL['sku']);
    var allProduct = @json(\App\Models\TaxMaster::APPLIED_ALL['product']);
    var allVariant = @json(\App\Models\TaxMaster::APPLIED_ALL['variant']);

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

    function refreshSalePanel() {
        if (!sameAsPurchase()) {
            $('#sale-tax-panel').removeClass('d-none');
            $('#sale-tax-toggle').addClass('d-none');
            return;
        }
        $('#sale-tax-toggle').removeClass('d-none').text(salePanelOpen ? hideSaleText : showSaleText);
        $('#sale-tax-panel').toggleClass('d-none', !salePanelOpen);
    }

    function suggestCode() {
        if ($('#tax_code').data('auto') !== 1 && $('#tax_code').data('auto') !== '1') {
            return;
        }
        clearTimeout(codeTimer);
        codeTimer = setTimeout(function () {
            var tax = numberValue('#tax_percent');
            $.get('{{ route('tax_masters.suggest_code') }}', {
                tax: tax,
                ignore_id: $('#tax_code').data('ignore-id') || ''
            }).done(function (response) {
                var stillAuto = $('#tax_code').data('auto') === 1 || $('#tax_code').data('auto') === '1';
                if (stillAuto && response.tax_code && numberValue('#tax_percent') === tax) {
                    $('#tax_code').val(response.tax_code);
                }
            });
        }, 200);
    }

    function refreshKindPicker() {
        if (typeof $('#kind').selectpicker === 'function') {
            $('#kind').selectpicker('refresh');
        }
    }

    function syncForm() {
        if (zeroKind()) {
            $('#tax_percent').val('0').prop('readonly', true);
            applyBreakup('purchase', 0, false);
            setSameAsPurchase(true);
            salePanelOpen = false;
            copyPurchaseToSale();
            lockSale(true);
            $('#sale_same_as_purchase_y, #sale_same_as_purchase_n').prop('disabled', true);
            refreshSalePanel();
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
        refreshSalePanel();
    }

    $('#kind').on('change', function () {
        syncForm();
        suggestCode();
    });

    $('input[name="sale_same_as_purchase"]').on('change', function () {
        salePanelOpen = !sameAsPurchase();
        syncForm();
    });

    $('#sale-tax-toggle').on('click', function () {
        salePanelOpen = !salePanelOpen;
        refreshSalePanel();
    });

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

    function fitDescription() {
        var el = document.getElementById('description');
        if (!el) {
            return;
        }
        el.style.height = 'auto';
        el.style.height = Math.max(el.scrollHeight, 58) + 'px';
    }

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

    function hideChapterResults() {
        $('#hsn-chapter-results').attr('hidden', true).empty();
    }

    function lookupTaxByHsn(code) {
        if (!code) {
            return;
        }
        $.get('{{ route('tax_masters.by_hsn') }}', { hsn: code }).done(function (row) {
            if (!row || !row.found || !row.kind) {
                return;
            }
            $('#kind').val(row.kind);
            refreshKindPicker();
            $('#tax_percent').val(row.tax_percent);
            syncForm();
            suggestCode();
        });
    }

    function applyHsnRow(row) {
        $('#hsn_code').val(row.code);
        $('#hs_code').val(row.hs_code || hsFromHsn(row.code));
        $('#description').val((row.description || '').slice(0, 255));
        fitDescription();
        hideHsnResults();
        hideChapterResults();
        lookupTaxByHsn(row.code);
    }

    function renderHsnResults(target, rows) {
        var $box = $(target).empty();
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
    }

    $('#description').on('input', fitDescription);

    $('#hsn_code').on('input', function () {
        var query = $(this).val();
        $('#hs_code').val(hsFromHsn(query));
        clearTimeout(hsnTimer);
        hideChapterResults();
        if (!query || query.length < 2) {
            hideHsnResults();
            return;
        }
        hsnTimer = setTimeout(function () {
            $.get('{{ route('tax_masters.hsn_search') }}', { q: query }).done(function (rows) {
                renderHsnResults('#hsn-results', rows);
            });
        }, 250);
    });

    $('#hsn_chapter').on('input', function () {
        var query = $(this).val();
        clearTimeout(chapterTimer);
        hideHsnResults();
        if (!query || query.length < 2) {
            hideChapterResults();
            return;
        }
        chapterTimer = setTimeout(function () {
            $.get('{{ route('tax_masters.hsn_chapters') }}', { q: query }).done(function (rows) {
                renderHsnResults('#hsn-chapter-results', rows);
            });
        }, 250);
    });

    $('#hsn-results, #hsn-chapter-results').on('mousedown', 'button', function (event) {
        event.preventDefault();
        applyHsnRow($(this).data('row'));
    });

    $(document).on('click', function (event) {
        if (!$(event.target).closest('.tm-hsn-wrap').length) {
            hideHsnResults();
            hideChapterResults();
            hideStockResults();
        }
    });

    function hideStockResults(box) {
        var $boxes = box ? $(box) : $('.tm-stock-results');
        $boxes.attr('hidden', true).empty();
    }

    function syncCombo(selectId) {
        var $select = $('#' + selectId);
        var text = $select.find('option:selected').text();
        $select.closest('.tm-combo').find('.tm-combo-input').val(text);
    }

    function showLocalOptions($combo) {
        var $box = $combo.find('.tm-stock-results').empty();
        $combo.find('select option').each(function () {
            var button = $('<button type="button"></button>');
            button.text($(this).text());
            button.attr('data-local', '1');
            button.attr('data-value', $(this).val());
            button.attr('data-stock-id', $(this).attr('data-stock-id') || '');
            $box.append(button);
        });
        if (!$box.children().length) {
            $box.attr('hidden', true);
            return;
        }
        $box.removeAttr('hidden');
    }

    function showStockResults(box, rows) {
        var $box = $(box).empty();
        var allLabel = $box.closest('.tm-combo').find('select option:first').text();
        if (allLabel) {
            $box.append($('<button type="button"></button>').text(allLabel).attr('data-all', '1'));
        }
        (rows || []).forEach(function (row) {
            var button = $('<button type="button"></button>');
            button.text(row.label || row.sku || row.product_name || row.variant || '');
            button.data('row', row);
            $box.append(button);
        });
        if (!$box.children().length) {
            $box.attr('hidden', true);
            return;
        }
        $box.removeAttr('hidden');
    }

    var applyingStock = false;

    function stockExtra(row) {
        return {
            stockId: row.id || row.stockId || '',
            productId: row.product_id || row.productId || '',
            productName: row.product_name || row.productName || '',
            sku: row.sku || '',
            variant: row.variant || ''
        };
    }

    function replaceAppliedOptions(selectId, allValue, rows, valueKey, selectedStockId) {
        var $select = $('#' + selectId);
        $select.find('option').not(':first').remove();
        var selected = null;
        (rows || []).forEach(function (row) {
            var value = row[valueKey] || '';
            if (!value || value === allValue) {
                return;
            }
            var extra = stockExtra(row);
            var option = $('<option></option>').val(value).text(value);
            option.attr('data-stock-id', extra.stockId);
            option.attr('data-product-id', extra.productId);
            option.attr('data-product-name', extra.productName);
            option.attr('data-sku', extra.sku);
            option.attr('data-variant', extra.variant);
            $select.append(option);
            if (selectedStockId && String(extra.stockId) === String(selectedStockId)) {
                selected = option;
            }
        });
        if (selected) {
            selected.prop('selected', true);
        } else {
            $select.prop('selectedIndex', 0);
        }
        syncCombo(selectId);
    }

    function selectByStockId(selectId, stockId) {
        if (!stockId) {
            return;
        }
        var $match = $('#' + selectId).find('option').filter(function () {
            return String($(this).attr('data-stock-id') || '') === String(stockId);
        });
        if ($match.length) {
            $match.prop('selected', true);
            syncCombo(selectId);
        }
    }

    function applyProductStocks(rows, preferredRow) {
        rows = rows || [];
        var productRow = preferredRow || rows[0] || null;
        var chosenId = preferredRow && preferredRow.id ? preferredRow.id : (rows.length === 1 ? rows[0].id : null);
        applyingStock = true;
        if (productRow && productRow.product_name) {
            replaceAppliedOptions('applied_on_product', allProduct, [productRow], 'product_name', productRow.id);
        }
        replaceAppliedOptions('applied_on_sku', allSku, rows, 'sku', chosenId);
        replaceAppliedOptions('applied_on_variant', allVariant, rows, 'variant', chosenId);
        applyingStock = false;
    }

    function loadProductStocks(productId, preferredRow, showSkuList) {
        $.get('{{ route('tax_masters.lookup.stocks') }}', { product_id: productId, mode: 'sku' }).done(function (rows) {
            rows = rows || [];
            var preferred = preferredRow;
            if (!preferred && rows.length === 1) {
                preferred = rows[0];
            }
            applyProductStocks(rows.length ? rows : (preferred ? [preferred] : []), preferred || null);
            if (showSkuList && rows.length > 1) {
                showStockResults('#applied-sku-results', rows);
            }
        });
    }

    $('.tm-combo-input').on('focus', function () {
        showLocalOptions($(this).closest('.tm-combo'));
    });

    $('.tm-combo-caret').on('mousedown', function (event) {
        event.preventDefault();
        var $combo = $(this).closest('.tm-combo');
        var $box = $combo.find('.tm-stock-results');
        if ($box.attr('hidden')) {
            showLocalOptions($combo);
            $combo.find('.tm-combo-input').trigger('focus');
        } else {
            hideStockResults($box);
        }
    });

    $('.tm-combo-input').on('input', function () {
        var $input = $(this);
        var mode = $input.data('mode');
        var query = $.trim($input.val());
        var $combo = $input.closest('.tm-combo');
        var selectedText = $.trim($combo.find('select option:selected').text());
        clearTimeout(stockTimer);
        if (!query || query === selectedText) {
            showLocalOptions($combo);
            return;
        }
        stockTimer = setTimeout(function () {
            $.get('{{ route('tax_masters.lookup.stocks') }}', { q: query, mode: mode }).done(function (rows) {
                showStockResults($input.siblings('.tm-stock-results'), rows);
            });
        }, 250);
    });

    $('.tm-combo-input').on('blur', function () {
        var selectId = $(this).data('for');
        setTimeout(function () {
            syncCombo(selectId);
        }, 200);
    });

    $('.tm-stock-results').on('mousedown', 'button', function (event) {
        event.preventDefault();
        var $btn = $(this);
        var $combo = $btn.closest('.tm-combo');
        var $select = $combo.find('select');
        var mode = $combo.find('.tm-combo-input').data('mode');
        var row = $btn.data('row');
        hideStockResults();
        if ($btn.attr('data-all')) {
            $select.prop('selectedIndex', 0).trigger('change');
            syncCombo($select.attr('id'));
            return;
        }
        if ($btn.attr('data-local')) {
            var stockId = $btn.attr('data-stock-id');
            var value = $btn.attr('data-value');
            var $opt = stockId
                ? $select.find('option').filter(function () {
                    return String($(this).attr('data-stock-id') || '') === String(stockId);
                })
                : $select.find('option').filter(function () {
                    return $(this).val() === value;
                });
            if ($opt.length) {
                $opt.prop('selected', true);
                $select.trigger('change');
                syncCombo($select.attr('id'));
            }
            return;
        }
        if (!row || !row.product_id) {
            return;
        }
        var preferred = mode === 'product' ? null : row;
        loadProductStocks(row.product_id, preferred, mode === 'product');
    });

    $('#applied_on_sku').on('change', function () {
        if (applyingStock) {
            return;
        }
        applyingStock = true;
        if ($(this).val() === allSku) {
            $('#applied_on_variant').val(allVariant);
            syncCombo('applied_on_variant');
        } else {
            selectByStockId('applied_on_variant', $(this).find('option:selected').attr('data-stock-id'));
        }
        applyingStock = false;
    });

    $('#applied_on_product').on('change', function () {
        if (applyingStock) {
            return;
        }
        if ($(this).val() === allProduct) {
            applyingStock = true;
            replaceAppliedOptions('applied_on_sku', allSku, [], 'sku', null);
            replaceAppliedOptions('applied_on_variant', allVariant, [], 'variant', null);
            applyingStock = false;
            return;
        }
        var productId = $(this).find('option:selected').attr('data-product-id');
        if (!productId) {
            return;
        }
        loadProductStocks(productId, null, true);
    });

    $('#applied_on_variant').on('change', function () {
        if (applyingStock || $(this).val() === allVariant) {
            return;
        }
        applyingStock = true;
        selectByStockId('applied_on_sku', $(this).find('option:selected').attr('data-stock-id'));
        applyingStock = false;
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
    fitDescription();
    syncCombo('applied_on_sku');
    syncCombo('applied_on_product');
    syncCombo('applied_on_variant');
    if (!$('#tax_code').val()) {
        $('#tax_code').data('auto', 1);
        suggestCode();
    }
})();
</script>
