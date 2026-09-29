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
                'quotation',
                'performa_invoice',
                'order_entry',
                'sales_invoice',
                'bill_of_supply',
                'e_invoice',
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

        if (!array_key_exists($type, self::TYPES)) {
            return [
                'document_type' => null,
                'document_type_custom' => null,
            ];
        }

        return [
            'document_type' => $type,
            'document_type_custom' => null,
        ];
    }

    public static function label($type, $custom): string
    {
        $custom = trim((string) $custom);
        if ($custom !== '') {
            return $custom;
        }

        return self::TYPES[$type] ?? '—';
    }
}
