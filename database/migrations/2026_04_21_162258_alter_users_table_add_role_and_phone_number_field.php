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
        Schema::table("users", function (Blueprint $table) {
            $table->enum("role", ['super_admin', 'kepala_sekolah', 'staff_tu', 'guru', 'siswa']);
            $table->enum('gender', ['L', 'P']);
            $table->string("phone_number")->nullable();
            $table->boolean("is_active")->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
