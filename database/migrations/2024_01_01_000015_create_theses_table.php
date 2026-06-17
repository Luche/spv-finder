<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('theses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedTinyInteger('position'); // 1-5
            $table->timestamps();
        });

        DB::statement('ALTER TABLE theses ADD FULLTEXT fulltext_title (title)');
    }

    public function down(): void
    {
        Schema::dropIfExists('theses');
    }
};
