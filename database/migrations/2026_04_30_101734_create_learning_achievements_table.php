<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_achievements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_id')->constrained('students');
            $table->foreignUlid('subject_id')->constrained('subjects');
            $table->foreignUlid('academic_year_id')->constrained('academic_years');
            $table->text('topic_coverage')->nullable();
            $table->text('notes')->nullable();

            $table->unique(['student_id', 'subject_id', 'academic_year_id'], 'uq_la');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_achievements');
    }
};
