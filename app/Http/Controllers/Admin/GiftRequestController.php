<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftRequest;
use App\Services\GiftRedemptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class GiftRequestController extends Controller
{
    public function index()
    {
        $requests = GiftRequest::with(['user', 'gift'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('backend.gifts.requests', compact('requests'));
    }

    public function approve(Request $request, GiftRedemptionService $service)
    {
        $request->validate([
            'request_id' => 'required|exists:tbl_gift_requests,id',
            'note' => 'nullable|string',
        ]);

        $giftRequest = GiftRequest::findOrFail($request->request_id);
        try {
            $service->approve($giftRequest, Auth::id(), $request->note);
            flash(translate('Gift request approved.'))->success();
        } catch (RuntimeException $e) {
            flash($e->getMessage())->warning();
        } catch (\Throwable $e) {
            flash(translate('Unable to approve request.'))->error();
        }

        return back();
    }

    public function deliver(Request $request, GiftRedemptionService $service)
    {
        $request->validate([
            'request_id' => 'required|exists:tbl_gift_requests,id',
            'note' => 'nullable|string',
        ]);

        $giftRequest = GiftRequest::findOrFail($request->request_id);
        try {
            $service->deliver($giftRequest, Auth::id(), $request->note);
            flash(translate('Gift marked as delivered.'))->success();
        } catch (RuntimeException $e) {
            flash($e->getMessage())->warning();
        } catch (\Throwable $e) {
            flash(translate('Unable to mark delivered.'))->error();
        }

        return back();
    }

    public function reject(Request $request, GiftRedemptionService $service)
    {
        $request->validate([
            'request_id' => 'required|exists:tbl_gift_requests,id',
            'reason' => 'nullable|string',
        ]);

        $giftRequest = GiftRequest::findOrFail($request->request_id);
        try {
            $service->reject($giftRequest, Auth::id(), $request->reason);
            flash(translate('Gift request rejected and refunded.'))->success();
        } catch (RuntimeException $e) {
            flash($e->getMessage())->warning();
        } catch (\Throwable $e) {
            flash(translate('Unable to reject request.'))->error();
        }

        return back();
    }
}
