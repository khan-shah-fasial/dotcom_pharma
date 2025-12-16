<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMinQtyAndExpiryToProductStocks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            if (!Schema::hasColumn('product_stocks', 'min_qty')) {
                $table->integer('min_qty')->default(1)->after('count');
            }

            if (!Schema::hasColumn('product_stocks', 'product_min_pack_size')) {
                $table->integer('product_min_pack_size')->default(1)->after('min_qty');
            }

            if (!Schema::hasColumn('product_stocks', 'product_exp_date')) {
                $table->string('product_exp_date')->nullable()->after('product_min_pack_size');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            if (Schema::hasColumn('product_stocks', 'product_exp_date')) {
                $table->dropColumn('product_exp_date');
            }

            if (Schema::hasColumn('product_stocks', 'product_min_pack_size')) {
                $table->dropColumn('product_min_pack_size');
            }

            if (Schema::hasColumn('product_stocks', 'min_qty')) {
                $table->dropColumn('min_qty');
            }
        });
    }
}
