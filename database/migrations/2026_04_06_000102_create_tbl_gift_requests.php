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
        Schema::create('tbl_gift_requests', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedInteger('user_id'); // ✅ FIXED
            $table->unsignedBigInteger('gift_id');

            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('cost_snapshot', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->string('idempotency_key')->unique();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedBigInteger('refund_txn_id')->nullable();
            $table->text('admin_note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('gift_id')
                ->references('id')
                ->on('tbl_gifts')
                ->onDelete('cascade');

            $table->index(['user_id', 'gift_id', 'status']);
            $table->index(['status', 'processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_gift_requests');
    }
};
