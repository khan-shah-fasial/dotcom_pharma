@extends('backend.layouts.app')

@section('content')
    <div class="container my-3">
        <div class="card">
            <div class="container my-3 mx-2">
                <h3> View  Details of Customer {{ $user->name ?? "-" }} </h3>
                <hr>
                <br>
                <div class="row">
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="name">Name</label>
                            <p>{{ $user->name ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="email_id">Email</label>
                            <p>{{ $user->email ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="phone">Phone No</label>
                            <p> {{ $user->phone ?? "-" }}</p>
                        </div>

                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="phone">Ad. Contact Number</label>
                            <p> {{ $user->ad_contact_number ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="phone">Land Mark Village</label>
                            <p> {{ $user->land_mark_village ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="phone">Post</label>
                            <p> {{ $user->post ?? "-" }}</p>
                        </div>

                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="address_1">Address 1</label>
                            <p> {{ $user->address_1 ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-6 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="address_2">Address 2</label>
                            <p> {{ $user->address_2 ?? "-" }}</p>
                        </div>

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="pincode">Pincode</label>
                            <p> {{ $user->pincode ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="district">District</label>
                            <p> {{ $user->district ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="state">State</label>
                            <p> {{ $user->state ?? "-" }}</p>
                        </div>

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="country__code">Country Code</label>
                            <p> {{ $user->country__code ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="phone_no_1">Phone No 1</label>
                            <p> {{ $user->phone_no_1 ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="phone_no_2">Phone No 2</label>
                            <p> {{ $user->phone_no_2 ?? "-" }}</p>
                        </div>

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="whats_app_no">Whats App No</label>
                            <p> {{ $user->whats_app_no ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="gst_no">GST No</label>
                            <p> {{ $user->gst_no ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="cc_no">CC NO</label>
                            <p> {{ $user->cc_no ?? "-" }}</p>
                        </div>

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="d_l_no_1">D.L No 1 (Drug Licence)</label>
                            <p> {{ $user->d_l_no_1 ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="d_l_no_2">D.L No 2</label>
                            <p> {{ $user->d_l_no_2 ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="d_l_no_3">D.L No 3</label>
                            <p> {{ $user->d_l_no_3 ?? "-" }}</p>
                        </div>

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="d_l_exp_Date">D.L Expiry Date</label>
                            <p> {{ $user->d_l_exp_Date ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="transport">Transport</label>
                            <p> {{ $user->transport ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="cargo">Cargo</label>
                            <p> {{ $user->cargo ?? "-" }}</p>
                        </div>

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="booked_to">Booked To</label>
                            <p> {{ $user->booked_to ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="bank_name">Bank Name</label>
                            <p> {{ $user->bank_name ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="account_no">Account No</label>
                            <p> {{ $user->account_no ?? "-" }}</p>
                        </div>

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="branch_no">Branch No</label>
                            <p> {{ $user->branch_no ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="branch_code">Branch Code</label>
                            <p> {{ $user->branch_code ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="ifsc_code">IFSC Code</label>
                            <p> {{ $user->ifsc_code ?? "-" }}</p>
                        </div>

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="micr_code">MICR Code</label>
                            <p> {{ $user->micr_code ?? "-" }}</p>
                        </div>

                    </div>
                    <div class="col-md-4 mb-4">

                        <div class="form-group">
                            <label class="form-label" for="customer_care_executive">Customer Care Executive</label>
                            <p> {{ $user->customer_care_executive ?? "-" }}</p>
                        </div>

                    </div>
                </div>

                <div class="row">
                    <div>
                        @can('ban_customer')
                            @if($user->approval_status != 1)
                                <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm" onclick="show_Approval_model({{ $user->id }});" title="{{ translate('Approval this Customer') }}">
                                    <i class="las la-thumbs-up"></i>
                                </a>
                                @else
                                <a href="#" class="btn btn-soft-success btn-icon btn-circle btn-sm" onclick="show_Approval_model({{ $user->id }});" title="{{ translate('Not Approve this Customer') }}">
                                    <i class="las la-thumbs-down"></i>
                                </a>
                            @endif
                        @endcan
                    </div>
                </div>

            </div>
        </div>
    </div>



    {{--- //------------------------------ approval modal -----------------------// ----}}

    <div class="modal fade" id="approval_model" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel_phone"
    aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content py-3">
                <div class="modal-header">
                    <div class="heading">
                        <h5 class="modal-title" id="exampleModalLabel_phone">Approval Statusr</h5>
                    </div>
                    <div class="purple_btn_close">
                        <button type="button" class="close p-1 px-3" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true" style="font-size: 24px;">&times;</span>
                        </button>
                    </div>
                </div>
                <form id="approval-status-model" action="{{ url(route('customers.approval')) }}"
                    method="post">
                    @csrf

                    <input type="hidden" name="id">

                    <div class="modal-body">
                            <div class="form-group">
                                <label for="recipient-name" class="col-form-label form-label">Note :</label>
                                <textarea type="text" class="form-control" id="note" name="note"></textarea>
                            </div>
                    </div>
                    <div class="modal-footer">
                        <div class="blue_btn">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                        <div class="purple_btn">
                            <button type="submit" class="btn btn-primary">Proceed</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{--- //------------------------------ approval modal -----------------------// ----}}
    


@endsection

@section('script')
    <script type="text/javascript">
    
            // // Global scope
            function show_Approval_model(id) {
                // // Set the value of the hidden input field
                $('#approval_model input[name="id"]').val(id);

                // Show the modal
                $('#approval_model').modal('show');
            }
    
    </script>
@endsection
