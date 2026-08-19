<?php

namespace App\Services\Ai;

/**
 * What ConversationHistory decided to send, and what it left behind.
 */
class ReplayedHistory
{
    /**
     * @param  list<array{role: string, content: string}>  $messages  Oldest first.
     * @param  bool  $trimmed  Whether anything was dropped.
     * @param  int|null  $oldestKeptId  Message id the replay starts at, so the
     *                                  summariser knows exactly what it must cover.
     */
    public function __construct(
        public readonly array $messages,
        public readonly bool $trimmed,
        public readonly ?int $oldestKeptId,
    ) {}
}
