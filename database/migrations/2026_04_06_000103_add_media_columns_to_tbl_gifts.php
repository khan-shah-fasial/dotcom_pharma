<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tbl_gifts', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_gifts', 'photos')) {
                $table->text('photos')->nullable()->after('description');
            }
            if (!Schema::hasColumn('tbl_gifts', 'thumbnail_id')) {
                $table->unsignedBigInteger('thumbnail_id')->nullable()->after('photos');
            }
        });
    }

    public function down()
    {
        Schema::table('tbl_gifts', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_gifts', 'thumbnail_id')) {
                $table->dropColumn('thumbnail_id');
            }
            if (Schema::hasColumn('tbl_gifts', 'photos')) {
                $table->dropColumn('photos');
            }
        });
    }
};
