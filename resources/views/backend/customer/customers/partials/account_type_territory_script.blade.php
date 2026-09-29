<script>
(function () {
    function norm(value) {
        return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function readConfig() {
        var node = document.getElementById('customer-place-of-supply-data');
        if (!node) {
            return { companies: [], states: [], taxes: [], utgstStates: [] };
        }
        try {
            return JSON.parse(node.textContent || '{}');
        } catch (e) {
            return { companies: [], states: [], taxes: [], utgstStates: [] };
        }
    }

    function selectedText(selector) {
        var el = document.querySelector(selector);
        if (!el || el.selectedIndex < 0) {
            return '';
        }
        return el.options[el.selectedIndex].text || '';
    }

    function selectedValue(selector) {
        var el = document.querySelector(selector);
        return el ? String(el.value || '') : '';
    }

    function detectSeller(config) {
        var companies = config.companies || [];
        var states = config.states || [];
        var seller = companies[0] || null;
        var sellerState = '';
        companies.forEach(function (company) {
            var address = norm(company.address);
            states.forEach(function (state) {
                var name = norm(state.name);
                if (name && address.indexOf(name) !== -1 && name.length > sellerState.length) {
                    seller = company;
                    sellerState = state.name;
                }
            });
        });
        var sellerCountry = 'India';
        if (seller && norm(seller.address).indexOf('india') === -1 && /[a-z]/i.test(seller.address || '') && sellerState === '') {
            sellerCountry = '';
        }
        return { company: seller, state: sellerState, country: sellerCountry };
    }

    function buyerCountry() {
        var text = selectedText('#country_id_business');
        if (!text || norm(text) === 'select country' || norm(text).indexOf('select') === 0) {
            return 'India';
        }
        return text;
    }

    function matchingTax(taxes, mode) {
        var list = taxes || [];
        var found = null;
        list.forEach(function (tax) {
            if (found) {
                return;
            }
            var blob = norm((tax.tax_code || '') + ' ' + (tax.description || '') + ' ' + (tax.kind || ''));
            var cgst = parseFloat(tax.sale_cgst || 0);
            var sgst = parseFloat(tax.sale_sgst || 0);
            var igst = parseFloat(tax.sale_igst || 0);
            if (mode === 'cgst' && cgst > 0 && sgst > 0) {
                found = tax;
            } else if (mode === 'igst' && igst > 0) {
                found = tax;
            } else if (mode === 'utgst' && blob.indexOf('utgst') !== -1) {
                found = tax;
            } else if (mode === 'lut' && (blob.indexOf('lut') !== -1 || norm(tax.kind) === 'exempted')) {
                found = tax;
            }
        });
        return found;
    }

    function rateText(tax) {
        if (!tax) {
            return 'Tax Master rate: not found';
        }
        var parts = [];
        if (tax.tax_code) {
            parts.push(tax.tax_code);
        }
        if (parseFloat(tax.sale_cgst || 0) > 0) {
            parts.push('CGST ' + tax.sale_cgst + '%');
        }
        if (parseFloat(tax.sale_sgst || 0) > 0) {
            parts.push('SGST ' + tax.sale_sgst + '%');
        }
        if (parseFloat(tax.sale_igst || 0) > 0) {
            parts.push('IGST ' + tax.sale_igst + '%');
        }
        if (!parts.length) {
            parts.push('Tax ' + (tax.sale_tax || 0) + '%');
        }
        return parts.join(' / ');
    }

    function refreshTax() {
        var config = readConfig();
        var seller = detectSeller(config);
        var buyerState = selectedText('#state_id_business');
        if (!buyerState || norm(buyerState).indexOf('select') === 0) {
            buyerState = '';
        }
        var buyerCountryName = buyerCountry();
        var sellerLine = document.getElementById('customer-seller-line');
        var taxLabel = document.getElementById('customer-tax-label');
        var taxRate = document.getElementById('customer-tax-rate');
        var international = document.getElementById('customer-international-tax');
        var bankNote = document.getElementById('customer-tax-beside-bank');
        if (!taxLabel) {
            return;
        }

        if (sellerLine) {
            var sellerName = seller.company ? seller.company.name : 'Company Master';
            sellerLine.textContent = 'Seller: ' + sellerName + (seller.state ? ', ' + seller.state : ', state not found in company address');
        }

        var label = '';
        var mode = '';
        var internationalSale = norm(buyerCountryName) !== '' && norm(buyerCountryName) !== 'india';
        if (internationalSale) {
            international.classList.remove('d-none');
            var choice = document.querySelector('input[name="international_tax_choice"]:checked');
            var choiceValue = choice ? choice.value : 'igst';
            if (choiceValue === 'lut') {
                label = 'International - LUT';
                mode = 'lut';
            } else {
                label = 'International - IGST';
                mode = 'igst';
            }
        } else {
            international.classList.add('d-none');
            var sameState = seller.state && norm(seller.state) === norm(buyerState);
            var sellerIsUt = (config.utgstStates || []).indexOf(norm(seller.state)) !== -1;
            if (sameState) {
                label = 'Intra-State - CGST + SGST';
                mode = 'cgst';
            } else if (sellerIsUt && buyerState) {
                label = 'Union Territory - UTGST';
                mode = 'utgst';
            } else if (buyerState) {
                label = 'Inter-State - IGST';
                mode = 'igst';
            } else {
                label = 'Select the business state to calculate tax';
            }
        }

        taxLabel.textContent = label;
        if (taxRate) {
            taxRate.textContent = mode ? rateText(matchingTax(config.taxes, mode)) : '';
        }
        if (bankNote) {
            bankNote.textContent = label;
        }
    }

    function syncNotInList(select) {
        var inputSelector = select.getAttribute('data-custom-input');
        if (!inputSelector) {
            return;
        }
        var input = document.querySelector(inputSelector);
        if (!input) {
            return;
        }
        var show = select.value === '__not_in_list__';
        input.classList.toggle('d-none', !show);
        if (!show) {
            input.value = '';
        }
    }

    function territoryClass() {
        var config = readConfig();
        var seller = detectSeller(config);
        var buyerState = selectedText('#state_id_business');
        if (!buyerState || norm(buyerState).indexOf('select') === 0) {
            buyerState = '';
        }
        var buyerCountryName = buyerCountry();
        if (norm(buyerCountryName) !== '' && norm(buyerCountryName) !== 'india') {
            return 'International';
        }
        if (!buyerState) {
            return '';
        }
        var sameState = seller.state && norm(seller.state) === norm(buyerState);
        var sellerIsUt = (config.utgstStates || []).indexOf(norm(seller.state)) !== -1;
        if (sameState) {
            return 'Intra-State';
        }
        if (sellerIsUt) {
            return 'UT';
        }
        return 'Inter-State';
    }

    function syncTerritoryFromState(force) {
        var territory = document.getElementById('territory');
        if (!territory || territory.value === '__not_in_list__') {
            return;
        }
        if (!force && territory.value) {
            return;
        }
        var value = territoryClass();
        if (!value) {
            return;
        }
        territory.value = value;
        syncNotInList(territory);
        if (window.jQuery && window.jQuery.fn.selectpicker) {
            window.jQuery(territory).selectpicker('refresh');
        }
    }

    function syncAccountName() {
        var source = document.getElementById('account_name_business');
        var target = document.getElementById('customer-account-used-name');
        if (!source || !target) {
            return;
        }
        target.textContent = source.value.trim() || 'Account name';
    }

    function ensureBankTaxNote() {
        var accountNo = document.getElementById('account_no_business');
        if (!accountNo || document.getElementById('customer-tax-beside-bank')) {
            return;
        }
        var note = document.createElement('div');
        note.id = 'customer-tax-beside-bank';
        note.className = 'small text-muted mt-1';
        accountNo.parentNode.appendChild(note);
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        var international = document.getElementById('customer-international-tax');
        if (!form || !international || !international.classList.contains('d-none')) {
            return;
        }
        form.querySelectorAll('[name="international_tax_choice"]').forEach(function (input) {
            input.checked = false;
            input.disabled = true;
        });
    });

    document.addEventListener('change', function (event) {
        var target = event.target;
        if (!target) {
            return;
        }
        if (target.classList && target.classList.contains('js-not-in-list')) {
            syncNotInList(target);
        }
        if (target.id === 'state_id_business' || target.id === 'country_id_business') {
            syncTerritoryFromState(true);
        }
        if (target.id === 'state_id_business' || target.id === 'country_id_business' || target.name === 'international_tax_choice') {
            refreshTax();
        }
    });

    document.addEventListener('input', function (event) {
        if (event.target && event.target.id === 'account_name_business') {
            syncAccountName();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        ensureBankTaxNote();
        document.querySelectorAll('select.js-not-in-list').forEach(syncNotInList);
        syncTerritoryFromState(false);
        syncAccountName();
        refreshTax();
    });
})();
</script>
