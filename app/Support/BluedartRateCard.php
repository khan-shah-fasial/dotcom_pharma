<?php

namespace App\Support;

/**
 * Blue Dart has no price API, so checkout prices come from the contract rate
 * card the admin enters on Setup > Shipping > Courier APIs.
 *
 * Card shape (stored as JSON in business setting `bluedart_rate_card`):
 * [
 *   'zones' => [
 *     'local'      => ['first' => 60, 'additional' => 40],  // Rs. for first 500 g, each extra 500 g
 *     'state'      => [...], 'metro' => [...], 'rest' => [...], 'special' => [...],
 *   ],
 *   'fuel_pct' => 35,      // fuel surcharge % on freight
 *   'cod_flat' => 50,      // COD fee: the higher of flat and % of order value
 *   'cod_pct'  => 2,
 *   'gst_pct'  => 18,      // added on the total
 * ]
 *
 * Zones are worked out from the two pincodes, so they work for guests too:
 *   local   = same first 3 digits (same city / sorting district)
 *   state   = same postal circle (see CIRCLES)
 *   special = north-east, J&K/Ladakh, Andaman, Lakshadweep
 *   metro   = destination in a metro city
 *   rest    = everything else
 */
class BluedartRateCard
{
    public const ZONES = [
        'local' => 'Within city',
        'state' => 'Within state',
        'metro' => 'Metro cities',
        'rest' => 'Rest of India',
        'special' => 'North-east, J&K, islands',
    ];

    /** First 3 digits of metro pincodes: Mumbai, Delhi, Kolkata, Chennai, Bengaluru, Hyderabad, Ahmedabad, Pune. */
    public const METRO_PREFIXES = ['400', '110', '700', '600', '560', '500', '380', '411'];

    /** North-east (78, 79), J&K/Ladakh (18, 19), Andaman (744), Lakshadweep (682 55x). */
    public const SPECIAL_PREFIXES = ['78', '79', '18', '19', '744', '68255'];

    public const SLAB_KG = 0.5;

    /**
     * Postal circles by the first two pincode digits (India Post), used as "same state".
     * Telangana/AP and Bihar/Jharkhand share circles, so they count as one region.
     */
    public const CIRCLES = [
        'delhi' => [11], 'haryana' => [12, 13], 'punjab' => [14, 15, 16], 'himachal' => [17],
        'jk' => [18, 19], 'up_uk' => [20, 21, 22, 23, 24, 25, 26, 27, 28], 'rajasthan' => [30, 31, 32, 33, 34],
        'gujarat' => [36, 37, 38, 39], 'maharashtra' => [40, 41, 42, 43, 44], 'mp' => [45, 46, 47, 48],
        'chhattisgarh' => [49], 'ap_telangana' => [50, 51, 52, 53], 'karnataka' => [56, 57, 58, 59],
        'tamil_nadu' => [60, 61, 62, 63, 64], 'kerala' => [67, 68, 69], 'west_bengal' => [70, 71, 72, 73, 74],
        'odisha' => [75, 76, 77], 'assam' => [78], 'north_east' => [79], 'bihar_jharkhand' => [80, 81, 82, 83, 84, 85],
    ];

    public static function circle(string $pin): ?string
    {
        $two = (int) substr(preg_replace('/\D+/', '', $pin), 0, 2);
        foreach (self::CIRCLES as $circle => $prefixes) {
            if (in_array($two, $prefixes, true)) {
                return $circle;
            }
        }

        return null;
    }

    public static function zone(string $originPin, string $destPin): string
    {
        $o = preg_replace('/\D+/', '', $originPin);
        $d = preg_replace('/\D+/', '', $destPin);

        foreach (self::SPECIAL_PREFIXES as $prefix) {
            if (str_starts_with($d, $prefix)) {
                return 'special';
            }
        }
        if (strlen($o) >= 3 && substr($o, 0, 3) === substr($d, 0, 3)) {
            return 'local';
        }
        $circle = self::circle($o);
        if ($circle !== null && $circle === self::circle($d)) {
            return 'state';
        }
        if (in_array(substr($d, 0, 3), self::METRO_PREFIXES, true)) {
            return 'metro';
        }

        return 'rest';
    }

    /**
     * Price in Rs. incl. surcharges and GST, or null when the card has no rate for the zone.
     */
    public static function price(array $card, string $zone, float $chargedWeightKg, bool $cod, float $orderValue): ?array
    {
        $rate = $card['zones'][$zone] ?? null;
        $first = is_array($rate) && is_numeric($rate['first'] ?? null) ? (float) $rate['first'] : null;
        $additional = is_array($rate) && is_numeric($rate['additional'] ?? null) ? (float) $rate['additional'] : 0.0;

        if ($first === null || $first <= 0) {
            return null;
        }

        $slabs = max(1, (int) ceil(round(max($chargedWeightKg, 0.01) / self::SLAB_KG, 6)));
        $freight = $first + ($slabs - 1) * $additional;
        $fuel = $freight * (float) ($card['fuel_pct'] ?? 0) / 100;
        $codFee = $cod
            ? max((float) ($card['cod_flat'] ?? 0), $orderValue * (float) ($card['cod_pct'] ?? 0) / 100)
            : 0.0;
        $subtotal = $freight + $fuel + $codFee;
        $gst = $subtotal * (float) ($card['gst_pct'] ?? 18) / 100;

        return [
            'zone' => $zone,
            'slabs' => $slabs,
            'freight' => round($freight, 2),
            'fuel' => round($fuel, 2),
            'cod_fee' => round($codFee, 2),
            'gst' => round($gst, 2),
            'total' => round($subtotal + $gst, 2),
        ];
    }

    public static function fromSetting($raw): array
    {
        $card = is_array($raw) ? $raw : json_decode((string) $raw, true);

        return is_array($card) ? $card : [];
    }
}
