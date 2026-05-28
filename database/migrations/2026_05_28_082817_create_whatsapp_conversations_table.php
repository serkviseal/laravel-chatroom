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
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('whatsapp_contacts')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete(); // mirrors a chatroom channel
            $table->string('wa_conversation_id')->nullable()->index();
            $table->enum('status', ['open', 'pending', 'snoozed', 'resolved', 'spam'])->default('open');
            $table->enum('conversation_type', ['contact', 'group'])->default('contact');
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_bot_id')->nullable()->constrained('bots')->nullOnDelete();
            $table->timestamp('window_expires_at')->nullable(); // 24h window
            $table->timestamp('first_reply_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('snoozed_until')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['assigned_agent_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
