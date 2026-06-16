<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Activity History') }}</h5>
    </div>
    <div class="card-body">
        @php $activityHistoryColspan = auth()->user()->can('edit_lead') ? 8 : 7; @endphp
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>{{ translate('Type') }}</th>
                    <th>{{ translate('Sub-status') }}</th>
                    <th>{{ translate('Description') }}</th>
                    <th>{{ translate('Next Follow-up') }}</th>
                    <th>{{ translate('Attachments') }}</th>
                    <th>{{ translate('Created By') }}</th>
                    <th>{{ translate('Created At') }}</th>
                    @can('edit_lead')
                        <th class="text-right">{{ translate('Actions') }}</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @forelse ($lead->activities->sortByDesc('created_at') as $activity)
                    @php $attachmentFiles = $activity->attachment_files; @endphp
                    <tr>
                        <td>{{ translate(ucfirst($activity->activity_type)) }}</td>
                        <td>{{ $activity->activity_sub_status ? translate(ucwords(str_replace('_', ' ', $activity->activity_sub_status))) : '-' }}</td>
                        <td>{{ $activity->description }}</td>
                        <td>{{ $activity->next_followup ? $activity->next_followup->format('d-m-Y h:i A') : '-' }}</td>
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
                        <td>{{ optional($activity->creator)->name ?? '-' }}</td>
                        <td>{{ $activity->created_at ? $activity->created_at->format('d-m-Y h:i A') : '-' }}</td>
                        @can('edit_lead')
                            <td class="text-right">
                                <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete"
                                    data-href="{{ route('leads.activities.destroy', [$lead->id, $activity->id]) }}"
                                    title="{{ translate('Delete') }}">
                                    <i class="las la-trash"></i>
                                </a>
                            </td>
                        @endcan
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
