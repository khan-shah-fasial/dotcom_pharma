<?php

namespace App\Http\Controllers;

use App\Models\LocalDeliveryPartner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocalDeliveryPartnerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:manage_carriers'])->only('index', 'create', 'edit', 'destroy');
    }

    public function index(Request $request)
    {
        $sort_search = $request->search;
        $local_delivery_partners = LocalDeliveryPartner::with('creator')->orderBy('created_at', 'desc');

        if ($sort_search) {
            $local_delivery_partners->where('name', 'like', '%' . $sort_search . '%');
        }

        $local_delivery_partners = $local_delivery_partners->paginate(15);

        return view('backend.setup_configurations.transport.local_delivery_partners.index', compact('local_delivery_partners', 'sort_search'));
    }

    public function create()
    {
        $existingNames = LocalDeliveryPartner::pluck('name')->all();
        return view('backend.setup_configurations.transport.local_delivery_partners.create', compact('existingNames'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'name' => trim((string) $request->name),
        ]);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('local_delivery_partners', 'name'),
            ],
            'status' => 'required|in:active,inactive',
        ]);

        LocalDeliveryPartner::create([
            'name' => $request->name,
            'status' => $request->status,
            'created_by' => auth()->id(),
        ]);

        flash(translate('Local delivery partner has been added successfully'))->success();
        return redirect()->route('local-delivery-partners.index');
    }

    public function edit($id)
    {
        $localDeliveryPartner = LocalDeliveryPartner::findOrFail($id);
        $existingNames = LocalDeliveryPartner::where('id', '!=', $localDeliveryPartner->id)->pluck('name')->all();
        return view('backend.setup_configurations.transport.local_delivery_partners.edit', compact('localDeliveryPartner', 'existingNames'));
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'name' => trim((string) $request->name),
        ]);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('local_delivery_partners', 'name')->ignore($id),
            ],
            'status' => 'required|in:active,inactive',
        ]);

        $localDeliveryPartner = LocalDeliveryPartner::findOrFail($id);
        $localDeliveryPartner->update([
            'name' => $request->name,
            'status' => $request->status,
        ]);

        flash(translate('Local delivery partner has been updated successfully'))->success();
        return redirect()->route('local-delivery-partners.index');
    }

    public function destroy($id)
    {
        LocalDeliveryPartner::destroy($id);
        flash(translate('Local delivery partner has been deleted successfully'))->success();
        return redirect()->route('local-delivery-partners.index');
    }

    public function updateStatus(Request $request)
    {
        $localDeliveryPartner = LocalDeliveryPartner::findOrFail($request->id);
        $localDeliveryPartner->status = (int) $request->status === 1 ? 'active' : 'inactive';
        return $localDeliveryPartner->save() ? 1 : 0;
    }
}
