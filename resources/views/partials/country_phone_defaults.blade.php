@php
    $locationCountries = get_active_countries();
    $locationCountryId = (int) session('country_id', 0);
    $sessionCountryCode = strtolower((string) session('country_code', ''));
    $locationCountry = $locationCountries->firstWhere('id', $locationCountryId)
        ?: $locationCountries->first(function ($country) use ($sessionCountryCode) {
            return strtolower((string) $country->code) === $sessionCountryCode;
        })
        ?: $locationCountries->first();
    $locationCountryId = (int) optional($locationCountry)->id;
    $locationCountryCode = strtolower((string) optional($locationCountry)->code) ?: 'in';
    $locationCountryCodesById = $locationCountries->mapWithKeys(function ($country) {
        return [(string) $country->id => strtolower((string) $country->code)];
    });
@endphp

<script>
    (function (window, document) {
        const countryId = @json($locationCountryId ? (string) $locationCountryId : '');
        const countryCode = @json($locationCountryCode);
        const countryCodesById = @json($locationCountryCodesById);
        const enabledCountryCodes = Object.values(countryCodesById);
        const countrySelectSelector = 'select[name="country_id"], select[name$="_country_id"]';

        function isoForCountryId(id) {
            return countryCodesById[String(id || '')] || '';
        }

        function refreshSelect(select) {
            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.selectpicker) {
                window.jQuery(select).selectpicker('refresh');
            }
        }

        function syncPhoneFlags(select) {
            const iso2 = isoForCountryId(select.value);
            const scope = select.form || select.closest('.modal, .card, .container') || document;

            if (!iso2 || !window.intlTelInputGlobals || !window.intlTelInputGlobals.getInstance) {
                return;
            }

            scope.querySelectorAll('input[type="tel"]').forEach(function (input) {
                const instance = window.intlTelInputGlobals.getInstance(input);
                if (instance) {
                    instance.setCountry(iso2);
                }
            });
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
                if (select.value || !select.querySelector('option[value="' + countryId + '"]')) {
                    return;
                }

                select.value = countryId;
                refreshSelect(select);
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }

        window.CountryPhoneDefaults = {
            countryId: countryId,
            countryCode: countryCode,
            countryCodesById: countryCodesById,
            isoForCountryId: isoForCountryId,
            syncPhoneFlags: syncPhoneFlags,
            initializePhoneInput: initializePhoneInput,
            applyTo: defaultCountrySelects
        };

        document.addEventListener('change', function (event) {
            if (event.target && event.target.matches(countrySelectSelector)) {
                syncPhoneFlags(event.target);
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            defaultCountrySelects(document);
            initializePhoneInputs(document);

            if (window.MutationObserver) {
                new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        mutation.addedNodes.forEach(function (node) {
                            if (node.nodeType === 1) {
                                defaultCountrySelects(node);
                                initializePhoneInputs(node);
                            }
                        });
                    });
                }).observe(document.body, { childList: true, subtree: true });
            }
        });
    })(window, document);
</script>
