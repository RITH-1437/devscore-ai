<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('github_accounts', function (Blueprint $table) {
            $table->string('name')->nullable()->after('username');
            $table->text('bio')->nullable()->after('name');
            $table->string('company')->nullable()->after('bio');
            $table->string('location')->nullable()->after('company');
            $table->string('blog')->nullable()->after('location');
            $table->string('email')->nullable()->after('blog');
            $table->integer('public_repos')->default(0)->after('following');
            $table->timestamp('github_created_at')->nullable()->after('public_repos');
        });
    }

    public function down(): void
    {
        Schema::table('github_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'name', 'bio', 'company', 'location',
                'blog', 'email', 'public_repos', 'github_created_at',
            ]);
        });
    }
};
