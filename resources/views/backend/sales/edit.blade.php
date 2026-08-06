@extends('backend.layouts.app')

@section('content')
    @php
        $shippingChoice = $order->shipping_choice ?: 'transport';
        $isInternational = $invoiceType === \App\Support\InvoiceType::INTERNATIONAL;
        $hasExistingConsigneeCopy = $order->attachments->where('category', 'consignee_copy')->isNotEmpty() || filled($order->cc_attached_path);
        $hasExistingOrderAttachments = $order->attachments->where('category', 'order_attachment')->isNotEmpty();
    @endphp

    <style>
        .apple-green-order-box {
            border: 1px solid #86efac;
            border-radius: 12px;
            background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
            box-shadow: 0 2px 8px rgba(34, 197, 94, .10);
        }
        .selected-file-list {
            min-height: 42px;
            padding: 7px;
            border: 1px solid #e2e5ec;
            border-radius: 4px;
            background: #f8f9fa;
        }
        .selected-file-chip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 4px 7px;
            margin-bottom: 4px;
            border-radius: 6px;
            background: #fff;
        }
        .selected-file-chip:last-child {
            margin-bottom: 0;
        }
        .existing-attachment-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 7px 9px;
            margin-bottom: 5px;
            border: 1px solid #e2e5ec;
            border-radius: 6px;
            background: #fff;
        }
    </style>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0 h6">{{ translate('Edit Order') }}: {{ $order->code }}</h5>
            <a href="{{ route('all_orders.show', encrypt($order->id)) }}" class="btn btn-sm btn-soft-secondary">
                {{ translate('Back to Order') }}
            </a>
        </div>
        <div class="card-body">
            <form action="{{ route('orders.update', $order->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <div class="fw-600 mb-1">{{ translate('The order could not be updated. Please correct the following fields:') }}</div>
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row gutters-10">
                    <div class="col-lg-7">
                        <div class="apple-green-order-box p-3 mb-3">
                            <div class="row gutters-10">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>{{ translate('Order No') }}</label>
                                        <input type="text" class="form-control" value="{{ $order->code }}" readonly>
                                        <small class="text-muted">{{ translate('Existing order numbers are never regenerated during edit.') }}</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ translate('Shipping Method') }}</label>
                                        <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $shippingChoice)) }}" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>{{ translate('Payment Terms') }}</label>
                                <select class="form-control" name="payment_type">
                                    <option value="">{{ translate('Select Payment Terms') }}</option>
                                    @foreach(\App\Support\InvoiceType::paymentTerms($invoiceType) as $value => $label)
                                        <option value="{{ $value }}" @selected(old('payment_type', $order->payment_type) === $value)>{{ translate($label) }}</option>
                                    @endforeach
                                </select>
                                @error('payment_type') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            @if($shippingChoice === 'transport')
                                <div class="form-group">
                                    <label>{{ translate('Transport') }}</label>
                                    <select class="form-control" name="transport_id" id="edit-transport-id">
                                        <option value="">{{ translate('Select Transport') }}</option>
                                        @foreach($transports as $transport)
                                            <option value="{{ $transport->id }}" @selected((string) old('transport_id', $order->transport_id) === (string) $transport->id)>{{ $transport->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('transport_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>

                                <div class="row gutters-10">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ translate('Transport Mode') }}</label>
                                            <select class="form-control" name="fod_mode" id="edit-fod-mode">
                                                <option value="surface" @selected(old('fod_mode', $order->fod_mode ?: $order->transport_mode ?: 'surface') === 'surface')>{{ translate('Surface') }}</option>
                                                <option value="air" @selected(old('fod_mode', $order->fod_mode ?: $order->transport_mode) === 'air')>{{ translate('Air') }}</option>
                                                <option value="sea" @selected(old('fod_mode', $order->fod_mode ?: $order->transport_mode) === 'sea')>{{ translate('Sea') }}</option>
                                            </select>
                                            @error('fod_mode') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group" id="edit-transport-surface-mode-wrap">
                                            <label>{{ translate('Surface Mode') }}</label>
                                            <select class="form-control" name="transport_surface_mode" id="edit-transport-surface-mode">
                                                <option value="road" @selected(old('transport_surface_mode', $order->transport_surface_mode ?: 'road') === 'road')>{{ translate('Road') }}</option>
                                                <option value="train" @selected(old('transport_surface_mode', $order->transport_surface_mode) === 'train')>{{ translate('Train') }}</option>
                                            </select>
                                            @error('transport_surface_mode') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>{{ translate('Booked To') }}</label>
                                    <select class="form-control" name="booked_to_id" id="edit-booked-to-id" data-selected="{{ (string) old('booked_to_id', $order->booked_to_id) }}">
                                        <option value="">{{ translate('Select transport first') }}</option>
                                    </select>
                                    @error('booked_to_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            @elseif($shippingChoice === 'local')
                                <div class="form-group">
                                    <label>{{ translate('Local Delivery Partner') }}</label>
                                    <select class="form-control" name="local_delivery_partner_id">
                                        <option value="">{{ translate('Select Partner') }}</option>
                                        @foreach($localDeliveryPartners as $partner)
                                            <option value="{{ $partner->id }}" @selected((string) old('local_delivery_partner_id', $order->local_delivery_partner_id) === (string) $partner->id)>{{ $partner->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('local_delivery_partner_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            @else
                                @if($courierConfigurationLocked)
                                    <div class="alert alert-soft-warning mb-3">
                                        {{ translate('Courier provider and service are locked because this order already has a booked shipment.') }}
                                    </div>
                                    <div class="row gutters-10">
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('Courier Provider') }}</label>
                                            <input type="text" class="form-control" value="{{ optional($currentShippingMethod)->name ?: $order->shipping_by }}" readonly>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>{{ translate('Courier Service') }}</label>
                                            <input type="text" class="form-control" value="{{ $order->shipping_courier_id }}" readonly>
                                        </div>
                                    </div>
                                @else
                                    <div class="form-group">
                                        <label>{{ translate('Courier Provider') }}</label>
                                        <select class="form-control" name="shipping_method_id" id="edit-courier-provider">
                                            <option value="">{{ translate('Select Courier Provider') }}</option>
                                            @foreach($shippingMethods as $method)
                                                <option value="{{ $method->id }}" @selected((string) old('shipping_method_id', optional($currentShippingMethod)->id) === (string) $method->id)>
                                                    {{ $method->name }}{{ !$method->is_active ? ' (' . translate('Inactive') . ')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('shipping_method_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="form-group">
                                        <label>{{ translate('Courier Service') }}</label>
                                        <div class="input-group">
                                            <select class="form-control" name="courier_service" id="edit-courier-service"
                                                data-selected="{{ old('courier_service', $order->shipping_courier_id) }}">
                                                <option value="{{ old('courier_service', $order->shipping_courier_id) }}">
                                                    {{ old('courier_service', $order->shipping_courier_id) ?: translate('Load available services') }}
                                                </option>
                                            </select>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-soft-primary" id="edit-load-courier-services">
                                                    {{ translate('Load Services') }}
                                                </button>
                                            </div>
                                        </div>
                                        <div id="edit-courier-service-message" class="small text-muted mt-1"></div>
                                        @error('courier_service') <div class="text-danger small">{{ $message }}</div> @enderror
                                    </div>
                                @endif
                            @endif

                            <div class="form-group">
                                <label>{{ translate('Delivery Type') }}</label>
                                <select class="form-control" name="transport_delivery_type">
                                    <option value="">{{ translate('Select Delivery Type') }}</option>
                                    @foreach(\App\Support\InvoiceType::deliveryTerms($invoiceType) as $value => $label)
                                        <option value="{{ $value }}" @selected(old('transport_delivery_type', $order->transport_delivery_type) === $value)>{{ translate($label) }}</option>
                                    @endforeach
                                </select>
                                @error('transport_delivery_type') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            @if(!$isInternational)
                                <div class="form-group">
                                    <label>{{ translate('Reverse Charges') }}</label>
                                    <select class="form-control" name="reverse_charge">
                                        <option value="0" @selected((string) old('reverse_charge', (int) $order->reverse_charge) === '0')>{{ translate('No') }}</option>
                                        <option value="1" @selected((string) old('reverse_charge', (int) $order->reverse_charge) === '1')>{{ translate('Yes') }}</option>
                                    </select>
                                    @error('reverse_charge') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            @endif

                            @if($shippingChoice === 'transport')
                                <input type="hidden" name="loading_location_type" id="edit-loading-location-type" value="{{ old('loading_location_type', $order->loading_location_type ?: ($order->fod_mode ?: 'sea')) }}">
                                <input type="hidden" name="discharge_location_type" id="edit-discharge-location-type" value="{{ old('discharge_location_type', $order->discharge_location_type ?: ($order->fod_mode ?: 'sea')) }}">

                                <div id="edit-port-logistics-wrap">
                                    <div id="edit-sea-logistics-fields">
                                        <div class="form-group">
                                            <label>{{ translate('Port Of Loading') }}</label>
                                            <select class="form-control" name="loading_sea_port_id" id="edit-loading-sea-port-id">
                                                <option value="">{{ translate('Select Sea Port') }}</option>
                                                @foreach($seaPorts as $port)
                                                    <option value="{{ $port->id }}" @selected((string) old('loading_sea_port_id', $order->loading_sea_port_id) === (string) $port->id)>{{ $port->country ? $port->country . ' - ' : '' }}{{ $port->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('loading_sea_port_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="form-group">
                                            <label>{{ translate('Destination Port Of Discharge') }}</label>
                                            <select class="form-control" name="discharge_sea_port_id" id="edit-discharge-sea-port-id">
                                                <option value="">{{ translate('Select Sea Port') }}</option>
                                                @foreach($seaPorts as $port)
                                                    <option value="{{ $port->id }}" @selected((string) old('discharge_sea_port_id', $order->discharge_sea_port_id) === (string) $port->id)>{{ $port->country ? $port->country . ' - ' : '' }}{{ $port->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('discharge_sea_port_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div id="edit-air-logistics-fields">
                                        <div class="form-group">
                                            <label>{{ translate('Departure') }}</label>
                                            <select class="form-control" name="loading_airport_id" id="edit-loading-airport-id">
                                                <option value="">{{ translate('Select Airport') }}</option>
                                                @foreach($airports as $airport)
                                                    <option value="{{ $airport->id }}" @selected((string) old('loading_airport_id', $order->loading_airport_id) === (string) $airport->id)>{{ $airport->country ? $airport->country . ' - ' : '' }}{{ $airport->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('loading_airport_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="form-group">
                                            <label>{{ translate('Arrival') }}</label>
                                            <select class="form-control" name="discharge_airport_id" id="edit-discharge-airport-id">
                                                <option value="">{{ translate('Select Airport') }}</option>
                                                @foreach($airports as $airport)
                                                    <option value="{{ $airport->id }}" @selected((string) old('discharge_airport_id', $order->discharge_airport_id) === (string) $airport->id)>{{ $airport->country ? $airport->country . ' - ' : '' }}{{ $airport->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('discharge_airport_id') <div class="text-danger small">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>{{ translate('Final Destination') }}</label>
                                        <input type="text" class="form-control" name="final_destination" value="{{ old('final_destination', $order->final_destination) }}">
                                        @error('final_destination') <div class="text-danger small">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            @endif

                            <div class="form-group">
                                <label>{{ translate('Freight') }}</label>
                                <select class="form-control" name="freight_type">
                                    <option value="">{{ translate('Select Freight') }}</option>
                                    <option value="pre_paid" @selected(old('freight_type', $order->freight_type) === 'pre_paid')>{{ translate('Pre-Paid') }}</option>
                                    <option value="to_pay" @selected(old('freight_type', $order->freight_type) === 'to_pay')>{{ translate('To Pay') }}</option>
                                    <option value="fod" @selected(old('freight_type', $order->freight_type) === 'fod')>{{ translate('FOD') }}</option>
                                </select>
                                @error('freight_type') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>{{ translate('Shipping Cost') }}</label>
                                <select class="form-control" name="shipping_cost_type" id="edit-shipping-cost-type">
                                    <option value="by_seller" @selected(old('shipping_cost_type', $order->free_shipping ? 'free_shipping' : 'by_seller') === 'by_seller')>{{ translate('By Seller') }}</option>
                                    <option value="free_shipping" @selected(old('shipping_cost_type', $order->free_shipping ? 'free_shipping' : 'by_seller') === 'free_shipping')>{{ translate('Free Shipping') }}</option>
                                </select>
                                @error('shipping_cost_type') <div class="text-danger small">{{ $message }}</div> @enderror
                                <input type="hidden" name="free_shipping" id="edit-free-shipping" value="{{ old('shipping_cost_type', $order->free_shipping ? 'free_shipping' : 'by_seller') === 'free_shipping' ? 1 : 0 }}">
                            </div>
                            <div class="form-group" id="edit-sell-amount-wrap">
                                <label>{{ translate('Sell Amount') }}</label>
                                <input type="number" min="0" step="0.01" class="form-control" name="sell_amount"
                                    id="edit-sell-amount" value="{{ old('sell_amount', $sellAmount) }}">
                                @error('sell_amount') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="border rounded p-3 mb-3">
                            <div class="form-group">
                                <label>{{ translate('Carrier GST No.') }}</label>
                                <input type="text" class="form-control" name="carrier_tax_number" value="{{ old('carrier_tax_number', $order->carrier_tax_number) }}">
                                @error('carrier_tax_number') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="row gutters-10">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ translate('Cases') }}</label>
                                        <input type="number" min="0" class="form-control" name="cases" value="{{ old('cases', $order->cases) }}">
                                        @error('cases') <div class="text-danger small">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ translate('PO Date') }}</label>
                                        <input type="date" class="form-control" name="po_date" value="{{ old('po_date', optional($order->po_date)->format('Y-m-d')) }}">
                                        @error('po_date') <div class="text-danger small">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ translate('LR Date') }}</label>
                                        <input type="date" class="form-control" name="lr_date" value="{{ old('lr_date', optional($order->lr_date)->format('Y-m-d')) }}">
                                        @error('lr_date') <div class="text-danger small">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>{{ translate('Weight (Gram)') }}</label>
                                <input type="number" min="0" step="0.001" class="form-control" name="weight_grams"
                                    id="edit-weight-grams" value="{{ old('weight_grams', $order->weight_grams) }}">
                                <small class="apple-green-highlight mt-1" id="edit-weight-kg-display">0 KG</small>
                                @error('weight_grams') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>{{ translate('Dimensions (CM)') }}</label>
                                <div class="row gutters-5">
                                    <div class="col-4">
                                        <input type="number" min="0" step="0.01" class="form-control" name="length_cm"
                                            value="{{ old('length_cm', $order->length_cm) }}" placeholder="{{ translate('Length') }}">
                                    </div>
                                    <div class="col-4">
                                        <input type="number" min="0" step="0.01" class="form-control" name="width_cm"
                                            value="{{ old('width_cm', $order->width_cm) }}" placeholder="{{ translate('Width') }}">
                                    </div>
                                    <div class="col-4">
                                        <input type="number" min="0" step="0.01" class="form-control" name="height_cm"
                                            value="{{ old('height_cm', $order->height_cm) }}" placeholder="{{ translate('Height') }}">
                                    </div>
                                </div>
                                @error('length_cm') <div class="text-danger small">{{ $message }}</div> @enderror
                                @error('width_cm') <div class="text-danger small">{{ $message }}</div> @enderror
                                @error('height_cm') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>{{ translate('PO Number') }}</label>
                                <input type="text" class="form-control" name="po_number" value="{{ old('po_number', $order->po_number) }}">
                                @error('po_number') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>{{ translate('LR Number') }}</label>
                                <input type="text" class="form-control" name="lr_number" value="{{ old('lr_number', $order->lr_number) }}">
                                @error('lr_number') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="row gutters-10">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ translate('Net Weight (KG)') }}</label>
                                        <input type="number" min="0" step="0.000001" class="form-control" name="net_weight_kg" value="{{ old('net_weight_kg', $order->net_weight_kg) }}">
                                        @error('net_weight_kg') <div class="text-danger small">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ translate('Gross Weight (KG)') }}</label>
                                        <input type="number" min="0" step="0.000001" class="form-control" name="gross_weight_kg" value="{{ old('gross_weight_kg', $order->gross_weight_kg) }}">
                                        @error('gross_weight_kg') <div class="text-danger small">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ translate('Total Volume / CBM') }}</label>
                                        <input type="number" min="0" step="0.000001" class="form-control" name="total_volume_cbm" value="{{ old('total_volume_cbm', $order->total_volume_cbm) }}">
                                        @error('total_volume_cbm') <div class="text-danger small">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            @if($order->attachments->isNotEmpty())
                                <div class="form-group">
                                    <label>{{ translate('Existing Attachments') }}</label>
                                    @foreach($order->attachments as $attachment)
                                        <div class="existing-attachment-row">
                                            <div class="text-truncate">
                                                <span class="badge badge-soft-secondary mr-1">
                                                    {{ $attachment->category === 'consignee_copy' ? translate('Consignee Copy') : translate('Order Attachment') }}
                                                </span>
                                                <a href="{{ asset('storage/' . $attachment->path) }}" target="_blank" rel="noopener">
                                                    {{ $attachment->original_name }}
                                                </a>
                                            </div>
                                            <button type="button" class="btn btn-xs btn-soft-danger delete-existing-attachment"
                                                data-url="{{ route('orders.attachments.destroy', [$order, $attachment]) }}"
                                                data-name="{{ $attachment->original_name }}">
                                                <i class="las la-trash"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="form-group">
                                <label>{{ translate('Consignee Copy') }}</label>
                                <select class="form-control" name="consignee_copy_status" id="edit-consignee-copy-status">
                                    <option value="attached" @selected(old('consignee_copy_status', $order->consignee_copy_status ?: ($hasExistingConsigneeCopy ? 'attached' : 'not_attached')) === 'attached')>{{ translate('Attached') }}</option>
                                    <option value="not_attached" @selected(old('consignee_copy_status', $order->consignee_copy_status ?: ($hasExistingConsigneeCopy ? 'attached' : 'not_attached')) === 'not_attached')>{{ translate('Not Attached') }}</option>
                                </select>
                                @error('consignee_copy_status') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group" id="edit-consignee-files-wrap">
                                <label>{{ translate('LR / Consignee Copy Files') }}</label>
                                <input type="file" class="form-control multi-file-input" name="cc_attachments[]" id="edit-cc-attachments" multiple
                                    accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv" data-list="#edit-cc-attachment-names">
                                <div class="selected-file-list mt-1" id="edit-cc-attachment-names">
                                    <span class="text-muted">{{ translate('No files selected') }}</span>
                                </div>
                                @error('cc_attachments') <div class="text-danger small">{{ $message }}</div> @enderror
                                @if($hasExistingConsigneeCopy)
                                    <small class="text-success d-block mt-1">{{ translate('An existing consignee copy is already stored.') }}</small>
                                @endif
                            </div>

                            <div class="form-group">
                                <label>{{ translate('Order Attachments') }}</label>
                                <input type="file" class="form-control multi-file-input" name="order_attachments[]" id="edit-order-attachments" multiple
                                    accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv" data-list="#edit-order-attachment-names">
                                <div class="selected-file-list mt-1" id="edit-order-attachment-names">
                                    <span class="text-muted">{{ translate('No files selected') }}</span>
                                </div>
                                @error('order_attachments') <div class="text-danger small">{{ $message }}</div> @enderror
                                @if($hasExistingOrderAttachments)
                                    <small class="text-success d-block mt-1">{{ translate('Existing order attachments are already stored.') }}</small>
                                @endif
                            </div>

                            <div class="form-group">
                                <label>{{ translate('Attachment File Name') }}</label>
                                <input type="text" class="form-control" name="attached_file_name" value="{{ old('attached_file_name', $order->attached_file_name) }}">
                                @error('attached_file_name') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>{{ translate('Additional Info') }}</label>
                                <textarea class="form-control" name="additional_info" rows="3">{{ old('additional_info', $order->additional_info) }}</textarea>
                                @error('additional_info') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>{{ translate('Sales Executive Name') }}</label>
                                <select class="form-control aiz-selectpicker" name="sales_executive_id" data-live-search="true">
                                    <option value="">{{ translate('Select Sales Executive') }}</option>
                                    @foreach($salesPeople as $staff)
                                        <option value="{{ $staff->user_id }}" @selected((string) old('sales_executive_id', $order->sales_executive_id ?: $order->sales_person_id) === (string) $staff->user_id)>
                                            {{ optional($staff->user)->name }}{{ $staff->designation ? ' - ' . $staff->designation : '' }}{{ !$staff->status ? ' (' . translate('Inactive') . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @foreach([
                                ['name' => 'packed_by', 'label' => 'Packed By', 'staff' => $packedStaff],
                                ['name' => 'checked_by', 'label' => 'Checked By', 'staff' => $checkedStaff],
                                ['name' => 'billing_by', 'label' => 'Billing By', 'staff' => $billingStaff],
                            ] as $staffField)
                                <div class="form-group">
                                    <label>{{ translate($staffField['label']) }}</label>
                                    <select class="form-control aiz-selectpicker" name="{{ $staffField['name'] }}" data-live-search="true">
                                        <option value="">{{ translate('Select Staff') }}</option>
                                        @foreach($staffField['staff'] as $staff)
                                            <option value="{{ $staff->user_id }}" @selected((string) old($staffField['name'], $order->{$staffField['name']}) === (string) $staff->user_id)>
                                                {{ optional($staff->user)->name }}{{ $staff->designation ? ' - ' . $staff->designation : '' }}{{ !$staff->status ? ' (' . translate('Inactive') . ')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error($staffField['name']) <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ translate('Update Order') }}</button>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function () {
            var shippingChoice = @json($shippingChoice);
            var hasExistingConsigneeCopy = @json($hasExistingConsigneeCopy);
            var courierRatesUrl = @json(route('orders.create.courier_rates'));
            var orderId = @json($order->id);
            var csrf = @json(csrf_token());
            var bookedToOptions = @json($bookedToOptions->map(function ($option) {
                return ['id' => (string) $option->id, 'transport_id' => (string) $option->transport_id, 'name' => $option->name];
            })->values());

            function escapeHtml(value) {
                return String(value || '').replace(/[&<>"']/g, function (char) {
                    return ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    })[char];
                });
            }

            function loadCourierServices() {
                if (shippingChoice !== 'courier' || !$('#edit-courier-provider').length) {
                    return;
                }

                var providerId = $('#edit-courier-provider').val();
                var $service = $('#edit-courier-service');
                var selected = String($service.data('selected') || $service.val() || '');
                var $message = $('#edit-courier-service-message')
                    .removeClass('text-danger')
                    .addClass('text-muted')
                    .text('');

                if (!providerId) {
                    $service.html('<option value="">{{ translate('Select provider to load services') }}</option>').prop('disabled', true);
                    return;
                }

                $service.html('<option value="">{{ translate('Loading services...') }}</option>').prop('disabled', true);
                $.ajax({
                    url: courierRatesUrl,
                    method: 'POST',
                    dataType: 'json',
                    headers: {'X-CSRF-TOKEN': csrf},
                    data: {
                        order_id: orderId,
                        shipping_method_id: providerId,
                        payment_type: $('select[name="payment_type"]').val(),
                        weight_grams: $('input[name="weight_grams"]').val(),
                        length_cm: $('input[name="length_cm"]').val(),
                        width_cm: $('input[name="width_cm"]').val(),
                        height_cm: $('input[name="height_cm"]').val()
                    }
                }).done(function (response) {
                    var services = response && response.success && Array.isArray(response.data) ? response.data : [];
                    $service.empty().append('<option value="">{{ translate('Select Courier Service') }}</option>');
                    services.forEach(function (service, index) {
                        var value = String(service.carrier_id || service.id || index);
                        var price = service.price === null || service.price === undefined
                            ? ''
                            : ' - ' + Number(service.price || 0).toFixed(3);
                        $service.append('<option value="' + escapeHtml(value) + '">' + escapeHtml(service.name || '{{ translate('Courier Service') }}') + price + '</option>');
                    });
                    $service.prop('disabled', !services.length);
                    if (selected && services.some(function (service, index) {
                        return String(service.carrier_id || service.id || index) === selected;
                    })) {
                        $service.val(selected);
                    }
                    if (!services.length) {
                        $message.removeClass('text-muted').addClass('text-danger')
                            .text((response && response.message) || '{{ translate('No courier services are available.') }}');
                    }
                }).fail(function (xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : '{{ translate('Unable to load courier services.') }}';
                    $service.html('<option value="">{{ translate('No services available') }}</option>').prop('disabled', true);
                    $message.removeClass('text-muted').addClass('text-danger').text(message);
                });
            }

            function renderSelectedFiles(input) {
                var $input = $(input);
                var $list = $($input.data('list')).empty();
                var files = Array.prototype.slice.call(input.files || []);
                if (!files.length) {
                    $list.html('<span class="text-muted">{{ translate('No files selected') }}</span>');
                    return;
                }
                files.forEach(function (file, index) {
                    $list.append('<div class="selected-file-chip"><span class="text-truncate">' + escapeHtml(file.name) + '</span>'
                        + '<button type="button" class="btn btn-xs btn-soft-danger remove-selected-file" data-input="' + input.id + '" data-index="' + index + '" aria-label="{{ translate('Remove file') }}">&times;</button></div>');
                });
            }

            function syncConsigneeCopy() {
                var attached = $('#edit-consignee-copy-status').val() === 'attached';
                $('#edit-consignee-files-wrap').toggleClass('d-none', !attached);
                $('#edit-cc-attachments').prop('required', attached && !hasExistingConsigneeCopy);
            }

            function syncShippingCost() {
                var freeShipping = $('#edit-shipping-cost-type').val() === 'free_shipping';
                $('#edit-free-shipping').val(freeShipping ? 1 : 0);
                $('#edit-sell-amount-wrap').toggleClass('d-none', freeShipping);
                $('#edit-sell-amount').prop('disabled', freeShipping).prop('required', !freeShipping);
            }

            function updateWeightDisplay() {
                var grams = Number($('#edit-weight-grams').val());
                var kilograms = Number.isFinite(grams) && grams >= 0 ? grams / 1000 : 0;
                $('#edit-weight-kg-display').text((Math.round(kilograms * 1000000) / 1000000) + ' KG');
            }

            function filterBookedTo() {
                if (shippingChoice !== 'transport') {
                    return;
                }
                var transportId = String($('#edit-transport-id').val() || '');
                var $bookedTo = $('#edit-booked-to-id');
                var selected = String($bookedTo.data('selected') || $bookedTo.val() || '');
                var matches = bookedToOptions.filter(function (option) {
                    return String(option.transport_id) === transportId;
                });
                $bookedTo.empty();
                $bookedTo.append('<option value="">' + (transportId ? '{{ translate('Select Booked To') }}' : '{{ translate('Select transport first') }}') + '</option>');
                matches.forEach(function (option) {
                    $bookedTo.append('<option value="' + option.id + '">' + escapeHtml(option.name) + '</option>');
                });
                $bookedTo.prop('disabled', !transportId);
                if (selected && matches.some(function (option) { return String(option.id) === selected; })) {
                    $bookedTo.val(selected);
                } else if (selected) {
                    $bookedTo.val('');
                }
                $bookedTo.data('selected', $bookedTo.val() || '');
            }

            function syncSurfaceMode() {
                if (shippingChoice !== 'transport') {
                    return;
                }
                var isSurface = $('#edit-fod-mode').val() === 'surface';
                $('#edit-transport-surface-mode-wrap').toggleClass('d-none', !isSurface);
                $('#edit-transport-surface-mode').prop('disabled', !isSurface);
            }

            function syncPortLogistics() {
                if (shippingChoice !== 'transport') {
                    return;
                }
                var mode = $('#edit-fod-mode').val();
                var usesPorts = mode === 'sea' || mode === 'air';
                $('#edit-port-logistics-wrap').toggleClass('d-none', !usesPorts);
                $('#edit-sea-logistics-fields').toggleClass('d-none', mode !== 'sea');
                $('#edit-air-logistics-fields').toggleClass('d-none', mode !== 'air');
                $('#edit-loading-location-type').val(usesPorts ? mode : '');
                $('#edit-discharge-location-type').val(usesPorts ? mode : '');
                $('#edit-loading-sea-port-id, #edit-discharge-sea-port-id').prop('disabled', mode !== 'sea');
                $('#edit-loading-airport-id, #edit-discharge-airport-id').prop('disabled', mode !== 'air');
            }

            $('#edit-consignee-copy-status').on('change', syncConsigneeCopy);
            $('#edit-shipping-cost-type').on('change', syncShippingCost);
            $('#edit-weight-grams').on('input change', updateWeightDisplay);
            $('#edit-transport-id').on('change', filterBookedTo);
            $('#edit-booked-to-id').on('change', function () {
                $(this).data('selected', $(this).val() || '');
            });
            $('#edit-fod-mode').on('change', function () {
                syncSurfaceMode();
                syncPortLogistics();
            });
            $('#edit-courier-provider').on('change', function () {
                $('#edit-courier-service').data('selected', '');
                loadCourierServices();
            });
            $('#edit-load-courier-services').on('click', loadCourierServices);
            $('#edit-courier-service').on('change', function () {
                $(this).data('selected', $(this).val() || '');
            });
            $('.multi-file-input').on('change', function () {
                renderSelectedFiles(this);
            });
            $(document).on('click', '.remove-selected-file', function () {
                var input = document.getElementById($(this).data('input'));
                if (!input) {
                    return;
                }
                var files = Array.prototype.slice.call(input.files || []);
                var removeIndex = Number($(this).data('index'));
                if (Number.isNaN(removeIndex) || !files.length) {
                    return;
                }
                var nextFiles = new DataTransfer();
                files.forEach(function (file, index) {
                    if (index !== removeIndex) {
                        nextFiles.items.add(file);
                    }
                });
                input.files = nextFiles.files;
                renderSelectedFiles(input);
                syncConsigneeCopy();
            });
            $(document).on('click', '.delete-existing-attachment', function () {
                var url = $(this).data('url');
                var name = $(this).data('name') || '';
                if (!url || !window.confirm('{{ translate('Delete this attachment?') }}' + (name ? '\n' + name : ''))) {
                    return;
                }
                $('<form method="POST"></form>')
                    .attr('action', url)
                    .append($('<input type="hidden" name="_token">').val(csrf))
                    .append($('<input type="hidden" name="_method" value="DELETE">'))
                    .appendTo(document.body)
                    .trigger('submit');
            });

            syncConsigneeCopy();
            syncShippingCost();
            updateWeightDisplay();
            filterBookedTo();
            syncSurfaceMode();
            syncPortLogistics();
        })();
    </script>
@endsection
