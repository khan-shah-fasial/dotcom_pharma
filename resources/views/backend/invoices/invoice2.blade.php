<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ translate('Invoice') }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="UTF-8">
    <style media="all">
        @page { margin: 4px 7px; }
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
        .invoice-wrap { padding: 3px 4px 5px; background: #fff; }
        .band td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-weight: 700;
            text-align: center;
            font-size: 10px;
        }
        .title {
            text-align: center;
            font-size: 21px;
            font-weight: 800;
            letter-spacing: 0.8px;
            color: #c00;
            padding: 3px 0 2px;
        }
        .subtitle {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
        }
        .contact {
            text-align: center;
            font-size: 10px;
            line-height: 1.25;
            margin-bottom: 3px;
        }
        .meta td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-size: 10px;
        }
        .meta .head { font-weight: 700; width: 25%; }
        .section-title {
            background: #dfe6f3;
            border: 1px solid #000;
            padding: 3px 5px;
            font-weight: 700;
            font-size: 11px;
        }
        .box {
            border: 1px solid #000;
            padding: 4px;
            font-size: 10px;
            min-height: 70px;
        }
        .label { font-weight: 700; }
        .items th,
        .items td {
            border: 1px solid #000;
            padding: 2px 3px;
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
            padding: 3px 6px;
            font-weight: 700;
            text-align: center;
            font-size: 11px;
            margin-top: 4px;
        }
        .red-color { color: #c00; }
        .terms {
            border: 1px solid #000;
            padding: 4px;
            font-size: 10px;
            line-height: 1.25;
        }
        .footer-box td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-size: 10px;
        }
        .small { font-size: 9px; }
        .grid td { border: 1px solid #000; padding: 3px 4px; font-size: 10px; }
        .invoice-checkbox {
            display: inline-block;
            width: 11px;
            height: 11px;
            margin-left: 4px;
            border: 1px solid #000;
            font-size: 10px;
            font-weight: 700;
            line-height: 10px;
            text-align: center;
            vertical-align: middle;
        }
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
    $contactWebsite = get_setting('website_url') ?: get_setting('website_name');
    $drugLicenceNumbers = array_filter(array_map('trim', explode(',', get_setting('drug_licence_numbers') ?? '')));

    $shipping = json_decode($order->shipping_address ?? '{}');
    $billing = json_decode($order->billing_address ?? '{}') ?: $shipping;
    $user = $order->user ?? null;
    $invoiceType = $invoiceType ?? \App\Support\InvoiceType::forUser($user);
    $isDomestic = $invoiceType === \App\Support\InvoiceType::DOMESTIC;
    $isInternational = !$isDomestic;
    $userDetails = $user?->user_details;
    $customerName = $billing->name ?? optional($order->user)->name ?? translate('Customer');
    $customerEmail = $billing->email ?? optional($order->user)->email;
    $customerPhone = $billing->phone ?? optional($order->user)->phone;
    $customerGst = $userDetails?->gst_no ?: (optional($order->user)->gst_no ?? ($billing->gst_no ?? null));
    $customerIec = $userDetails?->iec_no ?: optional($order->user)->iec_no;
    $customerAadhaar = $userDetails?->aadhaar_no ?: optional($order->user)->aadhaar_no;
    $customerPan = $userDetails?->pan_no ?: (optional($order->user)->pan_no ?? ($billing->pan_no ?? null));
    $customerPassport = $userDetails?->passport_no ?: optional($order->user)->passport_no;
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
    $challanNo = $order->challan_number ?: '-';
    $invoiceDateObj = $order->date
        ? \Carbon\Carbon::createFromTimestamp($order->date)
        : ($order->created_at ? $order->created_at->copy() : null);
    $invoiceDate = $invoiceDateObj ? $invoiceDateObj->format('d-m-Y') : '-';
    $invoiceDay = $invoiceDateObj ? $invoiceDateObj->format('l') : null;
    $invoiceTime = $invoiceDateObj ? $invoiceDateObj->format('H:i:s') : '-';
    $creditDays = (int) ($user?->credit_days ?? 0);
    $dueDate = '-';
    if ($invoiceDateObj && $creditDays > 0) {
        $dueDate = $invoiceDateObj->copy()->addDays($creditDays)->format('d-m-Y');
    }
    $companyName = $userDetails?->company_name ?: $customerName;
    $stateBusiness = optional($userDetails?->businessState)->name
        ?: ($billing->state ?? optional($userDetails?->personalState)->name);
    $isMaharashtra = $stateBusiness && strcasecmp(trim($stateBusiness), 'maharashtra') === 0;
    $bookTo = optional($order->bookedTo)->name ?? ($userDetails?->booked_to ?? '-');
    $transport = optional($order->transport)->name
        ?? optional($order->localDeliveryPartner)->name
        ?? ($order->shipping_by ?: ($userDetails?->transport ?? '-'));
    $salesExecutiveName = optional($order->salesExecutive)->name;
    $salesExecutiveCode = $order->sales_man_code ?: $userDetails?->salesman;
    $dl1 = $userDetails?->d_l_no_1 ?: $userDetails?->dl1;
    $dl2 = $userDetails?->d_l_no_2 ?: $userDetails?->dl2;
    $dl3 = $userDetails?->d_l_no_3;
    $dlExpiry = $userDetails?->dl_expiry;
    $otherRegistration = $userDetails?->other_reg_no;
    // $dlExpiry = format_dd_mm_yy($userDetails?->dl_expiry);
    $paymentMethod = \App\Support\InvoiceType::paymentTermLabel($order->payment_type, $invoiceType)
        ?: ($order->payment_type ? translate(ucwords(str_replace('_', ' ', $order->payment_type))) : null);
    $paymentStatus = $order->payment_status ? translate(ucwords($order->payment_status)) : translate('Unpaid');
    $deliveryType = \App\Support\InvoiceType::deliveryTermLabel($order->transport_delivery_type, $invoiceType);
    $documentHasTax = (float) $order->orderDetails->sum('tax') > 0;
    $documentTitle = $isInternational
        ? 'Commercial Invoice'
        : ($documentHasTax ? 'GST Invoice' : 'Bill Of Supply');
    $invoiceNumberLabel = $isInternational
        ? 'Commercial Invoice No.'
        : ($documentHasTax ? 'Tax Invoice No.' : 'BOS Invoice No.');
    $addressHeading = $isDomestic ? 'Billing Address' : 'Buyer (Importer)';
    $shippingAddressHeading = $isDomestic ? 'Shipping Address' : 'Consignee';
    $licenceLabel = $isDomestic ? 'Drug Licence' : 'Pharmacy Licence';
    $transportDocumentLabel = $isDomestic
        ? 'LR / GR / Doc / Vehicle No. & Date'
        : 'B/L / AWB No. & Date';
    $destinationLabel = $isDomestic ? 'Booked To / Location' : 'Port Of Discharge';
    $carrierTaxLabel = $isDomestic ? 'Carrier GST No.' : 'Carrier Tax No.';
    $termsDeliveryLabel = $isInternational ? 'Incoterm / Terms Of Delivery' : 'Terms Of Delivery';
    $transportMode = $order->transport_mode
        ? translate(ucfirst($order->transport_mode) . ($order->transport_surface_mode ? ' / ' . ucfirst($order->transport_surface_mode) : ''))
        : null;
    $loadingLocation = $order->loading_location_type === 'sea'
        ? optional($order->loadingSeaPort)->name
        : optional($order->loadingAirport)->name;
    $dischargeLocation = $order->discharge_location_type === 'sea'
        ? optional($order->dischargeSeaPort)->name
        : optional($order->dischargeAirport)->name;
    $carrierTaxNumber = $order->carrier_tax_number;
    $recordFileNo = $userDetails?->record_file_no;
    $customerTypeStatus = implode(' / ', array_filter([$userDetails?->customer_type, $userDetails?->current_status]));
    $billingBy = optional($order->billingByStaff)->name;
    $countryOfOrigin = $order->orderDetails
        ->map(fn ($detail) => optional($detail->product)->product_origin)
        ->filter()->unique()->implode(', ');

    $shippingTotal = $order->orderDetails->sum('shipping_cost');
    $freightPaid = (bool) $order->freight_paid;
    $ccAttached = filled($order->cc_attached_path);
    $shippingInvoiceLine = shipping_invoice_line($order->orderDetails, $shippingTotal, $transport, translate('Shipping'));
    $shippingBaseAmount = $shippingInvoiceLine['base_amount'] ?? 0;
    $taxTotal = 0;
    $detailProductDiscount = function ($detail) {
        if ((bool) ($detail->is_scheme ?? false)) {
            return 0.0;
        }

        $quantity = max(0, (int) ($detail->quantity ?? 0));
        $saleUnit = $detail->sale_price !== null
            ? (float) $detail->sale_price
            : ($quantity > 0 ? (float) ($detail->price ?? 0) / $quantity : 0);
        $baseUnit = $detail->before_productandbatch_discount ?? $saleUnit;

        return round(max(0, (float) $baseUnit - $saleUnit) * $quantity, 2);
    };
    $detailCouponDiscount = function ($detail) use ($detailProductDiscount) {
        return round(max(0, (float) ($detail->discount_amount ?? 0) - $detailProductDiscount($detail)), 2);
    };
    $productDiscountTotal = $order->orderDetails->sum($detailProductDiscount);
    $couponDiscount = $order->coupon_discount ?? 0;
    $grandTotal = $order->grand_total;
    $systemCurrency = get_system_default_currency();
    $invoiceCurrencyCode = $order->quote_currency_code ?: optional($systemCurrency)->code;
    $invoiceCurrency = $invoiceCurrencyCode
        ? \App\Models\Currency::where('code', $invoiceCurrencyCode)->first()
        : null;
    $invoiceCurrency = $invoiceCurrency ?: $systemCurrency;
    $invoiceCurrencySymbol = optional($invoiceCurrency)->symbol ?: optional($systemCurrency)->symbol;
    $invoiceExchangeRate = $order->quote_currency_exchange_rate ?: optional($invoiceCurrency)->exchange_rate ?: optional($systemCurrency)->exchange_rate ?: 1;
    $systemExchangeRate = optional($systemCurrency)->exchange_rate ?: 1;
    $invoicePrice = function ($price) use ($invoiceCurrencyCode, $invoiceCurrencySymbol, $invoiceExchangeRate, $systemCurrency, $systemExchangeRate) {
        $price = (float) $price;

        if ($invoiceCurrencyCode && $invoiceCurrencyCode !== optional($systemCurrency)->code) {
            $price = ($price / (float) $systemExchangeRate) * (float) $invoiceExchangeRate;
        }

        if (get_setting('decimal_separator') == 1) {
            $formattedPrice = number_format($price, get_setting('no_of_decimals'));
        } else {
            $formattedPrice = number_format($price, get_setting('no_of_decimals'), ',', '.');
        }

        if (get_setting('symbol_format') == 1 || get_setting('symbol_format') == 3) {
            return $invoiceCurrencySymbol . ' ' . $formattedPrice;
        } elseif (get_setting('symbol_format') == 4) {
            return $formattedPrice . ' ' . $invoiceCurrencySymbol;
        }

        return $formattedPrice . $invoiceCurrencySymbol;
    };
    $paidQtyTotal = $order->orderDetails->sum(function ($row) {
        return (bool) ($row->is_scheme ?? false) ? 0 : ($row->quantity ?? 0);
    });
    $totalQty = $order->orderDetails->sum('quantity');
    $schemeQtyTotal = $order->orderDetails->sum(function ($row) {
        return (bool) ($row->is_scheme ?? false) ? ($row->quantity ?? 0) : 0;
    });
    $productGrossTotal = $order->orderDetails->sum(function ($row) {
        if ((bool) ($row->is_scheme ?? false)) {
            return 0;
        }

        $qty = max(0, (int) ($row->quantity ?? 0));
        $saleUnit = $row->sale_price !== null
            ? (float) $row->sale_price
            : ($qty > 0 ? ((float) ($row->price ?? 0) / $qty) : 0);
        $beforeUnit = $row->before_productandbatch_discount ?? $saleUnit;

        return max(0, (float) $beforeUnit) * $qty;
    });
    $taxableTotal = $order->orderDetails->sum(function ($row) use ($detailCouponDiscount) {
        if ((bool) ($row->is_scheme ?? false)) {
            return 0;
        }

        return max(0, order_detail_line_subtotal($row) - $detailCouponDiscount($row));
    });
    $sgstTotal = 0;
    $cgstTotal = 0;
    $igstTotal = 0;
    $pointsEarned = $order->orderDetails->sum(function ($row) {
        return (bool) ($row->is_scheme ?? false)
            ? 0
            : ((float) ($row->earn_point ?? 0) * (int) ($row->quantity ?? 0));
    });

    $paidDetails = $order->orderDetails
        ->filter(function ($detail) {
            return !(bool) ($detail->is_scheme ?? false);
        })
        ->values();
    $schemeDetails = $order->orderDetails
        ->filter(function ($detail) {
            return (bool) ($detail->is_scheme ?? false);
        })
        ->values();

    $invoiceLines = collect();
    $mergedSchemeDetailIds = [];
    foreach ($paidDetails as $detail) {
        $sameBatchSchemeRows = $schemeDetails
            ->filter(function ($row) use ($detail) {
                return (int) ($row->product_id ?? 0) === (int) ($detail->product_id ?? 0)
                    && (string) ($row->variation ?? '') === (string) ($detail->variation ?? '')
                    && (int) ($row->batch_id ?? 0) === (int) ($detail->batch_id ?? 0);
            })
            ->filter(function ($row) use ($mergedSchemeDetailIds) {
                return !in_array((int) ($row->id ?? 0), $mergedSchemeDetailIds, true);
            });
        $sameBatchSchemeQty = $sameBatchSchemeRows->sum('quantity');
        foreach ($sameBatchSchemeRows as $row) {
            $mergedSchemeDetailIds[] = (int) ($row->id ?? 0);
        }

        $invoiceLines->push([
            'detail' => $detail,
            'scheme_qty' => (int) $sameBatchSchemeQty,
            'paid_qty' => (int) ($detail->quantity ?? 0),
            'total_qty' => (int) ($detail->quantity ?? 0) + (int) $sameBatchSchemeQty,
            'is_scheme_only' => false,
        ]);
    }

    foreach ($schemeDetails as $schemeDetail) {
        if (in_array((int) ($schemeDetail->id ?? 0), $mergedSchemeDetailIds, true)) {
            continue;
        }

        $hasPaidSameBatch = $paidDetails->contains(function ($detail) use ($schemeDetail) {
            return (int) ($detail->product_id ?? 0) === (int) ($schemeDetail->product_id ?? 0)
                && (string) ($detail->variation ?? '') === (string) ($schemeDetail->variation ?? '')
                && (int) ($detail->batch_id ?? 0) === (int) ($schemeDetail->batch_id ?? 0);
        });

        if (!$hasPaidSameBatch) {
            $invoiceLines->push([
                'detail' => $schemeDetail,
                'scheme_qty' => (int) ($schemeDetail->quantity ?? 0),
                'paid_qty' => 0,
                'total_qty' => (int) ($schemeDetail->quantity ?? 0),
                'is_scheme_only' => true,
            ]);
        }
    }
@endphp
<div class="invoice-wrap">
    <table class="band">
        <tr>
            <td>
                @if($isDomestic)
                    {{ translate('Reverse Charges') }}: {{ $order->reverse_charge === null ? translate('None') : ($order->reverse_charge ? translate('Yes') : translate('No')) }}<br>
                @endif
                {{ $documentTitle }}
            </td>
        </tr>
    </table>

    <div class="title">{{ strtoupper($siteName) }}</div>
    <div class="contact">
        @if($contactAddress) {{ $contactAddress }}<br>@endif
        @if($contactPhone) {{ translate('Customer Care') }}: {{ $contactPhone }} | @endif
        @if($contactSalesPhone) {{ translate('Sales') }}: {{ $contactSalesPhone }} | @endif
        @if($contactAccountPhone) {{ translate('Account') }}: {{ $contactAccountPhone }} | @endif
        @if($contactEmail) {{ translate('E-mail') }}: {{ $contactEmail }}@if($contactWebsite) | @endif @endif
        @if($contactWebsite){{ translate('Website') }}: {{ $contactWebsite }}@endif
    </div>

    <table class="meta">
        <tr>
            <td colspan="3">
                {{ $addressHeading }}:
                <span class="label">{{ $companyName }}</span>
                @if($billingAddress)<br>{{ $billingAddress }}@endif
                @if($pinCode)<br>{{ translate('Pin Code') }}: {{ $pinCode }}@endif
                @if($billing_state && $billing_state !== '-') | {{ translate('State') }}: {{ $billing_state }}@endif
            </td>
            <td class="head">
                {{ $invoiceNumberLabel }}: {{ $invoiceNo }}
                @if($order->challan_number)<br>{{ translate('Challan No.') }}: {{ $challanNo }}@endif
                <br>{{ translate('Date') }}: {{ $invoiceDate }}@if($invoiceDay) ({{ $invoiceDay }})@endif
                <br>
                {{ translate('Time') }}: {{ $invoiceTime }}
            </td>
        </tr>
        <tr>
            <td colspan="3">
                {{ $shippingAddressHeading }}:
                <span class="label">{{ $companyName }}</span>
                @if($shippingAddress)<br>{{ $shippingAddress }}@endif
                @if($shipping_postal_code && $shipping_postal_code !== '-')<br>{{ translate('Pin Code') }}: {{ $shipping_postal_code }}@endif
                @if($shipping_state && $shipping_state !== '-') | {{ translate('State') }}: {{ $shipping_state }}@endif
            </td>
            <td class="head">
                @if($isDomestic)
                    {{ translate('Credit Days') }}: {{ $creditDays }}
                    <br>{{ translate('Due Date') }}: {{ $dueDate }}
                @endif
                @if($customerName)
                    @if($isDomestic)<br>@endif{{ translate('Order By') }}: {{ $customerName }}
                @endif
            </td>
        </tr>
        <tr>
            @if($customerPhone)<td class="head">{{ translate('Phone') }}: {{ $customerPhone }}</td>@endif
            @if($customerEmail)<td class="head">{{ translate('E-mail') }}: {{ $customerEmail }}</td>@endif
        </tr>
        @if(($isDomestic && ($customerGst || $customerAadhaar || $customerPan)) || ($isInternational && ($customerIec || $customerPassport || $customerPan)))
        <tr>
            @if($isDomestic && $customerGst)<td class="head">{{ translate('GST No.') }}: {{ $customerGst }}</td>@endif
            @if($isInternational && $customerIec)<td class="head">{{ translate('IEC No.') }}: {{ $customerIec }}</td>@endif
            @if($isDomestic && $customerAadhaar)<td class="head">{{ translate('Aadhaar No.') }}: {{ $customerAadhaar }}</td>@endif
            @if($isInternational && $customerPassport)<td class="head">{{ translate('Passport No.') }}: {{ $customerPassport }}</td>@endif
            @if($customerPan)<td class="head">{{ $isDomestic ? translate('PAN No.') : translate('Income Tax ID') }}: {{ $customerPan }}</td>@endif
        </tr>
        @endif
        @if($dl1 || $dl2 || $dl3 || $dlExpiry || $otherRegistration)
        <tr>
            @if($dl1)<td class="head">{{ $licenceLabel }} {{ translate('No. 1') }}: {{ $dl1 }}</td>@endif
            @if($dl2)<td class="head">{{ $licenceLabel }} {{ translate('No. 2') }}: {{ $dl2 }}</td>@endif
            @if($dl3)<td class="head">{{ $licenceLabel }} {{ translate('No. 3') }}: {{ $dl3 }}</td>@endif
            @if($dlExpiry)<td class="head">{{ $licenceLabel }} {{ translate('Expiry') }}: {{ $dlExpiry }}</td>@endif
            @if($otherRegistration)<td class="head">{{ translate('Other Registration') }}: {{ $otherRegistration }}</td>@endif
        </tr>
        @endif
        @if($paymentMethod || $paymentStatus || $deliveryType || $order->po_number)
        <tr>
            @if($paymentMethod)<td class="head">Payment Terms: {{ $paymentMethod }}</td>@endif
            @if($paymentStatus)<td class="head">{{ translate('Status') }}: {{ $paymentStatus }}</td>@endif
            @if($deliveryType)<td class="head">{{ $termsDeliveryLabel }}: {{ $deliveryType }}</td>@endif
            @if($order->po_number)
                <td class="head">{{ translate('P.O. No. & Date') }}: {{ $order->po_number }}@if($order->po_date) / {{ $order->po_date->format('d-m-Y') }}@endif</td>
            @endif
        </tr>
        @endif
        @if($transportMode || ($transport && $transport !== '-') || $order->cases || $shippingTotal > 0)
        <tr>
            @if($transportMode)<td class="head">{{ translate('Mode Of Transport') }}: {{ $transportMode }}</td>@endif
            @if($transport && $transport !== '-')<td class="head">{{ translate('Carrier By') }}: {{ $transport }}</td>@endif
            @if($order->cases)<td class="head">{{ translate('Cases') }}: {{ $order->cases }}</td>@endif
            @if($shippingTotal > 0)<td class="head">{{ translate('Shipping / Freight') }}: {{ $invoicePrice($shippingTotal) }}</td>@endif
        </tr>
        @endif
        @if($order->lr_number || ($isDomestic && $bookTo && $bookTo !== '-') || ($isInternational && ($dischargeLocation || ($bookTo && $bookTo !== '-'))) || $loadingLocation)
        <tr>
            @if($order->lr_number)
                <td class="head">{{ $transportDocumentLabel }}: {{ $order->lr_number }}@if($order->lr_date) / {{ $order->lr_date->format('d-m-Y') }}@endif</td>
            @endif
            @if($isDomestic && $bookTo && $bookTo !== '-')
                <td class="head">{{ $destinationLabel }}: {{ $bookTo }}</td>
            @elseif($isInternational && ($dischargeLocation || ($bookTo && $bookTo !== '-')))
                <td class="head">{{ $destinationLabel }}: {{ $dischargeLocation ?: $bookTo }}</td>
            @endif
            @if($isInternational && $loadingLocation)
                <td class="head">Sea / Air Port Of Loading: {{ $loadingLocation }}</td>
            @endif
            @if($order->final_destination)<td class="head">{{ translate('Final Destination') }}: {{ $order->final_destination }}</td>@endif
        </tr>
        @endif
        @if($order->net_weight_kg || $order->gross_weight_kg || $order->total_volume_cbm || $carrierTaxNumber)
        <tr>
            @if($order->net_weight_kg)<td class="head">{{ translate('Net Weight') }}: {{ $order->net_weight_kg }} KG</td>@endif
            @if($order->gross_weight_kg)<td class="head">{{ translate('Gross Weight') }}: {{ $order->gross_weight_kg }} KG</td>@endif
            @if($order->total_volume_cbm)<td class="head">{{ translate('Total Volume / CBM') }}: {{ $order->total_volume_cbm }}</td>@endif
            @if($carrierTaxNumber)<td class="head">{{ $carrierTaxLabel }}: {{ $carrierTaxNumber }}</td>@endif
        </tr>
        @endif
        @if($recordFileNo || $customerTypeStatus || $billingBy || $salesExecutiveName || $salesExecutiveCode)
        <tr>
            @if($recordFileNo)<td class="head">{{ translate('Record File No.') }}: {{ $recordFileNo }}</td>@endif
            @if($customerTypeStatus)<td class="head">{{ translate('Customer Type / Status') }}: {{ $customerTypeStatus }}</td>@endif
            @if($billingBy)<td class="head">{{ translate('Billing By') }}: {{ $billingBy }}</td>@endif
            @if($salesExecutiveName || $salesExecutiveCode)
                <td class="head">{{ translate('Sales Executive Name & Code') }}: {{ implode(' / ', array_filter([$salesExecutiveName, $salesExecutiveCode])) }}</td>
            @endif
        </tr>
        @endif
        @if($countryOfOrigin || $invoiceCurrencyCode || $order->consignee_copy_status)
        <tr>
            @if($countryOfOrigin)<td class="head">{{ translate('Country Of Origin') }}: {{ $countryOfOrigin }}</td>@endif
            @if($invoiceCurrencyCode)<td class="head">{{ translate('Currency') }}: {{ $invoiceCurrencyCode }}</td>@endif
            @if($order->consignee_copy_status)<td class="head">{{ translate('Consignee Copy') }}: {{ translate(ucwords(str_replace('_', ' ', $order->consignee_copy_status))) }}</td>@endif
        </tr>
        @endif
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
                        $qty = $invoiceLine['paid_qty'] ?? $detail->quantity;
                        $schemeQty = $invoiceLine['scheme_qty'];
                        $displayTotalQty = $invoiceLine['total_qty'];
                        $lineSaleSubtotal = order_detail_line_subtotal($detail);
                        $saleUnitPrice = $qty > 0 ? $lineSaleSubtotal / $qty : 0;
                        $unitPrice = $detail->before_productandbatch_discount ?? $saleUnitPrice;
                        $lineGross = $unitPrice * max(0, (int) $qty);
                        $lineTax = $detail->tax;
                        $productDiscountValue = $detailProductDiscount($detail);
                        $lineCouponDiscount = $detailCouponDiscount($detail);
                        $discountValue = $productDiscountValue + $lineCouponDiscount;
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
                        $grossWithShipping = $lineGross;
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
                            @if(!empty($invoiceLine['is_scheme_only']))
                                <div class="small">{{ translate('Scheme Free') }}</div>
                            @endif
                            @php $stockArray = $matchingStock ? $matchingStock->toArray() : []; @endphp
                            @if(!empty($stockArray['sku']))
                                <div class="small">{{ translate('SKU') }}: {{ $stockArray['sku'] }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ $category }}</td>
                        <td class="text-center">{{ $pack }}</td>
                        <td colspan="2" class="text-center qty-total">{{ $displayTotalQty }}</td>
                        <td class="text-center">{{ $invoicePrice($sgst) }}</td>
                        <td class="text-center">{{ $invoicePrice($cgst) }}</td>
                        <td class="text-center">{{ $invoicePrice($igst) }}</td>
                        <td class="text-center">{{ $invoicePrice($lineTax) }}</td>
                        <td class="text-center rate-value">{{ $invoicePrice($unitPrice) }}</td>
                        <td rowspan="2" class="text-center">{{ $invoicePrice($grossWithShipping) }}</td>
                        <td class="text-center">{{ $invoicePrice($discountValue) }}</td>
                        <td rowspan="2" class="text-right">{{ $invoicePrice($taxableAmount) }}</td>
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
                        <td class="text-center mrp-value">{{ $invoicePrice($mrp) }}</td>
                        <td class="text-center">{{ $discountPercent }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center">{{ translate('No items found for this order.') }}</td>
                    </tr>
                @endforelse
                @if($shippingInvoiceLine)
                    @php
                        $shippingSgst = $isMaharashtra ? $shippingInvoiceLine['gst_amount'] / 2 : 0;
                        $shippingCgst = $isMaharashtra ? $shippingInvoiceLine['gst_amount'] / 2 : 0;
                        $shippingIgst = $isMaharashtra ? 0 : $shippingInvoiceLine['gst_amount'];
                        $shippingSgstPercent = $isMaharashtra ? $shippingInvoiceLine['gst_percent'] / 2 : 0;
                        $shippingCgstPercent = $isMaharashtra ? $shippingInvoiceLine['gst_percent'] / 2 : 0;
                        $shippingIgstPercent = $isMaharashtra ? 0 : $shippingInvoiceLine['gst_percent'];
                        $sgstTotal += $shippingSgst;
                        $cgstTotal += $shippingCgst;
                        $igstTotal += $shippingIgst;
                        $taxTotal += $shippingInvoiceLine['gst_amount'];
                    @endphp
                    <tr class="item-top">
                        <td rowspan="2" class="text-center">{{ $invoiceLines->count() + 1 }}</td>
                        <td class="product-name">{{ $shippingInvoiceLine['description'] }}</td>
                        <td class="text-center">{{ translate('Shipping') }}</td>
                        <td class="text-center">-</td>
                        <td colspan="2" class="text-center qty-total">1</td>
                        <td class="text-center">{{ $invoicePrice($shippingSgst) }}</td>
                        <td class="text-center">{{ $invoicePrice($shippingCgst) }}</td>
                        <td class="text-center">{{ $invoicePrice($shippingIgst) }}</td>
                        <td class="text-center">{{ $invoicePrice($shippingInvoiceLine['gst_amount']) }}</td>
                        <td class="text-center rate-value">{{ $invoicePrice($shippingInvoiceLine['base_amount']) }}</td>
                        <td rowspan="2" class="text-center">{{ $invoicePrice($shippingInvoiceLine['total_amount']) }}</td>
                        <td class="text-center">{{ $invoicePrice(0) }}</td>
                        <td rowspan="2" class="text-right">{{ $invoicePrice($shippingInvoiceLine['base_amount']) }}</td>
                    </tr>
                    <tr class="item-bottom">
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">-</td>
                        <td class="text-center">1</td>
                        <td class="text-center">0</td>
                        <td class="text-center">{{ round($shippingSgstPercent, 2) }}</td>
                        <td class="text-center">{{ round($shippingCgstPercent, 2) }}</td>
                        <td class="text-center">{{ round($shippingIgstPercent, 2) }}</td>
                        <td class="text-center"></td>
                        <td class="text-center mrp-value">{{ $invoicePrice($shippingInvoiceLine['total_amount']) }}</td>
                        <td class="text-center">0</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <table class="meta" style="margin-top: 8px;">
        <tr>
            <td colspan="2" class="head">{{ translate('Qty') }}: {{ $paidQtyTotal }}</td>
            <td class="head">{{ translate('Gross Value') }}: {{ $invoicePrice($productGrossTotal + $shippingBaseAmount) }}</td>
            <td class="head">{{ translate('SGST') }}: {{ $invoicePrice($sgstTotal) }}</td>
            <td class="head">{{ translate('Total Taxable Amount') }}: {{ $invoicePrice($taxableTotal + $shippingBaseAmount) }}</td>
        </tr>
        <tr>
            <td colspan="2" class="head">{{ translate('Scheme Qty (Free)') }}: {{ $schemeQtyTotal }}</td>
            <td class="head">{{ translate('Product Discount') }}: {{ $invoicePrice($productDiscountTotal) }}</td>
            <td class="head">{{ translate('CGST') }}: {{ $invoicePrice($cgstTotal) }}</td>
            <td class="head">{{ translate('Total GST Payable') }}: {{ $invoicePrice($taxTotal) }}</td>
        </tr>
        <tr>
            <td colspan="2" class="head">{{ translate('Total QTY') }}: {{ $totalQty }}</td>
            <td class="head">{{ translate('Coupon Discount') }}: {{ $invoicePrice($couponDiscount) }}</td>
            <td class="head">{{ translate('IGST') }}: {{ $invoicePrice($igstTotal) }}</td>
            <td class="head">{{ translate('Shipping / Freight') }}: {{ $invoicePrice($shippingTotal) }}</td>
        </tr>
        @if($pointsEarned > 0)
        <tr>
            <td colspan="5" class="head">{{ translate('Total Points Earn') }}: {{ number_format($pointsEarned, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td colspan="4" class="head text-right">{{ translate('Grand Total') }}</td>
            <td colspan="1"><strong>{{ $invoicePrice($grandTotal) }}</strong></td>
        </tr>
    </table>

    @if($isDomestic)
        <div class="note-band">
            {{ translate('PLEASE NOTE: NO EXPIRY / NO BREAKAGE / NO GOODS RETURN AT ANY CONDITION') }}
        </div>
    @endif

    <table class="grid" style="margin-top: 6px;">
        <tr>
            <td width="16%">
                {{ translate('Freight Paid') }}
                <span class="invoice-checkbox">@if($freightPaid)&#10003;@endif</span>
            </td>
            <td width="16%">
                {{ translate('C/C Attached') }}
                <span class="invoice-checkbox">@if($ccAttached)&#10003;@endif</span>
                @if($order->attached_file_name)<br>{{ $order->attached_file_name }}@endif
            </td>
            <td width="16%">
                @if($deliveryType)
                    {{ $termsDeliveryLabel }}: {{ $deliveryType }}
                @endif
            </td>
            <td width="52%" rowspan="2">                
                <div class="label">{{ translate('Registered Under MSMED Act') }}</div>
                <div>{{ translate('Account Name') }} : {{ $siteName }}</div>
                @if(get_setting('company_bank'))<div>{{ translate('Bank') }} : {{ get_setting('company_bank') }}</div>@endif
                @if(get_setting('company_bank_ifsc'))<div>{{ translate('IFSC.') }} : {{ get_setting('company_bank_ifsc') }}</div>@endif
                @if(get_setting('company_gst'))<div>{{ translate('GST No.') }} : {{ get_setting('company_gst') }}</div>@endif
                @if(get_setting('company_pan'))<div>{{ translate('PAN No.') }} : {{ get_setting('company_pan') }}</div>@endif
                @if($drugLicenceNumbers)
                <div>{{ $isDomestic ? translate('Drug Licence') : translate('Pharmacy Licence') }} :
                    @foreach($drugLicenceNumbers as $dlNum)
                        <br>{{ $dlNum }}
                    @endforeach
                </div>
                @endif
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
                    <li>{{ translate('If medicine solution is not clear or it contains suspended particles, DO NOT USE it and send it back for free replacement.') }}</li>
                    <li>{{ translate('Subject to Mumbai jurisdiction only.') }}</li>
                </ol>
            </td>
        </tr>
        @if($isDomestic)
        <tr>
            <td colspan="4" style="border-top:0; padding-top:2px; font-size:10px;">
                {{ translate('We hereby certify that my / our Registration Certificate under the Goods & Services Tax Act.2017 is in force on the date 1st July 2017 on which the sale of the goods specified in this GST invoice & Bill of Supply is made by me / us and that the transaction of sale covered by this GST invoice has been effected by me/ us and it shall be accounted for in the turnover of sales while filling of return and the due tax if any payable on the sale has been paid or shall be paid') }}
            </td>
        </tr>
        @endif
        <tr>
            <td colspan="3" class="footer-box text-center">
                <br>
                <div class="red-color label">{{ translate('Received By') }}</div>
                <div class="small">{{ translate('Sign with Rubber Stamp') }}</div>
            </td>
            <td class="footer-box text-center">
                <br>
                <div class="label">{{ translate('For') }} <span class="red-color">{{ $siteName }}</span></div>
                <div>{{ translate('Authorised Signatory') }}</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
