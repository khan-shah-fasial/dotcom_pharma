<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSupportFieldsToContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('contacts')) {
            return;
        }

        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'type')) {
                $table->string('type')->nullable()->after('id');
            }

            if (!Schema::hasColumn('contacts', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('image');
            }

            if (!Schema::hasColumn('contacts', 'url')) {
                $table->text('url')->nullable()->after('product_id');
            }

            if (!Schema::hasColumn('contacts', 'attachment')) {
                $table->string('attachment')->nullable()->after('url');
            }

            if (!Schema::hasColumn('contacts', 'data')) {
                $table->json('data')->nullable()->after('attachment');
            }

            if (!Schema::hasColumn('contacts', 'status')) {
                $table->string('status')->nullable()->after('data');
            }

            if (!Schema::hasColumn('contacts', 'review')) {
                $table->unsignedTinyInteger('review')->nullable()->after('status');
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
        if (!Schema::hasTable('contacts')) {
            return;
        }

        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'review')) {
                $table->dropColumn('review');
            }
            if (Schema::hasColumn('contacts', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('contacts', 'data')) {
                $table->dropColumn('data');
            }
            if (Schema::hasColumn('contacts', 'attachment')) {
                $table->dropColumn('attachment');
            }
            if (Schema::hasColumn('contacts', 'url')) {
                $table->dropColumn('url');
            }
            if (Schema::hasColumn('contacts', 'product_id')) {
                $table->dropColumn('product_id');
            }
            if (Schema::hasColumn('contacts', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
}

