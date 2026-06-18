@php
    $activityRows = $lead->activities;
    $activitySortOrder = strtolower((string) ($activitySortOrder ?? request('activity_sort_order', 'desc'))) === 'asc' ? 'asc' : 'desc';
    $nextActivitySortOrder = $activitySortOrder === 'asc' ? 'desc' : 'asc';
    $canEditActivity = auth()->user()->can('edit_lead');
    $canDeleteActivity = auth()->user()->hasRole('Super Admin');
    $activityHistoryColspan = $canEditActivity ? 9 : 8;
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Activity History') }}</h5>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>
                        <a href="{{ route('leads.show', $lead->id) . '?' . http_build_query(array_merge(request()->except('page'), ['activity_sort_by' => 'created_at', 'activity_sort_order' => $nextActivitySortOrder])) }}">
                            {{ translate('Created At') }}
                            <i class="las la-sort-amount-{{ $activitySortOrder === 'asc' ? 'up' : 'down' }}"></i>
                        </a>
                    </th>
                    <th>{{ translate('Next Follow-up') }}</th>
                    <th>{{ translate('Activity Type') }}</th>
                    <th>{{ translate('Sub-status') }}</th>
                    <th>{{ translate('Created By') }}</th>
                    <th>{{ translate('Expected Value') }}</th>
                    <th>{{ translate('Attachments') }}</th>
                    <th>{{ translate('Description') }}</th>
                    @if($canEditActivity)
                        <th class="text-right">{{ translate('Actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($activityRows as $activity)
                    @php $attachmentFiles = $activity->attachment_files; @endphp
                    <tr>
                        <td>{{ $activity->created_at ? $activity->created_at->format('d-m-Y h:i A') : '-' }}</td>
                        <td>{{ $activity->next_followup ? $activity->next_followup->format('d-m-Y h:i A') : '-' }}</td>
                        <td>{{ optional($activity->activityType)->title ?? translate(ucfirst($activity->activity_type)) }}</td>
                        <td>{{ optional($activity->subStatus)->title ?? ($activity->activity_sub_status ? translate(ucwords(str_replace('_', ' ', $activity->activity_sub_status))) : '-') }}</td>
                        <td>{{ optional($activity->creator)->name ?? '-' }}</td>
                        <td>{{ $activity->expected_value !== null ? number_format((float) $activity->expected_value, 2) : '-' }}</td>
                        <td>
                            @forelse ($attachmentFiles as $upload)
                                @php
                                    $fileName = $upload->file_original_name ?: translate('View File');
                                    $extension = $upload->extension ? '.'.$upload->extension : '';
                                @endphp
                                <a href="{{ uploaded_asset($upload->id) }}" target="_blank" rel="noopener" class="badge badge-inline badge-light mb-1">
                                    #{{ $upload->id }} - {{ $fileName }}{{ $extension }}
                                </a>
                            @empty
                                -
                            @endforelse
                        </td>
                        <td>{{ $activity->description }}</td>
                        @if($canEditActivity)
                            <td class="text-right">
                                <button type="button" class="btn btn-soft-primary btn-icon btn-circle btn-sm" data-toggle="modal" data-target="#editActivityModal{{ $activity->id }}" title="{{ translate('Edit') }}">
                                    <i class="las la-edit"></i>
                                </button>
                                @if($canDeleteActivity)
                                    <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete"
                                        data-href="{{ route('leads.activities.destroy', [$lead->id, $activity->id]) }}"
                                        title="{{ translate('Delete') }}">
                                        <i class="las la-trash"></i>
                                    </a>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $activityHistoryColspan }}" class="text-center">{{ translate('No activities found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($canEditActivity)
    @foreach ($activityRows as $activity)
        <div class="modal fade" id="editActivityModal{{ $activity->id }}" tabindex="-1" role="dialog" aria-labelledby="editActivityModalLabel{{ $activity->id }}" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="{{ route('leads.activities.update', [$lead->id, $activity->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="editActivityModalLabel{{ $activity->id }}">{{ translate('Edit Activity') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>{{ translate('Activity Type') }} <span class="text-danger">*</span></label>
                                <select name="activity_type_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                                    @foreach ($activityTypes as $type)
                                        <option value="{{ $type->id }}" @selected((string) $activity->activity_type_id === (string) $type->id)>{{ $type->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Sub-status') }} <span class="text-danger">*</span></label>
                                <select name="sub_status_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                                    @foreach ($activitySubStatuses as $subStatus)
                                        <option value="{{ $subStatus->id }}" @selected((string) $activity->sub_status_id === (string) $subStatus->id)>{{ $subStatus->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Expected Value') }}</label>
                                <input type="number" name="expected_value" class="form-control" step="0.01" min="0" value="{{ $activity->expected_value }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Description') }}</label>
                                <textarea name="description" rows="4" class="form-control">{{ $activity->description }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Next Follow-up') }}</label>
                                <input type="datetime-local" name="next_followup" class="form-control" value="{{ $activity->next_followup ? $activity->next_followup->format('Y-m-d\TH:i') : '' }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Add Attachments') }}</label>
                                <input type="file" name="attachments[]" class="form-control" multiple
                                    accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.xml,.zip,.rar,.7z,image/*">
                                <small class="text-muted d-block mt-1">{{ translate('Existing attachments will remain. New files will be added.') }}</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif
