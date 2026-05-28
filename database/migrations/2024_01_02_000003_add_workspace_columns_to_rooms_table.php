<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete()->after('id');
            $table->enum('type', ['public', 'private', 'announcement', 'dm', 'group_dm'])->default('public')->after('description');
            $table->boolean('is_archived')->default(false)->after('type');
            $table->string('topic')->nullable()->after('is_archived');
            $table->dropUnique(['name']);
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropColumn(['workspace_id', 'type', 'is_archived', 'topic']);
            $table->unique('name');
        });
    }
};
