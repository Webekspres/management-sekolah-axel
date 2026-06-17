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
        Schema::create('lesson_plan_materials', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('lesson_plan_id')->constrained('lesson_plans')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_filename');

            $table->index('lesson_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_materials');
    }
};
