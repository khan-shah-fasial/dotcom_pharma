@extends('frontend.layouts.app')

@section('content')
    <section class="gradient-custom">
        <div class="container py-5">
            <div class="row justify-content-center align-items-center h-100">
                <div class="col-12 col-lg-12 col-xl-12">
                    <div class="card shadow-2-strong card-registration" style="border-radius: 15px;">
                        <div class="card-body p-4 p-md-5">
                            <h3 class="mb-4 pb-2 pb-md-0 mb-md-5 center">Registration Form</h3>
                            <form id="user-info" action="{{ url(route('create.new.user.registration')) }}" method="post"
                                enctype="multipart/form-data">
                                @csrf

                                <div class="row">
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="name">Name</label>
                                            <input type="text" id="name" name="name"
                                                class="form-control form-control-lg" required />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="email_id">Email</label>
                                            <input type="email" id="email_id" name="email_id"
                                                class="form-control form-control-lg" required />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="phone">Phone No</label>
                                            <input type="text" id="phone" name="phone"
                                                class="form-control form-control-lg" required />
                                        </div>

                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="phone">Ad. Contact Number</label>
                                            <input type="text" id="ad_contact_number" name="ad_contact_number"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="phone">Land Mark Village</label>
                                            <input type="text" id="land_mark_village" name="land_mark_village"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="phone">Post</label>
                                            <input type="text" id="post" name="post"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="address_1">Address 1</label>
                                            <textarea class="form-control" id="address_1" name="address_1" rows="3" required></textarea>
                                        </div>

                                    </div>
                                    <div class="col-md-6 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="address_2">Address 2</label>
                                            <textarea class="form-control" id="address_2" name="address_2" rows="3"></textarea>
                                        </div>

                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="pincode">Pincode</label>
                                            <input type="text" id="pincode" name="pincode"
                                                class="form-control form-control-lg" required />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="district">District</label>
                                            <input type="text" id="district" name="district"
                                                class="form-control form-control-lg" required />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="state">State</label>
                                            <input type="text" id="state" name="state"
                                                class="form-control form-control-lg" required />
                                        </div>

                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="country__code">Country Code</label>
                                            <input type="text" id="country__code" name="country__code"
                                                class="form-control form-control-lg" required />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="phone_no_1">Phone No 1</label>
                                            <input type="text" id="phone_no_1" name="phone_no_1"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="phone_no_2">Phone No 2</label>
                                            <input type="text" id="phone_no_2" name="phone_no_2"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="whats_app_no">Whats App No</label>
                                            <input type="text" id="whats_app_no" name="whats_app_no"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="gst_no">GST No</label>
                                            <input type="text" id="gst_no" name="gst_no"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="cc_no">CC NO</label>
                                            <input type="text" id="cc_no" name="cc_no"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="d_l_no_1">D.L No 1 (Drug Licence)</label>
                                            <input type="text" id="d_l_no_1" name="d_l_no_1"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="d_l_no_2">D.L No 2</label>
                                            <input type="text" id="d_l_no_2" name="d_l_no_2"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="d_l_no_3">D.L No 3</label>
                                            <input type="text" id="d_l_no_3" name="d_l_no_3"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="d_l_exp_Date">D.L Expiry Date</label>
                                            <input type="date" id="d_l_exp_Date" name="d_l_exp_Date"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="transport">Transport</label>
                                            <input type="text" id="transport" name="transport"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="cargo">Cargo</label>
                                            <input type="text" id="cargo" name="cargo"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="booked_to">Booked To</label>
                                            <input type="text" id="booked_to" name="booked_to"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="bank_name">Bank Name</label>
                                            <input type="text" id="bank_name" name="bank_name"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="account_no">Account No</label>
                                            <input type="text" id="account_no" name="account_no"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="branch_no">Branch No</label>
                                            <input type="text" id="branch_no" name="branch_no"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="branch_code">Branch Code</label>
                                            <input type="text" id="branch_code" name="branch_code"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="ifsc_code">IFSC Code</label>
                                            <input type="text" id="ifsc_code" name="ifsc_code"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="micr_code">MICR Code</label>
                                            <input type="text" id="micr_code" name="micr_code"
                                                class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="customer_care_executive">Customer Care
                                                Executive</label>
                                            <input type="text" id="customer_care_executive"
                                                name="customer_care_executive" class="form-control form-control-lg" />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="password">Password</label>
                                            <input type="password" id="password" name="password"
                                                class="form-control form-control-lg" required />
                                        </div>

                                    </div>
                                    <div class="col-md-4 mb-4">

                                        <div class="form-group">
                                            <label class="form-label" for="password_confirmation">Conform Password</label>
                                            <input type="password" id="password_confirmation"
                                                name="password_confirmation" class="form-control form-control-lg"
                                                required />
                                        </div>

                                    </div>
                                </div>


                                <div class="mt-4 pt-2">
                                    <button class="btn btn-primary btn-lg" type="submit">Submit</button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- - //------------------------------ Phone verify modal -----------------------// -- --}}

    <div class="modal fade" id="phone_otp_model" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalLabel_phone" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content py-3">
                <div class="modal-header">
                    <div class="heading">
                        <h5 class="modal-title" id="exampleModalLabel_phone">Verify Phone Number</h5>
                    </div>
                    <div class="purple_btn_close">
                        <button type="button" onclick="close_Phone_modal();" class="close p-1 px-3"
                            data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true" style="font-size: 24px;">&times;</span>
                        </button>
                    </div>
                </div>
                <form id="phone-verify-otp" action="{{ url(route('create.new.user.registration.phone.verify')) }}"
                    method="post">
                    @csrf

                    <div class="modal-body">
                        <div class="form-group">
                            <label for="recipient-name" class="col-form-label form-label">Verification Code:</label>
                            <input type="number" class="form-control" id="recipient-name" name="otp"
                                pattern="[0-9]+" minlength="6" maxlength="6" placeholder="Please Enter Code" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="blue_btn">
                            <button type="button" onclick="close_Phone_modal();" class="btn btn-secondary"
                                data-dismiss="modal">Close</button>
                        </div>
                        <div class="purple_btn">
                            <button type="submit" class="btn btn-primary">Verify</button>
                        </div>
                        <div class="resend_otp">
                            <a class="ms-4" class="btn btn-primary" id="resendOTPButton_Phone"
                                style="display: none; cursor: pointer;">Resend OTP</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- - //------------------------------  phone verify modal -----------------------// -- --}}

