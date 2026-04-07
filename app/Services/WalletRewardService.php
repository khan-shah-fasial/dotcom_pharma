<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\WalletRewardCredited;
use Illuminate\Support\Facades\DB;

class WalletRewardService
{
    /**
     * Apply wallet gift reward for a paid product order based on configured tiers.
     *
     * The method is idempotent and safe to call multiple times; it will no-op
     * when rewards were already applied or when no tier matches.
     */
    public function applyReward(Order $order): void
    {
        // Preconditions
        if ($order->payment_status !== 'paid') {
            return;
        }

        // Respect optional order type flag if present on the model/record.
        if (isset($order->order_type) && $order->order_type !== 'product') {
            return;
        }

        if ($order->gift_reward_applied_at) {
            return;
        }

        $tiers = get_gift_reward_tiers();
        if (empty($tiers)) {
            return;
        }

        $tier = null;
        foreach ($tiers as $candidate) {
            $min = $candidate['min'] ?? null;
            if ($min === null) {
                continue;
            }
            if ((float) $order->grand_total >= (float) $min) {
                $tier = $candidate;
                break;
            }
        }

        if (!$tier || empty($tier['reward'])) {
            return;
        }

        DB::transaction(function () use ($order, $tier) {
            $orderForUpdate = Order::where('id', $order->id)->lockForUpdate()->first();
            if (!$orderForUpdate || $orderForUpdate->gift_reward_applied_at) {
                return;
            }

            // Ensure order is still paid after locks.
            if ($orderForUpdate->payment_status !== 'paid') {
                return;
            }

            if (isset($orderForUpdate->order_type) && $orderForUpdate->order_type !== 'product') {
                return;
            }

            $existingWallet = Wallet::where([
                'reference_type' => 'order',
                'reference_id'   => $orderForUpdate->id,
                'transaction_type' => 'gift_reward',
            ])->lockForUpdate()->first();

            if ($existingWallet) {
                return;
            }

            $user = User::where('id', $orderForUpdate->user_id)->lockForUpdate()->first();
            if (!$user) {
                return;
            }

            $payload = [
                'order_id' => (int) $orderForUpdate->id,
                'tier_min' => (float) $tier['min'],
                'reward'   => (float) $tier['reward'],
            ];

            $wallet = new Wallet;
            $wallet->user_id = $user->id;
            $wallet->amount = (float) $tier['reward'];
            $wallet->transaction_type = 'gift_reward';
            $wallet->payment_method = 'order_reward';
            $wallet->reference_type = 'order';
            $wallet->reference_id = $orderForUpdate->id;
            $wallet->meta = json_encode($payload);
            $wallet->save();

            $user->balance = (float) $user->balance + (float) $tier['reward'];
            $user->save();

            $orderForUpdate->gift_reward_applied_at = now();
            $orderForUpdate->gift_reward_applied_tier = json_encode($tier);
            $orderForUpdate->save();

            $user->notify(new WalletRewardCredited($tier['reward'], $orderForUpdate->id));
        });
    }
}
