<script>
    $(function () {
        var lookupTimer;
        var pincodeLookupTimer;
        var customerLookupUrl = @json(route('leads.customer_by_phone'));
        var stateUrl = @json(route('get-state'));
        var cityUrl = @json(route('get-city'));
        var locationUrl = @json(route('get-location'));

        function refreshPicker($select) {
            $select.selectpicker('refresh');
        }

        function parseLocationOptions(response) {
            if (typeof response !== 'string') return response;
            try {
                return JSON.parse(response);
            } catch (error) {
                return response;
            }
        }

        function loadStates(countryId, selectedStateId) {
            var $state = $('#lead_state_id');
            var $city = $('#lead_city_id');
            $state.html('<option value="">{{ translate('Select State') }}</option>');
            $city.html('<option value="">{{ translate('Select City') }}</option>');
            refreshPicker($state);
            refreshPicker($city);

            if (!countryId) return $.Deferred().resolve().promise();

            return $.post(stateUrl, {
                _token: $('meta[name="csrf-token"]').attr('content'),
                country_id: countryId
            }).done(function (response) {
                $state.html(parseLocationOptions(response));
                if (selectedStateId) $state.val(String(selectedStateId));
                refreshPicker($state);
            });
        }

        function loadCities(stateId, selectedCityId) {
            var $city = $('#lead_city_id');
            $city.html('<option value="">{{ translate('Select City') }}</option>');
            refreshPicker($city);

            if (!stateId) return $.Deferred().resolve().promise();

            return $.post(cityUrl, {
                _token: $('meta[name="csrf-token"]').attr('content'),
                state_id: stateId
            }).done(function (response) {
                $city.html(parseLocationOptions(response));
                if (selectedCityId) $city.val(String(selectedCityId));
                refreshPicker($city);
            });
        }

        $('#lead_country_id').on('change', function () {
            loadStates(this.value);
        });

        $('#lead_state_id').on('change', function () {
            loadCities(this.value);
        });

        function findSelectValueByLabel($select, label) {
            var normalized = String(label || '').trim().toLowerCase();
            var value = '';

            $select.find('option').each(function () {
                if (String($(this).text()).trim().toLowerCase() === normalized) {
                    value = $(this).val();
                    return false;
                }
            });

            return value;
        }

        function applyLocationFromPincode(data) {
            var $country = $('#lead_country_id');
            var $state = $('#lead_state_id');
            var $city = $('#lead_city_id');
            var selectedCountry = data.country_id ? String(data.country_id) : $country.val();
            var selectedState = data.state_id ? String(data.state_id) : '';
            var selectedCity = data.city_id ? String(data.city_id) : '';

            if (selectedCountry) {
                $country.val(selectedCountry);
                refreshPicker($country);
            }

            loadStates(selectedCountry, selectedState).done(function () {
                if (!selectedState && data.state_name) {
                    selectedState = findSelectValueByLabel($state, data.state_name);
                    if (selectedState) {
                        $state.val(selectedState);
                        refreshPicker($state);
                    }
                }

                if (!selectedState) {
                    return;
                }

                loadCities(selectedState, selectedCity).done(function () {
                    if (!selectedCity && data.city_name) {
                        selectedCity = findSelectValueByLabel($city, data.city_name);
                        if (selectedCity) {
                            $city.val(selectedCity);
                            refreshPicker($city);
                        }
                    }
                });
            });
        }

        $('#lead_pincode').on('input blur', function () {
            var postalCode = this.value.trim();
            clearTimeout(pincodeLookupTimer);

            if (postalCode.length < 5) {
                return;
            }

            pincodeLookupTimer = setTimeout(function () {
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: locationUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        postal_code: postalCode,
                        country_id: $('#lead_country_id').val() || ''
                    }
                }).done(function (data) {
                    applyLocationFromPincode(data || {});
                }).fail(function () {
                    if (window.AIZ && AIZ.plugins && AIZ.plugins.notify) {
                        AIZ.plugins.notify('danger', '{{ translate('Unable to fetch location details') }}');
                    }
                });
            }, 350);
        });

        function fillCustomer(customer) {
            $('[name="name"]').val(customer.name || '');
            $('[name="company_name"]').val(customer.company_name || '');
            $('[name="email"]').val(customer.email || '');
            if (customer.phone) $('[name="phone"]').val(customer.phone);
            if (customer.whatsapp_number) $('[name="whatsapp_number"]').val(customer.whatsapp_number);
            $('#lead_address').val(customer.address || '');
            $('#lead_pincode').val(customer.pincode || '');
            $('#lead_country_id').val(customer.country_id ? String(customer.country_id) : '');
            refreshPicker($('#lead_country_id'));

            loadStates(customer.country_id, customer.state_id).done(function () {
                loadCities(customer.state_id, customer.city_id);
            });
        }

        $('#lead_phone').on('input blur', function () {
            var phone = this.value.trim();
            clearTimeout(lookupTimer);

            if (phone.replace(/\D/g, '').length < 5) {
                $('#lead_customer_lookup_status').text('');
                return;
            }

            lookupTimer = setTimeout(function () {
                $('#lead_customer_lookup_status').removeClass('text-success text-muted').addClass('text-muted')
                    .text('{{ translate('Searching business customer...') }}');

                $.getJSON(customerLookupUrl, { phone: phone }).done(function (response) {
                    if (response.found && response.customer) {
                        fillCustomer(response.customer);
                        $('#lead_customer_lookup_status').removeClass('text-muted').addClass('text-success')
                            .text('{{ translate('Business customer details loaded') }}');
                    } else {
                        $('#lead_customer_lookup_status').removeClass('text-success').addClass('text-muted')
                            .text('{{ translate('No business customer found') }}');
                    }
                }).fail(function () {
                    $('#lead_customer_lookup_status').removeClass('text-success').addClass('text-danger')
                        .text('{{ translate('Customer lookup failed') }}');
                });
            }, 450);
        });

        $('#lead_source_name').on('focus click input', function () {
            var search = this.value.trim().toLowerCase();
            $('#lead_source_options').show();
            $('#lead_source_id').val('');
            $('#lead_source_options .lead-combo-option').each(function () {
                $(this).toggle(!search || String($(this).data('name')).toLowerCase().includes(search));
            });
        });

        $(document).on('mousedown', '#lead_source_options .lead-combo-option', function (event) {
            event.preventDefault();
            $('#lead_source_name').val($(this).data('name'));
            $('#lead_source_id').val($(this).data('id'));
            $('#lead_source_options').hide();
        });

        $(document).on('click', function (event) {
            if (!$(event.target).closest('#lead_source_combo').length) {
                $('#lead_source_options').hide();
            }
        });
    });
</script>
