<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('name', 'idx_products_name');
            $table->index(['drug_name', 'role_label'], 'idx_products_drug_role');
            $table->index(['tags'], 'idx_products_tags');
            $table->index(['description'], 'idx_products_description');
        });

        Schema::table('product_translations', function (Blueprint $table) {
            $table->index('name', 'idx_prod_trans_name');
            $table->index('description', 'idx_prod_trans_description');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('name', 'idx_categories_name_btree');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->index('category_id', 'idx_pc_category');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_name');
            $table->dropIndex('idx_products_drug_role');
            $table->dropIndex('idx_products_tags');
            $table->dropIndex('idx_products_description');
        });

        Schema::table('product_translations', function (Blueprint $table) {
            $table->dropIndex('idx_prod_trans_name');
            $table->dropIndex('idx_prod_trans_description');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_name_btree');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropIndex('idx_pc_category');
        });
    }
};
