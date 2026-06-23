@php
    $editingStaff = $staff ?? null;
@endphp

<div class="form-group row">
    <label class="col-sm-3 col-from-label" for="aadhaar_card_no">{{ translate('Aadhaar Card No') }}</label>
    <div class="col-sm-9">
        <input type="text" inputmode="numeric" pattern="[0-9]{12}" maxlength="12" id="aadhaar_card_no"
            name="aadhaar_card_no" value="{{ old('aadhaar_card_no', optional($editingStaff)->aadhaar_card_no) }}"
            class="form-control" placeholder="{{ translate('12 digit Aadhaar number') }}">
        @error('aadhaar_card_no') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-from-label" for="pan_no">{{ translate('PAN No') }}</label>
    <div class="col-sm-9">
        <input type="text" maxlength="10" id="pan_no" name="pan_no"
            value="{{ old('pan_no', optional($editingStaff)->pan_no) }}" class="form-control text-uppercase"
            placeholder="{{ translate('PAN number') }}">
        @error('pan_no') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-from-label" for="bank_account_holder_name">{{ translate('Account Holder Name') }}</label>
    <div class="col-sm-9">
        <input type="text" id="bank_account_holder_name" name="bank_account_holder_name"
            value="{{ old('bank_account_holder_name', optional($editingStaff)->bank_account_holder_name) }}"
            class="form-control" placeholder="{{ translate('Name as per bank account') }}">
        @error('bank_account_holder_name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-from-label" for="bank_name">{{ translate('Bank Name') }}</label>
    <div class="col-sm-9">
        <input type="text" id="bank_name" name="bank_name"
            value="{{ old('bank_name', optional($editingStaff)->bank_name) }}" class="form-control"
            placeholder="{{ translate('Bank name') }}">
        @error('bank_name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-from-label" for="bank_branch_name">{{ translate('Branch Name') }}</label>
    <div class="col-sm-9">
        <input type="text" id="bank_branch_name" name="bank_branch_name"
            value="{{ old('bank_branch_name', optional($editingStaff)->bank_branch_name) }}" class="form-control"
            placeholder="{{ translate('Bank branch') }}">
        @error('bank_branch_name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-from-label" for="bank_account_number">{{ translate('Account Number') }}</label>
    <div class="col-sm-9">
        <input type="text" inputmode="numeric" id="bank_account_number" name="bank_account_number"
            value="{{ old('bank_account_number', optional($editingStaff)->bank_account_number) }}"
            class="form-control" maxlength="34" placeholder="{{ translate('Bank account number') }}">
        @error('bank_account_number') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-from-label" for="bank_account_type">{{ translate('Account Type') }}</label>
    <div class="col-sm-9">
        <select id="bank_account_type" name="bank_account_type" class="form-control aiz-selectpicker">
            <option value="">{{ translate('Select Account Type') }}</option>
            @foreach(['savings' => 'Savings', 'current' => 'Current', 'salary' => 'Salary'] as $value => $label)
                <option value="{{ $value }}" @selected(old('bank_account_type', optional($editingStaff)->bank_account_type) === $value)>
                    {{ translate($label) }}
                </option>
            @endforeach
        </select>
        @error('bank_account_type') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-from-label" for="bank_ifsc_code">{{ translate('IFSC Code') }}</label>
    <div class="col-sm-9">
        <input type="text" id="bank_ifsc_code" name="bank_ifsc_code" maxlength="11"
            value="{{ old('bank_ifsc_code', optional($editingStaff)->bank_ifsc_code) }}"
            class="form-control text-uppercase" placeholder="{{ translate('Example: SBIN0001234') }}">
        @error('bank_ifsc_code') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-from-label" for="attendance_id">{{ translate('Attendance ID') }}</label>
    <div class="col-sm-9">
        <input type="text" id="attendance_id" name="attendance_id"
            value="{{ old('attendance_id', optional($editingStaff)->attendance_id) }}" class="form-control">
        @error('attendance_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-from-label">{{ translate('Attachments') }}</label>
    <div class="col-sm-9">
        <div class="input-group" data-toggle="aizuploader" data-multiple="true">
            <div class="input-group-prepend">
                <div class="input-group-text bg-soft-secondary">{{ translate('Browse') }}</div>
            </div>
            <div class="form-control file-amount">{{ translate('Choose Files') }}</div>
            <input type="hidden" name="attachments" value="{{ old('attachments', optional($editingStaff)->attachments) }}" class="selected-files">
        </div>
        <div class="file-preview box sm"></div>
        @error('attachments') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-from-label" for="emergency_contact_name">{{ translate('Emergency Contact Name') }}</label>
    <div class="col-sm-9">
        <input type="text" id="emergency_contact_name" name="emergency_contact_name"
            value="{{ old('emergency_contact_name', optional($editingStaff)->emergency_contact_name) }}" class="form-control">
        @error('emergency_contact_name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-from-label" for="emergency_contact_number">{{ translate('Emergency Contact Number') }}</label>
    <div class="col-sm-9">
        <input type="text" id="emergency_contact_number" name="emergency_contact_number"
            value="{{ old('emergency_contact_number', optional($editingStaff)->emergency_contact_number) }}" class="form-control">
        @error('emergency_contact_number') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-from-label" for="date_of_birth">{{ translate('Date of Birth') }}</label>
    <div class="col-sm-9">
        <input type="date" id="date_of_birth" name="date_of_birth"
            value="{{ old('date_of_birth', optional(optional($editingStaff)->date_of_birth)->format('Y-m-d')) }}" class="form-control">
        @error('date_of_birth') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-from-label" for="religion">{{ translate('Religion') }}</label>
    <div class="col-sm-9">
        <input type="text" id="religion" name="religion"
            value="{{ old('religion', optional($editingStaff)->religion) }}" class="form-control">
        @error('religion') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-sm-3 col-from-label" for="anniversary_date">{{ translate('Anniversary Date') }}</label>
    <div class="col-sm-9">
        <input type="date" id="anniversary_date" name="anniversary_date"
            value="{{ old('anniversary_date', optional(optional($editingStaff)->anniversary_date)->format('Y-m-d')) }}" class="form-control">
        @error('anniversary_date') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
