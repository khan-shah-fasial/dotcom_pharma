@extends('frontend.layouts.app')

@section('content')
    <style>
        .enq-shell {
            max-width: 1100px;
            margin: 0 auto;
            padding: 30px 15px;
        }
        
        .enq-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            background: #ffffff;
            overflow: hidden;
        }
        
        .enq-head {
            background: linear-gradient(135deg, #2b56a1 0%, #1e3f7a 100%);
            border-bottom: 2px solid #1e3f7a;
            padding: 24px 30px;
            color: #ffffff;
        }
        
        .enq-head h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .enq-head p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin-bottom: 0;
        }
        
        .enq-section {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: 24px;
            background: #ffffff;
            transition: box-shadow 0.3s ease;
        }
        
        .enq-section:hover {
            box-shadow: 0 2px 8px rgba(43, 86, 161, 0.1);
        }
        
        .enq-section .section-title {
            background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            border-bottom: 2px solid #2b56a1;
            border-radius: 10px 10px 0 0;
            padding: 14px 20px;
            font-weight: 700;
            font-size: 16px;
            color: #2b56a1;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .enq-body {
            padding: 24px;
        }
        
        .helper {
            font-size: 12px;
            color: #6b7280;
            margin-top: 6px;
            line-height: 1.5;
        }
        
        .label-strong {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
            margin-bottom: 8px;
            display: block;
        }
        
        .upload-col {
            min-height: 100%;
        }
        
        .form-control,
        .input-group-text,
        select.form-control {
            border-radius: 8px !important;
            border: 1px solid #d1d5db;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus,
        select.form-control:focus {
            border-color: #2b56a1;
            box-shadow: 0 0 0 3px rgba(43, 86, 161, 0.1);
            outline: none;
        }
        
        textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }
        
        .aiz-megabox {
            padding: 10px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-bottom:0px;
        }
        
        .aiz-megabox:hover {
            border-color: #2b56a1;
            background: #f8f9fa;
        }
        
        .aiz-megabox input {
            display: none;
        }
        
        .aiz-megabox .aiz-rounded-check {
            width: 18px;
            height: 18px;
            border: 2px solid #d1d5db;
            border-radius: 50%;
            display: inline-block;
            transition: all 0.3s ease;
            margin-right: 8px;
        }
        
        .aiz-megabox input:checked + span .aiz-rounded-check {
            
            border-color: #2b56a1;
            box-shadow: 0 0 0 3px rgba(43, 86, 161, 0.2);
        }
        
        .aiz-megabox input:checked + span {
            color: #2b56a1;
            font-weight: 600;
        }

        /* Type toggle - special pill style buttons for Enquiry / Suggestion only */
        .type-toggle-group {
            gap: 10px;
        }

        .type-toggle-option {
            border-radius: 999px;
            border-color: #285098;
            background: #eef2ff;
            padding: 0;
            overflow: hidden;
        }

        .type-toggle-option > span {
            padding: 8px 22px;
            border-radius: 999px;
            background: transparent;
            font-weight: 500;
            color: #374151;
        }

        .type-toggle-option .aiz-rounded-check {
            display: none;
        }

        .type-toggle-option:hover {
            background: #e0e7ff;
            border-color: #285198;
            transform: translateY(-1px);
            box-shadow: none;
        }

        .type-toggle-option input:checked + span {
            background: #285198;
            color: #ffffff;
            box-shadow: none;
        }

        .type-toggle-option input:checked + span::after {
            content: '';
            display: block;
            position: absolute;
            inset: 0;
            border-radius: 999px;
            box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.35) inset;
        }
        
        .form-group-spacing {
            margin-bottom: 20px;
        }
        
        .row.g-3 {
            margin-bottom: 0;
        }
        
        .row.g-3 > [class*="col-"] {
            margin-bottom: 20px;
        }
        
        .row.g-3 > [class*="col-"]:last-child {
            margin-bottom: 0;
        }
        
        .input-group .btn {
            border-radius: 0 8px 8px 0;
            border-left: none;
            font-size: 14px;
            font-weight: 500;
            padding: 10px 16px;
        }
        
        .input-group .form-control:first-child {
            border-radius: 8px 0 0 8px !important;
        }

        .product-autocomplete {
            position: relative;
        }

        .product-autocomplete .autocomplete-toggle {
            border-radius: 0 8px 8px 0 !important;
            border-left: 0;
            background: #ffffff;
            color: #4b5563;
        }

        .product-autocomplete .autocomplete-dropdown {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            z-index: 1060;
            max-height: 260px;
            overflow-y: auto;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
            display: none;
        }

        .product-autocomplete .autocomplete-option {
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            line-height: 1.35;
        }

        .product-autocomplete .autocomplete-option:last-child {
            border-bottom: 0;
        }

        .product-autocomplete .autocomplete-option:hover {
            background: #eff6ff;
        }

        .product-autocomplete .autocomplete-option-title {
            font-weight: 600;
            color: #111827;
        }

        .product-autocomplete .autocomplete-option-sub {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }

        .product-autocomplete .autocomplete-empty {
            padding: 10px 14px;
            font-size: 13px;
            color: #6b7280;
        }

        .selected-product-photo {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px;
            background: #ffffff;
            display: none;
            max-width: 210px;
        }

        .selected-product-photo img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 8px;
            cursor: zoom-in;
            border: 1px solid #e5e7eb;
        }

        .selected-product-photo .photo-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2b56a1 0%, #1e3f7a 100%);
            border: none;
            border-radius: 8px;
            padding: 12px 40px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(43, 86, 161, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(43, 86, 161, 0.4);
        }
        
        .btn-outline-secondary {
            border-color: #d1d5db;
            color: #6b7280;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover {
            background-color: #2b56a1;
            border-color: #2b56a1;
            color: #ffffff;
        }
        
        /* Tagify auto-expand height */
        .aiz-tag-input.tagify {
            min-height: 42px;
            max-height: none;
            height: auto;
            padding: 4px;
        }
        
        .aiz-tag-input.tagify .tagify__tags {
            min-height: auto;
            height: auto;
        }
        
        .aiz-tag-input.tagify .tagify__tag {
            margin: 2px;
        }
        
        .aiz-tag-input.tagify .tagify__input {
            margin: 2px;
        }
        
        .gap-3
        {
            gap:10px;
        }

        div#productPhotoModal img#productPhotoModalImg {
    width: 78% !important;
    height: auto;
    display: block;
    border-radius: 6px;
    margin-left: auto;
    margin-right: auto;
    display: block;
}

