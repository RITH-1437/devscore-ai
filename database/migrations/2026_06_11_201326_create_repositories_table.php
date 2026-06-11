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
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('github_account_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->bigInteger('repo_id')->unique();

            $table->string('name');

            $table->text('description')->nullable();

            $table->string('language')->nullable();

            $table->integer('stars')->default(0);

            $table->integer('forks')->default(0);

            $table->integer('open_issues')->default(0);

            $table->string('html_url');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
