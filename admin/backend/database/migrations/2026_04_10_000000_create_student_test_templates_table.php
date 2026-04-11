<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_test_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('passing_score')->nullable();
            $table->timestamps();
        });

        Schema::create('student_template_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('student_test_templates')->cascadeOnDelete();
            // Use unsignedBigInteger to match typical test_questions id, avoiding foreign key typing issues if not exact
            $table->unsignedBigInteger('question_id');
            $table->foreign('question_id')->references('id')->on('test_questions')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_template_questions');
        Schema::dropIfExists('student_test_templates');
    }
};
