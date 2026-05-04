<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_skill_scores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_id')->constrained('students');
            $table->foreignUlid('subject_id')->constrained('subjects');
            $table->foreignUlid('academic_year_id')->constrained('academic_years');
            $table->decimal('knowledge_score', 5, 2)->nullable();
            $table->char('knowledge_predicate', 1)->nullable();
            $table->text('knowledge_description')->nullable();
            $table->decimal('skill_score', 5, 2)->nullable();
            $table->char('skill_predicate', 1)->nullable();
            $table->text('skill_description')->nullable();

            $table->unique(['student_id', 'subject_id', 'academic_year_id'], 'uq_ks');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_skill_scores');
    }
};
