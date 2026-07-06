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
    <form id="backend-order-form" action="{{ route('orders.store') }}" method="POST">
        @csrf
        <input type="hidden" name="backend_add_order" value="1">
        <input type="hidden" name="customer_id" id="selected-customer-id" value="{{ old('customer_id') }}">

        <div class="row gutters-10">
            <div class="col-lg-8">
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
                                            <span>{{ translate('Add new shipping address') }}</span>
                                            <span class="aiz-square-check"></span>
                                        </label>
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
                                        <th class="text-right">{{ translate('Sale') }}</th>
                                        <th class="text-right">{{ translate('GST Amount') }}</th>
                                        <th class="text-right">{{ translate('Gross') }}</th>
                                        <th class="text-right">{{ translate('Coupon') }}</th>
                                        <th class="text-right">{{ translate('Final') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="order-lines-body">
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">{{ translate('No products added') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
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
                                <select class="form-control" name="shipping_method_id">
                                    @foreach($shippingMethods as $method)
                                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Courier Service') }}</label>
                                <input type="text" class="form-control" name="courier_service" value="{{ old('courier_service') }}">
                            </div>
                        </div>

                        <div id="transport-fields" class="d-none">
                            <div class="form-group">
                                <label>{{ translate('Transport') }}</label>
                                <select class="form-control" name="transport_id">
                                    <option value="">{{ translate('Select Transport') }}</option>
                                    @foreach($transports as $transport)
                                        <option value="{{ $transport->id }}">{{ $transport->name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" class="form-control mt-2" name="transport_name" placeholder="{{ translate('Or enter transport name') }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Booked To') }}</label>
                                <select class="form-control" name="booked_to_id">
                                    <option value="">{{ translate('Select Booked To') }}</option>
                                    @foreach($bookedToOptions as $bookedTo)
                                        <option value="{{ $bookedTo->id }}">{{ $bookedTo->name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" class="form-control mt-2" name="booked_to_name" placeholder="{{ translate('Or enter booked to') }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Transport Mode') }}</label>
                                <select class="form-control" name="fod_mode">
                                    <option value="surface">{{ translate('Surface') }}</option>
                                    <option value="air">{{ translate('Air') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ translate('Delivery Type') }}</label>
                                <input type="text" class="form-control" name="transport_delivery_type">
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
                            <label>{{ translate('Coupon Code') }}</label>
                            <input type="text" class="form-control" name="coupon_code" id="coupon-code">
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
                                <tr><td>{{ translate('Scheme Qty') }}</td><td class="text-right" id="summary-scheme">0</td></tr>
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

            function money(value) {
                return (Number(value || 0)).toFixed(2);
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
                    ['{{ translate('Credit Limit') }}', customer.credit_limit]
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
                        $('#new-shipping-wrap').addClass('d-none');
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
                    var image = product.thumbnail
                        ? '<img src="' + escapeHtml(product.thumbnail) + '" class="backend-product-result-image rounded border" alt="">'
                        : '<span class="backend-product-result-image rounded border bg-light d-inline-block"></span>';
                    html += '<div class="backend-product-result-row">'
                        + '<div class="backend-product-result-info">'
                        + image
                        + '<div class="backend-product-result-text"><strong>' + escapeHtml(product.name) + '</strong><small>' + escapeHtml(product.brand || '') + ' | ' + escapeHtml(product.owner_name || '') + '</small></div>'
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
                        + ' | {{ translate('Final') }}: ' + money(currentQuote.final_amount || currentQuote.line_total)
                        + ' | {{ translate('Scheme Qty') }}: ' + (currentQuote.scheme_quantity || 0));
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
                    $body.html('<tr><td colspan="10" class="text-center text-muted">{{ translate('No products added') }}</td></tr>');
                    $('#seller-shipping-costs').html('{{ translate('Add products to enter seller shipping cost.') }}');
                    refreshSummary();
                    return;
                }

                lines.forEach(function (line, index) {
                    $body.append('<tr>'
                        + '<td>' + escapeHtml(line.product_name) + '<br><small>' + escapeHtml(line.owner_name || '') + '</small></td>'
                        + '<td>' + escapeHtml(line.stock_label || '-') + '</td>'
                        + '<td>' + escapeHtml(line.batch_label || '-') + '</td>'
                        + '<td class="text-right">' + line.quantity + '</td>'
                        + '<td class="text-right">' + money(line.quote.sale_price) + '</td>'
                        + '<td class="text-right">' + money(line.quote.gst_amount || line.quote.tax) + '</td>'
                        + '<td class="text-right">' + money(line.quote.gross_amount) + '</td>'
                        + '<td class="text-right">' + money(line.quote.coupon_discount) + '</td>'
                        + '<td class="text-right">' + money(line.quote.final_amount || line.quote.line_total) + '</td>'
                        + '<td class="text-right"><button type="button" class="btn btn-soft-danger btn-icon btn-sm remove-line" data-index="' + index + '"><i class="las la-trash"></i></button></td>'
                        + '</tr>');

                    ['product_id', 'stock_id', 'variation', 'id_variant', 'batch_id', 'quantity', 'sale_price'].forEach(function (field) {
                        $hidden.append('<input type="hidden" name="items[' + index + '][' + field + ']" value="' + (line[field] || '') + '">');
                    });
                });

                renderSellerShipping();
                refreshSummary();
            }

            function renderSellerShipping() {
                var sellers = {};
                lines.forEach(function (line) {
                    sellers[line.owner_id] = line.owner_name || ('{{ translate('Seller') }} #' + line.owner_id);
                });
                var $box = $('#seller-shipping-costs').empty();
                Object.keys(sellers).forEach(function (sellerId) {
                    $box.append('<div class="form-group mb-2">'
                        + '<label class="mb-1">' + sellers[sellerId] + '</label>'
                        + '<input type="number" min="0" step="0.01" class="form-control seller-shipping-input" name="shipping_costs[' + sellerId + ']" value="0">'
                        + '</div>');
                });
            }

            function refreshSummary() {
                $('#summary-message').text('');
                if (!$('#selected-customer-id').val() || !lines.length) {
                    $('#summary-subtotal,#summary-product-discount,#summary-tax,#summary-coupon,#summary-shipping,#summary-grand-total').text('0.00');
                    $('#summary-scheme').text('0');
                    return;
                }
                requestJson({
                    url: summaryUrl,
                    method: 'POST',
                    data: $('#backend-order-form').serialize()
                }).done(function (response) {
                    var data = response.data || {};
                    $('#summary-subtotal').text(money(data.subtotal));
                    $('#summary-product-discount').text(money(data.product_discount));
                    $('#summary-tax').text(money(data.tax));
                    $('#summary-scheme').text(data.scheme_quantity || 0);
                    $('#summary-coupon').text(money(data.coupon_discount));
                    $('#summary-shipping').text(money(data.shipping));
                    $('#summary-grand-total').text(money(data.grand_total));
                    if (data.lines) {
                        data.lines.forEach(function (line, index) {
                            if (lines[index]) {
                                lines[index].quote = line;
                            }
                        });
                    }
                }).fail(function (xhr) {
                    $('#summary-message').text((xhr.responseJSON && xhr.responseJSON.message) || '{{ translate('Unable to calculate order summary.') }}');
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
                quoteCurrentProduct();
            });
            $('#picker-batch').on('change', function () {
                salePriceEdited = false;
                $('#picker-sale-price').val('');
                quoteCurrentProduct();
            });
            $('#picker-quantity').on('input change keyup', function () {
                syncQuantityBounds();
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

            $(document).on('input change', '.seller-shipping-input,#coupon-code', refreshSummary);

            $('#shipping-method').on('change', function () {
                var value = $(this).val();
                $('#courier-fields').toggleClass('d-none', value !== 'courier');
                $('#transport-fields').toggleClass('d-none', value !== 'transport');
                $('#local-fields').toggleClass('d-none', value !== 'local');
            });

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
            });

            $(document).on('change', 'input[name="shipping_address_id"], input[name="shipping_same_as_billing"]', function () {
                syncShippingMode();
                refreshSummary();
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
                }
            });
        })();
    </script>
@endsection
