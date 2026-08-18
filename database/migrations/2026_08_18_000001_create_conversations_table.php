<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            // Forward seam: always null in v1. There is no video page yet, so
            // threads are course-scoped; flipping on per-lesson threads later
            // needs no migration.
            $table->foreignId('video_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        // Two PARTIAL unique indexes, not one composite index.
        //
        // A plain UNIQUE(user_id, course_id, video_id) does NOT make load-or-create
        // race-safe here: SQL treats NULLs as distinct, so with video_id null on
        // every v1 row the index permits unlimited duplicates. Verified on both
        // SQLite and Postgres.
        //
        // Splitting on the null-ness of video_id enforces "one thread per
        // (user, course)" today and "one per (user, course, video)" once
        // per-lesson threads ship. Identical syntax on Postgres and SQLite.
        DB::statement(
            'CREATE UNIQUE INDEX conversations_user_course_unique
             ON conversations (user_id, course_id) WHERE video_id IS NULL'
        );

        DB::statement(
            'CREATE UNIQUE INDEX conversations_user_course_video_unique
             ON conversations (user_id, course_id, video_id) WHERE video_id IS NOT NULL'
        );
    }

    public function down(): void
    {
        // Indexes are dropped with the table on both drivers.
        Schema::dropIfExists('conversations');
    }
};
