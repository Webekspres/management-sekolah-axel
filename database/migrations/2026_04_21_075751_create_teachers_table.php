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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->references('id');
            $table->string('nip')->nullable();
            $table->string('name');
            $table->string('place_of_birth');
            $table->dateTime('date_of_birth');
            $table->string('address');
            $table->boolean('employment_status')->default(true); #ask: defined enum atau bisa di CRUD?
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
