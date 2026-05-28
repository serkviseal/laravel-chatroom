<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete()->after('id');
            $table->foreignId('thread_id')->nullable()->constrained('messages')->nullOnDelete()->after('workspace_id');
            $table->boolean('is_thread_reply')->default(false)->after('thread_id');
            $table->boolean('is_pinned')->default(false)->after('is_thread_reply');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropForeign(['thread_id']);
            $table->dropColumn(['workspace_id', 'thread_id', 'is_thread_reply', 'is_pinned']);
        });
    }
};
