<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBeforeProductandbatchDiscountToCartsAndOrderDetails extends Migration
{
    public function up()
    {
        Schema::table('carts', function (Blueprint $table) {
            if (!Schema::hasColumn('carts', 'before_productandbatch_discount')) {
                $table->decimal('before_productandbatch_discount', 20, 2)->nullable()->after('price');
            }
        });

        Schema::table('order_details', function (Blueprint $table) {
            if (!Schema::hasColumn('order_details', 'before_productandbatch_discount')) {
                $table->decimal('before_productandbatch_discount', 20, 2)->nullable()->after('price');
            }
        });

        if (Schema::hasColumn('carts', 'before_productandbatch_discount')) {
            DB::table('carts')
                ->whereNull('before_productandbatch_discount')
                ->update(['before_productandbatch_discount' => DB::raw('COALESCE(sale_price, price, 0)')]);
        }

        if (Schema::hasColumn('order_details', 'before_productandbatch_discount')) {
            DB::table('order_details')
                ->whereNull('before_productandbatch_discount')
                ->update(['before_productandbatch_discount' => DB::raw('COALESCE(sale_price, price, 0)')]);
        }
    }

    public function down()
    {
        Schema::table('order_details', function (Blueprint $table) {
            if (Schema::hasColumn('order_details', 'before_productandbatch_discount')) {
                $table->dropColumn('before_productandbatch_discount');
            }
        });

        Schema::table('carts', function (Blueprint $table) {
            if (Schema::hasColumn('carts', 'before_productandbatch_discount')) {
                $table->dropColumn('before_productandbatch_discount');
            }
        });
    }
}
