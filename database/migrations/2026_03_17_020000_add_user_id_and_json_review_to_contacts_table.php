<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUserIdAndJsonReviewToContactsTable extends Migration
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
            if (!Schema::hasColumn('contacts', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }

            if (Schema::hasColumn('contacts', 'review') && !Schema::hasColumn('contacts', 'review_meta')) {
                $table->json('review_meta')->nullable()->after('status');
            }
        });

        // Migrate any existing numeric review values into JSON structure.
        if (Schema::hasColumn('contacts', 'review') && Schema::hasColumn('contacts', 'review_meta')) {
            // For databases that support JSON_OBJECT (MySQL / MariaDB)
            try {
                DB::statement("
                    UPDATE contacts
                    SET review_meta = JSON_OBJECT('rating', review, 'comment', NULL)
                    WHERE review IS NOT NULL AND review_meta IS NULL
                ");
            } catch (\Throwable $e) {
                // Fallback for drivers without JSON_OBJECT support: do a best-effort PHP migration.
                $rows = DB::table('contacts')
                    ->whereNotNull('review')
                    ->whereNull('review_meta')
                    ->get(['id', 'review']);

                foreach ($rows as $row) {
                    $rating = is_numeric($row->review) ? (int) $row->review : null;
                    DB::table('contacts')
                        ->where('id', $row->id)
                        ->update([
                            'review_meta' => $rating !== null
                                ? json_encode(['rating' => $rating, 'comment' => null])
                                : null,
                        ]);
                }
            }

            Schema::table('contacts', function (Blueprint $table) {
                if (Schema::hasColumn('contacts', 'review')) {
                    $table->dropColumn('review');
                }
            });

            Schema::table('contacts', function (Blueprint $table) {
                if (Schema::hasColumn('contacts', 'review_meta') && !Schema::hasColumn('contacts', 'review')) {
                    $table->renameColumn('review_meta', 'review');
                }
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
        if (!Schema::hasTable('contacts')) {
            return;
        }

        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'user_id')) {
                $table->dropColumn('user_id');
            }

            // Best-effort: change review back to unsignedTinyInteger if currently JSON.
            if (Schema::hasColumn('contacts', 'review')) {
                $table->unsignedTinyInteger('review')->nullable()->change();
            }
        });
    }
}

