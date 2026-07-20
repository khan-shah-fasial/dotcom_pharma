@extends('backend.layouts.app')

@section('content')

    <style>
        .apple-green-highlight {
            display: inline-flex;
            align-items: center;
            padding: 7px 12px;
            color: #14532d;
            background: linear-gradient(180deg, #ecfdf3 0%, #dcfce7 100%);
            border: 1px solid #86efac;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(34, 197, 94, .12), inset 0 1px 0 rgba(255, 255, 255, .9);
            font-weight: 700;
        }
    </style>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h1 class="h2 fs-16 mb-0">{{ translate('Order Details') }}</h1>
            @can('add_order')
                <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-sm btn-soft-primary">
                    <i class="las la-edit mr-1"></i>{{ translate('Edit Order') }}
                </a>
            @endcan
        </div>
        <div class="card-body">
            <div class="row gutters-5">
                <div class="col text-md-left text-center">
                    {{-- show.blade.php --}}
                    @if(isset($order) && $order->shipping_choice === 'courier' && (!$order->shipment || $order->shipment->status === 'error'))
                        <button id="create-shipment-btn"
                                class="btn btn-primary"
                                data-order="{{ encrypt($order->id) }}"
                                data-provider="{{ $order->shipping_by ?? '' }}"
                                data-total="{{ (float) $order->grand_total }}"
                                type="button">
                            Create Shipment
                        </button>
                    @endif
                </div>
                @php
                    $delivery_status = $order->delivery_status;
                    $payment_status = $order->payment_status;
                    $admin_user_id = get_admin()->id;
                @endphp
                @if ($order->seller_id == $admin_user_id || get_setting('product_manage_by_admin') == 1)

                    <!--Assign Delivery Boy-->
                    @if (addon_is_activated('delivery_boy'))
                        <div class="col-md-3 ml-auto">
                            <label for="assign_deliver_boy">{{ translate('Assign Deliver Boy') }}</label>
                            @if (($delivery_status == 'pending' || $delivery_status == 'confirmed' || $delivery_status == 'picked_up') && auth()->user()->can('assign_delivery_boy_for_orders'))
                                <select class="form-control aiz-selectpicker" data-live-search="true"
                                    data-minimum-results-for-search="Infinity" id="assign_deliver_boy">
                                    <option value="">{{ translate('Select Delivery Boy') }}</option>
                                    @foreach ($delivery_boys as $delivery_boy)
                                        <option value="{{ $delivery_boy->id }}"
                                            @if ($order->assign_delivery_boy == $delivery_boy->id) selected @endif>
                                            {{ $delivery_boy->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control" value="{{ optional($order->delivery_boy)->name }}"
                                    disabled>
                            @endif
                        </div>
                    @endif

                    <div class="col-md-3 ml-auto">
                        <label for="update_payment_status">{{ translate('Payment Status') }}</label>
                        @if (auth()->user()->can('update_order_payment_status') && $payment_status == 'unpaid')
                            {{-- <select class="form-control aiz-selectpicker" data-minimum-results-for-search="Infinity" id="update_payment_status"> --}}
                            <select class="form-control aiz-selectpicker" data-minimum-results-for-search="Infinity" id="update_payment_status" onchange="confirm_payment_status()">
                                <option value="unpaid" @if ($payment_status == 'unpaid') selected @endif>
                                    {{ translate('Unpaid') }}
                                </option>
                                <option value="paid" @if ($payment_status == 'paid') selected @endif>
                                    {{ translate('Paid') }}
                                </option>
                            </select>
                        @else
                            <input type="text" class="form-control" value="{{ ucfirst($payment_status) }}" disabled>
                        @endif
                    </div>
                    <div class="col-md-3 ml-auto">
                        <label for="update_delivery_status">{{ translate('Delivery Status') }}</label>
                        @if (auth()->user()->can('update_order_delivery_status') && $delivery_status != 'delivered' && $delivery_status != 'cancelled')
                            <select class="form-control aiz-selectpicker" data-minimum-results-for-search="Infinity"
                                id="update_delivery_status">
                                <option value="pending" @if ($delivery_status == 'pending') selected @endif>
                                    {{ translate('Pending') }}
                                </option>
                                <option value="confirmed" @if ($delivery_status == 'confirmed') selected @endif>
                                    {{ translate('Confirmed') }}
                                </option>
                                <option value="picked_up" @if ($delivery_status == 'picked_up') selected @endif>
                                    {{ translate('Picked Up') }}
                                </option>
                                <option value="on_the_way" @if ($delivery_status == 'on_the_way') selected @endif>
                                    {{ translate('On The Way') }}
                                </option>
                                <option value="delivered" @if ($delivery_status == 'delivered') selected @endif>
                                    {{ translate('Delivered') }}
                                </option>
                                <option value="cancelled" @if ($delivery_status == 'cancelled') selected @endif>
                                    {{ translate('Cancel') }}
                                </option>
                            </select>
                        @else
                            <input type="text" class="form-control" value="{{ $delivery_status }}" disabled>
                        @endif
                    </div>
                    <div class="col-md-3 ml-auto">
                        <label for="update_tracking_code">
                            {{ translate('Tracking Code (optional)') }}
                        </label>
                        <input type="text" class="form-control" id="update_tracking_code"
                            value="{{ $order->tracking_code }}">
                    </div>
                @endif
            </div>
            <div class="mb-3">
                @php
                    $removedXML = '<?xml version="1.0" encoding="UTF-8"?>';
                @endphp
                {!! str_replace($removedXML, '', QrCode::size(100)->generate($order->code)) !!}
            </div>
            <div class="row gutters-5">
                <div class="col text-md-left text-center">
                    @if(json_decode($order->shipping_address))
                        <address>
                            <strong class="text-main">
                                {{ json_decode($order->shipping_address)->name }}
                            </strong><br>
                            {{ json_decode($order->shipping_address)->email }}<br>
                            {{ json_decode($order->shipping_address)->phone }}<br>
                            {{ json_decode($order->shipping_address)->address }}, {{ json_decode($order->shipping_address)->city }}, @if(isset(json_decode($order->shipping_address)->state)) {{ json_decode($order->shipping_address)->state }} - @endif {{ json_decode($order->shipping_address)->postal_code }}<br>
                            {{ json_decode($order->shipping_address)->country }}
                        </address>
                    @else
                        <address>
                            <strong class="text-main">
                                {{ $order->user->name }}
                            </strong><br>
                            {{ $order->user->email }}<br>
                            {{ $order->user->phone }}<br>
                        </address>
                    @endif
                    @if ($order->manual_payment && is_array(json_decode($order->manual_payment_data, true)))
                        <br>
                        <strong class="text-main">{{ translate('Payment Information') }}</strong><br>
                        {{ translate('Name') }}: {{ json_decode($order->manual_payment_data)->name }},
                        {{ translate('Amount') }}:
                        {{ single_price(json_decode($order->manual_payment_data)->amount) }},
                        {{ translate('TRX ID') }}: {{ json_decode($order->manual_payment_data)->trx_id }}
                        <br>
                        <a href="{{ uploaded_asset(json_decode($order->manual_payment_data)->photo) }}" target="_blank">
                            <img src="{{ uploaded_asset(json_decode($order->manual_payment_data)->photo) }}" alt=""
                                height="100">
                        </a>
                    @endif
                </div>
                <div class="col-md-4">
                    <table class="ml-auto">
                        <tbody>
                            <tr>
                                <td class="text-main text-bold">{{ translate('Order #') }}</td>
                                <td class="text-info text-bold text-right"> {{ $order->code }}</td>
                            </tr>
                            <tr>
                                <td class="text-main text-bold">{{ translate('Order Status') }}</td>
                                <td class="text-right">
                                    <span class="apple-green-highlight">
                                        {{ translate(ucfirst(str_replace('_', ' ', $delivery_status))) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-main text-bold">{{ translate('Credit / Balance Amount') }}</td>
                                <td class="text-right"><span class="apple-green-highlight">{{ single_price(0) }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-main text-bold">{{ translate('Order Date') }} </td>
                                <td class="text-right">
                                    {{ $order->order_date ? $order->order_date->format('d-m-Y') : date('d-m-Y', $order->date) }}
                                    {{ $order->order_time ? \Carbon\Carbon::parse($order->order_time)->format('h:i A') : date('h:i A', $order->date) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-main text-bold">
                                    {{ translate('Total amount') }}
                                </td>
                                <td class="text-right">
                                    {{ single_price($order->grand_total) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-main text-bold">{{ translate('Payment method') }}</td>
                                <td class="text-right">
                                    {{ translate(ucfirst(str_replace('_', ' ', $order->payment_type))) }}</td>
                            </tr>
                            <tr>
                                <td class="text-main text-bold">{{ translate('Shipping Method') }}</td>
                                <td class="text-right">
                                    @if($order->shipping_choice === 'transport')
                                        {{ translate('Transport') }}: {{ optional($order->transport)->name ?? $order->shipping_by ?? '-' }}
                                        @if($order->bookedTo)
                                            <br>{{ translate('Booked To') }}: {{ $order->bookedTo->name }}
                                        @endif
                                        @if($order->transport_mode)
                                            <br>{{ translate('Mode') }}: {{ translate(ucfirst($order->transport_mode)) }}
                                            @if($order->transport_surface_mode)
                                                / {{ translate(ucfirst($order->transport_surface_mode)) }}
                                            @endif
                                        @endif
                                        @if($order->transport_delivery_type)
                                            <br>{{ translate([
                                                'transport_godown' => 'Transport Warehouse',
                                                'transport_warehouse' => 'Transport Warehouse',
                                                'our_warehouse_delivery' => 'Our Warehouse Delivery',
                                                'hand_delivery' => 'Hand Delivery',
                                                'door_delivery' => 'Door Delivery',
                                            ][$order->transport_delivery_type] ?? ucfirst(str_replace('_', ' ', $order->transport_delivery_type))) }}
                                        @endif
                                    @elseif($order->shipping_choice === 'local')
                                        {{ translate('Local') }}: {{ optional($order->localDeliveryPartner)->name ?? $order->shipping_by ?? '-' }}
                                    @else
                                        {{ $order->shipping_by ?? $order->shipping_choice ?? '-' }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-main text-bold">{{ translate('Additional Info') }}</td>
                                <td class="text-right">{{ $order->additional_info }}</td>
                            </tr>
                            <tr>
                                <td class="text-main text-bold">{{ translate('Total Cases') }}</td>
                                <td class="text-right">{{ $order->cases !== null && $order->cases !== '' ? $order->cases : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-main text-bold">{{ translate('LR / GR / Doc / Vehicle / AWB No.') }}</td>
                                <td class="text-right">{{ $order->lr_number ?: '-' }} @if($order->lr_date)<br>{{ $order->lr_date->format('d-m-Y') }}@endif</td>
                            </tr>
                            <tr>
                                <td class="text-main text-bold">{{ translate('Sales Executive / Sales Man Code') }}</td>
                                <td class="text-right">{{ optional($order->salesExecutive ?: $order->salesPerson)->name ?: '-' }} / {{ $order->sales_man_code ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-main text-bold">{{ translate('Weight / Dimensions') }}</td>
                                <td class="text-right">
                                    @if($order->weight_grams !== null)
                                        {{ (float) $order->weight_grams }} Gram = {{ (float) $order->weight_kg }} KG
                                    @else
                                        {{ $order->weight ?: '-' }}
                                    @endif
                                    / {{ $order->dimensions ?: '-' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-main text-bold">{{ translate('Freight / Shipping Cost') }}</td>
                                <td class="text-right">{{ $order->freight_type ? translate(ucwords(str_replace('_', ' ', $order->freight_type))) : '-' }} / {{ $order->free_shipping ? translate('Free Shipping') : translate('By Seller') }}</td>
                            </tr>
                            <tr>
                                <td class="text-main text-bold">{{ translate('Packed By / Checked By / Billing By') }}</td>
                                <td class="text-right">{{ optional($order->packedByStaff)->name ?: '-' }} / {{ optional($order->checkedByStaff)->name ?: '-' }} / {{ optional($order->billingByStaff)->name ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-main text-bold">{{ translate('Consignee Copy / Attachments') }}</td>
                                <td class="text-right">
                                    <div class="mb-1">{{ $order->consignee_copy_status === 'attached' || ($order->consignee_copy_status === null && ($order->cc_attached_path || $order->attachments->where('category', 'consignee_copy')->isNotEmpty())) ? translate('Attached') : translate('Not Attached') }}</div>
                                    @forelse($order->attachments as $attachment)
                                        <div class="d-flex align-items-center justify-content-end mb-1">
                                            <span class="badge badge-inline badge-soft-secondary mr-1">{{ $attachment->category === 'consignee_copy' ? translate('Consignee Copy') : translate('Order') }}</span>
                                            <a href="{{ asset('storage/' . $attachment->path) }}" target="_blank" rel="noopener" class="text-break">
                                                {{ $attachment->original_name }}
                                            </a>
                                            <form method="POST" action="{{ route('orders.attachments.destroy', [$order, $attachment]) }}" class="ml-2" onsubmit="return confirm('{{ translate('Remove this attachment?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-soft-danger" title="{{ translate('Remove') }}">&times;</button>
                                            </form>
                                        </div>
                                    @empty
                                        @if($order->cc_attached_path)
                                            <a href="{{ asset('storage/' . $order->cc_attached_path) }}" target="_blank" rel="noopener">{{ $order->attached_file_name ?: basename($order->cc_attached_path) }}</a>
                                        @else
                                            {{ $order->attached_file_name ?: '-' }}
                                        @endif
                                    @endforelse
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <hr class="new-section-sm bord-no">
            <div class="row">
                <div class="col-lg-12 table-responsive">
                    <table class="table-bordered aiz-table invoice-summary table">
                        <thead>
                            <tr class="bg-trans-dark">
                                <th data-breakpoints="lg" class="min-col">#</th>
                                <th width="10%">{{ translate('Photo') }}</th>
                                <th class="text-uppercase">{{ translate('Description') }}</th>
                                <th data-breakpoints="lg" class="text-uppercase">{{ translate('Delivery Type') }}</th>
                                <th data-breakpoints="lg" class="min-col text-uppercase text-center">
                                    {{ translate('Qty') }}
                                </th>
                                <th data-breakpoints="lg" class="min-col text-uppercase text-center">
                                    {{ translate('Price') }}</th>
                                <th data-breakpoints="lg" class="min-col text-uppercase text-right">
                                    {{ translate('MRP') }}</th>
                                <th data-breakpoints="lg" class="min-col text-uppercase text-right">
                                    {{ translate('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->orderDetails as $key => $orderDetail)
                                @php
                                    $isSchemeLine = (bool) ($orderDetail->is_scheme ?? false);
                                    $batchName = optional($orderDetail->batch)->batch;
                                    $detailQuantity = max(0, (int) ($orderDetail->quantity ?? 0));
                                    $detailSaleUnit = $orderDetail->sale_price !== null
                                        ? (float) $orderDetail->sale_price
                                        : ($detailQuantity > 0 ? (float) ($orderDetail->price ?? 0) / $detailQuantity : 0);
                                    $detailBaseUnit = $orderDetail->before_productandbatch_discount ?? $detailSaleUnit;
                                    $detailProductDiscount = round(max(0, (float) $detailBaseUnit - $detailSaleUnit) * $detailQuantity, 2);
                                    $detailCouponDiscount = round(max(0, (float) ($orderDetail->discount_amount ?? 0) - $detailProductDiscount), 2);
                                    $product_stock = $orderDetail->product
                                        ? $orderDetail->product->stocks->where('variant', $orderDetail->variation)->first()
                                        : null;
                                @endphp
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        @if ($orderDetail->product != null && $orderDetail->product->auction_product == 0)
                                            <a href="{{ route('product', $orderDetail->product->slug) }}" target="_blank">
                                                <img height="50" src="{{ uploaded_asset($orderDetail->product->thumbnail_img) }}">
                                            </a>
                                        @elseif ($orderDetail->product != null && $orderDetail->product->auction_product == 1)
                                            <a href="{{ route('auction-product', $orderDetail->product->slug) }}" target="_blank">
                                                <img height="50" src="{{ uploaded_asset($orderDetail->product->thumbnail_img) }}">
                                            </a>
                                        @else
                                            <strong>{{ translate('N/A') }}</strong>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($orderDetail->product != null && $orderDetail->product->auction_product == 0)
                                            <strong>
                                                <a href="{{ route('product', $orderDetail->product->slug) }}" target="_blank"
                                                    class="text-muted">
                                                    {{ $orderDetail->product->getTranslation('name') }}
                                                </a>
                                                @if($isSchemeLine)
                                                    <span class="badge badge-inline badge-success ml-1">{{ translate('Scheme Free') }}</span>
                                                @endif
                                            </strong>
                                            <small>
                                                {{ $orderDetail->variation }}
                                            </small>
                                            @if($batchName)
                                                <br>
                                                <small>{{ translate('Batch') }}: {{ $batchName }}</small>
                                            @endif
                                            <br>
                                            <small>
                                                {{ translate('SKU') }}: {{ $product_stock['sku'] ?? '-' }}
                                            </small>
                                        @elseif ($orderDetail->product != null && $orderDetail->product->auction_product == 1)
                                            <strong>
                                                <a href="{{ route('auction-product', $orderDetail->product->slug) }}" target="_blank"
                                                    class="text-muted">
                                                    {{ $orderDetail->product->getTranslation('name') }}
                                                </a>
                                            </strong>
                                        @else
                                            <strong>{{ translate('Product Unavailable') }}</strong>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($order->shipping_type != null && $order->shipping_type == 'home_delivery')
                                            {{ translate('Home Delivery') }}
                                        @elseif ($order->shipping_type == 'pickup_point')
                                            @if ($order->pickup_point != null)
                                                {{ $order->pickup_point->getTranslation('name') }}
                                                ({{ translate('Pickup Point') }})
                                            @else
                                                {{ translate('Pickup Point') }}
                                            @endif
                                        @elseif($order->shipping_type == 'carrier')
                                            @if ($order->carrier != null)
                                                {{ $order->carrier->name }} ({{ translate('Carrier') }})
                                                <br>
                                                {{ translate('Transit Time').' - '.$order->carrier->transit_time }}
                                            @else
                                                {{ translate('Carrier') }}
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{ $orderDetail->quantity }}
                                        @if($isSchemeLine)
                                            <br>
                                            <small class="text-success fw-600">{{ translate('Free item') }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{ single_price($orderDetail->quantity > 0 ? $orderDetail->price / $orderDetail->quantity : 0) }}
                                    </td>
                                    <td class="text-right">
                                        {{ single_price((float) ($orderDetail->mrp_price ?? 0)) }}
                                    </td>
                                    <td class="text-center">
                                        {{ single_price(max(0, $orderDetail->price + $orderDetail->tax - $detailCouponDiscount)) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="clearfix float-right">
                <table class="table">
                    <tbody>
                        <tr>
                            <td>
                                <strong class="text-muted">{{ translate('Sub Total') }} :</strong>
                            </td>
                            <td>
                                {{ single_price($order->orderDetails->sum('price')) }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="text-muted">{{ translate('Tax') }} :</strong>
                            </td>
                            <td>
                                {{ single_price($order->orderDetails->sum('tax')) }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="text-muted">{{ translate('Shipping') }} :</strong>
                            </td>
                            <td>
                                {{ single_price($order->orderDetails->sum('shipping_cost')) }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="text-muted">{{ translate('Coupon') }} :</strong>
                            </td>
                            <td>
                                {{ single_price($order->coupon_discount) }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong class="text-muted">{{ translate('TOTAL') }} :</strong>
                            </td>
                            <td class="text-muted h5">
                                {{ single_price($order->grand_total) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="no-print text-right">
                    <a href="https://ewaybillgst.gov.in" target="_blank" rel="noopener" type="button" class="btn btn-light">
                        {{ translate('E-Way Bill') }}
                    </a>
                    <a href="{{ route('invoice.download', $order->id) }}" type="button" class="btn btn-icon btn-light"><i
                            class="las la-print"></i></a>
                </div>
            </div>

        </div>
    </div>

    @include('components.location-info', ['data' => getStoredIPLocation('orders', $order->id)])

@endsection

@section('modal')

    <!-- confirm payment Status Modal -->
    <div id="confirm-payment-status" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered" style="max-width: 540px;">
            <div class="modal-content p-2rem">
                <div class="modal-body text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="72" height="64" viewBox="0 0 72 64">
                        <g id="Octicons" transform="translate(-0.14 -1.02)">
                          <g id="alert" transform="translate(0.14 1.02)">
                            <path id="Shape" d="M40.159,3.309a4.623,4.623,0,0,0-7.981,0L.759,58.153a4.54,4.54,0,0,0,0,4.578A4.718,4.718,0,0,0,4.75,65.02H67.587a4.476,4.476,0,0,0,3.945-2.289,4.773,4.773,0,0,0,.046-4.578Zm.6,52.555H31.582V46.708h9.173Zm0-13.734H31.582V23.818h9.173Z" transform="translate(-0.14 -1.02)" fill="#ffc700" fill-rule="evenodd"/>
                          </g>
                        </g>
                    </svg>
                    <p class="mt-3 mb-3 fs-16 fw-700">{{translate('Are you sure you want to change the payment status?')}}</p>
                    <button type="button" class="btn btn-light rounded-2 mt-2 fs-13 fw-700 w-150px" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" onclick="update_payment_status()" class="btn btn-success rounded-2 mt-2 fs-13 fw-700 w-150px">{{translate('Confirm')}}</button>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('script')
    <script type="text/javascript">

        // === Create Shipment ===
        $('#create-shipment-btn').on('click', function () {
            const btn = $(this);
            const provider = btn.data('provider');
            const providerSlug = String(provider || '').toLowerCase();
            const orderEnc = btn.data('order');
            const orderTotal = parseFloat(btn.data('total')) || 0;
            let ewaybill = '';

            if (!provider || provider === 'N/A') {
                AIZ.plugins.notify('danger', 'No shipment provider configured for this order.');
                return;
            }

            if (providerSlug === 'delhivery' && orderTotal > 50000) {
                ewaybill = prompt('Enter E-waybill number for this Delhivery shipment:');
                if (!ewaybill || !ewaybill.trim()) {
                    AIZ.plugins.notify('danger', 'E-waybill number is required for Delhivery shipments above Rs.50,000.');
                    return;
                }
                ewaybill = ewaybill.trim();
            }

            if (!confirm('Create shipment with provider: ' + provider + ' ?')) return;

            btn.prop('disabled', true);
            const oldText = btn.text();
            btn.text('Creating...');

            $.ajax({
                url: "{{ route('shipment.create') }}",
                type: 'POST',
                data: {
                    provider: provider,
                    order: orderEnc,
                    ewaybill: ewaybill
                },
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'   // ensure Laravel returns JSON error responses
                },
                success: function (payload) {
                    console.log('Shipment create response:', payload);
                    if (payload && payload.success) {
                        AIZ.plugins.notify('success', payload.message || 'Shipment created successfully.');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        AIZ.plugins.notify('danger', payload.message || 'Failed to create shipment. Check logs.');
                        console.error(payload);
                        btn.prop('disabled', false).text(oldText);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error', status, error, xhr.responseText);
                    let msg = 'Unexpected error occurred. Please check logs.';
                    try {
                        const json = JSON.parse(xhr.responseText || "{}");
                        msg = json.message || msg;
                    } catch (e) {}
                    AIZ.plugins.notify('danger', msg);
                    btn.prop('disabled', false).text(oldText);
                }
            });
        });
        
        $('#assign_deliver_boy').on('change', function() {
            var order_id = {{ $order->id }};
            var delivery_boy = $('#assign_deliver_boy').val();
            $.post('{{ route('orders.delivery-boy-assign') }}', {
                _token: '{{ @csrf_token() }}',
                order_id: order_id,
                delivery_boy: delivery_boy
            }, function(data) {
                AIZ.plugins.notify('success', '{{ translate('Delivery boy has been assigned') }}');
            });
        });
        $('#update_delivery_status').on('change', function() {
            var order_id = {{ $order->id }};
            var status = $('#update_delivery_status').val();
            $.post('{{ route('orders.update_delivery_status') }}', {
                _token: '{{ @csrf_token() }}',
                order_id: order_id,
                status: status
            }, function(data) {
                AIZ.plugins.notify('success', '{{ translate('Delivery status has been updated') }}');
            });
        });

        // Payment Status Update
        function confirm_payment_status(value){
            $('#confirm-payment-status').modal('show');
        }

        function update_payment_status(){
            $('#confirm-payment-status').modal('hide');
            var order_id = {{ $order->id }};
            $.post('{{ route('orders.update_payment_status') }}', {
                _token: '{{ @csrf_token() }}',
                order_id: order_id,
                status: 'paid'
            }, function(data) {
                $('#update_payment_status').prop('disabled', true);
                AIZ.plugins.bootstrapSelect('refresh');
                AIZ.plugins.notify('success', '{{ translate('Payment status has been updated') }}');
            });
        }

        $('#update_tracking_code').on('change', function() {
            var order_id = {{ $order->id }};
            var tracking_code = $('#update_tracking_code').val();
            $.post('{{ route('orders.update_tracking_code') }}', {
                _token: '{{ @csrf_token() }}',
                order_id: order_id,
                tracking_code: tracking_code
            }, function(data) {
                AIZ.plugins.notify('success', '{{ translate('Order tracking code has been updated') }}');
            });
        });
    </script>
@endsection
