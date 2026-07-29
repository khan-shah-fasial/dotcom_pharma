<?php

namespace App\Imports;

use App\Imports\Concerns\NormalizesLogisticsImportValues;
use App\Models\Airport;
use App\Models\Country;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AirportsImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    use NormalizesLogisticsImportValues;

    private int $created = 0;
    private int $updated = 0;

    public function collection(Collection $rows): void
    {
        $countries = Country::query()
            ->get(['id', 'name', 'code', 'iso3'])
            ->keyBy(fn (Country $country) => strtoupper((string) $country->code));

        $prepared = [];
        $errors = [];
        $seenIata = [];
        $seenIcao = [];

        foreach ($rows as $index => $row) {
            $excelRow = $index + 2;
            $data = $this->normalizeRow($row->toArray());
            $data['iso2'] = $this->uppercase($data['iso2'] ?? null);
            $data['iso3'] = $this->uppercase($data['iso3'] ?? null);
            $data['iata'] = $this->uppercase($data['iata'] ?? null);
            $data['icao'] = $this->uppercase($data['icao'] ?? null);
            $status = $this->statusValue($data['status'] ?? null);

            $validator = Validator::make($data, [
                'iso2' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
                'iso3' => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
                'iata' => ['nullable', 'required_without:icao', 'string', 'size:3', 'regex:/^[A-Z0-9]{3}$/'],
                'icao' => ['nullable', 'required_without:iata', 'string', 'size:4', 'regex:/^[A-Z0-9]{4}$/'],
                'name' => ['required', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:255'],
                'terminal_type' => ['nullable', 'string', 'max:255'],
                'authority_name' => ['nullable', 'string', 'max:255'],
                'authority_contact' => ['nullable', 'string', 'max:65535'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = "Row {$excelRow}: {$message}";
                }
            }

            if ($status === null) {
                $errors[] = "Row {$excelRow}: status must be Active, Inactive, Yes, No, 1, or 0.";
            }

            $country = $data['iso2'] ? $countries->get($data['iso2']) : null;
            if (!$country) {
                $errors[] = "Row {$excelRow}: ISO2 code '{$data['iso2']}' does not exist in Shipping Countries.";
            }

            $this->checkDuplicateCode($data['iata'], $excelRow, 'IATA', $seenIata, $errors);
            $this->checkDuplicateCode($data['icao'], $excelRow, 'ICAO', $seenIcao, $errors);

            if ($validator->fails() || $status === null || !$country) {
                continue;
            }

            $matchingIds = Airport::query()
                ->where(function ($query) use ($data) {
                    if ($data['iata']) {
                        $query->where('iata', $data['iata']);
                    }
                    if ($data['icao']) {
                        $method = $data['iata'] ? 'orWhere' : 'where';
                        $query->{$method}('icao', $data['icao']);
                    }
                })
                ->pluck('id')
                ->unique();

            if ($matchingIds->count() > 1) {
                $errors[] = "Row {$excelRow}: IATA '{$data['iata']}' and ICAO '{$data['icao']}' belong to different existing airports.";
                continue;
            }

            $prepared[] = [
                'existing_id' => $matchingIds->first(),
                'attributes' => [
                    'country_id' => $country->id,
                    'country' => $country->name,
                    'iso2' => strtoupper((string) $country->code),
                    'iso3' => $this->uppercase($country->iso3 ?: ($data['iso3'] ?? null)),
                    'iata' => $data['iata'],
                    'icao' => $data['icao'],
                    'name' => trim((string) $data['name']),
                    'city' => $this->nullableString($data['city'] ?? null),
                    'terminal_type' => $this->nullableString($data['terminal_type'] ?? null),
                    'cargo_airport' => $this->shortValue($data['cargo_airport'] ?? null),
                    'customs_airport' => $this->shortValue($data['customs_airport'] ?? null),
                    'cold_chain_facility' => $this->shortValue($data['cold_chain_facility'] ?? null),
                    'authority_name' => $this->nullableString($data['authority_name'] ?? null),
                    'authority_contact' => $this->nullableString($data['authority_contact'] ?? null),
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'status' => $status,
                ],
            ];
        }

        if ($errors) {
            throw ValidationException::withMessages([
                'bulk_file' => array_slice($errors, 0, 25),
            ]);
        }

        DB::transaction(function () use ($prepared) {
            foreach ($prepared as $item) {
                if ($item['existing_id']) {
                    Airport::query()->findOrFail($item['existing_id'])->update($item['attributes']);
                    $this->updated++;
                } else {
                    Airport::create($item['attributes']);
                    $this->created++;
                }
            }
        });
    }

    public function createdCount(): int
    {
        return $this->created;
    }

    public function updatedCount(): int
    {
        return $this->updated;
    }

    private function checkDuplicateCode(?string $code, int $row, string $label, array &$seen, array &$errors): void
    {
        if (!$code) {
            return;
        }

        if (isset($seen[$code])) {
            $errors[] = "Row {$row}: {$label} code '{$code}' is already used in row {$seen[$code]}.";
        } else {
            $seen[$code] = $row;
        }
    }

    private function shortValue($value): ?string
    {
        $value = $this->nullableString($value);

        return $value === null ? null : substr($value, 0, 20);
    }
}
