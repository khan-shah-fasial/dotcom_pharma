<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ translate('Invoice') }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="UTF-8">
    <style media="all">
        @page { margin: 8px 10px; }
        body {
            margin: 0;
            padding: 0;
            font-size: 11px;
            font-family: {!! $font_family !!};
            direction: {{ $direction }};
            text-align: {{ $text_align }};
            color: #000;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .invoice-wrap { padding: 8px 6px 14px; background: #fff; }
        .band td {
            border: 1px solid #000;
            padding: 5px 6px;
            font-weight: 700;
            text-align: center;
            font-size: 10px;
        }
        .title {
            text-align: center;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.8px;
            color: #c00;
            padding: 6px 0 4px;
        }
        .subtitle {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
        }
        .contact {
            text-align: center;
            font-size: 10px;
            line-height: 1.45;
            margin-bottom: 6px;
        }
        .meta td {
            border: 1px solid #000;
            padding: 6px 7px;
            font-size: 10px;
        }
        .meta .head { font-weight: 700; width: 25%; }
        .section-title {
            background: #dfe6f3;
            border: 1px solid #000;
            padding: 6px 7px;
            font-weight: 700;
            font-size: 11px;
        }
        .box {
            border: 1px solid #000;
            padding: 7px;
            font-size: 10px;
            min-height: 70px;
        }
        .label { font-weight: 700; }
        .items th,
        .items td {
            border: 1px solid #000;
            padding: 6px 5px;
            font-size: 10px;
        }
        .items th { background: #dfe6f3; font-weight: 700; }
        .text-right { text-align: {{ $not_text_align }}; }
        .text-center { text-align: center; }
        .note-band {
            border: 1px solid #c00;
            color: #c00;
            padding: 6px 8px;
            font-weight: 700;
            text-align: center;
            font-size: 11px;
            margin-top: 6px;
        }
        .red-color { color: #c00; }
        .terms {
            border: 1px solid #000;
            padding: 7px;
            font-size: 10px;
            line-height: 1.45;
        }
        .footer-box td {
            border: 1px solid #000;
            padding: 6px 7px;
            font-size: 10px;
        }
        .small { font-size: 9px; }
        .grid td { border: 1px solid #000; padding: 6px 5px; font-size: 10px; }
    </style>
</head>
<body>
@php
    $siteName = get_setting('site_name') ?: 'Dotcom Pharma';
    $contactAddress = get_setting('contact_address', null, 'en');
    $contactPhone = get_setting('contact_phone');
    $contactSalesPhone = get_setting('contact_sales_phone');
    $contactAccountPhone = get_setting('contact_account_phone');
    $contactEmail = get_setting('contact_email');
    // $contactWebsite = get_setting('website_name') ?: url('/');
    $contactWebsite = get_setting('website_url') ?: url('/');

    $shipping = json_decode($order->shipping_address ?? '{}');
    $user = $order->user ?? null;
    $userID = $user->id ?? '-' ;

    $userDetails = $user->user_details;
    $customerName = $shipping->name ?? optional($order->user)->name ?? translate('Customer');
    $customerEmail = $shipping->email ?? optional($order->user)->email;
    $customerPhone = $shipping->phone ?? optional($order->user)->phone;
    $customerGst = optional($order->user)->gst_no ?? ($shipping->gst_no ?? null);
    $customerPan = optional($order->user)->pan_no ?? ($shipping->pan_no ?? null);
    $pinCode = $shipping->postal_code ?? null;
    $addressParts = array_filter([
        $shipping->address ?? null,
        $shipping->city ?? null,
        $shipping->state ?? null,
        $shipping->country ?? null,
        // $shipping->postal_code ?? null,
    ]);
    $billingAddress = implode(', ', $addressParts);
    $shippingAddress = '-';
    $shipping_postal_code = $shipping->postal_code ?? "-";
    $shipping_state = $shipping->state ?? "-";
    $invoiceNo = $order->code ?? $order->id;
    $challanNo = '-';
    $invoiceDateObj = $order->date
        ? \Carbon\Carbon::createFromTimestamp($order->date)
        : ($order->created_at ? $order->created_at->copy() : null);
    $invoiceDate = $invoiceDateObj ? $invoiceDateObj->format('d-m-Y') : '-';
    $invoiceTime = $invoiceDateObj ? $invoiceDateObj->format('H:i:s') : '-';
    $creditDays = (int) ($user?->credit_days ?? 0);
    $dueDate = '-';
    // if ($invoiceDateObj && $creditDays > 0) {
    //     $dueDate = $invoiceDateObj->copy()->addDays($creditDays)->format('d-m-Y');
    // }
    $countryBusinessID = $userDetails?->country_id_business ?? '-';
    $countryBusiness = $countryBusinessID != '-' ? optional(\App\Models\Country::find($countryBusinessID))->name : '-';
    $postBusiness = $userDetails?->post_business ?? '-';
    $stateBusiness = $userDetails?->state_id_business ?? '-';
    $bookTo = $userDetails?->booked_to ?? '-';
    $dl1 = $userDetails?->dl1 ?? '-';
    $dl2 = $userDetails?->dl2 ?? '-';
    $dlExpiry = $userDetails?->dl_expiry ?? '-';
    // $dlExpiry = format_dd_mm_yy($userDetails?->dl_expiry);
    $paymentMethod = $order->payment_type ? translate(ucwords(str_replace('_', ' ', $order->payment_type))) : translate('Not provided');
    $paymentStatus = $order->payment_status ? translate(ucwords($order->payment_status)) : translate('Unpaid');
    $deliveryType = translate('Home Delivery');
    if ($order->shipping_type === 'pickup_point' && $order->pickup_point) {
        $deliveryType = $order->pickup_point->getTranslation('name');
    } elseif ($order->shipping_type === 'carrier' && $order->carrier) {
        $deliveryType = $order->carrier->name;
    }

    $subTotal = $order->orderDetails->sum('price');
    $shippingTotal = $order->orderDetails->sum('shipping_cost');
    $taxTotal = $order->orderDetails->sum('tax');
    $discountTotal = $order->coupon_discount ?? 0;
    $grandTotal = $order->grand_total;
    $totalQty = $order->orderDetails->sum('quantity');
@endphp
<div class="invoice-wrap">
    <table class="band">
        <tr>
            <td>{{ translate('Original For Buyers') }}<br>{{ translate('Duplicate For Records') }}</td>
            <td>{{ translate('Reverse Charges') }} : {{ translate('Yes') }} / {{ translate('No') }}<br>{{ translate('GST Invoice / Bill of Supply') }}</td>
            <td>{{ translate('Triplicate For Transporter') }}<br>{{ translate('For GST & FDA Record') }}</td>
        </tr>
    </table>

    <div class="title">{{ strtoupper($siteName) }}</div>
    <div class="contact">
        @if($contactAddress) {{ $contactAddress }}<br>@endif
        @if($contactPhone) {{ translate('Customer Care') }}: {{ $contactPhone }} | @endif
        @if($contactSalesPhone) {{ translate('Sales') }}: {{ $contactSalesPhone }} | @endif
        @if($contactAccountPhone) {{ translate('Account') }}: {{ $contactAccountPhone }} | @endif
        @if($contactEmail) {{ translate('E-mail') }}: {{ $contactEmail }} | @endif
        {{ translate('Website') }}: {{ $contactWebsite }}
    </div>

    <table class="meta">
        <tr>
            <td colspan="3">
                {{ translate('Billing Address') }}:
                <span class="label">{{ $customerName }}</span>
                @if($billingAddress)<br>{{ $billingAddress }}@endif
                <br>
                {{ translate('Pin Code') }}: {{ $pinCode ?: '-' }} {{ $shipping_postal_code !== '-' ? '| ' . translate('Post') . ': ' . $shipping_postal_code : '' }} {{ $shipping_state !== '-' ? '| ' . translate('State') . ': ' . $shipping_state : '' }}
            </td>
            <td class="head">
                {{ translate('Tax Invoice No.') }}: {{ $invoiceNo }}
                <br>
                {{ translate('Challan No.') }}: {{ $challanNo }}
                <br>
                {{ translate('Dated') }}: {{ $invoiceDate }}
                <br>
                {{ translate('Time') }}: {{ $invoiceTime }}
            </td>
        </tr>
        <tr>
            <td colspan="3">{{ translate('Shipping Address') }}: -</td>
            <td class="head">
                {{ translate('Terms') }}: {{ $creditDays }} 
                <br>
                {{ translate('Due Date') }}: {{ $dueDate }}
                <br>
                {{ translate('Order No.') }}: {{ $orderNo ?? '-' }}
                <br>
                {{ translate('Order By') }}: {{ $orderBy ?? '-' }}
            </td>
        </tr>
        <tr>
            <td class="head">{{ translate('Phone') }}: {{ $customerPhone ?: '-' }}</td>
            <td class="head">{{ translate('E-mail') }}: {{ $customerEmail ?: '-' }}</td>
            <td class="head">{{ translate('GST No') }}: {{ $customerGst ?: '-' }}</td>
            <td class="head">{{ translate('PAN No') }}: {{ $customerPan ?: '-' }}</td>
        </tr>
        {{-- <tr>
            <td class="head">{{ translate('Payment') }}: {{ $paymentMethod }}</td>
            <td class="head">{{ translate('Delivery Type') }}: {{ $deliveryType }}</td>
            <td class="head">{{ translate('Status') }}: {{ $paymentStatus }}</td>
            <td class="head">{{ translate('Customer ID') }}: {{ $userID }}</td>
            <td class="head">{{ translate('Customer ID') }}: {{ $userID }}</td>
        </tr> --}}
        <tr>
            <td class="head">{{ translate('DL 1') }}: {{ $dl1 ?: '-' }}</td>
            <td class="head">{{ translate('DL 2') }}: {{ $dl2 ?: '-' }}</td>
            <td class="head">{{ translate('DL Expiry') }}: {{ $dlExpiry }}</td>
        </tr>
        <tr>
            <td class="head">{{ translate('TRP') }}: -</td>
            <td class="head">{{ translate('Cases') }}: -</td>
            <td class="head">{{ translate('PM') }}: -</td>
            <td class="head">{{ translate('Trp-GST') }}: -</td>
        </tr>
        <tr>
            <td class="head">{{ translate('L.R.NO') }}: -</td>
            <td class="head">{{ translate('Weight') }}: -</td>
            <td class="head">{{ translate('Dimension') }}: -</td>
            <td class="head">{{ translate('EWB') }}: -</td>
        </tr>
        <tr>
            <td class="head">{{ translate('Book to') }}: {{ $bookTo ? $bookTo : '-' }}</td>
            <td class="head">{{ translate('State') }}: {{ $stateBusiness ? $stateBusiness : '-' }}</td>
            <td class="head">{{ translate('Post') }}: {{ $postBusiness ? $postBusiness : '-' }}</td>
            <td class="head">{{ translate('Country') }}: {{ $countryBusiness ? $countryBusiness : '-' }}</td>
        </tr>
    </table>

    <div style="margin-top: 8px;">
        <table class="items">
            <thead>
                <tr>
                    <th width="3%">{{ translate('Sr.') }}</th>
                    <th width="12%">{{ translate('Batch / Expiry') }}</th>
                    <th width="16%">{{ translate('Description') }}</th>
                    <th width="8%">{{ translate('Category') }}</th>
                    <th width="6%">{{ translate('HSN') }}</th>
                    <th width="7%">{{ translate('Pack') }}</th>
                    <th width="7%">{{ translate('Total Qty') }}</th>
                    <th width="8%">{{ translate('SGST') }}</th>
                    <th width="8%">{{ translate('CGST') }}</th>
                    <th width="8%">{{ translate('IGST') }}</th>
                    <th width="7%">{{ translate('GST Value') }}</th>
                    <th width="6%">{{ translate('Rate') }}</th>
                    <th width="6%">{{ translate('Gross') }}</th>
                    <th width="8%" class="text-right">{{ translate('Taxable Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->orderDetails as $idx => $detail)
                    @php
                        $product = $detail->product;
                        $variation = $detail->variation ? ' (' . $detail->variation . ')' : '';
                        $category = optional($product?->main_category)->getTranslation('name') ?? '-';
                        $hsn = $product->product_hsn ?? '-';
                        $qty = $detail->quantity;
                        $unitPrice = $qty > 0 ? $detail->price / $qty : $detail->price;
                        $lineGross = $detail->price;
                        $lineTax = $detail->tax;
                        $lineShipping = $detail->shipping_cost;
                        $taxableAmount = $lineGross;
                        $sgst = 0;
                        $cgst = 0;
                        $igst = $lineTax;
                        $pack = $product?->unit ?? translate('Unit');
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="text-center">-</td>
                        <td>
                            {{ optional($product)->name ?? translate('Product Removed') }}{{ $variation }}
                            @if($product && $product->stocks && $product->stocks->first())
                                @php $stock = json_decode($product->stocks->first(), true); @endphp
                                @if(!empty($stock['sku']))
                                    <div class="small">{{ translate('SKU') }}: {{ $stock['sku'] }}</div>
                                @endif
                            @endif
                        </td>
                        <td class="text-center">{{ $category }}</td>
                        <td class="text-center">{{ $hsn }}</td>
                        <td class="text-center">{{ $pack }}</td>
                        <td class="text-center">{{ $qty }}</td>
                        <td class="text-center">{{ single_price($sgst) }}</td>
                        <td class="text-center">{{ single_price($cgst) }}</td>
                        <td class="text-center">{{ single_price($igst) }}</td>
                        <td class="text-center">{{ single_price($lineTax) }}</td>
                        <td class="text-center">{{ single_price($unitPrice) }}</td>
                        <td class="text-center">{{ single_price($lineGross + $lineShipping) }}</td>
                        <td class="text-right">{{ single_price($taxableAmount) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center">{{ translate('No items found for this order.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <table class="meta" style="margin-top: 8px;">
        <tr>
            <td class="head">{{ translate('Qty') }}</td>
            <td>{{ $totalQty }}</td>
            <td class="head">{{ translate('Gross Value') }}</td>
            <td>{{ single_price($subTotal) }}</td>
            <td class="head">{{ translate('Total Taxable Amount') }}</td>
            <td>{{ single_price($subTotal) }}</td>
        </tr>
        <tr>
            <td class="head">{{ translate('Total GST Payable') }}</td>
            <td>{{ single_price($taxTotal) }}</td>
            <td class="head">{{ translate('CR/DR Note Adjusted') }}</td>
            <td>{{ single_price(0) }}</td>
            <td class="head">{{ translate('Grand Total') }}</td>
            <td><strong>{{ single_price($grandTotal) }}</strong></td>
        </tr>
    </table>

    <div class="note-band">
        {{ translate('PLEASE NOTE: NO EXPIRY / NO BREAKAGE / NO GOODS RETURN AT ANY CONDITION') }}
    </div>

    <table class="grid" style="margin-top: 6px;">
        <tr>
            <td width="16%">{{ translate('Freight Paid') }}</td>
            <td width="16%">{{ translate('C/C Attached') }}</td>
            <td width="16%">{{ translate('Door Delivery') }}</td>
            <td width="52%" rowspan="2">                
                <div class="label">{{ translate('Registered Under MSMED Act') }}</div>
                <div>{{ translate('Account Name') }} : {{ $siteName }}</div>
                <div>{{ translate('Bank') }} : {{ translate('Not provided') }}</div>
                <div>{{ translate('IFSC.') }} : {{ get_setting('bank_ifsc') ?? '-' }}</div>
                <div>{{ translate('GST No.') }} : {{ get_setting('company_gst') ?? '-' }}</div>
                <div>{{ translate('PAN No.') }} : {{ get_setting('company_pan') ?? '-' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <div class="label">{{ translate('Terms & Condition') }} :</div>
                <ol>
                    <li>{{ translate('Payment to be made by only A/C Payee cheque /Draft /IMPS / NEFT/ RTGS Only') }}</li>
                    <li>{{ translate('Payment should be made as per terms of payment stipulated in this Invoice.') }}</li>
                    <li>{{ translate('If Payment over due date then interest@36%p.a. will be charged extra.') }}</li>
                    <li>{{ translate('Our responsibility ceases the moment goods leave our premises.') }}</li>
                    <li>{{ translate('Goods once sold will not be taken back or echanged at any condition') }}</li>
                    <li>{{ translate('Expird and Breakage Goods will not be taken back or exchanged at any condition') }}</li>
                    <li>{{ translate('If Medicine Solution is not clear or ii contains suspended particles DO NOT USE & send back for Free replacement.,Subject to Mumbai Jurisdictions Only') }}</li>
                </ol>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="border-top:0; padding-top:2px; font-size:10px;">
                {{ translate('We hereby certify that my / our Registration Certificate under the Goods & Services Tax Act.2017 is in force on the date 1st July 2017 on which the sale of the goods specified in this GST invoice & Bill of Supply is made by me / us and that the transaction of sale covered by this GST invoice has been effected by me/ us and it shall be accounted for in the turnover of sales while filling of return and the due tax if any payable on the sale has been paid or shall be paid') }}
            </td>
            <td width="10%" class="footer-box text-center">
                <div class="red-color label">{{ translate('Payment Terms') }}</div>
                <div class="small">
                    {{ translate('Immediate Payment / 15 Days Max') }}
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="footer-box text-center">
                <br>
                <br>
                <div class="red-color label">{{ translate('Received By') }}</div>
                <div class="small">{{ translate('Sign with Rubber Stamp') }}</div>
            </td>
            <td class="footer-box text-center">
                <br>
                <br>
                <div class="label">{{ translate('For') }} <span class="red-color">{{ $siteName }}</span></div>
                <div style="margin-top: 22px;">{{ translate('Authorised Signatory') }}</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
