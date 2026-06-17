<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supervisors', function (Blueprint $table) {
            $table->id();
            $table->string('kddsn')->unique();
            $table->string('name');
            $table->string('email');
            $table->string('scholar_url')->nullable();
            $table->text('specific_topics')->nullable();
            $table->unsignedInteger('active_titles')->default(0);
            $table->boolean('is_global_class')->default(false);
            $table->unsignedBigInteger('views_total')->default(0);
            $table->unsignedBigInteger('contacts_total')->default(0);
            $table->timestamps();
        });

        // FULLTEXT index for name search
        DB::statement('ALTER TABLE supervisors ADD FULLTEXT fulltext_name (name)');
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisors');
    }
};
