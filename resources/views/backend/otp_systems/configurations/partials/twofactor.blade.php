<div class="form-group row">
    <input type="hidden" name="types[]" value="TWOFACTOR_KEY">
    <div class="col-lg-3">
        <label class="col-from-label">{{ translate('TWOFACTOR_KEY') }}</label>
    </div>
    <div class="col-lg-6">
        <input type="text" class="form-control" name="TWOFACTOR_KEY"
            value="{{ env('TWOFACTOR_KEY') }}" placeholder="TWOFACTOR_KEY" required>
    </div>
</div>
<div class="form-group row">
    <input type="hidden" name="types[]" value="TWOFACTOR_SENDER">
    <div class="col-lg-3">
        <label class="col-from-label">{{ translate('TWOFACTOR_SENDER') }}</label>
    </div>
    <div class="col-lg-6">
        <input type="text" class="form-control" name="TWOFACTOR_SENDER"
            value="{{ env('TWOFACTOR_SENDER') }}" placeholder="TWOFACTOR_SENDER" required>
    </div>
</div>
<div class="form-group mb-0 text-right">
    <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
</div>