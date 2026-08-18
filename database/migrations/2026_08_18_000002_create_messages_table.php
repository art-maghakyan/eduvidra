<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->text('content');

            // Token accounting exists from day one — retrofitting it later means
            // no history to analyse. cache_creation_tokens is here even though
            // caching is inert at our prompt size (4,096-token minimum on Haiku
            // 4.5 vs our ~700-token prefix), because total input is
            // input + cache_creation + cache_read and all three must be summed.
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cache_creation_tokens')->default(0);
            $table->unsignedInteger('cache_read_tokens')->default(0);

            $table->string('model')->nullable();
            $table->string('stop_reason')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
