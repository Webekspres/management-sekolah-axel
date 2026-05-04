<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personality_scores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_id')->constrained('students');
            $table->foreignUlid('academic_year_id')->constrained('academic_years');
            $table->char('kedisiplinan', 1);
            $table->char('kerapihan', 1);
            $table->char('kerajinan', 1);
            $table->char('kesopanan', 1);

            $table->unique(['student_id', 'academic_year_id'], 'uq_ps');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personality_scores');
    }
};
