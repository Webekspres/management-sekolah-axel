<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_kkms', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('subject_id')->constrained('subjects');
            $table->foreignUlid('level_id')->constrained('levels');
            $table->decimal('kkm', 5, 2)->default(70.00);

            $table->unique(['subject_id', 'level_id'], 'uq_kkm');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_kkms');
    }
};
