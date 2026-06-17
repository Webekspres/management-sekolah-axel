<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rapors', function (Blueprint $table) {
            $table->string('program')->nullable()->after('rejection_note');
            $table->string('sumber_pembelajaran', 500)->nullable()->after('program');
        });
    }

    public function down(): void
    {
        Schema::table('rapors', function (Blueprint $table) {
            $table->dropColumn(['program', 'sumber_pembelajaran']);
        });
    }
};
