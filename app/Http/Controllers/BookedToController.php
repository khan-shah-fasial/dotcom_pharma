<?php

namespace App\Http\Controllers;

use App\Models\BookedTo;
use App\Models\Transport;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BookedToController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:manage_carriers'])->only('index', 'create', 'show', 'edit', 'destroy');
    }

    public function index(Request $request)
    {
        $sort_search = $request->search;
        $allowedSorts = [
            'transport', 'location', 'branch_name', 'branch_address', 'branch_code', 'branch_gst_number',
            'branch_mobile_number', 'branch_alternate_mobile_number', 'contact_incharge', 'branch_email',
            'scanner', 'created_by', 'status',
        ];
        $sortBy = in_array((string) $request->get('sort_by'), $allowedSorts, true) ? (string) $request->get('sort_by') : '';
        $sortDir = strtolower((string) $request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $filters = [
            'transport' => trim((string) $request->get('transport', '')),
            'location' => trim((string) $request->get('location', '')),
            'branch_name' => trim((string) $request->get('branch_name', '')),
            'branch_address' => trim((string) $request->get('branch_address', '')),
            'branch_code' => trim((string) $request->get('branch_code', '')),
            'branch_gst_number' => trim((string) $request->get('branch_gst_number', '')),
            'branch_mobile_number' => trim((string) $request->get('branch_mobile_number', '')),
            'branch_alternate_mobile_number' => trim((string) $request->get('branch_alternate_mobile_number', '')),
            'contact_incharge' => trim((string) $request->get('contact_incharge', '')),
            'branch_email' => trim((string) $request->get('branch_email', '')),
            'scanner' => (string) $request->get('scanner', ''),
            'created_by' => trim((string) $request->get('created_by', '')),
            'status' => (string) $request->get('status', ''),
        ];

        $booked_to = BookedTo::with(['transport', 'creator', 'scannerUpload']);

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

        $likeColumns = [
            'location' => 'booked_to.name',
            'branch_name' => 'booked_to.branch_name',
            'branch_address' => 'booked_to.branch_address',
            'branch_code' => 'booked_to.branch_code',
            'branch_gst_number' => 'booked_to.branch_gst_number',
            'branch_mobile_number' => 'booked_to.branch_mobile_number',
            'branch_alternate_mobile_number' => 'booked_to.branch_alternate_mobile_number',
            'contact_incharge' => 'booked_to.contact_incharge',
            'branch_email' => 'booked_to.branch_email',
        ];
        foreach ($likeColumns as $key => $column) {
            if ($filters[$key] !== '') {
                $booked_to->where($column, 'like', '%' . $filters[$key] . '%');
            }
        }
        if ($filters['transport'] !== '') {
            $transportName = $filters['transport'];
            $booked_to->whereHas('transport', function ($query) use ($transportName) {
                $query->where('name', 'like', '%' . $transportName . '%');
            });
        }
        if ($filters['created_by'] !== '') {
            $createdBy = $filters['created_by'];
            $booked_to->whereHas('creator', function ($query) use ($createdBy) {
                $query->where('name', 'like', '%' . $createdBy . '%');
            });
        }
        if ($filters['scanner'] === '1') {
            $booked_to->whereNotNull('booked_to.scanner')->where('booked_to.scanner', '!=', '');
        } elseif ($filters['scanner'] === '0') {
            $booked_to->where(function ($query) {
                $query->whereNull('booked_to.scanner')->orWhere('booked_to.scanner', '');
            });
        }
        if (in_array($filters['status'], ['active', 'inactive'], true)) {
            $booked_to->where('booked_to.status', $filters['status']);
        }

        if ($sortBy === 'transport') {
            $booked_to->orderByRaw('(select name from transports where transports.id = booked_to.transport_id limit 1) ' . $sortDir);
        } elseif ($sortBy === 'location') {
            $booked_to->orderBy('booked_to.name', $sortDir);
        } elseif ($sortBy === 'created_by') {
            $booked_to->orderByRaw('(select name from users where users.id = booked_to.created_by limit 1) ' . $sortDir);
        } elseif ($sortBy === 'scanner') {
            $booked_to->orderByRaw("case when booked_to.scanner is null or booked_to.scanner = '' then 1 else 0 end " . $sortDir);
        } elseif ($sortBy !== '') {
            $booked_to->orderBy('booked_to.' . $sortBy, $sortDir);
        } else {
            $booked_to->orderBy('booked_to.created_at', 'desc');
        }
        $booked_to->orderBy('booked_to.id', 'desc');

        $booked_to = $booked_to->paginate(15)->appends($request->query());

        return view('backend.setup_configurations.transport.booked_to.index', compact('booked_to', 'sort_search', 'filters', 'sortBy', 'sortDir'));
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
        if ($request->hasFile('scanner')) {
            $data['scanner'] = $this->storeScannerUpload($request);
        }

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
        $data = $this->validatedData($request);
        if ($request->hasFile('scanner')) {
            $data['scanner'] = $this->storeScannerUpload($request);
        }

        $bookedTo->update($data);

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
            'scanner' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt',
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

    protected function storeScannerUpload(Request $request): int
    {
        $file = $request->file('scanner');
        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $targetDir = 'uploads/all/' . date('Y/m');
        $path = $file->store($targetDir, 'local');

        $types = [
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'webp' => 'image',
            'pdf' => 'document',
            'doc' => 'document',
            'docx' => 'document',
            'xls' => 'document',
            'xlsx' => 'document',
            'csv' => 'document',
            'txt' => 'document',
        ];

        $upload = new Upload();
        $upload->file_original_name = $originalName;
        $upload->file_name = $path;
        $upload->user_id = auth()->id();
        $upload->extension = $extension;
        $upload->type = $types[$extension] ?? 'document';
        $upload->file_size = $file->getSize();

        if (Schema::hasColumn('uploads', 'disk')) {
            $upload->disk = 'local';
        }

        $upload->save();

        return $upload->id;
    }
}
