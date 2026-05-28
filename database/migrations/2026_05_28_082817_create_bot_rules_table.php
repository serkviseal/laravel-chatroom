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
        Schema::create('bot_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->nullable()->constrained('bots')->nullOnDelete();
            $table->string('name');
            $table->enum('trigger_type', ['keyword', 'regex', 'intent', 'always', 'outside_hours', 'first_contact', 'unassigned_timeout']);
            $table->string('trigger_value')->nullable(); // the keyword/regex/intent value
            $table->enum('action_type', ['reply', 'assign_agent', 'assign_bot', 'add_label', 'close', 'send_template', 'escalate_ai']);
            $table->json('action_value')->nullable(); // reply text, agent_id, template_name, etc.
            $table->unsignedSmallInteger('priority')->default(100); // lower = higher priority
            $table->boolean('is_active')->default(true);
            $table->boolean('stop_on_match')->default(true); // don't evaluate further rules
            $table->timestamps();

            $table->index(['workspace_id', 'is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_rules');
    }
};
