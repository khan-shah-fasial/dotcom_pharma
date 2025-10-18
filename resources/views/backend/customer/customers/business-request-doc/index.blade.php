@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="align-items-center">
            <h1 class="h3">{{ translate('All Business Customers Documents Requests') }}</h1>
            <p class="text-muted mb-0">
                {{ translate('Manage Tender/BID document requests, filter by status/type/date, and approve or disapprove with a note.') }}
            </p>
        </div>
    </div>

    {{-- Business Request PDFs (PDF-only, saved in business_settings) --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div>
                <h5 class="mb-0 h6">{{ translate('Business Request Documents (PDF Only)') }}</h5>
                <small class="text-muted">{{ translate('Upload multiple PDFs to store under business settings.') }}</small>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.request-doc.pdf.store') }}">
                @csrf

                <div class="form-group row">
                    <label class="col-md-3 col-form-label">{{ translate('Upload PDFs') }}</label>
                    <div class="col-md-9">
                        {{-- AIZ Uploader: document type; restrict UI to PDFs --}}
                        <div class="input-group" data-toggle="aizuploader" data-type="document" data-multiple="true"
                            data-extensions="pdf">
                            <div class="input-group-prepend">
                                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}
                                </div>
                            </div>
                            <div class="form-control file-amount">{{ translate('Choose PDF Files') }}</div>

                            {{-- This hidden will be filled by aizuploader (usually comma-separated file IDs/paths) --}}
                            <input type="hidden" name="docs" class="selected-files"
                                value="{{ $currentPdfValue ?? '' }}">
                        </div>
                        <div class="file-preview box sm"></div>

                        <small class="text-muted d-block mt-2">
                            {{ translate('Only PDF is allowed. You can select multiple files.') }}
                        </small>

                        @error('docs')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="las la-save"></i> {{ translate('Save PDFs') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        {{-- FILTERS --}}
        <form id="sort_docs" action="" method="GET">
            <div class="card-header">
                <div class="row gutters-5 align-items-end">
                    <div class="col-md-12 mb-2">
                        <h5 class="mb-0 h6">{{ translate('Filters') }}</h5>
                    </div>

                    {{-- Search (name/email/typed number) --}}
                    <div class="col-md-4 mb-3">
                        <div class="form-group mb-0">
                            <input type="text" class="form-control" id="q" name="q"
                                value="{{ request('q') }}"
                                placeholder="{{ translate('Search by Name / Email / Tender-BID Number') }}">
                        </div>
                    </div>

                    {{-- Type --}}
                    <div class="col-md-2 mb-3">
                        <select class="form-control aiz-selectpicker" name="type" data-live-search="true"
                            data-selected="{{ request('type') }}">
                            <option value="">{{ translate('All Types') }}</option>
                            <option value="tender" @selected(request('type') === 'tender')>{{ translate('Tender') }}</option>
                            <option value="bid" @selected(request('type') === 'bid')>{{ translate('BID') }}</option>
                        </select>
                    </div>

                    {{-- Status --}}
                    <div class="col-md-2 mb-3">
                        <select class="form-control aiz-selectpicker" name="status"
                            data-selected="{{ request('status') }}">
                            <option value="">{{ translate('All Status') }}</option>
                            <option value="0" @selected(request('status') === '0')>{{ translate('New') }}</option>
                            <option value="1" @selected(request('status') === '1')>{{ translate('Approved') }}</option>
                            <option value="2" @selected(request('status') === '2')>{{ translate('Disapproved') }}</option>
                        </select>
                    </div>

                    {{-- From date --}}
                    <div class="col-md-2 mb-3">
                        <div class="form-group mb-0">
                            <input type="date" class="form-control" name="from" value="{{ request('from') }}"
                                placeholder="{{ translate('From Date') }}">
                        </div>
                    </div>

                    {{-- To date --}}
                    <div class="col-md-2 mb-3">
                        <div class="form-group mb-0">
                            <input type="date" class="form-control" name="to" value="{{ request('to') }}"
                                placeholder="{{ translate('To Date') }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-block">{{ translate('Search') }}</button>
                    </div>

                    <div class="col-md-2">
                        <a class="btn btn-soft-danger" href="{{ url()->current() }}">{{ translate('Reset') }}</a>
                    </div>
                </div>
            </div>

            {{-- TABLE --}}
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table aiz-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ translate('Sr No.') }}</th>
                                <th>{{ translate('User') }}</th>
                                <th>{{ translate('Email') }}</th>
                                <th>{{ translate('Type') }}</th>
                                <th>{{ translate('Tender/BID # / Name') }}</th>
                                <th>{{ translate('Start Date') }}</th>
                                <th>{{ translate('Expiry Date') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th class="text-right">{{ translate('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($docs as $key => $doc)
                                <tr>
                                    <td>{{ $key + 1 + ($docs->currentPage() - 1) * $docs->perPage() }}</td>

                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="font-weight-600">{{ $doc->name }}</span>
                                            <span class="text-muted ml-2">#{{ $doc->user_id }}</span>
                                        </div>
                                    </td>

                                    <td>{{ $doc->email }}</td>

                                    <td>
                                        <span class="badge badge-pill badge-info badge-inline">
                                            {{ strtoupper($doc->type) }}
                                        </span>
                                    </td>

                                    <td class="text-truncate" style="max-width: 220px;">
                                        {{ $doc->type_input ?: '—' }}
                                    </td>

                                    <td>{{ $doc->start_date }}</td>

                                    <td>{{ $doc->expiry_date }}</td>

                                    <td id="doc-status-{{ $doc->id }}">
                                        @if ($doc->status === 0)
                                            <span class="badge badge-warning badge-inline">{{ translate('New Inquiry') }}</span>
                                        @elseif($doc->status === 1)
                                            <span class="badge badge-success badge-inline">{{ translate('Approved') }}</span>
                                        @else
                                            <span class="badge badge-danger badge-inline">{{ translate('Disapproved') }}</span>
                                        @endif
                                    </td>

                                    <td class="text-right" id="doc-actions-{{ $doc->id }}">
                                        @if ($doc->status === 0)
                                            <a href="javascript:void(0);" class="btn btn-sm btn-success js-approve-doc"
                                                data-url="{{ route('admin.request-doc.approve', $doc) }}"
                                                data-id="{{ $doc->id }}" title="{{ translate('Approve') }}">
                                                <i class="las la-check"></i> {{ translate('Approve') }}
                                            </a>

                                            <a href="javascript:void(0);" class="btn btn-sm btn-outline-danger"
                                                data-toggle="modal" data-target="#disapproveModal"
                                                data-id="{{ $doc->id }}"
                                                data-url="{{ route('admin.request-doc.disapprove', $doc) }}"
                                                title="{{ translate('Disapprove') }}">
                                                <i class="las la-times-circle"></i> {{ translate('Disapprove') }}
                                            </a>
                                        @else
                                            @if ($doc->status === 1)
                                                <span class="text-success">Approved</span>
                                            @else
                                                <span class="text-danger">Disapproved</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        {{ translate('No records found for the applied filters.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="aiz-pagination mt-3">
                    {{ $docs->appends(request()->input())->links() }}
                </div>
            </div>
        </form>
    </div>
@endsection

@section('modal')
    {{-- Disapprove Modal --}}
    <div class="modal fade" id="disapproveModal" tabindex="-1" role="dialog" aria-labelledby="disapproveLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form id="disapproveForm" method="POST" action="#">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title h6" id="disapproveLabel">{{ translate('Disapprove Request') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"
                            aria-label="{{ translate('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <p class="text-muted">
                            {{ translate('Add a note for the user. This will be emailed upon disapproval.') }}</p>
                        <div class="form-group">
                            <label for="admin_note" class="mb-1">{{ translate('Admin Note') }}</label>
                            <textarea name="admin_note" id="admin_note" class="form-control" rows="5" required
                                placeholder="{{ translate('Reason / details for disapproval') }}"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-light" type="button"
                            data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button class="btn btn-danger" type="submit">
                            <i class="las la-paper-plane"></i> {{ translate('Disapprove & Notify') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        // Submit the filter form when select changes (optional UX sugar)
        function sort_docs() {
            document.getElementById('sort_docs').submit();
        }

        // Bind dynamic action to Disapprove Modal
        $('#disapproveModal').on('show.bs.modal', function(e) {
            var id = $(e.relatedTarget).data('id');
            var action = "{{ url(route('admin.request-doc.disapprove', ':id')) }}".replace(':id', id);
            $('#disapproveForm').attr('action', action);
            $('#admin_note').val('');
        });
    </script>
    <script>
        (function() {
            // CSRF setup for jQuery AJAX
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Approve (AJAX)
            $(document).on('click', '.js-approve-doc', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var url = $btn.data('url');
                var id = $btn.data('id');

                $btn.prop('disabled', true).addClass('disabled');

                $.post(url, {})
                    .done(function(res) {
                        if (res && res.ok) {
                            // Update status cell and actions cell
                            $('#doc-status-' + id).html(res.status_badge);
                            $('#doc-actions-' + id).html(res.actions_html);

                            if (window.AIZ && AIZ.plugins && AIZ.plugins.notify) {
                                AIZ.plugins.notify('success', res.message || 'Approved');
                            }

                            // Refresh the page or update the UI as needed to reflect changes
                            setTimeout(() => {
                                location.reload();
                            }, 1000);

                        } else {
                            if (window.AIZ && AIZ.plugins && AIZ.plugins.notify) {
                                AIZ.plugins.notify('danger', (res && res.message) ||
                                    'Something went wrong');
                            }
                        }
                    })
                    .fail(function(xhr) {
                        if (window.AIZ && AIZ.plugins && AIZ.plugins.notify) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON
                                .message : 'Server error';
                            AIZ.plugins.notify('danger', msg);
                        }
                    })
                    .always(function() {
                        $btn.prop('disabled', false).removeClass('disabled');
                    });
            });

            // When opening Disapprove modal, set form target and doc id
            $('#disapproveModal').on('show.bs.modal', function(e) {
                var trigger = $(e.relatedTarget);
                var id = trigger.data('id');
                var url = trigger.data('url'); // from button data-url

                $('#disapproveForm').data('doc-id', id);
                $('#disapproveForm').data('post-url', url);
                $('#admin_note').val('');
            });

            // Disapprove (AJAX)
            $('#disapproveForm').on('submit', function(e) {
                e.preventDefault();

                var $form = $(this);
                var id = $form.data('doc-id');
                var url = $form.data('post-url');
                var note = $('#admin_note').val();

                if (!note || note.trim().length === 0) {
                    if (window.AIZ && AIZ.plugins && AIZ.plugins.notify) {
                        AIZ.plugins.notify('warning', "{{ translate('Please add a note') }}");
                    }
                    return;
                }

                var $submitBtn = $form.find('button[type="submit"]');
                $submitBtn.prop('disabled', true).addClass('disabled');

                $.post(url, {
                        admin_note: note
                    })
                    .done(function(res) {
                        if (res && res.ok) {
                            $('#doc-status-' + id).html(res.status_badge);
                            $('#doc-actions-' + id).html(res.actions_html);

                            // Close modal
                            $('#disapproveModal').modal('hide');

                            if (window.AIZ && AIZ.plugins && AIZ.plugins.notify) {
                                AIZ.plugins.notify('success', res.message || 'Disapproved');
                            }

                            // Refresh the page or update the UI as needed to reflect changes
                            setTimeout(() => {
                                location.reload();
                            }, 1000);


                        } else {
                            if (window.AIZ && AIZ.plugins && AIZ.plugins.notify) {
                                AIZ.plugins.notify('danger', (res && res.message) ||
                                    'Something went wrong');
                            }
                        }
                    })
                    .fail(function(xhr) {
                        if (window.AIZ && AIZ.plugins && AIZ.plugins.notify) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON
                                .message : 'Server error';
                            AIZ.plugins.notify('danger', msg);
                        }
                    })
                    .always(function() {
                        $submitBtn.prop('disabled', false).removeClass('disabled');
                    });
            });

        })();
    </script>
@endsection
