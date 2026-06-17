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
        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('teacher_id')->constrained('teachers');
            $table->foreignUlid('subject_id')->constrained('subjects');
            $table->string('topic');
            $table->string('file_path');
            $table->enum('status', ['DRAFT', 'PENDING', 'REVISED', 'APPROVED'])->default('DRAFT');
            $table->text('revision_note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_plans');
    }
};
