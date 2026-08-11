<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old minimal analyses table
        Schema::dropIfExists('analyses');

        Schema::create('analyses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('repository_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Meta
            $table->string('model_used')->nullable();
            $table->integer('prompt_tokens')->nullable();
            $table->integer('completion_tokens')->nullable();
            $table->string('status')->default('pending'); // pending|processing|completed|failed
            $table->text('error_message')->nullable();

            // Scores
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('difficulty')->nullable();       // beginner|intermediate|advanced|expert
            $table->string('portfolio_level')->nullable();  // junior|mid|senior|staff|principal
            $table->unsignedTinyInteger('recruiter_rating')->nullable();
            $table->string('estimated_experience')->nullable();
            $table->unsignedTinyInteger('hiring_probability')->nullable();
            $table->string('market_readiness')->nullable();

            // Analysis content (JSON arrays)
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('architecture_review')->nullable();
            $table->json('security_review')->nullable();
            $table->json('performance_review')->nullable();
            $table->json('code_style_review')->nullable();
            $table->json('missing_features')->nullable();

            // Career content
            $table->json('resume_suggestions')->nullable();
            $table->json('cv_suggestions')->nullable();
            $table->json('linkedin_suggestions')->nullable();
            $table->json('interview_questions')->nullable();
            $table->json('best_companies')->nullable();
            $table->json('improvement_roadmap')->nullable();

            // Raw AI response for debugging
            $table->longText('raw_response')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['repository_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');

        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
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
};
