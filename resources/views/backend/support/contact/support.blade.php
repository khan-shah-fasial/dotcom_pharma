@extends('backend.layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Support Enquiries') }}</h5>
        </div>

        <form action="" method="GET">
            <div class="card-header row gutters-5 align-items-end">
                <div class="col">
                    <h5 class="mb-md-0 h6">{{ translate('All Support Enquiries') }}</h5>
                </div>

                <input type="hidden" name="type" value="support">

                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label for="search" class="form-label">{{ translate('Search') }}</label>
                        <input type="text"
                               class="form-control form-control-sm"
                               id="search"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="{{ translate('Customer or staff name / email / phone') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label for="date_from" class="form-label">{{ translate('From Date') }}</label>
                        <input type="date"
                               class="form-control form-control-sm"
                               id="date_from"
                               name="date_from"
                               value="{{ request('date_from') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label for="date_to" class="form-label">{{ translate('To Date') }}</label>
                        <input type="date"
                               class="form-control form-control-sm"
                               id="date_to"
                               name="date_to"
                               value="{{ request('date_to') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label for="status" class="form-label">{{ translate('Status') }}</label>
                        <select name="status" id="status" class="form-control form-control-sm aiz-selectpicker">
                            <option value="">{{ translate('All') }}</option>
                            <option value="open" @selected(request('status') === 'open')>{{ translate('Open') }}</option>
                            <option value="closed" @selected(request('status') === 'closed')>{{ translate('Closed') }}</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group mt-4 d-flex">
                        <button type="submit" class="btn btn-primary me-2 mx-1">
                            <i class="las la-search"></i> {{ translate('Search') }}
                        </button>
                        <a href="{{ url()->current() }}?type=support" class="btn btn-secondary">
                            <i class="las la-sync"></i> {{ translate('Reset') }}
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <div class="card-body">
            <table class="table aiz-table mb-0" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ translate('Customer') }}</th>
                        <th data-breakpoints="md">{{ translate('Customer Email') }}</th>
                        <th data-breakpoints="md">{{ translate('Customer Phone') }}</th>
                        <th>{{ translate('Staff') }}</th>
                        <th data-breakpoints="md">{{ translate('Channel') }}</th>
                        <th data-breakpoints="md">{{ translate('Preferred Date & Time') }}</th>
                        <th data-breakpoints="md">{{ translate('Status') }}</th>
                        <th data-breakpoints="lg">{{ translate('Created At') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($contacts as $key => $contact)
                        @php
                            $data = $contact->data ? json_decode($contact->data, true) : [];
                            $customer = $data['customer'] ?? [];
                            $staff = $data['staff'] ?? [];
                            $channel = $data['channel'] ?? $contact->content;
                        @endphp
                        <tr>
                            <td>{{ ($key + 1) + ($contacts->currentPage() - 1) * $contacts->perPage() }}</td>
                            <td>
                                <div class="fw-600">{{ $customer['name'] ?? $contact->name }}</div>
                            </td>
                            <td>{{ $customer['email'] ?? $contact->email }}</td>
                            <td>{{ $customer['phone'] ?? $contact->phone }}</td>
                            <td>{{ $staff['name'] ?? '-' }}</td>
                            <td>
                                @if ($channel === 'video')
                                    <span class="badge badge-inline badge-primary">{{ translate('Video Meet') }}</span>
                                @elseif ($channel === 'callback')
                                    <span class="badge badge-inline badge-info">{{ translate('Call Back') }}</span>
                                @else
                                    <span class="badge badge-inline badge-soft-secondary">{{ $channel ?? '-' }}</span>
                                @endif
                            </td>
                            <td>{{ $data['scheduled_at'] ?? '-' }}</td>
                            <td>
                                @php $status = $contact->status ?? 'open'; @endphp
                                @if ($status === 'closed')
                                    <span class="badge badge-inline badge-success">{{ translate('Closed') }}</span>
                                @else
                                    <span class="badge badge-inline badge-warning">{{ translate('Open') }}</span>
                                @endif
                            </td>
                            <td>{{ $contact->created_at?->format('d-m-Y H:i') }}</td>
                            <td class="text-right">
                                @if (($contact->status ?? 'open') === 'open')
                                    <button type="button"
                                            class="btn btn-soft-success btn-icon btn-circle btn-sm js-support-toggle-status"
                                            data-id="{{ $contact->id }}"
                                            data-status="closed"
                                            title="{{ translate('Mark as Closed') }}">
                                        <i class="las la-check"></i>
                                    </button>
                                @else
                                    <button type="button"
                                            class="btn btn-soft-warning btn-icon btn-circle btn-sm js-support-toggle-status"
                                            data-id="{{ $contact->id }}"
                                            data-status="open"
                                            title="{{ translate('Reopen') }}">
                                        <i class="las la-undo"></i>
                                    </button>
                                @endif
                                <a class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                   href="javascript:void(0)" onclick="showQuery({{ $contact->id }})"
                                   title="{{ translate('View') }}">
                                    <i class="las la-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="aiz-pagination">
                {{ $contacts->appends(request()->input())->links() }}
            </div>
        </div>
    </div>
@endsection

@section('modal')
    @include('modals.delete_modal')
    <div class="modal fade" id="query_modal">
        <div class="modal-dialog">
            <div class="modal-content" id="query-modal-content">

            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function showQuery(id) {
            $.post("{{ route('contact.query_modal') }}", {_token: '{{ csrf_token() }}', id: id}, function (data) {
                $('#query_modal #query-modal-content').html(data);
                $('#query_modal').modal('show');
            });
        }

        $(document).on('click', '.js-support-toggle-status', function () {
            var id = $(this).data('id');
            var status = $(this).data('status');

            var message = status === 'closed'
                ? "{{ translate('Do you want to close this support enquiry and send a review email to the customer?') }}"
                : "{{ translate('Do you want to reopen this support enquiry?') }}";

            if (!confirm(message)) {
                return;
            }

            $.post("{{ route('contact.support_update_status') }}", {
                _token: '{{ csrf_token() }}',
                id: id,
                status: status
            }, function (resp) {
                if (resp && resp.success) {
                    location.reload();
                } else {
                    AIZ.plugins.notify('danger', '{{ translate('Unable to update status') }}');
                }
            }).fail(function () {
                AIZ.plugins.notify('danger', '{{ translate('Unable to update status') }}');
            });
        });
    </script>
@endsection

