<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'referral_points')) {
                $table->dropColumn('referral_points');
            }
            if (Schema::hasColumn('users', 'referral_reward_granted_at')) {
                $table->dropColumn('referral_reward_granted_at');
            }
            if (Schema::hasColumn('users', 'referral_discount_used_at')) {
                $table->dropColumn('referral_discount_used_at');
            }
            if (Schema::hasColumn('users', 'referral_discount_locked_at')) {
                $table->dropColumn('referral_discount_locked_at');
            }
            if (Schema::hasColumn('users', 'referral_discount_locked_order_id')) {
                $table->dropColumn('referral_discount_locked_order_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'referral_discount_used_at')) {
                $table->timestamp('referral_discount_used_at')->nullable()->after('referral_code');
            }
            if (!Schema::hasColumn('users', 'referral_discount_locked_at')) {
                $table->timestamp('referral_discount_locked_at')->nullable()->after('referral_discount_used_at');
            }
            if (!Schema::hasColumn('users', 'referral_discount_locked_order_id')) {
                $table->unsignedBigInteger('referral_discount_locked_order_id')->nullable()->after('referral_discount_locked_at');
            }
            if (!Schema::hasColumn('users', 'referral_reward_granted_at')) {
                $table->timestamp('referral_reward_granted_at')->nullable()->after('referral_discount_locked_order_id');
            }
            if (!Schema::hasColumn('users', 'referral_points')) {
                $table->unsignedInteger('referral_points')->default(0)->after('referral_reward_granted_at');
            }
        });
    }
};
