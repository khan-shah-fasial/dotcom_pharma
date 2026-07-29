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
        $seenPortIds = [];
        $seenIata = [];
        $seenIcao = [];

        foreach ($rows as $index => $row) {
            $excelRow = $index + 2;
            $data = $this->normalizeRow($row->toArray());
            $data['iso2'] = $this->uppercase($data['iso2'] ?? null);
            $data['iso3'] = $this->uppercase($data['iso3'] ?? null);
            $data['port_id'] = $this->uppercase($data['port_id'] ?? null);
            $data['iata'] = $this->uppercase($data['iata'] ?? null);
            $data['icao'] = $this->uppercase($data['icao'] ?? null);
            $status = $this->statusValue($data['status'] ?? null);

            $validator = Validator::make($data, [
                'port_id' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9._-]+$/'],
                'iso2' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
                'iso3' => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
                'iata' => ['nullable', 'required_without:icao', 'string', 'size:3', 'regex:/^[A-Z0-9]{3}$/'],
                'icao' => ['nullable', 'required_without:iata', 'string', 'size:4', 'regex:/^[A-Z0-9]{4}$/'],
                'name' => ['required', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:255'],
                'terminal_type' => ['nullable', 'string', 'max:255'],
                'authority_name' => ['nullable', 'string', 'max:255'],
                'authority_designation' => ['nullable', 'string', 'max:255'],
                'authority_mobile' => ['nullable', 'string', 'max:30'],
                'authority_whatsapp' => ['nullable', 'string', 'max:30'],
                'authority_email' => ['nullable', 'email', 'max:191'],
                'coordinator_name' => ['nullable', 'string', 'max:255'],
                'coordinator_designation' => ['nullable', 'string', 'max:255'],
                'coordinator_mobile' => ['nullable', 'string', 'max:30'],
                'coordinator_whatsapp' => ['nullable', 'string', 'max:30'],
                'coordinator_email' => ['nullable', 'email', 'max:191'],
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

            $this->checkDuplicateCode($data['port_id'], $excelRow, 'Port ID', $seenPortIds, $errors);
            $this->checkDuplicateCode($data['iata'], $excelRow, 'IATA', $seenIata, $errors);
            $this->checkDuplicateCode($data['icao'], $excelRow, 'ICAO', $seenIcao, $errors);

            if ($validator->fails() || $status === null || !$country) {
                continue;
            }

            $matchingIds = Airport::query()
                ->where(function ($query) use ($data) {
                    $query->where('port_id', $data['port_id']);
                    if ($data['iata']) {
                        $query->orWhere('iata', $data['iata']);
                    }
                    if ($data['icao']) {
                        $query->orWhere('icao', $data['icao']);
                    }
                })
                ->pluck('id')
                ->unique();

            if ($matchingIds->count() > 1) {
                $errors[] = "Row {$excelRow}: Port ID '{$data['port_id']}', IATA '{$data['iata']}', and ICAO '{$data['icao']}' belong to different existing airports.";
                continue;
            }

            $prepared[] = [
                'existing_id' => $matchingIds->first(),
                'attributes' => [
                    'port_id' => $data['port_id'],
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
                    'authority_designation' => $this->nullableString($data['authority_designation'] ?? null),
                    'authority_mobile' => $this->contactNumber($data['authority_mobile'] ?? null),
                    'authority_whatsapp' => $this->contactNumber($data['authority_whatsapp'] ?? null),
                    'authority_email' => $this->nullableString($data['authority_email'] ?? null),
                    'coordinator_name' => $this->nullableString($data['coordinator_name'] ?? null),
                    'coordinator_designation' => $this->nullableString($data['coordinator_designation'] ?? null),
                    'coordinator_mobile' => $this->contactNumber($data['coordinator_mobile'] ?? null),
                    'coordinator_whatsapp' => $this->contactNumber($data['coordinator_whatsapp'] ?? null),
                    'coordinator_email' => $this->nullableString($data['coordinator_email'] ?? null),
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

    private function contactNumber($value): ?string
    {
        $value = $this->nullableString($value);

        return $value === null ? null : substr($value, 0, 30);
    }
}
