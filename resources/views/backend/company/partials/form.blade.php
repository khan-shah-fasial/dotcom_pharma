@php
    $selectedCategoryIds = collect(old(
        'deal_in_category_ids',
        isset($company) ? $company->categories->pluck('id')->all() : []
    ))->map(fn ($id) => (string) $id)->values()->all();
@endphp

<style>
    .company-category-picker .hummingbird-treeview input[type="radio"] {
        display: none;
    }
</style>

<div class="form-group row">
    <label class="col-md-3 col-form-label" for="code">
        {{ translate('Code') }} <span class="text-danger">*</span>
    </label>
    <div class="col-md-9">
        <input type="text" id="code" name="code"
            class="form-control @error('code') is-invalid @enderror"
            value="{{ old('code', $company->code ?? '') }}" maxlength="50" required>
        @error('code') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>
</div>

<div class="row">
    @foreach ([
        'logo' => 'Logo',
        'stamp' => 'Stamp',
        'sign' => 'Sign',
    ] as $field => $label)
        <div class="col-lg-4">
            <div class="form-group">
                <label>{{ translate($label) }}</label>
                <div class="input-group" data-toggle="aizuploader" data-type="image">
                    <div class="input-group-prepend">
                        <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                    </div>
                    <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                    <input type="hidden" name="{{ $field }}"
                        value="{{ old($field, $company->{$field} ?? '') }}" class="selected-files">
                </div>
                <div class="file-preview box sm"></div>
                @error($field) <span class="text-danger small">{{ $message }}</span> @enderror
            </div>
        </div>
    @endforeach
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label" for="company_name">
        {{ translate('Company Name') }} <span class="text-danger">*</span>
    </label>
    <div class="col-md-9">
        <input type="text" id="company_name" name="company_name"
            class="form-control @error('company_name') is-invalid @enderror"
            value="{{ old('company_name', $company->company_name ?? '') }}" maxlength="255" required>
        @error('company_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label" for="full_address">
        {{ translate('Full Address') }} <span class="text-danger">*</span>
    </label>
    <div class="col-md-9">
        <textarea id="full_address" name="full_address" rows="4"
            class="form-control @error('full_address') is-invalid @enderror"
            required>{{ old('full_address', $company->full_address ?? '') }}</textarea>
        @error('full_address') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="form-group">
            <label for="contact_person">{{ translate('Contact Person') }}</label>
            <input type="text" id="contact_person" name="contact_person"
                class="form-control @error('contact_person') is-invalid @enderror"
                value="{{ old('contact_person', $company->contact_person ?? '') }}" maxlength="255">
            @error('contact_person') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>
    <div class="col-lg-6">
        <div class="form-group">
            <label for="designation">{{ translate('Designation') }}</label>
            <input type="text" id="designation" name="designation"
                class="form-control @error('designation') is-invalid @enderror"
                value="{{ old('designation', $company->designation ?? '') }}" maxlength="255">
            @error('designation') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="form-group">
            <label for="mobile">{{ translate('Mobile') }}</label>
            <input type="tel" id="mobile" name="mobile"
                class="form-control @error('mobile') is-invalid @enderror"
                value="{{ old('mobile', $company->mobile ?? '') }}" maxlength="30">
            @error('mobile') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-group">
            <label for="whatsapp">{{ translate('WhatsApp') }}</label>
            <input type="tel" id="whatsapp" name="whatsapp"
                class="form-control @error('whatsapp') is-invalid @enderror"
                value="{{ old('whatsapp', $company->whatsapp ?? '') }}" maxlength="30">
            @error('whatsapp') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-group">
            <label for="email">{{ translate('E-mail') }}</label>
            <input type="email" id="email" name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email', $company->email ?? '') }}" maxlength="255">
            @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label" for="company_type">
        {{ translate('Company Type') }} <span class="text-danger">*</span>
    </label>
    <div class="col-md-9">
        <select id="company_type" name="company_type"
            class="form-control aiz-selectpicker @error('company_type') is-invalid @enderror"
            data-live-search="true" required>
            <option value="">{{ translate('Select Company Type') }}</option>
            @foreach ($companyTypes as $companyType)
                <option value="{{ $companyType }}"
                    @selected(old('company_type', $company->company_type ?? '') === $companyType)>
                    {{ translate($companyType) }}
                </option>
            @endforeach
        </select>
        @error('company_type') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>

<div class="form-group row">
    <label class="col-md-3 col-form-label">
        {{ translate('Deal In Category') }} <span class="text-danger">*</span>
    </label>
    <div class="col-md-9">
        <div class="card mb-0 company-category-picker @error('deal_in_category_ids') border border-danger @enderror">
            <div class="card-header">
                <h6 class="mb-0">{{ translate('Select all applicable product categories') }}</h6>
            </div>
            <div class="card-body">
                <div class="h-300px overflow-auto c-scrollbar-light">
                    <ul class="hummingbird-treeview-converter list-unstyled"
                        data-checkbox-name="deal_in_category_ids[]"
                        data-radio-name="deal_in_main_category_id"
                        data-id="-company-category">
                        @foreach ($categories as $category)
                            <li id="{{ $category->id }}">{{ $category->getTranslation('name') }}</li>
                            @foreach ($category->childrenCategories as $childCategory)
                                @include('backend.company.partials.category_item', ['category' => $childCategory])
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @error('deal_in_category_ids') <span class="text-danger small">{{ $message }}</span> @enderror
        @error('deal_in_category_ids.*') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>

@push('company_scripts')
    <script src="{{ static_asset('assets/js/hummingbird-treeview.js') }}"></script>
    <script>
        $(document).ready(function () {
            const selectedCategoryIds = @json($selectedCategoryIds);
            const $tree = $('#treeview-company-category');

            if (!$tree.length) {
                return;
            }

            $tree.hummingbird();

            selectedCategoryIds.forEach(function (categoryId) {
                const $checkbox = $tree.find('input:checkbox#' + categoryId);
                $checkbox.prop('checked', true);
                $checkbox.parents('ul').css('display', 'block');
                $checkbox.parents('li').children('.las').removeClass('la-plus').addClass('la-minus');
            });
        });
    </script>
@endpush
