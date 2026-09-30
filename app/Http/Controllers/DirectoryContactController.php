<?php

namespace App\Http\Controllers;

use App\Models\ContactClassification;
use App\Models\Country;
use App\Models\DirectoryContact;
use App\Models\DirectoryContactActivity;
use App\Models\State;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class DirectoryContactController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_contact_directory'])->only(['index', 'show']);
        $this->middleware(['permission:add_contact_directory'])->only(['create', 'store']);
        $this->middleware(['permission:edit_contact_directory'])->only([
            'edit',
            'update',
            'storeActivity',
            'updateActivity',
            'destroyActivity',
        ]);
        $this->middleware(['permission:delete_contact_directory'])->only('destroy');
    }

    public function index(Request $request)
    {
        $hasRegion = Schema::hasColumn('directory_contacts', 'region');
        $textFilters = [
            'search', 'name', 'company_name', 'designation', 'phone', 'whatsapp_number',
            'alternate_mobile_number', 'email', 'instagram', 'linkedin', 'district', 'post', 'tags',
        ];
        if ($hasRegion) {
            $textFilters[] = 'region';
        }
        $idFilters = [
            'group_id', 'category_id', 'subcategory_id', 'type_id', 'subject_id',
            'industry_id', 'work_profile_id', 'department_id', 'purpose_id', 'country_id', 'state_id',
        ];
        $filters = [];
        foreach (array_merge($textFilters, $idFilters) as $field) {
            $filters[$field] = trim((string) $request->input($field, ''));
        }

        $contacts = DirectoryContact::query()
            ->select([
                'directory_contacts.id',
                'directory_contacts.contact_no',
                'directory_contacts.name',
                'directory_contacts.company_name',
                'directory_contacts.designation',
                'directory_contacts.email',
                'directory_contacts.phone',
                'directory_contacts.alternate_mobile_number',
                'directory_contacts.whatsapp_number',
                'directory_contacts.social_media_ids',
                'directory_contacts.country_id',
                'directory_contacts.state_id',
                'directory_contacts.district',
                'directory_contacts.post',
                'directory_contacts.tags',
                'directory_contacts.group_id',
                'directory_contacts.category_id',
                'directory_contacts.subcategory_id',
                'directory_contacts.type_id',
                'directory_contacts.subject_id',
                'directory_contacts.industry_id',
                'directory_contacts.work_profile_id',
                'directory_contacts.department_id',
                'directory_contacts.purpose_id',
                'directory_contacts.created_by',
                'directory_contacts.updated_by',
                'directory_contacts.created_at',
                'directory_contacts.updated_at',
            ])
            ->with([
                'group:id,name',
                'category:id,name',
                'subcategory:id,name',
                'type:id,name',
                'subject:id,name',
                'industry:id,name',
                'workProfile:id,name',
                'department:id,name',
                'purpose:id,name',
                'country:id,name',
                'state:id,name',
                'creator:id,name',
                'updater:id,name',
            ]);
        if ($hasRegion) {
            $contacts->addSelect('directory_contacts.region');
        }
        $hasVisitingCards = Schema::hasColumn('directory_contacts', 'visiting_cards');
        if ($hasVisitingCards) {
            $contacts->addSelect('directory_contacts.visiting_cards');
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $contacts->where(function ($query) use ($search) {
                $query->where('contact_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('alternate_mobile_number', 'like', "%{$search}%")
                    ->orWhere('whatsapp_number', 'like', "%{$search}%");
            });
        }

        foreach ([
            'name' => 'directory_contacts.name',
            'company_name' => 'directory_contacts.company_name',
            'designation' => 'directory_contacts.designation',
            'phone' => 'directory_contacts.phone',
            'whatsapp_number' => 'directory_contacts.whatsapp_number',
            'alternate_mobile_number' => 'directory_contacts.alternate_mobile_number',
            'email' => 'directory_contacts.email',
            'district' => 'directory_contacts.district',
            'post' => 'directory_contacts.post',
        ] as $filter => $column) {
            if ($filters[$filter] !== '') {
                $contacts->where($column, 'like', '%' . $filters[$filter] . '%');
            }
        }
        if ($hasRegion && ($filters['region'] ?? '') !== '') {
            $contacts->where('directory_contacts.region', 'like', '%' . $filters['region'] . '%');
        }
        if ($filters['instagram'] !== '') {
            $this->whereSocialPlatform($contacts, ['insta', 'instagram'], $filters['instagram']);
        }
        if ($filters['linkedin'] !== '') {
            $this->whereSocialPlatform($contacts, ['linkedin'], $filters['linkedin']);
        }
        foreach ($idFilters as $field) {
            if ($filters[$field] !== '') {
                $contacts->where('directory_contacts.' . $field, $filters[$field]);
            }
        }
        if ($filters['tags'] !== '') {
            $tag = $filters['tags'];
            $contacts->where(function ($query) use ($tag) {
                $query->whereJsonContains('tags', $tag)
                    ->orWhere('tags', 'like', '%' . $tag . '%');
            });
        }

        $allowedSorts = [
            'name' => 'directory_contacts.name',
            'company_name' => 'directory_contacts.company_name',
            'designation' => 'directory_contacts.designation',
            'group' => '(select name from contact_classifications where contact_classifications.id = directory_contacts.group_id limit 1)',
            'category' => '(select name from contact_classifications where contact_classifications.id = directory_contacts.category_id limit 1)',
            'subcategory' => '(select name from contact_classifications where contact_classifications.id = directory_contacts.subcategory_id limit 1)',
            'type' => '(select name from contact_classifications where contact_classifications.id = directory_contacts.type_id limit 1)',
            'subject' => '(select name from contact_classifications where contact_classifications.id = directory_contacts.subject_id limit 1)',
            'industry' => '(select name from contact_classifications where contact_classifications.id = directory_contacts.industry_id limit 1)',
            'work_profile' => '(select name from contact_classifications where contact_classifications.id = directory_contacts.work_profile_id limit 1)',
            'department' => '(select name from contact_classifications where contact_classifications.id = directory_contacts.department_id limit 1)',
            'purpose' => '(select name from contact_classifications where contact_classifications.id = directory_contacts.purpose_id limit 1)',
            'phone' => 'directory_contacts.phone',
            'whatsapp_number' => 'directory_contacts.whatsapp_number',
            'alternate_mobile_number' => 'directory_contacts.alternate_mobile_number',
            'email' => 'directory_contacts.email',
            'instagram' => 'directory_contacts.social_media_ids',
            'linkedin' => 'directory_contacts.social_media_ids',
            'country' => '(select name from countries where countries.id = directory_contacts.country_id limit 1)',
            'state' => '(select name from states where states.id = directory_contacts.state_id limit 1)',
            'district' => 'directory_contacts.district',
            'post' => 'directory_contacts.post',
            'tags' => 'directory_contacts.tags',
        ];
        if ($hasRegion) {
            $allowedSorts['region'] = 'directory_contacts.region';
        }
        $sortBy = array_key_exists((string) $request->input('sort_by'), $allowedSorts) ? (string) $request->input('sort_by') : '';
        $sortDir = strtolower((string) $request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        if ($sortBy !== '') {
            $expression = $allowedSorts[$sortBy];
            if (str_starts_with($expression, '(')) {
                $contacts->orderByRaw($expression . ' ' . $sortDir);
            } else {
                $contacts->orderBy($expression, $sortDir);
            }
            $contacts->orderBy('directory_contacts.id', 'desc');
        } else {
            $contacts->latest('directory_contacts.created_at');
        }

        $visitingCardSlides = $hasVisitingCards ? $this->visitingCardSlides(clone $contacts) : [];
        $contacts = $contacts->paginate(20)->appends($request->query());

        return view('backend.contact_management.index', $this->indexData() + $this->activityFormData() + [
            'contacts' => $contacts,
            'filters' => $filters,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'hasRegion' => $hasRegion,
            'hasVisitingCards' => $hasVisitingCards,
            'visitingCardSlides' => $visitingCardSlides,
        ]);
    }

    public function create()
    {
        return view('backend.contact_management.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validatedContactData($request);

        $contact = DB::transaction(function () use ($data) {
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();
            $contact = DirectoryContact::create($data);
            $contact->contact_no = 'CN-' . str_pad((string) $contact->id, 5, '0', STR_PAD_LEFT);
            $contact->save();

            return $contact;
        });

        flash(translate('Contact has been created successfully'))->success();
        return redirect()->route('contact-directory.show', $contact->id);
    }

    public function show(Request $request, DirectoryContact $directoryContact)
    {
        $activitySortOrder = 'desc';
        $relations = [
            'photoUpload',
            'country',
            'state',
            'creator',
            'updater',
            'group',
            'category',
            'subcategory',
            'type',
            'subject',
            'industry',
            'workProfile',
            'department',
            'purpose',
        ];

        if ($this->activitiesEnabled()) {
            $activitySortOrder = strtolower((string) $request->input('activity_sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
            $relations['activities'] = function ($query) use ($activitySortOrder) {
                $query->orderBy('created_at', $activitySortOrder)
                    ->orderBy('id', $activitySortOrder);
            };
            $relations[] = 'activities.creator';
            $relations[] = 'activities.activityType';
            $relations[] = 'activities.subStatus';
        }

        $directoryContact->load($relations);

        $hasVisitingCards = Schema::hasColumn('directory_contacts', 'visiting_cards');

        return view('backend.contact_management.show', $this->activityFormData() + [
            'contact' => $directoryContact,
            'activitySortOrder' => $activitySortOrder,
            'hasVisitingCards' => $hasVisitingCards,
            'visitingCards' => $this->visitingCardUploads($directoryContact),
            'visitingCardSlides' => $hasVisitingCards ? $this->directoryVisitingCardSlides() : [],
        ]);
    }

    public function edit(DirectoryContact $directoryContact)
    {
        return view('backend.contact_management.edit', $this->formData($directoryContact) + [
            'contact' => $directoryContact,
        ]);
    }

    public function update(Request $request, DirectoryContact $directoryContact)
    {
        $data = $this->validatedContactData($request, $directoryContact);
        $data['updated_by'] = auth()->id();
        $directoryContact->update($data);

        flash(translate('Contact has been updated successfully'))->success();
        return redirect()->route('contact-directory.show', $directoryContact->id);
    }

    public function destroy(DirectoryContact $directoryContact)
    {
        $directoryContact->delete();

        flash(translate('Contact has been deleted successfully'))->success();
        return redirect()->route('contact-directory.index');
    }

    public function storeActivity(Request $request, DirectoryContact $directoryContact)
    {
        abort_unless($this->activitiesEnabled(), 404);

        $data = $this->validatedActivityData($request);
        $data['attachments'] = $this->storeActivityAttachments($request);
        $data['directory_contact_id'] = $directoryContact->id;
        $data['created_by'] = auth()->id();

        DirectoryContactActivity::create($data);

        flash(translate('Contact activity has been added successfully'))->success();

        return back();
    }

    public function updateActivity(Request $request, DirectoryContact $directoryContact, DirectoryContactActivity $directoryContactActivity)
    {
        abort_unless($this->activitiesEnabled(), 404);
        abort_unless((int) $directoryContactActivity->directory_contact_id === (int) $directoryContact->id, 404);

        $data = $this->validatedActivityData($request);
        unset($data['attachments']);
        $attachments = $this->storeActivityAttachments($request);

        if ($attachments) {
            $data['attachments'] = $this->mergeAttachmentIds($directoryContactActivity->attachments, $attachments);
        }

        $directoryContactActivity->update($data);

        flash(translate('Contact activity has been updated successfully'))->success();

        return back();
    }

    public function destroyActivity(DirectoryContact $directoryContact, DirectoryContactActivity $directoryContactActivity)
    {
        abort_unless($this->activitiesEnabled(), 404);
        abort_unless((int) $directoryContactActivity->directory_contact_id === (int) $directoryContact->id, 404);
        abort_unless($this->currentUserIsSuperAdmin(), 403);

        $directoryContactActivity->delete();

        flash(translate('Contact activity has been deleted successfully'))->success();

        return back();
    }

    protected function validatedContactData(Request $request, ?DirectoryContact $contact = null): array
    {
        $request->merge([
            'email' => $this->nullableTrimmedInput($request->input('email')),
            'phone' => $this->nullableTrimmedInput($request->input('phone')),
            'alternate_mobile_number' => $this->nullableTrimmedInput($request->input('alternate_mobile_number')),
            'whatsapp_number' => $this->nullableTrimmedInput($request->input('whatsapp_number')),
            'designation' => $this->nullableTrimmedInput($request->input('designation')),
            'company_name' => $this->nullableTrimmedInput($request->input('company_name')),
            'district' => $this->nullableTrimmedInput($request->input('district')),
            'post' => $this->nullableTrimmedInput($request->input('post')),
            'tags' => $this->nullableTrimmedInput($request->input('tags')),
            'region' => $this->nullableTrimmedInput($request->input('region')),
        ]);

        $phoneRules = ['nullable', 'string', 'max:50', 'regex:/^\+?[0-9\s().-]{7,20}$/'];

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'photo' => 'nullable|integer|exists:uploads,id',
            'visiting_cards' => 'nullable|string',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('directory_contacts', 'email')->ignore($contact?->id),
            ],
            'phone' => array_merge($phoneRules, [
                Rule::unique('directory_contacts', 'phone')->ignore($contact?->id),
            ]),
            'alternate_mobile_number' => array_merge($phoneRules, [
                Rule::unique('directory_contacts', 'alternate_mobile_number')->ignore($contact?->id),
            ]),
            'whatsapp_number' => array_merge($phoneRules, [
                Rule::unique('directory_contacts', 'whatsapp_number')->ignore($contact?->id),
            ]),
            'group_id' => $this->classificationRule('group', $contact?->group_id),
            'category_id' => $this->classificationRule('category', $contact?->category_id),
            'subcategory_id' => $this->classificationRule('subcategory', $contact?->subcategory_id),
            'type_id' => $this->classificationRule('type', $contact?->type_id),
            'subject_id' => $this->classificationRule('subject', $contact?->subject_id),
            'industry_id' => $this->classificationRule('industry', $contact?->industry_id),
            'work_profile_id' => $this->classificationRule('work_profile', $contact?->work_profile_id),
            'department_id' => $this->classificationRule('department', $contact?->department_id),
            'purpose_id' => $this->classificationRule('purpose', $contact?->purpose_id),
            'address' => 'nullable|string|max:500',
            'country_id' => 'nullable|integer|exists:countries,id',
            'state_id' => 'nullable|integer|exists:states,id',
            'region' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'post' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'tags' => 'nullable|string|max:500',
            'social_media_keys' => 'nullable|array',
            'social_media_keys.*' => 'nullable|string|max:100',
            'social_media_values' => 'nullable|array',
            'social_media_values.*' => 'nullable|string|max:500',
        ], [
            'email.unique' => translate('Already Exist'),
            'phone.unique' => translate('Already Exist'),
            'alternate_mobile_number.unique' => translate('Already Exist'),
            'whatsapp_number.unique' => translate('Already Exist'),
            'phone.regex' => translate('Please enter a valid phone number'),
            'alternate_mobile_number.regex' => translate('Please enter a valid alternate mobile number'),
            'whatsapp_number.regex' => translate('Please enter a valid WhatsApp number'),
        ]);

        $this->assertClassificationChain($data, [
            'category_id' => 'group_id',
            'subcategory_id' => 'category_id',
            'type_id' => 'subcategory_id',
            'subject_id' => 'type_id',
            'work_profile_id' => 'industry_id',
        ]);

        $data['social_media_ids'] = $this->socialMediaRows($request);
        unset($data['social_media_keys'], $data['social_media_values']);

        $data['tags'] = $this->parseTags($data['tags'] ?? null);
        if (!Schema::hasColumn('directory_contacts', 'region')) {
            unset($data['region']);
        }
        if (Schema::hasColumn('directory_contacts', 'visiting_cards')) {
            $cardIds = $this->visitingCardIdsFromRequest($request);
            $data['visiting_cards'] = empty($cardIds) ? null : (string) $cardIds[0];
        } else {
            unset($data['visiting_cards']);
        }

        foreach (array_keys(ContactClassification::CONTACT_COLUMNS) as $kind) {
            $column = ContactClassification::CONTACT_COLUMNS[$kind];
            $data[$column] = !empty($data[$column]) ? (int) $data[$column] : null;
        }

        return $data;
    }

    protected function classificationRule(string $kind, $selectedId = null): array
    {
        return [
            'nullable',
            'integer',
            Rule::exists('contact_classifications', 'id')->where(function ($query) use ($kind, $selectedId) {
                $query->where('kind', $kind)
                    ->where(function ($statusQuery) use ($selectedId) {
                        $statusQuery->where('status', 1);
                        if ($selectedId) {
                            $statusQuery->orWhere('id', $selectedId);
                        }
                    });
            }),
        ];
    }

    protected function assertClassificationChain(array $data, array $childToParent): void
    {
        $ids = collect($childToParent)
            ->flatMap(fn ($parentColumn, $childColumn) => [$data[$childColumn] ?? null, $data[$parentColumn] ?? null])
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $items = ContactClassification::query()
            ->whereIn('id', $ids)
            ->get(['id', 'parent_id'])
            ->keyBy('id');

        foreach ($childToParent as $childColumn => $parentColumn) {
            $childId = $data[$childColumn] ?? null;
            if (!$childId) {
                continue;
            }

            $child = $items->get((int) $childId);
            $parentId = $data[$parentColumn] ?? null;

            if (!$child || ($parentId && (int) $child->parent_id !== (int) $parentId) || (!$parentId && $child->parent_id)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $childColumn => translate('Selected value does not match the parent item'),
                ]);
            }
        }
    }

    protected function formData(?DirectoryContact $contact = null): array
    {
        $classifications = $this->classificationOptions();

        $filterKind = function (string $kind, $parentId = null, $selectedId = null) use ($classifications) {
            return $classifications
                ->where('kind', $kind)
                ->filter(function ($item) use ($parentId, $selectedId) {
                    if ($selectedId && (int) $item->id === (int) $selectedId) {
                        return true;
                    }
                    if ((int) $item->status !== 1) {
                        return false;
                    }
                    if ($parentId === null) {
                        return $item->parent_id === null;
                    }

                    return (int) $item->parent_id === (int) $parentId;
                })
                ->sortBy('name')
                ->values();
        };

        $groupId = old('group_id', $contact?->group_id);
        $categoryId = old('category_id', $contact?->category_id);
        $subcategoryId = old('subcategory_id', $contact?->subcategory_id);
        $typeId = old('type_id', $contact?->type_id);
        $industryId = old('industry_id', $contact?->industry_id);
        $countryId = old('country_id', $contact?->country_id);

        return [
            'groups' => $filterKind('group', null, $contact?->group_id),
            'categories' => $groupId ? $filterKind('category', $groupId, $contact?->category_id) : collect(),
            'subcategories' => $categoryId ? $filterKind('subcategory', $categoryId, $contact?->subcategory_id) : collect(),
            'types' => $subcategoryId ? $filterKind('type', $subcategoryId, $contact?->type_id) : collect(),
            'subjects' => $typeId ? $filterKind('subject', $typeId, $contact?->subject_id) : collect(),
            'industries' => $filterKind('industry', null, $contact?->industry_id),
            'workProfiles' => $industryId ? $filterKind('work_profile', $industryId, $contact?->work_profile_id) : collect(),
            'departments' => $filterKind('department', null, $contact?->department_id),
            'purposes' => $filterKind('purpose', null, $contact?->purpose_id),
            'countries' => Country::query()->isEnabled()->orderBy('name')->get(['id', 'name']),
            'states' => $countryId
                ? State::where('country_id', $countryId)->orderBy('name')->get(['id', 'name'])
                : collect(),
        ];
    }

    protected function indexData(): array
    {
        $classifications = $this->classificationOptions();
        $active = function (string $kind) use ($classifications) {
            return $classifications->where('kind', $kind)->where('status', 1)->sortBy('name')->values();
        };

        return [
            'groups' => $active('group'),
            'categories' => $active('category'),
            'subcategories' => $active('subcategory'),
            'types' => $active('type'),
            'subjects' => $active('subject'),
            'industries' => $active('industry'),
            'workProfiles' => $active('work_profile'),
            'departments' => $active('department'),
            'purposes' => $active('purpose'),
            'countries' => Country::query()->isEnabled()->orderBy('name')->get(['id', 'name']),
            'states' => State::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    protected function whereSocialPlatform($query, array $needles, string $value): void
    {
        $query->where(function ($outer) use ($needles, $value) {
            foreach ($needles as $needle) {
                $outer->orWhere(function ($inner) use ($needle, $value) {
                    $inner->where('directory_contacts.social_media_ids', 'like', '%' . $needle . '%')
                        ->where('directory_contacts.social_media_ids', 'like', '%' . $value . '%');
                });
            }
        });
    }

    protected function classificationOptions()
    {
        return Cache::remember('contact_options.classifications', now()->addMinutes(5), function () {
            return ContactClassification::query()
                ->select(['id', 'parent_id', 'kind', 'name', 'status'])
                ->orderBy('name')
                ->get();
        });
    }

    protected function nullableTrimmedInput($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function parseTags(?string $tags): ?array
    {
        $items = collect(explode(',', (string) $tags))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique(fn ($tag) => strtolower($tag))
            ->values()
            ->all();

        return empty($items) ? null : $items;
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

    protected function activitiesEnabled(): bool
    {
        return Schema::hasTable('directory_contact_activities');
    }

    protected function activityFormData(): array
    {
        if (!$this->activitiesEnabled()) {
            return [
                'activitiesEnabled' => false,
                'activityTypes' => collect(),
                'activitySubStatuses' => collect(),
            ];
        }

        return [
            'activitiesEnabled' => true,
            'activityTypes' => $this->activityTypeOptions(),
            'activitySubStatuses' => auth()->user()?->can('edit_contact_directory')
                ? $this->activitySubStatusOptions()
                : collect(),
        ];
    }

    protected function activityTypeOptions()
    {
        if (!Schema::hasTable('lead_activity_types')) {
            return collect();
        }

        return DB::table('lead_activity_types')
            ->select(['id', 'title'])
            ->where('status', 1)
            ->orderBy('title')
            ->get();
    }

    protected function activitySubStatusOptions()
    {
        if (!Schema::hasTable('lead_activity_sub_statuses')) {
            return collect();
        }

        return DB::table('lead_activity_sub_statuses')
            ->select(['id', 'title'])
            ->where('status', 1)
            ->orderBy('title')
            ->get();
    }

    protected function validatedActivityData(Request $request): array
    {
        return $request->validate([
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

    protected function visitingCardIdsFromRequest(Request $request): array
    {
        $ids = collect(explode(',', (string) $request->input('visiting_cards', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->take(1)
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $validIds = Upload::query()
            ->whereIn('id', $ids->all())
            ->where('type', 'image')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($ids->diff($validIds)->isNotEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'visiting_cards' => translate('Please choose valid visiting card images'),
            ]);
        }

        return $ids->all();
    }

    protected function directoryVisitingCardSlides(): array
    {
        return $this->visitingCardSlides(
            DirectoryContact::query()->latest('directory_contacts.created_at')->orderBy('directory_contacts.id', 'desc')
        );
    }

    protected function visitingCardSlides($query): array
    {
        $rows = (clone $query)
            ->setEagerLoads([])
            ->select([
                'directory_contacts.id',
                'directory_contacts.name',
                'directory_contacts.visiting_cards',
            ])
            ->whereNotNull('directory_contacts.visiting_cards')
            ->where('directory_contacts.visiting_cards', '!=', '')
            ->get();

        $slides = [];
        foreach ($rows as $row) {
            $uploadId = $row->visitingCardId();
            if (!$uploadId) {
                continue;
            }

            $slides[] = [
                'contactId' => (int) $row->id,
                'title' => (string) $row->name,
                'src' => uploaded_asset($uploadId),
            ];
        }

        return $slides;
    }

    protected function visitingCardUploads(DirectoryContact $contact)
    {
        if (!Schema::hasColumn('directory_contacts', 'visiting_cards')) {
            return collect();
        }

        $ids = array_slice($contact->visitingCardIds(), 0, 1);
        if ($ids === []) {
            return collect();
        }

        return Upload::query()
            ->whereIn('id', $ids)
            ->where('type', 'image')
            ->get()
            ->sortBy(fn ($upload) => array_search((int) $upload->id, $ids, true))
            ->values();
    }

    protected function currentUserIsSuperAdmin(): bool
    {
        return auth()->check() && auth()->user()->hasRole('Super Admin');
    }
}
