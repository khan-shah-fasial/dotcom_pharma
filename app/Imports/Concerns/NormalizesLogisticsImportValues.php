<?php

namespace App\Imports\Concerns;

trait NormalizesLogisticsImportValues
{
    protected function normalizeRow(array $row): array
    {
        return array_map(function ($value) {
            if (!is_string($value)) {
                return $value;
            }

            $value = trim($value);

            return $value === '' ? null : $value;
        }, $row);
    }

    protected function uppercase($value): ?string
    {
        $value = $this->nullableString($value);

        return $value === null ? null : strtoupper($value);
    }

    protected function nullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function statusValue($value): ?bool
    {
        if ($value === null || trim((string) $value) === '') {
            return true;
        }

        return match (strtolower(trim((string) $value))) {
            '1', 'active', 'yes', 'true' => true,
            '0', 'inactive', 'no', 'false' => false,
            default => null,
        };
    }
}
