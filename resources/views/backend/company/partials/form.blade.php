@php
    $savedCategoryIds = (isset($company) && $company->exists) ? $company->categories->pluck('id')->all() : [];
    $selectedCategoryIds = collect(old('deal_in_category_ids', $savedCategoryIds))
        ->map(fn ($id) => (string) $id)
        ->values()
        ->all();
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
        @if (!empty($lockCompanyName))
            <input type="text" id="company_name" class="form-control" maxlength="255"
                value="{{ \App\Models\CompanyConfiguration::BILLING_NAME }}" disabled>
            <small class="text-muted">{{ translate('Billing company name is locked.') }}</small>
        @else
            <input type="text" id="company_name" name="company_name"
                class="form-control @error('company_name') is-invalid @enderror"
                value="{{ old('company_name', $company->company_name ?? '') }}" maxlength="255" required>
            @error('company_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
        @endif
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

@if (!empty($locationReady))
    @php
        $selectedCountry = (string) old('country_id', $company->country_id ?? '');
        $selectedState = (string) old('state_id', $company->state_id ?? '');
        $selectedCity = (string) old('city_id', $company->city_id ?? '');
        $selectedDistrict = (string) old('district', $company->district ?? '');
        $selectedPost = (string) old('post', $company->post ?? '');
        $selectedVillage = (string) old('village', $company->village ?? '');
        $selectedPincode = (string) old('pincode', $company->pincode ?? '');
    @endphp
    <div class="form-group row">
        <label class="col-md-3 col-form-label">{{ translate('Location') }}</label>
        <div class="col-md-9">
            <div class="row gutters-5">
                <div class="col-md-4 mb-3">
                    <label for="company_country_id">{{ translate('Country') }} <span class="text-danger">*</span></label>
                    <select name="country_id" id="company_country_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                        <option value="">{{ translate('Select Country') }}</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected($selectedCountry === (string) $country->id)>{{ $country->name }}</option>
                        @endforeach
                    </select>
                    @error('country_id') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label for="company_state_id">{{ translate('State') }} <span class="text-danger">*</span></label>
                    <select name="state_id" id="company_state_id" class="form-control aiz-selectpicker" data-live-search="true" data-selected="{{ $selectedState }}" required>
                        <option value="">{{ translate('Select State') }}</option>
                    </select>
                    @error('state_id') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label for="company_district">{{ translate('District') }} <span class="text-danger">*</span></label>
                    <select name="district" id="company_district" class="form-control aiz-selectpicker" data-live-search="true" data-selected="{{ $selectedDistrict }}" required>
                        <option value="">{{ translate('Select District') }}</option>
                    </select>
                    @error('district') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label for="company_city_id">{{ translate('City') }} <span class="text-danger">*</span></label>
                    <select name="city_id" id="company_city_id" class="form-control aiz-selectpicker" data-live-search="true" data-selected="{{ $selectedCity }}" required>
                        <option value="">{{ translate('Select City') }}</option>
                    </select>
                    @error('city_id') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label for="company_post">{{ translate('Post') }} <span class="text-danger">*</span></label>
                    <select name="post" id="company_post" class="form-control aiz-selectpicker" data-live-search="true" data-selected="{{ $selectedPost }}" required>
                        <option value="">{{ translate('Select Post') }}</option>
                    </select>
                    @error('post') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label for="company_village">{{ translate('Village') }} <span class="text-danger">*</span></label>
                    <select name="village" id="company_village" class="form-control aiz-selectpicker" data-live-search="true" data-selected="{{ $selectedVillage }}" required>
                        <option value="">{{ translate('Select Village') }}</option>
                    </select>
                    @error('village') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label for="company_pincode">{{ translate('Pincode') }} <span class="text-danger">*</span></label>
                    <select name="pincode" id="company_pincode" class="form-control aiz-selectpicker" data-live-search="true" data-selected="{{ $selectedPincode }}" required>
                        <option value="">{{ translate('Select Pincode') }}</option>
                    </select>
                    @error('pincode') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>
@else
    <div class="alert alert-soft-warning">
        {{ translate('Location fields are waiting for the database update. Run the company location SQL, then reload this page.') }}
    </div>
@endif

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
        @php
            $selectedCompanyType = (string) old('company_type', $company->company_type ?? '');
            $companyTypeIsManual = $selectedCompanyType !== '' && !in_array($selectedCompanyType, $companyTypes instanceof \Illuminate\Support\Collection ? $companyTypes->all() : (array) $companyTypes, true);
        @endphp
        <select id="company_type" name="company_type"
            class="form-control aiz-selectpicker @error('company_type') is-invalid @enderror"
            data-live-search="true" required>
            <option value="">{{ translate('Select Company Type') }}</option>
            @foreach ($companyTypes as $companyType)
                <option value="{{ $companyType }}" @selected(!$companyTypeIsManual && $selectedCompanyType === $companyType)>
                    {{ translate($companyType) }}
                </option>
            @endforeach
            <option value="__not_in_list__" @selected($companyTypeIsManual)>{{ translate('Not In List') }}</option>
        </select>
        <div id="company_type_manual_wrap" class="mt-2 {{ $companyTypeIsManual ? '' : 'd-none' }}">
            <input type="text" id="company_type_manual" name="company_type_manual" maxlength="100"
                class="form-control"
                value="{{ old('company_type_manual', $companyTypeIsManual ? $selectedCompanyType : '') }}"
                placeholder="{{ translate('Add company type manually') }}">
        </div>
        @error('company_type') <span class="text-danger small">{{ $message }}</span> @enderror
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.jQuery) {
            return;
        }
        jQuery('#company_type').on('changed.bs.select', function () {
            var manual = jQuery('#company_type_manual_wrap');
            if (jQuery(this).val() === '__not_in_list__') {
                manual.removeClass('d-none');
            } else {
                manual.addClass('d-none');
            }
        });
    });
