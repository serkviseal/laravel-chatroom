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
        Schema::table('rooms', function (Blueprint $table) {
            $table->enum('source', ['internal', 'whatsapp', 'whatsapp_group'])->default('internal')->after('type');
            $table->boolean('is_inbox_item')->default(false)->after('source');
            $table->json('contact_metadata')->nullable()->after('is_inbox_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['source', 'is_inbox_item', 'contact_metadata']);
        });
    }
};
