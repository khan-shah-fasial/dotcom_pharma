@extends('frontend.layouts.app')

@section('content')

    @if (!Session::has('step') || Session::get('step') == 1)

        @php
            Session()->put('step', 1);
        @endphp

    @endif

    <div id="regModalContainer"></div>


    {{--- //------------------------------ aadhar verify modal -----------------------// ----}}

    <div class="modal fade" id="aadhar_otp_model" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content py-3">
                <div class="modal-header">
                    <div class="heading">
                        <h5 class="modal-title" id="exampleModalLabel">Verify aadhar</h5>
                    </div>
                    <div class="purple_btn_close">
                        <button type="button" onclick="close_Emai_modal();" class="close p-1 px-3" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true" style="font-size: 24px;">&times;</span>
                        </button>
                    </div>
                </div>
                <form id="aadhar-verify-otp" action="{{ url(route('new.user.account.create', ['param' => 'aadhar-otp-verify'])) }}"
                    method="post">
                    @csrf

                    <div class="modal-body">
                            <div class="form-group">
                                <label for="recipient-name" class="col-form-label form-label">Verification Code:</label>
                                <input type="number" class="form-control" id="recipient-name" name="otp" pattern="[0-9]+" minlength="6"
                                maxlength="6" placeholder="Please Enter Code" required>
                            </div>
                    </div>
                    <div class="modal-footer">
                        <div class="blue_btn">
                            <button type="button" onclick="close_aadhar_modal();" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                        <div class="purple_btn">
                            <button type="submit" class="btn btn-primary">Verify</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{--- //------------------------------  aadhar verify modal -----------------------// ----}}


@endsection

@section('custome-script')
    <script>

        const metaData = {
            whats_app_no_meta: 'null',
            phone_business_meta: 'null',
            phone_code_meta: 'null',
            alternate_whats_app_no_business_meta: 'null',
            alternate_mob_no_business_meta: 'null',
            alternate_whats_app_no_personal_meta: 'null',
            alternate_mob_no_personal_meta: 'null'
        };

        $(document).ready(function() {

            // ---------------- Gst verify --------------------------- //

            function checkAndAppendButton() {
                const verifyBtnId = 'verify-gst-btn';
                const val = $('#gst_no').val();
                if (val.length === 15) {
                    verifyDocument('gst_no', 'gst-validate', 15);
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
            let dob;
            function checkAndAppendpassport_noButton() {
                const val = $('#passport_no').val().trim();
                if (val.length === 9 || val.length === 15) {
                    verifyDocument('passport_no', 'passport-validate', null, /^[A-Z0-9]{1,15}$/i);
                }
            }

            // Watch changes on IEC input
            $('body').on('input', '#passport_no', function () {
                dob = $('#dob').val().trim();
                setTimeout(checkAndAppendpassport_noButton, 50); // wait for paste/input value
            });

            // ---------------- passport_no verify --------------------------- //
            
            {{--
            // function toggleLocalityFields() {
            //     const isDomestic = document.getElementById('domestic').checked;

            //     const domesticDivs = document.querySelectorAll('.locality-base-domestic');
            //     const internationalDivs = document.querySelectorAll('.locality-base-international');

            //     let content = document.getElementById('content-base_type');

            //     if (content) {  // Make sure the element exists
            //         if (isDomestic) {
            //             content.innerHTML = 'GSTIN Status / Current Status *';

            //         } else {
            //             content.innerHTML = 'UIN Status / Current Status *';

            //         }
            //     } else {
            //         console.error("Element with id 'content-base_type' not found.");
            //     }

            //     domesticDivs.forEach(div => {
            //         if (isDomestic) {
            //             div.classList.remove('d-none');
            //             div.querySelectorAll('input').forEach(input => input.required = true);
            //         } else {
            //             div.classList.add('d-none');
            //             div.querySelectorAll('input').forEach(input => input.required = false);

            //         }
            //     });

            //     internationalDivs.forEach(div => {
            //         if (!isDomestic) {
            //             div.classList.remove('d-none');
            //             div.querySelectorAll('input').forEach(input => input.required = true);
            //         } else {
            //             div.classList.add('d-none');
            //             div.querySelectorAll('input').forEach(input => input.required = false);
            //         }
            //     });
            // }
            --}}

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
                    if (response.phone_otp === true || response.phone_otp === 'true') {

                        // Create OTP HTML block
                        const otpHtml = `
                            <div class="form-group">
                                <label for="recipient-name" class="col-form-label form-label">Verification Code:</label>
                                <input type="number" class="form-control form-control-lg" id="recipient-name" name="otp"
                                    pattern="[0-9]+" minlength="6" maxlength="6" placeholder="Please Enter Code" required>
                            </div>
                            <div class="resend_otp">
                                <a class="ms-4 btn btn-primary" onclick="resendOTPButton_Phone();">Resend OTP</a>
                            </div>
                        `;

                        // Select the correct form
                        const formSelector = `#reg_model_form_${step}`;
                        const form = document.querySelector(formSelector);

                        if (form) {
                            // Append OTP HTML to modal-body inside the form
                            const modalBody = form.querySelector('.modal-body');
                            if (modalBody) {
                                modalBody.innerHTML += otpHtml;
                            }

                            // Update the form action
                            form.setAttribute('action', "{{ url(route('new.user.account.create', ['param' => 'verify-phone-otp'])) }}");

                            const phoneInput = form.querySelector('#phone_code');
                            if (phoneInput) {
                                phoneInput.value = response.phone;
                            }
                        }

                    } else if (response.email_otp === true || response.email_otp === 'true') {  

                        // Create OTP HTML block
                        const otpHtml = `
                            <div class="form-group">
                                <label for="recipient-name" class="col-form-label form-label">Verification Code:</label>
                                <input type="number" class="form-control form-control-lg" id="recipient-name" name="otp"
                                    pattern="[0-9]+" minlength="6" maxlength="6" placeholder="Please Enter Code" required>
                            </div>
                            <div class="resend_otp">
                                <a class="ms-4 btn btn-primary" onclick="resendOTPButton_Phone();">Resend OTP</a>
                            </div>
                        `;

                        // Select the correct form
                        const formSelector = `#reg_model_form_${step}`;
                        const form = document.querySelector(formSelector);

                        if (form) {
                            // Append OTP HTML to modal-body inside the form
                            const modalBody = form.querySelector('.modal-body');
                            if (modalBody) {
                                modalBody.innerHTML += otpHtml;
                            }

                            // Update the form action
                            form.setAttribute('action', "{{ url(route('new.user.account.create', ['param' => 'verify-email-otp'])) }}");

                            const emailInput = form.querySelector('#email');
                            if (emailInput) {
                                emailInput.value = response.email;
                            }
                        }

                    } else {
                        modelRendStep(); 
                    }

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
                    let country = new URLSearchParams(window.location.search).get('type') || 'null';

                    if (country === "domestic") {
                        iti1.setCountry('in'); // 'in' is the ISO2 code for India
                    } else if (country === "international") {
                        iti1.setCountry('us'); // set to 'us' for international
                    } else {
                        iti1.setCountry('in'); // default fallback
                    }
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

                    metaData[`${name}_meta`] = iti1.getSelectedCountryData().iso2;
                });
            }



            function intil_input_form2(name) {
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

                if(name === 'whats_app_no'){

                    var country_selected = "{{ getSelectedCountry_form2('whats_app_no_meta') }}";

                } else if (name === 'alternate_mob_no_personal') { 
                    var country_selected = "{{ getSelectedCountry_form2('alternate_mob_no_personal_meta') }}";
                } else if (name === 'alternate_whats_app_no_personal') { 
                    var country_selected = "{{ getSelectedCountry_form2('alternate_whats_app_no_personal_meta') }}";
                } else {
                    var country_selected = "{{ getSelectedCountry_form2('phone_code_meta') }}"; 

                }

                if(country_selected !== 'null' && country_selected !== ''){
                    iti1.setCountry(country_selected); // 'in' is the ISO2 code for India
                } else {
                    // Set default country code to +91 (India)
                    let country = new URLSearchParams(window.location.search).get('type') || 'null';

                    if (country === "domestic") {
                        iti1.setCountry('in'); // 'in' is the ISO2 code for India
                    } else if (country === "international") {
                        iti1.setCountry('us'); // set to 'us' for international
                    } else {
                        iti1.setCountry('in'); // default fallback
                    }
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

                    metaData[`${name}_meta`] = iti1.getSelectedCountryData().iso2;
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

                            if(step == 2){
                                intil_input('phone_code');
                            }

                            if(step == 5){
                                intil_input('phone_code');
                                intil_input('whats_app_no');
                                intil_input('alternate_mob_no_business');
                                intil_input('alternate_whats_app_no_business');
                                // toggleLocalityFields();

                                const isDomestic = document.getElementById('domestic').checked;
                                if (isDomestic) {
                                    checkAndAppendButton();
                                } else {
                                    checkAndAppendIECButton();
                                }
                                
                            } else if (step == 6) {
                                intil_input_form2('phone_code');
                                intil_input_form2('whats_app_no');
                                intil_input_form2('alternate_mob_no_personal');
                                intil_input_form2('alternate_whats_app_no_personal');
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
                                modelRendStep();
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

        });

// ---------------------------------------------------------------------------------------------------------------//

        function checkAndAppendButton() {
            const verifyBtnId = 'verify-gst-btn';
            const val = $('#gst_no').val();
            if (val.length === 15) {
                verifyDocument('gst_no', 'gst-validate', 15);
            }
        }

        function checkAndAppendIECButton() {
            const verifyBtnId = 'verify-iec-btn';
            const val = $('#iec_no').val().trim();
            if (val.length === 10) {
                verifyDocument('iec_no', 'iec-validate', 10);
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

                if (response.phone_otp === true || response.phone_otp === 'true') {

                    // Create OTP HTML block
                    const otpHtml = `
                        <div class="form-group">
                            <label for="recipient-name" class="col-form-label form-label">Verification Code:</label>
                            <input type="number" class="form-control form-control-lg" id="recipient-name" name="otp"
                                pattern="[0-9]+" minlength="6" maxlength="6" placeholder="Please Enter Code" required>
                        </div>
                        <div class="resend_otp">
                            <a class="ms-4 btn btn-primary" onclick="resendOTPButton_Phone();">Resend OTP</a>
                        </div>
                    `;

                    // Select the correct form
                    const formSelector = `#reg_model_form_${step}`;
                    const form = document.querySelector(formSelector);

                    if (form) {
                        // Append OTP HTML to modal-body inside the form
                        const modalBody = form.querySelector('.modal-body');
                        if (modalBody) {
                            modalBody.innerHTML += otpHtml;
                        }

                        // Update the form action
                        form.setAttribute('action', "{{ url(route('new.user.account.create', ['param' => 'verify-phone-otp'])) }}");

                        const phoneInput = form.querySelector('#phone_code');
                        if (phoneInput) {
                            phoneInput.value = response.phone;
                        }
                    }

                } else if (response.email_otp === true || response.email_otp === 'true') {

                    // Create OTP HTML block
                    const otpHtml = `
                        <div class="form-group">
                            <label for="recipient-name" class="col-form-label form-label">Verification Code:</label>
                            <input type="number" class="form-control form-control-lg" id="recipient-name" name="otp"
                                pattern="[0-9]+" minlength="6" maxlength="6" placeholder="Please Enter Code" required>
                        </div>
                        <div class="resend_otp">
                            <a class="ms-4 btn btn-primary" onclick="resendOTPButton_Phone();">Resend OTP</a>
                        </div>
                    `;

                    // Select the correct form
                    const formSelector = `#reg_model_form_${step}`;
                    const form = document.querySelector(formSelector);

                    if (form) {
                        // Append OTP HTML to modal-body inside the form
                        const modalBody = form.querySelector('.modal-body');
                        if (modalBody) {
                            modalBody.innerHTML += otpHtml;
                        }

                        // Update the form action
                        form.setAttribute('action', "{{ url(route('new.user.account.create', ['param' => 'verify-email-otp'])) }}");

                        const emailInput = form.querySelector('#email');
                        if (emailInput) {
                            emailInput.value = response.email;
                        }
                    }

                } else {

                    modelRendStep(); // Perform the required step rendering gfdgdfg
                }
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

                var country_selected = "{{ getSelectedCountry('whats_app_no_business_meta') }}";

                if(country_selected == 'null' || country_selected == ''){
                    country_selected = metaData.whats_app_no_business_meta;
                }

            } else if (name === 'alternate_mob_no_business') { 
                var country_selected = "{{ getSelectedCountry('alternate_mob_no_business_meta') }}";

                if(country_selected == 'null' || country_selected == ''){
                    country_selected = metaData.alternate_mob_no_business_meta;
                }

            } else if (name === 'alternate_whats_app_no_business') { 
                var country_selected = "{{ getSelectedCountry('alternate_whats_app_no_business_meta') }}";

                if(country_selected == 'null' || country_selected == ''){
                    country_selected = metaData.alternate_whats_app_no_business_meta;
                }


            } else {
                var country_selected = "{{ getSelectedCountry('phone_business_meta') }}";

                if(country_selected == 'null' || country_selected == ''){
                    country_selected = metaData.phone_business_meta;
                }

            }

            if(country_selected !== 'null' && country_selected !== ''){
                iti1.setCountry(country_selected); // 'in' is the ISO2 code for India
            } else {
                // Set default country code to +91 (India)
                let country = new URLSearchParams(window.location.search).get('type') || 'null';

                if (country === "domestic") {
                    iti1.setCountry('in'); // 'in' is the ISO2 code for India
                } else if (country === "international") {
                    iti1.setCountry('us'); // set to 'us' for international
                } else {
                    iti1.setCountry('in'); // default fallback
                }
                
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

        function intil_input_form2(name) {
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

            if(name === 'whats_app_no'){

                var country_selected = "{{ getSelectedCountry_form2('whats_app_no_meta') }}";

                if(country_selected == 'null' || country_selected == ''){
                    country_selected = metaData.whats_app_no_meta;
                }

            } else if (name === 'alternate_mob_no_personal') { 
                var country_selected = "{{ getSelectedCountry_form2('alternate_mob_no_personal_meta') }}";

                if(country_selected == 'null' || country_selected == ''){
                    country_selected = metaData.alternate_mob_no_personal_meta;
                }

            } else if (name === 'alternate_whats_app_no_personal') { 
                var country_selected = "{{ getSelectedCountry_form2('alternate_whats_app_no_personal_meta') }}";

                if(country_selected == 'null' || country_selected == ''){
                    country_selected = metaData.alternate_whats_app_no_personal_meta;
                }

            } else {
                var country_selected = "{{ getSelectedCountry_form2('phone_code_meta') }}"; 

                if(country_selected == 'null' || country_selected == ''){
                    country_selected = metaData.phone_code_meta;
                }

            }

            console.dir('vegiata');
            console.dir(country_selected);

            if(country_selected !== 'null' && country_selected !== '' && country_selected != null){
                iti1.setCountry(country_selected); // 'in' is the ISO2 code for India
            } else {
                // Set default country code to +91 (India)
                let country = new URLSearchParams(window.location.search).get('type') || 'null';

                if (country === "domestic") {
                    iti1.setCountry('in'); // 'in' is the ISO2 code for India
                } else if (country === "international") {
                    iti1.setCountry('us'); // set to 'us' for international
                } else {
                    iti1.setCountry('in'); // default fallback
                }
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
                        console.dir("goku");

                        if(step == 2){
                            intil_input('phone_code');
                        }

                        if(step == 5){
                            intil_input('phone_code');
                            intil_input('whats_app_no');
                            intil_input('alternate_mob_no_business');
                            intil_input('alternate_whats_app_no_business');
                            // toggleLocalityFields();

                            const isDomestic = document.getElementById('domestic').checked;
                            if (isDomestic) {
                                checkAndAppendButton();
                            } else {
                                checkAndAppendIECButton();
                            }
                            
                        } else if (step == 6) {
                            console.dir(step);
                            intil_input_form2('phone_code');
                            intil_input_form2('whats_app_no');
                            intil_input_form2('alternate_mob_no_personal');
                            intil_input_form2('alternate_whats_app_no_personal');
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
        
        {{--
        // function toggleLocalityFields() {
        //     const isDomestic = document.getElementById('domestic').checked;

        //     const domesticDivs = document.querySelectorAll('.locality-base-domestic');
        //     const internationalDivs = document.querySelectorAll('.locality-base-international');

        //     domesticDivs.forEach(div => {
        //         if (isDomestic) {
        //             div.classList.remove('d-none');
        //             div.querySelectorAll('input').forEach(input => input.required = true);
        //         } else {
        //             div.classList.add('d-none');
        //             div.querySelectorAll('input').forEach(input => input.required = false);
        //         }
        //     });

        //     internationalDivs.forEach(div => {
        //         if (!isDomestic) {
        //             div.classList.remove('d-none');
        //             div.querySelectorAll('input').forEach(input => input.required = true);
        //         } else {
        //             div.classList.add('d-none');
        //             div.querySelectorAll('input').forEach(input => input.required = false);
        //         }
        //     });
        // }
        --}}

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

                        if(step == 5){
                            intil_input('phone_code');
                            intil_input('whats_app_no');
                            intil_input('alternate_mob_no_business');
                            intil_input('alternate_whats_app_no_business');
                            toggleLocalityFields();

                            const isDomestic = document.getElementById('domestic').checked;
                            if (isDomestic) {
                                checkAndAppendButton();
                            } else {
                                checkAndAppendIECButton();
                            }
                            
                        } else if (step == 6) {
                            intil_input_form2('phone_code');
                            intil_input_form2('whats_app_no');
                            intil_input_form2('alternate_mob_no_personal');
                            intil_input_form2('alternate_whats_app_no_personal');
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
        

        function resendOTPButton_Phone(phonetype = null, phone_no = null) {
            var csrfToken = '{{ csrf_token() }}';
            $.ajax({
                url: "{{ route('create.new.user.registration.resend.phone.verify') }}",
                type: "Post",
                // data: {
                //     phonetype  : phonetype,
                //     phone_no   : phone_no
                // },
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
            const dob = $('#dob').val();
            // const verifyBtnId = `verify-${fieldId}-btn`;
            // const verifyBtn = $('#' + verifyBtnId);

            // Basic validation
            if (requiredLength && value.length !== requiredLength) {
                AIZ.plugins.notify('danger', `${fieldId.replace(/_/g, ' ').toUpperCase()} must be ${requiredLength} characters`);
                return;
            }

            if (pattern && !pattern.test(value)) {
                AIZ.plugins.notify('danger', `Invalid ${fieldId.replace(/_/g, ' ')}`);
                return;
            }


            if (fieldId == "passport_no") {
                if (!dob) { // This checks if dob is empty, null, or undefined
                    AIZ.plugins.notify('danger', "To validate your Passport, you need to select DOB too");
                    return;
                }
            }

            // const originalText = verifyBtn.text();
            // verifyBtn.text('Verifying...').prop('disabled', true);

            getCsrfToken()
                .done(function (response) {
                    const token = response.token;

                    $.ajax({
                        url: `{{ route('new.user.account.create', ['param' => '__param__']) }}`.replace('__param__', routeParam),
                        method: 'POST',
                        data: {
                            [fieldId]: value,
                            dob: dob,
                            _token: token
                        },
                        success: function (response) {
                            if (response.status === "success") {
                                AIZ.plugins.notify('success', response.message);

                                let data = response.data ? response.data : response;

                                if (fieldId == "gst_no") {
                                    $('#street_add_first_business').val(data.contact_details.principal.address);
                                    $('#registration_date').val(data.date_of_registration);
                                    $('#const_of_business').val(data.constitution_of_business);
                                    $('#gstin_current_status').val(data.gstin_status);
                                    $('#company_name').val(data.business_name);
                                    $('#phone_code').val(data.contact_details.principal.mobile);
                                    $('#whats_app_no').val(data.contact_details.principal.mobile);
                                    $('#prim_email_business').val(data.contact_details.principal.email);

                                    // Correctly accessing the first promoter, or showing an empty string if it doesn't exist
                                    $('#con_person_name').val(data.promoters.length > 0 ? data.promoters[0] : '');
                                } 

                                if (fieldId == "iec_no") {
                                    $('#street_add_first_business').val(data.address);
                                    $('#registration_date').val(data.iec_issuance_date);

                                    // $('#const_of_business').val(data.constitution_of_business);

                                    $('#uin_current_status').val(data.iec_status);
                                    $('#company_name').val(data.firm_name);
                                    $('#phone_code').val(data.firm_mobile_no);
                                    $('#whats_app_no').val(data.firm_mobile_no);
                                    $('#prim_email_business').val(data.firm_email_id);

                                    // Correctly accessing the first promoter, or showing an empty string if it doesn't exist
                                    $('#con_person_name').val(data.director_details.length > 0 ? data.director_details[0].name : '');
                                }

                                if (fieldId == "aadhaar_no" && data == "open") {
                                    setTimeout(function () {
                                        // location.reload();
                                        $('#aadhar_otp_model').modal('show');
                                    }, 100);
                                }

                                if (fieldId === "passport_no") {
                                    $('#name').val(data.full_name);
                                }

                            } else {
                                AIZ.plugins.notify('danger', response.message);
                            }
                        },
                        error: function () {
                            AIZ.plugins.notify('danger', `Error verifying ${fieldId.replace(/_/g, ' ')}`);
                        },
                        complete: function () {
                        }
                    });
                })
                .fail(function () {
                    AIZ.plugins.notify('danger', 'Something went wrong.');
                });
        }




        /*--------------------- Aadhar otp ------------------*/ 

            initValidate('#aadhar-verify-otp');

            $('#aadhar-verify-otp').on('submit', function(e){
                var form = $(this);
                ajax_form_submit(e, form, responseHandler_aadhar_verify_otp);
            });

            var responseHandler_aadhar_verify_otp = function (response) {
                var form = $('#aadhar-verify-otp'); 
                
                form.find("input[type=text]").val("");

                $('#aadhar_otp_model').modal('toggle');

                if(response.status == "success"){
                    let data = response.data;
                    var address = data.address.house + ',' + data.address.street + ',' + data.address.landmark + ',' + data.address.loc + ',' + data.address.dist + ',' + data.address.state + ',' + data.address.country;

                    $('#street_add_first_personal').val(address);
                    $('#locality_land_mark_personal').val(data.address.landmark);
                    $('#dob').val(data.dob);
                    $('#name').val(data.full_name);

                    $('#post_personal').val(data.address.po);

                    $('#pincode_personal').val(data.zip);
                    $('#district_personal').val(data.address.dist);
                } else {
                    $('#aadhaar_no').val('');
                }

            };

            function close_aadhar_modal() {
                $('#aadhar_otp_model').modal('toggle');
            };

         /*--------------------- email verify otp ------------------*/ 

    </script>
@endsection
