@php
    $locationCountries = get_active_countries();
    $locationCountry = detected_country()
        ?: $locationCountries->first();
    $locationCountryId = (int) optional($locationCountry)->id;
    $locationCountryCode = strtolower((string) optional($locationCountry)->code) ?: 'in';
    $locationDialCode = detected_dial_code();
    $locationCountryCodesById = $locationCountries->mapWithKeys(function ($country) {
        return [(string) $country->id => strtolower((string) $country->code)];
    });
@endphp

<script>
    (function (window, document) {
        const countryId = @json($locationCountryId ? (string) $locationCountryId : '');
        const countryCode = @json($locationCountryCode);
        const dialCode = @json($locationDialCode);
        const countryCodesById = @json($locationCountryCodesById);
        const enabledCountryCodes = Object.values(countryCodesById);
        const countrySelectSelector = [
            'select[name="country_id"]',
            'select[name$="_country_id"]',
            'select[name$="_country_id[]"]',
            'select.js-country-select',
            'select.country-select'
        ].join(', ');
        const skipNamePattern = /(export_|sort_|filter_|final_destination_)/i;
        const skipIds = {
            countryDropdown: true,
            sort_country: true
        };

        function isoForCountryId(id) {
            return countryCodesById[String(id || '')] || '';
        }

        function dialCodeForIso(iso) {
            if (dialCode && iso === countryCode) {
                return String(dialCode);
            }
            if (!window.intlTelInputGlobals || !window.intlTelInputGlobals.getCountryData) {
                return String(dialCode || '');
            }
            const match = window.intlTelInputGlobals.getCountryData().find(function (country) {
                return country.iso2 === iso;
            });
            return match ? String(match.dialCode) : String(dialCode || '');
        }

        function refreshSelect(select) {
            if (!window.jQuery) {
                return;
            }
            const $el = window.jQuery(select);
            if ($el.data('selectpicker') && $el.selectpicker) {
                $el.selectpicker('val', select.value);
                $el.selectpicker('refresh');
            }
            if ($el.hasClass('select2-hidden-accessible') && $el.select2) {
                $el.val(select.value).trigger('change.select2');
            }
        }

        function isFilterSelect(select) {
            if (!select) {
                return true;
            }
            if (select.dataset.skipCountryDefault === '1' || select.hasAttribute('data-skip-country-default')) {
                return true;
            }
            if (skipIds[select.id] || skipNamePattern.test(select.name || '')) {
                return true;
            }
            if (select.multiple) {
                return true;
            }
            if (select.closest('.aiz-filter, [data-filter], .search-form, .sort-by')) {
                return true;
            }
            const form = select.form;
            if (form && String(form.method || '').toLowerCase() === 'get') {
                return true;
            }
            const first = select.querySelector('option');
            const firstText = first ? String(first.textContent || '').toLowerCase() : '';
            if (first && !first.value && /all countr/.test(firstText)) {
                return true;
            }
            return false;
        }

        function hasExplicitCountry(select) {
            const dataSelected = select.getAttribute('data-selected');
            if (dataSelected) {
                return true;
            }
            const selectedOption = select.querySelector('option[selected]');
            return !!(selectedOption && selectedOption.value);
        }

        function syncPhoneFlags(select) {
            const iso2 = isoForCountryId(select.value);
            const scope = select.form || select.closest('.modal, .card, .container, form') || document;

            if (!iso2 || !window.intlTelInputGlobals || !window.intlTelInputGlobals.getInstance) {
                return;
            }

            scope.querySelectorAll('input[type="tel"], input.iti__tel-input').forEach(function (input) {
                if (phoneHasExplicitCountry(input)) {
                    return;
                }
                const instance = window.intlTelInputGlobals.getInstance(input);
                if (instance) {
                    instance.setCountry(iso2);
                    input.dispatchEvent(new Event('countrychange', { bubbles: true }));
                }
            });
        }

        function phoneHasExplicitCountry(input) {
            if ((input.value || '').replace(/\D/g, '').length > 0) {
                return true;
            }
            const form = input.form || document;
            const name = input.getAttribute('name') || input.id || '';
            const meta = form.querySelector('input[name="' + name + '_meta"], input[name="' + name + '_iso"]');
            if (meta && meta.value) {
                return true;
            }
            return false;
        }

        function initializePhoneInput(input) {
            if (!input || !window.intlTelInput || input.dataset.countryPhoneInitialized === '1') {
                return null;
            }

            const instance = intlTelInput(input, {
                separateDialCode: true,
                utilsScript: @json(static_asset('assets/js/intlTelutils.js') . '?1590403638580'),
                onlyCountries: enabledCountryCodes,
                initialCountry: countryCode
            });
            const dialCodeTarget = input.dataset.dialCodeTarget
                ? (input.form || document).querySelector(input.dataset.dialCodeTarget)
                : null;

            function syncDialCode() {
                if (!dialCodeTarget) return;
                const selected = instance.getSelectedCountryData();
                dialCodeTarget.value = selected.dialCode ? '+' + selected.dialCode : '';
            }

            input.dataset.countryPhoneInitialized = '1';
            instance.setCountry(countryCode);
            input.addEventListener('countrychange', syncDialCode);
            syncDialCode();

            return instance;
        }

        function initializePhoneInputs(root) {
            if (root.matches && root.matches('[data-country-phone-default]')) {
                initializePhoneInput(root);
            }
            if (root.querySelectorAll) {
                root.querySelectorAll('[data-country-phone-default]').forEach(initializePhoneInput);
            }
        }

        function isoForPhoneScope(input) {
            const scope = input.form || input.closest('.modal, .card, form') || document;
            const select = scope.querySelector(countrySelectSelector);
            if (select && !isFilterSelect(select) && select.value) {
                return isoForCountryId(select.value) || countryCode;
            }
            return countryCode;
        }

        function applyToPhoneInstances(root) {
            if (!countryCode || !window.intlTelInputGlobals || !window.intlTelInputGlobals.getInstance) {
                return;
            }

            const inputs = [];
            if (root.matches && root.matches('input')) {
                inputs.push(root);
            }
            if (root.querySelectorAll) {
                root.querySelectorAll('input[type="tel"], input.iti__tel-input, .iti input').forEach(function (input) {
                    inputs.push(input);
                });
            }

            inputs.forEach(function (input) {
                if (input.dataset.skipCountryDefault === '1' || phoneHasExplicitCountry(input)) {
                    return;
                }
                const instance = window.intlTelInputGlobals.getInstance(input);
                if (!instance) {
                    return;
                }
                const iso = isoForPhoneScope(input);
                const current = instance.getSelectedCountryData();
                if (current && current.iso2 === iso) {
                    return;
                }
                try {
                    instance.setCountry(iso);
                    input.dispatchEvent(new Event('countrychange', { bubbles: true }));
                } catch (e) {}
            });
        }

        function defaultCountrySelects(root) {
            if (!countryId) {
                return;
            }

            const selects = [];
            if (root.matches && root.matches(countrySelectSelector)) {
                selects.push(root);
            }
            if (root.querySelectorAll) {
                root.querySelectorAll(countrySelectSelector).forEach(function (select) {
                    selects.push(select);
                });
            }

            selects.forEach(function (select) {
                if (isFilterSelect(select) || hasExplicitCountry(select)) {
                    return;
                }
                if (!select.querySelector('option[value="' + countryId + '"]')) {
                    return;
                }
                if (String(select.value) === String(countryId) && select.dataset.countryDefaultApplied === '1') {
                    return;
                }

                select.value = countryId;
                select.setAttribute('data-selected', countryId);
                select.dataset.countryDefaultApplied = '1';
                refreshSelect(select);
                select.dispatchEvent(new Event('change', { bubbles: true }));
                if (window.jQuery) {
                    window.jQuery(select).trigger('change');
                }
            });
        }

        function defaultDialCodeInputs(root) {
            const selector = [
                'input[name="country_code"]',
                'input[name="country_code_business"]',
                'input[name="country_code_personal"]',
                'input[name="phone_country_code"]',
                'input[name="mobile_country_code"]',
                'input[name^="country_code_"]',
                'input[name$="_country_code"]'
            ].join(', ');

            const inputs = [];
            if (root.matches && root.matches(selector)) {
                inputs.push(root);
            }
            if (root.querySelectorAll) {
                root.querySelectorAll(selector).forEach(function (input) {
                    inputs.push(input);
                });
            }

            inputs.forEach(function (input) {
                if (input.dataset.skipCountryDefault === '1') {
                    return;
                }
                if (String(input.value || '').trim() !== '') {
                    return;
                }
                const code = dialCodeForIso(isoForPhoneScope(input));
                if (!code) {
                    return;
                }
                input.value = input.name && /phone_country_code|mobile_country_code/.test(input.name)
                    ? '+' + code
                    : code;
            });
        }

        function applyTo(root) {
            root = root || document;
            defaultCountrySelects(root);
            initializePhoneInputs(root);
            applyToPhoneInstances(root);
            defaultDialCodeInputs(root);
        }

        window.CountryPhoneDefaults = {
            countryId: countryId,
            countryCode: countryCode,
            dialCode: dialCode,
            countryCodesById: countryCodesById,
            isoForCountryId: isoForCountryId,
            syncPhoneFlags: syncPhoneFlags,
            initializePhoneInput: initializePhoneInput,
            applyTo: applyTo
        };

        document.addEventListener('change', function (event) {
            if (event.target && event.target.matches(countrySelectSelector) && !isFilterSelect(event.target)) {
                syncPhoneFlags(event.target);
            }
        });

        function start() {
            applyTo(document);
            [50, 200, 600, 1200].forEach(function (delay) {
                setTimeout(function () {
                    applyTo(document);
                }, delay);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start);
        } else {
            start();
        }

        if (window.jQuery) {
            window.jQuery(document).on('shown.bs.modal', function (event) {
                applyTo(event.target || document);
            });
        }

        if (window.MutationObserver) {
            var mutationTimer = null;
            new MutationObserver(function () {
                clearTimeout(mutationTimer);
                mutationTimer = setTimeout(function () {
                    applyTo(document);
                }, 80);
            }).observe(document.body || document.documentElement, { childList: true, subtree: true });
        }
    })(window, document);
</script>
