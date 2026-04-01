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
        Schema::create('road_signs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('road_sign_type_id')->constrained()->onDelete('cascade');
            $table->json('name');
            $table->integer('format')->default(1);
            $table->string('code')->nullable();
            $table->integer('status')->default(1);
            $table->string('image')->nullable();
            $table->longText('description')->nullable();
            $table->json('content')->nullable();
            $table->integer('order_column')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('road_signs');
    }
};
