<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GiftController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'name' => trim((string) $request->input('name', $request->input('search', ''))),
            'description' => trim((string) $request->input('description')),
            'cost_from' => $request->input('cost_from'),
            'cost_to' => $request->input('cost_to'),
            'stock_from' => $request->input('stock_from'),
            'stock_to' => $request->input('stock_to'),
            'updated_from' => trim((string) $request->input('updated_from')),
            'updated_to' => trim((string) $request->input('updated_to')),
            'status' => (string) $request->input('status', ''),
        ];

        $sortBy = in_array($request->input('sort_by'), ['updated_at', 'name', 'cost', 'description', 'stock'], true)
            ? $request->input('sort_by')
            : 'updated_at';
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = Gift::query();

        if ($filters['name'] !== '') {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if ($filters['description'] !== '') {
            $query->where('description', 'like', '%' . $filters['description'] . '%');
        }

        $costFrom = is_numeric($filters['cost_from']) ? (float) $filters['cost_from'] : null;
        $costTo = is_numeric($filters['cost_to']) ? (float) $filters['cost_to'] : null;
        if (! ($costFrom !== null && $costTo !== null && $costFrom > $costTo)) {
            if ($costFrom !== null) {
                $query->where('cost', '>=', $costFrom);
            }
            if ($costTo !== null) {
                $query->where('cost', '<=', $costTo);
            }
        }

        $stockFrom = is_numeric($filters['stock_from']) ? (int) $filters['stock_from'] : null;
        $stockTo = is_numeric($filters['stock_to']) ? (int) $filters['stock_to'] : null;
        if (! ($stockFrom !== null && $stockTo !== null && $stockFrom > $stockTo)) {
            if ($stockFrom !== null) {
                $query->where('stock', '>=', $stockFrom);
            }
            if ($stockTo !== null) {
                $query->where('stock', '<=', $stockTo);
            }
        }

        $updatedFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['updated_from']) ? $filters['updated_from'] : '';
        $updatedTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['updated_to']) ? $filters['updated_to'] : '';
        if (! ($updatedFrom !== '' && $updatedTo !== '' && $updatedFrom > $updatedTo)) {
            if ($updatedFrom !== '') {
                $query->whereDate('updated_at', '>=', $updatedFrom);
            }
            if ($updatedTo !== '') {
                $query->whereDate('updated_at', '<=', $updatedTo);
            }
        }

        if ($filters['status'] === '1' || $filters['status'] === '0') {
            $query->where('is_active', $filters['status'] === '1');
        }

        $query->orderBy($sortBy, $sortDir);
        if ($sortBy !== 'id') {
            $query->orderBy('id', 'desc');
        }

        $gifts = $query->paginate(15)->withQueryString();

        return view('backend.gifts.index', compact('gifts', 'filters', 'sortBy', 'sortDir'));
    }

    public function create()
    {
        $gift = new Gift(['is_active' => true, 'stock' => 0, 'cost' => 0]);
        return view('backend.gifts.create', compact('gift'));
    }

    public function store(Request $request)
    {
        Log::info('Gift store: received request', request()->all());
        // Normalize checkbox input so validation accepts the value Laravel's boolean rule expects.
        $isActive = $request->boolean('is_active', false);
        $request->merge(['is_active' => $isActive]);
        Log::info('is_active check', [
            'raw' => $request->input('is_active'),
            'boolean' => $isActive,
        ]);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'photos' => 'nullable|string',
            'thumbnail_id' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);
        if ($validator->fails()) {
            Log::warning('Gift store: validation failed', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->all(),
            ]);
            throw new ValidationException($validator);
        }
        $data = $validator->validated();
        Log::info('Gift store: validation passed');

        $photos = isset($data['photos']) && $data['photos'] !== ''
            ? array_values(array_filter(explode(',', $data['photos'])))
            : [];
        Log::info('Gift store: parsed photos', ['photos' => $photos]);

        $gift = new Gift();
        $gift->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'cost' => $data['cost'],
            'stock' => $data['stock'],
        ]);
        $gift->is_active = $isActive;
        $gift->photos = $photos;
        $gift->thumbnail_id = $data['thumbnail_id'] ?? null;
        $gift->created_by = Auth::id();
        Log::info('Gift store: about to save', ['gift' => $gift->toArray()]);
        $gift->save();
        Log::info('Gift store: saved', ['gift_id' => $gift->id]);

        Log::info('Gift created', [
            'gift_id' => $gift->id,
            'user_id' => Auth::id(),
            'name' => $gift->name,
            'photos' => $photos,
            'thumbnail_id' => $gift->thumbnail_id,
        ]);

        flash(translate('Gift created successfully.'))->success();
        return back();
    }

    public function update(Request $request, Gift $gift)
    {
        // Normalize checkbox/hidden input to a real boolean before validation.
        $isActive = $request->boolean('is_active', $gift->is_active);
        $request->merge(['is_active' => $isActive]);

        $request->validate([
            'name' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'photos' => 'nullable|string',
            'thumbnail_id' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $photos = $request->filled('photos')
            ? array_values(array_filter(explode(',', $request->photos)))
            : [];

        $gift->fill($request->only(['name', 'description', 'cost', 'stock']));
        $gift->is_active = $isActive;
        $gift->photos = $photos;
        $gift->thumbnail_id = $request->thumbnail_id;
        $gift->updated_by = Auth::id();
        $gift->save();

        Log::info('Gift updated', [
            'gift_id' => $gift->id,
            'user_id' => Auth::id(),
            'name' => $gift->name,
            'photos' => $photos,
            'thumbnail_id' => $gift->thumbnail_id,
        ]);

        flash(translate('Gift updated successfully.'))->success();
        return back();
    }

    public function edit(Gift $gift)
    {
        return view('backend.gifts.edit', compact('gift'));
    }

    public function toggleStatus(Gift $gift)
    {
        $gift->is_active = !$gift->is_active;
        $gift->updated_by = Auth::id();
        $gift->save();
        flash(translate('Gift status updated.'))->success();
        return back();
    }

    public function destroy(Gift $gift)
    {
        $gift->delete();
        flash(translate('Gift deleted.'))->success();
        return back();
    }
}
