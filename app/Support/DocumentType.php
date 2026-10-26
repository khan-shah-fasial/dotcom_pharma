<?php

namespace App\Support;

class DocumentType
{
    public const TYPES = [
        'quotation' => 'Quotation',
        'performa_invoice' => 'Performa Invoice',
        'order_entry' => 'Order Entry',
        'sales_invoice' => 'Sales Invoice',
        'bill_of_supply' => 'Bill of Supply',
        'e_invoice' => 'E-Invoice',
        'commercial_invoice' => 'Commercial Invoice',
        'international_invoice' => 'International Invoice',
        'sales_return' => 'Sales Return',
        'inquiry' => 'Inquiry',
        'counter_offer' => 'Counter Offer',
        'purchase_order' => 'Purchase Order',
        'purchase_invoice' => 'Purchase Invoice',
        'purchase_return' => 'Purchase Return',
    ];

    public const ENTRIES = [
        'sales' => [
            'title' => 'Sales Entry',
            'types' => [
                'bill_of_supply',
                'e_invoice',
                'order_entry',
                'performa_invoice',
                'sales_invoice',
                'sales_return',
                'quotation',
                'commercial_invoice',
                'international_invoice',
            ],
        ],
        'sales_return' => [
            'title' => 'Sales Return',
            'types' => ['sales_return'],
        ],
        'purchase' => [
            'title' => 'Purchase Entry',
            'types' => [
                'inquiry',
                'counter_offer',
                'quotation',
                'purchase_order',
                'purchase_invoice',
            ],
        ],
        'purchase_return' => [
            'title' => 'Purchase Return',
            'types' => ['purchase_return'],
        ],
    ];

    public const SALES_TYPES_BY_MARKET = [
        InvoiceType::DOMESTIC => [
            'bill_of_supply' => 'Bill Of Supply',
            'e_invoice' => 'E-Invoice',
            'order_entry' => 'Order',
            'performa_invoice' => 'Performa',
            'sales_invoice' => 'Sales',
            'sales_return' => 'Sales Return',
            'quotation' => 'Quotation',
        ],
        InvoiceType::INTERNATIONAL => [
            'commercial_invoice' => 'Commercial Invoice',
            'e_invoice' => 'E-Invoice',
            'order_entry' => 'Order',
            'performa_invoice' => 'Performa',
            'international_invoice' => 'International Invoice',
            'sales_return' => 'Sales Return',
            'quotation' => 'Quotation',
        ],
    ];

    public const DEFAULT_SALES_TYPE_BY_MARKET = [
        InvoiceType::DOMESTIC => 'performa_invoice',
        InvoiceType::INTERNATIONAL => 'quotation',
    ];

    public const ALIASES = [
        'order' => 'order_entry',
        'order entry' => 'order_entry',
        'performa' => 'performa_invoice',
        'performa invoice' => 'performa_invoice',
        'proforma' => 'performa_invoice',
        'proforma invoice' => 'performa_invoice',
        'sales' => 'sales_invoice',
        'sales invoice' => 'sales_invoice',
        'bill of supply' => 'bill_of_supply',
        'e-invoice' => 'e_invoice',
        'e invoice' => 'e_invoice',
        'commercial invoice' => 'commercial_invoice',
        'international invoice' => 'international_invoice',
        'sales return' => 'sales_return',
        'quotation' => 'quotation',
    ];

    public static function entry($value): ?string
    {
        $value = trim((string) $value);

        return array_key_exists($value, self::ENTRIES) ? $value : null;
    }

    public static function typesFor(?string $entry): array
    {
        if ($entry === null || !isset(self::ENTRIES[$entry])) {
            return self::TYPES;
        }

        $types = [];
        foreach (self::ENTRIES[$entry]['types'] as $key) {
            if (isset(self::TYPES[$key])) {
                $types[$key] = self::TYPES[$key];
            }
        }

        return $types;
    }

