@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Add New Local Delivery Partner') }}</h1></div>
        <div class="col-md-6 text-md-right"><a href="{{ route('local-delivery-partners.index') }}" class="btn btn-primary">{{ translate('Back') }}</a></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Local Delivery Partner Information') }}</h5></div>
    <div class="card-body">
        <form action="{{ route('local-delivery-partners.store') }}" method="POST" id="local-delivery-partner-form">
            @csrf
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Name') }} <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <input type="text" name="name" id="local-delivery-partner-name" class="form-control" value="{{ old('name') }}" maxlength="255" required>
                    @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Status') }}</label>
                <div class="col-md-9">
                    <select name="status" class="form-control aiz-selectpicker">
                        <option value="active">{{ translate('Active') }}</option>
                        <option value="inactive">{{ translate('Inactive') }}</option>
                    </select>
                </div>
            </div>
            <div class="text-right"><button type="submit" class="btn btn-primary">{{ translate('Save') }}</button></div>
        </form>
    </div>
</div>
@endsection

@section('script')
<script>
    (function () {
        var existingNames = @json($existingNames ?? []);
        var duplicateMessage = @json(translate('This local delivery partner name already exists.'));
        var nameInput = document.getElementById('local-delivery-partner-name');
        var form = document.getElementById('local-delivery-partner-form');

        function normalizeName(value) {
            return (value || '').trim().toLowerCase();
        }

        function validateName() {
            if (!nameInput) return true;
            var isDuplicate = existingNames.map(normalizeName).indexOf(normalizeName(nameInput.value)) !== -1;
            nameInput.setCustomValidity(isDuplicate ? duplicateMessage : '');
            return !isDuplicate;
        }

        if (nameInput) {
            nameInput.addEventListener('input', validateName);
            nameInput.addEventListener('blur', validateName);
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                if (!validateName()) {
                    event.preventDefault();
                    nameInput.reportValidity();
                }
            });
        }
    })();
</script>
@endsection
