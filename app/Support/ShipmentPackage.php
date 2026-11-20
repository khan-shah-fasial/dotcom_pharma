<?php

namespace App\Support;

/**
 * Builds the single-package details courier APIs expect, from the
 * Weight And Dimension Master (product_stocks + product_stock_dimensions).
 *
 * The master stores weights in GRAMS and sizes in CM. Couriers get kg.
 *
 * Per order line:
 *  - full outer cases use the master's case gross weight and case size;
 *  - loose pieces use the piece gross weight (falling back to net weight)
 *    and piece size.
 * Everything is then packed into one box: footprint = largest length x width,
 * height = whatever holds the combined volume. Charged weight is the higher of
 * physical and volumetric weight (L*W*H / 5000), which is how couriers bill.
 */
class ShipmentPackage
{
    /** Used when the master has no usable weight for a piece. */
    public const DEFAULT_ITEM_WEIGHT_KG = 0.21;
    /** Piece weights below this many grams are placeholders or old kg entries (shown as ~0 kg in the master). */
    public const MIN_REAL_WEIGHT_G = 2.0;
    public const MIN_SIDE_CM = 10.0;
    public const VOLUMETRIC_DIVISOR = 5000;

    protected static function positive($value): ?float
    {
        return is_numeric($value) && (float) $value > 0 ? (float) $value : null;
    }

    protected static function grams($value): ?float
    {
        $value = static::positive($value);

        return $value !== null && $value >= static::MIN_REAL_WEIGHT_G ? $value : null;
    }

    /**
     * L, W, H in cm, or nulls when not entered (or the 1x1x1 placeholder).
     */
    protected static function dims($l, $w, $h): array
    {
        $dims = [static::positive($l), static::positive($w), static::positive($h)];

        if (in_array(null, $dims, true) || max($dims) <= 1) {
            return [null, null, null];
        }

        return $dims;
    }

    /**
     * Package line for one order/cart row, read from the stock's master data.
     * $product is accepted for older callers but its legacy weight field is not used.
     */
    public static function line($stock, $product, int $quantity): array
    {
        $sheet = $stock?->dimensionSheet ?? null;

        $pieceGrams = static::grams($sheet?->piece_gross ?? null) ?? static::grams($stock?->weight ?? null);
        [$length, $width, $height] = static::dims($stock?->length ?? null, $stock?->width ?? null, $stock?->height ?? null);

        $line = [
            'weight' => $pieceGrams !== null ? $pieceGrams / 1000 : static::DEFAULT_ITEM_WEIGHT_KG,
            'weight_known' => $pieceGrams !== null,
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'quantity' => max(1, $quantity),
            'case' => null,
        ];

        // Outer case: qty per case, gross weight and size from the master.
        $perBuffer = static::positive($stock?->qty_per_buffer_box ?? null);
        $buffersPerCase = static::positive($stock?->count ?? null);
        $perCase = static::positive($stock?->total_qty_per_case ?? null)
            ?? ($perBuffer && $buffersPerCase ? $perBuffer * $buffersPerCase : null);

        $caseGrams = static::grams($stock?->weight_case ?? null);
        if ($caseGrams === null && $buffersPerCase) {
            $bufferGrams = static::grams($stock?->weight_buffer_box ?? null)
                ?? ($perBuffer && $pieceGrams ? $perBuffer * $pieceGrams : null);
            $caseGrams = $bufferGrams ? $buffersPerCase * $bufferGrams : null;
        }

        [$caseL, $caseW, $caseH] = static::dims($stock?->case_length ?? null, $stock?->case_width ?? null, $stock?->case_height ?? null);

        if ($perCase && $perCase > 1 && $caseGrams && $caseL) {
            $line['case'] = [
                'per_case' => (int) round($perCase),
                'weight' => $caseGrams / 1000,
                'length' => $caseL,
                'width' => $caseW,
                'height' => $caseH,
            ];
        }

        return $line;
    }

    /**
     * @param  array<int, array>  $lines  from line()
     */
    public static function fromLines(array $lines): array
    {
        $weight = 0.0;
        $volume = 0.0;
        $maxLength = 0.0;
        $maxWidth = 0.0;
        $cases = 0;

        foreach ($lines as $line) {
            $qty = max(1, (int) ($line['quantity'] ?? 1));
            $case = $line['case'] ?? null;

            $fullCases = 0;
            if ($case && $case['per_case'] > 1 && $qty >= $case['per_case']) {
                $fullCases = intdiv($qty, $case['per_case']);
                $qty -= $fullCases * $case['per_case'];

                $weight += $case['weight'] * $fullCases;
                $volume += $case['length'] * $case['width'] * $case['height'] * $fullCases;
                $maxLength = max($maxLength, $case['length']);
                $maxWidth = max($maxWidth, $case['width']);
                $cases += $fullCases;
            }

            if ($qty < 1) {
                continue;
            }

            $weight += (float) ($line['weight'] ?? 0) * $qty;

            $l = (float) ($line['length'] ?? 0);
            $w = (float) ($line['width'] ?? 0);
            $h = (float) ($line['height'] ?? 0);
            if ($l > 0 && $w > 0 && $h > 0) {
                $volume += $l * $w * $h * $qty;
                $maxLength = max($maxLength, $l);
                $maxWidth = max($maxWidth, $w);
            }
        }

        $length = max(static::MIN_SIDE_CM, $maxLength);
        $width = max(static::MIN_SIDE_CM, $maxWidth);
        $height = max(static::MIN_SIDE_CM, $volume > 0 ? ceil($volume / ($length * $width)) : 0);

        return static::format($weight, $length, $width, $height) + ['full_cases' => $cases];
    }

    /**
     * Package from known box dimensions (e.g. entered on the order). Weight in kg.
     */
    public static function format(float $weight, float $length, float $width, float $height): array
    {
        if ($weight <= 0) {
            $weight = static::DEFAULT_ITEM_WEIGHT_KG;
        }

        $length = max(static::MIN_SIDE_CM, $length);
        $width = max(static::MIN_SIDE_CM, $width);
        $height = max(static::MIN_SIDE_CM, $height);
        $volumetric = round(($length * $width * $height) / static::VOLUMETRIC_DIVISOR, 2);

        return [
            'total_physical_weight' => number_format($weight, 2, '.', ''),
            'box_length' => number_format($length, 2, '.', ''),
            'box_breadth' => number_format($width, 2, '.', ''),
            'box_height' => number_format($height, 2, '.', ''),
            'volumetric_weight' => number_format($volumetric, 2, '.', ''),
            'charged_weight' => number_format(max($weight, $volumetric), 2, '.', ''),
        ];
    }
}
