<script type="text/javascript">

    function submitShippingInfoForm(el) {
        var email = $("input[name='email']").val();
        var phone = $("input[name='country_code']").val()+$("input[name='phone']").val();
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('guest_customer_info_check')}}",
            type: 'POST',
            data: {
                email : email,
                phone : phone
            },
            success: function (response) {
                if(response ==  1){
                    $('#login_modal').modal();
                    AIZ.plugins.notify('warning', '{{ translate('You already have an account with this information. Please Login first.') }}');
                }
                else{
                    $('#shipping_info_form').submit();
                }
            }
        });
    }

    function add_new_address(){
        $('#new-address-modal').modal('show');
    }


    function intil_input_edit(name, phone_meta = null) {
        // Select the input element dynamically based on the name parameter
        var inputElement = document.querySelector(`#${name}`);

        // Initialize the intlTelInput plugin
        var iti1 = intlTelInput(inputElement, {
            separateDialCode: true,
            utilsScript: "{{ static_asset('assets/js/intlTelutils.js') }}?1590403638580",
            onlyCountries: @php echo json_encode(get_active_countries()->pluck('code')->toArray()) @endphp,
            customPlaceholder: function (selectedCountryPlaceholder, selectedCountryData) {
                if (selectedCountryData.iso2 === 'bd') {
                    return "01xxxxxxxxx"; // Custom placeholder for Bangladesh
                }
                return selectedCountryPlaceholder;
            }
        });

        // // Set default country code to +91 (India)
        // iti1.setCountry('in'); // 'in' is the ISO2 code for India


        if(phone_meta !== 'null'){
            iti1.setCountry(phone_meta); // 'in' is the ISO2 code for India
        } else {
            // Set default country code to +91 (India)
            iti1.setCountry('in'); // 'in' is the ISO2 code for India
        }

        // Update the hidden input with the selected country's dial code
        var countryData = iti1.getSelectedCountryData();
        document.querySelector(`input[name="country_code_${name}"]`).value = countryData.dialCode;
        document.querySelector(`input[name="${name}_meta"]`).value = countryData.iso2;

        // Update the country code when the country changes
        inputElement.addEventListener("countrychange", function () {
            var updatedCountryData = iti1.getSelectedCountryData();
            document.querySelector(`input[name="country_code_${name}"]`).value = updatedCountryData.dialCode;
            document.querySelector(`input[name="${name}_meta"]`).value = updatedCountryData.iso2;
        });
    }



    function edit_address(address) {
        var url = '{{ route("addresses.edit", ":id") }}';
        url = url.replace(':id', address);

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: url,
            type: 'GET',
            success: function (response) {
                $('#edit_modal_body').html(response.html);
                $('#edit-address-modal').modal('show');

                const phone_meta = $('#edit-address-modal').find('#phone_meta').val();

                if (phone_meta) {
                    intil_input_edit('phone_code_addr_edit', phone_meta);
                } else {
                    intil_input_edit('phone_code_addr_edit');
                }
                AIZ.plugins.bootstrapSelect('refresh');

                @if (get_setting('google_map') == 1)
                    var lat     = -33.8688;
                    var long    = 151.2195;

                    if(response.data.address_data.latitude && response.data.address_data.longitude) {
                        lat     = parseFloat(response.data.address_data.latitude);
                        long    = parseFloat(response.data.address_data.longitude);
                    }

                    initialize(lat, long, 'edit_');
                @endif
            }
        });
    }

    $(document).on('change', '[name=country_id]', function() {
        var country_id = $(this).val();
        get_states(country_id);
    });

    $(document).on('change', '[name=state_id]', function() {
        var state_id = $(this).val();
        get_city(state_id);
    });

    function get_states(country_id) {
        $('[name="state"]').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-state')}}",
            type: 'POST',
            data: {
                country_id  : country_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                if(obj != '') {
                    $('[name="state_id"]').html(obj);
                    AIZ.plugins.bootstrapSelect('refresh');
                }
            }
        });
    }

    function get_city(state_id) {
        $('[name="city"]').html("");
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: "{{route('get-city')}}",
            type: 'POST',
            data: {
                state_id: state_id
            },
            success: function (response) {
                var obj = JSON.parse(response);
                if(obj != '') {
                    $('[name="city_id"]').html(obj);
                    AIZ.plugins.bootstrapSelect('refresh');
                }
            }
        });
    }

</script>
