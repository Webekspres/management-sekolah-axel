<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rapors', function (Blueprint $table) {
            $table->enum('status', ['DRAFT', 'FINALIZED', 'APPROVED'])->default('DRAFT')->after('file_path');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->text('rejection_note')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('rapors', function (Blueprint $table) {
            $table->dropColumn(['status', 'approved_at', 'rejection_note']);
        });
    }
};
