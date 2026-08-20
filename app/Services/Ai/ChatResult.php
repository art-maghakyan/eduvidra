<?php

namespace App\Services\Ai;

/**
 * Terminal metadata for one assistant turn, returned by ChatClient::stream().
 *
 * The token counts map 1:1 onto the `messages` table so cost can be audited per
 * turn. When prompt caching is eventually enabled, total input is
 * inputTokens + cacheCreationTokens + cacheReadTokens — never inputTokens alone.
 */
class ChatResult
{
    public function __construct(
        public readonly string $model,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly int $cacheCreationTokens = 0,
        public readonly int $cacheReadTokens = 0,
        public readonly ?string $stopReason = null,
    ) {}
}
