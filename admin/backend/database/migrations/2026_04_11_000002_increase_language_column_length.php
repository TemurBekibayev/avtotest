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
        Schema::table('shablon_question_translations', function (Blueprint $table) {
            $table->string('language', 10)->change();
        });

        Schema::table('shablon_option_translations', function (Blueprint $table) {
            $table->string('language', 10)->change();
        });

        Schema::table('shablon_answers', function (Blueprint $table) {
            $table->string('language', 10)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shablon_question_translations', function (Blueprint $table) {
            $table->string('language', 5)->change();
        });

        Schema::table('shablon_option_translations', function (Blueprint $table) {
            $table->string('language', 5)->change();
        });

        Schema::table('shablon_answers', function (Blueprint $table) {
            $table->string('language', 5)->change();
        });
    }
};
