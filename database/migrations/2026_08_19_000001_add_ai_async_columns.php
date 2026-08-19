<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Replaces TutorPrompt::TRIMMED_NOTICE once the queued summariser
            // has run: what the dropped turns actually covered, rather than an
            // apology for having dropped them.
            $table->text('summary')->nullable()->after('title');

            // The summary covers every message up to and including this id, so
            // a later trim knows what still needs summarising.
            $table->unsignedBigInteger('summarised_through_message_id')->nullable()->after('summary');
        });

        Schema::table('messages', function (Blueprint $table) {
            // Wall time for the whole generation. Needed for the p95 latency
            // metric — there is nowhere else to derive it from, since a stream
            // has no single response time in the access log.
            $table->unsignedInteger('duration_ms')->nullable()->after('stop_reason');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['summary', 'summarised_through_message_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('duration_ms');
        });
    }
};
