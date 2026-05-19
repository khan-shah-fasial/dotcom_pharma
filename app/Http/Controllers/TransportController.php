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
        $transports = Transport::with('creator')->orderBy('created_at', 'desc');

        if ($sort_search) {
            $transports->where('name', 'like', '%' . $sort_search . '%');
        }

        $transports = $transports->paginate(15);

        return view('backend.setup_configurations.transport.transports.index', compact('transports', 'sort_search'));
    }

    public function create()
    {
        return view('backend.setup_configurations.transport.transports.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        Transport::create([
            'name' => trim($request->name),
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
            'status' => 'required|in:active,inactive',
        ]);

        $transport = Transport::findOrFail($id);
        $transport->update([
            'name' => trim($request->name),
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
