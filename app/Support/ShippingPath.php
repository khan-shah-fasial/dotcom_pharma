<?php

namespace App\Support;

class ShippingPath
{
    public const AIR_CARGO_TYPES = [
        'general_cargo' => 'General Cargo',
        'express_cargo' => 'Express Cargo',
        'perishable_cargo' => 'Perishable Cargo',
        'temperature_controlled' => 'Temperature Controlled',
        'dangerous_goods' => 'Dangerous Goods',
        'live_animals' => 'Live Animals',
        'valuable_cargo' => 'Valuable Cargo',
        'oversized_cargo' => 'Oversized Cargo',
    ];

    public static function allowedSubModes(?string $fodMode): array
    {
        if ($fodMode === 'sea') {
            return ['lcl', 'fcl'];
        }

        if ($fodMode === 'air') {
            return array_keys(self::AIR_CARGO_TYPES);
        }

        return ['road', 'train'];
    }

    public static function allSubModes(): array
    {
        return array_values(array_unique(array_merge(
            ['road', 'train', 'lcl', 'fcl'],
            array_keys(self::AIR_CARGO_TYPES)
        )));
    }

    public static function subModeLabel(?string $value): string
    {
        $labels = array_merge([
            'road' => 'Road',
            'train' => 'Train',
            'lcl' => 'LCL',
            'fcl' => 'FCL',
        ], self::AIR_CARGO_TYPES);

        if ($value && isset($labels[$value])) {
            return $labels[$value];
        }

        return $value ? ucfirst(str_replace('_', ' ', $value)) : '';
    }

    public static function isValidSubMode(?string $fodMode, ?string $subMode): bool
    {
        return in_array((string) $subMode, self::allowedSubModes($fodMode), true);
    }
}
