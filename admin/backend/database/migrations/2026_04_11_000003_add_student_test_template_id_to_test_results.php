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
        Schema::table('test_results', function (Blueprint $table) {
            // 1. Make old FK nullable (to allow results for new templates)
            $table->foreignId('test_template_id')->nullable()->change();
            
            // 2. Add new FK for student_test_templates
            $table->foreignId('student_test_template_id')
                ->nullable()
                ->after('test_template_id')
                ->constrained('student_test_templates')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_results', function (Blueprint $table) {
            $table->dropForeign(['student_test_template_id']);
            $table->dropColumn('student_test_template_id');
            $table->foreignId('test_template_id')->nullable(false)->change();
        });
    }
};
