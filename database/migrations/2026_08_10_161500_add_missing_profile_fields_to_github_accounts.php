<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('github_accounts', function (Blueprint $table) {
            // Add missing GitHub profile fields
            if (!Schema::hasColumn('github_accounts', 'public_gists')) {
                $table->integer('public_gists')->default(0)->after('public_repos');
            }
            
            if (!Schema::hasColumn('github_accounts', 'github_updated_at')) {
                $table->timestamp('github_updated_at')->nullable()->after('github_created_at');
            }
            
            if (!Schema::hasColumn('github_accounts', 'twitter_username')) {
                $table->string('twitter_username')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('github_accounts', 'hireable')) {
                $table->string('hireable')->nullable()->after('twitter_username');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('github_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'public_gists',
                'github_updated_at',
                'twitter_username',
                'hireable',
            ]);
        });
    }
};