    public static function titleFor(?string $entry): string
    {
        return self::ENTRIES[$entry]['title'] ?? 'Add Order';
    }

    public static function store($type, $custom): array
    {
        $type = trim((string) $type);
        $custom = trim((string) $custom);

        if ($type === '__not_in_list__') {
            return [
                'document_type' => null,
                'document_type_custom' => $custom !== '' ? $custom : null,
            ];
        }

        if (array_key_exists($type, self::TYPES)) {
            return [
                'document_type' => $type,
                'document_type_custom' => null,
            ];
        }

        $labels = [];
        foreach (self::TYPES as $key => $label) {
            $labels[strtolower($label)] = $key;
        }
        foreach (self::ALIASES as $alias => $key) {
            $labels[strtolower($alias)] = $key;
        }
        if (isset($labels[strtolower($type)])) {
            return [
                'document_type' => $labels[strtolower($type)],
                'document_type_custom' => null,
            ];
        }

        if ($type !== '') {
            return [
                'document_type' => null,
                'document_type_custom' => $type,
            ];
        }

        return [
            'document_type' => null,
            'document_type_custom' => null,
        ];
    }

    public static function label($type, $custom): string
    {
        $custom = trim((string) $custom);
        if ($custom !== '') {
            return $custom;
        }

        return self::TYPES[$type] ?? ($type !== '' ? $type : '—');
    }

    public static function selectableValues(): array
    {
        return array_values(array_unique(array_merge(
            array_keys(self::TYPES),
            array_values(self::TYPES),
            array_values(self::SALES_TYPES_BY_MARKET[InvoiceType::DOMESTIC]),
            array_values(self::SALES_TYPES_BY_MARKET[InvoiceType::INTERNATIONAL]),
            \App\Models\SeriesMaster::invoiceTypeNames(),
            ['__not_in_list__']
        )));
    }

    public static function canonicalKey($type, $custom = null): ?string
    {
        $stored = self::store($type, $custom);

        return $stored['document_type'];
    }

    public static function stockEffect(?string $type, $custom = null): string
    {
        $key = self::canonicalKey($type, $custom);
        if (in_array($key, ['performa_invoice', 'order_entry'], true)) {
            return 'none';
        }
        if ($key === 'sales_return') {
            return 'plus';
        }

        return 'minus';
    }

    public static function taxRule(?string $type, $custom = null): ?string
    {
        $key = self::canonicalKey($type, $custom);
        if ($key === 'sales_invoice') {
            return 'taxable_only';
        }
        if ($key === 'bill_of_supply') {
            return 'tax_free_only';
        }

        return null;
    }

    public static function numberHeading(?string $type, $custom = null): string
    {
        $key = self::canonicalKey($type, $custom);

        return match ($key) {
            'performa_invoice' => 'Performa Invoice No',
            'sales_invoice' => 'Sales Invoice No',
            'bill_of_supply' => 'Bill Of Supply No',
            'sales_return' => 'Sales Return No',
            default => 'Order No.',
        };
    }

    public static function dateHeading(?string $type, $custom = null): string
    {
        $key = self::canonicalKey($type, $custom);

        return in_array($key, ['performa_invoice', 'sales_invoice', 'bill_of_supply', 'sales_return'], true)
            ? 'Date'
            : 'Order Date';
    }

    public static function timeHeading(?string $type, $custom = null): string
    {
        $key = self::canonicalKey($type, $custom);

        return in_array($key, ['performa_invoice', 'sales_invoice', 'bill_of_supply', 'sales_return'], true)
            ? 'Time'
            : 'Order Time';
    }

    public static function seriesCode($type, $custom = null): string
    {
        return \App\Models\SeriesMaster::codeForInvoiceType($type, $custom);
    }

    public static function seriesCodesFor(array $documentTypes): array
    {
        $map = [];
        foreach ($documentTypes as $key => $label) {
            $map[$key] = self::seriesCode($key);
        }

        return $map;
    }
}
