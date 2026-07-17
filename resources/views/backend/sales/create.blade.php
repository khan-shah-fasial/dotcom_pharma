@extends('backend.layouts.app')

@section('content')
    <style>
        .backend-product-search-wrap {
            position: relative;
            z-index: 20;
        }
        .backend-product-results {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1060;
            max-height: 360px;
            overflow-y: auto;
            margin-top: 4px;
            background: #fff;
            border: 1px solid #e2e5ec;
            border-radius: 4px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
        }
        .backend-product-results.has-results {
            display: block;
        }
        .backend-product-result-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 10px;
            border-bottom: 1px solid #eef0f4;
        }
        .backend-product-result-row:last-child {
            border-bottom: 0;
        }
        .backend-product-result-info {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .backend-product-result-image {
            width: 50px;
            height: 50px;
            flex: 0 0 50px;
            object-fit: cover;
        }
        .backend-product-result-text {
            min-width: 0;
        }
        .backend-product-result-text strong,
        .backend-product-result-text small {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .backend-product-result-action {
            flex: 0 0 auto;
        }
    </style>
    <form id="backend-order-form" action="{{ route('orders.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="backend_add_order" value="1">
        <input type="hidden" name="customer_id" id="selected-customer-id" value="{{ old('customer_id') }}">

        <div class="row gutters-10">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 h6">{{ translate('Order Details') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row gutters-5">
                            <div class="col-md-6 form-group">
                                <label>{{ translate('Challan Number') }}</label>
                                <input type="text" class="form-control" name="challan_number" value="{{ old('challan_number') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ translate('Cases') }}</label>
                                <input type="text" class="form-control" name="cases" value="{{ old('cases') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ translate('Attached File Name') }}</label>
                                <input type="text" class="form-control" name="attached_file_name" value="{{ old('attached_file_name') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ translate('PM (Accountant Name)') }}</label>
                                <input type="text" class="form-control" name="pm_accountant_name" value="{{ old('pm_accountant_name') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ translate('LR Number') }}</label>
                                <input type="text" class="form-control" name="lr_number" value="{{ old('lr_number') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ translate('CC Attached') }}</label>
                                <input type="file" class="form-control" name="cc_attached" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                <small class="text-muted">{{ translate('One file only. Maximum size: 10 MB.') }}</small>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ translate('Sales Person') }}</label>
                                <select class="form-control aiz-selectpicker" name="sales_person_id" data-live-search="true" title="{{ translate('Select Sales Person') }}">
                                    <option value="">{{ translate('Select Sales Person') }}</option>
                                    @foreach ($salesPeople as $staff)
                                        <option value="{{ $staff->user_id }}" @selected((string) old('sales_person_id') === (string) $staff->user_id)>
                                            {{ optional($staff->user)->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ translate('Weight') }}</label>
                                <input type="text" class="form-control" name="weight" value="{{ old('weight') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ translate('Dimensions') }}</label>
                                <input type="text" class="form-control" name="dimensions" value="{{ old('dimensions') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>{{ translate('Transport Details') }}</label>
                                <input type="text" class="form-control" name="transport_details" value="{{ old('transport_details') }}">
                            </div>
                        </div>
                        <input type="hidden" name="freight_paid" value="0">
                        <label class="aiz-checkbox mb-0">
                            <input type="checkbox" name="freight_paid" value="1" @checked(old('freight_paid'))>
                            <span>{{ translate('Freight Paid') }}</span>
                            <span class="aiz-square-check"></span>
                        </label>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 h6">{{ translate('Customer') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label>{{ translate('Search Approved Customer') }}</label>
                            <input type="text" class="form-control" id="customer-search" placeholder="{{ translate('Company name, account no, person name, email or phone') }}" autocomplete="off">
                            <div id="customer-results" class="list-group mt-2"></div>
                        </div>
                        <div id="selected-customer-card" class="alert alert-info d-none mb-3"></div>
                        <div class="row gutters-10">
                            <div class="col-md-6">
                                <h6 class="mb-2">{{ translate('Billing Address') }}</h6>
                                <div id="billing-address-list"></div>
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
                                    <div id="shipping-same-preview" class="small text-muted mt-2">{{ translate('Select a billing address to use it for shipping.') }}</div>
                                </div>
                                <div id="shipping-address-options" class="d-none">
                                    <div id="shipping-address-list"></div>
                                    <div class="border-top pt-3 mt-2 d-none" id="new-shipping-wrap">
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
                    <div class="card-header">
                        <h5 class="mb-0 h6">{{ translate('Products') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group backend-product-search-wrap">
                            <label>{{ translate('Search Product') }}</label>
                            <input type="text" class="form-control" id="product-search" placeholder="{{ translate('Product name or barcode') }}" autocomplete="off">
                            <div id="product-results" class="backend-product-results"></div>
                        </div>

                        <div id="product-picker" class="border rounded p-3 d-none">
                            <div class="row gutters-10 align-items-end">
                                <div class="col-md-3">
                                    <label>{{ translate('Product') }}</label>
                                    <div class="d-flex align-items-center">
                                        <img src="" class="size-50px img-fit rounded border mr-2 d-none" id="picker-product-image" alt="">
                                        <div>
                                            <div class="fw-600" id="picker-product-name"></div>
                                            <small class="text-muted" id="picker-product-seller"></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label>{{ translate('Variant') }}</label>
                                    <select class="form-control" id="picker-stock"></select>
                                </div>
                                <div class="col-md-3">
                                    <label>{{ translate('Batch') }}</label>
                                    <select class="form-control" id="picker-batch"></select>
                                </div>
                                <div class="col-md-2">
                                    <label>{{ translate('Quantity') }}</label>
                                    <input type="number" min="1" step="1" class="form-control" id="picker-quantity" value="1">
                                </div>
                                <div class="col-md-2">
                                    <label>{{ translate('Sale') }}</label>
                                    <input type="number" min="0" step="0.01" class="form-control" id="picker-sale-price">
                                </div>
                            </div>
                            <div class="row gutters-10 mt-3">
                                <div class="col-md-4">
                                    <div class="border rounded p-2 h-100">
                                        <div class="fw-700 mb-2">{{ translate('Inventory') }}</div>
                                        <div class="row gutters-5 small">
                                            <div class="col-6 mb-1">{{ translate('Qty') }}: <strong id="picker-info-qty">-</strong></div>
                                            <div class="col-6 mb-1">{{ translate('Scheme') }}: <strong id="picker-info-scheme">-</strong></div>
                                            <div class="col-6">{{ translate('MOQ') }}: <strong id="picker-info-moq">-</strong></div>
                                            <div class="col-6">{{ translate('Current Stock') }}: <strong id="picker-info-stock">-</strong></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-2 h-100 small">
                                        <div class="fw-700 mb-2">{{ translate('Product Information') }}</div>
                                        <div>{{ translate('Product Name') }}: <strong id="picker-info-product">-</strong></div>
                                        <div>{{ translate('SKU') }}: <strong id="picker-info-sku">-</strong></div>
                                        <div>{{ translate('User') }}: <strong id="picker-info-user">-</strong></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-2 h-100 small">
                                        <div class="fw-700 mb-2">{{ translate('Variant Information') }}</div>
                                        <div>{{ translate('Pack Size') }}: <strong id="picker-info-pack">-</strong></div>
                                        <div>{{ translate('Type') }}: <strong id="picker-info-type">-</strong></div>
                                        <div>{{ translate('Quality') }}: <strong id="picker-info-quality">-</strong></div>
                                        <div>{{ translate('Material') }}: <strong id="picker-info-material">-</strong></div>
                                        <div>{{ translate('Size') }}: <strong id="picker-info-size">-</strong></div>
                                        <div>{{ translate('Country of Origin') }}: <strong id="picker-info-origin">-</strong></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row gutters-10 align-items-center mt-3">
                                <div class="col-md-9">
                                    <div id="picker-quote" class="small text-muted"></div>
                                </div>
                                <div class="col-md-3 text-md-right">
                                    <button type="button" class="btn btn-primary" id="add-line-btn" disabled>{{ translate('Add Product') }}</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table mb-0" id="order-lines-table">
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
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="order-lines-body">
                                    <tr>
                                        <td colspan="11" class="text-center text-muted">{{ translate('No products added') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="scheme-quantity-notice" class="alert alert-success mt-3 mb-0 d-none"></div>
                        <div id="line-hidden-inputs"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 h6">{{ translate('Shipping') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>{{ translate('Shipping Method') }}</label>
                            <select class="form-control" name="shipping_method" id="shipping-method">
                                <option value="courier">{{ translate('Courier') }}</option>
                                <option value="transport">{{ translate('Transport') }}</option>
                                <option value="local">{{ translate('Local Delivery') }}</option>
                            </select>
                        </div>

                        <div id="courier-fields">
                            <div class="form-group">
                                <label>{{ translate('Courier Provider') }}</label>
                                <select class="form-control" name="shipping_method_id" id="courier-provider">
                                    <option value="">{{ translate('Select Courier Provider') }}</option>
                                    @foreach($shippingMethods as $method)
                                        <option value="{{ $method->id }}" data-provider="{{ $method->slug }}">{{ $method->name }}</option>
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

                        <div id="transport-fields" class="d-none">
                            <div class="form-group">
                                <label>{{ translate('Transport') }}</label>
                                <select class="form-control" name="transport_id" id="transport-id">
                                    <option value="">{{ translate('Select Transport') }}</option>
                                    @foreach($transports as $transport)
                                        <option value="{{ $transport->id }}">{{ $transport->name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" class="form-control mt-2" name="transport_name" placeholder="{{ translate('Or enter transport name') }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Booked To') }}</label>
                                <select class="form-control" name="booked_to_id" id="booked-to-id" disabled>
                                    <option value="">{{ translate('Select transport first') }}</option>
                                </select>
                                <input type="text" class="form-control mt-2" name="booked_to_name" placeholder="{{ translate('Or enter booked to') }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Transport Mode') }}</label>
                                <select class="form-control" name="fod_mode">
                                    <option value="surface">{{ translate('Surface') }}</option>
                                    <option value="air">{{ translate('Air') }}</option>
                                    <option value="sea">{{ translate('Sea') }}</option>
                                </select>
                            </div>
                            <div class="form-group d-none" id="transport-surface-mode-fields">
                                <label>{{ translate('Surface Mode') }}</label>
                                <select class="form-control" name="transport_surface_mode" id="transport-surface-mode">
                                    <option value="road">{{ translate('Road') }}</option>
                                    <option value="train">{{ translate('Train') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Delivery Type') }}</label>
                                <select class="form-control" name="transport_delivery_type">
                                    <option value="door_delivery">{{ translate('Door Delivery') }}</option>
                                    <option value="transport_godown">{{ translate('Take from Transport Godown') }}</option>
                                </select>
                            </div>
                        </div>

                        <div id="local-fields" class="d-none">
                            <div class="form-group">
                                <label>{{ translate('Local Delivery Partner') }}</label>
                                <select class="form-control" name="local_delivery_partner_id">
                                    <option value="">{{ translate('Select Partner') }}</option>
                                    @foreach($localDeliveryPartners as $partner)
                                        <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" class="form-control mt-2" name="local_delivery_partner_name" placeholder="{{ translate('Or enter partner name') }}">
                            </div>
                        </div>

                        <div class="border-top pt-3">
                            <label>{{ translate('Shipping Cost By Seller') }}</label>
                            <div id="seller-shipping-costs" class="small text-muted">{{ translate('Add products to enter seller shipping cost.') }}</div>
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
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 h6">{{ translate('Payment') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>{{ translate('Payment Type') }}</label>
                            <select class="form-control" name="payment_type">
                                <option value="cash_on_delivery">{{ translate('Cash On Delivery') }}</option>
                                <option value="manual">{{ translate('Manual') }}</option>
                                <option value="bank_payment">{{ translate('Bank Payment') }}</option>
                                <option value="wallet">{{ translate('Wallet') }}</option>
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
                                    <input type="number" min="0" step="0.01" class="form-control" name="additional_discount" id="additional-discount" value="{{ old('additional_discount', 0) }}">
                                </div>
                                <div class="col-7">
                                    <label class="small mb-1">{{ translate('Discount Type') }}</label>
                                    <select class="form-control" name="additional_discount_type" id="additional-discount-type">
                                        <option value="percent" @selected(old('additional_discount_type', 'percent') === 'percent')>{{ translate('Percentage (%)') }}</option>
                                        <option value="amount" @selected(old('additional_discount_type') === 'amount')>{{ translate('Fixed Amount') }}</option>
                                    </select>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-block mt-2" id="apply-additional-discount-btn">{{ translate('Apply Discount') }}</button>
                            <input type="hidden" name="additional_discount_enabled" id="additional-discount-enabled" value="0">
                            <div id="additional-discount-message" class="small mt-1"></div>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Additional Info') }}</label>
                            <textarea class="form-control" name="additional_info" rows="3">{{ old('additional_info') }}</textarea>
                        </div>
                        <input type="hidden" name="send_order_notification" value="0">
                        <label class="aiz-checkbox">
                            <input type="checkbox" name="send_order_notification" value="1" checked>
                            <span>{{ translate('Send order notification') }}</span>
                            <span class="aiz-square-check"></span>
                        </label>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 h6">{{ translate('Summary') }}</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-3">
                            <tbody>
                                <tr><td>{{ translate('Subtotal') }}</td><td class="text-right" id="summary-subtotal">0.00</td></tr>
                                <tr><td>{{ translate('Product/Batch Discount') }}</td><td class="text-right" id="summary-product-discount">0.00</td></tr>
                                <tr><td>{{ translate('GST') }}</td><td class="text-right" id="summary-tax">0.00</td></tr>
                                <tr><td>{{ translate('Scheme Qty (Free)') }}</td><td class="text-right" id="summary-scheme">0</td></tr>
                                <tr><td>{{ translate('Coupon') }}</td><td class="text-right" id="summary-coupon">0.00</td></tr>
                                <tr><td>{{ translate('Shipping') }}</td><td class="text-right" id="summary-shipping">0.00</td></tr>
                                <tr class="fw-700"><td>{{ translate('Grand Total') }}</td><td class="text-right" id="summary-grand-total">0.00</td></tr>
                            </tbody>
                        </table>
                        <div id="summary-message" class="small text-danger mb-2"></div>
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
            var customerSearchUrl = @json(route('orders.create.customers'));
            var customerAddressUrlTemplate = @json(route('orders.create.customer_addresses', ['customer' => '__ID__']));
            var productSearchUrl = @json(route('orders.create.products'));
            var productQuoteUrl = @json(route('orders.create.product_quote'));
            var courierRatesUrl = @json(route('orders.create.courier_rates'));
            var summaryUrl = @json(route('orders.create.summary'));
            var stateUrl = @json(route('get-state'));
            var cityUrl = @json(route('get-city'));
            var csrf = @json(csrf_token());
            var lines = [];
            var currentProduct = null;
            var currentQuote = null;
            var debounceTimer = null;
            var customerAddresses = [];
            var salePriceEdited = false;
            var bookedToOptions = @json($bookedToOptions->map(function ($option) {
                return ['id' => $option->id, 'transport_id' => $option->transport_id, 'name' => $option->name];
            })->values());
            var courierServices = [];
            var shippingItems = [];
            var nextShippingItemId = 1;

            function money(value) {
                return (Number(value || 0)).toFixed(2);
            }

            function schemeQuantityBadge(value) {
                var quantity = Number(value || 0);
                return quantity > 0
                    ? '<span class="badge badge-inline badge-success">+' + quantity + ' {{ translate('Free') }}</span>'
                    : '-';
            }

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

            function debounce(fn) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(fn, 250);
            }

            function notify(type, message) {
                if (window.AIZ && AIZ.plugins && AIZ.plugins.notify) {
                    AIZ.plugins.notify(type, message);
                } else if (message) {
                    alert(message);
                }
            }

            function requestJson(options) {
                return $.ajax($.extend({
                    headers: {'X-CSRF-TOKEN': csrf},
                    dataType: 'json'
                }, options));
            }

            function displayCustomerName(customer) {
                return customer.company_name || customer.name || '-';
            }

            function customerSubText(customer) {
                return [
                    customer.account_no ? '{{ translate('Account No') }}: ' + customer.account_no : '',
                    customer.person_name ? '{{ translate('Person Name') }}: ' + customer.person_name : '',
                    customer.email || '',
                    customer.phone || ''
                ].filter(Boolean).join(' | ');
            }

            function customerMetaHtml(customer) {
                var rows = [
                    ['{{ translate('Approval Status') }}', customer.approval_status],
                    ['{{ translate('Customer Role') }}', customer.role],
                    ['{{ translate('Current Status') }}', customer.current_status],
                    ['{{ translate('Credit Status') }}', customer.credit_status],
                    ['{{ translate('Number of Days') }}', customer.credit_days],
                    ['{{ translate('Credit Limit') }}', customer.credit_limit],
                    ['{{ translate('Default Shipping') }}', customer.default_shipping_method]
                ];
                var html = '<div class="fw-700">' + escapeHtml(displayCustomerName(customer)) + '</div>'
                    + '<div>' + escapeHtml(customerSubText(customer)) + '</div>'
                    + '<div class="row gutters-5 mt-2">';

                rows.forEach(function (row) {
                    html += '<div class="col-md-4 col-sm-6 mb-1"><small class="d-block text-muted">'
                        + escapeHtml(row[0])
                        + '</small><span class="fw-600">'
                        + escapeHtml(row[1] === null || row[1] === undefined || row[1] === '' ? '-' : row[1])
                        + '</span></div>';
                });

                return html + '</div>';
            }

            function applyCustomerShippingDefaults(customer) {
                var method = customer.default_shipping_method || 'transport';
                $('#shipping-method').val(method).trigger('change');

                if (method !== 'transport') return;

                $('#transport-id').val(customer.default_transport_id || '').trigger('change');
                $('#booked-to-id').val(customer.default_booked_to_id || '');
                $('select[name="fod_mode"]').val(customer.default_transport_mode || 'surface').trigger('change');
                $('#transport-surface-mode').val(customer.default_transport_surface_mode || 'road');
                $('select[name="transport_delivery_type"]').val(customer.default_delivery_type || 'door_delivery');
            }

            function sortedByDefaultThenLatest(addresses) {
                return addresses.slice().sort(function (a, b) {
                    if (Boolean(a.set_default) !== Boolean(b.set_default)) {
                        return Boolean(b.set_default) - Boolean(a.set_default);
                    }
                    return Number(b.id || 0) - Number(a.id || 0);
                });
            }

            function sortedByLatest(addresses) {
                return addresses.slice().sort(function (a, b) {
                    return Number(b.id || 0) - Number(a.id || 0);
                });
            }

            function addressText(address) {
                return '<div class="fw-600 text-capitalize">' + escapeHtml(address.type || '') + '</div>'
                    + '<div>' + escapeHtml(address.address || '') + '</div>'
                    + '<small>' + escapeHtml([address.city, address.state, address.country, address.postal_code].filter(Boolean).join(', '))
                    + '<br>' + escapeHtml(address.phone || '') + '</small>';
            }

            function addressRadioHtml(address, name, label, checked) {
                return '<label class="d-block border rounded p-3 mb-2">'
                    + '<input type="radio" name="' + name + '" value="' + address.id + '" ' + (checked ? 'checked' : '') + '>'
                    + '<span class="ml-1">' + label + '</span>'
                    + addressText(address)
                    + '</label>';
            }

            function findAddress(addressId) {
                return customerAddresses.find(function (address) {
                    return Number(address.id) === Number(addressId);
                }) || null;
            }

            function syncShippingSamePreview() {
                var sameAsBilling = $('input[name="shipping_same_as_billing"]:checked').val() === '1';
                if (!sameAsBilling) {
                    $('#shipping-same-preview').text('{{ translate('Select a saved shipping address or add a new one below.') }}');
                    return;
                }

                var billingAddress = findAddress($('input[name="billing_address_id"]:checked').val());
                $('#shipping-same-preview').html(billingAddress
                    ? addressText(billingAddress)
                    : '<span class="text-muted">{{ translate('Select a billing address to use it for shipping.') }}</span>');
            }

            function syncShippingMode() {
                var sameAsBilling = $('input[name="shipping_same_as_billing"]:checked').val() === '1';
                $('#shipping-address-options').toggleClass('d-none', sameAsBilling);
                syncShippingSamePreview();
            }

            function renderCustomerResults(customers) {
                var $box = $('#customer-results').empty();
                if (!customers.length) {
                    $box.append('<div class="list-group-item text-muted">{{ translate('No customers found') }}</div>');
                    return;
                }
                customers.forEach(function (customer) {
                    $('<button type="button" class="list-group-item list-group-item-action"></button>')
                        .html('<strong>' + escapeHtml(displayCustomerName(customer)) + '</strong><br><small>' + escapeHtml(customerSubText(customer)) + '</small>')
                        .on('click', function () {
                            $('#selected-customer-id').val(customer.id);
                            $('#selected-customer-card').removeClass('d-none').html(customerMetaHtml(customer));
                            $box.empty();
                            applyCustomerShippingDefaults(customer);
                            loadAddresses(customer.id);
                            refreshSummary();
                        })
                        .appendTo($box);
                });
            }

            function loadAddresses(customerId) {
                customerAddresses = [];
                $('#new-shipping-toggle').prop('checked', false).trigger('change');
                $('input[name="shipping_same_as_billing"][value="1"]').prop('checked', true);
                $('#billing-address-list').html('<div class="text-muted">{{ translate('Loading addresses...') }}</div>');
                $('#shipping-address-list').empty();
                $('#new-shipping-wrap').addClass('d-none');
                syncShippingMode();
                requestJson({
                    url: customerAddressUrlTemplate.replace('__ID__', customerId),
                    method: 'GET'
                }).done(function (addresses) {
                    customerAddresses = addresses || [];
                    var $billingList = $('#billing-address-list').empty();
                    var $shippingList = $('#shipping-address-list').empty();
                    var billingAddresses = sortedByDefaultThenLatest(customerAddresses.filter(function (address) {
                        return address.type === 'billing';
                    }));
                    var shippingAddresses = sortedByLatest(customerAddresses.filter(function (address) {
                        return address.type === 'shipping' || !address.type;
                    }));

                    if (!billingAddresses.length) {
                        billingAddresses = sortedByDefaultThenLatest(shippingAddresses);
                    }

                    if (!billingAddresses.length) {
                        $billingList.html('<div class="text-muted">{{ translate('No saved billing address.') }}</div>');
                    } else {
                        billingAddresses.forEach(function (address, index) {
                            $billingList.append(addressRadioHtml(address, 'billing_address_id', '{{ translate('Bill to this address') }}', index === 0));
                        });
                    }

                    if (!shippingAddresses.length) {
                        $shippingList.html('<div class="text-muted">{{ translate('No saved shipping address. Add a new shipping address.') }}</div>');
                        $('#new-shipping-wrap').removeClass('d-none');
                        $('#new-shipping-toggle').prop('checked', true).trigger('change');
                    } else {
                        shippingAddresses.forEach(function (address, index) {
                            $shippingList.append(addressRadioHtml(address, 'shipping_address_id', '{{ translate('Ship to this address') }}', index === 0));
                        });
                        $('#new-shipping-toggle').prop('checked', false).trigger('change');
                        $('#new-shipping-wrap').removeClass('d-none');
                    }

                    syncShippingMode();
                });
            }

            function renderProductResults(products) {
                var $box = $('#product-results').empty().removeClass('has-results');
                if (!products.length) {
                    $box.html('<div class="backend-product-result-row text-muted">{{ translate('No products found') }}</div>').addClass('has-results');
                    return;
                }
                var html = '';
                products.forEach(function (product, index) {
                    var firstStock = (product.stocks || [])[0] || {};
                    var image = product.thumbnail
                        ? '<img src="' + escapeHtml(product.thumbnail) + '" class="backend-product-result-image rounded border" alt="">'
                        : '<span class="backend-product-result-image rounded border bg-light d-inline-block"></span>';
                    html += '<div class="backend-product-result-row">'
                        + '<div class="backend-product-result-info">'
                        + image
                        + '<div class="backend-product-result-text"><strong>' + escapeHtml(product.name) + '</strong>'
                        + '<small>{{ translate('SKU') }}: ' + escapeHtml(firstStock.sku || product.sku || '-') + ' | {{ translate('User') }}: ' + escapeHtml(product.owner_name || '-') + '</small>'
                        + '<small>{{ translate('Stock') }}: ' + escapeHtml(firstStock.current_stock ?? 0) + ' | {{ translate('MOQ') }}: ' + escapeHtml(firstStock.min_qty ?? 1) + ' | {{ translate('Scheme') }}: ' + escapeHtml(firstStock.scheme ?? 0) + '</small></div>'
                        + '</div>'
                        + '<button type="button" class="btn btn-soft-primary btn-sm backend-product-result-action product-select" data-index="' + index + '">{{ translate('Select') }}</button>'
                        + '</div>';
                });
                $box.html(html).data('products', products).addClass('has-results');
            }

            function selectProduct(product) {
                currentProduct = product;
                currentQuote = null;
                salePriceEdited = false;
                $('#product-search').val(product.name);
                $('#picker-product-name').text(product.name);
                $('#picker-product-seller').text(product.owner_name || '');
                $('#picker-sale-price').val('');
                if (product.thumbnail) {
                    $('#picker-product-image').attr('src', product.thumbnail).removeClass('d-none');
                } else {
                    $('#picker-product-image').attr('src', '').addClass('d-none');
                }
                var $stock = $('#picker-stock').empty();
                (product.stocks || []).forEach(function (stock) {
                    $stock.append('<option value="' + stock.id + '" data-index="' + $stock.children().length + '">' + escapeHtml(stock.variant) + '</option>');
                });
                $('#product-picker').removeClass('d-none');
                $('#product-results').empty().removeData('products').removeClass('has-results');
                syncBatchOptions();
                syncProductInformation();
                quoteCurrentProduct();
            }

            function selectedStock() {
                if (!currentProduct) return null;
                var selectedId = Number($('#picker-stock').val());
                return (currentProduct.stocks || []).find(function (stock) { return Number(stock.id) === selectedId; }) || null;
            }

            function batchOptionLabel(batch) {
                var label = batch.batch || '-';
                if (batch.product_exp_date) {
                    label += ' | {{ translate('Expiry') }} ' + batch.product_exp_date;
                }
                return label;
            }

            function selectedBatch() {
                var stock = selectedStock();
                var selectedId = Number($('#picker-batch').val());
                if (!stock || !selectedId) return null;
                return (stock.batches || []).find(function (batch) { return Number(batch.id) === selectedId; }) || null;
            }

            function infoValue(value) {
                return value === null || value === undefined || value === '' ? '-' : value;
            }

            function syncProductInformation() {
                var stock = selectedStock() || {};
                var batch = selectedBatch();
                var quantity = Number($('#picker-quantity').val() || 0);
                var currentStock = batch ? Number(batch.qty || 0) : Number(stock.current_stock ?? stock.qty ?? 0);

                $('#picker-info-qty').text(infoValue(quantity));
                $('#picker-info-scheme').text(infoValue(stock.scheme));
                $('#picker-info-moq').text(infoValue(stock.min_qty));
                $('#picker-info-stock').text(infoValue(currentStock));
                $('#picker-info-product').text(infoValue(currentProduct && currentProduct.name));
                $('#picker-info-sku').text(infoValue(stock.sku || (currentProduct && currentProduct.sku)));
                $('#picker-info-user').text(infoValue(currentProduct && currentProduct.owner_name));
                $('#picker-info-pack').text(infoValue(stock.pack_size || (currentProduct && currentProduct.pack_size)));
                $('#picker-info-type').text(infoValue(stock.type || (currentProduct && currentProduct.product_type)));
                $('#picker-info-quality').text(infoValue(stock.quality || (currentProduct && currentProduct.quality)));
                $('#picker-info-material').text(infoValue(stock.material || (currentProduct && currentProduct.material)));
                $('#picker-info-size').text(infoValue(stock.size));
                $('#picker-info-origin').text(infoValue(currentProduct && currentProduct.country_of_origin));
            }

            function syncQuantityBounds() {
                var stock = selectedStock();
                if (!stock) return false;

                var batch = selectedBatch();
                var minQty = Math.max(1, Number(stock.min_qty || 1));
                var availableQty = batch ? Number(batch.qty || 0) : Number(stock.qty || 0);
                var $quantity = $('#picker-quantity');
                var currentQty = Number($quantity.val());

                $quantity.attr('min', minQty);
                $quantity.attr('max', availableQty);

                if (!Number.isFinite(currentQty)) {
                    currentQty = minQty;
                }
                currentQty = Math.floor(currentQty);
                if (currentQty < minQty) {
                    currentQty = minQty;
                }
                $quantity.val(currentQty);

                if (availableQty <= 0 || availableQty < minQty) {
                    currentQuote = null;
                    $('#add-line-btn').prop('disabled', true);
                    $('#picker-quote').text('{{ translate('Available stock is below minimum quantity.') }}');
                    return false;
                }

                if (currentQty > availableQty) {
                    $quantity.val(availableQty);
                } else if (currentQty < minQty) {
                    $quantity.val(minQty);
                }

                return true;
            }

            function syncBatchOptions() {
                var stock = selectedStock();
                var $batch = $('#picker-batch').empty();
                $batch.append('<option value="">{{ translate('No batch') }}</option>');
                if (!stock) return;
                (stock.batches || []).forEach(function (batch) {
                    $batch.append('<option value="' + batch.id + '">' + escapeHtml(batchOptionLabel(batch)) + '</option>');
                });
                $('#picker-quantity').attr('min', stock.min_qty || 1);
                if (Number($('#picker-quantity').val()) < Number(stock.min_qty || 1)) {
                    $('#picker-quantity').val(stock.min_qty || 1);
                }
                syncQuantityBounds();
            }

            function quoteCurrentProduct() {
                currentQuote = null;
                $('#add-line-btn').prop('disabled', true);
                if (!currentProduct || !$('#selected-customer-id').val()) {
                    $('#picker-quote').text('{{ translate('Select customer before quoting product.') }}');
                    return;
                }
                var stock = selectedStock();
                if (!stock) return;
                if (!syncQuantityBounds()) return;
                var quoteData = {
                    customer_id: $('#selected-customer-id').val(),
                    product_id: currentProduct.id,
                    stock_id: stock.id,
                    batch_id: $('#picker-batch').val(),
                    quantity: $('#picker-quantity').val()
                };
                if (salePriceEdited && $('#picker-sale-price').val() !== '') {
                    quoteData.sale_price = $('#picker-sale-price').val();
                }
                requestJson({
                    url: productQuoteUrl,
                    method: 'POST',
                    data: quoteData
                }).done(function (response) {
                    currentQuote = response.data;
                    if (!salePriceEdited) {
                        $('#picker-sale-price').val(money(currentQuote.sale_price));
                    }
                    $('#add-line-btn').prop('disabled', false);
                    $('#picker-quote').html('{{ translate('MRP') }}: ' + money(currentQuote.mrp_price)
                        + ' | {{ translate('Sale') }}: ' + money(currentQuote.sale_price)
                        + ' | {{ translate('Discount') }}: ' + money(currentQuote.discount_amount)
                        + ' | {{ translate('GST Amount') }}: ' + money(currentQuote.gst_amount || currentQuote.tax)
                        + ' | {{ translate('Final') }}: ' + money(currentQuote.product_final_amount !== undefined ? currentQuote.product_final_amount : (currentQuote.final_amount || currentQuote.line_total))
                        + ' | {{ translate('Scheme Qty (Free)') }}: ' + (currentQuote.scheme_quantity || 0));
                }).fail(function (xhr) {
                    currentQuote = null;
                    $('#add-line-btn').prop('disabled', true);
                    $('#picker-quote').text((xhr.responseJSON && xhr.responseJSON.message) || '{{ translate('Unable to quote product.') }}');
                });
            }

            function renderLines() {
                var $body = $('#order-lines-body').empty();
                var $hidden = $('#line-hidden-inputs').empty();
                if (!lines.length) {
                    $body.html('<tr><td colspan="11" class="text-center text-muted">{{ translate('No products added') }}</td></tr>');
                    $('#scheme-quantity-notice').addClass('d-none').text('');
                    $('#seller-shipping-costs').html('{{ translate('Add products to enter seller shipping cost.') }}');
                    shippingItems = [];
                    renderShippingItems();
                    refreshSummary();
                    return;
                }

                lines.forEach(function (line, index) {
                    $body.append('<tr>'
                        + '<td>' + escapeHtml(line.product_name) + '<br><small>' + escapeHtml(line.owner_name || '') + '</small></td>'
                        + '<td>' + escapeHtml(line.stock_label || '-') + '</td>'
                        + '<td>' + escapeHtml(line.batch_label || '-') + '</td>'
                        + '<td class="text-right">' + line.quantity + '</td>'
                        + '<td class="text-right line-scheme" data-index="' + index + '">' + schemeQuantityBadge(line.quote.scheme_quantity) + '</td>'
                        + '<td class="text-right">' + money(line.quote.sale_price) + '</td>'
                        + '<td class="text-right line-tax" data-index="' + index + '">' + money(line.quote.gst_amount || line.quote.tax) + '</td>'
                        + '<td class="text-right line-gross" data-index="' + index + '">' + money(line.quote.gross_amount) + '</td>'
                        + '<td class="text-right">' + money(line.quote.mrp_price) + '</td>'
                        + '<td class="text-right line-final" data-index="' + index + '">' + money(line.quote.product_final_amount !== undefined ? line.quote.product_final_amount : (line.quote.final_amount || line.quote.line_total)) + '</td>'
                        + '<td class="text-right"><button type="button" class="btn btn-soft-danger btn-icon btn-sm remove-line" data-index="' + index + '"><i class="las la-trash"></i></button></td>'
                        + '</tr>');

                    ['product_id', 'stock_id', 'variation', 'id_variant', 'batch_id', 'quantity', 'sale_price'].forEach(function (field) {
                        $hidden.append('<input type="hidden" name="items[' + index + '][' + field + ']" value="' + (line[field] || '') + '">');
                    });
                });

                renderSellerShipping();
                renderOrderShippingRows();
                refreshSummary();
                if ($('#shipping-method').val() === 'courier' && $('#courier-provider').val()) {
                    loadCourierServices();
                }
            }

            function renderSellerShipping() {
                var existingCosts = {};
                $('.seller-shipping-input').each(function () {
                    existingCosts[$(this).data('seller-id')] = $(this).val();
                });
                var sellers = {};
                lines.forEach(function (line) {
                    sellers[line.owner_id] = line.owner_name || ('{{ translate('Seller') }} #' + line.owner_id);
                });
                var $box = $('#seller-shipping-costs').empty();
                Object.keys(sellers).forEach(function (sellerId) {
                    $box.append('<div class="form-group mb-2">'
                        + '<label class="mb-1">' + sellers[sellerId] + '</label>'
                        + '<input type="number" min="0" step="0.01" class="form-control seller-shipping-input" data-seller-id="' + sellerId + '" name="shipping_costs[' + sellerId + ']" value="' + (existingCosts[sellerId] || 0) + '">'
                        + '</div>');
                });
                renderShippingItems();
            }

            function sellerOptions(selectedSellerId) {
                var sellers = {};
                lines.forEach(function (line) {
                    sellers[line.owner_id] = line.owner_name || ('{{ translate('Seller') }} #' + line.owner_id);
                });

                return Object.keys(sellers).map(function (sellerId) {
                    return '<option value="' + sellerId + '" ' + (String(sellerId) === String(selectedSellerId) ? 'selected' : '') + '>'
                        + escapeHtml(sellers[sellerId]) + '</option>';
                }).join('');
            }

            function renderShippingItems() {
                var $box = $('#shipping-items').empty();
                if (!shippingItems.length) {
                    $box.addClass('text-muted').text('{{ translate('No shipping items added.') }}');
                    renderOrderShippingRows();
                    return;
                }

                $box.removeClass('text-muted');
                shippingItems.forEach(function (item, index) {
                    $box.append('<div class="row gutters-5 align-items-end mb-2 shipping-item-row" data-id="' + item.id + '">'
                        + '<div class="col-5"><label class="mb-1">{{ translate('Description') }}</label>'
                        + '<input type="text" class="form-control form-control-sm shipping-item-description" name="shipping_items[' + index + '][description]" value="' + escapeHtml(item.description) + '"></div>'
                        + '<div class="col-3"><label class="mb-1">{{ translate('Seller') }}</label>'
                        + '<select class="form-control form-control-sm shipping-item-seller" name="shipping_items[' + index + '][seller_id]">' + sellerOptions(item.seller_id) + '</select></div>'
                        + '<div class="col-3"><label class="mb-1">{{ translate('Amount') }}</label>'
                        + '<input type="number" min="0" step="0.01" class="form-control form-control-sm shipping-item-amount" name="shipping_items[' + index + '][amount]" value="' + money(item.amount) + '"></div>'
                        + '<div class="col-1"><button type="button" class="btn btn-sm btn-soft-danger remove-shipping-item"><i class="las la-trash"></i></button></div>'
                        + '</div>');
                });
                renderOrderShippingRows();
            }

            function renderOrderShippingRows() {
                var $body = $('#order-lines-body');
                $body.find('.order-shipping-line').remove();
                if (!lines.length) return;

                var sellers = {};
                lines.forEach(function (line) {
                    sellers[line.owner_id] = line.owner_name || ('{{ translate('Seller') }} #' + line.owner_id);
                });

                var rows = [];
                $('.seller-shipping-input').each(function () {
                    var amount = Number($(this).val() || 0);
                    if (amount > 0) {
                        var sellerId = $(this).data('seller-id');
                        rows.push({
                            id: null,
                            description: '{{ translate('Shipping Cost') }} - ' + (sellers[sellerId] || ''),
                            amount: amount
                        });
                    }
                });
                shippingItems.forEach(function (item) {
                    rows.push({
                        id: item.id,
                        description: item.description || '{{ translate('Shipping') }}',
                        amount: Number(item.amount || 0)
                    });
                });

                rows.forEach(function (row) {
                    var action = row.id === null
                        ? ''
                        : '<button type="button" class="btn btn-soft-danger btn-icon btn-sm remove-shipping-item-from-line" data-id="' + row.id + '"><i class="las la-trash"></i></button>';
                    $body.append('<tr class="order-shipping-line bg-soft-light">'
                        + '<td colspan="3"><strong>{{ translate('Shipping') }}</strong><br><small>' + escapeHtml(row.description) + '</small></td>'
                        + '<td class="text-right">-</td>'
                        + '<td class="text-right">-</td>'
                        + '<td class="text-right">-</td>'
                        + '<td class="text-right">-</td>'
                        + '<td class="text-right">' + money(row.amount) + '</td>'
                        + '<td class="text-right">-</td>'
                        + '<td class="text-right">' + money(row.amount) + '</td>'
                        + '<td class="text-right">' + action + '</td>'
                        + '</tr>');
                });
            }

            function firstSellerId() {
                return lines.length ? lines[0].owner_id : '';
            }

            function upsertCourierShippingItem(service) {
                var existing = shippingItems.find(function (item) { return item.source === 'courier'; });
                var description = ($('#courier-provider option:selected').text() + ' - ' + (service.name || '{{ translate('Courier Service') }}')).trim();
                if (existing) {
                    existing.description = description;
                    existing.amount = Number(service.price || 0);
                    existing.seller_id = existing.seller_id || firstSellerId();
                } else {
                    shippingItems.push({
                        id: nextShippingItemId++,
                        source: 'courier',
                        description: description,
                        amount: Number(service.price || 0),
                        seller_id: firstSellerId()
                    });
                }
                renderShippingItems();
                refreshSummary(false);
            }

            function loadCourierServices() {
                var providerId = $('#courier-provider').val();
                var $service = $('#courier-service');
                courierServices = [];
                $service.prop('disabled', true).html('<option value="">{{ translate('Loading services...') }}</option>');
                $('#courier-service-message').removeClass('text-danger').addClass('text-muted').text('');

                if (!providerId) {
                    $service.html('<option value="">{{ translate('Select provider to load services') }}</option>');
                    return;
                }
                if (!$('#selected-customer-id').val() || !lines.length) {
                    $service.html('<option value="">{{ translate('Add customer and products first') }}</option>');
                    $('#courier-service-message').text('{{ translate('Select a customer, address, and at least one product first.') }}');
                    return;
                }

                requestJson({
                    url: courierRatesUrl,
                    method: 'POST',
                    data: $('#backend-order-form').serialize()
                }).done(function (response) {
                    courierServices = response && response.success && Array.isArray(response.data) ? response.data : [];
                    $service.empty().append('<option value="">{{ translate('Select Courier Service') }}</option>');
                    courierServices.forEach(function (service, index) {
                        var price = service.price === null || service.price === undefined ? '' : ' - ' + money(service.price);
                        $service.append('<option value="' + escapeHtml(service.carrier_id || service.id || index) + '" data-index="' + index + '">' + escapeHtml(service.name || '{{ translate('Courier Service') }}') + price + '</option>');
                    });
                    $service.prop('disabled', !courierServices.length);
                    if (!courierServices.length) {
                        $('#courier-service-message').addClass('text-danger').text((response && response.message) || '{{ translate('No courier services are available.') }}');
                    }
                }).fail(function (xhr) {
                    var message = (xhr.responseJSON && xhr.responseJSON.message) || '{{ translate('Unable to load courier services.') }}';
                    $service.html('<option value="">{{ translate('No services available') }}</option>');
                    $('#courier-service-message').removeClass('text-muted').addClass('text-danger').text(message);
                });
            }

            function refreshSummary(validateDiscount) {
                $('#summary-message').text('');
                if (!$('#selected-customer-id').val() || !lines.length) {
                    $('#summary-subtotal,#summary-product-discount,#summary-tax,#summary-coupon,#summary-shipping,#summary-grand-total').text('0.00');
                    $('#summary-scheme').text('0');
                    $('#scheme-quantity-notice').addClass('d-none').text('');
                    return;
                }
                var requestData = $('#backend-order-form').serialize();
                if (validateDiscount) {
                    requestData += '&validate_additional_discount=1';
                }
                requestJson({
                    url: summaryUrl,
                    method: 'POST',
                    data: requestData
                }).done(function (response) {
                    var data = response.data || {};
                    $('#summary-subtotal').text(money(data.subtotal));
                    $('#summary-product-discount').text(money(data.product_discount));
                    $('#summary-tax').text(money(data.tax));
                    $('#summary-scheme').text(data.scheme_quantity || 0);
                    if (Number(data.scheme_quantity || 0) > 0) {
                        $('#scheme-quantity-notice')
                            .removeClass('d-none')
                            .html('<strong>{{ translate('Scheme applied') }}:</strong> ' + Number(data.scheme_quantity) + ' {{ translate('free unit(s) will be added to this order.') }}');
                    } else {
                        $('#scheme-quantity-notice').addClass('d-none').text('');
                    }
                    $('#summary-coupon').text(money(data.coupon_discount));
                    $('#summary-shipping').text(money(data.shipping));
                    $('#summary-grand-total').text(money(data.grand_total));
                    if (data.lines) {
                        data.lines.forEach(function (line, index) {
                            if (lines[index]) {
                                lines[index].quote = line;
                                $('.line-scheme[data-index="' + index + '"]').html(schemeQuantityBadge(line.scheme_quantity));
                                $('.line-tax[data-index="' + index + '"]').text(money(line.gst_amount || line.tax));
                                $('.line-gross[data-index="' + index + '"]').text(money(line.gross_amount));
                                $('.line-final[data-index="' + index + '"]').text(money(line.product_final_amount !== undefined ? line.product_final_amount : (line.final_amount || line.line_total)));
                            }
                        });
                    }
                    if (validateDiscount) {
                        var discount = data.additional_discount || {};
                        var typeLabel = discount.discount_type === 'percent'
                            ? money(discount.discount_value) + '%'
                            : money(discount.discount_value);
                        $('#additional-discount-message')
                            .removeClass('text-danger')
                            .addClass('text-success')
                            .text('{{ translate('Applied') }} (' + typeLabel + '): -' + money(data.coupon_discount));
                        notify('success', '{{ translate('Additional discount applied successfully.') }}');
                    }
                }).fail(function (xhr) {
                    var message = (xhr.responseJSON && xhr.responseJSON.message) || '{{ translate('Unable to calculate order summary.') }}';
                    $('#summary-message').text(message);
                    if (validateDiscount) {
                        $('#additional-discount-enabled').val('0');
                        $('#additional-discount-message').removeClass('text-success').addClass('text-danger').text(message);
                    }
                });
            }

            $('#customer-search').on('input', function () {
                var q = $(this).val();
                debounce(function () {
                    if (q.length < 2) {
                        $('#customer-results').empty();
                        return;
                    }
                    requestJson({url: customerSearchUrl, method: 'GET', data: {q: q}}).done(renderCustomerResults);
                });
            });

            $('#product-search').on('input', function () {
                var q = $(this).val();
                debounce(function () {
                    if (q.length < 2) {
                        $('#product-results').empty().removeData('products').removeClass('has-results');
                        return;
                    }
                    requestJson({url: productSearchUrl, method: 'GET', data: {q: q}}).done(renderProductResults);
                });
            });

            $(document).on('click', function (event) {
                if (!$(event.target).closest('.backend-product-search-wrap').length) {
                    $('#product-results').empty().removeData('products').removeClass('has-results');
                }
            });

            $(document).on('click', '.product-select', function () {
                var products = $('#product-results').data('products') || [];
                selectProduct(products[Number($(this).data('index'))]);
            });

            $('#picker-stock').on('change', function () {
                salePriceEdited = false;
                $('#picker-sale-price').val('');
                syncBatchOptions();
                syncProductInformation();
                quoteCurrentProduct();
            });
            $('#picker-batch').on('change', function () {
                salePriceEdited = false;
                $('#picker-sale-price').val('');
                syncProductInformation();
                quoteCurrentProduct();
            });
            $('#picker-quantity').on('input change keyup', function () {
                syncQuantityBounds();
                syncProductInformation();
                quoteCurrentProduct();
            });
            $('#picker-sale-price').on('input change keyup', function () {
                salePriceEdited = $(this).val() !== '';
                quoteCurrentProduct();
            });

            $('#add-line-btn').on('click', function () {
                if (!currentProduct || !currentQuote) {
                    notify('warning', '{{ translate('Please select a valid product quote.') }}');
                    return;
                }
                var stock = selectedStock();
                var batchText = $('#picker-batch option:selected').text();
                lines.push({
                    product_id: currentProduct.id,
                    owner_id: currentProduct.owner_id,
                    owner_name: currentProduct.owner_name,
                    product_name: currentProduct.name,
                    stock_id: stock.id,
                    variation: stock.raw_variant || '',
                    id_variant: stock.id_variant || '',
                    stock_label: stock.variant,
                    batch_id: $('#picker-batch').val(),
                    batch_label: $('#picker-batch').val() ? batchText : '',
                    quantity: $('#picker-quantity').val(),
                    sale_price: salePriceEdited ? $('#picker-sale-price').val() : '',
                    quote: currentQuote
                });
                renderLines();
            });

            $(document).on('click', '.remove-line', function () {
                lines.splice(Number($(this).data('index')), 1);
                renderLines();
            });

            $(document).on('input change', '.seller-shipping-input', function () {
                renderOrderShippingRows();
                refreshSummary(false);
            });

            $('#add-shipping-item-btn').on('click', function () {
                if (!lines.length) {
                    notify('warning', '{{ translate('Please add at least one product first.') }}');
                    return;
                }
                shippingItems.push({
                    id: nextShippingItemId++,
                    source: 'manual',
                    description: '{{ translate('Shipping') }}',
                    amount: 0,
                    seller_id: firstSellerId()
                });
                renderShippingItems();
            });

            $(document).on('input change', '.shipping-item-description,.shipping-item-seller,.shipping-item-amount', function () {
                var $row = $(this).closest('.shipping-item-row');
                var item = shippingItems.find(function (entry) { return Number(entry.id) === Number($row.data('id')); });
                if (!item) return;
                item.description = $row.find('.shipping-item-description').val();
                item.seller_id = $row.find('.shipping-item-seller').val();
                item.amount = Number($row.find('.shipping-item-amount').val() || 0);
                renderOrderShippingRows();
                refreshSummary(false);
            });

            $(document).on('click', '.remove-shipping-item', function () {
                var id = Number($(this).closest('.shipping-item-row').data('id'));
                shippingItems = shippingItems.filter(function (item) { return Number(item.id) !== id; });
                renderShippingItems();
                refreshSummary(false);
            });

            $(document).on('click', '.remove-shipping-item-from-line', function () {
                var id = Number($(this).data('id'));
                shippingItems = shippingItems.filter(function (item) { return Number(item.id) !== id; });
                renderShippingItems();
                refreshSummary(false);
            });

            $('#courier-provider').on('change', function () {
                shippingItems = shippingItems.filter(function (item) { return item.source !== 'courier'; });
                renderShippingItems();
                refreshSummary(false);
                loadCourierServices();
            });

            $('#courier-service').on('change', function () {
                var selectedIndex = Number($(this).find('option:selected').data('index'));
                if (!Number.isFinite(selectedIndex) || !courierServices[selectedIndex]) return;
                upsertCourierShippingItem(courierServices[selectedIndex]);
            });

            $('select[name="payment_type"]').on('change', function () {
                if ($('#shipping-method').val() !== 'courier' || !$('#courier-provider').val()) return;
                shippingItems = shippingItems.filter(function (item) { return item.source !== 'courier'; });
                renderShippingItems();
                refreshSummary(false);
                loadCourierServices();
            });

            $('#additional-discount,#additional-discount-type').on('input change', function () {
                $('#additional-discount-enabled').val('0');
                $('#additional-discount-message').removeClass('text-success text-danger').text('');
                debounce(function () {
                    refreshSummary(false);
                });
            }).on('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    $('#apply-additional-discount-btn').trigger('click');
                }
            });

            $('#apply-additional-discount-btn').on('click', function () {
                var value = Number($('#additional-discount').val());
                var type = $('#additional-discount-type').val();
                if (!Number.isFinite(value) || value <= 0) {
                    $('#additional-discount-message').removeClass('text-success').addClass('text-danger').text('{{ translate('Discount must be greater than zero.') }}');
                    return;
                }
                if (type === 'percent' && value > 100) {
                    $('#additional-discount-message').removeClass('text-success').addClass('text-danger').text('{{ translate('Percentage discount cannot exceed 100%.') }}');
                    return;
                }
                if (!lines.length) {
                    $('#additional-discount-message').removeClass('text-success').addClass('text-danger').text('{{ translate('Please add at least one product.') }}');
                    return;
                }
                $('#additional-discount-enabled').val('1');
                refreshSummary(true);
            });

            $('#shipping-method').on('change', function () {
                var value = $(this).val();
                $('#courier-fields').toggleClass('d-none', value !== 'courier');
                $('#transport-fields').toggleClass('d-none', value !== 'transport');
                $('#local-fields').toggleClass('d-none', value !== 'local');
                if (value !== 'courier') {
                    shippingItems = shippingItems.filter(function (item) { return item.source !== 'courier'; });
                    renderShippingItems();
                    refreshSummary(false);
                }
                if (value === 'courier' && $('#courier-provider').val()) {
                    loadCourierServices();
                }
            });

            $('#transport-id').on('change', function () {
                var transportId = Number($(this).val());
                var $bookedTo = $('#booked-to-id').empty();
                var matches = bookedToOptions.filter(function (option) {
                    return Number(option.transport_id) === transportId;
                });
                $bookedTo.append('<option value="">' + (transportId ? '{{ translate('Select Booked To') }}' : '{{ translate('Select transport first') }}') + '</option>');
                matches.forEach(function (option) {
                    $bookedTo.append('<option value="' + option.id + '">' + escapeHtml(option.name) + '</option>');
                });
                $bookedTo.prop('disabled', !transportId);
            });

            $('select[name="fod_mode"]').on('change', function () {
                var isSurface = $(this).val() === 'surface';
                $('#transport-surface-mode-fields').toggleClass('d-none', !isSurface);
                $('#transport-surface-mode').prop('disabled', !isSurface);
            }).trigger('change');

            $('#new-shipping-toggle').on('change', function () {
                $('#new-shipping-fields').toggleClass('d-none', !this.checked);
                if (this.checked) {
                    $('input[name="shipping_address_id"]').prop('checked', false);
                } else if (!$('input[name="shipping_address_id"]:checked').length) {
                    $('input[name="shipping_address_id"]').first().prop('checked', true);
                }
            });

            $(document).on('change', 'input[name="billing_address_id"]', function () {
                syncShippingSamePreview();
                refreshSummary();
                if ($('#shipping-method').val() === 'courier' && $('#courier-provider').val()) loadCourierServices();
            });

            $(document).on('change', 'input[name="shipping_address_id"], input[name="shipping_same_as_billing"]', function () {
                if ($(this).is('input[name="shipping_address_id"]')) {
                    $('#new-shipping-toggle').prop('checked', false);
                    $('#new-shipping-fields').addClass('d-none');
                }
                syncShippingMode();
                refreshSummary();
                if ($('#shipping-method').val() === 'courier' && $('#courier-provider').val()) loadCourierServices();
            });

            $(document).on('change', '.country-select', function () {
                var prefix = $(this).data('prefix');
                $.post(stateUrl, {_token: csrf, country_id: $(this).val()}, function (html) {
                    $('#' + prefix + '-state-id').html(JSON.parse(html));
                    $('#' + prefix + '-city-id').html('<option value="">{{ translate('Select City') }}</option>');
                });
            });

            $(document).on('change', '.state-select', function () {
                var prefix = $(this).data('prefix');
                $.post(cityUrl, {_token: csrf, state_id: $(this).val()}, function (html) {
                    $('#' + prefix + '-city-id').html(JSON.parse(html));
                });
            });

            $('#backend-order-form').on('submit', function (event) {
                if (!$('#selected-customer-id').val()) {
                    event.preventDefault();
                    notify('warning', '{{ translate('Please select an approved customer.') }}');
                    return;
                }
                if (!$('input[name="billing_address_id"]:checked').length) {
                    event.preventDefault();
                    notify('warning', '{{ translate('Please select a billing address.') }}');
                    return;
                }
                if ($('input[name="shipping_same_as_billing"]:checked').val() !== '1'
                    && !$('input[name="shipping_address_id"]:checked').length
                    && !$('#new-shipping-toggle').is(':checked')) {
                    event.preventDefault();
                    notify('warning', '{{ translate('Please select or add a shipping address.') }}');
                    return;
                }
                if (!lines.length) {
                    event.preventDefault();
                    notify('warning', '{{ translate('Please add at least one product.') }}');
                    return;
                }

                var shippingMethod = $('#shipping-method').val();
                if (shippingMethod === 'courier' && (!$('#courier-provider').val() || !$('#courier-service').val())) {
                    event.preventDefault();
                    notify('warning', '{{ translate('Please select a courier provider and service.') }}');
                    return;
                }
                if (shippingMethod === 'transport') {
                    var hasTransport = $('#transport-id').val() || $.trim($('input[name="transport_name"]').val());
                    var hasBookedTo = $('#booked-to-id').val() || $.trim($('input[name="booked_to_name"]').val());
                    if (!hasTransport || !hasBookedTo) {
                        event.preventDefault();
                        notify('warning', '{{ translate('Please select a transport provider and booked-to destination.') }}');
                        return;
                    }
                }
                if (shippingMethod === 'local' && !$('select[name="local_delivery_partner_id"]').val() && !$.trim($('input[name="local_delivery_partner_name"]').val())) {
                    event.preventDefault();
                    notify('warning', '{{ translate('Please select or enter a local delivery partner.') }}');
                }
            });
        })();
    </script>
@endsection
