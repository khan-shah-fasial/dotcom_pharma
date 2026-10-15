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
    var appliedConfig = {};
    try {
        appliedConfig = JSON.parse(document.getElementById('tm-applied-config').textContent || '{}');
    } catch (error) {
        appliedConfig = {};
    }
    var appliedSelected = Array.isArray(appliedConfig.selected) ? appliedConfig.selected.slice() : [];
    var appliedRequest = 0;

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
        if ($('#tax_code').data('ignore-id')) {
            return;
        }
        clearTimeout(codeTimer);
        codeTimer = setTimeout(function () {
            var tax = numberValue('#tax_percent');
            var currentKind = kind();
            $.get('{{ route('tax_masters.suggest_code') }}', {
                tax: tax,
                kind: currentKind,
                ignore_id: $('#tax_code').data('ignore-id') || ''
            }).done(function (response) {
                if (!response || !response.tax_code || numberValue('#tax_percent') !== tax || kind() !== currentKind) {
                    return;
                }
                if (response.existing) {
                    $('#tax_code').val(response.tax_code).prop('readonly', true);
                    $('#tax-code-existing').removeClass('d-none');
                    return;
                }
                $('#tax_code').prop('readonly', false);
                $('#tax-code-existing').addClass('d-none');
                var stillAuto = $('#tax_code').data('auto') === 1 || $('#tax_code').data('auto') === '1';
                if (stillAuto) {
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
            lookupTaxByHsn(query);
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
            hideAppliedResults();
        }
    });

    function appliedType() {
        return $('#applied_on_type').val() || appliedConfig.type || 'category';
    }

    function hideAppliedResults() {
        $('#applied-results').attr('hidden', true).empty();
        $('#applied-search').val('');
    }

    function renderApplied() {
        var type = appliedType();
        var $picks = $('#applied-picks').empty();
        var $hidden = $('#applied-hidden').empty();
        appliedSelected.forEach(function (item) {
            var chip = $('<span class="tm-multi-chip"></span>');
            chip.append($('<span></span>').text(item.label || item.id));
            var remove = $('<button type="button" aria-label="Remove">&times;</button>');
            remove.attr('data-id', item.id);
            chip.append(remove);
            $picks.append(chip);
            $hidden.append($('<input type="hidden" name="applied_targets[]">').val(item.id));
        });
        $('#applied-search').attr('placeholder', (appliedConfig.placeholders || {})[type] || '');
        $('#applied-target-label').text((appliedConfig.labels || {})[type] || '');
        $('#applied-hint').text((appliedConfig.hints || {})[type] || '');
        $('#applied-empty-note').text(appliedSelected.length ? '' : ((appliedConfig.emptyNotes || {})[type] || ''));
    }

    function appliedIsSelected(id) {
        return appliedSelected.some(function (item) {
            return String(item.id) === String(id);
        });
    }

    function renderAppliedResults(rows) {
        var $box = $('#applied-results').empty();
        if (!rows || !rows.length) {
            $box.append($('<div class="tm-multi-empty"></div>').text(appliedConfig.none || ''));
            $box.removeAttr('hidden');
            return;
        }
        rows.forEach(function (row) {
            if (!row || row.id === undefined || row.id === null || row.id === '') {
                return;
            }
            var button = $('<button type="button"></button>');
            var label = row.label || String(row.id);
            var selected = appliedIsSelected(row.id);
            button.text((selected ? '✓ ' : '') + label);
            button.attr('data-id', row.id);
            button.attr('data-label', label);
            button.toggleClass('tm-multi-on', selected);
            $box.append(button);
        });
        if (!$box.children().length) {
            $box.attr('hidden', true);
            return;
        }
        $box.removeAttr('hidden');
    }

    function loadAppliedResults(query) {
        var token = ++appliedRequest;
        var type = appliedType();
        $.get(appliedConfig.lookupUrl, { type: type, q: query || '' }).done(function (rows) {
            if (token !== appliedRequest) {
                return;
            }
            renderAppliedResults(rows);
        });
    }

    function openAppliedResults() {
        loadAppliedResults($.trim($('#applied-search').val()));
    }

    function toggleAppliedItem(id, label) {
        if (appliedIsSelected(id)) {
            appliedSelected = appliedSelected.filter(function (item) {
                return String(item.id) !== String(id);
            });
        } else {
            appliedSelected.push({ id: String(id), label: label || String(id) });
        }
        renderApplied();
        markAppliedResults();
    }

    function markAppliedResults() {
        $('#applied-results button[data-id]').each(function () {
            var on = appliedIsSelected($(this).attr('data-id'));
            var label = $(this).attr('data-label') || '';
            $(this).toggleClass('tm-multi-on', on).text((on ? '✓ ' : '') + label);
        });
    }

    $('#applied_on_type').on('change', function () {
        appliedSelected = [];
        appliedRequest++;
        hideAppliedResults();
        renderApplied();
    });

    $('#applied-search').on('focus', function () {
        openAppliedResults();
    });

    $('#applied-search').on('input', function () {
        var query = $.trim($(this).val());
        clearTimeout(stockTimer);
        stockTimer = setTimeout(function () {
            loadAppliedResults(query);
        }, 250);
    });

    $('#applied-search').on('keydown', function (event) {
        if (event.key === 'Backspace' && $(this).val() === '' && appliedSelected.length) {
            appliedSelected.pop();
            renderApplied();
            markAppliedResults();
        }
        if (event.key === 'Escape') {
            hideAppliedResults();
        }
        if (event.key === 'Enter') {
            event.preventDefault();
            var $first = $('#applied-results button[data-id]').first();
            if ($first.length) {
                toggleAppliedItem($first.attr('data-id'), $first.attr('data-label'));
            }
        }
    });

    $('#applied-caret').on('mousedown', function (event) {
        event.preventDefault();
        var $box = $('#applied-results');
        if ($box.attr('hidden')) {
            $('#applied-search').trigger('focus');
            openAppliedResults();
        } else {
            hideAppliedResults();
        }
    });

    $('#applied-picks').on('click', 'button', function () {
        var id = $(this).attr('data-id');
        appliedSelected = appliedSelected.filter(function (item) {
            return String(item.id) !== String(id);
        });
        renderApplied();
        markAppliedResults();
    });

    $('#applied-results').on('mousedown', 'button', function (event) {
        event.preventDefault();
        toggleAppliedItem($(this).attr('data-id'), $(this).attr('data-label'));
    });

    renderApplied();

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
    if (!$('#tax_code').val()) {
        $('#tax_code').data('auto', 1);
        suggestCode();
    }
})();
</script>
