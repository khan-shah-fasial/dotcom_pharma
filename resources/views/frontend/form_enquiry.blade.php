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
            border: 2px solid #e5e7eb;
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
        
        .text-right {
            margin-top: 30px;
            padding-top: 24px;
            border-top: 2px solid #e5e7eb;
        }
        .gap-3
        {
            gap:10px;
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
        
    </style>

    <section class="py-4 bg-light">
        <div class="container enq-shell">
            <div class="enq-card">
                <div class="enq-head text-center">
                    <h1 class="h5 fw-700 mb-1">{{ translate('Product Enquiry / Suggestion Form') }}</h1>
                    <p class="text-white mb-0">{{ translate('Fill the fields carefully. Uploads stay visible only to admin for review.') }}</p>
                </div>
                <div class="p-4">
                    <form id="form-enquiry" class="form-default" action="{{ route('form_enquiry.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4 form-group-spacing">
                                <label class="label-strong">{{ translate('Type') }}</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="aiz-megabox">
                                        <input type="radio" name="type" value="enquiry" checked>
                                        <span class="d-flex align-items-center">
                                            <span class="aiz-rounded-check"></span>
                                            <span class="ml-2">{{ translate('Enquiry') }}</span>
                                        </span>
                                    </label>
                                    <label class="aiz-megabox">
                                        <input type="radio" name="type" value="suggestion">
                                        <span class="d-flex align-items-center">
                                            <span class="aiz-rounded-check"></span>
                                            <span class="ml-2">{{ translate('Suggestion') }}</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 form-group-spacing">
                                <label class="label-strong">{{ translate('Form No (Auto)') }}</label>
                                <input type="text" class="form-control" name="form_code_display" id="form_code_display" value="{{ $nextCodes['enquiry'] ?? '' }}" readonly>
                                <input type="hidden" name="form_code_visual" id="form_code_visual" value="{{ $nextCodes['enquiry'] ?? '' }}">
                                <input type="hidden" id="next_enquiry_code" value="{{ $nextCodes['enquiry'] ?? '' }}">
                                <input type="hidden" id="next_suggestion_code" value="{{ $nextCodes['suggestion'] ?? '' }}">
                            </div>
                            <div class="col-md-4 form-group-spacing">
                                <label class="label-strong">{{ translate('Date') }}</label>
                                <input type="date" class="form-control" value="{{ $today }}" readonly>
                            </div>

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
                            <div class="col-md-8 form-group-spacing">
                                <label class="label-strong">{{ translate('For Domestic') }}</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="aiz-megabox">
                                        <input type="radio" name="domestic_type" value="govt_supply" checked>
                                        <span class="d-flex align-items-center"><span class="aiz-rounded-check"></span><span class="ml-2">{{ translate('Govt. Supply') }}</span></span>
                                    </label>
                                    <label class="aiz-megabox">
                                        <input type="radio" name="domestic_type" value="exports">
                                        <span class="d-flex align-items-center"><span class="aiz-rounded-check"></span><span class="ml-2">{{ translate('Exports') }}</span></span>
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


                        <div class="enq-section mt-4">
                            <div class="section-title">{{ translate('Product Details') }}</div>
                            <div class="enq-body">
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="label-strong">{{ translate('Product Name') }}</label>
                                        <div class="row">
                                            <div class="col-md-6 mb-2 mb-md-0">
                                                <select id="product_picker" class="form-control aiz-selectpicker" data-live-search="true">
                                                    <option value="">{{ translate('Select from list') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" class="form-control" name="product_name" id="product_name" placeholder="{{ translate('Type product name') }}" required>
                                                <input type="hidden" name="product_id" id="product_id">
                                            </div>
                                        </div>
                                        <div class="helper">{{ translate('If not in list, type manually. Selecting a product will auto-fill role, group, brand & categories.') }}</div>
                                    </div>
                                    <div class="col-md-5">
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
                                    <div class="col-md-4">
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
                                    <div class="col-md-2">
                                        <label class="label-strong">{{ translate('Pack Size') }}</label>
                                        <input type="number" class="form-control" id="pack_size" name="pack_size" min="0" placeholder="{{ translate('Pack size') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="label-strong">{{ translate('Qty Required') }}</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" min="0" placeholder="{{ translate('Qty') }}">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="label-strong">{{ translate('Full Composition / Descriptions') }}</label>
                                        <textarea class="form-control" rows="3" id="composition_text" name="composition_text" placeholder="{{ translate('Describe composition') }}"></textarea>
                                    </div>
                                    <div class="col-md-4 upload-col">
                                        <label class="label-strong">{{ translate('Upload File') }}</label>
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
                            <div class="section-title">{{ translate('Common Field For All') }}</div>
                            <div class="enq-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Product Photo (If You Have)') }}</label>
                                        <input type="file" class="form-control" name="common_product_photos[]" multiple accept="image/*">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('List Of Products (If More Than One)') }}</label>
                                        <input type="file" class="form-control" name="common_product_list_files[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Valid All Drug Licence') }}</label>
                                        <input type="file" class="form-control" name="common_drug_licence_files[]" multiple>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Valid GST Certificate') }}</label>
                                        <input type="file" class="form-control" name="common_gst_files[]" multiple>
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
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Company Name') }}</label>
                                        <input type="text" class="form-control" name="company_name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="label-strong">{{ translate('Full Address') }}</label>
                                        <textarea class="form-control" name="company_address" rows="2"></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('Post') }}</label>
                                        <input type="text" class="form-control" name="company_post">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('District') }}</label>
                                        <input type="text" class="form-control" name="company_district">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('Country') }}</label>
                                        <select class="form-control aiz-selectpicker" data-live-search="true" name="company_country_id" id="company_country">
                                            <option value="">{{ translate('Select Country') }}</option>
                                            @foreach($countries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('State') }}</label>
                                        <select class="form-control aiz-selectpicker" data-live-search="true" name="company_state_id" id="company_state">
                                            <option value="">{{ translate('Select State') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('Pincode') }}</label>
                                        <input type="text" class="form-control" name="company_pincode" id="company_pincode">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('Contact Person') }} *</label>
                                        <input type="text" class="form-control" name="contact_person" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('Designation') }}</label>
                                        <input type="text" class="form-control" name="designation">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('Mobile No *') }}</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" style="     max-width: 70px !important;    border-radius: 5px 0px 0px 5px !important;" name="mobile_country_code" value="+91">
                                            <input type="tel" class="form-control" name="mobile_number" required placeholder="{{ translate('Enter number') }}" style="border-radius: 0px 5px 5px 0px !important;">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('E-mail ID *') }}</label>
                                        <input type="email" class="form-control" name="email" required placeholder="name@example.com">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('Website') }}</label>
                                        <input type="text" class="form-control" name="website" placeholder="https://">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="label-strong">{{ translate('Visiting Card') }}</label>
                                        <input type="file" class="form-control" name="visiting_card_files[]" multiple>
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
@endsection
@section('script')
<script>
    const productPicker = document.getElementById('product_picker');
    const productNameInput = document.getElementById('product_name');
    const productIdInput = document.getElementById('product_id');
    const categoryRadios = document.querySelectorAll('input[name="category"]');
    const domesticRadios = document.querySelectorAll('input[name="domestic_type"]');
    const domesticSections = document.querySelectorAll('.domestic-section');

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
    document.querySelectorAll('input[name="type"]').forEach(r => r.addEventListener('change', setFormCode));

    function fetchProducts(q = '') {
        const category = document.querySelector('input[name="category"]:checked').value;
        const params = new URLSearchParams({category, q});
        fetch('{{ route('form_enquiry.products') }}?' + params.toString())
            .then(res => res.json())
            .then(items => {
                productPicker.innerHTML = '<option value="">{{ translate('Select from list') }}</option>';
                items.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name;
                    option.dataset.role = item.role || '';
                    option.dataset.group = item.group || '';
                    option.dataset.brand = item.brand || '';
                    option.dataset.categories = item.categories ? item.categories.join('|') : '';
                    productPicker.appendChild(option);
                });
                $('.aiz-selectpicker').selectpicker('refresh');
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!productPicker) {
            console.dir('productPicker element not found');
            return;
        }

        // Bind both Bootstrap-select and fallback change for robustness
        const onProductChange = function () {
            const rawVal = $(productPicker).val();
            const selectedVal = Array.isArray(rawVal) ? rawVal[0] : rawVal;
            const option = selectedVal ? productPicker.querySelector(`option[value="${selectedVal}"]`) : productPicker.querySelector('option:checked');
            if (!option || !option.value) {
                console.dir('product-select: no option found', { rawVal, selectedVal, option });
                productIdInput.value = '';
                return;
        }
        // Log selection for debugging
        console.dir({
            context: 'product-select',
            id: option.value,
            name: option.textContent
        });
        // AJAX load details and prefill
        /*$.getJSON('{{ url('/form-enquiry/product') }}/' + option.value, function (data) {
            console.dir({ context: 'product-select-success', data });
            productIdInput.value = data.id || option.value;
            productNameInput.value = data.name || option.textContent;
            if (data.role)  $('#drug_role').val(data.role);
            if (data.group) $('#product_group').val(data.group);
            if (data.brand) $('#brand_name').val(data.brand);

            const cats = data.categories || [];
            const $tagInput = $('#product_categories');
            const tagInstance = $tagInput.data('tagify');
            if (tagInstance) {
                console.dir({ context: 'tagify-existing', cats });
                tagInstance.removeAllTags();
                tagInstance.addTags(cats);
            } else if (window.Tagify) {
                const inst = new Tagify($tagInput[0]);
                console.dir({ context: 'tagify-new', cats });
                inst.addTags(cats);
                $tagInput.data('tagify', inst);
            } else {
                $tagInput.val(JSON.stringify(cats));
            }
        }).fail(function (jq, status, err) {*/
            // console.error('product-select-fail', status, err);
            // fallback to dataset
            productIdInput.value = option.value;
            productNameInput.value = option.textContent;
            if (option.dataset.role)  $('#drug_role').val(option.dataset.role);
            if (option.dataset.group) $('#product_group').val(option.dataset.group);
            if (option.dataset.brand) $('#brand_name').val(option.dataset.brand);
            const cats = option.dataset.categories ? option.dataset.categories.split('|').filter(Boolean) : [];
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
                $tagInput.val(JSON.stringify(cats));
            }
        // });
        };

        // Avoid double firing: prefer bootstrap-select event when plugin is present, fallback to native change otherwise.
        if ($.fn.selectpicker && $(productPicker).hasClass('aiz-selectpicker')) {
            $(productPicker).on('changed.bs.select', onProductChange);
        } else {
            $(productPicker).on('change', onProductChange);
        }

        console.log('productPicker bindings attached');
    });

    function clearProductDetails() {
        productPicker.innerHTML = '<option value="">{{ translate('Select from list') }}</option>';
        $('.aiz-selectpicker').selectpicker('refresh');
        productIdInput.value = '';
        productNameInput.value = '';
        $('#drug_role').val('');
        $('#product_group').val('');
        $('#brand_name').val('');
        const $tagInput = $('#product_categories');
        const tagInstance = $tagInput.data('tagify');
        if (tagInstance) {
            tagInstance.removeAllTags();
        } else {
            $tagInput.val('');
        }
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
    // initial load if country preselected
    if (companyCountry && companyCountry.value) {
        loadStates(companyCountry.value, companyState, '{{ old('company_state_id') }}');
    }
</script>
@endsection
