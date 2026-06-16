<form action="{{ route('leads.activities.store', $lead->id) }}" method="POST">
    @csrf
    <div class="form-group">
        <label>{{ translate('Activity Type') }} <span class="text-danger">*</span></label>
        <select name="activity_type" class="form-control aiz-selectpicker js-lead-activity-type" required>
            @foreach ($activityTypes as $type)
                <option value="{{ $type }}" @if(old('activity_type') == $type) selected @endif>{{ translate(ucfirst($type)) }}</option>
            @endforeach
        </select>
        @error('activity_type') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <div class="form-group">
        <label>{{ translate('Sub-status') }} <span class="text-danger">*</span></label>
        <select name="activity_sub_status" class="form-control aiz-selectpicker js-lead-activity-sub-status" required></select>
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
        <select name="attachments[]" class="form-control" multiple size="6">
            @foreach ($activityUploads as $upload)
                @php
                    $fileName = $upload->file_original_name ?: translate('Unknown');
                    $extension = $upload->extension ? '.'.$upload->extension : '';
                @endphp
                <option value="{{ $upload->id }}" @if(collect(old('attachments', []))->contains($upload->id)) selected @endif>
                    #{{ $upload->id }} - {{ $fileName }}{{ $extension }}
                </option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">{{ translate('Hold Ctrl to select multiple uploaded files.') }}</small>
        @error('attachments') <span class="text-danger small">{{ $message }}</span> @enderror
        @error('attachments.*') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <div class="text-right">
        <button type="submit" class="btn btn-primary">{{ translate('Add Activity') }}</button>
    </div>
</form>
