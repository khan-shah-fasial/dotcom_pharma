<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ProductNotify;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductNotifyController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => translate('Please login first')], 401);
        }

        $productId = (int) $request->input('product_id');
        $userId    = Auth::id();

        $exists = ProductNotify::where('user_id', $userId)->where('product_id', $productId)->exists();

        if (!$exists) {
            ProductNotify::create([
                'user_id'    => $userId,
                'product_id' => $productId,
            ]);
            Log::info('[ProductNotify] subscribed', ['user_id' => $userId, 'product_id' => $productId]);
            $created = true;
        } else {
            $created = false; // already there
        }

        return response()->json(['success' => true, 'subscribed' => true, 'created' => $created]);
    }

    public function remove(Request $request)
    {
        $request->validate([
            'id' => ['required_without:product_id', 'nullable', 'integer'],
            'product_id' => ['required_without:id', 'nullable', 'integer'],
        ]);

        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => translate('Please login first')], 401);
        }

        $query = ProductNotify::where('user_id', Auth::id());
        if ($request->filled('id')) {
            $query->where('id', $request->input('id'));
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        $deleted = $query->delete();

        Log::info('[ProductNotify] unsubscribed', [
            'user_id'    => Auth::id(),
            'product_id' => $request->input('product_id'),
            'id'         => $request->input('id'),
            'deleted'    => $deleted,
        ]);

        return response()->json(['success' => (bool)$deleted, 'deleted' => (int)$deleted]);
    }
}
