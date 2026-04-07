<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\Address;
use App\Models\GiftRequest;
use App\Services\GiftRedemptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;

class GiftController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $gifts = Gift::where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();
        $walletBalance = $user?->balance ?? 0;
        $addresses = $user
            ? $user->addresses()
                ->with(['country', 'state', 'city'])
                ->where(function ($q) {
                    $q->where('type', Address::TYPE_SHIPPING)->orWhereNull('type');
                })
                ->get()
            : collect();

        return view('frontend.gifts.index', compact('gifts', 'walletBalance', 'addresses'));
    }

    public function redeem(Request $request, GiftRedemptionService $service)
    {
        $request->validate([
            'gift_id' => 'required|exists:tbl_gifts,id',
            'address_id' => 'required|exists:addresses,id',
            'idempotency_key' => 'nullable|string|max:191',
        ]);

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $address = Address::where('id', $request->address_id)
            ->where('user_id', $user->id)
            ->with(['country', 'state', 'city'])
            ->first();
        if (!$address) {
            throw new RuntimeException('Shipping address not found.');
        }

        $addressSnapshot = [
            'id' => $address->id,
            'address' => $address->address,
            'phone' => $address->phone,
            'postal_code' => $address->postal_code,
            'city' => optional($address->city)->name,
            'state' => optional($address->state)->name,
            'country' => optional($address->country)->name,
            'type' => $address->type,
            'set_default' => (bool) $address->set_default,
        ];

        $quantity = 1; // fixed per requirements
        $idempotencyKey = $request->input('idempotency_key') ?: Str::uuid()->toString();

        try {
            $service->redeem($user, (int) $request->gift_id, $idempotencyKey, $quantity, [
                'requested_from' => 'web',
                'shipping_address' => $addressSnapshot,
            ]);
            flash(translate('Gift redemption submitted.'))->success();
        } catch (RuntimeException $e) {
            flash($e->getMessage())->warning();
        } catch (\Throwable $e) {
            flash(translate('Unable to process gift redemption.'))->error();
        }

        return back();
    }

    public function requests()
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $requests = GiftRequest::with('gift')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('frontend.gifts.requests', compact('requests'));
    }
}
