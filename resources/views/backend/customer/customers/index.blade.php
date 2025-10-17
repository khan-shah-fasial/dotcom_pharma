@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="align-items-center">
            <h1 class="h3">{{ translate('All Customers') }}</h1>
        </div>
        @can('add_customer')
            {{-- <div class="col text-right">
                <a href="{{ route('customers.create') }}" class="btn btn-circle btn-info">
                    <span>{{ translate('Add New Customer') }}</span>
                </a>
            </div> --}}
        @endcan
    </div>


    <div class="card">
        <form class="" id="sort_customers" action="" method="GET">
            <div class="card-header row gutters-5">


                {{-- <div class="dropdown mb-3 mb-md-0">
                    <button class="btn border dropdown-toggle" type="button" data-toggle="dropdown">
                        {{ translate('Bulk Action') }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item confirm-alert" href="javascript:void(0)"
                            data-target="#bulk-delete-modal">{{ translate('Delete selection') }}</a>
                    </div>
                </div> --}}
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <h5 class="mb-0 h6">{{ translate('Customers') }}</h5>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="form-group mb-0">
                            <input type="text" class="form-control" id="search"
                                name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset
                                placeholder="{{ translate('Type email or name & Phone & Telephone No Enter') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control aiz-selectpicker" name="verification_status" onchange="sort_customers()"
                            data-selected="{{ $verification_status }}">
                            <option value="">{{ translate('Filter by Approval Status') }}</option>
                            <option value="verified">{{ translate('Verified') }}</option>
                            <option value="un_verified">{{ translate('Unverified') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" onclick="sort_customers()">Search</button>
                        <a class="btn btn-danger" href="{{ url(route('customers.index')) }}" class="">Reset</a>                        
                    </div>
                </div>
            </div>

            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            {{-- <!--<th data-breakpoints="lg">#</th>--> --}}
                            <th>
                                Sr No.
                                {{-- <!-- <div class="form-group">
                                    <div class="aiz-checkbox-inline">
                                        <label class="aiz-checkbox">
                                            <input type="checkbox" class="check-all">
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>
                                </div> --> --}}
                            </th>
                            <th>{{ translate('Name') }}</th>
                            <th data-breakpoints="lg">{{ translate('Email Address') }}</th>
                            {{-- <th data-breakpoints="lg">{{ translate('Phone') }}</th> --}}
                            {{-- <!-- <th data-breakpoints="lg">{{ translate('Package') }}</th> --}}
                            {{-- <th data-breakpoints="lg">{{ translate('Wallet Balance') }}</th> --> --}}
                            <th data-breakpoints="lg">{{ translate('Email Verification Status') }}</th>
                            <th data-breakpoints="lg">{{ translate('Phone Verification Status') }}</th>
                            <th class="">{{ translate('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $key => $user)
                            @if ($user != null)
                                <tr>
                                    <td>{{ $key + 1 + ($users->currentPage() - 1) * $users->perPage() }}</td>
                                    {{-- <!-- <td>
                                        <div class="form-group">
                                            <div class="aiz-checkbox-inline">
                                                <label class="aiz-checkbox">
                                                    <input type="checkbox" class="check-one" name="id[]"
                                                        value="{{ $user->id }}">
                                                    <span class="aiz-square-check"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </td> --> --}}
                                    <td>
                                        @if ($user->banned == 1)
                                            <i class="fa fa-ban text-danger" aria-hidden="true"></i>
                                        @endif {{ $user->name }}
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->phone }}</td>
                                    {{-- <!-- <td>
                                        @if ($user->customer_package != null)
                                            {{ $user->customer_package->getTranslation('name') }}
                                        @endif
                                    </td>
                                    <td>{{ single_price($user->balance) }}</td> --> --}}
                                    <td>
                                        @if ($user->email_verified_at != null)
                                            <span
                                                class="badge badge-inline badge-success">{{ translate('Verified') }}</span>
                                        @else
                                            <span
                                                class="badge badge-inline badge-warning">{{ translate('Unverified') }}</span>
                                        @endif
                                    </td>
                                    {{-- <td>
                                        <span class="badge badge-inline badge-success">{{ translate('Verified') }}</span>
                                    </td> --}}
                                    {{-- <td>
                                        @if ($user->approval_status == 1)
                                            <span
                                                class="badge badge-inline badge-success">{{ translate('Verified') }}
                                            </span>
                                        @else
                                            <span
                                                class="badge badge-inline badge-warning">{{ translate('Unverified') }}
                                            </span>
                                        @endif
                                    </td> --}}
                                    <td class="text-right drop-down-text-icon">
                                       
                                <div class="dropdown">
    <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" id="customerActionDropdown{{ $user->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="las la-ellipsis-v"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-right p-2" aria-labelledby="customerActionDropdown{{ $user->id }}">

        <!-- View -->
        <a href="{{ route('customers.view', encrypt($user->id)) }}"
            class="btn"
            title="{{ translate('View Details of this Customer') }}">
            <i class="las la-eye btn-soft-success btn-icon btn-circle btn-sm mr-2"></i> <span class="ms-1">{{ translate('View') }}</span>
        </a>

        {{-- Approval buttons (kept commented exactly as your code) --}}
        {{-- 
        @can('ban_customer')
            @if ($user->approval_status != 1)
                <a href="#" class="btn btn-soft-success btn-icon btn-circle btn-sm"
                    onclick="show_Approval_model({{ $user->id }}, 'approve', '{{ $user->user_subtype ?? 'null' }}');"
                    title="{{ translate('Approval this Customer') }}">
                    <i class="las la-edit"></i> <span class="ms-1">{{ translate('Approve') }}</span>
                </a>
            @else
                <a href="#" class="btn btn-soft-success btn-icon btn-circle btn-sm"
                    onclick="show_Approval_model({{ $user->id }}, 'not_approve', '{{ $user->user_subtype ?? 'null' }}');"
                    title="{{ translate('Not Approve this Customer') }}">
                    <i class="las la-edit"></i> <span class="ms-1">{{ translate('Not Approve') }}</span>
                </a>
            @endif
        @endcan 
        --}}

        <!-- Login as Customer -->
        @if ($user->email_verified_at != null && auth()->user()->can('login_as_customer'))
            <a href="{{ route('customers.login', encrypt($user->id)) }}"
                class="btn "
                title="{{ translate('Log in as this Customer') }}">
                <i class="las la-sign-in-alt btn-soft-primary btn-icon btn-circle btn-sm mr-2"></i> <span class="ms-1">{{ translate('Login') }}</span>
            </a>
        @endif

        <!-- Ban / Unban -->
        @can('ban_customer')
            @if ($user->banned != 1)
                <a href="#" class="btn"
                    onclick="confirm_ban('{{ route('customers.ban', encrypt($user->id)) }}');"
                    title="{{ translate('Ban this Customer') }}">
                    <i class="las la-user-slash btn-soft-danger btn-icon btn-circle btn-sm mr-2"></i> <span class="ms-1">{{ translate('Ban') }}</span>
                </a>
            @else
                <a href="#" class="btn"
                    onclick="confirm_unban('{{ route('customers.ban', encrypt($user->id)) }}');"
                    title="{{ translate('Unban this Customer') }}">
                    <i class="las la-user-check  btn-soft-success btn-icon btn-circle btn-sm mr-2"></i> <span class="ms-1">{{ translate('Unban') }}</span>
                </a>
            @endif
        @endcan

        <!-- Delete -->
        @can('delete_customer')
            <a href="#"
                class="btn"
                data-href="{{ route('customers.destroy', $user->id) }}"
                title="{{ translate('Delete') }}">
                <i class="las la-trash btn-soft-danger btn-icon btn-circle btn-sm confirm-delete mr-2"></i> <span class="ms-1">{{ translate('Delete') }}</span>
            </a>
        @endcan

    </div>
</div>



                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                <div class="aiz-pagination">
                    {{ $users->appends(request()->input())->links() }}
                </div>
            </div>
        </form>
    </div>


    <div class="modal fade" id="confirm-ban">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h6">{{ translate('Confirmation') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ translate('Do you really want to ban this Customer?') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <a type="button" id="confirmation" class="btn btn-primary">{{ translate('Proceed!') }}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirm-unban">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h6">{{ translate('Confirmation') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ translate('Do you really want to unban this Customer?') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <a type="button" id="confirmationunban" class="btn btn-primary">{{ translate('Proceed!') }}</a>
                </div>
            </div>
        </div>
    </div>



    {{-- - //------------------------------ approval modal -----------------------// -- --}}

    <div class="modal fade" id="approval_model" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel_phone"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="heading">
                        <h5 class="modal-title" id="exampleModalLabel_phone">Approval Status User</h5>
                    </div>
                    <div class="purple_btn_close">
                        <button type="button" class="close p-1 px-3" data-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                </div>

                <div class="modal-body">
                <form id="approval-status-model" action="{{ url(route('customers.approval')) }}" method="post">
                    @csrf

                    <input type="hidden" name="id">

                    <!-- Approval Status Dropdown -->
                    <div class="form-group">
                        <label for="approval-status" class="col-form-label form-label">Approval Status:</label>
                        <select class="form-control" id="approval-status" name="approval_status"
                            onchange="toggleNote()">
                            <option value="approve">Approve</option>
                            <option value="not_approve">Not Approve</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="approval-status" class=" col-form-label form-label">User Role:</label>
                        <select class="form-control" id="user-role" name="user_subtype">
                            <option value="">Customer</option>
                            <option value="pts">pts</option>
                            <option value="ptr">ptr</option>
                            <option value="ptd">ptd</option>
                            <option value="gov">gov</option>
                            <option value="expo">expo</option>
                        </select>
                    </div>

                    <div id="note-section" style="display: none;" class="modal-body">
                        <div class="form-group">
                            <label for="recipient-name" class="col-form-label form-label">Note :</label>
                            <textarea type="text" class="form-control" id="note" name="note"></textarea>
                        </div>
                    </div>


                    <div class="modal-footer" style="padding: 0; border-top: 0;">
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
    </div>

    {{-- - //------------------------------ approval modal -----------------------// -- --}}
@endsection

@section('modal')
    <!-- Delete modal -->
    @include('modals.delete_modal')
    <!-- Bulk Delete modal -->
    @include('modals.bulk_delete_modal')
@endsection

@section('script')
    <script type="text/javascript">
        $(document).on("change", ".check-all", function() {
            if (this.checked) {
                // Iterate each checkbox
                $('.check-one:checkbox').each(function() {
                    this.checked = true;
                });
            } else {
                $('.check-one:checkbox').each(function() {
                    this.checked = false;
                });
            }

        });

        function sort_customers(el) {
            $('#sort_customers').submit();
        }

        function confirm_ban(url) {
            if ('{{ env('DEMO_MODE') }}' == 'On') {
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                return;
            }

            $('#confirm-ban').modal('show', {
                backdrop: 'static'
            });
            document.getElementById('confirmation').setAttribute('href', url);
        }

        function confirm_unban(url) {
            if ('{{ env('DEMO_MODE') }}' == 'On') {
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                return;
            }

            $('#confirm-unban').modal('show', {
                backdrop: 'static'
            });
            document.getElementById('confirmationunban').setAttribute('href', url);
        }

        function bulk_delete() {
            var data = new FormData($('#sort_customers')[0]);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{ route('bulk-customer-delete') }}",
                type: 'POST',
                data: data,
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response == 1) {
                        location.reload();
                    }
                }
            });
        }

        function toggleNote() {
            const approvalStatus = document.getElementById('approval-status').value;
            const noteSection = document.getElementById('note-section');

            if (approvalStatus === 'not_approve') {
                noteSection.style.display = 'block'; // Show the note section
            } else {
                noteSection.style.display = 'none'; // Hide the note section
            }
        }


        // // Global scope
        function show_Approval_model(id, status, role) {
            // // Set the value of the hidden input field
            $('#approval_model input[name="id"]').val(id);

            // Set the selected option in the dropdown
            $('#approval-status').val(status);

            if(role !== 'null'){
                // Set the selected option in the dropdown
                $('#user-role').val(role);
            }


            // $('#approval-status option').each(function () {
            //     if ($(this).val() !== status) {
            //         $(this).hide();
            //     } else {
            //         $(this).show();
            //     }
            // });

            // Trigger the toggleNote function to ensure the note section visibility is updated
            toggleNote();

            // Show the modal
            $('#approval_model').modal('show');
        }
    </script>
@endsection
