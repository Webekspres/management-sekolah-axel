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
        Schema::create('students', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('class_id')->constrained('classes');
            $table->string('nipd')->unique();
            $table->string('nisn')->unique();
            $table->string('nik')->unique();
            $table->string('kk_number')->nullable();
            $table->string('birth_cert_number')->nullable();
            $table->string('religion')->nullable();
            $table->string('school_code')->nullable();
            $table->string('student_phone')->nullable();
            $table->string('special_needs')->nullable();
            $table->string('house_number')->nullable();
            $table->string('rt')->nullable();
            $table->string('rw')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('father_name')->nullable();
            $table->string('father_phone')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_phone')->nullable();
            $table->date('admission_date')->nullable();
            $table->string('origin_school')->nullable();
            $table->date('diploma_date')->nullable();
            $table->string('diploma_number')->nullable();
            $table->decimal('custom_spp', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
