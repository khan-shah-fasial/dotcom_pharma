<?php

namespace App\Http\Controllers;

use App\Models\ContactClassification;
use App\Models\DirectoryContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ContactClassificationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_contact_directory'])->only('index');
        $this->middleware(['permission:view_contact_directory|add_contact_directory|edit_contact_directory'])->only('options');
        $this->middleware(['permission:add_contact_directory'])->only('store');
        $this->middleware(['permission:edit_contact_directory'])->only(['edit', 'update', 'updateStatus']);
        $this->middleware(['permission:delete_contact_directory'])->only('destroy');
    }

    public function index()
    {
        $classifications = ContactClassification::with('parent:id,name,kind')
            ->orderBy('name')
            ->get();

        $usageCounts = $this->usageCounts();
        $classifications = $this->flattenTrees($classifications);
        $classifications->each(function ($classification) use ($usageCounts) {
            $classification->setAttribute('usage_count', (int) ($usageCounts[$classification->id] ?? 0));
        });

        return view('backend.contact_management.masters.index', [
            'classifications' => $classifications,
            'kinds' => ContactClassification::KIND_LABELS,
            'parentOptions' => $this->parentOptionsByKind($classifications),
        ]);
    }

    public function options(Request $request)
    {
        $kind = (string) $request->input('kind');
        abort_unless(array_key_exists($kind, ContactClassification::KINDS), 422);

        $parentId = $request->filled('parent_id') ? (int) $request->input('parent_id') : null;
        $includeId = $request->filled('include_id') ? (int) $request->input('include_id') : null;

        $items = ContactClassification::query()
            ->select(['id', 'name', 'parent_id', 'kind', 'status'])
            ->where('kind', $kind)
            ->where(function ($query) use ($includeId) {
                $query->where('status', 1);
                if ($includeId) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->when($request->has('parent_id'), function ($query) use ($parentId) {
                if ($parentId) {
                    $query->where('parent_id', $parentId);
                } else {
                    $query->whereNull('parent_id');
                }
            })
            ->orderBy('name')
            ->get();

        return response()->json($items);
    }

    public function store(Request $request)
    {
        ContactClassification::create($this->validatedData($request));
        $this->forgetOptionsCache();

        flash(translate('Contact master item has been added successfully'))->success();
        return redirect()->route('contact-classifications.index');
    }

    public function edit(ContactClassification $contactClassification)
    {
        $parentKind = ContactClassification::parentKindFor($contactClassification->kind);
        $parents = $parentKind
            ? ContactClassification::ofKind($parentKind)->orderBy('name')->get(['id', 'name', 'status'])
            : collect();

        return view('backend.contact_management.masters.edit', [
            'classification' => $contactClassification,
            'kinds' => ContactClassification::KIND_LABELS,
            'parents' => $parents,
            'parentKind' => $parentKind,
        ]);
    }

    public function update(Request $request, ContactClassification $contactClassification)
    {
        $contactClassification->update($this->validatedData($request, $contactClassification));
        $this->forgetOptionsCache();

        flash(translate('Contact master item has been updated successfully'))->success();
        return redirect()->route('contact-classifications.index');
    }

    public function destroy(ContactClassification $contactClassification)
    {
        if ($contactClassification->children()->exists()) {
            flash(translate('Item cannot be deleted because child items exist'))->warning();
            return back();
        }

        if ($contactClassification->isUsedOnContacts()) {
            flash(translate('Item cannot be deleted because contacts are using it'))->warning();
            return back();
        }

        $contactClassification->delete();
        $this->forgetOptionsCache();

        flash(translate('Contact master item has been deleted successfully'))->success();
        return redirect()->route('contact-classifications.index');
    }

    public function updateStatus(Request $request)
    {
        $classification = ContactClassification::findOrFail($request->id);
        $classification->status = (int) $request->status === 1 ? 1 : 0;

        if ($classification->save()) {
            $this->forgetOptionsCache();
            return 1;
        }

        return 0;
    }

    protected function validatedData(Request $request, ?ContactClassification $classification = null): array
    {
        $kind = (string) $request->input('kind');
        $parentKind = array_key_exists($kind, ContactClassification::KINDS)
            ? ContactClassification::KINDS[$kind]
            : '__invalid__';

        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(ContactClassification::KINDS))],
            'parent_id' => array_filter([
                $parentKind ? 'required' : 'nullable',
                'integer',
                $parentKind
                    ? Rule::exists('contact_classifications', 'id')->where(function ($query) use ($parentKind, $classification) {
                        $query->where('kind', $parentKind);
                        if ($classification) {
                            $query->where(function ($statusQuery) use ($classification) {
                                $statusQuery->where('status', 1)
                                    ->orWhere('id', $classification->parent_id);
                            });
                        } else {
                            $query->where('status', 1);
                        }
                    })
                    : null,
            ]),
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('contact_classifications', 'name')
                    ->where(function ($query) use ($request) {
                        $query->where('kind', $request->kind);
                        if ($request->filled('parent_id')) {
                            $query->where('parent_id', $request->parent_id);
                        } else {
                            $query->whereNull('parent_id');
                        }
                    })
                    ->ignore($classification?->id),
            ],
            'status' => 'required|in:0,1',
        ]);

        $data['name'] = trim($data['name']);
        $data['status'] = (int) $data['status'];
        $data['parent_id'] = $parentKind ? (int) $data['parent_id'] : null;

        if ($classification && (int) ($data['parent_id'] ?? 0) === (int) $classification->id) {
            abort(422);
        }

        return $data;
    }

    protected function flattenTrees($classifications)
    {
        $byParent = $classifications->groupBy(fn ($item) => (string) ($item->parent_id ?: 0));
        $roots = $classifications->whereNull('parent_id')->sortBy(function ($item) {
            $order = array_search($item->kind, ContactClassification::ROOT_KINDS, true);
            return sprintf('%02d-%s', $order === false ? 99 : $order, strtolower($item->name));
        });

        $flat = collect();
        $walk = function ($nodes, int $depth) use (&$walk, $byParent, &$flat) {
            foreach ($nodes as $node) {
                $node->setAttribute('depth', $depth);
                $flat->push($node);
                $children = ($byParent->get((string) $node->id) ?? collect())->sortBy('name');
                $walk($children, $depth + 1);
            }
        };

        $walk($roots, 0);

        return $flat;
    }

    protected function parentOptionsByKind($classifications): array
    {
        $options = [];
        foreach (ContactClassification::KINDS as $kind => $parentKind) {
            $options[$kind] = $parentKind
                ? $classifications->where('kind', $parentKind)->filter(fn ($item) => (int) $item->status === 1)->values()
                : collect();
        }

        return $options;
    }

    protected function usageCounts(): array
    {
        if (!class_exists(DirectoryContact::class) || !Schema::hasTable('directory_contacts')) {
            return [];
        }

        $counts = [];
        foreach (ContactClassification::CONTACT_COLUMNS as $column) {
            $rows = DirectoryContact::query()
                ->selectRaw("{$column} as classification_id, COUNT(*) as aggregate")
                ->whereNotNull($column)
                ->groupBy($column)
                ->pluck('aggregate', 'classification_id');

            foreach ($rows as $id => $count) {
                $counts[$id] = ($counts[$id] ?? 0) + (int) $count;
            }
        }

        return $counts;
    }

    protected function forgetOptionsCache(): void
    {
        Cache::forget('contact_options.classifications');
    }
}
