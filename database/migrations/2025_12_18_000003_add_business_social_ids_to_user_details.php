<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (! Schema::hasColumn('user_details', 'business_instagram_id')) {
                $table->string('business_instagram_id')->nullable()->after('website_business');
            }
            if (! Schema::hasColumn('user_details', 'business_facebook_id')) {
                $table->string('business_facebook_id')->nullable()->after('business_instagram_id');
            }
            if (! Schema::hasColumn('user_details', 'business_linkedin_id')) {
                $table->string('business_linkedin_id')->nullable()->after('business_facebook_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'business_linkedin_id')) {
                $table->dropColumn('business_linkedin_id');
            }
            if (Schema::hasColumn('user_details', 'business_facebook_id')) {
                $table->dropColumn('business_facebook_id');
            }
            if (Schema::hasColumn('user_details', 'business_instagram_id')) {
                $table->dropColumn('business_instagram_id');
            }
        });
    }
};
