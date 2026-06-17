<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            // Drop the existing index before modifying the column
            $table->dropIndex('notifications_notifiable_type_notifiable_id_index');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            // Change notifiable_id from bigint to char(26) to support ULID user IDs
            $table->char('notifiable_id', 26)->change();
            $table->index(['notifiable_type', 'notifiable_id'], 'notifications_notifiable_type_notifiable_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_notifiable_type_notifiable_id_index');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->unsignedBigInteger('notifiable_id')->change();
            $table->index(['notifiable_type', 'notifiable_id'], 'notifications_notifiable_type_notifiable_id_index');
        });
    }
};
