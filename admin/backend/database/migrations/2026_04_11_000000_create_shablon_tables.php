<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. New Question table for Shablons
        Schema::create('shablon_questions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('json_id')->unsigned()->index();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        // 2. Question Translations
        Schema::create('shablon_question_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shablon_question_id')->constrained('shablon_questions')->cascadeOnDelete();
            $table->enum('language', ['uz', 'ru', 'kiril']);
            $table->text('question_text');
            $table->timestamps();
        });

        // 3. Options
        Schema::create('shablon_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shablon_question_id')->constrained('shablon_questions')->cascadeOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        // 4. Option Translations
        Schema::create('shablon_option_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shablon_option_id')->constrained('shablon_options')->cascadeOnDelete();
            $table->enum('language', ['uz', 'ru', 'kiril']);
            $table->text('option_text');
            $table->timestamps();
        });

        // 5. Answers (Explanations)
        Schema::create('shablon_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shablon_question_id')->constrained('shablon_questions')->cascadeOnDelete();
            $table->enum('language', ['uz', 'ru', 'kiril']);
            $table->text('description')->nullable();
            $table->string('video_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shablon_answers');
        Schema::dropIfExists('shablon_option_translations');
        Schema::dropIfExists('shablon_options');
        Schema::dropIfExists('shablon_question_translations');
        Schema::dropIfExists('shablon_questions');
    }
};
