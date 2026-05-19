<?php

namespace App\Http\Controllers;

use App\Models\BookedTo;
use App\Models\Transport;
use Illuminate\Http\Request;

class BookedToController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:manage_carriers'])->only('index', 'create', 'edit', 'destroy');
    }

    public function index(Request $request)
    {
        $sort_search = $request->search;
        $booked_to = BookedTo::with(['transport', 'creator'])->orderBy('created_at', 'desc');

        if ($sort_search) {
            $booked_to->where(function ($query) use ($sort_search) {
                $query->where('name', 'like', '%' . $sort_search . '%')
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
        $request->validate([
            'transport_id' => 'required|exists:transports,id',
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        BookedTo::create([
            'transport_id' => $request->transport_id,
            'name' => trim($request->name),
            'status' => $request->status,
            'created_by' => auth()->id(),
        ]);

        flash(translate('Booked To has been added successfully'))->success();
        return redirect()->route('booked-to.index');
    }

    public function edit($id)
    {
        $bookedTo = BookedTo::findOrFail($id);
        $transports = Transport::orderBy('name')->get();
        return view('backend.setup_configurations.transport.booked_to.edit', compact('bookedTo', 'transports'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'transport_id' => 'required|exists:transports,id',
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $bookedTo = BookedTo::findOrFail($id);
        $bookedTo->update([
            'transport_id' => $request->transport_id,
            'name' => trim($request->name),
            'status' => $request->status,
        ]);

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
}
