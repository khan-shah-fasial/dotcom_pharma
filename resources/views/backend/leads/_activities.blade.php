<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Activity History') }}</h5>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>{{ translate('Type') }}</th>
                    <th>{{ translate('Description') }}</th>
                    <th>{{ translate('Next Follow-up') }}</th>
                    <th>{{ translate('Created By') }}</th>
                    <th>{{ translate('Created At') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lead->activities->sortByDesc('created_at') as $activity)
                    <tr>
                        <td>{{ translate(ucfirst($activity->activity_type)) }}</td>
                        <td>{{ $activity->description }}</td>
                        <td>{{ $activity->next_followup ? $activity->next_followup->format('d-m-Y h:i A') : '-' }}</td>
                        <td>{{ optional($activity->creator)->name ?? '-' }}</td>
                        <td>{{ $activity->created_at ? $activity->created_at->format('d-m-Y h:i A') : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">{{ translate('No activities found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
