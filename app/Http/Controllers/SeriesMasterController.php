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
                'billColumnsMissing' => true,
                'series' => null,
                'filters' => [],
                'sortBy' => '',
                'sortDir' => 'asc',
            ]);
        }

        $billColumnsReady = $this->billColumnsReady();
        $filters = [
            'id' => trim((string) $request->input('id', '')),
            'name' => trim((string) $request->input('name', '')),
            'code' => trim((string) $request->input('code', '')),
            'payment_type' => trim((string) $request->input('payment_type', '')),
            'total_bills' => trim((string) $request->input('total_bills', '')),
            'from_bill_no' => trim((string) $request->input('from_bill_no', '')),
            'to_bill_no' => trim((string) $request->input('to_bill_no', '')),
            'description' => trim((string) $request->input('description', '')),
            'status' => (string) $request->input('status', ''),
            'created_from' => trim((string) $request->input('created_from', '')),
            'created_to' => trim((string) $request->input('created_to', '')),
            'updated_from' => trim((string) $request->input('updated_from', '')),
            'updated_to' => trim((string) $request->input('updated_to', '')),
        ];
        if (!$billColumnsReady) {
            $filters['payment_type'] = '';
            $filters['total_bills'] = '';
            $filters['from_bill_no'] = '';
            $filters['to_bill_no'] = '';
        }
        $allowedSorts = ['id', 'name', 'code', 'description', 'status', 'created_at', 'updated_at'];
        if ($billColumnsReady) {
            $allowedSorts = array_merge($allowedSorts, ['payment_type', 'total_bills', 'from_bill_no', 'to_bill_no']);
        }
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
        if ($billColumnsReady) {
            if ($filters['payment_type'] === 'cash' || $filters['payment_type'] === 'credit') {
                $series->where('payment_type', $filters['payment_type']);
            } elseif ($filters['payment_type'] === '__not_in_list__') {
                $series->where(function ($query) {
                    if (Schema::hasColumn('series_masters', 'payment_type_custom')) {
                        $query->where(function ($custom) {
                            $custom->whereNotNull('payment_type_custom')->where('payment_type_custom', '!=', '');
                        });
                    }
                    $query->orWhere(function ($manual) {
                        $manual->whereNotNull('payment_type')->whereNotIn('payment_type', ['cash', 'credit']);
                    });
                });
            }
            if ($filters['total_bills'] !== '' && ctype_digit($filters['total_bills'])) {
                $series->where('total_bills', (int) $filters['total_bills']);
            }
            if ($filters['from_bill_no'] !== '') {
                $series->where('from_bill_no', 'like', '%' . $filters['from_bill_no'] . '%');
            }
            if ($filters['to_bill_no'] !== '') {
                $series->where('to_bill_no', 'like', '%' . $filters['to_bill_no'] . '%');
            }
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
            'billColumnsMissing' => !$billColumnsReady,
            'series' => $series,
            'filters' => $filters,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
        ]);
    }

    public function create()
    {
        $this->ensureTable();

        return view('backend.series.create', [
            'billColumnsMissing' => !$this->billColumnsReady(),
        ]);
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
        return view('backend.series.edit', [
            'seriesItem' => $seriesMaster,
            'billColumnsMissing' => !$this->billColumnsReady(),
        ]);
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
        $billColumnsReady = $this->billColumnsReady();
        if ($billColumnsReady) {
            $request->merge([
                'payment_type' => $this->blankToNull($request->input('payment_type')),
                'payment_type_manual' => $this->blankToNull($request->input('payment_type_manual')),
                'total_bills' => $this->blankToNull($request->input('total_bills')),
                'from_bill_no' => $this->blankToNull($request->input('from_bill_no')),
                'to_bill_no' => $this->blankToNull($request->input('to_bill_no')),
            ]);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('series_masters', 'code')->ignore($series?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'boolean'],
        ];
        if ($billColumnsReady) {
            $rules['payment_type'] = ['nullable', Rule::in(array_merge(array_keys(SeriesMaster::PAYMENT_TYPES), ['__not_in_list__']))];
            $rules['payment_type_manual'] = ['nullable', 'required_if:payment_type,__not_in_list__', 'string', 'max:100'];
            $rules['total_bills'] = ['nullable', 'integer', 'min:0'];
            $rules['from_bill_no'] = ['nullable', 'string', 'max:50'];
            $rules['to_bill_no'] = ['nullable', 'string', 'max:50'];
        }

        $data = $request->validate($rules);
        $data['status'] = $request->boolean('status') ? 1 : 0;
        $data['code'] = trim((string) ($data['code'] ?? '')) === '' ? null : trim((string) $data['code']);

        if ($billColumnsReady) {
            $paymentType = $data['payment_type'] ?? null;
            $manual = trim((string) ($data['payment_type_manual'] ?? ''));
            $hasCustomColumn = Schema::hasColumn('series_masters', 'payment_type_custom');
            if ($paymentType === '__not_in_list__') {
                if ($hasCustomColumn) {
                    $data['payment_type'] = null;
                    $data['payment_type_custom'] = $manual !== '' ? $manual : null;
                } else {
                    $data['payment_type'] = $manual !== '' ? $manual : null;
                }
            } elseif ($hasCustomColumn) {
                $data['payment_type_custom'] = null;
            }
            unset($data['payment_type_manual']);
            $data['from_bill_no'] = $this->blankToNull($data['from_bill_no'] ?? null);
            $data['to_bill_no'] = $this->blankToNull($data['to_bill_no'] ?? null);
            $data['total_bills'] = ($data['total_bills'] ?? null) === null || $data['total_bills'] === ''
                ? null
                : (int) $data['total_bills'];
        }

        return $data;
    }

    private function blankToNull($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function billColumnsReady(): bool
    {
        return Schema::hasTable('series_masters')
            && Schema::hasColumn('series_masters', 'payment_type')
            && Schema::hasColumn('series_masters', 'total_bills')
            && Schema::hasColumn('series_masters', 'from_bill_no')
            && Schema::hasColumn('series_masters', 'to_bill_no');
    }

    private function ensureTable(): void
    {
        if (!Schema::hasTable('series_masters')) {
            abort(404);
        }
    }
}
