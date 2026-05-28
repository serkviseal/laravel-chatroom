<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_workspace_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->enum('notification_preference', ['all', 'mentions', 'nothing'])->default('all');
            $table->enum('status', ['online', 'away', 'dnd', 'offline'])->default('offline');
            $table->string('status_emoji')->nullable();
            $table->string('status_text')->nullable();
            $table->unique(['user_id', 'workspace_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_workspace_preferences');
    }
};
