<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('note_types')) {
            return;
        }

        Schema::create('note_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 50)->unique();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('note_types')->insert([
            ['name' => 'Refund', 'slug' => 'refund', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Warranty', 'slug' => 'warranty', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('note_types');
    }
};
