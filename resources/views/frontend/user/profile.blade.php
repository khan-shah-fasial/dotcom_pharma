@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="aiz-titlebar mb-4">
      <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="fs-20 fw-700 text-dark">{{ translate('Manage Profile') }}</h1>
        </div>
      </div>
    </div>

    <!-- Basic Info-->
    <div class="card rounded-0 shadow-none border">
        <div class="card-header pt-4 border-bottom-0">
            <h5 class="mb-0 fs-18 fw-700 text-dark">{{ translate('Personal Info')}}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('user.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <!-- Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('Concerned Person Name') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('Your Name') }}" name="name" value="{{ Auth::user()->name }}" required>
                    </div>
                </div>
                <!-- Company Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('Company Name') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('Company Name') }}" name="company_name" value="{{ Auth::user()->company_name }}" required>
                    </div>
                </div>
                <!-- whatsapp Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('Whatsapp No') }}</label>
                    <div class="col-md-10">

                        @php
                            if(!empty(Auth::user()->whats_app_no)){
                                $whats_app_no_parts = explode('-', Auth::user()->whats_app_no);
                                $whats_app_no_parts_number = $whats_app_no_parts[1] ?? ''; 
                            }
                        @endphp

                        <input type="text" id="whats_app_no" class="form-control rounded-0" placeholder="{{ translate('Whatsapp No') }}" name="whats_app_no" value="{{ $whats_app_no_parts_number }}">

                        <input type="hidden" name="country_code_whats_app_no" value="">
                        <input type="hidden" name="whats_app_no_meta" value="">
                    </div>
                </div>

                <!-- tel number -->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('Telephone No') }}</label>
                    <div class="col-md-10">
                        <input type="text" id="tel_number" class="form-control rounded-0" placeholder="{{ translate('Telephone No') }}" name="tel_number" value="{{ Auth::user()->tel_number }}">
                    </div>
                </div>
                <!-- gst number -->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('GST No') }}</label>
                    <div class="col-md-10">
                        <input type="text" id="gst_no" class="form-control rounded-0" placeholder="{{ translate('GST No') }}" name="gst_no" value="{{ Auth::user()->gst_no }}" required>
                    </div>
                </div>
                <!-- Post -->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('Post') }}</label>
                    <div class="col-md-10">
                        <input type="text" id="post" class="form-control rounded-0" placeholder="{{ translate('Post') }}" name="post" value="{{ Auth::user()->post }}">
                    </div>
                </div>
                <!-- Phone-->
                {{-- <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14">{{ translate('Your Phone') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('Your Phone')}}" name="phone" value="{{ Auth::user()->phone }}">
                    </div>
                </div> --}}
                <!-- Photo-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14">{{ translate('Photo') }}</label>
                    <div class="col-md-10">
                        <div class="input-group" data-toggle="aizuploader" data-type="image">
                            <div class="input-group-prepend">
                                <div class="input-group-text bg-soft-secondary font-weight-medium rounded-0">{{ translate('Browse')}}</div>
                            </div>
                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                            <input type="hidden" name="photo" value="{{ Auth::user()->avatar_original }}" class="selected-files">
                        </div>
                        <div class="file-preview box sm">
                        </div>
                    </div>
                </div>
                <!-- Password-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14">{{ translate('Your Password') }}</label>
                    <div class="col-md-10">
                        <input type="password" class="form-control rounded-0" placeholder="{{ translate('New Password') }}" name="new_password">
                    </div>
                </div>
                <!-- Confirm Password-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14">{{ translate('Confirm Password') }}</label>
                    <div class="col-md-10">
                        <input type="password" class="form-control rounded-0" placeholder="{{ translate('Confirm Password') }}" name="confirm_password">
                    </div>
                </div>
                <!-- Submit Button-->
                <div class="form-group mb-0 text-right">
                    <button type="submit" class="btn btn-primary rounded-0 w-150px mt-3">{{translate('Update Profile')}}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bank Info-->
    <div class="card rounded-0 shadow-none border">
        <div class="card-header pt-4 border-bottom-0">
            <h5 class="mb-0 fs-18 fw-700 text-dark">{{ translate('Bank Info')}}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('user.bankdetails.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <!-- Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('Bank Name') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('Your Bank Name') }}" name="bank_name" value="{{ Auth::user()->bank_name }}" required>
                    </div>
                </div>
                <!-- Company Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('Account No') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('Account No') }}" name="account_no" value="{{ Auth::user()->account_no }}" required>
                    </div>
                </div>
                <!-- Company Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('Branch No') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('Branch No') }}" name="branch_no" value="{{ Auth::user()->branch_no }}" required>
                    </div>
                </div>
                <!-- Company Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('Branch Code') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('Branch Code') }}" name="branch_code" value="{{ Auth::user()->branch_code }}" required>
                    </div>
                </div>
                <!-- Company Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('IFSC Code') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('IFSC Code') }}" name="ifsc_code" value="{{ Auth::user()->ifsc_code }}" required>
                    </div>
                </div>
                <!-- Company Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('MICR Code') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('MICR Code') }}" name="micr_code" value="{{ Auth::user()->micr_code }}" required>
                    </div>
                </div>
                <!-- Company Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('Customer Care Executive') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('Customer Care Executive') }}" name="customer_care_executive" value="{{ Auth::user()->customer_care_executive }}">
                    </div>
                </div>

                <!-- Submit Button-->
                <div class="form-group mb-0 text-right">
                    <button type="submit" class="btn btn-primary rounded-0 w-150px mt-3">{{translate('Update Profile')}}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- License Details-->
    <div class="card rounded-0 shadow-none border">
        <div class="card-header pt-4 border-bottom-0">
            <h5 class="mb-0 fs-18 fw-700 text-dark">{{ translate('License Details')}}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('user.licensedetails.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <!-- Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('CC NO') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('Your CC NO') }}" name="cc_no" value="{{ Auth::user()->cc_no }}" required>
                    </div>
                </div>
                <!-- Company Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('D.L No 1 (Drug Licence)') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('D.L No 1') }}" name="d_l_no_1" value="{{ Auth::user()->d_l_no_1 }}" required>
                    </div>
                </div>
                <!-- Company Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('D.L No 2') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('D.L No 2') }}" name="d_l_no_2" value="{{ Auth::user()->d_l_no_2 }}" required>
                    </div>
                </div>
                <!-- Company Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('D.L No 3') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('D.L No 3') }}" name="d_l_no_3" value="{{ Auth::user()->d_l_no_3 }}" required>
                    </div>
                </div>

                <!-- Submit Button-->
                <div class="form-group mb-0 text-right">
                    <button type="submit" class="btn btn-primary rounded-0 w-150px mt-3">{{translate('Update Profile')}}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Transport Details-->
    <div class="card rounded-0 shadow-none border">
        <div class="card-header pt-4 border-bottom-0">
            <h5 class="mb-0 fs-18 fw-700 text-dark">{{ translate('Transport Details')}}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('user.transportdetails.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <!-- Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('D.L Expiry Date') }}</label>
                    <div class="col-md-10">
                        <input type="date" class="form-control rounded-0" name="d_l_exp_Date" value="{{ Auth::user()->d_l_exp_Date }}" required>
                    </div>
                </div>
                <!-- Company Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('Transport') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('Transport') }}" name="transport" value="{{ Auth::user()->transport }}" required>
                    </div>
                </div>
                <!-- Company Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('Cargo') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('Cargo') }}" name="cargo" value="{{ Auth::user()->cargo }}" required>
                    </div>
                </div>
                <!-- Company Name-->
                <div class="form-group row">
                    <label class="col-md-2 col-form-label fs-14 fs-14">{{ translate('Booked To') }}</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control rounded-0" placeholder="{{ translate('Booked To') }}" name="booked_to" value="{{ Auth::user()->booked_to }}" required>
                    </div>
                </div>

                <!-- Submit Button-->
                <div class="form-group mb-0 text-right">
                    <button type="submit" class="btn btn-primary rounded-0 w-150px mt-3">{{translate('Update Profile')}}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Address -->
    <div class="card rounded-0 shadow-none border">
        <div class="card-header pt-4 border-bottom-0">
            <h5 class="mb-0 fs-18 fw-700 text-dark">{{ translate('Address')}}</h5>
        </div>
        <div class="card-body">
            @foreach (Auth::user()->addresses as $key => $address)
                <div class="">
                    <div class="border p-4 mb-4 position-relative">
                        <div class="row fs-14 mb-2 mb-md-0">
                            <span class="col-md-2 text-secondary">{{ translate('Address') }}:</span>
                            <span class="col-md-8 text-dark">{{ $address->address }}</span>
                        </div>
                        <div class="row fs-14 mb-2 mb-md-0">
                            <span class="col-md-2 text-secondary">{{ translate('Postal Code') }}:</span>
                            <span class="col-md-10 text-dark">{{ $address->postal_code }}</span>
                        </div>
                        <div class="row fs-14 mb-2 mb-md-0">
                            <span class="col-md-2 text-secondary">{{ translate('City') }}:</span>
                            <span class="col-md-10 text-dark">{{ optional($address->city)->name }}</span>
                        </div>
                        <div class="row fs-14 mb-2 mb-md-0">
                            <span class="col-md-2 text-secondary">{{ translate('State') }}:</span>
                            <span class="col-md-10 text-dark">{{ optional($address->state)->name }}</span>
                        </div>
                        <div class="row fs-14 mb-2 mb-md-0">
                            <span class="col-md-2 text-secondary">{{ translate('Country') }}:</span>
                            <span class="col-md-10 text-dark">{{ optional($address->country)->name }}</span>
                        </div>
                        <div class="row fs-14 mb-2 mb-md-0">
                            <span class="col-md-2 text-secondary text-secondary">{{ translate('Phone') }}:</span>
                            <span class="col-md-10 text-dark">{{ $address->phone }}</span>
                        </div>
                        @if ($address->set_default)
                            <div class="absolute-md-top-right pt-2 pt-md-4 pr-md-5">
                                <span class="badge badge-inline badge-secondary-base text-white p-3 fs-12" style="border-radius: 25px; min-width: 80px !important;">{{ translate('Default') }}</span>
                            </div>
                        @endif
                        <div class="dropdown position-absolute right-0 top-0 pt-4 mr-1">
                            <button class="btn bg-gray text-white px-1 py-1" type="button" data-toggle="dropdown">
                                <i class="la la-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                                <a class="dropdown-item" onclick="edit_address('{{$address->id}}')">
                                    {{ translate('Edit') }}
                                </a>
                                @if (!$address->set_default)
                                    <a class="dropdown-item" href="{{ route('addresses.set_default', $address->id) }}">{{ translate('Make This Default') }}</a>
                                @endif
                                <a class="dropdown-item" href="{{ route('addresses.destroy', $address->id) }}">{{ translate('Delete') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            <!-- Add New Address -->
            <div class="" onclick="add_new_address()">
                <div class="border p-3 mb-3 c-pointer text-center bg-light has-transition hov-bg-soft-light">
                    <i class="la la-plus la-2x"></i>
                    <div class="alpha-7 fs-14 fw-700">{{ translate('Add New Address') }}</div>
                </div>
            </div>
        </div>
    </div>


    <!-- Change Email -->
    <form action="{{ route('user.change.email') }}" method="POST">
        @csrf
        <div class="card rounded-0 shadow-none border">
          <div class="card-header pt-4 border-bottom-0">
              <h5 class="mb-0 fs-18 fw-700 text-dark">{{ translate('Change your email')}}</h5>
          </div>
          <div class="card-body">
              <div class="row">
                  <div class="col-md-2">
                      <label class="fs-14">{{ translate('Your Email') }}</label>
                  </div>
                  <div class="col-md-10">
                      <div class="input-group mb-3">
                        <input type="email" class="form-control rounded-0" placeholder="{{ translate('Your Email')}}" name="email" value="{{ Auth::user()->email }}" />
                        <div class="input-group-append">
                           <button type="button" class="btn btn-outline-secondary new-email-verification">
                               <span class="d-none loading">
                                   <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>{{ translate('Sending Email...') }}
                               </span>
                               <span class="default">{{ translate('Verify') }}</span>
                           </button>
                        </div>
                      </div>
                      <div class="form-group mb-0 text-right">
                          <button type="submit" class="btn btn-primary rounded-0 w-150px mt-3">{{translate('Update Email')}}</button>
                      </div>
                  </div>
              </div>
          </div>
        </div>
    </form>

    <!---- change phone ------->
    <form id="change_phone" action="{{ route('user.phone.update') }}" method="POST">
        @csrf
        <div class="card rounded-0 shadow-none border">
          <div class="card-header pt-4 border-bottom-0">
              <h5 class="mb-0 fs-18 fw-700 text-dark">{{ translate('Change your Phone')}}</h5>
          </div>
          <div class="card-body">
              <div class="row">
                  <div class="col-md-2">
                      <label class="fs-14">{{ translate('Your Phone') }}</label>
                  </div>
                  <div class="col-md-10">
                      <div class="input-group mb-3">
                        @php
                            if(!empty(Auth::user()->phone)){
                                $phone_parts = explode('-', Auth::user()->phone);
                                $phone_parts_parts_number = $phone_parts[1] ?? ''; 
                            }
                        @endphp

                        <input type="text" id="phone_code" class="form-control rounded-0" placeholder="{{ translate('Phone No') }}" name="phone_code" value="{{ $phone_parts_parts_number }}" required>

                        <input type="hidden" name="country_code_phone_code" value="">
                        <input type="hidden" name="phone_code_meta" value="">

                      </div>
                      <div class="form-group mb-0 text-right">
                          <button type="submit" class="btn btn-primary rounded-0 w-150px mt-3">{{translate('Update Phone')}}</button>
                      </div>
                  </div>
              </div>
          </div>
        </div>
    </form>


    {{--/* otp popup  */ --}}

    <div class="modal" id="otp-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mobile OTP Verify</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
                <form class="form-default" id="otp-phone-verify" role="form" action="{{ route('user.phone.verify.update') }}" method="POST">
                @csrf
                    <div class="modal-body">
                        <div class="form-group mt-4 adhar_field">
                            <label class="pb-2">Verify OTP *</label>
                            <input type="text" class="form-control" name="otp" pattern="[0-9]+" minlength="6"
                                maxlength="6" placeholder="Please Enter OTP" required />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Proceed</button>
                        <button type="button" class="btn btn-secondary" onclick="closeOtpModal()">Close</button>
                    </div>
                </form>
            
            </div>
        </div>
        </div>
    
    {{--/* otp popup  */ --}}

@endsection

@section('modal')
    <!-- Address modal -->
    @include('frontend.partials.address.address_modal')
@endsection

@section('script')
    @include('frontend.partials.address.address_js')

    <script type="text/javascript">
        $('.new-email-verification').on('click', function() {
            $(this).find('.loading').removeClass('d-none');
            $(this).find('.default').addClass('d-none');
            var email = $("input[name=email]").val();

            $.post('{{ route('user.new.verify') }}', {_token:'{{ csrf_token() }}', email: email}, function(data){
                data = JSON.parse(data);
                $('.default').removeClass('d-none');
                $('.loading').addClass('d-none');
                if(data.status == 2)
                    AIZ.plugins.notify('warning', data.message);
                else if(data.status == 1)
                    AIZ.plugins.notify('success', data.message);
                else
                    AIZ.plugins.notify('danger', data.message);
            });
        });
    </script>

    <script>

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

            // // Set default country code to +91 (India)
            // iti1.setCountry('in'); // 'in' is the ISO2 code for India

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

        intil_input('whats_app_no');
        intil_input('phone_code');


        $(document).ready(function() {

            function validate_form(formname) {
                // Initialize validation for the specific form formname
                initValidate(`#${formname}`);

                // Attach the submit event handler
                $(`#${formname}`).on('submit', function (e) {
                    var form = $(this);
                    ajax_form_submit(e, form, function (response) {
                        responseHandler(formname, response);
                    });
                });

                // Define the response handler function
                function responseHandler(formname, response) {
                    if(response.otp == true){
                        $('#otp-modal').modal('show');
                    } else {
  
                        location.reload();

                    }
                }

                ['phone_code', 'tel_number', 'whats_app_no'].forEach(function (id) {
                    const element = document.getElementById(id);
                    if (element) {
                        element.addEventListener('input', function (event) {
                            this.value = this.value.replace(/[^0-9+ ]/g, '');
                        });
                    }
                });

            }

            validate_form('change_phone');
            validate_form('otp-phone-verify');

        });

    </script>

    @if (get_setting('google_map') == 1)
        @include('frontend.partials.google_map')
    @endif

@endsection
