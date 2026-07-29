<?php

namespace App\Imports;

use App\Imports\Concerns\NormalizesLogisticsImportValues;
use App\Models\Country;
use App\Models\SeaPort;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SeaPortsImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
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
        $seenCodes = [];

        foreach ($rows as $index => $row) {
            $excelRow = $index + 2;
            $data = $this->normalizeRow($row->toArray());
            $data['iso2'] = $this->uppercase($data['iso2'] ?? null);
            $data['iso3'] = $this->uppercase($data['iso3'] ?? null);
            $data['port_id'] = $this->uppercase($data['port_id'] ?? null);
            $data['un_locode'] = $this->uppercase($data['un_locode'] ?? null);
            $status = $this->statusValue($data['status'] ?? null);

            $validator = Validator::make($data, [
                'port_id' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9._-]+$/'],
                'iso2' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
                'iso3' => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
                'name' => ['required', 'string', 'max:255'],
                'un_locode' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
                'continent' => ['nullable', 'string', 'max:255'],
                'port_type' => ['nullable', 'string', 'max:255'],
                'terminal_type' => ['nullable', 'string', 'max:255'],
                'classification' => ['nullable', 'string', 'max:255'],
                'water_body' => ['nullable', 'string', 'max:255'],
                'ocean' => ['nullable', 'string', 'max:255'],
                'state_region' => ['nullable', 'string', 'max:255'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'nearest_airport' => ['nullable', 'string', 'max:255'],
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

            if ($data['port_id'] && isset($seenPortIds[$data['port_id']])) {
                $errors[] = "Row {$excelRow}: Port ID '{$data['port_id']}' is already used in row {$seenPortIds[$data['port_id']]}.";
            } elseif ($data['port_id']) {
                $seenPortIds[$data['port_id']] = $excelRow;
            }

            if ($data['un_locode'] && isset($seenCodes[$data['un_locode']])) {
                $errors[] = "Row {$excelRow}: UN/LOCODE '{$data['un_locode']}' is already used in row {$seenCodes[$data['un_locode']]}.";
            } elseif ($data['un_locode']) {
                $seenCodes[$data['un_locode']] = $excelRow;
            }

            if ($validator->fails() || $status === null || !$country) {
                continue;
            }

            $matchingIds = SeaPort::query()
                ->where('port_id', $data['port_id'])
                ->orWhere('un_locode', $data['un_locode'])
                ->pluck('id')
                ->unique();

            if ($matchingIds->count() > 1) {
                $errors[] = "Row {$excelRow}: Port ID '{$data['port_id']}' and UN/LOCODE '{$data['un_locode']}' belong to different existing sea ports.";
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
                    'continent' => $this->nullableString($data['continent'] ?? null),
                    'name' => trim((string) $data['name']),
                    'un_locode' => $data['un_locode'],
                    'port_type' => $this->nullableString($data['port_type'] ?? null),
                    'terminal_type' => $this->nullableString($data['terminal_type'] ?? null),
                    'classification' => $this->nullableString($data['classification'] ?? null),
                    'water_body' => $this->nullableString($data['water_body'] ?? null),
                    'ocean' => $this->nullableString($data['ocean'] ?? null),
                    'state_region' => $this->nullableString($data['state_region'] ?? null),
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'nearest_airport' => $this->nullableString($data['nearest_airport'] ?? null),
                    'customs_port' => $this->shortValue($data['customs_port'] ?? null),
                    'export_supported' => $this->shortValue($data['export_supported'] ?? null),
                    'import_supported' => $this->shortValue($data['import_supported'] ?? null),
                    'container_supported' => $this->shortValue($data['container_supported'] ?? null),
                    'bulk_cargo_supported' => $this->shortValue($data['bulk_cargo_supported'] ?? null),
                    'liquid_cargo_supported' => $this->shortValue($data['liquid_cargo_supported'] ?? null),
                    'ro_ro_supported' => $this->shortValue($data['ro_ro_supported'] ?? null),
                    'cruise_supported' => $this->shortValue($data['cruise_supported'] ?? null),
                    'ferry_supported' => $this->shortValue($data['ferry_supported'] ?? null),
                    'fishing_supported' => $this->shortValue($data['fishing_supported'] ?? null),
                    'ship_repair_supported' => $this->shortValue($data['ship_repair_supported'] ?? null),
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
                    SeaPort::query()->findOrFail($item['existing_id'])->update($item['attributes']);
                    $this->updated++;
                } else {
                    SeaPort::create($item['attributes']);
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
