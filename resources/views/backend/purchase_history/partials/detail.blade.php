@php
    $customer = $record->customerDetails;
    $stock = $record->productStock;
    $product = $stock ? $stock->product : null;
    $brand = $product ? $product->brand : null;

    $partyName = $customer ? ($customer->company_name ?? '') : '';
    $contactPersonName = $customer ? ($customer->con_person_name ?? '') : '';

    $primaryMobiles = [];
    $otherMobiles = [];

    if ($customer) {
        $primaryMobiles = array_filter([
            $customer->prim_mobile_no_business ?? null,
            $customer->prim_whats_app_no_business ?? null,
            $customer->prim_mobile_no ?? null,
            $customer->prim_whats_app_no ?? null,
        ]);

        $otherMobiles = array_filter([
            $customer->alt_mobile_no_business ?? null,
            $customer->alternate_whats_app_no_business ?? null,
            $customer->alt_mobile_no ?? null,
            $customer->alt_whats_app_no ?? null,
        ]);
    }
@endphp

<div class="row">
    <div class="col-md-6">
        <table class="table table-sm table-borderless">
            <tbody>
            <tr>
                <th>{{ translate('Serial Number') }}</th>
                <td>{{ $record->serial_number }}</td>
            </tr>
            <tr>
                <th>{{ translate('Order Date') }}</th>
                <td>{{ $record->order_date }}</td>
            </tr>
            <tr>
                <th>{{ translate('Order Number') }}</th>
                <td>{{ $record->order_number }}</td>
            </tr>
            <tr>
                <th>{{ translate('Invoice Date') }}</th>
                <td>{{ $record->invoice_date }}</td>
            </tr>
            <tr>
                <th>{{ translate('Invoice Series') }}</th>
                <td>{{ $record->invoice_series }}</td>
            </tr>
            <tr>
                <th>{{ translate('Invoice Number') }}</th>
                <td>{{ $record->invoice_number }}</td>
            </tr>
            <tr>
                <th>{{ translate('AC Number (Customer)') }}</th>
                <td>{{ $record->ac_number }}</td>
            </tr>
            <tr>
                <th>{{ translate('Party Name') }}</th>
                <td>{{ $partyName }}</td>
            </tr>
            <tr>
                <th>{{ translate('Contact Person Name') }}</th>
                <td>{{ $contactPersonName }}</td>
            </tr>
            <tr>
                <th>{{ translate('Primary Mobile') }}</th>
                <td>{{ implode(', ', $primaryMobiles) }}</td>
            </tr>
            <tr>
                <th>{{ translate('Other Mobile') }}</th>
                <td>{{ implode(', ', $otherMobiles) }}</td>
            </tr>
            <tr>
                <th>{{ translate('Company') }}</th>
                <td>{{ $partyName }}</td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="col-md-6">
        <table class="table table-sm table-borderless">
            <tbody>
            <tr>
                <th>{{ translate('Product SKU') }}</th>
                <td>{{ $record->product_sku }}</td>
            </tr>
            <tr>
                <th>{{ translate('Product Name') }}</th>
                <td>{{ $product ? $product->name : '' }}</td>
            </tr>
            <tr>
                <th>{{ translate('Mfd By') }}</th>
                <td>{{ $brand ? $brand->name : '' }}</td>
            </tr>
            <tr>
                <th>{{ translate('Batch Number') }}</th>
                <td>{{ $record->batch_number }}</td>
            </tr>
            <tr>
                <th>{{ translate('Expiry Date') }}</th>
                <td>{{ $record->expiry_date }}</td>
            </tr>
            <tr>
                <th>{{ translate('Quantity') }}</th>
                <td>{{ $record->quantity }}</td>
            </tr>
            <tr>
                <th>{{ translate('Free') }}</th>
                <td>{{ $record->free }}</td>
            </tr>
            <tr>
                <th>{{ translate('Sale Rate') }}</th>
                <td>{{ $record->sale_rate }}</td>
            </tr>
            <tr>
                <th>{{ translate('MRP Rate') }}</th>
                <td>{{ $record->mrp_rate }}</td>
            </tr>
            <tr>
                <th>{{ translate('Discount %') }}</th>
                <td>{{ $record->discount }}</td>
            </tr>
            <tr>
                <th>{{ translate('Taxable Amount') }}</th>
                <td>{{ $record->taxable_amount }}</td>
            </tr>
            <tr>
                <th>{{ translate('GST %') }}</th>
                <td>{{ $record->gst_percentage }}</td>
            </tr>
            <tr>
                <th>{{ translate('GST Amount') }}</th>
                <td>{{ $record->gst_amount }}</td>
            </tr>
            <tr>
                <th>{{ translate('Final Amount') }}</th>
                <td>{{ $record->final_amount }}</td>
            </tr>
            <tr>
                <th>{{ translate('Sales Man Name') }}</th>
                <td>{{ $record->sales_man_name }}</td>
            </tr>
            <tr>
                <th>{{ translate('Sales Man Code') }}</th>
                <td>{{ $record->sales_man_code }}</td>
            </tr>
            <tr>
                <th>{{ translate('Case') }}</th>
                <td>{{ $record->case_value }}</td>
            </tr>
            <tr>
                <th>{{ translate('Packing') }}</th>
                <td>{{ $record->packing }}</td>
            </tr>
            <tr>
                <th>{{ translate('Transport') }}</th>
                <td>{{ $record->transport }}</td>
            </tr>
            <tr>
                <th>{{ translate('Book To') }}</th>
                <td>{{ $record->book_to }}</td>
            </tr>
            <tr>
                <th>{{ translate('LR Number') }}</th>
                <td>{{ $record->lr_number }}</td>
            </tr>
            <tr>
                <th>{{ translate('LR Date') }}</th>
                <td>{{ $record->lr_date }}</td>
            </tr>
            <tr>
                <th>{{ translate('Late By') }}</th>
                <td>{{ $record->late_by }}</td>
            </tr>
            <tr>
                <th>{{ translate('Country') }}</th>
                <td>{{ $record->country }}</td>
            </tr>
            <tr>
                <th>{{ translate('State') }}</th>
                <td>{{ $record->state }}</td>
            </tr>
            <tr>
                <th>{{ translate('City') }}</th>
                <td>{{ $record->city }}</td>
            </tr>
            <tr>
                <th>{{ translate('District') }}</th>
                <td>{{ $record->district }}</td>
            </tr>
            <tr>
                <th>{{ translate('Pincode') }}</th>
                <td>{{ $record->pincode }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

