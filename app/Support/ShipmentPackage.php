<?php

namespace App\Support;

/**
 * Builds the single-box package courier APIs expect.
 *
 * Items are packed into one box: footprint = largest item length x width,
 * height = whatever is needed to hold the combined item volume. Charged
 * weight is the higher of physical and volumetric weight (L*W*H / 5000),
 * which is how Delhivery, Blue Dart and DHL bill.
 */
class ShipmentPackage
{
    public const DEFAULT_ITEM_WEIGHT_KG = 0.21;
    public const MIN_SIDE_CM = 10.0;
    public const VOLUMETRIC_DIVISOR = 5000;

    /**
     * Product or stock dimension, or null when not set.
     */
    public static function dimension($stock, $product, string $field): ?float
    {
        $value = $stock->{$field} ?? null;
        if ((float) $value <= 0) {
            $value = $product->{$field} ?? null;
        }

        return (float) $value > 0 ? (float) $value : null;
    }

    /**
     * Package line for one order/cart row.
     */
    public static function line($stock, $product, int $quantity): array
    {
        $weight = static::dimension($stock, $product, 'weight') ?? static::DEFAULT_ITEM_WEIGHT_KG;

        return [
            'weight' => $weight,
            'length' => static::dimension($stock, $product, 'length'),
            'width' => static::dimension($stock, $product, 'width'),
            'height' => static::dimension($stock, $product, 'height'),
            'quantity' => max(1, $quantity),
        ];
    }

    /**
     * @param  array<int, array{weight: float, length: ?float, width: ?float, height: ?float, quantity: int}>  $lines
     */
    public static function fromLines(array $lines): array
    {
        $weight = 0.0;
        $volume = 0.0;
        $maxLength = 0.0;
        $maxWidth = 0.0;

        foreach ($lines as $line) {
            $qty = max(1, (int) ($line['quantity'] ?? 1));
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

        return static::format($weight, $length, $width, $height);
    }

    /**
     * Package from known box dimensions (e.g. entered on the order).
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
