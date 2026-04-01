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
        Schema::table('answers', function (Blueprint $table) {
            $table->text('answer_description')->nullable()->change();
            $table->text('answer_resource')->nullable()->change();
        });

        Schema::table('test_options', function (Blueprint $table) {
            $table->text('option')->change();
        });

        Schema::table('question_translations', function (Blueprint $table) {
            $table->text('question')->change();
        });

        Schema::table('test_questions', function (Blueprint $table) {
            $table->text('question_file')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->string('answer_description', 255)->nullable()->change();
            $table->string('answer_resource', 255)->nullable()->change();
        });

        Schema::table('test_options', function (Blueprint $table) {
            $table->string('option', 255)->change();
        });

        Schema::table('question_translations', function (Blueprint $table) {
            $table->string('question', 255)->change();
        });

        Schema::table('test_questions', function (Blueprint $table) {
            $table->string('question_file', 255)->nullable()->change();
        });
    }
};
