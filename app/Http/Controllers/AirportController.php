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
        $countryId = $request->input('country_id');
        $status = $request->input('status');

        $airports = Airport::query()
            ->with('country')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($filter) use ($search) {
                    $filter->where('name', 'like', "%{$search}%")
                        ->orWhere('iata', 'like', "%{$search}%")
                        ->orWhere('icao', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('authority_name', 'like', "%{$search}%");
                });
            })
            ->when($countryId, fn ($query) => $query->where('country_id', $countryId))
            ->when(in_array((string) $status, ['0', '1'], true), fn ($query) => $query->where('status', (int) $status))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $countries = Country::query()->orderBy('name')->get(['id', 'name']);

        return view('backend.setup_configurations.logistics.airports.index', compact(
            'airports',
            'countries',
            'search',
            'countryId',
            'status'
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
            'iata' => strtoupper(trim((string) $request->input('iata'))),
            'icao' => strtoupper(trim((string) $request->input('icao'))),
        ]);

        $validated = $request->validate([
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
}
