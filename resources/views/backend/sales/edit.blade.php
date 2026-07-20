@extends('backend.layouts.app')

@section('content')
    <style>
        .apple-green-order-box {
            border: 1px solid #86efac;
            border-radius: 12px;
            background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
            box-shadow: 0 2px 8px rgba(34, 197, 94, .10);
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

                <div class="row gutters-10">
                    <div class="col-lg-7">
                        <div class="apple-green-order-box p-3 mb-3">
                            <div class="form-group">
                                <label>{{ translate('Order No') }}</label>
                                <input type="text" class="form-control" value="{{ $order->code }}" readonly>
                                <small class="text-muted">{{ translate('Existing order numbers are never regenerated during edit.') }}</small>
                            </div>

                            <div class="form-group">
                                <label>{{ translate('Transport') }}</label>
                                <select class="form-control" name="transport_id" id="edit-transport-id">
                                    <option value="">{{ translate('Select Transport') }}</option>
                                    @foreach($transports as $transport)
                                        <option value="{{ $transport->id }}" @selected((string) old('transport_id', $order->transport_id) === (string) $transport->id)>{{ $transport->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label>{{ translate('Delivery Type') }}</label>
                                <select class="form-control" name="transport_delivery_type">
                                    <option value="">{{ translate('Select Delivery Type') }}</option>
                                    @foreach([
                                        'door_delivery' => 'Door Delivery',
                                        'our_warehouse_delivery' => 'Our Warehouse Delivery',
                                        'hand_delivery' => 'Hand Delivery',
                                        'transport_warehouse' => 'Transport Warehouse',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('transport_delivery_type', $order->transport_delivery_type) === $value)>{{ translate($label) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label>{{ translate('Consignee Copy') }}</label>
                                <select class="form-control" name="consignee_copy_status" id="edit-consignee-copy-status">
                                    <option value="attached" @selected(old('consignee_copy_status', $order->consignee_copy_status ?: (($order->cc_attached_path || $order->attachments->where('category', 'consignee_copy')->isNotEmpty()) ? 'attached' : 'not_attached')) === 'attached')>{{ translate('Attached') }}</option>
                                    <option value="not_attached" @selected(old('consignee_copy_status', $order->consignee_copy_status ?: (($order->cc_attached_path || $order->attachments->where('category', 'consignee_copy')->isNotEmpty()) ? 'attached' : 'not_attached')) === 'not_attached')>{{ translate('Not Attached') }}</option>
                                </select>
                                @error('consignee_copy_status') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group" id="edit-consignee-files-wrap">
                                <label>{{ translate('Add Consignee Copy Files') }}</label>
                                <input type="file" class="form-control" name="cc_attachments[]" id="edit-cc-attachments" multiple
                                    accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv">
                                @error('cc_attachments') <div class="text-danger small">{{ $message }}</div> @enderror
                                @if($order->attachments->where('category', 'consignee_copy')->isNotEmpty() || $order->cc_attached_path)
                                    <small class="text-success d-block mt-1">{{ translate('An existing consignee copy is already stored.') }}</small>
                                @endif
                            </div>

                            <div class="form-group">
                                <label>{{ translate('Booked To') }}</label>
                                <select class="form-control" name="booked_to_id" id="edit-booked-to-id">
                                    <option value="">{{ translate('Select Booked To') }}</option>
                                    @foreach($bookedToOptions as $bookedTo)
                                        <option value="{{ $bookedTo->id }}" data-transport-id="{{ $bookedTo->transport_id }}"
                                            @selected((string) old('booked_to_id', $order->booked_to_id) === (string) $bookedTo->id)>{{ $bookedTo->name }}</option>
                                    @endforeach
                                </select>
                                @error('booked_to_id') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-group">
                                <label>{{ translate('Freight') }}</label>
                                <select class="form-control" name="freight_type">
                                    <option value="">{{ translate('Select Freight') }}</option>
                                    <option value="pre_paid" @selected(old('freight_type', $order->freight_type) === 'pre_paid')>{{ translate('Pre-Paid') }}</option>
                                    <option value="to_pay" @selected(old('freight_type', $order->freight_type) === 'to_pay')>{{ translate('To Pay') }}</option>
                                    <option value="fod" @selected(old('freight_type', $order->freight_type) === 'fod')>{{ translate('FOD') }}</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>{{ translate('Shipping Cost') }}</label>
                                <select class="form-control" name="shipping_cost_type" id="edit-shipping-cost-type">
                                    <option value="by_seller" @selected(old('shipping_cost_type', $order->free_shipping ? 'free_shipping' : 'by_seller') === 'by_seller')>{{ translate('By Seller') }}</option>
                                    <option value="free_shipping" @selected(old('shipping_cost_type', $order->free_shipping ? 'free_shipping' : 'by_seller') === 'free_shipping')>{{ translate('Free Shipping') }}</option>
                                </select>
                                @error('shipping_cost_type') <div class="text-danger small">{{ $message }}</div> @enderror
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
                                <label>{{ translate('Sales Executive Name') }}</label>
                                <select class="form-control aiz-selectpicker" name="sales_executive_id" data-live-search="true">
                                    <option value="">{{ translate('Select Sales Executive') }}</option>
                                    @foreach($salesPeople as $staff)
                                        <option value="{{ $staff->user_id }}" @selected((string) old('sales_executive_id', $order->sales_executive_id ?: $order->sales_person_id) === (string) $staff->user_id)>
                                            {{ optional($staff->user)->name }}{{ $staff->designation ? ' - ' . $staff->designation : '' }}
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
                                                {{ optional($staff->user)->name }}{{ $staff->designation ? ' - ' . $staff->designation : '' }}
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
            var hasExistingConsigneeCopy = @json($order->attachments->where('category', 'consignee_copy')->isNotEmpty() || filled($order->cc_attached_path));

            function syncConsigneeCopy() {
                var attached = $('#edit-consignee-copy-status').val() === 'attached';
                $('#edit-consignee-files-wrap').toggleClass('d-none', !attached);
                $('#edit-cc-attachments').prop('required', attached && !hasExistingConsigneeCopy);
            }

            function syncShippingCost() {
                var freeShipping = $('#edit-shipping-cost-type').val() === 'free_shipping';
                $('#edit-sell-amount-wrap').toggleClass('d-none', freeShipping);
                $('#edit-sell-amount').prop('disabled', freeShipping).prop('required', !freeShipping);
            }

            function filterBookedTo() {
                var transportId = String($('#edit-transport-id').val() || '');
                var selected = String($('#edit-booked-to-id').val() || '');
                $('#edit-booked-to-id option[data-transport-id]').each(function () {
                    var visible = !transportId || String($(this).data('transport-id')) === transportId;
                    $(this).prop('hidden', !visible).prop('disabled', !visible);
                });
                if (selected && $('#edit-booked-to-id option[value="' + selected + '"]').prop('disabled')) {
                    $('#edit-booked-to-id').val('');
                }
            }

            $('#edit-consignee-copy-status').on('change', syncConsigneeCopy);
            $('#edit-shipping-cost-type').on('change', syncShippingCost);
            $('#edit-transport-id').on('change', filterBookedTo);
            syncConsigneeCopy();
            syncShippingCost();
            filterBookedTo();
        })();
    </script>
@endsection
