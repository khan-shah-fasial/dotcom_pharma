<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    protected array $activityTypes = ['call', 'email', 'meeting', 'whatsapp', 'note'];

    public function __construct()
    {
        $this->middleware(['permission:view_leads'])->only(['index', 'show']);
        $this->middleware(['permission:add_lead'])->only(['create', 'store']);
        $this->middleware(['permission:edit_lead'])->only(['edit', 'update', 'storeActivity']);
        $this->middleware(['permission:delete_lead'])->only('destroy');
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'search',
            'source_id',
            'status_id',
            'assigned_to',
            'expected_value_min',
            'expected_value_max',
            'created_from',
            'created_to',
            'next_followup_from',
            'next_followup_to',
            'activity_type',
        ]);

        $leads = Lead::with(['source', 'status', 'assignedUser', 'creator', 'activities' => function ($query) {
            $query->latest();
        }])->latest();

        $this->applyLeadVisibility($leads);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $leads->where(function ($query) use ($search) {
                $query->where('lead_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        foreach (['source_id', 'status_id', 'assigned_to'] as $field) {
            if ($request->filled($field)) {
                $leads->where($field, $request->input($field));
            }
        }

        if ($request->filled('expected_value_min')) {
            $leads->where('expected_value', '>=', (float) $request->expected_value_min);
        }

        if ($request->filled('expected_value_max')) {
            $leads->where('expected_value', '<=', (float) $request->expected_value_max);
        }

        if ($request->filled('created_from')) {
            $leads->whereDate('created_at', '>=', $request->created_from);
        }

        if ($request->filled('created_to')) {
            $leads->whereDate('created_at', '<=', $request->created_to);
        }

        if ($request->filled('activity_type')) {
            $leads->whereHas('activities', function ($query) use ($request) {
                $query->where('activity_type', $request->activity_type);
            });
        }

        if ($request->filled('next_followup_from') || $request->filled('next_followup_to')) {
            $leads->whereHas('activities', function ($query) use ($request) {
                if ($request->filled('next_followup_from')) {
                    $query->whereDate('next_followup', '>=', $request->next_followup_from);
                }
                if ($request->filled('next_followup_to')) {
                    $query->whereDate('next_followup', '<=', $request->next_followup_to);
                }
            });
        }

        $leads = $leads->paginate(20);

        return view('backend.leads.index', $this->formData() + [
            'leads' => $leads,
            'filters' => $filters,
            'activityTypes' => $this->activityTypes,
        ]);
    }

    public function create()
    {
        return view('backend.leads.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validatedLeadData($request);

        DB::transaction(function () use ($data, &$lead) {
            $data['created_by'] = auth()->id();
            $lead = Lead::create($data);
            $lead->lead_no = 'LD-' . str_pad((string) $lead->id, 5, '0', STR_PAD_LEFT);
            $lead->save();
        });

        flash(translate('Lead has been created successfully'))->success();
        return redirect()->route('leads.show', $lead->id);
    }

    public function show(Lead $lead)
    {
        $this->authorizeLeadAccess($lead);

        $lead->load(['source', 'status', 'assignedUser', 'creator', 'activities.creator']);

        return view('backend.leads.show', [
            'lead' => $lead,
            'activityTypes' => $this->activityTypes,
        ]);
    }

    public function edit(Lead $lead)
    {
        $this->authorizeLeadAccess($lead);

        $lead->load(['activities.creator']);

        return view('backend.leads.edit', $this->formData() + [
            'lead' => $lead,
            'activityTypes' => $this->activityTypes,
        ]);
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorizeLeadAccess($lead);

        $lead->update($this->validatedLeadData($request));

        flash(translate('Lead has been updated successfully'))->success();
        return redirect()->route('leads.show', $lead->id);
    }

    public function destroy(Lead $lead)
    {
        $this->authorizeLeadAccess($lead);

        $lead->delete();

        flash(translate('Lead has been deleted successfully'))->success();
        return redirect()->route('leads.index');
    }

    public function storeActivity(Request $request, Lead $lead)
    {
        $this->authorizeLeadAccess($lead);

        $data = $request->validate([
            'activity_type' => 'required|in:' . implode(',', $this->activityTypes),
            'description' => 'nullable|string',
            'next_followup' => 'nullable|date',
        ]);

        $data['lead_id'] = $lead->id;
        $data['created_by'] = auth()->id();

        LeadActivity::create($data);

        $message = translate('Lead activity has been added successfully');
        flash($message)->success();

        return back()->with('lead_activity_message', $message);
    }

    protected function validatedLeadData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'source_id' => 'nullable|exists:lead_sources,id',
            'status_id' => 'nullable|exists:lead_statuses,id',
            'assigned_to' => 'nullable|exists:users,id',
            'expected_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $data['expected_value'] = $data['expected_value'] ?? 0;

        return $data;
    }

    protected function formData(): array
    {
        return [
            'sources' => LeadSource::where('status', 1)->orderBy('name')->get(),
            'statuses' => LeadStatus::orderBy('sort_order')->orderBy('name')->get(),
            'assignees' => User::where(function ($query) {
                $query->whereIn('user_type', ['admin', 'staff'])
                    ->orWhereHas('staff');
            })->orderBy('name')->get(['id', 'name', 'email']),
        ];
    }

    protected function applyLeadVisibility($query): void
    {
        if ($this->currentUserIsAdmin()) {
            return;
        }

        $userId = auth()->id();
        $query->where(function ($query) use ($userId) {
            $query->where('created_by', $userId)
                ->orWhere('assigned_to', $userId);
        });
    }

    protected function authorizeLeadAccess(Lead $lead): void
    {
        if ($this->currentUserIsAdmin()) {
            return;
        }

        $userId = auth()->id();
        abort_unless((int) $lead->created_by === (int) $userId || (int) $lead->assigned_to === (int) $userId, 403);
    }

    protected function currentUserIsAdmin(): bool
    {
        return auth()->check() && auth()->user()->user_type === 'admin';
    }
}
