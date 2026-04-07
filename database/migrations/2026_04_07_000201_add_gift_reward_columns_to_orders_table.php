<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
    * Run the migrations.
    *
    * @return void
    */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('gift_reward_applied_at')->nullable()->after('delivery_status');
            $table->json('gift_reward_applied_tier')->nullable()->after('gift_reward_applied_at');
        });
    }

    /**
    * Reverse the migrations.
    *
    * @return void
    */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['gift_reward_applied_at', 'gift_reward_applied_tier']);
        });
    }
};
