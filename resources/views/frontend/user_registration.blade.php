@extends('frontend.layouts.app')

@section('content')

    @if (!Session::has('step') || Session::get('step') == 1)

        @php
            session()->forget('temp_user_id');
            session()->forget('otp');
            Session()->put('step', 1);
        @endphp

        {{-- - //------------------------------ Registration 1 modal -----------------------// -- --}}

        <div class="modal fade login_form_popup" id="reg_gst_model" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content py-3">
                    <div class="modal-header">
                        <div class="heading">
                            <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                            <h5 class="modal-title" id="exampleModalLabel_phone">Company Details</h5>
                        </div>
                        {{-- <div class="purple_btn_close">
                            <button type="button" onclick="close_Phone_modal();" class="close p-1 px-3"
                                data-dismiss="modal" aria-label="Close"> v 
                            </button>
                        </div> --}}
                    </div>
                    <form id="reg_gst" action="{{ url(route('new.user.account.create', ['param' => 'gst'])) }}"
                        method="post">
                    
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="gst_no" class="col-form-label form-label">GST No:</label>
                                <input type="text" class="form-control form-control-lg" id="gst_no" name="gst_no"
                                 minlength="15" maxlength="15" placeholder="Please Enter GST No" required>
                            </div>
                        </div>
                        <div class="modal-footer" style="justify-content: end;">
                            {{-- <div class="blue_btn">
                                <button type="button" onclick="close_Phone_modal();" class="btn btn-secondary"
                                    data-dismiss="modal">Close</button>
                            </div> --}}
                            <div class="purple_btn">
                                <button type="submit" class="animate_button black1_buttons">Next <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    {{-- - //------------------------------  Registration 1 modal -----------------------// -- --}}

    @endif

    <div id="regModalContainer"></div>


@endsection

@section('custome-script')
    <script>
        $(document).ready(function() {

            function validate_form(step) {
                // Initialize validation for the specific form step
                initValidate(`#reg_model_form_${step}`);

                // Attach the submit event handler
                $(`#reg_model_form_${step}`).on('submit', function (e) {
                    var form = $(this);
                    ajax_form_submit(e, form, function (response) {
                        responseHandler(step, response);
                    });
                });

                // Define the response handler function
                function responseHandler(step, response) {
                    modelRendStep(); // Perform the required step rendering
                }

                ['phone-code', 'tel_number', 'whats_app_no'].forEach(function (id) {
                    const element = document.getElementById(id);
                    if (element) {
                        element.addEventListener('input', function (event) {
                            this.value = this.value.replace(/[^0-9+ ]/g, '');
                        });
                    }
                });

                AIZ.plugins.bootstrapSelect('refresh'); 
            }

            function intil_input(name) {
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

                if(name === 'whats_app_no'){
                    var country_selected = "{{ getSelectedCountry('whats_app_no_meta') }}"; 
                } else {
                    var country_selected = "{{ getSelectedCountry('phone_code_meta') }}"; 
                }


                if(country_selected !== 'null'){
                    iti1.setCountry(country_selected); // 'in' is the ISO2 code for India
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


            // Function to render the modal for the current step
            function modelRendStep() {
                $.ajax({
                    url: "{{ route('get-reg-step') }}", // Simplified route helper
                    method: 'GET',
                    success: function (response) {
                        if (response.success) {
                            const step = response.step;

                            // Inject dynamic content and show the modal for the given step
                            $('#regModalContainer').html(response.html);
                            const backdrop = document.querySelector(".modal-backdrop");
                            if (backdrop) {
                                backdrop.remove(); // Removes only the backdrop
                                backdrop.parentElement?.remove(); // Removes the full div if the backdrop is inside another div
                            }

                            $(`#reg_model_${step}`).modal('show');


                            validate_form(step);
                            intil_input('phone_code');
                            intil_input('whats_app_no');


                        } else {
                            console.error('Error:', response.message || 'An error occurred.');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX error:', error);
                    },
                });
            }

            // Function to check the registration step
            function checkRegStep() {
                $.ajax({
                    url: "{{ route('get-reg-step') }}", // Simplified route helper
                    method: 'GET',
                    success: function (response) {
                        if (response.success) {
                            const step = response.step;

                            if (step === 1) {
                                // Show the first modal if the step is 1
                                $('#reg_gst_model').modal('show');
                            } else {
                                // Call modelRendStep for other steps
                                modelRendStep();
                            }
                        } else {
                            console.error('Error:', response.message || 'An error occurred.');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX error:', error);
                    },
                });
            }

            // Initial check when the document is ready
            checkRegStep();

            initValidate('#reg_gst');
            $('#reg_gst').on('submit', function(e) {
                var form = $(this);
                ajax_form_submit(e, form, responseHandler_reg_gst);
            });

            var responseHandler_reg_gst = function(response) {

                $(`#reg_gst_model`).modal('hide');
                modelRendStep();

            };

        });

        function back_to_prev_reg() {
            var csrfToken = '{{ csrf_token() }}';
            $.ajax({
                url: "{{ route('previous.reg.form') }}", // Simplified route helper
                method: 'GET',
                success: function (response) {
                    if (response.success) {

                        location.reload();

                    } else {
                        console.error('Error:', response.message || 'An error occurred.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                },
            });
        }

        function resendOTPButton_Phone() {
            var csrfToken = '{{ csrf_token() }}';
            $.ajax({
                url: "{{ route('create.new.user.registration.resend.phone.verify') }}",
                type: "Post",
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    AIZ.plugins.notify('success', response.message);
                },
                error: function(xhr, status, error) {
                    AIZ.plugins.notify('danger', response.message);
                }
            });
        }

        let login_page_reg = "{{ route('user.login') }}";

        function close_and_reload_reg (){
            $('#reg_model_8').modal('hide');
            setTimeout(function() {
                // location.reload();
                window.location.href = login_page_reg;
            }, 100);
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
@endsection
