<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\NoteTypeMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NoteTypeMasterController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_notes'])->only(['index']);
        $this->middleware(['permission:add_note'])->only(['create', 'store']);
        $this->middleware(['permission:edit_note'])->only(['edit', 'update', 'updateStatus']);
        $this->middleware(['permission:delete_note'])->only(['destroy']);
    }

    public function index(Request $request)
    {
        $tableReady = NoteTypeMaster::tableReady();
        $types = null;
        $sortBy = in_array($request->input('sort_by'), ['id', 'name', 'slug', 'status', 'updated_at'], true)
            ? $request->input('sort_by')
            : 'id';
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->input('search'));

        if ($tableReady) {
            $query = NoteTypeMaster::query();
            if ($search !== '') {
                $like = '%' . $search . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('slug', 'like', $like);
                });
            }
            $query->orderBy($sortBy, $sortDir);
            if ($sortBy !== 'id') {
                $query->orderBy('id', 'desc');
            }
            $types = $query->paginate(15)->withQueryString();
        }

        return view('backend.note.types.index', compact('types', 'tableReady', 'sortBy', 'sortDir', 'search'));
    }

    public function create()
    {
        if (! NoteTypeMaster::tableReady()) {
            flash(translate('Note Type Master table is not ready yet.'))->error();

            return redirect()->route('note_types.index');
        }

        return view('backend.note.types.create', ['type' => null]);
    }

    public function store(Request $request)
    {
        if (! NoteTypeMaster::tableReady()) {
            flash(translate('Note Type Master table is not ready yet.'))->error();

            return back()->withInput();
        }

        $validated = $this->validateType($request);
        $type = new NoteTypeMaster();
        $type->fill($validated);
        $type->save();

        flash(translate('Note type created successfully.'))->success();

        return redirect()->route('note_types.index');
    }

    public function edit($id)
    {
        if (! NoteTypeMaster::tableReady()) {
            flash(translate('Note Type Master table is not ready yet.'))->error();

            return redirect()->route('note_types.index');
        }

        $type = NoteTypeMaster::findOrFail($id);

        return view('backend.note.types.edit', compact('type'));
    }

    public function update(Request $request, $id)
    {
        $type = NoteTypeMaster::findOrFail($id);
        $oldSlug = $type->slug;
        $validated = $this->validateType($request, $type->id);

        DB::transaction(function () use ($type, $validated, $oldSlug) {
            $type->fill($validated);
            $type->save();

            if ($oldSlug !== $type->slug) {
                Note::where('note_type', $oldSlug)->update(['note_type' => $type->slug]);
            }
        });

        flash(translate('Note type updated successfully.'))->success();

        return redirect()->route('note_types.index');
    }

    public function updateStatus(Request $request)
    {
        $type = NoteTypeMaster::findOrFail($request->id);
        $type->status = $request->status == 1 ? 1 : 0;
        $type->save();

        return response()->json(1);
    }

    public function destroy($id)
    {
        $type = NoteTypeMaster::findOrFail($id);
        $used = Note::where('note_type', $type->slug)->count();
        if ($used > 0) {
            flash(translate('Cannot delete: this type is used by notes. Deactivate it instead.'))->error();

            return back();
        }

        $type->delete();
        flash(translate('Note type deleted successfully.'))->success();

        return back();
    }

    protected function validateType(Request $request, ?int $ignoreId = null): array
    {
        $name = trim((string) $request->input('name'));
        $slugInput = trim((string) $request->input('slug'));
        $slug = $slugInput !== ''
            ? NoteTypeMaster::makeSlug($slugInput, $ignoreId)
            : NoteTypeMaster::makeSlug($name, $ignoreId);

        $request->merge(['name' => $name, 'slug' => $slug]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('note_types', 'slug')->ignore($ignoreId),
            ],
            'status' => ['nullable', 'boolean'],
        ], [
            'slug.regex' => translate('Slug may only contain lowercase letters, numbers and underscores.'),
        ]);

        $validated['status'] = $request->has('status') ? 1 : 0;

        return $validated;
    }
}
