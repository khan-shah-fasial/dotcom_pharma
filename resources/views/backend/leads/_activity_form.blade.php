<form action="{{ route('leads.activities.store', $lead->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="form-group">
        <label>{{ translate('Activity Type') }} <span class="text-danger">*</span></label>
        <select name="activity_type_id" class="form-control aiz-selectpicker" data-live-search="true" required>
            @foreach ($activityTypes as $type)
                <option value="{{ $type->id }}" @selected((string) old('activity_type_id') === (string) $type->id)>{{ $type->title }}</option>
            @endforeach
        </select>
        @error('activity_type_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <div class="form-group">
        <label>{{ translate('Sub-status') }} <span class="text-danger">*</span></label>
        <select name="sub_status_id" class="form-control aiz-selectpicker" data-live-search="true" required>
            @foreach ($activitySubStatuses as $subStatus)
                <option value="{{ $subStatus->id }}" @selected((string) old('sub_status_id') === (string) $subStatus->id)>{{ $subStatus->title }}</option>
            @endforeach
        </select>
        @error('sub_status_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <div class="form-group">
        <label>{{ translate('Expected Value') }}</label>
        <input type="number" name="expected_value" class="form-control" step="0.01" min="0" value="{{ old('expected_value') }}">
        @error('expected_value') <span class="text-danger small">{{ $message }}</span> @enderror
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
