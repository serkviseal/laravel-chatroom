<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('avatar_url')->nullable();
            $table->string('description')->nullable();
            $table->string('auth_token', 64)->unique();  // Bearer token for incoming webhooks
            $table->string('outgoing_webhook_url')->nullable(); // URL to POST events to
            $table->json('subscribed_events')->default('["message.created"]'); // which events to forward
            $table->json('channel_ids')->nullable(); // null = all channels in workspace
            $table->boolean('is_active')->default(true);
            $table->string('provider')->nullable(); // 'whatsapp', 'slack', 'custom', etc.
            $table->json('provider_config')->nullable(); // provider-specific config (phone IDs, etc.)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bots');
    }
};
