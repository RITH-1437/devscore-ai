<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repositories', function (Blueprint $table) {
            // New core fields
            $table->string('full_name')->nullable()->after('name');
            $table->string('clone_url')->nullable()->after('html_url');
            $table->string('default_branch')->nullable()->after('clone_url');
            $table->json('topics')->nullable()->after('default_branch');
            $table->string('license')->nullable()->after('topics');
            $table->boolean('is_private')->default(false)->after('license');
            $table->boolean('is_fork')->default(false)->after('is_private');
            $table->boolean('is_archived')->default(false)->after('is_fork');
            $table->integer('watchers')->default(0)->after('open_issues');
            $table->integer('size')->default(0)->after('watchers');
            $table->timestamp('pushed_at')->nullable()->after('size');
            $table->timestamp('github_created_at')->nullable()->after('pushed_at');

            // User preference fields
            $table->boolean('is_featured')->default(false)->after('ai_analyzed_at');
            $table->boolean('is_pinned')->default(false)->after('is_featured');

            // Analysis status
            $table->string('analysis_status')->default('pending')->after('is_pinned');

            // Add index for performance
            $table->index(['github_account_id', 'is_private']);
            $table->index('analysis_status');
            $table->index('stars');
        });
    }

    public function down(): void
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->dropIndex(['github_account_id', 'is_private']);
            $table->dropIndex(['analysis_status']);
            $table->dropIndex(['stars']);

            $table->dropColumn([
                'full_name', 'clone_url', 'default_branch', 'topics',
                'license', 'is_private', 'is_fork', 'is_archived',
                'watchers', 'size', 'pushed_at', 'github_created_at',
                'is_featured', 'is_pinned', 'analysis_status',
            ]);
        });
    }
};
