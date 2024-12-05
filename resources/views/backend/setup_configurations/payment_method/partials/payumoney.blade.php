<form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
    @csrf
    <input type="hidden" name="payment_method" value="voguepay">
    <div class="form-group row">
        <input type="hidden" name="types[]" value="PAYUMONEY_SALT">
        <div class="col-md-4">
            <label class="col-from-label">{{ translate('Salt') }}</label>
        </div>
        <div class="col-md-8">
            <input type="text" class="form-control" name="PAYUMONEY_SALT"
                value="{{ env('PAYUMONEY_SALT') }}"
                placeholder="{{ translate('SALT') }}" required>
        </div>
    </div>
    <div class="form-group row">
        <input type="hidden" name="types[]" value="PAYUMONEY_KEY">
        <div class="col-md-4">
            <label class="col-from-label">{{ translate('KEY') }}</label>
        </div>
        <div class="col-md-8">
            <input type="text" class="form-control" name="PAYUMONEY_KEY"
                value="{{ env('PAYUMONEY_KEY') }}"
                placeholder="{{ translate('KEY') }}" required>
        </div>
    </div>    
    <div class="form-group row">
        <div class="col-md-4">
            <label class="col-from-label">{{ translate('Sandbox Mode') }}</label>
        </div>
        <div class="col-md-8">
            <label class="aiz-switch aiz-switch-success mb-0">
                <input value="1" name="puyumoney_sandbox" type="checkbox"
                    @if (get_setting('puyumoney_sandbox') == 1) checked @endif>
                <span class="slider round"></span>
            </label>
        </div>
    </div>
    <div class="form-group mb-0 text-right">
        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
    </div>
</form>
