<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only add FULLTEXT index on MySQL/MariaDB; SQLite and PostgreSQL use
        // their own search mechanisms and don't support the MySQL FULLTEXT syntax.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE messages ADD FULLTEXT INDEX ft_messages_body (body)');
            DB::statement('ALTER TABLE rooms ADD FULLTEXT INDEX ft_rooms_name_desc (name, description)');
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE messages DROP INDEX ft_messages_body');
            DB::statement('ALTER TABLE rooms DROP INDEX ft_rooms_name_desc');
        }
    }
};
