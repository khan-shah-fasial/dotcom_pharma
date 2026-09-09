@php
    $contact = $contact ?? null;
    $photoValue = old('photo', $contact->photo ?? '');
    $oldSocialKeys = old('social_media_keys');
    $socialMediaRows = collect();

    if (is_array($oldSocialKeys)) {
        $oldSocialValues = old('social_media_values', []);
        $socialMediaRows = collect($oldSocialKeys)->map(function ($key, $index) use ($oldSocialValues) {
            return [
                'key' => $key,
                'value' => $oldSocialValues[$index] ?? '',
            ];
        });
    } else {
        $socialMediaRows = collect($contact->social_media_ids ?? []);
    }

    if ($socialMediaRows->isEmpty()) {
        $socialMediaRows = collect([['key' => '', 'value' => '']]);
    }

    $tagsValue = old('tags');
    if ($tagsValue === null) {
        $tagsValue = collect($contact->tags ?? [])->implode(', ');
    }
@endphp

<style>
    .contact-social-media-row .btn {
        height: 38px;
    }
</style>

@csrf
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Full Name') }} <span class="text-danger">*</span></label>
    <div class="col-md-9">
        <input type="text" name="name" class="form-control" value="{{ old('name', $contact->name ?? '') }}" required>
        @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Photo') }}</label>
    <div class="col-md-9">
        <div class="input-group" data-toggle="aizuploader" data-type="image">
            <div class="input-group-prepend">
                <div class="input-group-text bg-soft-secondary">{{ translate('Browse') }}</div>
            </div>
            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
            <input type="hidden" name="photo" class="selected-files" value="{{ $photoValue }}">
        </div>
        <div class="file-preview box sm"></div>
        @error('photo') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Company Name') }}</label>
    <div class="col-md-9">
        <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $contact->company_name ?? '') }}">
        @error('company_name') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Designation') }}</label>
    <div class="col-md-9">
        <input type="text" name="designation" class="form-control" value="{{ old('designation', $contact->designation ?? '') }}">
        @error('designation') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Group') }}</label>
    <div class="col-md-4">
        <select name="group_id" id="contact_group_id" class="form-control aiz-selectpicker js-contact-cascade" data-live-search="true" data-child="#contact_category_id" data-child-kind="category">
            <option value="">{{ translate('Select Group') }}</option>
            @foreach ($groups as $group)
                <option value="{{ $group->id }}" @selected((string) old('group_id', $contact->group_id ?? '') === (string) $group->id)>{{ $group->name }}</option>
            @endforeach
        </select>
        @error('group_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-1 col-form-label">{{ translate('Category') }}</label>
    <div class="col-md-4">
        <select name="category_id" id="contact_category_id" class="form-control aiz-selectpicker js-contact-cascade" data-live-search="true" data-child="#contact_subcategory_id" data-child-kind="subcategory">
            <option value="">{{ translate('Select Category') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $contact->category_id ?? '') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Subcategory') }}</label>
    <div class="col-md-4">
        <select name="subcategory_id" id="contact_subcategory_id" class="form-control aiz-selectpicker js-contact-cascade" data-live-search="true" data-child="#contact_type_id" data-child-kind="type">
            <option value="">{{ translate('Select Subcategory') }}</option>
            @foreach ($subcategories as $subcategory)
                <option value="{{ $subcategory->id }}" @selected((string) old('subcategory_id', $contact->subcategory_id ?? '') === (string) $subcategory->id)>{{ $subcategory->name }}</option>
            @endforeach
        </select>
        @error('subcategory_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-1 col-form-label">{{ translate('Type') }}</label>
    <div class="col-md-4">
        <select name="type_id" id="contact_type_id" class="form-control aiz-selectpicker js-contact-cascade" data-live-search="true" data-child="#contact_subject_id" data-child-kind="subject">
            <option value="">{{ translate('Select Type') }}</option>
            @foreach ($types as $type)
                <option value="{{ $type->id }}" @selected((string) old('type_id', $contact->type_id ?? '') === (string) $type->id)>{{ $type->name }}</option>
            @endforeach
        </select>
        @error('type_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Subject') }}</label>
    <div class="col-md-4">
        <select name="subject_id" id="contact_subject_id" class="form-control aiz-selectpicker" data-live-search="true">
            <option value="">{{ translate('Select Subject') }}</option>
            @foreach ($subjects as $subject)
                <option value="{{ $subject->id }}" @selected((string) old('subject_id', $contact->subject_id ?? '') === (string) $subject->id)>{{ $subject->name }}</option>
            @endforeach
        </select>
        @error('subject_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-1 col-form-label">{{ translate('Industry') }}</label>
    <div class="col-md-4">
        <select name="industry_id" id="contact_industry_id" class="form-control aiz-selectpicker js-contact-cascade" data-live-search="true" data-child="#contact_work_profile_id" data-child-kind="work_profile">
            <option value="">{{ translate('Select Industry') }}</option>
            @foreach ($industries as $industry)
                <option value="{{ $industry->id }}" @selected((string) old('industry_id', $contact->industry_id ?? '') === (string) $industry->id)>{{ $industry->name }}</option>
            @endforeach
        </select>
        @error('industry_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Work Profile') }}</label>
    <div class="col-md-4">
        <select name="work_profile_id" id="contact_work_profile_id" class="form-control aiz-selectpicker" data-live-search="true">
            <option value="">{{ translate('Select Work Profile') }}</option>
            @foreach ($workProfiles as $workProfile)
                <option value="{{ $workProfile->id }}" @selected((string) old('work_profile_id', $contact->work_profile_id ?? '') === (string) $workProfile->id)>{{ $workProfile->name }}</option>
            @endforeach
        </select>
        @error('work_profile_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-1 col-form-label">{{ translate('Department') }}</label>
    <div class="col-md-4">
        <select name="department_id" id="contact_department_id" class="form-control aiz-selectpicker" data-live-search="true">
            <option value="">{{ translate('Select Department') }}</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected((string) old('department_id', $contact->department_id ?? '') === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
        @error('department_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Purpose') }}</label>
    <div class="col-md-9">
        <select name="purpose_id" id="contact_purpose_id" class="form-control aiz-selectpicker" data-live-search="true">
            <option value="">{{ translate('Select Purpose') }}</option>
            @foreach ($purposes as $purpose)
                <option value="{{ $purpose->id }}" @selected((string) old('purpose_id', $contact->purpose_id ?? '') === (string) $purpose->id)>{{ $purpose->name }}</option>
            @endforeach
        </select>
        @error('purpose_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Mobile Number') }}</label>
    <div class="col-md-4">
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $contact->phone ?? '') }}">
        @error('phone') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-1 col-form-label">{{ translate('WhatsApp Number') }}</label>
    <div class="col-md-4">
        <input type="text" name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $contact->whatsapp_number ?? '') }}">
        @error('whatsapp_number') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Alternate Mobile No') }}</label>
    <div class="col-md-9">
        <input type="text" name="alternate_mobile_number" class="form-control" value="{{ old('alternate_mobile_number', $contact->alternate_mobile_number ?? '') }}">
        @error('alternate_mobile_number') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Email') }}</label>
    <div class="col-md-9">
        <input type="email" name="email" class="form-control" value="{{ old('email', $contact->email ?? '') }}">
        @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Insta ID / LinkedIn ID') }}</label>
    <div class="col-md-9">
        <div id="contact_social_media_rows">
            @foreach ($socialMediaRows as $row)
                <div class="row gutters-5 contact-social-media-row mb-2">
                    <div class="col-md-5">
                        <input type="text" name="social_media_keys[]" class="form-control" value="{{ $row['key'] ?? '' }}" placeholder="{{ translate('Platform') }}">
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="social_media_values[]" class="form-control" value="{{ $row['value'] ?? '' }}" placeholder="{{ translate('ID / URL') }}">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-soft-danger btn-icon btn-circle js-remove-social-media-row" title="{{ translate('Remove') }}">
                            <i class="las la-trash"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-soft-primary btn-sm" id="contact_add_social_media_row">{{ translate('Add More') }}</button>
        @error('social_media_keys') <span class="text-danger small d-block">{{ $message }}</span> @enderror
        @error('social_media_values') <span class="text-danger small d-block">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Address') }}</label>
    <div class="col-md-9">
        <textarea name="address" rows="2" class="form-control">{{ old('address', $contact->address ?? '') }}</textarea>
        @error('address') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Country') }}</label>
    <div class="col-md-4">
        <select name="country_id" id="contact_country_id" class="form-control aiz-selectpicker" data-live-search="true">
            <option value="">{{ translate('Select Country') }}</option>
            @foreach ($countries as $country)
                <option value="{{ $country->id }}" @selected((string) old('country_id', $contact->country_id ?? '') === (string) $country->id)>{{ $country->name }}</option>
            @endforeach
        </select>
        @error('country_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-1 col-form-label">{{ translate('State/Region') }}</label>
    <div class="col-md-4">
        <select name="state_id" id="contact_state_id" class="form-control aiz-selectpicker" data-live-search="true">
            <option value="">{{ translate('Select State') }}</option>
            @foreach ($states as $state)
                <option value="{{ $state->id }}" @selected((string) old('state_id', $contact->state_id ?? '') === (string) $state->id)>{{ $state->name }}</option>
            @endforeach
        </select>
        @error('state_id') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('District') }}</label>
    <div class="col-md-4">
        <input type="text" name="district" class="form-control" value="{{ old('district', $contact->district ?? '') }}">
        @error('district') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
    <label class="col-md-1 col-form-label">{{ translate('Post') }}</label>
    <div class="col-md-4">
        <input type="text" name="post" class="form-control" value="{{ old('post', $contact->post ?? '') }}">
        @error('post') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Tags') }}</label>
    <div class="col-md-9">
        <input type="text" name="tags" class="form-control" value="{{ $tagsValue }}" placeholder="{{ translate('Comma separated tags') }}">
        @error('tags') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="form-group row">
    <label class="col-md-2 col-form-label">{{ translate('Notes') }}</label>
    <div class="col-md-9">
        <textarea name="notes" rows="4" class="form-control">{{ old('notes', $contact->notes ?? '') }}</textarea>
        @error('notes') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<div class="text-right">
    <button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
</div>
