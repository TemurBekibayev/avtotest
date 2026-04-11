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
        // 1. Force drop all shablon tables to solve enum/length issues
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('shablon_answers');
        Schema::dropIfExists('shablon_option_translations');
        Schema::dropIfExists('shablon_options');
        Schema::dropIfExists('shablon_question_translations');
        Schema::dropIfExists('shablon_questions');
        Schema::dropIfExists('student_template_questions');
        Schema::enableForeignKeyConstraints();

        // 2. Recreate with correct schema (string instead of enum)
        Schema::create('shablon_questions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('json_id')->unsigned()->index();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        Schema::create('shablon_question_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shablon_question_id')->constrained('shablon_questions')->cascadeOnDelete();
            $table->string('language', 10);
            $table->text('question_text');
            $table->timestamps();
        });

        Schema::create('shablon_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shablon_question_id')->constrained('shablon_questions')->cascadeOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        Schema::create('shablon_option_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shablon_option_id')->constrained('shablon_options')->cascadeOnDelete();
            $table->string('language', 10);
            $table->text('option_text');
            $table->timestamps();
        });

        Schema::create('shablon_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shablon_question_id')->constrained('shablon_questions')->cascadeOnDelete();
            $table->string('language', 10);
            $table->text('description')->nullable();
            $table->string('video_path')->nullable();
            $table->timestamps();
        });

        Schema::create('student_template_questions', function (Blueprint $table) {
            $table->foreignId('template_id')->constrained('student_test_templates')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('shablon_questions')->cascadeOnDelete();
            $table->primary(['template_id', 'question_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('shablon_answers');
        Schema::dropIfExists('shablon_option_translations');
        Schema::dropIfExists('shablon_options');
        Schema::dropIfExists('shablon_question_translations');
        Schema::dropIfExists('shablon_questions');
        Schema::dropIfExists('student_template_questions');
        Schema::enableForeignKeyConstraints();
    }
};
