<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ translate('Invoice') }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="UTF-8">
    <style media="all">
        @page { margin: 6mm 5mm; }
        body {
            margin: 0;
            padding: 0;
            font-size: 9px;
            font-family: {!! $font_family !!};
            direction: {{ $direction }};
            text-align: {{ $text_align }};
            color: #000;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .invoice-wrap { padding: 0; background: #fff; }
        .doc-title {
            text-align: center;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.4px;
            color: #c00;
            padding: 1px 0 3px;
        }
        .co-name {
            text-align: center;
            font-size: 16px;
            font-weight: 800;
            color: #c00;
            letter-spacing: 0.4px;
            padding-bottom: 2px;
        }
        .meta td, .logistics td, .totals td, .terms td, .sign td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 8px;
            line-height: 1.25;
        }
        .label { font-weight: 700; }
        .party-name { font-weight: 700; font-size: 10px; }
        .inner { border: 0; }
        .inner td { border: 0; padding: 1px 0; font-size: 8px; }
        .items { table-layout: fixed; }
        .items th, .items td {
            border: 1px solid #000;
            padding: 1px 2px;
            font-size: 7px;
            line-height: 1.15;
            word-wrap: break-word;
        }
        .items th {
            background: #e7f0d8;
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
        }
        .items td { vertical-align: middle; }
        .items .product-name { font-weight: 700; text-align: left; }
        .num { text-align: right; white-space: nowrap; }
        .text-right { text-align: {{ $not_text_align }}; }
        .text-center { text-align: center; }
        .red { color: #c00; font-weight: 700; }
        .totals td { font-size: 8px; }
        .totals .grand { font-size: 10px; font-weight: 800; color: #c00; }
        .terms { font-size: 8px; line-height: 1.3; }
        .terms ol { margin: 2px 0 0 16px; padding: 0; }
        .sign td { height: 72px; text-align: center; vertical-align: bottom; font-size: 9px; }
        .small { font-size: 7px; }
        .hl { background: #ffff00; }
        .hl-red { background: #ffff00; color: #c00; font-weight: 700; }
        .logo { height: 42px; margin-bottom: 2px; }
        .qr svg { width: 62px; height: 62px; }
        .sign td.qr { height: auto; vertical-align: middle; }
        .qr-label { font-size: 7px; line-height: 1.1; margin-top: 1px; }
        .seal {
            width: 74px;
            height: 74px;
            margin: 0 auto 2px;
            border: 2px solid #1a4f8b;
            border-radius: 50%;
            color: #1a4f8b;
            font-size: 7px;
            font-weight: 700;
            line-height: 1.15;
            text-align: center;
            padding: 18px 6px 0;
        }
        .seal img { width: 74px; height: 74px; object-fit: contain; }
    </style>
</head>
<body>
@php
    $dash = function ($value) {
        if ($value === null) {
            return '—';
        }
        if (is_string($value) && trim($value) === '') {
            return '—';
        }
        return $value;
    };
    $formatMeasure = function ($value, $unit) {
        if ($value === null || $value === '') {
            return '—';
        }
        $number = rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
        return $number . ' ' . $unit;
    };
    $formatVolume = function ($value) {
        if ($value === null || $value === '') {
            return '—';
        }
        $number = rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');
        if ($number === '' || $number === '0') {
            return '0 CBM';
        }
        return $number . ' CBM';
    };
    $formatPercent = function ($value) {
        return number_format((float) $value, 3, '.', '');
    };
    $formatRate = function ($value) {
        return number_format((float) $value, 2, '.', '');
    };
    $monthYear = function ($date) {
        if ($date === null || $date === '') {
            return '—';
        }
        try {
            return \Carbon\Carbon::parse($date)->format('m-y');
        } catch (\Throwable $exception) {
            return '—';
        }
    };

    $issuingCompany = issuing_company_for_order_code($order->code ?? null);
    $siteName = trim((string) ($issuingCompany?->company_name ?? ''));
    if ($siteName === '') {
        $siteName = trim((string) (get_setting('invoice_company_name') ?: ''));
    }
    $contactAddress = trim((string) ($issuingCompany?->full_address ?? ''));
    if ($contactAddress === '') {
        $contactAddress = get_setting('contact_address', null, 'en');
    }
    $contactPhone = trim((string) ($issuingCompany?->mobile ?? ''));
    if ($contactPhone === '') {
        $contactPhone = get_setting('contact_phone');
    }
    $contactWhatsapp = trim((string) ($issuingCompany?->whatsapp ?? ''));
    $contactSalesPhone = get_setting('contact_sales_phone');
    $contactAccountPhone = get_setting('contact_account_phone');
    $contactEmail = trim((string) ($issuingCompany?->email ?? ''));
    if ($contactEmail === '') {
        $contactEmail = get_setting('contact_email');
    }
    $contactWebsite = get_setting('website_url');
    $drugLicenceNumbers = array_filter(array_map('trim', explode(',', (string) (get_setting('drug_licence_numbers') ?? ''))));

    $shipping = json_decode($order->shipping_address ?? '{}') ?: (object) [];
    $billing = json_decode($order->billing_address ?? '{}') ?: $shipping;
    $user = $order->user ?? null;
    $invoiceType = $invoiceType ?? \App\Support\InvoiceType::forUser($user);
    $isInternationalCustomer = $invoiceType === \App\Support\InvoiceType::INTERNATIONAL;
    $userDetails = $user?->user_details;

    $storedDocumentType = trim((string) ($order->document_type ?? ''));
    $exportDocumentTypes = ['commercial_invoice', 'international_invoice'];
    if ($storedDocumentType !== '' && in_array($storedDocumentType, $exportDocumentTypes, true)) {
        $useExportIds = true;
    } elseif ($storedDocumentType !== '') {
        $useExportIds = false;
    } else {
        $useExportIds = $isInternationalCustomer;
    }

    $documentTitles = \App\Support\DocumentType::TYPES;
    $customDocumentTitle = trim((string) ($order->document_type_custom ?? ''));
    $documentHasTax = (float) ($order->orderDetails?->sum('tax') ?? 0) > 0;
    if ($customDocumentTitle !== '') {
        $documentTitle = $customDocumentTitle;
    } elseif ($storedDocumentType !== '' && isset($documentTitles[$storedDocumentType])) {
        $documentTitle = $documentTitles[$storedDocumentType];
    } elseif ($useExportIds) {
        $documentTitle = 'Commercial Invoice';
    } else {
        $documentTitle = $documentHasTax ? 'GST Invoice' : 'Bill Of Supply';
    }

    $numberLabels = [
        'sales_invoice' => 'Sales Invoice No',
        'bill_of_supply' => 'Bill Of Supply No',
        'e_invoice' => 'E-Invoice No.',
        'commercial_invoice' => 'Commercial Invoice No.',
        'international_invoice' => 'International Invoice No.',
        'order_entry' => 'Order No.',
        'performa_invoice' => 'Performa Invoice No',
        'quotation' => 'Quotation No.',
        'inquiry' => 'Inquiry No.',
        'counter_offer' => 'Counter Offer No.',
        'purchase_order' => 'Purchase Order No.',
        'purchase_invoice' => 'Purchase No.',
        'purchase_return' => 'Purchase Return No.',
        'sales_return' => 'Sales Return No',
    ];
    if ($storedDocumentType !== '' && isset($numberLabels[$storedDocumentType])) {
        $invoiceNumberLabel = $numberLabels[$storedDocumentType];
    } elseif ($customDocumentTitle !== '') {
        $invoiceNumberLabel = $customDocumentTitle . ' No.';
    } elseif ($useExportIds) {
        $invoiceNumberLabel = 'Commercial Invoice No.';
    } else {
        $invoiceNumberLabel = $documentHasTax ? 'Sales Invoice No' : 'Bill Of Supply No';
    }
    $invoiceDateLabel = \App\Support\DocumentType::dateHeading($storedDocumentType, $customDocumentTitle);
    $invoiceTimeLabel = \App\Support\DocumentType::timeHeading($storedDocumentType, $customDocumentTitle);

    $customerName = $billing->name ?? $user?->name ?? translate('Customer');
    $customerEmail = $billing->email ?? $user?->email ?? $userDetails?->prim_email_business;
    $customerPhone = $billing->phone ?? $user?->phone ?? $userDetails?->prim_mobile_no_business;
    $customerGst = $userDetails?->gst_no ?: ($user?->gst_no ?? ($billing->gst_no ?? null));
    $customerIec = $userDetails?->iec_no ?: $user?->iec_no;
    $customerAadhaar = $userDetails?->aadhaar_no ?: $user?->aadhaar_no;
    $customerPan = $userDetails?->pan_no ?: ($user?->pan_no ?? ($billing->pan_no ?? null));
    $customerPassport = $userDetails?->passport_no ?: $user?->passport_no;
    $companyName = $userDetails?->company_name ?: $customerName;
    $accountNo = $userDetails?->account_no_business;
    $contactPerson = $billing->contact_person ?? $userDetails?->con_person_name;
    $buyerMobile = $userDetails?->prim_mobile_no_business;
    $buyerWhatsapp = $userDetails?->prim_whats_app_no_business;

    $billingAddress = implode(', ', array_filter([
        $billing->address ?? null,
        $billing->city ?? null,
        $billing->state ?? null,
        $billing->postal_code ?? null,
        $billing->country ?? null,
    ]));
    $shippingAddress = implode(', ', array_filter([
        $shipping->address ?? null,
        $shipping->city ?? null,
        $shipping->state ?? null,
        $shipping->postal_code ?? null,
        $shipping->country ?? null,
    ]));
    $shippingName = $shipping->name ?? $companyName;
    $shippingPhone = $shipping->phone ?? null;
    $shippingEmail = $shipping->email ?? null;
    $shippingContact = $shipping->contact_person ?? null;

    $stateBusiness = optional($userDetails?->businessState)->name
        ?: ($billing->state ?? optional($userDetails?->personalState)->name);
    $isMaharashtra = $stateBusiness && strcasecmp(trim((string) $stateBusiness), 'maharashtra') === 0;

    $dl1 = $userDetails?->d_l_no_1 ?: $userDetails?->dl1;
    $dl2 = $userDetails?->d_l_no_2 ?: $userDetails?->dl2;
    $dl3 = $userDetails?->d_l_no_3;
    $dlExpiry = $userDetails?->dl_expiry;
    $dlExpiryText = $dlExpiry ? format_dd_mm_yy($dlExpiry) : null;
    if ($dlExpiryText === '-') {
        $dlExpiryText = null;
    }
    $otherRegistration = implode(', ', array_filter([
        $userDetails?->other_reg_no ?? null,
        $userDetails?->doctor_hospital_reg_no ?? null,
        $userDetails?->dairy_trust_ngo_reg_no ?? null,
        $userDetails?->cc_mdl_reg_no ?? null,
    ]));
    $customerTypeStatus = implode(' / ', array_filter([
        $userDetails?->customer_type,
        $userDetails?->current_status,
    ]));
    $recordFileNo = $userDetails?->record_file_no;

    $invoiceNo = $order->code ?? $order->id;
    $invoiceDateObj = null;
    if (!empty($order->order_date)) {
        try {
            $invoiceDateObj = \Carbon\Carbon::parse($order->order_date);
        } catch (\Throwable $exception) {
            $invoiceDateObj = null;
        }
    }
    if (!$invoiceDateObj && !empty($order->date)) {
        $invoiceDateObj = \Carbon\Carbon::createFromTimestamp($order->date);
    } elseif (!$invoiceDateObj && !empty($order->created_at)) {
        $invoiceDateObj = $order->created_at->copy();
    }
    $invoiceDate = $invoiceDateObj ? $invoiceDateObj->format('d-m-Y') : '—';
    $invoiceDay = $invoiceDateObj ? $invoiceDateObj->format('l') : null;
    $invoiceTime = trim((string) ($order->order_time ?? ''));
    if ($invoiceTime === '' && !empty($order->date)) {
        $invoiceTime = \Carbon\Carbon::createFromTimestamp($order->date)->format('H:i:s');
    }
    if ($invoiceTime === '') {
        $invoiceTime = '—';
    }
    $creditDays = (int) ($user?->credit_days ?? 0);
    $dueDate = '—';
    if ($invoiceDateObj && $creditDays > 0) {
        $dueDate = $invoiceDateObj->copy()->addDays($creditDays)->format('d-m-Y');
    }

    $bookedName = optional($order->bookedTo)->name ?: ($userDetails?->booked_to ?: null);
    $transport = optional($order->transport)->name
        ?? optional($order->localDeliveryPartner)->name
        ?? ($order->shipping_by ?: ($userDetails?->transport ?? null));
    $salesExecutiveName = optional($order->salesExecutive)->name;
    $salesExecutiveCode = $order->sales_man_code ?: $userDetails?->salesman;
    $paymentMethod = \App\Support\InvoiceType::paymentTermLabel($order->payment_type, $invoiceType)
        ?: ($order->payment_type ? ucwords(str_replace('_', ' ', (string) $order->payment_type)) : null);
    if (is_string($paymentMethod) && str_contains($paymentMethod, ' / ')) {
        $paymentMethod = trim(strstr($paymentMethod, ' / ', true));
    }
    $paymentStatus = $order->payment_status ? ucwords(str_replace('_', ' ', (string) $order->payment_status)) : null;
    $deliveryType = \App\Support\InvoiceType::deliveryTermLabel($order->transport_delivery_type, $invoiceType);
    $transportMode = $order->transport_mode
        ? ucfirst((string) $order->transport_mode) . ($order->transport_surface_mode ? ' / ' . ucfirst((string) $order->transport_surface_mode) : '')
        : null;
    $loadingLocation = ($order->loading_location_type ?? null) === 'sea'
        ? optional($order->loadingSeaPort)->name
        : optional($order->loadingAirport)->name;
    $dischargeLocation = ($order->discharge_location_type ?? null) === 'sea'
        ? optional($order->dischargeSeaPort)->name
        : optional($order->dischargeAirport)->name;
    $bookedDisplay = $useExportIds
        ? implode(' / ', array_filter([$bookedName, $dischargeLocation]))
        : ($bookedName ?: null);
    $carrierTaxNumber = $order->carrier_tax_number ?: optional($order->bookedTo)->branch_gst_number;
    $billingBy = optional($order->billingByStaff)->name;
    $countryOfOrigin = $order->orderDetails
        ? $order->orderDetails->map(fn ($detail) => optional($detail->product)->product_origin)->filter()->unique()->implode(', ')
        : '';
    $privateMark = \Illuminate\Support\Facades\Schema::hasColumn($order->getTable(), 'pm_accountant_name')
        ? ($order->pm_accountant_name ?: null)
        : null;

    $freightLabels = [
        'pre_paid' => 'Prepaid',
        'to_pay' => 'To Pay',
        'fod' => 'FOD',
    ];
    $freightLabel = $freightLabels[$order->freight_type ?? ''] ?? null;
    if ($freightLabel === null && $order->freight_paid) {
        $freightLabel = 'Prepaid';
    }
    if (($order->consignee_copy_status ?? null) === 'attached' || !empty($order->cc_attached_path)) {
        $consigneeCopy = translate('Attached');
    } elseif (($order->consignee_copy_status ?? null) === 'not_attached') {
        $consigneeCopy = translate('Not Attached');
    } else {
        $consigneeCopy = '—';
    }

    $poText = trim((string) ($order->po_number ?? ''));
    if ($poText !== '' && !empty($order->po_date)) {
        try {
            $poText .= ' / ' . \Carbon\Carbon::parse($order->po_date)->format('d-m-Y');
        } catch (\Throwable $exception) {
        }
    }
    $lrText = trim((string) ($order->lr_number ?? ''));
    if ($lrText !== '' && !empty($order->lr_date)) {
        try {
            $lrText .= ' / ' . \Carbon\Carbon::parse($order->lr_date)->format('d-m-Y');
        } catch (\Throwable $exception) {
        }
    }

    $buyerHeading = $useExportIds ? translate('Buyer (Importer)') : translate('Billing Address / Buyer');
    $consigneeHeading = $useExportIds ? translate('Consignee') : translate('Shipping Address / Consignee');
    $registrationTaxLabel = $useExportIds ? translate('IEC No.') : translate('GST No.');
    $registrationTaxValue = $useExportIds ? $customerIec : $customerGst;
    $identityLabel = $useExportIds ? translate('Passport No.') : translate('Aadhaar No.');
    $identityValue = $useExportIds ? $customerPassport : $customerAadhaar;
    $panLabel = $useExportIds ? translate('Tax No.') : translate('PAN No.');
    $carrierTaxLabel = $useExportIds ? translate('Carrier Tax No.') : translate('Carrier GST No.');
    $bankCodeLabel = $useExportIds ? translate('MICR') : translate('IFSC');

    $companyTaxLine = null;
    if ($useExportIds) {
        $companyIec = get_setting('company_iec');
        if ($companyIec) {
            $companyTaxLine = translate('IEC') . ': ' . $companyIec;
        }
    } elseif (get_setting('company_gst')) {
        $companyTaxLine = translate('GST') . ': ' . get_setting('company_gst');
    }
    $companyPanValue = $useExportIds
        ? (get_setting('company_tax_no') ?: get_setting('company_pan'))
        : get_setting('company_pan');
    $companyPanLine = $companyPanValue
        ? (($useExportIds ? translate('Tax No.') : translate('PAN No.')) . ': ' . $companyPanValue)
        : null;
    $companyMsme = get_setting('company_msme') ?: get_setting('udyam_number');
    $companyFssai = get_setting('company_fssai') ?: get_setting('fssai_licence');
    $bankCode = $useExportIds
        ? (get_setting('company_bank_micr') ?: get_setting('company_bank_ifsc'))
        : get_setting('company_bank_ifsc');
    $bankLine = null;
    if (get_setting('company_bank') || get_setting('company_bank_account') || $bankCode) {
        $bankBits = array_filter([
            get_setting('company_bank') ? translate('Bank') . ': ' . get_setting('company_bank') : null,
            get_setting('company_bank_account') ? translate('A/C No.') . ' ' . get_setting('company_bank_account') : null,
            $bankCode ? $bankCodeLabel . ': ' . $bankCode : null,
        ]);
        $bankLine = implode(', ', $bankBits);
    }
    $bankCodeTwo = $useExportIds
        ? (get_setting('company_bank_micr_2') ?: get_setting('company_bank_ifsc_2'))
        : get_setting('company_bank_ifsc_2');
    $bankLineTwo = null;
    if (get_setting('company_bank_2') || get_setting('company_bank_account_2') || $bankCodeTwo) {
        $bankBits = array_filter([
            get_setting('company_bank_2') ? translate('Bank') . ': ' . get_setting('company_bank_2') : null,
            get_setting('company_bank_account_2') ? translate('A/C No.') . ' ' . get_setting('company_bank_account_2') : null,
            $bankCodeTwo ? $bankCodeLabel . ': ' . $bankCodeTwo : null,
        ]);
        $bankLineTwo = implode(', ', $bankBits);
    }

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
        $decimals = price_decimal_places($invoiceCurrencyCode, $invoiceCurrencySymbol);
        if (get_setting('decimal_separator') == 1) {
            $formattedPrice = number_format($price, $decimals);
        } else {
            $formattedPrice = number_format($price, $decimals, ',', '.');
        }
        return $formattedPrice;
    };

    $shippingTotal = (float) ($order->orderDetails?->sum('shipping_cost') ?? 0);
    $moneyDecimals = price_decimal_places($invoiceCurrencyCode, $invoiceCurrencySymbol);
    $shippingInvoiceLine = shipping_invoice_line(
        $order->orderDetails,
        $shippingTotal,
        $transport,
        translate('Shipping / Freight'),
        $moneyDecimals
    );

    $paidQtyTotal = (int) ($order->orderDetails?->sum(function ($row) {
        return (bool) ($row->is_scheme ?? false) ? 0 : ($row->quantity ?? 0);
    }) ?? 0);
    $schemeQtyTotal = (int) ($order->orderDetails?->sum(function ($row) {
        return (bool) ($row->is_scheme ?? false) ? ($row->quantity ?? 0) : 0;
    }) ?? 0);
    $pointsEarned = (float) ($order->orderDetails?->sum(function ($row) {
        return (bool) ($row->is_scheme ?? false)
            ? 0
            : ((float) ($row->earn_point ?? 0) * (int) ($row->quantity ?? 0));
    }) ?? 0);

    $paidDetails = ($order->orderDetails ?? collect())
        ->filter(fn ($detail) => !(bool) ($detail->is_scheme ?? false))
        ->values();
    $schemeDetails = ($order->orderDetails ?? collect())
        ->filter(fn ($detail) => (bool) ($detail->is_scheme ?? false))
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
            ->filter(fn ($row) => !in_array((int) ($row->id ?? 0), $mergedSchemeDetailIds, true));
        $sameBatchSchemeQty = (int) $sameBatchSchemeRows->sum('quantity');
        foreach ($sameBatchSchemeRows as $row) {
            $mergedSchemeDetailIds[] = (int) ($row->id ?? 0);
        }
        $invoiceLines->push([
            'detail' => $detail,
            'scheme_qty' => $sameBatchSchemeQty,
            'paid_qty' => (int) ($detail->quantity ?? 0),
            'total_qty' => (int) ($detail->quantity ?? 0) + $sameBatchSchemeQty,
        ]);
    }
    foreach ($schemeDetails as $schemeDetail) {
        if (in_array((int) ($schemeDetail->id ?? 0), $mergedSchemeDetailIds, true)) {
            continue;
        }
        $invoiceLines->push([
            'detail' => $schemeDetail,
            'scheme_qty' => (int) ($schemeDetail->quantity ?? 0),
            'paid_qty' => 0,
            'total_qty' => (int) ($schemeDetail->quantity ?? 0),
        ]);
    }

    $splitDiscount = function ($detail, $rate, $gap) {
        $batch = $detail->batch;
        $product = $detail->product;
        $bdPercent = 0.0;
        $pdPercent = 0.0;
        $bdUnit = 0.0;
        $pdUnit = 0.0;

        if ($batch && (float) ($batch->batch_discount_percent ?? 0) > 0) {
            $bdPercent = min(100, max(0, (float) $batch->batch_discount_percent));
        } elseif ($batch && ($batch->discount_type ?? null) === 'percent' && (float) ($batch->discount ?? 0) > 0) {
            $bdPercent = min(100, max(0, (float) $batch->discount));
        } elseif ($rate > 0 && $batch && ($batch->discount_type ?? null) === 'amount' && (float) ($batch->discount ?? 0) > 0) {
            $bdUnit = max(0, (float) $batch->discount);
            $bdPercent = ($bdUnit / $rate) * 100;
        }

        if ($batch && (float) ($batch->product_discount_percent ?? 0) > 0) {
            $pdPercent = min(100, max(0, (float) $batch->product_discount_percent));
        } elseif ($product && ($product->discount_type ?? null) === 'percent' && (float) ($product->discount ?? 0) > 0) {
            $pdPercent = min(100, max(0, (float) $product->discount));
        } elseif ($rate > 0 && $product && ($product->discount_type ?? null) === 'amount' && (float) ($product->discount ?? 0) > 0) {
            $pdUnit = max(0, (float) $product->discount);
            $pdPercent = ($pdUnit / $rate) * 100;
        }

        if ($bdPercent > 0 && $bdUnit <= 0) {
            $bdUnit = $rate * $bdPercent / 100;
        }
        if ($pdPercent > 0 && $pdUnit <= 0) {
            $pdUnit = $rate * $pdPercent / 100;
        }

        if ($bdUnit > 0.0005 && $pdUnit > 0.0005) {
            return [
                'bd_percent' => $bdPercent,
                'pd_percent' => $pdPercent,
                'bd_unit' => $bdUnit,
                'pd_unit' => $pdUnit,
                'stacked' => true,
            ];
        }

        if ($gap <= 0.0005) {
            return ['bd_percent' => 0.0, 'pd_percent' => 0.0, 'bd_unit' => 0.0, 'pd_unit' => 0.0, 'stacked' => false];
        }

        if ($bdUnit > 0.0005) {
            if (abs($bdUnit - $gap) > 0.05) {
                $bdUnit = $gap;
                $bdPercent = $rate > 0 ? ($gap / $rate) * 100 : 0.0;
            }
            return ['bd_percent' => $bdPercent, 'pd_percent' => 0.0, 'bd_unit' => $bdUnit, 'pd_unit' => 0.0, 'stacked' => false];
        }

        $pdUnit = $gap;
        $pdPercent = $rate > 0 ? ($gap / $rate) * 100 : 0.0;

        return ['bd_percent' => 0.0, 'pd_percent' => $pdPercent, 'bd_unit' => 0.0, 'pd_unit' => $pdUnit, 'stacked' => false];
    };

    $renderedLines = [];
    $productSubtotal = 0.0;
    $productwiseDiscount = 0.0;
    $batchwiseDiscount = 0.0;
    $mrpValueTotal = 0.0;
    $schemeDiscountValue = 0.0;
    $productGrossTotal = 0.0;
    $taxableTotal = 0.0;
    $sgstTotal = 0.0;
    $cgstTotal = 0.0;
    $igstTotal = 0.0;
    $taxTotal = 0.0;
    $gstRateKeys = [];

    foreach ($invoiceLines as $invoiceLine) {
        $detail = $invoiceLine['detail'];
        $product = $detail->product;
        $qty = (int) $invoiceLine['paid_qty'];
        $schemeQty = (int) $invoiceLine['scheme_qty'];
        $displayTotalQty = (int) $invoiceLine['total_qty'];
        $saleUnit = $detail->sale_price !== null
            ? (float) $detail->sale_price
            : ($qty > 0 ? (float) ($detail->price ?? 0) / max(1, $qty) : 0);
        $rate = (float) ($detail->before_productandbatch_discount ?? $saleUnit);
        if ((bool) ($detail->is_scheme ?? false) && $qty === 0) {
            $rate = 0.0;
            $saleUnit = 0.0;
        }
        $chargedUnit = $saleUnit;
        $gap = max(0, $rate - $chargedUnit);
        $split = $splitDiscount($detail, $rate, $gap);
        if (!empty($split['stacked'])) {
            $chargedBase = $chargedUnit * max(0, $qty);
            $chargedGstPercent = $chargedBase > 0 ? ((float) ($detail->tax ?? 0) / $chargedBase) * 100 : 0.0;
            $saleUnit = max(0, $rate - (float) $split['bd_unit'] - (float) $split['pd_unit']);
            $gap = (float) $split['bd_unit'] + (float) $split['pd_unit'];
        }
        $productValue = $rate * max(0, $qty);
        $discountAmount = $gap * max(0, $qty);
        $taxableAmount = max(0, $saleUnit * max(0, $qty));
        $lineTax = (float) ($detail->tax ?? 0);
        if (!empty($split['stacked'])) {
            $lineTax = $taxableAmount * ($chargedGstPercent ?? 0) / 100;
        }
        if ($isMaharashtra) {
            $sgst = $lineTax / 2;
            $cgst = $lineTax / 2;
            $igst = 0.0;
        } else {
            $sgst = 0.0;
            $cgst = 0.0;
            $igst = $lineTax;
        }
        $sgstPercent = $taxableAmount > 0 ? ($sgst / $taxableAmount) * 100 : 0;
        $cgstPercent = $taxableAmount > 0 ? ($cgst / $taxableAmount) * 100 : 0;
        $igstPercent = $taxableAmount > 0 ? ($igst / $taxableAmount) * 100 : 0;
        $gstPercent = $sgstPercent + $cgstPercent + $igstPercent;
        if ($gstPercent > 0) {
            $gstRateKeys[number_format($gstPercent, 2, '.', '')] = true;
        }
        $matchingStock = $product?->stocks?->where('variant', $detail->variation)->first() ?? $product?->stocks?->first();
        $detailBatch = $detail->batch;
        $mrp = (float) ($detail->mrp_price ?? optional($detailBatch)->mrp_price ?? optional($matchingStock)->mrp_price ?? $product?->unit_price ?? 0);
        $mrpValue = $mrp * max(0, $displayTotalQty);
        $gross = $taxableAmount + $lineTax;
        $productName = $product ? $product->getTranslation('name') : translate('Product Removed');
        $brandName = null;
        if ($product && $product->brand) {
            $brandName = $product->brand->getTranslation('name') ?: $product->brand->name;
        }
        $description = $brandName && strcasecmp((string) $brandName, (string) $productName) !== 0
            ? $productName . ' (' . $brandName . ')'
            : $productName;
        $category = $product?->main_category ? $product->main_category->getTranslation('name') : null;
        $mfg = $monthYear(optional($detailBatch)->manufacturing_date);
        $expiry = $monthYear(optional($detailBatch)->product_exp_date ?? optional($matchingStock)->product_exp_date ?? $product?->product_exp_date);

        $productSubtotal += $productValue;
        $productwiseDiscount += $split['pd_unit'] * max(0, $qty);
        $batchwiseDiscount += $split['bd_unit'] * max(0, $qty);
        $mrpValueTotal += $mrpValue;
        $schemeDiscountValue += $mrp * max(0, $schemeQty);
        $productGrossTotal += $gross;
        $taxableTotal += $taxableAmount;
        $sgstTotal += $sgst;
        $cgstTotal += $cgst;
        $igstTotal += $igst;
        $taxTotal += $lineTax;

        $renderedLines[] = [
            'description' => $description,
            'variant' => $detail->variation ?: null,
            'batch' => optional($detailBatch)->batch,
            'mfg' => $mfg,
            'expiry' => $expiry,
            'category' => $category,
            'hsn' => $product?->product_hsn,
            'total_qty' => $displayTotalQty,
            'qty' => $qty,
            'scheme_qty' => $schemeQty,
            'rate' => $rate,
            'mrp' => $mrp,
            'product_value' => $productValue,
            'mrp_value' => $mrpValue,
            'bd_unit' => $split['bd_unit'],
            'pd_unit' => $split['pd_unit'],
            'bd_percent' => $split['bd_percent'],
            'pd_percent' => $split['pd_percent'],
            'discounted_rate' => $saleUnit,
            'discount_amount' => $discountAmount,
            'taxable' => $taxableAmount,
            'sgst' => $sgst,
            'cgst' => $cgst,
            'igst' => $igst,
            'gst' => $lineTax,
            'gross' => $gross,
            'sgst_percent' => $sgstPercent,
            'cgst_percent' => $cgstPercent,
            'igst_percent' => $igstPercent,
            'gst_percent' => $gstPercent,
            'scheme_only' => $qty === 0 && $schemeQty > 0,
        ];
    }

    $shippingBase = (float) ($shippingInvoiceLine['base_amount'] ?? 0);
    $shippingGst = (float) ($shippingInvoiceLine['gst_amount'] ?? 0);
    $shippingGstPercent = (float) ($shippingInvoiceLine['gst_percent'] ?? 0);
    $shippingGross = (float) ($shippingInvoiceLine['total_amount'] ?? 0);
    if ($isMaharashtra) {
        $shippingSgst = $shippingGst / 2;
        $shippingCgst = $shippingGst / 2;
        $shippingIgst = 0.0;
        $shippingSgstPercent = $shippingGstPercent / 2;
        $shippingCgstPercent = $shippingGstPercent / 2;
        $shippingIgstPercent = 0.0;
    } else {
        $shippingSgst = 0.0;
        $shippingCgst = 0.0;
        $shippingIgst = $shippingGst;
        $shippingSgstPercent = 0.0;
        $shippingCgstPercent = 0.0;
        $shippingIgstPercent = $shippingGstPercent;
    }
    if ($shippingGstPercent > 0) {
        $gstRateKeys[number_format($shippingGstPercent, 2, '.', '')] = true;
    }
    $sgstTotal += $shippingSgst;
    $cgstTotal += $shippingCgst;
    $igstTotal += $shippingIgst;
    $taxTotal += $shippingGst;
    $taxableTotal += $shippingBase;

    $singleGstRate = count($gstRateKeys) === 1 ? (float) array_key_first($gstRateKeys) : null;
    if ($singleGstRate === null && $taxTotal <= 0) {
        $singleGstRate = 0.0;
    }
    $sgstRateLabel = null;
    $cgstRateLabel = null;
    $igstRateLabel = null;
    $gstRateLabel = $singleGstRate;
    if ($singleGstRate !== null) {
        if ($igstTotal > 0 && $sgstTotal <= 0 && $cgstTotal <= 0) {
            $sgstRateLabel = 0.0;
            $cgstRateLabel = 0.0;
            $igstRateLabel = $singleGstRate;
        } else {
            $sgstRateLabel = $singleGstRate / 2;
            $cgstRateLabel = $singleGstRate / 2;
            $igstRateLabel = 0.0;
        }
    }
    $rateTag = function ($rate) use ($formatRate) {
        return $rate === null ? '' : ' : (' . $formatRate($rate) . ')';
    };

    $youSave = max(0, $mrpValueTotal - $productGrossTotal);
    $averageDiscount = $mrpValueTotal > 0 ? ($youSave / $mrpValueTotal) * 100 : 0;
    $schemePercent = $paidQtyTotal > 0 ? ($schemeQtyTotal / $paidQtyTotal) * 100 : 0;
    $orderDiscount = (float) ($order->coupon_discount ?? 0);
    $hasCouponCode = trim((string) ($order->coupon_code ?? '')) !== '';
    $couponDiscountTotal = $hasCouponCode ? $orderDiscount : 0.0;
    $additionalDiscountTotal = $hasCouponCode ? 0.0 : $orderDiscount;
    $orderAmount = function ($column) use ($order) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn($order->getTable(), $column)) {
            return 0.0;
        }
        return (float) ($order->{$column} ?? 0);
    };
    $packForwarding = $orderAmount('pack_forwarding_amount');
    $insuranceCharges = $orderAmount('insurance_amount');
    $otherCharges = $orderAmount('other_charges_amount');
    $amountwiseDiscount = $orderAmount('amountwise_discount');
    $crDbAdjustment = $orderAmount('cr_db_adjustment');
    $printedTaxable = $taxableTotal + $packForwarding + $insuranceCharges + $otherCharges - $amountwiseDiscount - $couponDiscountTotal - $additionalDiscountTotal;
    $grandTotal = $printedTaxable + $taxTotal + $crDbAdjustment;

    $netWeightKg = 0.0;
    $volumeCbm = 0.0;
    $hasPieceWeight = false;
    $hasPieceVolume = false;
    foreach ($order->orderDetails ?? [] as $detail) {
        $product = $detail->product;
        $qty = (int) ($detail->quantity ?? 0);
        if (!$product || $qty < 1) {
            continue;
        }
        $stock = $product->stocks?->where('variant', $detail->variation)->first() ?? $product->stocks?->first();
        $grams = (float) ($stock->weight ?? $product->weight ?? 0);
        if ($grams > 0) {
            $netWeightKg += ($grams * $qty) / 1000;
            $hasPieceWeight = true;
        }
        $length = (float) ($stock->length ?? $product->length ?? 0);
        $width = (float) ($stock->width ?? $product->width ?? 0);
        $height = (float) ($stock->height ?? $product->height ?? 0);
        if ($length > 0 && $width > 0 && $height > 0) {
            $volumeCbm += ($length * $width * $height * $qty) / 1000000;
            $hasPieceVolume = true;
        }
    }
    if (!$hasPieceWeight) {
        $netWeightKg = $order->net_weight_kg;
    }
    if (!$hasPieceVolume) {
        $volumeCbm = $order->total_volume_cbm;
    }
    if (($volumeCbm === null || $volumeCbm === '') && (float) ($order->length_cm ?? 0) > 0 && (float) ($order->width_cm ?? 0) > 0 && (float) ($order->height_cm ?? 0) > 0) {
        $volumeCbm = ((float) $order->length_cm * (float) $order->width_cm * (float) $order->height_cm) / 1000000;
    }

    $ewayNumber = null;
    $ewayDate = null;
    if (\Illuminate\Support\Facades\Schema::hasColumn($order->getTable(), 'eway_bill_number')) {
        $ewayNumber = trim((string) ($order->eway_bill_number ?? ''));
        $ewayDate = $order->eway_bill_date ?? null;
    }
    $ewayText = $ewayNumber !== null && $ewayNumber !== '' ? $ewayNumber : '';
    if ($ewayText !== '' && !empty($ewayDate)) {
        try {
            $ewayText .= ' / ' . \Carbon\Carbon::parse($ewayDate)->format('d-m-Y');
        } catch (\Throwable $exception) {
        }
    }

    $outstanding = null;
    if ($user) {
        $openingBalance = (float) ($userDetails?->opening_balance ?? 0);
        $priorUnpaid = (float) \App\Models\Order::query()
            ->where('user_id', $user->id)
            ->where('id', '!=', $order->id)
            ->where(function ($query) {
                $query->whereNull('payment_status')->orWhereNotIn('payment_status', ['paid', 'Paid']);
            })
            ->where(function ($query) {
                $query->whereNull('delivery_status')->orWhere('delivery_status', '!=', 'cancelled');
            })
            ->sum('grand_total');
        $outstanding = $openingBalance + $priorUnpaid;
    }

    $logoId = ($issuingCompany && $issuingCompany->logo) ? $issuingCompany->logo : get_setting('header_logo');
    $logoUrl = $logoId ? uploaded_asset($logoId) : null;
    if (!$logoUrl && file_exists(public_path('assets/img/logo.png'))) {
        $logoUrl = static_asset('assets/img/logo.png');
    }
    $stampId = ($issuingCompany && $issuingCompany->stamp) ? $issuingCompany->stamp : get_setting('invoice_stamp');
    $stampUrl = $stampId ? uploaded_asset($stampId) : null;
    $signId = ($issuingCompany && $issuingCompany->sign) ? $issuingCompany->sign : null;
    $signUrl = $signId ? uploaded_asset($signId) : null;
    $signatoryName = trim((string) (
        ($issuingCompany?->contact_person ?? '')
        ?: (get_setting('authorised_signatory') ?: '')
        ?: ($billingBy ?: '')
    ));
    $upiName = trim((string) (get_setting('company_upi_name') ?: ''));
    $upiId = trim((string) (get_setting('company_upi_id') ?: ''));
    $upiLine = '';
    if ($upiId !== '' || $upiName !== '') {
        $upiLine = translate('For UPI Payment') . ' : ' . translate('All Type Available');
        if ($upiName !== '') {
            $upiLine .= ', ' . translate('Name Display') . ': ' . $upiName;
        }
    }
    $qrMarkup = function ($text) {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }
        try {
            $svg = (string) \QrCode::format('svg')->size(70)->margin(0)->generate($text);
            return str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $svg);
        } catch (\Throwable $exception) {
            return '';
        }
    };
    $invoiceQr = $qrMarkup($invoiceNo);
    $upiQr = '';
    if ($upiId !== '') {
        $upiQr = $qrMarkup('upi://pay?pa=' . rawurlencode($upiId) . '&pn=' . rawurlencode($upiName) . '&cu=INR');
    } elseif ($upiName !== '') {
        $upiQr = $qrMarkup($upiName);
    }

    $licenceOneLabel = $useExportIds ? translate('Pharmacy Lic. No. 1 & 2') : translate('Drug Lic. No. 1 & 2');
    $licenceThreeLabel = $useExportIds ? translate('Pharmacy Lic. No. 3 & Expiry') : translate('Drug Lic. No. 3 & Expiry');
    $bookedLabel = $useExportIds
        ? translate('Booked To / Location / Port Of Discharge')
        : translate('Booked To / Location');

    $buyerContacts = array_filter([
        $customerPhone ? translate('Phone') . ': ' . $customerPhone : null,
        $contactPerson ? translate('Contact Person') . ': ' . $contactPerson : null,
        $buyerMobile && $buyerMobile !== $customerPhone ? translate('Mobile') . ': ' . $buyerMobile : null,
        $buyerWhatsapp ? translate('WhatsApp') . ': ' . $buyerWhatsapp : null,
        $customerEmail ? translate('E-mail') . ': ' . $customerEmail : null,
    ]);
    $consigneeContacts = array_filter([
        $shippingPhone ? translate('Phone') . ': ' . $shippingPhone : null,
        $shippingContact ? translate('Contact Person') . ': ' . $shippingContact : null,
        $shippingEmail ? translate('E-mail') . ': ' . $shippingEmail : null,
    ]);
    $namedBuyer = trim((string) ($billing->name ?? ''));
    if ($namedBuyer === '') {
        $namedBuyer = trim((string) ($user?->name ?? ''));
    }
    $orderByName = $namedBuyer !== '' ? $namedBuyer : $salesExecutiveName;
    $orderBy = implode(' / ', array_filter([trim((string) ($order->challan_number ?? '')), $orderByName]));
    $salesExecutiveText = implode(' / ', array_filter([$salesExecutiveName, $salesExecutiveCode]));
    $licencePair = implode(' / ', array_filter([$dl1, $dl2]));
    $licenceThree = implode(' / ', array_filter([
        $dl3,
        $dlExpiryText ? translate('Expiry') . ' ' . $dlExpiryText : null,
    ]));

    $intToIndianWords = function (int $num) use (&$intToIndianWords): string {
        if ($num === 0) {
            return 'Zero';
        }
        $ones = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
            14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
            18 => 'Eighteen', 19 => 'Nineteen',
        ];
        $tens = [
            0 => '', 2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
        ];
        if ($num < 20) {
            return $ones[$num];
        }
        if ($num < 100) {
            return trim($tens[(int) floor($num / 10)] . ' ' . $ones[$num % 10]);
        }
        if ($num < 1000) {
            return trim($ones[(int) floor($num / 100)] . ' Hundred ' . ($num % 100 ? $intToIndianWords($num % 100) : ''));
        }
        if ($num < 100000) {
            return trim($intToIndianWords((int) floor($num / 1000)) . ' Thousand ' . ($num % 1000 ? $intToIndianWords($num % 1000) : ''));
        }
        if ($num < 10000000) {
            return trim($intToIndianWords((int) floor($num / 100000)) . ' Lakh ' . ($num % 100000 ? $intToIndianWords($num % 100000) : ''));
        }

        return trim($intToIndianWords((int) floor($num / 10000000)) . ' Crore ' . ($num % 10000000 ? $intToIndianWords($num % 10000000) : ''));
    };

    $roundedGrandTotal = round(max(0, (float) $grandTotal), 2);
    $wholeAmount = (int) floor($roundedGrandTotal);
    $fractionalAmount = (int) round(($roundedGrandTotal - $wholeAmount) * 100);
    if ($fractionalAmount >= 100) {
        $wholeAmount += 1;
        $fractionalAmount = 0;
    }
    $currencyWordLabel = strtoupper((string) $invoiceCurrencyCode) === 'INR'
        ? 'Rupees'
        : strtoupper((string) ($invoiceCurrencyCode ?: 'INR'));
    $subCurrencyWordLabel = strtoupper((string) $invoiceCurrencyCode) === 'INR'
        ? 'Paise'
        : 'Cents';
    $grandTotalInWords = $currencyWordLabel . ' ' . $intToIndianWords($wholeAmount);
    if ($fractionalAmount > 0) {
        $grandTotalInWords .= ' and ' . $intToIndianWords($fractionalAmount) . ' ' . $subCurrencyWordLabel;
    }
    $grandTotalInWords .= ' Only';
