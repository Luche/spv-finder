<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE supervisors DROP INDEX fulltext_name');
        DB::statement('ALTER TABLE supervisors ADD FULLTEXT fulltext_name_topics (name, specific_topics)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE supervisors DROP INDEX fulltext_name_topics');
        DB::statement('ALTER TABLE supervisors ADD FULLTEXT fulltext_name (name)');
    }
};
