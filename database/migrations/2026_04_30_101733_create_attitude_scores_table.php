<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attitude_scores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_id')->constrained('students');
            $table->foreignUlid('academic_year_id')->constrained('academic_years');
            $table->string('aspect', 100);
            $table->decimal('score', 5, 2);
            $table->text('description')->nullable();

            $table->unique(['student_id', 'academic_year_id', 'aspect'], 'uq_attitude');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attitude_scores');
    }
};
