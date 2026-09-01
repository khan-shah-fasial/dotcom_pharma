<?php

namespace App\Http\Controllers;

use App\Exports\AirportImportSampleExport;
use App\Imports\AirportsImport;
use App\Models\Airport;
use App\Models\Country;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AirportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:manage_carriers']);
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $countryId = requested_or_detected_country_id($request);
        $status = $request->input('status');
        $city = trim((string) $request->input('city'));
        $terminalType = trim((string) $request->input('terminal_type'));
        $cargoAirport = trim((string) $request->input('cargo_airport'));
        $customsAirport = trim((string) $request->input('customs_airport'));
        $coldChain = trim((string) $request->input('cold_chain_facility'));
        if (!$request->has('country_id') && $countryId) {
            return redirect()->to($request->fullUrlWithQuery(['country_id' => $countryId]));
        }
        [$sortBy, $sortOrder] = $this->resolveSort($request, [
            'name', 'port_id', 'iata', 'icao', 'country', 'city', 'terminal_type', 'status', 'created_at',
        ], 'name');

        $airports = Airport::query()
            ->with('country')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($filter) use ($search) {
                    $filter->where('port_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('iata', 'like', "%{$search}%")
                        ->orWhere('icao', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('terminal_type', 'like', "%{$search}%")
                        ->orWhere('authority_name', 'like', "%{$search}%")
                        ->orWhere('authority_mobile', 'like', "%{$search}%")
                        ->orWhere('authority_email', 'like', "%{$search}%")
                        ->orWhere('coordinator_name', 'like', "%{$search}%")
                        ->orWhere('coordinator_mobile', 'like', "%{$search}%")
                        ->orWhere('coordinator_email', 'like', "%{$search}%");
                });
            })
            ->when($countryId, fn ($query) => $query->where('country_id', $countryId))
            ->when(in_array((string) $status, ['0', '1'], true), fn ($query) => $query->where('status', (int) $status))
            ->when($city !== '', fn ($query) => $query->where('city', $city))
            ->when($terminalType !== '', fn ($query) => $query->where('terminal_type', $terminalType))
            ->when($cargoAirport !== '', fn ($query) => $query->where('cargo_airport', $cargoAirport))
            ->when($customsAirport !== '', fn ($query) => $query->where('customs_airport', $customsAirport))
            ->when($coldChain !== '', fn ($query) => $query->where('cold_chain_facility', $coldChain))
            ->orderBy($sortBy, $sortOrder)
            ->orderBy('id')
            ->paginate(20)
            ->appends($request->except('page') + [
                'country_id' => $countryId,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ]);

        $countries = Country::query()->orderBy('name')->get(['id', 'name']);
        $facilityOptions = ['Yes', 'No', 'Limited', 'N/A'];
        $cities = $this->distinctValues('city');
        $terminalTypes = $this->distinctValues('terminal_type');

        return view('backend.setup_configurations.logistics.airports.index', compact(
            'airports',
            'countries',
            'search',
            'countryId',
            'status',
            'city',
            'terminalType',
            'cargoAirport',
            'customsAirport',
            'coldChain',
            'facilityOptions',
            'cities',
            'terminalTypes',
            'sortBy',
            'sortOrder'
        ));
    }

    public function create()
    {
        $countries = Country::query()->orderBy('name')->get(['id', 'name', 'code', 'iso3']);

        return view('backend.setup_configurations.logistics.airports.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $airport = Airport::create($this->validatedData($request));

        flash(translate('Airport has been added successfully'))->success();

        return redirect()->route('airports.edit', $airport);
    }

    public function edit(Airport $airport)
    {
        $countries = Country::query()->orderBy('name')->get(['id', 'name', 'code', 'iso3']);

        return view('backend.setup_configurations.logistics.airports.edit', compact('airport', 'countries'));
    }

    public function update(Request $request, Airport $airport)
    {
        $airport->update($this->validatedData($request, $airport));

        flash(translate('Airport has been updated successfully'))->success();

        return redirect()->route('airports.index');
    }

    public function destroy(Airport $airport)
    {
        $isUsed = Order::query()
            ->where('loading_airport_id', $airport->id)
            ->orWhere('discharge_airport_id', $airport->id)
            ->exists();

        if ($isUsed) {
            flash(translate('Airport cannot be deleted because it is used by an order. Deactivate it instead.'))->warning();

            return back();
        }

        $airport->delete();
        flash(translate('Airport has been deleted successfully'))->success();

        return redirect()->route('airports.index');
    }

    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:airports,id'],
            'status' => ['required', 'boolean'],
        ]);

        $airport = Airport::findOrFail($data['id']);
        $airport->status = (bool) $data['status'];

        return $airport->save() ? 1 : 0;
    }

    public function import(Request $request)
    {
        $request->validate([
            'bulk_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $import = new AirportsImport();
        Excel::import($import, $request->file('bulk_file'));

        flash(translate('Airports imported successfully') . ': '
            . $import->createdCount() . ' ' . translate('created') . ', '
            . $import->updatedCount() . ' ' . translate('updated'))->success();

        return redirect()->route('airports.index');
    }

    public function downloadSample()
    {
        return Excel::download(new AirportImportSampleExport(), 'airport-import-sample.xlsx');
    }

    private function validatedData(Request $request, ?Airport $airport = null): array
    {
        $request->merge([
            'port_id' => strtoupper(trim((string) $request->input('port_id'))),
            'iata' => strtoupper(trim((string) $request->input('iata'))),
            'icao' => strtoupper(trim((string) $request->input('icao'))),
        ]);

        $validated = $request->validate([
            'port_id' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9._-]+$/',
                Rule::unique('airports', 'port_id')->ignore($airport?->id),
            ],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'iata' => [
                'nullable',
                'required_without:icao',
                'string',
                'size:3',
                'regex:/^[A-Z0-9]{3}$/',
                Rule::unique('airports', 'iata')->ignore($airport?->id),
            ],
            'icao' => [
                'nullable',
                'required_without:iata',
                'string',
                'size:4',
                'regex:/^[A-Z0-9]{4}$/',
                Rule::unique('airports', 'icao')->ignore($airport?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'terminal_type' => ['nullable', 'string', 'max:255'],
            'cargo_airport' => ['nullable', 'string', 'max:20'],
            'customs_airport' => ['nullable', 'string', 'max:20'],
            'cold_chain_facility' => ['nullable', 'string', 'max:20'],
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
            'status' => ['required', 'boolean'],
        ]);

        $country = Country::findOrFail($validated['country_id']);
        $validated['country'] = $country->name;
        $validated['iso2'] = strtoupper((string) $country->code);
        $validated['iso3'] = $country->iso3 ? strtoupper((string) $country->iso3) : null;
        $validated['iata'] = $validated['iata'] ?: null;
        $validated['icao'] = $validated['icao'] ?: null;

        return $validated;
    }

    private function resolveSort(Request $request, array $allowed, string $default): array
    {
        $sortBy = in_array($request->input('sort_by'), $allowed, true) ? $request->input('sort_by') : $default;
        $sortOrder = $request->input('sort_order') === 'desc' ? 'desc' : 'asc';

        return [$sortBy, $sortOrder];
    }

    private function distinctValues(string $column)
    {
        return Airport::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column);
    }
}
