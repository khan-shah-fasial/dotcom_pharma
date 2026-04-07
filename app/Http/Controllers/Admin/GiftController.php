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
        $search = $request->input('search');
        $status = $request->input('status');

        $gifts = Gift::query()
            ->when($search, fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
            ->when($status !== null && $status !== '', fn ($q) => $q->where('is_active', (bool) $status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('backend.gifts.index', compact('gifts', 'search', 'status'));
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
