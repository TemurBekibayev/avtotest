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
        // Drop the pivot table if it exists to ensure we start with the correct foreign keys
        Schema::dropIfExists('student_template_questions');

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
        Schema::dropIfExists('student_template_questions');
    }
};
