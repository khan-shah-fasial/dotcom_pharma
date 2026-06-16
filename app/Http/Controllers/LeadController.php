<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Upload;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    protected array $activityTypes = ['call', 'email', 'meeting', 'whatsapp', 'note'];
    protected array $activitySubStatuses = [
        'call' => ['callback', 'busy', 'out_of_network', 'blocked', 'switched_off', 'ringing', 'not_responding'],
        'email' => ['sent', 'delivered', 'opened', 'replied', 'bounced'],
        'meeting' => ['scheduled', 'completed', 'rescheduled', 'cancelled', 'no_show'],
        'whatsapp' => ['sent', 'delivered', 'read', 'replied', 'not_on_whatsapp'],
        'note' => ['general', 'follow_up', 'internal'],
    ];

    public function __construct()
    {
        $this->middleware(['permission:view_leads'])->only(['index', 'show']);
        $this->middleware(['permission:add_lead'])->only(['create', 'store']);
        $this->middleware(['permission:edit_lead'])->only(['edit', 'update', 'storeActivity', 'destroyActivity']);
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
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('whatsapp_number', 'like', "%{$search}%");
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

        $lead->load(['source', 'status', 'assignedUser', 'creator', 'country', 'state', 'city', 'activities.creator']);

        return view('backend.leads.show', [
            'lead' => $lead,
            'activityTypes' => $this->activityTypes,
            'activitySubStatuses' => $this->activitySubStatuses,
            'activityUploads' => $this->activityUploads(),
        ]);
    }

    public function edit(Lead $lead)
    {
        $this->authorizeLeadAccess($lead);

        $lead->load(['activities.creator']);

        return view('backend.leads.edit', $this->formData($lead) + [
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

        $allowedSubStatuses = $this->activitySubStatuses[$request->activity_type] ?? [];
        $data = $request->validate([
            'activity_type' => 'required|in:' . implode(',', $this->activityTypes),
            'activity_sub_status' => ['required', Rule::in($allowedSubStatuses)],
            'description' => 'nullable|string',
            'next_followup' => 'nullable|date',
            'attachments' => 'nullable|array',
            'attachments.*' => 'integer|exists:uploads,id',
        ]);

        $data['attachments'] = $this->normalizeAttachmentIds($data['attachments'] ?? []);
        $data['lead_id'] = $lead->id;
        $data['created_by'] = auth()->id();

        LeadActivity::create($data);

        $message = translate('Lead activity has been added successfully');
        flash($message)->success();

        return back()->with('lead_activity_message', $message);
    }

    public function destroyActivity(Lead $lead, LeadActivity $activity)
    {
        $this->authorizeLeadAccess($lead);
        abort_unless((int) $activity->lead_id === (int) $lead->id, 404);

        $attachmentIds = $activity->attachment_ids;
        $activity->delete();

        foreach ($attachmentIds as $uploadId) {
            if (!$this->uploadInUse($uploadId)) {
                $upload = Upload::withoutGlobalScope('not_hidden')->find($uploadId);
                if ($upload) {
                    $this->deleteUploadFile($upload);
                }
            }
        }

        flash(translate('Lead activity has been deleted successfully'))->success();

        return back();
    }

    protected function validatedLeadData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'source_id' => 'nullable|integer|exists:lead_sources,id',
            'source_name' => 'nullable|string|max:100',
            'status_id' => 'nullable|exists:lead_statuses,id',
            'assigned_to' => 'nullable|exists:users,id',
            'expected_value' => 'nullable|numeric|min:0',
            'address' => 'nullable|string|max:500',
            'country_id' => 'nullable|integer|exists:countries,id',
            'state_id' => 'nullable|integer|exists:states,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'pincode' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $sourceName = trim((string) ($data['source_name'] ?? ''));
        if ($sourceName !== '') {
            $source = LeadSource::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($sourceName)])->first();
            if (!$source) {
                $source = LeadSource::create(['name' => $sourceName, 'status' => 1]);
            }
            $data['source_id'] = $source->id;
        }
        unset($data['source_name']);

        $data['expected_value'] = $data['expected_value'] ?? 0;

        return $data;
    }

    protected function formData(?Lead $lead = null): array
    {
        return [
            'sources' => LeadSource::where('status', 1)->orderBy('name')->get(),
            'statuses' => LeadStatus::orderBy('sort_order')->orderBy('name')->get(),
            'assignees' => User::where(function ($query) {
                $query->whereIn('user_type', ['admin', 'staff'])
                    ->orWhereHas('staff');
            })->orderBy('name')->get(['id', 'name', 'email']),
            'countries' => Country::query()->isEnabled()->orderBy('name')->get(['id', 'name']),
            'states' => $lead && $lead->country_id
                ? State::where('country_id', $lead->country_id)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'cities' => $lead && $lead->state_id
                ? City::where('state_id', $lead->state_id)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'activitySubStatuses' => $this->activitySubStatuses,
            'activityUploads' => $this->activityUploads(),
        ];
    }

    protected function activityUploads()
    {
        return Upload::latest()
            ->get(['id', 'file_original_name', 'extension', 'type']);
    }

    protected function normalizeAttachmentIds(array $attachments): ?string
    {
        $ids = collect($attachments)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        return $ids->isEmpty() ? null : $ids->implode(',');
    }

    protected function uploadInUse(int $uploadId): bool
    {
        if (Schema::hasTable('products')) {
            $inProducts = DB::table('products')
                ->whereRaw('FIND_IN_SET(?, photos)', [$uploadId])
                ->orWhere('thumbnail_img', $uploadId)
                ->orWhere('meta_img', $uploadId)
                ->orWhere('pdf', $uploadId)
                ->exists();

            if ($inProducts) {
                return true;
            }
        }

        if (Schema::hasTable('brands') && DB::table('brands')->where('logo', $uploadId)->exists()) {
            return true;
        }

        if (Schema::hasTable('categories')) {
            $inCategories = DB::table('categories')
                ->where('banner', $uploadId)
                ->orWhere('icon', $uploadId)
                ->exists();

            if ($inCategories) {
                return true;
            }
        }

        if (Schema::hasTable('flash_deals') && DB::table('flash_deals')->where('banner', $uploadId)->exists()) {
            return true;
        }

        if (Schema::hasTable('financial_archive') && DB::table('financial_archive')->where('upload_id', $uploadId)->exists()) {
            return true;
        }

        if (Schema::hasTable('users')) {
            $inUsers = DB::table('users')
                ->where('avatar_original', $uploadId)
                ->orWhere('avatar', $uploadId)
                ->exists();

            if ($inUsers) {
                return true;
            }
        }

        return DB::table('lead_activities')
            ->whereRaw('FIND_IN_SET(?, attachments)', [$uploadId])
            ->exists();
    }

    protected function deleteUploadFile(Upload $upload): void
    {
        try {
            if ($upload->external_link == null && $upload->file_name) {
                $diskName = $upload->disk ?: (env('FILESYSTEM_DRIVER') == 's3' ? 's3' : env('FILESYSTEM_DRIVER', 'local'));

                if ($diskName && $diskName !== 'local') {
                    Storage::disk($diskName)->delete($upload->file_name);
                }

                $publicPath = public_path($upload->file_name);
                if (file_exists($publicPath)) {
                    @unlink($publicPath);
                }
            }
        } catch (\Exception $e) {
            // Delete the upload row even if the physical file is already missing.
        }

        if (method_exists($upload, 'forceDelete')) {
            $upload->forceDelete();
        } else {
            $upload->delete();
        }
    }

    public function customerByPhone(Request $request)
    {
        $data = $request->validate(['phone' => 'required|string|min:5|max:50']);
        $phone = preg_replace('/\D+/', '', $data['phone']);

        if (strlen($phone) < 5) {
            return response()->json(['found' => false]);
        }

        $normalize = static function (string $column): string {
            return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column}, '+', ''), '-', ''), ' ', ''), '(', ''), ')', ''), '.', '')";
        };

        $userPhone = $normalize('users.phone');
        $businessPhone = $normalize('user_details.prim_mobile_no_business');
        $length = strlen($phone);

        $customer = User::query()
            ->select('users.*')
            ->join('user_details', 'user_details.user_id', '=', 'users.id')
            ->where('users.user_type', 'customer')
            ->whereNotNull('user_details.company_name')
            ->where(function ($query) use ($userPhone, $businessPhone, $phone, $length) {
                $query->whereRaw("{$userPhone} = ?", [$phone])
                    ->orWhereRaw("{$businessPhone} = ?", [$phone])
                    ->orWhereRaw("RIGHT({$userPhone}, ?) = ?", [$length, $phone])
                    ->orWhereRaw("RIGHT({$businessPhone}, ?) = ?", [$length, $phone]);
            })
            ->with('user_details')
            ->first();

        if (!$customer || !$customer->user_details) {
            return response()->json(['found' => false]);
        }

        $details = $customer->user_details;
        $address = collect([
            $details->street_add_first_business,
            $details->street_add_sec_business,
            $details->locality_land_mark_business,
            $details->village_business,
            $details->post_business,
        ])->filter()->implode(', ');

        return response()->json([
            'found' => true,
            'customer' => [
                'name' => $details->con_person_name ?: $customer->name,
                'company_name' => $details->company_name,
                'email' => $details->prim_email_business ?: $customer->email,
                'phone' => $details->prim_mobile_no_business ?: $customer->phone,
                'whatsapp_number' => $details->prim_whats_app_no_business,
                'address' => $address,
                'country_id' => $details->country_id_business,
                'state_id' => $details->state_id_business,
                'city_id' => $details->city_id_business,
                'pincode' => $details->pincode_business,
            ],
        ]);
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
