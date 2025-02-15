@extends('auth.layouts.authentication')

@section('content')

<style>
    .login_form_popup input {
    border: 1px solid #363636CC;
    border-radius: 15px !important;
    height: 45px !important;
}


.login_form_popup textarea,
.login_form_popup .dropdown-toggle {
    border: 1px solid #363636CC;
    border-radius: 15px !important;
}

.login_form_popup .form-control::placeholder {
    font-size: 14px;
}

.login_form_popup .iti__selected-flag {
    background: transparent !important;
    border: 0 !important;
}

.login_form_popup .iti--allow-dropdown input {
    padding-left: 80px !important;
}

.login_form_popup label {
    font-size: 16px !important;
    font-weight: 500 !important;
    color: #363636;
}

.login_form_popup .modal-header {
    border: 0;
    padding: 0;
    text-align: center;
    display: block;
}

.login_form_popup h5.modal-title {
    font-size: 24px;
    font-weight: 500;
    padding-top: 13px;
    padding-bottom: 10px;
}

.login_form_popup .modal-content {
    border: 0 !important;
    border-radius: 40px !important;
    padding-left: 40px;
    padding-right: 40px;
    padding-bottom: 25px !important;
    padding-top: 30px !important;
}

.login_form_popup .modal-body {
    padding: 0 !important;
    overflow: initial !important;
    max-height: max-content;
}

.login_form_popup .modal-footer {
    border-top: 0 !important;
    padding: 0;
    justify-content: space-between;
}
.proceed_btn
{
        border-radius: 50px !important;
    padding: 7px 25px;
}

@media(max-width:767px)
{
.login_form_popup .modal-content {
    border: 0 !important;
    border-radius: 40px !important;
    padding-left: 15px;
    padding-right: 15px;
    padding-bottom: 10px !important;
    padding-top: 20px !important;
}
.login_form_popup h5.modal-title {
    font-size: 18px;
    font-weight: 500;
    padding-top: 13px;
    padding-bottom: 10px;
}
.login_form_popup label {
    font-size: 14px !important;
}
.login_form_popup input {
    border: 1px solid #363636CC;
    border-radius: 15px !important;
    height: 42px !important;
}
.login_form_popup .form-control::placeholder {
    font-size: 13px;
}
.login_form_popup .modal-footer {
    min-height: auto !important;
}
}

