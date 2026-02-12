<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameBatcheIdToBatchIdIfExists extends Migration
{
    /**
     * Run the migrations.
     * Rename batche_id to batch_id in carts and order_details if old column exists (e.g. from previous spelling).
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('carts', 'batche_id') && !Schema::hasColumn('carts', 'batch_id')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->renameColumn('batche_id', 'batch_id');
            });
        }
        if (Schema::hasColumn('order_details', 'batche_id') && !Schema::hasColumn('order_details', 'batch_id')) {
            Schema::table('order_details', function (Blueprint $table) {
                $table->renameColumn('batche_id', 'batch_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('carts', 'batch_id') && !Schema::hasColumn('carts', 'batche_id')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->renameColumn('batch_id', 'batche_id');
            });
        }
        if (Schema::hasColumn('order_details', 'batch_id') && !Schema::hasColumn('order_details', 'batche_id')) {
            Schema::table('order_details', function (Blueprint $table) {
                $table->renameColumn('batch_id', 'batche_id');
            });
        }
    }
}