div#productPhotoModal .modal-body.p-2 {
    padding: 20px 25px;
    overflow-y: auto;
    max-height: none !important;
}

div#productPhotoModal .modal-content {
    background: transparent !important;
}

        @media (max-width: 767px) {
            .enq-shell {
                padding: 15px;
            }
            
            .enq-head {
                padding: 20px;
            }
            
            .enq-head h1 {
                font-size: 20px;
            }
            
            .enq-body {
                padding: 16px;
            }
            
            .enq-body .row > [class*="col-"] {
                margin-bottom: 16px;
            }
            
            .text-right {
                text-align: center !important;
            }
            
            .btn-primary {
                width: 100%;
            }
        }

        .form-header-section {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 28px;
    border: 1px solid #e2e8f0;
}

        
    </style>

    <section class="py-4 bg-light">
        <div class="container enq-shell">
            <div class="enq-card">
                <div class="enq-head text-center">
                    <h1 id="form_main_title" class="h5 fw-700 mb-1">
                        {{ translate((isset($defaultType) && $defaultType === 'suggestion') ? 'Suggest a product' : 'Enquiry') }}
                    </h1>
                    <p id="form_sub_title" class="text-white mb-0">
                        {{ translate((isset($defaultType) && $defaultType === 'suggestion') ? 'Product Suggestion Form' : 'Product Enquiry Form') }}
                    </p>
                </div>
                <div class="p-4">
                    <form id="form-enquiry" class="form-default" action="{{ route('form_enquiry.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-header-section">
                        <div class="row g-3">
                            <div class="col-md-4 form-group-spacing">
                                <label class="label-strong">{{ translate('Type') }}</label>
                                <div class="d-flex flex-wrap type-toggle-group">
                                    <label class="aiz-megabox type-toggle-option position-relative">
                                        <input type="radio" name="type" value="enquiry" {{ (isset($defaultType) && $defaultType == 'enquiry') || (!isset($defaultType)) ? 'checked' : '' }}>
                                        <span class="d-flex align-items-center">
                                            <span class="aiz-rounded-check"></span>
                                            <span class="ml-2">{{ translate('Enquiry') }}</span>
                                        </span>
                                    </label>
                                    <label class="aiz-megabox type-toggle-option position-relative">
                                        <input type="radio" name="type" value="suggestion" {{ (isset($defaultType) && $defaultType == 'suggestion') ? 'checked' : '' }}>
                                        <span class="d-flex align-items-center">
                                            <span class="aiz-rounded-check"></span>
                                            <span class="ml-2">{{ translate('Suggestion') }}</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 form-group-spacing">
                                <label class="label-strong">{{ translate('Form No (Auto)') }}</label>
                                <input type="text" class="form-control bg-white" name="form_code_display" id="form_code_display" value="{{ $nextCodes['enquiry'] ?? '' }}" readonly>
                                <input type="hidden" name="form_code_visual " id="form_code_visual" value="{{ $nextCodes['enquiry'] ?? '' }}">
                                <input type="hidden" id="next_enquiry_code" value="{{ $nextCodes['enquiry'] ?? '' }}">
                                <input type="hidden" id="next_suggestion_code" value="{{ $nextCodes['suggestion'] ?? '' }}">
                            </div>
                            <div class="col-md-4 form-group-spacing">
                                <label class="label-strong">{{ translate('Date') }}</label>
                                <input type="date" class="form-control" value="{{ $today }}" readonly>
                            </div>

                            
                            <div class="col-md-12 form-group-spacing">
                                <label class="label-strong">{{ translate('For') }}</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="aiz-megabox">
                                        <input type="radio" name="domestic_type" value="domestic" checked>
                                        <span class="d-flex align-items-center"><span class="aiz-rounded-check"></span><span class="ml-2">{{ translate('Domestic') }}</span></span>
                                    </label>
                                     <label class="aiz-megabox">
                                        <input type="radio" name="domestic_type" value="exports">
                                        <span class="d-flex align-items-center"><span class="aiz-rounded-check"></span><span class="ml-2">{{ translate('Exports') }}</span></span>
                                    </label>
                                    <label class="aiz-megabox">
                                        <input type="radio" name="domestic_type" value="govt_supply">
                                        <span class="d-flex align-items-center"><span class="aiz-rounded-check"></span><span class="ml-2">{{ translate('Govt. Supply') }}</span></span>
                                    </label>
                                   
                                    <label class="aiz-megabox">
                                        <input type="radio" name="domestic_type" value="third_party">
                                        <span class="d-flex align-items-center"><span class="aiz-rounded-check"></span><span class="ml-2">{{ translate('Third Party Manufacturing') }}</span></span>
                                    </label>
                                    <label class="aiz-megabox">
                                        <input type="radio" name="domestic_type" value="loan_licence">
                                        <span class="d-flex align-items-center"><span class="aiz-rounded-check"></span><span class="ml-2">{{ translate('Loan Licence Manufacturing') }}</span></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        </div>


                        <div class="enq-section mt-4">
                            <div class="section-title">{{ translate('Product Details') }}</div>
                            <div class="enq-body">
                                <div class="row g-3">
                                    <div class="col-md-4 form-group-spacing">
                                <label class="label-strong">{{ translate('Category') }}</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="aiz-megabox">
                                        <input type="radio" name="category" value="veterinary" checked>
                                        <span class="d-flex align-items-center">
                                            <span class="aiz-rounded-check"></span>
                                            <span class="ml-2">{{ translate('Veterinary') }}</span>
                                        </span>
                                    </label>
                                    <label class="aiz-megabox">
                                        <input type="radio" name="category" value="human">
                                        <span class="d-flex align-items-center">
                                            <span class="aiz-rounded-check"></span>
                                            <span class="ml-2">{{ translate('Human') }}</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            
                                <div class="col-md-8">
                                        <label class="label-strong">{{ translate('Product Name') }}</label>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="product-autocomplete">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" name="product_name" id="product_name" placeholder="{{ translate('Type product name or select from list') }}" autocomplete="off" required>
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-outline-secondary autocomplete-toggle" id="product_picker_toggle" aria-label="{{ translate('Show product list') }}">
                                                                <i class="fas fa-chevron-down"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div id="product_picker_dropdown" class="autocomplete-dropdown"></div>
                                                </div>
                                                <input type="hidden" name="product_id" id="product_id">
                                            </div>
                                        </div>
                                        <div class="helper">{{ translate('If not in list, type manually. Selecting a product will auto-fill role, group, brand & categories.') }}</div>
                                        <div class="mt-2">
                                            <label class="label-strong mb-1">{{ translate('Selected Product Photo') }}</label>
                                            <div id="selected_product_photo" class="selected-product-photo">
                                                <img id="selected_product_photo_img" src="" alt="{{ translate('Selected product') }}">
                                                <div class="photo-label">{{ translate('Click image to view large') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row g-3">
                                            <div class="col-12 col-md-6">
                                                <label class="label-strong">{{ translate('Drug Role') }}</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" name="drug_role" id="drug_role" readonly placeholder="{{ translate('Auto / manual') }}">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="button" onclick="toggleReadonly('drug_role')" title="{{ translate('Edit') }}"><i class="fas fa-edit"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="label-strong">{{ translate('Product Group') }}</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" name="product_group" id="product_group" readonly placeholder="{{ translate('Auto / manual') }}">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="button" onclick="toggleReadonly('product_group')" title="{{ translate('Edit') }}"><i class="fas fa-edit"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Product Category') }}</label>
                                        <input type="text" class="form-control aiz-tag-input" name="product_categories" id="product_categories" placeholder="{{ translate('Tag categories') }}">
                                        <div class="helper">{{ translate('Auto from product; add/remove tags as needed') }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('Brand Name') }}</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="brand_name" id="brand_name" readonly placeholder="{{ translate('Auto / manual') }}">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" onclick="toggleReadonly('brand_name')" title="{{ translate('Edit') }}"><i class="fas fa-edit"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('Pack Size') }}</label>
                                        <input type="number" class="form-control" id="pack_size" name="pack_size" min="0" placeholder="{{ translate('Pack size') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('Qty Required') }}</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" min="0" placeholder="{{ translate('Qty') }}">
                                    </div>
                                   
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Composition') }}</label>
                                        <textarea class="form-control" rows="3" id="composition_text" name="composition_text" placeholder="{{ translate('Describe composition') }}"></textarea>
                                    </div>

                                     <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Descriptions') }}</label>
                                        <textarea class="form-control" rows="3" id="description_text" name="description_text" placeholder="{{ translate('Describe details') }}"></textarea>
                                    </div>

                                     <div class="col-md-12 upload-col">
                                        <label class="label-strong">{{ translate('Upload File') }} (Composition / Description)</label>
                                        <input type="file" class="form-control" name="composition_files[]" multiple>
                                    </div>
                                   
                                   
                                </div>
                            </div>
                        </div>
                        <div class="enq-section domestic-section" data-section="govt_supply">
                            <div class="section-title">{{ translate('Government Supply') }}</div>
                            <div class="enq-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('Tender / Bid / GEM Bid No') }}</label>
                                        <input type="text" class="form-control" name="gov_tender_no">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('State') }}</label>
                                        <select class="form-control aiz-selectpicker" data-live-search="true" name="gov_state_id">
                                            <option value="">{{ translate('Select State') }}</option>
                                            @foreach($gov_state as $state)
                                                <option value="{{ $state->id }}">{{ $state->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('Department') }}</label>
                                        <input type="text" class="form-control" name="gov_department">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="label-strong">{{ translate('Start Date') }}</label>
                                        <input type="date" class="form-control" name="gov_start_date">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="label-strong">{{ translate('End Date') }}</label>
                                        <input type="date" class="form-control" name="gov_end_date">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Tender / Bid / GEM Bid Form') }}</label>
                                        <input type="file" class="form-control" name="gov_tender_files[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('List Of Documents Required') }}</label>
                                        <input type="file" class="form-control" name="gov_required_docs[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Authorisation Letter Format') }}</label>
                                        <input type="file" class="form-control" name="gov_authorisation_files[]" multiple>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="enq-section domestic-section d-none" data-section="exports">
                            <div class="section-title">{{ translate('Exports') }}</div>
                            <div class="enq-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('Country Name') }}</label>
                                        <select class="form-control aiz-selectpicker" data-live-search="true" name="export_country_id">
                                            <option value="">{{ translate('Select Country') }}</option>
                                            @foreach($countries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Valid IEC Certificate') }}</label>
                                        <input type="file" class="form-control" name="export_iec_files[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Design File (CDR / PDF / JPG)') }}</label>
                                        <input type="file" class="form-control" name="export_design_files[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('List Of Documents Required') }}</label>
                                        <input type="file" class="form-control" name="export_required_docs[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Authorisation Letter Format') }}</label>
                                        <input type="file" class="form-control" name="export_authorisation_files[]" multiple>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="enq-section domestic-section d-none" data-section="third_party">
                            <div class="section-title">{{ translate('Third Party Manufacturing') }}</div>
                            <div class="enq-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Your Brand Name Required') }}</label>
                                        <input type="text" class="form-control" name="tp_brand_name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Trade Mark Certificate / Undertaking Form') }}</label>
                                        <input type="file" class="form-control" name="tp_trademark_files[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <label class="label-strong mb-0">{{ translate('Undertaking Form Format') }}</label>
                                            @if($undertakingSample)
                                                <a class="btn btn-link p-0" target="_blank" href="{{ uploaded_asset($undertakingSample) }}">{{ translate('Download Sample') }}</a>
                                            @endif
                                        </div>
                                        <input type="file" class="form-control" name="tp_undertaking_files[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Drug Approval Copy (If Any)') }}</label>
                                        <input type="file" class="form-control" name="tp_drug_approval_files[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Design File (CDR / PDF / JPG)') }}</label>
                                        <input type="file" class="form-control" name="tp_design_files[]" multiple>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="enq-section domestic-section d-none" data-section="loan_licence">
                            <div class="section-title">{{ translate('Loan Licence Manufacturing') }}</div>
                            <div class="enq-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Your Brand Name Required') }}</label>
                                        <input type="text" class="form-control" name="loan_brand_name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Trade Mark Certificate / Undertaking Form') }}</label>
                                        <input type="file" class="form-control" name="loan_trademark_files[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <label class="label-strong mb-0">{{ translate('Undertaking Form Format') }}</label>
                                            @if($undertakingSample)
                                                <a class="btn btn-link p-0" target="_blank" href="{{ uploaded_asset($undertakingSample) }}">{{ translate('Download Sample') }}</a>
                                            @endif
                                        </div>
                                        <input type="file" class="form-control" name="loan_undertaking_files[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Drug Approval Copy (If Any)') }}</label>
                                        <input type="file" class="form-control" name="loan_drug_approval_files[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Design File (CDR / PDF / JPG)') }}</label>
                                        <input type="file" class="form-control" name="loan_design_files[]" multiple>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="enq-section">
                            <div class="section-title">{{ translate('Other Details') }}</div>
                            <div class="enq-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Product Photo (If You Have)') }}</label>
                                        <input type="file" class="form-control" name="common_product_photos[]" multiple accept="image/*">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('List Of Products (If More Than One Product)') }}</label>
                                        <input type="file" class="form-control" name="common_product_list_files[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Valid All Drug Licence') }}</label>
                                        <input type="file" class="form-control" name="common_drug_licence_files[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('GST No.') }}</label>
                                        <input type="text" class="form-control" name="common_gst_no" id="common_gst_no" placeholder="22AAAAA0000A1Z5" maxlength="15">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Valid GST Certificate') }}</label>
                                        <input type="file" class="form-control" name="common_gst_files[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Aadhar No.') }}</label>
                                        <input type="text" class="form-control" name="common_aadhar_no" id="common_aadhar_no" placeholder="{{ translate('Enter Aadhar number') }}" maxlength="12">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Aadhar Card No') }}</label>
                                        <input type="file" class="form-control" name="common_aadhar_files[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Special Instruction / Suggestion') }}</label>
                                        <input type="text" class="form-control" name="special_instruction" placeholder="{{ translate('Any specific instruction') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="enq-section">
                            <div class="section-title">{{ translate('Company Details') }}</div>
                            <div class="enq-body">
                                <div class="row g-3">
                                   
                                   
                                   
                                    

                                     <div class="col-md-3">
                                        <label class="label-strong">{{ translate('Company Name') }}</label>
                                        <input type="text" class="form-control" name="company_name" id="company_name">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="label-strong">{{ translate('Contact Person') }} *</label>
                                        <input type="text" class="form-control" name="contact_person" id="contact_person" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="label-strong">{{ translate('Designation') }}</label>
                                        <input type="text" class="form-control" name="designation" id="designation">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="label-strong">{{ translate('Mobile No *') }}</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" style="     max-width: 70px !important;    border-radius: 5px 0px 0px 5px !important;" name="mobile_country_code" id="mobile_country_code" value="+91">
                                            <input type="tel" class="form-control" name="mobile_number" id="mobile_number" required placeholder="{{ translate('Enter number') }}" style="border-radius: 0px 5px 5px 0px !important;">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="label-strong">{{ translate('E-mail ID *') }}</label>
                                        <input type="email" class="form-control" name="email" id="company_email" required placeholder="name@example.com">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="label-strong">{{ translate('Website') }}</label>
                                        <input type="text" class="form-control" name="website" id="company_website" placeholder="https://">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="label-strong">{{ translate('Visiting Card') }}</label>
                                        <input type="file" class="form-control" name="visiting_card_files[]" multiple>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="label-strong">{{ translate('Pincode') }}</label>
                                        <input type="text" class="form-control" name="company_pincode" id="company_pincode">
                                    </div>

                                     <div class="col-md-3">
                                        <label class="label-strong">{{ translate('Country') }}</label>
                                        <select class="form-control aiz-selectpicker" data-live-search="true" name="company_country_id" id="company_country">
                                            <option value="">{{ translate('Select Country') }}</option>
                                            @foreach($countries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="label-strong">{{ translate('State') }}</label>
                                        <select class="form-control aiz-selectpicker" data-live-search="true" name="company_state_id" id="company_state">
                                            <option value="">{{ translate('Select State') }}</option>
                                        </select>
                                    </div>

                                     <div class="col-md-3">
                                        <label class="label-strong">{{ translate('District') }}</label>
                                        <input type="text" class="form-control" name="company_district" id="company_district">
                                    </div>

                                     <div class="col-md-3">
                                        <label class="label-strong">{{ translate('Post') }}</label>
                                        <input type="text" class="form-control" name="company_post" id="company_post">
                                    </div>
                                   


                                     <div class="col-md-12">
                                        <label class="label-strong">{{ translate('Full Address') }}</label>
                                        <textarea class="form-control" name="company_address" id="company_address" rows="2"></textarea>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>

                        <div class="text-right" style="margin-bottom: 0; padding-bottom: 0;">
                            <button type="submit" class="btn btn-primary px-5 fw-700">{{ translate('Submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="productPhotoModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body p-2">
                    <img id="productPhotoModalImg" src="" alt="{{ translate('Product image') }}" style="width:100%;height:auto;display:block;border-radius:6px;">
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
<script>
    const productNameInput = document.getElementById('product_name');
    const productPickerToggle = document.getElementById('product_picker_toggle');
    const productPickerDropdown = document.getElementById('product_picker_dropdown');
    const selectedProductPhotoWrap = document.getElementById('selected_product_photo');
    const selectedProductPhotoImg = document.getElementById('selected_product_photo_img');
    const productPhotoModalImg = document.getElementById('productPhotoModalImg');
    const productIdInput = document.getElementById('product_id');
    const formMainTitle = document.getElementById('form_main_title');
    const formSubTitle = document.getElementById('form_sub_title');
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const categoryRadios = document.querySelectorAll('input[name="category"]');
    const domesticRadios = document.querySelectorAll('input[name="domestic_type"]');
    const domesticSections = document.querySelectorAll('.domestic-section');
    let productItems = [];

    function toggleSections() {
        const selected = document.querySelector('input[name="domestic_type"]:checked').value;
        domesticSections.forEach(sec => {
            sec.classList.toggle('d-none', sec.dataset.section !== selected);
        });
    }

    domesticRadios.forEach(r => r.addEventListener('change', toggleSections));
    toggleSections();

    function toggleReadonly(id) {
        const input = document.getElementById(id);
        input.readOnly = !input.readOnly;
        if (!input.readOnly) input.focus();
    }

    function setFormCode() {
        const type = document.querySelector('input[name="type"]:checked').value;
        const code = type === 'suggestion'
            ? document.getElementById('next_suggestion_code').value
            : document.getElementById('next_enquiry_code').value;
        document.getElementById('form_code_display').value = code;
        document.getElementById('form_code_visual').value = code;
    }

    function setFormTexts(type) {
        if (!formMainTitle || !formSubTitle) return;
        if (type === 'suggestion') {
            formMainTitle.textContent = "{{ translate('Suggest a product') }}";
            formSubTitle.textContent = "{{ translate('Product Suggestion Form') }}";
            return;
        }

        formMainTitle.textContent = "{{ translate('Enquiry') }}";
        formSubTitle.textContent = "{{ translate('Product Enquiry Form') }}";
    }
    
    function updateUrlType(type) {
        const url = new URL(window.location.href);
        url.searchParams.set('type', type);
        window.history.pushState({ type: type }, '', url.toString());
    }
    
    typeRadios.forEach(r => {
        r.addEventListener('change', function() {
            setFormCode();
            setFormTexts(this.value);
            updateUrlType(this.value);
        });
    });
    
    // Set form code on page load based on default type
    document.addEventListener('DOMContentLoaded', function() {
        setFormCode();
        setFormTexts(document.querySelector('input[name="type"]:checked')?.value || 'enquiry');
    });

    function fetchProducts(q = '') {
        const category = document.querySelector('input[name="category"]:checked').value;
        const params = new URLSearchParams({category, q});
        fetch('{{ route('form_enquiry.products') }}?' + params.toString())
            .then(res => res.json())
            .then(items => {
                productItems = items || [];
                renderProductDropdown([]);
            });
    }

    function getDisplayName(item) {
        const labels = getProductOptionLabels(item || {});
        return labels.title || '';
    }

    function isUsefulName(value) {
        const normalized = String(value || '').trim().toLowerCase();
        return normalized !== '' && normalized !== 'na' && normalized !== 'n/a' && normalized !== 'null' && normalized !== 'undefined' && normalized !== '-';
    }

    function getProductOptionLabels(item) {
        const drugName = (item.drug_name || '').trim();
        const productName = (item.name || '').trim();

        if (isUsefulName(drugName)) {
            return {
                title: drugName,
                sub: isUsefulName(productName) ? productName : '',
            };
        }

        if (isUsefulName(productName)) {
            return {
                title: productName,
                sub: '',
            };
        }

        return {
            title: '',
            sub: '',
        };
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderProductDropdown(items) {
        if (!productPickerDropdown) return;

        if (!items.length) {
            productPickerDropdown.innerHTML = '<div class="autocomplete-empty">{{ translate('No matching products') }}</div>';
            return;
        }

        productPickerDropdown.innerHTML = items.map((item) => {
            const labels = getProductOptionLabels(item);
            const displayName = escapeHtml(labels.title);
            const productName = escapeHtml(labels.sub);
            return `
                <div class="autocomplete-option" data-id="${item.id}">
                    <div class="autocomplete-option-title">${displayName || '-'}</div>
                    ${productName ? `<div class="autocomplete-option-sub">${productName}</div>` : ''}
                </div>
            `;
        }).join('');
    }

    function filterProducts(term) {
        const keyword = (term || '').trim().toLowerCase();
        if (!keyword) {
            return productItems.slice(0, 50);
        }

        return productItems.filter(item => {
            const displayName = getDisplayName(item).toLowerCase();
            const productName = (item.name || '').toLowerCase();
            return displayName.includes(keyword) || productName.includes(keyword);
        }).slice(0, 50);
    }

    function showDropdown() {
        if (!productPickerDropdown) return;
        productPickerDropdown.style.display = 'block';
    }

    function hideDropdown() {
        if (!productPickerDropdown) return;
        productPickerDropdown.style.display = 'none';
    }

    function setTagifyCategories(cats) {
        const $tagInput = $('#product_categories');
        const tagInstance = $tagInput.data('tagify');
        if (tagInstance) {
            tagInstance.removeAllTags();
            tagInstance.addTags(cats);
        } else if (window.Tagify) {
            const inst = new Tagify($tagInput[0]);
            inst.addTags(cats);
            $tagInput.data('tagify', inst);
        } else {
            $tagInput.val(cats.length ? JSON.stringify(cats) : '');
        }
    }

    function clearAutoProductFields() {
        productIdInput.value = '';
        productNameInput.dataset.selectedId = '';
        $('#drug_role').val('');
        $('#product_group').val('');
        $('#brand_name').val('');
        $('#composition_text').val('');
        $('#description_text').val('');
        if (selectedProductPhotoImg) {
            selectedProductPhotoImg.src = '';
        }
        if (selectedProductPhotoWrap) {
            selectedProductPhotoWrap.style.display = 'none';
        }
        setTagifyCategories([]);
    }

    function applyProductSelection(item) {
        productIdInput.value = item.id || '';
        productNameInput.dataset.selectedId = item.id || '';
        productNameInput.value = getDisplayName(item);
        if (item.role)  $('#drug_role').val(item.role); else $('#drug_role').val('');
        if (item.group) $('#product_group').val(item.group); else $('#product_group').val('');
        $('#brand_name').val(item.name || '');
        $('#composition_text').val(item.composition || '');
        $('#description_text').val(item.description || '');
        if (selectedProductPhotoImg) {
            selectedProductPhotoImg.src = item.image || '';
        }
        if (selectedProductPhotoWrap) {
            selectedProductPhotoWrap.style.display = item.image ? 'block' : 'none';
        }
        const cats = Array.isArray(item.categories) ? item.categories.filter(Boolean) : [];
        setTagifyCategories(cats);
    }

    function resolveProductFromInput() {
        const value = (productNameInput.value || '').trim();
        if (!value) {
            clearAutoProductFields();
            return;
        }

        const lowerValue = value.toLowerCase();
        const selectedId = Number(productNameInput.dataset.selectedId || 0);
        const selectedById = selectedId
            ? productItems.find(item => Number(item.id) === selectedId)
            : null;

        if (selectedById && lowerValue === getDisplayName(selectedById).toLowerCase()) {
            applyProductSelection(selectedById);
            return;
        }

        const nameMatch = productItems.find(item => (item.name || '').trim().toLowerCase() === lowerValue);
        if (nameMatch) {
            applyProductSelection(nameMatch);
            return;
        }

        const drugMatches = productItems.filter(item => getDisplayName(item).toLowerCase() === lowerValue);
        if (drugMatches.length === 1) {
            applyProductSelection(drugMatches[0]);
            return;
        }

        if (drugMatches.length > 1 && selectedById) {
            const sameItem = drugMatches.find(item => Number(item.id) === Number(selectedById.id));
            if (sameItem) {
                applyProductSelection(sameItem);
                return;
            }
        }

        clearAutoProductFields();
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!productNameInput || !productPickerDropdown) {
            return;
        }

        productNameInput.addEventListener('input', function () {
            const selectedId = Number(productNameInput.dataset.selectedId || 0);
            if (selectedId) {
                const selectedItem = productItems.find(item => Number(item.id) === selectedId);
                if (!selectedItem || (productNameInput.value || '').trim().toLowerCase() !== getDisplayName(selectedItem).toLowerCase()) {
                    productNameInput.dataset.selectedId = '';
                    productIdInput.value = '';
                }
            }
            const matched = filterProducts(productNameInput.value);
            renderProductDropdown(matched);
            showDropdown();
        });

        productNameInput.addEventListener('focus', function () {
            const matched = filterProducts(productNameInput.value);
            renderProductDropdown(matched);
            showDropdown();
        });

        productNameInput.addEventListener('change', resolveProductFromInput);
        productNameInput.addEventListener('blur', function () {
            setTimeout(() => {
                resolveProductFromInput();
                hideDropdown();
            }, 120);
        });

        if (productPickerToggle) {
            productPickerToggle.addEventListener('click', function () {
                if (productPickerDropdown.style.display === 'block') {
                    hideDropdown();
                    return;
                }
                const matched = filterProducts(productNameInput.value);
                renderProductDropdown(matched);
                showDropdown();
                productNameInput.focus();
            });
        }

        productPickerDropdown.addEventListener('mousedown', function (event) {
            const option = event.target.closest('.autocomplete-option');
            if (!option) return;

            const selectedId = Number(option.dataset.id);
            const selectedItem = productItems.find(item => Number(item.id) === selectedId);
            if (!selectedItem) return;

            applyProductSelection(selectedItem);
            hideDropdown();
        });

        document.addEventListener('click', function (event) {
            const inside = event.target.closest('.product-autocomplete');
            if (!inside) {
                hideDropdown();
            }
        });

        if (selectedProductPhotoImg && productPhotoModalImg) {
            selectedProductPhotoImg.addEventListener('click', function () {
                if (!selectedProductPhotoImg.src) return;
                productPhotoModalImg.src = selectedProductPhotoImg.src;
                $('#productPhotoModal').modal('show');
            });
        }
    });

    function clearProductDetails() {
        renderProductDropdown([]);
        hideDropdown();
        productNameInput.value = '';
        clearAutoProductFields();
        // $('#pack_size').val('');
        // $('#quantity').val('');
        // $('#composition_text').val('');
    }

    categoryRadios.forEach(r => r.addEventListener('change', () => {
        clearProductDetails();
        fetchProducts(productNameInput.value);
    }));
    fetchProducts();

    document.addEventListener('DOMContentLoaded', function () {
        if (AIZ && AIZ.plugins && AIZ.plugins.tagify) {
            AIZ.plugins.tagify();
        } else if (window.Tagify) {
            document.querySelectorAll('.aiz-tag-input').forEach(el => {
                if (!$(el).data('tagify')) {
                    const inst = new Tagify(el);
                    $(el).data('tagify', inst);
                }
            });
        }
    });

    // --- Dependent country -> state for company section
    const companyCountry = document.getElementById('company_country');
    const companyState   = document.getElementById('company_state');
    const companyPincode = document.getElementById('company_pincode');
    const gstNoInput = document.getElementById('common_gst_no');
    const companyNameInput = document.getElementById('company_name');
    const companyDistrictInput = document.getElementById('company_district');
    const companyPostInput = document.getElementById('company_post');
    const companyAddressInput = document.getElementById('company_address');
    const contactPersonInput = document.getElementById('contact_person');
    const designationInput = document.getElementById('designation');
    const mobileCountryCodeInput = document.getElementById('mobile_country_code');
    const mobileNumberInput = document.getElementById('mobile_number');
    const companyEmailInput = document.getElementById('company_email');

    async function loadStates(countryId, stateSelect, selectedStateId = null) {
        if (!stateSelect) return;
        const body = new URLSearchParams({country_id: countryId || ''});
        body.append('_token', '{{ csrf_token() }}');
        const res = await fetch('{{ route('get-state') }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body
        });
        let html = await res.text();
        // Handle controllers that return JSON-encoded HTML (\"<option>...\") or raw HTML
        try {
            if (typeof html === 'string' && html.trim().startsWith('"')) {
                html = JSON.parse(html);
            }
        } catch (e) {}
        html = html.replace(/<\\\//g, '</').replace(/\\"/g, '"');
        stateSelect.innerHTML = html;
        if (selectedStateId) {
            stateSelect.value = selectedStateId;
        }
        $(stateSelect).selectpicker('refresh');
    }

    companyCountry?.addEventListener('change', () => {
        loadStates(companyCountry.value, companyState);
    });

    // Auto-detect by pincode
    async function resolveByPincode() {
        const pin = companyPincode?.value?.trim();
        if (!pin || pin.length < 4) return;
        const body = new URLSearchParams({
            postal_code: pin,
            country_id: companyCountry.value || '',
            _token: '{{ csrf_token() }}'
        });
        const res = await fetch('{{ route('get-location') }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body
        });
        const data = await res.json();
        if (data.country_id) {
            companyCountry.value = data.country_id;
            $(companyCountry).selectpicker('refresh');
            await loadStates(data.country_id, companyState, data.state_id);
        } else if (data.state_id) {
            await loadStates(companyCountry.value, companyState, data.state_id);
        }
    }

    companyPincode?.addEventListener('blur', resolveByPincode);

    async function fillCompanyDetailsByGst() {
        if (!gstNoInput) return;

        const gstNo = (gstNoInput.value || '').trim().toUpperCase();
        gstNoInput.value = gstNo;
        if (!/^[0-9A-Z]{15}$/.test(gstNo)) {
            return;
        }

        try {
            const params = new URLSearchParams({ gst_no: gstNo });
            const res = await fetch('{{ route('form_enquiry.gst_details') }}?' + params.toString(), {
                headers: { 'Accept': 'application/json' }
            });
            const payload = await res.json();
            if (!res.ok || !payload?.success || !payload?.data) {
                return;
            }

            const data = payload.data;

            if (companyNameInput && data.company_name) {
                companyNameInput.value = data.company_name;
            }
            if (companyAddressInput && data.company_address) {
                companyAddressInput.value = data.company_address;
            }
            if (companyDistrictInput && data.company_district) {
                companyDistrictInput.value = data.company_district;
            }
            if (companyPostInput && data.company_post) {
                companyPostInput.value = data.company_post;
            }
            if (companyPincode && data.company_pincode) {
                companyPincode.value = data.company_pincode;
            }
            if (contactPersonInput && data.contact_person) {
                contactPersonInput.value = data.contact_person;
            }
            if (designationInput && data.designation) {
                designationInput.value = data.designation;
            }
            if (mobileCountryCodeInput && data.mobile_country_code) {
                mobileCountryCodeInput.value = data.mobile_country_code;
            }
            if (mobileNumberInput && data.mobile_number) {
                mobileNumberInput.value = data.mobile_number;
            }
            if (companyEmailInput && data.email) {
                companyEmailInput.value = data.email;
            }

        } catch (error) {
            // Keep form usable even if GST API fails.
        }
    }

    gstNoInput?.addEventListener('blur', fillCompanyDetailsByGst);

    // initial load if country preselected
    if (companyCountry && companyCountry.value) {
        loadStates(companyCountry.value, companyState, '{{ old('company_state_id') }}');
    }
</script>
@endsection
