<form action="{{ route('leads.activities.store', $lead->id) }}" method="POST">
    @csrf
    <div class="form-group">
        <label>{{ translate('Activity Type') }} <span class="text-danger">*</span></label>
        <select name="activity_type" class="form-control aiz-selectpicker" required>
            @foreach ($activityTypes as $type)
                <option value="{{ $type }}" @if(old('activity_type') == $type) selected @endif>{{ translate(ucfirst($type)) }}</option>
            @endforeach
        </select>
        @error('activity_type') <span class="text-danger small">{{ $message }}</span> @enderror
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
    <div class="text-right">
        <button type="submit" class="btn btn-primary">{{ translate('Add Activity') }}</button>
    </div>
</form>
