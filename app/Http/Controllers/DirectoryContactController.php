<?php

namespace App\Http\Controllers;

use App\Models\ContactClassification;
use App\Models\Country;
use App\Models\DirectoryContact;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DirectoryContactController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_contact_directory'])->only(['index', 'show']);
        $this->middleware(['permission:add_contact_directory'])->only(['create', 'store']);
        $this->middleware(['permission:edit_contact_directory'])->only(['edit', 'update']);
        $this->middleware(['permission:delete_contact_directory'])->only('destroy');
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'search',
            'group_id',
            'industry_id',
            'department_id',
            'tags',
        ]);

        $contacts = DirectoryContact::query()
            ->select([
                'directory_contacts.id',
                'directory_contacts.contact_no',
                'directory_contacts.name',
                'directory_contacts.email',
                'directory_contacts.phone',
                'directory_contacts.alternate_mobile_number',
                'directory_contacts.whatsapp_number',
                'directory_contacts.company_name',
                'directory_contacts.group_id',
                'directory_contacts.industry_id',
                'directory_contacts.department_id',
                'directory_contacts.created_by',
                'directory_contacts.updated_by',
                'directory_contacts.created_at',
                'directory_contacts.updated_at',
            ])
            ->with([
                'group:id,name',
                'industry:id,name',
                'department:id,name',
                'creator:id,name',
                'updater:id,name',
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
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

        foreach (['group_id', 'industry_id', 'department_id'] as $field) {
            if ($request->filled($field)) {
                $contacts->where($field, $request->input($field));
            }
        }

        if ($request->filled('tags')) {
            $tag = trim((string) $request->tags);
            $contacts->where(function ($query) use ($tag) {
                $query->whereJsonContains('tags', $tag)
                    ->orWhere('tags', 'like', '%' . $tag . '%');
            });
        }

        $contacts = $contacts->latest('directory_contacts.created_at')->paginate(20);

        return view('backend.contact_management.index', $this->indexData() + [
            'contacts' => $contacts,
            'filters' => $filters,
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

    public function show(DirectoryContact $directoryContact)
    {
        $directoryContact->load([
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
        ]);

        return view('backend.contact_management.show', [
            'contact' => $directoryContact,
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
        ]);

        $phoneRules = ['nullable', 'string', 'max:50', 'regex:/^\+?[0-9\s().-]{7,20}$/'];

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'designation' => 'nullable|string|max:255',
            'photo' => 'nullable|integer|exists:uploads,id',
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

        return [
            'groups' => $classifications->where('kind', 'group')->where('status', 1)->sortBy('name')->values(),
            'industries' => $classifications->where('kind', 'industry')->where('status', 1)->sortBy('name')->values(),
            'departments' => $classifications->where('kind', 'department')->where('status', 1)->sortBy('name')->values(),
        ];
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
}
