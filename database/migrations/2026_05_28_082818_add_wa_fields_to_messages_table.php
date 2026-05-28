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
        Schema::table('messages', function (Blueprint $table) {
            $table->string('wa_message_id', 100)->nullable()->index()->after('room_id');
            $table->enum('origin', ['internal', 'whatsapp'])->default('internal')->after('wa_message_id');
            $table->enum('delivery_status', ['sent', 'delivered', 'read', 'failed'])->nullable()->after('origin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['wa_message_id', 'origin', 'delivery_status']);
        });
    }
};
