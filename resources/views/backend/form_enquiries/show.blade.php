@extends('backend.layouts.app')

@section('content')
    @php
        use App\Models\Upload;
        $loadFiles = function ($csv) {
            $ids = array_filter(array_map('trim', explode(',', (string) $csv)));
            return $ids ? Upload::withoutGlobalScopes(['not_hidden'])->whereIn('id', $ids)->get() : collect();
        };

        $files = [
            'composition_files'         => $loadFiles($item->composition_files),
            'gov_tender_files'          => $loadFiles($item->gov_tender_files),
            'gov_required_docs'         => $loadFiles($item->gov_required_docs),
            'gov_authorisation_files'   => $loadFiles($item->gov_authorisation_files),
            'export_iec_files'          => $loadFiles($item->export_iec_files),
            'export_design_files'       => $loadFiles($item->export_design_files),
            'export_required_docs'      => $loadFiles($item->export_required_docs),
            'export_authorisation_files'=> $loadFiles($item->export_authorisation_files),
            'tp_trademark_files'        => $loadFiles($item->tp_trademark_files),
            'tp_undertaking_files'      => $loadFiles($item->tp_undertaking_files),
            'tp_drug_approval_files'    => $loadFiles($item->tp_drug_approval_files),
            'tp_design_files'           => $loadFiles($item->tp_design_files),
            'loan_trademark_files'      => $loadFiles($item->loan_trademark_files),
            'loan_undertaking_files'    => $loadFiles($item->loan_undertaking_files),
            'loan_drug_approval_files'  => $loadFiles($item->loan_drug_approval_files),
            'loan_design_files'         => $loadFiles($item->loan_design_files),
            'common_product_photos'     => $loadFiles($item->common_product_photos),
            'common_product_list_files' => $loadFiles($item->common_product_list_files),
            'common_drug_licence_files' => $loadFiles($item->common_drug_licence_files),
            'common_gst_files'          => $loadFiles($item->common_gst_files),
            'common_aadhar_files'       => $loadFiles($item->common_aadhar_files),
            'visiting_card_files'       => $loadFiles($item->visiting_card_files),
        ];

        $showFiles = function ($list) {
            if ($list->isEmpty()) {
                return '<span class="text-muted">-</span>';
            }
            return $list->map(function ($file) {
                $name = $file->file_original_name ?? $file->file_name;
                return '<a class="d-block" target="_blank" href="'.uploaded_asset($file->id).'">'.e($name).'</a>';
            })->implode('');
        };

        $forLabel = [
            'domestic'     => translate('Domestic'),
            'govt_supply'  => translate('Govt. Supply'),
            'exports'      => translate('Exports'),
            'third_party'  => translate('Third Party Manufacturing'),
            'loan_licence' => translate('Loan Licence Manufacturing'),
        ][$item->domestic_type] ?? str_replace('_', ' ', ucfirst((string) $item->domestic_type));
    @endphp

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 h6">{{ translate('Form Details') }}</h5>
                <span class="badge badge-inline badge-secondary">{{ $item->form_code }}</span>
            </div>
            <a href="{{ route('form_enquiries.index') }}" class="btn btn-soft-secondary btn-sm">{{ translate('Back') }}</a>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <p class="mb-1 text-muted">{{ translate('Type') }}</p>
                    <h6 class="fw-700">{{ ucfirst($item->type) }}</h6>
                </div>
                <div class="col-md-3">
                    <p class="mb-1 text-muted">{{ translate('For') }}</p>
                    <h6 class="fw-700">{{ $forLabel }}</h6>
                </div>
                <div class="col-md-3">
                    <p class="mb-1 text-muted">{{ translate('Category') }}</p>
                    <h6 class="fw-700">{{ ucfirst($item->category) }}</h6>
                </div>
                <div class="col-md-3">
                    <p class="mb-1 text-muted">{{ translate('Date') }}</p>
                    <h6 class="fw-700">{{ $item->created_at->format('d M Y') }}</h6>
                </div>
            </div>

            <h6 class="border-bottom pb-2 mb-3">{{ translate('Product Details') }}</h6>
            <div class="row">
                <div class="col-md-4">
                    <p class="mb-1 text-muted">{{ translate('Product Name') }}</p>
                    <div class="fw-600">{{ $item->product_name }}</div>
                    @if($item->product_id)
                        <small class="text-muted">{{ translate('Linked Product ID') }}: {{ $item->product_id }}</small>
                    @endif
                </div>
                <div class="col-md-4">
                    <p class="mb-1 text-muted">{{ translate('Drug Role') }}</p>
                    <div class="fw-600">{{ $item->drug_role ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <p class="mb-1 text-muted">{{ translate('Product Group') }}</p>
                    <div class="fw-600">{{ $item->product_group ?? '-' }}</div>
                </div>
                <div class="col-md-4 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Brand Name') }}</p>
                    <div class="fw-600">{{ $item->brand_name ?? '-' }}</div>
                </div>
                <div class="col-md-4 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Product Category') }}</p>
                    <div class="fw-600">{{ $item->product_categories ?? '-' }}</div>
                </div>
                <div class="col-md-2 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Pack Size') }}</p>
                    <div class="fw-600">{{ $item->pack_size ?? '-' }}</div>
                </div>
                <div class="col-md-2 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Quantity') }}</p>
                    <div class="fw-600">{{ $item->quantity ?? '-' }}</div>
                </div>
                <div class="col-md-6 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Composition') }}</p>
                    <div class="fw-600">{!! nl2br(e($item->composition_text ?? '-')) !!}</div>
                </div>
                <div class="col-md-6 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Descriptions') }}</p>
                    <div class="fw-600">{!! nl2br(e($item->description_text ?? '-')) !!}</div>
                </div>
                <div class="col-12 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Composition Files') }}</p>
                    <div class="mt-2">{!! $showFiles($files['composition_files']) !!}</div>
                </div>
            </div>

            <h6 class="border-bottom pb-2 my-4">{{ translate('For Details') }}</h6>
            @if($item->domestic_type === 'govt_supply')
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">{{ translate('Tender / Bid No') }}</p>
                        <div class="fw-600">{{ $item->gov_tender_no ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">{{ translate('State') }}</p>
                        <div class="fw-600">{{ $item->govState?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">{{ translate('Department') }}</p>
                        <div class="fw-600">{{ $item->gov_department ?? '-' }}</div>
                    </div>
                    <div class="col-md-3 mt-3">
                        <p class="mb-1 text-muted">{{ translate('Start Date') }}</p>
                        <div class="fw-600">{{ format_dd_mm_yy($item->gov_start_date) }}</div>
                    </div>
                    <div class="col-md-3 mt-3">
                        <p class="mb-1 text-muted">{{ translate('End Date') }}</p>
                        <div class="fw-600">{{ format_dd_mm_yy($item->gov_end_date) }}</div>
                    </div>
                    <div class="col-md-6 mt-3">
                        <p class="mb-1 text-muted">{{ translate('Tender / Bid Form') }}</p>
                        {!! $showFiles($files['gov_tender_files']) !!}
                    </div>
                    <div class="col-md-6 mt-3">
                        <p class="mb-1 text-muted">{{ translate('List Of Documents Required') }}</p>
                        {!! $showFiles($files['gov_required_docs']) !!}
                    </div>
                    <div class="col-md-6 mt-3">
                        <p class="mb-1 text-muted">{{ translate('Authorisation Letter Format') }}</p>
                        {!! $showFiles($files['gov_authorisation_files']) !!}
                    </div>
                </div>
            @elseif($item->domestic_type === 'exports')
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1 text-muted">{{ translate('Country') }}</p>
                        <div class="fw-600">{{ $item->exportCountry?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6 mt-3">
                        <p class="mb-1 text-muted">{{ translate('Valid IEC Certificate') }}</p>
                        {!! $showFiles($files['export_iec_files']) !!}
                    </div>
                    <div class="col-md-6 mt-3">
                        <p class="mb-1 text-muted">{{ translate('Design File') }}</p>
                        {!! $showFiles($files['export_design_files']) !!}
                    </div>
                    <div class="col-md-6 mt-3">
                        <p class="mb-1 text-muted">{{ translate('List Of Documents Required') }}</p>
                        {!! $showFiles($files['export_required_docs']) !!}
                    </div>
                    <div class="col-md-6 mt-3">
                        <p class="mb-1 text-muted">{{ translate('Authorisation Letter Format') }}</p>
                        {!! $showFiles($files['export_authorisation_files']) !!}
                    </div>
                </div>
            @elseif($item->domestic_type === 'third_party')
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">{{ translate('Brand Name') }}</p>
                        <div class="fw-600">{{ $item->tp_brand_name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">{{ translate('Trade Mark Certificate / Undertaking Form') }}</p>
                        {!! $showFiles($files['tp_trademark_files']) !!}
                    </div>
                    <div class="col-md-6 mt-3">
                        <p class="mb-1 text-muted">{{ translate('Undertaking Form') }}</p>
                        {!! $showFiles($files['tp_undertaking_files']) !!}
                    </div>
                    <div class="col-md-6 mt-3">
                        <p class="mb-1 text-muted">{{ translate('Drug Approval Copy') }}</p>
                        {!! $showFiles($files['tp_drug_approval_files']) !!}
                    </div>
                    <div class="col-md-6 mt-3">
                        <p class="mb-1 text-muted">{{ translate('Design File') }}</p>
                        {!! $showFiles($files['tp_design_files']) !!}
                    </div>
                </div>
            @elseif($item->domestic_type === 'loan_licence')
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">{{ translate('Brand Name') }}</p>
                        <div class="fw-600">{{ $item->loan_brand_name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1 text-muted">{{ translate('Trade Mark Certificate / Undertaking Form') }}</p>
                        {!! $showFiles($files['loan_trademark_files']) !!}
                    </div>
                    <div class="col-md-6 mt-3">
                        <p class="mb-1 text-muted">{{ translate('Undertaking Form') }}</p>
                        {!! $showFiles($files['loan_undertaking_files']) !!}
                    </div>
                    <div class="col-md-6 mt-3">
                        <p class="mb-1 text-muted">{{ translate('Drug Approval Copy') }}</p>
                        {!! $showFiles($files['loan_drug_approval_files']) !!}
                    </div>
                    <div class="col-md-6 mt-3">
                        <p class="mb-1 text-muted">{{ translate('Design File') }}</p>
                        {!! $showFiles($files['loan_design_files']) !!}
                    </div>
                </div>
            @elseif($item->domestic_type === 'domestic')
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-light mb-0">
                            {{ translate('No additional fields for Domestic.') }}
                        </div>
                    </div>
                </div>
            @endif

            <h6 class="border-bottom pb-2 my-4">{{ translate('Common Documents') }}</h6>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1 text-muted">{{ translate('Product Photo') }}</p>
                    {!! $showFiles($files['common_product_photos']) !!}
                </div>
                <div class="col-md-6">
                    <p class="mb-1 text-muted">{{ translate('List Of Products') }}</p>
                    {!! $showFiles($files['common_product_list_files']) !!}
                </div>
                <div class="col-md-6 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Valid Drug Licence') }}</p>
                    {!! $showFiles($files['common_drug_licence_files']) !!}
                </div>
                <div class="col-md-6 mt-3">
                    <p class="mb-1 text-muted">{{ translate('GST No.') }}</p>
                    <div class="fw-600">{{ $item->common_gst_no ?? '-' }}</div>
                </div>
                <div class="col-md-6 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Valid GST Certificate') }}</p>
                    {!! $showFiles($files['common_gst_files']) !!}
                </div>
                <div class="col-md-6 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Aadhar No.') }}</p>
                    <div class="fw-600">{{ $item->common_aadhar_no ?? '-' }}</div>
                </div>
                <div class="col-md-6 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Aadhar Card') }}</p>
                    {!! $showFiles($files['common_aadhar_files']) !!}
                </div>
                <div class="col-md-6 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Special Instruction / Suggestion') }}</p>
                    <div class="fw-600">{{ $item->special_instruction ?? '-' }}</div>
                </div>
            </div>

            <h6 class="border-bottom pb-2 my-4">{{ translate('Company Details') }}</h6>
            <div class="row">
                <div class="col-md-4">
                    <p class="mb-1 text-muted">{{ translate('Company Name') }}</p>
                    <div class="fw-600">{{ $item->company_name ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <p class="mb-1 text-muted">{{ translate('Contact Person') }}</p>
                    <div class="fw-600">{{ $item->contact_person ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <p class="mb-1 text-muted">{{ translate('Designation') }}</p>
                    <div class="fw-600">{{ $item->designation ?? '-' }}</div>
                </div>
                <div class="col-md-4 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Mobile') }}</p>
                    <div class="fw-600">{{ trim($item->mobile_country_code.' '.$item->mobile_number) }}</div>
                </div>
                <div class="col-md-4 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Email') }}</p>
                    <div class="fw-600">{{ $item->email ?? '-' }}</div>
                </div>
                <div class="col-md-4 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Website') }}</p>
                    <div class="fw-600">{{ $item->website ?? '-' }}</div>
                </div>
                <div class="col-md-8 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Address') }}</p>
                    <div class="fw-600">{!! nl2br(e($item->company_address ?? '-')) !!}</div>
                    <div class="text-muted small">
                        {{ $item->company_post }} {{ $item->company_district }} {{ $item->companyState?->name }} {{ $item->company_pincode }} {{ $item->companyCountry?->name }}
                    </div>
                </div>
                <div class="col-md-4 mt-3">
                    <p class="mb-1 text-muted">{{ translate('Visiting Card') }}</p>
                    {!! $showFiles($files['visiting_card_files']) !!}
                </div>
            </div>
        </div>
    </div>
@endsection
