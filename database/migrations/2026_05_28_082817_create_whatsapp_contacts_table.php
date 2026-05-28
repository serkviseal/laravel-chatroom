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
        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_account_id')->constrained()->cascadeOnDelete();
            $table->string('phone');
            $table->string('display_name')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('locale')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('opt_in_at')->nullable();
            $table->timestamp('opt_out_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['whatsapp_account_id', 'phone']);
            $table->index(['workspace_id', 'phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_contacts');
    }
};
