@extends('frontend.layouts.app')

@section('content')

    @if (!Session::has('step') || Session::get('step') == 1)

        @php
            session()->forget('temp_user_id');
            session()->forget('otp');
            Session()->put('step', 1);
        @endphp

        {{-- - //------------------------------ Registration 1 modal -----------------------// -- --}}

        {{-- <div class="modal fade login_form_popup" id="reg_model_1" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content py-3">
                    <div class="modal-header">
                        <div class="heading">
                            <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                            <h5 class="modal-title" id="exampleModalLabel_phone">Company Details</h5>
                        </div>
                        <div class="purple_btn_close">
                            <button type="button" onclick="close_Phone_modal();" class="close p-1 px-3"
                                data-dismiss="modal" aria-label="Close"> v 
                            </button>
                        </div>
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
                            <div class="blue_btn">
                                <button type="button" onclick="close_Phone_modal();" class="btn btn-secondary"
                                    data-dismiss="modal">Close</button>
                            </div>
                            <div class="purple_btn">
                                <button type="submit" class="animate_button black1_buttons">Next <img src="{{ static_asset('assets/img/arrow_left.svg') }}" /></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div> --}}

    {{-- - //------------------------------  Registration 1 modal -----------------------// -- --}}

    @endif

    <div id="regModalContainer"></div>


@endsection

@section('custome-script')
    <script>
        $(document).ready(function() {

            // ---------------- Gst verify --------------------------- //

            // function appendVerifyButton() {
            //     const verifyBtnId = 'verify-gst-btn';

            //     if ($('#' + verifyBtnId).length === 0) {
            //         const verifyBtn = $('<button>')
            //             .attr('type', 'button')
            //             .attr('id', verifyBtnId)
            //             .attr('onclick', 'verifyGST()')
            //             .addClass('btn btn-success btn-sm ml-2')
            //             .text('Verify');

            //         $('#gst_no').after(verifyBtn);
            //     }
            // }

            function checkAndAppendButton() {
                const verifyBtnId = 'verify-gst-btn';
                const val = $('#gst_no').val();
                if (val.length === 15) {
                    verifyDocument('gst_no', 'gst-validate', 15);
                    // appendVerifyButton();
                // } else {
                //     $('#' + verifyBtnId).remove();
                }
            }

            // Run on input/paste/change
            $('body').on('input', '#gst_no', function () {
                setTimeout(checkAndAppendButton, 50); // delay for paste to take effect
            });


            // ---------------- Gst verify --------------------------- //

            // ---------------- IEC verify --------------------------- //

            function checkAndAppendIECButton() {
                const verifyBtnId = 'verify-iec-btn';
                const val = $('#iec_no').val().trim();
                if (val.length === 10) {
                    verifyDocument('iec_no', 'iec-validate', 10);
                //     appendVerifyIECButton();
                // } else {
                //     $('#' + verifyBtnId).remove();
                }
            }

            // Watch changes on IEC input
            $('body').on('input', '#iec_no', function () {
                setTimeout(checkAndAppendIECButton, 50); // wait for paste/input value
            });


            // ---------------- ICE verify --------------------------- //

            // ---------------- aadhaar_no verify --------------------------- //

            function checkAndAppendaadhaarButton() {
                const val = $('#aadhaar_no').val().trim();
                if (val.length === 12) {
                    verifyDocument('aadhaar_no', 'aadhaar-validate', 12);
                }
            }

            // Watch changes on IEC input
            $('body').on('input', '#aadhaar_no', function () {
                setTimeout(checkAndAppendaadhaarButton, 50); // wait for paste/input value
            });


            // ---------------- aadhaar_no verify --------------------------- //

            // ---------------- pan_no verify --------------------------- //

            function checkAndAppendpan_noButton() {
                const val = $('#pan_no').val().trim();
                if (val.length === 10) {
                    // verifyDocument('pan_no', 'pan-validate', 10, /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i);
                    verifyDocument('pan_no', 'pan-validate', 10);
                }
            }

            // Watch changes on IEC input
            $('body').on('input', '#pan_no', function () {
                setTimeout(checkAndAppendpan_noButton, 50); // wait for paste/input value
            });


            // ---------------- pan_no verify --------------------------- //

            // ---------------- passport_no verify --------------------------- //

            function checkAndAppendpassport_noButton() {
                const val = $('#passport_no').val().trim();
                if (val.length === 8 || val.length === 9) {
                    verifyDocument('passport_no', 'passport-validate', null, /^[A-Z0-9]{1,9}$/i);
                }
            }

            // Watch changes on IEC input
            $('body').on('input', '#passport_no', function () {
                setTimeout(checkAndAppendpassport_noButton, 50); // wait for paste/input value
            });


            // ---------------- passport_no verify --------------------------- //

            function toggleLocalityFields() {
                const isDomestic = document.getElementById('domestic').checked;

                const domesticDivs = document.querySelectorAll('.locality-base-domestic');
                const internationalDivs = document.querySelectorAll('.locality-base-international');

                let content = document.getElementById('content-base_type');

                if (content) {  // Make sure the element exists
                    if (isDomestic) {
                        content.innerHTML = 'GSTIN Status / Current Status *';

                    } else {
                        content.innerHTML = 'UIN Status / Current Status *';

                    }
                } else {
                    console.error("Element with id 'content-base_type' not found.");
                }

                domesticDivs.forEach(div => {
                    if (isDomestic) {
                        div.classList.remove('d-none');
                        div.querySelectorAll('input').forEach(input => input.required = true);
                    } else {
                        div.classList.add('d-none');
                        div.querySelectorAll('input').forEach(input => input.required = false);

                    }
                });

                internationalDivs.forEach(div => {
                    if (!isDomestic) {
                        div.classList.remove('d-none');
                        div.querySelectorAll('input').forEach(input => input.required = true);
                    } else {
                        div.classList.add('d-none');
                        div.querySelectorAll('input').forEach(input => input.required = false);
                    }
                });
            }

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

                    if(country_selected == 'null' || country_selected == ''){
                        var country_selected = "{{ getSelectedCountry('whats_app_no_business_meta') }}";
                    }

                } else if (name === 'alternate_mob_no_business') { 
                    var country_selected = "{{ getSelectedCountry('alternate_mob_no_business_meta') }}";
                } else if (name === 'alternate_whats_app_no_business') { 
                    var country_selected = "{{ getSelectedCountry('alternate_whats_app_no_business_meta') }}";
                } else if (name === 'alternate_mob_no_personal') { 
                    var country_selected = "{{ getSelectedCountry('alternate_mob_no_personal_meta') }}";
                } else if (name === 'alternate_whats_app_no_personal') { 
                    var country_selected = "{{ getSelectedCountry('alternate_whats_app_no_personal_meta') }}";
                } else {
                    var country_selected = "{{ getSelectedCountry('phone_code_meta') }}"; 

                    if(country_selected == 'null' || country_selected == ''){
                        var country_selected = "{{ getSelectedCountry('phone_business_meta') }}";
                    }

                }


                if(country_selected !== 'null' && country_selected !== ''){
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


                            if(step == 2){
                                intil_input('alternate_mob_no_business');
                                intil_input('alternate_whats_app_no_business');
                                toggleLocalityFields();

                                const isDomestic = document.getElementById('domestic').checked;
                                if (isDomestic) {
                                    checkAndAppendButton();
                                } else {
                                    checkAndAppendIECButton();
                                }
                                
                            } else if (step == 3) {
                                intil_input('alternate_mob_no_personal');
                                intil_input('alternate_whats_app_no_personal');
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

            // Function to check the registration step
            function checkRegStep() {
                $.ajax({
                    url: "{{ route('get-reg-step') }}", // Simplified route helper
                    method: 'GET',
                    success: function (response) {
                        if (response.success) {
                            const step = response.step;

                            // if (step === 1) {
                            //     // Show the first modal if the step is 1
                            //     $('#reg_model_1').modal('show');
                            // } else {
                                // Call modelRendStep for other steps
                                modelRendStep();
                            // }
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



            // // Run on page load
            // toggleLocalityFields();

        });
// ---------------------------------------------------------------------------------------------------------------//

        function checkAndAppendButton() {
            const verifyBtnId = 'verify-gst-btn';
            const val = $('#gst_no').val();
            if (val.length === 15) {
                verifyDocument('gst_no', 'gst-validate', 15);
                // appendVerifyButton();
            // } else {
            //     $('#' + verifyBtnId).remove();
            }
        }

        function checkAndAppendIECButton() {
            const verifyBtnId = 'verify-iec-btn';
            const val = $('#iec_no').val().trim();
            if (val.length === 10) {
                verifyDocument('iec_no', 'iec-validate', 10);
            //     appendVerifyIECButton();
            // } else {
            //     $('#' + verifyBtnId).remove();
            }
        }

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
                modelRendStep(); // Perform the required step rendering gfdgdfg
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

                if(country_selected == 'null' || country_selected == ''){
                    var country_selected = "{{ getSelectedCountry('whats_app_no_business_meta') }}";
                }

            } else if (name === 'alternate_mob_no_business') { 
                var country_selected = "{{ getSelectedCountry('alternate_mob_no_business_meta') }}";
            } else if (name === 'alternate_whats_app_no_business') { 
                var country_selected = "{{ getSelectedCountry('alternate_whats_app_no_business_meta') }}";
            } else if (name === 'alternate_mob_no_personal') { 
                var country_selected = "{{ getSelectedCountry('alternate_mob_no_personal_meta') }}";
            } else if (name === 'alternate_whats_app_no_personal') { 
                var country_selected = "{{ getSelectedCountry('alternate_whats_app_no_personal_meta') }}";
            } else {
                var country_selected = "{{ getSelectedCountry('phone_code_meta') }}"; 

                if(country_selected == 'null' || country_selected == ''){
                    var country_selected = "{{ getSelectedCountry('phone_business_meta') }}";
                }

            }


            console.dir(country_selected);

            if(country_selected !== 'null' && country_selected !== ''){
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

                    } else {
                        console.error('Error:', response.message || 'An error occurred.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', error);
                },
            });
        }
        

        function toggleLocalityFields() {
            const isDomestic = document.getElementById('domestic').checked;

            const domesticDivs = document.querySelectorAll('.locality-base-domestic');
            const internationalDivs = document.querySelectorAll('.locality-base-international');

            domesticDivs.forEach(div => {
                if (isDomestic) {
                    div.classList.remove('d-none');
                    div.querySelectorAll('input').forEach(input => input.required = true);
                } else {
                    div.classList.add('d-none');
                    div.querySelectorAll('input').forEach(input => input.required = false);
                }
            });

            internationalDivs.forEach(div => {
                if (!isDomestic) {
                    div.classList.remove('d-none');
                    div.querySelectorAll('input').forEach(input => input.required = true);
                } else {
                    div.classList.add('d-none');
                    div.querySelectorAll('input').forEach(input => input.required = false);
                }
            });
        }

        function back_to_prev_reg() {
            var csrfToken = '{{ csrf_token() }}';
            $.ajax({
                url: "{{ route('previous.reg.form') }}", // Simplified route helper
                method: 'GET',
                success: function (response) {
                    if (response.success) {
                        // location.reload();
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


                        if(step == 2){
                            intil_input('alternate_mob_no_business');
                            intil_input('alternate_whats_app_no_business');
                            toggleLocalityFields();

                            const isDomestic = document.getElementById('domestic').checked;
                            if (isDomestic) {
                                checkAndAppendButton();
                            } else {
                                checkAndAppendIECButton();
                            }
                            
                        } else if (step == 3) {
                            intil_input('alternate_mob_no_personal');
                            intil_input('alternate_whats_app_no_personal');
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
        

        function resendOTPButton_Phone(phonetype, phone_no) {
            var csrfToken = '{{ csrf_token() }}';
            $.ajax({
                url: "{{ route('create.new.user.registration.resend.phone.verify') }}",
                type: "Post",
                data: {
                    phonetype  : phonetype,
                    phone_no   : phone_no
                },
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

        function getCsrfToken() {
            return $.get("/csrf-token"); // An endpoint that returns a new CSRF token
        }

        {{--
        // function verifyGST() {
        //     const gstInput = $('#gst_no');
        //     const gstNo = gstInput.val().trim();
        //     const submitBtn = $('#reg_model_form_2 button[type="submit"]'); // Adjust selector if needed
        //     const verifyBtnId = 'verify-gst-btn';
        //     const verifyBtn = $('#' + verifyBtnId);

        //     if (gstNo.length !== 15) {
        //         AIZ.plugins.notify('danger', 'GST No must be 15 characters');
        //         return;
        //     }

        //     // Show loading text
        //     const originalText = verifyBtn.text();
        //     verifyBtn.text('Verifying...').prop('disabled', true);



        //     getCsrfToken()
        //         .done(function (response) {
        //             const token = response.token;

        //             $.ajax({
        //                 url: '{{ route('new.user.account.create', ['param' => 'gst-validate']) }}',
        //                 method: 'POST',
        //                 data: {
        //                     gst_no: gstNo,
        //                     _token: token
        //                 },
        //                 success: function (response) {
        //                     if (response.status === "success") {
        //                         AIZ.plugins.notify('success', response.message);
        //                         $('#' + verifyBtnId).remove();
        //                         // submitBtn.prop('disabled', false);
        //                     } else {
        //                         AIZ.plugins.notify('danger', response.message);
        //                     }
        //                 },
        //                 error: function () {
        //                     alert('Error verifying GST.');
        //                 },
        //                 complete: function () {
        //                     verifyBtn.text(originalText).prop('disabled', false);
        //                 }
        //             });
        //         })
        //         .fail(function () {
        //             alert('Could not fetch CSRF token.');
        //             verifyBtn.text(originalText).prop('disabled', false);
        //         });
        // }


        // function verifyIEC() {
        //     const iecInput = $('#iec_no');
        //     const iecNo = iecInput.val().trim();
        //     const submitBtn = $('#reg_model_form_2 button[type="submit"]'); // Adjust selector if needed
        //     const verifyBtnId = 'verify-iec-btn';
        //     const verifyBtn = $('#' + verifyBtnId);

        //     if (iecNo.length !== 10) {
        //         AIZ.plugins.notify('danger', 'IEC No must be 10 characters');
        //         return;
        //     }

        //     // Show loading text
        //     const originalText = verifyBtn.text();
        //     verifyBtn.text('Verifying...').prop('disabled', true);



        //     getCsrfToken()
        //         .done(function (response) {
        //             const token = response.token;

        //             $.ajax({
        //                 url: '{{ route('new.user.account.create', ['param' => 'iec-validate']) }}',
        //                 method: 'POST',
        //                 data: {
        //                     iec_no: iecNo,
        //                     _token: token
        //                 },
        //                 success: function (response) {
        //                     if (response.status === "success") {
        //                         AIZ.plugins.notify('success', response.message);
        //                         $('#' + verifyBtnId).remove();
        //                         // submitBtn.prop('disabled', false);
        //                     } else {
        //                         AIZ.plugins.notify('danger', response.message);
        //                     }
        //                 },
        //                 error: function () {
        //                     AIZ.plugins.notify('danger', 'Error verifying IEC.');
        //                 },
        //                 complete: function () {
        //                     verifyBtn.text(originalText).prop('disabled', false);
        //                 }
        //             });
        //         })
        //         .fail(function () {
        //             AIZ.plugins.notify('danger', 'Somthing Went Wrong.');
        //             verifyBtn.text(originalText).prop('disabled', false);
        //         });
        // }
        --}}

        function verifyDocument(fieldId, routeParam, requiredLength = null, pattern = null) {
            const input = $('#' + fieldId);
            const value = input.val().trim();
            const submitBtn = $('#reg_model_form_2 button[type="submit"]');
            const verifyBtnId = `verify-${fieldId}-btn`;
            const verifyBtn = $('#' + verifyBtnId);

            // const sessionData = @json(session()->has(str_replace('-', '_', '$routeParam')));

            // // If session data exists, return early
            // if (sessionData) {
            //     return;
            // }

            // Basic validation
            if (requiredLength && value.length !== requiredLength) {
                AIZ.plugins.notify('danger', `${fieldId.replace(/_/g, ' ').toUpperCase()} must be ${requiredLength} characters`);
                return;
            }

            if (pattern && !pattern.test(value)) {
                AIZ.plugins.notify('danger', `Invalid ${fieldId.replace(/_/g, ' ')}`);
                return;
            }

            const originalText = verifyBtn.text();
            verifyBtn.text('Verifying...').prop('disabled', true);

            getCsrfToken()
                .done(function (response) {
                    const token = response.token;

                    $.ajax({
                        url: `{{ route('new.user.account.create', ['param' => '__param__']) }}`.replace('__param__', routeParam),
                        method: 'POST',
                        data: {
                            [fieldId]: value,
                            _token: token
                        },
                        success: function (response) {
                            if (response.status === "success") {
                                AIZ.plugins.notify('success', response.message);
                                verifyBtn.remove();
                                // submitBtn.prop('disabled', false);
                            } else {
                                AIZ.plugins.notify('danger', response.message);
                            }
                        },
                        error: function () {
                            AIZ.plugins.notify('danger', `Error verifying ${fieldId.replace(/_/g, ' ')}`);
                        },
                        complete: function () {
                            verifyBtn.text(originalText).prop('disabled', false);
                        }
                    });
                })
                .fail(function () {
                    AIZ.plugins.notify('danger', 'Something went wrong.');
                    verifyBtn.text(originalText).prop('disabled', false);
                });
        }



    </script>
@endsection