</style>
    <!-- aiz-main-wrapper -->
    <div class="aiz-main-wrapper d-flex flex-column justify-content-md-center bg-white">
        <section class="bg-white overflow-hidden">
            <div class="row">
                <div class="col-xxl-6 col-xl-9 col-lg-10 col-md-7 mx-auto py-lg-4">
                    <div class="card shadow-none rounded-0 border-0">
                        <div class="row no-gutters">
                            <!-- Left Side Image-->
                            <div class="col-lg-6">
                                <img src="{{ uploaded_asset(get_setting('customer_login_page_image')) }}" alt="{{ translate('Customer Login Page Image') }}" class="img-fit h-100">
                            </div>

                            <!-- Right Side -->
                            <div class="col-lg-6 p-4 p-lg-5 d-flex flex-column justify-content-center border right-content" style="height: auto;">
                                <!-- Site Icon -->
                                <div class="size-48px mb-3 mx-auto mx-lg-0">
                                    <img src="{{ uploaded_asset(get_setting('site_icon')) }}" alt="{{ translate('Site Icon')}}" class="img-fit h-100">
                                </div>

                                <!-- Titles -->
                                <div class="text-center text-lg-left">
                                    <h1 class="fs-20 fs-md-24 fw-700 text-primary" style="text-transform: uppercase;">{{ translate('Welcome Back !')}}</h1>
                                    <h5 class="fs-14 fw-400 text-dark">{{ translate('Login to your account')}}</h5>
                                </div>

                                <!-- Login form -->
                                <div class="pt-3">
                                    <div class="">
                                        <form id="login-form-customer" class="form-default" role="form" action="{{ route('user.login.via.otp') }}" method="POST">
                                            @csrf
                                            
                                            <!-- Email or Phone -->
                                            @if (addon_is_activated('otp_system'))
                                                <div class="form-group phone-form-group mb-1">
                                                    <label for="phone" class="fs-12 fw-700 text-soft-dark">{{  translate('Phone') }}</label>
                                                    <input type="tel" id="phone-code" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }} rounded-0" value="{{ old('phone') }}" placeholder="" name="phone" autocomplete="off">
                                                </div>

                                                <input type="hidden" name="country_code" value="">
                                                
                                                <div class="form-group email-form-group mb-1 d-none">
                                                    <label for="email" class="fs-12 fw-700 text-soft-dark">{{  translate('Email') }}</label>
                                                    <input type="email" class="form-control rounded-0 {{ $errors->has('email') ? ' is-invalid' : '' }}" value="{{ old('email') }}" placeholder="{{  translate('johndoe@example.com') }}" name="email" id="email" autocomplete="off">
                                                    @if ($errors->has('email'))
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $errors->first('email') }}</strong>
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                {{-- <div class="form-group text-right">
                                                    <button class="btn btn-link p-0 text-primary fs-12 fw-400" type="button" onclick="toggleEmailPhone(this)"><i>*{{ translate('Use Email Instead') }}</i></button>
                                                </div> --}}
                                            @else
                                                <div class="form-group">
                                                    <label for="email" class="fs-12 fw-700 text-soft-dark">{{  translate('Email') }}</label>
                                                    <input type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }} rounded-0" value="{{ old('email') }}" placeholder="{{  translate('johndoe@example.com') }}" name="email" id="email" autocomplete="off">
                                                    @if ($errors->has('email'))
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $errors->first('email') }}</strong>
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                                
                                            <!-- password -->
                                            <div class="form-group">
                                                <label for="password" class="fs-12 fw-700 text-soft-dark pt-3">{{  translate('Password') }}</label>
                                                <div class="position-relative">
                                                    <input type="password" class="form-control rounded-0 {{ $errors->has('password') ? ' is-invalid' : '' }}" placeholder="{{ translate('Password')}}" name="password" id="password">
                                                    <i class="password-toggle las la-2x la-eye"></i>
                                                </div>
                                            </div>

                                            <div class="row mb-2">
                                                <!-- Remember Me -->
                                                <div class="col-6">
                                                    <label class="aiz-checkbox">
                                                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                                        <span class="has-transition fs-12 fw-400 text-gray-dark hov-text-primary">{{  translate('Remember Me') }}</span>
                                                        <span class="aiz-square-check"></span>
                                                    </label>
                                                </div>
                                                <!-- Forgot password -->
                                                <div class="col-6 text-right">
                                                    <a href="{{ route('password.request') }}" class="text-reset fs-12 fw-400 text-gray-dark hov-text-primary"><u>{{ translate('Forgot password?')}}</u></a>
                                                </div>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="mb-4 mt-4">
                                                <button type="submit" class="btn btn-primary btn-block fw-700 fs-14 rounded-0">{{  translate('Login') }}</button>
                                            </div>
                                        </form>

                                        <!-- DEMO MODE -->
                                        @if (env("DEMO_MODE") == "On")
                                            <div class="mb-4">
                                                <table class="table table-bordered mb-0">
                                                    <tbody>
                                                        <tr>
                                                            <td>{{ translate('Customer Account')}}</td>
                                                            <td class="text-center">
                                                                <button class="btn btn-info btn-sm" onclick="autoFillCustomer()">{{ translate('Copy credentials') }}</button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif

                                        <!-- Social Login -->
                                        @if(get_setting('google_login') == 1 || get_setting('facebook_login') == 1 || get_setting('twitter_login') == 1 || get_setting('apple_login') == 1)
                                            <div class="text-center mb-3">
                                                <span class="bg-white fs-12 text-gray">{{ translate('Or Login With')}}</span>
                                            </div>
                                            <ul class="list-inline social colored text-center mb-4">
                                                @if (get_setting('facebook_login') == 1)
                                                    <li class="list-inline-item">
                                                        <a href="{{ route('social.login', ['provider' => 'facebook']) }}" class="facebook">
                                                            <i class="lab la-facebook-f"></i>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if(get_setting('google_login') == 1)
                                                    <li class="list-inline-item">
                                                        <a href="{{ route('social.login', ['provider' => 'google']) }}" class="google">
                                                            <i class="lab la-google"></i>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (get_setting('twitter_login') == 1)
                                                    <li class="list-inline-item">
                                                        <a href="{{ route('social.login', ['provider' => 'twitter']) }}" class="twitter">
                                                            <i class="lab la-twitter"></i>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (get_setting('apple_login') == 1)
                                                    <li class="list-inline-item">
                                                        <a href="{{ route('social.login', ['provider' => 'apple']) }}"
                                                            class="apple">
                                                            <i class="lab la-apple"></i>
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        @endif
                                    </div>

                                    <!-- Register Now -->
                                    <p class="fs-12 text-gray mb-0">
                                        {{ translate('Dont have an account?')}}
                                        <a href="{{ route('user.new_registration') }}" class="ml-2 fs-14 fw-700 animate-underline-primary">{{ translate('Register Now')}}</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <!-- Go Back -->
                        <div class="mt-3 mr-4 mr-md-0">
                            <a href="{{ url()->previous() }}" class="ml-auto fs-14 fw-700 d-flex align-items-center text-primary" style="max-width: fit-content;">
                                <i class="las la-arrow-left fs-20 mr-1"></i>
                                {{ translate('Back to Previous Page')}}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>


    {{--/* otp popup  */ --}}

    <div class="modal login_form_popup" id="otp-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
                 <div class="heading">
                    <img src="{{ static_asset('assets/img/pharm_favicon.svg') }}" />
                    <h5 class="modal-title" id="exampleModalLabel_phone">Mobile OTP Verify</h5>
                </div>
              
            </div>
                <form class="form-default" id="otp-login-customer" role="form" action="{{ route('user.login.via.otp.verify') }}" method="POST">
                @csrf
                    <div class="modal-body">
                        <div class="form-group mt-md-4 mt-2 adhar_field">
                            <label class="pb-2">Verify OTP *</label>
                            <input type="text" class="form-control" name="otp" pattern="[0-9]+" minlength="6"
                                maxlength="6" placeholder="Please Enter OTP" required />
                        </div>
                    </div>
                    <div class="modal-footer " style="justify-content: end; !important">
                        <div class="purple_btn">
                            <button type="submit" class="btn btn-primary proceed_btn">Proceed</button>
                        </div>
                        
                        <!-- <button type="button" class="btn btn-secondary" onclick="closeOtpModal()">Close</button> -->
                    </div>
                </form>
            
            </div>
        </div>
        </div>
    
    {{--/* otp popup  */ --}}


@endsection

@section('script')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        function autoFillCustomer(){
            $('#email').val('customer@example.com');
            $('#password').val('123456');
        }


        /*-----------------New CODE-------------------------------------- */   
        function closeOtpModal() {
            $('#otp-modal').modal('hide');
        }
        
        $('#login-form-customer').on('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response) {
                    if (response.status == true) {
                        toastr.success(response.notification);
                        if(response.otp == true){
                            $('#otp-modal').modal('show');
                        } else {
                            toastr.error('Somthing Went Wrong!');
                        }
        
                    } else {
                        toastr.error(response.notification);
                    }
                },
                error: function(error) {
                    toastr.error('An error occurred while processing your request.');
                }
            });
        });

        $('#otp-login-customer').on('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(response) {
                    if (response.status == true) {
                        toastr.success(response.notification);

                        setTimeout(function() {
                            location.reload();
                        }, 1000);
        
                    } else {
                        toastr.error(response.notification);
                    }
                },
                error: function(error) {
                    toastr.error('An error occurred while processing your request.');
                }
            });
        });


    </script>
@endsection