@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="align-items-center">
            <h1 class="h3">{{ translate('Edit Purchase History') }}</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.purchase_history.update', $record->id) }}" method="POST" id="purchase-history-edit-form">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3">{{ translate('Order & Invoice') }}</h5>
                        <div class="form-group">
                            <label>{{ translate('Serial Number') }}</label>
                            <input type="text" class="form-control" name="serial_number" value="{{ old('serial_number', $record->serial_number) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Order Date') }}</label>
                            <input type="text" class="form-control" name="order_date" value="{{ old('order_date', $record->order_date) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Order Number') }}</label>
                            <input type="text" class="form-control" name="order_number" value="{{ old('order_number', $record->order_number) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Invoice Date') }}</label>
                            <input type="text" class="form-control" name="invoice_date" value="{{ old('invoice_date', $record->invoice_date) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Invoice Number') }}</label>
                            <input type="text" class="form-control" name="invoice_number" value="{{ old('invoice_number', $record->invoice_number) }}">
                        </div>

                        <h5 class="mb-3 mt-4">{{ translate('Customer') }}</h5>
                        <div class="form-group">
                            <label>{{ translate('AC Number (crm_id)') }}</label>
                            <input type="text" class="form-control" name="ac_number" value="{{ old('ac_number', $record->ac_number) }}">
                            <small class="text-muted d-block">
                                {{ translate('Linked to user_details.crm_id for Party & Contact info.') }}
                            </small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5 class="mb-3">{{ translate('Product') }}</h5>
                        <div class="form-group">
                            <label>{{ translate('Product SKU') }}</label>
                            <input type="text" class="form-control" name="product_sku" value="{{ old('product_sku', $record->product_sku) }}">
                            <small class="text-muted d-block">
                                {{ translate('Linked to product_stocks.sku to derive Product & Brand.') }}
                            </small>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Batch Number') }}</label>
                            <input type="text" class="form-control" name="batch_number" value="{{ old('batch_number', $record->batch_number) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Expiry Date') }}</label>
                            <input type="text" class="form-control" name="expiry_date" value="{{ old('expiry_date', $record->expiry_date) }}">
                        </div>

                        <h5 class="mb-3 mt-4">{{ translate('Amounts') }}</h5>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>{{ translate('Quantity') }}</label>
                                <input type="text" class="form-control" name="quantity" value="{{ old('quantity', $record->quantity) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label>{{ translate('Free') }}</label>
                                <input type="text" class="form-control" name="free" value="{{ old('free', $record->free) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label>{{ translate('Sale Rate') }}</label>
                                <input type="text" class="form-control" name="sale_rate" value="{{ old('sale_rate', $record->sale_rate) }}">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>{{ translate('MRP Rate') }}</label>
                                <input type="text" class="form-control" name="mrp_rate" value="{{ old('mrp_rate', $record->mrp_rate) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label>{{ translate('Discount %') }}</label>
                                <input type="text" class="form-control" name="discount" value="{{ old('discount', $record->discount) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label>{{ translate('Taxable Amount') }}</label>
                                <input type="text" class="form-control" name="taxable_amount" value="{{ old('taxable_amount', $record->taxable_amount) }}">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>{{ translate('Tax %') }}</label>
                                <input type="text" class="form-control" name="tax_percentage" value="{{ old('tax_percentage', $record->tax_percentage) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label>{{ translate('Tax Amount') }}</label>
                                <input type="text" class="form-control" name="tax_amount" value="{{ old('tax_amount', $record->tax_amount) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label>{{ translate('Final Amount') }}</label>
                                <input type="text" class="form-control" name="final_amount" value="{{ old('final_amount', $record->final_amount) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3">{{ translate('Salesman & Packing') }}</h5>
                        <div class="form-group">
                            <label>{{ translate('Sales Man Name') }}</label>
                            <input type="text" class="form-control" name="sales_man_name" value="{{ old('sales_man_name', $record->sales_man_name) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Sales Man Code') }}</label>
                            <input type="text" class="form-control" name="sales_man_code" value="{{ old('sales_man_code', $record->sales_man_code) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Case') }}</label>
                            <input type="text" class="form-control" name="case_value" value="{{ old('case_value', $record->case_value) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Packing') }}</label>
                            <input type="text" class="form-control" name="packing" value="{{ old('packing', $record->packing) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Transport') }}</label>
                            <input type="text" class="form-control" name="transport" value="{{ old('transport', $record->transport) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Book To') }}</label>
                            <input type="text" class="form-control" name="book_to" value="{{ old('book_to', $record->book_to) }}">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5 class="mb-3">{{ translate('Logistics & Location') }}</h5>
                        <div class="form-group">
                            <label>{{ translate('LR Number') }}</label>
                            <input type="text" class="form-control" name="lr_number" value="{{ old('lr_number', $record->lr_number) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('LR Date') }}</label>
                            <input type="text" class="form-control" name="lr_date" value="{{ old('lr_date', $record->lr_date) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Country') }}</label>
                            <input type="text" class="form-control" name="country" value="{{ old('country', $record->country) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('State') }}</label>
                            <input type="text" class="form-control" name="state" value="{{ old('state', $record->state) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('City') }}</label>
                            <input type="text" class="form-control" name="city" value="{{ old('city', $record->city) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('District') }}</label>
                            <input type="text" class="form-control" name="district" value="{{ old('district', $record->district) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Pincode') }}</label>
                            <input type="text" class="form-control" name="pincode" value="{{ old('pincode', $record->pincode) }}">
                        </div>
                    </div>
                </div>

                <div class="mt-3 text-right">
                    <button type="submit" class="btn btn-primary" onclick="return confirm_edit_purchase_history();">
                        {{ translate('Save Changes') }}
                    </button>
                    <a href="{{ route('purchase_history.index') }}" class="btn btn-outline-secondary">
                        {{ translate('Cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function confirm_edit_purchase_history() {
            var serialized = $('#purchase-history-edit-form').serializeArray();
            var summaryLines = [];
            serialized.forEach(function (field) {
                if (field.value) {
                    summaryLines.push(field.name + ': ' + field.value);
                }
            });

            AIZ.plugins.confirm(
                '{{ translate('Are you sure you want to update this record with the following values?') }}' + '\n\n' + summaryLines.join('\n'),
                function () {
                    $('#purchase-history-edit-form')[0].submit();
                }
            );

            return false;
        }
    </script>
@endsection

