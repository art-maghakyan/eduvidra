<?php

namespace App\Services\Ai;

use Generator;

/**
 * The seam between the application and Anthropic. Nothing outside
 * App\Services\Ai may touch the SDK directly — swap the binding in
 * AppServiceProvider and the whole feature runs offline for $0.
 */
interface ChatClient
{
    /**
     * Stream one assistant turn.
     *
     * Yields text fragments in order. The generator's *return value* is a
     * ChatResult with the token accounting, so callers do:
     *
     *     $stream = $client->stream($system, $messages);
     *     foreach ($stream as $chunk) { ... }
     *     $result = $stream->getReturn();
     *
     * @param  string  $system  System prompt. User text is never concatenated
     *                          into this — it stays in $messages.
     * @param  list<array{role: string, content: string}>  $messages
     * @return Generator<int, string, mixed, ChatResult>
     */
    public function stream(string $system, array $messages): Generator;

    /**
     * Input tokens the given prompt would cost, without generating a reply.
     *
     * Anthropic's count_tokens endpoint is free and sits on a rate-limit pool
     * separate from message creation, so prompt assembly can be measured
     * exactly at no cost. Step 7 uses this to cap replayed history by tokens
     * rather than by message count.
     *
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function countTokens(string $system, array $messages): int;
}
