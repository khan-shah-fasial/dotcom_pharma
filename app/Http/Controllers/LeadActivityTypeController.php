<?php

namespace App\Http\Controllers;

use App\Models\LeadActivityType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class LeadActivityTypeController extends Controller
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
        $activityTypes = LeadActivityType::withCount('activities')->orderBy('title')->get();

        return view('backend.leads.masters.activity_types.index', compact('activityTypes'));
    }

    public function store(Request $request)
    {
        LeadActivityType::create($this->validatedData($request));
        Cache::forget('lead_options.activity_types');

        flash(translate('Activity type has been added successfully'))->success();
        return redirect()->route('lead-activity-types.index');
    }

    public function edit(LeadActivityType $activityType)
    {
        return view('backend.leads.masters.activity_types.edit', compact('activityType'));
    }

    public function update(Request $request, LeadActivityType $activityType)
    {
        $activityType->update($this->validatedData($request, $activityType));
        Cache::forget('lead_options.activity_types');

        flash(translate('Activity type has been updated successfully'))->success();
        return redirect()->route('lead-activity-types.index');
    }

    public function destroy(LeadActivityType $activityType)
    {
        if ($activityType->activities()->exists()) {
            flash(translate('Activity type cannot be deleted because activities are using it'))->warning();
            return back();
        }

        $activityType->delete();
        Cache::forget('lead_options.activity_types');

        flash(translate('Activity type has been deleted successfully'))->success();
        return redirect()->route('lead-activity-types.index');
    }

    public function updateStatus(Request $request)
    {
        $activityType = LeadActivityType::findOrFail($request->id);
        $activityType->status = (int) $request->status === 1 ? 1 : 0;

        if ($activityType->save()) {
            Cache::forget('lead_options.activity_types');
            return 1;
        }

        return 0;
    }

    protected function validatedData(Request $request, ?LeadActivityType $activityType = null): array
    {
        $data = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lead_activity_types', 'title')->ignore($activityType?->id),
            ],
            'status' => 'required|in:0,1',
        ]);

        $data['title'] = trim($data['title']);
        $data['status'] = (int) $data['status'];

        return $data;
    }
}
