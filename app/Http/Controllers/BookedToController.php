<?php

namespace App\Http\Controllers;

use App\Models\BookedTo;
use App\Models\Transport;
use Illuminate\Http\Request;

class BookedToController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:manage_carriers'])->only('index', 'create', 'show', 'edit', 'destroy');
    }

    public function index(Request $request)
    {
        $sort_search = $request->search;
        $booked_to = BookedTo::with(['transport', 'creator', 'scannerUpload'])->orderBy('created_at', 'desc');

        if ($sort_search) {
            $booked_to->where(function ($query) use ($sort_search) {
                $query->where('name', 'like', '%' . $sort_search . '%')
                    ->orWhere('branch_name', 'like', '%' . $sort_search . '%')
                    ->orWhere('branch_address', 'like', '%' . $sort_search . '%')
                    ->orWhere('branch_code', 'like', '%' . $sort_search . '%')
                    ->orWhere('branch_gst_number', 'like', '%' . $sort_search . '%')
                    ->orWhere('branch_mobile_number', 'like', '%' . $sort_search . '%')
                    ->orWhere('branch_alternate_mobile_number', 'like', '%' . $sort_search . '%')
                    ->orWhere('contact_incharge', 'like', '%' . $sort_search . '%')
                    ->orWhere('branch_email', 'like', '%' . $sort_search . '%')
                    ->orWhereHas('transport', function ($q) use ($sort_search) {
                        $q->where('name', 'like', '%' . $sort_search . '%');
                    });
            });
        }

        $booked_to = $booked_to->paginate(15);

        return view('backend.setup_configurations.transport.booked_to.index', compact('booked_to', 'sort_search'));
    }

    public function create()
    {
        $transports = Transport::orderBy('name')->get();
        return view('backend.setup_configurations.transport.booked_to.create', compact('transports'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['created_by'] = auth()->id();

        BookedTo::create($data);

        flash(translate('Booked To has been added successfully'))->success();
        return redirect()->route('booked-to.index');
    }

    public function show($id)
    {
        $bookedTo = BookedTo::with(['transport', 'creator', 'scannerUpload'])->findOrFail($id);

        return view('backend.setup_configurations.transport.booked_to.show', compact('bookedTo'));
    }

    public function edit($id)
    {
        $bookedTo = BookedTo::findOrFail($id);
        $transports = Transport::orderBy('name')->get();
        return view('backend.setup_configurations.transport.booked_to.edit', compact('bookedTo', 'transports'));
    }

    public function update(Request $request, $id)
    {
        $bookedTo = BookedTo::findOrFail($id);
        $bookedTo->update($this->validatedData($request));

        flash(translate('Booked To has been updated successfully'))->success();
        return redirect()->route('booked-to.index');
    }

    public function destroy($id)
    {
        BookedTo::destroy($id);
        flash(translate('Booked To has been deleted successfully'))->success();
        return redirect()->route('booked-to.index');
    }

    public function updateStatus(Request $request)
    {
        $bookedTo = BookedTo::findOrFail($request->id);
        $bookedTo->status = (int) $request->status === 1 ? 'active' : 'inactive';
        return $bookedTo->save() ? 1 : 0;
    }

    protected function validatedData(Request $request): array
    {
        if (!$request->filled('name') && $request->filled('location')) {
            $request->merge(['name' => $request->input('location')]);
        }

        foreach ([
            'name',
            'branch_name',
            'branch_address',
            'branch_code',
            'branch_gst_number',
            'branch_mobile_number',
            'branch_alternate_mobile_number',
            'contact_incharge',
            'branch_email',
            'scanner',
        ] as $field) {
            $value = trim((string) $request->input($field));
            $request->merge([$field => $value === '' ? null : $value]);
        }

        $data = $request->validate([
            'transport_id' => 'required|exists:transports,id',
            'name' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'branch_address' => 'nullable|string|max:2000',
            'branch_code' => 'nullable|string|max:255',
            'branch_gst_number' => 'nullable|string|max:255',
            'branch_mobile_number' => 'nullable|string|max:50',
            'branch_alternate_mobile_number' => 'nullable|string|max:50',
            'contact_incharge' => 'nullable|string|max:255',
            'branch_email' => 'nullable|email|max:255',
            'scanner' => 'nullable|integer|exists:uploads,id',
            'status' => 'required|in:active,inactive',
        ]);

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $data[$key] = $value === '' ? null : $value;
            }
        }

        return $data;
    }
}
