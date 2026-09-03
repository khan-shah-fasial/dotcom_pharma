@extends('backend.layouts.app')

@section('content')
    @php
        $shippingAddress = json_decode($order->shipping_address);
        $billingAddress = json_decode($order->billing_address);
        $deliveryStatus = $order->delivery_status;
        $paymentStatus = $order->payment_status;
        $adminUserId = get_admin()->id;
        $isOrderOwner = $order->seller_id == $adminUserId || get_setting('product_manage_by_admin') == 1;
        $displayValue = fn ($value) => filled($value) ? $value : '—';
        $formatDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d-m-Y') : '—';
        $formatAmount = fn ($value) => single_price((float) ($value ?? 0));
        $formatEnum = fn ($value) => filled($value) ? translate(ucwords(str_replace('_', ' ', $value))) : '—';
        $productDiscount = $order->orderDetails->sum(function ($detail) {
            return max(0, (float) ($detail->before_productandbatch_discount ?? 0) - (float) ($detail->sale_price ?? 0))
                * (int) ($detail->quantity ?? 0);
        });
        $loadingLocation = $order->loading_location_type === 'air'
            ? $order->loadingAirport
            : $order->loadingSeaPort;
        $dischargeLocation = $order->discharge_location_type === 'air'
            ? $order->dischargeAirport
            : $order->dischargeSeaPort;
    @endphp

    <style>
        .order-detail-card .card-header { background: #f8fafc; }
        .order-detail-table th { width: 38%; color: #4b5563; font-weight: 600; }
        .order-detail-table td { word-break: break-word; }
        .order-detail-table th,
        .order-detail-table td { padding: .55rem .75rem; vertical-align: top; }
        .status-chip {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            color: #14532d;
            background: #dcfce7;
            border: 1px solid #86efac;
            border-radius: 999px;
            font-weight: 700;
        }
        .address-block { white-space: pre-line; }
        .summary-total td { border-top: 2px solid #94a3b8; font-size: 1.05rem; font-weight: 700; }
    </style>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h2 fs-16 mb-0">{{ translate('Order Details') }}</h1>
        <div class="d-flex align-items-center">
            @can('add_order')
                <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-sm btn-soft-primary mr-2">
                    <i class="las la-edit mr-1"></i>{{ translate('Edit Order') }}
                </a>
            @endcan
            <a href="{{ route('invoice.download', $order->id) }}" class="btn btn-sm btn-light">
                <i class="las la-print mr-1"></i>{{ translate('Invoice') }}
            </a>
        </div>
    </div>

    <div class="card order-detail-card">
        <div class="card-body">
            <div class="row gutters-10 align-items-center">
                <div class="col-md">
                    @if ($order->shipping_choice === 'courier' && (!$order->shipment || $order->shipment->status === 'error'))
                        <button id="create-shipment-btn" class="btn btn-primary" type="button"
                            data-order="{{ encrypt($order->id) }}"
                            data-provider="{{ $order->shipping_by ?? '' }}"
                            data-total="{{ (float) $order->grand_total }}">
                            {{ translate('Create Shipment') }}
                        </button>
                    @endif
                </div>

                @if ($isOrderOwner)
                    @if (addon_is_activated('delivery_boy'))
                        <div class="col-md-3 mt-2 mt-md-0">
                            <label for="assign-deliver-boy">{{ translate('Assign Delivery Boy') }}</label>
                            @if (in_array($deliveryStatus, ['pending', 'confirmed', 'picked_up']) && auth()->user()->can('assign_delivery_boy_for_orders'))
                                <select class="form-control aiz-selectpicker" data-live-search="true" id="assign-deliver-boy">
                                    <option value="">{{ translate('Select Delivery Boy') }}</option>
                                    @foreach ($delivery_boys as $deliveryBoy)
                                        <option value="{{ $deliveryBoy->id }}" @selected($order->assign_delivery_boy == $deliveryBoy->id)>
                                            {{ $deliveryBoy->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input class="form-control" value="{{ optional($order->delivery_boy)->name }}" disabled>
                            @endif
                        </div>
                    @endif

                    <div class="col-md-3 mt-2 mt-md-0">
                        <label for="update-payment-status">{{ translate('Payment Status') }}</label>
                        @if (auth()->user()->can('update_order_payment_status') && $paymentStatus === 'unpaid')
                            <select class="form-control aiz-selectpicker" id="update-payment-status">
                                <option value="unpaid" selected>{{ translate('Unpaid') }}</option>
                                <option value="paid">{{ translate('Paid') }}</option>
                            </select>
                        @else
                            <input class="form-control" value="{{ ucfirst($paymentStatus) }}" disabled>
                        @endif
                    </div>

                    <div class="col-md-3 mt-2 mt-md-0">
                        <label for="update-delivery-status">{{ translate('Delivery Status') }}</label>
                        @if (auth()->user()->can('update_order_delivery_status') && !in_array($deliveryStatus, ['delivered', 'cancelled']))
                            <select class="form-control aiz-selectpicker" id="update-delivery-status">
                                @foreach (['pending' => 'Pending', 'confirmed' => 'Confirmed', 'picked_up' => 'Picked Up', 'on_the_way' => 'On The Way', 'delivered' => 'Delivered', 'cancelled' => 'Cancel'] as $value => $label)
                                    <option value="{{ $value }}" @selected($deliveryStatus === $value)>{{ translate($label) }}</option>
                                @endforeach
                            </select>
                        @else
                            <input class="form-control" value="{{ $formatEnum($deliveryStatus) }}" disabled>
                        @endif
                    </div>

                    <div class="col-md-3 mt-2 mt-md-0">
                        <label for="update-tracking-code">{{ translate('Tracking Code') }}</label>
                        <input type="text" class="form-control" id="update-tracking-code" value="{{ $order->tracking_code }}">
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row gutters-10 mt-1">
        <div class="col-lg-6">
            <div class="card order-detail-card h-100">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('Order Information') }}</h5></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 order-detail-table">
                        <tbody>
                            <tr><th>{{ translate('Order No.') }}</th><td class="text-info fw-700">{{ $order->code }}</td></tr>
                            <tr><th>{{ translate('Company') }}</th><td>{{ $displayValue(issuing_company_label_for_order($order)) }}</td></tr>
                            <tr><th>{{ translate('Order Date') }}</th><td>{{ $order->order_date ? $formatDate($order->order_date) : date('d-m-Y', $order->date) }}</td></tr>
                            <tr><th>{{ translate('Order Time') }}</th><td>{{ $order->order_time ? \Carbon\Carbon::parse($order->order_time)->format('h:i A') : date('h:i A', $order->date) }}</td></tr>
                            <tr><th>{{ translate('Order Status') }}</th><td><span class="status-chip">{{ $formatEnum($deliveryStatus) }}</span></td></tr>
                            <tr><th>{{ translate('Payment Terms') }}</th><td>{{ $formatEnum($order->payment_type) }}</td></tr>
                            <tr><th>{{ translate('Payment Status') }}</th><td>{{ $formatEnum($paymentStatus) }}</td></tr>
                            <tr><th>{{ translate('Additional Info') }}</th><td class="address-block">{{ $displayValue($order->additional_info) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mt-2 mt-lg-0">
            <div class="card order-detail-card h-100">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('Customer and Addresses') }}</h5></div>
                <div class="card-body">
                    <div class="row gutters-10">
                        <div class="col-md-6">
                            <h6 class="text-main">{{ translate('Billing Address') }}</h6>
                            @if ($billingAddress)
                                <div class="address-block">
                                    <strong>{{ $billingAddress->name ?? $billingAddress->contact_person ?? '—' }}</strong><br>
                                    {{ $billingAddress->email ?? '' }}<br>
                                    {{ $billingAddress->phone ?? '' }}<br>
                                    {{ $billingAddress->address ?? '—' }}<br>
                                    {{ collect([$billingAddress->village ?? null, $billingAddress->district ?? null, $billingAddress->city ?? null, $billingAddress->state ?? null, $billingAddress->postal_code ?? null, $billingAddress->country ?? null])->filter()->implode(', ') }}
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                        <div class="col-md-6 mt-3 mt-md-0">
                            <h6 class="text-main">{{ translate('Shipping Address') }}</h6>
                            @if ($shippingAddress)
                                <div class="address-block">
                                    <strong>{{ $shippingAddress->name ?? $shippingAddress->contact_person ?? optional($order->user)->name ?? '—' }}</strong><br>
                                    {{ $shippingAddress->email ?? optional($order->user)->email ?? '' }}<br>
                                    {{ $shippingAddress->phone ?? '' }}<br>
                                    {{ $shippingAddress->address ?? '—' }}<br>
                                    {{ collect([$shippingAddress->village ?? null, $shippingAddress->district ?? null, $shippingAddress->city ?? null, $shippingAddress->state ?? null, $shippingAddress->postal_code ?? null, $shippingAddress->country ?? null])->filter()->implode(', ') }}
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div><strong>{{ translate('Same as Billing Address') }}:</strong> —</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card order-detail-card mt-3">
        <div class="card-header"><h5 class="mb-0 h6">{{ translate('Products') }}</h5></div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr class="bg-trans-dark">
                        <th>#</th>
                        <th>{{ translate('Product') }}</th>
                        <th>{{ translate('Variant / Batch') }}</th>
                        <th class="text-right">{{ translate('Qty') }}</th>
                        <th class="text-right">{{ translate('Scheme Qty') }}</th>
                        <th class="text-right">{{ translate('Rate') }}</th>
                        <th class="text-right">{{ translate('GST') }}</th>
                        <th class="text-right">{{ translate('MRP') }}</th>
                        <th class="text-right">{{ translate('Final') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($order->orderDetails as $key => $detail)
                        @php
                            $isSchemeLine = (bool) ($detail->is_scheme ?? false);
                            $unitPrice = $detail->sale_price !== null
                                ? (float) $detail->sale_price
                                : ((int) $detail->quantity > 0 ? (float) $detail->price / (int) $detail->quantity : 0);
                            $lineProductDiscount = max(0, (float) ($detail->before_productandbatch_discount ?? $unitPrice) - $unitPrice)
                                * (int) $detail->quantity;
                            $lineCouponDiscount = max(0, (float) ($detail->discount_amount ?? 0) - $lineProductDiscount);
                            $lineFinal = max(0, (float) $detail->price + (float) $detail->tax - $lineCouponDiscount);
                            $product = $detail->product;
                            $batch = $detail->batch;
                        @endphp
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-start">
                                    @if ($product)
                                        <img class="mr-2" height="42" src="{{ uploaded_asset($product->thumbnail_img) }}" alt="">
                                    @endif
                                    <div>
                                        <strong>{{ $product ? $product->getTranslation('name') : translate('Product Unavailable') }}</strong>
                                        @if ($isSchemeLine)<span class="badge badge-success ml-1">{{ translate('Free') }}</span>@endif
                                        <div class="small text-muted">{{ translate('SKU') }}: {{ $product?->stocks?->where('variant', $detail->variation)->first()?->sku ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                {{ $displayValue($detail->variation) }}<br>
                                <small>{{ translate('Batch') }}: {{ $batch?->batch ?? '—' }}</small>
                                @if ($batch)
                                    <br><small>{{ translate('Mfg.') }}: {{ $formatDate($batch->manufacturing_date ?? $batch->mfg_date ?? null) }} | {{ translate('Expiry') }}: {{ $formatDate($batch->expiry_date ?? null) }}</small>
                                @endif
                            </td>
                            <td class="text-right">{{ $detail->quantity }}</td>
                            <td class="text-right">{{ $isSchemeLine ? $detail->quantity : 0 }}</td>
                            <td class="text-right">{{ $formatAmount($unitPrice) }}</td>
                            <td class="text-right">{{ $formatAmount($detail->tax) }}</td>
                            <td class="text-right">{{ $formatAmount($detail->mrp_price) }}</td>
                            <td class="text-right">{{ $formatAmount($lineFinal) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">{{ translate('No products found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row gutters-10 mt-1">
        <div class="col-lg-6">
            <div class="card order-detail-card h-100">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('Additional Details') }}</h5></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 order-detail-table">
                        <tbody>
                            <tr><th>{{ translate('Sales Executive') }}</th><td>{{ optional($order->salesExecutive ?: $order->salesPerson)->name ?: '—' }}</td></tr>
                            <tr><th>{{ translate('Sales Man Code') }}</th><td>{{ $displayValue($order->sales_man_code) }}</td></tr>
                            <tr><th>{{ translate('Packed By') }}</th><td>{{ optional($order->packedByStaff)->name ?: '—' }}</td></tr>
                            <tr><th>{{ translate('Checked By') }}</th><td>{{ optional($order->checkedByStaff)->name ?: '—' }}</td></tr>
                            <tr><th>{{ translate('Billing By') }}</th><td>{{ optional($order->billingByStaff)->name ?: '—' }}</td></tr>
                            <tr><th>{{ translate('P.O. No. / Date') }}</th><td>{{ $displayValue($order->po_number) }} / {{ $formatDate($order->po_date) }}</td></tr>
                            <tr><th>{{ translate('LR / GR / Doc / Vehicle / AWB No.') }}</th><td>{{ $displayValue($order->lr_number) }} / {{ $formatDate($order->lr_date) }}</td></tr>
                            <tr><th>{{ translate('Attached File Name') }}</th><td>{{ $displayValue($order->attached_file_name) }}</td></tr>
                            <tr><th>{{ translate('Send Order Notification') }}</th><td>—</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mt-2 mt-lg-0">
            <div class="card order-detail-card h-100">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('Attachments') }}</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>{{ translate('Consignee Copy') }}:</strong>
                        {{ $order->consignee_copy_status === 'attached' || $order->cc_attached_path || $order->attachments->where('category', 'consignee_copy')->isNotEmpty() ? translate('Attached') : translate('Not Attached') }}
                    </div>
                    @forelse ($order->attachments as $attachment)
                        <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                            <div class="text-break">
                                <span class="badge badge-soft-secondary mr-1">
                                    {{ $attachment->category === 'consignee_copy' ? translate('Consignee Copy') : translate('Order') }}
                                </span>
                                <a href="{{ asset('storage/' . $attachment->path) }}" target="_blank" rel="noopener">{{ $attachment->original_name }}</a>
                            </div>
                            <form method="POST" action="{{ route('orders.attachments.destroy', [$order, $attachment]) }}" onsubmit="return confirm('{{ translate('Remove this attachment?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-soft-danger" title="{{ translate('Remove') }}">&times;</button>
                            </form>
                        </div>
                    @empty
                        @if ($order->cc_attached_path)
                            <a href="{{ asset('storage/' . $order->cc_attached_path) }}" target="_blank" rel="noopener">
                                {{ $order->attached_file_name ?: basename($order->cc_attached_path) }}
                            </a>
                        @else
                            <span class="text-muted">{{ translate('No attachments') }}</span>
                        @endif
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row gutters-10 mt-1">
        <div class="col-lg-6">
            <div class="card order-detail-card h-100">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('Shipping and Transport') }}</h5></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 order-detail-table">
                        <tbody>
                            <tr><th>{{ translate('Shipping Method') }}</th><td>{{ $formatEnum($order->shipping_choice) }}</td></tr>
                            <tr><th>{{ translate('Provider') }}</th><td>
                                @if ($order->shipping_choice === 'transport')
                                    {{ optional($order->transport)->name ?? $order->shipping_by ?? '—' }}
                                @elseif ($order->shipping_choice === 'local')
                                    {{ optional($order->localDeliveryPartner)->name ?? $order->shipping_by ?? '—' }}
                                @else
                                    {{ $displayValue($order->shipping_by) }}
                                @endif
                            </td></tr>
                            <tr><th>{{ translate('Courier Service') }}</th><td>{{ $displayValue($order->shipping_courier_id) }}</td></tr>
                            <tr><th>{{ translate('Transport Mode') }}</th><td>{{ $formatEnum($order->transport_mode ?? $order->fod_mode) }}{{ $order->transport_surface_mode ? ' / ' . $formatEnum($order->transport_surface_mode) : '' }}</td></tr>
                            <tr><th>{{ translate('Booked To') }}</th><td>{{ optional($order->bookedTo)->name ?: '—' }}</td></tr>
                            <tr><th>{{ translate('Terms Of Delivery') }}</th><td>{{ $formatEnum($order->transport_delivery_type) }}</td></tr>
                            <tr><th>{{ translate('Freight') }}</th><td>{{ $formatEnum($order->freight_type) }}</td></tr>
                            <tr><th>{{ translate('Shipping Cost') }}</th><td>{{ $order->free_shipping ? translate('Free Shipping') : translate('By Seller') }}</td></tr>
                            <tr><th>{{ translate('Consignee Copy') }}</th><td>{{ $order->consignee_copy_status === 'attached' ? translate('Attached') : translate('Not Attached') }}</td></tr>
                            <tr><th>{{ translate('Carrier GST No.') }}</th><td>{{ $displayValue($order->carrier_tax_number) }}</td></tr>
                            <tr><th>{{ translate('Transport Details') }}</th><td class="address-block">{{ $displayValue($order->transport_details) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mt-2 mt-lg-0">
            <div class="card order-detail-card h-100">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('Shipment Measurements') }}</h5></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 order-detail-table">
                        <tbody>
                            <tr><th>{{ translate('Total Cases') }}</th><td>{{ $displayValue($order->cases) }}</td></tr>
                            <tr><th>{{ translate('Weight') }}</th><td>
                                @if ($order->weight_grams !== null)
                                    {{ (float) $order->weight_grams }} {{ translate('Gram') }} / {{ (float) $order->weight_kg }} {{ translate('KG') }}
                                @else
                                    {{ $displayValue($order->weight) }}
                                @endif
                            </td></tr>
                            <tr><th>{{ translate('Dimensions') }}</th><td>{{ $displayValue($order->dimensions) }}</td></tr>
                            <tr><th>{{ translate('Net Weight') }}</th><td>{{ $order->net_weight_kg !== null ? (float) $order->net_weight_kg . ' KG' : '—' }}</td></tr>
                            <tr><th>{{ translate('Gross Weight') }}</th><td>{{ $order->gross_weight_kg !== null ? (float) $order->gross_weight_kg . ' KG' : '—' }}</td></tr>
                            <tr><th>{{ translate('Total Volume / CBM') }}</th><td>{{ $displayValue($order->total_volume_cbm) }}</td></tr>
                            <tr><th>{{ translate('Reverse Charges') }}</th><td>{{ $order->reverse_charge === null ? '—' : ($order->reverse_charge ? translate('Yes') : translate('No')) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if ($loadingLocation || $dischargeLocation || $order->loading_location_type || $order->discharge_location_type || $order->final_destination)
        <div class="card order-detail-card mt-3">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('International Logistics') }}</h5></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0 order-detail-table">
                    <tbody>
                        <tr>
                            <th>{{ translate('Loading Transport Type') }}</th>
                            <td>{{ $formatEnum($order->loading_location_type) }}</td>
                        </tr>
                        <tr>
                            <th>{{ $order->loading_location_type === 'air' ? translate('Departure') : translate('Port Of Loading') }}</th>
                            <td>{{ $loadingLocation ? $loadingLocation->name . ($loadingLocation->un_locode ? ' (' . $loadingLocation->un_locode . ')' : ($loadingLocation->iata ? ' (' . $loadingLocation->iata . ')' : '')) : '—' }}</td>
                        </tr>
                        <tr>
                            <th>{{ translate('Discharge Transport Type') }}</th>
                            <td>{{ $formatEnum($order->discharge_location_type) }}</td>
                        </tr>
                        <tr>
                            <th>{{ $order->discharge_location_type === 'air' ? translate('Arrival') : translate('Destination Port Of Discharge') }}</th>
                            <td>{{ $dischargeLocation ? $dischargeLocation->name . ($dischargeLocation->un_locode ? ' (' . $dischargeLocation->un_locode . ')' : ($dischargeLocation->iata ? ' (' . $dischargeLocation->iata . ')' : '')) : '—' }}</td>
                        </tr>
                        <tr><th>{{ translate('Final Destination') }}</th><td>{{ $displayValue($order->final_destination) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card order-detail-card mt-3">
        <div class="card-header"><h5 class="mb-0 h6">{{ translate('Order Summary') }}</h5></div>
        <div class="card-body">
            <div class="row justify-content-end">
                <div class="col-md-6 col-lg-4">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><td>{{ translate('Product Subtotal') }}</td><td class="text-right">{{ $formatAmount($order->orderDetails->sum('price')) }}</td></tr>
                            <tr><td>{{ translate('Less: Product / Batch Wise Discount') }}</td><td class="text-right text-danger">- {{ $formatAmount($productDiscount) }}</td></tr>
                            <tr><td>{{ translate('Less: Coupon / Additional Discount') }}</td><td class="text-right text-danger">- {{ $formatAmount($order->coupon_discount) }}</td></tr>
                            <tr><td>{{ translate('Add: Shipping / Freight') }}</td><td class="text-right">{{ $formatAmount($order->orderDetails->sum('shipping_cost')) }}</td></tr>
                            <tr><td>{{ translate('Total Tax Value') }}</td><td class="text-right">{{ $formatAmount($order->orderDetails->sum('tax')) }}</td></tr>
                            <tr class="summary-total"><td>{{ translate('Grand Total') }}</td><td class="text-right">{{ $formatAmount($order->grand_total) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('components.location-info', ['data' => getStoredIPLocation('orders', $order->id)])
@endsection

@section('modal')
    <div id="confirm-payment-status" class="modal fade">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <p class="mb-3 fs-16 fw-700">{{ translate('Are you sure you want to change the payment status?') }}</p>
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" id="confirm-payment-status-btn" class="btn btn-success">{{ translate('Confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function () {
            var orderId = {{ $order->id }};
            var csrf = '{{ csrf_token() }}';

            $('#create-shipment-btn').on('click', function () {
                var button = $(this);
                var provider = button.data('provider');
                var total = parseFloat(button.data('total')) || 0;
                var ewaybill = '';

                if (!provider || provider === 'N/A') {
                    AIZ.plugins.notify('danger', '{{ translate('No shipment provider configured for this order.') }}');
                    return;
                }

                if (String(provider).toLowerCase() === 'delhivery' && total > 50000) {
                    ewaybill = prompt('{{ translate('Enter E-waybill number for this Delhivery shipment:') }}');
                    if (!ewaybill || !ewaybill.trim()) {
                        AIZ.plugins.notify('danger', '{{ translate('E-waybill number is required for Delhivery shipments above Rs.50,000.') }}');
                        return;
                    }
                }

                if (!confirm('{{ translate('Create shipment with provider:') }} ' + provider + ' ?')) {
                    return;
                }

                button.prop('disabled', true);
                $.post('{{ route('shipment.create') }}', {
                    _token: csrf,
                    provider: provider,
                    order: button.data('order'),
                    ewaybill: ewaybill
                }).done(function (payload) {
                    if (payload && payload.success) {
                        AIZ.plugins.notify('success', payload.message || '{{ translate('Shipment created successfully.') }}');
                        window.setTimeout(function () { window.location.reload(); }, 800);
                        return;
                    }
                    AIZ.plugins.notify('danger', (payload && payload.message) || '{{ translate('Failed to create shipment.') }}');
                    button.prop('disabled', false);
                }).fail(function (xhr) {
                    AIZ.plugins.notify('danger', (xhr.responseJSON && xhr.responseJSON.message) || '{{ translate('Unable to create shipment.') }}');
                    button.prop('disabled', false);
                });
            });

            $('#assign-deliver-boy').on('change', function () {
                $.post('{{ route('orders.delivery-boy-assign') }}', {
                    _token: csrf,
                    order_id: orderId,
                    delivery_boy: this.value
                }).done(function () {
                    AIZ.plugins.notify('success', '{{ translate('Delivery boy has been assigned') }}');
                });
            });

            $('#update-delivery-status').on('change', function () {
                $.post('{{ route('orders.update_delivery_status') }}', {
                    _token: csrf,
                    order_id: orderId,
                    status: this.value
                }).done(function () {
                    AIZ.plugins.notify('success', '{{ translate('Delivery status has been updated') }}');
                });
            });

            $('#update-payment-status').on('change', function () {
                if (this.value === 'paid') {
                    $('#confirm-payment-status').modal('show');
                }
            });

            $('#confirm-payment-status-btn').on('click', function () {
                $('#confirm-payment-status').modal('hide');
                $.post('{{ route('orders.update_payment_status') }}', {
                    _token: csrf,
                    order_id: orderId,
                    status: 'paid'
                }).done(function () {
                    $('#update-payment-status').prop('disabled', true);
                    AIZ.plugins.bootstrapSelect('refresh');
                    AIZ.plugins.notify('success', '{{ translate('Payment status has been updated') }}');
                });
            });

            $('#update-tracking-code').on('change', function () {
                $.post('{{ route('orders.update_tracking_code') }}', {
                    _token: csrf,
                    order_id: orderId,
                    tracking_code: this.value
                }).done(function () {
                    AIZ.plugins.notify('success', '{{ translate('Order tracking code has been updated') }}');
                });
            });
        })();
    </script>
@endsection
