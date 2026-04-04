<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            if (!Schema::hasColumn('wallets', 'transaction_type')) {
                $table->string('transaction_type')->default('recharge')->after('amount');
            }
            if (!Schema::hasColumn('wallets', 'reference_type')) {
                $table->string('reference_type')->nullable()->after('payment_details');
            }
            if (!Schema::hasColumn('wallets', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            }
            if (!Schema::hasColumn('wallets', 'meta')) {
                $table->longText('meta')->nullable()->after('reference_id');
            }
        });

        try {
            Schema::table('wallets', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'transaction_type', 'reference_type', 'reference_id'],
                    'wallet_ref_tx_unique'
                );
            });
        } catch (\Throwable $e) {
            // Index may already exist on some environments.
        }

        $oldValue = DB::table('business_settings')
            ->where('type', 'referral_reward_points_for_referrer')
            ->value('value');
        $newExists = DB::table('business_settings')
            ->where('type', 'referral_reward_amount_for_referrer')
            ->exists();

        if (!$newExists) {
            DB::table('business_settings')->insert([
                'type' => 'referral_reward_amount_for_referrer',
                'value' => $oldValue ?? '0',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        try {
            Schema::table('wallets', function (Blueprint $table) {
                $table->dropUnique('wallet_ref_tx_unique');
            });
        } catch (\Throwable $e) {
        }

        Schema::table('wallets', function (Blueprint $table) {
            if (Schema::hasColumn('wallets', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('wallets', 'reference_id')) {
                $table->dropColumn('reference_id');
            }
            if (Schema::hasColumn('wallets', 'reference_type')) {
                $table->dropColumn('reference_type');
            }
            if (Schema::hasColumn('wallets', 'transaction_type')) {
                $table->dropColumn('transaction_type');
            }
        });
    }
};
