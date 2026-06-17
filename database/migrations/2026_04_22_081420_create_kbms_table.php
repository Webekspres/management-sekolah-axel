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
        Schema::create('kbms', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('schedule_id')->constrained('schedules');
            $table->foreignUlid('lesson_plan_id')->constrained('lesson_plans');
            $table->date('date');
            $table->text('process_note');
            $table->text('problem_note')->nullable();
            $table->text('solution_note')->nullable();
            $table->string('documentation_path')->nullable();
            $table->enum('status', ['DRAFT', 'PENDING', 'REVISED', 'APPROVED'])->default('PENDING');
            $table->text('revision_note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kbms');
    }
};
