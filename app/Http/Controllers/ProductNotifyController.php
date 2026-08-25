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
        // #region agent log
        @file_put_contents(base_path('debug-a0012d.log'), json_encode(['sessionId'=>'a0012d','hypothesisId'=>'B','runId'=>'post-fix','location'=>'ProductNotifyController.php:store','message'=>'store entered','data'=>['auth'=>\Illuminate\Support\Facades\Auth::check(),'user_type'=>optional(\Illuminate\Support\Facades\Auth::user())->user_type,'user_id'=>\Illuminate\Support\Facades\Auth::id(),'product_id'=>$request->input('product_id'),'wants_json'=>$request->expectsJson(),'ajax'=>$request->ajax()],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND);
        // #endregion

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
            try {
                ProductNotify::create([
                    'user_id'    => $userId,
                    'product_id' => $productId,
                ]);
                $created = true;
            } catch (\Throwable $e) {
                // #region agent log
                @file_put_contents(base_path('debug-a0012d.log'), json_encode(['sessionId'=>'a0012d','hypothesisId'=>'E','location'=>'ProductNotifyController.php:store','message'=>'create failed','data'=>['error'=>$e->getMessage(),'user_id'=>$userId,'product_id'=>$productId],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND);
                // #endregion
                throw $e;
            }
            Log::info('[ProductNotify] subscribed', ['user_id' => $userId, 'product_id' => $productId]);
        } else {
            $created = false; // already there
        }

        // #region agent log
        @file_put_contents(base_path('debug-a0012d.log'), json_encode(['sessionId'=>'a0012d','hypothesisId'=>'E','runId'=>'post-fix','location'=>'ProductNotifyController.php:store','message'=>'store result','data'=>['user_id'=>$userId,'product_id'=>$productId,'exists'=>$exists,'created'=>$created ?? false],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND);
        // #endregion

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
