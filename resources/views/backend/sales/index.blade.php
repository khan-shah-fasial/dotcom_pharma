@extends('backend.layouts.app')

@section('content')

@php
    $orderFilters = $orderFilters ?? [];
    $sortBy = $sortBy ?? '';
    $sortDir = $sortDir ?? 'desc';
    $orderRoute = Route::currentRouteName();
    $filtersApplied = filled($sort_search) || filled($delivery_status) || filled($payment_status) || filled($date) || filled($order_type)
        || collect($orderFilters)->contains(function ($value) {
            return $value !== null && $value !== '';
        });
@endphp

    <div class="card">
            <div class="card-header row gutters-5">
                <div class="col">
                    <h5 class="mb-md-0 h6">{{ translate('All Orders') }}</h5>
                    @if ($filtersApplied)
                        <span class="badge badge-info">{{ translate('Filters applied') }}</span>
                    @endif
                </div>

                @canany(['delete_order', 'export_order'])
                    <div class="dropdown mb-2 mb-md-0">
                        <button class="btn border dropdown-toggle" type="button" data-toggle="dropdown">
                            {{ translate('Bulk Action') }}
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            @can('delete_order')
                                <a class="dropdown-item confirm-alert" href="javascript:void(0)"  data-target="#bulk-delete-modal">{{ translate('Delete selection') }}</a>
                            @endcan
                            @can('export_order')
                                <a class="dropdown-item" href="javascript:void(0)" onclick="order_bulk_export()">{{ translate('Export') }}</a>
                            @endcan
                            @if(auth()->user()->can('unpaid_order_payment_notification_send') && $unpaid_order_payment_notification->status == 1 && Route::currentRouteName() == 'unpaid_orders.index')
                                <a class="dropdown-item" href="javascript:void(0)" onclick="bulk_unpaid_order_payment_notification()">{{ translate('Unpaid Order Payment Notification') }}</a>
                            @endif
                        </div>
                    </div>
                @endcan
                <div class="col-auto ml-auto">
                    <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#allOrdersFilterModal">
                        {{ translate('Open Filters') }}
                    </button>
                    <a href="{{ route($orderRoute) }}" class="btn btn-danger">{{ translate('Reset') }}</a>
                </div>
            </div>

        <form class="" action="" id="sort_orders" method="GET">
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            @if (auth()->user()->can('delete_order') || auth()->user()->can('export_order'))
                                <th>
                                    <div class="form-group">
                                        <div class="aiz-checkbox-inline">
                                            <label class="aiz-checkbox">
                                                <input type="checkbox" class="check-all">
                                                <span class="aiz-square-check"></span>
                                            </label>
                                        </div>
                                    </div>
                                </th>
                            @else
                                <th data-breakpoints="lg">#</th>
                            @endif

                            @include('backend.inc.sortable_th', ['column' => 'code', 'label' => translate('Order Code'), 'routeName' => $orderRoute, 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @include('backend.inc.sortable_th', ['column' => 'products', 'label' => translate('Num. of Products'), 'routeName' => $orderRoute, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'md'])
                            @include('backend.inc.sortable_th', ['column' => 'customer', 'label' => translate('Customer'), 'routeName' => $orderRoute, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'md'])
                            @include('backend.inc.sortable_th', ['column' => 'seller', 'label' => translate('Seller'), 'routeName' => $orderRoute, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'md'])
                            @include('backend.inc.sortable_th', ['column' => 'amount', 'label' => translate('Amount'), 'routeName' => $orderRoute, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'md'])
                            @include('backend.inc.sortable_th', ['column' => 'currency', 'label' => translate('Currency'), 'routeName' => $orderRoute, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'md'])
                            @include('backend.inc.sortable_th', ['column' => 'exchange_rate', 'label' => translate('Exchange Rate'), 'routeName' => $orderRoute, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'md'])
                            @include('backend.inc.sortable_th', ['column' => 'delivery_status', 'label' => translate('Delivery Status'), 'routeName' => $orderRoute, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'md'])
                            @include('backend.inc.sortable_th', ['column' => 'payment_method', 'label' => translate('Payment method'), 'routeName' => $orderRoute, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'md'])
                            @include('backend.inc.sortable_th', ['column' => 'payment_status', 'label' => translate('Payment Status'), 'routeName' => $orderRoute, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'md'])
                            @include('backend.inc.sortable_th', ['column' => 'tracking', 'label' => translate('Tracking'), 'routeName' => $orderRoute, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'md'])
                            @include('backend.inc.sortable_th', ['column' => 'shipping_method', 'label' => translate('Shipping Method'), 'routeName' => $orderRoute, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'md'])
                            @include('backend.inc.sortable_th', ['column' => 'shipping_type', 'label' => translate('Shipping Type'), 'routeName' => $orderRoute, 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'md'])
                            @if (addon_is_activated('refund_request'))
                                @include('backend.inc.sortable_th', ['column' => 'refund', 'label' => translate('Refund'), 'routeName' => $orderRoute, 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                            @endif
                            <th class="text-right" width="15%">{{ translate('options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $key => $order)
                            <tr>
                                @if (auth()->user()->can('delete_order') || auth()->user()->can('export_order'))
                                    <td>
                                        <div class="form-group">
                                            <div class="aiz-checkbox-inline">
                                                <label class="aiz-checkbox">
                                                    <input type="checkbox" class="check-one" name="id[]"
                                                        value="{{ $order->id }}">
                                                    <span class="aiz-square-check"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </td>
                                @else
                                    <td>{{ $key + 1 + ($orders->currentPage() - 1) * $orders->perPage() }}</td>
                                @endif
                                <td>
                                    {{ $order->code }}
                                    @if ($order->viewed == 0)
                                        <span class="badge badge-inline badge-info">{{ translate('New') }}</span>
                                    @endif
                                    @if (addon_is_activated('pos_system') && $order->order_from == 'pos')
                                        <span class="badge badge-inline badge-danger">{{ translate('POS') }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ count($order->orderDetails) }}
                                </td>
                                <td>
                                    @if ($order->user != null)
                                        {{ $order->user->name }}
                                    @else
                                        Guest ({{ $order->guest_id }})
                                    @endif
                                </td>
                                <td>
                                    @if ($order->shop)
                                        {{ $order->shop->name }}
                                    @else
                                        {{ translate('Inhouse Order') }}
                                    @endif
                                </td>
                                <td>
                                    {{ single_price($order->grand_total) }}
                                </td>
                                <td>
                                    {{ $order->quote_currency_code }}
                                </td>
                                <td>
                                    {{ $order->quote_grand_total }}
                                </td>
                                <td>
                                    {{ translate(ucfirst(str_replace('_', ' ', $order->delivery_status))) }}
                                </td>
                                <td>
                                    {{ translate(ucfirst(str_replace('_', ' ', $order->payment_type))) }}
                                </td>
                                <td>
                                    @if ($order->payment_status == 'paid')
                                        <span class="badge badge-inline badge-success">{{ translate('Paid') }}</span>
                                    @else
                                        <span class="badge badge-inline badge-danger">{{ translate('Unpaid') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php 
                                        $shipment = $order->shipment ?? null;
                                        $trackingUrl = optional($shipment)->tracking_url;
                                        $awb = optional($shipment)->shipping_id; // AWB or tracking number
                                        $carrier = optional($shipment)->shipping_type; // courier name
                                    @endphp

                                    @if($trackingUrl)
                                        <div class="small mb-1">
                                            @if($carrier)
                                                <strong>{{ $carrier }}</strong>
                                            @endif
                                            @if($awb)
                                                <span class="text-muted"> | {{ translate('AWB:') }} {{ $awb }}</span>
                                            @endif
                                        </div>
                                        <a href="{{ $trackingUrl }}" target="_blank" rel="noopener" class="btn btn-soft-primary btn-sm rounded-pill px-3 py-1">
                                            {{ translate('Track') }}
                                        </a>
                                    @else
                                        <span class="text-muted">{{ translate('Not Available') }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $order->shipping_choice ? translate(ucfirst(str_replace('_', ' ', $order->shipping_choice))) : '-' }}
                                </td>
                                <td>
                                    @if($order->shipping_choice === 'transport')
                                        {{ optional($order->transport)->name ?? $order->shipping_by ?? '-' }}
                                        @if($order->bookedTo)
                                            <br><small>{{ translate('Booked To') }}: {{ $order->bookedTo->name }}</small>
                                        @endif
                                    @elseif($order->shipping_choice === 'local')
                                        {{ optional($order->localDeliveryPartner)->name ?? $order->shipping_by ?? '-' }}
                                    @else
                                        {{ $order->shipping_by ?? '-' }}
                                    @endif
                                </td>
                                @if (addon_is_activated('refund_request'))
                                    <td>
                                        @if (count($order->refund_requests) > 0)
                                            {{ count($order->refund_requests) }} {{ translate('Refund') }}
                                        @else
                                            {{ translate('No Refund') }}
                                        @endif
                                    </td>
                                @endif
                                <td class="text-right">
                                    @if (addon_is_activated('pos_system') && $order->order_from == 'pos')
                                        <a class="btn btn-soft-success btn-icon btn-circle btn-sm"
                                            href="{{ route('admin.invoice.thermal_printer', $order->id) }}" target="_blank"
                                            title="{{ translate('Thermal Printer') }}">
                                            <i class="las la-print"></i>
                                        </a>
                                    @endif
                                    @can('view_order_details')
                                        @php
                                            $order_detail_route = route('orders.show', encrypt($order->id));
                                            if (Route::currentRouteName() == 'seller_orders.index') {
                                                $order_detail_route = route('seller_orders.show', encrypt($order->id));
                                            } elseif (Route::currentRouteName() == 'pick_up_point.index') {
                                                $order_detail_route = route('pick_up_point.order_show', encrypt($order->id));
                                            }
                                            if (Route::currentRouteName() == 'inhouse_orders.index') {
                                                $order_detail_route = route('inhouse_orders.show', encrypt($order->id));
                                            }
                                        @endphp
                                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                            href="{{ $order_detail_route }}" title="{{ translate('View') }}">
                                            <i class="las la-eye"></i>
                                        </a>
                                    @endcan
                                    @can('add_order')
                                        <a class="btn btn-soft-secondary btn-icon btn-circle btn-sm"
                                            href="{{ route('orders.edit', $order->id) }}" title="{{ translate('Edit Order') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @endcan
                                    <a class="btn btn-soft-info btn-icon btn-circle btn-sm"
                                        href="{{ route('invoice.download', $order->id) }}"
                                        title="{{ translate('Download Invoice') }}">
                                        <i class="las la-download"></i>
                                    </a>
                                    @if(auth()->user()->can('unpaid_order_payment_notification_send') && $order->payment_status == 'unpaid' && $unpaid_order_payment_notification->status == 1)
                                        <a class="btn btn-soft-warning btn-icon btn-circle btn-sm"
                                            href="javascript:void();" onclick="unpaid_order_payment_notification('{{ $order->id }}');"
                                            title="{{ translate('Unpaid Order Payment Notification') }}">
                                            <i class="las la-bell"></i>
                                        </a>
                                    @endif
                                    @can('delete_order')
                                        <a href="#"
                                            class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete"
                                            data-href="{{ route('orders.destroy', $order->id) }}"
                                            title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="aiz-pagination">
                    {{ $orders->appends(request()->input())->links() }}
                </div>

            </div>
        </form>
    </div>
@endsection

@section('modal')
    <!-- Delete modal -->
    @include('modals.delete_modal')

    <!-- Bulk Delete modal -->
    @include('modals.bulk_delete_modal')

    {{-- Bulk Unpaid Order Payment Notification --}}
    <div id="complete_unpaid_order_payment" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered" style="max-width: 540px;">
            <div class="modal-content pb-2rem px-2rem">
                <div class="modal-header border-0">
                    <button type="button" class="close" data-dismiss="modal"></button>
                </div>
                <form class="form-horizontal" action="{{ route('unpaid_order_payment_notification') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body text-center">
                        <input type="hidden" name="order_ids" value="" id="order_ids">
                        <p class="mt-2 mb-2 fs-16 fw-700">{{ translate('Are you sure to send notification for the selected orders?') }}</p>
                        <button type="submit" class="btn btn-warning rounded-2 mt-2 fs-13 fw-700 w-250px">{{ translate('Send Notification') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form action="{{ route($orderRoute) }}" method="GET">
        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
        <div class="modal fade" id="allOrdersFilterModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Filter Orders') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row gutters-5">
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Order Code') }}</label>
                                <input type="text" class="form-control" name="search" value="{{ $sort_search }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Num. of Products From') }}</label>
                                <input type="number" min="0" class="form-control" name="products_from" value="{{ $orderFilters['products_from'] ?? '' }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Num. of Products To') }}</label>
                                <input type="number" min="0" class="form-control" name="products_to" value="{{ $orderFilters['products_to'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Customer') }}</label>
                                <input type="text" class="form-control" name="customer" value="{{ $orderFilters['customer'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Seller') }}</label>
                                <input type="text" class="form-control" name="seller" value="{{ $orderFilters['seller'] ?? '' }}" placeholder="{{ translate('Shop name or Inhouse') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Amount From') }}</label>
                                <input type="number" step="0.01" class="form-control" name="amount_from" value="{{ $orderFilters['amount_from'] ?? '' }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Amount To') }}</label>
                                <input type="number" step="0.01" class="form-control" name="amount_to" value="{{ $orderFilters['amount_to'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Currency') }}</label>
                                <input type="text" class="form-control" name="currency" value="{{ $orderFilters['currency'] ?? '' }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Exchange Rate From') }}</label>
                                <input type="number" step="0.01" class="form-control" name="exchange_from" value="{{ $orderFilters['exchange_from'] ?? '' }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Exchange Rate To') }}</label>
                                <input type="number" step="0.01" class="form-control" name="exchange_to" value="{{ $orderFilters['exchange_to'] ?? '' }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{ translate('Delivery Status') }}</label>
                                <select name="delivery_status" class="form-control">
                                    <option value="">{{ translate('All') }}</option>
                                    @foreach (['pending' => 'Pending', 'confirmed' => 'Confirmed', 'picked_up' => 'Picked Up', 'on_the_way' => 'On The Way', 'delivered' => 'Delivered', 'cancelled' => 'Cancel'] as $value => $label)
                                        <option value="{{ $value }}" @selected($delivery_status == $value)>{{ translate($label) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{ translate('Payment method') }}</label>
                                <input type="text" class="form-control" name="payment_method" value="{{ $orderFilters['payment_method'] ?? '' }}">
                            </div>
                            @if($orderRoute != 'unpaid_orders.index')
                                <div class="col-md-4 mb-3">
                                    <label>{{ translate('Payment Status') }}</label>
                                    <select name="payment_status" class="form-control">
                                        <option value="">{{ translate('All') }}</option>
                                        <option value="paid" @selected($payment_status == 'paid')>{{ translate('Paid') }}</option>
                                        <option value="unpaid" @selected($payment_status == 'unpaid')>{{ translate('Unpaid') }}</option>
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Tracking') }}</label>
                                <input type="text" class="form-control" name="tracking" value="{{ $orderFilters['tracking'] ?? '' }}" placeholder="{{ translate('AWB or courier') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Date') }}</label>
                                <input type="text" class="aiz-date-range form-control" value="{{ $date }}"
                                    name="date" placeholder="{{ translate('Filter by date') }}" data-format="DD-MM-Y"
                                    data-separator=" to " data-advanced-range="true" autocomplete="off">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Shipping Method') }}</label>
                                <input type="text" class="form-control" name="shipping_method" value="{{ $orderFilters['shipping_method'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Shipping Type') }}</label>
                                <input type="text" class="form-control" name="shipping_type" value="{{ $orderFilters['shipping_type'] ?? '' }}">
                            </div>
                            @if($orderRoute == 'offline_payment_orders.index')
                                <div class="col-md-6 mb-3">
                                    <label>{{ translate('Order Type') }}</label>
                                    <select name="order_type" class="form-control">
                                        <option value="">{{ translate('All') }}</option>
                                        <option value="inhouse_orders" @selected($order_type == 'inhouse_orders')>{{ translate('Inhouse Orders') }}</option>
                                        <option value="seller_orders" @selected($order_type == 'seller_orders')>{{ translate('Seller Orders') }}</option>
                                    </select>
                                </div>
                            @endif
                            @if (addon_is_activated('refund_request'))
                                <div class="col-md-6 mb-3">
                                    <label>{{ translate('Refund') }}</label>
                                    <select name="refund" class="form-control">
                                        <option value="">{{ translate('All') }}</option>
                                        <option value="1" @selected(($orderFilters['refund'] ?? '') === '1')>{{ translate('Has refund') }}</option>
                                        <option value="0" @selected(($orderFilters['refund'] ?? '') === '0')>{{ translate('No refund') }}</option>
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route($orderRoute) }}" class="btn btn-danger">{{ translate('Reset') }}</a>
                        <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection

@section('script')
    <script type="text/javascript">
        $(document).on("change", ".check-all", function() {
            if (this.checked) {
                // Iterate each checkbox
                $('.check-one:checkbox').each(function() {
                    this.checked = true;
                });
            } else {
                $('.check-one:checkbox').each(function() {
                    this.checked = false;
                });
            }

        });
        
        function bulk_delete() {
            var data = new FormData($('#sort_orders')[0]);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{ route('bulk-order-delete') }}",
                type: 'POST',
                data: data,
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response == 1) {
                        location.reload();
                    }
                }
            });
        }
        
        function order_bulk_export (){
            var url = '{{route('order-bulk-export')}}';
            $("#sort_orders").attr("action", url);
            $('#sort_orders').submit();
            $("#sort_orders").attr("action", '');
        }

        // Set Commission
        function unpaid_order_payment_notification(shop_id){
            var orderIds = [];
            orderIds.push(shop_id);
            $('#order_ids').val(orderIds);
            $('#complete_unpaid_order_payment').modal('show', {backdrop: 'static'});
        }

        // Set seller bulk commission
         function bulk_unpaid_order_payment_notification(){
            var orderIds = [];
            $(".check-one[name='id[]']:checked").each(function() {
                orderIds.push($(this).val());
            });
            if(orderIds.length > 0){
                $('#order_ids').val(orderIds);
                $('#complete_unpaid_order_payment').modal('show', {backdrop: 'static'});
            }
            else{
                AIZ.plugins.notify('danger', '{{ translate('Please Select Order first.') }}');
            }
         }
    </script>
@endsection
