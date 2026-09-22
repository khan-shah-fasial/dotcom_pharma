<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\NoteTranslation;
use App\Models\NoteTypeMaster;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NoteController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:view_notes'])->only('index');
        $this->middleware(['permission:add_note'])->only(['create', 'store']);
        $this->middleware(['permission:edit_note'])->only(['edit', 'update']);
        $this->middleware(['permission:delete_note'])->only('destroy');
    }

    public function index(Request $request)
    {
        $filters = [
            'note_type' => trim((string) $request->input('note_type')),
            'description' => trim((string) $request->input('description')),
        ];

        $sortBy = in_array($request->input('sort_by'), ['id', 'note_type', 'description', 'updated_at'], true)
            ? $request->input('sort_by')
            : 'id';
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = Note::query();

        if ($filters['note_type'] !== '') {
            $query->where('note_type', $filters['note_type']);
        }

        if ($filters['description'] !== '') {
            $like = '%' . $filters['description'] . '%';
            $query->where(function ($q) use ($like) {
                $q->where('description', 'like', $like)
                    ->orWhereHas('note_translations', function ($tq) use ($like) {
                        $tq->where('description', 'like', $like);
                    });
            });
        }

        $query->orderBy($sortBy, $sortDir);
        if ($sortBy !== 'id') {
            $query->orderBy('id', 'desc');
        }

        $notes = $query->paginate(15)->withQueryString();
        $types = NoteTypeMaster::activeOptions();

        return view('backend.note.index', compact('notes', 'types', 'filters', 'sortBy', 'sortDir'));
    }

    public function create()
    {
        $types = NoteTypeMaster::activeOptions();

        return view('backend.note.create', compact('types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'note_type' => [
                'required',
                'string',
                'max:50',
                Rule::exists('note_types', 'slug')->where(fn ($q) => $q->where('status', 1)),
            ],
            'description' => ['required', 'string'],
        ]);

        $note = new Note();
        $note->note_type = $validated['note_type'];
        $note->description = $validated['description'];
        $note->save();

        $note_translation = NoteTranslation::firstOrNew([
            'lang' => env('DEFAULT_LANGUAGE'),
            'note_id' => $note->id,
        ]);
        $note_translation->description = $validated['description'];
        $note_translation->save();

        flash(translate('Note has been created successfully!'))->success();

        return redirect()->route('note.index');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(Request $request, $id)
    {
        $lang = $request->lang;
        $types = NoteTypeMaster::activeOptions();
        $note = Note::findOrFail($id);

        // Keep current type selectable even if deactivated
        if ($note->note_type && ! $types->contains('slug', $note->note_type)) {
            $current = NoteTypeMaster::query()->where('slug', $note->note_type)->first();
            if ($current) {
                $types = $types->prepend($current)->unique('id')->values();
            }
        }

        return view('backend.note.edit', compact('note', 'types', 'lang'));
    }

    public function update(Request $request, $id)
    {
        $note = Note::findOrFail($id);

        $validated = $request->validate([
            'note_type' => [
                'required',
                'string',
                'max:50',
                Rule::exists('note_types', 'slug'),
            ],
            'description' => ['required', 'string'],
            'lang' => ['nullable', 'string'],
        ]);

        $note->note_type = $validated['note_type'];
        if ($request->lang == env('DEFAULT_LANGUAGE')) {
            $note->description = $validated['description'];
        }
        $note->save();

        $note_translation = NoteTranslation::firstOrNew([
            'lang' => $request->lang,
            'note_id' => $note->id,
        ]);
        $note_translation->description = $validated['description'];
        $note_translation->save();

        flash(translate('Note has been updated successfully!'))->success();

        return back();
    }

    public function destroy(Note $note)
    {
        $note = Note::findOrFail($note->id);
        $note->note_translations()->delete();
        $note->delete();
        flash(translate('Note has been deleted successfully!'))->success();

        return back();
    }

    public function getNotes(Request $request)
    {
        $noteType = $request->note_type;
        $notes = Note::where('note_type', $noteType)->get();

        return view('backend.note.get_notes', compact('notes', 'noteType'));
    }
}
