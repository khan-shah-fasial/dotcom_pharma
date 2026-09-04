@extends('backend.layouts.app')

@section('content')
    <style>
        .order-sidebar-accordion .card { margin-bottom: 10px; }
        .order-accordion-toggle {
            display: flex; width: 100%; align-items: center; justify-content: space-between;
            padding: 14px 20px; color: #1f2937; background: transparent; border: 0;
            font-weight: 600; text-align: left; cursor: pointer;
        }
        .order-accordion-toggle:hover, .order-accordion-toggle:focus { color: var(--primary); outline: 0; }
        .order-accordion-toggle .order-accordion-icon { transition: transform .2s ease; }
        .order-accordion-toggle[aria-expanded="true"] .order-accordion-icon { transform: rotate(180deg); }
        .order-sidebar-accordion .form-group > label {
            display: inline-block;
            max-width: 100%;
            font-size: 12px;
            font-weight: 400;
            line-height: 18px;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: top;
        }
        .preview-section-title {
            margin: 0 0 8px;
            color: #74788d;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        #excel-path-chip {
            display: block;
            margin: -4px 0 10px;
            color: #74788d;
            font-size: 11px;
            font-weight: 400;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dimension-inputs {
            display: grid;
            grid-template-columns: 1fr auto 1fr auto 1fr auto;
            align-items: center;
            gap: 5px;
        }
        .dimension-inputs .dimension-separator { color: #74788d; font-weight: 700; }
        .selected-file-list { min-height: 0; padding: 0; border: 0; background: transparent; }
        .selected-file-list.has-files {
            min-height: 42px; padding: 7px; border: 1px solid #e2e5ec; border-radius: 4px; background: #f8f9fa;
        }
        .selected-location-hover { position: relative; display: inline-block; max-width: 100%; margin-top: 7px; }
        .selected-location-name {
            display: inline-flex; align-items: center; max-width: 100%; padding: 5px 9px;
            color: #2563eb; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; cursor: help;
        }
        .selected-location-card {
            display: none; position: absolute; right: 0; z-index: 1080; width: min(420px, 85vw);
            max-height: 330px; overflow-y: auto; padding: 12px; background: #fff;
            border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 12px 30px rgba(15, 23, 42, .18);
        }
        .selected-location-hover:hover .selected-location-card,
        .selected-location-hover:focus-within .selected-location-card { display: block; }
        .selected-location-detail-row {
            display: grid; grid-template-columns: minmax(105px, 38%) minmax(0, 1fr);
            gap: 8px; padding: 4px 0; border-bottom: 1px solid #eef2f7; font-size: 12px;
        }
        .selected-location-name span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .apple-green-highlight {
            display: inline-flex; align-items: center; min-height: 34px; padding: 7px 12px;
            color: #14532d; background: linear-gradient(180deg, #ecfdf3 0%, #dcfce7 100%);
            border: 1px solid #86efac; border-radius: 10px; font-weight: 700;
        }
        .order-number-parts {
            display: grid;
            grid-template-columns: minmax(110px, 1.4fr) minmax(80px, .8fr) minmax(100px, 1fr) minmax(80px, .7fr);
            gap: 6px;
        }
        .order-number-part-label { display: block; margin-bottom: 3px; color: #74788d; font-size: 11px; }
        .order-details-company-group > label {
            min-height: 21px;
            margin-bottom: 6px;
        }
        .order-details-company-group .bootstrap-select { width: 100% !important; }
        .order-details-company-group .bootstrap-select > .dropdown-toggle,
        .order-number-parts .form-control {
            height: 36px;
            min-height: 36px;
            padding-top: 6px;
            padding-bottom: 6px;
            line-height: 22px;
        }
        .summary-tax-hover { position: relative; display: inline-flex; align-items: center; gap: 4px; cursor: help; }
        .summary-tax-hover-label { border-bottom: 1px dashed currentColor; }
        .summary-tax-tooltip {
            position: absolute; right: 0; bottom: calc(100% + 7px); z-index: 1050; display: none;
            min-width: 210px; padding: 8px 10px; background: #fff; border: 1px solid #dfe5ee;
            border-radius: 6px; box-shadow: 0 7px 20px rgba(20, 40, 80, .14);
        }
        .summary-tax-hover:hover .summary-tax-tooltip, .summary-tax-hover:focus .summary-tax-tooltip { display: block; }
        .summary-tax-tooltip-row { display: flex; justify-content: space-between; gap: 20px; padding: 3px 0; }
        @media (max-width: 420px) {
            .dimension-inputs { grid-template-columns: minmax(0, 1fr); }
            .dimension-inputs .dimension-separator { display: none; }
        }
    </style>

    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3">{{ translate($previewTitle) }}</h1>
                <p class="text-muted mb-0">{{ translate('Frontend preview only. Nothing is saved. Live Create Order is unchanged.') }}</p>
            </div>
            <div class="col-md-4 text-md-right mt-2 mt-md-0">
                <a href="{{ route('admin.previews.index') }}" class="btn btn-soft-secondary btn-sm">{{ translate('All previews') }}</a>
                <a href="{{ route('orders.create') }}" class="btn btn-soft-primary btn-sm">{{ translate('Live create order') }}</a>
            </div>
        </div>
    </div>

    <form id="shipping-preview-form" action="#" method="post">
        <div class="row gutters-10">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 h6">{{ translate('Order Details') }}</h5>
                        <span class="apple-green-highlight"><i class="las la-check-circle mr-1"></i>{{ translate('Current Status') }}: {{ translate('Pending') }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row gutters-5">
                            <div class="col-md-4 form-group order-details-company-group">
                                <label>{{ translate('Company') }} <span class="text-danger">*</span></label>
                                <span class="order-number-part-label">&nbsp;</span>
                                <select class="form-control aiz-selectpicker" name="company_id" data-live-search="true">
                                    <option value="">{{ translate('Select Company') }}</option>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}" data-code="{{ $company->code }}">{{ $company->company_name }} ({{ $company->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8 form-group">
                                <label>{{ translate('Order No') }} <span class="text-danger">*</span></label>
                                <div class="order-number-parts">
                                    <div>
                                        <span class="order-number-part-label">{{ translate('Company Code') }}</span>
                                        <input type="text" class="form-control" value="{{ optional($companies->first())->code }}" readonly>
                                    </div>
                                    <div>
                                        <span class="order-number-part-label">{{ translate('Code (Series)') }}</span>
                                        <input type="text" class="form-control" value="S" readonly>
                                    </div>
                                    <div>
                                        <span class="order-number-part-label">{{ translate('Financial Year') }}</span>
                                        <input type="text" class="form-control" value="2026-27" readonly>
                                    </div>
                                    <div>
                                        <span class="order-number-part-label">{{ translate('Number') }}</span>
                                        <input type="text" class="form-control" value="" readonly>
                                    </div>
                                </div>
                                <small class="text-muted">{{ translate('Preview only. The final sequential number is reserved when the order is saved.') }}</small>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{ translate('Order Date') }} <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="order_date" value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{ translate('Order Time') }} <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="order_time" value="{{ now()->format('H:i') }}">
                            </div>
                            <div class="col-md-4 form-group" id="domestic-invoice-fields">
                                <label>{{ translate('Reverse Charges') }}</label>
                                <select class="form-control" name="reverse_charge">
                                    <option value="">{{ translate('None') }}</option>
                                    <option value="0">{{ translate('No') }}</option>
                                    <option value="1">{{ translate('Yes') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>{{ translate('Invoice Type') }}</label>
                                <select class="form-control" id="preview-invoice-type">
                                    <option value="domestic">{{ translate('Domestic') }}</option>
                                    <option value="international">{{ translate('International') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Customer') }}</h5></div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label>{{ translate('Search Approved Customer') }}</label>
                            <input type="text" class="form-control" placeholder="{{ translate('Company, person, location, mobile, WhatsApp, pincode or salesman code') }}">
                            <small class="text-muted">{{ translate('Search is live on Create Order. This preview uses a sample customer.') }}</small>
                        </div>
                        <div class="alert alert-info mb-3">Nexgeno Medical Agency — Ahmedabad</div>
                        <div class="row gutters-10">
                            <div class="col-md-6">
                                <h6 class="mb-2">{{ translate('Billing Address') }}</h6>
                                <div class="border rounded p-3">
                                    <div class="fw-600 text-capitalize">Billing</div>
                                    <div>{{ translate('Contact Person') }}: Nexgeno Medical Agency</div>
                                    <div>12, Ashram Road</div>
                                    <small>Ahmedabad, Gujarat, India 380009<br>+91 98765 43210</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-2">{{ translate('Shipping Address') }}</h6>
                                <div class="border rounded p-3 mb-2">
                                    <div class="fw-600 mb-2">{{ translate('Same as Billing Address') }}</div>
                                    <label class="aiz-radio mr-3 mb-2">
                                        <input type="radio" name="shipping_same_as_billing" value="1" class="shipping-same-toggle" checked>
                                        <span>{{ translate('Yes') }}</span>
                                        <span class="aiz-rounded-check"></span>
                                    </label>
                                    <label class="aiz-radio mb-2">
                                        <input type="radio" name="shipping_same_as_billing" value="0" class="shipping-same-toggle">
                                        <span>{{ translate('No') }}</span>
                                        <span class="aiz-rounded-check"></span>
                                    </label>
                                    <div id="shipping-same-preview" class="small text-muted mt-2">12, Ashram Road, Ahmedabad</div>
                                </div>
                                <div id="shipping-address-options" class="d-none">
                                    <div class="border rounded p-3 mb-2">Godown — Naroda GIDC, Ahmedabad</div>
                                    <div class="border-top pt-3 mt-2">
                                        <label class="aiz-checkbox">
                                            <input type="checkbox" name="new_shipping_address" value="1" id="new-shipping-toggle">
                                            <span>{{ translate('Add additional shipping address') }}</span>
                                            <span class="aiz-square-check"></span>
                                        </label>
                                        <div class="small text-muted mb-2">{{ translate('The address will be saved to the selected customer when this order is created.') }}</div>
                                        <div id="new-shipping-fields" class="row gutters-10 d-none mt-2">
                                            @include('backend.sales.partials.create_order_address_fields', ['prefix' => 'shipping', 'countries' => $countries])
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Products') }}</h5></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>{{ translate('Search Product') }}</label>
                            <input type="text" class="form-control" placeholder="{{ translate('Product name or barcode') }}">
                            <small class="text-muted">{{ translate('Live create has the full product picker (variant, batch, quote). This preview shows the same line columns plus packing labels.') }}</small>
                        </div>
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered mb-0 packing-details-table">
                                <thead><tr><th>{{ translate('Packing Level') }}</th><th class="text-right">{{ translate('Qty') }}</th><th class="text-right">{{ translate('Weight (gm)') }}</th><th class="text-right">{{ translate('Dimensions (cm)') }}</th></tr></thead>
                                <tbody>
                                    <tr><td>{{ translate('Piece') }}</td><td class="text-right">1</td><td class="text-right">12</td><td class="text-right">10 × 8 × 4</td></tr>
                                    <tr><td>{{ translate('Buffer Box / Shrink Pack') }}</td><td class="text-right">10</td><td class="text-right">130</td><td class="text-right">20 × 16 × 10</td></tr>
                                    <tr><td>{{ translate('Buffer Boxes Per Case') }}</td><td class="text-right">10</td><td class="text-right">-</td><td class="text-right">-</td></tr>
                                    <tr><td>{{ translate('Per Case') }}</td><td class="text-right">100</td><td class="text-right">1400</td><td class="text-right">40 × 30 × 25</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-responsive mt-3">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ translate('Product') }}</th>
                                        <th>{{ translate('Variant') }}</th>
                                        <th>{{ translate('Batch') }}</th>
                                        <th class="text-right">{{ translate('Qty') }}</th>
                                        <th class="text-right">{{ translate('Scheme Qty (Free)') }}</th>
                                        <th class="text-right">{{ translate('Sale') }}</th>
                                        <th class="text-right">{{ translate('GST Amount') }}</th>
                                        <th class="text-right">{{ translate('Gross') }}</th>
                                        <th class="text-right">{{ translate('MRP') }}</th>
                                        <th class="text-right">{{ translate('Final') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Paracetamol 500mg Tab</td>
                                        <td>500mg</td>
                                        <td>BTH-01</td>
                                        <td class="text-right">10</td>
                                        <td class="text-right">0</td>
                                        <td class="text-right">1,200.000</td>
                                        <td class="text-right">144.000</td>
                                        <td class="text-right">1,344.000</td>
                                        <td class="text-right">1,500.000</td>
                                        <td class="text-right">1,344.000</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="order-sidebar-accordion" id="preview-sidebar-accordion">
                    <div class="card">
                        <div class="card-header p-0">
                            <button type="button" class="order-accordion-toggle" data-toggle="collapse" data-target="#additional-details-collapse" aria-expanded="true">
                                <span>{{ translate('Additional Details') }}</span>
                                <i class="las la-angle-down order-accordion-icon"></i>
                            </button>
                        </div>
                        <div id="additional-details-collapse" class="collapse show">
                            <div class="card-body">
                                <div class="form-group">
                                    <label>{{ translate('Sales Executive Name') }}</label>
                                    <select class="form-control aiz-selectpicker" name="sales_executive_id" data-live-search="true">
                                        <option value="">{{ translate('Select Sales Executive') }}</option>
                                        @foreach ($salesPeople as $staff)
                                            <option value="{{ $staff['id'] }}">{{ $staff['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @foreach ([
                                    ['name' => 'packed_by', 'label' => 'Packed By', 'staff' => $packedStaff],
                                    ['name' => 'checked_by', 'label' => 'Checked By', 'staff' => $checkedStaff],
                                    ['name' => 'billing_by', 'label' => 'Billing By', 'staff' => $billingStaff],
                                ] as $staffField)
                                    <div class="form-group">
                                        <label>{{ translate($staffField['label']) }}</label>
                                        <select class="form-control aiz-selectpicker" name="{{ $staffField['name'] }}" data-live-search="true">
                                            <option value="">{{ translate('Select Staff') }}</option>
                                            @foreach ($staffField['staff'] as $staff)
                                                <option value="{{ $staff['id'] }}">{{ $staff['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                                <div class="form-group">
                                    <label>{{ translate('Sales Man Code') }}</label>
                                    <input type="text" class="form-control" value="SM-1042" readonly>
                                    <small class="text-muted">{{ translate('Fetched automatically from Account Master.') }}</small>
                                </div>
                                <div class="preview-section-title border-top pt-3">{{ translate('P.O. Details') }}</div>
                                <div class="form-group">
                                    <label>{{ translate('P.O. No.') }}</label>
                                    <input type="text" class="form-control" name="po_number">
                                </div>
                                <div class="form-group mb-0">
                                    <label>{{ translate('P.O. Date') }}</label>
                                    <input type="date" class="form-control" name="po_date">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header p-0">
                            <button type="button" class="order-accordion-toggle" data-toggle="collapse" data-target="#shipping-collapse" aria-expanded="true">
                                <span>{{ translate('Shipping') }}</span>
                                <i class="las la-angle-down order-accordion-icon"></i>
                            </button>
                        </div>
                        <div id="shipping-collapse" class="collapse show">
                            <div class="card-body">
                                <div class="preview-section-title">{{ translate('AutoFill') }}</div>
                                <div class="form-group">
                                    <label>{{ translate('Total Cases') }}</label>
                                    <input type="number" min="0" step="1" class="form-control" name="cases" value="2">
                                </div>
                                <div class="form-group">
                                    <label>{{ translate('Net Weight (KG)') }}</label>
                                    <input type="number" min="0" step="0.000001" class="form-control" name="net_weight_kg" value="8.500000">
                                </div>
                                <div class="form-group">
                                    <label>{{ translate('Gross Weight (KG)') }}</label>
                                    <input type="number" min="0" step="0.000001" class="form-control" name="gross_weight_kg" value="9.200000">
                                </div>
                                <div class="form-group">
                                    <label>{{ translate('Total Volume / CBM') }}</label>
                                    <input type="number" min="0" step="0.000001" class="form-control" name="total_volume_cbm" id="total-volume-cbm">
                                </div>
                                <div class="preview-section-title border-top pt-3">{{ translate('Manual') }}</div>
                                <div class="form-group">
                                    <label>{{ translate('Weight (Gram)') }}</label>
                                    <input type="number" min="0" step="0.001" class="form-control" name="weight_grams" id="weight-grams" value="9200">
                                    <small class="apple-green-highlight mt-1" id="weight-kg-display">0 KG</small>
                                </div>
                                <div class="form-group">
                                    <label>{{ translate('Dimensions (CM)') }}</label>
                                    <div class="dimension-inputs">
                                        <input type="number" min="0" step="0.01" class="form-control" id="length-cm" name="length_cm" value="40" placeholder="{{ translate('Length') }}">
                                        <span class="dimension-separator">×</span>
                                        <input type="number" min="0" step="0.01" class="form-control" id="width-cm" name="width_cm" value="30" placeholder="{{ translate('Width') }}">
                                        <span class="dimension-separator">×</span>
                                        <input type="number" min="0" step="0.01" class="form-control" id="height-cm" name="height_cm" value="25" placeholder="{{ translate('Height') }}">
                                        <span class="dimension-separator">CM</span>
                                    </div>
                                    <small class="text-muted d-block mt-1" id="manual-cbm-display">0 CBM</small>
                                </div>
                                <div class="form-group">
                                    <label>{{ translate('LR / GR / Doc / Vehicle / AWB No.') }}</label>
                                    <input type="text" class="form-control" name="lr_number">
                                </div>
                                <div class="form-group">
                                    <label>{{ translate('LR Date') }}</label>
                                    <input type="date" class="form-control" name="lr_date">
                                </div>
                                <div class="form-group">
                                    <label>{{ translate('Attached File Name') }}</label>
                                    <input type="text" class="form-control" name="attached_file_name">
                                </div>
                                <div class="form-group">
                                    <label>{{ translate('Attachment Option') }}</label>
                                    <input type="file" class="form-control preview-file-input" name="order_attachments[]" multiple
                                        accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv" data-list="#order-attachment-names">
                                    <small class="text-muted">{{ translate('Select multiple files; maximum 10 MB per file.') }}</small>
                                    <div class="selected-file-list mt-1" id="order-attachment-names"></div>
                                </div>

                                <div class="preview-section-title border-top pt-3">{{ translate('Shipping') }}</div>
                                <span id="excel-path-chip">{{ translate('Transport / Surface / Road') }}</span>
                                <div class="form-group">
                                    <label>{{ translate('Shipping Method') }}</label>
                                    <select class="form-control" name="shipping_method" id="shipping-method">
                                        <option value="courier">{{ translate('Courier') }}</option>
                                        <option value="transport" selected>{{ translate('Transport') }}</option>
                                        <option value="local">{{ translate('Local Delivery') }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>{{ translate('Transport Mode') }}</label>
                                    <select class="form-control" name="fod_mode" id="fod-mode">
                                        <option value="surface">{{ translate('Surface') }}</option>
                                        <option value="sea">{{ translate('Sea') }}</option>
                                        <option value="air">{{ translate('Air') }}</option>
                                    </select>
                                </div>
                                <div class="form-group" id="sub-mode-fields">
                                    <label id="sub-mode-label">{{ translate('Surface Mode') }}</label>
                                    <select class="form-control" name="transport_surface_mode" id="sub-mode"></select>
                                </div>

                                <div id="courier-fields" class="d-none">
                                    <div class="form-group">
                                        <label>{{ translate('Courier Provider') }}</label>
                                        <select class="form-control" name="shipping_method_id" id="courier-provider">
                                            <option value="">{{ translate('Select Courier Provider') }}</option>
                                            @foreach ($shippingMethods as $method)
                                                <option value="{{ $method['id'] }}" data-slug="{{ $method['slug'] }}">{{ $method['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Courier Service') }}</label>
                                        <select class="form-control" name="courier_service" id="courier-service" disabled>
                                            <option value="">{{ translate('Select provider to load services') }}</option>
                                        </select>
                                        <div id="courier-service-message" class="small text-muted mt-1"></div>
                                    </div>
                                </div>

                                <div id="transport-fields">
                                    <div class="form-group">
                                        <label id="transport-name-label">{{ translate('Transport') }}</label>
                                        <select class="form-control js-other-select" name="transport_id" id="transport-id">
                                            <option value="">{{ translate('Select Transport') }}</option>
                                            @foreach ($transports as $transport)
                                                <option value="{{ $transport['id'] }}" data-mode="{{ $transport['mode'] }}">{{ $transport['name'] }}</option>
                                            @endforeach
                                            <option value="other">{{ translate('Other') }}</option>
                                        </select>
                                        <input type="text" class="form-control mt-2 other-enter-input d-none" name="transport_name" placeholder="{{ translate('Enter transport name') }}">
                                    </div>
                                </div>

                                <div id="local-fields" class="d-none">
                                    <div class="form-group">
                                        <label>{{ translate('Local Delivery Partner') }}</label>
                                        <select class="form-control js-other-select" name="local_delivery_partner_id">
                                            <option value="">{{ translate('Select Partner') }}</option>
                                            @foreach ($localDeliveryPartners as $partner)
                                                <option value="{{ $partner['id'] }}">{{ $partner['name'] }}</option>
                                            @endforeach
                                            <option value="other">{{ translate('Other') }}</option>
                                        </select>
                                        <input type="text" class="form-control mt-2 other-enter-input d-none" name="local_delivery_partner_name" placeholder="{{ translate('Enter partner name') }}">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label id="carrier-tax-number-label">{{ translate('Carrier GST No.') }}</label>
                                    <input type="text" class="form-control" name="carrier_tax_number">
                                </div>

                                <div id="surface-route-fields">
                                    <div class="form-group">
                                        <label>{{ translate('Loading Country') }}</label>
                                        <input type="text" class="form-control" value="India" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label id="source-from-label">{{ translate('Source (From)') }}</label>
                                        <input type="text" class="form-control" value="Ahmedabad" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Destination Country') }}</label>
                                        <input type="text" class="form-control" value="India" readonly>
                                    </div>
                                </div>

                                <div id="port-route-fields" class="d-none">
                                    <input type="hidden" name="loading_location_type" id="loading-location-type" value="sea">
                                    <input type="hidden" name="discharge_location_type" id="discharge-location-type" value="sea">
                                    <div class="form-group">
                                        <label id="loading-country-label">{{ translate('Port Of Loading Country') }}</label>
                                        <select class="form-control" id="loading-country"></select>
                                    </div>
                                    <div class="form-group" id="loading-sea-wrap">
                                        <label>{{ translate('Port Of Loading') }}</label>
                                        <select class="form-control" name="loading_sea_port_id" id="loading-sea-port"></select>
                                    </div>
                                    <div class="form-group d-none" id="loading-air-wrap">
                                        <label>{{ translate('Departure') }}</label>
                                        <select class="form-control" name="loading_airport_id" id="loading-airport"></select>
                                    </div>
                                    <div class="selected-location-hover d-none mb-3" id="loading-location-detail" tabindex="0">
                                        <div class="selected-location-name"><i class="las la-info-circle mr-1"></i><span></span></div>
                                        <div class="selected-location-card"></div>
                                    </div>
                                    <div class="form-group">
                                        <label id="discharge-country-label">{{ translate('Destination Country') }}</label>
                                        <select class="form-control" id="discharge-country"></select>
                                    </div>
                                    <div class="form-group" id="discharge-sea-wrap">
                                        <label>{{ translate('Destination Port Of Discharge') }}</label>
                                        <select class="form-control" name="discharge_sea_port_id" id="discharge-sea-port"></select>
                                    </div>
                                    <div class="form-group d-none" id="discharge-air-wrap">
                                        <label>{{ translate('Arrival') }}</label>
                                        <select class="form-control" name="discharge_airport_id" id="discharge-airport"></select>
                                    </div>
                                    <div class="selected-location-hover d-none mb-3" id="discharge-location-detail" tabindex="0">
                                        <div class="selected-location-name"><i class="las la-info-circle mr-1"></i><span></span></div>
                                        <div class="selected-location-card"></div>
                                    </div>
                                </div>

                                <div class="form-group" id="booked-to-wrap">
                                    <label id="booked-to-label">{{ translate('Booked To') }}</label>
                                    <select class="form-control js-other-select" name="booked_to_id" id="booked-to-id" disabled>
                                        <option value="">{{ translate('Select transport first') }}</option>
                                    </select>
                                    <input type="text" class="form-control mt-2 other-enter-input d-none" name="booked_to_name" placeholder="{{ translate('Enter booked to') }}">
                                </div>

                                <div class="form-group">
                                    <label>{{ translate('Final Destination') }}</label>
                                    <select class="form-control" name="final_destination_type" id="final-destination-type">
                                        <option value="billing">{{ translate('Same as Billing Address') }}</option>
                                        <option value="shipping">{{ translate('Same as Shipping Address') }}</option>
                                        <option value="custom">{{ translate('Not in List') }}</option>
                                    </select>
                                    <div class="small text-muted mt-2" id="final-destination-preview">{{ translate('Same as Billing Address') }} — Ahmedabad, Gujarat, India</div>
                                </div>
                                <div class="border rounded p-3 mb-3 d-none" id="final-destination-custom-fields">
                                    <div class="row gutters-10">
                                        @include('backend.sales.partials.create_order_address_fields', ['prefix' => 'final_destination', 'countries' => $countries])
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label id="terms-of-delivery-label">{{ translate('Terms Of Delivery') }}</label>
                                    <select class="form-control aiz-selectpicker js-delivery-term-select" name="transport_delivery_type" id="terms-of-delivery" data-live-search="true" data-hide-disabled="true">
                                        @foreach ($domesticDeliveryTerms as $value => $label)
                                            <option value="{{ $value }}" data-invoice-type="domestic" data-fullform="{{ \App\Support\InvoiceType::deliveryTermFullForm($value) }}">{{ translate($label) }}</option>
                                        @endforeach
                                        @foreach ($internationalDeliveryTerms as $value => $label)
                                            <option value="{{ $value }}" data-invoice-type="international" data-fullform="{{ \App\Support\InvoiceType::deliveryTermFullForm($value) }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>{{ translate('Freight') }}</label>
                                    <select class="form-control" name="freight_type">
                                        <option value="">{{ translate('Select Freight') }}</option>
                                        <option value="pre_paid">{{ translate('Pre-Paid') }}</option>
                                        <option value="to_pay">{{ translate('To Pay') }}</option>
                                        <option value="fod">{{ translate('FOD') }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>{{ translate('Shipping Cost') }}</label>
                                    <select class="form-control" name="shipping_cost_type" id="shipping-cost-type">
                                        <option value="by_seller">{{ translate('By Seller') }}</option>
                                        <option value="free_shipping">{{ translate('Free Shipping') }}</option>
                                    </select>
                                    <input type="hidden" name="free_shipping" id="free-shipping" value="0">
                                </div>
                                <div id="sell-amount-wrap">
                                    <label>{{ translate('Sell Amount') }}</label>
                                    <div class="form-group">
                                        <label class="small mb-1">{{ translate('In-house Seller') }}</label>
                                        <label class="aiz-checkbox mb-1 d-block">
                                            <input type="checkbox" name="shipping_costs_tax_inclusive[1]" value="1">
                                            <span>{{ translate('GST Inclusive') }}</span>
                                            <span class="aiz-square-check"></span>
                                        </label>
                                        <input type="number" min="0" step="0.001" class="form-control" name="shipping_costs[1]" value="0" id="seller-shipping-input">
                                    </div>
                                    <div class="border-top pt-3 mt-3">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <label class="mb-0">{{ translate('Shipping Items') }}</label>
                                            <button type="button" class="btn btn-sm btn-soft-primary" id="add-shipping-item-btn">
                                                <i class="las la-plus"></i> {{ translate('Add Shipping Item') }}
                                            </button>
                                        </div>
                                        <div id="shipping-items" class="small text-muted">{{ translate('No shipping items added.') }}</div>
                                    </div>
                                </div>
                                <div class="form-group mt-3">
                                    <label>{{ translate('Consignee Copy') }}</label>
                                    <select class="form-control" name="consignee_copy_status" id="consignee-copy-status">
                                        <option value="not_attached">{{ translate('Not Attached') }}</option>
                                        <option value="attached">{{ translate('Attached') }}</option>
                                    </select>
                                </div>
                                <div class="form-group mb-0 d-none" id="consignee-copy-files-wrap">
                                    <label>{{ translate('LR / Consignee Copy Files') }}</label>
                                    <input type="file" class="form-control preview-file-input" name="cc_attachments[]" multiple
                                        accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv" data-list="#cc-attachment-names">
                                    <div class="selected-file-list mt-1" id="cc-attachment-names"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header p-0">
                            <button type="button" class="order-accordion-toggle" data-toggle="collapse" data-target="#payment-collapse" aria-expanded="true">
                                <span>{{ translate('Payment') }}</span>
                                <i class="las la-angle-down order-accordion-icon"></i>
                            </button>
                        </div>
                        <div id="payment-collapse" class="collapse show">
                            <div class="card-body">
                                <div class="form-group">
                                    <label>{{ translate('Payment Terms') }}</label>
                                    <select class="form-control aiz-selectpicker" name="payment_type" id="payment-terms" data-live-search="true" data-hide-disabled="true">
                                        @foreach ($domesticPaymentTerms as $value => $label)
                                            <option value="{{ $value }}" data-invoice-type="domestic" data-fullform="{{ \App\Support\InvoiceType::paymentTermFullForm($value) }}">{{ translate($label) }}</option>
                                        @endforeach
                                        @foreach ($internationalPaymentTerms as $value => $label)
                                            <option value="{{ $value }}" data-invoice-type="international" data-fullform="{{ \App\Support\InvoiceType::paymentTermFullForm($value) }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>{{ translate('Payment Status') }}</label>
                                    <select class="form-control" name="payment_status">
                                        <option value="unpaid">{{ translate('Unpaid') }}</option>
                                        <option value="paid">{{ translate('Paid') }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>{{ translate('Additional Discount') }}</label>
                                    <div class="row gutters-5">
                                        <div class="col-5">
                                            <label class="small mb-1">{{ translate('Discount') }}</label>
                                            <input type="number" min="0" step="0.001" class="form-control" name="additional_discount" id="additional-discount" value="0">
                                        </div>
                                        <div class="col-7">
                                            <label class="small mb-1">{{ translate('Discount Type') }}</label>
                                            <select class="form-control" name="additional_discount_type" id="additional-discount-type">
                                                <option value="percent">{{ translate('Percentage (%)') }}</option>
                                                <option value="amount">{{ translate('Fixed Amount') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-block mt-2" id="apply-additional-discount-btn">{{ translate('Apply Discount') }}</button>
                                    <div id="additional-discount-message" class="small mt-1"></div>
                                </div>
                                <div class="form-group">
                                    <label>{{ translate('Additional Info') }}</label>
                                    <textarea class="form-control auto-capitalize-first" name="additional_info" rows="3"></textarea>
                                </div>
                                <input type="hidden" name="send_order_notification" value="0">
                                <label class="aiz-checkbox mb-0">
                                    <input type="checkbox" name="send_order_notification" value="1" checked>
                                    <span>{{ translate('Send order notification') }}</span>
                                    <span class="aiz-square-check"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Summary') }}</h5></div>
                    <div class="card-body">
                        <table class="table table-sm mb-3">
                            <tbody>
                                <tr>
                                    <td>{{ translate('Product Subtotal') }}</td>
                                    <td class="text-right">1,200.000</td>
                                </tr>
                                <tr class="text-danger">
                                    <td>{{ translate('Less: Product / Batch Wise Discount') }}</td>
                                    <td class="text-right">0.000</td>
                                </tr>
                                <tr class="text-danger">
                                    <td>{{ translate('Less: Coupon / Additional Discount') }}</td>
                                    <td class="text-right" id="summary-coupon">0.000</td>
                                </tr>
                                <tr>
                                    <td>{{ translate('Add : Packing & Forwarding') }}</td>
                                    <td class="text-right">0.000</td>
                                </tr>
                                <tr>
                                    <td>{{ translate('Add : Shipping / Frieght') }}</td>
                                    <td class="text-right" id="summary-shipping">0.000</td>
                                </tr>
                                <tr>
                                    <td>{{ translate('Add : Insurance Charges') }}</td>
                                    <td class="text-right">0.000</td>
                                </tr>
                                <tr class="fw-600 border-top">
                                    <td>{{ translate('Taxable Value') }}</td>
                                    <td class="text-right" id="summary-taxable-value">1,200.000</td>
                                </tr>
                                <tr class="fw-600">
                                    <td>
                                        <span class="summary-tax-hover" tabindex="0">
                                            <span class="summary-tax-hover-label">{{ translate('Total Tax Value') }}</span>
                                            <i class="las la-info-circle" aria-hidden="true"></i>
                                            <span class="summary-tax-tooltip">
                                                <span class="summary-tax-tooltip-row"><span>{{ translate('Product GST') }}</span><span>144.000</span></span>
                                                <span class="summary-tax-tooltip-row"><span>{{ translate('Shipping GST') }}</span><span id="summary-shipping-tax">0.000</span></span>
                                            </span>
                                        </span>
                                    </td>
                                    <td class="text-right" id="summary-tax">144.000</td>
                                </tr>
                                <tr class="fw-700 border-top text-danger">
                                    <td>{{ translate('Grand Total') }}</td>
                                    <td class="text-right" id="summary-grand-total">1,344.000</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="alert alert-soft-success py-2">{{ translate('Customer will earn wallet point reward on this order after payment.') }}</div>
                        <button type="submit" class="btn btn-primary btn-block" id="submit-order-btn">{{ translate('Create Order') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
    <script>
        (function () {
            var bookedToOptions = @json($bookedToOptions);
            var courierServices = @json($courierServices);
            var seaPorts = @json($seaPorts);
            var airports = @json($airports);
            var airCargoTypes = @json($airCargoTypes);
            var invoiceType = 'domestic';
            var shippingItems = [];
            var nextShippingItemId = 1;

            function escapeHtml(value) {
                return String(value || '').replace(/[&<>"']/g, function (char) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
                });
            }

            function fillOptions($select, items, placeholder) {
                $select.empty().append('<option value="">' + placeholder + '</option>');
                items.forEach(function (item) {
                    $select.append($('<option></option>').val(item.value).text(item.label));
                });
            }

            function uniqueCountries(locations) {
                var seen = {};
                (locations || []).forEach(function (location) {
                    seen[location.country_key] = location.country;
                });
                return Object.keys(seen).map(function (key) {
                    return { value: key, label: seen[key] };
                });
            }

            function locationsForCountry(locations, countryKey) {
                return (locations || []).filter(function (location) {
                    return !countryKey || location.country_key === countryKey;
                }).map(function (location) {
                    return { value: location.id, label: location.name };
                });
            }

            function findLocation(kind, id) {
                return ((kind === 'sea' ? seaPorts : airports) || []).find(function (location) {
                    return String(location.id) === String(id || '');
                }) || null;
            }

            function renderLocationDetail(prefix, kind, id) {
                var location = findLocation(kind, id);
                var $detail = $('#' + prefix + '-location-detail');
                if (!location) {
                    $detail.addClass('d-none');
                    return;
                }
                var ignored = ['id', 'country_id', 'status', 'created_at', 'updated_at'];
                var html = '';
                Object.keys(location.details || {}).forEach(function (key) {
                    var value = location.details[key];
                    if (ignored.indexOf(key) !== -1 || value === null || value === '' || typeof value === 'object') return;
                    html += '<div class="selected-location-detail-row"><span>' + escapeHtml(key) + '</span><span>' + escapeHtml(value) + '</span></div>';
                });
                $detail.removeClass('d-none').find('.selected-location-name span').text(location.name);
                $detail.find('.selected-location-card').html(html || '<div class="text-muted">{{ translate('No extra details') }}</div>');
            }

            function filterInvoiceOptions($select) {
                var firstVisible = null;
                $select.find('option').each(function () {
                    var match = !this.value || $(this).data('invoice-type') === invoiceType;
                    $(this).toggle(match).prop('disabled', !match);
                    if (match && this.value && !firstVisible) firstVisible = this.value;
                });
                if (!$select.find('option:selected').is(':visible')) {
                    $select.val(firstVisible);
                }
            }

            function syncWeightKg() {
                $('#weight-kg-display').text(((Number($('#weight-grams').val()) || 0) / 1000).toFixed(3) + ' KG');
            }

            function syncManualCbm() {
                var cbm = ((Number($('#length-cm').val()) || 0) * (Number($('#width-cm').val()) || 0) * (Number($('#height-cm').val()) || 0)) / 1000000;
                $('#manual-cbm-display').text(cbm.toFixed(6) + ' CBM');
            }

            function renderFileList(input) {
                var $list = $($(input).data('list'));
                var files = Array.prototype.slice.call(input.files || []);
                $list.toggleClass('has-files', files.length > 0).empty();
                files.forEach(function (file) {
                    $list.append('<div>' + escapeHtml(file.name) + '</div>');
                });
            }

            function syncSubMode() {
                var mode = $('#fod-mode').val();
                var options = [];
                if (mode === 'surface') {
                    $('#sub-mode-label').text('{{ translate('Surface Mode') }}');
                    options = [{ value: 'road', label: '{{ translate('Road') }}' }, { value: 'train', label: '{{ translate('Train') }}' }];
                } else if (mode === 'sea') {
                    $('#sub-mode-label').text('{{ translate('Shipment Type') }}');
                    options = [{ value: 'lcl', label: 'LCL' }, { value: 'fcl', label: 'FCL' }];
                } else {
                    $('#sub-mode-label').text('{{ translate('Shipment Type') }}');
                    options = Object.keys(airCargoTypes).map(function (key) {
                        return { value: key, label: airCargoTypes[key] };
                    });
                }
                fillOptions($('#sub-mode'), options, '{{ translate('Select') }}');
                if (options.length) $('#sub-mode').val(options[0].value);
            }

            function filterTransports() {
                var mode = $('#fod-mode').val();
                var $transport = $('#transport-id');
                var selectedOk = false;
                $transport.find('option').each(function () {
                    var visible = !this.value || this.value === 'other' || String($(this).data('mode') || '') === mode;
                    $(this).prop('disabled', !visible).toggle(visible);
                    if (visible && this.selected && this.value) selectedOk = true;
                });
                if ($transport.val() && !selectedOk) $transport.val('');
            }

            function syncOtherEnter($select) {
                var $input = $select.siblings('.other-enter-input');
                var isOther = String($select.val()) === 'other';
                $input.toggleClass('d-none', !isOther);
                if (!isOther) {
                    $input.val('');
                }
            }

            function syncBookedTo() {
                var method = $('#shipping-method').val();
                var transportValue = String($('#transport-id').val() || '');
                var transportIsOther = transportValue === 'other';
                var transportId = Number(transportValue);
                var $bookedTo = $('#booked-to-id').empty();
                var matches = bookedToOptions;
                var hasTransport = transportIsOther || transportId > 0;
                if (method === 'transport') {
                    matches = transportIsOther ? [] : bookedToOptions.filter(function (option) {
                        return Number(option.transport_id) === transportId;
                    });
                    $bookedTo.append('<option value="">' + (hasTransport ? '{{ translate('Select Booked To') }}' : '{{ translate('Select transport first') }}') + '</option>');
                    $bookedTo.prop('disabled', !hasTransport);
                } else {
                    $bookedTo.append('<option value="">{{ translate('Select Booked To') }}</option>');
                    $bookedTo.prop('disabled', false);
                }
                matches.forEach(function (option) {
                    $bookedTo.append('<option value="' + option.id + '">' + escapeHtml(option.name) + '</option>');
                });
                $bookedTo.append('<option value="other">{{ translate('Other') }}</option>');
                syncOtherEnter($('#transport-id'));
                syncOtherEnter($bookedTo);
            }

            function syncPortSelects() {
                var kind = $('#fod-mode').val();
                var locations = kind === 'sea' ? seaPorts : airports;
                var countries = uniqueCountries(locations);
                fillOptions($('#loading-country'), countries, '{{ translate('Select Country') }}');
                fillOptions($('#discharge-country'), countries, '{{ translate('Select Country') }}');
                if (countries.length) {
                    $('#loading-country').val(countries[0].value);
                    $('#discharge-country').val(countries[countries.length - 1].value);
                }
                refreshPortOptions();
            }

            function refreshPortOptions() {
                var kind = $('#fod-mode').val();
                var locations = kind === 'sea' ? seaPorts : airports;
                var loadingSel = kind === 'sea' ? '#loading-sea-port' : '#loading-airport';
                var dischargeSel = kind === 'sea' ? '#discharge-sea-port' : '#discharge-airport';
                fillOptions($(loadingSel), locationsForCountry(locations, $('#loading-country').val()), '{{ translate('Select') }}');
                fillOptions($(dischargeSel), locationsForCountry(locations, $('#discharge-country').val()), '{{ translate('Select') }}');
                renderLocationDetail('loading', kind, $(loadingSel).val());
                renderLocationDetail('discharge', kind, $(dischargeSel).val());
            }

            function syncLabels() {
                var method = $('#shipping-method').val();
                var mode = $('#fod-mode').val();
                var subMode = $('#sub-mode').val();
                var international = invoiceType === 'international';
                var path = '';

                if (method === 'courier') {
                    path = '{{ translate('Courier') }} / ' + (mode === 'surface' ? '{{ translate('Surface') }}' : mode === 'sea' ? '{{ translate('Sea') }}' : '{{ translate('Air') }}');
                } else if (method === 'local') {
                    path = '{{ translate('Local Delivery') }} / {{ translate('Surface') }}';
                } else if (mode === 'sea') {
                    path = '{{ translate('Transport') }} / {{ translate('Sea') }} / ' + String(subMode || 'LCL').toUpperCase();
                } else if (mode === 'air') {
                    path = '{{ translate('Transport') }} / {{ translate('Air') }}';
                } else if (subMode === 'train') {
                    path = '{{ translate('Transport') }} / {{ translate('Surface') }} / {{ translate('Train') }}';
                } else {
                    path = '{{ translate('Transport') }} / {{ translate('Surface') }} / {{ translate('Road') }}';
                }

                $('#carrier-tax-number-label').text(international
                    ? '{{ translate('Carrier Tax No.') }}'
                    : '{{ translate('Carrier GST No.') }}');
                $('#terms-of-delivery-label').text(international
                    ? '{{ translate('Incoterm / Terms Of Delivery') }}'
                    : '{{ translate('Terms Of Delivery') }}');
                $('#domestic-invoice-fields').toggleClass('d-none', international);
                $('#excel-path-chip').text(path);

                var usePorts = method !== 'local' && (mode === 'sea' || mode === 'air');
                $('#loading-location-type,#discharge-location-type').val(usePorts ? mode : '');
                $('#surface-route-fields').toggleClass('d-none', usePorts);
                $('#port-route-fields').toggleClass('d-none', !usePorts);
                $('#loading-sea-wrap,#discharge-sea-wrap').toggleClass('d-none', mode !== 'sea');
                $('#loading-air-wrap,#discharge-air-wrap').toggleClass('d-none', mode !== 'air');
                $('#booked-to-wrap').toggleClass('d-none', usePorts);
                if (mode === 'sea') {
                    $('#loading-country-label').text('{{ translate('Port Of Loading Country') }}');
                    $('#discharge-country-label').text('{{ translate('Destination Country') }}');
                } else if (mode === 'air') {
                    $('#loading-country-label').text('{{ translate('Departure Country') }}');
                    $('#discharge-country-label').text('{{ translate('Arrival Country') }}');
                }
            }

            function syncPath() {
                var method = $('#shipping-method').val();
                $('#courier-fields').toggleClass('d-none', method !== 'courier');
                $('#transport-fields').toggleClass('d-none', method !== 'transport');
                $('#local-fields').toggleClass('d-none', method !== 'local');
                if (method === 'local') {
                    $('#fod-mode').val('surface').prop('disabled', true);
                    syncSubMode();
                } else {
                    $('#fod-mode').prop('disabled', false);
                }
                filterTransports();
                syncBookedTo();
                syncLabels();
                if ($('#fod-mode').val() === 'sea' || $('#fod-mode').val() === 'air') {
                    syncPortSelects();
                }
            }

            function renderShippingItems() {
                var $box = $('#shipping-items').empty();
                if (!shippingItems.length) {
                    $box.addClass('text-muted').text('{{ translate('No shipping items added.') }}');
                    syncSummary();
                    return;
                }
                $box.removeClass('text-muted');
                shippingItems.forEach(function (item) {
                    $box.append('<div class="row gutters-5 align-items-end mb-2 shipping-item-row" data-id="' + item.id + '">'
                        + '<div class="col-12 mb-1"><label class="mb-1">{{ translate('Description') }}</label>'
                        + '<input type="text" class="form-control form-control-sm shipping-item-description" value="' + escapeHtml(item.description) + '"></div>'
                        + '<div class="col-5"><label class="mb-1">{{ translate('Seller') }}</label>'
                        + '<select class="form-control form-control-sm"><option>{{ translate('In-house Seller') }}</option></select></div>'
                        + '<div class="col-5"><label class="mb-1">{{ translate('Amount') }}</label>'
                        + '<label class="aiz-checkbox mb-1 d-block"><input type="checkbox" class="shipping-item-tax-inclusive" ' + (item.tax_inclusive ? 'checked' : '') + '><span>{{ translate('GST Inclusive') }}</span><span class="aiz-square-check"></span></label>'
                        + '<input type="number" min="0" step="0.001" class="form-control form-control-sm shipping-item-amount" value="' + (item.amount || 0) + '"></div>'
                        + '<div class="col-2"><button type="button" class="btn btn-sm btn-soft-danger remove-shipping-item"><i class="las la-trash"></i></button></div></div>');
                });
                syncSummary();
            }

            function syncSummary() {
                var shipping = Number($('#seller-shipping-input').val() || 0);
                shippingItems.forEach(function (item) { shipping += Number(item.amount || 0); });
                if ($('#shipping-cost-type').val() === 'free_shipping') shipping = 0;
                var coupon = Number($('#summary-coupon').data('amount') || 0);
                $('#summary-shipping').text(shipping.toFixed(3));
                $('#summary-taxable-value').text((1200 + shipping - coupon).toFixed(3));
                $('#summary-grand-total').text((1344 + shipping - coupon).toFixed(3));
            }

            $('#preview-invoice-type').on('change', function () {
                invoiceType = $(this).val();
                filterInvoiceOptions($('#terms-of-delivery'));
                filterInvoiceOptions($('#payment-terms'));
                syncLabels();
            });
            $('#shipping-method,#fod-mode').on('change', function () {
                if (this.id === 'fod-mode') syncSubMode();
                syncPath();
            });
            $('#sub-mode').on('change', syncLabels);
            $('#transport-id').on('change', syncBookedTo);
            $(document).on('change', '.js-other-select', function () {
                syncOtherEnter($(this));
            });
            $('#loading-country,#discharge-country').on('change', refreshPortOptions);
            $('#loading-sea-port,#discharge-sea-port,#loading-airport,#discharge-airport').on('change', function () {
                var kind = $('#fod-mode').val();
                renderLocationDetail('loading', kind, $('#loading-' + (kind === 'sea' ? 'sea-port' : 'airport')).val());
                renderLocationDetail('discharge', kind, $('#discharge-' + (kind === 'sea' ? 'sea-port' : 'airport')).val());
            });
            $('#weight-grams').on('input', syncWeightKg);
            $('#length-cm,#width-cm,#height-cm').on('input', syncManualCbm);
            $('#final-destination-type').on('change', function () {
                var type = $(this).val();
                $('#final-destination-custom-fields').toggleClass('d-none', type !== 'custom');
                $('#final-destination-preview').toggleClass('d-none', type === 'custom').text(
                    type === 'billing'
                        ? '{{ translate('Same as Billing Address') }} — Ahmedabad, Gujarat, India'
                        : '{{ translate('Same as Shipping Address') }} — Ahmedabad, Gujarat, India'
                );
            });
            $('#consignee-copy-status').on('change', function () {
                $('#consignee-copy-files-wrap').toggleClass('d-none', $(this).val() !== 'attached');
            });
            $('#courier-provider').on('change', function () {
                var slug = $(this).find('option:selected').data('slug');
                var services = courierServices[slug] || ['Express', 'Surface'];
                var $service = $('#courier-service').prop('disabled', false).empty();
                $service.append('<option value="">{{ translate('Select Courier Service') }}</option>');
                services.forEach(function (name) {
                    $service.append($('<option></option>').val(name).text(name));
                });
                $('#courier-service-message').text(services.length ? '' : '{{ translate('No services configured; sample options shown.') }}');
            });
            $('#shipping-cost-type').on('change', function () {
                var isFree = $(this).val() === 'free_shipping';
                $('#free-shipping').val(isFree ? 1 : 0);
                $('#sell-amount-wrap :input,#add-shipping-item-btn').prop('disabled', isFree);
                syncSummary();
            });
            $('#seller-shipping-input').on('input', syncSummary);
            $('#add-shipping-item-btn').on('click', function () {
                shippingItems.push({ id: nextShippingItemId++, description: '{{ translate('Handling') }}', amount: 0, tax_inclusive: false });
                renderShippingItems();
            });
            $(document).on('click', '.remove-shipping-item', function () {
                var id = Number($(this).closest('.shipping-item-row').data('id'));
                shippingItems = shippingItems.filter(function (item) { return item.id !== id; });
                renderShippingItems();
            });
            $(document).on('input change', '.shipping-item-description,.shipping-item-amount,.shipping-item-tax-inclusive', function () {
                var $row = $(this).closest('.shipping-item-row');
                var item = shippingItems.find(function (entry) { return entry.id === Number($row.data('id')); });
                if (!item) return;
                item.description = $row.find('.shipping-item-description').val();
                item.amount = Number($row.find('.shipping-item-amount').val() || 0);
                item.tax_inclusive = $row.find('.shipping-item-tax-inclusive').is(':checked');
                syncSummary();
            });
            $('.shipping-same-toggle').on('change', function () {
                $('#shipping-address-options').toggleClass('d-none', $('input[name="shipping_same_as_billing"]:checked').val() === '1');
            });
            $('#new-shipping-toggle').on('change', function () {
                $('#new-shipping-fields').toggleClass('d-none', !this.checked);
            });
            $('#apply-additional-discount-btn').on('click', function () {
                var value = Number($('#additional-discount').val() || 0);
                var type = $('#additional-discount-type').val();
                var amount = type === 'percent' ? 1200 * (value / 100) : value;
                $('#summary-coupon').data('amount', amount).text(amount.toFixed(3));
                $('#additional-discount-message').addClass('text-success').text('{{ translate('Discount applied in this preview only.') }}');
                syncSummary();
            });
            $('.preview-file-input').on('change', function () { renderFileList(this); });
            $('#shipping-preview-form').on('submit', function (event) {
                event.preventDefault();
                if (window.AIZ && AIZ.plugins && AIZ.plugins.notify) {
                    AIZ.plugins.notify('info', '{{ translate('Preview only. This form does not create an order.') }}');
                } else {
                    alert('{{ translate('Preview only. This form does not create an order.') }}');
                }
            });

            filterInvoiceOptions($('#terms-of-delivery'));
            filterInvoiceOptions($('#payment-terms'));
            syncSubMode();
            syncPath();
            syncWeightKg();
            syncManualCbm();
            syncSummary();
        })();
    </script>
@endsection
