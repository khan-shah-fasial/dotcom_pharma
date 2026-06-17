<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadActivitySubStatus;
use App\Models\LeadActivityType;
use App\Models\Department;
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
    public function __construct()
    {
        $this->middleware(['permission:view_leads'])->only(['index', 'show']);
        $this->middleware(['permission:add_lead'])->only(['create', 'store']);
        $this->middleware(['permission:edit_lead'])->only(['edit', 'update', 'storeActivity', 'updateActivity', 'destroyActivity']);
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
            'activity_type_id',
        ]);

        $leads = Lead::query()
            ->select('leads.*')
            ->addSelect([
                'latest_activity_expected_value' => LeadActivity::query()
                    ->select('expected_value')
                    ->whereColumn('lead_activities.lead_id', 'leads.id')
                    ->latest('id')
                    ->limit(1),
            ])
            ->with([
                'source',
                'status',
                'department',
                'assignedUser',
                'creator',
                'country',
                'state',
                'city',
                'latestActivity.activityType',
                'latestActivity.subStatus',
            ])
            ->latest();

        $this->applyLeadVisibility($leads);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $leads->where(function ($query) use ($search) {
                $query->where('lead_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('whatsapp_number', 'like', "%{$search}%")
                    ->orWhereHas('department', function ($departmentQuery) use ($search) {
                        $departmentQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        foreach (['source_id', 'status_id', 'assigned_to'] as $field) {
            if ($request->filled($field)) {
                $leads->where($field, $request->input($field));
            }
        }

        if ($request->filled('expected_value_min')) {
            $leads->whereRaw('(' . $this->latestActivityExpectedValueSql() . ') >= ?', [(float) $request->expected_value_min]);
        }

        if ($request->filled('expected_value_max')) {
            $leads->whereRaw('(' . $this->latestActivityExpectedValueSql() . ') <= ?', [(float) $request->expected_value_max]);
        }

        if ($request->filled('created_from')) {
            $leads->whereDate('created_at', '>=', $request->created_from);
        }

        if ($request->filled('created_to')) {
            $leads->whereDate('created_at', '<=', $request->created_to);
        }

        if ($request->filled('activity_type_id')) {
            $leads->whereHas('activities', function ($query) use ($request) {
                $query->where('activity_type_id', $request->activity_type_id);
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

        $lead->load([
            'source',
            'status',
            'department',
            'photoUpload',
            'assignedUser',
            'creator',
            'country',
            'state',
            'city',
            'latestActivity.activityType',
            'latestActivity.subStatus',
            'activities.creator',
            'activities.activityType',
            'activities.subStatus',
        ]);

        return view('backend.leads.show', $this->formData($lead) + ['lead' => $lead]);
    }

    public function edit(Lead $lead)
    {
        $this->authorizeLeadAccess($lead);

        $lead->load(['activities.creator', 'department', 'photoUpload']);

        return view('backend.leads.edit', $this->formData($lead) + [
            'lead' => $lead,
        ]);
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorizeLeadAccess($lead);

        $lead->update($this->validatedLeadData($request, $lead));

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

        $data = $this->validatedActivityData($request);

        $data['attachments'] = $this->storeActivityAttachments($request);
        $data['lead_id'] = $lead->id;
        $data['created_by'] = auth()->id();

        LeadActivity::create($data);

        $message = translate('Lead activity has been added successfully');
        flash($message)->success();

        return back()->with('lead_activity_message', $message);
    }

    public function updateActivity(Request $request, Lead $lead, LeadActivity $activity)
    {
        $this->authorizeLeadAccess($lead);
        abort_unless((int) $activity->lead_id === (int) $lead->id, 404);

        $data = $this->validatedActivityData($request);
        $attachments = $this->storeActivityAttachments($request);

        if ($attachments) {
            $data['attachments'] = $this->mergeAttachmentIds($activity->attachments, $attachments);
        }

        $activity->update($data);

        $message = translate('Lead activity has been updated successfully');
        flash($message)->success();

        return back()->with('lead_activity_message', $message);
    }

    public function destroyActivity(Lead $lead, LeadActivity $activity)
    {
        $this->authorizeLeadAccess($lead);
        abort_unless((int) $activity->lead_id === (int) $lead->id, 404);
        abort_unless($this->currentUserIsSuperAdmin(), 403);

        $activity->delete();

        flash(translate('Lead activity has been deleted successfully'))->success();

        return back();
    }

    protected function validatedLeadData(Request $request, ?Lead $lead = null): array
    {
        $request->merge([
            'email' => $this->nullableTrimmedInput($request->input('email')),
            'phone' => $this->nullableTrimmedInput($request->input('phone')),
            'whatsapp_number' => $this->nullableTrimmedInput($request->input('whatsapp_number')),
            'designation' => $this->nullableTrimmedInput($request->input('designation')),
        ]);

        $phoneRules = ['nullable', 'string', 'max:50', 'regex:/^\+?[0-9\s().-]{7,20}$/'];

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'photo' => 'nullable|integer|exists:uploads,id',
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'work_profile' => 'nullable|string',
            'social_media_keys' => 'nullable|array',
            'social_media_keys.*' => 'nullable|string|max:100',
            'social_media_values' => 'nullable|array',
            'social_media_values.*' => 'nullable|string|max:500',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('leads', 'email')->ignore($lead?->id),
            ],
            'phone' => array_merge($phoneRules, [
                Rule::unique('leads', 'phone')->ignore($lead?->id),
            ]),
            'whatsapp_number' => array_merge($phoneRules, [
                Rule::unique('leads', 'whatsapp_number')->ignore($lead?->id),
            ]),
            'source_id' => 'nullable|integer|exists:lead_sources,id',
            'source_name' => 'nullable|string|max:100',
            'status_id' => [
                'nullable',
                Rule::exists('lead_statuses', 'id')->where(fn ($query) => $query->whereIn('name', ['New', 'Follow-up'])),
            ],
            'assigned_to' => 'nullable|exists:users,id',
            'address' => 'nullable|string|max:500',
            'country_id' => 'nullable|integer|exists:countries,id',
            'state_id' => 'nullable|integer|exists:states,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'pincode' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ], [
            'email.unique' => translate('Already Exist'),
            'phone.unique' => translate('Already Exist'),
            'whatsapp_number.unique' => translate('Already Exist'),
            'phone.regex' => translate('Please enter a valid phone number'),
            'whatsapp_number.regex' => translate('Please enter a valid WhatsApp number'),
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

        $data['social_media_ids'] = $this->socialMediaRows($request);
        unset($data['social_media_keys'], $data['social_media_values']);

        return $data;
    }

    protected function nullableTrimmedInput($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function formData(?Lead $lead = null): array
    {
        return [
            'sources' => LeadSource::where('status', 1)->orderBy('name')->get(),
            'statuses' => LeadStatus::whereIn('name', ['New', 'Follow-up'])->orderBy('sort_order')->orderBy('name')->get(),
            'departments' => Department::active()->with('category')->orderBy('name')->get(),
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
            'activityTypes' => LeadActivityType::active()->orderBy('title')->get(),
            'activitySubStatuses' => LeadActivitySubStatus::active()->orderBy('title')->get(),
        ];
    }

    protected function validatedActivityData(Request $request): array
    {
        $data = $request->validate([
            'activity_type_id' => [
                'required',
                'integer',
                Rule::exists('lead_activity_types', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'sub_status_id' => [
                'required',
                'integer',
                Rule::exists('lead_activity_sub_statuses', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'expected_value' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'next_followup' => 'nullable|date',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,webp,bmp,svg,pdf,doc,docx,xls,xlsx,csv,txt,xml,zip,rar,7z|max:20480',
        ]);

        $activityType = LeadActivityType::find($data['activity_type_id']);
        $subStatus = LeadActivitySubStatus::find($data['sub_status_id']);

        $data['activity_type'] = $this->legacyActivityTypeValue($activityType?->title);
        $data['activity_sub_status'] = $this->legacySubStatusValue($subStatus?->title);

        return $data;
    }

    protected function socialMediaRows(Request $request): ?array
    {
        $keys = (array) $request->input('social_media_keys', []);
        $values = (array) $request->input('social_media_values', []);
        $rows = [];

        foreach ($keys as $index => $key) {
            $key = trim((string) $key);
            $value = trim((string) ($values[$index] ?? ''));

            if ($key === '' && $value === '') {
                continue;
            }

            $rows[] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        return empty($rows) ? null : $rows;
    }

    protected function mergeAttachmentIds(?string $current, ?string $additional): ?string
    {
        $ids = collect(array_merge(
            $current ? explode(',', $current) : [],
            $additional ? explode(',', $additional) : []
        ))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        return $ids->isEmpty() ? null : $ids->implode(',');
    }

    protected function legacyActivityTypeValue(?string $title): string
    {
        $normalized = strtolower(trim((string) $title));
        $normalized = str_replace(['_', '-'], ' ', $normalized);

        return match ($normalized) {
            'call' => 'call',
            'email' => 'email',
            'meeting' => 'meeting',
            'whatsapp', 'whats app' => 'whatsapp',
            default => 'note',
        };
    }

    protected function legacySubStatusValue(?string $title): ?string
    {
        $title = strtolower(trim((string) $title));

        if ($title === '') {
            return null;
        }

        $value = preg_replace('/[^a-z0-9]+/', '_', $title);

        return substr(trim((string) $value, '_'), 0, 50);
    }

    protected function latestActivityExpectedValueSql(): string
    {
        return "select expected_value from lead_activities where lead_activities.lead_id = leads.id order by lead_activities.id desc limit 1";
    }

    protected function storeActivityAttachments(Request $request): ?string
    {
        if (!$request->hasFile('attachments')) {
            return null;
        }

        $ids = collect($request->file('attachments'))
            ->filter()
            ->map(fn ($file) => $this->storeFileToUploads($file))
            ->filter()
            ->values();

        return $ids->isEmpty() ? null : $ids->implode(',');
    }

    protected function storeFileToUploads($file): int
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $storedPath = $file->store('uploads/all/' . date('Y/m'), 'local');

        $upload = new Upload();
        $upload->file_original_name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $upload->extension = $extension;
        $upload->file_size = $file->getSize();
        $upload->user_id = auth()->id();
        $upload->type = $this->uploadTypeFromExtension($extension);
        $upload->file_name = $storedPath;
        $upload->disk = 'local';
        $upload->save();

        return $upload->id;
    }

    protected function uploadTypeFromExtension(string $extension): string
    {
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'svg', 'webp', 'gif', 'bmp'], true)) {
            return 'image';
        }

        if (in_array($extension, ['mp4', 'mpg', 'mpeg', 'webm', 'ogg', 'avi', 'mov', 'flv', 'swf', 'mkv', 'wmv'], true)) {
            return 'video';
        }

        if (in_array($extension, ['wma', 'aac', 'wav', 'mp3'], true)) {
            return 'audio';
        }

        if (in_array($extension, ['zip', 'rar', '7z'], true)) {
            return 'archive';
        }

        return 'document';
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

        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'photo') && DB::table('leads')->where('photo', $uploadId)->exists()) {
            return true;
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

    protected function currentUserIsSuperAdmin(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Super Admin');
    }
}
