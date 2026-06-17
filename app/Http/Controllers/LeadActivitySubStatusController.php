<?php

namespace App\Http\Controllers;

use App\Models\LeadActivitySubStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadActivitySubStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_leads'])->only('index');
        $this->middleware(['permission:add_lead'])->only('store');
        $this->middleware(['permission:edit_lead'])->only(['edit', 'update', 'updateStatus']);
        $this->middleware(['permission:delete_lead'])->only('destroy');
    }

    public function index()
    {
        $subStatuses = LeadActivitySubStatus::withCount('activities')->orderBy('title')->get();

        return view('backend.leads.masters.activity_sub_statuses.index', compact('subStatuses'));
    }

    public function store(Request $request)
    {
        LeadActivitySubStatus::create($this->validatedData($request));

        flash(translate('Activity sub status has been added successfully'))->success();
        return redirect()->route('lead-activity-sub-statuses.index');
    }

    public function edit(LeadActivitySubStatus $subStatus)
    {
        return view('backend.leads.masters.activity_sub_statuses.edit', compact('subStatus'));
    }

    public function update(Request $request, LeadActivitySubStatus $subStatus)
    {
        $subStatus->update($this->validatedData($request, $subStatus));

        flash(translate('Activity sub status has been updated successfully'))->success();
        return redirect()->route('lead-activity-sub-statuses.index');
    }

    public function destroy(LeadActivitySubStatus $subStatus)
    {
        if ($subStatus->activities()->exists()) {
            flash(translate('Activity sub status cannot be deleted because activities are using it'))->warning();
            return back();
        }

        $subStatus->delete();

        flash(translate('Activity sub status has been deleted successfully'))->success();
        return redirect()->route('lead-activity-sub-statuses.index');
    }

    public function updateStatus(Request $request)
    {
        $subStatus = LeadActivitySubStatus::findOrFail($request->id);
        $subStatus->status = (int) $request->status === 1 ? 1 : 0;

        return $subStatus->save() ? 1 : 0;
    }

    protected function validatedData(Request $request, ?LeadActivitySubStatus $subStatus = null): array
    {
        $data = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lead_activity_sub_statuses', 'title')->ignore($subStatus?->id),
            ],
            'status' => 'required|in:0,1',
        ]);

        $data['title'] = trim($data['title']);
        $data['status'] = (int) $data['status'];

        return $data;
    }
}
