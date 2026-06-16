<form action="{{ route('leads.activities.store', $lead->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="form-group">
        <label>{{ translate('Activity Type') }} <span class="text-danger">*</span></label>
        <select name="activity_type" class="form-control js-lead-activity-type" required>
            @foreach ($activityTypes as $type)
                <option value="{{ $type }}" @if(old('activity_type') == $type) selected @endif>{{ translate(ucfirst($type)) }}</option>
            @endforeach
        </select>
        @error('activity_type') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <div class="form-group">
        <label>{{ translate('Sub-status') }} <span class="text-danger">*</span></label>
        @php $selectedActivityType = old('activity_type', $activityTypes[0] ?? 'call'); @endphp
        <select name="activity_sub_status" class="form-control js-lead-activity-sub-status" required>
            @foreach (($activitySubStatuses[$selectedActivityType] ?? []) as $subStatus)
                <option value="{{ $subStatus }}" @if(old('activity_sub_status') == $subStatus) selected @endif>
                    {{ translate(ucwords(str_replace('_', ' ', $subStatus))) }}
                </option>
            @endforeach
        </select>
        @error('activity_sub_status') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <div class="form-group">
        <label>{{ translate('Description') }}</label>
        <textarea name="description" rows="4" class="form-control">{{ old('description') }}</textarea>
        @error('description') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <div class="form-group">
        <label>{{ translate('Next Follow-up') }}</label>
        <input type="datetime-local" name="next_followup" class="form-control" value="{{ old('next_followup') }}">
        @error('next_followup') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <div class="form-group">
        <label>{{ translate('Attachments') }}</label>
        <input type="file" name="attachments[]" class="form-control" multiple
            accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.xml,.zip,.rar,.7z,image/*">
        <small class="text-muted d-block mt-1">{{ translate('You can upload multiple images or documents.') }}</small>
        @error('attachments') <span class="text-danger small">{{ $message }}</span> @enderror
        @error('attachments.*') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <div class="text-right">
        <button type="submit" class="btn btn-primary">{{ translate('Add Activity') }}</button>
    </div>
</form>
