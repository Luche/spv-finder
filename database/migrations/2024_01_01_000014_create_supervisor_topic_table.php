<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supervisor_topic', function (Blueprint $table) {
            $table->foreignId('supervisor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->primary(['supervisor_id', 'topic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_topic');
    }
};
