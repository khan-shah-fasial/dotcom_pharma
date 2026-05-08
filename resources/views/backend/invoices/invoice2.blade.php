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
            padding: 3px 4px;
            font-size: 10px;
        }
        .items th {
            background: #dfe6f3;
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
            line-height: 1.12;
        }
        .items td {
            vertical-align: middle;
            line-height: 1.15;
        }
        .items .product-name { font-weight: 700; text-align: left; }
        .items .qty-total { color: #00f; font-weight: 700; }
        .items .rate-value { color: #00f; }
        .items .mrp-value { color: #f00; font-weight: 700; }
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
    $siteName = get_setting('invoice_company_name') ?: 'Dotcom Pharma';
    $contactAddress = get_setting('contact_address', null, 'en');
    $contactPhone = get_setting('contact_phone');
    $contactSalesPhone = get_setting('contact_sales_phone');
    $contactAccountPhone = get_setting('contact_account_phone');
    $contactEmail = get_setting('contact_email');
    // $contactWebsite = get_setting('website_name') ?: url('/');
    $contactWebsite = get_setting('website_url') ?: url('/');
    $drugLicenceNumbers = array_filter(array_map('trim', explode(',', get_setting('drug_licence_numbers') ?? '')));

    $shipping = json_decode($order->shipping_address ?? '{}');
    $billing = json_decode($order->billing_address ?? '{}') ?: $shipping;
    $user = $order->user ?? null;
    $userID = $user->id ?? '-' ;

    $userDetails = $user->user_details;
    $customerName = $billing->name ?? optional($order->user)->name ?? translate('Customer');
    $customerEmail = $billing->email ?? optional($order->user)->email;
    $customerPhone = $billing->phone ?? optional($order->user)->phone;
    $customerGst = optional($order->user)->gst_no ?? ($billing->gst_no ?? null);
    $customerPan = optional($order->user)->pan_no ?? ($billing->pan_no ?? null);
    $pinCode = $billing->postal_code ?? null;

    $billingAddressParts = array_filter([
        $billing->address ?? null,
        $billing->city ?? null,
        $billing->state ?? null,
        $billing->country ?? null,
    ]);
    $billingAddress = implode(', ', $billingAddressParts);
    $billing_state = $billing->state ?? "-";

    // $shippingName = $shipping->name ?? $companyName;
    $shippingAddressParts = array_filter([
        $shipping->address ?? null,
        $shipping->city ?? null,
        $shipping->state ?? null,
        $shipping->country ?? null,
    ]);
    $shippingAddress = $shippingAddressParts ? implode(', ', $shippingAddressParts) : '-';
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
    if ($invoiceDateObj && $creditDays > 0) {
        $dueDate = $invoiceDateObj->copy()->addDays($creditDays)->format('d-m-Y');
    }
    $companyName = $userDetails?->company_name ?? null;
    $countryBusinessID = $userDetails?->country_id_business ?? null;
    $countryBusiness = $countryBusinessID ? optional(\App\Models\Country::find($countryBusinessID))->name : '-';
    $postBusiness = $userDetails?->post_business ?? '-';
    $stateBusinessId = $userDetails?->state_id_business ?? null;
    $stateBusiness = $stateBusinessId ? optional(\App\Models\State::find($stateBusinessId))->name : '-';
    $isMaharashtra = $stateBusiness && strcasecmp(trim($stateBusiness), 'maharashtra') === 0;
    $bookTo = $userDetails?->booked_to ?? '-';
    $transport = $userDetails?->transport ?? '-';
    $orderNo = $userDetails?->salesman ?? '-';
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
    $taxTotal = 0;
    $lineDiscountTotal = 0; // order details do not carry a discount field
    $couponDiscount = $order->coupon_discount ?? 0;
    $discountTotal = $couponDiscount + $lineDiscountTotal;
    $grandTotal = $order->grand_total;
    $ewbNumber = $grandTotal >= 50000 ? ($order->eway_bill ?? '-') : '-';
    $totalQty = $order->orderDetails->sum('quantity');
    $schemeQtyTotal = $order->orderDetails->sum(function ($row) {
        return (bool) ($row->is_scheme ?? false) ? ($row->quantity ?? 0) : 0;
    });
    $taxableTotal = $order->orderDetails->sum(function ($row) {
        $lineGross = $row->price ?? 0;
        $discount = $row->discount ?? 0;
        return max($lineGross - $discount, 0);
    });
    $sgstTotal = 0;
    $cgstTotal = 0;
    $igstTotal = 0;
    $exemptedValue = 0;
    $roundOff = 0;

    $invoiceLines = $order->orderDetails
        ->filter(function ($detail) {
            return !(bool) ($detail->is_scheme ?? false);
        })
        ->values()
        ->map(function ($detail) use ($order) {
            $schemeQty = $order->orderDetails
                ->filter(function ($row) use ($detail) {
                    return (bool) ($row->is_scheme ?? false)
                        && (int) ($row->product_id ?? 0) === (int) ($detail->product_id ?? 0)
                        && (string) ($row->variation ?? '') === (string) ($detail->variation ?? '')
                        && (int) ($row->batch_id ?? 0) === (int) ($detail->batch_id ?? 0);
                })
                ->sum('quantity');

            return [
                'detail' => $detail,
                'scheme_qty' => (int) $schemeQty,
                'total_qty' => (int) ($detail->quantity ?? 0) + (int) $schemeQty,
            ];
        });
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
                <span class="label">{{ $companyName }}</span>
                @if($billingAddress)<br>{{ $billingAddress }}@endif
                <br>
                {{ translate('Pin Code') }}: {{ $pinCode ?: '-' }} {{ $billing_state !== '-' ? '| ' . translate('State') . ': ' . $billing_state : '' }}
            </td>
            <td class="head">
                {{ translate('Tax Invoice No.') }}: {{ $invoiceNo }}
                {{-- <br>
                {{ translate('Challan No.') }}: {{ $challanNo }} --}}
                <br>
                {{ translate('Dated') }}: {{ $invoiceDate }}
                <br>
                {{ translate('Time') }}: {{ $invoiceTime }}
            </td>
        </tr>
        <tr>
            <td colspan="3">
                {{ translate('Shipping Address') }}:
                <span class="label">{{ $companyName }}</span>
                @if($shippingAddress)<br>{{ $shippingAddress }}@endif
                <br>
                {{ translate('Pin Code') }}: {{ $shipping_postal_code ?: '-' }} {{ $shipping_state !== '-' ? '| ' . translate('State') . ': ' . $shipping_state : '' }}
            </td>
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
            <td class="head">{{ translate('Shipped By') }}: {{ $transport }}</td>
            <td class="head">{{ translate('Cases') }}: -</td>
            <td class="head">{{ translate('PM') }}: -</td>
            <td class="head">{{ translate('Shipment-GST') }}: -</td>
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
                    <th width="3%" rowspan="2">{{ translate('Sr.') }}<br>{{ translate('No.') }}</th>
                    <th width="16%">{{ translate('Description') }}</th>
                    <th width="7%">{{ translate('Category') }}</th>
                    <th width="7%">{{ translate('Pack') }}</th>
                    <th width="8%" colspan="2">{{ translate('Total Qty') }}</th>
                    <th width="20%" colspan="4">{{ translate('GST Details') }}</th>
                    <th width="7%">{{ translate('Rate') }}</th>
                    <th width="8%" rowspan="2">{{ translate('Gross') }}<br>{{ translate('Value') }}</th>
                    <th width="7%" rowspan="2">{{ translate('Amt') }}<br>{{ translate('Dis') }} %</th>
                    <th width="9%" rowspan="2">{{ translate('Taxable') }}<br>{{ translate('Amount') }}</th>
                </tr>
                <tr>
                    <th>{{ translate('Batch No. / Expiry') }}</th>
                    <th>{{ translate('HSN') }}</th>
                    <th>{{ translate('MFG/MKT') }}</th>
                    <th>{{ translate('Qty') }}</th>
                    <th>{{ translate('SCM') }}</th>
                    <th>{{ translate('SGST') }}%</th>
                    <th>{{ translate('CGST') }}%</th>
                    <th>{{ translate('IGST') }}%</th>
                    <th>{{ translate('Value') }}</th>
                    <th class="red-color">{{ translate('M.R.P') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoiceLines as $idx => $invoiceLine)
                    @php
                        $detail = $invoiceLine['detail'];
                        $product = $detail->product;
                        $variation = $detail->variation ? ' (' . $detail->variation . ')' : '';
                        $category = optional($product?->main_category)->getTranslation('name') ?? '-';
                        $hsn = $product->product_hsn ?? '-';
                        $qty = $detail->quantity;
                        $schemeQty = $invoiceLine['scheme_qty'];
                        $displayTotalQty = $invoiceLine['total_qty'];
                        $unitPrice = $qty > 0 ? $detail->price / $qty : $detail->price;
                        $lineGross = $detail->price;
                        $lineTax = $detail->tax;
                        $lineShipping = $detail->shipping_cost;
                        $lineDiscountValue = 0; // order details do not store per-line discount
                        $lineCouponDiscount = ($couponDiscount > 0 && $subTotal > 0)
                            ? ($couponDiscount * ($lineGross / $subTotal))
                            : 0;
                        $discountValue = $lineDiscountValue + $lineCouponDiscount;
                        $taxableAmount = max($lineGross - $discountValue, 0);
                        if ($isMaharashtra) {
                            $sgst = $lineTax / 2;
                            $cgst = $lineTax / 2;
                            $igst = 0;
                        } else {
                            $sgst = 0;
                            $cgst = 0;
                            $igst = $lineTax;
                        }
                        $sgstPercent = $taxableAmount > 0 ? round(($sgst / $taxableAmount) * 100, 2) : 0;
                        $cgstPercent = $taxableAmount > 0 ? round(($cgst / $taxableAmount) * 100, 2) : 0;
                        $igstPercent = $taxableAmount > 0 ? round(($igst / $taxableAmount) * 100, 2) : 0;
                        $brandName = optional($product?->brand)->getTranslation('name') ?? optional($product?->brand)->name ?? '-';
                        $matchingStock = $product?->stocks?->where('variant', $detail->variation)->first() ?? $product?->stocks?->first();
                        $detailBatch = $detail->batch;
                        $batchNo = optional($detailBatch)->batch ?? '-';
                        $expiryDate = optional($detailBatch)->product_exp_date ?? optional($matchingStock)->product_exp_date ?? optional($product)->product_exp_date ?? null;
                        $expiryFormatted = $expiryDate ? format_dd_mm_yy($expiryDate) : '-';
                        $mrp = optional($detailBatch)->mrp_price ?? optional($matchingStock)->mrp_price ?? $product?->unit_price ?? $unitPrice;
                        $grossWithShipping = $lineGross + $lineShipping;
                        $discountPercent = $lineGross > 0 ? round(($discountValue / $lineGross) * 100, 2) : 0;
                        $pack = $matchingStock?->variant ?? $detail->variation ?? '-';
                        $sgstTotal += $sgst;
                        $cgstTotal += $cgst;
                        $igstTotal += $igst;
                        $taxTotal += $lineTax;
                    @endphp
                    <tr class="item-top">
                        <td rowspan="2" class="text-center">{{ $idx + 1 }}</td>
                        <td class="product-name">
                            {{ optional($product)->name ?? translate('Product Removed') }}{{ $variation }}
                            @php $stockArray = $matchingStock ? $matchingStock->toArray() : []; @endphp
                            @if(!empty($stockArray['sku']))
                                <div class="small">{{ translate('SKU') }}: {{ $stockArray['sku'] }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ $category }}</td>
                        <td class="text-center">{{ $pack }}</td>
                        <td colspan="2" class="text-center qty-total">{{ $displayTotalQty }}</td>
                        <td class="text-center">{{ single_price($sgst) }}</td>
                        <td class="text-center">{{ single_price($cgst) }}</td>
                        <td class="text-center">{{ single_price($igst) }}</td>
                        <td class="text-center">{{ single_price($lineTax) }}</td>
                        <td class="text-center rate-value">{{ single_price($unitPrice) }}</td>
                        <td rowspan="2" class="text-center">{{ single_price($grossWithShipping) }}</td>
                        <td class="text-center">{{ single_price($discountValue) }}</td>
                        <td rowspan="2" class="text-right">{{ single_price($taxableAmount) }}</td>
                    </tr>
                    <tr class="item-bottom">
                        <td class="text-center">
                            {{ $batchNo }} &nbsp; {{ $expiryFormatted }}
                        </td>
                        <td class="text-center">{{ $hsn }}</td>
                        <td class="text-center">{{ $brandName }}</td>
                        <td class="text-center">{{ $qty }}</td>
                        <td class="text-center">{{ $schemeQty }}</td>
                        <td class="text-center">{{ $sgstPercent }}</td>
                        <td class="text-center">{{ $cgstPercent }}</td>
                        <td class="text-center">{{ $igstPercent }}</td>
                        <td class="text-center"></td>
                        <td class="text-center mrp-value">{{ single_price($mrp) }}</td>
                        <td class="text-center">{{ $discountPercent }}</td>
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
            <td colspan="2" class="head">{{ translate('Qty') }}: {{ $totalQty }}</td>
            <td class="head">{{ translate('Gross Value') }}: {{ single_price($subTotal + $shippingTotal) }}</td>
            <td class="head">{{ translate('SGST') }}: {{ single_price($sgstTotal) }}</td>
            <td class="head">{{ translate('Total Taxable Amount') }}: {{ single_price($taxableTotal) }}</td>
        </tr>
        <tr>
            <td colspan="2" class="head">{{ translate('Scheme Qty (Free)') }}: {{ $schemeQtyTotal }}</td>
            <td class="head">{{ translate('Less Discount') }}: {{ single_price($discountTotal) }}</td>
            <td class="head">{{ translate('CGST') }}: {{ single_price($cgstTotal) }}</td>
            <td class="head">{{ translate('Total GST Payable') }}: {{ single_price($taxTotal) }}</td>
        </tr>
        <tr>
            <td colspan="2" class="head">{{ translate('Coupon Discount') }}: {{ single_price($couponDiscount) }}</td>
            <td class="head">{{ translate('Line Discount') }}: {{ single_price($lineDiscountTotal) }}</td>
            <td class="head">{{ translate('IGST') }}: {{ single_price($igstTotal) }}</td>
            <td class="head">{{ translate('CR/DR Note Adjusted') }}: {{ single_price(0) }}</td>
        </tr>
        <tr>
            <td colspan="2" class="head">{{ translate('Exempted Value') }}: {{ single_price($exemptedValue) }}</td>
            <td class="head">{{ translate('Insurance / Packing') }}: {{ single_price(0) }}</td>
            <td class="head">{{ translate('GST') }}: {{ single_price($taxTotal) }}</td>
            <td class="head">{{ translate('Round Off') }}: {{ single_price($roundOff) }}</td>
        </tr>
        <tr>
            <td colspan="4" class="head text-right">{{ translate('Grand Total') }}</td>
            <td colspan="1"><strong>{{ single_price($grandTotal) }}</strong></td>
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
                <div>{{ translate('Bank') }} : {{ get_setting('company_bank') ?? '-' }}</div>
                <div>{{ translate('IFSC.') }} : {{ get_setting('company_bank_ifsc') ?? '-' }}</div>
                <div>{{ translate('GST No.') }} : {{ get_setting('company_gst') ?? '-' }}</div>
                <div>{{ translate('PAN No.') }} : {{ get_setting('company_pan') ?? '-' }}</div>
                <div>{{ translate('Drug Licence') }} : 
                    @foreach($drugLicenceNumbers as $dlNum)
                        <br>{{ $dlNum }}
                    @endforeach
                </div>
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
            <td class="footer-box text-center">
                <br>
                <br>
                <div class="red-color label">{{ translate('Payment Terms') }}</div>
                <div>{{ translate('Immediate Payment / 15 Days Max') }}</div>
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
                <div>{{ translate('Authorised Signatory') }}</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
