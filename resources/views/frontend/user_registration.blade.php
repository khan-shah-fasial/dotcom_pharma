@extends('frontend.layouts.app')

@section('content')

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Full-screen overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.3); /* Light semi-transparent background */
            backdrop-filter: blur(20px); /* This is where the glass blur effect is applied */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Ensure it's on top */
        }

        /* Loading text styling */
        .loading-content {
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .loading-text {
            font-size: 18px;
            font-weight: bold;
            color: #070321;
            animation: fadeIn 1s infinite; /* Add a fade animation for fun */
            text-transform: capitalize; /* Optional, if you want uppercase text */
            letter-spacing: 1px; /* Adds spacing between letters */
            /* text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3), -2px -2px 5px rgba(0, 0, 0, 0.3); */
        }

        /* Optional: Add a simple fadeIn effect */
        @keyframes fadeIn {
            0% {
                opacity: 0;
            }
            50% {
                opacity: 1;
            }
            100% {
                opacity: 0;
            }
        }
    </style>

    <!-- Full screen loading overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-text">Loading... Please Wait</div>
        </div>
    </div>

    <!-- Sliders -->
    <div class="home-banner-area mb-3">
        <div class="p-0">
            <!-- Sliders -->
            <div class="home-slider slider-full">
                <div class="d-block mw-100 img-fit overflow-hidden overflow-hidden">
                    <img class="img-fit m-auto has-transition ls-is-cached lazyloaded" src="{{ static_asset('assets/img/veterniry-banner-background.webp') }}" />
                </div>
            </div>
        </div>
    </div>

    @if (!Session::has('step') || Session::get('step') == 1)

        @php
            Session()->put('step', 1);
        @endphp

    @endif

    <div class="modal fade login_form_popup" id="reg_model_1" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
        <div class="modal-dialog" role="document">

            <div id="regModalContainer"></div>

        </div>
    </div>

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

        {{--
        // const metaData = {
        //     whats_app_no_meta: 'null',
        //     phone_business_meta: 'null',
        //     phone_code_meta: 'null',
        //     alternate_whats_app_no_business_meta: 'null',
        //     alternate_mob_no_business_meta: 'null',
        //     alternate_whats_app_no_personal_meta: 'null',
        //     alternate_mob_no_personal_meta: 'null'
        // };
        --}}
        let country_id = '';

        $(document).ready(function() {

        {{-- // ---------------- Gst verify --------------------------- // --}}

            function checkAndAppendButton() {
                const verifyBtnId = 'verify-gst-btn';
                const val = $('#gst_no').val();
                if (val.length === 15) {
                    verifyDocument('gst_no', 'gst-validate', 15);
                }
            }

        {{--    // Run on input/paste/change  --}}
            $('body').on('input', '#gst_no', function () {
                setTimeout(checkAndAppendButton, 50); // delay for paste to take effect
            });


        {{--    // ---------------- Gst verify --------------------------- //  --}}

        {{--    // ---------------- IEC verify --------------------------- //  --}}

            function checkAndAppendIECButton() {
                const verifyBtnId = 'verify-iec-btn';
                const val = $('#iec_no').val().trim();
                if (val.length === 10) {
                    verifyDocument('iec_no', 'iec-validate', 10);
                }
            }

        {{--    // Watch changes on IEC input  --}}
            $('body').on('input', '#iec_no', function () {
                setTimeout(checkAndAppendIECButton, 50); // wait for paste/input value
            });


        {{--    // ---------------- ICE verify --------------------------- // --}}

        {{--    // ---------------- aadhaar_no verify --------------------------- // --}}

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
                                <a class="" onclick="resendOTPButton_Phone();">Resend OTP</a>
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
                                phoneInput.disabled = true;
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
                                <a class="" onclick="resendOTPButton_Phone();">Resend OTP</a>
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
                                emailInput.disabled = true;
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

                {{--
                // // Set default country code to +91 (India)
                // iti1.setCountry('in'); // 'in' is the ISO2 code for India --}}


                var phone_meta = document.querySelector(`input[name="country_code_${name}"]`).value;
                var countryData = window.intlTelInputGlobals.getCountryData();

                if(phone_meta !== 'null' && phone_meta !== ''){

                    // Find the country matching the dial code
                    var matchedCountry = countryData.find(function(country) {
                        return country.dialCode === phone_meta;
                    });

                    iti1.setCountry(matchedCountry.iso2); // 'in' is the ISO2 code for India
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

                if (document.getElementById("country_code_business")) {
                    document.getElementById("country_code_business").value = countryData.dialCode;
                } else if (document.getElementById("country_code_personal")) {
                    document.getElementById("country_code_personal").value = countryData.dialCode;
                }

                // Update the country code when the country changes
                inputElement.addEventListener("countrychange", function () {
                    var updatedCountryData = iti1.getSelectedCountryData();
                    document.querySelector(`input[name="country_code_${name}"]`).value = updatedCountryData.dialCode;
                    document.querySelector(`input[name="${name}_meta"]`).value = updatedCountryData.iso2;

                    metaData[`${name}_meta`] = iti1.getSelectedCountryData().iso2;
                });
            }

            function selectCountry(){
                const countryHidden = document.getElementById("country__name");
                const countrySelect = document.querySelector("select[name='country_id']");

                if (countryHidden && countrySelect) {
                    const countryValue = countryHidden.value.trim();

                    console.dir(`Country hidden: ${countryHidden.value}`);
                    console.dir(`Country Value: ${countryValue}`);

                    if (countryValue !== "") {
                        countrySelect.value = countryValue;
                        
                        // Refresh aiz-selectpicker
                        if (typeof $ !== "undefined" && $.fn.selectpicker) {
                            $(countrySelect).selectpicker("refresh");
                            console.dir(`Country Value refreshed: ${countryValue}`);
                        }
                    } else {
                        countrySelect.value = country_id;
                        
                        // Refresh aiz-selectpicker
                        if (typeof $ !== "undefined" && $.fn.selectpicker) {
                            $(countrySelect).selectpicker("refresh");
                            console.dir(`Country Value else refreshed: ${country_id}`);
                        }
                    }
                }
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

                            {{--
                            // const backdrop = document.querySelector(".modal-backdrop");
                            // if (backdrop) {
                            //     backdrop.remove(); // Removes only the backdrop
                            //     backdrop.parentElement?.remove(); // Removes the full div if the backdrop is inside another div
                            // }
                            --}}

                            document.getElementById('loading-overlay').style.display = 'none';

                            // $(`#reg_model_${step}`).modal('show');
                            if (!$('#reg_model_1').hasClass('show')) {
                                $('#reg_model_1').modal('show');
                            }

                            if ([5, 6, 7].includes(step)) {
                                const $dialog = $('#reg_model_1 .modal-dialog');
                                if (!$dialog.hasClass('modal-lg')) {
                                    $dialog.addClass('modal-lg');
                                    $('#reg_model_1').modal('handleUpdate');
                                }
                            } else {
                                const $dialog = $('#reg_model_1 .modal-dialog');
                                if ($dialog.hasClass('modal-lg')) {
                                    $dialog.removeClass('modal-lg');
                                    $('#reg_model_1').modal('handleUpdate');
                                }
                            }



                            validate_form(step);

                            if(step == 2){
                                intil_input('phone_code');
                            }

                            if(step == 5){
                                selectCountry();
                                intil_input('phone_code');
                                intil_input('whats_app_no');
                                intil_input('alternate_mob_no_business');
                                intil_input('alternate_whats_app_no_business');
                                {{--
                                // toggleLocalityFields();

                                // const isDomestic = document.getElementById('domestic').checked;
                                // if (isDomestic) {
                                //     checkAndAppendButton();
                                // } else {
                                //     checkAndAppendIECButton();
                                // }
                                --}}
                                
                            } else if (step == 6) {
                                selectCountry();
                                intil_input('phone_code');
                                intil_input('whats_app_no');
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


            function passport_clear() {
                console.dir('passport clear');
                const passportField = document.getElementById('passport_no');
                if (passportField) {
                    passportField.value = '';
                }
            };

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
                            <a class="" onclick="resendOTPButton_Phone();">Resend OTP</a>
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
                            phoneInput.disabled = true;
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
                            <a class="" onclick="resendOTPButton_Phone();">Resend OTP</a>
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
                            emailInput.disabled = true;
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

            {{--
            // // Set default country code to +91 (India)
            // iti1.setCountry('in'); // 'in' is the ISO2 code for India --}}


            var phone_meta = document.querySelector(`input[name="country_code_${name}"]`).value;
            var countryData = window.intlTelInputGlobals.getCountryData();

            if(phone_meta !== 'null' && phone_meta !== ''){

                // Find the country matching the dial code
                var matchedCountry = countryData.find(function(country) {
                    return country.dialCode === phone_meta;
                });

                iti1.setCountry(matchedCountry.iso2);// 'in' is the ISO2 code for India
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

            if (document.getElementById("country_code_business")) {
                document.getElementById("country_code_business").value = countryData.dialCode;
            } else if (document.getElementById("country_code_personal")) {
                document.getElementById("country_code_personal").value = countryData.dialCode;
            }

            // Update the country code when the country changes
            inputElement.addEventListener("countrychange", function () {
                var updatedCountryData = iti1.getSelectedCountryData();
                document.querySelector(`input[name="country_code_${name}"]`).value = updatedCountryData.dialCode;
                document.querySelector(`input[name="${name}_meta"]`).value = updatedCountryData.iso2;

                metaData[`${name}_meta`] = iti1.getSelectedCountryData().iso2;
            });
        }

        function selectCountry(){
            const countryHidden = document.getElementById("country__name");
            const countrySelect = document.querySelector("select[name='country_id']");

            if (countryHidden && countrySelect) {
                const countryValue = countryHidden.value.trim();

                console.dir(`Country hidden: ${countryHidden.value}`);
                console.dir(`Country Value: ${countryValue}`);

                if (countryValue !== "") {
                    countrySelect.value = countryValue;
                    
                    // Refresh aiz-selectpicker
                    if (typeof $ !== "undefined" && $.fn.selectpicker) {
                        $(countrySelect).selectpicker("refresh");
                        console.dir(`Country Value refreshed: ${countryValue}`);
                    }
                } else {
                    countrySelect.value = country_id;
                    
                    // Refresh aiz-selectpicker
                    if (typeof $ !== "undefined" && $.fn.selectpicker) {
                        $(countrySelect).selectpicker("refresh");
                        console.dir(`Country Value else refreshed: ${country_id}`);
                    }
                }
            }
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

                        {{--
                        // const backdrop = document.querySelector(".modal-backdrop");
                        // if (backdrop) {
                        //     backdrop.remove(); // Removes only the backdrop
                        //     backdrop.parentElement?.remove(); // Removes the full div if the backdrop is inside another div
                        // }
                        --}}

                        document.getElementById('loading-overlay').style.display = 'none';

                        // $(`#reg_model_${step}`).modal('show');
                        if (!$('#reg_model_1').hasClass('show')) {
                            $('#reg_model_1').modal('show');
                        }

                        if ([5, 6, 7].includes(step)) {
                            const $dialog = $('#reg_model_1 .modal-dialog');
                            if (!$dialog.hasClass('modal-lg')) {
                                $dialog.addClass('modal-lg');
                                $('#reg_model_1').modal('handleUpdate');
                            }
                        } else {
                            const $dialog = $('#reg_model_1 .modal-dialog');
                            if ($dialog.hasClass('modal-lg')) {
                                $dialog.removeClass('modal-lg');
                                $('#reg_model_1').modal('handleUpdate');
                            }
                        }

                        validate_form(step);

                        if(step == 2){
                            intil_input('phone_code');
                        }

                        if(step == 5){
                            selectCountry();
                            intil_input('phone_code');
                            intil_input('whats_app_no');
                            intil_input('alternate_mob_no_business');
                            intil_input('alternate_whats_app_no_business');

                            {{--
                            // toggleLocalityFields();

                            // const isDomestic = document.getElementById('domestic').checked;
                            // if (isDomestic) {
                            //     checkAndAppendButton();
                            // } else {
                            //     checkAndAppendIECButton();
                            // }
                            --}}
                            
                        } else if (step == 6) {
                            selectCountry();
                            intil_input('phone_code');
                            intil_input('whats_app_no');
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
                        {{--
                        // const backdrop = document.querySelector(".modal-backdrop");
                        // if (backdrop) {
                        //     backdrop.remove(); // Removes only the backdrop
                        //     backdrop.parentElement?.remove(); // Removes the full div if the backdrop is inside another div
                        // }
                        --}}

                        // $(`#reg_model_${step}`).modal('show');
                        if (!$('#reg_model_1').hasClass('show')) {
                            $('#reg_model_1').modal('show');
                        }

                        if ([5, 6, 7].includes(step)) {
                            const $dialog = $('#reg_model_1 .modal-dialog');
                            if (!$dialog.hasClass('modal-lg')) {
                                $dialog.addClass('modal-lg');
                                $('#reg_model_1').modal('handleUpdate');
                            }
                        } else {
                            const $dialog = $('#reg_model_1 .modal-dialog');
                            if ($dialog.hasClass('modal-lg')) {
                                $dialog.removeClass('modal-lg');
                                $('#reg_model_1').modal('handleUpdate');
                            }
                        }

                        validate_form(step);

                        if(step == 2){
                            intil_input('phone_code');
                        }

                        if(step == 5){
                            selectCountry();
                            intil_input('phone_code');
                            intil_input('whats_app_no');
                            intil_input('alternate_mob_no_business');
                            intil_input('alternate_whats_app_no_business');

                            {{--
                            // toggleLocalityFields();

                            // const isDomestic = document.getElementById('domestic').checked;
                            // if (isDomestic) {
                            //     checkAndAppendButton();
                            // } else {
                            //     checkAndAppendIECButton();
                            // }
                            --}}
                            
                        } else if (step == 6) {
                            selectCountry();
                            intil_input('phone_code');
                            intil_input('whats_app_no');
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
        let login_page_home = "{{ route('home') }}";

        function close_and_reload_reg (){
            $('#reg_model_1').modal('hide');
            setTimeout(function() {
                // location.reload();
                window.location.href = login_page_reg;
            }, 100);
        }

        function close_and_reload_home (){
            document.getElementById('loading-overlay').style.display = 'block';
            $('#reg_model_1').modal('hide');
            window.location.href = login_page_home;
            // setTimeout(function() {
            //     // location.reload();
            //     window.location.href = login_page_home;
            // }, 100);
        }



        $(document).on('change', '[name=country_id]', function() {
            var country_id = $(this).val();
            get_states(country_id);
        });

        $(document).on('change', '[name=state_id]', function() {
            var state_id = $(this).val();
            get_city(state_id);
        });

        function parseOptions(resp) {
            if (typeof resp === 'string') {
                try {
                    return JSON.parse(resp);
                } catch (e) {
                    return resp;
                }
            }
            return resp;
        }

        function get_states(country_id) {
            $('[name="state_id"]').html("");
            $('[name="city_id"]').html("");
            return $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{route('get-state')}}",
                type: 'POST',
                data: {
                    country_id  : country_id
                },
                success: function (response) {
                    var obj = parseOptions(response);
                    if(obj != '') {
                        var $state = $('[name="state_id"]');
                        $state.html(obj);
                        var selected = $state.data('selected');
                        if (selected !== undefined && selected !== null && selected !== '') {
                            $state.val(String(selected));
                            $state.data('selected', '');
                        }
                        AIZ.plugins.bootstrapSelect('refresh');
                    }
                }
            });
        }

        function get_city(state_id) {
            $('[name="city_id"]').html("");
            return $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{route('get-city')}}",
                type: 'POST',
                data: {
                    state_id: state_id
                },
                success: function (response) {
                    var obj = parseOptions(response);
                    if(obj != '') {
                        var $city = $('[name="city_id"]');
                        $city.html(obj);
                        var selected = $city.data('selected');
                        if (selected !== undefined && selected !== null && selected !== '') {
                            $city.val(String(selected));
                            $city.data('selected', '');
                        }
                        AIZ.plugins.bootstrapSelect('refresh');
                    }
                }
            });
        }

/*
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
*/
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

            {{--
            // const verifyBtnId = `verify-${fieldId}-btn`;
            // const verifyBtn = $('#' + verifyBtnId);
            --}}

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

            {{--
            // const originalText = verifyBtn.text();
            // verifyBtn.text('Verifying...').prop('disabled', true);
            --}}

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

                                    // --------------- Temp comment ------------- //
                                    // $('#street_add_first_business').val(data.contact_details.principal.address);
                                    // $('#registration_date').val(data.date_of_registration);
                                    // $('#const_of_business').val(data.constitution_of_business);
                                    // $('#gstin_current_status').val(data.gstin_status);
                                    // $('#company_name').val(data.business_name);
                                    // $('#phone_code').val(data.contact_details.principal.mobile);
                                    // $('#whats_app_no').val(data.contact_details.principal.mobile);
                                    // $('#prim_email_business').val(data.contact_details.principal.email);
                                    // --------------- Temp comment ------------- //


                                    // Correctly accessing the first promoter, or showing an empty string if it doesn't exist
                                    // $('#con_person_name').val(data.promoters.length > 0 ? data.promoters[0] : '');
                                } 

                                if (fieldId == "iec_no") {

                                    // $('#street_add_first_business').val(data.address);
                                    // $('#registration_date').val(data.iec_issuance_date);

                                    // $('#const_of_business').val(data.constitution_of_business);

                                    // $('#uin_current_status').val(data.iec_status);
                                    // $('#company_name').val(data.firm_name);
                                    // $('#phone_code').val(data.firm_mobile_no);
                                    // $('#whats_app_no').val(data.firm_mobile_no);
                                    // $('#prim_email_business').val(data.firm_email_id);

                                    // Correctly accessing the first promoter, or showing an empty string if it doesn't exist
                                    // $('#con_person_name').val(data.director_details.length > 0 ? data.director_details[0].name : '');
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

         /* ----------------------------- Pincode ----------------------- */

            function pincode_info(e){
                const $input = e && e.target ? $(e.target) : $('#pincode');
                clearTimeout($input.data('timer'));
                
                const timer = setTimeout(function() {
                    const postalCode = ($input.val() || '').trim();
                    const countryId = $('[name="country_id"]').val() || null;

                    if (postalCode === '') {
                        return;
                    }

                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        url: "{{ route('get-location') }}",
                        type: 'POST',
                        data: {
                            postal_code: postalCode,
                            country_id: countryId
                        },
                        success: function(response) {
                            if (response.country_id) {
                                $('[name="country_id"]').val(response.country_id).data('selected','');
                                AIZ.plugins.bootstrapSelect('refresh');
                            }

                            if (response.state_id) {
                                $('[name="state_id"]').data('selected', response.state_id);
                            }

                            if (response.city_id) {
                                $('[name="city_id"]').data('selected', response.city_id);
                            }

                            // Trigger cascading load with slight delay so state -> city happens sequentially
                            if (response.state_id) {
                                setTimeout(function () {
                                    get_states($('[name="country_id"]').val()).done(function () {
                                        $('[name="state_id"]').val(String(response.state_id));
                                        AIZ.plugins.bootstrapSelect('refresh');
                                        // wait a bit to ensure cities are loaded before setting
                                        setTimeout(function () {
                                            if (response.city_id) {
                                                get_city(response.state_id).done(function () {
                                                    $('[name="city_id"]').val(String(response.city_id));
                                                    AIZ.plugins.bootstrapSelect('refresh');
                                                });
                                            } else {
                                                AIZ.plugins.bootstrapSelect('refresh');
                                            }
                                        }, 300);
                                    });
                                }, 200);
                            } else {
                                AIZ.plugins.bootstrapSelect('refresh');
                            }
                        },
                        error: function() {
                            AIZ.plugins.notify('danger', 'Error fetching location data');
                        }
                    });
                }, 400);
                
                $input.data('timer', timer);
            };

            /*
            let debounceTimeout;

            function pincode_info(){
                console.dir('working');
                clearTimeout(debounceTimeout);

                debounceTimeout = setTimeout(() => {
                    var postalCode = $('#pincode').val().trim();

                    if (postalCode.length === 0) {
                        $('#city').val('');
                        $('#state').val('');
                        return;
                    }

                    $.ajax({
                        url: 'https://secure.geonames.org/postalCodeSearchJSON',
                        dataType: 'json',
                        data: {
                            postalcode: postalCode,
                            country: '',
                            username: 'umair.makent'
                        },
                        success: function (data) {
                            if (data.postalCodes.length > 0) {
                                $('#country_name').val(data.postalCodes[0].countryCode).focus();
                                $('#city').val(data.postalCodes[0].adminName2).focus();
                                $('#state').val(data.postalCodes[0].adminName1).focus();
                                $('#pincode').focus();

                                $('#response').html('<pre>' + JSON.stringify(data, null, 2) + '</pre>');
                            }
                        }
                    });
                }, 100); // 500ms delay
            }
            */

            // $(document).ready(function () {
                // $('#pincode').on('input', pincode_info); // Use input event for real-time typing
            // });

            const IFSC_LOOKUP_URL = "{{ route('utilities.ifsc.lookup') }}";

            function setIfscTarget(selector, value) {
                if (!selector || !value) return;
                const el = document.querySelector(selector);
                if (el) {
                    el.value = value;
                }
            }

            function handleIfscButton(btn) {
                const input = btn.dataset.ifsc ? document.querySelector(btn.dataset.ifsc) : null;
                const code = (input?.value || '').trim();

                if (!code) {
                    AIZ.plugins.notify('warning', 'Enter IFSC code first');
                    return;
                }

                const originalText = btn.innerText;
                btn.disabled = true;
                btn.innerText = 'Fetching...';

                fetch(`${IFSC_LOOKUP_URL}?ifsc=${encodeURIComponent(code)}`)
                    .then(resp => resp.json().then(body => ({ ok: resp.ok, body })))
                    .then(({ ok, body }) => {
                        if (!ok || !body?.success) {
                            AIZ.plugins.notify('danger', body?.message || 'Unable to fetch bank details');
                            return;
                        }

                        const info = body.data || {};
                        setIfscTarget(btn.dataset.bank, info.bank || '');
                        setIfscTarget(btn.dataset.branch, info.branch || '');
                        setIfscTarget(btn.dataset.address, info.address || '');
                        setIfscTarget(btn.dataset.code, info.bank_code || info.ifsc || '');
                        setIfscTarget(btn.dataset.micr, info.micr || '');

                        AIZ.plugins.notify('success', 'Bank details applied');
                    })
                    .catch(() => {
                        AIZ.plugins.notify('danger', 'Unable to fetch bank details');
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerText = originalText;
                    });
            }

            document.addEventListener('click', function (event) {
                const btn = event.target.closest('.js-ifsc-lookup');
                if (!btn) return;
                handleIfscButton(btn);
            });

            document.addEventListener("change", function (event) {
                const targetNames = ["country_id"];

                if (event.target && targetNames.includes(event.target.name)) {
                    console.dir(`Function hit on: ${event.target.name}, value: ${event.target.value}`);
                    country_id = event.target.value;
                    myCustomFunction(event.target.name);
                }
            });


            function myCustomFunction(fieldName) {
                console.dir("Custom function executed →", fieldName);

                // Remove error if exists
                const errorElement = document.getElementById(`${fieldName}-error`);
                if (errorElement) {
                    errorElement.remove();
                    console.dir(`Removed error for: ${fieldName}`);
                }
            }

            function passport_clear() {
                console.dir('passport clear');
                const passportField = document.getElementById('passport_no');
                if (passportField) {
                    passportField.value = '';
                }
            };

    </script>
@endsection
