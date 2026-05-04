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
        Schema::create('rapors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_id')->constrained('students');
            $table->foreignUlid('academic_year_id')->constrained('academic_years');
            $table->string('file_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rapors');
    }
};
