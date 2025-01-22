<div class="form-group row">
    <input type="hidden" name="types[]" value="SMSGHUB_API_KEY">
    <div class="col-lg-3">
        <label class="col-from-label">{{ translate('SMSGHUB_API_KEY') }}</label>
    </div>
    <div class="col-lg-6">
        <input type="text" class="form-control" name="SMSGHUB_API_KEY"
            value="{{ env('SMSGHUB_API_KEY') }}" placeholder="SMSGHUB_API_KEY" required>
    </div>
</div>
<div class="form-group row">
    <input type="hidden" name="types[]" value="SMSGHUB_SENDER">
    <div class="col-lg-3">
        <label class="col-from-label">{{ translate('SMSGHUB_SENDER') }}</label>
    </div>
    <div class="col-lg-6">
        <input type="text" class="form-control" name="SMSGHUB_SENDER"
            value="{{ env('SMSGHUB_SENDER') }}" placeholder="SMSGHUB_SENDER" required>
    </div>
</div>
<div class="form-group mb-0 text-right">
    <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
</div>