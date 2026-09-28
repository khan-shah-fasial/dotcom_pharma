<?php

namespace App\Http\Controllers;

use App\Models\SeriesMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SeriesMasterController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_all_companies'])->only('index');
        $this->middleware(['permission:add_customer'])->only(['create', 'store']);
        $this->middleware(['permission:view_all_customers'])->only(['edit', 'update']);
        $this->middleware(['permission:delete_customer'])->only('destroy');
    }

    public function index(Request $request)
    {
        if (!Schema::hasTable('series_masters')) {
            return view('backend.series.index', [
                'tableMissing' => true,
                'series' => null,
                'filters' => [],
                'sortBy' => '',
                'sortDir' => 'asc',
            ]);
        }

        $filters = [
            'id' => trim((string) $request->input('id', '')),
            'name' => trim((string) $request->input('name', '')),
            'code' => trim((string) $request->input('code', '')),
            'description' => trim((string) $request->input('description', '')),
            'status' => (string) $request->input('status', ''),
            'created_from' => trim((string) $request->input('created_from', '')),
            'created_to' => trim((string) $request->input('created_to', '')),
            'updated_from' => trim((string) $request->input('updated_from', '')),
            'updated_to' => trim((string) $request->input('updated_to', '')),
        ];
        $allowedSorts = ['id', 'name', 'code', 'description', 'status', 'created_at', 'updated_at'];
        $sortBy = in_array((string) $request->input('sort_by'), $allowedSorts, true) ? (string) $request->input('sort_by') : 'name';
        $sortDir = strtolower((string) $request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $series = SeriesMaster::query();
        if ($filters['id'] !== '' && ctype_digit($filters['id'])) {
            $series->where('id', (int) $filters['id']);
        }
        if ($filters['name'] !== '') {
            $series->where('name', 'like', '%' . $filters['name'] . '%');
        }
        if ($filters['code'] !== '') {
            $series->where('code', 'like', '%' . $filters['code'] . '%');
        }
        if ($filters['description'] !== '') {
            $series->where('description', 'like', '%' . $filters['description'] . '%');
        }
        if ($filters['status'] !== '') {
            $series->where('status', $filters['status'] === '1' ? 1 : 0);
        }
        if ($filters['created_from'] !== '') {
            $series->whereDate('created_at', '>=', $filters['created_from']);
        }
        if ($filters['created_to'] !== '') {
            $series->whereDate('created_at', '<=', $filters['created_to']);
        }
        if ($filters['updated_from'] !== '') {
            $series->whereDate('updated_at', '>=', $filters['updated_from']);
        }
        if ($filters['updated_to'] !== '') {
            $series->whereDate('updated_at', '<=', $filters['updated_to']);
        }
        $series = $series->orderBy($sortBy, $sortDir)->orderBy('id')->paginate(15)->appends($request->query());

        return view('backend.series.index', [
            'tableMissing' => false,
            'series' => $series,
            'filters' => $filters,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
        ]);
    }

    public function create()
    {
        $this->ensureTable();

        return view('backend.series.create');
    }

    public function store(Request $request)
    {
        $this->ensureTable();
        $data = $this->validated($request);
        SeriesMaster::create($data);
        flash(translate('Series has been added successfully'))->success();

        return redirect()->route('series.index');
    }

    public function edit(SeriesMaster $seriesMaster)
    {
        return view('backend.series.edit', ['seriesItem' => $seriesMaster]);
    }

    public function update(Request $request, SeriesMaster $seriesMaster)
    {
        $seriesMaster->update($this->validated($request, $seriesMaster));
        flash(translate('Series has been updated successfully'))->success();

        return redirect()->route('series.index');
    }

    public function destroy(SeriesMaster $seriesMaster)
    {
        $seriesMaster->delete();
        flash(translate('Series has been deleted successfully'))->success();

        return redirect()->route('series.index');
    }

    private function validated(Request $request, ?SeriesMaster $series = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('series_masters', 'code')->ignore($series?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'boolean'],
        ]);
        $data['status'] = $request->boolean('status') ? 1 : 0;
        $data['code'] = trim((string) ($data['code'] ?? '')) === '' ? null : trim((string) $data['code']);

        return $data;
    }

    private function ensureTable(): void
    {
        if (!Schema::hasTable('series_masters')) {
            abort(404);
        }
    }
}
