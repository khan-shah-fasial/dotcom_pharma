<?php

namespace App\Utility;

use App\Models\Addon;
use App\Models\AttributeValue;
use App\Models\Color;

class ProductUtility
{
    protected static $attributeValueIdCache = [];

    public static function get_attribute_options($collection)
    {
        $options = array();
        if (
            isset($collection['colors_active']) &&
            $collection['colors_active'] &&
            $collection['colors'] &&
            count($collection['colors']) > 0
        ) {
            $colors_active = 1;
            array_push($options, $collection['colors']);
        }

        if (isset($collection['choice_no']) && $collection['choice_no']) {
            foreach ($collection['choice_no'] as $key => $no) {
                $name = 'choice_options_' . $no;
                $data = array();
                foreach (request()[$name] as $key => $eachValue) {
                    array_push($data, $eachValue);
                }
                array_push($options, $data);
            }
        }

        return $options;
    }

    public static function get_combination_string($combination, $collection)
    {
        return self::get_combination_variant_data($combination, $collection)['variant'];
    }

    public static function get_combination_variant_data($combination, $collection): array
    {
        $str = '';
        $idParts = [];
        $choiceNos = array_values((array) ($collection['choice_no'] ?? []));
        $choiceIndex = 0;
        $hasUnresolvedAttribute = false;

        foreach ($combination as $key => $item) {
            if ($key > 0) {
                $str .= '-' . str_replace(' ', '', $item);
                $attributeId = $choiceNos[$choiceIndex] ?? null;
                $choiceIndex++;
                $valueId = $attributeId ? self::attribute_value_id($attributeId, $item) : null;
                if ($valueId === null) {
                    $hasUnresolvedAttribute = true;
                }
                $idParts[] = $valueId;
            } else {
                if (isset($collection['colors_active']) && $collection['colors_active'] && $collection['colors'] && count($collection['colors']) > 0) {
                    $color_name = Color::where('code', $item)->first()->name;
                    $str .= $color_name;
                    $idParts[] = self::color_id_variant_segment($item);
                } else {
                    $str .= str_replace(' ', '', $item);
                    $attributeId = $choiceNos[$choiceIndex] ?? null;
                    $choiceIndex++;
                    $valueId = $attributeId ? self::attribute_value_id($attributeId, $item) : null;
                    if ($valueId === null) {
                        $hasUnresolvedAttribute = true;
                    }
                    $idParts[] = $valueId;
                }
            }
        }

        return [
            'variant' => $str,
            'id_variant' => $hasUnresolvedAttribute ? null : implode('-', $idParts),
        ];
    }

    public static function resolve_id_variant_for_product_variant($product, ?string $variant): ?string
    {
        if ($variant === null || $variant === '') {
            return null;
        }

        $choiceOptions = json_decode($product->choice_options ?? '[]', true);
        if (!is_array($choiceOptions)) {
            $choiceOptions = [];
        }

        $segments = explode('-', $variant);
        $colorSegmentCount = count($segments) > count($choiceOptions) ? count($segments) - count($choiceOptions) : 0;
        $idParts = [];
        $choiceIndex = 0;

        foreach ($segments as $index => $segment) {
            if ($index < $colorSegmentCount) {
                $idParts[] = self::color_id_variant_segment($segment);
                continue;
            }

            $option = $choiceOptions[$choiceIndex] ?? null;
            $choiceIndex++;
            $attributeId = is_array($option) ? ($option['attribute_id'] ?? ($option['attribute_at'] ?? null)) : null;
            $valueId = $attributeId ? self::attribute_value_id($attributeId, $segment) : null;
            if ($valueId === null) {
                return null;
            }
            $idParts[] = $valueId;
        }

        return implode('-', $idParts);
    }

    public static function attribute_value_id($attributeId, $value): ?int
    {
        $attributeId = (int) $attributeId;
        $normalizedValue = self::normalize_variant_value($value);
        $cacheKey = $attributeId . ':' . $normalizedValue;

        if (array_key_exists($cacheKey, self::$attributeValueIdCache)) {
            return self::$attributeValueIdCache[$cacheKey];
        }

        $attributeValues = AttributeValue::where('attribute_id', $attributeId)->get(['id', 'value']);
        foreach ($attributeValues as $attributeValue) {
            if (self::normalize_variant_value($attributeValue->value) === $normalizedValue) {
                return self::$attributeValueIdCache[$cacheKey] = (int) $attributeValue->id;
            }
        }

        return self::$attributeValueIdCache[$cacheKey] = null;
    }

    public static function normalize_variant_value($value): string
    {
        return str_replace(' ', '', (string) $value);
    }

    protected static function color_id_variant_segment($value): string
    {
        $segment = preg_replace('/[^A-Za-z0-9_]/', '_', (string) $value);
        return 'color_' . trim($segment, '_');
    }
}
