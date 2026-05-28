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
        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->enum('provider', ['meta', 'twilio'])->default('meta')->after('is_active');
            $table->string('account_sid')->nullable()->after('provider'); // Twilio Account SID
            $table->string('from_number')->nullable()->after('account_sid'); // e.g. whatsapp:+14155238886
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->dropColumn(['provider', 'account_sid', 'from_number']);
        });
    }
};
