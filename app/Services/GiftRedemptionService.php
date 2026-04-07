<?php

namespace App\Services;

use App\Models\Gift;
use App\Models\GiftRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\GiftRequestStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class GiftRedemptionService
{
    /**
     * Redeem a gift: deduct balance, decrement stock, log wallet, create request.
     */
    public function redeem(User $user, int $giftId, string $idempotencyKey, int $quantity = 1, array $meta = []): GiftRequest
    {
        return DB::transaction(function () use ($user, $giftId, $idempotencyKey, $quantity, $meta) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $gift = Gift::where('id', $giftId)
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotency: return existing request if the same key exists.
            $existingByKey = GiftRequest::where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
            if ($existingByKey) {
                return $existingByKey;
            }

            if (!$gift->is_active || $gift->deleted_at !== null) {
                throw new RuntimeException('Gift is not available.');
            }

            if ($gift->stock < $quantity) {
                throw new RuntimeException('Insufficient gift stock.');
            }

            $cost = (float) $gift->cost * $quantity;
            if ($lockedUser->balance < $cost) {
                throw new RuntimeException("Insufficient wallet balance.");
            }

            $gift->stock -= $quantity;
            $gift->save();

            $lockedUser->balance -= $cost;
            $lockedUser->save();

            $request = new GiftRequest();
            $request->user_id = $lockedUser->id;
            $request->gift_id = $gift->id;
            $request->quantity = $quantity;
            $request->cost_snapshot = $cost;
            $request->status = 'pending';
            $request->idempotency_key = $idempotencyKey ?: Str::uuid()->toString();
            $request->meta = $meta;
            $request->save();

            $wallet = new Wallet();
            $wallet->user_id = $lockedUser->id;
            $wallet->amount = -$cost;
            $wallet->payment_method = 'Gift Redemption';
            $wallet->payment_details = json_encode([
                'gift_id' => $gift->id,
                'gift_name' => $gift->name,
                'quantity' => $quantity,
            ]);
            $wallet->transaction_type = 'gift_redeem';
            $wallet->reference_type = 'gift_request';
            $wallet->reference_id = $request->id;
            $wallet->meta = $wallet->payment_details;
            $wallet->save();

            return $request;
        });
    }

    /**
     * Approve a pending gift request (no balance change).
     */
    public function approve(GiftRequest $giftRequest, int $adminUserId, ?string $adminNote = null): GiftRequest
    {
        return DB::transaction(function () use ($giftRequest, $adminUserId, $adminNote) {
            $lockedRequest = GiftRequest::where('id', $giftRequest->id)->lockForUpdate()->firstOrFail();
            if ($lockedRequest->status !== 'pending') {
                return $lockedRequest;
            }

            $gift = Gift::where('id', $lockedRequest->gift_id)->lockForUpdate()->first();
            if (!$gift || !$gift->is_active || $gift->deleted_at !== null) {
                throw new RuntimeException('Gift is no longer available for approval.');
            }

            $lockedRequest->status = 'approved';
            $lockedRequest->processed_by = $adminUserId;
            $lockedRequest->processed_at = now();
            $lockedRequest->admin_note = $adminNote;
            $lockedRequest->save();

            optional($lockedRequest->user)->notify(new GiftRequestStatusChanged($lockedRequest, $adminNote));

            return $lockedRequest;
        });
    }

    /**
     * Mark an approved gift request as delivered.
     */
    public function deliver(GiftRequest $giftRequest, int $adminUserId, ?string $adminNote = null): GiftRequest
    {
        return DB::transaction(function () use ($giftRequest, $adminUserId, $adminNote) {
            $lockedRequest = GiftRequest::where('id', $giftRequest->id)->lockForUpdate()->firstOrFail();
            if ($lockedRequest->status !== 'approved') {
                throw new RuntimeException('Only approved requests can be marked delivered.');
            }

            $lockedRequest->status = 'delivered';
            $lockedRequest->processed_by = $adminUserId;
            $lockedRequest->processed_at = now();
            $lockedRequest->admin_note = $adminNote;
            $lockedRequest->save();

            optional($lockedRequest->user)->notify(new GiftRequestStatusChanged($lockedRequest, $adminNote));

            return $lockedRequest;
        });
    }

    /**
     * Reject a pending gift request, refund balance, restock gift, log wallet refund.
     */
    public function reject(GiftRequest $giftRequest, int $adminUserId, ?string $reason = null): GiftRequest
    {
        return DB::transaction(function () use ($giftRequest, $adminUserId, $reason) {
            $lockedRequest = GiftRequest::where('id', $giftRequest->id)->lockForUpdate()->firstOrFail();
            if ($lockedRequest->status !== 'pending') {
                return $lockedRequest;
            }

            $user = User::where('id', $lockedRequest->user_id)->lockForUpdate()->firstOrFail();
            $gift = Gift::where('id', $lockedRequest->gift_id)->lockForUpdate()->first();

            if ($gift) {
                $gift->stock += $lockedRequest->quantity;
                $gift->save();
            }

            $user->balance += $lockedRequest->cost_snapshot;
            $user->save();

            $refundWallet = new Wallet();
            $refundWallet->user_id = $user->id;
            $refundWallet->amount = $lockedRequest->cost_snapshot;
            $refundWallet->payment_method = 'Gift Refund';
            $refundWallet->payment_details = json_encode([
                'gift_request_id' => $lockedRequest->id,
                'reason' => $reason,
            ]);
            $refundWallet->transaction_type = 'gift_refund';
            $refundWallet->reference_type = 'gift_request';
            $refundWallet->reference_id = $lockedRequest->id;
            $refundWallet->meta = $refundWallet->payment_details;
            $refundWallet->save();

            $lockedRequest->status = 'rejected';
            $lockedRequest->processed_by = $adminUserId;
            $lockedRequest->processed_at = now();
            $lockedRequest->admin_note = $reason;
            $lockedRequest->refund_txn_id = $refundWallet->id;
            $lockedRequest->save();

            optional($lockedRequest->user)->notify(new GiftRequestStatusChanged($lockedRequest, $reason));

            return $lockedRequest;
        });
    }
}
