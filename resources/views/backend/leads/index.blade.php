@extends('backend.layouts.app')

@section('content')
<style>
    .lead-description-clamp {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        max-width: 280px;
        overflow: hidden;
        white-space: normal;
        cursor: help;
    }
    .lead-description-popover .popover-body {
        max-width: 420px;
        white-space: pre-line;
    }
</style>

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Leads') }}</h1></div>
        <div class="col-md-6 text-md-right">
            @can('edit_lead')
                <button type="button" class="btn btn-circle btn-soft-primary mr-2" data-toggle="modal" data-target="#transferStaffLeadsModal">
                    {{ translate('Transfer Staff Leads') }}
                </button>
            @endcan
            @can('add_lead')
                <a href="{{ route('leads.create') }}" class="btn btn-circle btn-info">{{ translate('Add New Lead') }}</a>
            @endcan
        </div>
    </div>
</div>

<div class="card">
    <form id="sort_leads" method="GET">
        @php
            $filtersApplied = collect($filters)->filter(function ($value) {
                return $value !== null && $value !== '';
            })->isNotEmpty();
        @endphp
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-2">
                <h5 class="mb-0 h6">{{ translate('Lead List') }}</h5>
                @if ($filtersApplied)
                    <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
                @endif
            </div>
            <div class="d-flex flex-wrap align-items-center">
                <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#leadFilterModal">
                    {{ translate('Open Filters') }}
                </button>
                <a href="{{ route('leads.index') }}" class="btn btn-danger mb-2">{{ translate('Reset') }}</a>
            </div>
        </div>

        <div class="modal fade" id="leadFilterModal" tabindex="-1" role="dialog" aria-labelledby="leadFilterModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="leadFilterModalLabel">{{ translate('Filter Leads') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row gutters-5">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="lead_search">{{ translate('Search') }}</label>
                                <input type="text" id="lead_search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ translate('Lead no / name / company / email / phone / WhatsApp') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="lead_assigned_to">{{ translate('Assigned To') }}</label>
                                <select id="lead_assigned_to" name="assigned_to" class="form-control aiz-selectpicker" data-live-search="true">
                                    <option value="">{{ translate('All Assignees') }}</option>
                                    @foreach ($assignees as $user)
                                        <option value="{{ $user->id }}" @if(($filters['assigned_to'] ?? '') == $user->id) selected @endif>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="lead_source_id">{{ translate('Source') }}</label>
                                <select id="lead_source_id" name="source_id" class="form-control aiz-selectpicker" data-live-search="true">
                                    <option value="">{{ translate('All Sources') }}</option>
                                    @foreach ($sources as $source)
                                        <option value="{{ $source->id }}" @if(($filters['source_id'] ?? '') == $source->id) selected @endif>{{ $source->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="lead_status_id">{{ translate('Status') }}</label>
                                <select id="lead_status_id" name="status_id" class="form-control aiz-selectpicker" data-live-search="true">
                                    <option value="">{{ translate('All Statuses') }}</option>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->id }}" @if(($filters['status_id'] ?? '') == $status->id) selected @endif>{{ $status->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="lead_activity_type">{{ translate('Activity Type') }}</label>
                                <select id="lead_activity_type" name="activity_type_id" class="form-control aiz-selectpicker" data-live-search="true">
                                    <option value="">{{ translate('All Activity Types') }}</option>
                                    @foreach ($activityTypes as $type)
                                        <option value="{{ $type->id }}" @selected((string) ($filters['activity_type_id'] ?? '') === (string) $type->id)>{{ $type->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">{{ translate('Expected Value') }}</h6>
                                    <div class="row gutters-5">
                                        <div class="col-md-6 mb-0">
                                            <label class="form-label" for="expected_value_min">{{ translate('Minimum Value') }}</label>
                                            <input type="number" id="expected_value_min" step="0.01" min="0" name="expected_value_min" class="form-control" value="{{ $filters['expected_value_min'] ?? '' }}" placeholder="{{ translate('Min Value') }}">
                                        </div>
                                        <div class="col-md-6 mb-0">
                                            <label class="form-label" for="expected_value_max">{{ translate('Maximum Value') }}</label>
                                            <input type="number" id="expected_value_max" step="0.01" min="0" name="expected_value_max" class="form-control" value="{{ $filters['expected_value_max'] ?? '' }}" placeholder="{{ translate('Max Value') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">{{ translate('Created Date') }}</h6>
                                    <div class="row gutters-5">
                                        <div class="col-md-6 mb-0">
                                            <label class="form-label" for="created_from">{{ translate('Created From') }}</label>
                                            <input type="date" id="created_from" name="created_from" class="form-control" value="{{ $filters['created_from'] ?? '' }}">
                                        </div>
                                        <div class="col-md-6 mb-0">
                                            <label class="form-label" for="created_to">{{ translate('Created To') }}</label>
                                            <input type="date" id="created_to" name="created_to" class="form-control" value="{{ $filters['created_to'] ?? '' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">{{ translate('Next Follow-up Date') }}</h6>
                                    <div class="row gutters-5">
                                        <div class="col-md-6 mb-0">
                                            <label class="form-label" for="next_followup_from">{{ translate('Follow-up From') }}</label>
                                            <input type="date" id="next_followup_from" name="next_followup_from" class="form-control" value="{{ $filters['next_followup_from'] ?? '' }}">
                                        </div>
                                        <div class="col-md-6 mb-0">
                                            <label class="form-label" for="next_followup_to">{{ translate('Follow-up To') }}</label>
                                            <input type="date" id="next_followup_to" name="next_followup_to" class="form-control" value="{{ $filters['next_followup_to'] ?? '' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Close') }}</button>
                        <button type="button" class="btn btn-primary btn-apply-lead-filters">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </div>
        </div>

    <div class="card-body">
        @php
            $lastActivitySortActive = request('sort_by') === 'last_activity_created_at';
            $lastActivitySortOrder = strtolower((string) request('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
            $lastActivityNextSortOrder = $lastActivitySortActive && $lastActivitySortOrder === 'asc' ? 'desc' : 'asc';
        @endphp
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Lead No') }}</th>
                    <th>{{ translate('Name') }}</th>
                    <th>{{ translate('Company') }}</th>
                    <th>{{ translate('Customer Type') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th>{{ translate('Source') }}</th>
                    <th>{{ translate('Created By') }}</th>
                    <th>{{ translate('Value') }}</th>
                    <th>{{ translate('Next Follow-up') }}</th>
                    <th>
                        <a href="{{ route('leads.index', array_merge(request()->except('page'), ['sort_by' => 'last_activity_created_at', 'sort_order' => $lastActivityNextSortOrder])) }}">
                            {{ translate('Last Activity') }}
                            @if ($lastActivitySortActive)
                                <i class="las la-sort-amount-{{ $lastActivitySortOrder === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </a>
                    </th>
                    <th>{{ translate('Description') }}</th>
                    <th class="text-right">{{ translate('Options') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leads as $key => $lead)
                    @php
                        $lastActivity = $lead->latestActivity;
                        $activityDescription = $lastActivity ? trim((string) $lastActivity->description) : '';
                        $phoneHref = $lead->phone ? preg_replace('/\s+/', '', $lead->phone) : null;
                        $whatsappHref = $lead->whatsapp_number ? preg_replace('/\D+/', '', $lead->whatsapp_number) : null;
                    @endphp
                    <tr>
                        <td>{{ $leads->firstItem() + $key }}</td>
                        <td class="fw-700">{{ $lead->lead_no }}</td>
                        <td>
                            <div>{{ $lead->name }}</div>
                            <hr>
                            <small class="text-muted">
                                @if ($lead->email)
                                    <a href="mailto:{{ $lead->email }}" class="text-muted">{{ $lead->email }}</a>
                                @else
                                    -
                                @endif
                            </small>
                            @if ($lead->phone)
                                <small class="d-block text-muted">
                                    {{ translate('Phone') }}:
                                    <a href="tel:{{ $phoneHref }}" class="text-muted">{{ $lead->phone }}</a>
                                </small>
                            @endif
                            @if ($lead->whatsapp_number && $whatsappHref)
                                <small class="d-block text-muted">
                                    {{ translate('WhatsApp') }}:
                                    <a href="https://wa.me/{{ $whatsappHref }}" class="text-muted" target="_blank" rel="noopener">{{ $lead->whatsapp_number }}</a>
                                </small>
                            @endif
                        </td>
                        <td>
                            <div>{{ $lead->company_name ?? '-' }}</div>
                            @if($lead->country || $lead->state || $lead->city)
                            <hr>
                                <ul class="list-unstyled mb-0 mt-1 text-muted small">
                                    @if($lead->country)
                                        <li>{{ $lead->country->name }}</li>
                                    @endif
                                    @if($lead->state)
                                        <li>{{ $lead->state->name }}</li>
                                    @endif
                                    @if($lead->city)
                                        <li>{{ $lead->city->name }}</li>
                                    @endif
                                </ul>
                            @endif
                        </td>
                        <td>{{ $lead->customer_type ?? '-' }}</td>
                        <td>
                            @if($lead->status)
                                <span class="badge badge-inline text-white" style="background-color: {{ $lead->status->color ?? '#6c757d' }}">{{ $lead->status->name }}</span>
                            @else
                                -
                            @endif
                            <small class="d-block text-muted mt-1">{{ translate('Current Status') }}: {{ $lead->customer_current_status ?? '-' }}</small>
                        </td>
                        <td>{{ optional($lead->source)->name ?? '-' }}</td>
                        <td>
                            <div>{{ optional($lead->creator)->name ?? '-' }}</div>
                            @if(optional($lead->assignedUser)->name)
                            <hr>
                            <small class="d-block text-muted">{{ translate('Assign to') }}: {{ optional($lead->assignedUser)->name ?? '-' }}</small>
                            @endif
                        </td>
                        <td>{{ $lastActivity && $lastActivity->expected_value !== null ? number_format((float) $lastActivity->expected_value, 2) : '-' }}</td>
                        <td>{{ $lastActivity && $lastActivity->next_followup ? $lastActivity->next_followup->format('d-m-Y h:i A') : '-' }}</td>
                        <td>
                            @if($lastActivity)
                                {{ optional($lastActivity->activityType)->title ?? translate(ucfirst($lastActivity->activity_type)) }}
                                @if($lastActivity->subStatus || $lastActivity->activity_sub_status)
                                    <small class="d-block text-muted">
                                        {{ optional($lastActivity->subStatus)->title ?? translate(ucwords(str_replace('_', ' ', $lastActivity->activity_sub_status))) }}
                                    </small>
                                @endif
                                <small class="d-block text-muted">
                                    {{ translate('Created') }}: {{ $lastActivity->created_at ? $lastActivity->created_at->format('d-m-Y h:i A') : '-' }}
                                </small>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($activityDescription !== '')
                                <div class="lead-description-clamp js-lead-description-popover"
                                    tabindex="0"
                                    data-toggle="popover"
                                    data-trigger="hover focus"
                                    data-placement="top"
                                    title="{{ translate('Description') }}"
                                    data-content="{{ e($activityDescription) }}">
                                    {!! nl2br(e($activityDescription)) !!}
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('leads.show', $lead->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm" title="{{ translate('View') }}">
                                <i class="las la-eye"></i>
                            </a>
                            @can('edit_lead')
                                <button type="button"
                                    class="btn btn-soft-success btn-icon btn-circle btn-sm js-add-lead-activity"
                                    data-action="{{ route('leads.activities.store', $lead->id) }}"
                                    data-lead="{{ $lead->lead_no ?: $lead->name }}"
                                    title="{{ translate('Add Activity') }}">
                                    <i class="las la-plus"></i>
                                </button>
                                <a href="{{ route('leads.edit', $lead->id) }}" class="btn btn-soft-info btn-icon btn-circle btn-sm" title="{{ translate('Edit') }}">
                                    <i class="las la-edit"></i>
                                </a>
                            @endcan
                            @can('delete_lead')
                                <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('leads.destroy', $lead->id) }}" title="{{ translate('Delete') }}">
                                    <i class="las la-trash"></i>
                                </a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center">{{ translate('No leads found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $leads->appends(request()->input())->links() }}
        </div>
    </div>
    </form>
</div>

@can('edit_lead')
    <div class="modal fade" id="transferStaffLeadsModal" tabindex="-1" role="dialog" aria-labelledby="transferStaffLeadsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="transferStaffLeadsForm" action="{{ route('leads.transfer_staff') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_transfer_staff_form" value="1">
                    <div class="modal-header">
                        <h5 class="modal-title" id="transferStaffLeadsModalLabel">{{ translate('Transfer Staff Leads') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transfer_from_staff">From<span class="text-danger">*</span></label>
                                    <select id="transfer_from_staff" name="from_staff" class="form-control aiz-selectpicker"
                                        data-live-search="true" data-container="#transferStaffLeadsModal" required>
                                        <option value="">{{ translate('Select Staff') }}</option>
                                        @foreach ($transferFromStaff as $staff)
                                            <option value="{{ $staff->id }}" @selected((string) old('from_staff') === (string) $staff->id)>
                                                {{ $staff->name }}{{ $staff->status ? '' : ' (' . translate('Inactive') . ')' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('from_staff') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transfer_to_staff">{{ translate('To') }} <span class="text-danger">*</span></label>
                                    <select id="transfer_to_staff" name="to_staff" class="form-control aiz-selectpicker"
                                        data-live-search="true" data-container="#transferStaffLeadsModal" required>
                                        <option value="">{{ translate('Select Active Staff') }}</option>
                                        @foreach ($transferToStaff as $staff)
                                            <option value="{{ $staff->id }}" @selected((string) old('to_staff') === (string) $staff->id)>{{ $staff->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('to_staff') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <input id="transfer_countries" type="hidden">

                        <div class="form-group mb-0">
                            <label>{{ translate('States and Lead Count') }} <span class="text-danger">*</span></label>
                            <div id="transfer_state_loading" class="text-muted d-none">
                                <i class="las la-spinner la-spin"></i> {{ translate('Loading leads') }}...
                            </div>
                            <div id="transfer_state_message" class="alert alert-soft-info mb-0">
                                {{ translate('Select From staff to load states') }}
                            </div>
                            <div id="transfer_state_options" class="border rounded p-3 d-none"></div>
                            @error('state_ids') <div class="text-danger small">{{ $message }}</div> @enderror
                            @error('state_ids.*') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Close') }}</button>
                        <button id="transfer_staff_submit" type="submit" class="btn btn-primary" disabled>
                            {{ translate('Transfer Leads') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="quickLeadActivityModal" tabindex="-1" role="dialog" aria-labelledby="quickLeadActivityModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="quickLeadActivityForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="quickLeadActivityModalLabel">{{ translate('Add Activity') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-soft-info" id="quickLeadActivityLead"></div>
                        <div class="form-group">
                            <label for="quick_activity_type">{{ translate('Activity Type') }} <span class="text-danger">*</span></label>
                            <select id="quick_activity_type" name="activity_type_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                                @foreach ($activityTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quick_activity_sub_status">{{ translate('Sub-status') }} <span class="text-danger">*</span></label>
                            <select id="quick_activity_sub_status" name="sub_status_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                                @foreach ($activitySubStatuses as $subStatus)
                                    <option value="{{ $subStatus->id }}">{{ $subStatus->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quick_activity_expected_value">{{ translate('Expected Value') }}</label>
                            <input id="quick_activity_expected_value" type="number" step="0.01" min="0" name="expected_value" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="quick_activity_description">{{ translate('Description') }}</label>
                            <textarea id="quick_activity_description" name="description" rows="4" class="form-control"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="quick_activity_next_followup">{{ translate('Next Follow-up') }}</label>
                            <input id="quick_activity_next_followup" type="text" name="next_followup"
                                class="form-control aiz-date-time-picker" data-past-disable="true"
                                placeholder="{{ translate('Select date and time') }}" autocomplete="off" required>
                        </div>
                        <div class="form-group">
                            <label for="quick_activity_attachments">{{ translate('Attachments') }}</label>
                            <input id="quick_activity_attachments" type="file" name="attachments[]" class="form-control" multiple
                                accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.xml,.zip,.rar,.7z,image/*">
                            <small class="text-muted d-block mt-1">{{ translate('You can upload multiple images or documents.') }}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ translate('Add Activity') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
    <script>
        $('.btn-apply-lead-filters').on('click', function () {
            $('#leadFilterModal').modal('hide');
            $('#sort_leads').submit();
        });

        $(document).on('click', '.js-add-lead-activity', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var $button = $(this);
            $('#quickLeadActivityForm').attr('action', $button.data('action'));
            $('#quickLeadActivityLead').text($button.data('lead'));
            $('#quick_activity_description').val('');
            $('#quick_activity_next_followup').val('');
            $('#quick_activity_attachments').val('');
            $('#quick_activity_expected_value').val('');
            $('#quick_activity_type').prop('selectedIndex', 0);
            $('#quick_activity_sub_status').prop('selectedIndex', 0);
            $('#quick_activity_type, #quick_activity_sub_status').selectpicker('refresh');

            $('#quickLeadActivityModal').modal('show');
        });

        $('.js-lead-description-popover').popover({
            container: 'body',
            template: '<div class="popover lead-description-popover" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });

        (function () {
            var transferOptionsUrl = @json(route('leads.transfer_options'));
            var oldStateIds = @json(array_map('strval', (array) old('state_ids', [])));
            var $from = $('#transfer_from_staff');
            var $to = $('#transfer_to_staff');
            var $countries = $('#transfer_countries');
            var $stateOptions = $('#transfer_state_options');
            var $stateMessage = $('#transfer_state_message');
            var $loading = $('#transfer_state_loading');
            var $submit = $('#transfer_staff_submit');

            function refreshSubmitState() {
                var hasState = $stateOptions.find('input[name="state_ids[]"]:checked').length > 0;
                $submit.prop('disabled', !hasState || !$from.val() || !$to.val() || $from.val() === $to.val());
            }

            function excludeSourceFromDestination() {
                var fromId = String($from.val() || '');
                $to.find('option').each(function () {
                    var isSource = fromId !== '' && String($(this).val()) === fromId;
                    $(this).prop('disabled', isSource);
                    if (isSource && $(this).is(':selected')) {
                        $to.val('');
                    }
                });
                $to.selectpicker('refresh');
                refreshSubmitState();
            }

            function renderStates(states) {
                $stateOptions.empty();

                if (!states.length) {
                    $stateOptions.addClass('d-none');
                    $stateMessage.removeClass('d-none').text(@json(translate('No leads with a state were found for this staff member')));
                    refreshSubmitState();
                    return;
                }

                $.each(states, function (index, state) {
                    var stateId = String(state.id);
                    var checkboxId = 'transfer_state_' + stateId;
                    var label = state.country_name ? state.country_name + ' - ' + state.name : state.name;
                    var $wrapper = $('<div>', { class: 'custom-control custom-checkbox mb-2' });
                    var $checkbox = $('<input>', {
                        type: 'checkbox',
                        class: 'custom-control-input',
                        id: checkboxId,
                        name: 'state_ids[]',
                        value: stateId
                    }).prop('checked', oldStateIds.indexOf(stateId) !== -1);
                    var $label = $('<label>', {
                        class: 'custom-control-label',
                        for: checkboxId
                    }).text(label + ' (' + state.lead_count + ' ' + @json(translate('leads')) + ')');

                    $wrapper.append($checkbox, $label);
                    $stateOptions.append($wrapper);
                });

                oldStateIds = [];
                $stateMessage.addClass('d-none');
                $stateOptions.removeClass('d-none');
                refreshSubmitState();
            }

            function loadTransferOptions() {
                var fromId = $from.val();

                excludeSourceFromDestination();
                $countries.val('');
                $stateOptions.empty().addClass('d-none');
                $stateMessage.addClass('d-none');

                if (!fromId) {
                    $stateMessage.removeClass('d-none').text(@json(translate('Select From staff to load states')));
                    refreshSubmitState();
                    return;
                }

                $loading.removeClass('d-none');
                $submit.prop('disabled', true);

                $.ajax({
                    url: transferOptionsUrl,
                    type: 'GET',
                    data: { from_staff: fromId },
                    success: function (response) {
                        $countries.val((response.countries || []).join(', '));
                        renderStates(response.states || []);
                    },
                    error: function () {
                        $stateMessage.removeClass('d-none').text(@json(translate('Unable to load lead states. Please try again.')));
                    },
                    complete: function () {
                        $loading.addClass('d-none');
                        refreshSubmitState();
                    }
                });
            }

            $from.on('change', loadTransferOptions);
            $to.on('change', refreshSubmitState);
            $stateOptions.on('change', 'input[name="state_ids[]"]', refreshSubmitState);

            $('#transferStaffLeadsModal').on('shown.bs.modal', function () {
                $from.add($to).selectpicker('refresh');

                if ($from.val() && $stateOptions.children().length === 0) {
                    loadTransferOptions();
                }
            });

            $('#transferStaffLeadsModal').on('hide.bs.modal', function () {
                $from.add($to).selectpicker('close');
            });

            @if ($errors->any() && old('_transfer_staff_form'))
                $('#transferStaffLeadsModal').modal('show');
            @endif
        })();
    </script>
@endsection
