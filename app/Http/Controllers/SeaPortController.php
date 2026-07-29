<?php

namespace App\Http\Controllers;

use App\Exports\SeaPortImportSampleExport;
use App\Imports\SeaPortsImport;
use App\Models\Country;
use App\Models\Order;
use App\Models\SeaPort;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class SeaPortController extends Controller
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

        $ports = SeaPort::query()
            ->with('country')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($filter) use ($search) {
                    $filter->where('name', 'like', "%{$search}%")
                        ->orWhere('un_locode', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%")
                        ->orWhere('state_region', 'like', "%{$search}%")
                        ->orWhere('authority_name', 'like', "%{$search}%");
                });
            })
            ->when($countryId, fn ($query) => $query->where('country_id', $countryId))
            ->when(in_array((string) $status, ['0', '1'], true), fn ($query) => $query->where('status', (int) $status))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $countries = Country::query()->orderBy('name')->get(['id', 'name']);

        return view('backend.setup_configurations.logistics.sea_ports.index', compact(
            'ports',
            'countries',
            'search',
            'countryId',
            'status'
        ));
    }

    public function create()
    {
        $countries = Country::query()->orderBy('name')->get(['id', 'name', 'code', 'iso3']);

        return view('backend.setup_configurations.logistics.sea_ports.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $port = SeaPort::create($this->validatedData($request));

        flash(translate('Sea port has been added successfully'))->success();

        return redirect()->route('sea-ports.edit', $port);
    }

    public function edit(SeaPort $seaPort)
    {
        $countries = Country::query()->orderBy('name')->get(['id', 'name', 'code', 'iso3']);

        return view('backend.setup_configurations.logistics.sea_ports.edit', compact('seaPort', 'countries'));
    }

    public function update(Request $request, SeaPort $seaPort)
    {
        $seaPort->update($this->validatedData($request, $seaPort));

        flash(translate('Sea port has been updated successfully'))->success();

        return redirect()->route('sea-ports.index');
    }

    public function destroy(SeaPort $seaPort)
    {
        $isUsed = Order::query()
            ->where('loading_sea_port_id', $seaPort->id)
            ->orWhere('discharge_sea_port_id', $seaPort->id)
            ->exists();

        if ($isUsed) {
            flash(translate('Sea port cannot be deleted because it is used by an order. Deactivate it instead.'))->warning();

            return back();
        }

        $seaPort->delete();
        flash(translate('Sea port has been deleted successfully'))->success();

        return redirect()->route('sea-ports.index');
    }

    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:sea_ports,id'],
            'status' => ['required', 'boolean'],
        ]);

        $port = SeaPort::findOrFail($data['id']);
        $port->status = (bool) $data['status'];

        return $port->save() ? 1 : 0;
    }

    public function import(Request $request)
    {
        $request->validate([
            'bulk_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $import = new SeaPortsImport();
        Excel::import($import, $request->file('bulk_file'));

        flash(translate('Sea ports imported successfully') . ': '
            . $import->createdCount() . ' ' . translate('created') . ', '
            . $import->updatedCount() . ' ' . translate('updated'))->success();

        return redirect()->route('sea-ports.index');
    }

    public function downloadSample()
    {
        return Excel::download(new SeaPortImportSampleExport(), 'sea-port-import-sample.xlsx');
    }

    private function validatedData(Request $request, ?SeaPort $seaPort = null): array
    {
        $request->merge([
            'un_locode' => strtoupper(trim((string) $request->input('un_locode'))),
        ]);

        $validated = $request->validate([
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'name' => ['required', 'string', 'max:255'],
            'un_locode' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique('sea_ports', 'un_locode')->ignore($seaPort?->id),
            ],
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
            'customs_port' => ['nullable', 'string', 'max:20'],
            'export_supported' => ['nullable', 'string', 'max:20'],
            'import_supported' => ['nullable', 'string', 'max:20'],
            'container_supported' => ['nullable', 'string', 'max:20'],
            'bulk_cargo_supported' => ['nullable', 'string', 'max:20'],
            'liquid_cargo_supported' => ['nullable', 'string', 'max:20'],
            'ro_ro_supported' => ['nullable', 'string', 'max:20'],
            'cruise_supported' => ['nullable', 'string', 'max:20'],
            'ferry_supported' => ['nullable', 'string', 'max:20'],
            'fishing_supported' => ['nullable', 'string', 'max:20'],
            'ship_repair_supported' => ['nullable', 'string', 'max:20'],
            'authority_name' => ['nullable', 'string', 'max:255'],
            'authority_contact' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', 'boolean'],
        ]);

        $country = Country::findOrFail($validated['country_id']);
        $validated['country'] = $country->name;
        $validated['iso2'] = strtoupper((string) $country->code);
        $validated['iso3'] = $country->iso3 ? strtoupper((string) $country->iso3) : null;

        return $validated;
    }
}