@endphp
<div class="invoice-wrap">
    <div class="doc-title">{{ translate($documentTitle) }}</div>
    @if(!$useExportIds)
        <div class="text-center small" style="margin-bottom: 3px;">
            {{ translate('Reverse Charges') }}:
            {{ $order->reverse_charge === null ? translate('None') : ($order->reverse_charge ? translate('Yes') : translate('No')) }}
        </div>
    @endif

    <table class="meta">
        <tr>
            <td width="28%" rowspan="2">
                @if($logoUrl)
                    <div class="text-center"><img class="logo" src="{{ $logoUrl }}" alt="{{ $siteName }}"></div>
                @endif
                <div class="co-name">{{ strtoupper($siteName) }}</div>
                @if($contactAddress)<div>{{ $contactAddress }}</div>@endif
                @if($contactPhone)<div>{{ translate('Mobile') }}: {{ $contactPhone }}</div>@endif
                @if($contactWhatsapp)<div>{{ translate('WhatsApp') }}: {{ $contactWhatsapp }}</div>@endif
                @if($contactAccountPhone)<div>{{ translate('Account') }}: {{ $contactAccountPhone }}</div>@endif
                @if($contactSalesPhone)<div>{{ translate('Sales') }}: {{ $contactSalesPhone }}</div>@endif
                @if($contactEmail || $contactWebsite)
                    <div>
                        @if($contactEmail){{ translate('E-mail') }}: {{ $contactEmail }}@endif
                        @if($contactEmail && $contactWebsite) , @endif
                        @if($contactWebsite){{ translate('Website') }}: {{ $contactWebsite }}@endif
                    </div>
                @endif
                @if($companyTaxLine || $companyPanLine || $companyMsme)
                    <div>
                        {{ implode(', ', array_filter([$companyTaxLine, $companyPanLine, $companyMsme ? translate('MSMED Act') . ' ' . $companyMsme : null])) }}
                    </div>
                @endif
                @if($drugLicenceNumbers)
                    <div>{{ translate('Drug Licence Nos') }}: {{ implode(', ', $drugLicenceNumbers) }}</div>
                @endif
                @if($bankLine)<div>{{ $bankLine }}</div>@endif
                @if($bankLineTwo)<div>{{ $bankLineTwo }}</div>@endif
                @if($companyFssai)<div>{{ translate('FSSAI Lic. No.') }}: {{ $companyFssai }}</div>@endif
                @if($upiLine)<div>{{ $upiLine }}</div>@endif
            </td>
            <td width="27%">
                <div class="label">{{ $buyerHeading }}</div>
                <div class="party-name">{{ $dash($companyName) }}</div>
                @if($accountNo)<div>{{ translate('Acc. No.') }}: {{ $accountNo }}</div>@endif
                <div>{{ $dash($billingAddress) }}</div>
                @if($buyerContacts)<div class="small">{{ implode(' | ', $buyerContacts) }}</div>@endif
            </td>
            <td width="22%" rowspan="2">
                <table class="inner">
                    <tr><td class="label">{{ $registrationTaxLabel }}</td><td>{{ $dash($registrationTaxValue) }}</td></tr>
                    <tr><td class="label">{{ $identityLabel }}</td><td>{{ $dash($identityValue) }}</td></tr>
                    <tr><td class="label">{{ $panLabel }}</td><td>{{ $dash($customerPan) }}</td></tr>
                    <tr><td class="label">{{ $licenceOneLabel }}</td><td>{{ $dash($licencePair) }}</td></tr>
                    <tr><td class="label">{{ $licenceThreeLabel }}</td><td>{{ $dash($licenceThree) }}</td></tr>
                    <tr><td class="label">{{ translate('Other Registration') }}</td><td>{{ $dash($otherRegistration) }}</td></tr>
                    <tr><td class="label">{{ translate('Customer Type / Status') }}</td><td>{{ $dash($customerTypeStatus) }}</td></tr>
                    <tr><td class="label">{{ translate('Current Outstanding') }}</td><td>{{ $outstanding === null ? '—' : $invoicePrice($outstanding) }}</td></tr>
                    <tr><td class="label">{{ translate('Record File No.') }}</td><td>{{ $dash($recordFileNo) }}</td></tr>
                </table>
            </td>
            <td width="23%" rowspan="2">
                <table class="inner">
                    <tr><td class="label">{{ translate($invoiceNumberLabel) }}</td><td>{{ $dash($invoiceNo) }}</td></tr>
                    <tr><td class="label">{{ translate('Challan No. / Order By') }}</td><td>{{ $dash($orderBy !== '' ? $orderBy : null) }}</td></tr>
                    <tr><td class="label">{{ translate($invoiceDateLabel) }}</td><td>{{ $invoiceDate }}@if($invoiceDay) ({{ $invoiceDay }})@endif</td></tr>
                    <tr><td class="label">{{ translate($invoiceTimeLabel) }}</td><td>{{ $invoiceTime }}</td></tr>
                    <tr><td class="label">{{ translate('EWB No. & Date') }}</td><td>{{ $dash($ewayText !== '' ? $ewayText : null) }}</td></tr>
                    <tr><td class="label">{{ translate('P.O. No. & Date') }}</td><td>{{ $dash($poText) }}</td></tr>
                    <tr><td class="label">{{ translate('Sales Executive Name & Code') }}</td><td>{{ $dash($salesExecutiveText) }}</td></tr>
                    <tr><td class="label">{{ translate('Payment Terms / Status') }}</td><td>{{ $dash(implode(' / ', array_filter([$paymentMethod ? translate($paymentMethod) : null, $paymentStatus ? translate($paymentStatus) : null]))) }}</td></tr>
                    @if(!$useExportIds)
                        <tr><td class="label">{{ translate('Credit Days') }}</td><td>{{ $creditDays }}</td></tr>
                        <tr><td class="label">{{ translate('Due Date') }}</td><td>{{ $dueDate }}</td></tr>
                    @endif
                    <tr><td class="label">{{ translate('Billing By') }}</td><td>{{ $dash($billingBy) }}</td></tr>
                    <tr><td class="label">{{ translate('Country Of Origin') }}</td><td>{{ $dash($countryOfOrigin) }}</td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">{{ $consigneeHeading }}</div>
                <div class="party-name">{{ $dash($shippingName) }}</div>
                <div>{{ $dash($shippingAddress) }}</div>
                @if($consigneeContacts)<div class="small">{{ implode(' | ', $consigneeContacts) }}</div>@endif
            </td>
        </tr>
    </table>

    <table class="logistics" style="margin-top: 3px;">
        <tr>
            <td width="25%"><span class="label">{{ translate('Mode Of Transport') }}:</span> {{ $dash($transportMode ? translate($transportMode) : null) }}</td>
            <td width="25%"><span class="label">{{ translate('Cases') }}:</span> {{ $dash($order->cases) }}</td>
            <td width="25%"><span class="label">{{ translate('Carrier Pvt. Mark') }}:</span> {{ $dash($privateMark) }}</td>
            <td width="25%"><span class="label">{{ translate('Currency') }}:</span> {{ $dash($invoiceCurrencyCode) }}</td>
        </tr>
        <tr>
            <td><span class="label">{{ translate('Carrier By') }}:</span> {{ $dash($transport) }}</td>
            <td><span class="label">{{ translate('Net Weight') }}:</span> {{ $formatMeasure($netWeightKg, 'KG') }}</td>
            <td><span class="label">{{ translate('Shipping / Freight') }}:</span> {{ $dash($freightLabel ? translate($freightLabel) : null) }}</td>
            <td><span class="label">{{ translate('Terms Of Delivery') }}:</span> {{ $dash($deliveryType ? translate($deliveryType) : null) }}</td>
        </tr>
        <tr>
            <td><span class="label">{{ $useExportIds ? translate('B/L / AWB No. & Date') : translate('LR / GR / Doc / Vehicle No. & Date') }}:</span> {{ $dash($lrText) }}</td>
            <td><span class="label">{{ translate('Gross Weight') }}:</span> {{ $formatMeasure($order->gross_weight_kg, 'KG') }}</td>
            <td><span class="label">{{ translate('Consignee Copy') }}:</span> {{ $consigneeCopy }}</td>
            <td>@if($useExportIds)<span class="label">{{ translate('Sea / Air Port Of Loading') }}:</span> {{ $dash($loadingLocation) }}@endif</td>
        </tr>
        <tr>
            <td><span class="label">{{ $bookedLabel }}:</span> {{ $dash($bookedDisplay) }}</td>
            <td><span class="label">{{ translate('Total Volume / CBM') }}:</span> {{ $formatVolume($volumeCbm) }}</td>
            <td><span class="label">{{ $carrierTaxLabel }}:</span> {{ $dash($carrierTaxNumber) }}</td>
            <td>@if($useExportIds)<span class="label">{{ translate('Final Destination') }}:</span> {{ $dash($order->final_destination) }}@endif</td>
        </tr>
    </table>

    <table class="items" style="margin-top: 4px;">
        <thead>
            <tr>
                <th width="3%">{{ translate('Sr') }}</th>
                <th width="13%">{{ translate('Description') }}</th>
                <th width="8%">{{ translate('Batch / Lot') }}</th>
                <th width="7%">{{ translate('Category') }}</th>
                <th width="8%" colspan="2">{{ translate('Total Qty') }}</th>
                <th width="6%">{{ translate('Rate') }}</th>
                <th width="7%">{{ translate('Product Value') }}</th>
                <th width="5%">{{ translate('BD') }}</th>
                <th width="5%">{{ translate('PD') }}</th>
                <th width="7%">{{ translate('Discounted Rate') }}</th>
                <th width="7%">{{ translate('Taxable') }}</th>
                <th width="5%">{{ translate('SGST') }}</th>
                <th width="5%">{{ translate('CGST') }}</th>
                <th width="5%">{{ translate('IGST') }}</th>
                <th width="5%">{{ translate('GST Value') }}</th>
                <th width="5%">{{ translate('Gross') }}</th>
            </tr>
            <tr>
                <th>{{ translate('No') }}</th>
                <th>{{ translate('Variant') }}</th>
                <th>{{ translate('Mfg / Expiry') }}</th>
                <th>{{ translate('HSN / HS') }}</th>
                <th width="4%">{{ translate('Qty') }}</th>
                <th width="4%">{{ translate('Scheme') }}</th>
                <th>{{ translate('M.R.P') }}</th>
                <th>{{ translate('M.R.P Value') }}</th>
                <th>%</th>
                <th>%</th>
                <th>{{ translate('Discount Amount') }}</th>
                <th>{{ translate('Value') }}</th>
                <th>%</th>
                <th>%</th>
                <th>%</th>
                <th>%</th>
                <th>{{ translate('Value') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($renderedLines as $idx => $line)
                <tr>
                    <td rowspan="2" class="text-center">{{ $idx + 1 }}</td>
                    <td class="product-name">
                        {{ $line['description'] }}
                        @if($line['scheme_only'])<div class="small">{{ translate('Scheme Free') }}</div>@endif
                    </td>
                    <td class="text-center">{{ $dash($line['batch']) }}</td>
                    <td class="text-center">{{ $dash($line['category']) }}</td>
                    <td colspan="2" class="text-center">{{ $line['total_qty'] }}</td>
                    <td class="num">{{ $invoicePrice($line['rate']) }}</td>
                    <td class="num">{{ $invoicePrice($line['product_value']) }}</td>
                    <td class="num">{{ $invoicePrice($line['bd_unit']) }}</td>
                    <td class="num">{{ $invoicePrice($line['pd_unit']) }}</td>
                    <td class="num">{{ $invoicePrice($line['discounted_rate']) }}</td>
                    <td rowspan="2" class="num">{{ $invoicePrice($line['taxable']) }}</td>
                    <td class="num">{{ $invoicePrice($line['sgst']) }}</td>
                    <td class="num">{{ $invoicePrice($line['cgst']) }}</td>
                    <td class="num">{{ $invoicePrice($line['igst']) }}</td>
                    <td class="num">{{ $invoicePrice($line['gst']) }}</td>
                    <td rowspan="2" class="num">{{ $invoicePrice($line['gross']) }}</td>
                </tr>
                <tr>
                    <td>{{ $dash($line['variant']) }}</td>
                    <td class="text-center">{{ $line['mfg'] }} / {{ $line['expiry'] }}</td>
                    <td class="text-center">{{ $dash($line['hsn']) }}</td>
                    <td class="text-center">{{ $line['qty'] }}</td>
                    <td class="text-center">{{ $line['scheme_qty'] }}</td>
                    <td class="num hl-red">{{ $invoicePrice($line['mrp']) }}</td>
                    <td class="num hl-red">{{ $invoicePrice($line['mrp_value']) }}</td>
                    <td class="text-center">{{ $formatPercent($line['bd_percent']) }}</td>
                    <td class="text-center">{{ $formatPercent($line['pd_percent']) }}</td>
                    <td class="num hl-red">{{ $invoicePrice($line['discount_amount']) }}</td>
                    <td class="text-center">{{ $formatPercent($line['sgst_percent']) }}</td>
                    <td class="text-center">{{ $formatPercent($line['cgst_percent']) }}</td>
                    <td class="text-center">{{ $formatPercent($line['igst_percent']) }}</td>
                    <td class="text-center">{{ $formatPercent($line['gst_percent']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" class="text-center">{{ translate('No items found for this order.') }}</td>
                </tr>
            @endforelse
            <tr>
                <td rowspan="2" class="text-center"></td>
                <td class="product-name">{{ translate('Shipping / Freight') }}</td>
                <td class="text-center">—</td>
                <td class="text-center">—</td>
                <td colspan="2" class="text-center">0</td>
                <td class="num">{{ $invoicePrice(0) }}</td>
                <td class="num">{{ $invoicePrice($shippingBase) }}</td>
                <td class="num">—</td>
                <td class="num">{{ $invoicePrice(0) }}</td>
                <td class="num">{{ $invoicePrice(0) }}</td>
                <td rowspan="2" class="num">{{ $invoicePrice($shippingBase) }}</td>
                <td class="num">{{ $invoicePrice($shippingSgst) }}</td>
                <td class="num">{{ $invoicePrice($shippingCgst) }}</td>
                <td class="num">{{ $invoicePrice($shippingIgst) }}</td>
                <td class="num">{{ $invoicePrice($shippingGst) }}</td>
                <td rowspan="2" class="num">{{ $invoicePrice($shippingGross) }}</td>
            </tr>
            <tr>
                <td class="text-center">—</td>
                <td class="text-center">—</td>
                <td class="text-center">—</td>
                <td class="text-center">0</td>
                <td class="text-center">0</td>
                <td class="num">{{ $invoicePrice(0) }}</td>
                <td class="num">—</td>
                <td class="text-center">—</td>
                <td class="text-center">0</td>
                <td class="num">{{ $invoicePrice(0) }}</td>
                <td class="text-center">{{ $formatPercent($shippingSgstPercent) }}</td>
                <td class="text-center">{{ $formatPercent($shippingCgstPercent) }}</td>
                <td class="text-center">{{ $formatPercent($shippingIgstPercent) }}</td>
                <td class="text-center">{{ $formatPercent($shippingGstPercent) }}</td>
            </tr>
            <tr>
                <td colspan="11"></td>
                <td class="num label">{{ $invoicePrice($taxableTotal) }}</td>
                <td colspan="3"></td>
                <td class="num label">{{ $invoicePrice($taxTotal) }}</td>
                <td class="num label">{{ $invoicePrice($taxableTotal + $taxTotal) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals" style="margin-top: 4px;">
        <tr>
            <td width="14%"><span class="label">{{ translate('M.R.P Value') }}</span></td>
            <td width="8%" class="num hl">{{ $invoicePrice($mrpValueTotal) }}</td>
            <td width="12%"><span class="label">{{ translate('Qty') }}:</span> {{ $paidQtyTotal }}</td>
            <td width="12%"><span class="label">{{ translate('SGST') }}{{ $rateTag($sgstRateLabel) }}</span></td>
            <td width="8%" class="num">{{ $invoicePrice($sgstTotal) }}</td>
            <td width="16%"><span class="label">{{ translate('Product Subtotal') }}</span></td>
            <td width="8%" class="num hl">{{ $invoicePrice($productSubtotal) }}</td>
            <td width="14%"><span class="label">{{ translate('Less: Productwise Discount') }}</span></td>
            <td width="8%" class="num">{{ $invoicePrice($productwiseDiscount) }}</td>
        </tr>
        <tr>
            <td><span class="label">{{ translate('Average Discount %') }}</span></td>
            <td class="num">{{ $formatPercent($averageDiscount) }}</td>
            <td><span class="label">{{ translate('Scheme (Free)') }}:</span> {{ $schemeQtyTotal }}</td>
            <td><span class="label">{{ translate('CGST') }}{{ $rateTag($cgstRateLabel) }}</span></td>
            <td class="num">{{ $invoicePrice($cgstTotal) }}</td>
            <td><span class="label">{{ translate('Add: Pack & Forwarding') }}</span></td>
            <td class="num">{{ $invoicePrice($packForwarding) }}</td>
            <td><span class="label">{{ translate('Less: Batchwise Discount') }}</span></td>
            <td class="num">{{ $invoicePrice($batchwiseDiscount) }}</td>
        </tr>
        <tr>
            <td><span class="label">{{ translate('You Save') }}</span></td>
            <td class="num">{{ $invoicePrice($youSave) }}</td>
            <td><span class="label">{{ translate('Total Qty') }}:</span> {{ $paidQtyTotal + $schemeQtyTotal }}</td>
            <td><span class="label">{{ translate('IGST') }}{{ $rateTag($igstRateLabel) }}</span></td>
            <td class="num">{{ $invoicePrice($igstTotal) }}</td>
            <td><span class="label">{{ translate('Add: Shipping / Freight') }}</span></td>
            <td class="num">{{ $invoicePrice($shippingBase) }}</td>
            <td><span class="label">{{ translate('Less: Amountwise Discount') }}</span></td>
            <td class="num">{{ $invoicePrice($amountwiseDiscount) }}</td>
        </tr>
        <tr>
            <td><span class="label">{{ translate('Scheme Discount Value') }}</span></td>
            <td class="num">{{ $invoicePrice($schemeDiscountValue) }}</td>
            <td><span class="label">{{ translate('Total Points Earn') }}:</span> {{ number_format($pointsEarned, 2, '.', '') }}</td>
            <td><span class="label">{{ translate('UTGST') }} : (0.00)</span></td>
            <td class="num">{{ $invoicePrice(0) }}</td>
            <td><span class="label">{{ translate('Add: Insurance Charges') }}</span></td>
            <td class="num">{{ $invoicePrice($insuranceCharges) }}</td>
            <td><span class="label">{{ translate('Less: Coupon Discount') }}</span></td>
            <td class="num">{{ $invoicePrice($couponDiscountTotal) }}</td>
        </tr>
        <tr>
            <td><span class="label">{{ translate('Scheme %') }}</span></td>
            <td class="num">{{ $formatPercent($schemePercent) }}</td>
            <td></td>
            <td><span class="label">{{ translate('GST') }}{{ $rateTag($gstRateLabel) }}</span></td>
            <td class="num">{{ $invoicePrice($taxTotal) }}</td>
            <td><span class="label">{{ translate('Add: Other Charges') }}</span></td>
            <td class="num">{{ $invoicePrice($otherCharges) }}</td>
            <td><span class="label">{{ translate('Less: Additional Discount') }}</span></td>
            <td class="num">{{ $invoicePrice($additionalDiscountTotal) }}</td>
        </tr>
        <tr>
            <td colspan="5"></td>
            <td><span class="label">{{ translate('Taxable Value') }}</span></td>
            <td class="num">{{ $invoicePrice($printedTaxable) }}</td>
            <td><span class="label">{{ translate('Total Tax Value') }}</span></td>
            <td class="num">{{ $invoicePrice($taxTotal) }}</td>
        </tr>
        <tr>
            <td colspan="5"></td>
            <td><span class="label">{{ translate('CR/DB Adjustment') }}</span></td>
            <td class="num">{{ $invoicePrice($crDbAdjustment) }}</td>
            <td class="grand hl-red">{{ translate('Grand Total Payable') }}</td>
            <td class="num grand hl-red">{{ $invoicePrice($grandTotal) }}</td>
        </tr>
        <tr>
            <td colspan="9"><span class="label">{{ translate('Final Amount In Words') }}:</span> {{ $grandTotalInWords }}</td>
        </tr>
    </table>

    @if(!$useExportIds)
        <div class="red" style="margin-top: 4px;">{{ translate('For Domestics Invoice') }}</div>
        <table class="terms" style="margin-top: 2px;">
            <tr>
                <td>
                    <span class="label">{{ translate('Terms & Condition') }}:</span>
                    <ol>
                        <li>{{ translate('Payment to be made by only A/C Payee UPI / IMPS / NEFT / RTGS / Cheque / Draft Only') }}</li>
                        <li>{{ translate('Payment should be made as per terms of payment stipulated in this Invoice.') }}</li>
                        <li>{{ translate('If Payment over due date then interest@36%p.a. will be charged extra.') }}</li>
                        <li>{{ translate('Our responsibility ceases the moment goods leave our premises.') }}</li>
                        <li>{{ translate('Goods once sold will not be taken back or exchanged at any condition.') }}</li>
                        <li>{{ translate('Expired and breakage goods will not be taken back or exchanged at any condition.') }}</li>
                        <li>{{ translate('Subject to Mumbai jurisdiction only.') }}</li>
                        <li>{{ translate('If medicine solution is not clear or it contains suspended particles, DO NOT USE it and send it back for free replacement.') }}</li>
                    </ol>
                    <div style="margin-top: 3px;">
                        {{ translate('We hereby certify that my / our Registration Certificate under the Goods & Services Tax Act, 2017 is in force on the date on which the sale of the goods specified in this GST invoice and Bill of Supply is made by me / us and that the transaction of sale covered by this GST invoice has been effected by me / us and it shall be accounted for in the turnover of sales while filing of return and the due tax, if any, payable on the sale has been paid or shall be paid.') }}
                    </div>
                </td>
            </tr>
        </table>
    @endif

    <table class="sign" style="margin-top: 4px;">
        <tr>
            <td width="34%">
                <div class="red">{{ translate('Received By') }}</div>
                <div class="small">{{ translate('Sign with Rubber Stamp') }}</div>
            </td>
            <td width="32%">
                <table class="inner">
                    <tr>
                        <td class="text-center qr">{!! $invoiceQr !!}<div class="qr-label">{{ translate('Invoice No.') }}</div></td>
                        @if($upiQr)
                            <td class="text-center qr">{!! $upiQr !!}<div class="qr-label">{{ translate('UPI') }}</div></td>
                        @endif
                    </tr>
                </table>
            </td>
            <td width="34%">
                <div class="label">{{ translate('For') }}, {{ strtoupper($siteName) }}</div>
                @if($stampUrl)
                    <img src="{{ $stampUrl }}" alt="{{ translate('Authorised Signature') }}" style="height: 74px;">
                @else
                    <div class="seal">{{ strtoupper($siteName) }}</div>
                @endif
                @if($signUrl)
                    <img src="{{ $signUrl }}" alt="{{ translate('Authorised Signature') }}" style="height: 36px;">
                @endif
                @if($signatoryName)<div class="red">{{ $signatoryName }}</div>@endif
                <div>{{ translate('Authorised Signature') }}</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