</script>

@include('backend.company.partials.files')

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
    @if (!empty($locationReady))
        <script>
            $(document).ready(function () {
                const locationPlaceholder = @json(translate('Select'));
                const locationUrl = @json(route('companies.location.options'));

                function refreshCompanyPicker($el) {
                    if (window.AIZ && AIZ.plugins && typeof AIZ.plugins.bootstrapSelect === 'function') {
                        AIZ.plugins.bootstrapSelect('refresh');
                    } else if ($.fn.selectpicker) {
                        $el.selectpicker('refresh');
                    }
                }

                function setCompanyLocationOptions(field, options, selected) {
                    const $select = $('#company_' + field);
                    const finalSelected = selected === undefined || selected === null ? '' : String(selected);
                    const list = (options || []).slice();

                    if (finalSelected !== '' && !list.some(function (opt) { return String(opt.id) === String(finalSelected); })) {
                        list.push({ id: finalSelected, name: finalSelected });
                    }

                    $select.empty();
                    $select.append('<option value="">' + locationPlaceholder + '</option>');
                    list.forEach(function (opt) {
                        $select.append($('<option>', { value: opt.id, text: opt.name }));
                    });

                    if (finalSelected !== '') {
                        $select.val(String(finalSelected));
                    }

                    $select.data('selected', '');
                    refreshCompanyPicker($select);
                }

                function populateCompanyPincodes(preserveSelected) {
                    const village = $('#company_village').val();
                    if (!village) {
                        setCompanyLocationOptions('pincode', [], '');
                        return;
                    }

                    $.get(locationUrl, companyLocationParams()).done(function (resp) {
                        const selected = preserveSelected ? $('#company_pincode').data('selected') : '';
                        setCompanyLocationOptions('pincode', resp.pincodes || [], selected);
                    });
                }

                function populateCompanyVillages(preserveSelected) {
                    const post = $('#company_post').val();
                    if (!post) {
                        setCompanyLocationOptions('village', [], '');
                        setCompanyLocationOptions('pincode', [], '');
                        return;
                    }

                    $.get(locationUrl, companyLocationParams()).done(function (resp) {
                        const selected = preserveSelected ? $('#company_village').data('selected') : '';
                        setCompanyLocationOptions('village', resp.villages || [], selected);
                        populateCompanyPincodes(preserveSelected);
                    });
                }

                function populateCompanyPosts(preserveSelected) {
                    const district = $('#company_district').val();
                    const cityId = $('#company_city_id').val();
                    if (!district || !cityId) {
                        setCompanyLocationOptions('post', [], '');
                        setCompanyLocationOptions('village', [], '');
                        setCompanyLocationOptions('pincode', [], '');
                        return;
                    }

                    $.get(locationUrl, companyLocationParams()).done(function (resp) {
                        const selected = preserveSelected ? $('#company_post').data('selected') : '';
                        setCompanyLocationOptions('post', resp.posts || [], selected);
                        populateCompanyVillages(preserveSelected);
                    });
                }

                function populateCompanyDistricts(preserveSelected) {
                    const stateId = $('#company_state_id').val();
                    if (!stateId) {
                        setCompanyLocationOptions('district', [], '');
                        setCompanyLocationOptions('city_id', [], '');
                        setCompanyLocationOptions('post', [], '');
                        setCompanyLocationOptions('village', [], '');
                        setCompanyLocationOptions('pincode', [], '');
                        return;
                    }

                    $.get(locationUrl, companyLocationParams()).done(function (resp) {
                        const selectedDistrict = preserveSelected ? $('#company_district').data('selected') : '';
                        const selectedCity = preserveSelected ? $('#company_city_id').data('selected') : '';
                        setCompanyLocationOptions('district', resp.districts || [], selectedDistrict);
                        setCompanyLocationOptions('city_id', resp.cities || [], selectedCity);
                        populateCompanyPosts(preserveSelected);
                    });
                }

                function populateCompanyStates(preserveSelected) {
                    const countryId = $('#company_country_id').val();
                    if (!countryId) {
                        setCompanyLocationOptions('state_id', [], '');
                        setCompanyLocationOptions('district', [], '');
                        setCompanyLocationOptions('city_id', [], '');
                        setCompanyLocationOptions('post', [], '');
                        setCompanyLocationOptions('village', [], '');
                        setCompanyLocationOptions('pincode', [], '');
                        return;
                    }

                    $.get(locationUrl, { country_id: countryId }).done(function (resp) {
                        const selected = preserveSelected ? $('#company_state_id').data('selected') : '';
                        setCompanyLocationOptions('state_id', resp.states || [], selected);
                        populateCompanyDistricts(preserveSelected);
                    });
                }

                function companyLocationParams() {
                    return {
                        country_id: $('#company_country_id').val() || '',
                        state: $('#company_state_id').val() || '',
                        district: $('#company_district').val() || '',
                        city: $('#company_city_id').val() || '',
                        post: $('#company_post').val() || '',
                        village: $('#company_village').val() || ''
                    };
                }

                populateCompanyStates(true);

                $('#company_country_id').on('change', function () {
                    populateCompanyStates(false);
                });
                $('#company_state_id').on('change', function () {
                    populateCompanyDistricts(false);
                });
                $('#company_district, #company_city_id').on('change', function () {
                    populateCompanyPosts(false);
                });
                $('#company_post').on('change', function () {
                    populateCompanyVillages(false);
                });
                $('#company_village').on('change', function () {
                    populateCompanyPincodes(false);
                });
            });
        </script>
    @endif
@endpush
