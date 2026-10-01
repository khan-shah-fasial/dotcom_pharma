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

    public const INTERNATIONAL_PAYMENT_TERM_TOOLTIPS = [
        'advance_payment' => 'The buyer pays before production or shipment. Best for new customers, small export orders, and high-demand products. Example: a customer in Kenya orders veterinary medicines from Dotcom Pharma and pays the full amount before dispatch. Seller risk: very low. Buyer risk: high.',
        'letter_of_credit' => 'A bank guarantees payment if the seller submits the required shipping documents (commercial invoice, bill of lading, packing list, certificate of origin, and insurance certificate if applicable). Best for high-value shipments and new international customers. Risk: low for both parties.',
        'documents_against_payment' => 'The bank releases shipping documents only after the buyer makes payment. The buyer cannot collect the cargo until payment is made.',
        'documents_against_acceptance' => 'The buyer accepts a time draft (promise to pay later) and the shipping documents are released before payment. Payment is often due in 30, 60, or 90 days. Seller risk is higher than under D/P.',
        'open_account' => 'The seller ships the goods first and the buyer pays later under agreed credit terms such as Net 30, Net 60, or Net 90. Common among long-term trusted customers.',
        'cash_against_documents' => 'The buyer pays when shipping documents arrive through the bank. Similar to D/P, though practices vary by bank and agreement.',
        'cash_in_advance' => 'The buyer pays before shipment. Often used for custom manufacturing, special pharmaceutical formulations, and OEM production.',
        'telegraphic_transfer' => 'An electronic bank transfer. Common structures include 100% advance, 50% advance and 50% before shipment, or 30% advance and 70% against shipping documents. One of the most widely used methods in global trade.',
        'bank_transfer' => 'An international bank-to-bank electronic transfer used worldwide for import payments, freight charges, agent commissions, and overseas suppliers.',
        'bank_guarantee' => 'A bank guarantees payment if the buyer fails to meet contractual obligations. Common for government tenders, infrastructure projects, and large commercial contracts.',
        'standby_letter_of_credit' => 'Acts as a financial safety net. The seller can claim payment if the buyer defaults, subject to the SBLC terms. Used with large distributors, long-term supply agreements, and high-value exports.',
        'documentary_collection' => 'The seller\'s bank forwards shipping documents to the buyer\'s bank for collection according to agreed instructions. It may be D/P (Documents Against Payment) or D/A (Documents Against Acceptance).',
    ];

    public const INTERNATIONAL_DELIVERY_TERM_TOOLTIPS = [
        'exw' => 'Ex Works. Best for domestic sales and experienced buyers. The seller manufactures, packs, and keeps the goods ready at the factory or warehouse. The buyer arranges pickup, loading, inland transport, export customs, freight, insurance, import customs, and final delivery. Risk transfers as soon as the goods are available for pickup. Example: Dotcom Pharma manufactures veterinary injections in Mumbai; the buyer from Kenya sends their freight forwarder to collect, and from that moment the buyer bears the risk.',
        'fca' => 'Free Carrier. Best for air, road, rail, courier, and express carriers. The seller delivers the goods to a carrier chosen by the buyer at a named place and pays until the carrier receives the cargo. The buyer pays from there onward.',
        'fob' => 'Free On Board. Sea freight only, with a clear split of responsibilities. The seller pays factory to port, export customs, and loading onto the ship. The buyer pays ocean freight, insurance, destination charges, and import customs. Risk transfers once the goods are loaded onto the vessel. Example: Mumbai Port to Dubai — after Dotcom Pharma loads a container onto the ship, the buyer assumes the risk.',
        'cfr' => 'Cost and Freight. Best for sea freight. The seller pays inland transport, export customs, and ocean freight. The buyer pays insurance, import clearance, and local delivery. Risk transfers when the goods are loaded onto the ship, even though the seller pays the freight.',
        'cif' => 'Cost, Insurance and Freight. Best for sea freight. Same as CFR, but the seller also purchases marine insurance to the destination port. The seller pays ocean freight and marine insurance. The buyer pays import duty, customs, and local transport. One of the most common Incoterms in international sea trade.',
        'cpt' => 'Carriage Paid To. Best for any transport mode (air, sea, road, or rail). The seller pays transportation to the named destination. The buyer pays insurance, import duty, and taxes.',
        'cip' => 'Carriage and Insurance Paid To. Best for air, courier, and multimodal. Same as CPT, but the seller also provides insurance. Very common for pharmaceuticals, medical equipment, and veterinary products.',
        'dap' => 'Delivered At Place. Best for door delivery. The seller pays almost everything until the goods reach the agreed destination. The buyer pays import duty, GST/VAT if applicable, and customs clearance. Example: Dotcom Pharma delivers veterinary medicines to a distributor warehouse in Nairobi; the seller arranges transport and the buyer handles import clearance and taxes.',
        'ddp' => 'Delivered Duty Paid. Best for complete door-to-door service when the seller can manage import obligations. The seller handles transport, export clearance, freight, insurance if arranged, import customs, duties, taxes, and final delivery. The buyer simply receives the goods. Maximum convenience for the buyer and the greatest responsibility and cost for the seller.',
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

    public static function paymentTermTooltip(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::INTERNATIONAL_PAYMENT_TERM_TOOLTIPS[$value] ?? self::paymentTermFullForm($value);
    }

    public static function deliveryTermTooltip(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::INTERNATIONAL_DELIVERY_TERM_TOOLTIPS[$value] ?? self::deliveryTermFullForm($value);
    }
}
