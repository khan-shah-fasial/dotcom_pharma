@extends('frontend.layouts.app')

@section('content')
    <div class="container py-5">


        @if (!$latestRequest || $latestRequest->status == 2)

            <div class="text-center mb-5">
                <h2 class="font-weight-bold text-primary">Request a Document</h2>
                <p class="text-muted">Submit a Tender or BID request with your details and notes.</p>
            </div>


            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card border-0 shadow rounded">
                        <div class="card-body p-4">

                            <div class="form-group mb-4">
                                <p>
                                    Lorem Ipsum is simply dummy text of the printing and typesetting industry…
                                </p>
                            </div>

                            <div class="text-center">
                                <button class="btn btn-primary btn-lg px-5 py-2 rounded-pill shadow-sm" data-toggle="modal"
                                    data-target="#requestModal">
                                    Request Document
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        @elseif ($latestRequest->status == 0)
            <div class="text-center mb-5">
                <h2 class="font-weight-bold text-primary">Request a Document</h2>
                {{-- <p class="text-muted">Submit a Tender or BID request with your details and notes.</p> --}}
            </div>


            {{-- Case: status = 0 → request under process --}}
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card border-0 shadow rounded">
                        <div class="card-body p-4">

                            <div class="form-group mb-4">
                                <div class="alert alert-info text-center shadow-sm rounded mt-4">
                                    <i class="bi bi-hourglass-split me-2"></i>
                                    Your request is under process and it will be resolved within 48 hr.
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        @elseif ($latestRequest->status == 1)
            {{-- Case: status = 1 → show PDF icon and download link --}}

            <div class="text-center mb-5">
                <h2 class="font-weight-bold text-primary">Request a Document</h2>
                <p class="text-muted">All Documents are ready for download</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card border-0 shadow rounded">
                        <div class="card-body p-4">
                            <div class="text-center mt-4">
                                @if ($latestRequest && (int) $latestRequest->status === 1 && !empty($downloadUrl))

                                    @if ($stampedUrls && count($stampedUrls) > 1)
                                        @foreach ($stampedUrls as $i => $url)
                                            <a href="{{ static_asset($url) }}" download='{{ 'document-' . ($i + 1) . '.pdf' }}'
                                                class="btn btn-outline-primary btn-lg shadow-sm rounded-pill"
                                                target="_blank">
                                                <i class="bi bi-file-earmark-pdf me-2"></i>
                                                Download Document #{{ $i + 1 }}
                                            </a>
                                        @endforeach
                                    @else
                                        <a href="{{ static_asset($downloadUrl) }}" download='document.pdf'
                                            class="btn btn-outline-primary btn-lg shadow-sm rounded-pill" target="_blank">
                                            <i class="bi bi-file-earmark-pdf me-2"></i>
                                            Download Document
                                        </a>
                                    @endif
                                @else

                                    <button class="btn btn-outline-secondary btn-lg shadow-sm rounded-pill" disabled>
                                        <i class="bi bi-clock-history me-2"></i>
                                        Document not available yet
                                    </button>

                                @endif
                            </div>

                            {{-- @if (!empty($stampedUrls) && count($stampedUrls) > 1)
                                <hr>
                                <div class="mt-2">
                                    <h6 class="text-muted mb-2">More files</h6>
                                    <ul class="list-unstyled">
                                        @foreach ($stampedUrls as $i => $url)
                                            <li class="mb-1">
                                                <a href="{{ $url }}" target="_blank">PDF #{{ $i + 1 }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif --}}

                        </div>
                    </div>
                </div>
            </div>
        @endif


    </div>

    {{-- Bootstrap 4 Modal --}}
    <div class="modal fade" id="requestModal" tabindex="-1" role="dialog" aria-labelledby="requestModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form id="requestForm" class="modal-content border-0 shadow rounded" method="POST"
                action="{{ route('request-doc.store') }}" novalidate>
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-primary" id="requestModalLabel">
                        Submit Your Request
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">
                    {{-- Hidden user fields --}}
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                    <input type="hidden" name="name" value="{{ $user->name }}">
                    <input type="hidden" name="email" value="{{ $user->email }}">

                    <div class="form-row mb-3">
                        <div class="form-group col-md-6">
                            <label class="small text-muted mb-1">User</label>
                            <input type="text" class="form-control" value="{{ $user->name }}" disabled>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="small text-muted mb-1">Email</label>
                            <input type="text" class="form-control" value="{{ $user->email }}" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="type" class="font-weight-semibold">Type</label>
                        <select class="form-control" id="type" name="type" required>
                            <option value="">-- Select --</option>
                            <option value="tender">Tender</option>
                            <option value="bid">BID</option>
                        </select>
                        <small class="text-muted">Select the type of request.</small>
                    </div>

                    <div class="form-group mb-0">
                        <label for="type_input" class="font-weight-semibold">Tender/BID Name or Number</label>
                        <input type="text" id="type_input" name="type_input" class="form-control"
                            placeholder="e.g., Tender #ABC-2025 or BID-1234" required>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        Send Request
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection



@section('custome-script')
    <script>
        $(function() {
            // If CSRF meta tag exists, set it for AJAX
            if ($('meta[name="csrf-token"]').length) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
            }

            function validateForm() {
                const typeVal = $('#type').val();
                const typeInputVal = ($('#type_input').val() || '').trim();

                const errors = [];
                if (!typeVal) errors.push('Please select the request type (Tender or BID).');
                if (!typeInputVal) errors.push('Please enter the Tender/BID name or number.');

                if (errors.length) {
                    AIZ.plugins.notify('danger', errors.join('<br/>'));
                    return false;
                }
                return true;
            }

            $('#requestForm').on('submit', function(e) {
                e.preventDefault();

                // Client-side validation to stop empty form submits
                if (!validateForm()) return;

                const $form = $(this);
                const formData = $form.serialize();

                // Disable button while sending
                const $btn = $form.find('button[type=submit]');
                const originalBtnHtml = $btn.html();
                $btn.prop('disabled', true).text('Sending...');

                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        // Close modal, show success, then reload
                        $('#requestModal').modal('hide');
                        AIZ.plugins.notify('success', 'Request submitted successfully!');
                        $form.trigger('reset');
                        setTimeout(function() {
                            window.location.reload();
                        }, 800);
                    },
                    error: function(xhr) {
                        // Keep modal closed and show errors; do NOT reload
                        $('#requestModal').modal('hide');

                        let msg = 'Something went wrong!';
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const flat = [];
                            Object.values(xhr.responseJSON.errors).forEach(arr => arr.forEach(
                                i => flat.push(i)));
                            if (flat.length) msg = flat.join('<br/>');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        AIZ.plugins.notify('danger', msg);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalBtnHtml);
                    }
                });
            });
        });
    </script>
@endsection