@endsection

@section('custome-script')
    <script>
        $(document).ready(function() {
            // Initialize form validation
            initValidate('#user-info');
            initValidate('#phone-verify-otp');

            $('#user-info').on('submit', function(e) {
                var form = $(this);
                ajax_form_submit(e, form, responseHandler_phone_verify_otp);
            });


            var responseHandler_phone_verify_otp = function(response) {

                $('#phone_otp_model').modal('show');

                setTimeout(function() {
                    var resendButton_phone = document.getElementById('resendOTPButton_Phone');
                    resendButton_phone.style.display = 'block';
                }, 30000); // 30 seconds

            };


            $('#phone-verify-otp').on('submit', function(e) {
                var form = $(this);
                ajax_form_submit(e, form, responseHandler_phone_verify);
            });


            var responseHandler_phone_verify = function(response) {
                var form = $('#phone-verify-otp');

                // Check if the response status indicates success
                if (response.status === 'success' && response.registration === 'approve') {
                    // Clear form inputs
                    form.find("input[type=text], input[type=email], input[type=password], textarea").val("");

                    // Reload the page after 100ms
                    setTimeout(function() {
                        location.reload();
                    }, 100);
                }

                if (response.status === 'success' && response.registration === 'not approve') {
                    // Clear form inputs
                    $('#phone_otp_model').modal('hide');

                    form.find("input[type=text], input[type=email], input[type=password], textarea").val("");

                    $('#not_approval_model').modal('show');
                }
            };

        });

        $(document).ready(function() {
            $('#resendOTPButton_Phone').click(function(e) {
                e.preventDefault();

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
            });
        });
    </script>
@endsection
