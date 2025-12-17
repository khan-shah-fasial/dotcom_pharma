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

            if (!Schema::hasColumn('product_stocks', 'product_exp_date')) {
                $table->string('product_exp_date')->nullable()->after('min_qty');
            }

            if (!Schema::hasColumn('product_stocks', 'qty_per_piece')) {
                $table->decimal('qty_per_piece', 12, 2)->nullable()->after('product_exp_date');
            }

            if (!Schema::hasColumn('product_stocks', 'qty_per_buffer_box')) {
                $table->integer('qty_per_buffer_box')->nullable()->after('product_exp_date');
            }

            if (!Schema::hasColumn('product_stocks', 'total_qty_per_case')) {
                $table->integer('total_qty_per_case')->nullable()->after('qty_per_buffer_box');
            }

            if (!Schema::hasColumn('product_stocks', 'weight_buffer_box')) {
                $table->decimal('weight_buffer_box', 12, 3)->nullable()->after('total_qty_per_case');
            }

            if (!Schema::hasColumn('product_stocks', 'weight_case')) {
                $table->decimal('weight_case', 12, 3)->nullable()->after('weight_buffer_box');
            }

            if (!Schema::hasColumn('product_stocks', 'buffer_length')) {
                $table->decimal('buffer_length', 12, 2)->nullable()->after('weight_case');
            }

            if (!Schema::hasColumn('product_stocks', 'buffer_width')) {
                $table->decimal('buffer_width', 12, 2)->nullable()->after('buffer_length');
            }

            if (!Schema::hasColumn('product_stocks', 'buffer_height')) {
                $table->decimal('buffer_height', 12, 2)->nullable()->after('buffer_width');
            }

            if (!Schema::hasColumn('product_stocks', 'case_length')) {
                $table->decimal('case_length', 12, 2)->nullable()->after('buffer_height');
            }

            if (!Schema::hasColumn('product_stocks', 'case_width')) {
                $table->decimal('case_width', 12, 2)->nullable()->after('case_length');
            }

            if (!Schema::hasColumn('product_stocks', 'case_height')) {
                $table->decimal('case_height', 12, 2)->nullable()->after('case_width');
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
            if (Schema::hasColumn('product_stocks', 'case_height')) {
                $table->dropColumn('case_height');
            }
            if (Schema::hasColumn('product_stocks', 'qty_per_piece')) {
                $table->dropColumn('qty_per_piece');
            }
            if (Schema::hasColumn('product_stocks', 'case_width')) {
                $table->dropColumn('case_width');
            }
            if (Schema::hasColumn('product_stocks', 'case_length')) {
                $table->dropColumn('case_length');
            }
            if (Schema::hasColumn('product_stocks', 'buffer_height')) {
                $table->dropColumn('buffer_height');
            }
            if (Schema::hasColumn('product_stocks', 'buffer_width')) {
                $table->dropColumn('buffer_width');
            }
            if (Schema::hasColumn('product_stocks', 'buffer_length')) {
                $table->dropColumn('buffer_length');
            }
            if (Schema::hasColumn('product_stocks', 'weight_case')) {
                $table->dropColumn('weight_case');
            }
            if (Schema::hasColumn('product_stocks', 'weight_buffer_box')) {
                $table->dropColumn('weight_buffer_box');
            }
            if (Schema::hasColumn('product_stocks', 'total_qty_per_case')) {
                $table->dropColumn('total_qty_per_case');
            }
            if (Schema::hasColumn('product_stocks', 'qty_per_buffer_box')) {
                $table->dropColumn('qty_per_buffer_box');
            }
            if (Schema::hasColumn('product_stocks', 'product_exp_date')) {
                $table->dropColumn('product_exp_date');
            }

            if (Schema::hasColumn('product_stocks', 'min_qty')) {
                $table->dropColumn('min_qty');
            }
        });
    }
}
