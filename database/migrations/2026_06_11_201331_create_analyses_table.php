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
        Schema::create('analyses', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('portfolio_score')->nullable();

            $table->integer('backend_score')->nullable();

            $table->integer('frontend_score')->nullable();

            $table->integer('database_score')->nullable();

            $table->integer('devops_score')->nullable();

            $table->json('strengths')->nullable();

            $table->json('weaknesses')->nullable();

            $table->json('recommendations')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
