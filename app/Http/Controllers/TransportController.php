<?php

namespace App\Http\Controllers;

use App\Models\Transport;
use Illuminate\Http\Request;

class TransportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:manage_carriers'])->only('index', 'create', 'edit', 'destroy');
    }

    public function index(Request $request)
    {
        $sort_search = $request->search;
        $allowedSorts = ['name', 'mode', 'url', 'created_by', 'status'];
        $sortBy = in_array((string) $request->get('sort_by'), $allowedSorts, true) ? (string) $request->get('sort_by') : '';
        $sortDir = strtolower((string) $request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $filters = [
            'name' => trim((string) $request->get('name', '')),
            'mode' => (string) $request->get('mode', ''),
            'url' => trim((string) $request->get('url', '')),
            'created_by' => trim((string) $request->get('created_by', '')),
            'status' => (string) $request->get('status', ''),
        ];

        $transports = Transport::with('creator');

        if ($sort_search) {
            $transports->where('name', 'like', '%' . $sort_search . '%');
        }
        if ($filters['name'] !== '') {
            $transports->where('transports.name', 'like', '%' . $filters['name'] . '%');
        }
        if (in_array($filters['mode'], ['surface', 'sea', 'air'], true)) {
            $transports->where('transports.mode', $filters['mode']);
        }
        if ($filters['url'] !== '') {
            $transports->where('transports.url', 'like', '%' . $filters['url'] . '%');
        }
        if ($filters['created_by'] !== '') {
            $createdBy = $filters['created_by'];
            $transports->whereHas('creator', function ($query) use ($createdBy) {
                $query->where('name', 'like', '%' . $createdBy . '%');
            });
        }
        if (in_array($filters['status'], ['active', 'inactive'], true)) {
            $transports->where('transports.status', $filters['status']);
        }

        if ($sortBy === 'created_by') {
            $transports->orderByRaw('(select name from users where users.id = transports.created_by limit 1) ' . $sortDir);
        } elseif ($sortBy !== '') {
            $transports->orderBy('transports.' . $sortBy, $sortDir);
        } else {
            $transports->orderBy('transports.created_at', 'desc');
        }
        $transports->orderBy('transports.id', 'desc');

        $transports = $transports->paginate(15)->appends($request->query());

        return view('backend.setup_configurations.transport.transports.index', compact('transports', 'sort_search', 'filters', 'sortBy', 'sortDir'));
    }

    public function create()
    {
        return view('backend.setup_configurations.transport.transports.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'mode' => 'required|in:surface,sea,air',
            'url' => 'nullable|url|max:500',
            'status' => 'required|in:active,inactive',
        ]);

        Transport::create([
            'name' => trim($request->name),
            'mode' => $request->mode,
            'url' => $request->filled('url') ? trim($request->url) : null,
            'status' => $request->status,
            'created_by' => auth()->id(),
        ]);

        flash(translate('Transport has been added successfully'))->success();
        return redirect()->route('transports.index');
    }

    public function edit($id)
    {
        $transport = Transport::findOrFail($id);
        return view('backend.setup_configurations.transport.transports.edit', compact('transport'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'mode' => 'required|in:surface,sea,air',
            'url' => 'nullable|url|max:500',
            'status' => 'required|in:active,inactive',
        ]);

        $transport = Transport::findOrFail($id);
        $transport->update([
            'name' => trim($request->name),
            'mode' => $request->mode,
            'url' => $request->filled('url') ? trim($request->url) : null,
            'status' => $request->status,
        ]);

        flash(translate('Transport has been updated successfully'))->success();
        return redirect()->route('transports.index');
    }

    public function destroy($id)
    {
        $transport = Transport::findOrFail($id);

        if ($transport->bookedTo()->exists()) {
            flash(translate('Transport cannot be deleted because booked to records exist'))->warning();
            return back();
        }

        $transport->delete();
        flash(translate('Transport has been deleted successfully'))->success();
        return redirect()->route('transports.index');
    }

    public function updateStatus(Request $request)
    {
        $transport = Transport::findOrFail($request->id);
        $transport->status = (int) $request->status === 1 ? 'active' : 'inactive';
        return $transport->save() ? 1 : 0;
    }
}
