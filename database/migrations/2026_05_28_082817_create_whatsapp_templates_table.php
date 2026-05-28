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
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_account_id')->constrained()->cascadeOnDelete();
            $table->string('template_name');
            $table->string('language')->default('en_US');
            $table->enum('category', ['marketing', 'utility', 'authentication'])->default('utility');
            $table->json('components');  // header, body, footer, buttons
            $table->enum('status', ['pending', 'approved', 'rejected', 'paused'])->default('pending');
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['whatsapp_account_id', 'template_name', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
