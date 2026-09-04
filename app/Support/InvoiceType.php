<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class InvoiceType
{
    public const DOMESTIC = 'domestic';
    public const INTERNATIONAL = 'international';

    public const DOMESTIC_PAYMENT_TERMS = [
        'cash_on_delivery' => 'COD / Cash On Delivery',
        'manual' => 'Manual / Cash',
        'bank_payment' => 'Bank Payment / Cheque / NEFT / IMPS / RTGS',
        'wallet' => 'Wallet / Rechargeable',
        'credit' => 'Credit / Credit Allowed',
    ];

    public const INTERNATIONAL_PAYMENT_TERMS = [
        'advance_payment' => 'Payment in Advance / Advance Payment',
        'letter_of_credit' => 'L/C or LC / Letter of Credit',
        'documents_against_payment' => 'D/P / Documents Against Payment',
        'documents_against_acceptance' => 'D/A / Documents Against Acceptance',
        'open_account' => 'OA / Open Account',
        'cash_against_documents' => 'CAD / Cash Against Documents',
        'cash_in_advance' => 'CIA / Cash in Advance',
        'telegraphic_transfer' => 'T/T / Telegraphic Transfer',
        'bank_transfer' => 'Bank Transfer / Wire Transfer',
        'bank_guarantee' => 'BG / Bank Guarantee',
        'standby_letter_of_credit' => 'SBLC / Standby Letter of Credit',
        'documentary_collection' => 'D/C / Documentary Collection',
    ];

    public const DOMESTIC_DELIVERY_TERMS = [
        'door_delivery' => 'Door',
        'transport_warehouse' => 'Carrier Warehouse',
        'our_warehouse_delivery' => 'Our Warehouse',
        'hand_delivery' => 'In Hand',
    ];

    public const INTERNATIONAL_DELIVERY_TERMS = [
        'exw' => 'EXW',
        'fca' => 'FCA',
        'fob' => 'FOB',
        'cfr' => 'CFR',
        'cif' => 'CIF',
        'cpt' => 'CPT',
        'cip' => 'CIP',
        'dap' => 'DAP',
        'ddp' => 'DDP',
    ];

    public const DELIVERY_TERM_FULL_FORMS = [
        'door_delivery' => 'Door Delivery',
        'transport_warehouse' => 'Carrier Warehouse',
        'transport_godown' => 'Carrier Warehouse',
        'our_warehouse_delivery' => 'Our Warehouse',
        'hand_delivery' => 'In Hand',
        'exw' => 'Ex Works',
        'fca' => 'Free Carrier',
        'fob' => 'Free On Board',
        'cfr' => 'Cost and Freight',
        'cif' => 'Cost, Insurance and Freight',
        'cpt' => 'Carriage Paid To',
        'cip' => 'Carriage and Insurance Paid To',
        'dap' => 'Delivered At Place',
        'ddp' => 'Delivered Duty Paid',
    ];

    public static function forUser(?User $user): string
    {
        $rawType = $user?->type_option;
        if ($rawType === null || trim((string) $rawType) === '') {
            $rawType = optional($user?->user_details)->type_option;
        }

        return self::normalize($rawType, $user?->id);
    }

    public static function normalize($rawType, ?int $userId = null): string
    {
        $type = strtolower(trim((string) $rawType));

        // Existing customers were domestic before type_option was introduced.
        if ($type === '') {
            return self::DOMESTIC;
        }

        if (!in_array($type, [self::DOMESTIC, self::INTERNATIONAL], true)) {
            Log::warning('Invoice customer has an invalid type_option.', [
                'user_id' => $userId,
                'type_option' => $rawType,
            ]);

            throw new InvalidArgumentException('The invoice customer type must be domestic or international.');
        }

        return $type;
    }

    public static function isDomestic(string $type): bool
    {
        return $type === self::DOMESTIC;
    }

    public static function paymentTerms(string $type): array
    {
        return self::isDomestic($type)
            ? self::DOMESTIC_PAYMENT_TERMS
            : self::INTERNATIONAL_PAYMENT_TERMS;
    }

    public static function deliveryTerms(string $type): array
    {
        return self::isDomestic($type)
            ? self::DOMESTIC_DELIVERY_TERMS
            : self::INTERNATIONAL_DELIVERY_TERMS;
    }

    public static function paymentTermLabel(?string $value, string $type): ?string
    {
        return self::paymentTerms($type)[$value] ?? null;
    }

    public static function paymentTermFullForm(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $label = self::DOMESTIC_PAYMENT_TERMS[$value] ?? self::INTERNATIONAL_PAYMENT_TERMS[$value] ?? null;
        if ($label === null) {
            return null;
        }

        $separator = strpos($label, ' / ');
        if ($separator === false) {
            return $label;
        }

        return trim(substr($label, $separator + 3));
    }

    public static function deliveryTermLabel(?string $value, string $type): ?string
    {
        return self::deliveryTerms($type)[$value] ?? null;
    }

    public static function deliveryTermFullForm(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::DELIVERY_TERM_FULL_FORMS[$value] ?? null;
    }
}
