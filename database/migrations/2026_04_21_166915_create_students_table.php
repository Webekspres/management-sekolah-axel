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
            $table->id();
            $table->foreignId('user_id')->constrained()->references('id')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->references('id')->nullOnDelete();
            $table->string('nipd');
            $table->string('nisn');
            $table->string('nik');
            $table->string('no_kk');
            $table->string('no_akta');
            $table->timestamps();
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
