<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupsTables extends Migration
{
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->increments('id'); // INT UNSIGNED
            $table->unsignedInteger('parent_id')->default(0);
            $table->integer('level')->default(0);
            $table->string('name', 50);
            $table->integer('order_level')->default(0);
            $table->double('commision_rate', 8, 2)->default(0);
            $table->string('banner', 100)->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('cover_image', 100)->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('top')->default(false);
            $table->boolean('digital')->default(false);
            $table->string('slug')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });

        Schema::create('group_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('group_id');
            $table->string('name', 50);
            $table->string('lang', 100);
            $table->timestamps();

            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onDelete('cascade');
        });

        Schema::create('product_groups', function (Blueprint $table) {
            $table->unsignedInteger('product_id'); // 🔥 MATCH products.id
            $table->unsignedInteger('group_id');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->cascadeOnDelete();
        });


        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('group_id')->nullable()->after('category_id');

            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::dropIfExists('product_groups');
        Schema::dropIfExists('group_translations');
        Schema::dropIfExists('groups');
    }
}
